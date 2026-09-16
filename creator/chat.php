<?php
/**
 * CreatorAI - AI Chat Page
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
$sessions = $d->fetchAll("SELECT id, title, last_message_at FROM chat_sessions WHERE user_id = ? AND is_active = 1 ORDER BY last_message_at DESC LIMIT 50", [$userId]);
$unreadNotifications = $d->fetchColumn("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);

// Pre-load session if specified
$activeSessionId = (int)($_GET['session'] ?? 0);
$activeMessages = [];
if ($activeSessionId) {
    $session = $d->fetch("SELECT * FROM chat_sessions WHERE id = ? AND user_id = ?", [$activeSessionId, $userId]);
    if ($session) {
        $activeMessages = $d->fetchAll("SELECT role, message AS content, created_at FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC", [$activeSessionId]);
    } else {
        $activeSessionId = 0;
    }
}

$baseUrl = APP_URL;
$niche = $profile['primary_niche'] ?? 'your niche';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/chat.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar (reuse dashboard sidebar) -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-logo">
                <div class="sidebar-logo-icon">✦</div>
                CreatorAI
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <a href="<?php echo base_url('creator/dashboard.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📊</span> Dashboard</a>
                <a href="<?php echo base_url('creator/chat.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">💬</span> AI Creator Chat</a>
                <a href="<?php echo base_url('creator/ideas.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">💡</span> Content Ideas</a>
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

    <main class="main-content" style="display: flex; flex-direction: column;">
        <header class="top-header">
            <div class="top-header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
                <h2 class="page-title">AI Creator Chat</h2>
            </div>
            <div class="top-header-right">
                <button class="header-notification" onclick="window.location.href='<?php echo base_url('creator/notifications.php'); ?>'">🔔<?php if ($unreadNotifications > 0): ?><span class="notification-dot"></span><?php endif; ?></button>
            </div>
        </header>

        <div class="chat-layout">
            <!-- Chat Sessions Sidebar -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <button class="btn btn-primary chat-new-btn" onclick="newConversation()">
                        ✦ New Conversation
                    </button>
                </div>
                <div class="chat-search">
                    <input type="text" placeholder="Search conversations..." onkeyup="filterSessions(this.value)">
                </div>
                <div class="chat-list" id="chatList">
                    <?php foreach ($sessions as $s): ?>
                    <a href="?session=<?php echo $s['id']; ?>" class="chat-list-item <?php echo $s['id'] == $activeSessionId ? 'active' : ''; ?>" data-session="<?php echo $s['id']; ?>">
                        <div class="chat-list-item-icon">💬</div>
                        <div class="chat-list-item-info">
                            <div class="chat-list-item-title"><?php echo e($s['title']); ?></div>
                            <div class="chat-list-item-time"><?php echo $s['last_message_at'] ? timeAgo($s['last_message_at']) : 'New'; ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php if (empty($sessions)): ?>
                    <div class="empty-state" style="padding: var(--space-8) var(--space-4);">
                        <p style="font-size: var(--font-size-xs); color: var(--text-muted);">No conversations yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Main -->
            <div class="chat-main">
                <?php if ($activeSessionId && !empty($activeMessages)): ?>
                <!-- Active conversation -->
                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($activeMessages as $msg): ?>
                    <div class="chat-message <?php echo $msg['role'] === 'user' ? 'user' : 'ai'; ?>">
                        <div class="chat-message-avatar"><?php echo $msg['role'] === 'user' ? strtoupper(substr($displayName, 0, 1)) : '✦'; ?></div>
                        <div>
                            <div class="chat-message-bubble"><?php echo $msg['role'] === 'assistant' ? renderMarkdown($msg['content']) : e($msg['content']); ?></div>
                            <?php if ($msg['role'] === 'assistant'): ?>
                            <div class="chat-message-actions">
                                <button class="chat-action-btn" onclick="Utils.copyToClipboard(this.closest('.chat-message').querySelector('.chat-message-bubble').innerText)">📋 Copy</button>
                                <button class="chat-action-btn" onclick="saveContent(this.closest('.chat-message').querySelector('.chat-message-bubble').innerText)">💾 Save</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- Welcome State -->
                <div class="chat-welcome" id="chatWelcome">
                    <div class="chat-welcome-icon">✦</div>
                    <h2>Hi <?php echo e($displayName); ?>!</h2>
                    <p>I'm your personalized AI assistant for <strong><?php echo e($niche); ?></strong> content. How can I help you today?</p>
                    <div class="chat-suggestions">
                        <div class="chat-suggestion-card" onclick="sendSuggestion('Give me 5 content ideas for my <?php echo e($niche); ?> niche')">
                            <h4>💡 Content Ideas</h4>
                            <p>Generate ideas for my niche</p>
                        </div>
                        <div class="chat-suggestion-card" onclick="sendSuggestion('Write a YouTube video script about <?php echo e($niche); ?>')">
                            <h4>✍️ Write a Script</h4>
                            <p>Create a video script</p>
                        </div>
                        <div class="chat-suggestion-card" onclick="sendSuggestion('Create Instagram captions for my <?php echo e($niche); ?> content')">
                            <h4>📱 Social Captions</h4>
                            <p>Captions for my posts</p>
                        </div>
                        <div class="chat-suggestion-card" onclick="sendSuggestion('What are the best hashtags for <?php echo e($niche); ?> content?')">
                            <h4>🏷️ Hashtag Strategy</h4>
                            <p>Optimized hashtags</p>
                        </div>
                    </div>
                </div>
                <div class="chat-messages d-none" id="chatMessages"></div>
                <?php endif; ?>

                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="chat-message-avatar" style="background: var(--gradient-primary); color: white; width: 28px; height: 28px; font-size: 0.7rem;">✦</div>
                    <div class="typing-dots"><span></span><span></span><span></span></div>
                    <span class="typing-text">CreatorAI is thinking...</span>
                </div>

                <!-- Input Area -->
                <div class="chat-input-area">
                    <div class="chat-input-container">
                        <textarea class="chat-input" id="chatInput" placeholder="Ask CreatorAI anything about content creation..." rows="1" onkeydown="handleKeyDown(event)"></textarea>
                        <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()">➤</button>
                    </div>
                    <div class="chat-input-hint">
                        CreatorAI uses your profile to personalize responses. Press Enter to send, Shift+Enter for new line.
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    let currentSessionId = <?php echo $activeSessionId; ?>;
    let isGenerating = false;

    document.addEventListener('DOMContentLoaded', () => {
        API.init('<?php echo $baseUrl; ?>');
        autoResizeTextarea();
        scrollToBottom();
    });

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function autoResizeTextarea() {
        const ta = document.getElementById('chatInput');
        ta.addEventListener('input', () => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 150) + 'px';
        });
    }

    function handleKeyDown(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function scrollToBottom() {
        const container = document.getElementById('chatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    }

    function newConversation() {
        window.location.href = '<?php echo base_url("creator/chat.php"); ?>';
    }

    function filterSessions(query) {
        document.querySelectorAll('.chat-list-item').forEach(item => {
            const title = item.querySelector('.chat-list-item-title').textContent.toLowerCase();
            item.style.display = title.includes(query.toLowerCase()) ? '' : 'none';
        });
    }

    function sendSuggestion(text) {
        document.getElementById('chatInput').value = text;
        sendMessage();
    }

    async function sendMessage() {
        if (isGenerating) return;
        
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message) return;

        isGenerating = true;
        input.value = '';
        input.style.height = 'auto';
        document.getElementById('sendBtn').disabled = true;

        // Show messages area, hide welcome
        const welcome = document.getElementById('chatWelcome');
        const messages = document.getElementById('chatMessages');
        if (welcome) welcome.classList.add('d-none');
        messages.classList.remove('d-none');

        // Add user message
        appendMessage('user', message);

        // Show typing
        document.getElementById('typingIndicator').classList.add('active');
        scrollToBottom();

        try {
            const result = await API.post('api/creator/chat.php', {
                action: 'send_message',
                session_id: currentSessionId,
                message: message,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
            });

            document.getElementById('typingIndicator').classList.remove('active');

            if (result.success) {
                currentSessionId = result.session_id;
                const response = result.response;
                appendMessage('ai', response);
                
                // Update URL
                if (currentSessionId) {
                    history.replaceState(null, '', '?session=' + currentSessionId);
                }
            }
        } catch (error) {
            document.getElementById('typingIndicator').classList.remove('active');
            appendMessage('ai', 'Sorry, I encountered an error. Please try again.');
            Utils.toast(error.message || 'Failed to send message', 'error');
        }

        isGenerating = false;
        document.getElementById('sendBtn').disabled = false;
        input.focus();
    }

    function appendMessage(role, content) {
        const messages = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `chat-message ${role === 'user' ? 'user' : 'ai'}`;
        
        const avatar = role === 'user' ? '<?php echo strtoupper(substr($displayName, 0, 1)); ?>' : '✦';
        const bubbleContent = role === 'user' ? Utils.escapeHtml(content) : Utils.markdownToHtml(content);
        
        div.innerHTML = `
            <div class="chat-message-avatar">${avatar}</div>
            <div>
                <div class="chat-message-bubble">${bubbleContent}</div>
                ${role === 'ai' ? `
                <div class="chat-message-actions">
                    <button class="chat-action-btn" onclick="Utils.copyToClipboard(this.closest('.chat-message').querySelector('.chat-message-bubble').innerText)">📋 Copy</button>
                    <button class="chat-action-btn" onclick="saveContent(this.closest('.chat-message').querySelector('.chat-message-bubble').innerText)">💾 Save</button>
                </div>
                ` : ''}
            </div>
        `;
        messages.appendChild(div);
        scrollToBottom();
    }

    async function saveContent(content) {
        try {
            await API.post('api/creator/save_content.php', {
                content: content,
                content_type: 'chat_response',
                source: 'ai_chat',
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
            });
            Utils.toast('Content saved to your library!', 'success');
        } catch {
            Utils.toast('Failed to save content', 'error');
        }
    }
</script>
</body>
</html>
