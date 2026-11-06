<?php
// =============================================================================
// Momora AI - Forgot Password Endpoint
// Restricts Admin role from recovering password via public self-service.
// Endpoint: /auth/forgot_password.php
// Method: POST
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// 1. Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

$input = getInput();
$email = strtolower(trim($input['email'] ?? ''));

// 2. Validate email presence
if ($email === '') {
    respond(422, [
        'success' => false,
        'message' => 'Please provide an email address'
    ]);
}

// 3. Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, [
        'success' => false,
        'message' => 'Please provide a valid email address'
    ]);
}

try {
    // 4. Fetch user by email
    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 5. PROFESSOR'S REQUIREMENT: Restrict Admin users from Forgot Password
    if ($user && strtolower(trim($user['role'])) === 'admin') {
        respond(403, [
            'success' => false,
            'message' => 'Password recovery is not available for administrator accounts. Please contact the system administrator.'
        ]);
    }

    // 6. Non-admin recovery flow (patient, doctor):
    // If account exists and is active, generate and store reset token
    if ($user && (int)$user['is_active'] === 1) {
        // Invalidate any older unused reset tokens for this user
        $invStmt = $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE user_id = ? AND is_used = 0');
        $invStmt->execute([$user['id']]);

        // Generate secure 64-hex-character reset token valid for 1 hour
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $insStmt = $pdo->prepare(
            'INSERT INTO password_resets (user_id, email, reset_token, expires_at, is_used)
             VALUES (?, ?, ?, ?, 0)'
        );
        $insStmt->execute([$user['id'], $user['email'], $resetToken, $expiresAt]);
    }

    // Generic response to prevent account enumeration
    respond(200, [
        'success' => true,
        'message' => 'If an account exists for this email, reset instructions have been sent.'
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}

