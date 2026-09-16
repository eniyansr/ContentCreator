<?php
/**
 * CreatorAI - AI Client (Direct Gemini API Integration)
 * 
 * Calls the Google Gemini REST API directly from PHP via cURL.
 * No Python AI service is required.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/ai_config.php';

class AIClient {
    private $apiKey;
    private $model;
    private $baseUrl;
    private $timeout;
    
    public function __construct() {
        $this->apiKey  = GEMINI_API_KEY;
        $this->model   = AI_MODEL;
        $this->baseUrl = GEMINI_API_URL;
        $this->timeout = 120; // seconds
    }
    
    /**
     * Send a request to the Gemini generateContent endpoint
     *
     * @param string $systemPrompt  System instruction / creator context
     * @param array  $contents      Array of content parts (Gemini format)
     * @param bool   $jsonMode      If true, request JSON output from Gemini
     * @return array  ['success' => bool, 'response' => string, ...]
     */
    private function callGemini($systemPrompt, $contents, $jsonMode = false) {
        if (!AI_CONFIGURED) {
            return [
                'success' => false,
                'error'   => 'AI service is not configured. Please add your Gemini API key to the .env file.',
                'ai_not_configured' => true,
            ];
        }
        
        // Build the request URL
        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;
        
        // Build request body
        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature'  => AI_DEFAULTS['temperature'],
                'topP'         => AI_DEFAULTS['top_p'],
                'maxOutputTokens' => AI_DEFAULTS['max_tokens'],
            ],
        ];
        
        // Add system instruction
        if (!empty($systemPrompt)) {
            $body['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ];
        }
        
        // Enable JSON response mode
        if ($jsonMode) {
            $body['generationConfig']['responseMimeType'] = 'application/json';
        }
        
        // cURL request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        
        $startTime = microtime(true);
        $response  = curl_exec($ch);
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Handle connection errors
        if ($response === false) {
            logError("Gemini API connection failed", [
                'error' => $curlError,
            ]);
            return [
                'success'          => false,
                'error'            => 'Unable to connect to Gemini API: ' . $curlError,
                'ai_unavailable'   => true,
                'response_time_ms' => $responseTime,
            ];
        }
        
        // Parse JSON response
        $result = json_decode($response, true);
        
        // Handle HTTP errors
        if ($httpCode !== 200) {
            $errorMsg = 'Gemini API error';
            if (isset($result['error']['message'])) {
                $errorMsg = $result['error']['message'];
            } elseif (isset($result['error'])) {
                $errorMsg = is_string($result['error']) ? $result['error'] : json_encode($result['error']);
            }
            
            logError("Gemini API error", [
                'http_code' => $httpCode,
                'error'     => $errorMsg,
            ]);
            
            return [
                'success'          => false,
                'error'            => $errorMsg,
                'http_code'        => $httpCode,
                'response_time_ms' => $responseTime,
            ];
        }
        
        // Extract the text from Gemini response
        // Structure: candidates[0].content.parts[0].text
        $text = '';
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $result['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (empty($text)) {
            // Check for blocked content
            $blockReason = $result['candidates'][0]['finishReason'] ?? '';
            if ($blockReason === 'SAFETY') {
                return [
                    'success'          => false,
                    'error'            => 'The response was blocked by safety filters. Please rephrase your message.',
                    'response_time_ms' => $responseTime,
                ];
            }
            
            return [
                'success'          => false,
                'error'            => 'Empty response from Gemini API.',
                'response_time_ms' => $responseTime,
            ];
        }
        
        return [
            'success'          => true,
            'response'         => $text,
            'response_time_ms' => $responseTime,
        ];
    }
    
    /**
     * Chat with AI — used by the chat page
     *
     * @param string $message         The user's message
     * @param string $creatorContext   The system prompt / creator profile context
     * @param array  $chatHistory      Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param string $sessionType      Session type (unused, kept for API compatibility)
     * @return array
     */
    public function chat($message, $creatorContext, $chatHistory = [], $sessionType = 'general') {
        // Build Gemini contents array from chat history
        $contents = [];
        
        foreach ($chatHistory as $msg) {
            // Skip the current message if it appears at the end of history (it's already in $message)
            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }
        
        // If the last message in history is the same as $message, don't add it again
        $lastContent = end($contents);
        $alreadyIncluded = (
            $lastContent &&
            $lastContent['role'] === 'user' &&
            $lastContent['parts'][0]['text'] === $message
        );
        
        if (!$alreadyIncluded) {
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $message]],
            ];
        }
        
        return $this->callGemini($creatorContext, $contents, false);
    }
    
    /**
     * Generate content — used by content generator, ideas, analyzer, repurposer
     *
     * @param string $prompt          The generation prompt
     * @param string $creatorContext   System prompt / creator profile context
     * @param bool   $jsonMode        Whether to request structured JSON output
     * @return array
     */
    public function generate($prompt, $creatorContext, $jsonMode = false) {
        $contents = [
            [
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ],
        ];
        
        return $this->callGemini($creatorContext, $contents, $jsonMode);
    }
    
    /**
     * Generate content (legacy method — maps to generate)
     */
    public function generateContent($params, $creatorContext) {
        $prompt = is_array($params) ? ($params['prompt'] ?? json_encode($params)) : $params;
        return $this->generate($prompt, $creatorContext, false);
    }
    
    /**
     * Generate ideas (legacy method — maps to generate with JSON mode)
     */
    public function generateIdeas($params, $creatorContext) {
        $prompt = is_array($params) ? ($params['prompt'] ?? json_encode($params)) : $params;
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Generate script (legacy method)
     */
    public function generateScript($params, $creatorContext) {
        $prompt = is_array($params) ? ($params['prompt'] ?? json_encode($params)) : $params;
        return $this->generate($prompt, $creatorContext, false);
    }
    
    /**
     * Generate caption (legacy method)
     */
    public function generateCaption($params, $creatorContext) {
        $prompt = is_array($params) ? ($params['prompt'] ?? json_encode($params)) : $params;
        return $this->generate($prompt, $creatorContext, false);
    }
    
    /**
     * Repurpose content (legacy method)
     */
    public function repurpose($content, $sourcePlatform, $targetPlatforms, $creatorContext) {
        $prompt = "Repurpose the following content from $sourcePlatform to " . implode(', ', $targetPlatforms) . ":\n\n$content";
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Analyze content (legacy method)
     */
    public function analyzeContent($content, $platform, $creatorContext) {
        $prompt = "Analyze the following content for $platform:\n\n$content";
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Score content (legacy method)
     */
    public function scoreContent($content, $platform, $creatorContext) {
        $prompt = "Score the following content for $platform on a scale of 1-100:\n\n$content";
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Analyze performance (legacy method)
     */
    public function analyzePerformance($analyticsData, $creatorContext) {
        $prompt = "Analyze the following performance data and provide insights:\n\n" . json_encode($analyticsData);
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Generate calendar (legacy method)
     */
    public function generateCalendar($params, $creatorContext) {
        $prompt = is_array($params) ? ($params['prompt'] ?? json_encode($params)) : $params;
        return $this->generate($prompt, $creatorContext, true);
    }
    
    /**
     * Check AI service health — just verifies the API key is set
     */
    public function healthCheck() {
        return [
            'success' => AI_CONFIGURED,
            'provider' => 'gemini',
            'model' => $this->model,
            'status' => AI_CONFIGURED ? 'ready' : 'not_configured',
        ];
    }
}

/**
 * Quick access function
 */
function aiClient() {
    static $client = null;
    if ($client === null) {
        $client = new AIClient();
    }
    return $client;
}
