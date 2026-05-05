<?php
/**
 * UKLOOLE — Assessment Submission Handler
 * Receives answers from assessment.php, grades them automatically,
 * stores the result in `assessment_results`, and on a pass auto-creates
 * a `candidates` row with status='passed' so the certificate code is ready.
 */

require_once __DIR__ . '/security.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assessment.php');
    exit;
}

if (!csrf_check()) {
    http_response_code(403);
    die('Invalid session. Please reload the page and try again.');
}

$full_name = trim((string)($_POST['full_name'] ?? ''));
$email_raw = trim((string)($_POST['email'] ?? ''));
$email     = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
$answers   = $_POST['q'] ?? [];

if ($full_name === '' || strlen($full_name) > 120 || !$email || !is_array($answers)) {
    http_response_code(400);
    die('Missing or invalid information. Please go back and complete the form.');
}

$questions = require __DIR__ . '/assessment_questions.php';

// ---- GRADE ----
$total      = count($questions);
$score      = 0;
$breakdown  = [];

foreach ($questions as $q) {
    $picked = isset($answers[$q['id']]) ? strtoupper(substr((string)$answers[$q['id']], 0, 1)) : '';
    $picked = in_array($picked, ['A','B','C','D'], true) ? $picked : '';
    $is_correct = ($picked !== '' && $picked === $q['ans']);
    if ($is_correct) $score++;
    $breakdown[] = [
        'id'      => $q['id'],
        'picked'  => $picked,
        'correct' => $q['ans'],
        'ok'      => $is_correct,
    ];
}

$pct       = $total > 0 ? round(($score / $total) * 100, 2) : 0.0;
$pass_mark = 80;             // user-specified pass mark
$is_pass   = $pct >= $pass_mark ? 1 : 0;

// ---- STORE RESULT ----
require __DIR__ . '/db.php';

$cert_code = null;

if ($is_pass) {
    // Create a candidate record so the certificate code is ready for verify.php
    $cert_code = 'UKL-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $course    = 'Customer Service Mastery';
    $status    = 'passed';
    $issue_dt  = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO candidates (certificate_code, full_name, course_title, status, issue_date) VALUES (?,?,?,?,?)");
    if ($stmt) {
        $stmt->bind_param("sssss", $cert_code, $full_name, $course, $status, $issue_dt);
        $stmt->execute();
        $stmt->close();
    }
}

$json     = json_encode($breakdown, JSON_UNESCAPED_UNICODE);
$ip       = safe_request_ip();
$ua       = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);

$stmt = $conn->prepare("INSERT INTO assessment_results
    (full_name, email, score, total, percentage, pass, answers_json, certificate_code, ip_address, user_agent)
    VALUES (?,?,?,?,?,?,?,?,?,?)");
if ($stmt) {
    $score_i = (int)$score;
    $total_i = (int)$total;
    $pass_i  = (int)$is_pass;
    $stmt->bind_param(
        "ssiidisss" . "s",
        $full_name, $email, $score_i, $total_i, $pct, $pass_i, $json, $cert_code, $ip, $ua
    );
    $stmt->execute();
    $result_id = $conn->insert_id;
    $stmt->close();
}

$conn->close();

// Invalidate the CSRF token so a refresh of the result page can't double-submit
unset($_SESSION['_csrf']);

// ---- RENDER RESULT PAGE ----
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_pass ? 'You Passed' : 'Result' ?> — Ukloole Assessment</title>
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .result-wrap { max-width:760px; margin:0 auto; padding:60px 20px; }
    .result-card { background:white; border-radius:24px; padding:48px 36px; box-shadow:var(--shadow-lg, 0 12px 50px rgba(13,27,62,.18)); text-align:center; }
    .result-icon { width:96px; height:96px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:3rem; }
    .result-icon.pass { background:linear-gradient(135deg,#dcfce7,#86efac); color:#16a34a; }
    .result-icon.fail { background:linear-gradient(135deg,#fee2e2,#fca5a5); color:#dc2626; }
    .result-card h1 { font-family:'EB Garamond',serif; font-size:2.4rem; color:var(--secondary); margin-bottom:8px; }
    .result-card .sub { color:var(--muted); margin-bottom:30px; font-size:1rem; }
    .score-display { display:inline-flex; align-items:baseline; gap:12px; margin:18px 0; }
    .score-big { font-family:'EB Garamond',serif; font-size:4rem; font-weight:700; color:var(--primary); line-height:1; }
    .score-of { color:var(--muted); font-size:1.2rem; }
    .pct-badge { display:inline-block; background:linear-gradient(135deg,var(--primary),var(--secondary)); color:white; padding:6px 18px; border-radius:50px; font-weight:700; font-size:.95rem; margin-left:8px; }
    .cert-block { background:#FFF8E7; border:2px dashed #C9A84C; border-radius:14px; padding:22px; margin:28px 0; }
    .cert-block .cert-code { font-family:monospace; font-size:1.4rem; font-weight:700; color:#92400e; letter-spacing:.06em; }
    .cert-block p { color:var(--muted); margin:8px 0 0; font-size:.9rem; }
    .actions { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:24px; }
    .btn-result { padding:12px 24px; border-radius:10px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:8px; transition:transform .15s; }
    .btn-result:hover { transform:translateY(-2px); }
    .btn-primary-r { background:var(--primary); color:white; }
    .btn-outline-r { background:white; color:var(--primary); border:2px solid var(--primary); }
    .breakdown { text-align:left; margin-top:30px; padding-top:24px; border-top:1px solid var(--border); }
    .breakdown summary { cursor:pointer; font-weight:700; color:var(--primary); padding:6px 0; }
    .breakdown ul { margin:14px 0 0; padding:0; list-style:none; display:grid; grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); gap:6px; }
    .breakdown li { background:#f8faff; border-radius:8px; padding:8px; text-align:center; font-size:.78rem; }
    .breakdown li.ok { background:#dcfce7; color:#15803d; }
    .breakdown li.no { background:#fee2e2; color:#b91c1c; }
  </style>
</head>
<body>

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

  <div class="result-wrap">
    <div class="result-card">

      <?php if ($is_pass): ?>
        <div class="result-icon pass"><i class="fas fa-trophy"></i></div>
        <h1>Congratulations, <?= htmlspecialchars($full_name) ?>!</h1>
        <p class="sub">You passed the Customer Service Mastery Certification Assessment.</p>
      <?php else: ?>
        <div class="result-icon fail"><i class="fas fa-circle-exclamation"></i></div>
        <h1>Not quite there yet</h1>
        <p class="sub">You scored below the 80% pass mark. Review the lessons and try again — you can do this!</p>
      <?php endif; ?>

      <div class="score-display">
        <span class="score-big"><?= $score ?></span>
        <span class="score-of">/ <?= $total ?></span>
        <span class="pct-badge"><?= rtrim(rtrim(number_format($pct, 2), '0'), '.') ?>%</span>
      </div>

      <?php if ($is_pass && $cert_code): ?>
        <div class="cert-block">
          <div style="font-size:.78rem;color:var(--muted);text-transform:uppercase;font-weight:700;letter-spacing:.06em;margin-bottom:6px;">Your Certificate Code</div>
          <div class="cert-code"><?= htmlspecialchars($cert_code) ?></div>
          <p>Your candidate record has been created. Our team will issue your official certificate shortly. Anyone can verify your status at <strong>verify.php</strong> using this code.</p>
        </div>
        <div class="actions">
          <a class="btn-result btn-primary-r" href="verify.php?code=<?= urlencode($cert_code) ?>">
            <i class="fas fa-shield-check"></i> Verify My Certificate
          </a>
          <a class="btn-result btn-outline-r" href="/resources">
            <i class="fas fa-download"></i> Browse Materials
          </a>
        </div>
      <?php else: ?>
        <div class="actions">
          <a class="btn-result btn-primary-r" href="/courses">
            <i class="fas fa-book-open"></i> Review the Course
          </a>
          <a class="btn-result btn-outline-r" href="/assessment">
            <i class="fas fa-rotate-right"></i> Retake the Assessment
          </a>
        </div>
      <?php endif; ?>

      <details class="breakdown">
        <summary>Show question-by-question breakdown</summary>
        <ul>
          <?php foreach ($breakdown as $i => $b): ?>
            <li class="<?= $b['ok'] ? 'ok' : 'no' ?>">
              Q<?= $i + 1 ?><br>
              <strong><?= $b['ok'] ? '✓' : '✗' ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
        <p style="margin-top:14px; font-size:.82rem; color:var(--muted);">A full transcript including the correct answer for each question is visible to the admin team.</p>
      </details>

    </div>
  </div>

  <footer class="footer">
    <div class="container">
      <div class="footer-bottom">&copy; 2026 Ukloole. All rights reserved.</div>
    </div>
  </footer>

  <script src="/index.js"></script>
</body>
</html>
