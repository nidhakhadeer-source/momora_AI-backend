<?php
// =============================================================================
// Momora AI - Admin Settings & Logout Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);
$adminId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logout admin: revoke current active session token
    $token = $user['token'] ?? getBearerToken();
    if ($token) {
        $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE token = ? AND user_id = ?');
        $stmt->execute([$token, $adminId]);
    } else {
        $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE user_id = ?');
        $stmt->execute([$adminId]);
    }

    respond(200, [
        'success' => true,
        'message' => 'Admin logged out successfully'
    ]);
}

// GET Settings
$stmt = $pdo->prepare('SELECT is_mfa_enabled, session_timeout_minutes, updated_at FROM admin_settings WHERE admin_id = ?');
$stmt->execute([$adminId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$isMfa = (bool)($settings['is_mfa_enabled'] ?? true);
$timeout = isset($settings['session_timeout_minutes']) ? (int)$settings['session_timeout_minutes'] : 15;

// Compute lastPasswordChange from users.updated_at if it exists (fallback to created_at)
$stmtUser = $pdo->prepare('SELECT updated_at, created_at FROM users WHERE id = ?');
$stmtUser->execute([$adminId]);
$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);

$passwordTimestamp = !empty($userRow['updated_at']) ? $userRow['updated_at'] : (!empty($userRow['created_at']) ? $userRow['created_at'] : null);
$lastPasswordChange = $passwordTimestamp ? date('d M Y', strtotime($passwordTimestamp)) : null;
$passwordLastUpdatedText = $lastPasswordChange ? "Last updated {$lastPasswordChange}" : "Password set";

// Compute active session count dynamically from user_tokens
$stmtSessions = $pdo->prepare('SELECT COUNT(*) as session_count FROM user_tokens WHERE user_id = ? AND expires_at > NOW()');
$stmtSessions->execute([$adminId]);
$sessionCount = (int)($stmtSessions->fetch(PDO::FETCH_ASSOC)['session_count'] ?? 1);
if ($sessionCount <= 0) {
    $sessionCount = 1; // current active session
}
$activeSessionsText = ($sessionCount === 1) ? "1 active session" : "{$sessionCount} active sessions";

// Dynamic MFA status text based on settings
$mfaStatusText = $isMfa ? 'Two-factor authentication enabled' : 'Two-factor authentication disabled';

respond(200, [
    'success' => true,
    'data' => [
        'adminName' => $user['name'],
        'adminEmailText' => "{$user['email']} · Administrator",
        'lastPasswordChange' => $lastPasswordChange,
        'passwordLastUpdatedText' => $passwordLastUpdatedText,
        'isMfaEnabled' => $isMfa,
        'mfaStatusText' => $mfaStatusText,
        'sessionTimeoutMinutes' => $timeout,
        'sessionTimeoutText' => "Automatically sign out after {$timeout} minutes",
        'sessionCount' => $sessionCount,
        'activeSessionsText' => $activeSessionsText,
        'appVersionText' => 'Momora AI · v1.0.0 · Ends your session on this device.'
    ]
]);
