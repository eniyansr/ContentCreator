<?php
/**
 * CreatorAI - Admin Registration API
 */

require_once __DIR__ . '/../../config/database.php';
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
$v->required('name', 'Admin Name')
  ->minLength('name', 2, 'Admin Name')
  ->required('email', 'Email')
  ->email('email')
  ->required('password', 'Password')
  ->password('password')
  ->required('confirm_password', 'Confirm Password')
  ->matches('confirm_password', 'password', 'Passwords')
  ->required('registration_key', 'Registration Key');

if ($v->fails()) {
    jsonError($v->firstError(), 422, $v->errors());
}

$registrationKey = $v->getValue('registration_key');

// Validate admin registration key
if (empty(ADMIN_REGISTRATION_KEY) || $registrationKey !== ADMIN_REGISTRATION_KEY) {
    jsonError('Invalid admin registration key.', 403);
}

$name = cleanInput($v->getValue('name'));
$email = cleanInput(strtolower($v->getValue('email')));
$password = $v->getValue('password');

try {
    $d = db();
    
    // Check unique email
    $existing = $d->fetch("SELECT id FROM admins WHERE email = ?", [$email]);
    if ($existing) {
        jsonError('This email is already registered as an admin.', 422);
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin
    $adminId = $d->insert(
        "INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')",
        [$name, $email, $passwordHash]
    );
    
    regenerateCsrfToken();
    
    jsonSuccess(['admin_id' => $adminId], 'Admin account created! Please log in.');
    
} catch (Exception $e) {
    logError("Admin registration failed", ['error' => $e->getMessage()]);
    jsonError('Registration failed. Please try again.');
}
