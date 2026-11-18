<?php
// =============================================================================
// Momora AI - Patient Advice Fetch Endpoint (GET)
// Endpoint: /patient/advice_fetch.php
// Returns the authenticated patient's advice rows from doctor_advice (newest first).
// =============================================================================

require_once __DIR__ . '/../config/config.php';

// 1. Require authenticated patient
$user = require_auth(['patient']);
$patientId = (int)$user['id'];

// 2. Fetch advice rows for authenticated patient, newest first
$stmt = $pdo->prepare(
    'SELECT
        da.id,
        da.doctor_id,
        da.patient_id,
        da.subject,
        da.advice_text,
        da.save_copy,
        da.created_at,
        u.name AS doctor_name
     FROM doctor_advice da
     LEFT JOIN users u ON u.id = da.doctor_id
     WHERE da.patient_id = ?
     ORDER BY da.created_at DESC, da.id DESC'
);
$stmt->execute([$patientId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adviceList = [];
foreach ($rows as $r) {
    $doctorName = !empty($r['doctor_name']) ? $r['doctor_name'] : 'Doctor';
    $subject = preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['subject']);
    $adviceText = preg_replace('/\s*\?\?\s*/u', ' · ', (string)$r['advice_text']);

    $adviceList[] = [
        'id' => (int)$r['id'],
        'doctorId' => (int)$r['doctor_id'],
        'doctor_id' => (int)$r['doctor_id'],
        'doctorName' => $doctorName,
        'doctor_name' => $doctorName,
        'subject' => $subject,
        'adviceText' => $adviceText,
        'advice_text' => $adviceText,
        'createdAt' => $r['created_at'],
        'created_at' => $r['created_at']
    ];
}

respond(200, [
    'success' => true,
    'total' => count($adviceList),
    'data' => $adviceList
]);
