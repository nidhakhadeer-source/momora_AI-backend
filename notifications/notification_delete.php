<?php
// =============================================================================
// Momora AI - Delete Notification Endpoint
// Endpoint: /notifications/notification_delete.php
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

    // 5. Look up notification in database
    $stmt = $pdo->prepare('SELECT id, user_id, title FROM notifications WHERE id = ?');
    $stmt->execute([$notifId]);
    $notif = $stmt->fetch();

    if (!$notif) {
        respond(404, [
            'success' => false,
            'message' => 'Notification not found'
        ]);
    }

    // 6. Security check: User can only delete their own notification
    if ((int)$notif['user_id'] !== $userId) {
        respond(403, [
            'success' => false,
            'message' => "Access denied: You do not have permission to delete another user's notification"
        ]);
    }

    // 7. Delete notification
    $delStmt = $pdo->prepare('DELETE FROM notifications WHERE id = ? AND user_id = ?');
    $delStmt->execute([$notifId, $userId]);

    // 8. Fetch updated counts
    $totalStmt = $pdo->prepare('SELECT COUNT(*) AS total_count FROM notifications WHERE user_id = ?');
    $totalStmt->execute([$userId]);
    $totalCount = (int)$totalStmt->fetchColumn();

    $unreadStmt = $pdo->prepare('SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$userId]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    respond(200, [
        'success' => true,
        'message' => 'Notification deleted successfully',
        'data' => [
            'deleted_notification_id' => $notifId,
            'user_id' => $userId,
            'title' => $notif['title'],
            'total_count' => $totalCount,
            'unread_count' => $unreadCount
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
