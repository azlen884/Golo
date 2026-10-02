-- Migration 001: Initial Schema for Apex Gaming Platform
-- Version: 1.0.0

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Drop existing platform tables to guarantee clean schema state
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `bonuses`;
DROP TABLE IF EXISTS `referrals`;
DROP TABLE IF EXISTS `support_messages`;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `withdrawals`;
DROP TABLE IF EXISTS `deposits`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `wallet_ledger`;
DROP TABLE IF EXISTS `wallets`;
DROP TABLE IF EXISTS `profiles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;

CREATE TABLE IF NOT EXISTS `system_migrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL UNIQUE,
  `version` VARCHAR(50) NOT NULL,
  `checksum` VARCHAR(64) NOT NULL,
  `status` ENUM('executed', 'failed') NOT NULL DEFAULT 'executed',
  `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL DEFAULT 'System Administrator',
  `is_super` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
  `referral_code` VARCHAR(32) NOT NULL UNIQUE,
  `referred_by` INT UNSIGNED NULL,
  `email_verified_at` DATETIME NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `two_factor_secret` VARCHAR(128) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_referral` (`referral_code`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `profiles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `first_name` VARCHAR(60) NULL,
  `last_name` VARCHAR(60) NULL,
  `phone` VARCHAR(30) NULL,
  `country` VARCHAR(60) NULL,
  `avatar` VARCHAR(255) NULL,
  `date_of_birth` DATE NULL,
  `address` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `kyc_status` ENUM('unverified', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'unverified',
  `kyc_notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_profiles_user_id` (`user_id`),
  CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
  `balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `bonus_balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `locked_balance` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_deposited` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_withdrawn` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_wagered` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `total_won` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_wallets_user_id` (`user_id`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_wallet_balance` (`balance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wallet_ledger` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `wallet_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_type` ENUM('deposit', 'withdrawal', 'bet_placed', 'bet_won', 'bet_refund', 'bonus_credit', 'referral_commission', 'admin_adjustment', 'fee') NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `balance_before` DECIMAL(16,4) NOT NULL,
  `balance_after` DECIMAL(16,4) NOT NULL,
  `reference_id` VARCHAR(100) NULL,
  `reference_type` VARCHAR(50) NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ledger_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_ledger_user` (`user_id`),
  INDEX `idx_ledger_type` (`transaction_type`),
  INDEX `idx_ledger_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `wallet_id` INT UNSIGNED NOT NULL,
  `transaction_ref` VARCHAR(64) NOT NULL UNIQUE,
  `type` ENUM('deposit', 'withdrawal', 'bet', 'win', 'bonus', 'referral', 'adjustment') NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `fee` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `net_amount` DECIMAL(16,4) NOT NULL,
  `status` ENUM('pending', 'completed', 'failed', 'cancelled', 'rejected') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NULL,
  `gateway_tx_id` VARCHAR(128) NULL,
  `meta_data` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  INDEX `idx_tx_ref` (`transaction_ref`),
  INDEX `idx_tx_user_status` (`user_id`, `status`),
  INDEX `idx_tx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deposits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` INT UNSIGNED NOT NULL,
  `deposit_ref` VARCHAR(64) NOT NULL UNIQUE,
  `payment_method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `fee` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `payment_proof` VARCHAR(255) NULL,
  `admin_note` TEXT NULL,
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_deposits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deposits_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  INDEX `idx_dep_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` INT UNSIGNED NOT NULL,
  `withdrawal_ref` VARCHAR(64) NOT NULL UNIQUE,
  `payment_method` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `fee` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `account_details` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'processing') NOT NULL DEFAULT 'pending',
  `admin_note` TEXT NULL,
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_withdrawals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_withdrawals_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  INDEX `idx_with_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `session_id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `last_activity` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_sess_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger', 'system') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `action_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_code` VARCHAR(32) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `department` VARCHAR(60) NOT NULL DEFAULT 'General Support',
  `subject` VARCHAR(191) NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('open', 'in_progress', 'answered', 'closed') NOT NULL DEFAULT 'open',
  `last_reply_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_ticket_user` (`user_id`),
  INDEX `idx_ticket_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_id` INT UNSIGNED NOT NULL,
  `sender_type` ENUM('user', 'admin') NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `attachments` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `referrals` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `referrer_id` INT UNSIGNED NOT NULL,
  `referee_id` INT UNSIGNED NOT NULL UNIQUE,
  `referral_code` VARCHAR(32) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `total_earnings` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active', 'pending') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_referrals_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_referrals_referee` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bonuses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `bonus_type` ENUM('welcome', 'deposit_match', 'cashback', 'vip', 'referral', 'loyalty') NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(16,4) NOT NULL,
  `wager_requirement` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `wagered_so_far` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `status` ENUM('active', 'completed', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bonuses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `promotions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT NOT NULL,
  `banner_image` VARCHAR(255) NULL,
  `bonus_code` VARCHAR(50) NULL,
  `min_deposit` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `reward_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `max_bonus` DECIMAL(16,4) NOT NULL DEFAULT 0.0000,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `start_date` DATETIME NULL,
  `end_date` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
