-- =========================================
-- UKLOOLE — Community Dashboard Schema
-- Run this in phpMyAdmin → SQL tab
-- =========================================

-- Job links (admin posts, members see Open button)
CREATE TABLE IF NOT EXISTS community_job_links (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(200) NOT NULL,
  url         TEXT         NOT NULL,
  expires_at  DATE         NULL COMMENT 'Hide after this date, NULL = never expires',
  is_active   TINYINT      NOT NULL DEFAULT 1,
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Webinar / meeting links (admin posts quick updates)
CREATE TABLE IF NOT EXISTS community_webinars (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(200) NOT NULL,
  description TEXT         NULL,
  url         TEXT         NOT NULL,
  event_date  DATETIME     NULL,
  is_active   TINYINT      NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Q&A: questions submitted by members
CREATE TABLE IF NOT EXISTS community_questions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id   INT UNSIGNED NOT NULL,
  question    TEXT         NOT NULL,
  answer      TEXT         NULL COMMENT 'Admin reply — NULL means unanswered',
  answered_at DATETIME     NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member (member_id)
) ENGINE=InnoDB;

-- Community-exclusive resources (separate from the shop)
CREATE TABLE IF NOT EXISTS community_resources (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(200) NOT NULL,
  description TEXT         NULL,
  url         TEXT         NOT NULL COMMENT 'Google Drive, Dropbox, or any file link',
  is_active   TINYINT      NOT NULL DEFAULT 1,
  sort_order  SMALLINT     NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Secure link redirect table (obfuscates job URLs)
CREATE TABLE IF NOT EXISTS community_link_tokens (
  token       VARCHAR(64)  NOT NULL PRIMARY KEY,
  job_link_id INT UNSIGNED NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_job (job_link_id)
) ENGINE=InnoDB;
