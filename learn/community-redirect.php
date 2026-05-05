<?php
/**
 * UKLOOLE — Community Job Link Redirect
 * Hides the real destination URL from members.
 * Usage: community-redirect.php?t=TOKEN
 */
session_start();

// Must be logged in as a community member
if (empty($_SESSION['community_member_id'])) {
    header('Location: community-login.php');
    exit;
}

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    die('Server error. Please try again.');
}
$conn->set_charset('utf8mb4');

$token = htmlspecialchars(trim($_GET['t'] ?? ''));

if (!$token) {
    header('Location: community-dashboard.php');
    exit;
}

// Look up token → job link
$stmt = $conn->prepare("
    SELECT j.url, j.is_active, j.expires_at
    FROM community_link_tokens t
    JOIN community_job_links j ON j.id = t.job_link_id
    WHERE t.token = ?
    LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || !$row['is_active']) {
    die('This link is no longer available.');
}

if ($row['expires_at'] && strtotime($row['expires_at']) < strtotime('today')) {
    die('This job link has expired.');
}

$url = $row['url'];

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    die('Invalid link.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Opening Job Link...</title>
  <style>
    body { font-family: sans-serif; background: #0D1B3E; color: white; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 24px; }
    .box { max-width: 400px; }
    .spinner { font-size: 2.5rem; animation: spin 1s linear infinite; display: inline-block; margin-bottom: 16px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    p { color: rgba(255,255,255,.6); margin-top: 8px; font-size: .9rem; }
  </style>
</head>
<body>
  <div class="box">
    <div class="spinner">⟳</div>
    <h2>Opening job link...</h2>
    <p>You will be redirected in a moment.</p>
  </div>
  <script>
    // Redirect without exposing URL in referrer header
    setTimeout(function() {
      // Use location.replace so back button doesn't reveal the URL
      window.location.replace(<?= json_encode($url) ?>);
    }, 800);
  </script>
</body>
</html>
