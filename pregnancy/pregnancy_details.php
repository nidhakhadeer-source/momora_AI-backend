<?php
// =============================================================================
// Momora AI - Pregnancy Details Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $edd = trim($input['estimatedDueDate'] ?? ($input['estimated_due_date'] ?? ''));
    $lmp = trim($input['lastMenstrualPeriod'] ?? ($input['last_menstrual_period'] ?? ''));

    $eddSql = !empty($edd) ? date('Y-m-d', strtotime($edd)) : null;
    $lmpSql = !empty($lmp) ? date('Y-m-d', strtotime($lmp)) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO pregnancy_details (user_id, estimated_due_date, last_menstrual_period)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
         estimated_due_date = COALESCE(VALUES(estimated_due_date), estimated_due_date),
         last_menstrual_period = COALESCE(VALUES(last_menstrual_period), last_menstrual_period)'
    );
    $stmt->execute([$userId, $eddSql, $lmpSql]);

    respond(200, [
        'success' => true,
        'message' => 'Pregnancy details updated successfully'
    ]);
}

// GET Pregnancy Details
$stmt = $pdo->prepare(
    'SELECT
        pd.estimated_due_date,
        pd.last_menstrual_period,
        pd.gestational_weeks,
        pd.gestational_days,
        pd.trimester,
        pd.next_visit_date,
        pd.next_visit_clinic,
        doc.name AS doctor_name,
        dp.specialty AS doctor_specialty
     FROM pregnancy_details pd
     LEFT JOIN profiles p ON p.user_id = pd.user_id
     LEFT JOIN users doc ON doc.id = p.assigned_doctor_id
     LEFT JOIN doctor_profiles dp ON dp.user_id = doc.id
     WHERE pd.user_id = ?'
);
$stmt->execute([$userId]);
$data = $stmt->fetch();

$weeks = $data['gestational_weeks'] ?? 24;
$days = $data['gestational_days'] ?? 3;
$gestationalAgeText = "{$weeks} weeks, {$days} days";

$trimesterMap = [1 => 'FIRST TRIMESTER', 2 => 'SECOND TRIMESTER', 3 => 'THIRD TRIMESTER'];
$trimesterText = $trimesterMap[$data['trimester'] ?? 2] ?? 'SECOND TRIMESTER';

$eddTimestamp = $data['estimated_due_date'] ? strtotime($data['estimated_due_date']) : strtotime('+15 weeks +4 days');
$now = time();
$diffDays = max(0, (int)(($eddTimestamp - $now) / 86400));
$diffWeeks = (int)($diffDays / 7);
$remainingDays = $diffDays % 7;
$countdownText = "{$diffWeeks} weeks, {$remainingDays} days until your estimated due date.";

$eddFormatted = date('d M Y', $eddTimestamp);
$lmpFormatted = $data['last_menstrual_period'] ? date('d M Y', strtotime($data['last_menstrual_period'])) : '09 May 2026';

$doctorName = $data['doctor_name'] ?: 'Dr Amelia Chen';
$specialty = $data['doctor_specialty'] ?: 'Obstetrics · Your assigned doctor';

$nextVisitFormatted = '29 Oct 2026 · 10:30';
if (!empty($data['next_visit_date'])) {
    $nextVisitFormatted = date('d M Y · H:i', strtotime($data['next_visit_date']));
}
$clinic = $data['next_visit_clinic'] ?: 'Willow Clinic';

respond(200, [
    'success' => true,
    'data' => [
        'trimesterText' => $trimesterText,
        'gestationalAgeText' => $gestationalAgeText,
        'countdownText' => $countdownText,
        'estimatedDueDate' => $eddFormatted,
        'lastMenstrualPeriod' => $lmpFormatted,
        'assignedDoctorName' => $doctorName,
        'assignedDoctorSpecialty' => $specialty,
        'nextVisitDate' => $nextVisitFormatted,
        'nextVisitClinic' => $clinic
    ]
]);

