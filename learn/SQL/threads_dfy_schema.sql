-- =========================================
-- UKLOOLE — Q&A Threads + DFY Applications
-- Run this in phpMyAdmin → SQL tab
-- =========================================

CREATE TABLE IF NOT EXISTS community_threads (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id  INT UNSIGNED NOT NULL,
  subject    VARCHAR(300) NOT NULL,
  status     ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_member (member_id),
  INDEX idx_updated (updated_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS community_thread_messages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  thread_id  INT UNSIGNED NOT NULL,
  sender     ENUM('member','admin') NOT NULL,
  body       TEXT NOT NULL,
  edited_at  DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (thread_id) REFERENCES community_threads(id) ON DELETE CASCADE,
  INDEX idx_thread (thread_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dfy_applications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(150) NOT NULL,
  email       VARCHAR(200) NOT NULL,
  target_role VARCHAR(200) NOT NULL,
  experience  VARCHAR(20)  NOT NULL,
  has_cv      VARCHAR(5)   NOT NULL,
  countries   VARCHAR(300) NOT NULL,
  start_when  VARCHAR(50)  NOT NULL,
  extra_info  TEXT         NULL,
  member_id   INT UNSIGNED NULL,
  status      ENUM('new','reviewing','contacted','closed') DEFAULT 'new',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS community_updates (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type       ENUM('update','opportunity','announcement') NOT NULL DEFAULT 'update',
  title      VARCHAR(200) NOT NULL,
  body       TEXT         NOT NULL,
  is_active  TINYINT      NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
