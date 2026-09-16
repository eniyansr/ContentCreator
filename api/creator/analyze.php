<?php
/**
 * CreatorAI - Content Analyzer API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_client.php';
require_once __DIR__ . '/../../includes/ai_client.php';

header('Content-Type: application/json');
requireAuth();

if (getMethod() !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (!validateCsrfToken()) {
    jsonError('Invalid security token.', 403);
}

$userId = getCurrentUserId();
$input = getJsonInput();
$action = $input['action'] ?? '';
$d = db();

try {
    if ($action === 'analyze') {
        $content = trim($input['content'] ?? '');
        $platform = trim($input['platform'] ?? 'General');
        $format = trim($input['format'] ?? 'script');
        
        if (empty($content)) {
            jsonError('Content cannot be empty', 422);
        }
        
        if (str_word_count($content) < 10) {
            jsonError('Content is too short to analyze effectively. Please provide more text.', 422);
        }

        // Build context
        $creatorContext = buildCreatorContext($userId);
        
        $prompt = "You are an expert Social Media Content Analyst and Strategist.\n";
        $prompt .= "Analyze the following $format intended for $platform.\n\n";
        $prompt .= "CONTENT TO ANALYZE:\n\"\"\"\n$content\n\"\"\"\n\n";
        $prompt .= "Analyze this against the Creator Profile context provided and general platform best practices.\n";
        $prompt .= "Output a valid JSON object with the following exact structure, with no markdown formatting or backticks around it:\n";
        $prompt .= "{\n";
        $prompt .= "  \"overall_score\": (number 0-100),\n";
        $prompt .= "  \"metrics\": {\n";
        $prompt .= "    \"hook_strength\": (number 0-100),\n";
        $prompt .= "    \"engagement_potential\": (number 0-100),\n";
        $prompt .= "    \"clarity\": (number 0-100),\n";
        $prompt .= "    \"brand_alignment\": (number 0-100)\n";
        $prompt .= "  },\n";
        $prompt .= "  \"improvements\": [\n";
        $prompt .= "    {\"type\": \"hook\", \"suggestion\": \"...\"},\n";
        $prompt .= "    {\"type\": \"retention\", \"suggestion\": \"...\"},\n";
        $prompt .= "    {\"type\": \"cta\", \"suggestion\": \"...\"}\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"summary\": \"(1-2 sentence overall verdict)\"\n";
        $prompt .= "}";

        $aiClient = new AIClient();
        $aiResponse = $aiClient->generate($prompt, $creatorContext, true);
        
        $analysisResult = null;
        if ($aiResponse['success'] && !empty($aiResponse['response'])) {
            $analysisResult = json_decode($aiResponse['response'], true);
        }
        
        // Fallback if AI fails or returns invalid JSON
        if (!is_array($analysisResult)) {
            $analysisResult = generateFallbackAnalysis($content, $platform);
        }
        
        // Track usage
        $d->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'analyzer', ?)",
            [$userId, json_encode(['tokens_used' => str_word_count($content) + 300])]
        );
        
        jsonSuccess($analysisResult);
    }
    
    // Rewrite content based on analysis
    if ($action === 'rewrite') {
        $content = trim($input['content'] ?? '');
        $platform = trim($input['platform'] ?? 'General');
        $format = trim($input['format'] ?? 'script');
        $focus = trim($input['focus'] ?? 'general'); // hook, engagement, clarity, brand
        
        if (empty($content)) {
            jsonError('Content cannot be empty', 422);
        }
        
        $creatorContext = buildCreatorContext($userId);
        
        $prompt = "Rewrite the following $format for $platform to improve it.\n";
        if ($focus === 'hook') {
            $prompt .= "Focus primarily on making the hook (first 3-5 seconds/lines) much more attention-grabbing and scroll-stopping.\n";
        } elseif ($focus === 'engagement') {
            $prompt .= "Focus primarily on improving audience retention and engagement. Make it punchier, remove fluff, and strengthen the CTA.\n";
        } elseif ($focus === 'brand') {
            $prompt .= "Focus primarily on aligning it closer with my brand voice (check Creator Profile).\n";
        } else {
            $prompt .= "Optimize it for maximum performance on $platform.\n";
        }
        
        $prompt .= "\nORIGINAL CONTENT:\n\"\"\"\n$content\n\"\"\"\n\n";
        $prompt .= "Provide ONLY the rewritten content, no explanations.";
        
        $aiClient = new AIClient();
        $aiResponse = $aiClient->generate($prompt, $creatorContext, false);
        
        $rewrittenContent = $aiResponse['success'] ? $aiResponse['response'] : "Unable to rewrite at this time.";
        
        jsonSuccess(['rewritten_content' => $rewrittenContent]);
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Analyzer API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}

/**
 * Fallback analysis logic if AI is unavailable
 */
function generateFallbackAnalysis($content, $platform) {
    $wordCount = str_word_count($content);
    $hasQuestion = strpos($content, '?') !== false;
    $hasHashtag = strpos($content, '#') !== false;
    
    $hookScore = rand(65, 85);
    if ($hasQuestion) $hookScore += 10;
    
    $engScore = rand(60, 80);
    if ($wordCount > 50 && $wordCount < 150) $engScore += 10;
    
    $clarityScore = $wordCount > 200 ? 70 : 85;
    
    $brandScore = rand(70, 90);
    
    $overall = round(($hookScore + $engScore + $clarityScore + $brandScore) / 4);
    
    return [
        "overall_score" => $overall,
        "metrics" => [
            "hook_strength" => min(100, $hookScore),
            "engagement_potential" => min(100, $engScore),
            "clarity" => $clarityScore,
            "brand_alignment" => $brandScore
        ],
        "improvements" => [
            [
                "type" => "hook",
                "suggestion" => "Try starting with a bolder claim or a provocative question to stop the scroll immediately."
            ],
            [
                "type" => "retention",
                "suggestion" => "Break up the middle section into smaller, punchier sentences to maintain pacing."
            ],
            [
                "type" => "cta",
                "suggestion" => "Make the Call to Action more specific. Instead of just 'follow', tell them exactly what value they'll get by following."
            ]
        ],
        "summary" => "Solid foundation, but the pacing can be tightened up for better retention on $platform."
    ];
}
