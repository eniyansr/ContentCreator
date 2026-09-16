<?php
/**
 * CreatorAI - Main Application Configuration
 * Loads environment variables and defines application constants
 */

// Prevent direct access
if (!defined('CREATOR_AI')) {
    define('CREATOR_AI', true);
}

// Base paths
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH', ROOT_PATH . 'public' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('LOG_PATH', ROOT_PATH . 'logs' . DIRECTORY_SEPARATOR);

// Load .env file
function loadEnv($path) {
    $envFile = $path . '.env';
    if (!file_exists($envFile)) {
        // Try .env.example as fallback in development
        $envFile = $path . '.env.example';
        if (!file_exists($envFile)) {
            return;
        }
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes
        if (preg_match('/^"(.+)"$/', $value, $m)) $value = $m[1];
        if (preg_match("/^'(.+)'$/", $value, $m)) $value = $m[1];
        
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnv(ROOT_PATH);

// Helper to get env variable with default
function env($key, $default = '') {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Application settings
define('APP_NAME', env('APP_NAME', 'CreatorAI'));
$defaultAppUrl = env('APP_URL', 'http://localhost/myworkouts/contentcreators');
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://';
    }
    $isBuiltInServer = php_sapi_name() === 'cli-server';
    $path = $isBuiltInServer ? '' : '/myworkouts/contentcreators';
    define('APP_URL', $protocol . $_SERVER['HTTP_HOST'] . $path);
} else {
    define('APP_URL', $defaultAppUrl);
}
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_DEBUG', env('APP_DEBUG', 'true') === 'true');

// Cloudflare D1 Configuration
define('CF_ACCOUNT_ID', env('CF_ACCOUNT_ID', ''));
define('CF_DB_ID', env('CF_DB_ID', ''));
define('CF_API_TOKEN', env('CF_API_TOKEN', ''));

// AI settings
define('AI_PROVIDER', env('AI_PROVIDER', 'gemini'));
define('AI_API_KEY', env('AI_API_KEY', ''));
define('AI_MODEL', env('AI_MODEL', 'gemini-3.6-flash'));
define('GEMINI_API_KEY', env('AI_API_KEY', ''));
define('PYTHON_AI_URL', env('PYTHON_AI_URL', 'http://127.0.0.1:8000'));
define('PYTHON_AI_SECRET', env('PYTHON_AI_SECRET', ''));

// Admin
define('ADMIN_REGISTRATION_KEY', env('ADMIN_REGISTRATION_KEY', ''));

// Session
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', '7200'));
define('SESSION_NAME', env('SESSION_NAME', 'creatorai_session'));

// Upload
define('MAX_UPLOAD_SIZE', (int) env('MAX_UPLOAD_SIZE', '10485760'));

// Rate limiting
define('MAX_LOGIN_ATTEMPTS', (int) env('MAX_LOGIN_ATTEMPTS', '5'));
define('LOGIN_LOCKOUT_TIME', (int) env('LOGIN_LOCKOUT_TIME', '900'));
define('MAX_AI_REQUESTS_PER_HOUR', (int) env('MAX_AI_REQUESTS_PER_HOUR', '100'));

// Error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0'); // Never display to user even in debug
    ini_set('log_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Create required directories
$dirs = [UPLOAD_PATH, LOG_PATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Timezone
date_default_timezone_set('UTC');

// Base URL helper
function base_url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

// Asset URL helper
function asset_url($path = '') {
    return base_url('assets/' . ltrim($path, '/'));
}
