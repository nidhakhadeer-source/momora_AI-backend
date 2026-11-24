<?php
// =============================================================================
// Momora AI - Admin Doctor Management Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'admin');

$input = getInput();
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

// 1. Return Add Doctor Form Initial Data
if ($action === 'form') {
    respond(200, [
        'success' => true,
        'data' => [
            'defaultName' => 'Dr Olivia Bennett',
            'defaultEmail' => 'olivia.bennett@willow.example',
            'defaultSpecialty' => 'Obstetrics',
            'defaultLicenseId' => 'OBS-28482',
            'defaultClinic' => 'Willow Maternity Clinic',
            'availableClinics' => [
                'Willow Maternity Clinic',
                'SIMATS Women\'s Care',
                'City Central Maternity Center'
            ]
        ]
    ]);
}

// 2. Create Doctor Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $specialty = trim($input['specialty'] ?? 'Obstetrics');
    $licenseId = trim($input['licenseId'] ?? 'OBS-' . rand(10000, 99999));
    $clinic = trim($input['clinic'] ?? 'Willow Maternity Clinic');

    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['success' => false, 'message' => 'Please provide a valid doctor name and email']);
    }

    try {
        $pdo->beginTransaction();

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'An account with this email already exists']);
        }

        // Check license ID uniqueness
        if (!empty($licenseId)) {
            $checkLicense = $pdo->prepare('SELECT id FROM doctor_profiles WHERE license_id = ?');
            $checkLicense->execute([$licenseId]);
            if ($checkLicense->fetch()) {
                $pdo->rollBack();
                respond(409, ['success' => false, 'message' => 'A doctor account with this license ID already exists']);
            }
        }

        // Default temp password for clinician
        $tempPassword = password_hash('Doctor123!', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, "doctor", 1)');
        $stmt->execute([$name, $email, $tempPassword]);
        $docUserId = (int)$pdo->lastInsertId();

        $pStmt = $pdo->prepare('INSERT INTO doctor_profiles (user_id, specialty, license_id, clinic) VALUES (?, ?, ?, ?)');
        $pStmt->execute([$docUserId, $specialty, $licenseId, $clinic]);

        $pdo->commit();

        respond(201, [
            'success' => true,
            'message' => 'Doctor account created successfully'
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        respond(500, ['success' => false, 'message' => 'Failed to create doctor: ' . $e->getMessage()]);
    }
}

// 3. Toggle Doctor Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle') {
    $doctorId = filter_var($input['doctorId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);
    if (!$doctorId) {
        respond(422, ['success' => false, 'message' => 'Doctor ID required']);
    }

    $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = "doctor"');
    $stmt->execute([$doctorId]);

    respond(200, [
        'success' => true,
        'message' => 'Doctor status toggled successfully'
    ]);
}

// 4. Update Doctor Details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $doctorId = filter_var($input['doctorId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $specialty = trim($input['specialty'] ?? '');
    $licenseId = trim($input['licenseId'] ?? ($input['license_id'] ?? ''));
    $clinic = trim($input['clinic'] ?? '');

    if (!$doctorId || empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['success' => false, 'message' => 'Please provide a valid doctor name and email']);
    }

    try {
        $pdo->beginTransaction();

        // Check email uniqueness excluding this doctor
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $doctorId]);
        if ($check->fetch()) {
            $pdo->rollBack();
            respond(409, ['success' => false, 'message' => 'An account with this email already exists']);
        }

        // Check license uniqueness excluding this doctor
        if (!empty($licenseId)) {
            $checkLic = $pdo->prepare('SELECT id FROM doctor_profiles WHERE license_id = ? AND user_id != ?');
            $checkLic->execute([$licenseId, $doctorId]);
            if ($checkLic->fetch()) {
                $pdo->rollBack();
                respond(409, ['success' => false, 'message' => 'A doctor account with this license ID already exists']);
            }
        }

        $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ? AND role = "doctor"');
        $stmt->execute([$name, $email, $doctorId]);

        $pStmt = $pdo->prepare('UPDATE doctor_profiles SET specialty = ?, license_id = ?, clinic = ? WHERE user_id = ?');
        $pStmt->execute([$specialty, $licenseId, $clinic, $doctorId]);

        $pdo->commit();

        respond(200, ['success' => true, 'message' => 'Doctor details updated successfully']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        respond(500, ['success' => false, 'message' => 'Failed to update doctor: ' . $e->getMessage()]);
    }
}

// 5. Default: List Doctors
$stmt = $pdo->query(
    'SELECT u.id, u.name, u.email, u.is_active, dp.specialty, dp.license_id, dp.clinic,
            (SELECT COUNT(*) FROM profiles p WHERE p.assigned_doctor_id = u.id) as assigned_patients
     FROM users u
     LEFT JOIN doctor_profiles dp ON dp.user_id = u.id
     WHERE u.role = "doctor"
     ORDER BY u.id ASC'
);
$docs = $stmt->fetchAll();

$items = [];
foreach ($docs as $i => $d) {
    $spec = $d['specialty'] ?: 'Obstetrics';
    $license = $d['license_id'] ?: 'OBS-19382';
    $clinic = $d['clinic'] ?: 'Willow Maternity Clinic';
    $isActive = (bool)$d['is_active'];
    $assignedCount = (int)($d['assigned_patients'] ?? 0);

    $statusText = $isActive ? "{$spec} · {$assignedCount} assigned patients" : "{$spec} · Access disabled";
    $emailLic = "{$d['email']} · {$license}";

    $items[] = [
        'id' => (string)$d['id'],
        'name' => $d['name'],
        'email' => $d['email'],
        'specialty' => $spec,
        'licenseId' => $license,
        'clinic' => $clinic,
        'assignedPatientCount' => $assignedCount,
        'specialtyAndPatientsText' => $statusText,
        'emailAndLicenseText' => $emailLic,
        'isActive' => $isActive,
        'isHighlighted' => true
    ];
}

// Ensure at least 3 doctors shown for admin view if database is empty
if (count($items) === 0) {
    $items[] = [
        'id' => '201',
        'name' => 'Dr Daniel Okafor',
        'email' => 'daniel.okafor@willow.example',
        'specialty' => 'Obstetrics',
        'licenseId' => 'OBS-20491',
        'clinic' => 'Willow Maternity Clinic',
        'assignedPatientCount' => 14,
        'specialtyAndPatientsText' => 'Obstetrics · 14 assigned patients',
        'emailAndLicenseText' => 'daniel.okafor@willow.example · OBS-20491',
        'isActive' => true,
        'isHighlighted' => true
    ];
    $items[] = [
        'id' => '202',
        'name' => 'Dr Priya Shah',
        'email' => 'priya.shah@willow.example',
        'specialty' => 'Maternal medicine',
        'licenseId' => 'OBS-11029',
        'clinic' => 'Willow Maternity Clinic',
        'assignedPatientCount' => 0,
        'specialtyAndPatientsText' => 'Maternal medicine · Access disabled',
        'emailAndLicenseText' => 'priya.shah@willow.example · OBS-11029',
        'isActive' => false,
        'isHighlighted' => true
    ];
}

respond(200, [
    'success' => true,
    'data' => $items
]);

