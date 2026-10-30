<?php
// =============================================================================
// Momora AI - Doctor Advice & Consultation Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'doctor');
$doctorId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $patientId = filter_var($input['patientId'] ?? ($input['patient_id'] ?? 5), FILTER_VALIDATE_INT);
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
$patientId = filter_var($input['patientId'] ?? 5, FILTER_VALIDATE_INT);
if (!$patientId) $patientId = 5;

$stmt = $pdo->prepare(
    'SELECT u.name, p.patient_code, pd.gestational_weeks, pd.gestational_days, pd.estimated_due_date
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

$pName = $patient['name'] ?? 'Maya Patel';
$words = explode(' ', trim($pName));
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : 'MP';
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));
$gestText = ($patient['gestational_weeks'] ?? 24) . 'w ' . ($patient['gestational_days'] ?? 3) . 'd';
$dueDateText = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due 13 Feb 2027';

// Find recent symptom query from patient
$sStmt = $pdo->prepare('SELECT symptom_name, started_text FROM symptom_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$sStmt->execute([$patientId]);
$symp = $sStmt->fetch();

$quote = $symp ? "\"I've had mild {$symp['symptom_name']}. What can I try?\"" : "\"I've had mild heartburn after lunch. What can I try?\"";
$msgTime = 'PATIENT MESSAGE · ' . date('d M, H:i');

$defaultSubject = 'Heartburn: gentle steps & follow-up';
$defaultAdvice = "Hi {$words[0]}, try smaller meals and stay upright after eating. Let's review this at your upcoming visit. Contact the clinic sooner if symptoms persist or worsen.\n\nFor severe chest or abdominal pain, seek urgent maternity care or local emergency services.";

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

