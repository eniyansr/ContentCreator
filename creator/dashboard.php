<?php
/**
 * CreatorAI - Creator Dashboard
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireCompletedProfile();

$userId = getCurrentUserId();
$user = getCurrentUser();
$d = db();

// Dashboard data
$profileCompletion = getProfileCompletion($userId);
$profile = $d->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]);
$recentChats = $d->fetchAll("SELECT id, title, last_message_at FROM chat_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_message_at DESC LIMIT 5", [$userId]);
$recentIdeas = $d->fetchAll("SELECT id, title, platform, status FROM content_ideas WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$userId]);
$savedCount = $d->fetchColumn("SELECT COUNT(*) FROM saved_content WHERE user_id = ?", [$userId]);
$chatCount = $d->fetchColumn("SELECT COUNT(*) FROM chat_sessions WHERE user_id = ?", [$userId]);
$ideasCount = $d->fetchColumn("SELECT COUNT(*) FROM content_ideas WHERE user_id = ?", [$userId]);
$calendarCount = $d->fetchColumn("SELECT COUNT(*) FROM content_calendar WHERE user_id = ? AND publish_date >= CURRENT_DATE", [$userId]);
$platforms = $d->fetchAll("SELECT platform_name, followers FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
$unreadNotifications = $d->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);

$displayName = $profile['display_name'] ?: $profile['creator_name'] ?: $user['full_name'];
$greeting = getGreeting();
$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-logo">
                <div class="sidebar-logo-icon">✦</div>
                CreatorAI
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-link active">
                    <span class="sidebar-link-icon">📊</span> Dashboard
                </a>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">💬</span> AI Creator Chat
                </a>
                <a href="<?php echo base_url('creator/ideas.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">💡</span> Content Ideas
                </a>
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">✍️</span> Generate Content
                </a>
                <a href="<?php echo base_url('creator/repurpose.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">🔄</span> Repurpose
                </a>
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">🔍</span> Content Analyzer
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Manage</div>
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">📈</span> Analytics
                </a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">📅</span> Calendar
                    <?php if ($calendarCount > 0): ?><span class="sidebar-link-badge"><?php echo $calendarCount; ?></span><?php endif; ?>
                </a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">📚</span> Saved Content
                </a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">📋</span> Templates
                </a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Settings</div>
                <a href="<?php echo base_url('creator/profile.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">👤</span> My Profile
                </a>
                <a href="<?php echo base_url('creator/notifications.php'); ?>" class="sidebar-link">
                    <span class="sidebar-link-icon">🔔</span> Notifications
                    <?php if ($unreadNotifications > 0): ?><span class="sidebar-link-badge"><?php echo $unreadNotifications; ?></span><?php endif; ?>
                </a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user" onclick="document.getElementById('userDropdown').classList.toggle('show')">
                <div class="avatar avatar-sm"><?php echo strtoupper(substr($displayName ?? 'U', 0, 1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo e($displayName); ?></div>
                    <div class="sidebar-user-role">Creator</div>
                </div>
            </div>
            <div class="dropdown-menu" id="userDropdown" style="bottom: 100%; top: auto; margin-bottom: 8px;">
                <a href="<?php echo base_url('creator/profile.php'); ?>" class="dropdown-item">👤 Profile</a>
                <a href="<?php echo base_url('api/auth/logout.php'); ?>" class="dropdown-item" style="color: var(--error);">🚪 Logout</a>
            </div>
        </div>
    </aside>

    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <h2 class="page-title">Dashboard</h2>
            </div>
            <div class="top-header-right">
                <button class="header-notification" onclick="window.location.href='<?php echo base_url('creator/notifications.php'); ?>'">
                    🔔
                    <?php if ($unreadNotifications > 0): ?><span class="notification-dot"></span><?php endif; ?>
                </button>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="btn btn-primary btn-sm">💬 Ask CreatorAI</a>
            </div>
        </header>

        <div class="page-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2 class="welcome-greeting"><?php echo $greeting; ?>, <?php echo e($displayName); ?>! 👋</h2>
                <p class="welcome-message">Here's your content creation hub. Let AI help you create amazing content today.</p>
                <?php if ($profileCompletion < 100): ?>
                <div class="welcome-profile-bar">
                    <span class="form-label" style="margin: 0;">Profile completion:</span>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?php echo $profileCompletion; ?>%"></div>
                    </div>
                    <span class="welcome-profile-pct"><?php echo $profileCompletion; ?>%</span>
                    <a href="<?php echo base_url('creator/profile.php'); ?>" class="btn btn-sm btn-secondary">Complete Profile</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-grid">
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(108,92,231,0.15);">💬</div>
                    <div class="quick-action-info"><h4>Ask CreatorAI</h4><p>Chat with your AI assistant</p></div>
                </a>
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(0,210,255,0.15);">✍️</div>
                    <div class="quick-action-info"><h4>Generate Content</h4><p>Create scripts, captions & more</p></div>
                </a>
                <a href="<?php echo base_url('creator/ideas.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(254,202,87,0.15);">💡</div>
                    <div class="quick-action-info"><h4>Get Ideas</h4><p>AI-powered content ideas</p></div>
                </a>
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(0,184,148,0.15);">🔍</div>
                    <div class="quick-action-info"><h4>Analyze Content</h4><p>Score & improve your content</p></div>
                </a>
                <a href="<?php echo base_url('creator/repurpose.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(253,121,168,0.15);">🔄</div>
                    <div class="quick-action-info"><h4>Repurpose</h4><p>Multi-platform content</p></div>
                </a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(162,155,254,0.15);">📅</div>
                    <div class="quick-action-info"><h4>Content Calendar</h4><p>Plan your content schedule</p></div>
                </a>
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(255,107,107,0.15);">📈</div>
                    <div class="quick-action-info"><h4>Analytics</h4><p>Track your performance</p></div>
                </a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="quick-action-card">
                    <div class="quick-action-icon" style="background: rgba(0,136,204,0.15);">📚</div>
                    <div class="quick-action-info"><h4>Content Library</h4><p>Your saved content</p></div>
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-label">Conversations</div>
                    <div class="stat-card-value"><?php echo $chatCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Content Ideas</div>
                    <div class="stat-card-value"><?php echo $ideasCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Saved Content</div>
                    <div class="stat-card-value"><?php echo $savedCount; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label">Upcoming Posts</div>
                    <div class="stat-card-value"><?php echo $calendarCount; ?></div>
                </div>
            </div>

            <!-- Two Column Grid -->
            <div class="dashboard-grid">
                <!-- Recent Conversations -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">Recent Conversations</h3>
                        <a href="<?php echo base_url('creator/chat.php'); ?>" class="dashboard-card-action">View All →</a>
                    </div>
                    <?php if (empty($recentChats)): ?>
                        <div class="empty-state" style="padding: var(--space-8) 0;">
                            <p style="font-size: var(--font-size-sm);">No conversations yet. Start chatting with CreatorAI!</p>
                            <a href="<?php echo base_url('creator/chat.php'); ?>" class="btn btn-primary btn-sm mt-4">Start Chat</a>
                        </div>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($recentChats as $chat): ?>
                            <li class="recent-item">
                                <div class="recent-item-icon">💬</div>
                                <div class="recent-item-info">
                                    <div class="recent-item-title"><?php echo e($chat['title']); ?></div>
                                    <div class="recent-item-meta"><?php echo $chat['last_message_at'] ? timeAgo($chat['last_message_at']) : 'No messages'; ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Platforms Overview -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">Your Platforms</h3>
                        <a href="<?php echo base_url('creator/profile.php'); ?>" class="dashboard-card-action">Manage →</a>
                    </div>
                    <?php if (empty($platforms)): ?>
                        <div class="empty-state" style="padding: var(--space-8) 0;">
                            <p style="font-size: var(--font-size-sm);">No platforms added yet.</p>
                            <a href="<?php echo base_url('creator/profile.php'); ?>" class="btn btn-secondary btn-sm mt-4">Add Platforms</a>
                        </div>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($platforms as $p): ?>
                            <li class="recent-item">
                                <div class="recent-item-icon">📱</div>
                                <div class="recent-item-info">
                                    <div class="recent-item-title"><?php echo e($p['platform_name']); ?></div>
                                    <div class="recent-item-meta"><?php echo $p['followers'] > 0 ? number_format($p['followers']) . ' followers' : 'No data'; ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Recent Ideas -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">Recent Ideas</h3>
                        <a href="<?php echo base_url('creator/ideas.php'); ?>" class="dashboard-card-action">View All →</a>
                    </div>
                    <?php if (empty($recentIdeas)): ?>
                        <div class="empty-state" style="padding: var(--space-8) 0;">
                            <p style="font-size: var(--font-size-sm);">Generate content ideas with AI!</p>
                            <a href="<?php echo base_url('creator/ideas.php'); ?>" class="btn btn-primary btn-sm mt-4">Get Ideas</a>
                        </div>
                    <?php else: ?>
                        <ul class="recent-list">
                            <?php foreach ($recentIdeas as $idea): ?>
                            <li class="recent-item">
                                <div class="recent-item-icon">💡</div>
                                <div class="recent-item-info">
                                    <div class="recent-item-title"><?php echo e($idea['title']); ?></div>
                                    <div class="recent-item-meta"><?php echo e($idea['platform'] ?? 'General'); ?> · <?php echo e($idea['status']); ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- AI Recommendations -->
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <h3 class="dashboard-card-title">AI Recommendations</h3>
                    </div>
                    <div style="padding: var(--space-4); background: var(--bg-tertiary); border-radius: var(--radius-lg);">
                        <p style="font-size: var(--font-size-sm); color: var(--text-secondary); line-height: 1.7;">
                            <?php
                            $niche = $profile['primary_niche'] ?? 'your niche';
                            $creatorTypes = json_decode($profile['creator_types'] ?? '[]', true);
                            $type = !empty($creatorTypes) ? $creatorTypes[0] : 'content';
                            ?>
                            💡 Based on your profile as a <strong><?php echo e($type); ?></strong> in the <strong><?php echo e($niche); ?></strong> niche, 
                            try using the <a href="<?php echo base_url('creator/content-generator.php'); ?>">Content Generator</a> to create platform-optimized content.
                        </p>
                    </div>
                    <div style="margin-top: var(--space-4); padding: var(--space-4); background: var(--bg-tertiary); border-radius: var(--radius-lg);">
                        <p style="font-size: var(--font-size-sm); color: var(--text-secondary); line-height: 1.7;">
                            📅 Stay consistent! Use the <a href="<?php echo base_url('creator/calendar.php'); ?>">Content Calendar</a> to plan your posts ahead of time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.sidebar-user') && !e.target.closest('#userDropdown')) {
            document.getElementById('userDropdown')?.classList.remove('show');
        }
    });
</script>
</body>
</html>
