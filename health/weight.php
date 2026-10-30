<?php
// =============================================================================
// Momora AI - Weight Tracker Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $weight = filter_var($input['weight'] ?? ($input['weight_kg'] ?? 0), FILTER_VALIDATE_FLOAT);
    $dateText = trim($input['date'] ?? '');

    if (!$weight || $weight < 30.0 || $weight > 250.0) {
        respond(422, [
            'success' => false,
            'message' => 'Please enter a valid weight in kg (e.g. 64.5)'
        ]);
    }

    $recordedAt = date('Y-m-d H:i:s');
    if (!empty($dateText)) {
        $parsed = strtotime($dateText);
        if ($parsed !== false) {
            $recordedAt = date('Y-m-d H:i:s', $parsed);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO weight_logs (user_id, weight_kg, recorded_at)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $weight, $recordedAt]);

    respond(201, [
        'success' => true,
        'message' => 'Weight logged successfully'
    ]);
}

// GET Weight Logs
$stmt = $pdo->prepare(
    'SELECT weight_kg, baseline_weight_kg, recorded_at
     FROM weight_logs
     WHERE user_id = ?
     ORDER BY recorded_at DESC LIMIT 6'
);
$stmt->execute([$userId]);
$rows = array_reverse($stmt->fetchAll());

$currentWeightKg = "64.2";
$baseline = 59.0;
$baselineDeltaText = "+5.2 kg from 59.0 kg baseline · 27 Oct 2026, 08:10";
$lastReadingText = "Previous - 63.8 kg on 20 Oct 2026 · Change +0.4 kg";

if (!empty($rows)) {
    $latest = end($rows);
    $currentWeightKg = number_format((float)$latest['weight_kg'], 1);
    $baseline = (float)($latest['baseline_weight_kg'] ?: 59.0);
    $diff = (float)$latest['weight_kg'] - $baseline;
    $sign = $diff >= 0 ? '+' : '';
    $timeStr = date('d M Y, H:i', strtotime($latest['recorded_at']));
    $baselineDeltaText = "{$sign}" . number_format($diff, 1) . " kg from " . number_format($baseline, 1) . " kg baseline · {$timeStr}";

    if (count($rows) > 1) {
        $prev = $rows[count($rows) - 2];
        $prevWeight = (float)$prev['weight_kg'];
        $prevDiff = (float)$latest['weight_kg'] - $prevWeight;
        $prevSign = $prevDiff >= 0 ? '+' : '';
        $prevDate = date('d M Y', strtotime($prev['recorded_at']));
        $lastReadingText = "Previous - " . number_format($prevWeight, 1) . " kg on {$prevDate} · Change {$prevSign}" . number_format($prevDiff, 1) . " kg";
    }
}

$timeRangeTabs = ['4 weeks', 'Pregnancy'];
$yAxisLabels = ['65', '63', '61', '59'];
$xAxisLabels = [];
$chartPoints = [];

$count = count($rows);
if ($count === 0) {
    $xAxisLabels = ['W16', 'W18', 'W20', 'W22', 'W24'];
    $chartPoints = [
        ['first' => 0.00, 'second' => 0.88],
        ['first' => 0.25, 'second' => 0.72],
        ['first' => 0.50, 'second' => 0.48],
        ['first' => 0.75, 'second' => 0.30],
        ['first' => 1.00, 'second' => 0.12]
    ];
} else {
    foreach ($rows as $i => $row) {
        $xAxisLabels[] = 'W' . (16 + $i * 2);
        $x = $count > 1 ? (float)($i / ($count - 1)) : 0.5;
        // Normalize 58kg - 66kg to 0.0 - 1.0
        $y = 1.0 - (float)((float)$row['weight_kg'] - 58.0) / 8.0;
        $chartPoints[] = ['first' => (float)round($x, 2), 'second' => (float)round(max(0.05, min(0.95, $y)), 2)];
    }
}

respond(200, [
    'success' => true,
    'data' => [
        'currentWeightKg' => $currentWeightKg,
        'baselineDeltaText' => $baselineDeltaText,
        'timeRangeTabs' => $timeRangeTabs,
        'yAxisLabels' => $yAxisLabels,
        'xAxisLabels' => $xAxisLabels,
        'chartPointsNormalized' => $chartPoints,
        'lastReadingText' => $lastReadingText
    ]
]);

