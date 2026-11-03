<?php
// =============================================================================
// Momora AI - Create Notification Endpoint
// Endpoint: /notifications/notification_create.php
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

    // 4. Validate notification fields
    $title = trim($input['title'] ?? '');
    $subtitle = trim($input['subtitle'] ?? '');
    $detailText = trim($input['detail_text'] ?? ($input['detailText'] ?? ''));

    if ($title === '') {
        respond(422, [
            'success' => false,
            'message' => 'Notification title cannot be empty'
        ]);
    }

    if (mb_strlen($title) > 200) {
        respond(422, [
            'success' => false,
            'message' => 'Notification title must not exceed 200 characters'
        ]);
    }

    if ($subtitle === '') {
        respond(422, [
            'success' => false,
            'message' => 'Notification subtitle cannot be empty'
        ]);
    }

    // 5. Map & normalize notification type to existing ENUM
    $rawType = strtoupper(trim($input['type'] ?? 'REMINDER'));
    $typeMap = [
        'APPOINTMENT'        => 'APPOINTMENT',
        'REMINDER'           => 'REMINDER',
        'MEDICATION'         => 'REMINDER',
        'REPORT_REVIEW'      => 'REPORT_REVIEW',
        'DOCTOR'             => 'REPORT_REVIEW',
        'DOCTOR_MESSAGE'     => 'REPORT_REVIEW',
        'WEEKLY_DISCOVERY'   => 'WEEKLY_DISCOVERY',
        'PREGNANCY_UPDATE'   => 'WEEKLY_DISCOVERY',
        'GENERAL'            => 'WEEKLY_DISCOVERY'
    ];
    $type = $typeMap[$rawType] ?? 'REMINDER';

    $isNew = isset($input['is_new']) ? ((int)(bool)$input['is_new']) : 1;
    $isRead = isset($input['is_read']) ? ((int)(bool)$input['is_read']) : 0;

    // 6. Insert notification into database
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $userId,
        $title,
        $subtitle,
        $detailText !== '' ? $detailText : null,
        $type,
        $isNew,
        $isRead
    ]);
    $notifId = (int)$pdo->lastInsertId();

    // 7. Fetch created notification to return
    $fetchStmt = $pdo->prepare(
        'SELECT id, user_id, title, subtitle, detail_text, type, is_new, is_read, created_at
         FROM notifications WHERE id = ?'
    );
    $fetchStmt->execute([$notifId]);
    $record = $fetchStmt->fetch();

    respond(201, [
        'success' => true,
        'message' => 'Notification created successfully',
        'data' => [
            'id' => (int)$record['id'],
            'user_id' => (int)$record['user_id'],
            'title' => $record['title'],
            'subtitle' => $record['subtitle'],
            'detail_text' => $record['detail_text'],
            'detailText' => $record['detail_text'] ?? '',
            'type' => $record['type'],
            'is_new' => (bool)$record['is_new'],
            'isNew' => (bool)$record['is_new'],
            'is_read' => (bool)$record['is_read'],
            'isRead' => (bool)$record['is_read'],
            'created_at' => $record['created_at']
        ]
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
