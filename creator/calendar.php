<?php
/**
 * CreatorAI - Content Calendar Page
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

// Fetch platforms for dropdown
$platforms = $d->fetchAll("SELECT platform_name FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
$platformList = array_column($platforms, 'platform_name');

// Fetch saved content for linking
$savedContent = $d->fetchAll("SELECT id, title, content_type FROM saved_content WHERE user_id = ? ORDER BY created_at DESC LIMIT 100", [$userId]);

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Calendar – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/calendar.css'); ?>">
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
                <a href="<?php echo base_url('creator/calendar.php'); ?>" class="sidebar-link active"><span class="sidebar-link-icon">📅</span> Calendar</a>
                <a href="<?php echo base_url('creator/library.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📚</span> Saved Content</a>
                <a href="<?php echo base_url('creator/templates.php'); ?>" class="sidebar-link"><span class="sidebar-link-icon">📋</span> Templates</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="top-header-left">
                <h2 class="page-title">Content Calendar</h2>
            </div>
            <div class="top-header-right">
                <button class="btn btn-primary" onclick="openEventModal()">+ Add Event</button>
            </div>
        </header>

        <div class="page-content">
            <div class="calendar-layout">
                
                <!-- Controls -->
                <div class="calendar-controls">
                    <div class="calendar-month-selector">
                        <button class="calendar-nav-btn" onclick="changeMonth(-1)">◀</button>
                        <div class="calendar-current-month" id="currentMonthLabel">September 2026</div>
                        <button class="calendar-nav-btn" onclick="changeMonth(1)">▶</button>
                        <button class="btn btn-ghost btn-sm" onclick="goToToday()">Today</button>
                    </div>
                    
                    <div class="calendar-filters">
                        <select class="form-select form-select-sm" id="platformFilter" onchange="renderCalendar()">
                            <option value="all">All Platforms</option>
                            <?php foreach ($platformList as $p): ?>
                            <option value="<?php echo e($p); ?>"><?php echo e($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select class="form-select form-select-sm" id="statusFilter" onchange="renderCalendar()">
                            <option value="all">All Statuses</option>
                            <option value="draft">Drafts</option>
                            <option value="planned">Planned</option>
                            <option value="ready">Ready to Publish</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>
                
                <!-- Calendar Grid -->
                <div class="calendar-grid-container">
                    <div class="calendar-days-header">
                        <div class="calendar-day-name">Sun</div>
                        <div class="calendar-day-name">Mon</div>
                        <div class="calendar-day-name">Tue</div>
                        <div class="calendar-day-name">Wed</div>
                        <div class="calendar-day-name">Thu</div>
                        <div class="calendar-day-name">Fri</div>
                        <div class="calendar-day-name">Sat</div>
                    </div>
                    <div class="calendar-grid" id="calendarGrid">
                        <!-- Populated via JS -->
                    </div>
                </div>
                
            </div>
        </div>
    </main>
</div>

<!-- Event Modal -->
<div class="modal-backdrop" id="eventModal-backdrop"></div>
<div class="modal" id="eventModal" style="max-width: 600px;">
    <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Add Content Event</h3>
        <button class="modal-close" onclick="Utils.closeModal('eventModal')">×</button>
    </div>
    
    <input type="hidden" id="eventId" value="0">
    
    <div class="form-group">
        <label class="form-label">Post Title/Topic <span class="required">*</span></label>
        <input type="text" id="eventTitle" class="form-input" placeholder="e.g. My Morning Routine VLOG">
    </div>
    
    <div class="event-form-grid">
        <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" id="eventDate" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Time</label>
            <input type="time" id="eventTime" class="form-input" value="12:00">
        </div>
        <div class="form-group">
            <label class="form-label">Platform</label>
            <select id="eventPlatform" class="form-select">
                <?php foreach ($platformList as $p): ?>
                <option value="<?php echo e($p); ?>"><?php echo e($p); ?></option>
                <?php endforeach; ?>
                <option value="YouTube">YouTube</option>
                <option value="Instagram">Instagram</option>
                <option value="TikTok">TikTok</option>
                <option value="Twitter">Twitter</option>
                <option value="LinkedIn">LinkedIn</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Format</label>
            <select id="eventFormat" class="form-select">
                <option value="Short Video">Short Video</option>
                <option value="Long Video">Long Video</option>
                <option value="Post/Image">Post/Image</option>
                <option value="Carousel">Carousel</option>
                <option value="Text/Thread">Text/Thread</option>
            </select>
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label">Status</label>
        <select id="eventStatus" class="form-select">
            <option value="draft">📝 Draft</option>
            <option value="planned">📅 Planned</option>
            <option value="ready">✅ Ready to Publish</option>
            <option value="published">🚀 Published</option>
        </select>
    </div>
    
    <div class="form-group">
        <label class="form-label">Link to Library Content (Optional)</label>
        <select id="eventLinked" class="form-select">
            <option value="0">-- None --</option>
            <?php foreach ($savedContent as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo e($c['title']); ?> (<?php echo e($c['content_type']); ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea id="eventNotes" class="form-textarea" rows="2" placeholder="Any specific requirements, tags, or reminders..."></textarea>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--space-6);">
        <button class="btn btn-ghost" id="deleteEventBtn" style="color: var(--error); display: none;" onclick="deleteEvent()">Delete Event</button>
        <div style="display: flex; gap: var(--space-3); margin-left: auto;">
            <button class="btn btn-secondary" onclick="Utils.closeModal('eventModal')">Cancel</button>
            <button class="btn btn-primary" id="saveEventBtn" onclick="saveEvent()">Save Event</button>
        </div>
    </div>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    let currentDate = new Date(); // Represents the currently viewed month
    let eventsData = [];
    
    const platformEmojis = {
        'YouTube': '▶️',
        'Instagram': '📸',
        'TikTok': '🎵',
        'Twitter': '🐦',
        'X': '𝕏',
        'LinkedIn': '💼',
        'Facebook': '👥'
    };

    document.addEventListener('DOMContentLoaded', () => {
        API.init('<?php echo $baseUrl; ?>');
        loadEvents();
    });

    function getPlatformEmoji(platform) {
        for (const [key, emoji] of Object.entries(platformEmojis)) {
            if (platform.toLowerCase().includes(key.toLowerCase())) return emoji;
        }
        return '📱';
    }

    async function loadEvents() {
        try {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;
            const result = await API.get('api/creator/calendar.php', { action: 'list', year: year, month: month });
            if (result.success) {
                eventsData = result.events || [];
                renderCalendar();
            }
        } catch (error) {
            Utils.toast('Failed to load calendar events', 'error');
        }
    }

    function changeMonth(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        loadEvents();
    }

    function goToToday() {
        currentDate = new Date();
        loadEvents();
    }

    function renderCalendar() {
        const grid = document.getElementById('calendarGrid');
        const monthLabel = document.getElementById('currentMonthLabel');
        const pFilter = document.getElementById('platformFilter').value;
        const sFilter = document.getElementById('statusFilter').value;
        
        grid.innerHTML = '';
        
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        // Month formatting
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        monthLabel.textContent = `${monthNames[month]} ${year}`;
        
        // Grid calculations
        const firstDay = new Date(year, month, 1).getDay(); // 0 (Sun) to 6 (Sat)
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();
        
        // Total cells: standard 35 (5 weeks) or 42 (6 weeks) if needed
        const totalCells = (firstDay + daysInMonth > 35) ? 42 : 35;
        
        const todayStr = new Date().toISOString().split('T')[0];

        for (let i = 0; i < totalCells; i++) {
            const cell = document.createElement('div');
            
            let cellDate;
            let isOtherMonth = false;
            
            if (i < firstDay) {
                // Previous month
                const day = daysInPrevMonth - firstDay + i + 1;
                cellDate = new Date(year, month - 1, day);
                isOtherMonth = true;
            } else if (i >= firstDay && i < firstDay + daysInMonth) {
                // Current month
                const day = i - firstDay + 1;
                cellDate = new Date(year, month, day);
            } else {
                // Next month
                const day = i - firstDay - daysInMonth + 1;
                cellDate = new Date(year, month + 1, day);
                isOtherMonth = true;
            }
            
            // Format YYYY-MM-DD avoiding timezone shift
            const dStr = `${cellDate.getFullYear()}-${String(cellDate.getMonth()+1).padStart(2,'0')}-${String(cellDate.getDate()).padStart(2,'0')}`;
            
            cell.className = `calendar-cell ${isOtherMonth ? 'other-month' : ''} ${dStr === todayStr ? 'today' : ''}`;
            
            // Allow drop
            cell.dataset.date = dStr;
            cell.ondragover = (e) => e.preventDefault();
            cell.ondrop = (e) => handleDrop(e, dStr);
            
            // Header (Date number + Add button)
            cell.innerHTML = `
                <div class="calendar-date-header">
                    <span class="calendar-date-num">${cellDate.getDate()}</span>
                    <button class="calendar-add-btn" onclick="openEventModal(null, '${dStr}')">+</button>
                </div>
                <div class="calendar-events"></div>
            `;
            
            // Add events
            const eventsContainer = cell.querySelector('.calendar-events');
            
            eventsData.forEach(event => {
                if (event.publish_date === dStr) {
                    // Apply filters
                    if (pFilter !== 'all' && event.platform !== pFilter) return;
                    if (sFilter !== 'all' && event.status !== sFilter) return;
                    
                    const el = document.createElement('div');
                    el.className = `calendar-event status-${event.status}`;
                    el.draggable = true;
                    el.ondragstart = (e) => { e.dataTransfer.setData('text/plain', event.id); };
                    el.onclick = () => openEventModal(event);
                    
                    const emoji = getPlatformEmoji(event.platform);
                    el.innerHTML = `<span class="event-platform-icon">${emoji}</span> ${Utils.escapeHtml(event.title)}`;
                    
                    eventsContainer.appendChild(el);
                }
            });
            
            grid.appendChild(cell);
        }
    }

    async function handleDrop(e, targetDate) {
        e.preventDefault();
        const eventId = e.dataTransfer.getData('text/plain');
        if (!eventId) return;
        
        // Find in UI data immediately for optimistic update
        const evtIndex = eventsData.findIndex(ev => ev.id == eventId);
        if (evtIndex > -1) {
            eventsData[evtIndex].publish_date = targetDate;
            renderCalendar();
        }
        
        try {
            await API.post('api/creator/calendar.php', {
                action: 'quick_update',
                id: eventId,
                publish_date: targetDate,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
        } catch (error) {
            Utils.toast('Failed to move event', 'error');
            loadEvents(); // Reload true state
        }
    }

    function openEventModal(eventObj = null, defaultDate = null) {
        if (eventObj) {
            document.getElementById('modalTitle').textContent = 'Edit Event';
            document.getElementById('eventId').value = eventObj.id;
            document.getElementById('eventTitle').value = eventObj.title;
            document.getElementById('eventDate').value = eventObj.publish_date;
            document.getElementById('eventTime').value = eventObj.publish_time;
            document.getElementById('eventPlatform').value = eventObj.platform;
            document.getElementById('eventFormat').value = eventObj.content_format;
            document.getElementById('eventStatus').value = eventObj.status;
            document.getElementById('eventLinked').value = eventObj.linked_content_id || 0;
            document.getElementById('eventNotes').value = eventObj.notes || '';
            document.getElementById('deleteEventBtn').style.display = 'block';
        } else {
            document.getElementById('modalTitle').textContent = 'Add Event';
            document.getElementById('eventId').value = 0;
            document.getElementById('eventTitle').value = '';
            document.getElementById('eventDate').value = defaultDate || new Date().toISOString().split('T')[0];
            document.getElementById('eventTime').value = '12:00';
            document.getElementById('eventStatus').value = 'draft';
            document.getElementById('eventLinked').value = 0;
            document.getElementById('eventNotes').value = '';
            document.getElementById('deleteEventBtn').style.display = 'none';
        }
        
        Utils.openModal('eventModal');
    }

    async function saveEvent() {
        const title = document.getElementById('eventTitle').value.trim();
        if (!title) {
            Utils.toast('Title is required', 'error');
            return;
        }
        
        const btn = document.getElementById('saveEventBtn');
        Utils.btnLoading(btn, true);
        
        try {
            const result = await API.post('api/creator/calendar.php', {
                action: 'save',
                id: document.getElementById('eventId').value,
                title: title,
                publish_date: document.getElementById('eventDate').value,
                publish_time: document.getElementById('eventTime').value,
                platform: document.getElementById('eventPlatform').value,
                format: document.getElementById('eventFormat').value,
                status: document.getElementById('eventStatus').value,
                linked_content_id: document.getElementById('eventLinked').value,
                notes: document.getElementById('eventNotes').value,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                Utils.toast('Event saved', 'success');
                Utils.closeModal('eventModal');
                loadEvents();
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to save event', 'error');
        }
        
        Utils.btnLoading(btn, false);
    }

    async function deleteEvent() {
        if (!confirm('Are you sure you want to delete this event?')) return;
        
        const id = document.getElementById('eventId').value;
        const btn = document.getElementById('deleteEventBtn');
        Utils.btnLoading(btn, true);
        
        try {
            const result = await API.post('api/creator/calendar.php', {
                action: 'delete',
                id: id,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            });
            
            if (result.success) {
                Utils.toast('Event deleted', 'success');
                Utils.closeModal('eventModal');
                loadEvents();
            }
        } catch (error) {
            Utils.toast(error.message || 'Failed to delete event', 'error');
            Utils.btnLoading(btn, false);
        }
    }
</script>
</body>
</html>
