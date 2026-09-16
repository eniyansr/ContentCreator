<?php
/**
 * CreatorAI - Login API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (!validateCsrfToken()) {
    jsonError('Invalid security token. Please refresh the page.', 403);
}

$input = getJsonInput();
if (empty($input)) $input = $_POST;

$v = new Validator($input);
$v->required('email', 'Email or Username')
  ->required('password', 'Password');

if ($v->fails()) {
    jsonError($v->firstError(), 422, $v->errors());
}

$emailOrUsername = cleanInput($v->getValue('email'));
$password = $v->getValue('password');

try {
    $d = db();
    
    // Check rate limit
    if (!checkLoginRateLimit($emailOrUsername)) {
        jsonError('Too many login attempts. Please try again in 15 minutes.', 429);
    }
    
    // Find user by email or username
    $user = $d->fetch(
        "SELECT * FROM users WHERE email = ? OR username = ?",
        [$emailOrUsername, $emailOrUsername]
    );
    
    if (!$user) {
        jsonError('Invalid email/username or password.', 401);
    }
    
    // Check if banned
    if ($user['is_banned']) {
        jsonError('Your account has been suspended. Please contact support.', 403);
    }
    
    // Check if active
    if (!$user['is_active']) {
        jsonError('Your account is deactivated. Please contact support.', 403);
    }
    
    // Check locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
        jsonError("Account temporarily locked. Try again in {$mins} minutes.", 429);
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        incrementLoginAttempts($user['email']);
        jsonError('Invalid email/username or password.', 401);
    }
    
    // Login successful
    loginUser($user);
    logActivity($user['id'], 'login', 'Creator logged in');
    regenerateCsrfToken();
    
    // Determine redirect
    $redirect = $user['profile_completed'] ? 'creator/dashboard.php' : 'onboarding/';
    
    jsonSuccess([
        'redirect' => base_url($redirect),
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'profile_completed' => (bool) $user['profile_completed'],
        ]
    ], 'Login successful!');
    
} catch (Exception $e) {
    logError("Login failed", ['error' => $e->getMessage()]);
    jsonError('Login failed. Please try again.');
}
