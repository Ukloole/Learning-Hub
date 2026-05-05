-- =========================================
-- UKLOOLE — Assessment Results Schema
-- Run this in phpMyAdmin → SQL tab
-- =========================================

CREATE TABLE IF NOT EXISTS assessment_results (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(150) NOT NULL,
  email            VARCHAR(200) NOT NULL,
  score            TINYINT UNSIGNED NOT NULL,
  total            TINYINT UNSIGNED NOT NULL DEFAULT 40,
  percentage       DECIMAL(5,2) NOT NULL,
  pass             TINYINT(1)   NOT NULL DEFAULT 0,
  answers_json     TEXT         NULL COMMENT 'JSON of submitted answers per question',
  certificate_code VARCHAR(30)  NULL COMMENT 'Linked candidate code if passed',
  ip_address       VARCHAR(45)  NULL,
  user_agent       VARCHAR(255) NULL,
  taken_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email  (email),
  INDEX idx_pass   (pass),
  INDEX idx_taken  (taken_at)
) ENGINE=InnoDB;
