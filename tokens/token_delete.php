<?php
// =============================================================================
// Momora AI - Delete / Deactivate Device Token Endpoint
// Endpoint: /tokens/token_delete.php
// Method: POST or DELETE
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// 1. Only allow POST or DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST or DELETE method'
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

    // 4. Identify token target by token string or token_id
    $tokenString = trim($input['token'] ?? ($input['device_token'] ?? ''));
    $tokenId = filter_var($input['token_id'] ?? null, FILTER_VALIDATE_INT);

    if (empty($tokenString) && empty($tokenId)) {
        respond(400, [
            'success' => false,
            'message' => 'Please provide either token or token_id to delete'
        ]);
    }

    // 5. Look up token in database
    if (!empty($tokenString)) {
        $stmt = $pdo->prepare('SELECT id, user_id, token, device_info FROM user_tokens WHERE token = ?');
        $stmt->execute([$tokenString]);
    } else {
        $stmt = $pdo->prepare('SELECT id, user_id, token, device_info FROM user_tokens WHERE id = ?');
        $stmt->execute([$tokenId]);
    }
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        respond(404, [
            'success' => false,
            'message' => 'Token not found'
        ]);
    }

    // 6. Security check: Ensure token belongs to the requesting user
    if ((int)$tokenRow['user_id'] !== $userId) {
        respond(403, [
            'success' => false,
            'message' => "Access denied: You do not have permission to delete another user's token"
        ]);
    }

    // 7. Execute deletion
    $delStmt = $pdo->prepare('DELETE FROM user_tokens WHERE id = ? AND user_id = ?');
    $delStmt->execute([(int)$tokenRow['id'], $userId]);

    respond(200, [
        'success' => true,
        'message' => 'Device token deleted successfully',
        'data' => [
            'token_id' => (int)$tokenRow['id'],
            'user_id' => $userId,
            'token' => $tokenRow['token'],
            'device_info' => $tokenRow['device_info']
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
