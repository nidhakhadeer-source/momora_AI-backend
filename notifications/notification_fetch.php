<?php
// =============================================================================
// Momora AI - Fetch Notifications Endpoint
// Endpoint: /notifications/notification_fetch.php
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
    // 2. Confirm user exists
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

    // 3. Calculate total and unread counts for this user
    $countStmt = $pdo->prepare('SELECT COUNT(*) AS total_count FROM notifications WHERE user_id = ?');
    $countStmt->execute([$userId]);
    $totalCount = (int)$countStmt->fetchColumn();

    $unreadStmt = $pdo->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    // 4. Check if unread filter requested
    $unreadOnlyRaw = $input['unread_only'] ?? ($_GET['unread_only'] ?? null);
    $statusRaw = strtolower(trim((string)($input['status'] ?? ($_GET['status'] ?? ''))));
    $unreadOnly = ($unreadOnlyRaw === '1' || $unreadOnlyRaw === 'true' || $unreadOnlyRaw === true || $statusRaw === 'unread');

    $limit = isset($input['limit']) ? min(max((int)$input['limit'], 1), 100) : 50;

    $sql = 'SELECT id, user_id, title, subtitle, detail_text, type, is_new, is_read, created_at
            FROM notifications
            WHERE user_id = ?';

    if ($unreadOnly) {
        $sql .= ' AND is_read = 0';
    }

    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ?';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $items = [];
    foreach ($rows as $n) {
        $items[] = [
            'id' => (int)$n['id'],
            'user_id' => (int)$n['user_id'],
            'title' => $n['title'],
            'subtitle' => $n['subtitle'],
            'detail_text' => $n['detail_text'],
            'detailText' => $n['detail_text'] ?? '',
            'type' => strtoupper($n['type']),
            'is_new' => (bool)$n['is_new'],
            'isNew' => (bool)$n['is_new'],
            'is_read' => (bool)$n['is_read'],
            'isRead' => (bool)$n['is_read'],
            'created_at' => $n['created_at']
        ];
    }

    respond(200, [
        'success' => true,
        'user_id' => $userId,
        'total_count' => $totalCount,
        'unread_count' => $unreadCount,
        'returned_count' => count($items),
        'unread_only' => $unreadOnly,
        'data' => $items
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
