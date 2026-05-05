<?php
/**
 * UKLOOLE — Dynamic Resources Page
 * Loads resources from the DB (admin-managed via admin.php)
 * Falls back to static display if DB unavailable
 */

$resources_from_db = [];
$db_ok = false;

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');
    $db_ok = true;
    $q = $conn->query("SELECT * FROM resources WHERE is_active=1 ORDER BY sort_order ASC, created_at ASC");
    while ($r = $q->fetch_assoc()) {
        $resources_from_db[] = $r;
    }
    $conn->close();
}

// ---- JSON API endpoint (?json=1) ----
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    if ($db_ok) {
        echo json_encode(['ok' => true, 'resources' => $resources_from_db]);
    } else {
        echo json_encode(['ok' => false, 'resources' => []]);
    }
    exit;
}

// Icon mapping based on keywords
function resource_icon($name) {
    $name = strtolower($name);
    if (strpos($name,'slide')!==false || strpos($name,'course')!==false) return 'fa-file-powerpoint';
    if (strpos($name,'cv')!==false || strpos($name,'resume')!==false) return 'fa-file-alt';
    if (strpos($name,'workbook')!==false) return 'fa-book-open';
    if (strpos($name,'cover letter')!==false) return 'fa-envelope-open-text';
    if (strpos($name,'interview')!==false) return 'fa-clipboard-question';
    if (strpos($name,'script')!==false || strpos($name,'support')!==false) return 'fa-headset';
    if (strpos($name,'bundle')!==false || strpos($name,'toolkit')!==false) return 'fa-layer-group';
    if (strpos($name,'job')!==false || strpos($name,'remote')!==false) return 'fa-toolbox';
    return 'fa-file-pdf';
}

function resource_type_label($name) {
    $name = strtolower($name);
    if (strpos($name,'bundle')!==false || strpos($name,'zip')!==false) return 'ZIP Bundle';
    if (strpos($name,'cv')!==false) return 'Interactive';
    return 'PDF';
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
  <title>Resources &amp; Materials — Ukloole</title>
  <meta name="description" content="Download PDF templates, CV guides, cover letters and remote job toolkits to get hired faster.">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .resource-price-wrap { display:flex; align-items:center; gap:7px; flex-wrap:wrap; }
    .resource-price-orig { text-decoration:line-through; color:#9CA3AF; font-size:.82rem; font-weight:500; }
  </style>
</head>
<body class="has-cart">

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="container">
      <a href="/" class="navbar-brand">
        <img src="/logo.png" alt="Ukloole Logo">
        <span>Ukloole</span>
      </a>
      <ul class="nav-links">
        <li><a href="/">Home</a></li>
        <li><a href="/courses">Course</a></li>
        <li><a href="/resources" class="active">Materials</a></li>
        <li><a href="/community">Guidance</a></li>
      </ul>
      <div class="nav-actions">
        <button class="cart-btn" onclick="toggleCart()">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-count" id="cart-count" style="display:none;">0</span>
        </button>
        <button class="hamburger" id="hamburger" aria-label="Toggle menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </nav>

  <div class="mobile-menu" id="mobile-menu">
    <a href="/">Home</a>
    <a href="/courses">Course</a>
    <a href="/resources" class="active">Materials</a>
    <a href="/community">Guidance</a>
  </div>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <div class="container">
      <h1>You're not Underqualified. You're Underprepared</h1>
      <p>Fix how you communicate, answer questions and show up so you finally get the Job</p>
    </div>
  </section>

  <!-- PRODUCT GRID -->
  <section>
    <div class="container">
      <div class="resource-grid">

        <?php if ($db_ok && !empty($resources_from_db)): ?>

          <?php foreach ($resources_from_db as $r): ?>
          <?php
              $is_gpt    = ($r['type'] ?? '') === 'gpt';
              $icon      = $is_gpt ? 'fa-robot' : resource_icon($r['name']);
              $type_lbl  = $is_gpt ? 'GPT' : resource_type_label($r['name']);
              $is_free   = $r['type'] === 'free' || (!$is_gpt && floatval($r['price']) == 0);
              $cart_id   = strtolower(preg_replace('/[^a-z0-9]/i','-',$r['name']));
              $cart_id   = preg_replace('/-+/','-',trim($cart_id,'-'));
            ?>
            <div class="resource-card">
              <div class="resource-card-top">
                <div class="resource-badges">
                  <?php if ($is_gpt): ?>
                      <span class="badge badge-free" style="background:rgba(124,58,237,.15);color:#7c3aed;">GPT</span>
                      <?php if (floatval($r['price']) > 0): ?>
                        <span class="badge badge-paid">&#8358;<?= number_format($r['price']) ?></span>
                      <?php else: ?>
                        <span class="badge badge-free">Free</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php if ($is_free): ?>
                        <span class="badge badge-free">Free</span>
                      <?php else: ?>
                        <span class="badge badge-paid">&#8358;<?= number_format($r['price']) ?></span>
                      <?php endif; ?>
                      <?php if (!empty($r['file_type'])): ?>
                      <span class="badge badge-type" style="text-transform:uppercase;"><?= htmlspecialchars(strtoupper($r['file_type'])) ?></span>
                    <?php else: ?>
                      <span class="badge badge-type"><?= htmlspecialchars($type_lbl) ?></span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="resource-icon"<?= $is_gpt ? ' style="color:#7c3aed"' : '' ?>><i class="fas <?= $icon ?>"></i></div>
              </div>
              <div class="resource-card-bottom">
                <span class="resource-tag"><?= $is_gpt ? 'GPT Tool' : ($is_free ? 'Free Resource' : 'Premium') ?></span>
                <h3><?= htmlspecialchars($r['name']) ?></h3>
                <p><?= htmlspecialchars($r['description'] ?: 'Practical resource to advance your career.') ?></p>
                <div class="resource-footer">
                  <?php if ($is_gpt && floatval($r['price']) == 0): ?>
                    <span class="resource-price">Free</span>
                    <a class="btn-download" href="<?= htmlspecialchars($r['file_key']) ?>" target="_blank" rel="noopener" style="background:#7c3aed;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                      <i class="fas fa-external-link-alt"></i> Use GPT
                    </a>
                  <?php elseif ($is_gpt): ?>
                    <div class="resource-price-wrap">
                      <?php if (!empty($r['original_price']) && floatval($r['original_price']) > floatval($r['price'])): ?>
                        <span class="resource-price-orig">&#8358;<?= number_format($r['original_price']) ?></span>
                      <?php endif; ?>
                      <span class="resource-price">&#8358;<?= number_format($r['price']) ?></span>
                    </div>
                    <button class="btn-add-cart" id="cart-btn-<?= $cart_id ?>" style="background:#7c3aed;"
                      onclick="addToCart('<?= $cart_id ?>','<?= addslashes($r['name']) ?>',<?= intval($r['price']) ?>)">
                      <i class="fas fa-robot"></i> Use GPT
                    </button>
                  <?php elseif ($is_free): ?>
                    <span class="resource-price">&#8358;0</span>
                    <button class="btn-download" onclick="downloadFree('<?= htmlspecialchars($r['file_key']) ?>')">
                      <i class="fas fa-download"></i> Download
                    </button>
                  <?php else: ?>
                    <div class="resource-price-wrap">
                      <?php if (!empty($r['original_price']) && floatval($r['original_price']) > floatval($r['price'])): ?>
                        <span class="resource-price-orig">&#8358;<?= number_format($r['original_price']) ?></span>
                      <?php endif; ?>
                      <span class="resource-price">&#8358;<?= number_format($r['price']) ?></span>
                    </div>
                    <button class="btn-add-cart" id="cart-btn-<?= $cart_id ?>"
                      onclick="addToCart('<?= $cart_id ?>','<?= addslashes($r['name']) ?>',<?= intval($r['price']) ?>)">
                      <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

        <?php else: ?>
          <!-- Fallback: static content if DB is not available -->
          <div class="resource-card">
            <div class="resource-card-top">
              <div class="resource-badges"><span class="badge badge-free">Free</span><span class="badge badge-type">PDF</span></div>
              <div class="resource-icon"><i class="fas fa-file-powerpoint"></i></div>
            </div>
            <div class="resource-card-bottom">
              <span class="resource-tag">All Modules</span>
              <h3>Course Slides</h3>
              <p>Complete slide deck from the Customer Service Mastery course.</p>
              <div class="resource-footer">
                <span class="resource-price">&#8358;0</span>
                <button class="btn-download" onclick="downloadFree('course-slides.pdf')"><i class="fas fa-download"></i> Download</button>
              </div>
            </div>
          </div>
          <div class="resource-card">
            <div class="resource-card-top">
              <div class="resource-badges"><span class="badge badge-paid">&#8358;2,000</span><span class="badge badge-type">PDF</span></div>
              <div class="resource-icon"><i class="fas fa-book-open"></i></div>
            </div>
            <div class="resource-card-bottom">
              <span class="resource-tag">Workbook</span>
              <h3>Customer Service Workbook</h3>
              <p>Interactive exercises to sharpen your communication and support skills.</p>
              <div class="resource-footer">
                <span class="resource-price">&#8358;2,000</span>
                <button class="btn-add-cart" id="cart-btn-workbook" onclick="addToCart('workbook','Customer Service Workbook',2000)"><i class="fas fa-cart-plus"></i> Add to Cart</button>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <!-- CV BUILDER PROMO (teaser banner) -->
  <section style="padding:48px 0 24px;">
    <div class="container">
      <a href="https://www.ukloole.com/cv-builder-app" target="_blank" rel="noopener" class="cv-teaser-link">
        <div class="cv-teaser">
          <div class="cv-teaser-icon"><i class="fas fa-file-alt"></i></div>
          <div class="cv-teaser-body">
            <span class="cv-teaser-badge">NEW</span>
            <h3>Build a recruiter-ready CV in minutes</h3>
            <p>Try the Ukloole CV Builder &mdash; beautiful, ATS-friendly templates.</p>
          </div>
          <div class="cv-teaser-cta">Open CV Builder &nbsp;&#8599;</div>
        </div>
      </a>
    </div>
    <style>
      .cv-teaser-link { text-decoration:none; display:block; }
      .cv-teaser {
        display:flex; align-items:center; gap:20px;
        background:linear-gradient(120deg,#1A2D5A 0%,#2d3f7a 100%);
        border-radius:18px; padding:28px 32px;
        box-shadow:0 8px 30px rgba(13,27,62,.18);
        transition:transform .18s,box-shadow .18s;
      }
      .cv-teaser:hover { transform:translateY(-2px); box-shadow:0 14px 40px rgba(13,27,62,.26); }
      .cv-teaser-icon {
        flex-shrink:0; width:56px; height:56px;
        background:rgba(255,255,255,.1); border-radius:14px;
        display:flex; align-items:center; justify-content:center;
        font-size:1.6rem; color:#C9A84C;
      }
      .cv-teaser-body { flex:1; min-width:0; }
      .cv-teaser-badge {
        display:inline-block; background:#C9A84C; color:#1A2D5A;
        font-size:.68rem; font-weight:800; letter-spacing:.08em;
        padding:3px 10px; border-radius:50px; margin-bottom:8px;
        text-transform:uppercase;
      }
      .cv-teaser-body h3 { font-family:'EB Garamond',serif; font-size:1.35rem; color:#fff; margin:0 0 6px; }
      .cv-teaser-body p { color:rgba(255,255,255,.72); font-size:.88rem; margin:0; line-height:1.5; }
      .cv-teaser-cta {
        flex-shrink:0;
        background:#C9A84C; color:#1A2D5A;
        font-weight:800; font-size:.92rem;
        padding:13px 22px; border-radius:10px;
        white-space:nowrap; transition:background .15s;
      }
      .cv-teaser:hover .cv-teaser-cta { background:#e0bb5c; }
      @media (max-width:640px) {
        .cv-teaser { flex-wrap:wrap; padding:20px; gap:14px; }
        .cv-teaser-cta { width:100%; text-align:center; }
      }
    </style>
  </section>

    <!-- CART OVERLAY -->
  <div class="cart-overlay" id="cart-overlay" onclick="toggleCart()"></div>

  <!-- CART PANEL -->
  <div class="cart-panel" id="cart-panel">
    <div class="cart-header">
      <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
      <button class="cart-close" onclick="toggleCart()"><i class="fas fa-times"></i></button>
    </div>
    <div class="cart-items" id="cart-items-list">
      <div class="cart-empty" id="cart-empty">
        <div class="cart-empty-icon"><i class="fas fa-cart-shopping"></i></div>
        <p>Your cart is empty</p>
        <button class="btn btn-outline btn-sm" onclick="toggleCart()">Browse Materials</button>
      </div>
    </div>
    <div id="cart-footer" style="display:none;">
      <div class="cart-footer">
        <div class="cart-total">
          <span>Total</span>
          <span id="cart-total">&#8358;0</span>
        </div>
        <div class="checkout-form" id="checkout-form">
          <input type="text" id="checkout-name" class="form-input" placeholder="Full Name" required>
          <input type="email" id="checkout-email" class="form-input" placeholder="Email Address" required>
        </div>
        <button class="btn-checkout" id="checkout-btn" onclick="proceedToCheckout()">
          <i class="fas fa-lock"></i> Checkout Securely
        </button>
        <p class="secure-note"><i class="fas fa-shield-halved"></i> Secured by Paystack</p>
      </div>
    </div>
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
            <li><a href="privacy.html">Privacy Policy</a></li>
            <li><a href="terms.html">Terms of Service</a></li>
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

  <script src="https://js.paystack.co/v1/inline.js"></script>
  <script src="/index.js"></script>
  <script>
    function downloadFree(filename) {
      if (!filename) return;
      // Remove any existing modal first
      const existing = document.getElementById('free-dl-modal');
      if (existing) existing.remove();

      const modal = document.createElement('div');
      modal.id = 'free-dl-modal';
      modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px;';
      modal.innerHTML = `
        <div style="background:white;border-radius:16px;padding:36px;max-width:400px;width:100%;text-align:center;">
          <div style="font-size:2.5rem;margin-bottom:14px;">&#128196;</div>
          <h3 style="font-family:'EB Garamond',serif;font-size:1.5rem;color:#1A2D5A;margin-bottom:8px;">Get Your Free Download</h3>
          <p style="color:#6B7A99;margin-bottom:20px;font-size:.9rem;">Enter your email to receive the download link.</p>
          <input type="email" id="dl-email" placeholder="your@email.com" style="width:100%;padding:12px 16px;border:2px solid #E2E8F0;border-radius:10px;font-size:.95rem;margin-bottom:12px;font-family:'DM Sans',sans-serif;outline:none;">
          <button onclick="submitFreeDownload('${filename}', this)" style="width:100%;padding:13px;background:#0D1B3E;color:white;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-weight:700;cursor:pointer;font-size:.95rem;">
            <i class="fas fa-download"></i> Send Download Link
          </button>
          <button onclick="document.getElementById('free-dl-modal').remove()" style="margin-top:10px;background:none;border:none;color:#6B7A99;cursor:pointer;font-size:.85rem;font-family:'DM Sans',sans-serif;text-decoration:underline;">Cancel</button>
        </div>`;
      document.body.appendChild(modal);
    }

    function submitFreeDownload(filename, btn) {
      const email = document.getElementById('dl-email').value.trim();
      if (!email || !email.includes('@')) { alert('Please enter a valid email.'); return; }
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
      btn.disabled = true;
      fetch('save-email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email) + '&file=' + encodeURIComponent(filename)
      }).then(() => {
        const m = document.getElementById('free-dl-modal');
        if (m) m.remove();
        alert('Check your email for the download link!');
      }).catch(() => {
        alert('Could not send. Please try again.');
        btn.innerHTML = '<i class="fas fa-download"></i> Send Download Link';
        btn.disabled = false;
      });
    }
  </script>
</body>
</html>
