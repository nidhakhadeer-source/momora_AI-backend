<?php
// =============================================================================
// Momora AI - Admin Patient Account Management Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $patientId = filter_var($input['patientId'] ?? ($input['id'] ?? 0), FILTER_VALIDATE_INT);

    if (!$patientId) {
        respond(422, ['success' => false, 'message' => 'Patient ID required']);
    }

    $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = "patient"');
    $stmt->execute([$patientId]);

    respond(200, [
        'success' => true,
        'message' => 'Patient account access updated successfully'
    ]);
}

// GET Patients List
$stmt = $pdo->query(
    'SELECT u.id, u.name, u.email, u.is_active, p.patient_code, p.phone, p.blood_group, p.date_of_birth,
            d.name AS doctor_name,
            pd.estimated_due_date, pd.gestational_weeks, pd.next_visit_clinic
     FROM users u
     LEFT JOIN profiles p ON p.user_id = u.id
     LEFT JOIN users d ON d.id = p.assigned_doctor_id
     LEFT JOIN pregnancy_details pd ON pd.user_id = u.id
     WHERE u.role = "patient"
     ORDER BY u.id ASC'
);
$patients = $stmt->fetchAll();

$items = [];
foreach ($patients as $i => $p) {
    $code = $p['patient_code'] ?: ('P-' . (1000 + (int)$p['id']));
    $isActive = (bool)$p['is_active'];
    $doctorName = $p['doctor_name'] ? ('Dr ' . preg_replace('/^Dr\s+/i', '', $p['doctor_name'])) : 'No doctor assigned';
    $docText = $isActive ? ('Assigned doctor: ' . $doctorName) : 'Account access disabled';
    $gestational = $p['gestational_weeks'] ? ($p['gestational_weeks'] . ' weeks') : null;

    $items[] = [
        'id' => (string)$p['id'],
        'name' => $p['name'],
        'patientIdText' => $code,
        'assignedDoctorText' => $docText,
        'emailText' => $p['email'],
        'isActive' => $isActive,
        'isHighlighted' => false,
        'phone' => $p['phone'] ?: null,
        'bloodGroup' => $p['blood_group'] ?: null,
        'dueDate' => $p['estimated_due_date'] ?: null,
        'gestationalAge' => $gestational,
        'clinic' => $p['next_visit_clinic'] ?: null,
        'assignedDoctor' => $doctorName,
        'dateOfBirth' => $p['date_of_birth'] ?: null
    ];
}

respond(200, [
    'success' => true,
    'data' => $items
]);
