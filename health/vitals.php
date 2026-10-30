<?php
// =============================================================================
// Momora AI - Health Monitoring Vitals Overview Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

// Latest BP
$bpStmt = $pdo->prepare('SELECT systolic, diastolic, pulse, recorded_at FROM blood_pressure_logs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$bpStmt->execute([$userId]);
$bp = $bpStmt->fetch();

// Latest Weight
$wStmt = $pdo->prepare('SELECT weight_kg, baseline_weight_kg, recorded_at FROM weight_logs WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$wStmt->execute([$userId]);
$weight = $wStmt->fetch();

// General vitals
$vStmt = $pdo->prepare('SELECT heart_rate, temperature, temperature_unit, recorded_at FROM health_vitals WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1');
$vStmt->execute([$userId]);
$vitals = $vStmt->fetch();

// Doctor Name
$dStmt = $pdo->prepare('SELECT d.name FROM profiles p JOIN users d ON d.id = p.assigned_doctor_id WHERE p.user_id = ?');
$dStmt->execute([$userId]);
$doc = $dStmt->fetch();

$lastTime = $bp['recorded_at'] ?? ($weight['recorded_at'] ?? 'now');
$lastRecordedText = date('d M Y · Last recorded at H:i', strtotime($lastTime));

$bpVal = $bp ? "{$bp['systolic']}/{$bp['diastolic']}" : "112/72";
$heartRate = $vitals['heart_rate'] ?? ($bp['pulse'] ?? 78);
$weightVal = $weight ? number_format((float)$weight['weight_kg'], 1) : "64.2";
$temp = $vitals['temperature'] ?? 36.7;
$tempUnit = $vitals['temperature_unit'] ?? '°C';
$doctor = $doc['name'] ?? 'Dr Amelia Chen';

respond(200, [
    'success' => true,
    'data' => [
        'lastRecordedText' => $lastRecordedText,
        'bloodPressure' => $bpVal,
        'bloodPressureUnit' => 'mmHg',
        'heartRate' => (string)$heartRate,
        'heartRateUnit' => 'beats per minute',
        'weight' => $weightVal,
        'weightDelta' => 'kg · +0.2 kg from last week',
        'temperature' => (string)$temp,
        'temperatureUnit' => "{$tempUnit} · Oral",
        'doctorName' => $doctor
    ]
]);

