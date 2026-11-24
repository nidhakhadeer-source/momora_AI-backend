<?php
// =============================================================================
// Momora AI - Admin Profile Endpoint (GET)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = require_auth(['admin']);
$adminId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed. GET required.'
    ]);
}

$stmt = $pdo->prepare('SELECT name, email, role, created_at, is_active FROM users WHERE id = ?');
$stmt->execute([$adminId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    respond(404, [
        'success' => false,
        'message' => 'Admin profile not found'
    ]);
}

respond(200, [
    'success' => true,
    'data' => [
        'name' => $profile['name'],
        'email' => $profile['email'],
        'role' => $profile['role'],
        'created_at' => $profile['created_at'],
        'is_active' => (int)$profile['is_active']
    ]
]);
