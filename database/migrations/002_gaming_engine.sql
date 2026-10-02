-- Migration 002: Common Gaming Platform Engine & Logs
-- Version: 1.0.0

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE IF NOT EXISTS `rounds` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `round_code` VARCHAR(64) NOT NULL UNIQUE,
  `status` ENUM('scheduled', 'open', 'betting_closed', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
  `total_bets_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_pool_amount` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_payout_amount` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `scheduled_start` DATETIME NOT NULL,
  `betting_end` DATETIME NOT NULL,
  `round_end` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_round_status` (`status`),
  INDEX `idx_round_dates` (`scheduled_start`, `round_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `bet_code` VARCHAR(64) NOT NULL UNIQUE,
  `round_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `potential_payout` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `actual_payout` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `selection_data` TEXT NOT NULL,
  `status` ENUM('pending', 'won', 'lost', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
  `odds` DECIMAL(8,4) NOT NULL DEFAULT 1.0000,
  `placed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `settled_at` DATETIME NULL,
  CONSTRAINT `fk_bets_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_bet_round_user` (`round_id`, `user_id`),
  INDEX `idx_bet_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `results` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `round_id` INT UNSIGNED NOT NULL UNIQUE,
  `result_data` TEXT NOT NULL,
  `rng_seed` VARCHAR(128) NOT NULL,
  `server_hash` VARCHAR(128) NOT NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `declared_by` VARCHAR(50) NOT NULL DEFAULT 'CRON_RNG',
  `declared_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_results_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settlements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `round_id` INT UNSIGNED NOT NULL UNIQUE,
  `total_winners` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_losers` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_pool` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_paid` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `platform_margin` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `settled_by` VARCHAR(50) NOT NULL DEFAULT 'CRON_MASTER',
  `settled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_settlements_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admin_activity` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `target_type` VARCHAR(50) NULL,
  `target_id` VARCHAR(50) NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_admin_act_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  INDEX `idx_admin_act_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_activity` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_type` ENUM('user', 'admin') NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `status` ENUM('success', 'failed') NOT NULL,
  `failure_reason` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_act_user` (`user_type`, `user_id`),
  INDEX `idx_login_act_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(50) NOT NULL,
  `user_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `request_uri` VARCHAR(255) NOT NULL,
  `payload` TEXT NULL,
  `severity` ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'low',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sec_event` (`event_type`),
  INDEX `idx_sec_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` LONGTEXT NULL,
  `category` ENUM('general', 'game', 'security', 'financial', 'legal', 'maintenance', 'cron') NOT NULL DEFAULT 'general',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `method_key` VARCHAR(50) NOT NULL UNIQUE,
  `method_name` VARCHAR(100) NOT NULL,
  `method_type` ENUM('crypto', 'bank_transfer', 'upi', 'card', 'manual') NOT NULL DEFAULT 'crypto',
  `min_deposit` DECIMAL(16,4) NOT NULL DEFAULT 10.0000,
  `max_deposit` DECIMAL(16,4) NOT NULL DEFAULT 10000.0000,
  `deposit_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `min_withdrawal` DECIMAL(16,4) NOT NULL DEFAULT 20.0000,
  `max_withdrawal` DECIMAL(16,4) NOT NULL DEFAULT 5000.0000,
  `withdrawal_fee_percent` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `instructions` TEXT NULL,
  `qr_code_url` VARCHAR(255) NULL,
  `wallet_address` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `task_name` VARCHAR(100) NOT NULL,
  `status` ENUM('started', 'success', 'failed', 'locked') NOT NULL,
  `execution_time_ms` INT UNSIGNED NOT NULL DEFAULT 0,
  `output_summary` TEXT NULL,
  `error_message` TEXT NULL,
  `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cron_locks` (
  `lock_key` VARCHAR(64) PRIMARY KEY,
  `locked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `update_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `previous_commit` VARCHAR(100) NULL,
  `new_commit` VARCHAR(100) NOT NULL,
  `backup_file` VARCHAR(255) NULL,
  `backup_status` ENUM('success', 'failed', 'skipped') NOT NULL DEFAULT 'skipped',
  `migration_status` ENUM('success', 'failed', 'none') NOT NULL DEFAULT 'none',
  `update_status` ENUM('success', 'failed', 'rolled_back') NOT NULL DEFAULT 'success',
  `error_details` TEXT NULL,
  `admin_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `backups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `backup_name` VARCHAR(150) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `backup_type` ENUM('full', 'database', 'files') NOT NULL DEFAULT 'database',
  `created_by` VARCHAR(100) NOT NULL DEFAULT 'SYSTEM',
  `status` ENUM('completed', 'failed') NOT NULL DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
