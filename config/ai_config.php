<?php
/**
 * CreatorAI - AI Service Configuration
 */

require_once __DIR__ . '/config.php';

// AI Provider settings
define('AI_CONFIGURED', !empty(AI_API_KEY));

// Gemini API base URL (called directly from PHP)
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');

// Python AI Service endpoints (legacy, no longer required)
define('AI_ENDPOINTS', [
    'chat'              => PYTHON_AI_URL . '/api/chat',
    'generate_content'  => PYTHON_AI_URL . '/api/generate-content',
    'generate_ideas'    => PYTHON_AI_URL . '/api/generate-ideas',
    'generate_script'   => PYTHON_AI_URL . '/api/generate-script',
    'generate_caption'  => PYTHON_AI_URL . '/api/generate-caption',
    'repurpose'         => PYTHON_AI_URL . '/api/repurpose',
    'analyze_content'   => PYTHON_AI_URL . '/api/analyze-content',
    'analyze_performance' => PYTHON_AI_URL . '/api/analyze-performance',
    'generate_calendar' => PYTHON_AI_URL . '/api/generate-calendar',
    'score_content'     => PYTHON_AI_URL . '/api/score-content',
    'health'            => PYTHON_AI_URL . '/health',
]);

// Default AI generation parameters
define('AI_DEFAULTS', [
    'max_tokens' => 8192,
    'temperature' => 0.7,
    'top_p' => 0.9,
    'response_format' => 'text',
]);

/**
 * Check if AI service is available
 */
function isAIAvailable() {
    return AI_CONFIGURED;
}
