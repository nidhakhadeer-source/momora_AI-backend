
<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Use POST method'
    ]);
    exit;
}

$user_id = filter_var($_POST['user_id'] ?? '', FILTER_VALIDATE_INT);
$phone = trim($_POST['phone'] ?? '');
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');

if (!$user_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
    exit;
}

try {
    $check = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $check->execute([$user_id]);

    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    $sql = "INSERT INTO profiles
            (user_id, phone, date_of_birth, gender, address)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            phone = VALUES(phone),
            date_of_birth = VALUES(date_of_birth),
            gender = VALUES(gender),
            address = VALUES(address)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user_id,
        $phone !== '' ? $phone : null,
        $date_of_birth !== '' ? $date_of_birth : null,
        $gender !== '' ? $gender : null,
        $address !== '' ? $address : null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile saved successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database operation failed'
    ]);
}
