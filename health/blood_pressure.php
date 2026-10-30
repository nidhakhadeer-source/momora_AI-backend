<?php
// =============================================================================
// Momora AI - Blood Pressure Tracker Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $systolic = filter_var($input['systolic'] ?? 0, FILTER_VALIDATE_INT);
    $diastolic = filter_var($input['diastolic'] ?? 0, FILTER_VALIDATE_INT);
    $pulse = filter_var($input['pulse'] ?? 78, FILTER_VALIDATE_INT);

    if (!$systolic || !$diastolic || $systolic < 50 || $systolic > 250 || $diastolic < 30 || $diastolic > 160) {
        respond(422, [
            'success' => false,
            'message' => 'Please enter realistic blood pressure readings (e.g. 110-140 systolic, 60-90 diastolic)'
        ]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO blood_pressure_logs (user_id, systolic, diastolic, pulse)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $systolic, $diastolic, $pulse]);

    respond(201, [
        'success' => true,
        'message' => 'Blood pressure reading logged successfully'
    ]);
}

// GET BP History & Chart normalization
$stmt = $pdo->prepare(
    'SELECT systolic, diastolic, pulse, recorded_at
     FROM blood_pressure_logs
     WHERE user_id = ?
     ORDER BY recorded_at DESC LIMIT 7'
);
$stmt->execute([$userId]);
$rows = array_reverse($stmt->fetchAll());

$currentReading = "112 / 72 mmHg";
$timestampText = date('d M Y · H:i · Pulse 78 bpm');

if (!empty($rows)) {
    $latest = end($rows);
    $currentReading = "{$latest['systolic']} / {$latest['diastolic']} mmHg";
    $time = date('d M Y · H:i', strtotime($latest['recorded_at']));
    $pulse = $latest['pulse'] ?? 78;
    $timestampText = "{$time} · Pulse {$pulse} bpm";
}

$yAxisLabels = ['120', '100', '80', '60'];
$xAxisLabels = [];
$systolicPoints = [];
$diastolicPoints = [];

$count = count($rows);
if ($count === 0) {
    $xAxisLabels = ['21 Oct', '23 Oct', '25 Oct', '27 Oct'];
    $systolicPoints = [
        ['first' => 0.00, 'second' => 0.28],
        ['first' => 0.33, 'second' => 0.35],
        ['first' => 0.66, 'second' => 0.25],
        ['first' => 1.00, 'second' => 0.28]
    ];
    $diastolicPoints = [
        ['first' => 0.00, 'second' => 0.78],
        ['first' => 0.33, 'second' => 0.82],
        ['first' => 0.66, 'second' => 0.75],
        ['first' => 1.00, 'second' => 0.78]
    ];
} else {
    foreach ($rows as $i => $row) {
        $xAxisLabels[] = date('d M', strtotime($row['recorded_at']));
        $x = $count > 1 ? (float)($i / ($count - 1)) : 0.5;

        // Normalize 60-140 mmHg onto 0.0-1.0 Y coordinate
        $sysY = 1.0 - (float)(($row['systolic'] - 60) / 80.0);
        $diaY = 1.0 - (float)(($row['diastolic'] - 60) / 80.0);

        $systolicPoints[] = ['first' => (float)round($x, 2), 'second' => (float)round(max(0.05, min(0.95, $sysY)), 2)];
        $diastolicPoints[] = ['first' => (float)round($x, 2), 'second' => (float)round(max(0.05, min(0.95, $diaY)), 2)];
    }
}

respond(200, [
    'success' => true,
    'data' => [
        'currentReadingText' => $currentReading,
        'timestampText' => $timestampText,
        'yAxisLabels' => $yAxisLabels,
        'xAxisLabels' => $xAxisLabels,
        'systolicPointsNormalized' => $systolicPoints,
        'diastolicPointsNormalized' => $diastolicPoints
    ]
]);

