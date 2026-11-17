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

    // Send notification to the patient
    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read)
         VALUES (?, "Doctor advice received", ?, "View guidance from Dr Amelia Chen", "REPORT_REVIEW", 1, 0)'
    );
    $notifStmt->execute([$patientId, "{$user['name']} replied: \"{$subject}\""]);

    respond(201, [
        'success' => true,
        'message' => 'Advice sent securely to patient'
    ]);
}

// GET Advice Form Data
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
    'SELECT u.name, p.patient_code, pd.gestational_weeks, pd.gestational_days, pd.estimated_due_date
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
$gestText = ($patient['gestational_weeks'] ?? 24) . 'w ' . ($patient['gestational_days'] ?? 3) . 'd';
$dueDateText = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due date not set';

// Find recent symptom query from patient
$sStmt = $pdo->prepare('SELECT symptom_name, started_text FROM symptom_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$sStmt->execute([$patientId]);
$symp = $sStmt->fetch();

$quote = $symp ? "\"I've had mild {$symp['symptom_name']}. What can I try?\"" : "\"I've had mild symptoms. What can I try?\"";
$msgTime = 'PATIENT MESSAGE · ' . date('d M, H:i');

$defaultSubject = $symp ? ucfirst($symp['symptom_name']) . ': gentle steps & follow-up' : 'Clinical guidance & follow-up';
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
        'defaultSubject' => $defaultSubject,
        'defaultAdviceText' => $defaultAdvice
    ]
]);

