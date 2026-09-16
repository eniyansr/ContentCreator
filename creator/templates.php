<?php
/**
 * CreatorAI - Templates Page (Stub)
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
    <title>Templates – CreatorAI</title>
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
                <div class="sidebar-section-title">Manage</div>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h2 class="page-title">Content Templates</h2>
        </header>

        <div class="page-content">
            <div style="background: var(--bg-card); padding: var(--space-8); border-radius: var(--radius-xl); border: 1px solid var(--border-light); text-align: center;">
                <div style="font-size: 3rem; margin-bottom: var(--space-4);">📋</div>
                <h3>Standard Templates</h3>
                <p style="color: var(--text-secondary); margin-top: var(--space-2);">CreatorAI uses your profile preferences as a baseline for all content generation, acting as an implicit template. Future updates will allow for saving custom specific structural templates.</p>
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="btn btn-primary" style="margin-top: var(--space-6);">Go to Generator</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
