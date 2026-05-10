-- IM System Database Schema for MySQL 5.7
-- Database: im_system

CREATE DATABASE IF NOT EXISTS im_system DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE im_system;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `nickname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0=禁用 1=正常 2=在线',
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Friendships Table
CREATE TABLE IF NOT EXISTS `friendships` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `friend_id` INT UNSIGNED NOT NULL,
    `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0=待确认 1=已添加 2=已拒绝',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_friendship` (`user_id`, `friend_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_friend_id` (`friend_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Private Messages Table
CREATE TABLE IF NOT EXISTS `private_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_user_id` INT UNSIGNED NOT NULL,
    `to_user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `message_type` TINYINT NOT NULL DEFAULT 1 COMMENT '1=文本 2=图片 3=文件',
    `is_read` TINYINT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_from_user` (`from_user_id`),
    KEY `idx_to_user` (`to_user_id`),
    KEY `idx_conversation` (`from_user_id`, `to_user_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups Table
CREATE TABLE IF NOT EXISTS `groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `owner_id` INT UNSIGNED NOT NULL,
    `invite_code` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_owner_id` (`owner_id`),
    KEY `idx_invite_code` (`invite_code`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group Members Table
CREATE TABLE IF NOT EXISTS `group_members` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `role` TINYINT NOT NULL DEFAULT 1 COMMENT '1=成员 2=管理员 3=群主',
    `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_user` (`group_id`, `user_id`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group Messages Table
CREATE TABLE IF NOT EXISTS `group_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `message_type` TINYINT NOT NULL DEFAULT 1 COMMENT '1=文本 2=图片 3=文件',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `role` TINYINT NOT NULL DEFAULT 1 COMMENT '1=普通管理员 2=超级管理员',
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
INSERT INTO `admins` (`username`, `password`, `role`) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);
