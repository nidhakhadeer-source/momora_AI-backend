<?php
// =============================================================================
// Momora AI - Admin Session Timeout Configuration Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);
$adminId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $rawMinutes = $input['minutes'] ?? ($input['session_timeout_minutes'] ?? null);

    if ($rawMinutes === null || !is_numeric($rawMinutes)) {
        respond(422, [
            'success' => false,
            'message' => 'Timeout minutes required. Allowed values are: 5, 15, 30, 60'
        ]);
    }

    $minutes = (int)$rawMinutes;
    $allowed = [5, 15, 30, 60];

    if (!in_array($minutes, $allowed, true)) {
        respond(422, [
            'success' => false,
            'message' => 'Invalid timeout value. Allowed values are: 5, 15, 30, 60'
        ]);
    }

    // Check if admin_settings record exists
    $checkStmt = $pdo->prepare('SELECT id FROM admin_settings WHERE admin_id = ?');
    $checkStmt->execute([$adminId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE admin_settings SET session_timeout_minutes = ?, updated_at = NOW() WHERE admin_id = ?');
        $stmt->execute([$minutes, $adminId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO admin_settings (admin_id, session_timeout_minutes, is_mfa_enabled, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())');
        $stmt->execute([$adminId, $minutes]);
    }

    respond(200, [
        'success' => true,
        'message' => "Session timeout updated successfully to {$minutes} minutes",
        'data' => [
            'session_timeout_minutes' => $minutes
        ]
    ]);
}

// GET: returns current value
$stmt = $pdo->prepare('SELECT session_timeout_minutes FROM admin_settings WHERE admin_id = ?');
$stmt->execute([$adminId]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$timeout = $settings && isset($settings['session_timeout_minutes']) ? (int)$settings['session_timeout_minutes'] : 15;

respond(200, [
    'success' => true,
    'data' => [
        'session_timeout_minutes' => $timeout
    ]
]);
