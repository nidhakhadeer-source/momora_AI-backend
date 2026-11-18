<?php
// =============================================================================
// Momora AI - Doctor Dashboard Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];

// Get doctor profile
$dStmt = $pdo->prepare('SELECT specialty, clinic FROM doctor_profiles WHERE user_id = ?');
$dStmt->execute([$doctorId]);
$docProfile = $dStmt->fetch();

$clinic = $docProfile['clinic'] ?? 'Willow Maternity Clinic';
$dateText = date('l, d M Y');

// Count assigned patients
$pStmt = $pdo->prepare(
    'SELECT COUNT(*) as cnt
     FROM users u
     JOIN profiles p ON p.user_id = u.id
     WHERE u.role = "patient" AND u.is_active = 1
       AND p.assigned_doctor_id = ?'
);
$pStmt->execute([$doctorId]);
$assignedPatientsCount = (int)($pStmt->fetchColumn() ?: 0);

// Count visits
$vStmt = $pdo->prepare(
    'SELECT
        COUNT(*) as total_today,
        SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status IN ("Scheduled", "Confirmed") THEN 1 ELSE 0 END) as upcoming
     FROM appointments
     WHERE doctor_id = ? AND appointment_date = CURDATE()'
);
$vStmt->execute([$doctorId]);
$vStats = $vStmt->fetch(PDO::FETCH_ASSOC);

$visitsToday = (int)($vStats['total_today'] ?? 0);
$completed = (int)($vStats['completed'] ?? 0);
$upcoming = (int)($vStats['upcoming'] ?? 0);

// Review items (real medical reports from patients requiring review)
$rStmt = $pdo->prepare(
    'SELECT
        mr.id,
        mr.title,
        mr.category,
        u.name as patient_name,
        pd.last_menstrual_period,
        pd.estimated_due_date,
        pd.gestational_weeks,
        pd.gestational_days
     FROM medical_reports mr
     JOIN users u ON u.id = mr.user_id
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.role = "patient" AND u.is_active = 1
       AND p.assigned_doctor_id = ?
     ORDER BY mr.created_at DESC
     LIMIT 10'
);
$rStmt->execute([$doctorId]);
$reports = $rStmt->fetchAll(PDO::FETCH_ASSOC);

$reviewItems = [];
foreach ($reports as $r) {
    $gestAge = computeGestationalAge(
        $r['last_menstrual_period'] ?? null,
        $r['estimated_due_date'] ?? null,
        isset($r['gestational_weeks']) ? (int)$r['gestational_weeks'] : null,
        isset($r['gestational_days']) ? (int)$r['gestational_days'] : null
    );
    $categoryName = $r['category'] ?: 'medical';
    if ($categoryName === 'ULTRASOUND') $categoryName = 'ultrasound';
    elseif ($categoryName === 'LAB_RESULTS') $categoryName = 'lab';
    $reportType = !empty($r['title']) ? $r['title'] : ("New " . strtolower($categoryName) . " report");

    $reviewItems[] = [
        'id' => (string)$r['id'],
        'patientName' => $r['patient_name'],
        'gestationalAge' => $gestAge ?: 'Not recorded',
        'reportType' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)$reportType)
    ];
}

// Next appointment
$nextStmt = $pdo->prepare(
    'SELECT a.appointment_date, a.appointment_time, a.appointment_type, a.is_in_person, u.name as patient_name
     FROM appointments a
     JOIN users u ON u.id = a.patient_id
     WHERE a.doctor_id = ?
       AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.status != "Completed"))
       AND a.status != "Cancelled"
     ORDER BY a.appointment_date ASC, a.appointment_time ASC
     LIMIT 1'
);
$nextStmt->execute([$doctorId]);
$nextRow = $nextStmt->fetch(PDO::FETCH_ASSOC);

if ($nextRow) {
    $isToday = ($nextRow['appointment_date'] === date('Y-m-d'));
    $timePrefix = $isToday ? 'Today' : date('d M', strtotime($nextRow['appointment_date']));
    $timeText = "{$timePrefix}, {$nextRow['appointment_time']}";
    $visitType = $nextRow['appointment_type'] ?: 'Routine antenatal';
    $mode = $nextRow['is_in_person'] ? 'In-person' : 'Video';

    // Find subsequent appointment for future note
    $subStmt = $pdo->prepare(
        'SELECT a.appointment_date, a.appointment_time, u.name as patient_name
         FROM appointments a
         JOIN users u ON u.id = a.patient_id
         WHERE a.doctor_id = ?
           AND (a.appointment_date > ? OR (a.appointment_date = ? AND a.appointment_time > ?))
           AND a.status != "Cancelled"
         ORDER BY a.appointment_date ASC, a.appointment_time ASC
         LIMIT 1'
    );
    $subStmt->execute([
        $doctorId,
        $nextRow['appointment_date'],
        $nextRow['appointment_date'],
        $nextRow['appointment_time']
    ]);
    $subRow = $subStmt->fetch(PDO::FETCH_ASSOC);

    if ($subRow) {
        $subDate = date('d M', strtotime($subRow['appointment_date']));
        $subTime = date('H:i', strtotime($subRow['appointment_time']));
        $futureNote = "{$subRow['patient_name']}'s next visit · {$subDate} at {$subTime}";
    } else {
        $futureNote = "";
    }

    $nextAppt = [
        'patientName' => $nextRow['patient_name'],
        'timeText' => $timeText,
        'visitType' => $visitType,
        'mode' => $mode,
        'futureNote' => $futureNote
    ];
} else {
    $nextAppt = [
        'patientName' => 'No upcoming appointments',
        'timeText' => 'None scheduled',
        'visitType' => '',
        'mode' => '',
        'futureNote' => ''
    ];
}

respond(200, [
    'success' => true,
    'data' => [
        'doctorName' => $user['name'],
        'clinicName' => $clinic,
        'dateText' => $dateText,
        'assignedPatientsCount' => $assignedPatientsCount,
        'visitsTodayCount' => $visitsToday,
        'upcomingVisitsCount' => $upcoming,
        'completedVisitsCount' => $completed,
        'toReviewCount' => count($reviewItems),
        'reviewItems' => $reviewItems,
        'nextAppointment' => $nextAppt
    ]
]);

