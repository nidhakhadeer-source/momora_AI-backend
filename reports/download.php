<?php
// =============================================================================
// Momora AI - Secure Medical Report View & Download Endpoint
// Endpoint: /reports/download.php?id=<report_id>
// Method: GET
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// 1. Authenticate user (Supports Bearer token or token query param for browser preview)
$user = authenticateUser($pdo);
$requesterId = (int)$user['id'];
$requesterRole = $user['role'];

// 2. Validate report ID
$reportIdRaw = $_GET['id'] ?? ($_GET['report_id'] ?? null);
if ($reportIdRaw === null || trim((string)$reportIdRaw) === '') {
    respond(400, [
        'success' => false,
        'message' => 'Report ID is required'
    ]);
}

$reportId = filter_var($reportIdRaw, FILTER_VALIDATE_INT);
if ($reportId === false || $reportId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'Report ID must be a valid positive integer'
    ]);
}

// 3. Fetch report record from database
$stmt = $pdo->prepare(
    'SELECT id, user_id, title, original_filename, file_path, file_type, file_size, mime_type
     FROM medical_reports
     WHERE id = ?'
);
$stmt->execute([$reportId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    respond(404, [
        'success' => false,
        'message' => 'Medical report not found'
    ]);
}

$reportOwnerId = (int)$report['user_id'];

// 4. Role-based Access Control
// - Patient: Only access their own reports
// - Doctor: Only access reports belonging to assigned patients
// - Admin: Allowed access for administrative auditing
if ($requesterRole === 'patient') {
    if ($requesterId !== $reportOwnerId) {
        respond(403, [
            'success' => false,
            'message' => 'Access denied: You do not have permission to access another patient\'s medical report'
        ]);
    }
} elseif ($requesterRole === 'doctor') {
    if (!isDoctorAssignedToPatient($pdo, $requesterId, $reportOwnerId)) {
        respond(403, [
            'success' => false,
            'message' => 'Access denied: You are not assigned to this patient'
        ]);
    }
} elseif ($requesterRole !== 'admin') {
    respond(403, [
        'success' => false,
        'message' => 'Access denied: Unauthorized role for medical document access'
    ]);
}

// 5. Verify file path and existence on disk
if (empty($report['file_path'])) {
    respond(404, [
        'success' => false,
        'message' => 'No digital document file is associated with this medical report record'
    ]);
}

$baseUploadDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports');
$fullFilePath = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $report['file_path']));

// Prevent path traversal attack
if ($fullFilePath === false || $baseUploadDir === false || !str_starts_with($fullFilePath, $baseUploadDir) || !is_file($fullFilePath)) {
    respond(404, [
        'success' => false,
        'message' => 'Report file not found on the server'
    ]);
}

// 6. Detect MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fullFilePath);
if (empty($mimeType) || $mimeType === 'application/octet-stream') {
    $mimeType = !empty($report['mime_type']) ? $report['mime_type'] : 'application/pdf';
}

// 7. Determine content disposition (inline for in-browser view, attachment for forced download)
$dispositionParam = strtolower(trim($_GET['disposition'] ?? ''));
$isDownload = (isset($_GET['download']) && ($_GET['download'] === '1' || $_GET['download'] === 'true'));
$contentDisposition = ($dispositionParam === 'attachment' || $isDownload) ? 'attachment' : 'inline';

// Sanitize download filename for safe HTTP header
$downloadFilename = !empty($report['original_filename']) ? basename($report['original_filename']) : basename($fullFilePath);
$safeFilename = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $downloadFilename);

// 8. Stream file
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullFilePath));
header('Content-Disposition: ' . $contentDisposition . '; filename="' . $safeFilename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600, must-revalidate');
header('Pragma: private');

readfile($fullFilePath);
exit;
