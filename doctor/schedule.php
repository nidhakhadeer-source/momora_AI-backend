<?php
// =============================================================================
// Momora AI - Doctor Appointment Schedule Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$doctorId = (int)$user['id'];
$doctorName = $user['name'] ?? 'Doctor';

$input = getInput();
$dateParam = $input['date'] ?? ($_GET['date'] ?? null);

$selectedDate = null;
if ($dateParam !== null && $dateParam !== '') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid date format. Expected Y-m-d (e.g. 2026-10-29).'
        ]);
    }
    $d = DateTime::createFromFormat('Y-m-d', $dateParam);
    if (!$d || $d->format('Y-m-d') !== $dateParam) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid date value. Expected valid Y-m-d.'
        ]);
    }
    $selectedDate = $dateParam;
} else {
    $selectedDate = date('Y-m-d');
}

$dayName = date('l', strtotime($selectedDate));
$formattedSelectedDate = date('d M Y', strtotime($selectedDate));
$dateText = "{$dayName}, {$formattedSelectedDate}";

// 1. Fetch real date tabs from appointments table for this doctor
$tabStmt = $pdo->prepare(
    'SELECT DISTINCT appointment_date
     FROM appointments
     WHERE doctor_id = ?
     ORDER BY appointment_date ASC'
);
$tabStmt->execute([$doctorId]);
$dbDates = $tabStmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array($selectedDate, $dbDates, true)) {
    $dbDates[] = $selectedDate;
    sort($dbDates);
}

$dateTabs = array_values(array_map(function($d) {
    return date('d M', strtotime($d));
}, $dbDates));

// 2. Fetch real counts of completed and upcoming visits for this doctor on $selectedDate
$countStmt = $pdo->prepare(
    'SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status IN ("Scheduled", "Confirmed") THEN 1 ELSE 0 END) as upcoming_count
     FROM appointments
     WHERE doctor_id = ? AND appointment_date = ?'
);
$countStmt->execute([$doctorId, $selectedDate]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

$totalVisits = (int)($counts['total_count'] ?? 0);
$completedVisits = (int)($counts['completed_count'] ?? 0);
$upcomingVisits = (int)($counts['upcoming_count'] ?? 0);

// 3. Fetch appointments for $selectedDate
$stmt = $pdo->prepare(
    'SELECT a.*, u.name as patient_name, p.patient_code,
            pd.gestational_weeks, pd.gestational_days, pd.last_menstrual_period, pd.estimated_due_date
     FROM appointments a
     JOIN users u ON u.id = a.patient_id
     LEFT JOIN profiles p ON p.user_id = a.patient_id
     LEFT JOIN pregnancy_details pd ON pd.user_id = a.patient_id
     WHERE a.doctor_id = ? AND a.appointment_date = ?
     ORDER BY a.appointment_time ASC'
);
$stmt->execute([$doctorId, $selectedDate]);
$dateAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch the overall next appointment (from today onwards, not completed or cancelled)
$nextStmt = $pdo->prepare(
    'SELECT a.*, u.name as patient_name, p.patient_code,
            pd.gestational_weeks, pd.gestational_days, pd.last_menstrual_period, pd.estimated_due_date
     FROM appointments a
     JOIN users u ON u.id = a.patient_id
     LEFT JOIN profiles p ON p.user_id = a.patient_id
     LEFT JOIN pregnancy_details pd ON pd.user_id = a.patient_id
     WHERE a.doctor_id = ?
       AND (a.appointment_date > CURDATE() OR (a.appointment_date = CURDATE() AND a.status != "Completed"))
       AND a.status != "Cancelled"
     ORDER BY a.appointment_date ASC, a.appointment_time ASC
     LIMIT 1'
);
$nextStmt->execute([$doctorId]);
$nextRow = $nextStmt->fetch(PDO::FETCH_ASSOC);

// Helper function to format an appointment as main/next appointment
function formatMainAppointment($row) {
    if (!$row) {
        return [
            'timeRange' => 'No appointments',
            'statusText' => 'None',
            'patientName' => 'No appointments',
            'patientSubtitle' => 'No scheduled visits',
            'patientIdText' => '',
            'notesText' => 'No consultations scheduled for this date.'
        ];
    }

    $patientCode = $row['patient_code'] ?: ('P-' . (10000 + (int)$row['patient_id']));
    
    // Compute real gestational age dynamically from pregnancy_details
    $gestText = computeGestationalAge(
        $row['last_menstrual_period'] ?? null,
        $row['estimated_due_date'] ?? null,
        isset($row['gestational_weeks']) ? (int)$row['gestational_weeks'] : null,
        isset($row['gestational_days']) ? (int)$row['gestational_days'] : null
    );

    $dateStr = date('d M', strtotime($row['appointment_date']));
    $subtitleParts = [];
    if (!empty($gestText)) {
        $subtitleParts[] = "{$gestText} on {$dateStr}";
    }
    $subtitleParts[] = "Patient {$patientCode}";
    $patientSubtitle = implode(' · ', $subtitleParts);

    $startTime = $row['appointment_time'];
    $endTime = date('H:i', strtotime($startTime . ' +30 minutes'));
    $timeRange = "{$startTime}–{$endTime}";

    $notes = $row['location_notes'] ?: ($row['appointment_type'] . ' · ' . ($row['is_in_person'] ? 'In person' : 'Video visit'));

    return [
        'id' => (string)$row['id'],
        'timeRange' => $timeRange,
        'statusText' => $row['status'],
        'patientName' => $row['patient_name'],
        'patientSubtitle' => $patientSubtitle,
        'patientIdText' => $patientCode,
        'notesText' => $notes
    ];
}

// 5. Structure main appointment, other appointments, and all appointments list
$mainAppt = null;
$otherAppts = [];
$allAppointmentsList = [];

foreach ($dateAppts as $row) {
    $modeStr = $row['is_in_person'] ? 'In person' : 'Video visit';
    $typeStr = $row['appointment_type'] ?: 'Routine antenatal';
    $allAppointmentsList[] = [
        'id' => (string)$row['id'],
        'patientId' => (int)$row['patient_id'],
        'patientName' => $row['patient_name'],
        'appointmentDate' => $row['appointment_date'],
        'appointmentTime' => $row['appointment_time'],
        'timeText' => $row['appointment_time'],
        'visitTypeAndMode' => "{$typeStr} · {$modeStr}",
        'statusText' => $row['status'],
        'isVideo' => !(bool)$row['is_in_person'],
        'locationNotes' => $row['location_notes']
    ];
}

if (!empty($dateAppts)) {
    $mainAppt = formatMainAppointment($dateAppts[0]);
    for ($i = 1; $i < count($dateAppts); $i++) {
        $row = $dateAppts[$i];
        $modeStr = $row['is_in_person'] ? 'In person' : 'Video visit';
        $typeStr = $row['appointment_type'] ?: 'Routine antenatal';
        $otherAppts[] = [
            'id' => (string)$row['id'],
            'patientName' => $row['patient_name'],
            'timeText' => $row['appointment_time'],
            'visitTypeAndMode' => "{$typeStr} · {$modeStr}",
            'statusText' => $row['status'],
            'isVideo' => !(bool)$row['is_in_person']
        ];
    }
} else {
    // If no appointments on selected date, use the next appointment overall if available
    $mainAppt = formatMainAppointment($nextRow);
}

// Format the next appointment specifically for the nextAppointment field
$nextApptFormatted = $nextRow ? formatMainAppointment($nextRow) : $mainAppt;

// 6. Build summary note with real counts
$isToday = ($selectedDate === date('Y-m-d'));
$prefix = $isToday ? ("Today, " . date('d M', strtotime($selectedDate))) : date('l, d M', strtotime($selectedDate));

if ($totalVisits > 0) {
    $nextPatientStr = !empty($dateAppts[0]['patient_name'])
        ? ". Next: {$dateAppts[0]['patient_name']} at {$dateAppts[0]['appointment_time']}."
        : ".";
    $summaryNote = "{$prefix} · {$totalVisits} visits; {$completedVisits} completed, {$upcomingVisits} upcoming{$nextPatientStr}";
} else {
    $summaryNote = "{$prefix} · 0 visits; 0 completed, 0 upcoming.";
}

respond(200, [
    'success' => true,
    'data' => [
        'doctorName' => $doctorName,
        'dateText' => $dateText,
        'selectedDate' => $selectedDate,
        'dateTabs' => $dateTabs,
        'mainAppointment' => $mainAppt,
        'nextAppointment' => $nextApptFormatted,
        'otherAppointments' => $otherAppts,
        'appointments' => $allAppointmentsList,
        'completedVisitsCount' => $completedVisits,
        'upcomingVisitsCount' => $upcomingVisits,
        'totalVisitsCount' => $totalVisits,
        'visitsSummaryNote' => $summaryNote
    ]
]);
