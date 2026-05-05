<?php
/**
 * UKLOOLE — Certificate Verification System
 * verify.php — Full page + backend logic
 * Features: navy + gold premium UI, prepared statements, input sanitization
 */

require_once 'db.php';

$result_data = null;
$search_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['code'])) {
    $raw_code    = trim($_POST['certificate_code'] ?? $_GET['code'] ?? '');
    $search_code = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $raw_code));

    if (!empty($search_code)) {
        $stmt = $conn->prepare(
            "SELECT id, certificate_code, full_name, course_title, status, issue_date, created_at
             FROM candidates
             WHERE certificate_code = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $search_code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $result_data = ['type' => 'invalid'];
        } elseif ($row['status'] === 'passed') {
            $result_data = ['type' => 'verified', 'data' => $row];
        } elseif ($row['status'] === 'pending') {
            $result_data = ['type' => 'not_issued', 'data' => $row];
        } elseif ($row['status'] === 'failed') {
            $result_data = ['type' => 'failed', 'data' => $row];
        } else {
            $result_data = ['type' => 'invalid'];
        }
    }
}

$conn->close();
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
  <title>Verify Certificate — Ukloole</title>
  <meta name="description" content="Instantly verify the authenticity of any Ukloole certificate.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:      #0D1B3E;
      --navy-mid:  #1A2D5A;
      --navy-light:#243566;
      --gold:      #C9A84C;
      --gold-light:#F2D06B;
      --gold-pale: #FFF8E7;
      --text:      #1A2D5A;
      --muted:     #6B7A99;
      --border:    #E2E8F0;
      --bg:        #F0F4FA;
      --white:     #FFFFFF;
      --success:   #16a34a;
      --radius:    16px;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      min-height: 100vh;
    }

    a { text-decoration: none; color: inherit; }
    img { max-width: 100%; display: block; }

    /* ---- NAVBAR ---- */
    .navbar {
      background: var(--white);
      border-bottom: 1px solid var(--border);
      padding: 0 24px;
      height: 72px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 12px rgba(13,27,62,0.06);
    }

    .navbar .container {
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      font-size: 1.3rem;
      color: var(--navy);
    }

    .navbar-brand img { width: 38px; height: 38px; object-fit: contain; }

    .nav-links {
      display: flex;
      gap: 28px;
      list-style: none;
    }

    .nav-links a {
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--muted);
      transition: color 0.2s;
    }

    .nav-links a:hover,
    .nav-links a.active { color: var(--navy); }

    .hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 8px;
    }

    .hamburger span {
      display: block;
      width: 24px;
      height: 2px;
      background: var(--navy);
      border-radius: 2px;
      transition: all 0.3s;
    }

    .mobile-menu {
      display: none;
      position: fixed;
      top: 72px;
      left: 0; right: 0;
      background: white;
      border-top: 1px solid var(--border);
      padding: 24px 20px;
      z-index: 999;
      flex-direction: column;
      gap: 8px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    }

    .mobile-menu.open { display: flex; }

    .mobile-menu a {
      font-size: 1.1rem;
      font-weight: 500;
      padding: 14px 16px;
      border-radius: 12px;
      color: var(--navy);
      transition: background 0.2s;
    }

    .mobile-menu a:hover { background: var(--bg); }
    .mobile-menu a.active { color: var(--navy-mid); font-weight: 700; }

    /* ---- HERO ---- */
    .verify-hero {
      background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, var(--navy-light) 100%);
      padding: 80px 24px 70px;
      text-align: center;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .verify-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at 70% 50%, rgba(201,168,76,0.08) 0%, transparent 70%);
    }

    .hero-icon-wrap {
      width: 80px; height: 80px;
      background: rgba(201,168,76,0.15);
      border: 2px solid rgba(201,168,76,0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 2rem;
      color: var(--gold-light);
      position: relative;
      z-index: 1;
    }

    .verify-hero h1 {
      font-family: 'EB Garamond', serif;
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 14px;
      position: relative;
      z-index: 1;
    }

    .verify-hero p {
      color: rgba(255,255,255,0.75);
      font-size: 1.1rem;
      max-width: 520px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    /* Gold accent bar */
    .gold-bar {
      height: 4px;
      background: linear-gradient(90deg, transparent, var(--gold), transparent);
      opacity: 0.6;
    }

    /* ---- MAIN ---- */
    .main-section {
      padding: 60px 24px 80px;
      max-width: 680px;
      margin: 0 auto;
    }

    .verify-card {
      background: white;
      border-radius: 24px;
      padding: 48px;
      box-shadow: 0 8px 40px rgba(13,27,62,0.10);
      border: 1px solid rgba(201,168,76,0.2);
    }

    .verify-card-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 8px;
    }

    .verify-card-header-icon {
      width: 48px; height: 48px;
      background: var(--gold-pale);
      border: 1px solid rgba(201,168,76,0.3);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--gold);
      font-size: 1.3rem;
    }

    .verify-card h2 {
      font-family: 'EB Garamond', serif;
      font-size: 1.8rem;
      color: var(--navy);
    }

    .verify-card > p {
      color: var(--muted);
      margin-bottom: 28px;
      font-size: 0.95rem;
      margin-left: 62px;
    }

    .input-group {
      display: flex;
      gap: 10px;
      margin-bottom: 8px;
    }

    .cert-input {
      flex: 1;
      padding: 14px 18px;
      border: 2px solid var(--border);
      border-radius: 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: 0.05em;
      color: var(--navy);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: var(--bg);
    }

    .cert-input:focus {
      border-color: var(--gold);
      box-shadow: 0 0 0 3px rgba(201,168,76,0.15);
      background: white;
    }

    .cert-input::placeholder { color: var(--muted); font-weight: 400; letter-spacing: 0; }

    .btn-verify {
      padding: 14px 24px;
      background: linear-gradient(135deg, var(--navy), var(--navy-mid));
      color: white;
      border: none;
      border-radius: 12px;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }

    .btn-verify:hover {
      background: linear-gradient(135deg, var(--navy-mid), var(--gold));
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(13,27,62,0.25);
    }

    .input-hint {
      font-size: 0.8rem;
      color: var(--muted);
      margin-bottom: 28px;
    }

    /* ---- RESULTS ---- */
    .result-block { margin-top: 28px; }

    /* Verified */
    .result-verified {
      border-radius: 20px;
      border: 2px solid #16a34a;
      background: linear-gradient(135deg, #f0fdf4, #dcfce7);
      padding: 32px;
      animation: resultIn 0.4s ease;
    }

    .result-verified-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid rgba(22,163,74,0.2);
    }

    .verified-icon {
      width: 56px; height: 56px;
      background: #16a34a;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    .verified-title {
      font-family: 'EB Garamond', serif;
      font-size: 1.5rem;
      color: #15803d;
      font-weight: 700;
    }

    .verified-subtitle {
      font-size: 0.85rem;
      color: #166534;
      margin-top: 2px;
    }

    .cert-details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 20px;
    }

    .cert-detail-box {
      background: white;
      border-radius: 12px;
      padding: 16px 18px;
      border: 1px solid rgba(22,163,74,0.15);
    }

    .cert-detail-label {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--muted);
      display: block;
      margin-bottom: 5px;
    }

    .cert-detail-value {
      font-size: 1rem;
      font-weight: 700;
      color: var(--navy);
    }

    .cert-id-stamp {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(13,27,62,0.06);
      border: 1px solid rgba(13,27,62,0.12);
      border-radius: 8px;
      padding: 10px 16px;
      font-family: 'Courier New', monospace;
      font-size: 0.95rem;
      color: var(--navy);
      font-weight: 700;
    }

    /* Not issued */
    .result-pending {
      border-radius: 20px;
      border: 2px solid #d97706;
      background: linear-gradient(135deg, #fffbeb, #fef3c7);
      padding: 32px;
      text-align: center;
      animation: resultIn 0.4s ease;
    }

    .result-pending .status-icon {
      font-size: 3.5rem;
      margin-bottom: 14px;
    }

    .result-pending h3 {
      font-family: 'EB Garamond', serif;
      font-size: 1.6rem;
      color: #92400e;
      margin-bottom: 10px;
    }

    .result-pending p {
      color: #78350f;
      font-size: 0.95rem;
      line-height: 1.6;
    }

    /* Failed */
    .result-failed {
      border-radius: 20px;
      border: 2px solid #9f1239;
      background: linear-gradient(135deg, #fff1f2, #ffe4e6);
      padding: 32px;
      text-align: center;
      animation: resultIn 0.4s ease;
    }

    .result-failed .status-icon {
      font-size: 3.5rem;
      margin-bottom: 14px;
    }

    .result-failed h3 {
      font-family: 'EB Garamond', serif;
      font-size: 1.6rem;
      color: #9f1239;
      margin-bottom: 10px;
    }

    .result-failed p {
      color: #881337;
      font-size: 0.95rem;
    }

    /* Invalid */
    .result-invalid {
      border-radius: 20px;
      border: 2px solid #dc2626;
      background: #fef2f2;
      padding: 32px;
      text-align: center;
      animation: resultIn 0.4s ease;
    }

    .result-invalid .error-icon { font-size: 3.5rem; color: #dc2626; margin-bottom: 14px; }
    .result-invalid h3 {
      font-family: 'EB Garamond', serif;
      font-size: 1.5rem;
      color: #dc2626;
      margin-bottom: 10px;
    }

    .result-invalid p { color: #6b7280; }

    @keyframes resultIn {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Tips */
    .tips-box {
      background: var(--gold-pale);
      border: 1px solid rgba(201,168,76,0.3);
      border-radius: 14px;
      padding: 22px 24px;
      margin-top: 28px;
    }

    .tips-box h4 {
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .tips-box h4 i { color: var(--gold); }

    .tip-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin-bottom: 8px;
      font-size: 0.87rem;
      color: var(--muted);
    }

    .tip-row i { color: var(--gold); margin-top: 3px; flex-shrink: 0; }

    /* Footer */
    .footer {
      background: var(--navy);
      color: white;
      padding: 50px 24px 0;
    }

    .footer-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 2fr 1fr 1fr;
      gap: 48px;
      padding-bottom: 40px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .footer-brand-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .footer-brand-row img { width: 32px; height: 32px; object-fit: contain; }
    .footer-brand-row span { font-weight: 700; font-size: 1.15rem; }
    .footer-tagline { color: rgba(255,255,255,0.5); font-size: 0.85rem; }
    .footer-bottom-bar { text-align: center; padding: 18px 0; color: rgba(255,255,255,0.3); font-size: 0.82rem; }

    .footer h4 { font-family: 'EB Garamond', serif; font-size: 1rem; margin-bottom: 14px; color: #ffffff; }
    .footer ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .footer ul a { color: rgba(255,255,255,0.5); font-size: 0.88rem; transition: color 0.2s; }
    .footer ul a:hover { color: white; }

    @media (max-width: 640px) {
      .nav-links { display: none; }
      .hamburger { display: flex; }
      .verify-hero { padding: 50px 20px 44px; }
      .verify-hero h1 { font-size: 1.9rem; }
      .verify-hero p { font-size: 0.95rem; }
      .hero-icon-wrap { width: 64px; height: 64px; font-size: 1.6rem; }
      .main-section { padding: 32px 16px 60px; }
      .verify-card { padding: 24px 18px; border-radius: 18px; }
      .verify-card-header { gap: 10px; }
      .verify-card h2 { font-size: 1.4rem; }
      .verify-card > p { margin-left: 0; margin-top: 8px; }
      .input-group { flex-direction: column; }
      .btn-verify { width: 100%; justify-content: center; }
      .cert-details-grid { grid-template-columns: 1fr; }
      .cert-id-stamp { font-size: 0.82rem; word-break: break-all; }
      .result-verified,
      .result-pending,
      .result-failed,
      .result-invalid { padding: 22px 16px; }
      .verified-title { font-size: 1.2rem; }
      .tips-box { padding: 18px 16px; }
      .navbar { padding: 0 16px; }
      .footer-inner { grid-template-columns: 1fr; gap: 28px; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="container">
    <a href="/" class="navbar-brand">
      <img src="/logo.png" alt="Ukloole">
      <span>Ukloole</span>
    </a>
    <ul class="nav-links">
      <li><a href="/">Home</a></li>
      <li><a href="/courses">Course</a></li>
      <li><a href="/resources">Materials</a></li>
      <li><a href="/community">Guidance</a></li>
    </ul>
    <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<div class="mobile-menu" id="mobile-menu">
  <a href="/">Home</a>
  <a href="/courses">Course</a>
  <a href="/resources">Materials</a>
  <a href="/community">Guidance</a>
</div>

<!-- HERO -->
<section class="verify-hero">
  <div class="hero-icon-wrap"><i class="fas fa-shield-halved"></i></div>
  <h1>Certificate Verification</h1>
  <p>Instantly verify the authenticity of any Ukloole certificate</p>
</section>
<div class="gold-bar"></div>

<!-- MAIN -->
<section class="main-section">
  <div class="verify-card">

    <div class="verify-card-header">
      <div class="verify-card-header-icon"><i class="fas fa-certificate"></i></div>
      <h2>Verify a Certificate</h2>
    </div>
    <p>Enter the code printed on the document.</p>

    <form method="POST" action="">
      <div class="input-group">
        <input
          type="text"
          name="certificate_code"
          class="cert-input"
          placeholder="e.g. UKL-2026-ABC123"
          value="<?= htmlspecialchars($search_code) ?>"
          style="text-transform:uppercase;"
          oninput="this.value=this.value.toUpperCase()"
          autocomplete="off"
          required
        >
        <button type="submit" class="btn-verify">
          <i class="fas fa-search"></i> Verify
        </button>
      </div>
      <p class="input-hint">Code format: UKL-YYYY-XXXXXX &nbsp;•&nbsp; Case-insensitive</p>
    </form>

    <?php if ($result_data !== null): ?>

      <div class="result-block">

        <?php if ($result_data['type'] === 'verified'): ?>

          <?php
            $d = $result_data['data'];
            $issue_date = $d['issue_date']
              ? date('F j, Y', strtotime($d['issue_date']))
              : 'N/A';
          ?>
          <div class="result-verified">
            <div class="result-verified-header">
              <div class="verified-icon"><i class="fas fa-check"></i></div>
              <div>
                <div class="verified-title">Certificate Verified</div>
                <div class="verified-subtitle">This is an authentic Ukloole certificate</div>
              </div>
            </div>
            <div class="cert-details-grid">
              <div class="cert-detail-box">
                <span class="cert-detail-label">Certificate Holder</span>
                <span class="cert-detail-value"><?= htmlspecialchars($d['full_name']) ?></span>
              </div>
              <div class="cert-detail-box">
                <span class="cert-detail-label">Course Completed</span>
                <span class="cert-detail-value"><?= htmlspecialchars($d['course_title']) ?></span>
              </div>
              <div class="cert-detail-box">
                <span class="cert-detail-label">Issue Date</span>
                <span class="cert-detail-value"><?= $issue_date ?></span>
              </div>
              <div class="cert-detail-box">
                <span class="cert-detail-label">Issuing Authority</span>
                <span class="cert-detail-value">Ukloole Learning Hub</span>
              </div>
            </div>
            <div class="cert-id-stamp">
              <i class="fas fa-fingerprint" style="color:var(--navy-mid)"></i>
              <?= htmlspecialchars($d['certificate_code']) ?>
            </div>
          </div>

        <?php elseif ($result_data['type'] === 'not_issued'): ?>

          <div class="result-pending">
            <div class="status-icon">⏳</div>
            <h3>Certificate Not Yet Issued</h3>
            <p>This candidate exists in our system but their certificate has <strong>not yet been issued</strong>.<br>
            The assessment may still be pending review. Please contact
            <a href="mailto:learn@ukloole.com" style="color:#92400e;font-weight:700;">learn@ukloole.com</a>
            if you believe this is an error.</p>
          </div>

        <?php elseif ($result_data['type'] === 'failed'): ?>

          <div class="result-failed">
            <div class="status-icon">❌</div>
            <h3>Assessment Not Passed</h3>
            <p>This candidate did not pass the assessment. The certificate has not been issued.<br>
            Contact <a href="mailto:learn@ukloole.com" style="color:#9f1239;font-weight:700;">learn@ukloole.com</a>
            for assistance.</p>
          </div>

        <?php elseif ($result_data['type'] === 'invalid'): ?>

          <div class="result-invalid">
            <div class="error-icon"><i class="fas fa-times-circle"></i></div>
            <h3>Certificate Not Found</h3>
            <p>The code <strong><?= htmlspecialchars($search_code) ?></strong> could not be found in our records.<br>
            Please check for typos or contact
            <a href="mailto:learn@ukloole.com" style="color:#dc2626;">learn@ukloole.com</a>.</p>
          </div>

        <?php endif; ?>

      </div>

    <?php endif; ?>

    <div class="tips-box">
      <h4><i class="fas fa-lightbulb"></i> How to find your Certificate Code</h4>
      <div class="tip-row"><i class="fas fa-chevron-right"></i><span>Look at the top of your physical or digital certificate</span></div>
      <div class="tip-row"><i class="fas fa-chevron-right"></i><span>It starts with "UKL-" followed by the year and a 6-character code</span></div>
      <div class="tip-row"><i class="fas fa-chevron-right"></i><span>Example: <strong>UKL-2026-AB12CD</strong></span></div>
      <div class="tip-row"><i class="fas fa-chevron-right"></i><span>Need help? Email <a href="mailto:learn@ukloole.com" style="color:var(--navy);font-weight:600;">learn@ukloole.com</a></span></div>
    </div>

  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-inner">
    <div>
      <div class="footer-brand-row"><img src="/logo.png" alt="Ukloole"><span>Ukloole</span></div>
      <p class="footer-tagline">Tech and Talent For The Modern Business.</p>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul>
        <li><a href="/verify">Verify Certificate</a></li>
            <!--<li><a href="/coaching">1 : 1 Coaching</a></li>-->  
            <li><a href="https://www.ukloole.com" target="_blank">B2B Services</a></li>
            <li><a href="/privacy" target="_blank">Privacy Policy</a></li>
            <li><a href="/terms" target="_blank">Terms of Service</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul>
        <li><a href="mailto:learn@ukloole.com">learn@ukloole.com</a></li>
        <li><a href="https://wa.me/message/IQZ6JVHJGNETE1">WhatsApp Support</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom-bar">&copy; 2026 Ukloole. All rights reserved.</div>
</footer>

<script>
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobile-menu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      mobileMenu.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', mobileMenu.classList.contains('open'));
    });
    document.addEventListener('click', function (e) {
      if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('open');
      }
    });
  }
</script>
</body>
</html>