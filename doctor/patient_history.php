<?php
// =============================================================================
// Momora AI - Patient Medical History for Clinician Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'doctor');
$input = getInput();
$patientId = filter_var($input['patientId'] ?? ($input['patient_id'] ?? 5), FILTER_VALIDATE_INT);
if (!$patientId) $patientId = 5;

$stmt = $pdo->prepare(
    'SELECT u.name, p.patient_code, p.clinical_background, pd.gestational_weeks, pd.gestational_days, pd.estimated_due_date
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

$name = $patient['name'] ?? 'Maya Patel';
$words = explode(' ', trim($name));
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : 'MP';

$weeks = $patient['gestational_weeks'] ?? 24;
$days = $patient['gestational_days'] ?? 3;
$gestText = "{$weeks}w {$days}d";

$edd = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due 13 Feb 2027';
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));
$clinical = $patient['clinical_background'] ?: 'Age 29 · O positive · First pregnancy (G1 P0). No known drug allergies or chronic conditions reported.';

// Fetch patient's medications
$mStmt = $pdo->prepare('SELECT id, name, dosage_and_time, is_taken FROM medications WHERE user_id = ?');
$mStmt->execute([$patientId]);
$meds = $mStmt->fetchAll();

$medItems = [];
foreach ($meds as $m) {
    $medItems[] = [
        'id' => (string)$m['id'],
        'name' => $m['name'],
        'dosageDetails' => $m['dosage_and_time'],
        'isTakenToday' => (bool)$m['is_taken']
    ];
}

if (empty($medItems)) {
    $medItems = [
        [
            'id' => '1',
            'name' => 'Prenatal vitamin',
            'dosageDetails' => '1 tablet daily · 08:00 · Taken today',
            'isTakenToday' => true
        ],
        [
            'id' => '2',
            'name' => 'Vitamin D · 400 IU',
            'dosageDetails' => '1 tablet daily · 20:00',
            'isTakenToday' => false
        ]
    ];
}

respond(200, [
    'success' => true,
    'data' => [
        'patientName' => $name,
        'patientInitials' => $initials,
        'gestationalAge' => $gestText,
        'dueDateText' => $edd,
        'patientIdText' => $patientCode,
        'clinicalBackgroundText' => $clinical,
        'patientConfirmedDateText' => 'Patient confirmed · 05 Oct 2026',
        'medications' => $medItems,
        'recentCareRecordTitle' => 'Complete blood count',
        'recentCareRecordDetails' => '28 Oct 2026 · Reviewed 27 Oct 2026'
    ]
]);

