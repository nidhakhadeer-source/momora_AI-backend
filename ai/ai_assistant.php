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

    // 4. Validate question, conversation_id, and optional attached image
    $conversationId = trim($input['conversation_id'] ?? '');
    if ($conversationId === '') {
        $conversationId = 'conv_' . time() . '_' . bin2hex(random_bytes(4));
    }

    $uploadedRelativePath = null;
    $imageBase64 = null;

    $fileKey = null;
    foreach (['image', 'photo', 'file', 'attachment'] as $k) {
        if (isset($_FILES[$k]) && is_array($_FILES[$k]) && !empty($_FILES[$k]['name'])) {
            $fileKey = $k;
            break;
        }
    }

    if ($fileKey) {
        $file = $_FILES[$fileKey];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            respond(400, [
                'success' => false,
                'message' => 'Image upload failed (error code ' . $file['error'] . ')'
            ]);
        }

        // Validate max size (5 MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            respond(422, [
                'success' => false,
                'message' => 'Image size exceeds maximum limit of 5 MB'
            ]);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid image format (' . $extension . '). Allowed formats: JPG, JPEG, PNG, WEBP'
            ]);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes)) {
            respond(422, [
                'success' => false,
                'message' => 'Uploaded file is not a valid image (' . $detectedMime . ')'
            ]);
        }

        if (@getimagesize($file['tmp_name']) === false) {
            respond(422, [
                'success' => false,
                'message' => 'The uploaded file image data is corrupt or invalid'
            ]);
        }

        $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai_chat';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $saveExtension = ($extension === 'jpeg') ? 'jpg' : $extension;
        $filename = 'chat_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $saveExtension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            respond(500, [
                'success' => false,
                'message' => 'Failed to store uploaded image on server'
            ]);
        }

        $uploadedRelativePath = 'uploads/ai_chat/' . $filename;
        $imageBase64 = base64_encode(file_get_contents($destination));
    } elseif (!empty($input['image_base64'])) {
        $rawB64 = $input['image_base64'];
        if (strpos($rawB64, 'base64,') !== false) {
            $rawB64 = explode('base64,', $rawB64)[1];
        }
        $decoded = base64_decode($rawB64, true);
        if ($decoded !== false && strlen($decoded) > 0) {
            $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'ai_chat';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $filename = 'chat_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.jpg';
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($destination, $decoded);
            $uploadedRelativePath = 'uploads/ai_chat/' . $filename;
            $imageBase64 = $rawB64;
        }
    }

    $question = trim($input['question'] ?? ($input['message'] ?? ($input['query'] ?? ($input['text'] ?? ''))));
    if ($question === '' && !empty($imageBase64)) {
        $question = "Please analyze this image and explain any health, nutritional, or pregnancy considerations.";
    } elseif ($question === '') {
        respond(422, [
            'success' => false,
            'message' => 'Please enter a question or attach an image'
        ]);
    }

    if (mb_strlen($question) > 1000) {
        respond(422, [
            'success' => false,
            'message' => 'Question is too long (maximum 1000 characters)'
        ]);
    }

    // 5. Retrieve patient pregnancy context
    $pregStmt = $pdo->prepare(
        'SELECT pd.gestational_weeks, pd.gestational_days, pd.trimester, pd.estimated_due_date,
                bd.length_cm_text, bd.weight_gram_text, bd.highlights
         FROM pregnancy_details pd
         LEFT JOIN baby_development bd ON bd.week = pd.gestational_weeks
         WHERE pd.user_id = ?'
    );
    $pregStmt->execute([$userId]);
    $pregData = $pregStmt->fetch();

    $gestationalWeeks = ($pregData && isset($pregData['gestational_weeks']) && (int)$pregData['gestational_weeks'] > 0)
        ? (int)$pregData['gestational_weeks']
        : null;
    $gestationalDays = ($pregData && isset($pregData['gestational_days']))
        ? (int)$pregData['gestational_days']
        : null;
    $trimester = ($pregData && isset($pregData['trimester'])) ? (int)$pregData['trimester'] : null;

    $userContext = [
        'user_id' => $userId,
        'name' => $user['name'],
        'gestational_weeks' => $gestationalWeeks,
        'gestational_days' => $gestationalDays,
        'trimester' => $trimester,
        'length_cm' => $pregData['length_cm_text'] ?? null,
        'weight_gram' => $pregData['weight_gram_text'] ?? null,
        'highlights' => $pregData['highlights'] ?? null
    ];

    // 6. Generate AI guidance
    $aiResult = generatePregnancyAiResponse($question, $userContext, $imageBase64);

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
        'INSERT INTO ai_chat_messages (user_id, conversation_id, sender, message_text, image_url, created_at)
         VALUES (?, ?, "USER", ?, ?, NOW())'
    );
    $userMsgStmt->execute([$userId, $conversationId, $question, $uploadedRelativePath]);
    $questionId = (int)$pdo->lastInsertId();

    // Insert AI response
    $bulletJson = !empty($aiData['bullet_points']) ? json_encode($aiData['bullet_points'], JSON_UNESCAPED_UNICODE) : null;
    $aiMsgStmt = $pdo->prepare(
        'INSERT INTO ai_chat_messages (user_id, conversation_id, sender, message_text, bullet_points, footer_note, created_at)
         VALUES (?, ?, "AI", ?, ?, ?, NOW())'
    );
    $aiMsgStmt->execute([
        $userId,
        $conversationId,
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
            'conversation_id' => $conversationId,
            'sender' => 'AI',
            'text' => $aiData['message_text'],
            'bulletPoints' => $aiData['bullet_points'] ?? [],
            'footerNote' => $aiData['footer_note'] ?? null,
            'image_url' => $uploadedRelativePath,
            'timestampText' => "Momora AI · {$nowTime}",
            'is_pregnancy_related' => (bool)($aiData['is_pregnancy_related'] ?? true),
            'is_emergency' => (bool)($aiData['is_emergency'] ?? false),
            'provider' => $aiResult['provider'] ?? 'fastapi_ollama'
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
