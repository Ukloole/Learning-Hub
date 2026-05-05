-- =========================================
-- UKLOOLE Learning Hub — Database Schema v4
-- Run this on your MySQL/MariaDB server
-- =========================================


-- Orders table
CREATE TABLE IF NOT EXISTS orders (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150)  NOT NULL,
  email       VARCHAR(200)  NOT NULL,
  product     VARCHAR(200)  NOT NULL,
  reference   VARCHAR(100)  NOT NULL UNIQUE,
  token       VARCHAR(60)   UNIQUE,
  amount      DECIMAL(10,2) DEFAULT 0,
  status      ENUM('paid','pending','failed') DEFAULT 'paid',
  downloaded  SMALLINT UNSIGNED DEFAULT 0,
  expires_at  DATETIME,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_token (token)
) ENGINE=InnoDB;

-- Coaching bookings
CREATE TABLE IF NOT EXISTS coaching_bookings (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(150)  NOT NULL,
  email          VARCHAR(200)  NOT NULL,
  preferred_date DATE          NOT NULL,
  goals          TEXT,
  reference      VARCHAR(100)  NOT NULL UNIQUE,
  amount         DECIMAL(10,2) DEFAULT 40000,
  status         ENUM('paid','pending') DEFAULT 'paid',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Candidates / Certificate Verification System
-- Stores candidates for the certificate verification system.
-- Admin can add candidates, generate codes, and update status.
-- verify.php checks this table to show VERIFIED / NOT ISSUED / INVALID
CREATE TABLE IF NOT EXISTS candidates (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_code VARCHAR(30)   NOT NULL UNIQUE COMMENT 'Format: UKL-YYYY-XXXXXX',
  full_name        VARCHAR(150)  NOT NULL,
  course_title     VARCHAR(200)  NOT NULL DEFAULT 'Customer Service Mastery',
  status           ENUM('pending','passed','failed') NOT NULL DEFAULT 'pending'
                   COMMENT 'pending = not yet issued, passed = verified, failed = did not pass',
  issue_date       DATE          NULL COMMENT 'Set automatically when status is changed to passed',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cert_code (certificate_code),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- Old certificates table (kept for backward compatibility)
CREATE TABLE IF NOT EXISTS certificates (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  certificate_id  VARCHAR(30)  NOT NULL UNIQUE,
  holder_name     VARCHAR(150) NOT NULL,
  email           VARCHAR(200) NOT NULL,
  course_name     VARCHAR(200) NOT NULL DEFAULT 'Customer Service Mastery',
  issued_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at      DATETIME NULL,
  INDEX idx_cert_id (certificate_id)
) ENGINE=InnoDB;

-- Community members (paid premium access)
CREATE TABLE IF NOT EXISTS community_members (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150)  NOT NULL,
  email       VARCHAR(200)  NOT NULL UNIQUE,
  reference   VARCHAR(100)  NOT NULL,
  amount      DECIMAL(10,2) DEFAULT 25000,
  username    VARCHAR(100)  NULL COMMENT 'Set after user creates login credentials',
  password_hash VARCHAR(255) NULL,
  status      ENUM('active','expired','suspended') DEFAULT 'active',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB;

-- FAQ entries
CREATE TABLE IF NOT EXISTS faqs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question    TEXT         NOT NULL,
  answer      TEXT         NOT NULL,
  sort_order  SMALLINT     DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(150) NOT NULL,
  role             VARCHAR(200) NOT NULL DEFAULT '',
  content          TEXT         NOT NULL,
  rating           TINYINT      DEFAULT 5,
  avatar_initials  CHAR(2)      NOT NULL DEFAULT 'UK',
  created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Resources (paid products + free downloads)
CREATE TABLE IF NOT EXISTS resources (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200)  NOT NULL,
  description TEXT          NOT NULL DEFAULT '',
  price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  type        ENUM('paid','free') NOT NULL DEFAULT 'paid',
  file_key    VARCHAR(300)  NOT NULL DEFAULT '',
  is_active   TINYINT       NOT NULL DEFAULT 1,
  sort_order  SMALLINT      NOT NULL DEFAULT 0,
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_type (type),
  INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =============================================
-- SAMPLE DATA
-- =============================================

INSERT IGNORE INTO faqs (question, answer, sort_order) VALUES
('Do I need any experience to start?', 'No experience is required. Our course is designed for complete beginners. If you can read and write, you can start today.', 1),
('How long does the course take to complete?', 'The course has 3 modules and 12 lessons. Most students finish in 1–2 weeks at their own pace.', 2),
('What is the cost of the course?', 'The core course is 100% free. You only pay for premium add-ons like the certificate (₦5,000) or premium membership (₦25,000).', 3),
('How do I get my certificate?', 'Complete all lessons, take the assessment on the Guidance page, pay ₦5,000 via Paystack, and your certificate is issued instantly.', 4),
('Can I really get a remote job with this?', 'Yes! Many of our graduates now work remotely. Our premium package includes job links, CV templates, and application guidance.', 5),
('What does the 1-on-1 coaching cover?', 'A personalised career strategy, CV and LinkedIn review, mock interview practice, and a job search roadmap — plus 7 days of email follow-up.', 6);

INSERT IGNORE INTO resources (name, description, price, type, file_key, is_active, sort_order) VALUES
('Customer Service Workbook',  'Interactive exercises to sharpen communication and support skills.',  2000, 'paid', 'customer-service-workbook.pdf', 1, 1),
('Cover Letter Pack',          '5 proven cover letter templates for remote jobs.',                    999,  'paid', 'cover-letter-pack.pdf',         1, 2),
('Interview Cheat Sheet',      '16 interview questions, answers and how to personalize them.',        1999, 'paid', 'interview-cheat-sheet.pdf',     1, 3),
('Customer Support Scripts',   'Ready-to-use responses for 50+ customer scenarios.',                 2500, 'paid', 'customer-support-scripts.pdf',  1, 4),
('Ultimate Job Bundle',        'All materials above + interview guide in one package.',              7000, 'paid', 'ultimate-job-bundle.zip',       1, 5),
('Course Slides',              'Complete slide deck from all 3 modules of the course.',              0,    'free', 'course-slides.pdf',             1, 6),
('Remote Job Readiness',       'Everything you need to find, apply for, and land a remote job.',     0,    'free', 'remote-job-readiness.pdf',      1, 7);

-- Sample candidate
INSERT IGNORE INTO candidates (certificate_code, full_name, course_title, status, issue_date) VALUES
('UKL-2026-DEMO01', 'Demo Candidate', 'Customer Service Mastery', 'passed', '2026-01-01');
