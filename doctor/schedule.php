<?php
// =============================================================================
// Momora AI - Doctor Appointment Schedule Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'doctor');
$doctorId = (int)$user['id'];

$todayDate = date('d M Y');
$dayName = date('l');

// Generate 3 date tabs
$dateTabs = [
    date('d M', strtotime('-2 days')),
    date('d M', strtotime('-1 day')),
    date('d M')
];

$mainAppt = [
    'timeRange' => '10:30–11:00',
    'statusText' => 'Confirmed',
    'patientName' => 'Maya Patel',
    'patientSubtitle' => '24w 3d on 27 Oct · Patient P-13042',
    'patientIdText' => 'P-13042',
    'notesText' => 'Routine antenatal check · In person, Willow Maternity Clinic · Room 3.'
];

$otherAppts = [
    [
        'id' => '1',
        'patientName' => 'Laila Ahmed',
        'timeText' => '09:00',
        'visitTypeAndMode' => 'Routine antenatal · In person',
        'statusText' => 'Confirmed',
        'isVideo' => false
    ],
    [
        'id' => '2',
        'patientName' => 'Emma Wilson',
        'timeText' => '14:00',
        'visitTypeAndMode' => 'Follow-up · Video visit',
        'statusText' => 'Confirmed',
        'isVideo' => true
    ]
];

$summary = "Today, {$todayDate} · 6 visits; 2 completed, 4 upcoming. Next: Nora Williams at 11:30.";

respond(200, [
    'success' => true,
    'data' => [
        'doctorName' => $user['name'],
        'dateText' => "{$dayName}, {$todayDate}",
        'dateTabs' => $dateTabs,
        'mainAppointment' => $mainAppt,
        'otherAppointments' => $otherAppts,
        'visitsSummaryNote' => $summary
    ]
]);

