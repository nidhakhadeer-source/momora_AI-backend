<?php
// =============================================================================
// Momora AI - Edit / Update Patient Profile API
// Endpoint: /profile/profile_update.php
// Method: POST
// Accepts: application/json or application/x-www-form-urlencoded / multipart
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Use POST method'
    ]);
}

$input = getInput();

// 1. Validate user_id
if (!isset($input['user_id']) || trim((string)$input['user_id']) === '') {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

$userId = filter_var($input['user_id'], FILTER_VALIDATE_INT);
if ($userId === false || $userId <= 0) {
    respond(400, [
        'success' => false,
        'message' => 'Valid user_id is required'
    ]);
}

try {
    // 2. Check if user exists in the database
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

    // 3. Collect only the fields provided in the request
    $userUpdates = [];
    $userParams = [];

    // Field: Full Name (accepts name, fullName, or full_name)
    if (array_key_exists('name', $input) || array_key_exists('fullName', $input) || array_key_exists('full_name', $input)) {
        $nameVal = trim($input['name'] ?? ($input['fullName'] ?? $input['full_name']));
        if ($nameVal === '') {
            respond(422, [
                'success' => false,
                'message' => 'Full name cannot be empty'
            ]);
        }
        if (mb_strlen($nameVal) > 100) {
            respond(422, [
                'success' => false,
                'message' => 'Full name must not exceed 100 characters'
            ]);
        }
        $userUpdates[] = 'name = ?';
        $userParams[] = $nameVal;
    }

    // Field: Email (optional)
    if (array_key_exists('email', $input)) {
        $emailVal = strtolower(trim($input['email']));
        if (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid email address format'
            ]);
        }
        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $emailCheck->execute([$emailVal, $userId]);
        if ($emailCheck->fetch()) {
            respond(409, [
                'success' => false,
                'message' => 'Email address is already in use by another account'
            ]);
        }
        $userUpdates[] = 'email = ?';
        $userParams[] = $emailVal;
    }

    // Profile table updates
    $profileUpdates = [];

    // Field: Phone (accepts phone or phone_number)
    if (array_key_exists('phone', $input) || array_key_exists('phone_number', $input)) {
        $phoneVal = trim($input['phone'] ?? $input['phone_number']);
        if (strlen($phoneVal) > 20) {
            respond(422, [
                'success' => false,
                'message' => 'Phone number must not exceed 20 characters'
            ]);
        }
        $profileUpdates['phone'] = $phoneVal !== '' ? $phoneVal : null;
    }

    // Field: Date of Birth (accepts date_of_birth, dateOfBirth, or dob)
    if (array_key_exists('date_of_birth', $input) || array_key_exists('dateOfBirth', $input) || array_key_exists('dob', $input)) {
        $dobRaw = trim($input['date_of_birth'] ?? ($input['dateOfBirth'] ?? $input['dob']));
        if ($dobRaw === '') {
            $profileUpdates['date_of_birth'] = null;
        } else {
            $time = strtotime($dobRaw);
            if ($time === false) {
                respond(422, [
                    'success' => false,
                    'message' => 'Invalid date of birth format. Use YYYY-MM-DD'
                ]);
            }
            if ($time > time()) {
                respond(422, [
                    'success' => false,
                    'message' => 'Date of birth cannot be in the future'
                ]);
            }
            $profileUpdates['date_of_birth'] = date('Y-m-d', $time);
        }
    }

    // Field: Gender
    if (array_key_exists('gender', $input)) {
        $genderVal = trim($input['gender']);
        if (strlen($genderVal) > 30) {
            respond(422, [
                'success' => false,
                'message' => 'Gender must not exceed 30 characters'
            ]);
        }
        $profileUpdates['gender'] = $genderVal !== '' ? $genderVal : null;
    }

    // Field: Blood Group (accepts blood_group or bloodGroup)
    if (array_key_exists('blood_group', $input) || array_key_exists('bloodGroup', $input)) {
        $bgVal = trim($input['blood_group'] ?? $input['bloodGroup']);
        if (strlen($bgVal) > 15) {
            respond(422, [
                'success' => false,
                'message' => 'Blood group must not exceed 15 characters'
            ]);
        }
        $profileUpdates['blood_group'] = $bgVal !== '' ? $bgVal : null;
    }

    // Field: Address
    if (array_key_exists('address', $input)) {
        $addrVal = trim($input['address']);
        $profileUpdates['address'] = $addrVal !== '' ? $addrVal : null;
    }

    // Field: Care team sharing toggle (accepts is_care_team_sharing_on or isCareTeamSharingOn)
    if (array_key_exists('is_care_team_sharing_on', $input) || array_key_exists('isCareTeamSharingOn', $input)) {
        $sharingRaw = $input['is_care_team_sharing_on'] ?? $input['isCareTeamSharingOn'];
        $profileUpdates['is_care_team_sharing_on'] = ($sharingRaw === true || $sharingRaw === 1 || $sharingRaw === '1' || $sharingRaw === 'true') ? 1 : 0;
    }

    // Field: Reminder summary
    if (array_key_exists('reminder_summary', $input)) {
        $remVal = trim($input['reminder_summary']);
        if (strlen($remVal) > 255) {
            respond(422, [
                'success' => false,
                'message' => 'Reminder summary must not exceed 255 characters'
            ]);
        }
        $profileUpdates['reminder_summary'] = $remVal !== '' ? $remVal : null;
    }

    // Field: Clinical background
    if (array_key_exists('clinical_background', $input)) {
        $clinVal = trim($input['clinical_background']);
        $profileUpdates['clinical_background'] = $clinVal !== '' ? $clinVal : null;
    }

    // 4. Check if at least one field was supplied to update
    if (empty($userUpdates) && empty($profileUpdates)) {
        respond(400, [
            'success' => false,
            'message' => 'No profile fields provided to update'
        ]);
    }

    // 5. Execute database updates in a transaction
    $pdo->beginTransaction();

    // Update users table if name or email changed
    if (!empty($userUpdates)) {
        $userParams[] = $userId;
        $uSql = 'UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?';
        $uStmt = $pdo->prepare($uSql);
        $uStmt->execute($userParams);
    }

    // Update profiles table if profile fields changed
    if (!empty($profileUpdates)) {
        $checkProf = $pdo->prepare('SELECT id FROM profiles WHERE user_id = ?');
        $checkProf->execute([$userId]);
        $existingProfile = $checkProf->fetch();

        if ($existingProfile) {
            // Update only the provided fields in profiles
            $pSetClauses = [];
            $pParams = [];
            foreach ($profileUpdates as $col => $val) {
                $pSetClauses[] = "$col = ?";
                $pParams[] = $val;
            }
            $pParams[] = $userId;
            $pSql = 'UPDATE profiles SET ' . implode(', ', $pSetClauses) . ' WHERE user_id = ?';
            $pStmt = $pdo->prepare($pSql);
            $pStmt->execute($pParams);
        } else {
            // Profile row does not exist yet: insert with provided fields
            $patientCode = 'P-' . (10000 + $userId);
            $cols = array_merge(['user_id', 'patient_code'], array_keys($profileUpdates));
            $placeholders = array_fill(0, count($cols), '?');
            $insertValues = array_merge([$userId, $patientCode], array_values($profileUpdates));

            $pSql = 'INSERT INTO profiles (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $placeholders) . ')';
            $pStmt = $pdo->prepare($pSql);
            $pStmt->execute($insertValues);
        }
    }

    $pdo->commit();

    // 6. Fetch the complete updated profile to return in response
    $fetchStmt = $pdo->prepare(
        'SELECT
            u.id AS user_id,
            u.name,
            u.email,
            u.role,
            p.patient_code,
            p.phone,
            p.date_of_birth,
            p.gender,
            p.blood_group,
            p.address,
            p.profile_photo,
            p.is_care_team_sharing_on,
            p.reminder_summary,
            p.clinical_background,
            p.updated_at
         FROM users u
         LEFT JOIN profiles p ON p.user_id = u.id
         WHERE u.id = ?'
    );
    $fetchStmt->execute([$userId]);
    $updated = $fetchStmt->fetch();

    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $photoUrl = !empty($updated['profile_photo']) ? ($scheme . '://' . $host . '/momora-AI/' . $updated['profile_photo']) : null;

    respond(200, [
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'user_id' => (int)$updated['user_id'],
            'name' => $updated['name'],
            'email' => $updated['email'],
            'role' => $updated['role'],
            'patient_code' => $updated['patient_code'],
            'phone' => $updated['phone'],
            'date_of_birth' => $updated['date_of_birth'],
            'gender' => $updated['gender'],
            'blood_group' => $updated['blood_group'],
            'address' => $updated['address'],
            'profile_photo' => $updated['profile_photo'] ?? null,
            'photo_url' => $photoUrl,
            'is_care_team_sharing_on' => (bool)($updated['is_care_team_sharing_on'] ?? true),
            'reminder_summary' => $updated['reminder_summary'],
            'clinical_background' => $updated['clinical_background'],
            'updated_at' => $updated['updated_at']
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

