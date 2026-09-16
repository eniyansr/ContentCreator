<?php
/**
 * CreatorAI - Profile Settings Page (Stub)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCompletedProfile();

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile – CreatorAI</title>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header"><a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-logo"><div class="sidebar-logo-icon">✦</div>CreatorAI</a></div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📊</span> Dashboard</a>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">💬</span> AI Creator Chat</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Settings</div>
                <a href="<?php echo base_url('creator/profile.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">👤</span> My Profile</a>
                <a href="<?php echo base_url('creator/notifications.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔔</span> Notifications</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h2 class="page-title">Creator Profile Settings</h2>
        </header>

        <div class="page-content" style="max-width: 800px; margin: 0 auto;">
            <div style="background: var(--bg-card); padding: var(--space-8); border-radius: var(--radius-xl); border: 1px solid var(--border-light); text-align: center;">
                <div style="font-size: 3rem; margin-bottom: var(--space-4);">👤</div>
                <h3>Profile Settings</h3>
                <p style="color: var(--text-secondary); margin-top: var(--space-2);">Your profile dictates how the AI generates content for you. To update your core preferences, you can retake the onboarding wizard.</p>
                <a href="<?php echo base_url('onboarding/index.php?edit=1'); ?>" class="btn btn-primary" style="margin-top: var(--space-6);">Re-run Profile Setup</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
