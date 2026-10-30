<?php
// =============================================================================
// Momora AI - Symptom Checker & Logging Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

$availableSymptoms = [
    'Heartburn after meals',
    'Morning sickness / Nausea',
    'Lower back tension',
    'Mild fatigue',
    'Swollen ankles',
    'Leg cramps',
    'Shortness of breath',
    'Headaches'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $name = trim($input['symptomName'] ?? ($input['symptom_name'] ?? ''));
    $severity = strtoupper(trim($input['severity'] ?? 'MILD'));
    $started = trim($input['startedText'] ?? ($input['started_text'] ?? 'Just now'));
    $duration = trim($input['durationText'] ?? ($input['duration_text'] ?? 'About 20 min'));

    if (empty($name)) {
        respond(422, ['success' => false, 'message' => 'Please select a symptom']);
    }

    if (!in_array($severity, ['MILD', 'MODERATE', 'SEVERE'])) {
        $severity = 'MILD';
    }

    // Dynamic AI educational guidance based on symptom & severity
    $guidance = "Smaller meals and sitting upright after eating may help. Speak with your maternity team if it persists, worsens, or worries you.";
    if (stripos($name, 'nausea') !== false || stripos($name, 'sickness') !== false) {
        $guidance = "Sip ginger tea, eat small bland snacks before getting out of bed, and keep hydrated. Inform your doctor if you cannot keep fluids down.";
    } elseif (stripos($name, 'back') !== false) {
        $guidance = "Gentle prenatal stretching, warm compresses, and supportive footwear may relieve tension. Avoid heavy lifting and consult your care team if pain radiates.";
    } elseif (stripos($name, 'swollen') !== false || stripos($name, 'ankle') !== false) {
        $guidance = "Elevate your feet above heart level when resting, stay hydrated, and wear loose clothing. Seek immediate clinical assessment if swelling is sudden or in your hands/face.";
    } elseif ($severity === 'SEVERE') {
        $guidance = "For severe symptoms, do not wait. Contact Willow Maternity Clinic or proceed to your nearest maternity emergency unit.";
    }

    $stmt = $pdo->prepare(
        'INSERT INTO symptom_logs (user_id, symptom_name, severity, started_text, duration_text, ai_guidance)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $name, $severity, $started, $duration, $guidance]);

    respond(201, [
        'success' => true,
        'message' => 'Symptom logged successfully',
        'aiGuidance' => $guidance
    ]);
}

// GET Latest Symptom Entry & Available Symptoms
$stmt = $pdo->prepare(
    'SELECT symptom_name, severity, started_text, duration_text, ai_guidance
     FROM symptom_logs
     WHERE user_id = ?
     ORDER BY created_at DESC LIMIT 1'
);
$stmt->execute([$userId]);
$latest = $stmt->fetch();

$symptomName = $latest['symptom_name'] ?? 'Heartburn after meals';
$severity = $latest['severity'] ?? 'MILD';
$startedText = $latest['started_text'] ?? date('d M, H:i', strtotime('-2 hours'));
$durationText = $latest['duration_text'] ?? 'About 20 min';
$aiGuidance = $latest['ai_guidance'] ?? 'Smaller meals and sitting upright after eating may help. Speak with your maternity team if it persists, worsens, or worries you.';

respond(200, [
    'success' => true,
    'data' => [
        'symptomName' => $symptomName,
        'severity' => $severity,
        'startedText' => $startedText,
        'durationText' => $durationText,
        'aiGuidanceText' => $aiGuidance,
        'availableSymptoms' => $availableSymptoms
    ]
]);

