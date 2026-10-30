<?php
// =============================================================================
// Momora AI - Conversational Guidance Endpoint (GET & POST)
// =============================================================================

require_once __DIR__ . '/../config/config.php';

$user = authenticateUser($pdo, 'patient');
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getInput();
    $userText = trim($input['message'] ?? ($input['text'] ?? ($input['query'] ?? '')));

    if (empty($userText)) {
        respond(422, ['success' => false, 'message' => 'Please enter a message']);
    }

    $nowTime = date('H:i');

    // Save user message to DB
    $uStmt = $pdo->prepare(
        'INSERT INTO ai_chat_messages (user_id, sender, message_text, created_at)
         VALUES (?, "USER", ?, NOW())'
    );
    $uStmt->execute([$userId, $userText]);
    $userMsgId = $pdo->lastInsertId();

    // Generate responsive educational guidance
    $lower = strtolower($userText);
    $bulletPoints = [];
    $footerNote = "Momora AI educational guidance · Consult Dr Amelia Chen or your clinic for clinical diagnosis.";

    if (strpos($lower, 'heartburn') !== false) {
        $aiText = "That sounds uncomfortable. For pregnancy-related heartburn, safe non-pharmacological steps include:";
        $bulletPoints = [
            "Eat smaller, more frequent meals rather than large portions.",
            "Remain upright for at least 30 to 45 minutes after eating.",
            "Avoid spicy, acidic, or fried foods near bedtime."
        ];
    } elseif (strpos($lower, 'sleep') !== false || strpos($lower, 'tired') !== false) {
        $aiText = "Rest is essential during pregnancy. Practical comfort tips include:";
        $bulletPoints = [
            "Sleep on your left side to maximize blood and nutrient flow to the placenta.",
            "Use a pregnancy pillow between your knees and under your abdomen for support.",
            "Establish a gentle wind-down routine without screens 30 minutes before bed."
        ];
    } elseif (strpos($lower, 'kick') !== false || strpos($lower, 'movement') !== false) {
        $aiText = "Monitoring fetal movement is a wonderful way to connect with your baby:";
        $bulletPoints = [
            "Most mothers feel distinct movements between 18 and 24 weeks.",
            "Note your baby's typical daily rhythms and peaks of activity.",
            "If you notice a significant decrease in usual movements, contact your care team promptly."
        ];
    } elseif (strpos($lower, 'vitamin') !== false || strpos($lower, 'iron') !== false) {
        $aiText = "Prenatal vitamins support both maternal and fetal nutritional needs:";
        $bulletPoints = [
            "Take iron supplements with water or orange juice (vitamin C helps absorption).",
            "Avoid taking calcium supplements or dairy at the exact same moment as iron.",
            "Always follow your obstetrician's prescribed dosages."
        ];
    } else {
        $aiText = "Thank you for asking. Here is some general pregnancy wellbeing guidance regarding '{$userText}':";
        $bulletPoints = [
            "Stay well-hydrated throughout the day with regular sips of water.",
            "Listen to your body and pace your daily physical activities.",
            "Note any persisting questions to discuss at your upcoming antenatal visit."
        ];
    }

    // Save AI response to DB
    $aStmt = $pdo->prepare(
        'INSERT INTO ai_chat_messages (user_id, sender, message_text, bullet_points, footer_note, created_at)
         VALUES (?, "AI", ?, ?, ?, NOW())'
    );
    $aStmt->execute([$userId, $aiText, json_encode($bulletPoints), $footerNote]);
    $aiMsgId = $pdo->lastInsertId();

    respond(200, [
        'success' => true,
        'data' => [
            'id' => (string)$aiMsgId,
            'sender' => 'AI',
            'text' => $aiText,
            'bulletPoints' => $bulletPoints,
            'footerNote' => $footerNote,
            'timestampText' => "Momora AI · {$nowTime}"
        ]
    ]);
}

// GET Conversation History
$stmt = $pdo->prepare(
    'SELECT id, sender, message_text, bullet_points, footer_note, created_at
     FROM ai_chat_messages
     WHERE user_id = ?
     ORDER BY created_at ASC LIMIT 50'
);
$stmt->execute([$userId]);
$messages = $stmt->fetchAll();

if (empty($messages)) {
    // Return standard welcome exchange
    $messages = [
        [
            'id' => '1',
            'sender' => 'USER',
            'message_text' => 'What can I try for heartburn after lunch?',
            'bullet_points' => null,
            'footer_note' => null,
            'created_at' => date('Y-m-d 08:20:00')
        ],
        [
            'id' => '2',
            'sender' => 'AI',
            'message_text' => "That sounds uncomfortable. You could try:",
            'bullet_points' => json_encode([
                "Smaller meals, eaten slowly.",
                "Sitting upright after eating.",
                "Noticing foods that trigger discomfort."
            ]),
            'footer_note' => "Check with your pharmacist or maternity team before trying medicines or supplements.",
            'created_at' => date('Y-m-d 08:20:05')
        ]
    ];
}

$items = [];
foreach ($messages as $m) {
    $bullets = is_string($m['bullet_points']) ? json_decode($m['bullet_points'], true) : ($m['bullet_points'] ?? []);
    $senderLabel = $m['sender'] === 'USER' ? 'You' : 'Momora AI';
    $time = date('H:i', strtotime($m['created_at']));

    $items[] = [
        'id' => (string)$m['id'],
        'sender' => $m['sender'],
        'text' => $m['message_text'],
        'bulletPoints' => is_array($bullets) ? $bullets : [],
        'footerNote' => $m['footer_note'] ?: null,
        'timestampText' => "{$senderLabel} · {$time}"
    ];
}

respond(200, [
    'success' => true,
    'data' => $items
]);

