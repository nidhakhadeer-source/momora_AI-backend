<?php
// =============================================================================
// Momora AI - Medical Reports API
// Endpoints:
//   GET  /reports/reports.php                 (List authenticated patient's reports)
//   POST /reports/reports.php                 (Upload report file or metadata)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// Authenticate patient
$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

// -----------------------------------------------------------------------------
// POST: Upload Patient Medical Report
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a document file is uploaded via $_FILES
    $fileKey = null;
    foreach (['file', 'report', 'document', 'report_file', 'photo'] as $k) {
        if (isset($_FILES[$k]) && is_array($_FILES[$k]) && !empty($_FILES[$k]['name'])) {
            $fileKey = $k;
            break;
        }
    }

    if ($fileKey !== null) {
        $file = $_FILES[$fileKey];

        // 1. Check PHP upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server upload limit',
                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form MAX_FILE_SIZE limit',
                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload'
            ];
            respond(400, [
                'success' => false,
                'message' => $errorMessages[$file['error']] ?? ('File upload failed with error code ' . $file['error'])
            ]);
        }

        // 2. Validate file size (max 10 MB)
        $maxFileSize = 10 * 1024 * 1024; // 10 MB
        if ($file['size'] > $maxFileSize) {
            respond(422, [
                'success' => false,
                'message' => 'File size exceeds maximum allowed limit of 10 MB'
            ]);
        }

        if ($file['size'] <= 0) {
            respond(422, [
                'success' => false,
                'message' => 'Uploaded file is empty'
            ]);
        }

        // 3. Validate file extension
        $originalFilename = basename($file['name']);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowedExtensions, true)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid file extension (' . $extension . '). Allowed formats: PDF, JPG, JPEG, PNG'
            ]);
        }

        // Check for dangerous double extensions (e.g. evil.php.pdf, script.exe.jpg)
        if (preg_match('/(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|pl|py|jsp|asp|sh|cgi|exe|bat|cmd|vbs|js)\./', $originalFilename)) {
            respond(422, [
                'success' => false,
                'message' => 'Unsafe file naming detected'
            ]);
        }

        // 4. Validate genuine MIME type via finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png']
        ];

        $validMimesForExt = $allowedMimes[$extension] ?? [];
        if (!in_array($detectedMime, $validMimesForExt, true)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid file MIME type (' . $detectedMime . ') for extension .' . $extension
            ]);
        }

        // 5. Deep file integrity validation
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                respond(422, [
                    'success' => false,
                    'message' => 'The uploaded file is not a valid image'
                ]);
            }
        } elseif ($extension === 'pdf') {
            $fh = @fopen($file['tmp_name'], 'rb');
            if ($fh) {
                $header = fread($fh, 5);
                fclose($fh);
                if ($header !== '%PDF-') {
                    respond(422, [
                        'success' => false,
                        'message' => 'The uploaded file is not a valid PDF document'
                    ]);
                }
            }
        }

        // 6. Ensure target directory exists
        $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 7. Generate unique safe server filename: report_{userId}_{timestamp}_{randomHex}.{ext}
        $saveExt = ($extension === 'jpeg') ? 'jpg' : $extension;
        $fileType = ($saveExt === 'pdf') ? 'PDF' : strtoupper($saveExt);
        $newFilename = 'report_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $saveExt;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;
        $relativeFilePath = 'uploads/reports/' . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            respond(500, [
                'success' => false,
                'message' => 'Failed to store report file on server'
            ]);
        }

        // 8. Prepare metadata
        $title = trim($_POST['title'] ?? ($_POST['fileName'] ?? ''));
        if ($title === '') {
            $title = pathinfo($originalFilename, PATHINFO_FILENAME);
            $title = ucwords(preg_replace('/[_\-]+/', ' ', $title));
        }

        $category = strtoupper(trim($_POST['category'] ?? 'LAB_RESULTS'));
        if (!in_array($category, ['ALL', 'ULTRASOUND', 'LAB_RESULTS'], true)) {
            $category = 'LAB_RESULTS';
        }

        $notes = trim($_POST['description'] ?? ($_POST['notes'] ?? ''));
        $fileSizeBytes = filesize($destination);
        $formattedSize = formatBytes($fileSizeBytes);
        $dateAndSize = date('d M Y') . ' · ' . $fileType . ' · ' . $formattedSize;

        // 9. Save in database
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO medical_reports
                 (user_id, title, original_filename, file_path, file_type, file_size, mime_type, date_and_size_text, category, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userId,
                $title,
                $originalFilename,
                $relativeFilePath,
                $fileType,
                $fileSizeBytes,
                $detectedMime,
                $dateAndSize,
                $category,
                $notes !== '' ? $notes : null
            ]);
            $newReportId = (int)$pdo->lastInsertId();

            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $downloadUrl = $scheme . '://' . $host . '/momora-AI/reports/download.php?id=' . $newReportId;

            respond(201, [
                'success' => true,
                'message' => 'Medical report uploaded successfully',
                'reportId' => (string)$newReportId,
                'data' => [
                    'id' => (string)$newReportId,
                    'report_id' => $newReportId,
                    'title' => $title,
                    'category' => $category,
                    'original_filename' => $originalFilename,
                    'originalFilename' => $originalFilename,
                    'file_type' => $fileType,
                    'fileType' => $fileType,
                    'file_size' => $fileSizeBytes,
                    'fileSize' => $fileSizeBytes,
                    'file_size_formatted' => $formattedSize,
                    'date_and_size_text' => $dateAndSize,
                    'dateAndSizeText' => $dateAndSize,
                    'description' => $notes,
                    'notes' => $notes,
                    'download_url' => $downloadUrl,
                    'file_url' => $downloadUrl,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (PDOException $e) {
            // Cleanup uploaded file on DB failure
            if (file_exists($destination)) {
                @unlink($destination);
            }
            respond(500, [
                'success' => false,
                'message' => 'Database operation failed: ' . $e->getMessage()
            ]);
        }
    } else {
        // Fallback: Legacy metadata-only upload (application/x-www-form-urlencoded or JSON)
        $input = getInput();
        $title = trim($input['fileName'] ?? ($input['title'] ?? 'Lab_Results.pdf'));
        $category = strtoupper(trim($input['category'] ?? 'LAB_RESULTS'));
        if (!in_array($category, ['ALL', 'ULTRASOUND', 'LAB_RESULTS'], true)) {
            $category = 'LAB_RESULTS';
        }

        $notes = trim($input['description'] ?? ($input['notes'] ?? ''));
        $dateAndSize = date('d M Y') . ' · PDF · 1.2 MB';

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO medical_reports
                 (user_id, title, original_filename, file_type, date_and_size_text, category, notes, created_at)
                 VALUES (?, ?, ?, "PDF", ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $userId,
                $title,
                $title,
                $dateAndSize,
                $category,
                $notes !== '' ? $notes : null
            ]);
            $newReportId = (int)$pdo->lastInsertId();

            respond(201, [
                'success' => true,
                'message' => 'Report uploaded successfully',
                'reportId' => (string)$newReportId,
                'data' => [
                    'id' => (string)$newReportId,
                    'report_id' => $newReportId,
                    'title' => $title,
                    'category' => $category,
                    'dateAndSizeText' => $dateAndSize,
                    'fileType' => 'PDF',
                    'description' => $notes
                ]
            ]);
        } catch (PDOException $e) {
            respond(500, [
                'success' => false,
                'message' => 'Database operation failed: ' . $e->getMessage()
            ]);
        }
    }
}

// -----------------------------------------------------------------------------
// GET: Patient Report List
// -----------------------------------------------------------------------------
$input = getInput();
$categoryFilter = strtoupper(trim($_GET['category'] ?? ($input['category'] ?? 'ALL')));

$sql = 'SELECT id, user_id, title, original_filename, file_path, file_type, file_size, mime_type, date_and_size_text, category, notes, created_at
        FROM medical_reports
        WHERE user_id = ?';
$params = [$userId];

if (!empty($categoryFilter) && $categoryFilter !== 'ALL' && in_array($categoryFilter, ['ULTRASOUND', 'LAB_RESULTS'], true)) {
    $sql .= ' AND category = ?';
    $params[] = $categoryFilter;
}

$sql .= ' ORDER BY created_at DESC, id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDownloadUrl = $scheme . '://' . $host . '/momora-AI/reports/download.php?id=';

$items = [];
foreach ($reports as $r) {
    $cleanTitle = preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['title']);
    $cleanDateAndSize = preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['date_and_size_text']);
    $fileType = strtoupper($r['file_type'] ?: 'PDF');

    $hasFile = !empty($r['file_path']) && file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $r['file_path']));
    $downloadUrl = $hasFile ? ($baseDownloadUrl . $r['id']) : null;
    $origName = !empty($r['original_filename']) ? $r['original_filename'] : $r['title'];
    $fileSize = !empty($r['file_size']) ? (int)$r['file_size'] : null;

    $items[] = [
        'id' => (string)$r['id'],
        'report_id' => (int)$r['id'],
        'title' => $cleanTitle,
        'category' => strtoupper($r['category']),
        'original_filename' => $origName,
        'originalFilename' => $origName,
        'upload_date' => $r['created_at'],
        'uploadDate' => $r['created_at'],
        'file_type' => $fileType,
        'fileType' => $fileType,
        'file_size' => $fileSize,
        'fileSize' => $fileSize,
        'file_size_formatted' => formatBytes($fileSize),
        'date_and_size_text' => $cleanDateAndSize,
        'dateAndSizeText' => $cleanDateAndSize,
        'mime_type' => $r['mime_type'],
        'description' => $r['notes'] ?? '',
        'notes' => $r['notes'] ?? '',
        'has_file' => $hasFile,
        'download_url' => $downloadUrl,
        'file_url' => $downloadUrl
    ];
}

respond(200, [
    'success' => true,
    'total' => count($items),
    'data' => $items
]);
