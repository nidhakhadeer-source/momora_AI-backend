<?php
// =============================================================================
// Momora AI - Admin Active Sessions Management Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);
$adminId = (int)$user['id'];
$currentToken = $user['token'] ?? '';

// Check if device_info column exists in user_tokens
$colStmt = $pdo->query("SHOW COLUMNS FROM user_tokens LIKE 'device_info'");
$hasDeviceInfo = (bool)$colStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $action = $input['action'] ?? '';
    $sessionId = filter_var($input['sessionId'] ?? ($input['session_id'] ?? ($input['id'] ?? 0)), FILTER_VALIDATE_INT);

    if ($action !== 'revoke' || !$sessionId) {
        respond(422, [
            'success' => false,
            'message' => 'Action "revoke" and a valid sessionId are required'
        ]);
    }

    // Fetch this admin's session row using prepared statement
    $stmt = $pdo->prepare('SELECT id, token FROM user_tokens WHERE id = ? AND user_id = ?');
    $stmt->execute([$sessionId, $adminId]);
    $sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sessionRow) {
        respond(404, [
            'success' => false,
            'message' => 'Session not found or does not belong to this admin'
        ]);
    }

    // Do not delete current active token
    if (!empty($currentToken) && $sessionRow['token'] === $currentToken) {
        respond(400, [
            'success' => false,
            'message' => 'Cannot revoke the current active session. Use logout to end this session.'
        ]);
    }

    // Delete only that admin's own token row using prepared statement
    $delStmt = $pdo->prepare('DELETE FROM user_tokens WHERE id = ? AND user_id = ?');
    $delStmt->execute([$sessionId, $adminId]);

    respond(200, [
        'success' => true,
        'message' => 'Session revoked successfully'
    ]);
}

// GET: list this admin's rows from user_tokens
$sql = $hasDeviceInfo
    ? 'SELECT id, token, created_at, expires_at, device_info FROM user_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC'
    : 'SELECT id, token, created_at, expires_at FROM user_tokens WHERE user_id = ? AND expires_at > NOW() ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute([$adminId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sessions = [];
foreach ($rows as $row) {
    $item = [
        'id' => (int)$row['id'],
        'created_at' => $row['created_at'],
        'expires_at' => $row['expires_at'],
        'is_current' => (!empty($currentToken) && $row['token'] === $currentToken)
    ];
    if ($hasDeviceInfo) {
        $item['device_info'] = $row['device_info'] ?: 'Unknown Device';
    }
    $sessions[] = $item;
}

respond(200, [
    'success' => true,
    'data' => $sessions
]);
