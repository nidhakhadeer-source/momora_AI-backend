<?php
// =============================================================================
// Momora AI - Pregnancy AI Assistant Service Helper
// Providers supported: Google Gemini (Recommended), OpenAI, or Fallback reference
// =============================================================================

require_once __DIR__ . '/../config/config.php';

/**
 * Detect critical emergency warning signs in question
 */
function detectPregnancyEmergency($text) {
    $lower = strtolower($text);
    $emergencyTriggers = [
        'heavy bleeding', 'vaginal bleeding', 'soaking pads', 'blood clots',
        'severe abdominal pain', 'severe pelvic pain', 'sharp abdominal pain',
        'severe cramping', 'leaking fluid', 'water broke', 'water breaking',
        'difficulty breathing', 'shortness of breath', 'chest pain',
        'severe headache with blurred vision', 'blurred vision', 'seeing spots',
        'seizure', 'convulsion', 'fainting', 'passed out', 'loss of consciousness',
        'high fever', 'uncontrolled vomiting', 'cannot keep fluids down',
        'reduced fetal movement', 'baby not moving', 'no fetal movement', 'stopped kicking'
    ];

    foreach ($emergencyTriggers as $trigger) {
        if (strpos($lower, $trigger) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Check if the text is clearly outside pregnancy domain
 */
function isNonPregnancyQuery($text) {
    $lower = strtolower($text);

    // Explicit non-pregnancy indicators
    $nonPregnancyKeywords = [
        'python', 'javascript', 'java', 'c++', 'html', 'css', 'sql', 'programming',
        'write code', 'debug code', 'algorithm', 'bitcoin', 'cryptocurrency',
        'stock market', 'car repair', 'fix engine', 'plumbing', 'football match',
        'cricket match', 'fifa', 'who won the election', 'politics', 'movie review',
        'video game', 'cheat codes', 'recipe for pizza', 'how to bake a cake'
    ];

    foreach ($nonPregnancyKeywords as $kw) {
        if (strpos($lower, $kw) !== false) {
            // Confirm it doesn't also mention pregnancy/baby
            if (strpos($lower, 'pregnan') === false && strpos($lower, 'trimester') === false &&
                strpos($lower, 'baby') === false && strpos($lower, 'fetus') === false &&
                strpos($lower, 'maternal') === false && strpos($lower, 'prenatal') === false) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Main AI Generation function
 *
 * @param string $question User's pregnancy question
 * @param array $userContext Optional patient context (name, gestational week)
 * @return array Result array with success, data, or error message
 */
function generatePregnancyAiResponse($question, $userContext = []) {
    $question = trim($question);
    if (empty($question)) {
        return [
            'success' => false,
            'error_code' => 'EMPTY_QUESTION',
            'message' => 'Please enter a question'
        ];
    }

    // 1. FAST-PATH: Detect critical medical emergencies immediately
    if (detectPregnancyEmergency($question)) {
        return [
            'success' => true,
            'provider' => 'safety_triage',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => true,
                'message_text' => 'URGENT MEDICAL NOTICE: The symptoms you described may indicate a potential pregnancy emergency that requires immediate clinical evaluation. Please contact your obstetrician, visit the nearest maternity emergency triage room, or call your local emergency service (e.g. 112 / 911 / 108) immediately. Do not rely on an AI or delay seeking medical attention.',
                'bullet_points' => [
                    'Contact your maternity hospital triage helpline or obstetrician right now.',
                    'Do not take unprescribed painkillers, aspirin, or medicines before clinical evaluation.',
                    'Have a family member or caregiver stay with you and accompany you to the hospital.',
                    'Bring your antenatal medical records and note the exact time symptoms started.'
                ],
                'footer_note' => 'Momora AI Emergency Alert · Immediate clinical evaluation required.'
            ]
        ];
    }

    // 2. Identify active AI provider from environment
    $provider = strtolower(trim(getEnvVar('AI_PROVIDER', 'gemini')));

    // 3. Fallback educational reference engine (for offline testing / demo)
    if ($provider === 'fallback') {
        return generateFallbackResponse($question);
    }

    // 4. OpenAI provider integration
    if ($provider === 'openai') {
        return callOpenAiProvider($question);
    }

    // 5. Google Gemini provider integration (Default)
    return callGeminiProvider($question);
}

/**
 * Call Google Gemini REST API
 */
function callGeminiProvider($question) {
    $apiKey = getEnvVar('GEMINI_API_KEY', '');
    if (empty($apiKey) || $apiKey === 'your_gemini_api_key_here') {
        return [
            'success' => false,
            'error_code' => 'MISSING_API_KEY',
            'message' => 'AI Assistant service is not configured. Please set a valid GEMINI_API_KEY in the backend .env configuration file.'
        ];
    }

    $model = getEnvVar('GEMINI_MODEL', 'gemini-1.5-flash');
    $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

    $systemInstruction = "You are Momora AI, an empathetic, supportive, and knowledgeable pregnancy guidance assistant designed specifically for expectant mothers.\n\n"
        . "Core Rules:\n"
        . "1. Scope: Answer strictly questions related to pregnancy, prenatal health, fetal growth, common pregnancy symptoms, postpartum recovery, maternal nutrition, and safe physical activity.\n"
        . "2. Non-Pregnancy Questions: If the user asks about anything unrelated to pregnancy (e.g. computer programming, history, math, sports, cars, politics, general pop culture), set 'is_pregnancy_related' to false, set 'message_text' to 'I am Momora AI, designed exclusively to provide pregnancy monitoring and maternal wellness guidance. I can only assist with pregnancy-related questions. Please feel free to ask about nutrition, symptoms, baby milestones, or wellness during pregnancy!', and set 'bullet_points' to an empty array.\n"
        . "3. Medical Safety: You are an educational AI assistant, NOT a licensed doctor. Never diagnose clinical diseases or prescribe medicines. Never advise changing prescribed medications.\n"
        . "4. Emergency Advice: If any urgent signs appear (severe pain, bleeding, reduced fetal movement), set 'is_emergency' to true and advise urgent hospital triage.\n"
        . "5. Tone: Warm, supportive, empathetic, easy to read, and reassuring.\n"
        . "6. Output Format: Return a strictly valid JSON object without markdown fences, with these exact keys:\n"
        . "   {\n"
        . "     \"is_pregnancy_related\": true/false,\n"
        . "     \"is_emergency\": true/false,\n"
        . "     \"message_text\": \"Warm, direct explanation (2 to 4 sentences)\",\n"
        . "     \"bullet_points\": [\"Actionable tip 1\", \"Actionable tip 2\", \"Actionable tip 3\"],\n"
        . "     \"footer_note\": \"Momora AI educational guidance · Consult your doctor for clinical advice.\"\n"
        . "   }";

    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $question]
                ]
            ]
        ],
        'system_instruction' => [
            'parts' => [
                ['text' => $systemInstruction]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'response_mime_type' => 'application/json'
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8'
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $rawResponse = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        return [
            'success' => false,
            'error_code' => 'PROVIDER_NETWORK_ERROR',
            'message' => 'Unable to reach Google Gemini AI service: ' . $curlErr
        ];
    }

    $json = json_decode($rawResponse, true);

    if ($httpCode === 400 && isset($json['error']['message'])) {
        $msg = $json['error']['message'];
        if (strpos($msg, 'API key not valid') !== false || strpos($msg, 'API_KEY_INVALID') !== false) {
            return [
                'success' => false,
                'error_code' => 'INVALID_API_KEY',
                'message' => 'AI provider authentication failed: Invalid GEMINI_API_KEY configured in .env'
            ];
        }
        return [
            'success' => false,
            'error_code' => 'PROVIDER_BAD_REQUEST',
            'message' => 'AI provider error: ' . $msg
        ];
    }

    if ($httpCode === 429) {
        return [
            'success' => false,
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'message' => 'AI provider rate limit reached. Please wait a few moments and try again.'
        ];
    }

    if ($httpCode !== 200 || empty($json['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => false,
            'error_code' => 'PROVIDER_RESPONSE_ERROR',
            'message' => 'AI service returned an unexpected response (HTTP ' . $httpCode . ')'
        ];
    }

    $aiTextRaw = $json['candidates'][0]['content']['parts'][0]['text'];
    $parsed = json_decode($aiTextRaw, true);

    if (!is_array($parsed) || empty($parsed['message_text'])) {
        // Fallback cleanup if the model returned unstructured text
        $parsed = [
            'is_pregnancy_related' => true,
            'is_emergency' => false,
            'message_text' => trim($aiTextRaw),
            'bullet_points' => [],
            'footer_note' => 'Momora AI educational guidance · Consult your doctor for clinical advice.'
        ];
    }

    return [
        'success' => true,
        'provider' => 'gemini',
        'data' => [
            'is_pregnancy_related' => (bool)($parsed['is_pregnancy_related'] ?? true),
            'is_emergency' => (bool)($parsed['is_emergency'] ?? false),
            'message_text' => $parsed['message_text'],
            'bullet_points' => is_array($parsed['bullet_points'] ?? null) ? $parsed['bullet_points'] : [],
            'footer_note' => $parsed['footer_note'] ?? 'Momora AI educational guidance · Consult your doctor for clinical advice.'
        ]
    ];
}

/**
 * Call OpenAI REST API
 */
function callOpenAiProvider($question) {
    $apiKey = getEnvVar('OPENAI_API_KEY', '');
    if (empty($apiKey) || $apiKey === 'your_openai_api_key_here') {
        return [
            'success' => false,
            'error_code' => 'MISSING_API_KEY',
            'message' => 'AI Assistant service is not configured. Please set a valid OPENAI_API_KEY in the backend .env configuration file.'
        ];
    }

    $model = getEnvVar('OPENAI_MODEL', 'gpt-4o-mini');
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $systemPrompt = "You are Momora AI, an empathetic, supportive pregnancy guidance assistant for expectant mothers.\n"
        . "Answer ONLY pregnancy, prenatal wellness, nutrition, and baby development queries.\n"
        . "If non-pregnancy, set is_pregnancy_related to false, message_text to a polite decline, and empty bullet_points.\n"
        . "Output strict JSON with: is_pregnancy_related, is_emergency, message_text, bullet_points (array), footer_note.";

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $question]
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.3
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        return [
            'success' => false,
            'error_code' => 'PROVIDER_NETWORK_ERROR',
            'message' => 'Unable to reach OpenAI service: ' . $curlErr
        ];
    }

    $json = json_decode($raw, true);
    if ($httpCode === 401) {
        return [
            'success' => false,
            'error_code' => 'INVALID_API_KEY',
            'message' => 'AI provider authentication failed: Invalid OPENAI_API_KEY configured in .env'
        ];
    }

    if ($httpCode !== 200 || empty($json['choices'][0]['message']['content'])) {
        return [
            'success' => false,
            'error_code' => 'PROVIDER_RESPONSE_ERROR',
            'message' => 'OpenAI service error (HTTP ' . $httpCode . ')'
        ];
    }

    $parsed = json_decode($json['choices'][0]['message']['content'], true);
    return [
        'success' => true,
        'provider' => 'openai',
        'data' => [
            'is_pregnancy_related' => (bool)($parsed['is_pregnancy_related'] ?? true),
            'is_emergency' => (bool)($parsed['is_emergency'] ?? false),
            'message_text' => $parsed['message_text'] ?? '',
            'bullet_points' => is_array($parsed['bullet_points'] ?? null) ? $parsed['bullet_points'] : [],
            'footer_note' => $parsed['footer_note'] ?? 'Momora AI educational guidance · Consult your doctor for clinical advice.'
        ]
    ];
}

/**
 * Fallback verified educational reference engine (for offline/demo/testing mode)
 */
function generateFallbackResponse($question) {
    if (isNonPregnancyQuery($question)) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => false,
                'is_emergency' => false,
                'message_text' => 'I am Momora AI, designed exclusively to provide pregnancy monitoring and maternal wellness guidance. I can only assist with pregnancy-related questions. Please feel free to ask about nutrition, symptoms, baby milestones, or wellness during pregnancy!',
                'bullet_points' => [],
                'footer_note' => 'Momora AI · Exclusively dedicated to pregnancy & maternal health.'
            ]
        ];
    }

    $lower = strtolower($question);
    $footer = 'Momora AI educational guidance · Consult your obstetrician or clinic for clinical diagnosis.';

    if (strpos($lower, 'food') !== false || strpos($lower, 'diet') !== false || strpos($lower, 'eat') !== false || strpos($lower, 'nutrition') !== false) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => false,
                'message_text' => 'A balanced diet rich in essential nutrients supports both maternal health and healthy fetal development:',
                'bullet_points' => [
                    'Focus on folate-rich foods like dark leafy greens, citrus fruits, lentils, and fortified grains.',
                    'Include adequate protein from thoroughly cooked eggs, poultry, lean meats, beans, and tofu.',
                    'Consume calcium-rich foods like pasteurized milk, yogurt, and fortified plant milks.',
                    'Avoid unpasteurized dairy, raw or undercooked meats/seafood, and high-mercury fish like swordfish or shark.'
                ],
                'footer_note' => $footer
            ]
        ];
    }

    if (strpos($lower, 'precaution') !== false || strpos($lower, 'first trimester') !== false || strpos($lower, '1st trimester') !== false) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => false,
                'message_text' => 'During your first trimester, your baby experiences rapid organ development. Key precautions to observe include:',
                'bullet_points' => [
                    'Take a daily prenatal supplement containing at least 400 mcg of folic acid.',
                    'Avoid alcohol, tobacco, vaping, and limit caffeine to under 200 mg per day.',
                    'Avoid hot tubs, saunas, and intense heat that can raise maternal core body temperature.',
                    'Stay well-hydrated and attend your scheduled initial prenatal ultrasound and blood screenings.'
                ],
                'footer_note' => $footer
            ]
        ];
    }

    if (strpos($lower, 'morning sickness') !== false || strpos($lower, 'nausea') !== false || strpos($lower, 'vomit') !== false) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => false,
                'message_text' => 'Morning sickness is driven primarily by surging hCG and estrogen hormones and commonly peaks between weeks 8 and 11:',
                'bullet_points' => [
                    'Keep bland crackers or dry biscuits by your bedside to eat before getting out of bed.',
                    'Eat small, frequent meals throughout the day so your stomach never stays completely empty.',
                    'Sip cold water, ginger tea, or lemon water in small amounts throughout the day.',
                    'If you cannot keep liquids down for 24 hours, contact your care team for hyperemesis gravidarum evaluation.'
                ],
                'footer_note' => $footer
            ]
        ];
    }

    if (strpos($lower, 'exercise') !== false || strpos($lower, 'workout') !== false || strpos($lower, 'fitness') !== false || strpos($lower, 'walking') !== false || strpos($lower, 'yoga') !== false) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => false,
                'message_text' => 'Moderate, low-impact exercise during uncomplicated pregnancy helps boost energy, improve sleep, and reduce backaches:',
                'bullet_points' => [
                    'Brisk walking, prenatal yoga, and stationary cycling are generally considered safe and effective.',
                    'Swimming or water aerobics relieves pressure on your joints and back while keeping you cool.',
                    'Avoid contact sports, activities with fall risks (e.g. skiing, horseback riding), or lying flat on your back after the first trimester.',
                    'Always check with your obstetrician before starting or modifying an exercise routine.'
                ],
                'footer_note' => $footer
            ]
        ];
    }

    if (strpos($lower, 'symptom') !== false || strpos($lower, 'common') !== false) {
        return [
            'success' => true,
            'provider' => 'fallback',
            'data' => [
                'is_pregnancy_related' => true,
                'is_emergency' => false,
                'message_text' => 'Every pregnancy is unique, but common physiological symptoms across stages include:',
                'bullet_points' => [
                    'Fatigue and tender breasts, which are typical early in pregnancy as progesterone levels rise.',
                    'Mild cramping or round ligament stretches as your uterus expands.',
                    'Frequent urination and mild digestive changes such as heartburn or constipation.',
                    'Always discuss any persistent, unusual, or distressing symptoms with your maternity care team.'
                ],
                'footer_note' => $footer
            ]
        ];
    }

    // Default pregnancy guidance response
    return [
        'success' => true,
        'provider' => 'fallback',
        'data' => [
            'is_pregnancy_related' => true,
            'is_emergency' => false,
            'message_text' => "Thank you for asking Momora AI. Here is general pregnancy wellbeing guidance regarding '{$question}':",
            'bullet_points' => [
                'Prioritize regular hydration and wholesome, balanced meals throughout your day.',
                'Listen closely to your body and pace your daily physical activity with adequate rest.',
                'Record any personal questions in your Momora AI app to discuss during your upcoming prenatal visit.',
                'Always contact your obstetrician or maternity clinic promptly if any concerning changes occur.'
            ],
            'footer_note' => $footer
        ]
    ];
}
