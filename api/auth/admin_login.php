<?php
/**
 * CreatorAI - Admin Login API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (!validateCsrfToken()) {
    jsonError('Invalid security token.', 403);
}

$input = getJsonInput();
if (empty($input)) $input = $_POST;

$v = new Validator($input);
$v->required('email', 'Email')
  ->email('email')
  ->required('password', 'Password');

if ($v->fails()) {
    jsonError($v->firstError(), 422, $v->errors());
}

$email = cleanInput(strtolower($v->getValue('email')));
$password = $v->getValue('password');

try {
    $d = db();
    
    // Find admin
    $admin = $d->fetch("SELECT * FROM admins WHERE email = ?", [$email]);
    
    if (!$admin) {
        jsonError('Invalid admin credentials.', 401);
    }
    
    if (!$admin['is_active']) {
        jsonError('Admin account is deactivated.', 403);
    }
    
    // Check locked
    if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
        jsonError('Account temporarily locked. Try again later.', 429);
    }
    
    // Verify password
    if (!password_verify($password, $admin['password_hash'])) {
        // Increment attempts
        $attempts = $admin['login_attempts'] + 1;
        $lockedUntil = $attempts >= MAX_LOGIN_ATTEMPTS ? date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME) : null;
        $d->update("UPDATE admins SET login_attempts = ?, locked_until = ? WHERE id = ?", [$attempts, $lockedUntil, $admin['id']]);
        
        jsonError('Invalid admin credentials.', 401);
    }
    
    // Login admin
    loginAdmin($admin);
    regenerateCsrfToken();
    
    jsonSuccess([
        'redirect' => base_url('admin/dashboard.php'),
    ], 'Admin login successful!');
    
} catch (Exception $e) {
    logError("Admin login failed", ['error' => $e->getMessage()]);
    jsonError('Login failed. Please try again.');
}
