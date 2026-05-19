-- Run this against your railway database (e.g. via phpMyAdmin or mysql CLI)
-- Creates the tables expected by the app: site_users, messages, comment_likes

CREATE TABLE IF NOT EXISTS `site_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `profile_blob` MEDIUMBLOB DEFAULT NULL,
  `profile_blob_mime` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(32) NOT NULL DEFAULT 'user',
  `quiz_highscore` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `page` VARCHAR(255) NOT NULL DEFAULT 'unknown',
  `user_id` INT DEFAULT NULL,
  `parent_id` INT DEFAULT NULL,
  `like_count` INT NOT NULL DEFAULT 0,
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `edited_at` DATETIME DEFAULT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_page` (`page`),
  FOREIGN KEY (`user_id`) REFERENCES `site_users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `comment_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `liked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `comment_user` (`comment_id`, `user_id`),
  INDEX `idx_comment_id` (`comment_id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`comment_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `site_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
