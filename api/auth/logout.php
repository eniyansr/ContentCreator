<?php
/**
 * CreatorAI - Logout API
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Log the logout
if (isLoggedIn()) {
    logActivity(getCurrentUserId(), 'logout', 'Creator logged out');
}

logoutUser();

// For AJAX requests
if (isAjaxRequest()) {
    jsonSuccess([], 'Logged out successfully.');
}

// For regular requests
header('Location: ' . base_url('public/login.php'));
exit;
