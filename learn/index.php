<?php
/**
 * UKLOOLE — Homepage
 * Dynamically loads FAQs and Testimonials from the database via admin.php
 */

$faqs         = [];
$testimonials = [];

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');
    $r = $conn->query("SELECT question, answer FROM faqs ORDER BY sort_order ASC, id ASC");
    while ($row = $r->fetch_assoc()) $faqs[] = $row;
    $r = $conn->query("SELECT name, avatar_initials, role, content, rating FROM testimonials ORDER BY created_at DESC LIMIT 6");
    while ($row = $r->fetch_assoc()) $testimonials[] = $row;
    $conn->close();
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
  <meta charset="UTF-8" />
  <link rel="icon" href="/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ukloole Learning Hub</title>
  <meta name="description" content="Master customer service, earn a certificate, and land remote jobs that pay in USD. Free course, real guidance, and community support.">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* TESTIMONIALS */
    .testi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 36px; }
    .testi-card { background: white; border-radius: 16px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 14px; }
    .testi-stars { color: #f59e0b; font-size: .9rem; }
    .testi-text { color: var(--muted); font-size: .92rem; line-height: 1.7; flex: 1; }
    .testi-author { display: flex; align-items: center; gap: 12px; }
    .testi-avatar { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; font-weight: 700; font-size: .85rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .testi-name { font-weight: 700; font-size: .9rem; color: var(--secondary); }
    .testi-role { font-size: .78rem; color: var(--muted); }

    /* FAQ */
    .faq-list { max-width: 760px; margin: 36px auto 0; display: flex; flex-direction: column; gap: 12px; }
    .faq-item { background: white; border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
    .faq-question { width: 100%; background: none; border: none; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; font-family: inherit; font-size: .97rem; font-weight: 600; color: var(--secondary); cursor: pointer; text-align: left; gap: 12px; transition: background .15s; }
    .faq-question:hover { background: #f8faff; }
    .faq-question i { color: var(--primary); font-size: .85rem; transition: transform .25s; flex-shrink: 0; }
    .faq-question.open i { transform: rotate(180deg); }
    .faq-answer { max-height: 0; overflow: hidden; transition: max-height .3s ease, padding .2s; padding: 0 24px; color: var(--muted); font-size: .92rem; line-height: 1.75; }
    .faq-answer.open { max-height: 400px; padding: 0 24px 20px; }
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
        <li><a href="/" class="active">Home</a></li>
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
    <a href="/" class="active">Home</a>
    <a href="/courses">Course</a>
    <a href="/resources">Materials</a>
    <a href="/community">Guidance</a>
  </div>

  <!-- HERO -->
  <section class="hero">
    <div class="container">
      <div class="hero-inner">
        <div class="hero-text">
          <span class="hero-badge">✦ Free Course</span>
          <h1>Become the customer-experience pro brands pay for.</h1>
          <p class="hero-sub">Practical training, verifiable certificate, and guidance to apply for remote jobs that pay in USD. No experience required.</p>
          <div class="hero-cta">
            <a href="/courses" class="btn btn-primary btn-lg"><i class="fas fa-play-circle"></i> Start Free Course</a>
            <a href="/community" class="btn btn-outline btn-lg" style="color:white;border-color:rgba(255,255,255,0.4);"><i class="fas fa-crown"></i> Get Certified</a>
          </div>
        </div>
        <div class="hero-cards">
          <div class="hero-card"><div class="hero-card-icon">🎓</div><h4>12 Lessons</h4><p>Structured, beginner-friendly</p></div>
          <div class="hero-card"><div class="hero-card-icon">📜</div><h4>Certificate</h4><p>Showcase your achievements on LinkedIn &amp; Portfolios</p></div>
          <div class="hero-card"><div class="hero-card-icon">💵</div><h4>Earn in USD</h4><p>Access exclusive remote job links</p></div>
          <div class="hero-card"><div class="hero-card-icon">👥</div><h4>Community</h4><p>Support, Q&amp;A &amp; opportunities</p></div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section">
    <div class="container">
      <h2 class="section-title">How It Works</h2>
      <p class="section-sub">Simple steps to get you started and earning quickly</p>
      <div class="how-grid">
        <div class="how-card">
          <div class="how-icon"><i class="fas fa-chalkboard-teacher"></i></div>
          <h3>Learn for Free</h3>
          <p>Watch structured lessons and build real customer service skills at your own pace.</p>
        </div>
        <div class="how-card">
          <div class="how-icon"><i class="fas fa-certificate"></i></div>
          <h3>Get Certified</h3>
          <p>Complete the course, pass the assessment, and earn your Ukloole certificate.</p>
        </div>
        <div class="how-card featured">
          <div class="how-icon"><i class="fas fa-hand-holding-dollar"></i></div>
          <h3>Get Guidance</h3>
          <ul class="feature-list">
            <li>Step-by-step Application Guide</li>
            <li>Exclusive Remote Opportunities</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS — only shown if admin has added any -->
  <?php if (!empty($testimonials)): ?>
  <section class="section" style="background:var(--bg);">
    <div class="container">
      <h2 class="section-title">What Our Students Say</h2>
      <p class="section-sub">Real results from real people who took the course</p>
      <div class="testi-grid">
        <?php foreach ($testimonials as $t): ?>
        <div class="testi-card">
          <div class="testi-stars">
            <?= str_repeat('★', (int)$t['rating']) . str_repeat('☆', 5 - (int)$t['rating']) ?>
          </div>
          <p class="testi-text">"<?= htmlspecialchars($t['content']) ?>"</p>
          <div class="testi-author">
            <div class="testi-avatar"><?= htmlspecialchars($t['avatar_initials']) ?></div>
            <div>
              <div class="testi-name"><?= htmlspecialchars($t['name']) ?></div>
              <div class="testi-role"><?= htmlspecialchars($t['role']) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA -->
  <section class="section-sm">
    <div class="container">
      <div class="cta-box">
        <h2>Start Learning for Free Today</h2>
        <p>No experience required. Take your first step towards a remote career.</p>
        <div class="hero-cta">
          <a href="/courses" class="btn btn-primary btn-lg" style="background:white;color:var(--primary);"><i class="fas fa-play-circle"></i> Start Free Course</a>
          <a href="/community" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:white;border:2px solid rgba(255,255,255,0.35);"><i class="fas fa-crown"></i> Earn Certificate</a>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ — only shown if admin has added any -->
  <?php if (!empty($faqs)): ?>
  <section class="section" style="background:white;">
    <div class="container">
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-sub">Everything you need to know before getting started</p>
      <div class="faq-list">
        <?php foreach ($faqs as $f): ?>
        <div class="faq-item">
          <button class="faq-question" onclick="toggleFaq(this)">
            <?= htmlspecialchars($f['question']) ?>
            <i class="fas fa-chevron-down"></i>
          </button>
          <div class="faq-answer"><?= htmlspecialchars($f['answer']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

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
  <script>
    function toggleFaq(btn) {
      const answer = btn.nextElementSibling;
      const isOpen = answer.classList.contains('open');
      document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
      document.querySelectorAll('.faq-question').forEach(b => b.classList.remove('open'));
      if (!isOpen) { answer.classList.add('open'); btn.classList.add('open'); }
    }
  </script>
</body>
</html>
