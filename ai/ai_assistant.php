<?php
// =============================================================================
// Momora AI - Pregnancy AI Assistant Endpoint
// Endpoint: /ai/ai_assistant.php
// Method: POST
// =============================================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ai_service.php';

// 1. Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

$input = getInput();

try {
    // 2. Authenticate user: strictly require verified Bearer token
    $token = getBearerToken();
    if (empty($token)) {
        respond(401, [
            'success' => false,
            'message' => 'Unauthorized: Please provide a valid authentication token'
        ]);
    }

    $stmt = $pdo->prepare(
        'SELECT u.id, u.name, u.email, u.role, u.is_active
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
            'message' => 'Access restricted: Only registered patients can access the pregnancy AI assistant'
        ]);
    }

    // Strictly derive patient identity from verified authentication mechanism
    $userId = (int)$user['id'];

    // Prevent parameter tampering: if user_id is explicitly supplied in request, ensure it matches authenticated identity
    $requestedUserId = isset($input['user_id']) ? filter_var($input['user_id'], FILTER_VALIDATE_INT) : null;
    if ($requestedUserId !== null && $requestedUserId !== false && (int)$requestedUserId !== $userId) {
        respond(403, [
            'success' => false,
            'message' => 'Forbidden: Cannot send messages on behalf of another user'
        ]);
    }

    // 4. Validate question
    $question = trim($input['question'] ?? ($input['message'] ?? ($input['query'] ?? ($input['text'] ?? ''))));
    if ($question === '') {
        respond(422, [
            'success' => false,
            'message' => 'Please enter a question'
        ]);
    }

    if (mb_strlen($question) > 1000) {
        respond(422, [
            'success' => false,
            'message' => 'Question is too long (maximum 1000 characters)'
        ]);
    }

    // 5. Generate AI guidance
    $aiResult = generatePregnancyAiResponse($question, [
        'user_id' => $userId,
        'name' => $user['name']
    ]);

    if (!$aiResult['success']) {
        $httpCode = ($aiResult['error_code'] === 'MISSING_API_KEY') ? 503 : 502;
        respond($httpCode, [
            'success' => false,
            'error_code' => $aiResult['error_code'] ?? 'AI_PROVIDER_ERROR',
            'message' => $aiResult['message']
        ]);
    }

    $aiData = $aiResult['data'];

    // 6. Save question and response to ai_chat_messages in a transaction
    $pdo->beginTransaction();

    // Insert user question
    $userMsgStmt = $pdo->prepare(
        'INSERT INTO ai_chat_messages (user_id, sender, message_text, created_at)
         VALUES (?, "USER", ?, NOW())'
    );
    $userMsgStmt->execute([$userId, $question]);
    $questionId = (int)$pdo->lastInsertId();

    // Insert AI response
    $bulletJson = !empty($aiData['bullet_points']) ? json_encode($aiData['bullet_points'], JSON_UNESCAPED_UNICODE) : null;
    $aiMsgStmt = $pdo->prepare(
        'INSERT INTO ai_chat_messages (user_id, sender, message_text, bullet_points, footer_note, created_at)
         VALUES (?, "AI", ?, ?, ?, NOW())'
    );
    $aiMsgStmt->execute([
        $userId,
        $aiData['message_text'],
        $bulletJson,
        $aiData['footer_note'] ?? null
    ]);
    $responseId = (int)$pdo->lastInsertId();

    $pdo->commit();

    // 7. Return structured JSON response
    $nowTime = date('H:i');
    respond(200, [
        'success' => true,
        'message' => 'AI guidance generated successfully',
        'data' => [
            'question_id' => $questionId,
            'response_id' => $responseId,
            'user_id' => $userId,
            'sender' => 'AI',
            'text' => $aiData['message_text'],
            'bulletPoints' => $aiData['bullet_points'] ?? [],
            'footerNote' => $aiData['footer_note'] ?? null,
            'timestampText' => "Momora AI · {$nowTime}",
            'is_pregnancy_related' => (bool)($aiData['is_pregnancy_related'] ?? true),
            'is_emergency' => (bool)($aiData['is_emergency'] ?? false),
            'provider' => $aiResult['provider'] ?? 'gemini'
        ]
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, [
        'success' => false,
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
