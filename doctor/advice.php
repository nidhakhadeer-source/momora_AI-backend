<?php
// =============================================================================
// Momora AI - Doctor Advice & Consultation Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $patientIdRaw = $input['patientId'] ?? ($input['patient_id'] ?? null);
    $patientId = filter_var($patientIdRaw, FILTER_VALIDATE_INT);
    if ($patientId === false || $patientId <= 0) {
        respond(400, [
            'success' => false,
            'message' => 'patientId is required and must be a valid integer'
        ]);
    }
    $subject = trim($input['subject'] ?? 'Clinical advice');
    $adviceText = trim($input['adviceText'] ?? ($input['advice_text'] ?? ''));
    $saveCopy = isset($input['saveCopy']) ? ($input['saveCopy'] ? 1 : 0) : 1;

    if (empty($adviceText)) {
        respond(422, ['success' => false, 'message' => 'Please write your advice before sending']);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO doctor_advice (doctor_id, patient_id, subject, advice_text, save_copy)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$doctorId, $patientId, $subject, $adviceText, $saveCopy]);

    // Send notification to the patient ("New advice from Dr <name>")
    $rawDocName = trim($user['name'] ?? 'Doctor');
    $cleanDocName = preg_replace('/^Dr\.?\s+/i', '', $rawDocName);
    $notifTitle = "New advice from Dr {$cleanDocName}";

    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read)
         VALUES (?, ?, ?, ?, "REPORT_REVIEW", 1, 0)'
    );
    $notifStmt->execute([
        $patientId,
        $notifTitle,
        $subject,
        "View guidance from Dr {$cleanDocName}"
    ]);

    respond(201, [
        'success' => true,
        'message' => 'Advice sent securely to patient'
    ]);
}

// -----------------------------------------------------------------------------
// GET: Advice Form Data & Patient Context
// -----------------------------------------------------------------------------
$input = getInput();
$patientIdRaw = $input['patientId'] ?? ($input['patient_id'] ?? ($_GET['patientId'] ?? ($_GET['patient_id'] ?? null)));
$patientId = filter_var($patientIdRaw, FILTER_VALIDATE_INT);
if ($patientId === false || $patientId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'patientId is required and must be a valid integer'
    ]);
}

$stmt = $pdo->prepare(
    'SELECT u.name, p.patient_code, pd.gestational_weeks, pd.gestational_days, pd.estimated_due_date, pd.last_menstrual_period
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.id = ? AND u.role = "patient"'
);
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    respond(404, [
        'success' => false,
        'message' => 'Patient not found'
    ]);
}

$pName = trim($patient['name'] ?? '');
$words = explode(' ', $pName);
$firstName = !empty($words[0]) ? $words[0] : 'Patient';
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : (mb_strtoupper(mb_substr($pName, 0, 2)) ?: 'PT');
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));

// Compute real gestational age dynamically
$now = time();
$gestText = null;
if (!empty($patient['last_menstrual_period'])) {
    $lmpTime = strtotime($patient['last_menstrual_period']);
    if ($lmpTime !== false && $lmpTime <= $now) {
        $daysDiff = (int)floor(($now - $lmpTime) / 86400);
        $w = (int)floor($daysDiff / 7);
        $d = $daysDiff % 7;
        $gestText = "{$w}w {$d}d";
    }
}
if ($gestText === null && !empty($patient['estimated_due_date'])) {
    $eddTime = strtotime($patient['estimated_due_date']);
    if ($eddTime !== false) {
        $daysUntilDue = (int)floor(($eddTime - $now) / 86400);
        $daysElapsed = max(0, 280 - $daysUntilDue);
        $w = (int)floor($daysElapsed / 7);
        $d = $daysElapsed % 7;
        $gestText = "{$w}w {$d}d";
    }
}
if ($gestText === null) {
    $weeks = (int)($patient['gestational_weeks'] ?? 0);
    $days = (int)($patient['gestational_days'] ?? 0);
    $gestText = ($weeks > 0 || $days > 0) ? "{$weeks}w {$days}d" : "Not recorded";
}

$dueDateText = !empty($patient['estimated_due_date'])
    ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date'])))
    : 'Due date not set';

// Fetch patient's recent symptoms as a list with real timestamps
$sympStmt = $pdo->prepare(
    'SELECT id, symptom_name, severity, started_text, duration_text, ai_guidance, created_at
     FROM symptom_logs
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 10'
);
$sympStmt->execute([$patientId]);
$symptomLogs = $sympStmt->fetchAll();

$recentSymptoms = array_map(function($s) {
    $ts = strtotime($s['created_at']);
    return [
        'id' => (int)$s['id'],
        'symptomName' => $s['symptom_name'],
        'severity' => $s['severity'],
        'startedText' => $s['started_text'],
        'durationText' => $s['duration_text'],
        'aiGuidance' => $s['ai_guidance'],
        'createdAt' => $s['created_at'],
        'timestampFormatted' => date('d M Y, H:i', $ts),
        'relativeTime' => date('d M, H:i', $ts)
    ];
}, $symptomLogs);

$latestSymp = !empty($symptomLogs[0]) ? $symptomLogs[0] : null;

if ($latestSymp) {
    $sevStr = strtolower($latestSymp['severity']);
    $quote = "\"I've had {$sevStr} {$latestSymp['symptom_name']}. What can I try?\"";
    $msgTime = 'PATIENT MESSAGE · ' . date('d M, H:i', strtotime($latestSymp['created_at']));
    $defaultSubject = ucfirst($latestSymp['symptom_name']) . ': gentle steps & follow-up';
} else {
    $quote = "\"I've had mild symptoms. What can I try?\"";
    $msgTime = 'NO RECENT SYMPTOMS';
    $defaultSubject = 'Clinical guidance & follow-up';
}

$defaultAdvice = "Hi {$firstName}, try smaller meals and stay upright after eating. Let's review this at your upcoming visit. Contact the clinic sooner if symptoms persist or worsen.\n\nFor severe chest or abdominal pain, seek urgent maternity care or local emergency services.";

respond(200, [
    'success' => true,
    'data' => [
        'doctorName' => $user['name'],
        'patientName' => $pName,
        'patientInitials' => $initials,
        'gestationalAge' => $gestText,
        'dueDateText' => $dueDateText,
        'patientIdText' => $patientCode,
        'patientMessageTimeText' => $msgTime,
        'patientMessageQuote' => $quote,
        'recentSymptoms' => $recentSymptoms,
        'defaultSubject' => $defaultSubject,
        'defaultAdviceText' => $defaultAdvice
    ]
]);
