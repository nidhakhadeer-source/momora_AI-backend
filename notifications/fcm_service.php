<?php
// =============================================================================
// Momora AI - Firebase Cloud Messaging (FCM) HTTP v1 Helper
// Implements secure FCM message delivery using Google Service Account OAuth2 JWT.
// Credentials are kept outside public web root and never hardcoded.
// =============================================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Locate and parse Firebase Service Account JSON credentials.
 * Checks environment variable and secure locations outside the public web root.
 */
function getFirebaseServiceAccount(): ?array {
    $candidates = [
        getenv('FCM_SERVICE_ACCOUNT_PATH') ?: '',
        'C:/xamp/firebase/service_account.json',
        'C:/xamp/htdocs/firebase/service_account.json',
        dirname(__DIR__, 2) . '/firebase/service_account.json',
        dirname(__DIR__, 1) . '/config/firebase_credentials.json',
        dirname(__DIR__, 1) . '/config/service_account.json'
    ];

    foreach ($candidates as $path) {
        if (!empty($path) && file_exists($path) && is_readable($path)) {
            $content = file_get_contents($path);
            if ($content !== false) {
                $json = json_decode($content, true);
                if (is_array($json) && !empty($json['client_email']) && !empty($json['private_key']) && !empty($json['project_id'])) {
                    return $json;
                }
            }
        }
    }
    return null;
}

/**
 * Base64Url encode helper for JWT creation.
 */
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Generate Google OAuth2 Access Token for Firebase Messaging scope using RS256 JWT.
 * Results are cached in a temporary file for performance until near expiration.
 */
function getGoogleOAuthAccessToken(array $sa): ?string {
    $clientEmail = $sa['client_email'];
    $cacheFile = sys_get_temp_dir() . '/momora_fcm_token_' . md5($clientEmail) . '.json';

    // Check cached token
    if (file_exists($cacheFile)) {
        $cacheRaw = file_get_contents($cacheFile);
        if ($cacheRaw !== false) {
            $cached = json_decode($cacheRaw, true);
            if (is_array($cached) && !empty($cached['access_token']) && !empty($cached['expires_at'])) {
                if ($cached['expires_at'] > (time() + 120)) { // 2 minute buffer
                    return $cached['access_token'];
                }
            }
        }
    }

    $now = time();
    $exp = $now + 3600;

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $claims = json_encode([
        'iss'   => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $exp,
        'iat'   => $now
    ]);

    $dataToSign = base64UrlEncode($header) . '.' . base64UrlEncode($claims);
    $signature = '';

    $privateKey = $sa['private_key'];
    $keyRes = openssl_pkey_get_private($privateKey);
    if (!$keyRes) {
        error_log('Momora AI FCM Error: Invalid private key in service account JSON');
        return null;
    }

    $signed = openssl_sign($dataToSign, $signature, $keyRes, OPENSSL_ALGO_SHA256);
    if (!$signed) {
        error_log('Momora AI FCM Error: Failed to sign OAuth JWT assertion');
        return null;
    }

    $jwt = $dataToSign . '.' . base64UrlEncode($signature);

    // Exchange JWT for Access Token via Google OAuth2
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt
    ]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode !== 200 || !$response) {
        error_log("Momora AI FCM Error: OAuth token request failed ($httpCode): $err - $response");
        return null;
    }

    $respData = json_decode($response, true);
    $accessToken = $respData['access_token'] ?? null;
    $expiresIn = (int)($respData['expires_in'] ?? 3600);

    if ($accessToken) {
        file_put_contents($cacheFile, json_encode([
            'access_token' => $accessToken,
            'expires_at'   => $now + $expiresIn
        ]));
    }

    return $accessToken;
}

/**
 * Send an FCM HTTP v1 push notification message to a specific device token.
 * Removes expired or invalid tokens from device_tokens table when reported by FCM.
 */
function sendFcmHttpV1Message(PDO $pdo, array $sa, string $accessToken, string $fcmToken, array $payload): array {
    $projectId = $sa['project_id'];
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $title = $payload['title'] ?? 'Momora AI';
    $subtitle = $payload['subtitle'] ?? '';
    $detailText = $payload['detail_text'] ?? '';
    $role = $payload['role'] ?? 'patient';
    $screen = $payload['screen'] ?? 'notifications';
    $notificationId = (string)($payload['notification_id'] ?? '0');
    $type = $payload['type'] ?? 'REMINDER';

    // Pure data-only FCM payload: allows MomoraFirebaseMessagingService to control
    // the heads-up notification display directly across foreground, background, and closed states.
    $message = [
        'message' => [
            'token' => $fcmToken,
            'data' => [
                'title'           => (string)$title,
                'subtitle'        => (string)$subtitle,
                'body'            => (string)$subtitle,
                'detail_text'     => (string)($detailText ?? ''),
                'role'            => (string)$role,
                'screen'          => (string)$screen,
                'notification_id' => (string)$notificationId,
                'type'            => (string)$type
            ],
            'android' => [
                'priority' => 'HIGH'
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json; UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'error' => $err, 'status' => $httpCode];
    }

    $respJson = json_decode($response, true) ?: [];

    // Check if token is invalid or expired
    if ($httpCode === 404 || $httpCode === 400) {
        $errorCode = $respJson['error']['details'][0]['errorCode'] ?? ($respJson['error']['status'] ?? '');
        if ($errorCode === 'UNREGISTERED' || $errorCode === 'NOT_FOUND' || strpos($response, 'registration-token-not-registered') !== false) {
            // Remove dead token from database
            $delStmt = $pdo->prepare('DELETE FROM device_tokens WHERE fcm_token = ?');
            $delStmt->execute([$fcmToken]);
            return ['success' => false, 'error' => 'Token unregistered', 'token_deleted' => true, 'status' => $httpCode];
        }
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'name' => $respJson['name'] ?? '', 'status' => $httpCode];
    }

    return ['success' => false, 'error' => $response, 'status' => $httpCode];
}

/**
 * Central dispatcher to send real-time push notifications to a user.
 * Queries registered device tokens for the user, ensures role consistency,
 * and delivers real-time notifications to all active devices.
 */
function sendFcmNotificationToUser(
    PDO $pdo,
    int $userId,
    string $title,
    string $subtitle,
    ?string $detailText = null,
    string $type = 'REMINDER',
    int $notificationId = 0,
    ?string $targetScreen = null
): array {
    if ($userId <= 0) {
        return ['sent_count' => 0, 'error' => 'Invalid user ID'];
    }

    // 1. Fetch user role and active state
    $uStmt = $pdo->prepare('SELECT id, name, role, is_active FROM users WHERE id = ?');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    if (!$user || (int)$user['is_active'] !== 1) {
        return ['sent_count' => 0, 'error' => 'User not found or inactive'];
    }

    $role = strtolower($user['role']);

    // 2. Map target screen if not explicitly passed
    if ($targetScreen === null || $targetScreen === '') {
        $targetScreen = match(strtoupper($type)) {
            'APPOINTMENT'        => ($role === 'doctor' ? 'doctor_schedule' : 'appointments'),
            'REPORT_REVIEW'      => ($role === 'doctor' ? 'doctor_advice' : 'doctor_advice'),
            'WEEKLY_DISCOVERY'   => 'baby_development',
            default              => ($role === 'admin' ? 'admin_notifications' : ($role === 'doctor' ? 'doctor_notifications' : 'notifications'))
        };
    }

    // 3. Fetch all active device tokens registered for this user
    $tStmt = $pdo->prepare('SELECT id, fcm_token, device_type FROM device_tokens WHERE user_id = ?');
    $tStmt->execute([$userId]);
    $deviceRows = $tStmt->fetchAll();

    if (empty($deviceRows)) {
        return [
            'sent_count'     => 0,
            'total_devices'  => 0,
            'fcm_configured' => true,
            'message'        => 'No registered devices found for user'
        ];
    }

    // 4. Locate Firebase Service Account credentials
    $sa = getFirebaseServiceAccount();
    if (!$sa) {
        return [
            'sent_count'     => 0,
            'total_devices'  => count($deviceRows),
            'fcm_configured' => false,
            'message'        => 'Firebase Service Account JSON not found. Place service_account.json at C:/xamp/firebase/service_account.json'
        ];
    }

    // 5. Get OAuth2 Access Token
    $accessToken = getGoogleOAuthAccessToken($sa);
    if (!$accessToken) {
        return [
            'sent_count'     => 0,
            'total_devices'  => count($deviceRows),
            'fcm_configured' => true,
            'error'          => 'Failed to generate Google OAuth access token'
        ];
    }

    $payload = [
        'title'           => $title,
        'subtitle'        => $subtitle,
        'detail_text'     => $detailText ?? '',
        'role'            => $role,
        'screen'          => $targetScreen,
        'notification_id' => $notificationId,
        'type'            => $type
    ];

    $sentCount = 0;
    $failedCount = 0;
    $deletedCount = 0;

    foreach ($deviceRows as $row) {
        $fcmToken = trim($row['fcm_token']);
        if (empty($fcmToken)) continue;

        $res = sendFcmHttpV1Message($pdo, $sa, $accessToken, $fcmToken, $payload);
        if ($res['success']) {
            $sentCount++;
        } else {
            $failedCount++;
            if (!empty($res['token_deleted'])) {
                $deletedCount++;
            }
        }
    }

    return [
        'sent_count'     => $sentCount,
        'failed_count'   => $failedCount,
        'deleted_tokens' => $deletedCount,
        'total_devices'  => count($deviceRows),
        'fcm_configured' => true
    ];
}
