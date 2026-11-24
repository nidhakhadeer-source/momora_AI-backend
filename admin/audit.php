<?php
// =============================================================================
// Momora AI - Admin Audit Trail Endpoint (GET)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed. GET required.'
    ]);
}

// 1. Sign-in events from user_tokens.created_at
$stmtTokens = $pdo->query(
    'SELECT t.id, t.created_at, t.device_info, u.name, u.role
     FROM user_tokens t
     JOIN users u ON u.id = t.user_id
     ORDER BY t.created_at DESC
     LIMIT 30'
);
$signinRows = $stmtTokens->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($signinRows as $row) {
    $device = !empty($row['device_info']) ? " on {$row['device_info']}" : "";
    $events[] = [
        'description' => "User {$row['name']} ({$row['role']}) signed in{$device}",
        'time' => $row['created_at'],
        'type' => 'SIGN_IN'
    ];
}

// 2. Account activation changes if recorded (users with updated_at > created_at)
$stmtUsers = $pdo->query(
    'SELECT id, name, role, is_active, updated_at, created_at
     FROM users
     WHERE updated_at IS NOT NULL AND updated_at > created_at
     ORDER BY updated_at DESC
     LIMIT 30'
);
$userRows = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

foreach ($userRows as $row) {
    $statusText = ((int)$row['is_active'] === 1) ? 'activated' : 'deactivated';
    $events[] = [
        'description' => "Account for {$row['name']} ({$row['role']}) was {$statusText}",
        'time' => $row['updated_at'],
        'type' => 'ACCOUNT_STATUS'
    ];
}

// Sort newest first by timestamp
usort($events, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

// Latest 30 events
$latest30 = array_slice($events, 0, 30);

respond(200, [
    'success' => true,
    'data' => $latest30
]);
