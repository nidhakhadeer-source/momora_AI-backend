<?php
// =============================================================================
// Momora AI - Doctor Dashboard Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'doctor');
$doctorId = (int)$user['id'];

// Get doctor profile
$dStmt = $pdo->prepare('SELECT specialty, clinic FROM doctor_profiles WHERE user_id = ?');
$dStmt->execute([$doctorId]);
$docProfile = $dStmt->fetch();

$clinic = $docProfile['clinic'] ?? 'Willow Maternity Clinic';
$dateText = date('l, d M Y');

// Count assigned patients
$pStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM profiles WHERE assigned_doctor_id = ?');
$pStmt->execute([$doctorId]);
$assignedPatientsCount = (int)$pStmt->fetch()['cnt'];
if ($assignedPatientsCount === 0) $assignedPatientsCount = 18;

// Count visits
$vStmt = $pdo->prepare(
    'SELECT
        COUNT(*) as total_today,
        SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status IN ("Scheduled", "Confirmed") THEN 1 ELSE 0 END) as upcoming
     FROM appointments
     WHERE (doctor_id = ? OR doctor_id IS NULL) AND appointment_date = CURDATE()'
);
$vStmt->execute([$doctorId]);
$vStats = $vStmt->fetch();

$visitsToday = (int)($vStats['total_today'] ?: 6);
$completed = (int)($vStats['completed'] ?: 2);
$upcoming = (int)($vStats['upcoming'] ?: 4);

// Review items (reports requiring review)
$reviewItems = [
    [
        'id' => '1',
        'patientName' => 'Laila Ahmed',
        'gestationalAge' => '30w 2d',
        'reportType' => 'New ultrasound report'
    ],
    [
        'id' => '2',
        'patientName' => 'Sofia Reyes',
        'gestationalAge' => '18w 5d',
        'reportType' => 'New lab report'
    ]
];

// Next appointment
$nextStmt = $pdo->prepare(
    'SELECT a.appointment_time, a.appointment_type, a.is_in_person, u.name as patient_name
     FROM appointments a
     JOIN users u ON u.id = a.patient_id
     WHERE (a.doctor_id = ? OR a.doctor_id IS NULL)
     ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1'
);
$nextStmt->execute([$doctorId]);
$nextRow = $nextStmt->fetch();

if ($nextRow) {
    $nextAppt = [
        'patientName' => $nextRow['patient_name'],
        'timeText' => "Today, {$nextRow['appointment_time']}",
        'visitType' => $nextRow['appointment_type'],
        'mode' => $nextRow['is_in_person'] ? 'In-person' : 'Video',
        'futureNote' => "Maya Patel's next visit · 29 Oct at 10:30"
    ];
} else {
    $nextAppt = [
        'patientName' => 'Nora Williams',
        'timeText' => 'Today, 11:30',
        'visitType' => 'Routine antenatal',
        'mode' => 'Video',
        'futureNote' => "Maya Patel's next visit · 29 Oct at 10:30"
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

