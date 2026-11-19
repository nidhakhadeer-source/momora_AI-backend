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

// Comprehensive nutrition plans catalogue per trimester (general, non-diagnostic)
$trimesterCatalog = [
    1 => [
        'trimester_title' => 'Nourishing ideas for your first trimester.',
        'featured_meal_category' => 'BREAKFAST INSPIRATION',
        'featured_meal_title' => 'Ginger Berry Oatmeal Bowl',
        'featured_meal_details' => '15 min · Nausea-friendly · Rich in folate',
        'meals' => [
            [
                'type' => 'breakfast',
                'title' => 'Ginger Berry Oatmeal Bowl',
                'preparationMinutes' => 15,
                'tags' => ['Nausea-friendly', 'Folate-rich', 'Warm & gentle'],
                'ingredients' => ['Rolled oats', 'Almond milk or water', 'Fresh grated ginger', 'Chia seeds', 'Fresh blueberries'],
                'steps' => [
                    'Simmer rolled oats with milk and a pinch of fresh ginger for 5 minutes.',
                    'Stir in chia seeds and transfer into a warm breakfast bowl.',
                    'Top with fresh blueberries and enjoy in small, gentle spoonfuls.'
                ]
            ],
            [
                'type' => 'lunch',
                'title' => 'Avocado & Egg Whole-Grain Toast',
                'preparationMinutes' => 12,
                'tags' => ['Choline-rich', 'Protein', 'Quick prep'],
                'ingredients' => ['Whole-grain bread', 'Ripe avocado', 'Hard-boiled or fully cooked egg', 'Fresh lemon juice', 'Sesame seeds'],
                'steps' => [
                    'Toast whole-grain bread until pleasantly crisp.',
                    'Mash avocado with a squeeze of fresh lemon juice and spread evenly.',
                    'Layer with sliced fully cooked egg and sprinkle with sesame seeds.'
                ]
            ],
            [
                'type' => 'snack',
                'title' => 'Greek Yogurt with Crushed Walnuts',
                'preparationMinutes' => 5,
                'tags' => ['Probiotics', 'Omega-3', 'Easy on stomach'],
                'ingredients' => ['Pasteurized plain Greek yogurt', 'Chopped walnuts', 'Drizzle of pure honey or maple syrup', 'Ground cinnamon'],
                'steps' => [
                    'Spoon pasteurized Greek yogurt into a small bowl.',
                    'Sprinkle with crushed walnuts and a dash of ground cinnamon.',
                    'Drizzle lightly with honey or maple syrup.'
                ]
            ],
            [
                'type' => 'dinner',
                'title' => 'Steamed Lemon Herb Salmon & Brown Rice',
                'preparationMinutes' => 25,
                'tags' => ['DHA & EPA', 'Easy digestion', 'Well-cooked'],
                'ingredients' => ['Salmon fillet (cooked thoroughly)', 'Steamed brown rice', 'Tender sliced zucchini', 'Olive oil & lemon zest', 'Fresh dill'],
                'steps' => [
                    'Steam or bake salmon fillet until thoroughly cooked and flaky throughout.',
                    'Steam sliced zucchini alongside fragrant brown rice.',
                    'Garnish with fresh lemon zest and dill before serving warm.'
                ]
            ]
        ],
        'priorities' => [
            [
                'id' => 1,
                'title' => 'Folate & iron support',
                'subtitle' => 'Dark leafy greens, fortified grains, and lentils.',
                'iconType' => 'IRON',
                'detail' => 'During the first trimester, dietary folate plays a critical role in cellular replication and healthy neural tube development. Iron supports your steadily expanding blood plasma volume and helps protect against early maternal fatigue. Pairing plant-based iron foods with vitamin C helps maximize natural nutrient bioavailability. Eating modest, frequent portions can help maintain comfortable digestion.',
                'foods' => ['Steamed spinach', 'Cooked brown lentils', 'Fortified whole-grain cereals', 'Black beans', 'Edamame'],
                'tip' => 'Pair plant-based iron sources with citrus or bell peppers to enhance natural absorption.'
            ],
            [
                'id' => 2,
                'title' => 'Gentle protein & vitamin B6',
                'subtitle' => 'Easy-to-digest sources that help soothe mild nausea.',
                'iconType' => 'CALCIUM',
                'detail' => 'Vitamin B6 and lean proteins work harmoniously to stabilize daytime energy and help soothe common first-trimester nausea. Choosing bland, well-tolerated proteins like pasteurized dairy, eggs, and tofu ensures steady tissue growth. Small, frequent snacks can prevent an empty stomach, which often triggers morning queasiness. Keeping snacks simple and plain allows for easier digestion throughout the day.',
                'foods' => ['Pasteurized Greek yogurt', 'Hard-boiled eggs', 'Firm tofu', 'Mild cottage cheese', 'Skinless poultry'],
                'tip' => 'Keep dry whole-grain crackers or plain nuts beside your bed to eat before standing up in the morning.'
            ],
            [
                'id' => 3,
                'title' => 'Sip fluids steadily',
                'subtitle' => 'Keep cool water, broths, and herb infusions near.',
                'iconType' => 'HYDRATION',
                'detail' => 'Adequate fluid intake supports the swift expansion of maternal blood volume and amniotic fluid formation. If plain water feels unappealing due to taste changes, fruit-infused water or clear broths provide refreshing alternatives. Sipping slowly between meals rather than gulping large amounts with food prevents feeling uncomfortably full. Consistent hydration also helps alleviate sluggish digestion and early pregnancy headaches.',
                'foods' => ['Lemon-infused water', 'Clear vegetable broth', 'Cucumber slices', 'Coconut water', 'Watermelon chunks'],
                'tip' => 'Sip fluids slowly throughout the day rather than drinking large glasses all at once.'
            ]
        ]
    ],
    2 => [
        'trimester_title' => 'Balanced ideas for your second trimester.',
        'featured_meal_category' => 'LUNCH INSPIRATION',
        'featured_meal_title' => 'Chickpea & quinoa bowl',
        'featured_meal_details' => '25 min · Plant-based · Protein & fiber',
        'meals' => [
            [
                'type' => 'breakfast',
                'title' => 'Spinach & Cheddar Scramble with Sweet Potato',
                'preparationMinutes' => 18,
                'tags' => ['High Protein', 'Choline', 'Complex Carbs'],
                'ingredients' => ['Two thoroughly cooked eggs', 'Fresh baby spinach', 'Grated pasteurized cheddar', 'Roasted sweet potato cubes', 'Olive oil'],
                'steps' => [
                    'Sauté baby spinach in olive oil until soft and wilted.',
                    'Whisk and cook eggs thoroughly with spinach and top with melted cheddar.',
                    'Serve alongside warm roasted sweet potato cubes.'
                ]
            ],
            [
                'type' => 'lunch',
                'title' => 'Chickpea & Quinoa Bowl',
                'preparationMinutes' => 25,
                'tags' => ['Plant-based', 'Protein & fiber', 'Colorful prep'],
                'ingredients' => ['Cooked rainbow quinoa', 'Rinsed chickpeas', 'Diced cucumbers and cherry tomatoes', 'Avocado slices', 'Tahini lemon dressing'],
                'steps' => [
                    'Assemble fluffy cooked quinoa and rinsed chickpeas in a wide bowl.',
                    'Arrange diced cucumbers, tomatoes, and ripe avocado on top.',
                    'Drizzle with creamy tahini lemon dressing before tossing.'
                ]
            ],
            [
                'type' => 'snack',
                'title' => 'Apple Slices with Almond Butter',
                'preparationMinutes' => 5,
                'tags' => ['Natural Fiber', 'Healthy Fats', 'Quick Snack'],
                'ingredients' => ['Crisp apple', 'All-natural almond butter', 'Chia seed sprinkle'],
                'steps' => [
                    'Core and slice a crisp apple into even wedges.',
                    'Serve with two tablespoons of smooth almond butter.',
                    'Dust with chia seeds for added texture and nutrients.'
                ]
            ],
            [
                'type' => 'dinner',
                'title' => 'Grilled Herb Chicken with Roasted Broccoli',
                'preparationMinutes' => 30,
                'tags' => ['Lean Iron', 'Calcium Support', 'Comforting'],
                'ingredients' => ['Lean chicken breast (well cooked)', 'Broccoli florets', 'Minced garlic and olive oil', 'Cooked brown rice or farro', 'Lemon wedge'],
                'steps' => [
                    'Season and grill chicken breast thoroughly until fully cooked and tender.',
                    'Roast broccoli florets with olive oil and minced garlic until tender-crisp.',
                    'Serve over warm grains with a fresh squeeze of lemon.'
                ]
            ]
        ],
        'priorities' => [
            [
                'id' => 1,
                'title' => 'Iron-rich foods',
                'subtitle' => 'Lentils, beans and well-cooked lean meats.',
                'iconType' => 'IRON',
                'detail' => 'During the second trimester, your body requires significantly more iron to support fetal growth and placenta circulation. Incorporating diverse sources such as beans, lentils, and lean meats helps replenish your red blood cell supply. Vitamin C-rich vegetables like bell peppers or broccoli boost plant-based iron bioavailability. Maintaining adequate iron reserves helps sustain your daily energy as baby grows rapidly.',
                'foods' => ['Lentils', 'Black beans', 'Lean well-cooked beef', 'Cooked spinach', 'Firm tofu', 'Pumpkin seeds'],
                'tip' => 'Combine beans or lentils with citrus dressing or tomatoes to maximize plant iron absorption.'
            ],
            [
                'id' => 2,
                'title' => 'Calcium & protein',
                'subtitle' => 'Pasteurized yogurt or fortified alternatives.',
                'iconType' => 'CALCIUM',
                'detail' => 'Your baby\'s skeleton is undergoing rapid ossification, requiring substantial daily calcium deposits from maternal intake. If dietary calcium is insufficient, your body will draw from your own bone stores, making consistent daily intake essential. Protein provides the fundamental structural amino acids needed for muscle and organ growth. Choosing pasteurized dairy or fortified plant alternatives protects maternal bone density.',
                'foods' => ['Pasteurized milk or yogurt', 'Fortified almond or soy milk', 'Cheddar cheese', 'Tofu prepared with calcium', 'Cooked kale'],
                'tip' => 'Aim for three servings of calcium-rich foods distributed evenly throughout your day.'
            ],
            [
                'id' => 3,
                'title' => 'Keep water nearby',
                'subtitle' => 'Sip regularly; adapt to your care plan.',
                'iconType' => 'HYDRATION',
                'detail' => 'Maintaining optimal hydration is critical for expanding amniotic fluid levels and supporting increased kidney filtration. Drinking sufficient water also helps prevent common second-trimester issues like constipation and mild swelling. Keep a reusable water bottle within arm’s reach at home and work as a visual reminder. Add cucumber slices or mint leaves if you prefer light natural flavor.',
                'foods' => ['Filtered water', 'Cucumber mint water', 'Electrolyte coconut water', 'Herbal rooibos tea', 'Fresh orange slices'],
                'tip' => 'Keep a full water bottle by your desk or bedside table to sip consistently between meals.'
            ]
        ]
    ],
    3 => [
        'trimester_title' => 'Nutrient-dense comfort for your third trimester.',
        'featured_meal_category' => 'DINNER INSPIRATION',
        'featured_meal_title' => 'Slow-Baked Salmon & Sweet Potato',
        'featured_meal_details' => '35 min · Omega-3 & fiber · Heartburn-friendly',
        'meals' => [
            [
                'type' => 'breakfast',
                'title' => 'Warm Chia & Berry Porridge',
                'preparationMinutes' => 12,
                'tags' => ['High Fiber', 'Gentle Digestion', 'Omega-3'],
                'ingredients' => ['Rolled oats & chia seeds', 'Warm oat milk', 'Stewed blueberries', 'Ground flaxseed', 'Cinnamon'],
                'steps' => [
                    'Cook oats and chia seeds gently in warm oat milk until creamy.',
                    'Fold in ground flaxseed and a dash of sweet cinnamon.',
                    'Top with warm stewed blueberries and enjoy while comforting and warm.'
                ]
            ],
            [
                'type' => 'lunch',
                'title' => 'Mediterranean Lentil & Avocado Wrap',
                'preparationMinutes' => 15,
                'tags' => ['Plant Protein', 'Fiber Rich', 'Heartburn-safe'],
                'ingredients' => ['Whole-wheat tortilla wrap', 'Cooked brown lentils', 'Sliced avocado', 'Shredded carrots & baby greens', 'Mild hummus'],
                'steps' => [
                    'Spread mild hummus generously over a whole-wheat tortilla.',
                    'Layer with warm cooked lentils, shredded carrots, and greens.',
                    'Top with avocado slices, wrap tightly, and slice into easy-to-hold halves.'
                ]
            ],
            [
                'type' => 'snack',
                'title' => 'Almond & Date Energy Bites',
                'preparationMinutes' => 10,
                'tags' => ['Natural Energy', 'Potassium', 'No Added Sugar'],
                'ingredients' => ['Medjool dates (pitted)', 'Raw almonds', 'Rolled oats', 'Unsweetened shredded coconut'],
                'steps' => [
                    'Pulse dates, almonds, and oats in a blender until finely crumbled.',
                    'Roll mixture into small, bite-sized rounds with clean palms.',
                    'Dust lightly with shredded coconut and chill for a quick, wholesome snack.'
                ]
            ],
            [
                'type' => 'dinner',
                'title' => 'Slow-Baked Salmon & Sweet Potato',
                'preparationMinutes' => 35,
                'tags' => ['DHA Brain Fuel', 'Vitamins A & C', 'Easy Cleanup'],
                'ingredients' => ['Salmon fillet (cooked thoroughly)', 'Baked sweet potato halves', 'Steamed green beans', 'Olive oil & fresh herbs'],
                'steps' => [
                    'Bake seasoned salmon fillet at 190°C until fully cooked through and tender.',
                    'Roast sweet potato halves until caramelized and soft.',
                    'Serve with crisp steamed green beans tossed in light olive oil.'
                ]
            ]
        ],
        'priorities' => [
            [
                'id' => 1,
                'title' => 'Omega-3 & brain development',
                'subtitle' => 'DHA-rich salmon, chia seeds, and walnuts.',
                'iconType' => 'IRON',
                'detail' => 'During the third trimester, baby’s brain undergoes an incredible growth spurt, accumulating significant amounts of DHA. Omega-3 fatty acids also support retina development and promote healthy maternal cardiovascular function. Sourcing DHA from well-cooked low-mercury fish or plant-based algae oil ensures pure, safe nourishment. Incorporating walnuts and chia seeds provides additional plant-based fatty acid precursors.',
                'foods' => ['Well-cooked wild salmon', 'Chia seeds', 'Crushed walnuts', 'Algae-derived DHA oil', 'Flaxseed meal'],
                'tip' => 'Choose well-cooked low-mercury fish twice a week or use an algae-based DHA supplement.'
            ],
            [
                'id' => 2,
                'title' => 'High fiber & smaller portions',
                'subtitle' => 'Gentle digestion that eases third-trimester fullness.',
                'iconType' => 'CALCIUM',
                'detail' => 'As baby expands and compresses stomach capacity, eating smaller and more frequent meals relieves common heartburn. Soluble and insoluble dietary fiber keep bowel movements regular and ease late-pregnancy constipation. Drinking water between meals rather than during eating helps prevent uncomfortable stomach distension. Choosing unrefined grains and stewed fruits provides gentle, slow-burning digestive comfort.',
                'foods' => ['Cooked oats', 'Pitted dates', 'Stewed pears', 'Lentil soup', 'Steamed sweet potatoes'],
                'tip' => 'Enjoy 5 to 6 modest meals spaced throughout the day rather than 3 heavy sittings.'
            ],
            [
                'id' => 3,
                'title' => 'Mineral-rich hydration',
                'subtitle' => 'Electrolytes and fluids to support circulation.',
                'iconType' => 'HYDRATION',
                'detail' => 'Late-pregnancy fluid demands remain high to maintain amniotic volume and support kidney filtration. Electrolyte minerals such as potassium and magnesium help soothe occasional nighttime leg cramps and support muscle relaxation. Sipping water steadily throughout the daytime ensures optimal hydration while reducing late-night bathroom trips. Coconut water and broths offer gentle, natural electrolyte replenishment.',
                'foods' => ['Fresh coconut water', 'Cucumber-infused water', 'Light mineral water', 'Warm bone or vegetable broth', 'Diluted pomegranate juice'],
                'tip' => 'Taper large beverage intake an hour before bedtime while staying fully hydrated during daylight hours.'
            ]
        ]
    ]
];

$defaultPlan = $trimesterCatalog[$trimester] ?? $trimesterCatalog[2];

// Query database for trimester plan
$stmt = $pdo->prepare('SELECT * FROM nutrition_plans WHERE trimester = ?');
$stmt->execute([$trimester]);
$plan = $stmt->fetch();

if ($plan) {
    $trimesterTitle = $plan['trimester_title'] ?? $defaultPlan['trimester_title'];
    $featuredMealCategory = $plan['featured_meal_category'] ?? $defaultPlan['featured_meal_category'];
    $featuredMealTitle = $plan['featured_meal_title'] ?? $defaultPlan['featured_meal_title'];
    $featuredMealDetails = $plan['featured_meal_details'] ?? $defaultPlan['featured_meal_details'];

    $rawPriorities = !empty($plan['priorities'])
        ? (is_string($plan['priorities']) ? json_decode($plan['priorities'], true) : $plan['priorities'])
        : null;

    if (is_array($rawPriorities) && !empty($rawPriorities)) {
        $priorities = [];
        foreach ($rawPriorities as $i => $item) {
            $fallback = $defaultPlan['priorities'][$i] ?? ($defaultPlan['priorities'][0] ?? []);
            $detail = !empty($item['detail']) ? $item['detail'] : ($fallback['detail'] ?? '');
            $foods = !empty($item['foods']) && is_array($item['foods']) ? $item['foods'] : ($fallback['foods'] ?? []);
            $tip = !empty($item['tip']) ? $item['tip'] : ($fallback['tip'] ?? '');

            $priorities[] = array_merge($item, [
                'detail' => $detail,
                'foods' => $foods,
                'tip' => $tip
            ]);
        }
    } else {
        $priorities = $defaultPlan['priorities'];
    }

    $meals = $defaultPlan['meals'];
} else {
    $trimesterTitle = $defaultPlan['trimester_title'];
    $featuredMealCategory = $defaultPlan['featured_meal_category'];
    $featuredMealTitle = $defaultPlan['featured_meal_title'];
    $featuredMealDetails = $defaultPlan['featured_meal_details'];
    $priorities = $defaultPlan['priorities'];
    $meals = $defaultPlan['meals'];
}

respond(200, [
    'success' => true,
    'data' => clean_dots([
        'trimesterTitle' => $trimesterTitle,
        'featuredMealCategory' => $featuredMealCategory,
        'featuredMealTitle' => $featuredMealTitle,
        'featuredMealDetails' => $featuredMealDetails,
        'priorities' => $priorities,
        'meals' => $meals
    ])
]);
