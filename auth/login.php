<?php
// =============================================================================
// Momora AI - User Login Endpoint
// Supports: Patient, Doctor, Admin roles
// =============================================================================

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Use POST method']);
}

$input = getInput();

$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';
$requestedRole = strtolower(trim($input['role'] ?? ''));

if (empty($email) || empty($password)) {
    respond(422, [
        'success' => false,
        'message' => 'Please provide both email and password'
    ]);
}

$stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash, role, is_active
     FROM users WHERE email = ?'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    respond(401, [
        'success' => false,
        'message' => 'Invalid email or password'
    ]);
}

if ((int)$user['is_active'] !== 1) {
    respond(403, [
        'success' => false,
        'message' => 'Your account has been deactivated. Please contact support.'
    ]);
}

// Check role if role was specified
if (!empty($requestedRole) && strtolower($user['role']) !== $requestedRole) {
    // If user exists with another role, inform them
    respond(403, [
        'success' => false,
        'message' => "This account is registered as a {$user['role']}. Please switch to the {$user['role']} login tab."
    ]);
}

// Generate session token
$tokenData = generateUserToken($pdo, $user['id'], $_SERVER['HTTP_USER_AGENT'] ?? 'Android App');

// Fetch extra role-specific metadata
$extra = [];
if ($user['role'] === 'patient') {
    $pStmt = $pdo->prepare(
        'SELECT p.patient_code, p.blood_group, d.name AS doctor_name
         FROM profiles p
         LEFT JOIN users d ON d.id = p.assigned_doctor_id
         WHERE p.user_id = ?'
    );
    $pStmt->execute([$user['id']]);
    $pData = $pStmt->fetch();
    if ($pData) {
        $extra['patient_code'] = $pData['patient_code'] ?? 'P-' . $user['id'];
        $extra['doctor_name'] = $pData['doctor_name'] ?? 'Dr Amelia Chen';
    }
} elseif ($user['role'] === 'doctor') {
    $dStmt = $pdo->prepare('SELECT specialty, license_id, clinic FROM doctor_profiles WHERE user_id = ?');
    $dStmt->execute([$user['id']]);
    $dData = $dStmt->fetch();
    if ($dData) {
        $extra['specialty'] = $dData['specialty'];
        $extra['license_id'] = $dData['license_id'];
        $extra['clinic'] = $dData['clinic'];
    }
}

respond(200, [
    'success' => true,
    'message' => 'Login successful',
    'token' => $tokenData['token'],
    'expires_at' => $tokenData['expires_at'],
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'patient_code' => $extra['patient_code'] ?? null,
        'doctor_name' => $extra['doctor_name'] ?? null,
        'specialty' => $extra['specialty'] ?? null,
        'clinic' => $extra['clinic'] ?? null
    ]
]);
