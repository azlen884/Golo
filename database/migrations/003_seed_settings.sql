-- Migration 003: Initial Platform Configuration & Default Settings
-- Version: 1.0.0

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `category`) VALUES
('site_name', 'Apex Gaming Platform', 'general'),
('site_tagline', 'Provably Fair Gaming & Infrastructure', 'general'),
('site_email', 'support@apexgaming.io', 'general'),
('currency_symbol', '$', 'financial'),
('currency_code', 'USD', 'financial'),
('min_deposit', '10.0000', 'financial'),
('max_deposit', '10000.0000', 'financial'),
('min_withdrawal', '20.0000', 'financial'),
('max_withdrawal', '5000.0000', 'financial'),
('referral_commission_rate', '5.00', 'financial'),
('kyc_required_for_withdrawal', '0', 'security'),
('max_login_attempts', '5', 'security'),
('lockout_duration_minutes', '15', 'security'),
('session_timeout_minutes', '120', 'security'),
('maintenance_mode', '0', 'maintenance'),
('maintenance_message', 'Platform is currently undergoing scheduled infrastructure upgrades. All user funds and bets are secure. We will be back online shortly.', 'maintenance'),
('github_repo', 'https://github.com/Azlenali007/Games.git', 'general'),
('installed_version', '1.0.0', 'general'),
('installed_commit', 'initial-base-v1.0.0', 'general'),
('last_update_check', '2026-10-01 00:00:00', 'general'),
('cron_last_run', NULL, 'cron'),
('cron_auto_settle', '1', 'cron'),
('cron_auto_rounds', '1', 'cron'),
('terms_content', 'Welcome to Apex Gaming Platform. By accessing or using our services, you agree to be bound by these Terms of Service. Users must be of legal age (18+ or the age of legal majority in your jurisdiction). Users are responsible for maintaining the confidentiality of their credentials and all activities that occur under their account.', 'legal'),
('privacy_content', 'We respect your privacy. Apex Gaming Platform encrypts sensitive user data and credentials. We do not sell or lease personal data to third parties. All financial transactions are protected using cryptographic hashing and strict relational database ledgers.', 'legal'),
('responsible_gaming_content', 'Gaming should always be a form of entertainment. Never wager money you cannot afford to lose. We support deposit limits, self-exclusion, and cool-off periods. If you feel your gaming is becoming problematic, please seek assistance from certified organizations.', 'legal');

INSERT IGNORE INTO `payment_settings` (`method_key`, `method_name`, `method_type`, `min_deposit`, `max_deposit`, `deposit_fee_percent`, `min_withdrawal`, `max_withdrawal`, `withdrawal_fee_percent`, `instructions`, `wallet_address`, `is_active`) VALUES
('usdt_trc20', 'Tether (USDT TRC20)', 'crypto', 10.0000, 25000.0000, 0.00, 20.0000, 10000.0000, 1.00, 'Send USDT exclusively over the TRC-20 network. Deposits are credited after 1 network confirmation.', 'TPApexPlatformMasterTRC20Address99812738', 1),
('btc', 'Bitcoin (BTC)', 'crypto', 25.0000, 50000.0000, 0.00, 50.0000, 25000.0000, 1.50, 'Send Bitcoin to the generated address. Requires 2 blockchain confirmations.', 'bc1qapexplatformmasterbtc998231478129381', 1),
('eth', 'Ethereum (ETH ERC20)', 'crypto', 20.0000, 30000.0000, 0.00, 30.0000, 15000.0000, 1.20, 'Send ETH to the ERC20 address. Deposits credit after 12 block confirmations.', '0xApexGamingPlatformVaultEthereum99823412', 1),
('bank_wire', 'International Bank Wire', 'bank_transfer', 100.0000, 100000.0000, 0.00, 100.0000, 50000.0000, 2.00, 'Include your Transaction Reference Code in the wire transfer memo. Processing takes 1-3 business days.', 'IBAN: APEX9982381273812 | BIC: APEXUS33', 1);

INSERT IGNORE INTO `promotions` (`title`, `slug`, `description`, `min_deposit`, `reward_percent`, `max_bonus`, `is_active`) VALUES
('100% First Deposit Match', 'welcome-bonus', 'Get a 100% deposit bonus match up to $500 on your first qualifying platform deposit. Standard wagering rules apply.', 20.0000, 100.00, 500.0000, 1),
('Weekly VIP Cashback', 'weekly-cashback', 'Earn 10% cashback every Monday calculated on net wagers across all platform rounds during the preceding 7 days.', 50.0000, 10.00, 1000.0000, 1);

-- Default Super Admin account: username 'admin', email 'admin@apexgame.com', password 'admin123456'
INSERT IGNORE INTO `admins` (`id`, `username`, `email`, `password_hash`, `full_name`, `is_super`, `status`) VALUES
(1, 'admin', 'admin@apexgame.com', '$2y$10$/WGBGPx6.J92ok6HzorfqucSYbd0I0UcvQphkhvERehV0gItPjoUS', 'Super Administrator', 1, 'active');

SET FOREIGN_KEY_CHECKS = 1;
