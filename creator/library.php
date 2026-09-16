<?php
/**
 * CreatorAI - Content Library Page
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

// Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $idToDelete = (int)$_POST['delete_id'];
    $d->delete("DELETE FROM saved_content WHERE id = ? AND user_id = ?", [$idToDelete, $userId]);
    header('Location: ' . base_url('creator/library.php?msg=deleted'));
    exit;
}

// Fetch saved content
$search = trim($_GET['q'] ?? '');
$filterType = $_GET['type'] ?? 'all';

$query = "SELECT * FROM saved_content WHERE user_id = ?";
$params = [$userId];

if ($search) {
    $query .= " AND (title LIKE ? OR content_data LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterType !== 'all') {
    $query .= " AND content_type = ?";
    $params[] = $filterType;
}

$query .= " ORDER BY created_at DESC";
$savedContent = $d->fetchAll($query, $params);

// Get counts for filters
$counts = $d->fetchAll("SELECT content_type, COUNT(*) as cnt FROM saved_content WHERE user_id = ? GROUP BY content_type", [$userId]);
$typeCounts = [];
$totalCount = 0;
foreach ($counts as $c) {
    $typeCounts[$c['content_type']] = $c['cnt'];
    $totalCount += $c['cnt'];
}

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Library – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/library.css'); ?>">
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
                <a href="<?php echo base_url('creator/analytics.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📈</span> Analytics</a>
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📅</span> Calendar</a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <h2 class="page-title">Content Library</h2>
            </div>
            <div class="top-header-right">
                <div class="library-header-actions">
                    <form method="GET" action="" class="library-search">
                        <input type="hidden" name="type" value="<?php echo e($filterType); ?>">
                        <input type="text" name="q" placeholder="Search your content..." value="<?php echo e($search); ?>">
                    </form>
                    <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="btn btn-primary btn-sm">+ New Content</a>
                </div>
            </div>
        </header>

        <div class="page-content">
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div style="background: rgba(0, 184, 148, 0.1); color: var(--success); padding: var(--space-3); border-radius: var(--radius-md); margin-bottom: var(--space-4); border: 1px solid var(--success);">
                Content deleted successfully.
            </div>
            <?php endif; ?>

            <div class="library-filters">
                <a href="?type=all&q=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filterType === 'all' ? 'active' : ''; ?>">
                    All (<?php echo $totalCount; ?>)
                </a>
                <?php
                $types = [
                    'script' => '🎬 Scripts',
                    'caption' => '📱 Captions',
                    'title' => '✨ Titles',
                    'hooks' => '🎣 Hooks',
                    'outline' => '📝 Outlines',
                    'repurposed' => '🔄 Repurposed',
                    'chat_response' => '💬 Chat AI',
                    'text' => '📄 Text'
                ];
                foreach ($types as $key => $label):
                    $c = $typeCounts[$key] ?? 0;
                    if ($c > 0 || $filterType === $key):
                ?>
                <a href="?type=<?php echo $key; ?>&q=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filterType === $key ? 'active' : ''; ?>">
                    <?php echo $label; ?> (<?php echo $c; ?>)
                </a>
                <?php endif; endforeach; ?>
            </div>

            <div class="library-grid">
                <?php if (empty($savedContent)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1; padding: var(--space-8);">
                        <div class="empty-state-icon">📚</div>
                        <h3>Your library is empty</h3>
                        <p>Use the Generator, Chat, or Repurposer to save content here.</p>
                        <a href="<?php echo base_url('creator/content-generator.php'); ?>" class="btn btn-primary mt-4">Start Creating</a>
                    </div>
                <?php endif; ?>

                <?php foreach ($savedContent as $item): 
                    // Map type to emoji
                    $emoji = '📄';
                    switch($item['content_type']) {
                        case 'script': $emoji = '🎬'; break;
                        case 'caption': $emoji = '📱'; break;
                        case 'title': $emoji = '✨'; break;
                        case 'hooks': $emoji = '🎣'; break;
                        case 'outline': $emoji = '📝'; break;
                        case 'repurposed': $emoji = '🔄'; break;
                        case 'chat_response': $emoji = '💬'; break;
                    }
                    
                    // Create a plain text preview (strip markdown/html)
                    $preview = strip_tags(preg_replace('/#|\*|_|\[|\]/', '', $item['content_data']));
                    $preview = mb_substr($preview, 0, 150) . (mb_strlen($preview) > 150 ? '...' : '');
                ?>
                <div class="library-card">
                    <div class="library-card-header">
                        <span class="library-card-type"><?php echo $emoji; ?> <?php echo ucfirst(str_replace('_', ' ', $item['content_type'])); ?></span>
                        <span class="library-card-date"><?php echo timeAgo($item['created_at']); ?></span>
                    </div>
                    
                    <h3 class="library-card-title"><?php echo e($item['title']); ?></h3>
                    <div class="library-card-platform"><?php echo e($item['platform']); ?></div>
                    <p class="library-card-preview"><?php echo e($preview); ?></p>
                    
                    <div class="library-card-actions">
                        <div class="library-card-actions-left">
                            <button class="library-action-btn" onclick="viewContent(<?php echo htmlspecialchars(json_encode([
                                'title' => $item['title'],
                                'type' => ucfirst(str_replace('_', ' ', $item['content_type'])),
                                'platform' => $item['platform'],
                                'date' => date('M j, Y', strtotime($item['created_at'])),
                                'content' => $item['content_data']
                            ])); ?>)">👁️ View</button>
                            <a href="<?php echo base_url('creator/repurpose.php?source_id=' . $item['id']); ?>" class="library-action-btn">🔄 Repurpose</a>
                        </div>
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" class="library-action-btn" style="color: var(--error);">🗑️</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<!-- View Modal -->
<div class="modal-backdrop" id="viewModal-backdrop"></div>
<div class="modal" id="viewModal" style="max-width: 800px; width: 90%;">
    <div class="modal-header">
        <h3 class="modal-title" id="viewModalTitle">Content View</h3>
        <button class="modal-close" onclick="Utils.closeModal('viewModal')">×</button>
    </div>
    
    <div class="view-modal-meta">
        <div id="viewModalType"></div>
        <div id="viewModalPlatform"></div>
        <div id="viewModalDate"></div>
    </div>
    
    <div class="view-modal-content" id="viewModalContent">
        <!-- Rendered markdown goes here -->
    </div>
    
    <div style="display: flex; justify-content: flex-end; gap: var(--space-3); margin-top: var(--space-4);">
        <button class="btn btn-secondary" onclick="Utils.closeModal('viewModal')">Close</button>
        <button class="btn btn-primary" id="copyModalBtn">📋 Copy Content</button>
    </div>
</div>

<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    let currentViewContent = '';

    function viewContent(data) {
        document.getElementById('viewModalTitle').textContent = data.title;
        document.getElementById('viewModalType').textContent = `Type: ${data.type}`;
        document.getElementById('viewModalPlatform').textContent = `Platform: ${data.platform}`;
        document.getElementById('viewModalDate').textContent = `Created: ${data.date}`;
        
        currentViewContent = data.content;
        
        // Simple markdown parser if marked isn't loaded, otherwise use marked
        if (typeof marked !== 'undefined') {
            document.getElementById('viewModalContent').innerHTML = marked.parse(data.content);
        } else {
            document.getElementById('viewModalContent').innerHTML = Utils.markdownToHtml(data.content);
        }
        
        Utils.openModal('viewModal');
    }

    document.getElementById('copyModalBtn').addEventListener('click', () => {
        Utils.copyToClipboard(currentViewContent);
    });
</script>
</body>
</html>
