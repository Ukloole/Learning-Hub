<?php
/**
 * UKLOOLE — Dynamic Courses Page
 * Loads modules, lessons, quizzes and scenarios from the database
 */

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    die("Database connection failed. Please try again later.");
}
$conn->set_charset('utf8mb4');

// Load all active modules
$modules = [];
$mod_res = $conn->query("SELECT * FROM course_modules WHERE is_active=1 ORDER BY sort_order ASC");
while ($m = $mod_res->fetch_assoc()) $modules[] = $m;

// Load all active lessons with their quiz and scenario
$lessons_by_module = [];
$lesson_res = $conn->query("
    SELECT
        l.*,
        q.question  AS quiz_q, q.option_a AS quiz_a, q.option_b AS quiz_b, q.option_c AS quiz_c, q.correct_option AS quiz_correct,
        s.question  AS sc_q,   s.option_a AS sc_a,   s.option_b AS sc_b,   s.option_c AS sc_c,   s.correct_option AS sc_correct
    FROM course_lessons l
    LEFT JOIN lesson_quizzes   q ON q.lesson_id = l.id
    LEFT JOIN lesson_scenarios s ON s.lesson_id = l.id
    WHERE l.is_active = 1
    ORDER BY l.module_id ASC, l.sort_order ASC
");
$lesson_counter = 0;
while ($row = $lesson_res->fetch_assoc()) {
    $lessons_by_module[$row['module_id']][] = $row;
}

$conn->close();

// Total lesson count for display
$total_lessons = 0;
foreach ($lessons_by_module as $group) $total_lessons += count($group);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-8RH12T08CY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-8RH12T08CY');
</script>
  <meta charset="UTF-8" />
  <link rel="icon" href="/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Customer Service Course — Ukloole</title>
  <meta name="description" content="Learn customer service step by step. Free modules, quizzes and scenarios — all free.">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="container">
      <a href="/" class="navbar-brand">
        <img src="/logo.png" alt="Ukloole Logo"><span>Ukloole</span>
      </a>
      <ul class="nav-links">
        <li><a href="/">Home</a></li>
        <li><a href="/courses" class="active">Course</a></li>
        <li><a href="/resources">Materials</a></li>
        <li><a href="/community">Guidance</a></li>
      </ul>
      <div class="nav-actions">
        <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </nav>

  <div class="mobile-menu" id="mobile-menu">
    <a href="/">Home</a>
    <a href="/courses" class="active">Course</a>
    <a href="/resources">Materials</a>
    <a href="/community">Guidance</a>
  </div>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <div class="container">
      <span class="badge">Professional Certificate</span>
      <h1>The Professional Customer Service Mastery Course</h1>
      <p>Master the art of customer support, learn industry-standard CRM tools,<br>and prepare for global remote opportunities.</p>
    </div>
  </section>

  <!-- COURSE CONTENT -->
  <section class="section">
    <div class="container" style="max-width:860px;">

      <?php if (empty($modules)): ?>
        <div style="text-align:center;padding:60px 0;color:var(--muted);">
          <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:16px;display:block;"></i>
          <p>Course content is being set up. Check back soon!</p>
        </div>
      <?php endif; ?>

      <?php
      $global_lesson_num = 0;
      $global_quiz_num   = 0;
      foreach ($modules as $mod):
        $mod_lessons = $lessons_by_module[$mod['id']] ?? [];
        if (empty($mod_lessons)) continue;
      ?>

      <!-- MODULE -->
      <div class="module-header">
        <div class="module-number"><?= $mod['sort_order'] ?></div>
        <h3><?= htmlspecialchars($mod['title']) ?></h3>
      </div>

      <?php foreach ($mod_lessons as $lesson):
        $global_lesson_num++;
        $global_quiz_num++;
        $qname = 'q' . $global_quiz_num; // unique radio group name
      ?>

      <!-- LESSON -->
      <div class="lesson-card">
        <div class="lesson-header">
          <div class="lesson-header-left">
            <div class="lesson-icon"><i class="fas fa-play"></i></div>
            <span class="lesson-title">Lesson <?= $global_lesson_num ?>: <?= htmlspecialchars($lesson['title']) ?></span>
          </div>
          <span class="lesson-chevron">▼</span>
        </div>
        <div class="lesson-body">

          <!-- VIDEO -->
          <?php if (!empty($lesson['youtube_id']) && $lesson['youtube_id'] !== 'VIDEO_ID_HERE'): ?>
          <div class="video-wrapper">
            <iframe
              src="https://www.youtube.com/embed/<?= htmlspecialchars($lesson['youtube_id']) ?>"
              allowfullscreen
              title="<?= htmlspecialchars($lesson['title']) ?>">
            </iframe>
          </div>
          <?php else: ?>
          <div style="background:var(--bg);border-radius:12px;padding:32px;text-align:center;color:var(--muted);margin-bottom:20px;">
            <i class="fas fa-video" style="font-size:2rem;margin-bottom:10px;display:block;opacity:.4;"></i>
            <p style="font-size:.9rem;">Video coming soon</p>
          </div>
          <?php endif; ?>

          <!-- QUIZ -->
          <?php if (!empty($lesson['quiz_q'])): ?>
          <div class="quiz-box">
            <button class="collapsible-trigger">📝 Quiz <span>▼</span></button>
            <div class="collapsible-body">
              <p class="quiz-question"><?= htmlspecialchars($lesson['quiz_q']) ?></p>
              <div class="quiz-options">
                <label class="quiz-option">
                  <input type="radio" name="<?= $qname ?>"
                    <?= $lesson['quiz_correct'] === 'a' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['quiz_a']) ?>
                </label>
                <label class="quiz-option">
                  <input type="radio" name="<?= $qname ?>"
                    <?= $lesson['quiz_correct'] === 'b' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['quiz_b']) ?>
                </label>
                <label class="quiz-option">
                  <input type="radio" name="<?= $qname ?>"
                    <?= $lesson['quiz_correct'] === 'c' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['quiz_c']) ?>
                </label>
              </div>
              <button class="quiz-submit">Submit Answer</button>
              <div class="quiz-feedback"></div>
            </div>
          </div>
          <?php endif; ?>

          <!-- SCENARIO -->
          <?php if (!empty($lesson['sc_q'])): ?>
          <div class="scenario-box">
            <button class="collapsible-trigger">🎭 Scenario <span>▼</span></button>
            <div class="collapsible-body">
              <p class="quiz-question"><?= htmlspecialchars($lesson['sc_q']) ?></p>
              <div class="scenario-options">
                <button class="scenario-btn" <?= $lesson['sc_correct'] === 'a' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['sc_a']) ?>
                </button>
                <button class="scenario-btn" <?= $lesson['sc_correct'] === 'b' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['sc_b']) ?>
                </button>
                <button class="scenario-btn" <?= $lesson['sc_correct'] === 'c' ? 'data-correct="true"' : '' ?>>
                  <?= htmlspecialchars($lesson['sc_c']) ?>
                </button>
              </div>
              <div class="quiz-feedback"></div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <?php endforeach; // lessons ?>
      <?php endforeach; // modules ?>

      <!-- CERTIFICATE SNEAK PEEK -->
      <?php if (!empty($modules)): ?>
      <div class="cert-peek" aria-label="Certificate preview">
        <div class="cert-peek-head">
          <span class="badge">Sneak Peek</span>
          <h2>This is what your certificate will look like</h2>
          <p>A preview of the official Customer Service Mastery Certificate, awarded after you pass the assessment.</p>
        </div>
        <div class="cert-peek-frame">
          <!-- Desktop: PDF iframe (Chrome/Firefox support) -->
          <iframe
            class="cert-iframe"
            src="certificate-template.pdf#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
            title="Sample Ukloole Certificate"
            loading="lazy"></iframe>
          <!-- Mobile fallback: image (iOS/Android don't render PDF iframes) -->
          <img
            class="cert-img-fallback"
            src="certificate-template.jpg"
            alt="Sample Ukloole Certificate"
            loading="lazy">
          <div class="cert-peek-fade" aria-hidden="true"></div>
          <div class="cert-peek-watermark" aria-hidden="true">SAMPLE</div>
        </div>
      </div>

      <style>
        .cert-peek { margin: 60px 0 30px; background: linear-gradient(180deg, #FFF8E7 0%, #ffffff 100%); border:1px solid #E2E8F0; border-radius:20px; padding: 32px 28px 28px; box-shadow: 0 8px 30px rgba(13,27,62,.08); }
        .cert-peek-head { text-align:center; margin-bottom:22px; }
        .cert-peek-head .badge { display:inline-block; background:#C9A84C; color:white; padding:4px 14px; border-radius:50px; font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; margin-bottom:10px; }
        .cert-peek-head h2 { font-family:'EB Garamond',serif; font-size:1.65rem; color:var(--secondary, #1A2D5A); margin-bottom:6px; }
        .cert-peek-head p { color:var(--muted, #6B7A99); font-size:.95rem; }
        .cert-peek-frame { position:relative; width:100%; padding-top: 70.7%; /* A4 landscape ratio */ background:#fafbff; border:1px solid #E2E8F0; border-radius:14px; overflow:hidden; box-shadow: 0 6px 24px rgba(13,27,62,.12); }
        .cert-peek-frame iframe { position:absolute; inset:0; width:100%; height:100%; border:none; pointer-events:none; }
        .cert-peek-frame .cert-img-fallback { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; border:none; display:none; }
        .cert-peek-fade { position:absolute; left:0; right:0; bottom:0; height:55%;
          background: linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.55) 28%, rgba(255,255,255,0.95) 65%, #ffffff 100%);
          backdrop-filter: blur(6px);
          -webkit-backdrop-filter: blur(6px);
        }
        .cert-peek-watermark { position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
          font-family:'EB Garamond', serif; font-weight:700; font-size: clamp(3rem, 12vw, 8rem);
          color: rgba(201,168,76,.22); letter-spacing:.4em; text-transform:uppercase;
          transform: rotate(-22deg); pointer-events:none; user-select:none;
          text-shadow: 0 2px 4px rgba(255,255,255,.4);
        }
        .cert-peek-note { text-align:center; color:var(--muted, #6B7A99); font-size:.85rem; margin-top:14px; font-style:italic; }
        @media (max-width:600px){
          .cert-peek { padding:22px 16px; }
          .cert-peek-head h2 { font-size:1.3rem; }
          /* PDF iframes don't work on mobile — show image instead */
          .cert-peek-frame .cert-iframe { display:none; }
          .cert-peek-frame .cert-img-fallback { display:block; }
        }
      </style>

      <!-- COMPLETED CTA -->
      <div class="cta-box" style="margin-top:30px;">
        <h2>Ready to Get Certified?</h2>
        <p>You've completed the course! Take the assessment and earn your official Ukloole Certificate.</p>
        <div class="hero-cta" style="justify-content:center;">
          <button onclick="document.getElementById('assess-gate').style.display='flex'" class="btn btn-lg" style="background:white;color:var(--primary);font-weight:700;">
            <i class="fas fa-certificate"></i> Start Assessment
          </button>
          <a href="/resources" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.3);">
            <i class="fas fa-download"></i> Download Study Materials
          </a>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <div class="footer-brand"><img src="/logo.png" alt="Ukloole Logo"><span>Ukloole</span></div>
          <p class="footer-tagline">Tech and Talent For The Modern Business.</p>
          <div class="social-links">
            <a href="https://www.linkedin.com/company/ukloole/" class="social-link"><i class="fab fa-linkedin-in"></i></a>
            <a href="https://www.instagram.com/ukloole/" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="https://www.facebook.com/ukloole" class="social-link"><i class="fab fa-facebook-f"></i></a>
          </div>
        </div>
        <div>
          <h4 class="footer-title">Quick Links</h4>
          <ul class="footer-links">
            <li><a href="/verify">Verify Certificate</a></li>
            <li><a href="https://www.ukloole.com" target="_blank">B2B Services</a></li>
            <li><a href="/privacy" target="_blank">Privacy Policy</a></li>
            <li><a href="/terms" target="_blank">Terms of Service</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">Contact</h4>
          <div class="footer-contact">
            <a href="mailto:learn@ukloole.com">✉ learn@ukloole.com</a>
            <a href="https://wa.me/message/IQZ6JVHJGNETE1">💬 WhatsApp: +234 810 159 3648</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">&copy; 2026 Ukloole. All rights reserved.</div>
    </div>
  </footer>

  <script src="/index.js"></script>

<!-- Assessment Email Gate -->
<div id="assess-gate" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:999;align-items:center;justify-content:center;padding:16px;">
  <div style="background:#fff;border-radius:20px;padding:36px 28px;max-width:420px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="font-size:2.5rem;margin-bottom:12px">📝</div>
    <h2 style="font-size:1.5rem;color:var(--navy);margin-bottom:8px">Start Your Assessment</h2>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:24px">Enter your details to receive your unique assessment link via email.</p>
    <input type="text" id="ag-name" placeholder="Your Full Name" style="width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:10px;font-size:.95rem;margin-bottom:12px;box-sizing:border-box;font-family:inherit">
    <input type="email" id="ag-email" placeholder="Your Email Address" style="width:100%;padding:12px 16px;border:1.5px solid var(--border);border-radius:10px;font-size:.95rem;margin-bottom:20px;box-sizing:border-box;font-family:inherit">
    <button onclick="submitAssessGate()" style="width:100%;padding:14px;background:var(--primary);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;font-family:inherit">
      <i class="fas fa-envelope"></i> Send Assessment Link
    </button>
    <button onclick="document.getElementById('assess-gate').style.display='none'" style="margin-top:10px;background:none;border:none;color:var(--muted);cursor:pointer;font-size:.85rem;font-family:inherit;text-decoration:underline">Cancel</button>
  </div>
</div>
<script>
function submitAssessGate() {
  const name  = document.getElementById('ag-name').value.trim();
  const email = document.getElementById('ag-email').value.trim();
  if (!name) { alert('Please enter your name.'); return; }
  if (!email || !email.includes('@')) { alert('Please enter a valid email.'); return; }

  const btn = event.currentTarget;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

  // Send assessment link via email (same flow as community page)
  fetch('/send-assessment', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'name=' + encodeURIComponent(name) + '&email=' + encodeURIComponent(email)
  })
  .then(r => r.text())
  .then(data => {
    // Also save to newsletter list
    fetch('/save-email', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'email=' + encodeURIComponent(email) + '&file=assessment'
    });
    if (data.trim() === 'success') {
      document.getElementById('assess-gate').style.display = 'none';
      alert('Assessment link sent! Please check your email to begin.');
    } else {
      alert('Could not send the link. Please try again.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-envelope"></i> Send Assessment Link';
    }
  })
  .catch(() => {
    alert('Network error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-envelope"></i> Send Assessment Link';
  });
}
</script>
</body>
</html>