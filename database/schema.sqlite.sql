CREATE TABLE IF NOT EXISTS `users` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_email` ON `users` (`email`);
CREATE INDEX IF NOT EXISTS `idx_username` ON `users` (`username`);
CREATE INDEX IF NOT EXISTS `idx_active` ON `users` (`is_active`);

CREATE TABLE IF NOT EXISTS `admins` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` TEXT DEFAULT 'admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_admin_email` ON `admins` (`email`);

CREATE TABLE IF NOT EXISTS `creator_profiles` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `creator_name` VARCHAR(100) DEFAULT NULL,
    `display_name` VARCHAR(100) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT NULL,
    `city_region` VARCHAR(100) DEFAULT NULL,
    `preferred_language` VARCHAR(50) DEFAULT 'English',
    `secondary_languages` TEXT DEFAULT NULL,
    `experience_level` TEXT DEFAULT 'beginner',
    `years_experience` INT DEFAULT 0,
    `primary_niche` VARCHAR(100) DEFAULT NULL,
    `custom_niche` VARCHAR(255) DEFAULT NULL,
    `niche_description` TEXT DEFAULT NULL,
    `creator_types` TEXT DEFAULT NULL,
    `content_types` TEXT DEFAULT NULL,
    `content_style` TEXT DEFAULT NULL,
    `custom_style_description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_creator_user` ON `creator_profiles` (`user_id`);

CREATE TABLE IF NOT EXISTS `creator_platforms` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_platform_user` ON `creator_platforms` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_platform_name` ON `creator_platforms` (`platform_name`);

CREATE TABLE IF NOT EXISTS `creator_audiences` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    `knowledge_level` TEXT DEFAULT 'mixed',
    `desired_feeling` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_audience_user` ON `creator_audiences` (`user_id`);

CREATE TABLE IF NOT EXISTS `creator_brand_profiles` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `tone` TEXT DEFAULT NULL,
    `speaking_style` TEXT DEFAULT NULL,
    `common_phrases` TEXT DEFAULT NULL,
    `avoided_phrases` TEXT DEFAULT NULL,
    `catchphrase` VARCHAR(500) DEFAULT NULL,
    `brand_description` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_brand_user` ON `creator_brand_profiles` (`user_id`);

CREATE TABLE IF NOT EXISTS `creator_goals` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `primary_goals` TEXT DEFAULT NULL,
    `publishing_frequency` VARCHAR(50) DEFAULT NULL,
    `content_challenges` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_goals_user` ON `creator_goals` (`user_id`);

CREATE TABLE IF NOT EXISTS `creator_preferences` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `creative_freedom` TEXT DEFAULT 'balanced',
    `ai_priorities` TEXT DEFAULT NULL,
    `auto_remember` TINYINT(1) DEFAULT 1,
    `response_length` TEXT DEFAULT 'medium',
    `script_structure` VARCHAR(100) DEFAULT 'hook_body_cta',
    `custom_structure` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_pref_user` ON `creator_preferences` (`user_id`);

CREATE TABLE IF NOT EXISTS `platforms` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS `niches` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS `creator_types` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS `content_types` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS `content_ideas` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `target_audience` VARCHAR(255) DEFAULT NULL,
    `engagement_potential` TEXT DEFAULT 'medium',
    `difficulty` TEXT DEFAULT 'medium',
    `suggested_hook` TEXT DEFAULT NULL,
    `suggested_cta` TEXT DEFAULT NULL,
    `keywords` TEXT DEFAULT NULL,
    `status` TEXT DEFAULT 'new',
    `ai_generated` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_ideas_user` ON `content_ideas` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_ideas_status` ON `content_ideas` (`status`);

CREATE TABLE IF NOT EXISTS `content_projects` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `project_type` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `topic` VARCHAR(500) DEFAULT NULL,
    `status` TEXT DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_projects_user` ON `content_projects` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_projects_status` ON `content_projects` (`status`);

CREATE TABLE IF NOT EXISTS `generated_content` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    FOREIGN KEY (`project_id`) REFERENCES `content_projects`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_generated_user` ON `generated_content` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_generated_type` ON `generated_content` (`content_type`);

CREATE TABLE IF NOT EXISTS `content_variants` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `project_id` INT UNSIGNED DEFAULT NULL,
    `source_content_id` INT UNSIGNED DEFAULT NULL,
    `platform` VARCHAR(50) NOT NULL,
    `variant_type` VARCHAR(100) DEFAULT NULL,
    `content` LONGTEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `content_projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`source_content_id`) REFERENCES `generated_content`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_variants_user` ON `content_variants` (`user_id`);

CREATE TABLE IF NOT EXISTS `content_analytics` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_analytics_user` ON `content_analytics` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_analytics_platform` ON `content_analytics` (`platform`);
CREATE INDEX IF NOT EXISTS `idx_analytics_date` ON `content_analytics` (`publish_date`);

CREATE TABLE IF NOT EXISTS `content_calendar` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `topic` VARCHAR(500) DEFAULT NULL,
    `status` TEXT DEFAULT 'idea',
    `publish_date` DATE DEFAULT NULL,
    `publish_time` TIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `color` VARCHAR(7) DEFAULT '#6C5CE7',
    `ai_generated` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_calendar_user` ON `content_calendar` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_calendar_date` ON `content_calendar` (`publish_date`);
CREATE INDEX IF NOT EXISTS `idx_calendar_status` ON `content_calendar` (`status`);

CREATE TABLE IF NOT EXISTS `chat_sessions` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) DEFAULT 'New Chat',
    `session_type` TEXT DEFAULT 'general',
    `is_active` TINYINT(1) DEFAULT 1,
    `message_count` INT DEFAULT 0,
    `last_message_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_chat_user` ON `chat_sessions` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_chat_active` ON `chat_sessions` (`is_active`);

CREATE TABLE IF NOT EXISTS `chat_messages` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `session_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` TEXT NOT NULL,
    `message` LONGTEXT NOT NULL,
    `metadata` JSON DEFAULT NULL,
    `tokens_used` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `chat_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_msg_session` ON `chat_messages` (`session_id`);
CREATE INDEX IF NOT EXISTS `idx_msg_user` ON `chat_messages` (`user_id`);

CREATE TABLE IF NOT EXISTS `saved_content` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `content_type` VARCHAR(100) DEFAULT NULL,
    `platform` VARCHAR(50) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `tags` TEXT DEFAULT NULL,
    `source` TEXT DEFAULT 'manual',
    `source_id` INT UNSIGNED DEFAULT NULL,
    `is_favorite` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_saved_user` ON `saved_content` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_saved_category` ON `saved_content` (`category`);
CREATE INDEX IF NOT EXISTS `idx_saved_type` ON `saved_content` (`content_type`);

CREATE TABLE IF NOT EXISTS `content_templates` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_template_active` ON `content_templates` (`is_active`);
CREATE INDEX IF NOT EXISTS `idx_template_platform` ON `content_templates` (`platform`);

CREATE TABLE IF NOT EXISTS `ai_prompt_templates` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `prompt_type` VARCHAR(50) NOT NULL,
    `system_prompt` LONGTEXT NOT NULL,
    `user_prompt_template` LONGTEXT DEFAULT NULL,
    `variables` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS `idx_prompt_type` ON `ai_prompt_templates` (`prompt_type`);
CREATE INDEX IF NOT EXISTS `idx_prompt_active` ON `ai_prompt_templates` (`is_active`);

CREATE TABLE IF NOT EXISTS `ai_requests` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `request_type` VARCHAR(50) NOT NULL,
    `ai_provider` VARCHAR(50) DEFAULT NULL,
    `ai_model` VARCHAR(50) DEFAULT NULL,
    `prompt_tokens` INT DEFAULT 0,
    `completion_tokens` INT DEFAULT 0,
    `total_tokens` INT DEFAULT 0,
    `response_time_ms` INT DEFAULT 0,
    `status` TEXT DEFAULT 'success',
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_req_user` ON `ai_requests` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_req_type` ON `ai_requests` (`request_type`);
CREATE INDEX IF NOT EXISTS `idx_req_date` ON `ai_requests` (`created_at`);

CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_usage_user` ON `ai_usage_logs` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_usage_date` ON `ai_usage_logs` (`created_at`);

CREATE TABLE IF NOT EXISTS `admin_logs` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS `idx_alog_admin` ON `admin_logs` (`admin_id`);
CREATE INDEX IF NOT EXISTS `idx_alog_date` ON `admin_logs` (`created_at`);

CREATE TABLE IF NOT EXISTS `notifications` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` TEXT DEFAULT 'info',
    `link` VARCHAR(500) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_notif_user` ON `notifications` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_notif_read` ON `notifications` (`is_read`);

CREATE TABLE IF NOT EXISTS `uploaded_files` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
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
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_files_user` ON `uploaded_files` (`user_id`);

CREATE TABLE IF NOT EXISTS `creator_memory` (
`id` INTEGER PRIMARY KEY AUTOINCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `memory_type` TEXT NOT NULL,
    `memory_key` VARCHAR(100) NOT NULL,
    `memory_value` TEXT NOT NULL,
    `confidence` DECIMAL(3,2) DEFAULT 1.00,
    `source` TEXT DEFAULT 'manual',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS `idx_memory_user` ON `creator_memory` (`user_id`);
CREATE INDEX IF NOT EXISTS `idx_memory_type` ON `creator_memory` (`memory_type`);

COMMIT;
