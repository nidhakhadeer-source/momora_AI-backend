<?php
// =============================================================================
// Momora AI - Retrieve AI Chat History Endpoint
// Endpoint: /ai/chat_history.php
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
    $userStmt = $pdo->prepare('SELECT id, name, role, is_active FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

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

    // 3. Fetch chat history strictly isolated to this user_id
    $limit = isset($input['limit']) ? min(max((int)$input['limit'], 1), 100) : 50;

    $stmt = $pdo->prepare(
        'SELECT id, user_id, sender, message_text, bullet_points, footer_note, created_at
         FROM ai_chat_messages
         WHERE user_id = ?
         ORDER BY created_at ASC, id ASC
         LIMIT ?'
    );
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $messages = [];
    foreach ($rows as $row) {
        $bullets = [];
        if (!empty($row['bullet_points'])) {
            $decoded = json_decode($row['bullet_points'], true);
            if (is_array($decoded)) {
                $bullets = $decoded;
            }
        }
        $time = date('H:i', strtotime($row['created_at']));
        $senderLabel = $row['sender'] === 'USER' ? 'You' : 'Momora AI';

        $messages[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'sender' => $row['sender'],
            'text' => $row['message_text'],
            'bulletPoints' => $bullets,
            'footerNote' => $row['footer_note'],
            'timestampText' => "{$senderLabel} · {$time}",
            'created_at' => $row['created_at']
        ];
    }

    respond(200, [
        'success' => true,
        'count' => count($messages),
        'user_id' => $userId,
        'data' => $messages
    ]);

} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
