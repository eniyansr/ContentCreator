<?php
/**
 * CreatorAI - Chat API
 * Handles chat sessions and messages
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/ai_client.php';

header('Content-Type: application/json');
requireAuth();

$userId = getCurrentUserId();
$method = getMethod();

try {
    $d = db();
    
    // ==================== GET ROUTES ====================
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        // List chat sessions
        if ($action === 'sessions') {
            $sessions = $d->fetchAll(
                "SELECT id, title, last_message_at, created_at FROM chat_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_message_at DESC LIMIT 50",
                [$userId]
            );
            jsonSuccess($sessions);
        }
        
        // Get messages for a session
        if ($action === 'messages') {
            $sessionId = (int)($_GET['session_id'] ?? 0);
            if (!$sessionId) jsonError('Invalid session', 400);
            
            // Verify ownership
            $session = $d->fetch("SELECT * FROM chat_sessions WHERE id = ? AND user_id = ?", [$sessionId, $userId]);
            if (!$session) jsonError('Session not found', 404);
            
            $messages = $d->fetchAll(
                "SELECT id, role, message AS content, created_at FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC LIMIT 200",
                [$sessionId]
            );
            jsonSuccess(['session' => $session, 'messages' => $messages]);
        }
        
        jsonError('Invalid action', 400);
    }
    
    // ==================== POST ROUTES ====================
    if ($method !== 'POST') jsonError('Method not allowed', 405);
    if (!validateCsrfToken()) jsonError('Invalid security token.', 403);
    
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    
    // Create new chat session
    if ($action === 'create_session') {
        $title = cleanInput($input['title'] ?? 'New Conversation');
        $sessionId = $d->insert(
            "INSERT INTO chat_sessions (user_id, title) VALUES (?, ?)",
            [$userId, $title]
        );
        jsonSuccess(['session_id' => $sessionId, 'title' => $title]);
    }
    
    // Send message
    if ($action === 'send_message') {
        $sessionId = (int)($input['session_id'] ?? 0);
        $message = trim($input['message'] ?? '');
        
        if (empty($message)) jsonError('Message cannot be empty.', 422);
        if (strlen($message) > 10000) jsonError('Message too long.', 422);
        
        // Create session if needed
        if (!$sessionId) {
            $title = mb_substr($message, 0, 60);
            $sessionId = $d->insert("INSERT INTO chat_sessions (user_id, title) VALUES (?, ?)", [$userId, $title]);
        }
        
        // Verify ownership
        $session = $d->fetch("SELECT * FROM chat_sessions WHERE id = ? AND user_id = ?", [$sessionId, $userId]);
        if (!$session) jsonError('Session not found.', 404);
        
        // Save user message
        $d->insert(
            "INSERT INTO chat_messages (session_id, user_id, role, message) VALUES (?, ?, 'user', ?)",
            [$sessionId, $userId, $message]
        );
        
        // Update session timestamp
        $d->update("UPDATE chat_sessions SET last_message_at = CURRENT_TIMESTAMP, message_count = message_count + 1 WHERE id = ?", [$sessionId]);
        
        // Build AI context from creator profile
        $context = buildCreatorContext($userId);
        
        // Get conversation history (last 20 messages)
        $history = $d->fetchAll(
            "SELECT role, message AS content FROM chat_messages WHERE session_id = ? ORDER BY created_at DESC LIMIT 20",
            [$sessionId]
        );
        $history = array_reverse($history);
        
        // Call AI service
        $aiClient = new AIClient();
        $aiResponse = $aiClient->chat($message, $context, $history);
        
        if ($aiResponse['success'] && !empty($aiResponse['response'])) {
            $responseText = $aiResponse['response'];
        } else {
            // Fallback: Generate response using built-in logic
            $responseText = generateFallbackResponse($message, $context);
        }
        
        // Save AI response
        $d->insert(
            "INSERT INTO chat_messages (session_id, user_id, role, message) VALUES (?, ?, 'assistant', ?)",
            [$sessionId, $userId, $responseText]
        );
        $d->update("UPDATE chat_sessions SET last_message_at = CURRENT_TIMESTAMP, message_count = message_count + 1 WHERE id = ?", [$sessionId]);
        
        // Track usage
        $tokenCount = str_word_count($message) + str_word_count($responseText);
        $d->insert(
            "INSERT INTO ai_usage_logs (user_id, action, details) VALUES (?, 'chat', ?)",
            [$userId, json_encode(['session_id' => $sessionId, 'tokens_used' => $tokenCount])]
        );
        
        jsonSuccess([
            'session_id' => $sessionId,
            'response' => $responseText,
            'title' => $session['title'],
        ]);
    }
    
    // Delete session
    if ($action === 'delete_session') {
        $sessionId = (int)($input['session_id'] ?? 0);
        $d->update("UPDATE chat_sessions SET is_active = 0 WHERE id = ? AND user_id = ?", [$sessionId, $userId]);
        jsonSuccess([], 'Conversation deleted.');
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Chat API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}

