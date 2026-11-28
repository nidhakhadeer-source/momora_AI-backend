<?php
// =============================================================================
// Momora AI - Retrieve AI Chat History Endpoint
// Endpoint: /ai/chat_history.php
// Method: GET or POST
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$input = getInput();

try {
    // 1. Authenticate user: strictly require verified Bearer token
    $token = getBearerToken();
    if (empty($token)) {
        respond(401, [
            'success' => false,
            'message' => 'Unauthorized: Please provide a valid authentication token'
        ]);
    }

    $stmt = $pdo->prepare(
        'SELECT u.id, u.name, u.role, u.is_active
         FROM user_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND t.expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        respond(401, [
            'success' => false,
            'message' => 'Unauthorized: Invalid or expired authentication token'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    // Confirm user has patient role
    if ($user['role'] !== 'patient') {
        respond(403, [
            'success' => false,
            'message' => 'Access restricted: Only registered patients can view pregnancy AI chat history'
        ]);
    }

    // Strictly derive patient identity from verified authentication mechanism
    $userId = (int)$user['id'];

    // Prevent cross-user parameter tampering: if user_id is passed in query/body (e.g. Android getChatMessages), ensure it matches authenticated identity
    $requestedUserId = isset($input['user_id']) ? filter_var($input['user_id'], FILTER_VALIDATE_INT) : null;
    if ($requestedUserId !== null && $requestedUserId !== false && (int)$requestedUserId !== $userId) {
        respond(403, [
            'success' => false,
            'message' => 'Forbidden: Cannot access chat history for another user'
        ]);
    }

    // 3. Fetch chat history strictly isolated to this user_id
    $limit = isset($input['limit']) ? min(max((int)$input['limit'], 1), 200) : 100;
    $filterConvId = isset($input['conversation_id']) ? trim($input['conversation_id']) : null;

    if ($filterConvId !== null && $filterConvId !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, user_id, conversation_id, sender, message_text, bullet_points, footer_note, image_url, created_at
             FROM ai_chat_messages
             WHERE user_id = ? AND conversation_id = ?
             ORDER BY created_at ASC, id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $filterConvId, PDO::PARAM_STR);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, user_id, conversation_id, sender, message_text, bullet_points, footer_note, image_url, created_at
             FROM ai_chat_messages
             WHERE user_id = ?
             ORDER BY created_at ASC, id ASC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    }
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
        $convId = !empty($row['conversation_id']) ? $row['conversation_id'] : ('conv_legacy_' . date('Ymd', strtotime($row['created_at'])) . '_' . $userId);

        $messages[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'conversation_id' => $convId,
            'sender' => $row['sender'],
            'text' => $row['message_text'],
            'bulletPoints' => $bullets,
            'footerNote' => $row['footer_note'],
            'image_url' => $row['image_url'] ?? null,
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
