<?php
// =============================================================================
// Momora AI - Profile Photo Upload API
// Endpoint: /profile/profile_photo_upload.php
// Method: POST (multipart/form-data)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

$input = getInput();

// 1. Validate user_id
$userIdRaw = $input['user_id'] ?? ($_POST['user_id'] ?? null);
if ($userIdRaw === null || trim((string)$userIdRaw) === '') {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

$userId = filter_var($userIdRaw, FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

try {
    // 2. Check if user exists in database
    $userStmt = $pdo->prepare('SELECT id, name, is_active FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        respond(404, [
            'success' => false,
            'message' => 'User not found'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    // 3. Locate uploaded file (supports field names: photo, profile_photo, image, file)
    $fileKey = null;
    foreach (['photo', 'profile_photo', 'image', 'file'] as $k) {
        if (isset($_FILES[$k]) && is_array($_FILES[$k]) && !empty($_FILES[$k]['name'])) {
            $fileKey = $k;
            break;
        }
    }

    if (!$fileKey) {
        respond(400, [
            'success' => false,
            'message' => 'No photo file uploaded. Please upload an image with field name "photo" or "profile_photo"'
        ]);
    }

    $file = $_FILES[$fileKey];

    // Check PHP upload error
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
            'message' => $errorMessages[$file['error']] ?? 'File upload failed with error code ' . $file['error']
        ]);
    }

    // 4. Validate file size (max 5 MB)
    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxFileSize) {
        respond(422, [
            'success' => false,
            'message' => 'Photo file size exceeds maximum limit of 5 MB'
        ]);
    }

    // 5. Validate file extension
    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExtensions)) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid file extension (' . $extension . '). Allowed formats: JPG, JPEG, PNG, WEBP'
        ]);
    }

    // 6. Validate genuine MIME type via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($detectedMime, $allowedMimes)) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid file MIME type (' . $detectedMime . '). Only genuine images are permitted'
        ]);
    }

    // 7. Verify image integrity via getimagesize()
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        respond(422, [
            'success' => false,
            'message' => 'The uploaded file is not a valid image'
        ]);
    }

    // 8. Prepare target storage directory
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // 9. Generate secure sanitized filename: profile_{userId}_{timestamp}_{random}.{ext}
    $saveExtension = ($extension === 'jpeg') ? 'jpg' : $extension;
    $newFilename = 'profile_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $saveExtension;
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;
    $relativeFilePath = 'uploads/profiles/' . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        respond(500, [
            'success' => false,
            'message' => 'Failed to move uploaded file to destination directory'
        ]);
    }

    // 10. Check if user already has a previous photo and remove it
    $prevStmt = $pdo->prepare('SELECT id, profile_photo FROM profiles WHERE user_id = ?');
    $prevStmt->execute([$userId]);
    $existingProfile = $prevStmt->fetch();

    if ($existingProfile && !empty($existingProfile['profile_photo'])) {
        $oldRelative = $existingProfile['profile_photo'];
        $oldFullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldRelative);
        if (file_exists($oldFullPath) && is_file($oldFullPath)) {
            @unlink($oldFullPath);
        }
    }

    // 11. Save photo path in profiles table
    if ($existingProfile) {
        $updateStmt = $pdo->prepare('UPDATE profiles SET profile_photo = ?, updated_at = NOW() WHERE user_id = ?');
        $updateStmt->execute([$relativeFilePath, $userId]);
    } else {
        $patientCode = 'P-' . (10000 + $userId);
        $insertStmt = $pdo->prepare('INSERT INTO profiles (user_id, patient_code, profile_photo) VALUES (?, ?, ?)');
        $insertStmt->execute([$userId, $patientCode, $relativeFilePath]);
    }

    // 12. Build public URL
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $publicUrl = $scheme . '://' . $host . '/momora-AI/' . $relativeFilePath;

    respond(200, [
        'success' => true,
        'message' => 'Profile photo uploaded successfully',
        'data' => [
            'user_id' => $userId,
            'profile_photo' => $relativeFilePath,
            'photo_url' => $publicUrl,
            'filename' => $newFilename,
            'file_size' => filesize($destination),
            'mime_type' => $detectedMime,
            'image_width' => $imageInfo[0] ?? null,
            'image_height' => $imageInfo[1] ?? null
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
