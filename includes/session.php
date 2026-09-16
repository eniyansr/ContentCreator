<?php
/**
 * CreatorAI - Secure Session Management
 */

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Load config if not loaded
    if (!defined('SESSION_NAME')) {
        require_once __DIR__ . '/../config/config.php';
    }
    
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    // Set session name
    session_name(SESSION_NAME);
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
    
    // Check session timeout
    if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['_last_activity'] = time();
}
