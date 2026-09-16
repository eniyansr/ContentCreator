<?php
/**
 * CreatorAI - Content Analyzer Page
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

// Fetch user platforms
$platforms = $d->fetchAll("SELECT platform_name FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
$platformList = array_column($platforms, 'platform_name');

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Analyzer – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/analyzer.css'); ?>">
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
                <a href="<?php echo base_url('creator/repurpose.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔄</span> Repurpose</a>
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">🔍</span> Content Analyzer</a>
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
                <h2 class="page-title">Content Analyzer</h2>
            </div>
        </header>

        <div class="page-content">
            <div class="analyzer-layout">
                
                <!-- Input Panel -->
                <div class="analyzer-input-panel">
                    <div class="analyzer-panel-header">
                        <div class="analyzer-panel-title">📝 Editor</div>
                        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('contentInput').value=''">Clear</button>
                    </div>
                    
                    <div class="analyzer-form">
                        <div class="analyzer-textarea-container">
                            <textarea id="contentInput" class="analyzer-textarea" placeholder="Paste your script, caption, or outline here to analyze its viral potential..."></textarea>
                        </div>
                    </div>
                    
                    <div class="analyzer-controls">
                        <div class="analyzer-controls-left">
                            <div class="form-group mb-0" style="min-width: 150px;">
                                <select id="platformInput" class="form-select">
                                    <?php foreach ($platformList as $p): ?>
                                    <option value="<?php echo e($p); ?>"><?php echo e($p); ?></option>
                                    <?php endforeach; ?>
                                    <?php if(empty($platformList)): ?>
                                    <option value="YouTube">YouTube</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="TikTok">TikTok</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group mb-0" style="min-width: 150px;">
                                <select id="formatInput" class="form-select">
                                    <option value="script">Video Script</option>
                                    <option value="caption">Social Caption</option>
                                    <option value="blog">Blog / Article</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary" id="analyzeBtn" onclick="analyzeContent()">
                            🔍 Analyze Content
                        </button>
                    </div>
                </div>
                
                <!-- Results Panel -->
                <div class="analyzer-results-panel">
                    <div class="analyzer-panel-header">
                        <div class="analyzer-panel-title">📊 Analysis Report</div>
                        <!-- Rewrite options shown only after analysis -->
                        <div class="dropdown" id="rewriteDropdown" style="display: none;">
                            <button class="btn btn-secondary btn-sm" onclick="this.nextElementSibling.classList.toggle('show')">✨ Auto-Improve ▼</button>
                            <div class="dropdown-menu" style="right: 0; min-width: 180px;">
                                <a href="#" class="dropdown-item" onclick="rewriteContent('general'); return false;">Improve Overall</a>
                                <a href="#" class="dropdown-item" onclick="rewriteContent('hook'); return false;">Make Hook Stronger</a>
                                <a href="#" class="dropdown-item" onclick="rewriteContent('engagement'); return false;">Boost Engagement</a>
                                <a href="#" class="dropdown-item" onclick="rewriteContent('brand'); return false;">Align with Brand</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="analyzer-results-body">
                        <!-- Empty State -->
                        <div class="analyzer-empty" id="analyzerEmpty">
                            <div class="analyzer-empty-icon">📈</div>
                            <h3 style="color: var(--text-primary); margin-bottom: var(--space-2);">No Data Yet</h3>
                            <p style="color: var(--text-secondary); font-size: var(--font-size-sm);">Paste your content and click Analyze to get AI feedback and viral scoring.</p>
                        </div>
                        
                        <!-- Loading State -->
                        <div class="analyzer-loading hidden" id="analyzerLoading">
                            <div class="loading-spinner"></div>
                            <p style="color: var(--primary-light); font-weight: 600; margin-top: var(--space-4);">Analyzing content metrics...</p>
                        </div>
                        
                        <!-- Results Container -->
                        <div id="resultsContainer" class="hidden">
                            
                            <!-- Score Overview -->
                            <div class="score-overview">
                                <div class="score-circle" id="overallScoreCircle">
                                    <div class="score-value" id="overallScore">0</div>
                                    <div class="score-max">/ 100</div>
                                </div>
                                <div class="score-label" id="scoreLabel">Good Potential</div>
                                <div class="score-desc" id="scoreDesc">Analysis complete. Check metrics below.</div>
                            </div>
                            
                            <!-- Metrics -->
                            <div class="metrics-container">
                                <!-- Hook -->
                                <div class="metric-item" id="metricHook">
                                    <div class="metric-header">
                                        <div class="metric-name">🎣 Hook Strength</div>
                                        <div class="metric-score">0/100</div>
                                    </div>
                                    <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: 0%"></div></div>
                                </div>
                                <!-- Engagement -->
                                <div class="metric-item" id="metricEngage">
                                    <div class="metric-header">
                                        <div class="metric-name">🔥 Engagement Potential</div>
                                        <div class="metric-score">0/100</div>
                                    </div>
                                    <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: 0%"></div></div>
                                </div>
                                <!-- Clarity -->
                                <div class="metric-item" id="metricClarity">
                                    <div class="metric-header">
                                        <div class="metric-name">👁️ Clarity & Pacing</div>
                                        <div class="metric-score">0/100</div>
                                    </div>
                                    <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: 0%"></div></div>
                                </div>
                                <!-- Brand -->
                                <div class="metric-item" id="metricBrand">
                                    <div class="metric-header">
                                        <div class="metric-name">👤 Brand Alignment</div>
                                        <div class="metric-score">0/100</div>
                                    </div>
                                    <div class="metric-bar-bg"><div class="metric-bar-fill" style="width: 0%"></div></div>
                                </div>
                            </div>
                            
                            <!-- Suggestions -->
                            <div class="suggestions-container">
                                <div class="suggestions-header">Actionable Improvements</div>
                                <div id="suggestionsList">
                                    <!-- Populated via JS -->
                                </div>
                            </div>
                            
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
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
            }
        });
    });

    async function analyzeContent() {
        const content = document.getElementById('contentInput').value.trim();
        if (!content || content.length < 50) {
            Utils.toast('Please enter more content to analyze.', 'error');
            return;
        }

        const btn = document.getElementById('analyzeBtn');
        const platform = document.getElementById('platformInput').value;
        const format = document.getElementById('formatInput').value;

        // UI State
        Utils.btnLoading(btn, true);
        document.getElementById('analyzerEmpty').classList.add('hidden');
        document.getElementById('resultsContainer').classList.add('hidden');
        document.getElementById('analyzerLoading').classList.remove('hidden');
        document.getElementById('rewriteDropdown').style.display = 'none';

        try {
            const result = await API.post('api/creator/analyze.php', {
                action: 'analyze',
                content: content,
                platform: platform,
                format: format,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });

            if (result.success) {
                renderResults(result);
            }
        } catch (error) {
            Utils.toast(error.message || 'Analysis failed', 'error');
            document.getElementById('analyzerLoading').classList.add('hidden');
            document.getElementById('analyzerEmpty').classList.remove('hidden');
        }

        Utils.btnLoading(btn, false);
    }

    function renderResults(data) {
        document.getElementById('analyzerLoading').classList.add('hidden');
        document.getElementById('resultsContainer').classList.remove('hidden');
        document.getElementById('rewriteDropdown').style.display = 'block';

        // Overall Score
        const overall = data.overall_score;
        document.getElementById('overallScore').textContent = overall;
        
        const circle = document.getElementById('overallScoreCircle');
        const label = document.getElementById('scoreLabel');
        
        circle.className = 'score-circle'; // reset
        if (overall >= 85) { circle.classList.add('excellent'); label.textContent = 'Excellent Viral Potential'; }
        else if (overall >= 70) { circle.classList.add('good'); label.textContent = 'Good Potential'; }
        else if (overall >= 50) { circle.classList.add('fair'); label.textContent = 'Needs Improvement'; }
        else { circle.classList.add('poor'); label.textContent = 'Poor Performance Likely'; }
        
        document.getElementById('scoreDesc').textContent = data.summary || 'Review the metrics below to improve your content.';

        // Metrics
        updateMetric('metricHook', data.metrics.hook_strength);
        updateMetric('metricEngage', data.metrics.engagement_potential);
        updateMetric('metricClarity', data.metrics.clarity);
        updateMetric('metricBrand', data.metrics.brand_alignment);

        // Suggestions
        const suggList = document.getElementById('suggestionsList');
        suggList.innerHTML = '';
        
        if (data.improvements && Array.isArray(data.improvements)) {
            data.improvements.forEach(imp => {
                let icon = '💡';
                if (imp.type === 'hook') icon = '🎣';
                if (imp.type === 'retention') icon = '⏱️';
                if (imp.type === 'cta') icon = '🎯';
                
                suggList.innerHTML += `
                    <div class="suggestion-item">
                        <div class="suggestion-icon">${icon}</div>
                        <div class="suggestion-content">
                            <h4>${imp.type.charAt(0).toUpperCase() + imp.type.slice(1)} Improvement</h4>
                            <p>${Utils.escapeHtml(imp.suggestion)}</p>
                        </div>
                    </div>
                `;
            });
        }
    }

    function updateMetric(elementId, score) {
        const el = document.getElementById(elementId);
        if (!el) return;
        
        el.className = 'metric-item'; // reset
        if (score >= 85) el.classList.add('metric-excellent');
        else if (score >= 70) el.classList.add('metric-good');
        else if (score >= 50) el.classList.add('metric-fair');
        else el.classList.add('metric-poor');
        
        el.querySelector('.metric-score').textContent = score + '/100';
        
        // Timeout to allow transition to animate from 0
        setTimeout(() => {
            el.querySelector('.metric-bar-fill').style.width = score + '%';
        }, 100);
    }

    async function rewriteContent(focus) {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
        
        const content = document.getElementById('contentInput').value.trim();
        const platform = document.getElementById('platformInput').value;
        const format = document.getElementById('formatInput').value;
        
        document.getElementById('analyzerLoading').classList.remove('hidden');
        document.getElementById('resultsContainer').classList.add('hidden');
        
        try {
            const result = await API.post('api/creator/analyze.php', {
                action: 'rewrite',
                content: content,
                platform: platform,
                format: format,
                focus: focus,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });

            if (result.success && result.rewritten_content) {
                document.getElementById('contentInput').value = result.rewritten_content;
                Utils.toast('Content rewritten successfully!', 'success');
                // Re-analyze automatically
                analyzeContent();
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to rewrite', 'error');
            document.getElementById('analyzerLoading').classList.add('hidden');
            document.getElementById('resultsContainer').classList.remove('hidden');
        }
    }
</script>
</body>
</html>
