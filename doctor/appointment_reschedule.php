<?php
// =============================================================================
// Momora AI - Doctor Appointment Reschedule Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// 1. Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method Not Allowed. Use POST.'
    ]);
}

// 2. Authenticate as doctor
$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];
$doctorName = $user['name'] ?: 'Doctor';

// 3. Read and parse inputs
$input = getInput();
$appointmentIdRaw = $input['appointmentId'] ?? ($input['appointment_id'] ?? null);
$newDate = trim((string)($input['newDate'] ?? ($input['new_date'] ?? '')));
$newTime = trim((string)($input['newTime'] ?? ($input['new_time'] ?? '')));

// 4. Validate appointmentId
$appointmentId = filter_var($appointmentIdRaw, FILTER_VALIDATE_INT);
if ($appointmentId === false || $appointmentId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'appointmentId is required and must be a valid positive integer'
    ]);
}

// 5. Validate newDate
if (empty($newDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
    respond(400, [
        'success' => false,
        'message' => 'newDate is required and must be in Y-m-d format (e.g. 2026-11-20)'
    ]);
}
$dObj = DateTime::createFromFormat('Y-m-d', $newDate);
if (!$dObj || $dObj->format('Y-m-d') !== $newDate) {
    respond(400, [
        'success' => false,
        'message' => 'newDate is not a valid calendar date'
    ]);
}

// 6. Reject past dates
$today = date('Y-m-d');
if ($newDate < $today) {
    respond(400, [
        'success' => false,
        'message' => 'Cannot reschedule appointment to a past date'
    ]);
}

// 7. Validate newTime
if (empty($newTime)) {
    respond(400, [
        'success' => false,
        'message' => 'newTime is required (e.g. 10:30 or 14:00)'
    ]);
}

// Normalize newTime if needed (e.g. "10:30" or "10:30:00")
if (preg_match('/^(\d{1,2}):(\d{2})$/', $newTime, $m)) {
    $newTime = sprintf('%02d:%02d', $m[1], $m[2]);
}

// 8. Find appointment and ensure it belongs to the authenticated doctor
$stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
$stmt->execute([$appointmentId]);
$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appointment) {
    respond(404, [
        'success' => false,
        'message' => 'Appointment not found'
    ]);
}

if ((int)$appointment['doctor_id'] !== $doctorId) {
    respond(403, [
        'success' => false,
        'message' => 'Forbidden: You can only reschedule your own appointments'
    ]);
}

// 9. Update the appointment with newDate and newTime
$updateStmt = $pdo->prepare(
    'UPDATE appointments
     SET appointment_date = ?, appointment_time = ?, status = "Confirmed"
     WHERE id = ?'
);
$updateStmt->execute([$newDate, $newTime, $appointmentId]);

// 10. Notify the patient by inserting a row into notifications
$patientId = (int)$appointment['patient_id'];
$formattedDate = date('d M Y', strtotime($newDate));
$notifTitle = 'Appointment Rescheduled';
$notifSubtitle = "{$doctorName} · {$formattedDate}, {$newTime}";
$notifDetail = "Your appointment has been rescheduled to {$formattedDate} at {$newTime}.";

$notifStmt = $pdo->prepare(
    'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read, created_at)
     VALUES (?, ?, ?, ?, "APPOINTMENT", 1, 0, NOW())'
);
$notifStmt->execute([
    $patientId,
    $notifTitle,
    $notifSubtitle,
    $notifDetail
]);
$notificationId = (int)$pdo->lastInsertId();

// 11. Return success response
respond(200, [
    'success' => true,
    'message' => 'Appointment rescheduled successfully',
    'data' => [
        'appointmentId' => $appointmentId,
        'patientId' => $patientId,
        'doctorId' => $doctorId,
        'previousDate' => $appointment['appointment_date'],
        'previousTime' => $appointment['appointment_time'],
        'newDate' => $newDate,
        'newTime' => $newTime,
        'status' => 'Confirmed',
        'notificationId' => $notificationId
    ]
]);
