<?php
// =============================================================================
// Momora AI - Mark Single Notification as Read Endpoint
// Endpoint: /notifications/notification_mark_read.php
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

// 3. Validate notification_id
$notifIdRaw = $input['notification_id'] ?? ($input['id'] ?? ($_POST['notification_id'] ?? ($_POST['id'] ?? null)));
if ($notifIdRaw === null || trim((string)$notifIdRaw) === '') {
    respond(400, [
        'success' => false,
        'message' => 'Valid notification_id is required'
    ]);
}

$notifId = filter_var($notifIdRaw, FILTER_VALIDATE_INT);
if ($notifId === false || $notifId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'Valid notification_id is required'
    ]);
}

try {
    // 4. Confirm user exists
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

    // 5. Check if notification exists
    $stmt = $pdo->prepare('SELECT id, user_id, title, is_read, is_new FROM notifications WHERE id = ?');
    $stmt->execute([$notifId]);
    $notif = $stmt->fetch();

    if (!$notif) {
        respond(404, [
            'success' => false,
            'message' => 'Notification not found'
        ]);
    }

    // 6. Security check: User can only mark their own notification as read
    if ((int)$notif['user_id'] !== $userId) {
        respond(403, [
            'success' => false,
            'message' => "Access denied: You do not have permission to modify another user's notification"
        ]);
    }

    // 7. Execute update
    $updateStmt = $pdo->prepare('UPDATE notifications SET is_read = 1, is_new = 0 WHERE id = ? AND user_id = ?');
    $updateStmt->execute([$notifId, $userId]);

    // 8. Fetch updated counts
    $unreadStmt = $pdo->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    $totalStmt = $pdo->prepare('SELECT COUNT(*) AS total_count FROM notifications WHERE user_id = ?');
    $totalStmt->execute([$userId]);
    $totalCount = (int)$totalStmt->fetchColumn();

    respond(200, [
        'success' => true,
        'message' => 'Notification marked as read',
        'data' => [
            'notification_id' => $notifId,
            'user_id' => $userId,
            'is_read' => true,
            'is_new' => false,
            'unread_count' => $unreadCount,
            'total_count' => $totalCount
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
