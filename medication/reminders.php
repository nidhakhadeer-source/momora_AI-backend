<?php
// =============================================================================
// Momora AI - Medication Reminders & Management Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

// Normalize UTF-8 question marks or broken mojibake
function cleanMojibake($str) {
    if ($str === null) return null;
    $s = preg_replace('/\s*[\?\x{00C0}\x{FFFD}]+\s*/u', ' · ', (string)$str);
    return trim(preg_replace('/\s*·\s*/u', ' · ', $s));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $action = strtolower(trim($input['action'] ?? ''));

    // 1. ADD NEW MEDICATION
    if ($action === 'add') {
        $name = trim($input['name'] ?? '');
        $dosage = trim($input['dosage'] ?? '');
        $frequency = trim($input['frequency'] ?? 'Daily');
        $reminderTime = trim($input['reminderTime'] ?? ($input['reminder_time'] ?? '08:00'));
        $instructions = trim($input['instructions'] ?? '');
        $startDate = trim($input['startDate'] ?? ($input['start_date'] ?? date('Y-m-d')));
        $endDate = trim($input['endDate'] ?? ($input['end_date'] ?? ''));
        $doctorName = trim($input['doctorName'] ?? ($input['doctor_name'] ?? 'Dr Amelia Chen'));

        if ($name === '') {
            respond(422, ['success' => false, 'message' => 'Medication name is required']);
        }
        if ($dosage === '') {
            respond(422, ['success' => false, 'message' => 'Prescribed dosage is required']);
        }
        if ($reminderTime === '') {
            respond(422, ['success' => false, 'message' => 'Reminder time is required']);
        }

        // Build composite dosageAndTime string
        $parts = [$dosage, "{$frequency} at {$reminderTime}"];
        if ($instructions !== '') {
            $parts[] = $instructions;
        }
        $dosageAndTime = implode(' · ', $parts);

        $prescriptionNote = $doctorName !== '' ? "As prescribed by {$doctorName}." : null;
        $dueLaterText = "Scheduled at {$reminderTime}";

        $stmt = $pdo->prepare(
            'INSERT INTO medications (
                user_id, name, dosage, frequency, reminder_time, instructions,
                start_date, end_date, doctor_name, dosage_and_time,
                doctor_prescription_note, is_taken, taken_time_note, due_later_text, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NULL, ?, 1, NOW())'
        );
        $stmt->execute([
            $userId, $name, $dosage, $frequency, $reminderTime,
            $instructions !== '' ? $instructions : null,
            $startDate !== '' ? $startDate : date('Y-m-d'),
            $endDate !== '' ? $endDate : null,
            $doctorName !== '' ? $doctorName : null,
            $dosageAndTime, $prescriptionNote, $dueLaterText
        ]);
        $newId = (int)$pdo->lastInsertId();

        respond(201, [
            'success' => true,
            'message' => 'Prescribed medication added successfully',
            'data' => [
                'id' => $newId,
                'name' => $name,
                'dosageAndTime' => $dosageAndTime,
                'reminderTime' => $reminderTime
            ]
        ]);
    }

    // 2. EDIT MEDICATION
    if ($action === 'edit') {
        $medId = filter_var($input['medicationId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);
        if (!$medId) {
            respond(422, ['success' => false, 'message' => 'Valid Medication ID required']);
        }

        $name = trim($input['name'] ?? '');
        $dosage = trim($input['dosage'] ?? '');
        $frequency = trim($input['frequency'] ?? 'Daily');
        $reminderTime = trim($input['reminderTime'] ?? ($input['reminder_time'] ?? '08:00'));
        $instructions = trim($input['instructions'] ?? '');
        $startDate = trim($input['startDate'] ?? ($input['start_date'] ?? ''));
        $endDate = trim($input['endDate'] ?? ($input['end_date'] ?? ''));
        $doctorName = trim($input['doctorName'] ?? ($input['doctor_name'] ?? ''));

        if ($name === '' || $dosage === '') {
            respond(422, ['success' => false, 'message' => 'Medication name and dosage are required']);
        }

        $parts = [$dosage, "{$frequency} at {$reminderTime}"];
        if ($instructions !== '') {
            $parts[] = $instructions;
        }
        $dosageAndTime = implode(' · ', $parts);
        $prescriptionNote = $doctorName !== '' ? "As prescribed by {$doctorName}." : null;

        $stmt = $pdo->prepare(
            'UPDATE medications
             SET name = ?, dosage = ?, frequency = ?, reminder_time = ?, instructions = ?,
                 start_date = ?, end_date = ?, doctor_name = ?, dosage_and_time = ?,
                 doctor_prescription_note = ?, due_later_text = ?, updated_at = NOW()
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([
            $name, $dosage, $frequency, $reminderTime,
            $instructions !== '' ? $instructions : null,
            $startDate !== '' ? $startDate : null,
            $endDate !== '' ? $endDate : null,
            $doctorName !== '' ? $doctorName : null,
            $dosageAndTime, $prescriptionNote, "Scheduled at {$reminderTime}",
            $medId, $userId
        ]);

        respond(200, [
            'success' => true,
            'message' => 'Medication schedule updated successfully'
        ]);
    }

    // 3. DISCONTINUE OR DELETE MEDICATION
    if ($action === 'discontinue' || $action === 'delete') {
        $medId = filter_var($input['medicationId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);
        if (!$medId) {
            respond(422, ['success' => false, 'message' => 'Valid Medication ID required']);
        }

        // Get medication details for log
        $medStmt = $pdo->prepare('SELECT name, dosage_and_time FROM medications WHERE id = ? AND user_id = ?');
        $medStmt->execute([$medId, $userId]);
        $existing = $medStmt->fetch();

        if ($existing) {
            // Set inactive
            $stmt = $pdo->prepare('UPDATE medications SET is_active = 0, updated_at = NOW() WHERE id = ? AND user_id = ?');
            $stmt->execute([$medId, $userId]);

            // Record discontinued in history log
            $logStmt = $pdo->prepare(
                'INSERT INTO medication_logs (user_id, medication_id, medication_name, dosage_and_time, action, action_time, notes)
                 VALUES (?, ?, ?, ?, "DISCONTINUED", NOW(), ?)'
            );
            $logStmt->execute([
                $userId, $medId, $existing['name'], $existing['dosage_and_time'],
                "Discontinued on " . date('d M Y')
            ]);
        }

        respond(200, [
            'success' => true,
            'message' => 'Medication discontinued and removed from active schedule'
        ]);
    }

    // 4. MARK MEDICATION AS TAKEN (Default action)
    $medId = filter_var($input['medicationId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);
    if (!$medId) {
        respond(422, ['success' => false, 'message' => 'Medication ID required']);
    }

    // Verify medication belongs to user
    $chkStmt = $pdo->prepare('SELECT id, name, dosage_and_time, is_taken FROM medications WHERE id = ? AND user_id = ?');
    $chkStmt->execute([$medId, $userId]);
    $med = $chkStmt->fetch();

    if (!$med) {
        respond(404, ['success' => false, 'message' => 'Medication record not found']);
    }

    $timeNow = date('H:i');
    $note = "Marked taken today at {$timeNow}";

    // Update medication state
    $stmt = $pdo->prepare(
        'UPDATE medications
         SET is_taken = 1, taken_time_note = ?, updated_at = NOW()
         WHERE id = ? AND user_id = ?'
    );
    $stmt->execute([$note, $medId, $userId]);

    // Insert into permanent medication history log
    $logStmt = $pdo->prepare(
        'INSERT INTO medication_logs (user_id, medication_id, medication_name, dosage_and_time, action, action_time, notes)
         VALUES (?, ?, ?, ?, "TAKEN", NOW(), ?)'
    );
    $logStmt->execute([
        $userId, $medId, $med['name'], $med['dosage_and_time'], $note
    ]);

    respond(200, [
        'success' => true,
        'message' => 'Medication marked as taken',
        'data' => [
            'id' => $medId,
            'isTaken' => true,
            'takenTimeNote' => $note
        ]
    ]);
}

// =============================================================================
// GET MEDICATIONS & HISTORY
// =============================================================================

// Fetch active prescribed medications for this authenticated user
$stmt = $pdo->prepare(
    'SELECT id, name, dosage, frequency, reminder_time, instructions,
            start_date, end_date, doctor_name, dosage_and_time,
            doctor_prescription_note, is_taken, taken_time_note, due_later_text, is_active
     FROM medications
     WHERE user_id = ? AND is_active = 1
     ORDER BY reminder_time ASC, id ASC'
);
$stmt->execute([$userId]);
$meds = $stmt->fetchAll();

$takenCount = 0;
$totalCount = count($meds);
$items = [];

foreach ($meds as $m) {
    $isTaken = (bool)$m['is_taken'];
    if ($isTaken) {
        $takenCount++;
    }

    $name = cleanMojibake($m['name']);
    $dosageAndTime = cleanMojibake($m['dosage_and_time']);
    $docNote = cleanMojibake($m['doctor_prescription_note']);

    $items[] = [
        'id' => (int)$m['id'],
        'name' => $name,
        'dosage' => $m['dosage'] ?: null,
        'frequency' => $m['frequency'] ?: 'Daily',
        'reminderTime' => $m['reminder_time'] ?: '08:00',
        'instructions' => $m['instructions'] ?: null,
        'startDate' => $m['start_date'] ?: null,
        'endDate' => $m['end_date'] ?: null,
        'doctorName' => $m['doctor_name'] ?: null,
        'dosageAndTime' => $dosageAndTime,
        'doctorPrescriptionNote' => $docNote,
        'isTaken' => $isTaken,
        'takenTimeNote' => cleanMojibake($m['taken_time_note']),
        'dueLaterText' => cleanMojibake($m['due_later_text']),
        'isActive' => (bool)$m['is_active']
    ];
}

// Fetch historical records for this user
$histStmt = $pdo->prepare(
    'SELECT id, medication_id, medication_name, dosage_and_time, action, action_time, notes
     FROM medication_logs
     WHERE user_id = ?
     ORDER BY action_time DESC
     LIMIT 100'
);
$histStmt->execute([$userId]);
$historyRows = $histStmt->fetchAll();

$historyItems = [];
foreach ($historyRows as $h) {
    $dt = new DateTime($h['action_time']);
    $historyItems[] = [
        'id' => (int)$h['id'],
        'medicationId' => (int)$h['medication_id'],
        'medicationName' => cleanMojibake($h['medication_name']),
        'dosageAndTime' => cleanMojibake($h['dosage_and_time']),
        'action' => $h['action'],
        'actionTime' => $h['action_time'],
        'formattedDate' => $dt->format('l, d M Y'),
        'formattedTime' => $dt->format('h:i A'),
        'notes' => cleanMojibake($h['notes'])
    ];
}

$dateText = date('l, d M Y');
$remainingCount = max(0, $totalCount - $takenCount);

if ($totalCount === 0) {
    $progressText = "NO MEDICATIONS SCHEDULED";
} elseif ($remainingCount === 0) {
    $progressText = "{$totalCount} OF {$totalCount} TAKEN · ALL COMPLETED TODAY";
} else {
    $progressText = "{$takenCount} OF {$totalCount} TAKEN · {$remainingCount} REMAINING TODAY";
}

respond(200, [
    'success' => true,
    'data' => [
        'dateText' => $dateText,
        'progressStatusText' => $progressText,
        'takenCount' => $takenCount,
        'totalCount' => $totalCount,
        'medications' => $items,
        'history' => $historyItems
    ]
]);
