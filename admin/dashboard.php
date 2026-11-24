<?php
// =============================================================================
// Momora AI - Admin Dashboard Statistics Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'admin');

// Total patients
$pStmt = $pdo->query(
    'SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
     FROM users WHERE role = "patient"'
);
$pStats = $pStmt->fetch();

$totalPatients = (int)($pStats['total'] ?? 0);
$activePatients = (int)($pStats['active'] ?? 0);
$inactivePatients = (int)($pStats['inactive'] ?? 0);

// Total doctors
$dStmt = $pdo->query(
    'SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
     FROM users WHERE role = "doctor"'
);
$dStats = $dStmt->fetch();

$totalDoctors = (int)($dStats['total'] ?? 0);
$activeDoctors = (int)($dStats['active'] ?? 0);
$inactiveDoctors = (int)($dStats['inactive'] ?? 0);

// Fetch real monthly patient registration counts for the last 4 calendar months
$monthsMap = [];
for ($i = 3; $i >= 0; $i--) {
    $time = strtotime("-{$i} month");
    $ym = date('Y-m', $time);
    $mLabel = date('M', $time);
    $monthsMap[$ym] = [
        'label' => $mLabel,
        'count' => 0.0
    ];
}

$trendStmt = $pdo->query(
    'SELECT
        DATE_FORMAT(created_at, "%Y-%m") as ym,
        COUNT(*) as patient_count
     FROM users
     WHERE role = "patient"
     GROUP BY ym'
);
while ($row = $trendStmt->fetch()) {
    $ym = $row['ym'];
    if (isset($monthsMap[$ym])) {
        $monthsMap[$ym]['count'] = (float)$row['patient_count'];
    }
}

$registrationTrendValues = array_values(array_column($monthsMap, 'count'));
$registrationMonths = array_values(array_column($monthsMap, 'label'));

$updatedText = 'Administrator · Updated ' . date('d M Y, H:i');

respond(200, [
    'success' => true,
    'data' => [
        'lastUpdatedText' => $updatedText,
        'totalPatientsCount' => $totalPatients,
        'activePatientsCount' => $activePatients,
        'inactivePatientsCount' => $inactivePatients,
        'totalDoctorsCount' => $totalDoctors,
        'activeDoctorsCount' => $activeDoctors,
        'inactiveDoctorsCount' => $inactiveDoctors,
        'registrationTrendValues' => $registrationTrendValues,
        'registrationMonths' => $registrationMonths
    ]
]);

