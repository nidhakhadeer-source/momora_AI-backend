<?php
// =============================================================================
// Momora AI - Nutrition Plan Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

function clean_dots($value) {
    if (is_string($value)) {
        return preg_replace('/\s*\?\?\s*/u', ' · ', $value);
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = clean_dots($v);
        }
        return $result;
    }
    return $value;
}

$input = getInput();
$trimester = filter_var($input['trimester'] ?? 2, FILTER_VALIDATE_INT);
if (!$trimester || $trimester < 1 || $trimester > 3) $trimester = 2;

$stmt = $pdo->prepare('SELECT * FROM nutrition_plans WHERE trimester = ?');
$stmt->execute([$trimester]);
$plan = $stmt->fetch();

if (!$plan) {
    $plan = [
        'trimester_title' => 'Balanced ideas for your pregnancy journey.',
        'featured_meal_category' => 'LUNCH INSPIRATION',
        'featured_meal_title' => 'Chickpea & quinoa bowl',
        'featured_meal_details' => '25 min · Plant-based · Protein & fiber',
        'priorities' => json_encode([
            [
                'id' => 1,
                'title' => 'Iron-rich foods',
                'subtitle' => 'Lentils, beans and well-cooked lean meats.',
                'iconType' => 'IRON'
            ],
            [
                'id' => 2,
                'title' => 'Calcium & protein',
                'subtitle' => 'Pasteurized yogurt or fortified alternatives.',
                'iconType' => 'CALCIUM'
            ],
            [
                'id' => 3,
                'title' => 'Keep water nearby',
                'subtitle' => 'Sip regularly; adapt to your care plan.',
                'iconType' => 'HYDRATION'
            ]
        ])
    ];
}

$priorities = is_string($plan['priorities']) ? json_decode($plan['priorities'], true) : $plan['priorities'];

respond(200, [
    'success' => true,
    'data' => clean_dots([
        'trimesterTitle' => $plan['trimester_title'],
        'featuredMealCategory' => $plan['featured_meal_category'],
        'featuredMealTitle' => $plan['featured_meal_title'],
        'featuredMealDetails' => $plan['featured_meal_details'],
        'priorities' => is_array($priorities) ? $priorities : []
    ])
]);

