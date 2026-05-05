<?php
/**
 * UKLOOLE — Certification Assessment
 * Auto-graded multiple-choice exam (40 questions, 80% pass mark).
 * Source paper: Assessment_QuestionPaper_AnswerKey.docx
 */
require_once __DIR__ . '/security.php';
session_start();

$questions = require __DIR__ . '/assessment_questions.php';

// On GET, if the user just finished, the result page is rendered by assessment-submit.php
// then it can redirect back here for "retake" — we just show the form normally.

// Group questions by section for nicer display
$grouped = [];
foreach ($questions as $q) {
    $grouped[$q['sec']][] = $q;
}
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
  <meta charset="UTF-8">
  <link rel="icon" href="/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certification Assessment — Ukloole</title>
  <meta name="description" content="Take the official Ukloole Customer Service Mastery certification assessment. 40 questions, 80% pass mark, automatic grading.">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .assess-wrap { max-width: 820px; margin: 0 auto; padding: 0 20px 60px; }
    .assess-intro { background:white; border:1px solid var(--border); border-radius:16px; padding:28px; box-shadow:var(--shadow); margin-bottom:24px; }
    .assess-intro h2 { font-family:'EB Garamond',serif; color:var(--secondary); font-size:1.5rem; margin-bottom:10px; }
    .assess-intro ul { margin:14px 0 0 22px; color:var(--muted); font-size:.92rem; line-height:1.85; }
    .assess-meta { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-top:18px; }
    .meta-pill { background:#F0F4FA; border-radius:10px; padding:10px 14px; text-align:center; }
    .meta-pill strong { display:block; font-size:1.15rem; color:var(--secondary); font-family:'EB Garamond',serif; }
    .meta-pill span { font-size:.74rem; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; font-weight:700; }

    .candidate-form { background:white; border:1px solid var(--border); border-radius:16px; padding:24px; box-shadow:var(--shadow); margin-bottom:24px; }
    .candidate-form .row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .candidate-form label { display:block; font-size:.82rem; font-weight:700; color:var(--secondary); margin-bottom:6px; }
    .candidate-form input { width:100%; padding:12px 14px; border:2px solid var(--border); border-radius:10px; font-family:inherit; font-size:.95rem; outline:none; transition:border .15s; }
    .candidate-form input:focus { border-color:var(--primary); }

    .section-title-q { font-family:'EB Garamond',serif; color:var(--primary); font-size:1.25rem; margin:30px 0 14px; padding-bottom:8px; border-bottom:2px solid var(--gold, #C9A84C); }
    .question-card { background:white; border:1px solid var(--border); border-radius:14px; padding:20px; box-shadow:var(--shadow); margin-bottom:14px; }
    .question-card .qnum { display:inline-block; background:var(--primary); color:white; font-weight:700; font-size:.78rem; padding:3px 10px; border-radius:12px; margin-bottom:10px; }
    .question-card .qtext { color:var(--secondary); font-weight:600; margin-bottom:14px; line-height:1.55; }
    .opts { display:flex; flex-direction:column; gap:8px; }
    .opt { display:flex; gap:10px; align-items:flex-start; padding:11px 14px; border:2px solid var(--border); border-radius:10px; cursor:pointer; transition:all .15s; }
    .opt:hover { border-color:var(--primary); background:#F8FAFF; }
    .opt input { margin-top:3px; accent-color:var(--primary); flex-shrink:0; }
    .opt span { color:var(--text, #1A2D5A); font-size:.92rem; line-height:1.5; }
    .opt input:checked + span { color:var(--primary); font-weight:600; }

    .submit-row { background:linear-gradient(135deg,var(--primary),var(--secondary)); border-radius:18px; padding:28px; color:white; text-align:center; margin-top:24px; }
    .submit-row h3 { font-family:'EB Garamond',serif; font-size:1.5rem; margin-bottom:8px; }
    .submit-row p { color:rgba(255,255,255,.8); margin-bottom:18px; font-size:.92rem; }
    .submit-row button { background:white; color:var(--primary); border:none; padding:14px 36px; border-radius:12px; font-family:inherit; font-weight:700; font-size:1rem; cursor:pointer; transition:transform .15s; }
    .submit-row button:hover { transform:translateY(-2px); }

    .progress-bar { position:sticky; top:0; z-index:50; background:white; padding:12px 20px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 10px rgba(0,0,0,.04); }
    .progress-bar .label { font-size:.82rem; color:var(--muted); font-weight:600; }
    .progress-bar .count strong { color:var(--primary); }
    .progress-track { flex:1; max-width:300px; margin:0 16px; height:6px; background:var(--border); border-radius:3px; overflow:hidden; }
    .progress-fill { height:100%; width:0; background:linear-gradient(90deg,var(--primary),var(--gold,#C9A84C)); transition:width .25s; }
    @media(max-width:600px){
      .candidate-form .row { grid-template-columns:1fr; }
      .progress-bar { flex-wrap:wrap; gap:8px; }
      .progress-track { order:3; flex-basis:100%; max-width:none; margin:6px 0 0; }
    }
  </style>
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
        <li><a href="/courses">Course</a></li>
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
    <a href="/courses">Course</a>
    <a href="/resources">Materials</a>
    <a href="/community">Guidance</a>
  </div>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <div class="container">
      <span class="badge">Official Assessment</span>
      <h1>Customer Service Mastery — Certification Assessment</h1>
      <p>40 multiple-choice questions covering Modules 1 &amp; 2.<br>Pass mark: 80%. Your result is graded automatically.</p>
    </div>
  </section>

  <!-- PROGRESS BAR -->
  <div class="progress-bar" id="progress-bar">
    <span class="label">Progress</span>
    <span class="count"><strong id="answered-count">0</strong> / <?= count($questions) ?> answered</span>
    <div class="progress-track"><div class="progress-fill" id="progress-fill"></div></div>
  </div>

  <div class="assess-wrap">

    <div class="assess-intro">
      <h2><i class="fas fa-circle-info" style="color:var(--primary);"></i> Before you start</h2>
      <p style="color:var(--muted);">Read each question carefully. Select the BEST answer. You may only submit once per session — answers are graded instantly and your score is sent to our admin team.</p>
      <ul>
        <li>40 multiple-choice questions covering Modules 1 &amp; 2</li>
        <li>Each correct answer = 1 mark (40 marks total)</li>
        <li><strong>Pass mark: 80%</strong> (32 of 40 correct)</li>
        <li>You will see your result immediately after submitting</li>
        <li>If you pass, your candidate record and certificate code are created automatically</li>
      </ul>
      <div class="assess-meta">
        <div class="meta-pill"><strong>40</strong><span>Questions</span></div>
        <div class="meta-pill"><strong>80%</strong><span>Pass Mark</span></div>
        <div class="meta-pill"><strong>~30 min</strong><span>Suggested Time</span></div>
        <div class="meta-pill"><strong>Auto</strong><span>Graded</span></div>
      </div>
    </div>

    <form method="POST" action="/assessment-submit" id="assess-form" autocomplete="off">
      <?= csrf_field() ?>

      <div class="candidate-form">
        <div class="row">
          <div>
            <label for="full_name">Full Name (as it should appear on your certificate)</label>
            <input type="text" id="full_name" name="full_name" required maxlength="120" placeholder="e.g. Aisha Bello">
          </div>
          <div>
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required maxlength="190" placeholder="you@example.com">
          </div>
        </div>
      </div>

      <?php $qnum = 0; foreach ($grouped as $sec => $qs): ?>
        <h3 class="section-title-q"><?= htmlspecialchars($sec) ?></h3>
        <?php foreach ($qs as $q): $qnum++; ?>
          <div class="question-card" data-qid="<?= $q['id'] ?>">
            <div class="qnum">Question <?= $qnum ?></div>
            <div class="qtext"><?= htmlspecialchars($q['q']) ?></div>
            <div class="opts">
              <?php foreach ($q['opts'] as $letter => $text): ?>
                <label class="opt">
                  <input type="radio" name="q[<?= $q['id'] ?>]" value="<?= $letter ?>" required>
                  <span><strong><?= $letter ?>.</strong> <?= htmlspecialchars($text) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>

      <div class="submit-row">
        <h3>Ready to submit?</h3>
        <p>Make sure you've answered every question. Once you submit, your score is final.</p>
        <button type="submit" id="submit-btn">
          <i class="fas fa-paper-plane"></i> &nbsp;Submit Assessment
        </button>
      </div>
    </form>
  </div>

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
            <a href="mailto:learn@ukloole.com">&#9993; learn@ukloole.com</a>
            <a href="https://wa.me/message/IQZ6JVHJGNETE1">&#128172; WhatsApp: +234 810 159 3648</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">&copy; 2026 Ukloole. All rights reserved.</div>
    </div>
  </footer>

  <script src="/index.js"></script>
  <script>
    (function(){
      const form = document.getElementById('assess-form');
      const fill = document.getElementById('progress-fill');
      const counter = document.getElementById('answered-count');
      const total = <?= count($questions) ?>;

      function updateProgress() {
        const answered = new Set();
        form.querySelectorAll('input[type=radio]:checked').forEach(i => answered.add(i.name));
        counter.textContent = answered.size;
        fill.style.width = ((answered.size / total) * 100) + '%';
      }
      form.addEventListener('change', updateProgress);

      form.addEventListener('submit', function(e){
        const answered = new Set();
        form.querySelectorAll('input[type=radio]:checked').forEach(i => answered.add(i.name));
        if (answered.size < total) {
          if (!confirm('You have only answered ' + answered.size + ' of ' + total + ' questions. Unanswered questions will be marked wrong. Submit anyway?')) {
            e.preventDefault();
            return false;
          }
        }
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> &nbsp;Grading...';
      });
    })();
  </script>
</body>
</html>
