<?php
// =============================================================================
// Momora AI - Medication Reminders Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $medId = filter_var($input['medicationId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);

    if (!$medId) {
        respond(422, ['success' => false, 'message' => 'Medication ID required']);
    }

    $timeNow = date('H:i');
    $stmt = $pdo->prepare(
        'UPDATE medications
         SET is_taken = 1, taken_time_note = ?
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute(["Marked taken today at {$timeNow}", $medId, $userId]);

    respond(200, [
        'success' => true,
        'message' => 'Medication marked as taken'
    ]);
}

// GET Medications
$stmt = $pdo->prepare(
    'SELECT id, name, dosage_and_time, doctor_prescription_note, is_taken, taken_time_note, due_later_text
     FROM medications
     WHERE user_id = ?
     ORDER BY id ASC'
);
$stmt->execute([$userId]);
$meds = $stmt->fetchAll();

if (empty($meds)) {
    // Initial defaults if user has no entries yet
    $meds = [
        [
            'id' => 1,
            'name' => 'Prenatal vitamin',
            'dosage_and_time' => '1 tablet · Daily at 08:00 · With food',
            'doctor_prescription_note' => null,
            'is_taken' => 1,
            'taken_time_note' => 'Marked taken today at 08:05',
            'due_later_text' => null
        ],
        [
            'id' => 2,
            'name' => 'Vitamin D · 400 IU',
            'dosage_and_time' => '1 tablet · Daily at 20:00',
            'doctor_prescription_note' => 'As prescribed by Dr. Amelia Chen.',
            'is_taken' => 0,
            'taken_time_note' => null,
            'due_later_text' => 'Due later'
        ]
    ];
}

$takenCount = 0;
$totalCount = count($meds);
$items = [];
foreach ($meds as $m) {
    $isTaken = (bool)$m['is_taken'];
    if ($isTaken) $takenCount++;
    $items[] = [
        'id' => (int)$m['id'],
        'name' => $m['name'],
        'dosageAndTime' => $m['dosage_and_time'],
        'doctorPrescriptionNote' => $m['doctor_prescription_note'] ?: null,
        'isTaken' => $isTaken,
        'takenTimeNote' => $m['taken_time_note'] ?: null,
        'dueLaterText' => $m['due_later_text'] ?: null
    ];
}

$dateText = date('l, d M Y');
$progressText = "{$takenCount} OF {$totalCount} TAKEN · EVENING REMINDER SET";

respond(200, [
    'success' => true,
    'data' => [
        'dateText' => $dateText,
        'progressStatusText' => $progressText,
        'medications' => $items
    ]
]);

