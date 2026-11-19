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

$doctorId = (int)$user['id'];
if (!isDoctorAssignedToPatient($pdo, $doctorId, $patientId)) {
    respond(403, [
        'success' => false,
        'message' => 'Access denied: You are not assigned to this patient'
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

// Fetch real reports from medical_reports: id, title, report date, category, size, file metadata, secure download link
$rStmt = $pdo->prepare(
    'SELECT id, title, original_filename, file_path, file_type, file_size, mime_type, date_and_size_text, category, notes, created_at
     FROM medical_reports
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC'
);
$rStmt->execute([$patientId]);
$reportsRows = $rStmt->fetchAll();

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDownloadUrl = $scheme . '://' . $host . '/momora-AI/reports/download.php?id=';

$reportsList = [];
foreach ($reportsRows as $r) {
    $reportDate = !empty($r['created_at']) ? date('d M Y', strtotime($r['created_at'])) : '';
    $size = !empty($r['file_size']) ? formatBytes((int)$r['file_size']) : '1.2 MB';
    if (!empty($r['date_and_size_text'])) {
        $parts = preg_split('/(\s*·\s*|\s*\?\?\s*)/u', $r['date_and_size_text']);
        if (!empty($parts[0])) {
            $reportDate = trim($parts[0]);
        }
        if (count($parts) >= 3 && empty($r['file_size'])) {
            $size = trim($parts[2]);
        } elseif (count($parts) == 2 && empty($r['file_size'])) {
            $size = trim($parts[1]);
        }
    }

    $hasFile = !empty($r['file_path']) && file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $r['file_path']));
    $downloadUrl = $hasFile ? ($baseDownloadUrl . $r['id']) : null;
    $origName = !empty($r['original_filename']) ? $r['original_filename'] : $r['title'];

    $reportsList[] = [
        'id' => (string)$r['id'],
        'report_id' => (int)$r['id'],
        'title' => preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['title']),
        'reportDate' => $reportDate,
        'report_date' => $reportDate,
        'category' => strtoupper($r['category'] ?? 'ALL'),
        'size' => $size,
        'fileType' => strtoupper($r['file_type'] ?: 'PDF'),
        'file_type' => strtoupper($r['file_type'] ?: 'PDF'),
        'originalFilename' => $origName,
        'original_filename' => $origName,
        'fileSize' => !empty($r['file_size']) ? (int)$r['file_size'] : null,
        'file_size' => !empty($r['file_size']) ? (int)$r['file_size'] : null,
        'mimeType' => $r['mime_type'],
        'mime_type' => $r['mime_type'],
        'description' => $r['notes'] ?? '',
        'notes' => $r['notes'] ?? '',
        'hasFile' => $hasFile,
        'has_file' => $hasFile,
        'downloadUrl' => $downloadUrl,
        'download_url' => $downloadUrl,
        'file_url' => $downloadUrl
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

