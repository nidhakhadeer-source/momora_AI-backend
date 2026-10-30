<?php
// =============================================================================
// Momora AI - Appointments & Booking Calendar Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $doctorName = trim($input['doctorName'] ?? 'Dr Amelia Chen');
    $dateText = trim($input['dateText'] ?? '');
    $timeText = trim($input['timeText'] ?? '10:30');
    $isInPerson = isset($input['isInPerson']) ? ($input['isInPerson'] ? 1 : 0) : 1;

    $apptDate = date('Y-m-d');
    if (!empty($dateText)) {
        $parsed = strtotime($dateText);
        if ($parsed !== false) {
            $apptDate = date('Y-m-d', $parsed);
        }
    }

    // Lookup doctor
    $dStmt = $pdo->prepare('SELECT id FROM users WHERE name LIKE ? AND role = "doctor" LIMIT 1');
    $dStmt->execute(['%' . $doctorName . '%']);
    $doc = $dStmt->fetch();
    $doctorId = $doc ? (int)$doc['id'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO appointments (patient_id, doctor_id, doctor_name, appointment_date, appointment_time, is_in_person, appointment_type, location_notes, status)
         VALUES (?, ?, ?, ?, ?, ?, "Antenatal Consultation", "Willow Maternity Clinic · Room 3", "Confirmed")'
    );
    $stmt->execute([$userId, $doctorId, $doctorName, $apptDate, $timeText, $isInPerson]);

    // Create confirmation notification
    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read)
         VALUES (?, "Your appointment is confirmed", ?, ?, "APPOINTMENT", 1, 0)'
    );
    $notifDateStr = date('d M Y', strtotime($apptDate));
    $notifStmt->execute([
        $userId,
        "{$doctorName} · {$notifDateStr}, {$timeText}",
        "Willow Maternity Clinic · " . ($isInPerson ? "In person" : "Video visit")
    ]);

    respond(201, [
        'success' => true,
        'message' => 'Appointment confirmed successfully'
    ]);
}

// GET Booking Calendar Details
$stmt = $pdo->prepare(
    'SELECT d.name, dp.specialty
     FROM profiles p
     JOIN users d ON d.id = p.assigned_doctor_id
     LEFT JOIN doctor_profiles dp ON dp.user_id = d.id
     WHERE p.user_id = ?'
);
$stmt->execute([$userId]);
$docData = $stmt->fetch();

$doctorName = $docData['name'] ?? 'Dr Amelia Chen';
$specialty = ($docData['specialty'] ?? 'Obstetrician') . ' · Willow Maternity Clinic';

$monthTitle = date('F Y');
$availableDates = [];
$todayDay = (int)date('j');

for ($i = 0; $i < 5; $i++) {
    $targetTime = strtotime("+{$i} days");
    $availableDates[] = [
        'dayOfWeek' => date('D', $targetTime),
        'dayOfMonth' => (int)date('j', $targetTime),
        'isSelected' => ($i === 2) // default select third day
    ];
}

$timeSlots = ['09:00', '10:30', '14:00'];
$defaultNotes = 'Willow Maternity Clinic · Room 3. Bring your pregnancy notes and recent reports.';

respond(200, [
    'success' => true,
    'data' => [
        'doctorName' => $doctorName,
        'doctorSpecialty' => $specialty,
        'monthTitle' => $monthTitle,
        'availableDates' => $availableDates,
        'availableTimeSlots' => $timeSlots,
        'defaultType' => 'Routine antenatal check',
        'defaultLocationNotes' => $defaultNotes
    ]
]);

