<?php
// =============================================================================
// Momora AI - Fetch Device Tokens Endpoint
// Endpoint: /tokens/token_fetch.php
// Method: GET or POST
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$input = getInput();

// 1. Validate user_id
$userIdRaw = $input['user_id'] ?? ($_GET['user_id'] ?? ($_POST['user_id'] ?? null));
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
    // 2. Confirm user exists in users table
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

    // 3. Fetch tokens strictly filtered by user_id
    $includeExpired = isset($input['include_expired']) ? (bool)$input['include_expired'] : true;

    $sql = 'SELECT id, user_id, token, device_info, expires_at, created_at
            FROM user_tokens
            WHERE user_id = ?';

    if (!$includeExpired) {
        $sql .= ' AND expires_at > NOW()';
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    $tokens = [];
    $activeCount = 0;
    foreach ($rows as $row) {
        $isActive = (strtotime($row['expires_at']) > time());
        if ($isActive) {
            $activeCount++;
        }
        $tokens[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'token' => $row['token'],
            'device_info' => $row['device_info'],
            'expires_at' => $row['expires_at'],
            'created_at' => $row['created_at'],
            'is_active' => $isActive
        ];
    }

    respond(200, [
        'success' => true,
        'count' => count($tokens),
        'active_count' => $activeCount,
        'user_id' => $userId,
        'data' => $tokens
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
