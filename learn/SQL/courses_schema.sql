-- =========================================
-- UKLOOLE — Dynamic Courses Schema
-- Run this in phpMyAdmin → SQL tab
-- =========================================

-- Modules (e.g. Module 1, Module 2, Module 3)
CREATE TABLE IF NOT EXISTS course_modules (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(200) NOT NULL,
  sort_order SMALLINT     NOT NULL DEFAULT 0,
  is_active  TINYINT      NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Lessons (each belongs to a module)
CREATE TABLE IF NOT EXISTS course_lessons (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id    INT UNSIGNED NOT NULL,
  title        VARCHAR(200) NOT NULL,
  youtube_id   VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'YouTube video ID only, e.g. dQw4w9WgXcQ',
  sort_order   SMALLINT     NOT NULL DEFAULT 0,
  is_active    TINYINT      NOT NULL DEFAULT 1,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
  INDEX idx_module (module_id)
) ENGINE=InnoDB;

-- Quiz question per lesson (one per lesson)
CREATE TABLE IF NOT EXISTS lesson_quizzes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id      INT UNSIGNED NOT NULL UNIQUE,
  question       TEXT         NOT NULL,
  option_a       VARCHAR(300) NOT NULL,
  option_b       VARCHAR(300) NOT NULL,
  option_c       VARCHAR(300) NOT NULL,
  correct_option ENUM('a','b','c') NOT NULL DEFAULT 'a',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Scenario question per lesson (one per lesson)
CREATE TABLE IF NOT EXISTS lesson_scenarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id      INT UNSIGNED NOT NULL UNIQUE,
  question       TEXT         NOT NULL,
  option_a       VARCHAR(300) NOT NULL,
  option_b       VARCHAR(300) NOT NULL,
  option_c       VARCHAR(300) NOT NULL,
  correct_option ENUM('a','b','c') NOT NULL DEFAULT 'a',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- SAMPLE DATA (mirrors your current content)
-- =========================================

INSERT IGNORE INTO course_modules (id, title, sort_order) VALUES
(1, 'Foundations of Excellent Customer Service', 1),
(2, 'Tools, Tone & Professional Communication',  2),
(3, 'Remote Work Readiness & Career Launch',      3);

INSERT IGNORE INTO course_lessons (id, module_id, title, youtube_id, sort_order) VALUES
(1,  1, 'What is Great Customer Service?',    'dQw4w9WgXcQ', 1),
(2,  1, 'The Psychology of the Customer',     'VIDEO_ID_HERE', 2),
(3,  1, 'The Communication Formula',          'VIDEO_ID_HERE', 3),
(4,  1, 'Handling Difficult Customers',       'VIDEO_ID_HERE', 4),
(5,  2, 'Mastering Email & Chat Support',     'VIDEO_ID_HERE', 1),
(6,  2, 'Using CRM Tools',                    'VIDEO_ID_HERE', 2),
(7,  2, 'Tone, Language & Professionalism',   'VIDEO_ID_HERE', 3),
(8,  2, 'Writing Effective Responses',        'VIDEO_ID_HERE', 4),
(9,  3, 'Setting Up Your Remote Workspace',   'VIDEO_ID_HERE', 1),
(10, 3, 'Time Management for Remote Workers', 'VIDEO_ID_HERE', 2),
(11, 3, 'Building a Remote Career',           'VIDEO_ID_HERE', 3),
(12, 3, 'Final Review & Certification Prep',  'VIDEO_ID_HERE', 4);

INSERT IGNORE INTO lesson_quizzes (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES
(1, 'Which sounds most customer friendly?', 'Let\'s see how I can help you resolve this', 'You didn\'t follow the process', 'Read the policy', 'a'),
(2, 'What emotion is showing up? "I just want to be sure everything went through"', 'Anxious', 'Confused', 'Neutral/Happy', 'a'),
(3, 'The main goal of clarifying is to?', 'Control the conversation politely and avoid assumptions', 'Sound intelligent', 'Delay giving a solution', 'a');

INSERT IGNORE INTO lesson_scenarios (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES
(1, 'A customer says: "I\'ve sent three emails and nobody has replied me. This is frustrating." Which reply shows the best empathy?',
   'I\'m really sorry about this delay. I understand how frustrating that must be, and I\'m here to help now.',
   'Our response time is 48 hours',
   'We are checking your issue', 'a'),
(2, '"This is taking too long, I\'m tired of following up."', 'Angry', 'Confused', 'Neutral/Happy', 'a'),
(3, 'A customer asks why their order is delayed. You don\'t have the info yet. What do you say?',
   'Let me look into this right now and get back to you with an update within the hour.',
   'I don\'t know, contact the warehouse.',
   'It will arrive soon.', 'a');
