<?php
// =============================================================================
// Momora AI - Unregister FCM Device Token Endpoint
// Endpoint: /notifications/unregister_token.php
// Method: POST
// Removes device push token on user logout or session revocation.
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

if ($fcmToken === '') {
    respond(422, [
        'success' => false,
        'message' => 'fcm_token cannot be empty'
    ]);
}

try {
    $stmt = $pdo->prepare('DELETE FROM device_tokens WHERE user_id = ? AND fcm_token = ?');
    $stmt->execute([$userId, $fcmToken]);

    respond(200, [
        'success' => true,
        'message' => 'FCM device token unregistered successfully'
    ]);
} catch (PDOException $e) {
    respond(500, [
        'success' => false,
        'message' => 'Failed to unregister device token: ' . $e->getMessage()
    ]);
}
