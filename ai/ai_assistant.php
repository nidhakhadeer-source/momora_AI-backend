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
    $userStmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
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

    // Confirm user has patient role
    if ($user['role'] !== 'patient') {
        respond(403, [
            'success' => false,
            'message' => 'Access restricted: Only registered patients can access the pregnancy AI assistant'
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
