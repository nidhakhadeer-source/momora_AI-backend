<?php
// =============================================================================
// Momora AI - Doctor Profile Fetch API
// Endpoint: /doctor/profile_fetch.php
// Method: GET (?user_id=)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// Enforce GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Use GET method'
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
    $userIdRaw = $input['user_id'] ?? ($_GET['user_id'] ?? null);

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

    // Query user and doctor_profiles table
    $stmt = $pdo->prepare(
        'SELECT
            u.id,
            u.name AS full_name,
            u.email,
            u.role,
            u.is_active,
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
    $stmt->execute([$userId]);
    $doctor = $stmt->fetch();

    // Return 404 if user not found or is not a doctor
    if (!$doctor || $doctor['role'] !== 'doctor') {
        respond(404, [
            'success' => false,
            'message' => 'Doctor profile not found'
        ]);
    }

    if ((int)$doctor['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    $photoUrl = buildDoctorPhotoUrl($doctor['profile_photo'] ?? null);

    respond(200, [
        'success' => true,
        'data' => [
            'fullName' => $doctor['full_name'],
            'email' => $doctor['email'],
            'specialty' => $doctor['specialty'] ?: 'Obstetrics',
            'licenseId' => $doctor['license_id'] ?: '',
            'clinic' => $doctor['clinic'] ?: 'Willow Maternity Clinic',
            'gender' => $doctor['gender'] !== null ? $doctor['gender'] : null,
            'phone' => $doctor['phone'] !== null ? $doctor['phone'] : null,
            'address' => $doctor['address'] !== null ? $doctor['address'] : null,
            'qualification' => $doctor['qualification'] !== null ? $doctor['qualification'] : null,
            'experienceYears' => $doctor['experience_years'] !== null ? (int)$doctor['experience_years'] : null,
            'photoUrl' => $photoUrl
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}

