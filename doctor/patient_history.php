<?php
// =============================================================================
// Momora AI - Patient Medical History for Clinician Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['doctor']);
$input = getInput();
$patientIdRaw = $input['patientId'] ?? ($input['patient_id'] ?? ($_GET['patientId'] ?? ($_GET['patient_id'] ?? null)));
$patientId = filter_var($patientIdRaw, FILTER_VALIDATE_INT);
if ($patientId === false || $patientId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'patientId is required and must be a valid integer'
    ]);
}

$stmt = $pdo->prepare(
    'SELECT u.id, u.name, p.patient_code, p.clinical_background, p.updated_at AS profile_updated_at,
            pd.gestational_weeks, pd.gestational_days, pd.estimated_due_date
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.id = ? AND u.role = "patient"'
);
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    respond(404, [
        'success' => false,
        'message' => 'Patient not found'
    ]);
}

$name = trim($patient['name'] ?? '');
$words = explode(' ', $name);
$initials = (count($words) >= 2) ? (strtoupper($words[0][0]) . strtoupper($words[1][0])) : (mb_strtoupper(mb_substr($name, 0, 2)) ?: 'PT');

$weeks = $patient['gestational_weeks'] ?? 24;
$days = $patient['gestational_days'] ?? 3;
$gestText = "{$weeks}w {$days}d";

$edd = $patient['estimated_due_date'] ? ('Due ' . date('d M Y', strtotime($patient['estimated_due_date']))) : 'Due date not set';
$patientCode = $patient['patient_code'] ?: ('P-' . (10000 + $patientId));
$clinical = $patient['clinical_background'] ?: '';

// Fetch patient's medications strictly from database
$mStmt = $pdo->prepare('SELECT id, name, dosage_and_time, is_taken FROM medications WHERE user_id = ? ORDER BY id ASC');
$mStmt->execute([$patientId]);
$meds = $mStmt->fetchAll();

$medItems = [];
foreach ($meds as $m) {
    $medItems[] = [
        'id' => (string)$m['id'],
        'name' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)$m['name']),
        'dosageDetails' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)($m['dosage_and_time'] ?? '')),
        'isTakenToday' => (bool)$m['is_taken']
    ];
}

// Fetch real reports from medical_reports: id, title, report date, category, size
$rStmt = $pdo->prepare(
    'SELECT id, title, date_and_size_text, category, file_type, created_at
     FROM medical_reports
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$rStmt->execute([$patientId]);
$reportsRows = $rStmt->fetchAll();

$reportsList = [];
foreach ($reportsRows as $r) {
    $reportDate = !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '';
    $size = '1.2 MB';
    if (!empty($r['date_and_size_text'])) {
        $parts = preg_split('/(\s*·\s*|\s*\?\?\s*)/u', $r['date_and_size_text']);
        if (!empty($parts[0])) {
            $reportDate = trim($parts[0]);
        }
        if (count($parts) >= 3) {
            $size = trim($parts[2]);
        } elseif (count($parts) == 2) {
            $size = trim($parts[1]);
        }
    }
    $reportsList[] = [
        'id' => (string)$r['id'],
        'title' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['title']),
        'reportDate' => $reportDate,
        'report_date' => $reportDate,
        'category' => strtoupper($r['category'] ?? 'ALL'),
        'size' => $size
    ];
}

// Fetch real visits from appointments: date, time, status, notes
$vStmt = $pdo->prepare(
    'SELECT id, appointment_date, appointment_time, status, location_notes, appointment_type
     FROM appointments
     WHERE patient_id = ?
     ORDER BY appointment_date DESC, appointment_time DESC'
);
$vStmt->execute([$patientId]);
$visitsRows = $vStmt->fetchAll();

$visitsList = [];
foreach ($visitsRows as $v) {
    $vDate = !empty($v['appointment_date']) ? date('d M Y', strtotime($v['appointment_date'])) : '';
    $notes = $v['location_notes'] ?: ($v['appointment_type'] ?? '');
    $visitsList[] = [
        'id' => (string)$v['id'],
        'date' => $vDate,
        'time' => $v['appointment_time'] ?? '',
        'status' => $v['status'] ?? 'Scheduled',
        'notes' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)$notes)
    ];
}

// Recent care record from real reports (if any, otherwise null)
$latestReport = $reportsList[0] ?? null;
$recentCareRecordTitle = $latestReport ? $latestReport['title'] : null;
$recentCareRecordDetails = $latestReport ? ($latestReport['reportDate'] . ' · ' . $latestReport['category']) : null;

// Patient confirmed date from profile updated timestamp (if available, otherwise null)
$patientConfirmedDate = !empty($patient['profile_updated_at']) ? ('Patient confirmed · ' . date('d M Y', strtotime($patient['profile_updated_at']))) : null;

respond(200, [
    'success' => true,
    'data' => [
        'patientName' => $name,
        'patientInitials' => $initials,
        'gestationalAge' => $gestText,
        'dueDateText' => $edd,
        'patientIdText' => $patientCode,
        'clinicalBackgroundText' => $clinical,
        'patientConfirmedDateText' => $patientConfirmedDate,
        'medications' => $medItems,
        'reports' => $reportsList,
        'visits' => $visitsList,
        'recentCareRecordTitle' => $recentCareRecordTitle,
        'recentCareRecordDetails' => $recentCareRecordDetails
    ]
]);

