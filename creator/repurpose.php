<?php
/**
 * CreatorAI - Content Repurposer Page
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

// Load saved content if passed via query string
$sourceId = (int)($_GET['source_id'] ?? 0);
$sourceContent = '';
if ($sourceId) {
    $saved = $d->fetch("SELECT content_data FROM saved_content WHERE id = ? AND user_id = ?", [$sourceId, $userId]);
    if ($saved) {
        $sourceContent = $saved['content_data'];
    }
}

// Available target platforms
$availablePlatforms = [
    'X/Twitter Thread' => '🐦',
    'LinkedIn Post' => '💼',
    'Instagram Caption' => '📸',
    'TikTok Script' => '🎵',
    'YouTube Community Post' => '📺',
    'Newsletter/Blog Intro' => '✉️'
];

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Repurposer – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/repurpose.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-logo"><div class="sidebar-logo-icon">✦</div>CreatorAI</a>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📊</span> Dashboard</a>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">💬</span> AI Creator Chat</a>
                <a href="<?php echo base_url('creator/ideas.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">💡</span> Content Ideas</a>
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">✍️</span> Generate Content</a>
                <a href="<?php echo base_url('creator/repurpose.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">🔄</span> Repurpose</a>
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔍</span> Content Analyzer</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Manage</div>
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📈</span> Analytics</a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📅</span> Calendar</a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <h2 class="page-title">Repurpose Content</h2>
            </div>
        </header>

        <div class="page-content">
            <div class="repurpose-layout">
                
                <!-- Source Panel -->
                <div class="repurpose-source-panel">
                    <div class="repurpose-panel-header">
                        <div class="repurpose-panel-title">📝 Source Content</div>
                        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('sourceContent').value=''">Clear</button>
                    </div>
                    
                    <div class="repurpose-source-body">
                        <div style="flex: 1; display: flex; flex-direction: column;">
                            <label class="form-label">Paste your original content (e.g., YouTube Script, Blog Post)</label>
                            <textarea id="sourceContent" class="repurpose-textarea" placeholder="Paste the content you want to repurpose here..."><?php echo e($sourceContent); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="form-label mt-4">Select target platforms (Max 5)</label>
                            <div class="platform-checkboxes">
                                <?php foreach ($availablePlatforms as $name => $emoji): ?>
                                <label class="platform-checkbox-card">
                                    <input type="checkbox" name="target_platforms" value="<?php echo $name; ?>" style="display: none;" onchange="togglePlatformSelection(this)">
                                    <span style="font-size: 1.2rem;"><?php echo $emoji; ?></span>
                                    <span style="font-size: var(--font-size-xs); font-weight: 600;"><?php echo $name; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: var(--space-4); background: var(--bg-tertiary); border-top: 1px solid var(--border-light);">
                        <button class="btn btn-primary w-full" id="repurposeBtn" onclick="repurposeContent()">
                            🔄 Repurpose Content
                        </button>
                    </div>
                </div>
                
                <!-- Results Panel -->
                <div class="repurpose-results-panel">
                    <div class="repurpose-panel-header">
                        <div class="repurpose-panel-title">✨ Generated Content</div>
                    </div>
                    
                    <div class="repurpose-results-body">
                        
                        <!-- Empty State -->
                        <div class="repurpose-empty" id="resultsEmpty">
                            <div class="repurpose-empty-icon">🪄</div>
                            <h3 style="color: var(--text-primary); margin-bottom: var(--space-2);">Multiply Your Content</h3>
                            <p style="color: var(--text-secondary); font-size: var(--font-size-sm);">Paste a script on the left, select your platforms, and watch AI instantly create native posts for all of them.</p>
                        </div>
                        
                        <!-- Loading State -->
                        <div class="repurpose-loading hidden" id="resultsLoading">
                            <div class="loading-spinner"></div>
                            <p style="color: var(--primary-light); font-weight: 600; margin-top: var(--space-4);">Adapting content to selected platforms...</p>
                        </div>
                        
                        <!-- Results Container -->
                        <div id="resultsContainer" class="hidden">
                            <!-- Populated via JS -->
                        </div>
                        
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

    function togglePlatformSelection(checkbox) {
        const card = checkbox.closest('.platform-checkbox-card');
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    }

    async function repurposeContent() {
        const content = document.getElementById('sourceContent').value.trim();
        if (!content) {
            Utils.toast('Please paste your source content first.', 'error');
            return;
        }

        const selectedCheckboxes = document.querySelectorAll('input[name="target_platforms"]:checked');
        const platforms = Array.from(selectedCheckboxes).map(cb => cb.value);

        if (platforms.length === 0) {
            Utils.toast('Please select at least one target platform.', 'error');
            return;
        }
        if (platforms.length > 5) {
            Utils.toast('Please select a maximum of 5 platforms.', 'error');
            return;
        }

        const btn = document.getElementById('repurposeBtn');
        
        // UI State
        Utils.btnLoading(btn, true);
        document.getElementById('resultsEmpty').classList.add('hidden');
        document.getElementById('resultsContainer').classList.add('hidden');
        document.getElementById('resultsLoading').classList.remove('hidden');
        document.getElementById('resultsContainer').innerHTML = '';

        try {
            const result = await API.post('api/creator/repurpose.php', {
                action: 'repurpose',
                content: content,
                platforms: platforms,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });

            if (result.success && result.results) {
                renderResults(result.results);
            }
        } catch (error) {
            Utils.toast(error.message || 'Repurposing failed', 'error');
            document.getElementById('resultsLoading').classList.add('hidden');
            document.getElementById('resultsEmpty').classList.remove('hidden');
        }

        Utils.btnLoading(btn, false);
    }

    function renderResults(resultsData) {
        document.getElementById('resultsLoading').classList.add('hidden');
        const container = document.getElementById('resultsContainer');
        container.classList.remove('hidden');

        // Loop through results object
        for (const [platform, content] of Object.entries(resultsData)) {
            // Find emoji for platform
            const card = Array.from(document.querySelectorAll('.platform-checkbox-card')).find(c => c.querySelector('input').value === platform);
            const emoji = card ? card.querySelector('span').textContent : '📱';
            
            const contentHtml = Utils.markdownToHtml(content);
            const rawContent = Utils.escapeHtml(content);

            const div = document.createElement('div');
            div.className = 'repurposed-item animate-in';
            div.innerHTML = `
                <div class="repurposed-header">
                    <div class="repurposed-platform">${emoji} ${platform}</div>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn btn-ghost btn-sm" onclick="Utils.copyToClipboard(this.closest('.repurposed-item').querySelector('.raw-content').value)">📋 Copy</button>
                        <button class="btn btn-primary btn-sm" onclick="saveRepurposedContent(this, '${platform}', this.closest('.repurposed-item').querySelector('.raw-content').value)">💾 Save</button>
                    </div>
                </div>
                <div class="repurposed-content">
                    ${contentHtml}
                    <textarea class="raw-content" style="display:none;">${rawContent}</textarea>
                </div>
            `;
            container.appendChild(div);
        }
    }
    
    async function saveRepurposedContent(btn, platform, content) {
        // Unescape the raw content
        const parser = new DOMParser();
        const unescapedStr = parser.parseFromString(content, 'text/html').documentElement.textContent;
        
        Utils.btnLoading(btn, true);
        
        try {
            // Using the generator API's save endpoint since it works for generic content
            const result = await API.post('api/creator/generate.php', {
                action: 'save',
                title: `${platform} Repurposed Post`,
                content: unescapedStr,
                content_type: 'repurposed',
                platform: platform,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                Utils.toast('Saved to library!', 'success');
                btn.innerHTML = '✓ Saved';
                btn.className = 'btn btn-success btn-sm';
                btn.disabled = true;
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to save', 'error');
            Utils.btnLoading(btn, false);
        }
    }
</script>
</body>
</html>
