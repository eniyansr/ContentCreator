<?php
/**
 * CreatorAI - Content Repurpose API
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
    if ($action === 'repurpose') {
        $content = trim($input['content'] ?? '');
        $platforms = $input['platforms'] ?? []; // Array of platforms to repurpose to
        
        if (empty($content)) {
            jsonError('Content cannot be empty', 422);
        }
        
        if (empty($platforms) || !is_array($platforms)) {
            jsonError('Please select at least one target platform.', 422);
        }
        
        if (count($platforms) > 5) {
            jsonError('Maximum of 5 platforms can be selected at once.', 422);
        }

        // Build context
        $creatorContext = buildCreatorContext($userId);
        
        $results = [];
        $aiClient = new AIClient();
        
        // Optimize: Send one batch prompt instead of a loop, as it's faster and uses fewer tokens
        $platformListStr = implode(", ", $platforms);
        $prompt = "You are an expert Social Media Repurposer.\n";
        $prompt .= "Take the following SOURCE CONTENT and repurpose it specifically for these platforms: $platformListStr.\n\n";
        $prompt .= "SOURCE CONTENT:\n\"\"\"\n$content\n\"\"\"\n\n";
        
        $prompt .= "Follow these platform-specific guidelines:\n";
        $prompt .= "- Twitter/X: Write a compelling thread (3-5 tweets) ending with a CTA.\n";
        $prompt .= "- LinkedIn: Professional tone, clear hook, spacious formatting, thought-provoking question at the end.\n";
        $prompt .= "- Instagram: Engaging caption with emojis, 15-20 relevant hashtags at the bottom.\n";
        $prompt .= "- TikTok/Reels: Write a short, punchy 30-second video script with visual cues in brackets.\n";
        $prompt .= "- YouTube Community Tab: Casual text post with a poll question.\n";
        $prompt .= "- Newsletter/Blog: Expand into a structured short article.\n\n";
        
        $prompt .= "Format your response as a valid JSON object where the keys are the platform names and the values are the rewritten content strings. Do not include markdown blocks or backticks around the JSON:\n";
        $prompt .= "{\n";
        foreach ($platforms as $p) {
            $prompt .= "  \"$p\": \"<repurposed content here>\",\n";
        }
        $prompt .= "}";

        $aiResponse = $aiClient->generate($prompt, $creatorContext, true);
        
        $parsedResults = null;
        if ($aiResponse['success'] && !empty($aiResponse['response'])) {
            $parsedResults = json_decode($aiResponse['response'], true);
        }
        
        if (is_array($parsedResults)) {
            $results = $parsedResults;
        } else {
            // Fallback
            foreach ($platforms as $p) {
                $results[$p] = generateFallbackRepurpose($content, $p);
            }
        }
        
        // Track usage
        $d->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'repurposer', ?)",
            [$userId, json_encode(['tokens_used' => (str_word_count($content) + 500) * count($platforms)])]
        );
        
        jsonSuccess(['results' => $results]);
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Repurpose API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}

/**
 * Fallback repurposing logic if AI is unavailable
 */
function generateFallbackRepurpose($content, $platform) {
    $shortTopic = mb_substr(strip_tags($content), 0, 50) . '...';
    
    if (strpos(strtolower($platform), 'twitter') !== false || strpos(strtolower($platform), 'x') !== false) {
        return "1/ Here's what nobody tells you about this topic 👇🧵\n\n[Your main point derived from: $shortTopic]\n\n2/ The most important thing to remember is consistency.\n\n3/ What are your thoughts? Drop them below!";
    }
    
    if (strpos(strtolower($platform), 'linkedin') !== false) {
        return "I recently learned a hard lesson about this.\n\n$shortTopic\n\nHere are 3 takeaways:\n1. Keep it simple.\n2. Stay consistent.\n3. Focus on value.\n\nDo you agree? Let me know in the comments.\n\n#ProfessionalGrowth #Strategy";
    }
    
    if (strpos(strtolower($platform), 'instagram') !== false) {
        return "Are you struggling with this? 👇\n\n$shortTopic\n\nSave this post to remember these tips!\n\nLet me know your biggest challenge in the comments ⬇️\n\n.\n.\n.\n#CreatorLife #SocialMediaTips #Growth";
    }
    
    if (strpos(strtolower($platform), 'tiktok') !== false) {
        return "[Hook] Stop scrolling if you care about this!\n\n[Body] $shortTopic\n\n[CTA] Hit the plus for more tips like this!";
    }
    
    return "Adapted content for $platform:\n\n$shortTopic";
}
