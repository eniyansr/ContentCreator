<?php
/**
 * CreatorAI - Notifications Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireCompletedProfile();

$userId = getCurrentUserId();
$d = db();

if (isset($_GET['mark_read'])) {
    $d->update("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
    header('Location: ' . base_url('creator/notifications.php'));
    exit;
}

$notifications = $d->fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", [$userId]);
$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications – CreatorAI</title>
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
                <a href="<?php echo base_url('creator/profile.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">👤</span> My Profile</a>
                <a href="<?php echo base_url('creator/notifications.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">🔔</span> Notifications</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <h2 class="page-title">Notifications</h2>
            <?php if(!empty($notifications)): ?>
            <a href="?mark_read=1" class="btn btn-secondary btn-sm">Mark All Read</a>
            <?php endif; ?>
        </header>

        <div class="page-content" style="max-width: 800px; margin: 0 auto;">
            <div style="background: var(--bg-card); border-radius: var(--radius-xl); border: 1px solid var(--border-light); overflow: hidden;">
                <?php if(empty($notifications)): ?>
                    <div style="padding: var(--space-8); text-align: center; color: var(--text-muted);">No new notifications.</div>
                <?php else: ?>
                    <?php foreach($notifications as $n): ?>
                    <div style="padding: var(--space-4); border-bottom: 1px solid var(--border-light); display: flex; gap: var(--space-3); background: <?php echo $n['is_read'] ? 'transparent' : 'var(--bg-tertiary)'; ?>;">
                        <div style="font-size: 1.5rem;"><?php echo $n['type'] === 'system' ? '⚙️' : '🔔'; ?></div>
                        <div>
                            <div style="font-weight: 600; font-size: var(--font-size-sm);"><?php echo e($n['title']); ?></div>
                            <div style="color: var(--text-secondary); font-size: var(--font-size-sm); margin-top: 4px;"><?php echo e($n['message']); ?></div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-xs); margin-top: var(--space-2);"><?php echo timeAgo($n['created_at']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
