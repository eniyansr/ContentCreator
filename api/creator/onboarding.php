<?php
/**
 * CreatorAI - Onboarding Save API
 * Handles saving data for each onboarding step
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireAuth();

if (getMethod() !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (!validateCsrfToken()) {
    jsonError('Invalid security token.', 403);
}

$userId = getCurrentUserId();
$input = getJsonInput();
$step = $input['step'] ?? 0;

try {
    $d = db();
    
    switch ((int) $step) {
        case 1: // Basic Creator Information
            $data = $input['data'] ?? [];
            $d->query(
                "INSERT INTO creator_profiles (user_id, creator_name, display_name, bio, country, city_region, preferred_language, secondary_languages, experience_level, years_experience) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET creator_name=EXCLUDED.creator_name, display_name=EXCLUDED.display_name, bio=EXCLUDED.bio, 
                 country=EXCLUDED.country, city_region=EXCLUDED.city_region, preferred_language=EXCLUDED.preferred_language,
                 secondary_languages=EXCLUDED.secondary_languages, experience_level=EXCLUDED.experience_level, years_experience=EXCLUDED.years_experience",
                [
                    $userId,
                    $data['creator_name'] ?? '',
                    $data['display_name'] ?? '',
                    $data['bio'] ?? '',
                    $data['country'] ?? '',
                    $data['city_region'] ?? '',
                    $data['preferred_language'] ?? 'English',
                    json_encode($data['secondary_languages'] ?? []),
                    $data['experience_level'] ?? 'beginner',
                    (int)($data['years_experience'] ?? 0),
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 1) WHERE id = ?", [$userId]);
            break;

        case 2: // Social Media Platforms
            error_log("Entering Step 2 logic...");
            $platforms = $input['data']['platforms'] ?? [];
            error_log("Platforms: " . json_encode($platforms));
            // Clear existing
            $d->delete("DELETE FROM creator_platforms WHERE user_id = ?", [$userId]);
            error_log("Cleared existing platforms");
            
            foreach ($platforms as $p) {
                $d->insert(
                    "INSERT INTO creator_platforms (user_id, platform_name, username_handle, profile_url, followers, average_views, posting_frequency, main_content_format, subscribers, uses_shorts, uses_longform, uses_reels, uses_posts, uses_stories, uses_carousels) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $userId,
                        $p['platform_name'] ?? '',
                        $p['username_handle'] ?? '',
                        $p['profile_url'] ?? '',
                        (int)($p['followers'] ?? 0),
                        (int)($p['average_views'] ?? 0),
                        $p['posting_frequency'] ?? '',
                        $p['main_content_format'] ?? '',
                        (int)($p['subscribers'] ?? 0),
                        (int)($p['uses_shorts'] ?? 0),
                        (int)($p['uses_longform'] ?? 0),
                        (int)($p['uses_reels'] ?? 0),
                        (int)($p['uses_posts'] ?? 0),
                        (int)($p['uses_stories'] ?? 0),
                        (int)($p['uses_carousels'] ?? 0),
                    ]
                );
            }
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 2) WHERE id = ?", [$userId]);
            break;

        case 3: // Creator Type & Niche
            $data = $input['data'] ?? [];
            $d->query(
                "UPDATE creator_profiles SET creator_types = ?, primary_niche = ?, custom_niche = ?, niche_description = ? WHERE user_id = ?",
                [
                    json_encode($data['creator_types'] ?? []),
                    $data['primary_niche'] ?? '',
                    $data['custom_niche'] ?? '',
                    $data['niche_description'] ?? '',
                    $userId,
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 3) WHERE id = ?", [$userId]);
            break;

        case 4: // Target Audience
            $data = $input['data'] ?? [];
            $d->query(
                "INSERT INTO creator_audiences (user_id, age_groups, gender_preference, country, region, primary_language, education_level, profession, interests, problems_to_solve, knowledge_level, desired_feeling)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET age_groups=EXCLUDED.age_groups, gender_preference=EXCLUDED.gender_preference,
                 country=EXCLUDED.country, region=EXCLUDED.region, primary_language=EXCLUDED.primary_language,
                 education_level=EXCLUDED.education_level, profession=EXCLUDED.profession, interests=EXCLUDED.interests,
                 problems_to_solve=EXCLUDED.problems_to_solve, knowledge_level=EXCLUDED.knowledge_level, desired_feeling=EXCLUDED.desired_feeling",
                [
                    $userId,
                    json_encode($data['age_groups'] ?? []),
                    $data['gender_preference'] ?? '',
                    $data['country'] ?? '',
                    $data['region'] ?? '',
                    $data['primary_language'] ?? '',
                    $data['education_level'] ?? '',
                    $data['profession'] ?? '',
                    json_encode($data['interests'] ?? []),
                    $data['problems_to_solve'] ?? '',
                    $data['knowledge_level'] ?? 'mixed',
                    json_encode($data['desired_feeling'] ?? []),
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 4) WHERE id = ?", [$userId]);
            break;

        case 5: // Content Style
            $data = $input['data'] ?? [];
            $d->query(
                "UPDATE creator_profiles SET content_types = ?, content_style = ?, custom_style_description = ? WHERE user_id = ?",
                [
                    json_encode($data['content_types'] ?? []),
                    json_encode($data['content_style'] ?? []),
                    $data['custom_style_description'] ?? '',
                    $userId,
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 5) WHERE id = ?", [$userId]);
            break;

        case 6: // Brand Voice
            $data = $input['data'] ?? [];
            $d->query(
                "INSERT INTO creator_brand_profiles (user_id, tone, speaking_style, common_phrases, avoided_phrases, catchphrase, brand_description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET tone=EXCLUDED.tone, speaking_style=EXCLUDED.speaking_style,
                 common_phrases=EXCLUDED.common_phrases, avoided_phrases=EXCLUDED.avoided_phrases,
                 catchphrase=EXCLUDED.catchphrase, brand_description=EXCLUDED.brand_description",
                [
                    $userId,
                    json_encode($data['tone'] ?? []),
                    $data['speaking_style'] ?? '',
                    $data['common_phrases'] ?? '',
                    $data['avoided_phrases'] ?? '',
                    $data['catchphrase'] ?? '',
                    $data['brand_description'] ?? '',
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 6) WHERE id = ?", [$userId]);
            break;

        case 7: // Creator Goals
            $data = $input['data'] ?? [];
            $d->query(
                "INSERT INTO creator_goals (user_id, primary_goals, publishing_frequency, content_challenges)
                 VALUES (?, ?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET primary_goals=EXCLUDED.primary_goals, publishing_frequency=EXCLUDED.publishing_frequency,
                 content_challenges=EXCLUDED.content_challenges",
                [
                    $userId,
                    json_encode($data['primary_goals'] ?? []),
                    $data['publishing_frequency'] ?? '',
                    json_encode($data['content_challenges'] ?? []),
                ]
            );
            $d->update("UPDATE users SET onboarding_step = MAX(onboarding_step, 7) WHERE id = ?", [$userId]);
            break;

        case 8: // AI Preferences
            $data = $input['data'] ?? [];
            $d->query(
                "INSERT INTO creator_preferences (user_id, creative_freedom, ai_priorities, auto_remember, response_length, script_structure, custom_structure)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON CONFLICT(user_id) DO UPDATE SET creative_freedom=EXCLUDED.creative_freedom, ai_priorities=EXCLUDED.ai_priorities,
                 auto_remember=EXCLUDED.auto_remember, response_length=EXCLUDED.response_length,
                 script_structure=EXCLUDED.script_structure, custom_structure=EXCLUDED.custom_structure",
                [
                    $userId,
                    $data['creative_freedom'] ?? 'balanced',
                    json_encode($data['ai_priorities'] ?? []),
                    (int)($data['auto_remember'] ?? 1),
                    $data['response_length'] ?? 'medium',
                    $data['script_structure'] ?? 'hook_body_cta',
                    $data['custom_structure'] ?? '',
                ]
            );
            // Mark profile as completed
            $d->update("UPDATE users SET onboarding_step = 8, profile_completed = 1 WHERE id = ?", [$userId]);
            $_SESSION['profile_completed'] = 1;
            
            // Create notification
            createNotification($userId, 'Profile Complete! 🎉', 'Your creator profile is set up. Start creating amazing content with AI!', 'success', 'creator/dashboard.php');
            logActivity($userId, 'onboarding_complete', 'Creator completed profile onboarding');
            break;

        default:
            jsonError('Invalid step', 400);
    }
    
    jsonSuccess(['step' => $step], 'Step ' . $step . ' saved successfully!');
    
} catch (Exception $e) {
    logError("Onboarding save failed", ['step' => $step, 'error' => $e->getMessage()]);
    jsonError('Failed to save. Please try again.');
}
