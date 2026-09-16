-- ============================================
-- CreatorAI - Database Schema
-- Database: creator_ai
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `creator_ai` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `creator_ai`;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `preferred_language` VARCHAR(50) DEFAULT 'English',
    `profile_completed` TINYINT(1) DEFAULT 0,
    `onboarding_step` TINYINT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_banned` TINYINT(1) DEFAULT 0,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `remember_token` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_username` (`username`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. ADMINS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. CREATOR PROFILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_profiles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `creator_name` VARCHAR(100) DEFAULT NULL,
    `display_name` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `city_region` VARCHAR(100) DEFAULT NULL,
    `preferred_language` VARCHAR(50) DEFAULT 'English',
    `secondary_languages` TEXT DEFAULT NULL,
    `experience_level` ENUM('beginner','intermediate','advanced','professional') DEFAULT 'beginner',
    `years_experience` INT DEFAULT 0,
    `primary_niche` VARCHAR(100) DEFAULT NULL,
    `custom_niche` VARCHAR(255) DEFAULT NULL,
    `niche_description` TEXT DEFAULT NULL,
    `creator_types` TEXT DEFAULT NULL,
    `content_types` TEXT DEFAULT NULL,
    `content_style` TEXT DEFAULT NULL,
    `custom_style_description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_creator_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. CREATOR PLATFORMS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_platforms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `platform_name` VARCHAR(50) NOT NULL,
    `username_handle` VARCHAR(100) DEFAULT NULL,
    `profile_url` VARCHAR(500) DEFAULT NULL,
    `followers` INT DEFAULT 0,
    `average_views` INT DEFAULT 0,
    `posting_frequency` VARCHAR(50) DEFAULT NULL,
    `main_content_format` VARCHAR(100) DEFAULT NULL,
    `subscribers` INT DEFAULT 0,
    `uses_shorts` TINYINT(1) DEFAULT 0,
    `uses_longform` TINYINT(1) DEFAULT 0,
    `uses_reels` TINYINT(1) DEFAULT 0,
    `uses_posts` TINYINT(1) DEFAULT 0,
    `uses_stories` TINYINT(1) DEFAULT 0,
    `uses_carousels` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_platform_user` (`user_id`),
    INDEX `idx_platform_name` (`platform_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CREATOR AUDIENCES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_audiences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `age_groups` TEXT DEFAULT NULL,
    `gender_preference` VARCHAR(50) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `region` VARCHAR(100) DEFAULT NULL,
    `primary_language` VARCHAR(50) DEFAULT NULL,
    `education_level` VARCHAR(100) DEFAULT NULL,
    `profession` VARCHAR(255) DEFAULT NULL,
    `interests` TEXT DEFAULT NULL,
    `problems_to_solve` TEXT DEFAULT NULL,
    `knowledge_level` ENUM('beginner','intermediate','advanced','mixed') DEFAULT 'mixed',
    `desired_feeling` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_audience_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. CREATOR BRAND PROFILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_brand_profiles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `tone` TEXT DEFAULT NULL,
    `speaking_style` TEXT DEFAULT NULL,
    `common_phrases` TEXT DEFAULT NULL,
    `avoided_phrases` TEXT DEFAULT NULL,
    `catchphrase` VARCHAR(500) DEFAULT NULL,
    `brand_description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_brand_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CREATOR GOALS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_goals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `primary_goals` TEXT DEFAULT NULL,
    `publishing_frequency` VARCHAR(50) DEFAULT NULL,
    `content_challenges` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_goals_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. CREATOR PREFERENCES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_preferences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `creative_freedom` ENUM('strict','mostly_follow','balanced','suggest_alternatives','highly_creative') DEFAULT 'balanced',
    `ai_priorities` TEXT DEFAULT NULL,
    `auto_remember` TINYINT(1) DEFAULT 1,
    `response_length` ENUM('short','medium','detailed') DEFAULT 'medium',
    `script_structure` VARCHAR(100) DEFAULT 'hook_body_cta',
    `custom_structure` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_pref_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. PLATFORMS TABLE (Reference)
-- ============================================
CREATE TABLE IF NOT EXISTS `platforms` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. NICHES TABLE (Reference)
-- ============================================
CREATE TABLE IF NOT EXISTS `niches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. CREATOR TYPES TABLE (Reference)
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. CONTENT TYPES TABLE (Reference)
-- ============================================
CREATE TABLE IF NOT EXISTS `content_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 13. CONTENT IDEAS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_ideas` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `target_audience` VARCHAR(255) DEFAULT NULL,
    `engagement_potential` ENUM('low','medium','high','very_high') DEFAULT 'medium',
    `difficulty` ENUM('easy','medium','hard') DEFAULT 'medium',
    `suggested_hook` TEXT DEFAULT NULL,
    `suggested_cta` TEXT DEFAULT NULL,
    `keywords` TEXT DEFAULT NULL,
    `status` ENUM('new','saved','planned','used','archived') DEFAULT 'new',
    `ai_generated` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_ideas_user` (`user_id`),
    INDEX `idx_ideas_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. CONTENT PROJECTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `project_type` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `topic` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('draft','in_progress','completed','archived') DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_projects_user` (`user_id`),
    INDEX `idx_projects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. GENERATED CONTENT TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `generated_content` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED DEFAULT NULL,
    `content_type` VARCHAR(100) NOT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `title` VARCHAR(500) DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `metadata` JSON DEFAULT NULL,
    `ai_model` VARCHAR(50) DEFAULT NULL,
    `tokens_used` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `content_projects`(`id`) ON DELETE SET NULL,
    INDEX `idx_generated_user` (`user_id`),
    INDEX `idx_generated_type` (`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. CONTENT VARIANTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_variants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED DEFAULT NULL,
    `source_content_id` INT UNSIGNED DEFAULT NULL,
    `platform` VARCHAR(50) NOT NULL,
    `variant_type` VARCHAR(100) DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `content_projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`source_content_id`) REFERENCES `generated_content`(`id`) ON DELETE SET NULL,
    INDEX `idx_variants_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. CONTENT ANALYTICS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_analytics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `content_title` VARCHAR(500) NOT NULL,
    `platform` VARCHAR(50) NOT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `publish_date` DATE DEFAULT NULL,
    `views` INT DEFAULT 0,
    `likes` INT DEFAULT 0,
    `comments` INT DEFAULT 0,
    `shares` INT DEFAULT 0,
    `saves` INT DEFAULT 0,
    `followers_gained` INT DEFAULT 0,
    `watch_time_minutes` DECIMAL(10,2) DEFAULT 0,
    `ctr` DECIMAL(5,2) DEFAULT 0,
    `engagement_rate` DECIMAL(5,2) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_analytics_user` (`user_id`),
    INDEX `idx_analytics_platform` (`platform`),
    INDEX `idx_analytics_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. CONTENT CALENDAR TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_calendar` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `topic` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('idea','planned','draft','ready','published','archived') DEFAULT 'idea',
    `publish_date` DATE DEFAULT NULL,
    `publish_time` TIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT '#6C5CE7',
    `ai_generated` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_calendar_user` (`user_id`),
    INDEX `idx_calendar_date` (`publish_date`),
    INDEX `idx_calendar_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. CHAT SESSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `chat_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) DEFAULT 'New Chat',
    `session_type` ENUM('general','content','ideas','script','analysis','repurpose') DEFAULT 'general',
    `is_active` TINYINT(1) DEFAULT 1,
    `message_count` INT DEFAULT 0,
    `last_message_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_chat_user` (`user_id`),
    INDEX `idx_chat_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 20. CHAT MESSAGES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` ENUM('user','assistant','system') NOT NULL,
    `message` LONGTEXT NOT NULL,
    `metadata` JSON DEFAULT NULL,
    `tokens_used` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_msg_session` (`session_id`),
    INDEX `idx_msg_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 21. SAVED CONTENT TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `saved_content` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `tags` TEXT DEFAULT NULL,
    `source` ENUM('chat','generator','ideas','repurpose','analyzer','manual') DEFAULT 'manual',
    `source_id` INT UNSIGNED DEFAULT NULL,
    `is_favorite` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_saved_user` (`user_id`),
    INDEX `idx_saved_category` (`category`),
    INDEX `idx_saved_type` (`content_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 22. CONTENT TEMPLATES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `content_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `template_body` LONGTEXT NOT NULL,
    `variables` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `is_system` TINYINT(1) DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `usage_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_template_active` (`is_active`),
    INDEX `idx_template_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 23. AI PROMPT TEMPLATES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_prompt_templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `prompt_type` VARCHAR(50) NOT NULL,
    `system_prompt` LONGTEXT NOT NULL,
    `user_prompt_template` LONGTEXT DEFAULT NULL,
    `variables` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_prompt_type` (`prompt_type`),
    INDEX `idx_prompt_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 24. AI REQUESTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `request_type` VARCHAR(50) NOT NULL,
    `ai_provider` VARCHAR(50) DEFAULT NULL,
    `ai_model` VARCHAR(50) DEFAULT NULL,
    `prompt_tokens` INT DEFAULT 0,
    `completion_tokens` INT DEFAULT 0,
    `total_tokens` INT DEFAULT 0,
    `response_time_ms` INT DEFAULT 0,
    `status` ENUM('success','error','timeout') DEFAULT 'success',
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_req_user` (`user_id`),
    INDEX `idx_req_type` (`request_type`),
    INDEX `idx_req_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 25. AI USAGE LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_usage_user` (`user_id`),
    INDEX `idx_usage_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 26. ADMIN LOGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX `idx_alog_admin` (`admin_id`),
    INDEX `idx_alog_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 27. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info','success','warning','error','ai') DEFAULT 'info',
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 28. UPLOADED FILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `uploaded_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_type` VARCHAR(100) NOT NULL,
    `file_size` INT UNSIGNED DEFAULT 0,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `purpose` VARCHAR(100) DEFAULT NULL,
    `extracted_text` LONGTEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_files_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 29. CREATOR MEMORY TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `creator_memory` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `memory_type` ENUM('preference','brand','audience','content','style','general') NOT NULL,
    `memory_key` VARCHAR(100) NOT NULL,
    `memory_value` TEXT NOT NULL,
    `confidence` DECIMAL(3,2) DEFAULT 1.00,
    `source` ENUM('onboarding','profile','chat','manual') DEFAULT 'manual',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_memory_user` (`user_id`),
    INDEX `idx_memory_type` (`memory_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
