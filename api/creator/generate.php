<?php
/**
 * CreatorAI - Content Generation API
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
    if ($action === 'generate') {
        $contentType = $input['content_type'] ?? 'script'; // script, caption, title, hooks, outline
        $platform = $input['platform'] ?? 'General';
        $topic = trim($input['topic'] ?? '');
        $tone = $input['tone'] ?? 'default';
        $length = $input['length'] ?? 'medium';
        $ideaId = (int)($input['idea_id'] ?? 0);
        
        if (empty($topic) && !$ideaId) {
            jsonError('Please provide a topic or select an idea.', 422);
        }
        
        // If generating from a saved idea, fetch its details
        $ideaContext = "";
        if ($ideaId) {
            $idea = $d->fetch("SELECT * FROM content_ideas WHERE id = ? AND user_id = ?", [$ideaId, $userId]);
            if ($idea) {
                $topic = $idea['title'];
                $ideaContext = "Base this content on the following idea:\nTitle: {$idea['title']}\nDescription: {$idea['description']}\nTags: " . implode(', ', json_decode($idea['keywords'] ?? '[]', true) ?: []) . "\n";
            }
        }
        
        // Build base context
        $context = buildCreatorContext($userId);
        
        // Construct prompt based on content type
        $prompt = "";
        
        if ($contentType === 'script') {
            $prompt = "Write a complete video script for $platform about: \"$topic\".\n";
            $prompt .= $ideaContext;
            $prompt .= "Include visual/B-roll suggestions in brackets [like this].\n";
            $prompt .= "Structure it clearly with a Hook, Intro, Body points, and a strong Call to Action.\n";
            
            if ($length === 'short') $prompt .= "Keep it short (under 60 seconds of speaking).\n";
            else if ($length === 'detailed') $prompt .= "Make it detailed and comprehensive (3-5 minutes of speaking).\n";
        } 
        else if ($contentType === 'caption') {
            $prompt = "Write an engaging social media caption for $platform about: \"$topic\".\n";
            $prompt .= $ideaContext;
            $prompt .= "Include a strong hook on the first line.\n";
            $prompt .= "Include relevant formatting and spacing for $platform.\n";
            $prompt .= "Include a clear Call to Action at the end.\n";
            $prompt .= "Provide a curated list of 15-20 relevant hashtags at the bottom.\n";
            
            if ($length === 'short') $prompt .= "Keep the caption brief and punchy.\n";
            else if ($length === 'detailed') $prompt .= "Write a detailed micro-blog style caption.\n";
        }
        else if ($contentType === 'title') {
            $prompt = "Generate 10 highly clickable, optimized titles/headlines for $platform about: \"$topic\".\n";
            $prompt .= $ideaContext;
            $prompt .= "Provide a mix of styles (e.g., How-to, Listicle, Question, Curiosity gap, Direct).\n";
            $prompt .= "Do not write the script, ONLY provide the list of 10 titles.\n";
        }
        else if ($contentType === 'hooks') {
            $prompt = "Generate 7 powerful, attention-grabbing hooks (first 3 seconds) for $platform about: \"$topic\".\n";
            $prompt .= $ideaContext;
            $prompt .= "Provide a mix of hook styles (e.g., Bold claim, Negative hook, Question, Visual hook description, Story start).\n";
            $prompt .= "Explain briefly *why* each hook works.\n";
        }
        else if ($contentType === 'outline') {
            $prompt = "Create a detailed structural outline for content on $platform about: \"$topic\".\n";
            $prompt .= $ideaContext;
            $prompt .= "Break it down into specific sections with estimated time/word count per section.\n";
            $prompt .= "List bullet points of what to cover in each section.\n";
            $prompt .= "Do not write the full script, just a comprehensive outline.\n";
        }
        
        if ($tone !== 'default') {
            $prompt .= "\nTone adjustment: Make this specifically sound $tone, overriding default settings if necessary.\n";
        }
        
        $aiClient = new AIClient();
        $aiResponse = $aiClient->generate($prompt, $context, false);
        
        if ($aiResponse['success'] && !empty($aiResponse['response'])) {
            $content = $aiResponse['response'];
        } else {
            // Fallback content if AI fails
            $content = "### ⚠️ AI Generation Unavailable\n\nWe couldn't generate the content right now. Here is a generic template you can use:\n\n";
            $content .= "**Platform:** $platform\n**Topic:** $topic\n\n";
            if ($contentType === 'script') {
                $content .= "**[Hook]** Are you struggling with $topic? Here's the solution.\n\n**[Intro]** Today I'm showing you exactly how I handle this.\n\n**[Body]**\n- Point 1\n- Point 2\n\n**[CTA]** Follow for more tips!";
            } else {
                $content .= "Please try generating again in a few moments.";
            }
        }
        
        // Track usage
        $d->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'generator', ?)",
            [$userId, json_encode(['tokens_used' => str_word_count($content) + 200])]
        );
        
        jsonSuccess([
            'content' => $content,
            'topic' => $topic
        ]);
    }
    
    // Save generated content to library
    if ($action === 'save') {
        $title = trim($input['title'] ?? 'Untitled Content');
        $content = $input['content'] ?? '';
        $contentType = $input['content_type'] ?? 'text';
        $platform = $input['platform'] ?? 'General';
        
        if (empty($content)) {
            jsonError('Content cannot be empty', 422);
        }
        
        $contentId = $d->insert(
            "INSERT INTO saved_content (user_id, title, content_type, platform, content) VALUES (?, ?, ?, ?, ?)",
            [$userId, $title, $contentType, $platform, $content]
        );
        
        jsonSuccess(['id' => $contentId], 'Content saved to your library!');
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Generator API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}
