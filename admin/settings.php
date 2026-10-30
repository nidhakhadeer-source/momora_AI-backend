<?php
// =============================================================================
// Momora AI - Admin Settings & Logout Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'admin');
$adminId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logout admin
    $token = getBearerToken();
    if ($token) {
        $stmt = $pdo->prepare('DELETE FROM user_tokens WHERE token = ?');
        $stmt->execute([$token]);
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
$settings = $stmt->fetch();

$isMfa = (bool)($settings['is_mfa_enabled'] ?? true);
$timeout = $settings['session_timeout_minutes'] ?? 15;
$updatedDate = $settings['updated_at'] ? date('d M Y', strtotime($settings['updated_at'])) : '01 Oct 2026';

respond(200, [
    'success' => true,
    'data' => [
        'adminName' => $user['name'],
        'adminEmailText' => "{$user['email']} · Administrator",
        'passwordLastUpdatedText' => "Last updated {$updatedDate}",
        'isMfaEnabled' => $isMfa,
        'mfaStatusText' => 'Authenticator app connected',
        'sessionTimeoutText' => "Automatically sign out after {$timeout} minutes",
        'activeSessionsText' => '1 device · This Android device',
        'appVersionText' => 'Momora AI · v1.0.0 · Ends your session on this device.'
    ]
]);

