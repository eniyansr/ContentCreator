<?php
/**
 * CreatorAI - Utility Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escape output for HTML
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Success JSON response
 */
function jsonSuccess($data = [], $message = 'Success') {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Error JSON response
 */
function jsonError($message = 'An error occurred', $statusCode = 400, $errors = []) {
    $response = ['success' => false, 'message' => $message];
    if (!empty($errors)) $response['errors'] = $errors;
    jsonResponse($response, $statusCode);
}

/**
 * Get POST JSON data
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return $data ?: [];
}

/**
 * Get request method
 */
function getMethod() {
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($date, $format = 'M d, Y h:i A') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Time ago helper
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Truncate text
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate random string
 */
function randomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get client IP address
 */
function getClientIP() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var(trim($ip), FILTER_VALIDATE_IP)) {
                return trim($ip);
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Log application error
 */
function logError($message, $context = []) {
    $logFile = LOG_PATH . 'error_' . date('Y-m-d') . '.log';
    $entry = date('Y-m-d H:i:s') . " | " . $message;
    if (!empty($context)) {
        $entry .= " | " . json_encode($context);
    }
    $entry .= PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log AI request
 */
function logAIRequest($userId, $requestType, $status = 'success', $tokensUsed = 0, $responseTime = 0, $error = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->insert(
            "INSERT INTO ai_requests (user_id, request_type, ai_provider, ai_model, total_tokens, response_time_ms, status, error_message) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$userId, $requestType, AI_PROVIDER, AI_MODEL, $tokensUsed, $responseTime, $status, $error]
        );
    } catch (Exception $e) {
        logError("Failed to log AI request", ['error' => $e->getMessage()]);
    }
}

/**
 * Log usage activity
 */
function logActivity($userId, $action, $details = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$userId, $action, $details, getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    } catch (Exception $e) {
        logError("Failed to log activity", ['error' => $e->getMessage()]);
    }
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->insert(
            "INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
            [$userId, $title, $message, $type, $link]
        );
    } catch (Exception $e) {
        logError("Failed to create notification", ['error' => $e->getMessage()]);
    }
}

/**
 * Check rate limit for AI requests
 */
function checkAIRateLimit($userId) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $count = db()->fetchColumn(
            "SELECT COUNT(*) FROM ai_requests WHERE user_id = ? AND created_at > datetime('now', '-1 hour')",
            [$userId]
        );
        return $count < MAX_AI_REQUESTS_PER_HOUR;
    } catch (Exception $e) {
        return true; // Allow on error
    }
}

/**
 * Check login rate limit
 */
function checkLoginRateLimit($email) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $user = db()->fetch("SELECT login_attempts, locked_until FROM users WHERE email = ?", [$email]);
        if (!$user) return true;
        
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return false;
        }
        
        return $user['login_attempts'] < MAX_LOGIN_ATTEMPTS;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * Increment login attempts
 */
function incrementLoginAttempts($email) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $user = db()->fetch("SELECT login_attempts FROM users WHERE email = ?", [$email]);
        if ($user) {
            $attempts = $user['login_attempts'] + 1;
            $lockedUntil = null;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            }
            db()->update("UPDATE users SET login_attempts = ?, locked_until = ? WHERE email = ?", [$attempts, $lockedUntil, $email]);
        }
    } catch (Exception $e) {
        logError("Failed to increment login attempts", ['error' => $e->getMessage()]);
    }
}

/**
 * Get greeting based on time
 */
function getGreeting() {
    $hour = (int) date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 17) return 'Good afternoon';
    return 'Good evening';
}

/**
 * Calculate profile completion percentage
 */
function getProfileCompletion($userId) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $sections = 0;
        $completed = 0;
        
        // Basic profile
        $sections++;
        $profile = db()->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]);
        if ($profile && !empty($profile['creator_name'])) $completed++;
        
        // Platforms
        $sections++;
        $platforms = db()->fetchColumn("SELECT COUNT(*) FROM creator_platforms WHERE user_id = ?", [$userId]);
        if ($platforms > 0) $completed++;
        
        // Audience
        $sections++;
        $audience = db()->fetch("SELECT * FROM creator_audiences WHERE user_id = ?", [$userId]);
        if ($audience && !empty($audience['age_groups'])) $completed++;
        
        // Brand
        $sections++;
        $brand = db()->fetch("SELECT * FROM creator_brand_profiles WHERE user_id = ?", [$userId]);
        if ($brand && !empty($brand['tone'])) $completed++;
        
        // Goals
        $sections++;
        $goals = db()->fetch("SELECT * FROM creator_goals WHERE user_id = ?", [$userId]);
        if ($goals && !empty($goals['primary_goals'])) $completed++;
        
        // Preferences
        $sections++;
        $prefs = db()->fetch("SELECT * FROM creator_preferences WHERE user_id = ?", [$userId]);
        if ($prefs) $completed++;
        
        return $sections > 0 ? round(($completed / $sections) * 100) : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get full creator context for AI
 */
function getCreatorContext($userId) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $d = db();
        
        $context = [];
        
        // User info
        $user = $d->fetch("SELECT full_name, username, country, preferred_language FROM users WHERE id = ?", [$userId]);
        if ($user) $context['user'] = $user;
        
        // Profile
        $profile = $d->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]);
        if ($profile) {
            unset($profile['id'], $profile['user_id'], $profile['created_at'], $profile['updated_at']);
            $context['profile'] = $profile;
        }
        
        // Platforms
        $platforms = $d->fetchAll("SELECT platform_name, followers, average_views, subscribers, posting_frequency, main_content_format FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
        if ($platforms) $context['platforms'] = $platforms;
        
        // Audience
        $audience = $d->fetch("SELECT * FROM creator_audiences WHERE user_id = ?", [$userId]);
        if ($audience) {
            unset($audience['id'], $audience['user_id'], $audience['created_at'], $audience['updated_at']);
            $context['audience'] = $audience;
        }
        
        // Brand voice
        $brand = $d->fetch("SELECT * FROM creator_brand_profiles WHERE user_id = ?", [$userId]);
        if ($brand) {
            unset($brand['id'], $brand['user_id'], $brand['created_at'], $brand['updated_at']);
            $context['brand_voice'] = $brand;
        }
        
        // Goals
        $goals = $d->fetch("SELECT * FROM creator_goals WHERE user_id = ?", [$userId]);
        if ($goals) {
            unset($goals['id'], $goals['user_id'], $goals['created_at'], $goals['updated_at']);
            $context['goals'] = $goals;
        }
        
        // Preferences
        $prefs = $d->fetch("SELECT * FROM creator_preferences WHERE user_id = ?", [$userId]);
        if ($prefs) {
            unset($prefs['id'], $prefs['user_id'], $prefs['created_at'], $prefs['updated_at']);
            $context['preferences'] = $prefs;
        }
        
        // Active memories
        $memories = $d->fetchAll("SELECT memory_type, memory_key, memory_value FROM creator_memory WHERE user_id = ? AND is_active = 1 ORDER BY updated_at DESC LIMIT 20", [$userId]);
        if ($memories) $context['memories'] = $memories;
        
        return $context;
    } catch (Exception $e) {
        logError("Failed to get creator context", ['user_id' => $userId, 'error' => $e->getMessage()]);
        return [];
    }
}
/**
 * Build creator context for AI
 */
function buildCreatorContext($userId) {
    $d = db();
    $profile = $d->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]);
    $audience = $d->fetch("SELECT * FROM creator_audiences WHERE user_id = ?", [$userId]);
    $brand = $d->fetch("SELECT * FROM creator_brand_profiles WHERE user_id = ?", [$userId]);
    $goals = $d->fetch("SELECT * FROM creator_goals WHERE user_id = ?", [$userId]);
    $prefs = $d->fetch("SELECT * FROM creator_preferences WHERE user_id = ?", [$userId]);
    $platforms = $d->fetchAll("SELECT platform_name, followers, average_views, posting_frequency FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
    
    $context = "You are CreatorAI, a personalized AI content creation assistant.\n\n";
    $context .= "=== CREATOR PROFILE ===\n";
    
    if ($profile) {
        $context .= "Creator Name: " . ($profile['creator_name'] ?: 'Not set') . "\n";
        $context .= "Niche: " . ($profile['primary_niche'] ?: 'Not set') . "\n";
        $context .= "Creator Types: " . ($profile['creator_types'] ?: '[]') . "\n";
        $context .= "Content Style: " . ($profile['content_style'] ?: '[]') . "\n";
        $context .= "Experience: " . ($profile['experience_level'] ?: 'Not set') . "\n";
        $context .= "Bio: " . ($profile['bio'] ?: '') . "\n";
    }
    
    if (!empty($platforms)) {
        $context .= "\n=== PLATFORMS ===\n";
        foreach ($platforms as $p) {
            $context .= "- {$p['platform_name']}: {$p['followers']} followers, {$p['average_views']} avg views, posts {$p['posting_frequency']}\n";
        }
    }
    
    if ($audience) {
        $context .= "\n=== TARGET AUDIENCE ===\n";
        $context .= "Age Groups: " . ($audience['age_groups'] ?: '[]') . "\n";
        $context .= "Language: " . ($audience['primary_language'] ?: '') . "\n";
        $context .= "Knowledge Level: " . ($audience['knowledge_level'] ?: 'mixed') . "\n";
        $context .= "Problems: " . ($audience['problems_to_solve'] ?: '') . "\n";
    }
    
    if ($brand) {
        $context .= "\n=== BRAND VOICE ===\n";
        $context .= "Tone: " . ($brand['tone'] ?: '[]') . "\n";
        $context .= "Speaking Style: " . ($brand['speaking_style'] ?: '') . "\n";
        $context .= "Common Phrases: " . ($brand['common_phrases'] ?: '') . "\n";
        $context .= "Avoid: " . ($brand['avoided_phrases'] ?: '') . "\n";
        $context .= "Catchphrase: " . ($brand['catchphrase'] ?: '') . "\n";
    }
    
    if ($goals) {
        $context .= "\n=== GOALS ===\n";
        $context .= "Primary Goals: " . ($goals['primary_goals'] ?: '[]') . "\n";
        $context .= "Challenges: " . ($goals['content_challenges'] ?: '[]') . "\n";
        $context .= "Publishing Frequency: " . ($goals['publishing_frequency'] ?: '') . "\n";
    }
    
    if ($prefs) {
        $context .= "\n=== AI PREFERENCES ===\n";
        $context .= "Creative Freedom: " . ($prefs['creative_freedom'] ?: 'balanced') . "\n";
        $context .= "Response Length: " . ($prefs['response_length'] ?: 'medium') . "\n";
        $context .= "Script Structure: " . ($prefs['script_structure'] ?: 'hook_body_cta') . "\n";
    }
    
    $context .= "\n=== INSTRUCTIONS ===\n";
    $context .= "Always personalize responses based on the creator's profile above.\n";
    $context .= "Use their preferred tone and brand voice.\n";
    $context .= "Reference their platforms, niche, and audience when relevant.\n";
    $context .= "Provide actionable, specific advice — not generic tips.\n";
    
    return $context;
}

/**
 * Fallback response when AI service is unavailable
 */
function generateFallbackResponse($message, $context) {
    // Extract key profile info from context
    $niche = 'your niche';
    if (preg_match('/Niche: (.+)/i', $context, $m)) $niche = trim($m[1]);
    
    $name = 'Creator';
    if (preg_match('/Creator Name: (.+)/i', $context, $m)) $name = trim($m[1]);
    
    $lower = strtolower($message);
    
    // Pattern-matched intelligent responses
    if (strpos($lower, 'idea') !== false || strpos($lower, 'topic') !== false) {
        return "Hey {$name}! Here are some content ideas for your **{$niche}** niche:\n\n" .
            "1. **\"The Biggest Mistakes in {$niche}\"** — Controversy drives engagement. Share your hot takes.\n" .
            "2. **\"My Honest Journey in {$niche}\"** — Storytelling builds deep audience connection.\n" .
            "3. **\"5 Things I Wish I Knew About {$niche}\"** — List content performs well across platforms.\n" .
            "4. **\"{$niche} in 2024: What's Changed\"** — Evergreen content with a timely angle.\n" .
            "5. **\"Beginner's Guide to {$niche}\"** — Capture new audience members with educational content.\n\n" .
            "💡 **Pro Tip:** Mix educational (40%), entertaining (30%), and personal (30%) content for the best audience growth.\n\n" .
            "Would you like me to create a full script for any of these?";
    }
    
    if (strpos($lower, 'script') !== false || strpos($lower, 'write') !== false) {
        return "I'd love to help you write a script, {$name}! Here's a template based on your preferred Hook → Body → CTA structure:\n\n" .
            "### 🎬 Script Template for {$niche}\n\n" .
            "**HOOK (0-3 seconds):**\n\"Stop scrolling if you care about {$niche}...\"\n\n" .
            "**INTRO (3-15 seconds):**\nBrief overview of what you'll cover and why it matters to your audience.\n\n" .
            "**BODY (Main Content):**\n- Point 1: [Your main insight]\n- Point 2: [Supporting evidence or story]\n- Point 3: [Practical application]\n\n" .
            "**CTA:**\n\"If this helped you, make sure to [subscribe/follow/share] for more {$niche} content!\"\n\n" .
            "📝 Tell me your specific topic and target platform, and I'll create a complete custom script!";
    }
    
    if (strpos($lower, 'caption') !== false || strpos($lower, 'instagram') !== false || strpos($lower, 'post') !== false) {
        return "Here's a caption framework for your {$niche} content, {$name}:\n\n" .
            "### 📱 Caption Template\n\n" .
            "**Hook Line:** [Something unexpected or attention-grabbing]\n\n" .
            "**Value:** [2-3 sentences delivering your main point]\n\n" .
            "**Story/Example:** [Brief personal anecdote or case study]\n\n" .
            "**CTA:** [Ask a question or tell them what to do next]\n\n" .
            "**Hashtags:**\n#" . str_replace(' ', '', $niche) . " #ContentCreator #CreatorLife #" . str_replace(' ', '', $niche) . "Tips\n\n" .
            "Want me to write a specific caption? Tell me the topic!";
    }
    
    if (strpos($lower, 'hashtag') !== false || strpos($lower, 'tag') !== false) {
        return "Here are hashtag strategies for your {$niche} niche, {$name}:\n\n" .
            "### 🏷️ Hashtag Strategy\n\n" .
            "**Tier 1 — Niche-Specific (High Relevance):**\n" .
            "#" . str_replace(' ', '', $niche) . " #" . str_replace(' ', '', $niche) . "Tips #" . str_replace(' ', '', $niche) . "Community\n\n" .
            "**Tier 2 — Broader Category:**\n#ContentCreator #DigitalCreator #CreatorEconomy\n\n" .
            "**Tier 3 — Discovery:**\n#LearnOn[Platform] #Viral #ForYouPage #Explore\n\n" .
            "📊 **Best Practices:**\n- Instagram: 20-25 hashtags\n- TikTok: 3-5 targeted hashtags\n- YouTube: 5-10 tags in description\n- LinkedIn: 3-5 professional hashtags";
    }
    
    if (strpos($lower, 'hello') !== false || strpos($lower, 'hi') !== false || strpos($lower, 'hey') !== false) {
        return "Hey {$name}! 👋 Welcome to your personalized AI assistant.\n\n" .
            "I'm here to help you with your **{$niche}** content. Here are some things I can do for you:\n\n" .
            "💡 **Generate content ideas** — platform-specific, trending topics\n" .
            "✍️ **Write scripts & captions** — in your brand voice\n" .
            "🏷️ **Create hashtag strategies** — optimized for reach\n" .
            "📊 **Analyze your content** — engagement predictions, improvements\n" .
            "🔄 **Repurpose content** — transform for different platforms\n" .
            "📅 **Plan your calendar** — consistent posting schedule\n\n" .
            "What would you like to work on today?";
    }
    
    // Default response
    return "Great question, {$name}! As your AI assistant focused on **{$niche}** content, I'd love to help with that.\n\n" .
        "Here's my take: Based on your creator profile, I'd suggest approaching this from the perspective of your target audience. " .
        "Consider what value this adds to their experience.\n\n" .
        "Would you like me to:\n" .
        "1. 💡 Generate specific content ideas around this topic?\n" .
        "2. ✍️ Write a script or caption based on this?\n" .
        "3. 📊 Analyze how this could perform on your platforms?\n" .
        "4. 🔄 Show you how to repurpose this across platforms?\n\n" .
        "Just let me know how to help!";
}
