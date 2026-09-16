<?php
/**
 * CreatorAI - Admin Authentication Middleware
 * Include this at the top of any admin-only page
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current admin ID
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin data
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    return [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin',
    ];
}

/**
 * Require admin authentication
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        if (isAjaxRequest()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Admin authentication required']);
            exit;
        }
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

/**
 * Login admin
 */
function loginAdmin($admin) {
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['_login_time'] = time();
    
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->update("UPDATE admins SET last_login = CURRENT_TIMESTAMP, login_attempts = 0 WHERE id = ?", [$admin['id']]);
        
        // Log admin login
        logAdminAction($admin['id'], 'login', null, null, 'Admin logged in');
    } catch (Exception $e) {
        error_log("Failed to update admin last login: " . $e->getMessage());
    }
}

/**
 * Logout admin
 */
function logoutAdmin() {
    if (isset($_SESSION['admin_id'])) {
        try {
            logAdminAction($_SESSION['admin_id'], 'logout', null, null, 'Admin logged out');
        } catch (Exception $e) {
            // Silent fail
        }
    }
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
 * Log admin action
 */
function logAdminAction($adminId, $action, $targetType = null, $targetId = null, $details = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        db()->insert(
            "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
            [$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
    }
}
