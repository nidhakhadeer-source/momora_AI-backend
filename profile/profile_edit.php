<?php
// =============================================================================
// Momora AI - Update Patient Profile Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Use POST method']);
}

$user = authenticateUser($pdo);
$userId = (int)$user['id'];
$input = getInput();

$fullName = trim($input['fullName'] ?? ($input['name'] ?? ''));
$email = strtolower(trim($input['email'] ?? ''));
$dateOfBirth = trim($input['dateOfBirth'] ?? ($input['date_of_birth'] ?? ''));
$bloodGroup = trim($input['bloodGroup'] ?? ($input['blood_group'] ?? ''));
$isCareTeamSharingOn = isset($input['isCareTeamSharingOn']) ? ($input['isCareTeamSharingOn'] ? 1 : 0) : 1;
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');

try {
    $pdo->beginTransaction();

    // Update users table name and email
    if (!empty($fullName) || !empty($email)) {
        $userUpdates = [];
        $params = [];
        if (!empty($fullName)) {
            $userUpdates[] = 'name = ?';
            $params[] = $fullName;
        }
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $userUpdates[] = 'email = ?';
            $params[] = $email;
        }
        if (!empty($userUpdates)) {
            $params[] = $userId;
            $uStmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?');
            $uStmt->execute($params);
        }
    }

    $dobSql = null;
    if (!empty($dateOfBirth)) {
        $time = strtotime($dateOfBirth);
        if ($time !== false) {
            $dobSql = date('Y-m-d', $time);
        }
    }

    $sql = 'INSERT INTO profiles (user_id, phone, date_of_birth, blood_group, address, is_care_team_sharing_on)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            phone = COALESCE(VALUES(phone), phone),
            date_of_birth = COALESCE(VALUES(date_of_birth), date_of_birth),
            blood_group = COALESCE(VALUES(blood_group), blood_group),
            address = COALESCE(VALUES(address), address),
            is_care_team_sharing_on = VALUES(is_care_team_sharing_on)';

    $pStmt = $pdo->prepare($sql);
    $pStmt->execute([
        $userId,
        $phone ?: null,
        $dobSql,
        $bloodGroup ?: null,
        $address ?: null,
        $isCareTeamSharingOn
    ]);

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(500, [
        'success' => false,
        'message' => 'Profile update failed: ' . $e->getMessage()
    ]);
}
