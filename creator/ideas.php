<?php
/**
 * CreatorAI - Content Ideas Page
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
$profile = $d->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]);
$displayName = $profile['display_name'] ?: $profile['creator_name'] ?: $user['full_name'];
$unreadNotifications = $d->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);

$platforms = $d->fetchAll("SELECT platform_name FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
$niche = $profile['primary_niche'] ?? 'your niche';

// Fetch saved ideas
$savedIdeas = $d->fetchAll("SELECT * FROM content_ideas WHERE user_id = ? ORDER BY created_at DESC", [$userId]);

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Ideas – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/ideas.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <!-- Reusing sidebar structure -->
        <div class="sidebar-header">
            <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-logo">
                <div class="sidebar-logo-icon">✦</div>
                CreatorAI
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📊</span> Dashboard</a>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">💬</span> AI Creator Chat</a>
                <a href="<?php echo base_url('creator/ideas.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">💡</span> Content Ideas</a>
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">✍️</span> Generate Content</a>
                <a href="<?php echo base_url('creator/repurpose.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔄</span> Repurpose</a>
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔍</span> Content Analyzer</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Manage</div>
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📈</span> Analytics</a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📅</span> Calendar</a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Settings</div>
                <a href="<?php echo base_url('creator/profile.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">👤</span> My Profile</a>
                <a href="<?php echo base_url('creator/notifications.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔔</span> Notifications</a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar avatar-sm"><?php echo strtoupper(substr($displayName, 0, 1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo e($displayName); ?></div>
                    <div class="sidebar-user-role">Creator</div>
                </div>
            </div>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <h2 class="page-title">Content Ideas</h2>
            </div>
            <div class="top-header-right">
                <button class="header-notification" onclick="window.location.href='<?php echo base_url('creator/notifications.php'); ?>'">🔔<?php if ($unreadNotifications > 0): ?><span class="notification-dot"></span><?php endif; ?></button>
            </div>
        </header>

        <div class="page-content">
            <div class="ideas-layout">
                <!-- Main Area -->
                <div class="ideas-main">
                    <!-- Generator Card -->
                    <div class="generator-card">
                        <h2 class="generator-title">💡 AI Idea Lab</h2>
                        <p class="generator-desc">Generate personalized content ideas tailored to your audience and niche (<?php echo e($niche); ?>).</p>
                        
                        <div class="generator-form" id="ideaGeneratorForm">
                            <div class="generator-input-group">
                                <div class="generator-input form-group" style="margin: 0;">
                                    <input type="text" id="topicInput" class="form-input" placeholder="Topic or theme (optional, e.g. 'morning routine')">
                                </div>
                                <button class="btn btn-primary" id="generateBtn" onclick="generateIdeas()">
                                    ✨ Generate Ideas
                                </button>
                            </div>
                            
                            <div class="generator-options">
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Platform</label>
                                    <select id="platformInput" class="form-select">
                                        <option value="Any Platform">Any Platform</option>
                                        <?php foreach ($platforms as $p): ?>
                                        <option value="<?php echo e($p['platform_name']); ?>"><?php echo e($p['platform_name']); ?></option>
                                        <?php endforeach; ?>
                                        <option value="YouTube">YouTube</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="TikTok">TikTok</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label class="form-label">Format</label>
                                    <select id="formatInput" class="form-select">
                                        <option value="Any Format">Any Format</option>
                                        <option value="Short Video">Short Video (Reels/Shorts/TikTok)</option>
                                        <option value="Long Video">Long Video</option>
                                        <option value="Carousel/Post">Carousel/Post</option>
                                        <option value="Thread/Text">Thread/Text</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Generated Ideas Container -->
                    <div id="generatedContainer" class="d-none" style="margin-bottom: var(--space-8);">
                        <div class="ideas-header">
                            <h3>✨ Generated Ideas</h3>
                            <button class="btn btn-ghost btn-sm" onclick="clearGenerated()">Clear</button>
                        </div>
                        <div class="ideas-grid" id="generatedGrid">
                            <!-- Populated via JS -->
                        </div>
                    </div>

                    <!-- Saved Ideas -->
                    <div class="ideas-header">
                        <h3>📚 My Idea Library</h3>
                        <div class="ideas-filter">
                            <button class="filter-btn active" onclick="filterIdeas('all', this)">All</button>
                            <button class="filter-btn" onclick="filterIdeas('idea', this)">Ideas</button>
                            <button class="filter-btn" onclick="filterIdeas('planned', this)">Planned</button>
                            <button class="filter-btn" onclick="filterIdeas('completed', this)">Completed</button>
                        </div>
                    </div>
                    
                    <div class="ideas-grid" id="savedGrid">
                        <?php if (empty($savedIdeas)): ?>
                            <div class="empty-state" style="grid-column: 1 / -1; padding: var(--space-8);">
                                <div class="empty-state-icon">💡</div>
                                <h3>No saved ideas yet</h3>
                                <p>Generate some ideas above and save the ones you like.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($savedIdeas as $idea): 
                            $tags = json_decode($idea['tags'], true) ?: [];
                            $score = $idea['viral_score'];
                            $scoreClass = $score >= 80 ? 'high' : ($score >= 50 ? 'med' : 'low');
                        ?>
                        <div class="idea-card" data-status="<?php echo $idea['status']; ?>" id="idea_<?php echo $idea['id']; ?>">
                            <div class="idea-header">
                                <span class="idea-platform"><?php echo e($idea['platform']); ?></span>
                                <span class="idea-score <?php echo $scoreClass; ?>" data-tooltip="Viral Potential Score">
                                    🔥 <?php echo $score; ?>
                                </span>
                            </div>
                            <h4 class="idea-title"><?php echo e($idea['title']); ?></h4>
                            <p class="idea-desc"><?php echo e($idea['description']); ?></p>
                            
                            <div class="idea-meta">
                                <?php foreach ($tags as $tag): ?>
                                <span class="idea-tag">#<?php echo e($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="idea-actions">
                                <div class="idea-actions-left">
                                    <select class="form-select" style="padding: 2px 8px; font-size: 11px; height: auto;" onchange="updateIdeaStatus(<?php echo $idea['id']; ?>, this.value)">
                                        <option value="idea" <?php echo $idea['status'] == 'idea' ? 'selected' : ''; ?>>Idea</option>
                                        <option value="planned" <?php echo $idea['status'] == 'planned' ? 'selected' : ''; ?>>Planned</option>
                                        <option value="completed" <?php echo $idea['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="idea-actions-right">
                                    <a href="<?php echo base_url('creator/content-generator.php'); ?>?idea_id=<?php echo $idea['id']; ?>" class="idea-action-btn generate">✍️ Create</a>
                                    <button class="idea-action-btn" onclick="deleteIdea(<?php echo $idea['id']; ?>)">🗑️</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="ideas-sidebar">
                    <div class="trending-card">
                        <div class="trending-header">📈 Trending in <?php echo e($niche); ?></div>
                        <ul class="trending-list">
                            <!-- Placeholder trending topics -->
                            <li class="trending-item" onclick="useTrendingTopic('How to start with <?php echo e($niche); ?>')">
                                <div class="trending-rank">#1</div>
                                <div class="trending-info">
                                    <div class="trending-title">Beginner guides</div>
                                    <div class="trending-meta">High search volume</div>
                                </div>
                            </li>
                            <li class="trending-item" onclick="useTrendingTopic('Biggest mistakes in <?php echo e($niche); ?>')">
                                <div class="trending-rank">#2</div>
                                <div class="trending-info">
                                    <div class="trending-title">Common mistakes</div>
                                    <div class="trending-meta">High engagement</div>
                                </div>
                            </li>
                            <li class="trending-item" onclick="useTrendingTopic('My exact workflow for <?php echo e($niche); ?>')">
                                <div class="trending-rank">#3</div>
                                <div class="trending-info">
                                    <div class="trending-title">Behind the scenes</div>
                                    <div class="trending-meta">Builds trust</div>
                                </div>
                            </li>
                            <li class="trending-item" onclick="useTrendingTopic('Top tools for <?php echo e($niche); ?> in 2024')">
                                <div class="trending-rank">#4</div>
                                <div class="trending-info">
                                    <div class="trending-title">Tool recommendations</div>
                                    <div class="trending-meta">High save rate</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="dashboard-card" style="margin-top: var(--space-4);">
                        <h4 style="font-size: var(--font-size-sm); margin-bottom: var(--space-2);">Pro Tip 💡</h4>
                        <p style="font-size: var(--font-size-xs); color: var(--text-secondary); line-height: 1.6;">
                            Save the ideas you like! Once saved, you can send them directly to the Content Generator to instantly create full scripts, captions, or outlines.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        API.init('<?php echo $baseUrl; ?>');
    });

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function useTrendingTopic(topic) {
        document.getElementById('topicInput').value = topic;
        document.getElementById('topicInput').focus();
    }

    function filterIdeas(status, btn) {
        // Update active class
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Filter cards
        const cards = document.querySelectorAll('#savedGrid .idea-card');
        cards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    async function generateIdeas() {
        const btn = document.getElementById('generateBtn');
        const topic = document.getElementById('topicInput').value.trim();
        const platform = document.getElementById('platformInput').value;
        const format = document.getElementById('formatInput').value;

        Utils.btnLoading(btn, true);

        try {
            const result = await API.post('api/creator/ideas.php', {
                action: 'generate',
                topic: topic,
                platform: platform,
                format: format,
                count: 4,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });

            if (result.success && result.ideas) {
                renderGeneratedIdeas(result.ideas, platform, format);
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to generate ideas', 'error');
        }

        Utils.btnLoading(btn, false);
    }

    function renderGeneratedIdeas(ideas, platform, format) {
        const container = document.getElementById('generatedContainer');
        const grid = document.getElementById('generatedGrid');
        
        grid.innerHTML = '';
        container.classList.remove('d-none');

        ideas.forEach((idea, index) => {
            const scoreClass = idea.viral_score >= 80 ? 'high' : (idea.viral_score >= 50 ? 'med' : 'low');
            let tagsHtml = '';
            if (idea.tags && Array.isArray(idea.tags)) {
                tagsHtml = idea.tags.map(t => `<span class="idea-tag">#${t}</span>`).join('');
            }
            
            // Store raw idea object as JSON in a data attribute
            const ideaJson = Utils.escapeHtml(JSON.stringify({
                title: idea.title,
                description: idea.description,
                viral_score: idea.viral_score,
                tags: idea.tags,
                platform: idea.platform || platform,
                format: idea.format || format
            }));

            const div = document.createElement('div');
            div.className = 'idea-card animate-in';
            div.style.animationDelay = `${index * 0.1}s`;
            div.innerHTML = `
                <div class="idea-header">
                    <span class="idea-platform">${idea.platform || platform}</span>
                    <span class="idea-score ${scoreClass}" data-tooltip="Viral Potential Score">🔥 ${idea.viral_score}</span>
                </div>
                <h4 class="idea-title">${Utils.escapeHtml(idea.title)}</h4>
                <p class="idea-desc">${Utils.escapeHtml(idea.description)}</p>
                <div class="idea-meta">${tagsHtml}</div>
                <div class="idea-actions" style="justify-content: flex-end;">
                    <button class="btn btn-primary btn-sm" onclick="saveGeneratedIdea(this, '${ideaJson}')">💾 Save Idea</button>
                </div>
            `;
            grid.appendChild(div);
        });
    }

    function clearGenerated() {
        document.getElementById('generatedContainer').classList.add('d-none');
        document.getElementById('generatedGrid').innerHTML = '';
    }

    async function saveGeneratedIdea(btn, ideaJsonStr) {
        try {
            // Unescape the HTML we escaped earlier, then parse JSON
            const parser = new DOMParser();
            const unescapedStr = parser.parseFromString(ideaJsonStr, 'text/html').documentElement.textContent;
            const ideaObj = JSON.parse(unescapedStr);
            
            Utils.btnLoading(btn, true);
            
            const result = await API.post('api/creator/ideas.php', {
                action: 'save',
                idea: ideaObj,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });

            if (result.success) {
                Utils.toast('Idea saved to library!', 'success');
                btn.innerHTML = '✓ Saved';
                btn.className = 'btn btn-success btn-sm';
                btn.disabled = true;
                
                // Optionally reload to show in the saved list, or we could append it via JS
                setTimeout(() => window.location.reload(), 1000);
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to save idea', 'error');
            Utils.btnLoading(btn, false);
        }
    }

    async function updateIdeaStatus(id, status) {
        try {
            await API.post('api/creator/ideas.php', {
                action: 'update_status',
                id: id,
                status: status,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            document.getElementById('idea_' + id).dataset.status = status;
            Utils.toast('Status updated', 'success');
        } catch (error) {
            Utils.toast('Failed to update status', 'error');
        }
    }

    async function deleteIdea(id) {
        if (!await Utils.confirm('Are you sure you want to delete this idea?')) return;
        
        try {
            const result = await API.post('api/creator/ideas.php', {
                action: 'delete',
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                const card = document.getElementById('idea_' + id);
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
                Utils.toast('Idea deleted', 'success');
            }
        } catch (error) {
            Utils.toast('Failed to delete idea', 'error');
        }
    }
</script>
</body>
</html>
