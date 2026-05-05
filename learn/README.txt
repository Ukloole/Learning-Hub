====================================================
  UKLOOLE LEARNING HUB — Full Package README
  v4.1  |  April 2026
====================================================

WHAT'S INCLUDED
--------------
verify.php              — Certificate Verification System (navy + gold UI)
admin.php               — Admin Dashboard (password-protected, rate-limited)
resources.php           — Dynamic Resources/Materials page (now with embedded CV Builder)
courses.php             — Dynamic Courses page (now with sample-certificate sneak peek)
community.html          — Community/Guidance landing page
community-login.php     — Member login + first-time password setup
community-dashboard.php — Protected member area (post-login)
save-community-member.php — Saves new community members after Paystack payment
db.php                  — Shared MySQL connection helper
security.php            — Shared security helper (sessions, headers, CSRF)
SQL_schema.sql          — Full database schema + sample data
courses_schema.sql      — Schema + sample data for the dynamic courses
assessment_schema.sql   — Schema for the assessment_results table  (NEW)
assessment.php          — 40-question certification assessment page (NEW)
assessment-submit.php   — Auto-grader + result page                (NEW)
assessment_questions.php— Question bank (40 MCQs from the official paper)

INDEX PAGE / STATIC FILES
--------------------------
index.html, index.js, style.css, logo.png
courses.html (legacy redirect), privacy.html, terms.html
save-email.php, save-order.php, send-assessment.php
download.php, verify-payment.php
certificate-template.pdf
.htaccess               — Pretty URLs + security headers + sensitive file blocking

====================================================
  SETUP INSTRUCTIONS (cPanel)
====================================================

STEP 1 — UPLOAD FILES
  • Upload ALL files (including the hidden .htaccess) to your public_html
    directory (or a subdirectory, e.g. public_html/learn/).

STEP 2 — CREATE MYSQL DATABASE
  1. Open cPanel → MySQL Databases
  2. Create a database (default name used by this package: ukloolec_learninghub)
  3. Create a user (default: ukloolec_lbuser) and assign ALL PRIVILEGES
  4. Note down: DB host, DB name, DB username, DB password

STEP 3 — IMPORT SQL SCHEMAS  (run all three, in order)
  1. Open phpMyAdmin (from cPanel) → select your database
  2. Click Import → Choose File → select SQL_schema.sql       → Go
  3. Click Import → Choose File → select courses_schema.sql   → Go
  4. Click Import → Choose File → select assessment_schema.sql → Go    (NEW)

STEP 4 — CONFIGURE DB CONNECTION
  Open db.php and update the fallback values to match your cPanel
  credentials (or set DB_HOST / DB_USER / DB_PASS / DB_NAME as
  environment variables in cPanel → Setup PHP).

    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'YOUR_DB_USER';
    $db_pass = getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD';
    $db_name = getenv('DB_NAME') ?: 'YOUR_DB_NAME';

STEP 5 — UPDATE PAYSTACK KEY
  All Paystack flows (materials shop + community membership) share a
  single secret key.  Set it in:
    • save-community-member.php  (community payments)
    • verify-payment.php / save-order.php  (materials shop)
  The webhook routes by `purchase_type` metadata so one key handles both.

STEP 6 — CHANGE ADMIN PASSWORD (strongly recommended)
  Either set the ADMIN_PASSWORD environment variable in cPanel, OR
  open admin.php and change:
    $admin_password = getenv('ADMIN_PASSWORD') ?: 'Admin4ukloole';
  to a strong password.  Login is rate-limited (5 attempts → 5-min lock).

STEP 7 — ENABLE HTTPS
  In cPanel → SSL/TLS Status, install a free Let's Encrypt certificate
  for your domain.  Then uncomment the HTTPS-redirect block at the top
  of .htaccess.

====================================================
  PAGE URLS
====================================================

  Homepage:                /index.html
  Course (dynamic):        /courses.php
  Materials/Resources:     /resources.php       ← Dynamic + embedded CV Builder
  Certification Exam:      /assessment.php      ← NEW (40 Qs, 80% pass)
  Guidance/Community:      /community.html
  Community Login:         /community-login.php
  Community Dashboard:     /community-dashboard.php
  Verify Certificate:      /verify.php
  Admin Panel:             /admin.php

====================================================
  CERTIFICATION ASSESSMENT  (NEW)
====================================================

  • 40 multiple-choice questions covering Modules 1 & 2.
  • Pass mark: 80% (32 of 40 correct).
  • Auto-graded the moment the candidate clicks Submit.
  • Every attempt is logged in `assessment_results` (with score, %,
    pass/fail flag, IP, and full per-question breakdown JSON).
  • If the candidate passes, a `candidates` row with status='passed'
    is created automatically and the certificate code (UKL-YYYY-XXXXXX)
    is shown on the result page.
  • Admin → Assessment → Assessment Results shows live stats: total
    attempts, passes, pass rate, average score, and the latest 200
    submissions with cert codes.

====================================================
  CERTIFICATE VERIFICATION FLOW
====================================================

  1. Candidate passes the assessment OR is added by admin
  2. A code like UKL-2026-AB12CD is auto-generated
  3. Student or employer visits verify.php, enters the code
  4. Result: VERIFIED / NOT ISSUED / FAILED / INVALID

====================================================
  COMMUNITY PAYMENT FLOW
====================================================

  1. Visitor lands on community.html
  2. Enters name + email → clicks "Unlock Premium Access"
  3. Paystack processes the payment
  4. On success: save-community-member.php saves them to DB
  5. Auto-redirected to community-login.php
  6. First-time: clicks "Set up your password"
  7. Returns to login → accesses community-dashboard.php

====================================================
  ADMIN PANEL TABS
====================================================

  Dashboard            — Stats: revenue, orders, members, certs
  Orders & Payments    — All Paystack transactions
  Resources            — Add/edit/delete/show-hide paid & free downloads
  Certificates         — Add candidates, generate codes, mark Pass/Fail
  Community Members    — View premium members, remove if needed
  FAQ                  — Add/edit/reorder FAQ items
  Testimonials         — Add/delete testimonials
  Courses              — Modules, lessons, quizzes, scenarios
  Job Links            — Curated job opportunities for community
  Webinars             — Schedule and post webinars
  Q&A                  — Answer member questions (badge shows unanswered)
  Member Resources     — Premium-only resources separate from the shop
  Assessment Results   — NEW. Live grading dashboard for the cert exam.

====================================================
  SECURITY NOTES
====================================================

  • Sessions are hardened: HttpOnly + SameSite=Lax + Secure on HTTPS.
  • Admin login is rate-limited (5 fails → 5-min lockout, session-scoped).
  • Login form uses a CSRF token; assessment submission does too.
  • Sensitive headers sent on every response: X-Content-Type-Options,
    X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS.
  • .htaccess blocks direct access to .sql, .docx, .env, .log, .ini files
    and to db.php / security.php / assessment_questions.php.
  • All user input uses prepared statements + htmlspecialchars on output.
  • Member passwords are hashed with password_hash(PASSWORD_DEFAULT).
  • Use HTTPS (free via Let's Encrypt in cPanel) and uncomment the
    HTTPS redirect at the top of .htaccess.

====================================================
  SUPPORT
====================================================

  Email: learn@ukloole.com
  WhatsApp: https://wa.me/message/IQZ6JVHJGNETE1

====================================================
