<?php
// =============================================================================
// Momora AI - Admin Change Password Endpoint (POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);
$adminId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed. POST required.'
    ]);
}

$input = getInput();
$currentPassword = (string)($input['currentPassword'] ?? ($input['current_password'] ?? ''));
$newPassword = (string)($input['newPassword'] ?? ($input['new_password'] ?? ''));
$confirmPassword = (string)($input['confirmPassword'] ?? ($input['confirm_password'] ?? ''));

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    respond(422, [
        'success' => false,
        'message' => 'All password fields are required: currentPassword, newPassword, confirmPassword'
    ]);
}

if ($newPassword !== $confirmPassword) {
    respond(422, [
        'success' => false,
        'message' => 'New password and confirm password do not match'
    ]);
}

if (strlen($newPassword) < 8) {
    respond(422, [
        'success' => false,
        'message' => 'New password must be at least 8 characters long'
    ]);
}

// Verify current password with password_verify using prepared statement
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData || empty($userData['password_hash'])) {
    respond(404, [
        'success' => false,
        'message' => 'User account not found'
    ]);
}

if (!password_verify($currentPassword, $userData['password_hash'])) {
    respond(400, [
        'success' => false,
        'message' => 'Current password is incorrect'
    ]);
}

// Hash with password_hash and update users using prepared statement
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
$updateStmt->execute([$newHash, $adminId]);

respond(200, [
    'success' => true,
    'message' => 'Password updated successfully'
]);
