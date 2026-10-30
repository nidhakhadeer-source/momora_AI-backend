<?php
// =============================================================================
// Momora AI - Baby Development Milestones Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$input = getInput();
$week = filter_var($input['week'] ?? 24, FILTER_VALIDATE_INT);
if (!$week || $week < 1) $week = 24;

// Query database for week milestones
$stmt = $pdo->prepare('SELECT * FROM baby_development WHERE week = ?');
$stmt->execute([$week]);
$dev = $stmt->fetch();

if (!$dev) {
    // Generate educational milestone estimate if exact week not seeded
    $lengthEst = max(5, min(52, round(2.5 + ($week * 1.2))));
    $weightEst = max(10, min(3600, round(pow($week / 6.0, 3) * 60)));

    $dev = [
        'week' => $week,
        'subtitle' => "Week {$week} · Your baby's weekly development.",
        'length_cm_text' => "{$lengthEst} cm",
        'length_sub_text' => 'Head to heel · approximate',
        'weight_gram_text' => "{$weightEst} g",
        'weight_sub_text' => 'educational estimate',
        'highlights' => json_encode([
            [
                'id' => 1,
                'title' => 'A world of sound',
                'subtitle' => 'Hearing continues to develop this week.',
                'iconType' => 'SOUND'
            ],
            [
                'id' => 2,
                'title' => 'Little movements',
                'subtitle' => "You may notice your baby's own rhythm.",
                'iconType' => 'MOVEMENT'
            ]
        ])
    ];
}

$highlights = is_string($dev['highlights']) ? json_decode($dev['highlights'], true) : $dev['highlights'];
if (!is_array($highlights)) {
    $highlights = [];
}

respond(200, [
    'success' => true,
    'data' => [
        'currentWeek' => (int)$dev['week'],
        'weekSubtitle' => $dev['subtitle'],
        'lengthCmText' => $dev['length_cm_text'],
        'lengthSubText' => $dev['length_sub_text'] ?? 'Head to heel · approximate',
        'weightGramText' => $dev['weight_gram_text'],
        'weightSubText' => $dev['weight_sub_text'] ?? 'educational estimate',
        'highlights' => $highlights
    ]
]);

