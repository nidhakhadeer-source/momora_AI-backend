<?php
// =============================================================================
// Momora AI - Patient Dashboard Summary Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

// 1. Fetch user & pregnancy details
$stmt = $pdo->prepare(
    'SELECT u.name, p.estimated_due_date, p.gestational_weeks, p.gestational_days, p.trimester
     FROM users u
     LEFT JOIN pregnancy_details p ON p.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$userId]);
$patient = $stmt->fetch();

$patientName = explode(' ', trim($patient['name'] ?? 'Mama'))[0];
$todayText = date('l, d M Y') . ' · A little care, every day.';

// Calculate or retrieve gestational age and progress
$weeks = $patient['gestational_weeks'] ?? 24;
$days = $patient['gestational_days'] ?? 3;
$gestationalAgeText = "{$weeks} weeks, {$days} days";

$trimesterMap = [1 => 'FIRST TRIMESTER', 2 => 'SECOND TRIMESTER', 3 => 'THIRD TRIMESTER'];
$trimesterNum = $patient['trimester'] ?? 2;
$trimesterText = $trimesterMap[$trimesterNum] ?? 'SECOND TRIMESTER';

$dueDate = $patient['estimated_due_date'] ? date('d M Y', strtotime($patient['estimated_due_date'])) : '13 Feb 2027';
$dueDateText = "Due {$dueDate} · Pregnancy details ➔";
$progressPercent = min(1.0, max(0.1, ($weeks * 7 + $days) / 280.0));

// 2. Fetch latest Blood Pressure
$bpStmt = $pdo->prepare(
    'SELECT systolic, diastolic, recorded_at
     FROM blood_pressure_logs
     WHERE user_id = ?
     ORDER BY recorded_at DESC LIMIT 1'
);
$bpStmt->execute([$userId]);
$latestBp = $bpStmt->fetch();

if ($latestBp) {
    $bloodPressureValue = "{$latestBp['systolic']}/{$latestBp['diastolic']}";
    $bpTimeFormatted = date('H:i', strtotime($latestBp['recorded_at']));
    $bloodPressureTime = "mmHg · Today, {$bpTimeFormatted}";
} else {
    $bloodPressureValue = "112/72";
    $bloodPressureTime = "mmHg · Today, 08:15";
}

// 3. Fetch latest Weight
$wStmt = $pdo->prepare(
    'SELECT weight_kg, recorded_at
     FROM weight_logs
     WHERE user_id = ?
     ORDER BY recorded_at DESC LIMIT 1'
);
$wStmt->execute([$userId]);
$latestWeight = $wStmt->fetch();

if ($latestWeight) {
    $weightValue = number_format((float)$latestWeight['weight_kg'], 1);
    $wTimeFormatted = date('H:i', strtotime($latestWeight['recorded_at']));
    $weightTime = "kg · Today, {$wTimeFormatted}";
} else {
    $weightValue = "64.2";
    $weightTime = "kg · Today, 08:10";
}

// 4. Fetch Daily Care Items (Medications + Upcoming Appointments)
$dailyCareItems = [];

// Medications
$medStmt = $pdo->prepare(
    'SELECT id, name, dosage_and_time, is_taken
     FROM medications
     WHERE user_id = ?
     ORDER BY id ASC'
);
$medStmt->execute([$userId]);
$meds = $medStmt->fetchAll();

foreach ($meds as $med) {
    $dailyCareItems[] = [
        'id' => (int)$med['id'],
        'title' => $med['name'],
        'subtitle' => $med['dosage_and_time'],
        'isTaken' => (bool)$med['is_taken'],
        'isAppointment' => false
    ];
}

// Next appointment
$apptStmt = $pdo->prepare(
    'SELECT id, doctor_name, appointment_date, appointment_time, is_in_person
     FROM appointments
     WHERE patient_id = ? AND appointment_date >= CURDATE()
     ORDER BY appointment_date ASC, appointment_time ASC LIMIT 1'
);
$apptStmt->execute([$userId]);
$nextAppt = $apptStmt->fetch();

if ($nextAppt) {
    $apptDateFormatted = date('d M Y', strtotime($nextAppt['appointment_date']));
    $mode = $nextAppt['is_in_person'] ? 'In-person' : 'Video visit';
    $dailyCareItems[] = [
        'id' => 1000 + (int)$nextAppt['id'],
        'title' => $nextAppt['doctor_name'],
        'subtitle' => "{$apptDateFormatted} · {$nextAppt['appointment_time']} · {$mode}",
        'isTaken' => false,
        'isAppointment' => true
    ];
}

// If no medications in DB yet, supply initial defaults so the screen isn't empty
if (empty($dailyCareItems)) {
    $dailyCareItems = [
        [
            'id' => 1,
            'title' => 'Prenatal vitamin',
            'subtitle' => '1 tablet · Today at 08:00',
            'isTaken' => true,
            'isAppointment' => false
        ],
        [
            'id' => 2,
            'title' => 'Dr Amelia Chen',
            'subtitle' => '29 Oct 2026 · 10:30 · In-person',
            'isTaken' => false,
            'isAppointment' => true
        ]
    ];
}

respond(200, [
    'success' => true,
    'data' => [
        'patientName' => $patientName,
        'dateText' => $todayText,
        'trimesterText' => $trimesterText,
        'gestationalAgeText' => $gestationalAgeText,
        'dueDateText' => $dueDateText,
        'progressPercent' => (float)$progressPercent,
        'bloodPressureValue' => $bloodPressureValue,
        'bloodPressureTime' => $bloodPressureTime,
        'weightValue' => $weightValue,
        'weightTime' => $weightTime,
        'dailyCareItems' => $dailyCareItems
    ]
]);

