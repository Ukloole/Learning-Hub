<?php
/**
 * UKLOOLE — Community Member Login
 * After payment, members set up credentials and log in here.
 * Includes: Login, First-time Setup, Forgot Password, Reset Password
 */

session_start();
require_once 'db.php';

// Self-heal: add reset token columns if not present
// Helper: add a column only if it doesn't already exist (compatible with MySQL 5.x)
function cl_add_column_if_missing($conn, $table, $column, $definition) {
    $db = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    $res = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
    if ($res && $res->fetch_row()[0] == 0) {
        @$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}
cl_add_column_if_missing($conn, 'community_members', 'reset_token', 'VARCHAR(64) NULL DEFAULT NULL');
cl_add_column_if_missing($conn, 'community_members', 'reset_expires', 'DATETIME NULL DEFAULT NULL');

$error   = '';
$success = '';

// ── Determine which section to show ──────────────────────────────────────
$reset_token_get = trim($_GET['reset_token'] ?? '');
$show_setup      = (isset($_GET['setup']) || (!empty($_POST['action']) && $_POST['action'] === 'setup'));
$show_forgot     = (!empty($_POST['action']) && $_POST['action'] === 'forgot') && empty($_POST['forgot_done']);
$show_reset      = !empty($reset_token_get);

// ── POST Handlers ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ── LOGIN ──────────────────────────────────────────────────────────────
    if ($_POST['action'] === 'login') {
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = "Please enter your email and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password_hash, status FROM community_members WHERE email=? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$member) {
                $error = "No account found with that email. Have you paid for premium access?";
            } elseif (empty($member['password_hash'])) {
                $error = "Please set up your password first using the link in your welcome email, or contact learn@ukloole.com.";
            } elseif (!password_verify($password, $member['password_hash'])) {
                $error = "Incorrect password. Please try again.";
            } elseif ($member['status'] !== 'active') {
                $error = "Your account is not active. Contact learn@ukloole.com.";
            } else {
                $_SESSION['community_member_id']   = $member['id'];
                $_SESSION['community_member_name'] = $member['name'];
                header('Location: community-dashboard.php');
                exit;
            }
        }
    }

    // ── SETUP PASSWORD (first-time after payment) ──────────────────────────
    if ($_POST['action'] === 'setup') {
        $email    = filter_var(trim($_POST['setup_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['setup_password'] ?? '';
        $confirm  = $_POST['setup_confirm'] ?? '';

        if (!$email) {
            $error = "Please enter a valid email.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, password_hash FROM community_members WHERE email=? AND status='active' LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$member) {
                $error = "Email not found. Please ensure you have completed payment, or contact learn@ukloole.com.";
            } elseif (!empty($member['password_hash'])) {
                $error = "A password is already set for this account. Please login below.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE community_members SET password_hash=? WHERE id=?");
                $stmt->bind_param("si", $hash, $member['id']);
                $stmt->execute();
                $stmt->close();
                $success     = "Password set! You can now login.";
                $show_setup  = false;
            }
        }
    }

    // ── FORGOT PASSWORD (send reset email) ────────────────────────────────
    if ($_POST['action'] === 'forgot') {
        $email = filter_var(trim($_POST['forgot_email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $error       = "Please enter a valid email address.";
            $show_forgot = true;
        } else {
            // Always show success (prevent email enumeration)
            $stmt = $conn->prepare("SELECT id, name FROM community_members WHERE email=? AND status='active' LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($member) {
                $token   = bin2hex(random_bytes(32)); // 64-char secure token
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                $stmt = $conn->prepare("UPDATE community_members SET reset_token=?, reset_expires=? WHERE id=?");
                $stmt->bind_param("ssi", $token, $expires, $member['id']);
                $stmt->execute();
                $stmt->close();

                // Send reset email
                $site_url   = rtrim(getenv('SITE_URL') ?: 'https://learn.ukloole.com', '/');
                $reset_link = $site_url . '/community-login.php?reset_token=' . $token;
                $first_name = explode(' ', $member['name'])[0];

                require_once 'mailer.php';
                $html = lh_email_template(
                    'Reset Your Password',
                    '<p style="color:#333;font-size:15px;line-height:1.6;margin:0 0 18px;">
                        Hi ' . htmlspecialchars($first_name) . ',
                    </p>
                    <p style="color:#333;font-size:15px;line-height:1.6;margin:0 0 18px;">
                        We received a request to reset your Ukloole Community password.
                        Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.
                    </p>
                    <div style="text-align:center;margin:28px 0;">
                        <a href="' . $reset_link . '"
                           style="background:#0D1B3E;color:#ffffff;padding:14px 32px;
                                  border-radius:8px;text-decoration:none;font-weight:700;
                                  font-size:15px;display:inline-block;">
                            Reset My Password
                        </a>
                    </div>
                    <p style="color:#666;font-size:13px;line-height:1.6;margin:18px 0 0;">
                        If you did not request this, you can safely ignore this email —
                        your password will not be changed.
                    </p>
                    <p style="color:#666;font-size:12px;margin:12px 0 0;">
                        Or copy this link into your browser:<br>
                        <a href="' . $reset_link . '" style="color:#0D1B3E;word-break:break-all;">' . $reset_link . '</a>
                    </p>'
                );
                lh_send_email($email, $member['name'], 'Reset Your Ukloole Community Password', $html);
            }

            // Always show this message
            $success     = "If that email is registered, a password reset link has been sent. Please check your inbox (and spam folder).";
            $show_forgot = false;
        }
    }

    // ── RESET PASSWORD (with token) ────────────────────────────────────────
    if ($_POST['action'] === 'reset_password') {
        $token    = trim($_POST['reset_token'] ?? '');
        $password = $_POST['new_password'] ?? '';
        $confirm  = $_POST['new_confirm'] ?? '';

        if (!$token) {
            $error = "Invalid reset link.";
        } elseif (strlen($password) < 8) {
            $error      = "Password must be at least 8 characters.";
            $show_reset = true;
            $reset_token_get = $token;
        } elseif ($password !== $confirm) {
            $error      = "Passwords do not match.";
            $show_reset = true;
            $reset_token_get = $token;
        } else {
            $now  = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("SELECT id, name FROM community_members WHERE reset_token=? AND reset_expires > ? AND status='active' LIMIT 1");
            $stmt->bind_param("ss", $token, $now);
            $stmt->execute();
            $member = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$member) {
                $error = "This reset link is invalid or has expired. Please request a new one.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE community_members SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
                $stmt->bind_param("si", $hash, $member['id']);
                $stmt->execute();
                $stmt->close();
                $success    = "Your password has been reset successfully. You can now log in.";
                $show_reset = false;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Community Login — Ukloole</title>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-8RH12T08CY"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-8RH12T08CY');
  </script>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--navy:#0D1B3E;--navy-mid:#1A2D5A;--gold:#C9A84C;--gold-pale:#FFF8E7;--border:#E2E8F0;--muted:#6B7A99;--bg:#F0F4FA;}
    body{font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;background:linear-gradient(135deg,var(--navy) 0%,#1A2D5A 50%,#243566 100%);color:white;}
    .top-bar{padding:20px 28px;display:flex;align-items:center;justify-content:space-between;}
    .brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:1.2rem;text-decoration:none;color:white;}
    .brand img{width:34px;}
    .back-link{color:rgba(255,255,255,.6);font-size:.88rem;text-decoration:none;}
    .back-link:hover{color:white;}
    .main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;}
    .card{background:white;border-radius:24px;padding:44px;max-width:460px;width:100%;color:var(--navy);box-shadow:0 24px 60px rgba(0,0,0,.3);}
    .card-icon{width:60px;height:60px;background:var(--gold-pale);border:2px solid rgba(201,168,76,.3);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--gold);margin-bottom:20px;}
    h2{font-family:'EB Garamond',serif;font-size:1.9rem;color:var(--navy);margin-bottom:6px;}
    .sub{color:var(--muted);font-size:.9rem;margin-bottom:28px;}
    .alert{padding:12px 16px;border-radius:9px;font-size:.87rem;margin-bottom:18px;display:flex;align-items:flex-start;gap:8px;}
    .alert-error{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;}
    .alert-success{background:#f0fdf4;border:1px solid #86efac;color:#15803d;}
    label{display:block;font-size:.82rem;font-weight:700;color:var(--navy);margin-bottom:5px;}
    .finput{width:100%;padding:12px 16px;border:2px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.92rem;outline:none;transition:border-color .2s;margin-bottom:14px;}
    .finput:focus{border-color:var(--gold);}
    .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:white;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
    .btn-login:hover{background:linear-gradient(135deg,var(--navy-mid),var(--gold));}
    .divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:var(--muted);font-size:.82rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}
    .toggle-link{text-align:center;margin-top:16px;font-size:.88rem;color:var(--muted);}
    .toggle-link a{color:var(--navy);font-weight:700;cursor:pointer;text-decoration:none;}
    .toggle-link a:hover{color:var(--gold);}
    .forgot-link{display:block;text-align:right;font-size:.82rem;color:var(--muted);margin-top:-8px;margin-bottom:14px;cursor:pointer;text-decoration:none;}
    .forgot-link:hover{color:var(--gold);}
    footer{text-align:center;padding:16px;color:rgba(255,255,255,.3);font-size:.78rem;}
  </style>
</head>
<body>

<div class="top-bar">
  <a href="/" class="brand"><img src="/logo.png" alt="Ukloole"><span>Ukloole</span></a>
  <a href="/community" class="back-link"><i class="fas fa-arrow-left"></i> Back to Guidance</a>
</div>

<div class="main">
  <div class="card">

    <?php
      // Determine which section to display
      $active = 'login';
      if ($show_reset)  $active = 'reset';
      elseif ($show_setup)  $active = 'setup';
      elseif ($show_forgot) $active = 'forgot';
    ?>

    <!-- ── LOGIN SECTION ── -->
    <div id="login-section" <?= $active !== 'login' ? 'style="display:none"' : '' ?>>
      <div class="card-icon"><i class="fas fa-users"></i></div>
      <h2>Community Login</h2>
      <p class="sub">Access your premium guidance area</p>

      <?php if ($error && $active === 'login'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success && $active === 'login'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="login">
        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" class="finput" placeholder="your@email.com" required>
        <label for="password">Password</label>
        <input type="password" name="password" id="password" class="finput" placeholder="Your password" required>
        <a class="forgot-link" onclick="toggleSection('forgot')"><i class="fas fa-lock"></i> Forgot your password?</a>
        <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
      </form>

      <div class="divider">OR</div>
      <div class="toggle-link">New member? <a onclick="toggleSection('setup')">Set up your password</a></div>
      <div class="toggle-link" style="margin-top:8px;"><a href="/community">Not a member yet? Get Premium Access</a></div>
    </div>

    <!-- ── SETUP PASSWORD SECTION ── -->
    <div id="setup-section" <?= $active !== 'setup' ? 'style="display:none"' : '' ?>>
      <div class="card-icon"><i class="fas fa-key"></i></div>
      <h2>Set Your Password</h2>
      <p class="sub">First-time setup after your payment is confirmed.</p>

      <?php if ($error && $active === 'setup'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success && $active === 'setup'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="setup">
        <label>Email Address (used during payment)</label>
        <input type="email" name="setup_email" class="finput" placeholder="your@email.com" required>
        <label>Choose a Password</label>
        <input type="password" name="setup_password" class="finput" placeholder="At least 8 characters" minlength="8" required>
        <label>Confirm Password</label>
        <input type="password" name="setup_confirm" class="finput" placeholder="Repeat your password" required>
        <button type="submit" class="btn-login"><i class="fas fa-lock"></i> Set Password &amp; Login</button>
      </form>

      <div class="toggle-link" style="margin-top:16px;">Already set up? <a onclick="toggleSection('login')">Login here</a></div>
    </div>

    <!-- ── FORGOT PASSWORD SECTION ── -->
    <div id="forgot-section" <?= $active !== 'forgot' ? 'style="display:none"' : '' ?>>
      <div class="card-icon"><i class="fas fa-envelope-open-text"></i></div>
      <h2>Forgot Password</h2>
      <p class="sub">Enter your email and we'll send you a reset link.</p>

      <?php if ($error && $active === 'forgot'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success && $active === 'forgot'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST">
        <input type="hidden" name="action" value="forgot">
        <label>Your Email Address</label>
        <input type="email" name="forgot_email" class="finput" placeholder="your@email.com" required autofocus>
        <button type="submit" class="btn-login"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
      </form>
      <?php endif; ?>

      <div class="toggle-link" style="margin-top:16px;"><a onclick="toggleSection('login')"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
    </div>

    <!-- ── RESET PASSWORD SECTION (token link) ── -->
    <div id="reset-section" <?= $active !== 'reset' ? 'style="display:none"' : '' ?>>
      <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
      <h2>Reset Password</h2>
      <p class="sub">Choose a strong new password for your account.</p>

      <?php if ($error && $active === 'reset'): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($success && $active !== 'reset'): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="reset_token" value="<?= htmlspecialchars($reset_token_get) ?>">
        <label>New Password</label>
        <input type="password" name="new_password" class="finput" placeholder="At least 8 characters" minlength="8" required autofocus>
        <label>Confirm New Password</label>
        <input type="password" name="new_confirm" class="finput" placeholder="Repeat your new password" required>
        <button type="submit" class="btn-login"><i class="fas fa-check"></i> Save New Password</button>
      </form>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="toggle-link" style="margin-top:16px;"><a onclick="toggleSection('login')"><i class="fas fa-sign-in-alt"></i> Go to Login</a></div>
      <?php endif; ?>
    </div>

  </div>
</div>

<footer>&copy; 2026 Ukloole. Premium Community Access.</footer>

<script>
  function toggleSection(show) {
    ['login','setup','forgot','reset'].forEach(function(s){
      document.getElementById(s+'-section').style.display = (s === show) ? 'block' : 'none';
    });
  }
</script>
</body>
</html>
