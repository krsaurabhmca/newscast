-- NewsCast Feature Upgrade: DB Migration
-- Run this SQL in your phpMyAdmin or MySQL client

-- 1. Password Reset Tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(128) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Reporter Payments & Earnings
CREATE TABLE IF NOT EXISTS `reporter_payments` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pay_type`    ENUM('salary','bonus','article_fee','expense','advance','other') DEFAULT 'salary',
  `pay_date`    DATE NOT NULL,
  `note`        TEXT,
  `status`      ENUM('paid','pending','cancelled') DEFAULT 'paid',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_reporter` (`reporter_id`),
  INDEX `idx_date` (`pay_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Optional: track auto-shares
CREATE TABLE IF NOT EXISTS `social_shares` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT NOT NULL,
  `platform`   ENUM('facebook','instagram','twitter') NOT NULL,
  `status`     ENUM('success','failed') DEFAULT 'success',
  `response`   TEXT,
  `shared_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
