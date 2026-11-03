<?php
// =============================================================================
// Momora AI - Mark All Notifications as Read Endpoint
// Endpoint: /notifications/notification_mark_all_read.php
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
    // 3. Confirm user exists
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

    // 4. Mark all unread notifications for this user as read
    $updateStmt = $pdo->prepare('UPDATE notifications SET is_read = 1, is_new = 0 WHERE user_id = ? AND is_read = 0');
    $updateStmt->execute([$userId]);
    $updatedRows = $updateStmt->rowCount();

    // 5. Calculate total count for user
    $totalStmt = $pdo->prepare('SELECT COUNT(*) AS total_count FROM notifications WHERE user_id = ?');
    $totalStmt->execute([$userId]);
    $totalCount = (int)$totalStmt->fetchColumn();

    respond(200, [
        'success' => true,
        'message' => 'All notifications marked as read',
        'data' => [
            'user_id' => $userId,
            'updated_count' => $updatedRows,
            'unread_count' => 0,
            'total_count' => $totalCount
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
