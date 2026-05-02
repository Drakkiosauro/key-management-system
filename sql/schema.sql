CREATE TABLE IF NOT EXISTS `keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(255) UNIQUE NOT NULL,
  `status` ENUM('unused', 'used', 'revoked', 'expired') DEFAULT 'unused',
  `user_id` VARCHAR(100),
  `username` VARCHAR(100),
  `display_name` VARCHAR(100),
  `account_age` INT,
  `is_premium` TINYINT DEFAULT 0,
  `voice_chat` TINYINT DEFAULT 0,
  `device_type` VARCHAR(50),
  `executor` VARCHAR(100),
  `hwid` VARCHAR(255),
  `ip` VARCHAR(45),
  `place_id` VARCHAR(100),
  `job_id` VARCHAR(100),
  `game_name` VARCHAR(255),
  `is_global` TINYINT DEFAULT 0,
  `allowed_game_id` VARCHAR(100),
  `allowed_script` VARCHAR(255),
  `activated_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_is_global` (`is_global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_code` VARCHAR(255),
  `action` VARCHAR(100),
  `user_id` VARCHAR(100),
  `username` VARCHAR(100),
  `display_name` VARCHAR(100),
  `account_age` INT,
  `is_premium` TINYINT,
  `voice_chat` TINYINT,
  `device_type` VARCHAR(50),
  `executor` VARCHAR(100),
  `hwid` VARCHAR(255),
  `ip` VARCHAR(45),
  `place_id` VARCHAR(100),
  `job_id` VARCHAR(100),
  `game_name` VARCHAR(255),
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_key_code` (`key_code`),
  INDEX `idx_action` (`action`),
  INDEX `idx_timestamp` (`timestamp`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) UNIQUE NOT NULL,
  `attempts` INT DEFAULT 1,
  `expires_at` TIMESTAMP,
  INDEX `idx_ip` (`ip`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `banned_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(100),
  `hwid` VARCHAR(255),
  `ip` VARCHAR(45),
  `reason` TEXT,
  `banned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `unbanned_at` TIMESTAMP NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_hwid` (`hwid`),
  INDEX `idx_ip` (`ip`),
  INDEX `idx_unbanned_at` (`unbanned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `allowed_games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_id` VARCHAR(100) UNIQUE NOT NULL,
  `game_name` VARCHAR(255) NOT NULL,
  `active` TINYINT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_game_id` (`game_id`),
  INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
