<?php
// =============================================================================
// Momora AI - Register FCM Device Token Endpoint
// Endpoint: /notifications/register_token.php
// Method: POST
// Associates FCM device push token with the authenticated user account and role.
// =============================================================================

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

$user = authenticateUser($pdo);
$userId = (int)$user['id'];

$input = getInput();
$fcmToken = trim($input['fcm_token'] ?? ($input['fcmToken'] ?? ''));
$deviceType = trim($input['device_type'] ?? 'android');
$deviceModel = trim($input['device_model'] ?? ($input['deviceModel'] ?? ''));

if ($fcmToken === '') {
    respond(422, [
        'success' => false,
        'message' => 'fcm_token cannot be empty'
    ]);
}

try {
    // Upsert into device_tokens table
    $stmt = $pdo->prepare(
        'INSERT INTO device_tokens (user_id, fcm_token, device_type, device_model, updated_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
             device_type = VALUES(device_type),
             device_model = VALUES(device_model),
             updated_at = NOW()'
    );
    $stmt->execute([
        $userId,
        $fcmToken,
        !empty($deviceType) ? $deviceType : 'android',
        !empty($deviceModel) ? $deviceModel : null
    ]);

    respond(200, [
        'success' => true,
        'message' => 'FCM device token registered successfully',
        'data' => [
            'user_id' => $userId,
            'role' => $user['role'],
            'device_type' => $deviceType
        ]
    ]);
} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Failed to register device token: ' . $e->getMessage()
    ]);
}
