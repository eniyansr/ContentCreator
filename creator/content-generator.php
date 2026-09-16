<?php
/**
 * CreatorAI - Content Generator Page
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

// Pre-fill idea if passed via query string
$ideaId = (int)($_GET['idea_id'] ?? 0);
$selectedIdea = null;
if ($ideaId) {
    $selectedIdea = $d->fetch("SELECT * FROM content_ideas WHERE id = ? AND user_id = ?", [$ideaId, $userId]);
}

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
    <title>Generate Content – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/generator.css'); ?>">
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
                <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">✍️</span> Generate Content</a>
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
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <h2 class="page-title">Content Generator</h2>
            </div>
        </header>

        <div class="page-content">
            <div class="generator-layout">
                
                <!-- Options Panel -->
                <div class="generator-options-panel">
                    <div class="generator-panel-header">
                        <h3 class="generator-panel-title">🛠️ Generation Settings</h3>
                    </div>
                    
                    <div class="generator-panel-body">
                        <!-- What to create -->
                        <div class="form-group">
                            <label class="form-label">What do you want to create?</label>
                            <div class="content-type-grid" id="contentTypeGrid">
                                <label class="content-type-card selected">
                                    <input type="radio" name="content_type" value="script" checked>
                                    <div class="content-type-icon">🎬</div>
                                    <div class="content-type-name">Full Script</div>
                                </label>
                                <label class="content-type-card">
                                    <input type="radio" name="content_type" value="caption">
                                    <div class="content-type-icon">📱</div>
                                    <div class="content-type-name">Social Caption</div>
                                </label>
                                <label class="content-type-card">
                                    <input type="radio" name="content_type" value="outline">
                                    <div class="content-type-icon">📝</div>
                                    <div class="content-type-name">Outline / Plan</div>
                                </label>
                                <label class="content-type-card">
                                    <input type="radio" name="content_type" value="hooks">
                                    <div class="content-type-icon">🎣</div>
                                    <div class="content-type-name">Hooks / Intros</div>
                                </label>
                                <label class="content-type-card">
                                    <input type="radio" name="content_type" value="title">
                                    <div class="content-type-icon">✨</div>
                                    <div class="content-type-name">Titles / Headlines</div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Topic / Idea -->
                        <div class="form-group">
                            <label class="form-label">Topic or Idea <span class="required">*</span></label>
                            
                            <?php if ($selectedIdea): ?>
                            <!-- Using a saved idea -->
                            <div class="reference-item">
                                <div class="reference-item-content">
                                    <div class="reference-item-title">💡 <?php echo e($selectedIdea['title']); ?></div>
                                    <div class="reference-item-desc"><?php echo e($selectedIdea['description']); ?></div>
                                </div>
                                <button class="reference-item-remove" onclick="window.location.href='?'" title="Remove idea">×</button>
                            </div>
                            <input type="hidden" id="ideaId" value="<?php echo $selectedIdea['id']; ?>">
                            <input type="hidden" id="topicInput" value="<?php echo e($selectedIdea['title']); ?>">
                            <?php else: ?>
                            <!-- Custom topic -->
                            <input type="hidden" id="ideaId" value="0">
                            <textarea id="topicInput" class="form-textarea" rows="3" placeholder="What should this content be about? Be as specific as you like..."></textarea>
                            <div class="form-hint" style="text-align: right;"><a href="<?php echo base_url('creator/ideas.php'); ?>">Or select from saved ideas →</a></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Target Platform -->
                        <div class="form-group">
                            <label class="form-label">Target Platform</label>
                            <select id="platformInput" class="form-select">
                                <?php 
                                $preselectPlatform = $selectedIdea ? $selectedIdea['platform'] : (empty($platformList) ? '' : $platformList[0]);
                                ?>
                                <?php foreach ($platformList as $p): ?>
                                <option value="<?php echo e($p); ?>" <?php echo $p === $preselectPlatform ? 'selected' : ''; ?>><?php echo e($p); ?></option>
                                <?php endforeach; ?>
                                <option value="YouTube" <?php echo $preselectPlatform === 'YouTube' ? 'selected' : ''; ?>>YouTube</option>
                                <option value="Instagram" <?php echo $preselectPlatform === 'Instagram' ? 'selected' : ''; ?>>Instagram Reels/Post</option>
                                <option value="TikTok" <?php echo $preselectPlatform === 'TikTok' ? 'selected' : ''; ?>>TikTok</option>
                                <option value="LinkedIn" <?php echo $preselectPlatform === 'LinkedIn' ? 'selected' : ''; ?>>LinkedIn</option>
                                <option value="X/Twitter" <?php echo $preselectPlatform === 'X/Twitter' ? 'selected' : ''; ?>>X / Twitter</option>
                                <option value="General" <?php echo $preselectPlatform === 'General' ? 'selected' : ''; ?>>General / Multi-purpose</option>
                            </select>
                        </div>
                        
                        <!-- Length & Tone -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
                            <div class="form-group mb-0">
                                <label class="form-label">Length</label>
                                <select id="lengthInput" class="form-select">
                                    <option value="short">Short & Punchy</option>
                                    <option value="medium" selected>Medium / Standard</option>
                                    <option value="detailed">Long & Detailed</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Tone (Override)</label>
                                <select id="toneInput" class="form-select">
                                    <option value="default">My Default Brand Voice</option>
                                    <option value="highly professional">Highly Professional</option>
                                    <option value="very casual">Very Casual & Raw</option>
                                    <option value="funny and sarcastic">Funny / Sarcastic</option>
                                    <option value="educational and academic">Educational / Academic</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="generator-panel-footer">
                        <button class="btn btn-primary btn-lg w-full" id="generateBtn" onclick="generateContent()">
                            ✨ Generate Content
                        </button>
                    </div>
                </div>
                
                <!-- Main Editor Panel -->
                <div class="generator-editor-panel">
                    <div class="editor-header">
                        <div class="editor-tabs">
                            <button class="editor-tab active">Preview / Result</button>
                        </div>
                        <div class="editor-actions">
                            <button class="btn btn-secondary btn-sm" id="copyBtn" onclick="copyResult()" disabled>📋 Copy</button>
                            <button class="btn btn-success btn-sm" id="saveBtn" onclick="saveResult()" disabled>💾 Save to Library</button>
                        </div>
                    </div>
                    
                    <div class="editor-body">
                        <!-- Empty State -->
                        <div class="editor-empty" id="editorEmpty">
                            <div class="editor-empty-icon">✍️</div>
                            <h3>Ready to Create</h3>
                            <p>Configure your settings on the left and click Generate.</p>
                        </div>
                        
                        <!-- Loading State -->
                        <div class="editor-loading hidden" id="editorLoading">
                            <div class="loading-spinner"></div>
                            <p>AI is writing your content...</p>
                            <p style="font-size: var(--font-size-sm); color: var(--text-muted); font-weight: 400; margin-top: var(--space-2);">Applying your creator profile, brand voice, and niche data.</p>
                        </div>
                        
                        <!-- Result Content -->
                        <div class="result-content hidden" id="resultContent">
                            <!-- Rendered markdown output goes here -->
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>
</div>

<!-- Save Modal -->
<div class="modal-backdrop" id="saveModal-backdrop"></div>
<div class="modal" id="saveModal">
    <div class="modal-header">
        <h3 class="modal-title">Save to Library</h3>
        <button class="modal-close" onclick="Utils.closeModal('saveModal')">×</button>
    </div>
    <div class="form-group">
        <label class="form-label">Title</label>
        <input type="text" id="saveTitle" class="form-input" placeholder="e.g. YouTube Script - Morning Routine">
    </div>
    <div style="display: flex; justify-content: flex-end; gap: var(--space-3); margin-top: var(--space-6);">
        <button class="btn btn-secondary" onclick="Utils.closeModal('saveModal')">Cancel</button>
        <button class="btn btn-primary" id="confirmSaveBtn" onclick="confirmSave()">Save Content</button>
    </div>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    let currentRawContent = '';

    document.addEventListener('DOMContentLoaded', () => {
        console.log("Running content-generator.php v2 - cache cleared");
        API.init('<?php echo $baseUrl; ?>');
        
        // Handle content type grid selection
        document.querySelectorAll('.content-type-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.content-type-card').forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');
                card.querySelector('input').checked = true;
            });
        });
    });

    async function generateContent() {
        const topic = document.getElementById('topicInput').value.trim();
        const ideaId = document.getElementById('ideaId').value;
        
        if (!topic && ideaId == "0") {
            Utils.toast('Please provide a topic to generate content for.', 'error');
            document.getElementById('topicInput').focus();
            return;
        }
        
        const btn = document.getElementById('generateBtn');
        const contentType = document.querySelector('input[name="content_type"]:checked').value;
        const platform = document.getElementById('platformInput').value;
        const length = document.getElementById('lengthInput').value;
        const tone = document.getElementById('toneInput').value;
        
        // UI Updates
        Utils.btnLoading(btn, true);
        document.getElementById('editorEmpty').classList.add('hidden');
        document.getElementById('resultContent').classList.add('hidden');
        document.getElementById('editorLoading').classList.remove('hidden');
        document.getElementById('copyBtn').disabled = true;
        document.getElementById('saveBtn').disabled = true;
        
        try {
            const result = await API.post('api/creator/generate.php', {
                action: 'generate',
                content_type: contentType,
                platform: platform,
                topic: topic,
                idea_id: ideaId,
                length: length,
                tone: tone,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                currentRawContent = result.content;
                
                // Render markdown to HTML
                document.getElementById('resultContent').innerHTML = Utils.markdownToHtml(currentRawContent);
                
                // Show result
                document.getElementById('editorLoading').classList.add('hidden');
                document.getElementById('resultContent').classList.remove('hidden');
                
                // Enable actions
                document.getElementById('copyBtn').disabled = false;
                document.getElementById('saveBtn').disabled = false;
                
                // Set default save title
                const typeName = document.querySelector('.content-type-card.selected .content-type-name').textContent;
                const shortTopic = topic.length > 30 ? topic.substring(0, 30) + '...' : topic;
                document.getElementById('saveTitle').value = `${platform} ${typeName} - ${shortTopic}`;
            }
        } catch (error) {
            Utils.toast(error.message || 'Generation failed', 'error');
            document.getElementById('editorLoading').classList.add('hidden');
            if (currentRawContent) {
                document.getElementById('resultContent').classList.remove('hidden');
            } else {
                document.getElementById('editorEmpty').classList.remove('hidden');
            }
        }
        
        Utils.btnLoading(btn, false);
    }
    
    function copyResult() {
        if (!currentRawContent) return;
        Utils.copyToClipboard(currentRawContent);
    }
    
    function saveResult() {
        if (!currentRawContent) return;
        Utils.openModal('saveModal');
    }
    
    async function confirmSave() {
        const title = document.getElementById('saveTitle').value.trim();
        if (!title) {
            Utils.toast('Please enter a title', 'error');
            return;
        }
        
        const btn = document.getElementById('confirmSaveBtn');
        Utils.btnLoading(btn, true);
        
        try {
            const result = await API.post('api/creator/generate.php', {
                action: 'save',
                title: title,
                content: currentRawContent,
                content_type: document.querySelector('input[name="content_type"]:checked').value,
                platform: document.getElementById('platformInput').value,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                Utils.toast('Content saved successfully!', 'success');
                Utils.closeModal('saveModal');
                
                // Update save button visually
                const saveBtn = document.getElementById('saveBtn');
                saveBtn.innerHTML = '✓ Saved';
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-secondary');
                saveBtn.disabled = true;
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to save', 'error');
        }
        
        Utils.btnLoading(btn, false);
    }
</script>
</body>
</html>
