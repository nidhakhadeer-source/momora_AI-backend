<?php
// =============================================================================
// Momora AI - Doctor Availability & Weekly Hours Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];

$validWeekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/**
 * Normalizes and validates time format (HH:MM or HH:MM:SS)
 * Returns "HH:MM:SS" on success, or false on failure.
 */
function normalizeTime($timeStr) {
    if (!is_string($timeStr) && !is_numeric($timeStr)) {
        return false;
    }
    $timeStr = trim((string)$timeStr);
    if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $timeStr, $m)) {
        $h = sprintf('%02d', (int)$m[1]);
        $min = sprintf('%02d', (int)$m[2]);
        $sec = isset($m[3]) ? sprintf(':%02d', (int)substr($m[3], 1)) : ':00';
        return "{$h}:{$min}{$sec}";
    }
    return false;
}

// -----------------------------------------------------------------------------
// GET: Return Doctor's Weekly Hours
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT id, doctor_id, weekday, start_time, end_time, slot_minutes, is_active, created_at, updated_at
         FROM doctor_availability
         WHERE doctor_id = ?
         ORDER BY FIELD(weekday, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"), start_time ASC'
    );
    $stmt->execute([$doctorId]);
    $rows = $stmt->fetchAll();

    $availability = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'doctorId' => (int)$r['doctor_id'],
            'weekday' => $r['weekday'],
            'startTime' => substr($r['start_time'], 0, 5),
            'endTime' => substr($r['end_time'], 0, 5),
            'slotMinutes' => (int)$r['slot_minutes'],
            'isActive' => (bool)$r['is_active'],
            'createdAt' => $r['created_at'],
            'updatedAt' => $r['updated_at']
        ];
    }, $rows);

    respond(200, [
        'success' => true,
        'data' => [
            'doctorId' => $doctorId,
            'doctorName' => $user['name'] ?? 'Doctor',
            'availability' => $availability,
            'weeklyHours' => $availability,
            'totalSlots' => count($availability)
        ]
    ]);
}

// -----------------------------------------------------------------------------
// POST: Save Doctor's Weekly Hours
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();

    // Determine slots to process: supports array of slots or single slot
    $rawSlots = [];
    $isBatch = false;

    if (isset($input['weeklyHours']) && is_array($input['weeklyHours'])) {
        $rawSlots = $input['weeklyHours'];
        $isBatch = true;
    } elseif (isset($input['availability']) && is_array($input['availability'])) {
        $rawSlots = $input['availability'];
        $isBatch = true;
    } elseif (isset($input['slots']) && is_array($input['slots'])) {
        $rawSlots = $input['slots'];
        $isBatch = true;
    } elseif (array_is_list($input) && !empty($input)) {
        $rawSlots = $input;
        $isBatch = true;
    } elseif (!empty($input['weekday'])) {
        $rawSlots = [$input];
        $isBatch = false;
    } else {
        respond(400, [
            'success' => false,
            'message' => 'No availability slots provided. Expected "weeklyHours" array or single slot data.'
        ]);
    }

    if (empty($rawSlots)) {
        respond(400, [
            'success' => false,
            'message' => 'Availability list cannot be empty.'
        ]);
    }

    $validatedSlots = [];
    foreach ($rawSlots as $index => $slot) {
        $idx = $index + 1;
        $weekdayRaw = trim((string)($slot['weekday'] ?? ''));

        // Match weekday case-insensitively
        $matchedWeekday = null;
        foreach ($validWeekdays as $vDay) {
            if (strcasecmp($vDay, $weekdayRaw) === 0) {
                $matchedWeekday = $vDay;
                break;
            }
        }
        if (!$matchedWeekday) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: Invalid weekday '{$weekdayRaw}'. Allowed: " . implode(', ', $validWeekdays)
            ]);
        }

        $startTimeRaw = $slot['startTime'] ?? ($slot['start_time'] ?? null);
        $endTimeRaw = $slot['endTime'] ?? ($slot['end_time'] ?? null);

        $startTime = normalizeTime($startTimeRaw);
        if ($startTime === false) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: Invalid startTime '{$startTimeRaw}'. Expected HH:MM (e.g. 09:00)."
            ]);
        }

        $endTime = normalizeTime($endTimeRaw);
        if ($endTime === false) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: Invalid endTime '{$endTimeRaw}'. Expected HH:MM (e.g. 17:00)."
            ]);
        }

        $startTs = strtotime("2000-01-01 {$startTime}");
        $endTs = strtotime("2000-01-01 {$endTime}");

        if ($startTs >= $endTs) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: startTime ({$startTimeRaw}) must be strictly earlier than endTime ({$endTimeRaw})."
            ]);
        }

        $slotMinutes = (int)($slot['slotMinutes'] ?? ($slot['slot_minutes'] ?? 30));
        if ($slotMinutes < 5 || $slotMinutes > 480) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: slotMinutes must be between 5 and 480."
            ]);
        }

        if (($endTs - $startTs) < ($slotMinutes * 60)) {
            respond(400, [
                'success' => false,
                'message' => "Slot #{$idx}: Duration between startTime and endTime must be at least {$slotMinutes} minutes."
            ]);
        }

        $isActive = isset($slot['isActive'])
            ? ($slot['isActive'] ? 1 : 0)
            : (isset($slot['is_active']) ? ($slot['is_active'] ? 1 : 0) : 1);

        $slotId = isset($slot['id']) ? filter_var($slot['id'], FILTER_VALIDATE_INT) : null;

        $validatedSlots[] = [
            'id' => $slotId ?: null,
            'weekday' => $matchedWeekday,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'slot_minutes' => $slotMinutes,
            'is_active' => $isActive
        ];
    }

    $shouldReplace = $isBatch && ($input['replace'] ?? true);

    $pdo->beginTransaction();
    try {
        if ($shouldReplace) {
            $delStmt = $pdo->prepare('DELETE FROM doctor_availability WHERE doctor_id = ?');
            $delStmt->execute([$doctorId]);
        }

        $insertStmt = $pdo->prepare(
            'INSERT INTO doctor_availability (doctor_id, weekday, start_time, end_time, slot_minutes, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $updateStmt = $pdo->prepare(
            'UPDATE doctor_availability
             SET weekday = ?, start_time = ?, end_time = ?, slot_minutes = ?, is_active = ?
             WHERE id = ? AND doctor_id = ?'
        );

        foreach ($validatedSlots as $slot) {
            if (!$shouldReplace && !empty($slot['id'])) {
                $updateStmt->execute([
                    $slot['weekday'],
                    $slot['start_time'],
                    $slot['end_time'],
                    $slot['slot_minutes'],
                    $slot['is_active'],
                    $slot['id'],
                    $doctorId
                ]);
            } else {
                $insertStmt->execute([
                    $doctorId,
                    $slot['weekday'],
                    $slot['start_time'],
                    $slot['end_time'],
                    $slot['slot_minutes'],
                    $slot['is_active']
                ]);
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        respond(500, [
            'success' => false,
            'message' => 'Failed to save availability: ' . $e->getMessage()
        ]);
    }

    // Fetch and return saved hours
    $fetchStmt = $pdo->prepare(
        'SELECT id, doctor_id, weekday, start_time, end_time, slot_minutes, is_active, created_at, updated_at
         FROM doctor_availability
         WHERE doctor_id = ?
         ORDER BY FIELD(weekday, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"), start_time ASC'
    );
    $fetchStmt->execute([$doctorId]);
    $savedRows = $fetchStmt->fetchAll();

    $savedAvailability = array_map(function($r) {
        return [
            'id' => (int)$r['id'],
            'doctorId' => (int)$r['doctor_id'],
            'weekday' => $r['weekday'],
            'startTime' => substr($r['start_time'], 0, 5),
            'endTime' => substr($r['end_time'], 0, 5),
            'slotMinutes' => (int)$r['slot_minutes'],
            'isActive' => (bool)$r['is_active'],
            'createdAt' => $r['created_at'],
            'updatedAt' => $r['updated_at']
        ];
    }, $savedRows);

    respond(200, [
        'success' => true,
        'message' => 'Doctor availability saved successfully',
        'data' => [
            'doctorId' => $doctorId,
            'doctorName' => $user['name'] ?? 'Doctor',
            'availability' => $savedAvailability,
            'weeklyHours' => $savedAvailability,
            'totalSlots' => count($savedAvailability)
        ]
    ]);
}

// -----------------------------------------------------------------------------
// Other Methods: 405 Method Not Allowed
// -----------------------------------------------------------------------------
respond(405, [
    'success' => false,
    'message' => 'Method Not Allowed. Use GET or POST.'
]);
