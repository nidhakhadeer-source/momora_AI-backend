<?php
// =============================================================================
// Momora AI - Doctor Profile Update API
// Endpoint: /doctor/profile_update.php
// Method: POST (?user_id= & form/json fields)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

/**
 * Build absolute photo URL relative to incoming request without hard-coding localhost
 */
function buildDoctorPhotoUrl($photoPath) {
    if (empty($photoPath)) {
        return null;
    }
    if (preg_match('/^https?:\/\//i', $photoPath)) {
        return $photoPath;
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);
    $appBaseDir = dirname($scriptDir);
    if ($appBaseDir === '/' || $appBaseDir === '\\' || $appBaseDir === '.') {
        $appBaseDir = '';
    }

    $cleanPath = ltrim(str_replace('\\', '/', $photoPath), '/');

    if (!empty($host)) {
        return $scheme . '://' . $host . $appBaseDir . '/' . $cleanPath;
    }

    return $appBaseDir . '/' . $cleanPath;
}

try {
    $pdo->exec("SET NAMES utf8mb4");

    $input = getInput();
    $userIdRaw = $input['user_id'] ?? ($_GET['user_id'] ?? ($_POST['user_id'] ?? null));

    // If user_id is omitted, check for authenticated token fallback
    if ($userIdRaw === null || trim((string)$userIdRaw) === '') {
        $token = getBearerToken();
        if (!empty($token)) {
            $tStmt = $pdo->prepare(
                'SELECT u.id, u.role FROM user_tokens t JOIN users u ON u.id = t.user_id WHERE t.token = ? AND t.expires_at > NOW()'
            );
            $tStmt->execute([$token]);
            $tUser = $tStmt->fetch();
            if ($tUser) {
                $userIdRaw = $tUser['id'];
            }
        }
    }

    $userId = filter_var($userIdRaw, FILTER_VALIDATE_INT);
    if ($userId === false || $userId <= 0) {
        respond(400, [
            'success' => false,
            'message' => 'Valid user_id is required'
        ]);
    }

    // Verify user exists and is a doctor
    $userStmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user || $user['role'] !== 'doctor') {
        respond(404, [
            'success' => false,
            'message' => 'Doctor profile not found'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    // Collect updates (explicitly excluding email and role)
    $nameUpdate = null;
    $docUpdates = [];

    // Optional fullName update on users table (not email or role)
    if (array_key_exists('fullName', $input) || array_key_exists('full_name', $input) || array_key_exists('name', $input)) {
        $nameVal = trim($input['fullName'] ?? ($input['full_name'] ?? $input['name']));
        if ($nameVal === '') {
            respond(422, [
                'success' => false,
                'message' => 'Full name cannot be empty'
            ]);
        }
        if (mb_strlen($nameVal) > 100) {
            respond(422, [
                'success' => false,
                'message' => 'Full name must not exceed 100 characters'
            ]);
        }
        $nameUpdate = $nameVal;
    }

    // doctor_profiles fields: specialty
    if (array_key_exists('specialty', $input)) {
        $val = trim($input['specialty']);
        if ($val === '') {
            respond(422, [
                'success' => false,
                'message' => 'Specialty cannot be empty'
            ]);
        }
        if (mb_strlen($val) > 150) {
            respond(422, [
                'success' => false,
                'message' => 'Specialty must not exceed 150 characters'
            ]);
        }
        $docUpdates['specialty'] = $val;
    }

    // licenseId / license_id
    if (array_key_exists('licenseId', $input) || array_key_exists('license_id', $input)) {
        $val = trim($input['licenseId'] ?? $input['license_id']);
        if ($val === '') {
            respond(422, [
                'success' => false,
                'message' => 'License ID cannot be empty'
            ]);
        }
        if (mb_strlen($val) > 100) {
            respond(422, [
                'success' => false,
                'message' => 'License ID must not exceed 100 characters'
            ]);
        }
        $docUpdates['license_id'] = $val;
    }

    // clinic
    if (array_key_exists('clinic', $input)) {
        $val = trim($input['clinic']);
        if ($val === '') {
            respond(422, [
                'success' => false,
                'message' => 'Clinic name cannot be empty'
            ]);
        }
        if (mb_strlen($val) > 150) {
            respond(422, [
                'success' => false,
                'message' => 'Clinic name must not exceed 150 characters'
            ]);
        }
        $docUpdates['clinic'] = $val;
    }

    // gender
    if (array_key_exists('gender', $input)) {
        $val = trim($input['gender']);
        if (mb_strlen($val) > 20) {
            respond(422, [
                'success' => false,
                'message' => 'Gender must not exceed 20 characters'
            ]);
        }
        $docUpdates['gender'] = $val !== '' ? $val : null;
    }

    // phone / phone_number
    if (array_key_exists('phone', $input) || array_key_exists('phone_number', $input)) {
        $val = trim($input['phone'] ?? $input['phone_number']);
        if (mb_strlen($val) > 30) {
            respond(422, [
                'success' => false,
                'message' => 'Phone number must not exceed 30 characters'
            ]);
        }
        $docUpdates['phone'] = $val !== '' ? $val : null;
    }

    // address
    if (array_key_exists('address', $input)) {
        $val = trim($input['address']);
        if (mb_strlen($val) > 255) {
            respond(422, [
                'success' => false,
                'message' => 'Address must not exceed 255 characters'
            ]);
        }
        $docUpdates['address'] = $val !== '' ? $val : null;
    }

    // qualification
    if (array_key_exists('qualification', $input)) {
        $val = trim($input['qualification']);
        if (mb_strlen($val) > 150) {
            respond(422, [
                'success' => false,
                'message' => 'Qualification must not exceed 150 characters'
            ]);
        }
        $docUpdates['qualification'] = $val !== '' ? $val : null;
    }

    // experienceYears / experience_years
    if (array_key_exists('experienceYears', $input) || array_key_exists('experience_years', $input)) {
        $raw = $input['experienceYears'] ?? $input['experience_years'];
        if ($raw === null || trim((string)$raw) === '') {
            $docUpdates['experience_years'] = null;
        } else {
            if (!is_numeric($raw) || (int)$raw < 0 || (int)$raw > 80) {
                respond(422, [
                    'success' => false,
                    'message' => 'Experience years must be a valid number between 0 and 80'
                ]);
            }
            $docUpdates['experience_years'] = (int)$raw;
        }
    }

    // Photo upload or photo URL update
    $uploadedFileKey = null;
    foreach (['photo', 'profile_photo', 'image', 'file'] as $k) {
        if (isset($_FILES[$k]) && is_array($_FILES[$k]) && !empty($_FILES[$k]['name'])) {
            $uploadedFileKey = $k;
            break;
        }
    }

    if ($uploadedFileKey && $_FILES[$uploadedFileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$uploadedFileKey];
        if ($file['size'] > 5 * 1024 * 1024) {
            respond(422, [
                'success' => false,
                'message' => 'Photo file size exceeds maximum limit of 5 MB'
            ]);
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid photo extension (' . $ext . '). Allowed formats: JPG, JPEG, PNG, WEBP'
            ]);
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid file MIME type (' . $mime . '). Only genuine images are permitted'
            ]);
        }
        $imgSize = @getimagesize($file['tmp_name']);
        if ($imgSize === false) {
            respond(422, [
                'success' => false,
                'message' => 'The uploaded file is not a valid image'
            ]);
        }

        $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $saveExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        $newFilename = 'doctor_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $saveExt;
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(500, [
                'success' => false,
                'message' => 'Failed to save uploaded photo'
            ]);
        }

        // Remove old photo if exists
        $prevStmt = $pdo->prepare('SELECT profile_photo FROM doctor_profiles WHERE user_id = ?');
        $prevStmt->execute([$userId]);
        $oldPhoto = $prevStmt->fetchColumn();
        if ($oldPhoto && !preg_match('/^https?:\/\//i', $oldPhoto)) {
            $oldPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldPhoto);
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $docUpdates['profile_photo'] = 'uploads/profiles/' . $newFilename;

    } elseif (array_key_exists('photoUrl', $input) || array_key_exists('photo_url', $input) || array_key_exists('profile_photo', $input) || array_key_exists('profilePhoto', $input)) {
        $rawPhoto = trim($input['photoUrl'] ?? ($input['photo_url'] ?? ($input['profile_photo'] ?? $input['profilePhoto'])));
        if ($rawPhoto === '') {
            $docUpdates['profile_photo'] = null;
        } else {
            // If full URL was provided, strip scheme + host + app prefix down to relative path
            if (preg_match('#/(uploads/profiles/[^?\#]+)#', $rawPhoto, $m)) {
                $docUpdates['profile_photo'] = $m[1];
            } else {
                if (mb_strlen($rawPhoto) > 255) {
                    respond(422, [
                        'success' => false,
                        'message' => 'Photo URL must not exceed 255 characters'
                    ]);
                }
                $docUpdates['profile_photo'] = $rawPhoto;
            }
        }
    }

    // Ensure at least one valid field was provided to update
    if ($nameUpdate === null && empty($docUpdates)) {
        respond(400, [
            'success' => false,
            'message' => 'No profile fields provided to update'
        ]);
    }

    // Perform database updates inside transaction
    $pdo->beginTransaction();

    if ($nameUpdate !== null) {
        $uStmt = $pdo->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?');
        $uStmt->execute([$nameUpdate, $userId]);
    }

    if (!empty($docUpdates)) {
        $checkStmt = $pdo->prepare('SELECT id FROM doctor_profiles WHERE user_id = ?');
        $checkStmt->execute([$userId]);
        $existingProfile = $checkStmt->fetch();

        if ($existingProfile) {
            $setClauses = [];
            $params = [];
            foreach ($docUpdates as $col => $val) {
                $setClauses[] = "`$col` = ?";
                $params[] = $val;
            }
            $params[] = $userId;
            $sql = 'UPDATE doctor_profiles SET ' . implode(', ', $setClauses) . ', updated_at = NOW() WHERE user_id = ?';
            $upStmt = $pdo->prepare($sql);
            $upStmt->execute($params);
        } else {
            if (!isset($docUpdates['license_id'])) {
                $docUpdates['license_id'] = 'DOC-' . (10000 + $userId);
            }
            if (!isset($docUpdates['specialty'])) {
                $docUpdates['specialty'] = 'Obstetrics';
            }
            if (!isset($docUpdates['clinic'])) {
                $docUpdates['clinic'] = 'Willow Maternity Clinic';
            }
            $docUpdates['user_id'] = $userId;

            $cols = array_keys($docUpdates);
            $placeholders = array_fill(0, count($cols), '?');
            $sql = 'INSERT INTO doctor_profiles (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $placeholders) . ')';
            $inStmt = $pdo->prepare($sql);
            $inStmt->execute(array_values($docUpdates));
        }
    }

    $pdo->commit();

    // Fetch and return the updated profile
    $fetchStmt = $pdo->prepare(
        'SELECT
            u.id,
            u.name AS full_name,
            u.email,
            u.role,
            d.specialty,
            d.license_id,
            d.clinic,
            d.gender,
            d.phone,
            d.address,
            d.qualification,
            d.experience_years,
            d.profile_photo
         FROM users u
         LEFT JOIN doctor_profiles d ON d.user_id = u.id
         WHERE u.id = ?'
    );
    $fetchStmt->execute([$userId]);
    $updated = $fetchStmt->fetch();

    $photoUrl = buildDoctorPhotoUrl($updated['profile_photo'] ?? null);

    respond(200, [
        'success' => true,
        'message' => 'Doctor profile updated successfully',
        'data' => [
            'fullName' => $updated['full_name'],
            'email' => $updated['email'],
            'specialty' => $updated['specialty'] ?: 'Obstetrics',
            'licenseId' => $updated['license_id'] ?: '',
            'clinic' => $updated['clinic'] ?: 'Willow Maternity Clinic',
            'gender' => $updated['gender'] !== null ? $updated['gender'] : null,
            'phone' => $updated['phone'] !== null ? $updated['phone'] : null,
            'address' => $updated['address'] !== null ? $updated['address'] : null,
            'qualification' => $updated['qualification'] !== null ? $updated['qualification'] : null,
            'experienceYears' => $updated['experience_years'] !== null ? (int)$updated['experience_years'] : null,
            'photoUrl' => $photoUrl
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}

