<?php
/**
 * CreatorAI - Registration API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Validate CSRF
if (!validateCsrfToken()) {
    jsonError('Invalid security token. Please refresh the page.', 403);
}

// Get input
$input = getJsonInput();
if (empty($input)) {
    $input = $_POST;
}

// Validate
$v = new Validator($input);
$v->required('full_name', 'Full Name')
  ->minLength('full_name', 2, 'Full Name')
  ->maxLength('full_name', 100, 'Full Name')
  ->required('username', 'Username')
  ->username('username')
  ->required('email', 'Email')
  ->email('email')
  ->required('password', 'Password')
  ->password('password')
  ->required('confirm_password', 'Confirm Password')
  ->matches('confirm_password', 'password', 'Passwords')
  ->required('country', 'Country');

if ($v->fails()) {
    jsonError($v->firstError(), 422, $v->errors());
}

$fullName = cleanInput($v->getValue('full_name'));
$username = cleanInput($v->getValue('username'));
$email = cleanInput(strtolower($v->getValue('email')));
$password = $v->getValue('password');
$country = cleanInput($v->getValue('country'));
$language = cleanInput($v->getValue('preferred_language', 'English'));

try {
    $d = db();
    
    // Check unique email
    $existing = $d->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        jsonError('This email address is already registered.', 422);
    }
    
    // Check unique username
    $existing = $d->fetch("SELECT id FROM users WHERE username = ?", [$username]);
    if ($existing) {
        jsonError('This username is already taken.', 422);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $userId = $d->insert(
        "INSERT INTO users (full_name, username, email, password_hash, country, preferred_language) VALUES (?, ?, ?, ?, ?, ?)",
        [$fullName, $username, $email, $passwordHash, $country, $language]
    );
    
    // Create empty profile
    $d->insert("INSERT INTO creator_profiles (user_id) VALUES (?)", [$userId]);
    
    // Create welcome notification
    createNotification($userId, 'Welcome to CreatorAI! 🎉', 
        'Your account has been created. Complete your profile to get personalized AI content assistance.', 
        'success', 'onboarding/');
    
    // Log activity
    logActivity($userId, 'registration', 'New creator account created');
    
    // Regenerate CSRF
    regenerateCsrfToken();
    
    jsonSuccess(['user_id' => $userId], 'Registration successful! Please log in.');
    
} catch (Exception $e) {
    logError("Registration failed", ['error' => $e->getMessage()]);
    jsonError('Registration failed. Please try again.');
}
