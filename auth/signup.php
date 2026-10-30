<?php
// =============================================================================
// Momora AI - Patient Registration Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Use POST method']);
}

$input = getInput();

$name = trim($input['name'] ?? ($input['fullName'] ?? ''));
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';
$dateOfBirth = trim($input['dateOfBirth'] ?? ($input['date_of_birth'] ?? ''));

if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    respond(422, [
        'success' => false,
        'message' => 'Please provide a full name, valid email, and a password of at least 6 characters'
    ]);
}

try {
    $pdo->beginTransaction();

    // Check duplicate email
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        respond(409, [
            'success' => false,
            'message' => 'An account with this email address already exists'
        ]);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, is_active)
         VALUES (?, ?, ?, "patient", 1)'
    );
    $stmt->execute([$name, $email, $hash]);
    $userId = (int)$pdo->lastInsertId();

    // Generate initial patient code (e.g. P-1000 + ID)
    $patientCode = 'P-' . (10000 + $userId);

    // Find default doctor (e.g. first available doctor in system)
    $docStmt = $pdo->query('SELECT id FROM users WHERE role = "doctor" AND is_active = 1 LIMIT 1');
    $defaultDoctor = $docStmt->fetch();
    $doctorId = $defaultDoctor ? (int)$defaultDoctor['id'] : null;

    // Convert date of birth if possible
    $dobSql = null;
    if (!empty($dateOfBirth)) {
        $parsedTime = strtotime($dateOfBirth);
        if ($parsedTime !== false) {
            $dobSql = date('Y-m-d', $parsedTime);
        }
    }

    // Create default profile
    $profStmt = $pdo->prepare(
        'INSERT INTO profiles (user_id, patient_code, date_of_birth, gender, blood_group, is_care_team_sharing_on, assigned_doctor_id, reminder_summary)
         VALUES (?, ?, ?, "Female", "O positive", 1, ?, "Medication and appointment alerts")'
    );
    $profStmt->execute([$userId, $patientCode, $dobSql, $doctorId]);

    // Create initial pregnancy details (defaults to ~24 weeks, due date 16 weeks ahead)
    $estimatedDueDate = date('Y-m-d', strtotime('+16 weeks'));
    $lmpDate = date('Y-m-d', strtotime('-24 weeks'));
    $pregStmt = $pdo->prepare(
        'INSERT INTO pregnancy_details (user_id, last_menstrual_period, estimated_due_date, gestational_weeks, gestational_days, trimester)
         VALUES (?, ?, ?, 24, 0, 2)'
    );
    $pregStmt->execute([$userId, $lmpDate, $estimatedDueDate]);

    // Create a welcoming notification
    $notifStmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, title, subtitle, detail_text, type, is_new, is_read)
         VALUES (?, "Welcome to Momora AI", "Your personalized pregnancy care companion is ready.", "Explore your dashboard, track health vitals, and connect with your maternity team.", "WEEKLY_DISCOVERY", 1, 0)'
    );
    $notifStmt->execute([$userId]);

    // Generate auth token
    $tokenData = generateUserToken($pdo, $userId, $_SERVER['HTTP_USER_AGENT'] ?? 'Android App');

    $pdo->commit();

    respond(201, [
        'success' => true,
        'message' => 'Account created successfully',
        'token' => $tokenData['token'],
        'expires_at' => $tokenData['expires_at'],
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'patient',
            'patient_code' => $patientCode
        ]
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, [
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}
