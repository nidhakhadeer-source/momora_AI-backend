<?php
// =============================================================================
// Momora AI - Notifications Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo);
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all as read
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1, is_new = 0 WHERE user_id = ?');
    $stmt->execute([$userId]);

    respond(200, [
        'success' => true,
        'message' => 'All notifications marked as read'
    ]);
}

// GET Notifications
$stmt = $pdo->prepare(
    'SELECT id, title, subtitle, detail_text, type, is_new, is_read
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC LIMIT 20'
);
$stmt->execute([$userId]);
$notifs = $stmt->fetchAll();

if (empty($notifs)) {
    $notifs = [
        [
            'id' => '1',
            'title' => 'Your appointment is confirmed',
            'subtitle' => 'Dr Amelia Chen · 29 Oct 2026, 10:30',
            'detail_text' => '09:10 · Willow Maternity Clinic · In person',
            'is_new' => 1,
            'is_read' => 0,
            'type' => 'APPOINTMENT'
        ],
        [
            'id' => '2',
            'title' => "Tonight's vitamin D reminder",
            'subtitle' => '400 IU · 1 tablet at 20:00',
            'detail_text' => '08:30 · Reminder scheduled',
            'is_new' => 1,
            'is_read' => 0,
            'type' => 'REMINDER'
        ],
        [
            'id' => '3',
            'title' => 'Your report was reviewed',
            'subtitle' => 'Dr Chen reviewed your blood count.',
            'detail_text' => '08:25 · View care-team notes in Medical Reports',
            'is_new' => 1,
            'is_read' => 0,
            'type' => 'REPORT_REVIEW'
        ],
        [
            'id' => '4',
            'title' => 'A new week to discover',
            'subtitle' => "Yesterday · Explore today's week 24 development.",
            'detail_text' => '',
            'is_new' => 0,
            'is_read' => 1,
            'type' => 'WEEKLY_DISCOVERY'
        ]
    ];
}

$items = [];
foreach ($notifs as $n) {
    $items[] = [
        'id' => (string)$n['id'],
        'title' => $n['title'],
        'subtitle' => $n['subtitle'],
        'detailText' => $n['detail_text'] ?? '',
        'isNew' => (bool)$n['is_new'],
        'isRead' => (bool)$n['is_read'],
        'type' => strtoupper($n['type'])
    ];
}

respond(200, [
    'success' => true,
    'data' => $items
]);

