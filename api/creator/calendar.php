<?php
/**
 * CreatorAI - Calendar API
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');
requireAuth();

$userId = getCurrentUserId();
$method = getMethod();
$d = db();

try {
    // ==================== GET ROUTES ====================
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            $month = (int)($_GET['month'] ?? date('n'));
            $year = (int)($_GET['year'] ?? date('Y'));
            
            // Get all events for the given month (and slightly padding before/after for grid display)
            $startDate = date('Y-m-d', strtotime("$year-$month-01 -7 days"));
            $endDate = date('Y-m-d', strtotime("$year-$month-01 +35 days"));
            
            $events = $d->fetchAll(
                "SELECT * FROM content_calendar WHERE user_id = ? AND publish_date BETWEEN ? AND ? ORDER BY publish_time ASC",
                [$userId, $startDate, $endDate]
            );
            
            jsonSuccess(['events' => $events]);
        }
        
        jsonError('Invalid action', 400);
    }
    
    // ==================== POST ROUTES ====================
    if ($method !== 'POST') jsonError('Method not allowed', 405);
    if (!validateCsrfToken()) jsonError('Invalid security token.', 403);
    
    $input = getJsonInput();
    $action = $input['action'] ?? '';
    
    // Create or Update Event
    if ($action === 'save') {
        $id = (int)($input['id'] ?? 0);
        $title = trim($input['title'] ?? '');
        $date = $input['publish_date'] ?? date('Y-m-d');
        $time = $input['publish_time'] ?? '12:00:00';
        $platform = $input['platform'] ?? 'General';
        $format = $input['format'] ?? '';
        $status = $input['status'] ?? 'draft';
        $notes = $input['notes'] ?? '';
        $linkedContentId = (int)($input['linked_content_id'] ?? 0);
        
        if (empty($title)) jsonError('Title is required.', 422);
        
        if ($id > 0) {
            // Update
            $d->update(
                "UPDATE content_calendar SET title=?, publish_date=?, publish_time=?, platform=?, content_format=?, status=?, notes=?, linked_content_id=? WHERE id=? AND user_id=?",
                [$title, $date, $time, $platform, $format, $status, $notes, $linkedContentId > 0 ? $linkedContentId : null, $id, $userId]
            );
            jsonSuccess(['id' => $id], 'Event updated.');
        } else {
            // Insert
            $newId = $d->insert(
                "INSERT INTO content_calendar (user_id, title, publish_date, publish_time, platform, content_format, status, notes, linked_content_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$userId, $title, $date, $time, $platform, $format, $status, $notes, $linkedContentId > 0 ? $linkedContentId : null]
            );
            jsonSuccess(['id' => $newId], 'Event created.');
        }
    }
    
    // Quick status update (e.g. dragging to a new status/date)
    if ($action === 'quick_update') {
        $id = (int)($input['id'] ?? 0);
        $date = $input['publish_date'] ?? null;
        
        if ($id <= 0 || !$date) jsonError('Invalid parameters.', 422);
        
        $d->update("UPDATE content_calendar SET publish_date=? WHERE id=? AND user_id=?", [$date, $id, $userId]);
        jsonSuccess([], 'Date updated.');
    }
    
    // Delete event
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) jsonError('Invalid ID.', 422);
        
        $d->delete("DELETE FROM content_calendar WHERE id = ? AND user_id = ?", [$id, $userId]);
        jsonSuccess([], 'Event deleted.');
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Calendar API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}
