<?php
// =============================================================================
// Momora AI - Central Backend Configuration & Security Helper
// =============================================================================

// Headers & CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database Credentials (Local XAMPP environment)
$dbHost = 'localhost';
$dbName = 'momora_ai';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

/**
 * Standard JSON Response helper
 */
function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Parse input from JSON body, POST, or GET
 */
function getInput() {
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    if (is_array($jsonData)) {
        return array_merge($_REQUEST, $jsonData);
    }
    return $_REQUEST;
}

/**
 * Extract Bearer token from HTTP headers or input
 */
function getBearerToken() {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
            return $matches[1];
        }
    }

    // Also support token in parameter for flexible Android query
    $input = getInput();
    if (!empty($input['token'])) {
        return trim($input['token']);
    }

    return null;
}

/**
 * Authenticate user via bearer token or user_id fallback if token omitted
 */
function authenticateUser($pdo, $requiredRole = null) {
    $token = getBearerToken();
    $input = getInput();

    $user = null;

    if (!empty($token)) {
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.is_active
             FROM user_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW()'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
    } elseif (!empty($input['user_id'])) {
        // Fallback for direct user_id access when token is not yet established
        $userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
        if ($userId) {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, role, is_active
                 FROM users WHERE id = ?'
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }

    if (!$user) {
        respond(401, [
            'success' => false,
            'message' => 'Unauthorized: Please provide a valid authentication token'
        ]);
    }

    if ((int)$user['is_active'] !== 1) {
        respond(403, [
            'success' => false,
            'message' => 'Account access has been deactivated by administrator'
        ]);
    }

    if ($requiredRole !== null) {
        $allowed = is_array($requiredRole) ? $requiredRole : [$requiredRole];
        if (!in_array($user['role'], $allowed)) {
            respond(403, [
                'success' => false,
                'message' => 'Forbidden: You do not have permission to access this resource'
            ]);
        }
    }

    return $user;
}

/**
 * Generate secure session token (valid for 30 days)
 */
function generateUserToken($pdo, $userId, $deviceInfo = 'Android Device') {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $pdo->prepare(
        'INSERT INTO user_tokens (user_id, token, device_info, expires_at)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $token, $deviceInfo, $expiresAt]);

    return [
        'token' => $token,
        'expires_at' => $expiresAt
    ];
}
