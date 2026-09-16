<?php
/**
 * CreatorAI - Analytics Page
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

// Fetch platforms for filter
$platforms = $d->fetchAll("SELECT platform_name FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/analytics.css'); ?>">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="<?php echo base_url('creator/analyzer.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">🔍</span> Content Analyzer</a>
            </div>
            <div class="sidebar-section">
                <div class="sidebar-section-title">Manage</div>
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">📈</span> Analytics</a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📅</span> Calendar</a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <h2 class="page-title">Performance Analytics</h2>
            </div>
            <div class="top-header-right">
                <span class="platform-badge" style="background: var(--bg-tertiary); padding: 4px 12px;">Mock Data Active</span>
            </div>
        </header>

        <div class="page-content">
            <div class="analytics-layout">
                
                <!-- Controls -->
                <div class="analytics-controls">
                    <div class="form-group mb-0" style="min-width: 200px;">
                        <select class="form-select" id="platformSelect" onchange="loadAnalytics()">
                            <option value="all">All Platforms</option>
                            <?php foreach ($platforms as $p): ?>
                            <option value="<?php echo e($p['platform_name']); ?>"><?php echo e($p['platform_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="analytics-date-range">
                        <button class="date-btn" data-range="7d" onclick="setDateRange('7d', this)">7 Days</button>
                        <button class="date-btn active" data-range="30d" onclick="setDateRange('30d', this)">30 Days</button>
                        <button class="date-btn" data-range="90d" onclick="setDateRange('90d', this)">90 Days</button>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="stats-overview-grid">
                    <div class="stat-box primary">
                        <div class="stat-box-title">👁️ Total Views</div>
                        <div class="stat-box-value" id="statViews">--</div>
                        <div class="stat-box-change positive" id="statViewsChange">--</div>
                    </div>
                    <div class="stat-box success">
                        <div class="stat-box-title">🔥 Avg Engagement</div>
                        <div class="stat-box-value" id="statEngagement">--</div>
                        <div class="stat-box-change positive" id="statEngagementChange">--</div>
                    </div>
                    <div class="stat-box accent">
                        <div class="stat-box-title">👥 New Followers</div>
                        <div class="stat-box-value" id="statFollowers">--</div>
                        <div class="stat-box-change positive" id="statFollowersChange">--</div>
                    </div>
                    <div class="stat-box warning">
                        <div class="stat-box-title">📝 Posts Published</div>
                        <div class="stat-box-value" id="statPosts">--</div>
                        <div class="stat-box-change" id="statPostsChange" style="color: var(--text-muted);">--</div>
                    </div>
                </div>
                
                <!-- Charts and Insights -->
                <div class="charts-grid">
                    <!-- Main Chart -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <div class="chart-title">Views & Growth</div>
                        </div>
                        <div class="chart-container">
                            <canvas id="mainChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- AI Insights -->
                    <div class="chart-card" style="padding: 0; background: transparent; border: none;">
                        <div class="chart-header" style="margin-bottom: var(--space-4);">
                            <div class="chart-title">🤖 AI Insights</div>
                        </div>
                        <div class="insights-card">
                            <div id="insightsContainer">
                                <!-- Populated via JS -->
                                <div class="loading-spinner" style="margin: 0 auto;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Content -->
                <div class="chart-card">
                    <div class="chart-header">
                        <div class="chart-title">Top Performing Content</div>
                    </div>
                    <table class="top-content-table">
                        <thead>
                            <tr>
                                <th>Content Title</th>
                                <th>Platform</th>
                                <th>Views</th>
                                <th>Engagement</th>
                            </tr>
                        </thead>
                        <tbody id="topContentBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </main>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    let currentRange = '30d';
    let chartInstance = null;

    document.addEventListener('DOMContentLoaded', () => {
        API.init('<?php echo $baseUrl; ?>');
        
        // Setup Chart defaults for dark/light mode
        Chart.defaults.color = '#94a3b8'; // text-muted
        Chart.defaults.font.family = "'Inter', sans-serif";
        
        loadAnalytics();
    });

    function setDateRange(range, btn) {
        document.querySelectorAll('.date-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentRange = range;
        loadAnalytics();
    }

    async function loadAnalytics() {
        const platform = document.getElementById('platformSelect').value;
        
        try {
            const result = await API.get('api/creator/analytics.php', { 
                range: currentRange,
                platform: platform
            });
            
            if (result.success) {
                renderDashboard(result);
            }
        } catch (error) {
            Utils.toast('Failed to load analytics data', 'error');
        }
    }

    function renderDashboard(data) {
        // 1. Update Overview Stats
        const formatNumber = (num) => {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        };

        document.getElementById('statViews').textContent = formatNumber(data.overview.views);
        document.getElementById('statViewsChange').textContent = data.overview.views_change;
        
        document.getElementById('statEngagement').textContent = data.overview.engagement;
        document.getElementById('statEngagementChange').textContent = data.overview.engagement_change;
        
        document.getElementById('statFollowers').textContent = formatNumber(data.overview.followers);
        document.getElementById('statFollowersChange').textContent = data.overview.followers_change;
        
        document.getElementById('statPosts').textContent = data.overview.posts;
        document.getElementById('statPostsChange').textContent = data.overview.posts_change;

        // 2. Render Chart
        const ctx = document.getElementById('mainChart').getContext('2d');
        
        if (chartInstance) {
            chartInstance.destroy();
        }
        
        // Gradient for line chart
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(108, 92, 231, 0.5)');
        gradient.addColorStop(1, 'rgba(108, 92, 231, 0.0)');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.chart.labels,
                datasets: [{
                    label: 'Views',
                    data: data.chart.views,
                    borderColor: '#6c5ce7',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#6c5ce7',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        borderColor: '#334155',
                        borderWidth: 1,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawBorder: false }
                    },
                    y: {
                        grid: { color: '#334155', drawBorder: false },
                        beginAtZero: true
                    }
                }
            }
        });

        // 3. Render Top Content
        const tbody = document.getElementById('topContentBody');
        tbody.innerHTML = '';
        
        data.top_content.forEach(item => {
            let emoji = '📱';
            if (item.platform.includes('YouTube')) emoji = '▶️';
            if (item.platform.includes('Instagram')) emoji = '📸';
            if (item.platform.includes('TikTok')) emoji = '🎵';
            
            tbody.innerHTML += `
                <tr>
                    <td class="content-title-cell">
                        <span class="platform-icon">${emoji}</span>
                        ${Utils.escapeHtml(item.title)}
                    </td>
                    <td>${Utils.escapeHtml(item.platform)}</td>
                    <td style="font-weight: 600;">${formatNumber(item.views)}</td>
                    <td style="color: var(--success); font-weight: 600;">${item.eng}</td>
                </tr>
            `;
        });

        // 4. Render Insights
        const insightsContainer = document.getElementById('insightsContainer');
        insightsContainer.innerHTML = '';
        
        data.insights.forEach(insight => {
            let icon = '💡';
            if (insight.type === 'positive') icon = '📈';
            if (insight.type === 'warning') icon = '⚠️';
            
            insightsContainer.innerHTML += `
                <div class="insight-item">
                    <div class="insight-icon">${icon}</div>
                    <div class="insight-content">
                        <h4>${Utils.escapeHtml(insight.title)}</h4>
                        <p>${Utils.escapeHtml(insight.desc)}</p>
                    </div>
                </div>
            `;
        });
    }
</script>
</body>
</html>
