<?php
// =============================================================================
// Momora AI - Reset Password Endpoint
// Restricts Admin role from resetting password via public recovery flow.
// Endpoint: /auth/reset_password.php
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
$userIdRaw = $input['user_id'] ?? null;
$resetToken = trim($input['reset_token'] ?? ($input['token'] ?? ''));
$newPassword = $input['new_password'] ?? ($input['password'] ?? '');
$confirmPassword = $input['confirm_password'] ?? null;

// 2. Validate password inputs
if ($newPassword === '') {
    respond(422, [
        'success' => false,
        'message' => 'Please provide a new password'
    ]);
}

if (strlen($newPassword) < 6) {
    respond(422, [
        'success' => false,
        'message' => 'Password must be at least 6 characters long'
    ]);
}

if ($confirmPassword !== null && $newPassword !== $confirmPassword) {
    respond(422, [
        'success' => false,
        'message' => 'Passwords do not match'
    ]);
}

// 3. Validate token presence
if ($resetToken === '') {
    respond(422, [
        'success' => false,
        'message' => 'Please provide a valid reset token'
    ]);
}

try {
    // 4. Identify targeted user
    $user = null;
    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    } elseif ($userIdRaw !== null) {
        $userId = filter_var($userIdRaw, FILTER_VALIDATE_INT);
        if ($userId) {
            $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    } else {
        // Fallback: look up user from reset_token record directly
        $tokStmt = $pdo->prepare('SELECT user_id FROM password_resets WHERE reset_token = ?');
        $tokStmt->execute([$resetToken]);
        $tokRow = $tokStmt->fetch();
        if ($tokRow) {
            $uStmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
            $uStmt->execute([(int)$tokRow['user_id']]);
            $user = $uStmt->fetch();
        }
    }

    if (!$user) {
        respond(404, [
            'success' => false,
            'message' => 'User account not found'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    // 5. PROFESSOR'S REQUIREMENT: Restrict Admin users from Reset Password
    if (strtolower(trim($user['role'])) === 'admin') {
        respond(403, [
            'success' => false,
            'message' => 'Password recovery is not available for administrator accounts. Please contact the system administrator.'
        ]);
    }

    // 6. Verify reset token validity for non-admin
    $checkStmt = $pdo->prepare(
        'SELECT id, user_id, email, reset_token, expires_at, is_used
         FROM password_resets
         WHERE reset_token = ? AND user_id = ?'
    );
    $checkStmt->execute([$resetToken, (int)$user['id']]);
    $resetRecord = $checkStmt->fetch();

    if (!$resetRecord) {
        respond(400, [
            'success' => false,
            'message' => 'Invalid reset token'
        ]);
    }

    if ((int)$resetRecord['is_used'] === 1) {
        respond(400, [
            'success' => false,
            'message' => 'This reset token has already been used'
        ]);
    }

    if (strtotime($resetRecord['expires_at']) < time()) {
        respond(400, [
            'success' => false,
            'message' => 'Reset token has expired'
        ]);
    }

    // 7. Update password hash in users table
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $upStmt->execute([$newHash, (int)$user['id']]);

    // 8. Mark reset token as used
    $useStmt = $pdo->prepare('UPDATE password_resets SET is_used = 1 WHERE id = ?');
    $useStmt->execute([(int)$resetRecord['id']]);

    // 9. Invalidate existing user tokens/sessions for security
    $delTokStmt = $pdo->prepare('DELETE FROM user_tokens WHERE user_id = ?');
    $delTokStmt->execute([(int)$user['id']]);

    respond(200, [
        'success' => true,
        'message' => 'Password has been reset successfully. You can now log in with your new password.',
        'data' => [
            'user_id' => (int)$user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
