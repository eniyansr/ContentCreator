<?php
/**
 * CreatorAI - Content Ideas API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_client.php';
require_once __DIR__ . '/../../includes/ai_client.php';

header('Content-Type: application/json');
requireAuth();

$userId = getCurrentUserId();
$method = getMethod();
$d = db();

try {
    // ==================== GET ROUTES ====================
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        // List saved/generated ideas
        if ($action === 'list') {
            $status = $_GET['status'] ?? '';
            $query = "SELECT * FROM content_ideas WHERE user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT 50";
            
            $ideas = $d->fetchAll($query, $params);
            
            // Decode JSON fields
            foreach ($ideas as &$idea) {
                $idea['tags'] = json_decode($idea['keywords'] ?? '[]', true) ?: [];
            }
            
            jsonSuccess($ideas);
        }
        
        jsonError('Invalid action', 400);
    }
    
    // ==================== POST ROUTES ====================
    if ($method !== 'POST') jsonError('Method not allowed', 405);
    if (!validateCsrfToken()) jsonError('Invalid security token.', 403);
    
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    
    // Generate ideas via AI
    if ($action === 'generate') {
        $topic = trim($input['topic'] ?? '');
        $platform = trim($input['platform'] ?? 'General');
        $format = trim($input['format'] ?? 'Any');
        $count = (int)($input['count'] ?? 4);
        
        if ($count < 1 || $count > 10) $count = 4;
        
        $context = buildCreatorContext($userId);
        $aiClient = new AIClient();
        
        $prompt = "Generate $count content ideas for a $platform $format.\n";
        if ($topic) {
            $prompt .= "The specific topic or theme should be: \"$topic\"\n";
        } else {
            $prompt .= "Generate ideas based entirely on my creator profile and niche.\n";
        }
        $prompt .= "\nProvide the output as a valid JSON array where each object has the following keys:\n";
        $prompt .= "- 'title' (string: catchy title/hook)\n";
        $prompt .= "- 'description' (string: brief description of the content)\n";
        $prompt .= "- 'viral_score' (number: 1-100 estimated engagement potential)\n";
        $prompt .= "- 'tags' (array of strings: 2-3 relevant topic tags)\n";
        
        $aiResponse = $aiClient->generate($prompt, $context, true);
        
        if ($aiResponse['success'] && !empty($aiResponse['response'])) {
            $generatedIdeas = json_decode($aiResponse['response'], true);
            if (!is_array($generatedIdeas)) {
                $generatedIdeas = generateFallbackIdeas($topic, $platform, $format, $count);
            }
        } else {
            $generatedIdeas = generateFallbackIdeas($topic, $platform, $format, $count);
        }
        
        // Track usage
        $d->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'ideas', ?)",
            [$userId, json_encode(['tokens_used' => 500])]
        );
        
        jsonSuccess(['ideas' => $generatedIdeas]);
    }
    
    // Save an idea to the database
    if ($action === 'save') {
        $idea = $input['idea'] ?? [];
        if (empty($idea['title']) || empty($idea['description'])) {
            jsonError('Invalid idea data', 422);
        }
        
        $ideaId = $d->insert(
            "INSERT INTO content_ideas (user_id, title, description, platform, content_type, keywords, status, ai_generated) VALUES (?, ?, ?, ?, ?, ?, 'new', 1)",
            [
                $userId,
                $idea['title'],
                $idea['description'],
                $idea['platform'] ?? 'General',
                $idea['format'] ?? 'Any',
                json_encode($idea['tags'] ?? [])
            ]
        );
        
        jsonSuccess(['id' => $ideaId], 'Idea saved successfully.');
    }
    
    // Update idea status (e.g., to 'planned' or 'completed')
    if ($action === 'update_status') {
        $ideaId = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? 'idea';
        
        $d->update("UPDATE content_ideas SET status = ? WHERE id = ? AND user_id = ?", [$status, $ideaId, $userId]);
        jsonSuccess([], 'Status updated.');
    }
    
    // Delete idea
    if ($action === 'delete') {
        $ideaId = (int)($input['id'] ?? 0);
        $d->delete("DELETE FROM content_ideas WHERE id = ? AND user_id = ?", [$ideaId, $userId]);
        jsonSuccess([], 'Idea deleted.');
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Ideas API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}

/**
 * Fallback idea generation
 */
function generateFallbackIdeas($topic, $platform, $format, $count) {
    $topicStr = $topic ? $topic : "your niche";
    $ideas = [
        [
            "title" => "The Biggest Mistake in $topicStr",
            "description" => "A controversial take on what everyone gets wrong about $topicStr, followed by the correct approach.",
            "viral_score" => 92,
            "tags" => ["Controversial", "Educational"]
        ],
        [
            "title" => "How I mastered $topicStr in 30 Days",
            "description" => "A personal story and step-by-step framework documenting your journey and results.",
            "viral_score" => 85,
            "tags" => ["Storytime", "Framework"]
        ],
        [
            "title" => "5 Secret Tools for $topicStr",
            "description" => "A rapid-fire list of lesser-known tools, apps, or techniques you use.",
            "viral_score" => 78,
            "tags" => ["Tools", "Listicle"]
        ],
        [
            "title" => "Behind the Scenes: $topicStr",
            "description" => "A raw, unedited look at your process and the reality versus expectation.",
            "viral_score" => 88,
            "tags" => ["BTS", "Authentic"]
        ]
    ];
    
    // Add platform to generated ideas
    foreach ($ideas as &$idea) {
        $idea['platform'] = $platform;
        $idea['format'] = $format;
    }
    
    return array_slice($ideas, 0, $count);
}
