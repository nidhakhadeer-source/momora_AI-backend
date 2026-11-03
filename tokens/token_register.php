<?php
// =============================================================================
// Momora AI - Register / Update Device Token Endpoint
// Endpoint: /tokens/token_register.php
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

// 2. Validate user_id
$userIdRaw = $input['user_id'] ?? ($_POST['user_id'] ?? null);
if ($userIdRaw === null || trim((string)$userIdRaw) === '') {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

$userId = filter_var($userIdRaw, FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

try {
    // 3. Confirm user exists in users table
    $uStmt = $pdo->prepare('SELECT id, name, is_active FROM users WHERE id = ?');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();

    if (!$user) {
        respond(404, [
            'success' => false,
            'message' => 'User not found'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    // 4. Validate token
    $token = trim($input['token'] ?? ($input['device_token'] ?? ''));
    if ($token === '') {
        respond(422, [
            'success' => false,
            'message' => 'Device token is required'
        ]);
    }

    if (mb_strlen($token) > 64) {
        respond(422, [
            'success' => false,
            'message' => 'Device token must not exceed 64 characters to match database schema'
        ]);
    }

    // 5. Sanitize device_info & calculate expires_at
    $deviceInfo = trim($input['device_info'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Android Device'));
    if (mb_strlen($deviceInfo) > 255) {
        $deviceInfo = mb_substr($deviceInfo, 0, 255);
    }

    $days = isset($input['expires_in_days']) ? max((int)$input['expires_in_days'], 1) : 30;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));

    if (!empty($input['expires_at'])) {
        $parsedTime = strtotime($input['expires_at']);
        if ($parsedTime !== false && $parsedTime > time()) {
            $expiresAt = date('Y-m-d H:i:s', $parsedTime);
        }
    }

    // 6. Check if token already exists to prevent duplicate active tokens
    $checkStmt = $pdo->prepare('SELECT id, user_id, token, device_info, expires_at, created_at FROM user_tokens WHERE token = ?');
    $checkStmt->execute([$token]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        // Update existing token record
        $updateStmt = $pdo->prepare(
            'UPDATE user_tokens
             SET user_id = ?, device_info = ?, expires_at = ?
             WHERE id = ?'
        );
        $updateStmt->execute([$userId, $deviceInfo, $expiresAt, $existing['id']]);
        $tokenId = (int)$existing['id'];
        $action = 'updated';
        $message = 'Device token updated successfully';
    } else {
        // Insert new token record
        $insertStmt = $pdo->prepare(
            'INSERT INTO user_tokens (user_id, token, device_info, expires_at, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $insertStmt->execute([$userId, $token, $deviceInfo, $expiresAt]);
        $tokenId = (int)$pdo->lastInsertId();
        $action = 'registered';
        $message = 'Device token registered successfully';
    }

    // 7. Return complete token details
    $fetchStmt = $pdo->prepare('SELECT id, user_id, token, device_info, expires_at, created_at FROM user_tokens WHERE id = ?');
    $fetchStmt->execute([$tokenId]);
    $record = $fetchStmt->fetch();

    respond(200, [
        'success' => true,
        'action' => $action,
        'message' => $message,
        'data' => [
            'id' => (int)$record['id'],
            'user_id' => (int)$record['user_id'],
            'token' => $record['token'],
            'device_info' => $record['device_info'],
            'expires_at' => $record['expires_at'],
            'created_at' => $record['created_at'],
            'is_active' => (strtotime($record['expires_at']) > time())
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
