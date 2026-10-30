<?php
// =============================================================================
// Momora AI - Fetch Patient Profile Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo);
$userId = (int)$user['id'];

$stmt = $pdo->prepare(
    'SELECT
        u.id AS user_id,
        u.name AS full_name,
        u.email,
        p.patient_code,
        p.phone,
        p.date_of_birth,
        p.blood_group,
        p.gender,
        p.address,
        p.is_care_team_sharing_on,
        p.reminder_summary,
        d.name AS care_team_doctor_name
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN users d ON d.id = p.assigned_doctor_id
     WHERE u.id = ?'
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    respond(404, ['success' => false, 'message' => 'Profile not found']);
}

// Generate initials (e.g. Maya Patel -> MP)
$words = explode(' ', trim($profile['full_name']));
$initials = '';
foreach ($words as $w) {
    if (!empty($w)) {
        $initials .= strtoupper($w[0]);
    }
}
$avatarInitials = substr($initials, 0, 2) ?: 'PT';

// Format date of birth nicely if set
$dobFormatted = $profile['date_of_birth'] ?? '';
if (!empty($profile['date_of_birth'])) {
    $dobFormatted = date('d M Y', strtotime($profile['date_of_birth']));
}

$patientCode = $profile['patient_code'] ?: ('P-' . (10000 + $userId));
$doctorName = $profile['care_team_doctor_name'] ?: 'Dr Amelia Chen';

respond(200, [
    'success' => true,
    'data' => [
        'avatarInitials' => $avatarInitials,
        'patientName' => $profile['full_name'],
        'patientId' => $patientCode,
        'fullName' => $profile['full_name'],
        'email' => $profile['email'],
        'dateOfBirth' => $dobFormatted,
        'bloodGroup' => $profile['blood_group'] ?: 'O positive',
        'isCareTeamSharingOn' => (bool)($profile['is_care_team_sharing_on'] ?? true),
        'careTeamDoctorName' => $doctorName,
        'reminderSummary' => $profile['reminder_summary'] ?: 'Medication and appointment alerts'
    ]
]);
