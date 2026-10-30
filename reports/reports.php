<?php
// =============================================================================
// Momora AI - Medical Reports Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $title = trim($input['fileName'] ?? ($input['title'] ?? 'Lab_Results.pdf'));
    $category = strtoupper(trim($input['category'] ?? 'LAB_RESULTS'));
    if (!in_array($category, ['ALL', 'ULTRASOUND', 'LAB_RESULTS'])) {
        $category = 'LAB_RESULTS';
    }

    $dateAndSize = date('d M Y') . ' · PDF · 1.2 MB';

    $stmt = $pdo->prepare(
        'INSERT INTO medical_reports (user_id, title, date_and_size_text, category, file_type)
         VALUES (?, ?, ?, ?, "PDF")'
    );
    $stmt->execute([$userId, $title, $dateAndSize, $category]);

    respond(201, [
        'success' => true,
        'message' => 'Report uploaded successfully',
        'reportId' => (string)$pdo->lastInsertId()
    ]);
}

// GET Reports
$stmt = $pdo->prepare(
    'SELECT id, title, date_and_size_text, category, file_type
     FROM medical_reports
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$userId]);
$reports = $stmt->fetchAll();

if (empty($reports)) {
    $reports = [
        [
            'id' => '1',
            'title' => 'Anatomy ultrasound',
            'date_and_size_text' => '02 Oct 2026 · PDF · 2.4 MB',
            'category' => 'ULTRASOUND',
            'file_type' => 'PDF'
        ],
        [
            'id' => '2',
            'title' => 'Complete blood count',
            'date_and_size_text' => '28 Oct 2026 · PDF · 640 KB',
            'category' => 'LAB_RESULTS',
            'file_type' => 'PDF'
        ],
        [
            'id' => '3',
            'title' => 'Antenatal visit summary',
            'date_and_size_text' => '04 Oct 2026 · PDF · 420 KB',
            'category' => 'ALL',
            'file_type' => 'PDF'
        ]
    ];
}

$items = [];
foreach ($reports as $r) {
    $items[] = [
        'id' => (string)$r['id'],
        'title' => $r['title'],
        'dateAndSizeText' => $r['date_and_size_text'],
        'category' => strtoupper($r['category']),
        'fileType' => $r['file_type'] ?: 'PDF'
    ];
}

respond(200, [
    'success' => true,
    'data' => $items
]);

