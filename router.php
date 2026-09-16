<?php
// Simple router for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Don't serve sensitive files
if (preg_match('/\.(env|log|sql|md)$/', $uri)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Don't serve from protected directories
if (preg_match('/^\/(config|includes|logs|database|python-ai)\//', $uri)) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

// Serve existing files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; 
}

// Default route to public/index.php
$_SERVER['SCRIPT_NAME'] = '/public/index.php';
require_once __DIR__ . '/public/index.php';
