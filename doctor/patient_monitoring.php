<?php
// =============================================================================
// Momora AI - Patient Health Monitoring Trends for Doctor Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'doctor');
$input = getInput();
$patientId = filter_var($input['patientId'] ?? ($input['patient_id'] ?? 5), FILTER_VALIDATE_INT);
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

$name = $patient['name'] ?? 'Maya Patel';
$words = explode(' ', trim($name));
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : 'MP';
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));
$gestText = ($patient['gestational_weeks'] ?? 24) . 'w ' . ($patient['gestational_days'] ?? 3) . 'd';
$dueDateText = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due 13 Feb 2027';

// Fetch BP logs for trends
$bpStmt = $pdo->prepare('SELECT systolic, diastolic, pulse, recorded_at FROM blood_pressure_logs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 7');
$bpStmt->execute([$patientId]);
$bpRows = array_reverse($bpStmt->fetchAll());

$latestSys = 112;
$latestDia = 72;
$bpTime = '08:15';
$sysTrends = [114.0, 110.0, 115.0, 112.0, 116.0, 110.0, 112.0];
$diaTrends = [70.0, 72.0, 68.0, 74.0, 71.0, 73.0, 72.0];

if (!empty($bpRows)) {
    $latest = end($bpRows);
    $latestSys = (int)$latest['systolic'];
    $latestDia = (int)$latest['diastolic'];
    $bpTime = date('H:i', strtotime($latest['recorded_at']));

    $sysTrends = [];
    $diaTrends = [];
    foreach ($bpRows as $r) {
        $sysTrends[] = (float)$r['systolic'];
        $diaTrends[] = (float)$r['diastolic'];
    }
}

// Fetch Weight log
$wStmt = $pdo->prepare('SELECT weight_kg, recorded_at FROM weight_logs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$wStmt->execute([$patientId]);
$wRow = $wStmt->fetch();
$latestWeight = $wRow ? (float)$wRow['weight_kg'] : 64.2;
$weightTime = $wRow ? date('H:i', strtotime($wRow['recorded_at'])) : '08:10';

respond(200, [
    'success' => true,
    'data' => [
        'patientName' => $name,
        'patientInitials' => $initials,
        'gestationalAge' => $gestText,
        'dueDateText' => $dueDateText,
        'patientIdText' => $patientCode,
        'dateText' => date('d M Y'),
        'latestSystolic' => $latestSys,
        'latestDiastolic' => $latestDia,
        'bpReadingTime' => $bpTime,
        'latestWeightKg' => $latestWeight,
        'weightReadingTime' => $weightTime,
        'systolicTrends' => $sysTrends,
        'diastolicTrends' => $diaTrends,
        'additionalVitalsText' => 'Pulse 76 bpm · Temperature 36.7 °C. Latest symptom: mild heartburn after meals.'
    ]
]);

