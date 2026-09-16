<?php
/**
 * CreatorAI - CSRF Protection
 */

require_once __DIR__ . '/session.php';

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token HTML input
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Get CSRF meta tag for AJAX
 */
function csrfMeta() {
    $token = generateCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token
 */
function validateCsrfToken($token = null) {
    if ($token === null) {
        // Check POST data first
        $token = $_POST['csrf_token'] ?? null;
        
        // Then check headers (for AJAX)
        if ($token === null) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token
 */
function requireCsrf() {
    if (!validateCsrfToken()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
            exit;
        }
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

/**
 * Regenerate CSRF token (call after form submission)
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
