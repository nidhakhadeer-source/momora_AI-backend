<?php
// =============================================================================
// Momora AI - Patient Health Monitoring Trends for Doctor Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
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

$name = trim($patient['name'] ?? '');
$words = explode(' ', $name);
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : (mb_strtoupper(mb_substr($name, 0, 2)) ?: 'PT');
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));
$gestText = ($patient['gestational_weeks'] ?? 24) . 'w ' . ($patient['gestational_days'] ?? 3) . 'd';
$dueDateText = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due date not set';

// Optional days parameter (7, 14 or 30), default 7
$daysRaw = $input['days'] ?? ($_GET['days'] ?? 7);
$days = filter_var($daysRaw, FILTER_VALIDATE_INT);
if (!$days || !in_array($days, [7, 14, 30], true)) {
    $days = 7;
}

// Fetch BP logs for trends based on days parameter
$bpStmt = $pdo->prepare(
    'SELECT systolic, diastolic, pulse, recorded_at
     FROM blood_pressure_logs
     WHERE user_id = ?
     ORDER BY recorded_at DESC
     LIMIT ?'
);
$bpStmt->bindValue(1, $patientId, PDO::PARAM_INT);
$bpStmt->bindValue(2, $days, PDO::PARAM_INT);
$bpStmt->execute();
$bpRows = array_reverse($bpStmt->fetchAll());

$latestSys = null;
$latestDia = null;
$latestBpPulse = null;
$bpTime = null;
$sysTrends = [];
$diaTrends = [];

if (!empty($bpRows)) {
    $latest = end($bpRows);
    $latestSys = (int)$latest['systolic'];
    $latestDia = (int)$latest['diastolic'];
    $latestBpPulse = !empty($latest['pulse']) ? (int)$latest['pulse'] : null;
    $bpTime = date('H:i', strtotime($latest['recorded_at']));

    foreach ($bpRows as $r) {
        $sysTrends[] = (float)$r['systolic'];
        $diaTrends[] = (float)$r['diastolic'];
    }
}

// Fetch latest Weight log
$wStmt = $pdo->prepare('SELECT weight_kg, recorded_at FROM weight_logs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$wStmt->execute([$patientId]);
$wRow = $wStmt->fetch();
$latestWeight = $wRow ? (float)$wRow['weight_kg'] : null;
$weightTime = $wRow ? date('H:i', strtotime($wRow['recorded_at'])) : null;

// Fetch latest health_vitals row (heart_rate, temperature)
$hvStmt = $pdo->prepare('SELECT heart_rate, temperature, temperature_unit, recorded_at FROM health_vitals WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$hvStmt->execute([$patientId]);
$hvRow = $hvStmt->fetch();

$pulse = $hvRow && $hvRow['heart_rate'] !== null ? (int)$hvRow['heart_rate'] : $latestBpPulse;
$temperature = $hvRow && $hvRow['temperature'] !== null ? (float)$hvRow['temperature'] : null;
$temperatureUnit = $hvRow && !empty($hvRow['temperature_unit']) ? $hvRow['temperature_unit'] : '°C';

// Fetch latest symptom_logs row
$sympStmt = $pdo->prepare('SELECT symptom_name, severity, started_text, created_at FROM symptom_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
$sympStmt->execute([$patientId]);
$sympRow = $sympStmt->fetch();

// Construct additionalVitalsText dynamically from real rows
$vitalsParts = [];
if ($pulse !== null) {
    $vitalsParts[] = "Pulse {$pulse} bpm";
}
if ($temperature !== null) {
    $vitalsParts[] = "Temperature {$temperature} {$temperatureUnit}";
}
$vitalsSummary = !empty($vitalsParts) ? implode(' · ', $vitalsParts) : 'No vitals recorded';

if ($sympRow && !empty($sympRow['symptom_name'])) {
    $vitalsSummary .= ". Latest symptom: " . $sympRow['symptom_name'] . ".";
} else {
    $vitalsSummary .= ". No recent symptoms reported.";
}

respond(200, [
    'success' => true,
    'data' => [
        'patientName' => $name,
        'patientInitials' => $initials,
        'gestationalAge' => $gestText,
        'dueDateText' => $dueDateText,
        'patientIdText' => $patientCode,
        'dateText' => date('d M Y'),
        'days' => $days,
        'latestSystolic' => $latestSys,
        'latestDiastolic' => $latestDia,
        'bpReadingTime' => $bpTime,
        'latestWeightKg' => $latestWeight,
        'weightReadingTime' => $weightTime,
        'systolicTrends' => $sysTrends,
        'diastolicTrends' => $diaTrends,
        'pulse' => $pulse,
        'temperature' => $temperature,
        'temperatureUnit' => $temperatureUnit,
        'latestSymptom' => $sympRow ? $sympRow['symptom_name'] : null,
        'additionalVitalsText' => $vitalsSummary
    ]
]);

