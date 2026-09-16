<?php
/**
 * CreatorAI - Onboarding Wizard (All 8 Steps)
 * Single-page multi-step wizard with AJAX saving
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();

$isEditMode = isset($_GET['edit']) && $_GET['edit'] == '1';

// If profile already completed and not in edit mode, redirect to dashboard
if (!$isEditMode && !empty($_SESSION['profile_completed']) && $_SESSION['profile_completed'] == 1) {
    header('Location: ' . base_url('creator/dashboard.php'));
    exit;
}

$userId = getCurrentUserId();
$user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
$currentStep = (int)($user['onboarding_step'] ?? 0);

// Load existing data
$profile = db()->fetch("SELECT * FROM creator_profiles WHERE user_id = ?", [$userId]) ?: [];
$platforms = db()->fetchAll("SELECT * FROM creator_platforms WHERE user_id = ?", [$userId]) ?: [];
$audience = db()->fetch("SELECT * FROM creator_audiences WHERE user_id = ?", [$userId]) ?: [];
$brand = db()->fetch("SELECT * FROM creator_brand_profiles WHERE user_id = ?", [$userId]) ?: [];
$goals = db()->fetch("SELECT * FROM creator_goals WHERE user_id = ?", [$userId]) ?: [];
$prefs = db()->fetch("SELECT * FROM creator_preferences WHERE user_id = ?", [$userId]) ?: [];

// Load reference data
$nichesList = db()->fetchAll("SELECT name FROM niches WHERE is_active = 1 ORDER BY sort_order");
$creatorTypesList = db()->fetchAll("SELECT name FROM creator_types WHERE is_active = 1 ORDER BY sort_order");

$baseUrl = APP_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Your Creator Profile – CreatorAI</title>
    <meta name="base-url" content="<?php echo $baseUrl; ?>">
    <?php echo csrfMeta(); ?>
    <link rel="stylesheet" href="<?php echo asset_url('css/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/onboarding.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">
</head>
<body>
<div class="onboarding-page">
    <!-- Header -->
    <div class="onboarding-header">
        <a href="<?php echo base_url('public/'); ?>" class="onboarding-logo">
            <div class="onboarding-logo-icon">✦</div>
            CreatorAI
        </a>
        <div class="onboarding-progress-info">
            Step <strong id="stepCounter">1</strong> of <strong>8</strong>
        </div>
        <button class="onboarding-skip" id="skipBtn" onclick="skipStep()">Skip this step →</button>
    </div>

    <!-- Progress Bar -->
    <div class="onboarding-progress">
        <div class="onboarding-progress-bar">
            <div class="onboarding-progress-fill" id="progressFill" style="width: 12.5%"></div>
        </div>
    </div>

    <!-- Step Dots -->
    <div class="onboarding-steps" id="stepDots">
        <?php for ($i = 1; $i <= 8; $i++): ?>
            <div class="onboarding-step-dot <?php echo $i == 1 ? 'active' : ($i <= $currentStep ? 'completed' : ''); ?>" data-step="<?php echo $i; ?>">
                <?php echo $i <= $currentStep && $i != 1 ? '✓' : $i; ?>
            </div>
            <?php if ($i < 8): ?><div class="onboarding-step-connector <?php echo $i < $currentStep ? 'completed' : ''; ?>"></div><?php endif; ?>
        <?php endfor; ?>
    </div>

    <!-- Content Body -->
    <div class="onboarding-body">
        <!-- =============== STEP 1: Basic Info =============== -->
        <div class="onboarding-content" id="step1" style="display: block;">
            <h2>👋 Let's Build Your Creator Profile</h2>
            <p class="step-description">Tell us about yourself so our AI can personalize everything for you.</p>

            <div class="form-group">
                <label class="form-label">Creator Name <span class="required">*</span></label>
                <input type="text" class="form-input" id="s1_creator_name" value="<?php echo e($profile['creator_name'] ?? $user['full_name']); ?>" placeholder="Your creator/channel name">
            </div>
            <div class="form-group">
                <label class="form-label">Display Name</label>
                <input type="text" class="form-input" id="s1_display_name" value="<?php echo e($profile['display_name'] ?? ''); ?>" placeholder="How should AI address you?">
            </div>
            <div class="form-group">
                <label class="form-label">Profile Bio</label>
                <textarea class="form-textarea" id="s1_bio" rows="3" placeholder="A short bio about yourself as a creator" maxlength="500"><?php echo e($profile['bio'] ?? ''); ?></textarea>
                <span class="form-hint">Max 500 characters</span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" class="form-input" id="s1_country" value="<?php echo e($profile['country'] ?? $user['country']); ?>" placeholder="Your country">
                </div>
                <div class="form-group">
                    <label class="form-label">City / Region</label>
                    <input type="text" class="form-input" id="s1_city_region" value="<?php echo e($profile['city_region'] ?? ''); ?>" placeholder="Your city or region">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Preferred Language</label>
                    <select class="form-select" id="s1_language">
                        <?php foreach(['English','Hindi','Spanish','French','German','Portuguese','Arabic','Japanese','Korean','Mandarin','Indonesian','Turkish','Other'] as $lang): ?>
                        <option value="<?php echo $lang; ?>" <?php echo ($profile['preferred_language'] ?? $user['preferred_language'] ?? '') == $lang ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Experience Level</label>
                    <select class="form-select" id="s1_experience">
                        <?php foreach(['beginner'=>'Beginner (0-1 years)','intermediate'=>'Intermediate (1-3 years)','advanced'=>'Advanced (3-5 years)','professional'=>'Professional (5+ years)'] as $k=>$v): ?>
                        <option value="<?php echo $k; ?>" <?php echo ($profile['experience_level'] ?? '') == $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="onboarding-nav">
                <div></div>
                <button class="btn btn-primary" onclick="nextStep(1)">Next → Platforms</button>
            </div>
        </div>

        <!-- =============== STEP 2: Platforms =============== -->
        <div class="onboarding-content" id="step2" style="display: none;">
            <h2>📱 Your Social Media Platforms</h2>
            <p class="step-description">Which platforms do you create content for? Select all that apply.</p>

            <div class="platform-select-grid" id="platformGrid">
                <?php
                $platformEmojis = ['YouTube'=>'▶️','Instagram'=>'📸','TikTok'=>'🎵','Facebook'=>'👥','X/Twitter'=>'𝕏','LinkedIn'=>'💼','Pinterest'=>'📌','Blog/Website'=>'🌐','Podcast'=>'🎙️','Telegram'=>'✈️','Other'=>'➕'];
                $selectedPlatforms = array_column($platforms, 'platform_name');
                foreach ($platformEmojis as $name => $emoji):
                ?>
                <label class="platform-select-card <?php echo in_array($name, $selectedPlatforms) ? 'selected' : ''; ?>" data-platform="<?php echo $name; ?>">
                    <input type="checkbox" value="<?php echo $name; ?>" <?php echo in_array($name, $selectedPlatforms) ? 'checked' : ''; ?>>
                    <span class="platform-select-icon"><?php echo $emoji; ?></span>
                    <span class="platform-select-name"><?php echo $name; ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <div id="platformDetails" class="mt-6">
                <!-- Dynamically generated platform detail forms -->
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(2)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(2)">Next → Creator Type</button>
            </div>
        </div>

        <!-- =============== STEP 3: Creator Type =============== -->
        <div class="onboarding-content" id="step3" style="display: none;">
            <h2>🎯 Your Creator Type</h2>
            <p class="step-description">What type of content creator are you? Select all that apply.</p>

            <div class="step-section">
                <div class="step-section-title">Creator Types</div>
                <div class="multi-chip-group" id="creatorTypesGroup">
                    <?php 
                    $selectedTypes = json_decode($profile['creator_types'] ?? '[]', true) ?: [];
                    foreach ($creatorTypesList as $ct): ?>
                    <label class="multi-chip <?php echo in_array($ct['name'], $selectedTypes) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo e($ct['name']); ?>" <?php echo in_array($ct['name'], $selectedTypes) ? 'checked' : ''; ?>>
                        <?php echo e($ct['name']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step-section">
                <div class="step-section-title">Primary Niche</div>
                <select class="form-select" id="s3_niche">
                    <option value="">Select your primary niche</option>
                    <?php foreach ($nichesList as $n): ?>
                    <option value="<?php echo e($n['name']); ?>" <?php echo ($profile['primary_niche'] ?? '') == $n['name'] ? 'selected' : ''; ?>><?php echo e($n['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">Custom Niche</option>
                </select>
            </div>

            <div class="form-group" id="customNicheGroup" style="<?php echo !empty($profile['custom_niche']) ? '' : 'display:none'; ?>">
                <label class="form-label">Custom Niche</label>
                <input type="text" class="form-input" id="s3_custom_niche" value="<?php echo e($profile['custom_niche'] ?? ''); ?>" placeholder="Enter your custom niche">
            </div>

            <div class="form-group">
                <label class="form-label">Describe your content niche in your own words</label>
                <textarea class="form-textarea" id="s3_niche_desc" rows="3" placeholder="What makes your content unique?"><?php echo e($profile['niche_description'] ?? ''); ?></textarea>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(3)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(3)">Next → Audience</button>
            </div>
        </div>

        <!-- =============== STEP 4: Target Audience =============== -->
        <div class="onboarding-content" id="step4" style="display: none;">
            <h2>👥 Your Target Audience</h2>
            <p class="step-description">Who do you create content for? The more detail you provide, the better AI personalizes your content.</p>

            <div class="step-section">
                <div class="step-section-title">Audience Age Groups</div>
                <div class="multi-chip-group" id="ageGroupsChips">
                    <?php 
                    $selectedAges = json_decode($audience['age_groups'] ?? '[]', true) ?: [];
                    foreach (['Under 13','13-17','18-24','25-34','35-44','45+'] as $age): ?>
                    <label class="multi-chip <?php echo in_array($age, $selectedAges) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $age; ?>" <?php echo in_array($age, $selectedAges) ? 'checked' : ''; ?>>
                        <?php echo $age; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label class="form-label">Audience Country</label>
                    <input type="text" class="form-input" id="s4_country" value="<?php echo e($audience['country'] ?? ''); ?>" placeholder="Primary audience location">
                </div>
                <div class="form-group">
                    <label class="form-label">Primary Language</label>
                    <input type="text" class="form-input" id="s4_language" value="<?php echo e($audience['primary_language'] ?? ''); ?>" placeholder="Audience's primary language">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Audience Knowledge Level</label>
                <div class="multi-chip-group" id="knowledgeLevelChips">
                    <?php foreach (['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced','mixed'=>'Mixed'] as $k=>$v): ?>
                    <label class="multi-chip <?php echo ($audience['knowledge_level'] ?? 'mixed') == $k ? 'selected' : ''; ?>">
                        <input type="radio" name="knowledge_level" value="<?php echo $k; ?>" <?php echo ($audience['knowledge_level'] ?? 'mixed') == $k ? 'checked' : ''; ?>>
                        <?php echo $v; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">What problem does your content solve?</label>
                <textarea class="form-textarea" id="s4_problems" rows="3" placeholder="Describe the problems your audience faces..."><?php echo e($audience['problems_to_solve'] ?? ''); ?></textarea>
            </div>

            <div class="step-section">
                <div class="step-section-title">What should your audience feel?</div>
                <div class="multi-chip-group" id="feelingChips">
                    <?php 
                    $selectedFeelings = json_decode($audience['desired_feeling'] ?? '[]', true) ?: [];
                    foreach (['Learn','Laugh','Feel Inspired','Take Action','Buy','Trust','Entertain','Think Differently'] as $f): ?>
                    <label class="multi-chip <?php echo in_array($f, $selectedFeelings) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $f; ?>" <?php echo in_array($f, $selectedFeelings) ? 'checked' : ''; ?>>
                        <?php echo $f; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(4)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(4)">Next → Content Style</button>
            </div>
        </div>

        <!-- =============== STEP 5: Content Style =============== -->
        <div class="onboarding-content" id="step5" style="display: none;">
            <h2>🎨 Your Content Style</h2>
            <p class="step-description">What type of content do you create and what's your style?</p>

            <div class="step-section">
                <div class="step-section-title">Content Types You Create</div>
                <div class="multi-chip-group" id="contentTypesChips">
                    <?php 
                    $selectedContentTypes = json_decode($profile['content_types'] ?? '[]', true) ?: [];
                    foreach (['Short Videos','Long Videos','Reels','Shorts','Carousels','Images','Articles','Blogs','Podcasts','Livestreams','Tutorials','Reviews','Interviews','Stories','Educational Posts','Promotional Posts'] as $ct): ?>
                    <label class="multi-chip <?php echo in_array($ct, $selectedContentTypes) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $ct; ?>" <?php echo in_array($ct, $selectedContentTypes) ? 'checked' : ''; ?>>
                        <?php echo $ct; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step-section">
                <div class="step-section-title">Content Style</div>
                <div class="multi-chip-group" id="contentStyleChips">
                    <?php 
                    $selectedStyles = json_decode($profile['content_style'] ?? '[]', true) ?: [];
                    foreach (['Professional','Casual','Friendly','Funny','Serious','Motivational','Educational','Storytelling','Emotional','Bold','Minimal','Conversational'] as $s): ?>
                    <label class="multi-chip <?php echo in_array($s, $selectedStyles) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $s; ?>" <?php echo in_array($s, $selectedStyles) ? 'checked' : ''; ?>>
                        <?php echo $s; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Custom Style Description</label>
                <textarea class="form-textarea" id="s5_custom_style" rows="2" placeholder="Describe your unique content style..."><?php echo e($profile['custom_style_description'] ?? ''); ?></textarea>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(5)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(5)">Next → Brand Voice</button>
            </div>
        </div>

        <!-- =============== STEP 6: Brand Voice =============== -->
        <div class="onboarding-content" id="step6" style="display: none;">
            <h2>🗣️ Your Brand Voice</h2>
            <p class="step-description">This is crucial for AI personalization. Define how your content should sound.</p>

            <div class="step-section">
                <div class="step-section-title">Tone of AI-Generated Content</div>
                <div class="multi-chip-group" id="toneChips">
                    <?php 
                    $selectedTones = json_decode($brand['tone'] ?? '[]', true) ?: [];
                    foreach (['Professional','Casual','Conversational','Humorous','Inspirational','Authoritative','Friendly','Emotional','Bold','Simple','Technical','Youthful'] as $t): ?>
                    <label class="multi-chip <?php echo in_array($t, $selectedTones) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $t; ?>" <?php echo in_array($t, $selectedTones) ? 'checked' : ''; ?>>
                        <?php echo $t; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">How do you normally speak to your audience?</label>
                <textarea class="form-textarea" id="s6_speaking_style" rows="3" placeholder="Describe your speaking style..."><?php echo e($brand['speaking_style'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Words/phrases you commonly use</label>
                <textarea class="form-textarea" id="s6_common_phrases" rows="2" placeholder="e.g., 'Let's dive in', 'Here's the thing'..."><?php echo e($brand['common_phrases'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Words/phrases AI should AVOID</label>
                <textarea class="form-textarea" id="s6_avoided_phrases" rows="2" placeholder="Words that don't fit your brand..."><?php echo e($brand['avoided_phrases'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Signature Catchphrase</label>
                <input type="text" class="form-input" id="s6_catchphrase" value="<?php echo e($brand['catchphrase'] ?? ''); ?>" placeholder="Your signature phrase (optional)">
            </div>
            <div class="form-group">
                <label class="form-label">Describe Your Personal Brand</label>
                <textarea class="form-textarea" id="s6_brand_description" rows="3" placeholder="What does your brand stand for?"><?php echo e($brand['brand_description'] ?? ''); ?></textarea>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(6)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(6)">Next → Goals</button>
            </div>
        </div>

        <!-- =============== STEP 7: Goals =============== -->
        <div class="onboarding-content" id="step7" style="display: none;">
            <h2>🎯 Your Creator Goals</h2>
            <p class="step-description">What are you trying to achieve? AI will align recommendations with your goals.</p>

            <div class="step-section">
                <div class="step-section-title">Primary Goals</div>
                <div class="multi-chip-group" id="goalsChips">
                    <?php 
                    $selectedGoals = json_decode($goals['primary_goals'] ?? '[]', true) ?: [];
                    foreach (['Increase Followers','Increase Views','Increase Engagement','Build Personal Brand','Increase Subscribers','Generate Leads','Sell Products','Promote Services','Monetize Content','Build Community','Improve Consistency','Save Time','Improve Quality'] as $g): ?>
                    <label class="multi-chip <?php echo in_array($g, $selectedGoals) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $g; ?>" <?php echo in_array($g, $selectedGoals) ? 'checked' : ''; ?>>
                        <?php echo $g; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Publishing Frequency</label>
                <select class="form-select" id="s7_frequency">
                    <?php foreach (['Daily','3-5 times/week','1-2 times/week','Weekly','Biweekly','Monthly'] as $f): ?>
                    <option value="<?php echo $f; ?>" <?php echo ($goals['publishing_frequency'] ?? '') == $f ? 'selected' : ''; ?>><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="step-section">
                <div class="step-section-title">Biggest Content Challenges</div>
                <div class="multi-chip-group" id="challengesChips">
                    <?php 
                    $selectedChallenges = json_decode($goals['content_challenges'] ?? '[]', true) ?: [];
                    foreach (['Finding Ideas','Writing Scripts','Research','Consistency','Editing','Audience Engagement','SEO','Hashtags','Time Management','Burnout','Monetization','Branding','Understanding Analytics'] as $c): ?>
                    <label class="multi-chip <?php echo in_array($c, $selectedChallenges) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $c; ?>" <?php echo in_array($c, $selectedChallenges) ? 'checked' : ''; ?>>
                        <?php echo $c; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(7)">← Back</button>
                <button class="btn btn-primary" onclick="nextStep(7)">Next → AI Preferences</button>
            </div>
        </div>

        <!-- =============== STEP 8: AI Preferences =============== -->
        <div class="onboarding-content" id="step8" style="display: none;">
            <h2>🤖 AI Preferences</h2>
            <p class="step-description">Customize how CreatorAI behaves for you. You can always change these later.</p>

            <div class="step-section">
                <div class="step-section-title">How much creative freedom should AI have?</div>
                <div class="scale-selector" id="creativeFreedom">
                    <?php 
                    $freedomLabels = ['strict'=>'Strictly Follow','mostly_follow'=>'Mostly Follow','balanced'=>'Balanced','suggest_alternatives'=>'Suggest Ideas','highly_creative'=>'Highly Creative'];
                    foreach ($freedomLabels as $k=>$v): ?>
                    <label class="scale-option <?php echo ($prefs['creative_freedom'] ?? 'balanced') == $k ? 'selected' : ''; ?>">
                        <input type="radio" name="creative_freedom" value="<?php echo $k; ?>" <?php echo ($prefs['creative_freedom'] ?? 'balanced') == $k ? 'checked' : ''; ?>>
                        <div class="scale-label"><?php echo $v; ?></div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="step-section">
                <div class="step-section-title">What should AI prioritize?</div>
                <div class="multi-chip-group" id="prioritiesChips">
                    <?php 
                    $selectedPriorities = json_decode($prefs['ai_priorities'] ?? '[]', true) ?: [];
                    foreach (['Accuracy','Creativity','Engagement','SEO','Simplicity','Storytelling','Virality','Brand Consistency','Educational Value'] as $p): ?>
                    <label class="multi-chip <?php echo in_array($p, $selectedPriorities) ? 'selected' : ''; ?>">
                        <input type="checkbox" value="<?php echo $p; ?>" <?php echo in_array($p, $selectedPriorities) ? 'checked' : ''; ?>>
                        <?php echo $p; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Preferred Response Length</label>
                <div class="multi-chip-group" id="responseLengthChips">
                    <?php foreach (['short'=>'Short','medium'=>'Medium','detailed'=>'Detailed'] as $k=>$v): ?>
                    <label class="multi-chip <?php echo ($prefs['response_length'] ?? 'medium') == $k ? 'selected' : ''; ?>">
                        <input type="radio" name="response_length" value="<?php echo $k; ?>" <?php echo ($prefs['response_length'] ?? 'medium') == $k ? 'checked' : ''; ?>>
                        <?php echo $v; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Preferred Script Structure</label>
                <select class="form-select" id="s8_structure">
                    <?php foreach (['hook_body_cta'=>'Hook → Body → CTA','storytelling'=>'Storytelling','problem_solution'=>'Problem → Solution','educational'=>'Educational','custom'=>'Custom'] as $k=>$v): ?>
                    <option value="<?php echo $k; ?>" <?php echo ($prefs['script_structure'] ?? 'hook_body_cta') == $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" id="s8_auto_remember" <?php echo ($prefs['auto_remember'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="form-check-label">Should AI automatically remember your preferences?</span>
                </label>
            </div>

            <div class="onboarding-nav">
                <button class="btn btn-secondary" onclick="prevStep(8)">← Back</button>
                <button class="btn btn-primary btn-lg" onclick="finishOnboarding()" id="finishBtn">
                    🚀 Complete Profile & Start Creating
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo asset_url('js/api.js'); ?>"></script>
<script src="<?php echo asset_url('js/utils.js'); ?>"></script>
<script>
    let currentStep = 1;
    const totalSteps = 8;
    const existingPlatforms = <?php echo json_encode($platforms); ?>;

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        API.init('<?php echo $baseUrl; ?>');
        initChipSelectors();
        initPlatformSelector();
        initNicheSelector();
        
        // Load existing platform details
        if (existingPlatforms.length > 0) {
            existingPlatforms.forEach(p => {
                addPlatformDetail(p.platform_name, p);
            });
        }
    });

    // Chip selector toggle
    function initChipSelectors() {
        document.querySelectorAll('.multi-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const input = chip.querySelector('input');
                if (input.type === 'checkbox') {
                    input.checked = !input.checked;
                    chip.classList.toggle('selected', input.checked);
                } else if (input.type === 'radio') {
                    // Deselect siblings
                    chip.closest('.multi-chip-group, .scale-selector').querySelectorAll('.multi-chip, .scale-option').forEach(s => s.classList.remove('selected'));
                    input.checked = true;
                    chip.classList.add('selected');
                }
            });
        });

        document.querySelectorAll('.scale-option').forEach(opt => {
            opt.addEventListener('click', () => {
                opt.closest('.scale-selector').querySelectorAll('.scale-option').forEach(s => s.classList.remove('selected'));
                opt.classList.add('selected');
                opt.querySelector('input').checked = true;
            });
        });
    }

    // Platform selector
    function initPlatformSelector() {
        document.querySelectorAll('.platform-select-card').forEach(card => {
            card.addEventListener('click', () => {
                const input = card.querySelector('input');
                input.checked = !input.checked;
                card.classList.toggle('selected', input.checked);
                const platform = card.dataset.platform;
                if (input.checked) {
                    addPlatformDetail(platform);
                } else {
                    removePlatformDetail(platform);
                }
            });
        });
    }

    function addPlatformDetail(platform, data = {}) {
        if (document.getElementById('pd_' + platform.replace(/[\/ ]/g, '_'))) return;
        
        const id = platform.replace(/[\/ ]/g, '_');
        const div = document.createElement('div');
        div.className = 'platform-detail';
        div.id = 'pd_' + id;
        div.innerHTML = `
            <div class="platform-detail-header">
                <div class="platform-detail-title">${platform}</div>
                <button class="platform-detail-remove" onclick="removePlatformByBtn('${platform}')">✕ Remove</button>
            </div>
            <div class="platform-detail-body form-row">
                <div class="form-group">
                    <label class="form-label">Username/Handle</label>
                    <input type="text" class="form-input pd-input" data-field="username_handle" value="${data.username_handle || ''}" placeholder="@yourusername">
                </div>
                <div class="form-group">
                    <label class="form-label">Followers/Subscribers</label>
                    <input type="number" class="form-input pd-input" data-field="followers" value="${data.followers || ''}" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Average Views</label>
                    <input type="number" class="form-input pd-input" data-field="average_views" value="${data.average_views || ''}" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Posting Frequency</label>
                    <select class="form-select pd-input" data-field="posting_frequency">
                        <option value="">Select</option>
                        <option value="Daily" ${data.posting_frequency === 'Daily' ? 'selected' : ''}>Daily</option>
                        <option value="3-5/week" ${data.posting_frequency === '3-5/week' ? 'selected' : ''}>3-5 times/week</option>
                        <option value="1-2/week" ${data.posting_frequency === '1-2/week' ? 'selected' : ''}>1-2 times/week</option>
                        <option value="Weekly" ${data.posting_frequency === 'Weekly' ? 'selected' : ''}>Weekly</option>
                        <option value="Monthly" ${data.posting_frequency === 'Monthly' ? 'selected' : ''}>Monthly</option>
                    </select>
                </div>
            </div>
        `;
        document.getElementById('platformDetails').appendChild(div);
    }

    function removePlatformDetail(platform) {
        const id = 'pd_' + platform.replace(/[\/ ]/g, '_');
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function removePlatformByBtn(platform) {
        removePlatformDetail(platform);
        const card = document.querySelector(`.platform-select-card[data-platform="${platform}"]`);
        if (card) {
            card.classList.remove('selected');
            card.querySelector('input').checked = false;
        }
    }

    // Niche selector
    function initNicheSelector() {
        const select = document.getElementById('s3_niche');
        if (select) {
            select.addEventListener('change', () => {
                document.getElementById('customNicheGroup').style.display = select.value === 'custom' ? '' : 'none';
            });
        }
    }

    // Gather checked values from chip group
    function getCheckedValues(containerId) {
        return Array.from(document.querySelectorAll(`#${containerId} input:checked`)).map(i => i.value);
    }

    function getRadioValue(containerId) {
        const checked = document.querySelector(`#${containerId} input:checked`);
        return checked ? checked.value : '';
    }

    // Collect step data
    function collectStepData(step) {
        switch(step) {
            case 1: return {
                creator_name: document.getElementById('s1_creator_name').value,
                display_name: document.getElementById('s1_display_name').value,
                bio: document.getElementById('s1_bio').value,
                country: document.getElementById('s1_country').value,
                city_region: document.getElementById('s1_city_region').value,
                preferred_language: document.getElementById('s1_language').value,
                experience_level: document.getElementById('s1_experience').value,
            };
            case 2: {
                const platforms = [];
                document.querySelectorAll('.platform-detail').forEach(detail => {
                    const name = detail.querySelector('.platform-detail-title').textContent.trim();
                    const inputs = detail.querySelectorAll('.pd-input');
                    const p = { platform_name: name };
                    inputs.forEach(inp => { p[inp.dataset.field] = inp.value; });
                    platforms.push(p);
                });
                // Also include selected platforms without details
                document.querySelectorAll('.platform-select-card.selected').forEach(card => {
                    const name = card.dataset.platform;
                    if (!platforms.find(p => p.platform_name === name)) {
                        platforms.push({ platform_name: name });
                    }
                });
                return { platforms };
            }
            case 3: return {
                creator_types: getCheckedValues('creatorTypesGroup'),
                primary_niche: document.getElementById('s3_niche').value,
                custom_niche: document.getElementById('s3_custom_niche').value,
                niche_description: document.getElementById('s3_niche_desc').value,
            };
            case 4: return {
                age_groups: getCheckedValues('ageGroupsChips'),
                country: document.getElementById('s4_country').value,
                primary_language: document.getElementById('s4_language').value,
                knowledge_level: getRadioValue('knowledgeLevelChips'),
                problems_to_solve: document.getElementById('s4_problems').value,
                desired_feeling: getCheckedValues('feelingChips'),
            };
            case 5: return {
                content_types: getCheckedValues('contentTypesChips'),
                content_style: getCheckedValues('contentStyleChips'),
                custom_style_description: document.getElementById('s5_custom_style').value,
            };
            case 6: return {
                tone: getCheckedValues('toneChips'),
                speaking_style: document.getElementById('s6_speaking_style').value,
                common_phrases: document.getElementById('s6_common_phrases').value,
                avoided_phrases: document.getElementById('s6_avoided_phrases').value,
                catchphrase: document.getElementById('s6_catchphrase').value,
                brand_description: document.getElementById('s6_brand_description').value,
            };
            case 7: return {
                primary_goals: getCheckedValues('goalsChips'),
                publishing_frequency: document.getElementById('s7_frequency').value,
                content_challenges: getCheckedValues('challengesChips'),
            };
            case 8: return {
                creative_freedom: getRadioValue('creativeFreedom'),
                ai_priorities: getCheckedValues('prioritiesChips'),
                response_length: getRadioValue('responseLengthChips'),
                script_structure: document.getElementById('s8_structure').value,
                auto_remember: document.getElementById('s8_auto_remember').checked ? 1 : 0,
            };
        }
    }

    // Save step data
    async function saveStep(step) {
        const data = collectStepData(step);
        try {
            const result = await API.post('api/creator/onboarding.php', {
                step: step,
                data: data,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content,
            });
            return result.success;
        } catch (error) {
            Utils.toast(error.message || 'Failed to save. Please try again.', 'error');
            return false;
        }
    }

    // Navigate steps
    async function nextStep(fromStep) {
        const saved = await saveStep(fromStep);
        if (!saved) return;

        goToStep(fromStep + 1);
    }

    function prevStep(fromStep) {
        goToStep(fromStep - 1);
    }

    function skipStep() {
        goToStep(currentStep + 1);
    }

    function goToStep(step) {
        if (step < 1 || step > 8) return;
        
        document.getElementById('step' + currentStep).style.display = 'none';
        document.getElementById('step' + step).style.display = 'block';
        
        currentStep = step;
        
        // Update UI
        document.getElementById('stepCounter').textContent = step;
        document.getElementById('progressFill').style.width = (step / totalSteps * 100) + '%';
        
        // Update dots
        document.querySelectorAll('.onboarding-step-dot').forEach(dot => {
            const s = parseInt(dot.dataset.step);
            dot.className = 'onboarding-step-dot';
            if (s === step) dot.classList.add('active');
            else if (s < step) { dot.classList.add('completed'); dot.textContent = '✓'; }
            else dot.textContent = s;
        });
        
        // Scroll to top
        document.querySelector('.onboarding-body').scrollTop = 0;
    }

    // Finish onboarding
    async function finishOnboarding() {
        const btn = document.getElementById('finishBtn');
        Utils.btnLoading(btn, true);
        
        const saved = await saveStep(8);
        if (saved) {
            Utils.toast('Profile completed! Redirecting to your dashboard...', 'success');
            setTimeout(() => {
                window.location.href = '<?php echo base_url("creator/dashboard.php"); ?>';
            }, 1500);
        } else {
            Utils.btnLoading(btn, false);
        }
    }
</script>
</body>
</html>
