<?php
/**
 * UKLOOLE — Admin Dashboard v3
 * Full admin: Dashboard, Orders, Resources, Certificates (Candidates), FAQ, Testimonials, Community
 *
 * SECURITY:
 *  - Session cookies are hardened in security.php (HttpOnly, SameSite, Secure on HTTPS).
 *  - Login is rate-limited (5 failed attempts → 5-minute lockout per session).
 *  - The login form requires a CSRF token.
 *  - Set ADMIN_PASSWORD as an environment variable in cPanel to override the default.
 */

require_once __DIR__ . '/security.php';

$admin_password = getenv('ADMIN_PASSWORD') ?: 'Admin4ukloole';
$staff_password = getenv('STAFF_PASSWORD') ?: 'StaffUkloole2024';

// Load staff password override from DB if available
try {
    $_early_conn = new mysqli('localhost', 'ukloolec_lbuser', 'Admin4ukloole', 'ukloolec_learninghub');
    if (!$_early_conn->connect_error) {
        $r = $_early_conn->query("SELECT v FROM app_settings WHERE k='staff_password_override' LIMIT 1");
        if ($r) {
            $row = $r->fetch_assoc();
            if (!empty($row['v'])) $staff_password = $row['v'];
        }
        $_early_conn->close();
    }
} catch (Exception $e) { /* ignore, use default */ }
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---- Brute-force throttle ----
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$_SESSION['login_locked_until'] = $_SESSION['login_locked_until'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_SESSION['admin'])) {
    if (time() < $_SESSION['login_locked_until']) {
        $login_error = 'Too many failed attempts. Try again in ' . max(1, (int)ceil(($_SESSION['login_locked_until'] - time()) / 60)) . ' minute(s).';
    } elseif (!csrf_check()) {
        $login_error = 'Session expired. Please reload and try again.';
    } elseif (hash_equals($admin_password, (string)$_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_role'] = 'super';
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = 0;
        unset($_SESSION['_csrf']);
    } elseif (hash_equals($staff_password, (string)$_POST['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_role'] = 'staff';
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_locked_until'] = 0;
        unset($_SESSION['_csrf']);
    } else {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
            $login_error = 'Too many failed attempts. Try again in 5 minutes.';
        } else {
            $login_error = 'Incorrect password.';
        }
    }
}

// ---- Role helpers ----
// Settings tab is always super-only. All other tabs can be toggled.
$is_super = ($_SESSION['admin_role'] ?? 'super') === 'super';
$is_staff = ($_SESSION['admin_role'] ?? '') === 'staff';
if (!isset($_SESSION['admin'])) {
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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login — Ukloole</title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--navy:#0D1B3E;--navy-mid:#1A2D5A;--gold:#C9A84C;--gold-pale:#FFF8E7;--border:#E2E8F0;--muted:#6B7A99;}
    body{font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,var(--navy) 0%,var(--navy-mid) 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .card{background:#fff;border-radius:24px;padding:48px;max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
    .brand{display:flex;align-items:center;gap:10px;margin-bottom:32px;}
    .brand img{width:42px;}
    .brand-name{font-size:1.4rem;font-weight:700;color:var(--navy);}
    .brand-sub{font-size:0.75rem;color:var(--muted);}
    h2{font-family:'EB Garamond',serif;font-size:2rem;color:var(--navy);margin-bottom:6px;}
    .subtitle{color:var(--muted);font-size:0.9rem;margin-bottom:28px;}
    .error{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:0.88rem;margin-bottom:16px;}
    .input{width:100%;padding:13px 16px;border:2px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:0.95rem;outline:none;transition:border-color 0.2s;margin-bottom:14px;}
    .input:focus{border-color:var(--gold);}
    .btn{width:100%;padding:14px;background:linear-gradient(135deg,var(--navy),var(--navy-mid));color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.2s;}
    .btn:hover{background:linear-gradient(135deg,var(--navy-mid),var(--gold));transform:translateY(-1px);}
    .gold-dot{width:8px;height:8px;background:var(--gold);border-radius:50%;display:inline-block;margin-right:6px;}
  </style>
</head>
<body class="login-page">
  <div class="card">
    <div class="brand">
      <img src="/logo.png" alt="Ukloole">
      <div><div class="brand-name">Ukloole</div><div class="brand-sub">Admin Dashboard</div></div>
    </div>
    <h2>Admin Login</h2>
    <p class="subtitle">Restricted Access — Authorized Personnel Only</p>
    <?php if (!empty($login_error)): ?><p class="error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($login_error) ?></p><?php endif; ?>
    <form method="POST" autocomplete="off">
      <?= csrf_field() ?>
      <input type="password" name="password" class="input" placeholder="Admin Password" required autofocus autocomplete="current-password">
      <button type="submit" class="btn"><i class="fas fa-lock-open"></i> Access Dashboard</button>
    </form>
  </div>
</body>
</html>
<?php
    exit;
}
// ============================================================
// DATABASE
// ============================================================
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

  // ---- Self-heal: add new resource columns if they don't exist ----
  try { $conn->query("ALTER TABLE resources MODIFY COLUMN type ENUM('paid','free','gpt') NOT NULL DEFAULT 'paid'"); } catch (Exception $e) { /* ignore */ }
  // Helper: add a column only if it doesn't already exist (compatible with MySQL 5.x)
  function admin_add_column_if_missing($conn, $table, $column, $definition) {
      $db = $conn->query("SELECT DATABASE()")->fetch_row()[0];
      $res = $conn->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
      if ($res && $res->fetch_row()[0] == 0) {
          @$conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
      }
  }
  admin_add_column_if_missing($conn, 'resources', 'file_type', 'VARCHAR(10) NULL DEFAULT NULL');
  admin_add_column_if_missing($conn, 'resources', 'original_price', 'DECIMAL(10,2) NULL DEFAULT NULL');

  // Initialize $mods globally so modal templates outside the courses tab don't get undefined warnings
  $mods = [];

  // ---- App settings (key/value) -------------------------------------------
  @$conn->query("CREATE TABLE IF NOT EXISTS app_settings (
    k VARCHAR(100) NOT NULL PRIMARY KEY,
    v TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  function admin_get_setting($conn, $k, $default = '') {
      $stmt = $conn->prepare("SELECT v FROM app_settings WHERE k=? LIMIT 1");
      if (!$stmt) return $default;
      $stmt->bind_param('s', $k);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $stmt->close();
      return ($row && $row['v'] !== null) ? $row['v'] : $default;
  }
  function admin_set_setting($conn, $k, $v) {
      $stmt = $conn->prepare("INSERT INTO app_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)");
      if (!$stmt) return false;
      $stmt->bind_param('ss', $k, $v);
      $ok = $stmt->execute();
      $stmt->close();
      return $ok;
  }

  // ---- All toggleable tabs (Settings is always super-only) ----
  $all_tabs = [
      'dashboard'          => 'Dashboard',
      'orders'             => 'Orders & Payments',
      'resources'          => 'Resources',
      'certificates'       => 'Certificates',
      'community'          => 'Community Members',
      'faq'                => 'FAQ',
      'testimonials'       => 'Testimonials',
      'courses'            => 'Courses',
      'community_jobs'     => 'Job Links',
      'community_webinars' => 'Webinars',
      'community_qa'       => 'Q&A',
      'community_resources'=> 'Member Resources',
      'community_updates'  => 'Updates',
      'newsletter'         => 'Newsletter',
      'assessment_results' => 'Assessment Results',
  ];

  // Load which tabs staff are allowed — stored as JSON in app_settings
  $staff_perms_raw = admin_get_setting($conn, 'staff_permissions', '');
  $staff_perms = $staff_perms_raw ? json_decode($staff_perms_raw, true) : [];
  if (!is_array($staff_perms)) $staff_perms = [];

  // Helper: can the current user access a given tab?
  function can_access($tab, $is_super, $staff_perms) {
      if ($is_super) return true;
      if ($tab === 'settings') return false; // always super-only
      return in_array($tab, $staff_perms);
  }

  // Enforce tab restriction for staff
  if ($is_staff) {
      $requested_tab = $_GET['tab'] ?? 'dashboard';
      if (!can_access($requested_tab, false, $staff_perms)) {
          header('Location: admin.php?tab=' . (can_access('dashboard', false, $staff_perms) ? 'dashboard' : (($staff_perms[0] ?? 'orders'))));
          exit;
      }
  }
  
$action = $_POST['action'] ?? '';

// ============================================================
// ACTIONS
// ============================================================

// --- CERTIFICATES / CANDIDATES ---
if ($action === 'add_candidate') {
    $full_name    = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $course_title = htmlspecialchars(trim($_POST['course_title'] ?? 'Customer Service Mastery'));
    $status       = in_array($_POST['status'] ?? '', ['pending','passed','failed']) ? $_POST['status'] : 'pending';
    $issue_date   = $status === 'passed' ? date('Y-m-d') : null;
    if ($full_name) {
        $cert_code = 'UKL-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $stmt = $conn->prepare("INSERT INTO candidates (certificate_code, full_name, course_title, status, issue_date) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $cert_code, $full_name, $course_title, $status, $issue_date);
        $stmt->execute();
        $_SESSION['flash'] = "Candidate added. Code: $cert_code";
    }
    header('Location: admin.php?tab=certificates'); exit;
}

if ($action === 'update_candidate_status') {
    $id     = intval($_POST['cand_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['pending','passed','failed']) ? $_POST['status'] : 'pending';
    if ($id) {
        $issue_date = $status === 'passed' ? date('Y-m-d') : null;
        $stmt = $conn->prepare("UPDATE candidates SET status=?, issue_date=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $issue_date, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Candidate status updated to: $status";
    }
    header('Location: admin.php?tab=certificates'); exit;
}

if ($action === 'delete_candidate') {
    $id = intval($_POST['cand_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM candidates WHERE id=$id");
    header('Location: admin.php?tab=certificates'); exit;
}

// --- RESOURCES ---
if ($action === 'add_resource') {
    $name  = trim($_POST['r_name'] ?? '');
    $desc  = trim($_POST['r_desc'] ?? '');
    $price = floatval($_POST['r_price'] ?? 0);
    $type  = in_array($_POST['r_type'] ?? '', ['paid','free','gpt']) ? $_POST['r_type'] : 'paid';
    $fkey  = trim($_POST['r_filekey'] ?? '');
    $sort  = intval($_POST['r_sort'] ?? 0);
    if ($type === 'free') $price = 0;
    if ($name) {
        $file_type  = trim($_POST['r_file_type'] ?? '');
        $orig_price = floatval($_POST['r_orig_price'] ?? 0);
        $orig_price_val = ($orig_price > 0 && $orig_price > $price) ? $orig_price : 0.0;
        $stmt = $conn->prepare("INSERT INTO resources (name, description, price, type, file_key, is_active, sort_order, file_type, original_price) VALUES (?,?,?,?,?,1,?,?,?)");
        $stmt->bind_param("ssdssisd", $name, $desc, $price, $type, $fkey, $sort, $file_type, $orig_price_val);
        $stmt->execute();
        $_SESSION['flash'] = "Resource added: $name";
    }
    header('Location: admin.php?tab=resources'); exit;
}

if ($action === 'edit_resource') {
    $id     = intval($_POST['r_id'] ?? 0);
    $name   = trim($_POST['r_name'] ?? '');
    $desc   = trim($_POST['r_desc'] ?? '');
    $price  = floatval($_POST['r_price'] ?? 0);
    $type   = in_array($_POST['r_type'] ?? '', ['paid','free','gpt']) ? $_POST['r_type'] : 'paid';
    $fkey   = trim($_POST['r_filekey'] ?? '');
    $sort   = intval($_POST['r_sort'] ?? 0);
    $active = intval($_POST['r_active'] ?? 1);
    if ($type === 'free') $price = 0;
    if ($id && $name) {
        $file_type  = trim($_POST['r_file_type'] ?? '');
        $orig_price = floatval($_POST['r_orig_price'] ?? 0);
        $orig_price_val = ($orig_price > 0 && $orig_price > $price) ? $orig_price : 0.0;
        $stmt = $conn->prepare("UPDATE resources SET name=?,description=?,price=?,type=?,file_key=?,is_active=?,sort_order=?,file_type=?,original_price=? WHERE id=?");
        $stmt->bind_param("ssdssiisdi", $name, $desc, $price, $type, $fkey, $active, $sort, $file_type, $orig_price_val, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Resource updated: $name";
    }
    header('Location: admin.php?tab=resources'); exit;
}

if ($action === 'toggle_resource') {
    $id = intval($_POST['r_id'] ?? 0);
    if ($id) $conn->query("UPDATE resources SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=resources'); exit;
}

if ($action === 'save_settings') {
      // Multi-tier pricing
      $tiers = ['monthly', 'quarterly', 'bi_annual', 'yearly'];
      foreach ($tiers as $tier) {
          $raw = trim($_POST['s_price_' . $tier] ?? '');
          $val = ($raw === '' || $raw === '0') ? '' : (string)max(0, intval(preg_replace('/[^0-9]/', '', $raw)));
          admin_set_setting($conn, 'price_' . $tier, $val);
      }
      // Staff permissions — save as JSON array of allowed tab keys
      $allowed = [];
      foreach (array_keys($all_tabs) as $tab_key) {
          if ($tab_key === 'settings') continue; // never allowed for staff
          if (!empty($_POST['staff_perm_' . $tab_key])) {
              $allowed[] = $tab_key;
          }
      }
      admin_set_setting($conn, 'staff_permissions', json_encode($allowed));
      // Staff password change (optional)
      $new_staff_pass = trim($_POST['new_staff_password'] ?? '');
      if ($new_staff_pass !== '') {
          admin_set_setting($conn, 'staff_password_override', $new_staff_pass);
      }
      header('Location: admin.php?tab=settings&saved=1'); exit;
  }

  if ($action === 'delete_resource') {
    $id = intval($_POST['r_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM resources WHERE id=$id");
    header('Location: admin.php?tab=resources'); exit;
}



// --- Newsletter ---
if ($action === 'delete_subscriber') {
    $id = intval($_POST['sub_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM newsletter_subscribers WHERE id=$id");
    header('Location: admin.php?tab=newsletter'); exit;
}
if ($action === 'add_subscriber') {
    $email = filter_var(trim($_POST['sub_email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $name  = trim($_POST['sub_name'] ?? '');
    if ($email) {
        $stmt = $conn->prepare("INSERT IGNORE INTO newsletter_subscribers (email, name, source) VALUES (?,?,'manual')");
        $stmt->bind_param("ss", $email, $name);
        $stmt->execute(); $stmt->close();
    }
    header('Location: admin.php?tab=newsletter'); exit;
}

// --- Community Updates ---
if ($action === 'add_update') {
    $type  = in_array($_POST['upd_type']??'', ['update','opportunity','announcement']) ? $_POST['upd_type'] : 'update';
    $title = trim($_POST['upd_title'] ?? '');
    $body  = trim($_POST['upd_body']  ?? '');
    if ($title && $body) {
        $stmt = $conn->prepare("INSERT INTO community_updates (type, title, body) VALUES (?,?,?)");
        $stmt->bind_param("sss", $type, $title, $body);
        $stmt->execute(); $stmt->close();
    }
    header('Location: admin.php?tab=community_updates'); exit;
}
if ($action === 'delete_update') {
    $id = intval($_POST['upd_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_updates WHERE id=$id");
    header('Location: admin.php?tab=community_updates'); exit;
}
if ($action === 'toggle_update') {
    $id = intval($_POST['upd_id'] ?? 0);
    if ($id) $conn->query("UPDATE community_updates SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=community_updates'); exit;
}

// --- FAQ ---
if ($action === 'add_faq') {
    $q = trim($_POST['question'] ?? '');
    $a = trim($_POST['answer'] ?? '');
    $s = intval($_POST['sort_order'] ?? 0);
    if ($q && $a) {
        $stmt = $conn->prepare("INSERT INTO faqs (question, answer, sort_order) VALUES (?,?,?)");
        $stmt->bind_param("ssi", $q, $a, $s);
        $stmt->execute();
    }
    header('Location: admin.php?tab=faq'); exit;
}

if ($action === 'edit_faq') {
    $id = intval($_POST['faq_id'] ?? 0);
    $q  = trim($_POST['question'] ?? '');
    $a  = trim($_POST['answer'] ?? '');
    $s  = intval($_POST['sort_order'] ?? 0);
    if ($id && $q && $a) {
        $stmt = $conn->prepare("UPDATE faqs SET question=?,answer=?,sort_order=? WHERE id=?");
        $stmt->bind_param("ssii", $q, $a, $s, $id);
        $stmt->execute();
    }
    header('Location: admin.php?tab=faq'); exit;
}

if ($action === 'delete_faq') {
    $id = intval($_POST['faq_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM faqs WHERE id=$id");
    header('Location: admin.php?tab=faq'); exit;
}

// --- ORDERS: Resend download link ---
if ($action === 'resend_link') {
    $id = intval($_POST['order_id'] ?? 0);
    if ($id) {
        // Reset downloaded count and extend expiry by 7 days from now
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $upd_stmt = $conn->prepare("UPDATE orders SET downloaded=0, expires_at=? WHERE id=?");
        $upd_stmt->bind_param("si", $expires, $id);
        $upd_stmt->execute();
        $upd_stmt->close();
        // Fetch order details to resend email
        $sel_stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
        $sel_stmt->bind_param("i", $id);
        $sel_stmt->execute();
        $o = $sel_stmt->get_result()->fetch_assoc();
        $sel_stmt->close();
        if ($o) {
            require_once __DIR__ . '/mailer.php';
            $link = 'https://learn.ukloole.com/download.php?token=' . $o['token'];
            $items = json_decode($o['product'], true);
            $product_display = is_array($items) ? implode(', ', $items) : $o['product'];
            $subject = 'Your Ukloole Download Link (Reissued)';
            $plain = "Hello " . ($o['name'] ?: 'there') . ",

Here is your reissued download link:
" . $link . "

This link expires in 7 days and can only be used once.

- The Ukloole Team";
            $html = lh_email_template('Your Download Link - Reissued',
              '<p style="color:#444;line-height:1.7;">Hello <strong>' . htmlspecialchars($o['name'] ?: 'there') . '</strong>,</p>'
              . '<p style="color:#444;line-height:1.7;">Here is your reissued download link for:<br><strong>' . htmlspecialchars($product_display) . '</strong></p>'
              . '<p style="text-align:center;margin:28px 0;">'
              . '<a href="' . htmlspecialchars($link) . '" style="background:#1a3c5e;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">Download My File</a>'
              . '</p>'
              . '<p style="color:#888;font-size:13px;">This link expires in <strong>7 days</strong> and can only be used <strong>once</strong>.<br>'
              . 'Issues? <a href="mailto:learn@ukloole.com" style="color:#1a3c5e;">learn@ukloole.com</a></p>'
            );
            lh_send_email($o['email'], $o['name'], $subject, $html, $plain);
        }
    }
    header('Location: admin.php?tab=orders&resent=1'); exit;
}

// --- TESTIMONIALS ---
if ($action === 'add_testimonial') {
    $name    = htmlspecialchars(trim($_POST['t_name'] ?? ''));
    $role    = htmlspecialchars(trim($_POST['t_role'] ?? ''));
    $content = trim($_POST['t_content'] ?? '');
    $rating  = min(5, max(1, intval($_POST['t_rating'] ?? 5)));
    $init    = strtoupper(substr(htmlspecialchars(trim($_POST['t_initials'] ?? '')), 0, 2));
    if ($name && $content) {
        $stmt = $conn->prepare("INSERT INTO testimonials (name, role, content, rating, avatar_initials) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssis", $name, $role, $content, $rating, $init);
        $stmt->execute();
    }
    header('Location: admin.php?tab=testimonials'); exit;
}

if ($action === 'edit_testimonial') {
    $id       = intval($_POST['t_id'] ?? 0);
    $name     = trim($_POST['t_name']    ?? '');
    $role     = trim($_POST['t_role']    ?? '');
    $content_t= trim($_POST['t_content'] ?? '');
    $rating   = intval($_POST['t_rating'] ?? 5);
    $initials = strtoupper(substr(trim($_POST['t_initials'] ?? ''), 0, 2));
    if ($id && $name) {
        $stmt = $conn->prepare("UPDATE testimonials SET name=?,role=?,content=?,rating=?,avatar_initials=? WHERE id=?");
        $stmt->bind_param("sssisi", $name, $role, $content_t, $rating, $initials, $id);
        $stmt->execute(); $stmt->close();
    }
    header('Location: admin.php?tab=testimonials'); exit;
}
if ($action === 'delete_testimonial') {
    $id = intval($_POST['t_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM testimonials WHERE id=$id");
    header('Location: admin.php?tab=testimonials'); exit;
}

// --- COMMUNITY MEMBERS ---
if ($action === 'delete_community') {
    $id = intval($_POST['cm_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_members WHERE id=$id");
    header('Location: admin.php?tab=community'); exit;
}

// --- JOB LINKS ---
if ($action === 'add_job_link') {
    $title   = trim($_POST['jl_title']   ?? '');
    $url     = htmlspecialchars(trim($_POST['jl_url']     ?? ''));
    $expires = htmlspecialchars(trim($_POST['jl_expires'] ?? '')) ?: null;
    $sort    = intval($_POST['jl_sort'] ?? 0);
    if ($title && $url) {
        $stmt = $conn->prepare("INSERT INTO community_job_links (title, url, expires_at, sort_order) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi", $title, $url, $expires, $sort);
        $stmt->execute();
        $_SESSION['flash'] = "Job link added: $title";
    }
    header('Location: admin.php?tab=community_jobs'); exit;
}

if ($action === 'edit_job_link') {
    $id      = intval($_POST['jl_id']      ?? 0);
    $title   = trim($_POST['jl_title']   ?? '');
    $url     = htmlspecialchars(trim($_POST['jl_url']     ?? ''));
    $expires = htmlspecialchars(trim($_POST['jl_expires'] ?? '')) ?: null;
    $sort    = intval($_POST['jl_sort'] ?? 0);
    if ($id && $title && $url) {
        $stmt = $conn->prepare("UPDATE community_job_links SET title=?, url=?, expires_at=?, sort_order=? WHERE id=?");
        $stmt->bind_param("sssii", $title, $url, $expires, $sort, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Job link updated.";
    }
    header('Location: admin.php?tab=community_jobs'); exit;
}

if ($action === 'toggle_job_link') {
    $id = intval($_POST['jl_id'] ?? 0);
    if ($id) $conn->query("UPDATE community_job_links SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=community_jobs'); exit;
}

if ($action === 'delete_job_link') {
    $id = intval($_POST['jl_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_job_links WHERE id=$id");
    header('Location: admin.php?tab=community_jobs'); exit;
}

// --- WEBINARS ---
if ($action === 'add_webinar') {
    $title = trim($_POST['wb_title'] ?? '');
    $desc  = htmlspecialchars(trim($_POST['wb_desc']  ?? ''));
    $url   = htmlspecialchars(trim($_POST['wb_url']   ?? ''));
    $date  = htmlspecialchars(trim($_POST['wb_date']  ?? '')) ?: null;
    if ($title && $url) {
        $stmt = $conn->prepare("INSERT INTO community_webinars (title, description, url, event_date) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $title, $desc, $url, $date);
        $stmt->execute();
        $_SESSION['flash'] = "Webinar added: $title";
    }
    header('Location: admin.php?tab=community_webinars'); exit;
}

if ($action === 'edit_webinar') {
    $id    = intval($_POST['wb_id']    ?? 0);
    $title = trim($_POST['wb_title'] ?? '');
    $desc  = htmlspecialchars(trim($_POST['wb_desc']  ?? ''));
    $url   = htmlspecialchars(trim($_POST['wb_url']   ?? ''));
    $date  = htmlspecialchars(trim($_POST['wb_date']  ?? '')) ?: null;
    if ($id && $title && $url) {
        $stmt = $conn->prepare("UPDATE community_webinars SET title=?, description=?, url=?, event_date=? WHERE id=?");
        $stmt->bind_param("ssssi", $title, $desc, $url, $date, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Webinar updated.";
    }
    header('Location: admin.php?tab=community_webinars'); exit;
}

if ($action === 'toggle_webinar') {
    $id = intval($_POST['wb_id'] ?? 0);
    if ($id) $conn->query("UPDATE community_webinars SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=community_webinars'); exit;
}

if ($action === 'delete_webinar') {
    $id = intval($_POST['wb_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_webinars WHERE id=$id");
    header('Location: admin.php?tab=community_webinars'); exit;
}

// --- Q&A REPLY (threads system) ---
if ($action === 'reply_question') {
    $thread_id = intval($_POST['q_id'] ?? 0);
    $answer    = trim($_POST['q_answer'] ?? '');
    if ($thread_id && $answer) {
        $sender = 'admin';
        $stmt = $conn->prepare("INSERT INTO community_thread_messages (thread_id, sender, body) VALUES (?,?,?)");
        $stmt->bind_param("iss", $thread_id, $sender, $answer);
        $stmt->execute(); $stmt->close();
        $conn->query("UPDATE community_threads SET status='answered', updated_at=NOW() WHERE id=$thread_id");
        $_SESSION['flash'] = "Reply sent.";
    }
    header('Location: admin.php?tab=community_qa'); exit;
}

if ($action === 'delete_question') {
    $id = intval($_POST['q_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_threads WHERE id=$id");
    header('Location: admin.php?tab=community_qa'); exit;
}

// --- COMMUNITY RESOURCES ---
if ($action === 'add_comm_resource') {
    $title = trim($_POST['cr_title'] ?? '');
    $desc  = htmlspecialchars(trim($_POST['cr_desc']  ?? ''));
    $url   = htmlspecialchars(trim($_POST['cr_url']   ?? ''));
    $sort  = intval($_POST['cr_sort'] ?? 0);
    if ($title && $url) {
        $stmt = $conn->prepare("INSERT INTO community_resources (title, description, url, sort_order) VALUES (?,?,?,?)");
        $stmt->bind_param("sssi", $title, $desc, $url, $sort);
        $stmt->execute();
        $_SESSION['flash'] = "Resource added: $title";
    }
    header('Location: admin.php?tab=community_resources'); exit;
}

if ($action === 'edit_comm_resource') {
    $id    = intval($_POST['cr_id']    ?? 0);
    $title = trim($_POST['cr_title'] ?? '');
    $desc  = htmlspecialchars(trim($_POST['cr_desc']  ?? ''));
    $url   = htmlspecialchars(trim($_POST['cr_url']   ?? ''));
    $sort  = intval($_POST['cr_sort'] ?? 0);
    if ($id && $title && $url) {
        $stmt = $conn->prepare("UPDATE community_resources SET title=?, description=?, url=?, sort_order=? WHERE id=?");
        $stmt->bind_param("sssii", $title, $desc, $url, $sort, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Resource updated.";
    }
    header('Location: admin.php?tab=community_resources'); exit;
}

if ($action === 'toggle_comm_resource') {
    $id = intval($_POST['cr_id'] ?? 0);
    if ($id) $conn->query("UPDATE community_resources SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=community_resources'); exit;
}

if ($action === 'delete_comm_resource') {
    $id = intval($_POST['cr_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM community_resources WHERE id=$id");
    header('Location: admin.php?tab=community_resources'); exit;
}

// --- COURSE MODULES ---
if ($action === 'add_module') {
    $title = trim($_POST['mod_title'] ?? '');
    $sort  = intval($_POST['mod_sort'] ?? 0);
    if ($title) {
        $stmt = $conn->prepare("INSERT INTO course_modules (title, sort_order) VALUES (?,?)");
        $stmt->bind_param("si", $title, $sort);
        $stmt->execute();
        $_SESSION['flash'] = "Module added: $title";
    }
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'edit_module') {
    $id    = intval($_POST['mod_id'] ?? 0);
    $title = trim($_POST['mod_title'] ?? '');
    $sort  = intval($_POST['mod_sort'] ?? 0);
    if ($id && $title) {
        $stmt = $conn->prepare("UPDATE course_modules SET title=?, sort_order=? WHERE id=?");
        $stmt->bind_param("sii", $title, $sort, $id);
        $stmt->execute();
        $_SESSION['flash'] = "Module updated.";
    }
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'delete_module') {
    $id = intval($_POST['mod_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM course_modules WHERE id=$id");
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'toggle_module') {
    $id = intval($_POST['mod_id'] ?? 0);
    if ($id) $conn->query("UPDATE course_modules SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=courses'); exit;
}

// --- COURSE LESSONS ---
if ($action === 'add_lesson') {
    $mod_id = intval($_POST['lesson_module_id'] ?? 0);
    $title  = trim($_POST['lesson_title'] ?? '');
    $yt     = trim($_POST['lesson_youtube'] ?? '');
    $sort   = intval($_POST['lesson_sort'] ?? 0);
    if (preg_match('/(?:v=|\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $yt, $m)) $yt = $m[1];
    if ($mod_id && $title) {
        $stmt = $conn->prepare("INSERT INTO course_lessons (module_id, title, youtube_id, sort_order) VALUES (?,?,?,?)");
        $stmt->bind_param("issi", $mod_id, $title, $yt, $sort);
        $stmt->execute();
        $new_lesson_id = $conn->insert_id;
        $_SESSION['flash'] = "Lesson added: $title";

        // Save quiz if provided
        $qq = trim($_POST['quiz_q'] ?? '');
        $qa = trim($_POST['quiz_a'] ?? '');
        $qb = trim($_POST['quiz_b'] ?? '');
        $qc = trim($_POST['quiz_c'] ?? '');
        $qcor = in_array($_POST['quiz_cor']??'', ['a','b','c']) ? $_POST['quiz_cor'] : 'a';
        if ($qq && $qa && $qb && $qc) {
            $stmt2 = $conn->prepare("INSERT INTO lesson_quizzes (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES (?,?,?,?,?,?)");
            $stmt2->bind_param("isssss", $new_lesson_id, $qq, $qa, $qb, $qc, $qcor);
            $stmt2->execute();
        }

        // Save scenario if provided
        $sq = trim($_POST['sc_q'] ?? '');
        $sa = trim($_POST['sc_a'] ?? '');
        $sb = trim($_POST['sc_b'] ?? '');
        $sc = trim($_POST['sc_c'] ?? '');
        $scor = in_array($_POST['sc_cor']??'', ['a','b','c']) ? $_POST['sc_cor'] : 'a';
        if ($sq && $sa && $sb && $sc) {
            $stmt3 = $conn->prepare("INSERT INTO lesson_scenarios (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES (?,?,?,?,?,?)");
            $stmt3->bind_param("isssss", $new_lesson_id, $sq, $sa, $sb, $sc, $scor);
            $stmt3->execute();
        }
    }
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'edit_lesson') {
    $id     = intval($_POST['lesson_id'] ?? 0);
    $title  = trim($_POST['lesson_title'] ?? '');
    $yt     = trim($_POST['lesson_youtube'] ?? '');
    $sort   = intval($_POST['lesson_sort'] ?? 0);
    $mod_id = intval($_POST['lesson_module_id'] ?? 0);
    if (preg_match('/(?:v=|\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $yt, $m)) $yt = $m[1];
    if ($id && $title) {
        $stmt = $conn->prepare("UPDATE course_lessons SET module_id=?, title=?, youtube_id=?, sort_order=? WHERE id=?");
        $stmt->bind_param("issii", $mod_id, $title, $yt, $sort, $id);
        $stmt->execute();

        // Upsert quiz
        $qq = trim($_POST['quiz_q'] ?? '');
        $qa = trim($_POST['quiz_a'] ?? '');
        $qb = trim($_POST['quiz_b'] ?? '');
        $qc = trim($_POST['quiz_c'] ?? '');
        $qcor = in_array($_POST['quiz_cor']??'', ['a','b','c']) ? $_POST['quiz_cor'] : 'a';
        if ($qq) {
            $stmt2 = $conn->prepare("INSERT INTO lesson_quizzes (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE question=VALUES(question), option_a=VALUES(option_a), option_b=VALUES(option_b), option_c=VALUES(option_c), correct_option=VALUES(correct_option)");
            $stmt2->bind_param("isssss", $id, $qq, $qa, $qb, $qc, $qcor);
            $stmt2->execute();
        }

        // Upsert scenario
        $sq = trim($_POST['sc_q'] ?? '');
        $sa = trim($_POST['sc_a'] ?? '');
        $sb = trim($_POST['sc_b'] ?? '');
        $sc_opt = trim($_POST['sc_c'] ?? '');
        $scor = in_array($_POST['sc_cor']??'', ['a','b','c']) ? $_POST['sc_cor'] : 'a';
        if ($sq) {
            $stmt3 = $conn->prepare("INSERT INTO lesson_scenarios (lesson_id, question, option_a, option_b, option_c, correct_option) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE question=VALUES(question), option_a=VALUES(option_a), option_b=VALUES(option_b), option_c=VALUES(option_c), correct_option=VALUES(correct_option)");
            $stmt3->bind_param("isssss", $id, $sq, $sa, $sb, $sc_opt, $scor);
            $stmt3->execute();
        }

        $_SESSION['flash'] = "Lesson updated.";
    }
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'delete_lesson') {
    $id = intval($_POST['lesson_id'] ?? 0);
    if ($id) $conn->query("DELETE FROM course_lessons WHERE id=$id");
    header('Location: admin.php?tab=courses'); exit;
}

if ($action === 'toggle_lesson') {
    $id = intval($_POST['lesson_id'] ?? 0);
    if ($id) $conn->query("UPDATE course_lessons SET is_active = 1 - is_active WHERE id=$id");
    header('Location: admin.php?tab=courses'); exit;
}
// ============================================================
// FETCH STATS
// ============================================================
$tab = $_GET['tab'] ?? 'dashboard';

$total_orders      = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0;
$total_revenue     = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM orders WHERE status='paid'")->fetch_assoc()['s'] ?? 0;
$unique_customers  = $conn->query("SELECT COUNT(DISTINCT email) c FROM orders")->fetch_assoc()['c'] ?? 0;
$total_candidates  = $conn->query("SELECT COUNT(*) c FROM candidates")->fetch_assoc()['c'] ?? 0;
$passed_certs      = $conn->query("SELECT COUNT(*) c FROM candidates WHERE status='passed'")->fetch_assoc()['c'] ?? 0;
$total_resources   = $conn->query("SELECT COUNT(*) c FROM resources")->fetch_assoc()['c'] ?? 0;
$community_members = $conn->query("SELECT COUNT(*) c FROM community_members")->fetch_assoc()['c'] ?? 0;

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="icon" href="/logo.png" type="image/png">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Ukloole</title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{
      --navy:#0D1B3E;--navy-mid:#1A2D5A;--navy-light:#243566;
      --gold:#C9A84C;--gold-light:#F2D06B;--gold-pale:#FFF8E7;
      --bg:#F0F4FA;--card:#FFFFFF;--border:#E2E8F0;
      --text:#1A2D5A;--muted:#6B7A99;
      --success:#16a34a;--danger:#dc2626;--warn:#d97706;
      --shadow:0 4px 24px rgba(13,27,62,0.08);
      --shadow-lg:0 8px 40px rgba(13,27,62,0.14);
    }
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);}
    h1,h2,h3,h4{font-family:'EB Garamond',serif;}
    a{text-decoration:none;color:inherit;}

    .admin-wrap{display:flex;min-height:100vh;}

    /* SIDEBAR */
    .sidebar{
      width:258px;flex-shrink:0;
      background:var(--navy);
      display:flex;flex-direction:column;
      position:sticky;top:0;height:100vh;overflow-y:auto;
    }
    .sb-brand{
      padding:24px 20px 20px;
      border-bottom:1px solid rgba(255,255,255,.1);
      display:flex;align-items:center;gap:12px;
    }
    .sb-brand img{width:36px;border-radius:8px;}
    .sb-brand-text span{display:block;font-weight:700;font-size:1.1rem;color:#fff;}
    .sb-brand-text small{color:rgba(255,255,255,.4);font-size:.7rem;letter-spacing:.04em;text-transform:uppercase;}
    .sb-nav{padding:16px 0;flex:1;}
    .sb-section{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.3);padding:10px 20px 4px;}
    .sb-link{
      display:flex;align-items:center;gap:10px;
      padding:11px 20px;font-size:.88rem;font-weight:500;
      color:rgba(255,255,255,.6);transition:.15s;
      border-left:3px solid transparent;
    }
    .sb-link:hover{background:rgba(255,255,255,.06);color:#fff;}
    .sb-link.active{background:rgba(201,168,76,.12);color:var(--gold-light);border-left-color:var(--gold);}
    .sb-link i{width:18px;text-align:center;font-size:.9rem;}
    .sb-footer{padding:14px 16px;border-top:1px solid rgba(255,255,255,.1);}
    .sb-footer a{display:flex;align-items:center;gap:10px;padding:10px 14px;color:rgba(255,100,100,.7);font-size:.85rem;border-radius:8px;transition:.15s;}
    .sb-footer a:hover{background:rgba(255,80,80,.1);color:#fff;}

    /* MAIN */
    .main{flex:1;padding:32px;min-width:0;overflow-x:auto;}
    .page-title{font-size:1.8rem;color:var(--navy);margin-bottom:4px;}
    .page-sub{color:var(--muted);font-size:.9rem;margin-bottom:28px;}

    /* FLASH */
    .flash{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:12px 18px;border-radius:10px;margin-bottom:20px;font-size:.88rem;display:flex;align-items:center;gap:8px;}

    /* STATS */
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:36px;}
    .stat-card{background:var(--card);border-radius:16px;padding:20px;border:1px solid var(--border);box-shadow:var(--shadow);}
    .stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;font-size:1rem;}
    .stat-value{font-family:'EB Garamond',serif;font-size:2rem;font-weight:700;color:var(--navy);}
    .stat-label{color:var(--muted);font-size:.78rem;margin-top:3px;}

    /* CHART MINI */
    .revenue-card{background:linear-gradient(135deg,var(--navy),var(--navy-mid));border-radius:16px;padding:24px;color:white;margin-bottom:28px;}
    .revenue-card h3{font-size:1rem;color:rgba(255,255,255,.6);font-family:'DM Sans',sans-serif;font-weight:500;margin-bottom:4px;}
    .revenue-big{font-family:'EB Garamond',serif;font-size:2.8rem;font-weight:700;color:var(--gold-light);}
    .revenue-card .sub{font-size:.82rem;color:rgba(255,255,255,.4);margin-top:2px;}

    /* TABLES */
    .tcard{background:var(--card);border-radius:16px;overflow:hidden;box-shadow:var(--shadow);border:1px solid var(--border);margin-bottom:24px;}
    .tcard-hd{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px;}
    .tcard-hd h3{font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:700;color:var(--navy);}
    table{width:100%;border-collapse:collapse;}
    th{background:#f8faff;padding:11px 16px;text-align:left;font-size:.72rem;color:var(--muted);letter-spacing:.05em;font-weight:700;text-transform:uppercase;}
    td{padding:13px 16px;border-bottom:1px solid var(--border);font-size:.86rem;color:var(--text);vertical-align:middle;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:#fafbff;}

    /* BADGES */
    .badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:700;}
    .b-paid{background:rgba(22,163,74,.1);color:#15803d;}
    .b-pending{background:rgba(217,119,6,.1);color:#b45309;}
    .b-failed{background:rgba(220,38,38,.1);color:#b91c1c;}
    .b-passed{background:rgba(22,163,74,.15);color:#15803d;}
    .b-purple{background:rgba(13,27,62,.08);color:var(--navy-mid);}
    .b-gray{background:#f3f4f6;color:#6b7280;}
    .b-green{background:rgba(22,163,74,.1);color:#15803d;}
    .b-gold{background:rgba(201,168,76,.15);color:#92400e;}

    /* BUTTONS */
    .btn-add{background:var(--navy);color:#fff;padding:9px 18px;border-radius:9px;font-size:.84rem;font-weight:700;cursor:pointer;border:none;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;gap:7px;transition:.15s;}
    .btn-add:hover{background:linear-gradient(135deg,var(--navy-mid),var(--gold));}
    .btn-ic{width:30px;height:30px;border:none;border-radius:7px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;transition:.15s;margin-right:2px;}
    .ic-edit{background:#eff6ff;color:#3b82f6;} .ic-edit:hover{background:#dbeafe;}
    .ic-tog-on{background:#fef9c3;color:#ca8a04;} .ic-tog-on:hover{background:#fef08a;}
    .ic-tog-off{background:#dcfce7;color:#16a34a;} .ic-tog-off:hover{background:#bbf7d0;}
    .ic-del{background:#fef2f2;color:var(--danger);} .ic-del:hover{background:#fee2e2;}
    .btn-status{padding:6px 12px;border:none;border-radius:7px;font-family:'DM Sans',sans-serif;font-size:.78rem;font-weight:700;cursor:pointer;transition:.15s;}
    .btn-pass{background:rgba(22,163,74,.12);color:#15803d;} .btn-pass:hover{background:rgba(22,163,74,.25);}

    /* MODAL */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;display:none;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(3px);}
    .modal-overlay.open{display:flex;}
    .mbox{background:#fff;border-radius:20px;padding:32px;max-width:560px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto;}
    .mbox h3{font-size:1.25rem;margin-bottom:18px;color:var(--navy);display:flex;align-items:center;gap:8px;}
    .fg{margin-bottom:14px;}
    .fg label{display:block;font-size:.82rem;font-weight:700;color:var(--text);margin-bottom:5px;letter-spacing:.02em;}
    .fg-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .finput{width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:9px;font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;}
    .finput:focus{border-color:var(--gold);}
    .mactions{display:flex;gap:10px;margin-top:20px;}
    .mbtn{flex:1;padding:12px;border:none;border-radius:9px;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;transition:.15s;}
    .mbtn-primary{background:var(--navy);color:#fff;} .mbtn-primary:hover{background:linear-gradient(135deg,var(--navy-mid),var(--gold));}
    .mbtn-cancel{background:var(--bg);color:var(--muted);border:1px solid var(--border);}

    /* Code tag */
    .cert-code{font-family:monospace;font-size:.82rem;color:var(--navy-mid);background:rgba(13,27,62,.07);padding:3px 8px;border-radius:5px;}
    .faq-trunc{font-size:.82rem;color:var(--muted);max-width:340px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;}

    /* Resource cards */
    .res-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:14px;margin-bottom:24px;}
    .res-card{background:#fff;border-radius:12px;border:1px solid var(--border);padding:16px;display:flex;justify-content:space-between;align-items:flex-start;gap:10px;}
    .res-card.inactive{opacity:.5;}
    .res-info h4{font-family:'DM Sans',sans-serif;font-size:.92rem;font-weight:700;color:var(--navy);margin-bottom:4px;}
    .res-info p{font-size:.78rem;color:var(--muted);}
    .res-info .price{font-weight:700;color:var(--gold);font-size:.92rem;margin-top:6px;}
    .res-info .fkey{font-size:.73rem;font-family:monospace;color:var(--muted);margin-top:3px;}
    .res-actions{display:flex;flex-direction:column;gap:5px;flex-shrink:0;}

    /* Status pill inline form */
    .status-form{display:inline;}

    /* ── MOBILE HAMBURGER ── */
    .mob-topbar{display:none;position:fixed;top:0;left:0;right:0;height:56px;background:var(--navy);z-index:200;align-items:center;justify-content:space-between;padding:0 16px;}
    .mob-topbar .brand{color:#fff;font-family:'EB Garamond',serif;font-size:1.2rem;font-weight:700;display:flex;align-items:center;gap:8px;}
    .mob-topbar .brand span{color:var(--gold-light);}
    .hamburger-btn{background:none;border:none;cursor:pointer;display:flex;flex-direction:column;gap:5px;padding:6px;}
    .hamburger-btn span{display:block;width:22px;height:2px;background:#fff;border-radius:2px;transition:.2s;}
    .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:299;}
    .sidebar-overlay.open{display:block;}

    @media(max-width:900px){
      /* Layout */
      .admin-wrap{flex-direction:column;}
      .sidebar{position:fixed;top:0;left:-260px;height:100vh;z-index:300;transition:left .25s;padding-top:56px;width:240px;}
      .sidebar.mob-open{left:0;}
      .main-content{margin-left:0!important;padding:72px 14px 24px;}
      .mob-topbar{display:flex;}

      /* Stats */
      .stat-grid{grid-template-columns:repeat(2,1fr);gap:10px;}
      .stat-card{padding:14px;}

      /* Tables — horizontal scroll */
      .tcard{overflow-x:auto;}
      table{min-width:520px;}
      th,td{padding:10px 12px;font-size:.78rem;}

      /* Page titles */
      .page-title{font-size:1.4rem;}

      /* Modals */
      .mbox{padding:22px 16px;border-radius:14px;}
      .fg-row{grid-template-columns:1fr;}

      /* Revenue card */
      .revenue-big{font-size:2rem;}

      /* Resource grid */
      .res-grid{grid-template-columns:1fr;}

      /* Buttons */
      .tcard-hd{flex-wrap:wrap;gap:8px;}
      .btn-add{font-size:.78rem;padding:8px 14px;}
    }

    @media(max-width:480px){
      .stat-grid{grid-template-columns:1fr 1fr;}
      .mob-topbar .brand{font-size:1rem;}
    }
    /* Hide modals on login page */
    body.login-page .modal-overlay { display: none !important; }
    body.login-page .mob-topbar { display: none !important; }
    body.login-page .sidebar-overlay { display: none !important; }
  </style>
</head>
<body>
<!-- Mobile topbar -->
<div class="mob-topbar">
  <div class="brand"><i class="fas fa-graduation-cap"></i> <span>Ukloole</span> Admin</div>
  <button class="hamburger-btn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
</div>
<div class="sidebar-overlay" id="sb-overlay" onclick="toggleSidebar()"></div>
<div class="admin-wrap">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-brand">
      <img src="/logo.png" alt="Ukloole">
      <div class="sb-brand-text">
        <span>Ukloole</span>
        <small>Admin Panel</small>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-section">Overview</div>
      <?php if (can_access('dashboard', $is_super, $staff_perms)): ?>
      <a href="?tab=dashboard"    class="sb-link <?= $tab==='dashboard'?'active':''?>"><i class="fas fa-chart-pie"></i> Dashboard</a>
      <?php endif; ?>
      <?php if (can_access('orders', $is_super, $staff_perms)): ?>
      <a href="?tab=orders"       class="sb-link <?= $tab==='orders'?'active':''?>"><i class="fas fa-shopping-bag"></i> Orders &amp; Payments</a>
      <?php endif; ?>
      <div class="sb-section">Content</div>
      <?php if (can_access('resources', $is_super, $staff_perms)): ?>
      <a href="?tab=resources"    class="sb-link <?= $tab==='resources'?'active':''?>"><i class="fas fa-box-open"></i> Resources</a>
      <?php endif; ?>
      <?php if (can_access('certificates', $is_super, $staff_perms)): ?>
      <a href="?tab=certificates" class="sb-link <?= $tab==='certificates'?'active':''?>"><i class="fas fa-certificate"></i> Certificates</a>
      <?php endif; ?>
      <?php if (can_access('community', $is_super, $staff_perms)): ?>
      <a href="?tab=community"    class="sb-link <?= $tab==='community'?'active':''?>"><i class="fas fa-users"></i> Community Members</a>
      <?php endif; ?>
      <?php if (can_access('faq', $is_super, $staff_perms)): ?>
      <a href="?tab=faq"          class="sb-link <?= $tab==='faq'?'active':''?>"><i class="fas fa-question-circle"></i> FAQ</a>
      <?php endif; ?>
      <?php if (can_access('testimonials', $is_super, $staff_perms)): ?>
      <a href="?tab=testimonials" class="sb-link <?= $tab==='testimonials'?'active':''?>"><i class="fas fa-star"></i> Testimonials</a>
      <?php endif; ?>
      <?php if (can_access('courses', $is_super, $staff_perms)): ?>
      <a href="?tab=courses" class="sb-link <?= $tab==='courses'?'active':''?>"><i class="fas fa-play-circle"></i> Courses</a>
      <?php endif; ?>
      <div class="sb-section">Community</div>
      <?php if (can_access('community_jobs', $is_super, $staff_perms)): ?>
      <a href="?tab=community_jobs"      class="sb-link <?= $tab==='community_jobs'?'active':''?>"><i class="fas fa-briefcase"></i> Job Links</a>
      <?php endif; ?>
      <?php if (can_access('community_webinars', $is_super, $staff_perms)): ?>
      <a href="?tab=community_webinars"  class="sb-link <?= $tab==='community_webinars'?'active':''?>"><i class="fas fa-video"></i> Webinars</a>
      <?php endif; ?>
      <?php if (can_access('community_qa', $is_super, $staff_perms)): ?>
      <a href="?tab=community_qa"        class="sb-link <?= $tab==='community_qa'?'active':''?>">
        <i class="fas fa-comments"></i> Q&amp;A
        <?php
          $unanswered = $conn->query("SELECT COUNT(*) c FROM community_threads WHERE status='open'")->fetch_assoc()['c'] ?? 0;
          if ($unanswered > 0): ?>
          <span style="margin-left:auto;background:#dc2626;color:white;border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700;"><?= $unanswered ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>
      <?php if (can_access('community_resources', $is_super, $staff_perms)): ?>
      <a href="?tab=community_resources" class="sb-link <?= $tab==='community_resources'?'active':''?>"><i class="fas fa-folder-open"></i> Member Resources</a>
      <?php endif; ?>
      <div class="sb-section">Assessment</div>
      <?php if (can_access('community_updates', $is_super, $staff_perms)): ?>
      <a href="?tab=community_updates" class="sb-link <?= $tab==='community_updates'?'active':'' ?>"><i class="fas fa-bullhorn"></i> Updates</a>
      <?php endif; ?>
      <?php if (can_access('newsletter', $is_super, $staff_perms)): ?>
      <a href="?tab=newsletter" class="sb-link <?= $tab==='newsletter'?'active':'' ?>"><i class="fas fa-envelope"></i> Newsletter</a>
      <?php endif; ?>
      <?php if ($is_super): ?>
      <a href="?tab=settings"     class="sb-link <?= $tab==='settings'?'active':''?>"><i class="fas fa-cog"></i> Settings</a>
      <?php endif; ?>
      <?php if (can_access('assessment_results', $is_super, $staff_perms)): ?>
      <a href="?tab=assessment_results" class="sb-link <?= $tab==='assessment_results'?'active':''?>">
        <i class="fas fa-clipboard-check"></i> Assessment Results
        <?php
          $r = $conn->query("SHOW TABLES LIKE 'assessment_results'");
          if ($r && $r->num_rows) {
            $new_results = $conn->query("SELECT COUNT(*) c FROM assessment_results WHERE taken_at > (NOW() - INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0;
            if ($new_results > 0) echo '<span style="margin-left:auto;background:#16a34a;color:white;border-radius:10px;padding:1px 7px;font-size:.7rem;font-weight:700;">' . (int)$new_results . '</span>';
          }
        ?>
      </a>
      <?php endif; ?>
    </nav>
    <div class="sb-footer">
      <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="main">
    <?php if ($flash): ?>
      <div class="flash"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- =========================================================
         DASHBOARD
    ========================================================== -->
    <?php if ($tab === 'dashboard'): ?>

    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">Overview of Ukloole Learning Hub activity</p>

    <!-- Revenue highlight -->
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:28px;">
      <div class="revenue-card">
        <h3>Total Revenue</h3>
        <div class="revenue-big">&#8358;<?= number_format($total_revenue) ?></div>
        <div class="sub">From <?= $total_orders ?> paid orders</div>
      </div>
      <div class="stat-grid" style="margin-bottom:0;">
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(13,27,62,.08);color:var(--navy);"><i class="fas fa-shopping-bag"></i></div>
          <div class="stat-value"><?= $total_orders ?></div>
          <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(22,163,74,.1);color:var(--success);"><i class="fas fa-users"></i></div>
          <div class="stat-value"><?= $unique_customers ?></div>
          <div class="stat-label">Unique Customers</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(201,168,76,.15);color:var(--gold);"><i class="fas fa-certificate"></i></div>
          <div class="stat-value"><?= $passed_certs ?></div>
          <div class="stat-label">Certificates Issued</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(93,91,173,.1);color:#6366f1;"><i class="fas fa-users-viewfinder"></i></div>
          <div class="stat-value"><?= $community_members ?></div>
          <div class="stat-label">Premium Members</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:rgba(236,72,153,.1);color:#db2777;"><i class="fas fa-box-open"></i></div>
          <div class="stat-value"><?= $total_resources ?></div>
          <div class="stat-label">Resources</div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="tcard">
      <div class="tcard-hd">
        <h3><i class="fas fa-clock" style="color:var(--gold);margin-right:6px;"></i>Recent Orders</h3>
        <a href="?tab=orders" style="font-size:.82rem;color:var(--gold);font-weight:600;">View all &rarr;</a>
      </div>
      <table>
        <thead><tr><th>Customer</th><th>Product</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php
          $q = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");
          while ($r = $q->fetch_assoc()):
        ?>
          <tr>
            <td><?= htmlspecialchars($r['name'] ?? '—') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['email']) ?></small></td>
            <td><span class="badge b-purple"><?= htmlspecialchars($r['product']) ?></span></td>
            <td><strong>&#8358;<?= number_format($r['amount'] ?? 0) ?></strong></td>
            <td><span class="badge <?= $r['status']==='paid'?'b-paid':'b-pending' ?>"><?= $r['status'] ?></span></td>
            <td style="color:var(--muted);font-size:.8rem"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- Certificate pipeline summary -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
      <?php
        $cp = $conn->query("SELECT status, COUNT(*) c FROM candidates GROUP BY status");
        $cpd = ['pending'=>0,'passed'=>0,'failed'=>0];
        while ($cr = $cp->fetch_assoc()) $cpd[$cr['status']] = $cr['c'];
      ?>
      <div class="tcard" style="margin-bottom:0;padding:20px;">
        <div style="font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:8px;">Pending</div>
        <div style="font-family:'EB Garamond',serif;font-size:2rem;color:var(--warn);"><?= $cpd['pending'] ?></div>
        <div style="font-size:.8rem;color:var(--muted);">Awaiting review</div>
      </div>
      <div class="tcard" style="margin-bottom:0;padding:20px;">
        <div style="font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:8px;">Passed</div>
        <div style="font-family:'EB Garamond',serif;font-size:2rem;color:var(--success);"><?= $cpd['passed'] ?></div>
        <div style="font-size:.8rem;color:var(--muted);">Certified</div>
      </div>
      <div class="tcard" style="margin-bottom:0;padding:20px;">
        <div style="font-size:.78rem;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:8px;">Failed</div>
        <div style="font-family:'EB Garamond',serif;font-size:2rem;color:var(--danger);"><?= $cpd['failed'] ?></div>
        <div style="font-size:.8rem;color:var(--muted);">Did not pass</div>
      </div>
    </div>

    <!-- =========================================================
         ORDERS
    ========================================================== -->
    <?php elseif ($tab === 'orders'): ?>

    <h1 class="page-title">Orders &amp; Payments</h1>
    <p class="page-sub">All transactions processed through Paystack</p>

    <!-- Summary row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;">
      <?php
        $paid_count  = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='paid'")->fetch_assoc()['c'];
        $rev_today   = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM orders WHERE status='paid' AND DATE(created_at)=CURDATE()")->fetch_assoc()['s'];
        $rev_week    = $conn->query("SELECT COALESCE(SUM(amount),0) s FROM orders WHERE status='paid' AND created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['s'];
        $avg_order   = $paid_count > 0 ? ($total_revenue / $paid_count) : 0;
      ?>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(22,163,74,.1);color:var(--success);"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $paid_count ?></div>
        <div class="stat-label">Paid Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(201,168,76,.15);color:var(--gold);"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-value">&#8358;<?= number_format($rev_today) ?></div>
        <div class="stat-label">Revenue Today</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99,102,241,.1);color:#6366f1;"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-value">&#8358;<?= number_format($rev_week) ?></div>
        <div class="stat-label">Revenue (7 days)</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(13,27,62,.08);color:var(--navy);"><i class="fas fa-chart-line"></i></div>
        <div class="stat-value">&#8358;<?= number_format($avg_order) ?></div>
        <div class="stat-label">Avg Order Value</div>
      </div>
    </div>

    <div class="tcard">
      <?php if (isset($_GET['resent'])): ?>
      <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 18px;margin-bottom:16px;color:#065f46;font-weight:600;">
        <i class="fas fa-check-circle"></i> Download link reset and resent to customer successfully.
      </div>
      <?php endif; ?>
      <div class="tcard-hd"><h3>All Orders (<?= $total_orders ?>)</h3></div>
      <table>
        <thead><tr><th>#</th><th>Customer</th><th>Product(s)</th><th>Reference</th><th>Amount</th><th>Status</th><th>Downloaded</th><th>Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php
          $i = 1;
          $all = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");
          while ($r = $all->fetch_assoc()):
        ?>
          <tr>
            <td style="color:var(--muted);font-size:.8rem"><?= $i++ ?></td>
            <td><?= htmlspecialchars($r['name'] ?? '—') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($r['email']) ?></small></td>
            <td><span class="badge b-purple"><?= htmlspecialchars($r['product']) ?></span></td>
            <td style="font-family:monospace;font-size:.73rem;color:var(--muted)"><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
            <td><strong>&#8358;<?= number_format($r['amount'] ?? 0) ?></strong></td>
            <td><span class="badge <?= $r['status']==='paid'?'b-paid':'b-pending' ?>"><?= $r['status'] ?></span></td>
            <td style="text-align:center"><?= intval($r['downloaded'] ?? 0) ?>×</td>
            <td style="color:var(--muted);font-size:.8rem"><?= date('M j, Y H:i', strtotime($r['created_at'])) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Reset download count and resend link to ' + <?= json_encode($r['email']) ?> + '?')" style="display:inline">
                <input type="hidden" name="action" value="resend_link">
                <input type="hidden" name="order_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn-ic" title="Resend download link" style="background:#ede9fe;color:#6d28d9;border:none;padding:6px 10px;border-radius:7px;cursor:pointer;font-size:.8rem;font-weight:600;">
                  <i class="fas fa-paper-plane"></i> Resend
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- =========================================================
         RESOURCES
    ========================================================== -->
    <?php elseif ($tab === 'resources'): ?>

    <h1 class="page-title">Resources</h1>
    <p class="page-sub">Manage paid products and free downloads shown on the Materials page.</p>

    <?php
      $res_all = $conn->query("SELECT * FROM resources ORDER BY sort_order ASC, created_at ASC");
      $res_paid = []; $res_free = []; $res_gpt = [];
      while ($r = $res_all->fetch_assoc()) {
        if ($r['type']==='paid') $res_paid[] = $r;
        elseif ($r['type']==='gpt') $res_gpt[] = $r;
        else $res_free[] = $r;
      }
    ?>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
      <button class="btn-add" onclick="openResModal()"><i class="fas fa-plus"></i> Add Resource</button>
    </div>

    <h3 style="font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:700;color:var(--navy);margin-bottom:10px;">
      <i class="fas fa-tag" style="color:var(--gold);margin-right:6px;"></i>Paid Products (<?= count($res_paid) ?>)
    </h3>
    <?php if (empty($res_paid)): ?>
      <div class="tcard" style="padding:24px;text-align:center;color:var(--muted);margin-bottom:24px;">No paid resources yet.</div>
    <?php else: ?>
    <div class="res-grid">
      <?php foreach ($res_paid as $r): ?>
      <div class="res-card <?= !$r['is_active']?'inactive':'' ?>">
        <div class="res-info">
          <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:7px;">
            <span class="badge b-gold">PAID</span>
            <?php if (!$r['is_active']): ?><span class="badge b-gray">HIDDEN</span><?php endif; ?>
          </div>
          <h4><?= htmlspecialchars($r['name']) ?></h4>
          <?php if ($r['description']): ?><p><?= htmlspecialchars($r['description']) ?></p><?php endif; ?>
          <div class="price">&#8358;<?= number_format($r['price']) ?></div>
          <?php if ($r['file_key']): ?><div class="fkey">&#128206; <?= htmlspecialchars($r['file_key']) ?></div><?php endif; ?>
          <div style="font-size:.72rem;color:var(--muted);margin-top:4px;">Sort: <?= $r['sort_order'] ?></div>
        </div>
        <div class="res-actions">
          <button class="btn-ic ic-edit" title="Edit"
            onclick="openResModal(<?= $r['id'] ?>,'<?= addslashes($r['name']) ?>','<?= addslashes($r['description']) ?>',<?= $r['price'] ?>,'<?= $r['type'] ?>','<?= addslashes($r['file_key']) ?>',<?= $r['sort_order'] ?>,<?= $r['is_active'] ?>,'<?= htmlspecialchars($r['file_type'] ?? '') ?>',<?= floatval($r['original_price'] ?? 0) ?>)">
            <i class="fas fa-pen"></i>
          </button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic <?= $r['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="<?= $r['is_active']?'Hide':'Show' ?>">
              <i class="fas fa-<?= $r['is_active']?'eye-slash':'eye' ?>"></i>
            </button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this resource?')">
            <input type="hidden" name="action" value="delete_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h3 style="font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:700;color:var(--navy);margin-bottom:10px;">
      <i class="fas fa-download" style="color:var(--success);margin-right:6px;"></i>Free Downloads (<?= count($res_free) ?>)
    </h3>
    <?php if (empty($res_free)): ?>
      <div class="tcard" style="padding:24px;text-align:center;color:var(--muted);">No free resources yet.</div>
    <?php else: ?>
    <div class="res-grid">
      <?php foreach ($res_free as $r): ?>
      <div class="res-card <?= !$r['is_active']?'inactive':'' ?>">
        <div class="res-info">
          <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:7px;">
            <span class="badge b-green">FREE</span>
            <?php if (!$r['is_active']): ?><span class="badge b-gray">HIDDEN</span><?php endif; ?>
          </div>
          <h4><?= htmlspecialchars($r['name']) ?></h4>
          <?php if ($r['description']): ?><p><?= htmlspecialchars($r['description']) ?></p><?php endif; ?>
          <?php if ($r['file_key']): ?><div class="fkey">&#128206; <?= htmlspecialchars($r['file_key']) ?></div><?php endif; ?>
        </div>
        <div class="res-actions">
          <button class="btn-ic ic-edit" title="Edit"
            onclick="openResModal(<?= $r['id'] ?>,'<?= addslashes($r['name']) ?>','<?= addslashes($r['description']) ?>',<?= $r['price'] ?>,'<?= $r['type'] ?>','<?= addslashes($r['file_key']) ?>',<?= $r['sort_order'] ?>,<?= $r['is_active'] ?>,'<?= htmlspecialchars($r['file_type'] ?? '') ?>',<?= floatval($r['original_price'] ?? 0) ?>)">
            <i class="fas fa-pen"></i>
          </button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic <?= $r['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="<?= $r['is_active']?'Hide':'Show' ?>">
              <i class="fas fa-<?= $r['is_active']?'eye-slash':'eye' ?>"></i>
            </button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this resource?')">
            <input type="hidden" name="action" value="delete_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h3 style="font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:700;color:var(--navy);margin-top:24px;margin-bottom:10px;">
      <i class="fas fa-robot" style="color:#7c3aed;margin-right:6px;"></i>GPT Tools (<?= count($res_gpt) ?>)
    </h3>
    <?php if (empty($res_gpt)): ?>
      <div class="tcard" style="padding:24px;text-align:center;color:var(--muted);margin-bottom:24px;">No GPT tools yet.</div>
    <?php else: ?>
    <div class="res-grid">
      <?php foreach ($res_gpt as $r): ?>
      <div class="res-card <?= !$r['is_active']?'inactive':'' ?>">
        <div class="res-info">
          <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:7px;">
            <span class="badge" style="background:rgba(124,58,237,.12);color:#7c3aed;">GPT</span>
            <?php if (floatval($r['price']) > 0): ?>
              <span class="badge b-paid">&#8358;<?= number_format($r['price']) ?></span>
            <?php else: ?>
              <span class="badge b-green">FREE</span>
            <?php endif; ?>
            <?php if (!$r['is_active']): ?><span class="badge b-gray">HIDDEN</span><?php endif; ?>
          </div>
          <h4><?= htmlspecialchars($r['name']) ?></h4>
          <?php if ($r['description']): ?><p><?= htmlspecialchars($r['description']) ?></p><?php endif; ?>
          <?php if ($r['file_key']): ?><div class="fkey" style="word-break:break-all;"><i class="fas fa-link" style="color:#7c3aed"></i> <a href="<?= htmlspecialchars($r['file_key']) ?>" target="_blank" rel="noopener" style="color:#7c3aed;text-decoration:none;"><?= htmlspecialchars($r['file_key']) ?></a></div><?php endif; ?>
        </div>
        <div class="res-actions">
          <button class="btn-ic ic-edit" title="Edit"
            onclick="openResModal(<?= $r['id'] ?>,'<?= addslashes($r['name']) ?>','<?= addslashes($r['description']) ?>',<?= $r['price'] ?>,'<?= $r['type'] ?>','<?= addslashes($r['file_key']) ?>',<?= $r['sort_order'] ?>,<?= $r['is_active'] ?>,'<?= htmlspecialchars($r['file_type'] ?? '') ?>',<?= floatval($r['original_price'] ?? 0) ?>)">
            <i class="fas fa-pen"></i>
          </button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic <?= $r['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="<?= $r['is_active']?'Hide':'Show' ?>">
              <i class="fas fa-<?= $r['is_active']?'eye-slash':'eye' ?>"></i>
            </button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this resource?')">
            <input type="hidden" name="action" value="delete_resource">
            <input type="hidden" name="r_id" value="<?= $r['id'] ?>">
            <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- =========================================================
         CERTIFICATES (CANDIDATES)
    ========================================================== -->
    <?php elseif ($tab === 'certificates'): ?>

    <h1 class="page-title">Certificate Management</h1>
    <p class="page-sub">Add candidates, generate certificate codes, and update assessment status.</p>

    <div class="tcard">
      <div class="tcard-hd">
        <h3><i class="fas fa-certificate" style="color:var(--gold);margin-right:6px;"></i>Candidates (<?= $total_candidates ?>)</h3>
        <button class="btn-add" onclick="document.getElementById('cand-modal').classList.add('open')"><i class="fas fa-plus"></i> Add Candidate</button>
      </div>
      <table>
        <thead><tr><th>Certificate Code</th><th>Full Name</th><th>Course</th><th>Status</th><th>Issue Date</th><th>Added</th><th>Actions</th></tr></thead>
        <tbody>
        <?php
          $cands = $conn->query("SELECT * FROM candidates ORDER BY created_at DESC");
          while ($row = $cands->fetch_assoc()):
            $status_class = ['pending'=>'b-pending','passed'=>'b-passed','failed'=>'b-failed'][$row['status']] ?? 'b-gray';
        ?>
          <tr>
            <td><span class="cert-code"><?= htmlspecialchars($row['certificate_code']) ?></span></td>
            <td><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
            <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($row['course_title']) ?></td>
            <td><span class="badge <?= $status_class ?>"><?= strtoupper($row['status']) ?></span></td>
            <td style="font-size:.8rem;color:var(--muted)"><?= $row['issue_date'] ? date('M j, Y', strtotime($row['issue_date'])) : '—' ?></td>
            <td style="font-size:.8rem;color:var(--muted)"><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
            <td style="white-space:nowrap;">
              <?php if ($row['status'] !== 'passed'): ?>
              <form method="POST" class="status-form" style="display:inline">
                <input type="hidden" name="action" value="update_candidate_status">
                <input type="hidden" name="cand_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="passed">
                <button type="submit" class="btn-status btn-pass" onclick="return confirm('Mark as PASSED and issue certificate?')">
                  <i class="fas fa-check"></i> Pass
                </button>
              </form>
              <?php endif; ?>
              <?php if ($row['status'] !== 'failed'): ?>
              <form method="POST" class="status-form" style="display:inline;margin-left:4px;">
                <input type="hidden" name="action" value="update_candidate_status">
                <input type="hidden" name="cand_id" value="<?= $row['id'] ?>">
                <input type="hidden" name="status" value="failed">
                <button type="submit" class="btn-status" style="background:rgba(220,38,38,.1);color:#dc2626;" onclick="return confirm('Mark as FAILED?')">
                  <i class="fas fa-times"></i> Fail
                </button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline;margin-left:4px;" onsubmit="return confirm('Delete this candidate?')">
                <input type="hidden" name="action" value="delete_candidate">
                <input type="hidden" name="cand_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- =========================================================
         COMMUNITY MEMBERS
    ========================================================== -->
    <?php elseif ($tab === 'community'): ?>

    <h1 class="page-title">Community Members</h1>
    <p class="page-sub">Premium members who have paid for guidance access.</p>

    <div class="tcard">
      <div class="tcard-hd"><h3><i class="fas fa-users" style="color:var(--gold);margin-right:6px;"></i>Members (<?= $community_members ?>)</h3></div>
      <table>
        <thead><tr><th>#</th><th>Name / Email</th><th>Reference</th><th>Amount</th><th>Login</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php
          $cm_all = $conn->query("SELECT * FROM community_members ORDER BY created_at DESC");
          $ci = 1;
          while ($cm = $cm_all->fetch_assoc()):
        ?>
          <tr>
            <td style="color:var(--muted)"><?= $ci++ ?></td>
            <td><?= htmlspecialchars($cm['name'] ?? '—') ?><br><small style="color:var(--muted)"><?= htmlspecialchars($cm['email']) ?></small></td>
            <td style="font-family:monospace;font-size:.73rem;color:var(--muted)"><?= htmlspecialchars($cm['reference'] ?? '—') ?></td>
            <td>&#8358;<?= number_format($cm['amount'] ?? 0) ?></td>
            <td>
              <?php if (!empty($cm['username'])): ?>
                <span class="badge b-green"><i class="fas fa-check"></i> Set</span>
              <?php else: ?>
                <span class="badge b-gray">Pending</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.8rem;color:var(--muted)"><?= date('M j, Y', strtotime($cm['created_at'])) ?></td>
            <td>
              <form method="POST" onsubmit="return confirm('Remove this member?')" style="display:inline">
                <input type="hidden" name="action" value="delete_community">
                <input type="hidden" name="cm_id" value="<?= $cm['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- =========================================================
         FAQ
    ========================================================== -->
    <?php elseif ($tab === 'faq'): ?>

    <h1 class="page-title">FAQ Management</h1>
    <p class="page-sub">Manage frequently asked questions shown on the homepage.</p>
    <div class="tcard">
      <div class="tcard-hd">
        <h3>FAQs</h3>
        <button class="btn-add" onclick="openFaqModal()"><i class="fas fa-plus"></i> Add FAQ</button>
      </div>
      <table>
        <thead><tr><th>#</th><th>Question</th><th>Answer (preview)</th><th></th></tr></thead>
        <tbody>
        <?php
          $faqs = $conn->query("SELECT * FROM faqs ORDER BY sort_order ASC, created_at ASC");
          while ($row = $faqs->fetch_assoc()):
        ?>
          <tr>
            <td style="color:var(--muted)"><?= $row['sort_order'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($row['question']) ?></td>
            <td><span style="font-size:.82rem;color:var(--muted);max-width:340px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:block"><?= htmlspecialchars($row['answer']) ?></span></td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit faq-edit-btn" title="Edit"
                data-id="<?= $row['id'] ?>"
                data-q="<?= htmlspecialchars($row['question'], ENT_QUOTES) ?>"
                data-a="<?= htmlspecialchars($row['answer'], ENT_QUOTES) ?>"
                data-sort="<?= intval($row['sort_order']) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" onsubmit="return confirm('Delete this FAQ?')" style="display:inline">
                <input type="hidden" name="action" value="delete_faq">
                <input type="hidden" name="faq_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($faqs->num_rows === 0): ?>
          <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--muted)">No FAQs yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'testimonials'): ?>

    <h1 class="page-title">Testimonials</h1>
    <p class="page-sub">Manage student testimonials shown on the homepage.</p>
    <div class="tcard">
      <div class="tcard-hd">
        <h3>Testimonials</h3>
        <button class="btn-add" onclick="document.getElementById('testi-modal').classList.add('open')"><i class="fas fa-plus"></i> Add Testimonial</button>
      </div>
      <table>
        <thead><tr><th>Name</th><th>Role</th><th>Testimonial</th><th>Rating</th><th></th></tr></thead>
        <tbody>
        <?php
          $tst = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
          while ($row = $tst->fetch_assoc()):
        ?>
          <tr>
            <td><strong><?= htmlspecialchars($row['name']) ?></strong>
              <div style="font-size:.75rem;background:var(--bg);border-radius:4px;display:inline-block;padding:1px 6px;margin-left:6px;font-weight:700;"><?= htmlspecialchars($row['avatar_initials']) ?></div>
            </td>
            <td style="color:var(--muted);font-size:.83rem"><?= htmlspecialchars($row['role']) ?></td>
            <td><span style="font-size:.82rem;color:var(--muted);max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:block"><?= htmlspecialchars($row['content']) ?></span></td>
            <td><?php for($i=1;$i<=5;$i++) echo $i<=$row['rating']?'&#11088;':'&#9734;'; ?></td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit" title="Edit"
                onclick="openTestiModal(<?= $row['id'] ?>,'<?= addslashes($row['name']) ?>','<?= addslashes($row['role']) ?>','<?= addslashes($row['content']) ?>',<?= $row['rating'] ?>,'<?= addslashes($row['avatar_initials']) ?>')">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" onsubmit="return confirm('Delete this testimonial?')" style="display:inline">
                <input type="hidden" name="action" value="delete_testimonial">
                <input type="hidden" name="t_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($tst->num_rows === 0): ?>
          <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">No testimonials yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'courses'): ?>

    <h1 class="page-title">Course Management</h1>
    <p class="page-sub">Manage modules, lessons, YouTube videos, quiz and scenario questions.</p>

    <?php
      $all_mods = $conn->query("SELECT * FROM course_modules ORDER BY sort_order ASC");
      $mods = [];
      while ($m = $all_mods->fetch_assoc()) $mods[] = $m;

      $all_lessons_q = $conn->query("
          SELECT l.*, q.question AS quiz_q, q.option_a AS qa, q.option_b AS qb, q.option_c AS qc, q.correct_option AS qcor,
                 s.question AS sc_q, s.option_a AS sa, s.option_b AS sb, s.option_c AS sc, s.correct_option AS scor
          FROM course_lessons l
          LEFT JOIN lesson_quizzes   q ON q.lesson_id = l.id
          LEFT JOIN lesson_scenarios s ON s.lesson_id = l.id
          ORDER BY l.module_id ASC, l.sort_order ASC
      ");
      $lessons_by_mod = [];
      while ($row = $all_lessons_q->fetch_assoc()) $lessons_by_mod[$row['module_id']][] = $row;
    ?>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:18px;">
      <button class="btn-add" onclick="document.getElementById('mod-modal').classList.add('open')">
        <i class="fas fa-plus"></i> Add Module
      </button>
    </div>

    <?php foreach ($mods as $mod): ?>
    <div class="tcard" style="margin-bottom:24px;">
      <div class="tcard-hd">
        <h3>
          <?php if (!$mod['is_active']): ?><span class="badge b-gray" style="margin-right:8px;">HIDDEN</span><?php endif; ?>
          Module <?= $mod['sort_order'] ?>: <?= htmlspecialchars($mod['title']) ?>
        </h3>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
          <button class="btn-ic ic-edit" title="Edit Module"
            onclick="openModModal(<?= $mod['id'] ?>,'<?= addslashes($mod['title']) ?>',<?= $mod['sort_order'] ?>)">
            <i class="fas fa-pen"></i>
          </button>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="toggle_module">
            <input type="hidden" name="mod_id" value="<?= $mod['id'] ?>">
            <button type="submit" class="btn-ic <?= $mod['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="<?= $mod['is_active']?'Hide':'Show' ?>">
              <i class="fas fa-<?= $mod['is_active']?'eye-slash':'eye' ?>"></i>
            </button>
          </form>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this module and ALL its lessons?')">
            <input type="hidden" name="action" value="delete_module">
            <input type="hidden" name="mod_id" value="<?= $mod['id'] ?>">
            <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
          </form>
          <button class="btn-add" style="padding:6px 14px;font-size:.8rem;"
            onclick="openLessonModal(0,<?= $mod['id'] ?>)">
            <i class="fas fa-plus"></i> Add Lesson
          </button>
        </div>
      </div>

      <?php $mod_lessons = $lessons_by_mod[$mod['id']] ?? []; ?>
      <?php if (empty($mod_lessons)): ?>
        <div style="padding:20px 22px;color:var(--muted);font-size:.88rem;">No lessons yet. Click "Add Lesson" to get started.</div>
      <?php else: ?>
      <table>
        <thead>
          <tr><th>#</th><th>Title</th><th>YouTube ID</th><th>Quiz</th><th>Scenario</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($mod_lessons as $ls): ?>
          <tr>
            <td style="color:var(--muted)"><?= $ls['sort_order'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($ls['title']) ?></td>
            <td style="font-family:monospace;font-size:.78rem;color:var(--muted)">
              <?php if ($ls['youtube_id'] && $ls['youtube_id'] !== 'VIDEO_ID_HERE'): ?>
                <a href="https://youtube.com/watch?v=<?= $ls['youtube_id'] ?>" target="_blank" style="color:var(--primary);">
                  <?= htmlspecialchars($ls['youtube_id']) ?> <i class="fas fa-external-link-alt" style="font-size:.7rem;"></i>
                </a>
              <?php else: ?>
                <span style="color:#dc2626;">Not set</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ls['quiz_q']): ?>
                <span class="badge b-green"><i class="fas fa-check"></i> Set</span>
              <?php else: ?>
                <span class="badge b-gray">None</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ls['sc_q']): ?>
                <span class="badge b-green"><i class="fas fa-check"></i> Set</span>
              <?php else: ?>
                <span class="badge b-gray">None</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($ls['is_active']): ?>
                <span class="badge b-paid">Active</span>
              <?php else: ?>
                <span class="badge b-gray">Hidden</span>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit lesson-edit-btn" title="Edit"
                data-id="<?= $ls['id'] ?>"
                data-mod="<?= $ls['module_id'] ?>"
                data-title="<?= htmlspecialchars($ls['title'], ENT_QUOTES) ?>"
                data-yt="<?= htmlspecialchars($ls['youtube_id'], ENT_QUOTES) ?>"
                data-sort="<?= $ls['sort_order'] ?>"
                data-qq="<?= htmlspecialchars($ls['quiz_q'] ?? '', ENT_QUOTES) ?>"
                data-qa="<?= htmlspecialchars($ls['qa'] ?? '', ENT_QUOTES) ?>"
                data-qb="<?= htmlspecialchars($ls['qb'] ?? '', ENT_QUOTES) ?>"
                data-qc="<?= htmlspecialchars($ls['qc'] ?? '', ENT_QUOTES) ?>"
                data-qcor="<?= $ls['qcor'] ?? 'a' ?>"
                data-sq="<?= htmlspecialchars($ls['sc_q'] ?? '', ENT_QUOTES) ?>"
                data-sa="<?= htmlspecialchars($ls['sa'] ?? '', ENT_QUOTES) ?>"
                data-sb="<?= htmlspecialchars($ls['sb'] ?? '', ENT_QUOTES) ?>"
                data-sc="<?= htmlspecialchars($ls['sc'] ?? '', ENT_QUOTES) ?>"
                data-scor="<?= $ls['scor'] ?? 'a' ?>">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_lesson">
                <input type="hidden" name="lesson_id" value="<?= $ls['id'] ?>">
                <button type="submit" class="btn-ic <?= $ls['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="<?= $ls['is_active']?'Hide':'Show' ?>">
                  <i class="fas fa-<?= $ls['is_active']?'eye-slash':'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this lesson?')">
                <input type="hidden" name="action" value="delete_lesson">
                <input type="hidden" name="lesson_id" value="<?= $ls['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($mods)): ?><div class="tcard" style="padding:32px;text-align:center;color:var(--muted);">No modules yet. Click "Add Module" to create your first module.</div><?php endif; ?>

    <?php elseif ($tab === 'community_jobs'): ?>

    <h1 class="page-title">Job Links</h1>
    <p class="page-sub">Post direct job links for premium members. Links are obfuscated — members cannot see the real URL.</p>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
      <button class="btn-add" onclick="document.getElementById('jl-modal').classList.add('open')">
        <i class="fas fa-plus"></i> Add Job Link
      </button>
    </div>

    <div class="tcard">
      <table>
        <thead><tr><th>#</th><th>Title</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php
          $jls = $conn->query("SELECT * FROM community_job_links ORDER BY sort_order ASC, created_at DESC");
          $ji = 1;
          while ($jl = $jls->fetch_assoc()):
        ?>
          <tr>
            <td style="color:var(--muted)"><?= $ji++ ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($jl['title']) ?></td>
            <td style="font-size:.82rem;color:var(--muted)"><?= $jl['expires_at'] ? date('M j, Y', strtotime($jl['expires_at'])) : 'Never' ?></td>
            <td>
              <?php
                $expired = $jl['expires_at'] && strtotime($jl['expires_at']) < strtotime('today');
                if (!$jl['is_active']): ?><span class="badge b-gray">Hidden</span>
                <?php elseif ($expired): ?><span class="badge b-failed">Expired</span>
                <?php else: ?><span class="badge b-paid">Active</span><?php endif; ?>
            </td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit" title="Edit"
                onclick="openJlModal(<?= $jl['id'] ?>,'<?= addslashes($jl['title']) ?>','<?= addslashes($jl['url']) ?>','<?= $jl['expires_at'] ?? '' ?>',<?= $jl['sort_order'] ?>)">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_job_link">
                <input type="hidden" name="jl_id" value="<?= $jl['id'] ?>">
                <button type="submit" class="btn-ic <?= $jl['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="Toggle">
                  <i class="fas fa-<?= $jl['is_active']?'eye-slash':'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this job link?')">
                <input type="hidden" name="action" value="delete_job_link">
                <input type="hidden" name="jl_id" value="<?= $jl['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'community_webinars'): ?>

    <h1 class="page-title">Webinars &amp; Live Sessions</h1>
    <p class="page-sub">Post Zoom, Google Meet, or any meeting links for premium members.</p>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
      <button class="btn-add" onclick="document.getElementById('wb-modal').classList.add('open')">
        <i class="fas fa-plus"></i> Add Webinar / Session
      </button>
    </div>

    <div class="tcard">
      <table>
        <thead><tr><th>Title</th><th>Date &amp; Time</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php
          $wbs = $conn->query("SELECT * FROM community_webinars ORDER BY event_date ASC, created_at DESC");
          while ($wb = $wbs->fetch_assoc()):
        ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($wb['title']) ?></strong>
              <?php if ($wb['description']): ?>
                <br><small style="color:var(--muted)"><?= htmlspecialchars($wb['description']) ?></small>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;color:var(--muted)">
              <?= $wb['event_date'] ? date('M j Y, g:ia', strtotime($wb['event_date'])) : '—' ?>
            </td>
            <td><?= $wb['is_active'] ? '<span class="badge b-paid">Active</span>' : '<span class="badge b-gray">Hidden</span>' ?></td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit" title="Edit"
                onclick="openWbModal(<?= $wb['id'] ?>,'<?= addslashes($wb['title']) ?>','<?= addslashes($wb['description'] ?? '') ?>','<?= addslashes($wb['url']) ?>','<?= $wb['event_date'] ? date('Y-m-d\TH:i', strtotime($wb['event_date'])) : '' ?>')">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_webinar">
                <input type="hidden" name="wb_id" value="<?= $wb['id'] ?>">
                <button type="submit" class="btn-ic <?= $wb['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="Toggle">
                  <i class="fas fa-<?= $wb['is_active']?'eye-slash':'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this session?')">
                <input type="hidden" name="action" value="delete_webinar">
                <input type="hidden" name="wb_id" value="<?= $wb['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'community_qa'): ?>

    <h1 class="page-title">Q&amp;A — Member Questions</h1>
    <p class="page-sub">Reply to questions submitted by premium members on their dashboard.</p>

    <div class="tcard">
        <table>
          <thead><tr><th>Member</th><th>Subject</th><th>Conversation</th><th>Status</th><th>Updated</th><th></th></tr></thead>
          <tbody>
          <?php
            // Defensive Q&A renderer
            @set_error_handler(function(){return true;});
            $qa_count = 0;
            $threads_result = $conn->query("SELECT t.id, t.subject, t.status, t.member_id, t.updated_at, m.name AS member_name, m.email AS member_email FROM community_threads t LEFT JOIN community_members m ON m.id = t.member_id ORDER BY t.updated_at DESC, t.id DESC");
            $all_threads = [];
            if ($threads_result) {
              while ($row = $threads_result->fetch_assoc()) { $all_threads[] = $row; }
            }
            $mb_substr_fn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
            $mb_strlen_fn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
            foreach ($all_threads as $q):
              $qa_count++;
              $tid          = isset($q['id']) ? intval($q['id']) : 0;
              $member_name  = isset($q['member_name']) && $q['member_name'] !== null ? (string)$q['member_name'] : 'Unknown';
              $member_email = isset($q['member_email']) && $q['member_email'] !== null ? (string)$q['member_email'] : '';
              $subject_txt  = isset($q['subject']) && $q['subject'] !== null ? (string)$q['subject'] : '';
              $status_txt   = isset($q['status']) && $q['status'] !== null ? (string)$q['status'] : 'open';
              $first_body = '';
              $reply_body = '';
              $msg_count  = 0;
              $all_msgs   = [];
              if ($tid > 0) {
                $mres = @$conn->query("SELECT sender, body, created_at FROM community_thread_messages WHERE thread_id=$tid ORDER BY created_at ASC, id ASC");
                if ($mres) {
                  while ($mrow = $mres->fetch_assoc()) {
                    $all_msgs[] = [
                      'sender' => isset($mrow['sender']) ? (string)$mrow['sender'] : 'member',
                      'body'   => isset($mrow['body']) ? (string)$mrow['body'] : '',
                      'at'     => isset($mrow['created_at']) ? (string)$mrow['created_at'] : ''
                    ];
                  }
                }
                $msg_count = count($all_msgs);
                foreach ($all_msgs as $m) { if ($m['sender'] === 'member' && $first_body === '') { $first_body = $m['body']; break; } }
                for ($i = count($all_msgs) - 1; $i >= 0; $i--) { if ($all_msgs[$i]['sender'] === 'admin') { $reply_body = $all_msgs[$i]['body']; break; } }
              }
              $upd_raw = isset($q['updated_at']) && $q['updated_at'] !== null ? (string)$q['updated_at'] : '';
              $upd_ts  = $upd_raw !== '' ? @strtotime($upd_raw) : false;
              $upd_date = $upd_ts ? date('M j, Y', $upd_ts) : '—';
              $upd_time = $upd_ts ? strtolower(date('g:ia', $upd_ts)) : '';
              $first_short = $mb_substr_fn($first_body, 0, 100);
              $first_more  = $mb_strlen_fn($first_body) > 100 ? '...' : '';
              $reply_short = $mb_substr_fn($reply_body, 0, 80);
              $reply_more  = $mb_strlen_fn($reply_body) > 80 ? '...' : '';
              if ($status_txt === 'answered') { $badge_class = 'b-paid';   $badge_text = 'Answered'; }
              elseif ($status_txt === 'closed') { $badge_class = 'b-gray'; $badge_text = 'Closed'; }
              else { $badge_class = 'b-failed'; $badge_text = 'Open'; }
              $thread_payload = [
                'id'      => $tid,
                'subject' => $subject_txt,
                'member'  => $member_name,
                'email'   => $member_email,
                'status'  => $status_txt,
                'last_reply' => $reply_body,
                'messages'   => $all_msgs
              ];
              $payload_json = htmlspecialchars(json_encode($thread_payload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE), ENT_QUOTES);
          ?>
            <tr style="cursor:pointer" onclick="openQaThread(this)" data-thread="<?= $payload_json ?>">
              <td>
                <strong><?= htmlspecialchars($member_name) ?></strong>
                <br><small style="color:var(--muted)"><?= htmlspecialchars($member_email) ?></small>
              </td>
              <td style="font-weight:600;max-width:180px"><?= htmlspecialchars($subject_txt) ?></td>
              <td style="max-width:280px">
                <div style="font-size:.85rem;color:#0D1B3E;line-height:1.4"><?= $first_body !== '' ? htmlspecialchars($first_short) . $first_more : '<span style="color:#9ca3af">(no message)</span>' ?></div>
                <?php if ($reply_body !== ''): ?>
                  <div style="margin-top:6px;padding:6px 10px;background:rgba(201,168,76,.08);border-left:3px solid #C9A84C;border-radius:0 6px 6px 0;font-size:.78rem;color:#6B7A99;">
                    <strong style="color:#C9A84C;">Your last reply:</strong> <?= htmlspecialchars($reply_short) . $reply_more ?>
                  </div>
                <?php endif; ?>
                <div style="margin-top:6px;font-size:.75rem;color:#6B7A99;display:flex;align-items:center;gap:5px;">
                  <i class="fas fa-comment-dots" style="color:#C9A84C;"></i>
                  <?= $msg_count ?> message<?= $msg_count === 1 ? '' : 's' ?> · <span style="color:#C9A84C;font-weight:600">click to read</span>
                </div>
              </td>
              <td>
                <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
              </td>
              <td style="font-size:.78rem;color:#6B7A99;white-space:nowrap;line-height:1.5">
                <?= htmlspecialchars($upd_date) ?>
                <?php if ($upd_time): ?><br><span><?= htmlspecialchars($upd_time) ?></span><?php endif; ?>
              </td>
              <td style="white-space:nowrap" onclick="event.stopPropagation()">
                <button class="btn-ic ic-edit" title="Reply" type="button" onclick="event.stopPropagation();openQaThread(this.closest('tr'))">
                  <i class="fas fa-reply"></i>
                </button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this thread?')">
                  <input type="hidden" name="action" value="delete_question">
                  <input type="hidden" name="q_id" value="<?= $tid ?>">
                  <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; @restore_error_handler(); ?>
          <?php if ($qa_count === 0): ?>
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--muted)">No questions yet.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php elseif ($tab === 'community_resources'): ?>

    <h1 class="page-title">Member Resources</h1>
    <p class="page-sub">Post exclusive resources for premium community members. These are separate from the shop.</p>

    <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
      <button class="btn-add" onclick="document.getElementById('cr-modal').classList.add('open')">
        <i class="fas fa-plus"></i> Add Resource
      </button>
    </div>

    <div class="tcard">
      <table>
        <thead><tr><th>#</th><th>Title</th><th>Description</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php
          $crs = $conn->query("SELECT * FROM community_resources ORDER BY sort_order ASC, created_at DESC");
          $cri = 1;
          while ($cr = $crs->fetch_assoc()):
        ?>
          <tr>
            <td style="color:var(--muted)"><?= $cri++ ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($cr['title']) ?></td>
            <td style="font-size:.82rem;color:var(--muted)"><?= htmlspecialchars($cr['description'] ?? '—') ?></td>
            <td><?= $cr['is_active'] ? '<span class="badge b-paid">Active</span>' : '<span class="badge b-gray">Hidden</span>' ?></td>
            <td style="white-space:nowrap">
              <button class="btn-ic ic-edit" title="Edit"
                onclick="openCrModal(<?= $cr['id'] ?>,'<?= addslashes($cr['title']) ?>','<?= addslashes($cr['description'] ?? '') ?>','<?= addslashes($cr['url']) ?>',<?= $cr['sort_order'] ?>)">
                <i class="fas fa-pen"></i>
              </button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_comm_resource">
                <input type="hidden" name="cr_id" value="<?= $cr['id'] ?>">
                <button type="submit" class="btn-ic <?= $cr['is_active']?'ic-tog-on':'ic-tog-off' ?>" title="Toggle">
                  <i class="fas fa-<?= $cr['is_active']?'eye-slash':'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this resource?')">
                <input type="hidden" name="action" value="delete_comm_resource">
                <input type="hidden" name="cr_id" value="<?= $cr['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <?php
    ?>
    <?php elseif ($tab === 'community_updates'): ?>

    <h1 class="page-title">Updates &amp; Opportunities</h1>
    <p class="page-sub">Post updates, opportunities and announcements to the community dashboard.</p>
    <div class="tcard">
      <div class="tcard-hd">
        <h3>Updates</h3>
        <button class="btn-add" onclick="document.getElementById('upd-modal').classList.add('open')"><i class="fas fa-plus"></i> Add Update</button>
      </div>
      <table>
        <thead><tr><th>Type</th><th>Title</th><th>Body</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php
          $upds = $conn->query("SELECT * FROM community_updates ORDER BY created_at DESC");
          while ($row = $upds->fetch_assoc()):
        ?>
          <tr>
            <td><span style="font-size:.75rem;font-weight:700;text-transform:uppercase;padding:2px 8px;border-radius:20px;background:var(--navy);color:#fff"><?= htmlspecialchars($row['type']) ?></span></td>
            <td style="font-weight:600"><?= htmlspecialchars($row['title']) ?></td>
            <td><span style="font-size:.82rem;color:var(--muted);max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;display:block"><?= htmlspecialchars($row['body']) ?></span></td>
            <td>
              <form method="POST" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_update">
                <input type="hidden" name="upd_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-ic" style="background:<?= $row['is_active']?'var(--green,#22c55e)':'var(--muted)' ?>;color:#fff" title="<?= $row['is_active']?'Click to hide':'Click to show' ?>">
                  <i class="fas fa-<?= $row['is_active']?'eye':'eye-slash' ?>"></i>
                </button>
              </form>
            </td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete this update?')" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_update">
                <input type="hidden" name="upd_id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($upds->num_rows === 0): ?>
          <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--muted)">No updates yet. Click "Add Update" to post one.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'newsletter'): ?>

    <h1 class="page-title">Newsletter Subscribers</h1>
    <p class="page-sub">People who submitted their email to get free downloads. You can export or manage them here.</p>

    <?php
      // Auto-create table if not exists
      $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(200) NOT NULL UNIQUE,
        name VARCHAR(150) DEFAULT '',
        source VARCHAR(50) DEFAULT 'download',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email)
      ) ENGINE=InnoDB");

      // Import from emails.txt if it exists
      $emails_file = __DIR__ . '/emails.txt';
      if (file_exists($emails_file)) {
          $lines = file($emails_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
          foreach ($lines as $line) {
              $parts = explode(',', $line, 2);
              $e = filter_var(trim($parts[0]), FILTER_VALIDATE_EMAIL);
              if ($e) {
                  $stmt = $conn->prepare("INSERT IGNORE INTO newsletter_subscribers (email, source) VALUES (?, 'download')");
                  $stmt->bind_param("s", $e);
                  $stmt->execute(); $stmt->close();
              }
          }
      }

      $subs = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC");
      $sub_count = $subs->num_rows ?? 0;
    ?>

    <div class="tcard">
      <div class="tcard-hd">
        <h3>Subscribers <span style="font-size:.8rem;color:var(--muted);font-weight:400">(<?= $sub_count ?> total)</span></h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <a href="?tab=newsletter&export=1" class="btn-add" style="background:var(--green,#22c55e)"><i class="fas fa-download"></i> Export CSV</a>
          <button class="btn-add" onclick="document.getElementById('sub-modal').classList.add('open')"><i class="fas fa-plus"></i> Add Subscriber</button>
        </div>
      </div>

      <?php
        // Handle CSV export
        if (isset($_GET['export']) && $_GET['export'] == 1) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');
            echo "Name,Email,Source,Date
";
            $conn->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
            $all = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY created_at DESC");
            while ($s = $all->fetch_assoc()) {
                echo '"' . $s['name'] . '","' . $s['email'] . '","' . $s['source'] . '","' . $s['created_at'] . '"' . "
";
            }
            exit;
        }
      ?>

      <table>
        <thead><tr><th>Email</th><th>Name</th><th>Source</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php
          $subs->data_seek(0);
          while ($s = $subs->fetch_assoc()):
        ?>
          <tr>
            <td><?= htmlspecialchars($s['email']) ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($s['name'] ?: '—') ?></td>
            <td><span style="font-size:.75rem;padding:2px 8px;border-radius:20px;background:var(--bg);font-weight:600"><?= htmlspecialchars($s['source']) ?></span></td>
            <td style="font-size:.78rem;color:var(--muted)"><?= date('M j, Y', strtotime($s['created_at'])) ?></td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirm('Remove subscriber?')">
                <input type="hidden" name="action" value="delete_subscriber">
                <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn-ic ic-del"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($sub_count === 0): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--muted)">No subscribers yet. They appear here when someone downloads a free resource.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php elseif ($tab === 'assessment_results'): ?>

    <h1 class="page-title">Assessment Results</h1>
    <p class="page-sub">Auto-graded submissions from the Customer Service Mastery certification assessment (40 questions, 80% pass mark).</p>

    <?php
      // Make sure the table exists; if not, show a friendly note
      $has_ar = $conn->query("SHOW TABLES LIKE 'assessment_results'");
      if (!$has_ar || $has_ar->num_rows === 0):
    ?>
      <div class="tcard" style="padding:28px;">
        <h3 style="margin-bottom:8px;color:var(--danger);"><i class="fas fa-triangle-exclamation"></i> Table not found</h3>
        <p style="color:var(--muted);">Run <code>assessment_schema.sql</code> in phpMyAdmin to enable assessment tracking.</p>
      </div>
    <?php else:
      $a_total  = $conn->query("SELECT COUNT(*) c FROM assessment_results")->fetch_assoc()['c'] ?? 0;
      $a_passed = $conn->query("SELECT COUNT(*) c FROM assessment_results WHERE pass=1")->fetch_assoc()['c'] ?? 0;
      $a_avg    = $conn->query("SELECT COALESCE(AVG(percentage),0) a FROM assessment_results")->fetch_assoc()['a'] ?? 0;
      $a_pass_rate = $a_total > 0 ? round(($a_passed / $a_total) * 100, 1) : 0;
    ?>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(13,27,62,.08);color:var(--navy);"><i class="fas fa-file-pen"></i></div>
        <div class="stat-value"><?= (int)$a_total ?></div>
        <div class="stat-label">Total Attempts</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(22,163,74,.1);color:var(--success);"><i class="fas fa-circle-check"></i></div>
        <div class="stat-value"><?= (int)$a_passed ?></div>
        <div class="stat-label">Passed</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(201,168,76,.15);color:var(--gold);"><i class="fas fa-percent"></i></div>
        <div class="stat-value"><?= $a_pass_rate ?>%</div>
        <div class="stat-label">Pass Rate</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:rgba(99,102,241,.1);color:#6366f1;"><i class="fas fa-chart-line"></i></div>
        <div class="stat-value"><?= round((float)$a_avg, 1) ?>%</div>
        <div class="stat-label">Avg Score</div>
      </div>
    </div>

    <div class="tcard">
      <div class="tcard-hd"><h3>Latest Submissions</h3></div>
      <table>
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Score</th><th>Result</th><th>Cert Code</th><th>IP</th><th>Taken</th></tr>
        </thead>
        <tbody>
          <?php
            $rs = $conn->query("SELECT * FROM assessment_results ORDER BY taken_at DESC LIMIT 200");
            $n = 1;
            while ($row = $rs->fetch_assoc()):
          ?>
            <tr>
              <td style="color:var(--muted)"><?= $n++ ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($row['full_name']) ?></td>
              <td style="font-size:.85rem;color:var(--muted)"><?= htmlspecialchars($row['email']) ?></td>
              <td>
                <strong><?= (int)$row['score'] ?>/<?= (int)$row['total'] ?></strong>
                <span style="color:var(--muted);font-size:.78rem;">(<?= rtrim(rtrim(number_format((float)$row['percentage'], 2),'0'),'.') ?>%)</span>
              </td>
              <td>
                <?php if ($row['pass']): ?>
                  <span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700;">PASSED</span>
                <?php else: ?>
                  <span style="background:#fee2e2;color:#b91c1c;padding:3px 10px;border-radius:50px;font-size:.72rem;font-weight:700;">FAILED</span>
                <?php endif; ?>
              </td>
              <td style="font-family:monospace;font-size:.82rem;">
                <?= $row['certificate_code'] ? htmlspecialchars($row['certificate_code']) : '<span style="color:var(--muted)">—</span>' ?>
              </td>
              <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($row['ip_address'] ?? '') ?></td>
              <td style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($row['taken_at']) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php if ($a_total === 0): ?>
        <div style="padding:24px;text-align:center;color:var(--muted);">No assessment submissions yet.</div>
      <?php endif; ?>
    </div>
    <?php endif; // end !$has_ar / else ?>

      <?php elseif ($tab === 'settings'): ?>

      <h1 class="page-title"><i class="fas fa-cog" style="color:var(--gold)"></i> Settings</h1>
      <p class="page-sub">Set the pricing tiers for Premium Community access. Leave a tier blank (or 0) to hide it from the public page. Only tiers with a price will appear.</p>

      <?php if (!empty($_GET['saved'])): ?>
        <div class="tcard" style="padding:14px 18px;margin-bottom:18px;background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.35);color:#15803d;">
          <i class="fas fa-check-circle"></i> Settings saved successfully.
        </div>
      <?php endif; ?>

      <?php
        $tier_defs = [
          'monthly'   => ['label' => 'Monthly',    'icon' => 'fa-calendar-day'],
          'quarterly' => ['label' => 'Quarterly',   'icon' => 'fa-calendar-alt'],
          'bi_annual' => ['label' => 'Bi-Annual',   'icon' => 'fa-calendar-week'],
          'yearly'    => ['label' => 'Yearly',      'icon' => 'fa-calendar'],
        ];
        $tier_prices = [];
        foreach ($tier_defs as $key => $def) {
          $tier_prices[$key] = admin_get_setting($conn, 'price_' . $key, '');
        }
        // Count active tiers for live preview
        $active_tiers = array_filter($tier_prices, fn($v) => $v !== '' && intval($v) > 0);
      ?>

      <div class="tcard" style="padding:28px;max-width:700px;">
        <h3 style="font-family:'DM Sans',sans-serif;font-size:1.1rem;color:var(--navy);margin-bottom:4px;">
          <i class="fas fa-crown" style="color:var(--gold)"></i> Premium Community Pricing Tiers
        </h3>
        <p style="color:var(--muted);font-size:.88rem;margin-bottom:22px;">
          Enter a Naira amount for each plan you want to offer. Leave blank or set to 0 to hide that plan entirely.
        </p>

        <form method="POST">
          <input type="hidden" name="action" value="save_settings">

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px;">
          <?php foreach ($tier_defs as $key => $def): ?>
            <div style="background:rgba(13,27,62,.03);border:1px solid var(--border);border-radius:12px;padding:16px;">
              <label style="display:flex;align-items:center;gap:7px;font-weight:700;color:var(--navy);margin-bottom:10px;font-size:.93rem;">
                <i class="fas <?= $def['icon'] ?>" style="color:var(--gold);width:16px;text-align:center;"></i>
                <?= $def['label'] ?>
                <span style="font-size:.75rem;font-weight:400;color:var(--muted);margin-left:auto;">optional</span>
              </label>
              <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-size:1.1rem;color:var(--gold);font-weight:700;">&#8358;</span>
                <input type="number" name="s_price_<?= $key ?>" class="finput" min="0" step="100" placeholder="Leave blank to hide"
                  value="<?= htmlspecialchars($tier_prices[$key]) ?>" style="flex:1;padding:9px 12px;">
              </div>
            </div>
          <?php endforeach; ?>
          </div>

          <!-- Live preview -->
          <div style="background:rgba(13,27,62,.04);border-radius:10px;padding:16px;margin-bottom:20px;">
            <p style="font-size:.82rem;color:var(--muted);margin-bottom:10px;font-weight:600;letter-spacing:.03em;text-transform:uppercase;">Active Tiers Preview</p>
            <?php if (empty($active_tiers)): ?>
              <p style="color:#dc2626;font-size:.88rem;"><i class="fas fa-exclamation-triangle"></i> No tiers set — community pricing card will be hidden.</p>
            <?php else: ?>
              <div style="display:flex;gap:10px;flex-wrap:wrap;">
              <?php foreach ($tier_defs as $key => $def):
                if (empty($tier_prices[$key]) || intval($tier_prices[$key]) <= 0) continue; ?>
                <div style="background:white;border:2px solid var(--gold);border-radius:10px;padding:10px 16px;text-align:center;min-width:110px;">
                  <div style="font-size:1.25rem;font-weight:800;color:var(--navy);">&#8358;<?= number_format(intval($tier_prices[$key])) ?></div>
                  <div style="font-size:.78rem;color:var(--muted);margin-top:2px;"><?= $def['label'] ?></div>
                </div>
              <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <button type="submit" style="background:var(--gold);color:#fff;border:none;padding:12px 28px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.95rem;">
            <i class="fas fa-save"></i> Save Settings
          </button>
        </form>

        <!-- ---- Staff Permissions ---- -->
        <div style="margin-top:40px;border-top:1px solid var(--border);padding-top:32px;">
          <h3 style="font-family:'EB Garamond',serif;font-size:1.3rem;color:var(--navy);margin-bottom:6px;">
            <i class="fas fa-user-shield" style="color:var(--gold);margin-right:8px;"></i>Staff Admin Permissions
          </h3>
          <p style="color:var(--muted);font-size:.88rem;margin-bottom:20px;">
            Control which sections your staff admin can access. Settings is always super-admin only.
            Staff password: set <code>STAFF_PASSWORD</code> env variable or it defaults to <strong>StaffUkloole2024</strong> — change it below.
          </p>
          <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            <!-- Carry over pricing values so they don't get wiped -->
            <?php foreach ($tier_defs as $key => $def): ?>
            <input type="hidden" name="s_price_<?= $key ?>" value="<?= htmlspecialchars($tier_prices[$key]) ?>">
            <?php endforeach; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:24px;">
              <?php foreach ($all_tabs as $tab_key => $tab_label):
                if ($tab_key === 'settings') continue; ?>
              <label style="display:flex;align-items:center;gap:10px;background:<?= in_array($tab_key, $staff_perms) ? 'rgba(16,163,74,.08)' : 'rgba(13,27,62,.03)' ?>;
                border:1px solid <?= in_array($tab_key, $staff_perms) ? '#16a34a' : 'var(--border)' ?>;
                border-radius:10px;padding:12px 14px;cursor:pointer;transition:all .2s;">
                <input type="checkbox" name="staff_perm_<?= $tab_key ?>" value="1"
                  <?= in_array($tab_key, $staff_perms) ? 'checked' : '' ?>
                  style="width:16px;height:16px;accent-color:var(--gold);cursor:pointer;">
                <span style="font-size:.88rem;font-weight:600;color:var(--navy);"><?= htmlspecialchars($tab_label) ?></span>
              </label>
              <?php endforeach; ?>
            </div>

            <div style="margin-bottom:20px;max-width:340px;">
              <label style="display:block;font-weight:600;color:var(--navy);margin-bottom:6px;font-size:.88rem;">
                <i class="fas fa-key" style="color:var(--gold);margin-right:6px;"></i>Change Staff Password
              </label>
              <input type="password" name="new_staff_password" placeholder="Leave blank to keep current"
                style="width:100%;padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:.9rem;">
              <p style="font-size:.78rem;color:var(--muted);margin-top:5px;">Leave blank to keep the existing staff password.</p>
            </div>

            <button type="submit" style="background:var(--navy);color:#fff;border:none;padding:12px 28px;border-radius:8px;font-weight:700;cursor:pointer;font-size:.95rem;">
              <i class="fas fa-save"></i> Save Staff Permissions
            </button>
          </form>
        </div>
      </div>

    </main>
</div>
<?php endif; // end tab if/elseif chain ?>

<?php if (isset($_SESSION['admin'])): ?>
<!-- =============================================================
     MODALS
============================================================== -->

<!-- Add Candidate -->
<div class="modal-overlay" id="cand-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3><i class="fas fa-user-plus" style="color:var(--gold)"></i> Add Candidate</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_candidate">
      <div class="fg"><label>Full Name *</label><input type="text" name="full_name" class="finput" required placeholder="e.g. Adaeze Okafor"></div>
      <div class="fg"><label>Course Title</label><input type="text" name="course_title" class="finput" value="Customer Service Mastery"></div>
      <div class="fg">
        <label>Initial Status</label>
        <select name="status" class="finput">
          <option value="pending">Pending (not yet issued)</option>
          <option value="passed">Passed (issue certificate now)</option>
          <option value="failed">Failed</option>
        </select>
      </div>
      <p style="font-size:.8rem;color:var(--muted);margin-top:-8px;">A unique certificate code (UKL-YEAR-XXXXXX) will be auto-generated.</p>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary"><i class="fas fa-plus"></i> Add Candidate</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('cand-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add / Edit Resource -->
<div class="modal-overlay" id="res-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="res-modal-title"><i class="fas fa-box-open" style="color:var(--gold)"></i> Add Resource</h3>
    <form method="POST">
      <input type="hidden" name="action" id="res-action" value="add_resource">
      <input type="hidden" name="r_id" id="res-id" value="">
      <input type="hidden" name="r_active" id="res-active" value="1">
      <div class="fg"><label>Name *</label><input type="text" name="r_name" id="res-name" class="finput" placeholder="e.g. Cover Letter Pack" required></div>
      <div class="fg"><label>Description</label><textarea name="r_desc" id="res-desc" class="finput" rows="2" placeholder="Short description..."></textarea></div>
      <div class="fg-row">
        <div class="fg">
          <label>Type *</label>
          <select name="r_type" id="res-type" class="finput" onchange="resTypeChanged(this.value)">
            <option value="paid">Paid (Download)</option>
            <option value="free">Free (Download)</option>
            <option value="gpt">GPT Tool</option>
          </select>
        </div>
        <div class="fg" id="price-wrap">
          <label>Price (&#8358;)</label>
          <input type="number" name="r_price" id="res-price" class="finput" min="0" step="1" placeholder="0">
        </div>
      </div>
      <div class="fg"><label id="res-fkey-label">File Key / Filename</label><input type="text" name="r_filekey" id="res-fkey" class="finput" placeholder="e.g. cover-letter-pack.pdf"></div>
      <div class="fg"><label>File Type</label><select name="r_file_type" id="res-file-type" class="finput"><option value="">-- None --</option><option value="pdf">PDF</option><option value="doc">DOC</option><option value="docx">DOCX</option><option value="xls">XLS</option><option value="xlsx">XLSX</option><option value="ppt">PPT</option><option value="pptx">PPTX</option><option value="mp4">MP4</option><option value="zip">ZIP</option></select></div>
      <div class="fg" id="res-orig-price-wrap"><label>Original Price (&#8358;) — for showing slashed price</label><input type="number" name="r_orig_price" id="res-orig-price" class="finput" min="0" step="1" placeholder="0" value="0"></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="r_sort" id="res-sort" class="finput" value="0" min="0"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="res-submit-btn">Add Resource</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('res-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add / Edit FAQ -->
<div class="modal-overlay" id="faq-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="faq-modal-title"><i class="fas fa-question-circle" style="color:var(--gold)"></i> Add FAQ</h3>
    <form method="POST">
      <input type="hidden" name="action" id="faq-action" value="add_faq">
      <input type="hidden" name="faq_id" id="faq-id" value="">
      <div class="fg"><label>Question *</label><input type="text" name="question" id="faq-q" class="finput" required></div>
      <div class="fg"><label>Answer *</label><textarea name="answer" id="faq-a" class="finput" rows="4" required></textarea></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="sort_order" id="faq-sort" class="finput" value="0" min="0"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary">Save FAQ</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('faq-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Testimonial -->
<div class="modal-overlay" id="testi-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3><i class="fas fa-star" style="color:#f59e0b"></i> Add Testimonial</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_testimonial">
      <div class="fg-row">
        <div class="fg"><label>Full Name *</label><input type="text" name="t_name" class="finput" required></div>
        <div class="fg"><label>Initials (2 letters) *</label><input type="text" name="t_initials" class="finput" maxlength="2" style="text-transform:uppercase" required></div>
      </div>
      <div class="fg"><label>Role / Title</label><input type="text" name="t_role" class="finput" placeholder="e.g. Customer Service Rep, Lagos"></div>
      <div class="fg"><label>Testimonial *</label><textarea name="t_content" class="finput" rows="3" required></textarea></div>
      <div class="fg">
        <label>Rating</label>
        <select name="t_rating" class="finput">
          <option value="5">&#11088;&#11088;&#11088;&#11088;&#11088; (5)</option>
          <option value="4">&#11088;&#11088;&#11088;&#11088; (4)</option>
          <option value="3">&#11088;&#11088;&#11088; (3)</option>
          <option value="2">&#11088;&#11088; (2)</option>
          <option value="1">&#11088; (1)</option>
        </select>
      </div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary">Add Testimonial</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('testi-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openResModal(id,name,desc,price,type,fkey,sort,active,file_type,orig_price) {
  const m = document.getElementById('res-modal');
  const isEdit = !!id;
  document.getElementById('res-modal-title').innerHTML = '<i class="fas fa-box-open" style="color:var(--gold)"></i> ' + (isEdit?'Edit':'Add') + ' Resource';
  document.getElementById('res-action').value = isEdit ? 'edit_resource' : 'add_resource';
  document.getElementById('res-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Resource';
  document.getElementById('res-id').value      = id    || '';
  document.getElementById('res-name').value    = name  || '';
  document.getElementById('res-desc').value    = desc  || '';
  document.getElementById('res-price').value   = price || '';
  document.getElementById('res-type').value    = type  || 'paid';
  document.getElementById('res-fkey').value    = fkey  || '';
  document.getElementById('res-sort').value    = sort  || 0;
  document.getElementById('res-active').value  = (active !== undefined) ? active : 1;
  document.getElementById('res-file-type').value  = file_type  || '';
  document.getElementById('res-orig-price').value = orig_price || 0;
  resTypeChanged(type || 'paid');
  m.classList.add('open');
}

function resTypeChanged(t) {
  document.getElementById('price-wrap').style.display = (t === 'free') ? 'none' : 'block';
  const label = document.getElementById('res-fkey-label');
  const input = document.getElementById('res-fkey');
  if (t === 'gpt') {
    label.textContent = 'GPT Link / URL *';
    input.placeholder = 'https://chatgpt.com/g/...';
  } else {
    label.textContent = 'File Key / Filename';
    input.placeholder = 'e.g. cover-letter-pack.pdf';
  }
}

// Delegate click on FAQ edit buttons - data attributes keep special chars safe
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.faq-edit-btn');
  if (!btn) return;
  openFaqModal(btn.dataset.id, btn.dataset.q, btn.dataset.a, btn.dataset.sort);
});

function openFaqModal(id,q,a,sort) {
  const isEdit = !!id;
  document.getElementById('faq-modal-title').innerHTML = '<i class="fas fa-question-circle" style="color:var(--gold)"></i> ' + (isEdit?'Edit':'Add') + ' FAQ';
  document.getElementById('faq-action').value = isEdit ? 'edit_faq' : 'add_faq';
  document.getElementById('faq-id').value   = id   || '';
  document.getElementById('faq-q').value    = q    || '';
  document.getElementById('faq-a').value    = a    || '';
  document.getElementById('faq-sort').value = sort || 0;
  document.getElementById('faq-modal').classList.add('open');
}
</script>

<!-- Add / Edit Module -->
<div class="modal-overlay" id="mod-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="mod-modal-title"><i class="fas fa-layer-group" style="color:var(--gold)"></i> Add Module</h3>
    <form method="POST">
      <input type="hidden" name="action" id="mod-action" value="add_module">
      <input type="hidden" name="mod_id" id="mod-id" value="">
      <div class="fg"><label>Module Title *</label><input type="text" name="mod_title" id="mod-title" class="finput" required placeholder="e.g. Foundations of Customer Service"></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="mod_sort" id="mod-sort" class="finput" value="0" min="0"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="mod-submit-btn">Add Module</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('mod-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Add / Edit Lesson -->
<div class="modal-overlay" id="lesson-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox" style="max-width:680px;">
    <h3 id="lesson-modal-title"><i class="fas fa-play-circle" style="color:var(--gold)"></i> Add Lesson</h3>
    <form method="POST">
      <input type="hidden" name="action" id="lesson-action" value="add_lesson">
      <input type="hidden" name="lesson_id" id="lesson-id" value="">

      <p style="font-size:.82rem;font-weight:700;color:var(--navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em;">📹 Lesson Info</p>
      <div class="fg-row">
        <div class="fg" style="grid-column:1/-1">
          <label>Module</label>
          <select name="lesson_module_id" id="lesson-mod" class="finput">
            <?php foreach ($mods as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="fg"><label>Lesson Title *</label><input type="text" name="lesson_title" id="lesson-title" class="finput" required placeholder="e.g. What is Great Customer Service?"></div>
      <div class="fg">
        <label>YouTube Video URL or ID</label>
        <input type="text" name="lesson_youtube" id="lesson-yt" class="finput" placeholder="https://youtube.com/watch?v=XXXX or just the ID">
        <small style="color:var(--muted);font-size:.78rem;">Paste the full YouTube link or just the video ID (e.g. dQw4w9WgXcQ)</small>
      </div>
      <div class="fg"><label>Sort Order</label><input type="number" name="lesson_sort" id="lesson-sort" class="finput" value="0" min="0"></div>

      <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
      <p style="font-size:.82rem;font-weight:700;color:var(--navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em;">📝 Quiz Question</p>
      <div class="fg"><label>Question</label><input type="text" name="quiz_q" id="lesson-qq" class="finput" placeholder="e.g. Which sounds most customer friendly?"></div>
      <div class="fg"><label>Option A</label><input type="text" name="quiz_a" id="lesson-qa" class="finput" placeholder="Option A text"></div>
      <div class="fg"><label>Option B</label><input type="text" name="quiz_b" id="lesson-qb" class="finput" placeholder="Option B text"></div>
      <div class="fg"><label>Option C</label><input type="text" name="quiz_c" id="lesson-qc" class="finput" placeholder="Option C text"></div>
      <div class="fg">
        <label>Correct Answer</label>
        <select name="quiz_cor" id="lesson-qcor" class="finput">
          <option value="a">A</option><option value="b">B</option><option value="c">C</option>
        </select>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
      <p style="font-size:.82rem;font-weight:700;color:var(--navy);margin-bottom:12px;text-transform:uppercase;letter-spacing:.04em;">🎭 Scenario Question</p>
      <div class="fg"><label>Question</label><textarea name="sc_q" id="lesson-sq" class="finput" rows="2" placeholder="e.g. A customer says..."></textarea></div>
      <div class="fg"><label>Option A</label><input type="text" name="sc_a" id="lesson-sa" class="finput" placeholder="Option A text"></div>
      <div class="fg"><label>Option B</label><input type="text" name="sc_b" id="lesson-sb" class="finput" placeholder="Option B text"></div>
      <div class="fg"><label>Option C</label><input type="text" name="sc_c" id="lesson-sc" class="finput" placeholder="Option C text"></div>
      <div class="fg">
        <label>Correct Answer</label>
        <select name="sc_cor" id="lesson-scor" class="finput">
          <option value="a">A</option><option value="b">B</option><option value="c">C</option>
        </select>
      </div>

      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="lesson-submit-btn">Add Lesson</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('lesson-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModModal(id, title, sort) {
  const isEdit = !!id;
  document.getElementById('mod-modal-title').innerHTML = '<i class="fas fa-layer-group" style="color:var(--gold)"></i> ' + (isEdit ? 'Edit' : 'Add') + ' Module';
  document.getElementById('mod-action').value     = isEdit ? 'edit_module' : 'add_module';
  document.getElementById('mod-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Module';
  document.getElementById('mod-id').value    = id    || '';
  document.getElementById('mod-title').value = title || '';
  document.getElementById('mod-sort').value  = sort  || 0;
  document.getElementById('mod-modal').classList.add('open');
}

// Lesson modal — called directly OR via data attributes
function openLessonModal(id, modId, title, yt, sort, qq, qa, qb, qc, qcor, sq, sa, sb, sc, scor) {
  const isEdit = !!id;
  document.getElementById('lesson-modal-title').innerHTML = '<i class="fas fa-play-circle" style="color:var(--gold)"></i> ' + (isEdit ? 'Edit' : 'Add') + ' Lesson';
  document.getElementById('lesson-action').value     = isEdit ? 'edit_lesson' : 'add_lesson';
  document.getElementById('lesson-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Lesson';
  document.getElementById('lesson-id').value    = id    || '';
  document.getElementById('lesson-mod').value   = modId || '';
  document.getElementById('lesson-title').value = title || '';
  document.getElementById('lesson-yt').value    = yt    || '';
  document.getElementById('lesson-sort').value  = sort  || 0;
  document.getElementById('lesson-qq').value    = qq    || '';
  document.getElementById('lesson-qa').value    = qa    || '';
  document.getElementById('lesson-qb').value    = qb    || '';
  document.getElementById('lesson-qc').value    = qc    || '';
  document.getElementById('lesson-qcor').value  = qcor  || 'a';
  document.getElementById('lesson-sq').value    = sq    || '';
  document.getElementById('lesson-sa').value    = sa    || '';
  document.getElementById('lesson-sb').value    = sb    || '';
  document.getElementById('lesson-sc').value    = sc    || '';
  document.getElementById('lesson-scor').value  = scor  || 'a';
  document.getElementById('lesson-modal').classList.add('open');
}

// Event delegation for lesson edit buttons (handles special chars safely via data attributes)
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.lesson-edit-btn');
  if (!btn) return;
  const d = btn.dataset;
  openLessonModal(
    d.id, d.mod, d.title, d.yt, d.sort,
    d.qq, d.qa, d.qb, d.qc, d.qcor,
    d.sq, d.sa, d.sb, d.sc, d.scor
  );
});

// Event delegation for module edit buttons
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.mod-edit-btn');
  if (!btn) return;
  openModModal(btn.dataset.id, btn.dataset.title, btn.dataset.sort);
});
</script>

<!-- Job Link Modal -->
<div class="modal-overlay" id="jl-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="jl-modal-title"><i class="fas fa-briefcase" style="color:var(--gold)"></i> Add Job Link</h3>
    <form method="POST">
      <input type="hidden" name="action" id="jl-action" value="add_job_link">
      <input type="hidden" name="jl_id" id="jl-id" value="">
      <div class="fg"><label>Title *</label><input type="text" name="jl_title" id="jl-title" class="finput" required placeholder="e.g. Remote Customer Support - Shopify"></div>
      <div class="fg"><label>Job URL *</label><input type="url" name="jl_url" id="jl-url" class="finput" required placeholder="https://..."></div>
      <div class="fg"><label>Expiry Date (optional)</label><input type="date" name="jl_expires" id="jl-expires" class="finput"></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="jl_sort" id="jl-sort" class="finput" value="0" min="0"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="jl-submit-btn">Add Job Link</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('jl-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Webinar Modal -->
<div class="modal-overlay" id="wb-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="wb-modal-title"><i class="fas fa-video" style="color:var(--gold)"></i> Add Webinar</h3>
    <form method="POST">
      <input type="hidden" name="action" id="wb-action" value="add_webinar">
      <input type="hidden" name="wb_id" id="wb-id" value="">
      <div class="fg"><label>Title *</label><input type="text" name="wb_title" id="wb-title" class="finput" required placeholder="e.g. Weekly Q&A Session"></div>
      <div class="fg"><label>Description</label><input type="text" name="wb_desc" id="wb-desc" class="finput" placeholder="Short description (optional)"></div>
      <div class="fg"><label>Meeting URL *</label><input type="url" name="wb_url" id="wb-url" class="finput" required placeholder="Zoom / Google Meet / etc."></div>
      <div class="fg"><label>Date &amp; Time</label><input type="datetime-local" name="wb_date" id="wb-date" class="finput"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="wb-submit-btn">Add Session</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('wb-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Q&A Reply Modal -->
  <div class="modal-overlay" id="qa-modal" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="mbox" style="max-width:640px">
      <h3 style="margin-bottom:6px"><i class="fas fa-comments" style="color:var(--gold)"></i> <span id="qa-modal-title">Conversation</span></h3>
      <div id="qa-modal-meta" style="font-size:.8rem;color:var(--muted);margin-bottom:14px"></div>
      <div class="fg">
        <label>Conversation</label>
        <div id="qa-thread" style="background:var(--bg);padding:12px;border-radius:9px;max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:10px"></div>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="reply_question">
        <input type="hidden" name="q_id" id="qa-q-id" value="">
        <div class="fg"><label>Your Reply *</label><textarea name="q_answer" id="qa-answer" class="finput" rows="4" required placeholder="Type your reply here..."></textarea></div>
        <div class="mactions">
          <button type="submit" class="mbtn mbtn-primary">Send Reply</button>
          <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('qa-modal').classList.remove('open')">Close</button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Community Resource Modal -->
<div class="modal-overlay" id="cr-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3 id="cr-modal-title"><i class="fas fa-folder-open" style="color:var(--gold)"></i> Add Resource</h3>
    <form method="POST">
      <input type="hidden" name="action" id="cr-action" value="add_comm_resource">
      <input type="hidden" name="cr_id" id="cr-id" value="">
      <div class="fg"><label>Title *</label><input type="text" name="cr_title" id="cr-title" class="finput" required placeholder="e.g. CV Template Pack"></div>
      <div class="fg"><label>Description</label><input type="text" name="cr_desc" id="cr-desc" class="finput" placeholder="Short description (optional)"></div>
      <div class="fg"><label>URL (Google Drive / Dropbox / etc.) *</label><input type="url" name="cr_url" id="cr-url" class="finput" required placeholder="https://..."></div>
      <div class="fg"><label>Sort Order</label><input type="number" name="cr_sort" id="cr-sort" class="finput" value="0" min="0"></div>
      <div class="mactions">
        <button type="submit" class="mbtn mbtn-primary" id="cr-submit-btn">Add Resource</button>
        <button type="button" class="mbtn mbtn-cancel" onclick="document.getElementById('cr-modal').classList.remove('open')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function openJlModal(id, title, url, expires, sort) {
  const isEdit = !!id;
  document.getElementById('jl-modal-title').innerHTML = '<i class="fas fa-briefcase" style="color:var(--gold)"></i> ' + (isEdit?'Edit':'Add') + ' Job Link';
  document.getElementById('jl-action').value     = isEdit ? 'edit_job_link' : 'add_job_link';
  document.getElementById('jl-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Job Link';
  document.getElementById('jl-id').value      = id      || '';
  document.getElementById('jl-title').value   = title   || '';
  document.getElementById('jl-url').value     = url     || '';
  document.getElementById('jl-expires').value = expires || '';
  document.getElementById('jl-sort').value    = sort    || 0;
  document.getElementById('jl-modal').classList.add('open');
}

function openWbModal(id, title, desc, url, date) {
  const isEdit = !!id;
  document.getElementById('wb-modal-title').innerHTML = '<i class="fas fa-video" style="color:var(--gold)"></i> ' + (isEdit?'Edit':'Add') + ' Webinar';
  document.getElementById('wb-action').value      = isEdit ? 'edit_webinar' : 'add_webinar';
  document.getElementById('wb-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Session';
  document.getElementById('wb-id').value    = id    || '';
  document.getElementById('wb-title').value = title || '';
  document.getElementById('wb-desc').value  = desc  || '';
  document.getElementById('wb-url').value   = url   || '';
  document.getElementById('wb-date').value  = date  || '';
  document.getElementById('wb-modal').classList.add('open');
}

function openQaThread(rowEl) {
    if (!rowEl) return;
    let data;
    try { data = JSON.parse(rowEl.getAttribute('data-thread') || '{}'); } catch(e) { data = {}; }
    document.getElementById('qa-q-id').value = data.id || '';
    document.getElementById('qa-modal-title').textContent = data.subject || 'Conversation';
    const meta = document.getElementById('qa-modal-meta');
    meta.innerHTML = '<strong>' + (data.member || 'Unknown') + '</strong>'
      + (data.email ? ' &middot; ' + data.email : '')
      + ' &middot; <span style="text-transform:capitalize">' + (data.status || 'open') + '</span>';
    const wrap = document.getElementById('qa-thread');
    wrap.innerHTML = '';
    const msgs = (data.messages && data.messages.length) ? data.messages : [];
    if (!msgs.length) {
      wrap.innerHTML = '<div style="color:#9ca3af;font-size:.85rem">No messages yet.</div>';
    } else {
      msgs.forEach(function(m){
        const isAdmin = m.sender === 'admin';
        const bubble = document.createElement('div');
        bubble.style.cssText = 'padding:10px 12px;border-radius:10px;font-size:.88rem;line-height:1.5;max-width:85%;'
          + (isAdmin
              ? 'background:rgba(201,168,76,.12);border-left:3px solid #C9A84C;align-self:flex-end;color:#0D1B3E;'
              : 'background:#fff;border:1px solid #e5e7eb;align-self:flex-start;color:#0D1B3E;');
        const who = document.createElement('div');
        who.style.cssText = 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:' + (isAdmin ? '#C9A84C' : '#6B7A99') + ';margin-bottom:4px';
        who.textContent = (isAdmin ? 'Admin' : (data.member || 'Member')) + (m.at ? ' · ' + formatQaDate(m.at) : '');
        const body = document.createElement('div');
        body.style.whiteSpace = 'pre-wrap';
        body.textContent = m.body || '';
        bubble.appendChild(who);
        bubble.appendChild(body);
        wrap.appendChild(bubble);
      });
      setTimeout(function(){ wrap.scrollTop = wrap.scrollHeight; }, 30);
    }
    document.getElementById('qa-answer').value = '';
    document.getElementById('qa-modal').classList.add('open');
  }
  
  function formatQaDate(s) {
    if (!s) return '';
    const d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d.getTime())) return s;
    const m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    let h = d.getHours(); const mm = String(d.getMinutes()).padStart(2,'0');
    const ap = h >= 12 ? 'pm' : 'am'; h = h % 12 || 12;
    return m[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ' ' + h + ':' + mm + ap;
  }

function openCrModal(id, title, desc, url, sort) {
  const isEdit = !!id;
  document.getElementById('cr-modal-title').innerHTML = '<i class="fas fa-folder-open" style="color:var(--gold)"></i> ' + (isEdit?'Edit':'Add') + ' Resource';
  document.getElementById('cr-action').value      = isEdit ? 'edit_comm_resource' : 'add_comm_resource';
  document.getElementById('cr-submit-btn').textContent = isEdit ? 'Save Changes' : 'Add Resource';
  document.getElementById('cr-id').value    = id    || '';
  document.getElementById('cr-title').value = title || '';
  document.getElementById('cr-desc').value  = desc  || '';
  document.getElementById('cr-url').value   = url   || '';
  document.getElementById('cr-sort').value  = sort  || 0;
  document.getElementById('cr-modal').classList.add('open');
}
</script>


<!-- Add Update Modal -->
<div class="modal-overlay" id="upd-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3><i class="fas fa-bullhorn" style="color:var(--gold)"></i> Add Update</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_update">
      <div class="fg">
        <label>Type</label>
        <select name="upd_type" class="finput">
          <option value="update">Update</option>
          <option value="opportunity">Opportunity</option>
          <option value="announcement">Announcement</option>
        </select>
      </div>
      <div class="fg"><label>Title *</label><input type="text" name="upd_title" class="finput" required placeholder="e.g. New Job Opportunity"></div>
      <div class="fg"><label>Body *</label><textarea name="upd_body" class="finput" rows="4" required placeholder="Write your update here..."></textarea></div>
      <button type="submit" class="btn-add" style="width:100%;justify-content:center">Post Update</button>
    </form>
  </div>
</div>


<script>
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('mob-open');
  document.getElementById('sb-overlay').classList.toggle('open');
}
// Close sidebar when a nav link is clicked on mobile
document.querySelectorAll('.sb-link').forEach(function(link){
  link.addEventListener('click', function(){
    if(window.innerWidth <= 900){
      document.querySelector('.sidebar').classList.remove('mob-open');
      document.getElementById('sb-overlay').classList.remove('open');
    }
  });
});
</script>

<!-- Edit Testimonial Modal -->
<div class="modal-overlay" id="testi-edit-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3><i class="fas fa-star" style="color:var(--gold)"></i> Edit Testimonial</h3>
    <form method="POST">
      <input type="hidden" name="action" value="edit_testimonial">
      <input type="hidden" name="t_id" id="te-id">
      <div class="fg-row">
        <div class="fg"><label>Name *</label><input type="text" name="t_name" id="te-name" class="finput" required></div>
        <div class="fg"><label>Initials (2 chars)</label><input type="text" name="t_initials" id="te-initials" class="finput" maxlength="2"></div>
      </div>
      <div class="fg"><label>Role / Title</label><input type="text" name="t_role" id="te-role" class="finput"></div>
      <div class="fg"><label>Testimonial *</label><textarea name="t_content" id="te-content" class="finput" rows="4" required></textarea></div>
      <div class="fg"><label>Rating (1-5)</label><input type="number" name="t_rating" id="te-rating" class="finput" min="1" max="5" value="5"></div>
      <button type="submit" class="btn-add" style="width:100%;justify-content:center">Save Changes</button>
    </form>
  </div>
</div>
<script>
function openTestiModal(id,name,role,content,rating,initials){
  document.getElementById('te-id').value       = id;
  document.getElementById('te-name').value     = name;
  document.getElementById('te-role').value     = role;
  document.getElementById('te-content').value  = content;
  document.getElementById('te-rating').value   = rating;
  document.getElementById('te-initials').value = initials;
  document.getElementById('testi-edit-modal').classList.add('open');
}
</script>

<?php endif; ?>

<!-- Add Subscriber Modal -->
<div class="modal-overlay" id="sub-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="mbox">
    <h3><i class="fas fa-envelope" style="color:var(--gold)"></i> Add Subscriber</h3>
    <form method="POST">
      <input type="hidden" name="action" value="add_subscriber">
      <div class="fg"><label>Email *</label><input type="email" name="sub_email" class="finput" required placeholder="email@example.com"></div>
      <div class="fg"><label>Name (optional)</label><input type="text" name="sub_name" class="finput" placeholder="Full name"></div>
      <button type="submit" class="btn-add" style="width:100%;justify-content:center">Add Subscriber</button>
    </form>
  </div>
</div>
</body>
</html>