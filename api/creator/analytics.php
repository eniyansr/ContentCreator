<?php
/**
 * CreatorAI - Analytics API
 * Note: In a real app, this would pull from YouTube/Instagram APIs.
 * For this MVP, we generate realistic mock data based on the user's platform inputs.
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
    if ($method === 'GET') {
        $range = $_GET['range'] ?? '30d'; // 7d, 30d, 90d
        $platform = $_GET['platform'] ?? 'all';
        
        // Fetch user's registered platforms to base mock data on
        $platforms = $d->fetchAll("SELECT * FROM creator_platforms WHERE user_id = ? AND is_active = 1", [$userId]);
        
        $totalFollowers = 0;
        $totalAvgViews = 0;
        
        foreach ($platforms as $p) {
            if ($platform === 'all' || $platform === $p['platform_name']) {
                $totalFollowers += (int)$p['followers'];
                $totalAvgViews += (int)$p['average_views'];
            }
        }
        
        // If no data, use some baselines
        if ($totalFollowers === 0) $totalFollowers = 1500;
        if ($totalAvgViews === 0) $totalAvgViews = 500;
        
        // Determine multiplier based on range
        $days = 30;
        if ($range === '7d') $days = 7;
        if ($range === '90d') $days = 90;
        
        // Generate mock overview stats
        $totalViews = $totalAvgViews * $days * 0.8; // assuming they don't post every single day
        $engagementRate = rand(30, 80) / 10; // 3.0 to 8.0 %
        $newFollowers = round($totalFollowers * ($days / 365) * (rand(5, 15) / 10)); // realistic growth
        
        // Generate mock chart data
        $chartDates = [];
        $chartViews = [];
        $chartEngagement = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('M d', strtotime("-$i days"));
            $chartDates[] = $date;
            
            // Random fluctuations
            $dailyViews = round(($totalViews / $days) * (rand(50, 150) / 100));
            $chartViews[] = $dailyViews;
            
            $dailyEng = round(($engagementRate) * (rand(80, 120) / 100), 1);
            $chartEngagement[] = $dailyEng;
        }
        
        // Generate mock top content
        $topContent = [
            ["title" => "My Morning Routine", "platform" => "YouTube", "views" => round($totalAvgViews * 2.5), "eng" => "8.4%"],
            ["title" => "3 Tools I Use Daily", "platform" => "Instagram", "views" => round($totalAvgViews * 1.8), "eng" => "7.1%"],
            ["title" => "Behind the Scenes", "platform" => "TikTok", "views" => round($totalAvgViews * 1.5), "eng" => "6.8%"],
            ["title" => "Why I stopped doing this", "platform" => "YouTube", "views" => round($totalAvgViews * 1.2), "eng" => "5.5%"]
        ];
        
        // AI Insights (Dynamic based on data)
        $insights = [
            [
                "type" => "positive",
                "title" => "Growth Trend Detected",
                "desc" => "Your views are up 15% compared to the previous period. Consistent posting on " . (!empty($platforms) ? $platforms[0]['platform_name'] : 'your main platform') . " is paying off."
            ],
            [
                "type" => "opportunity",
                "title" => "Format Opportunity",
                "desc" => "Your short-form content has a 20% higher engagement rate than your long-form content. Consider repurposing your top video into 3 Shorts."
            ],
            [
                "type" => "warning",
                "title" => "Audience Retention",
                "desc" => "We noticed a slight dip in engagement on Wednesdays. Try shifting your mid-week posts to Thursday evenings when your audience is most active."
            ]
        ];
        
        jsonSuccess([
            'overview' => [
                'views' => $totalViews,
                'views_change' => '+12.5%',
                'engagement' => $engagementRate . '%',
                'engagement_change' => '+1.2%',
                'followers' => $newFollowers,
                'followers_change' => '+5.4%',
                'posts' => round($days * 0.4), // approx posts
                'posts_change' => '0%'
            ],
            'chart' => [
                'labels' => $chartDates,
                'views' => $chartViews,
                'engagement' => $chartEngagement
            ],
            'top_content' => $topContent,
            'insights' => $insights
        ]);
    }
    
    jsonError('Invalid action', 400);
    
} catch (Exception $e) {
    logError("Analytics API error", ['error' => $e->getMessage()]);
    jsonError('An error occurred. Please try again.');
}
