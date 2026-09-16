<?php
/**
 * CreatorAI - Creator Authentication Middleware
 * Include this at the top of any creator-only page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'creator';
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data from session
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'profile_completed' => $_SESSION['profile_completed'] ?? 0,
        'avatar' => $_SESSION['avatar'] ?? null,
    ];
}

/**
 * Require authentication - redirect to login if not logged in
 */
function requireAuth() {
    if (!isLoggedIn()) {
        // For AJAX requests, return JSON error
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        // For regular requests, redirect to login
        header('Location: ' . base_url('public/login.php'));
        exit;
    }
}

/**
 * Require completed profile - redirect to onboarding if incomplete
 */
function requireCompletedProfile() {
    requireAuth();
    if (empty($_SESSION['profile_completed']) || $_SESSION['profile_completed'] != 1) {
        if (isAjaxRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Please complete your profile first']);
            exit;
        }
        header('Location: ' . base_url('onboarding/'));
        exit;
    }
}

/**
 * Login user - set session variables
 */
function loginUser($user) {
    // Regenerate session ID on login
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_role'] = 'creator';
    $_SESSION['profile_completed'] = $user['profile_completed'];
    $_SESSION['avatar'] = $user['avatar'] ?? null;
    $_SESSION['_login_time'] = time();
    
    // Update last login in database
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->update("UPDATE users SET last_login = CURRENT_TIMESTAMP, login_attempts = 0 WHERE id = ?", [$user['id']]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
           !empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
}
