<?php
/**
 * UKLOOLE — Community / Guidance Page
 * Renamed from community.html to community.php
 * DB connection ready for future dynamic content
 */

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) {
    $conn = null; // graceful fallback — page still renders
} else {
    $conn->set_charset('utf8mb4');
}

// Load community access pricing tiers from app_settings
// Each tier is stored as price_monthly, price_quarterly, price_bi_annual, price_yearly
// An empty string means "not set / don't show"
$pricing_tiers = [
  'monthly'   => ['label' => 'Monthly',  'price' => 0, 'active' => false],
  'quarterly' => ['label' => 'Quarterly','price' => 0, 'active' => false],
  'bi_annual' => ['label' => 'Bi-Annual','price' => 0, 'active' => false],
  'yearly'    => ['label' => 'Yearly',   'price' => 0, 'active' => false],
];

if ($conn) {
    @$conn->query("CREATE TABLE IF NOT EXISTS app_settings (
        k VARCHAR(100) NOT NULL PRIMARY KEY,
        v TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $keys_in = "'price_monthly','price_quarterly','price_bi_annual','price_yearly'";
    if ($res = @$conn->query("SELECT k,v FROM app_settings WHERE k IN ($keys_in)")) {
        while ($row = $res->fetch_assoc()) {
            $tier_key = str_replace('price_', '', $row['k']);
            if (isset($pricing_tiers[$tier_key]) && $row['v'] !== '' && $row['v'] !== null && intval($row['v']) > 0) {
                $pricing_tiers[$tier_key]['price']  = intval($row['v']);
                $pricing_tiers[$tier_key]['active'] = true;
            }
        }
    }
    $conn->close();
}

// Only the active tiers (those with a price set)
$active_tiers = array_filter($pricing_tiers, fn($t) => $t['active']);

// Fallback: if nothing is set yet, show a default quarterly tier so the page isn't empty
if (empty($active_tiers)) {
    $pricing_tiers['quarterly']['price']  = 25000;
    $pricing_tiers['quarterly']['active'] = true;
    $active_tiers = ['quarterly' => $pricing_tiers['quarterly']];
}

// Default selected tier (first active one)
$default_tier_key   = array_key_first($active_tiers);
$default_tier       = $active_tiers[$default_tier_key];
$community_price    = $default_tier['price'];          // used by Paystack init
$community_duration = $default_tier['label'];
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
  <title>Community - Ukloole</title>
  <meta name="description" content="Join Ukloole Premium. Get certified, access CV templates, and connect with peers earning in USD globally.">
  <link rel="stylesheet" href="/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* DFY Card */
    .dfy-card{background:linear-gradient(135deg,#0D1B3E,#1A2D5A);border-radius:16px;padding:28px;color:white;margin-top:20px;position:relative;overflow:hidden;}
    .dfy-card::before{content:'';position:absolute;top:-30px;right:-30px;width:120px;height:120px;background:rgba(201,168,76,.12);border-radius:50%;}
    .dfy-badge{display:inline-block;background:rgba(201,168,76,.2);color:#C9A84C;border:1px solid rgba(201,168,76,.4);border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:12px;}
    .dfy-card h3{font-size:1.25rem;margin-bottom:8px;color:white;}
    .dfy-card p{color:rgba(255,255,255,.75);font-size:.88rem;line-height:1.6;margin-bottom:10px;}
    .dfy-limited{color:#C9A84C;font-size:.8rem;font-weight:700;margin-bottom:14px;}
    .dfy-who{margin-bottom:16px;}
    .dfy-who p{color:rgba(255,255,255,.6);font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;}
    .dfy-who li{color:rgba(255,255,255,.8);font-size:.85rem;list-style:none;padding:3px 0;}
    .dfy-btn{display:inline-flex;align-items:center;gap:8px;background:#C9A84C;color:#0D1B3E;padding:12px 24px;border-radius:10px;font-weight:800;font-size:.92rem;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
    .dfy-btn:hover{background:#F2D06B;transform:translateY(-1px);}
    /* DFY Modal */
    .dfy-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:2000;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px);}
    .dfy-overlay.open{display:flex;}
    .dfy-modal{background:white;border-radius:24px;padding:36px;max-width:520px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,.3);}
    .dfy-modal h2{font-size:1.6rem;color:#0D1B3E;margin-bottom:6px;}
    .dfy-modal .sub{color:#6B7A99;font-size:.88rem;margin-bottom:24px;}
    .dfy-step{margin-bottom:16px;}
    .dfy-step label{display:block;font-size:.82rem;font-weight:700;color:#1A2D5A;margin-bottom:5px;}
    .dfy-step label span.req{color:#dc2626;}
    .dfy-step label span.opt{font-size:.75rem;color:#6B7A99;font-weight:400;}
    .dfy-input,.dfy-textarea{width:100%;padding:11px 14px;border:2px solid #E2E8F0;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;color:#1A2D5A;}
    .dfy-input:focus,.dfy-textarea:focus{border-color:#C9A84C;}
    .dfy-textarea{resize:vertical;min-height:80px;}
    .dfy-radio-group{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;}
    .dfy-radio-group label{display:flex;align-items:center;gap:6px;padding:8px 14px;border:2px solid #E2E8F0;border-radius:8px;cursor:pointer;font-size:.84rem;font-weight:500;color:#1A2D5A;transition:all .15s;margin-bottom:0;}
    .dfy-radio-group input[type=radio]{display:none;}
    .dfy-radio-group label:has(input:checked){border-color:#C9A84C;background:#FFF8E7;}
    .dfy-checkbox-group{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;}
    .dfy-checkbox-group label{display:flex;align-items:center;gap:6px;padding:8px 14px;border:2px solid #E2E8F0;border-radius:8px;cursor:pointer;font-size:.84rem;font-weight:500;color:#1A2D5A;transition:all .15s;margin-bottom:0;}
    .dfy-checkbox-group input[type=checkbox]{accent-color:#C9A84C;width:15px;height:15px;}
    .dfy-divider{border:none;border-top:1px solid #E2E8F0;margin:20px 0;}
    .dfy-submit-btn{width:100%;padding:14px;background:linear-gradient(135deg,#0D1B3E,#1A2D5A);color:white;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;}
    .dfy-submit-btn:hover{background:linear-gradient(135deg,#1A2D5A,#C9A84C);}
    .dfy-success{text-align:center;padding:40px 20px;display:none;}
    .dfy-success .check{font-size:3rem;margin-bottom:16px;}
    .dfy-success h3{font-size:1.5rem;color:#0D1B3E;margin-bottom:10px;}
    .dfy-success p{color:#6B7A99;font-size:.92rem;line-height:1.7;}
  </style>
</head>
<body>

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
        <li><a href="/resources">Materials</a></li>
        <li><a href="/community" class="active">Guidance</a></li>
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
    <a href="/community" class="active">Guidance</a>
  </div>

  <!-- HERO -->
  <section class="community-hero">
    <div class="container">
      <div class="page-header" style="background:none;padding:0;">
        <span class="badge" style="display:inline-block;background:rgba(255,255,255,0.15);color:white;padding:6px 16px;border-radius:20px;font-size:0.85rem;font-weight:600;margin-bottom:20px;border:1px solid rgba(255,255,255,0.3);">&#128737; Exclusive Access</span>
        <h1>The Ultimate Support</h1>
        <p>Upgrade to premium and get the guidance, access and opportunities you need to help you secure a Job remotely. This is a private, structured space designed to help you move faster and get real results</p>
      </div>
    </div>
  </section>

  <!-- MAIN CONTENT -->
  <section class="section">
    <div class="container">
      <div class="community-grid">

        <!-- Left: Benefits + Assessment -->
        <div>
          <h2 style="font-size:2rem;margin-bottom:28px;color:var(--secondary);">What's included in Premium?</h2>
          <div class="benefits-list">
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Private Access <p><small>No group chat chaos. Structured environment. Everything important comes directly to you</small></p></span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Direct Job Links</span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Live Sessions</span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Step-by-step Application Guidance</span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Members-only Resource Library</span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Q&amp;A (Ask your questions whenever you need to)</span></div>
            <div class="benefit-item"><div class="benefit-check">&#10003;</div><span>Opportunity Updates</span></div>

          <!-- Done-For-You Card -->
          <div class="dfy-card">
            <div class="dfy-badge">Hands-Free (VIP)</div>
            <h3>Done-For-You Job Application</h3>
            <p>No time to apply? We handle everything for you and position you to land interviews.</p>
            <p class="dfy-limited">&#9203; Limited slots available.</p>
            <div class="dfy-who">
              <p>Who it's for:</p>
              <ul>
                <li>&#9203; Busy professionals</li>
                <li>&#10004; People tired of applying without results</li>
              </ul>
            </div>
            <button class="dfy-btn" onclick="openDfyModal()">&#128073; Request Service</button>
          </div>
          </div>

          <div class="assessment-card">
            <h3>Ready to Get Certified?</h3>
            <p>Take the final assessment, Pay &#8358;10,000 and receive your verifiable Ukloole Certificate.</p>
            <button class="btn btn-lg" style="background:white;color:var(--primary);font-weight:700;width:100%;" onclick="openModal('assess-modal')">
              <i class="fas fa-paper-plane"></i> Start Assessment
            </button>
          </div>
        </div>

        <!-- Right: Pricing Card -->
        <div>
          <div class="pricing-card">
            <div class="pricing-card-top">
              <span class="lifetime-tag">Limited-time offer</span>
              <h3>Premium Access</h3>
              <div class="price-amount" id="display-price">&#8358;<?= number_format($default_tier['price']) ?></div>
              <p class="price-sub" id="display-label"><?= htmlspecialchars($default_tier['label']) ?></p>
              <input type="hidden" id="selected-tier-price" value="<?= $default_tier['price'] ?>">
              <input type="hidden" id="selected-tier-label" value="<?= htmlspecialchars($default_tier['label']) ?>">
              <?php if (count($active_tiers) > 1): ?>
              <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:14px;" id="tier-selector">
                <?php foreach ($active_tiers as $tkey => $tier): $isDefault = ($tkey === $default_tier_key); ?>
                  <button type="button"
                    onclick="selectTier(<?= $tier['price'] ?>, '<?= htmlspecialchars($tier['label']) ?>', this)"
                    class="tier-btn <?= $isDefault ? 'tier-btn-active' : '' ?>"
                    style="padding:7px 16px;border-radius:20px;border:2px solid <?= $isDefault ? 'var(--gold)' : '#e2e8f0' ?>;
                           background:<?= $isDefault ? 'var(--gold)' : 'white' ?>;color:<?= $isDefault ? 'white' : '#374151' ?>;
                           font-weight:600;font-size:.82rem;cursor:pointer;transition:all .2s;white-space:nowrap;">
                    <?= htmlspecialchars($tier['label']) ?>
                  </button>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <div class="pricing-card-body">
              <div class="pricing-form">
                <input type="text" id="community-name" class="form-input" placeholder="Your Full Name" required>
                <input type="email" id="community-email" class="form-input" placeholder="Your Email Address" required>
                <button class="btn-unlock" onclick="payWithPaystack()">
                  <i class="fas fa-lock-open"></i> Unlock Premium Access
                </button>
              </div>
              <div class="security-note">
                <i class="fas fa-shield-halved"></i> 100% Secure Payment via Paystack
              </div>

              <!-- Login note (shown after payment) -->
              <div id="login-note" style="display:none;margin-top:16px;background:var(--bg);border-radius:10px;padding:14px;text-align:center;">
                <p style="font-size:.88rem;color:var(--muted);margin-bottom:10px;">Payment confirmed! Access your community below.</p>
                <a href="/community-login" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;">
                  <i class="fas fa-sign-in-alt"></i> Login to Community
                </a>
              </div>
            </div>
          </div>

          <!-- Already a member — sits snugly below the pricing card -->
          <div style="display:flex;align-items:center;justify-content:center;gap:14px;margin-top:12px;padding:16px 24px;background:white;border:1px solid #e5e7f0;border-radius:12px;flex-wrap:wrap;box-shadow:0 2px 8px rgba(93,91,173,0.08);">
            <p style="color:#6b7280;font-size:.9rem;margin:0;">Already a member?</p>
            <a href="/community-login" style="display:inline-flex;align-items:center;gap:6px;color:#5D5BAD;font-weight:700;font-size:.95rem;white-space:nowrap;text-decoration:none;">
              <i class="fas fa-sign-in-alt"></i> Login to your account
            </a>
          </div>

        </div><!-- end right column -->
      </div><!-- end community-grid -->
    </div><!-- end container -->
  </section>

  <!-- GUARANTEE -->
  <section class="section-sm" style="background:white;">
    <div class="container" style="text-align:center;">
      <h3 style="font-size:1.8rem;margin-bottom:10px;">No experience required</h3>
      <p style="color:var(--muted);font-size:1.05rem;">Everything is designed to take you from beginner &rarr; earning remotely.</p>
    </div>
  </section>

  <!-- ASSESSMENT MODAL -->
  <div class="modal-overlay" id="assess-modal" onclick="if(event.target===this)closeModal('assess-modal')">
    <div class="modal">
      <h2>Start Certification Assessment</h2>
      <p>Enter your details to receive your unique assessment link via email.</p>
      <div class="modal-form">
        <input type="text" id="assess-name" class="form-input" placeholder="Full Name" required>
        <input type="email" id="assess-email" class="form-input" placeholder="Email Address" required>
        <div class="modal-actions">
          <button class="btn btn-primary" style="flex:1;" onclick="sendAssessment()">
            <i class="fas fa-envelope"></i> Send Assessment Link
          </button>
          <button class="btn btn-outline" onclick="closeModal('assess-modal')">Cancel</button>
        </div>
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
            <!--<li><a href="/coaching">1 : 1 Coaching</a></li>-->  
            <li><a href="https://www.ukloole.com" target="_blank">B2B Services</a></li>
            <li><a href="/privacy" target="_blank">Privacy Policy</a></li>
            <li><a href="/terms" target="_blank">Terms of Service</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">Contact</h4>
          <div class="footer-contact">
            <a href="mailto:learn@ukloole.com">&#9993; learn@ukloole.com</a>
            <a href="https://wa.me/message/IQZ6JVHJGNETE1">&#128172; Whatsapp: +234 810 159 3648</a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">&copy; 2026 Ukloole. All rights reserved.</div>
    </div>
  </footer>

  <script src="https://js.paystack.co/v1/inline.js"></script>
  <script src="/index.js"></script>
  <script>
    // Tier selector: update displayed price when user picks a plan
    function selectTier(price, label, btn) {
      document.getElementById('selected-tier-price').value = price;
      document.getElementById('selected-tier-label').value = label;
      document.getElementById('display-price').innerHTML = '&#8358;' + price.toLocaleString('en-NG');
      document.getElementById('display-label').textContent = label;
      // Update button styles
      document.querySelectorAll('.tier-btn').forEach(function(b) {
        b.style.background   = 'white';
        b.style.color        = '#374151';
        b.style.borderColor  = '#e2e8f0';
      });
      btn.style.background  = 'var(--gold)';
      btn.style.color       = 'white';
      btn.style.borderColor = 'var(--gold)';
    }

    // payWithPaystack uses the currently selected tier price
    function payWithPaystack() {
      const name  = document.getElementById('community-name');
      const email = document.getElementById('community-email');
      if (!name || !email || !name.value.trim() || !email.value.trim()) {
        alert('Please fill in your name and email.');
        return;
      }

      const selectedPrice = parseInt(document.getElementById('selected-tier-price').value, 10) || 0;
      const selectedLabel = document.getElementById('selected-tier-label').value || 'Community';

      if (selectedPrice <= 0) {
        alert('No plan selected. Please choose a pricing plan.');
        return;
      }

      const PAYSTACK_KEY = 'pk_live_b7249de7d74fe255f72dc04cfcb8495f22f06353';
      const handler = PaystackPop.setup({
        key: PAYSTACK_KEY,
        email: email.value.trim(),
        amount: selectedPrice * 100,
        currency: 'NGN',
        ref: 'UKLOOLE_COM_' + Date.now() + '_' + Math.floor(Math.random() * 1e6),
        channels: ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer', 'eft'],
        metadata: {
          custom_fields: [
            { display_name: 'Full Name', variable_name: 'full_name', value: name.value.trim() },
            { display_name: 'Plan', variable_name: 'plan', value: selectedLabel },
            { display_name: 'Type', variable_name: 'type', value: 'community' }
          ]
        },
        callback: function(response) {
          // Save to DB and redirect to login
          fetch('save-community-member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              name: name.value.trim(),
              email: email.value.trim(),
              reference: response.reference,
              amount: selectedPrice
            })
          })
          .then(r => r.json())
          .then(data => {
            // Show login prompt after payment
            document.getElementById('login-note').style.display = 'block';
            name.value = '';
            email.value = '';
            // Auto-redirect after 2 seconds
            setTimeout(() => { window.location.href = 'community-login.php'; }, 2000);
          })
          .catch(() => {
            document.getElementById('login-note').style.display = 'block';
          });
        },
        onClose: function() { console.log('Paystack closed.'); }
      });
      handler.openIframe();
    }
  </script>

  <!-- DONE-FOR-YOU MODAL -->
  <div class="dfy-overlay" id="dfy-modal" onclick="if(event.target===this)closeDfyModal()">
    <div class="dfy-modal">
      <div id="dfy-form-view">
        <h2>&#128640; Done-For-You Application</h2>
        <p class="sub">Fill in a few details and we'll take it from there. Takes less than 2 minutes.</p>
        <div class="dfy-step">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" class="dfy-input" id="dfy-name" placeholder="e.g. Adaeze Okafor">
        </div>
        <div class="dfy-step">
          <label>Email Address <span class="req">*</span></label>
          <input type="email" class="dfy-input" id="dfy-email" placeholder="your@email.com">
        </div>
        <div class="dfy-step">
          <label>What job role are you targeting? <span class="req">*</span></label>
          <input type="text" class="dfy-input" id="dfy-role" placeholder="e.g. Remote Customer Support, Virtual Assistant">
        </div>
        <hr class="dfy-divider">
        <div class="dfy-step">
          <label>Years of experience in this role?</label>
          <div class="dfy-radio-group">
            <label><input type="radio" name="dfy_exp" value="0-1"><span>0 &ndash; 1 year</span></label>
            <label><input type="radio" name="dfy_exp" value="2-4"><span>2 &ndash; 4 years</span></label>
            <label><input type="radio" name="dfy_exp" value="5+"><span>5+ years</span></label>
          </div>
        </div>
        <div class="dfy-step">
          <label>Do you currently have a CV?</label>
          <div class="dfy-radio-group">
            <label><input type="radio" name="dfy_cv" value="Yes"><span>&#10003; Yes, I have one</span></label>
            <label><input type="radio" name="dfy_cv" value="No"><span>&#10007; No, I need help</span></label>
          </div>
        </div>
        <div class="dfy-step">
          <label>What countries are you open to working in?</label>
          <div class="dfy-checkbox-group" id="dfy-countries">
            <label><input type="checkbox" value="Remote"><span>&#127760; Remote (Any)</span></label>
            <label><input type="checkbox" value="UK"><span>&#127468;&#127463; UK</span></label>
            <label><input type="checkbox" value="US"><span>&#127482;&#127480; US</span></label>
            <label><input type="checkbox" value="Canada"><span>&#127464;&#127462; Canada</span></label>
            <label><input type="checkbox" value="Europe"><span>&#127466;&#127482; Europe</span></label>
            <label><input type="checkbox" value="Other"><span>&#127758; Other</span></label>
          </div>
        </div>
        <div class="dfy-step">
          <label>When are you looking to start?</label>
          <div class="dfy-radio-group">
            <label><input type="radio" name="dfy_start" value="Immediately"><span>&#9889; Immediately</span></label>
            <label><input type="radio" name="dfy_start" value="1-3 months"><span>&#128197; 1 &ndash; 3 months</span></label>
            <label><input type="radio" name="dfy_start" value="Just exploring"><span>&#128064; Just exploring</span></label>
          </div>
        </div>
        <div class="dfy-step">
          <label>Anything else we should know? <span class="opt">(Optional but helpful)</span></label>
          <textarea class="dfy-textarea" id="dfy-extra" placeholder="e.g. I've been applying for 6 months without success, I also need help with my CV..."></textarea>
        </div>
        <div id="dfy-error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:.86rem;margin-bottom:12px;"></div>
        <button class="dfy-submit-btn" onclick="submitDfy()">
          <i class="fas fa-paper-plane"></i> Submit &mdash; We'll Be In Touch
        </button>
        <p style="text-align:center;color:#6B7A99;font-size:.75rem;margin-top:12px;">
          <i class="fas fa-lock"></i> Your info is private and never shared.
        </p>
      </div>
      <div class="dfy-success" id="dfy-success-view">
        <div class="check">&#9989;</div>
        <h3>Request Received!</h3>
        <p>We'll review your details and get back to you within <strong>24&ndash;48 hours</strong>.<br><br>Keep an eye on your email &mdash; we may also reach out via WhatsApp.</p>
        <button class="dfy-btn" style="margin-top:24px;justify-content:center;width:100%;" onclick="closeDfyModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    function openDfyModal() {
      document.getElementById('dfy-form-view').style.display = 'block';
      document.getElementById('dfy-success-view').style.display = 'none';
      document.getElementById('dfy-modal').classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeDfyModal() {
      document.getElementById('dfy-modal').classList.remove('open');
      document.body.style.overflow = '';
    }
    function submitDfy() {
      const name    = document.getElementById('dfy-name').value.trim();
      const email   = document.getElementById('dfy-email').value.trim();
      const role    = document.getElementById('dfy-role').value.trim();
      const errEl   = document.getElementById('dfy-error');
      if (!name || !email || !role) {
        errEl.textContent = 'Please fill in your name, email and target role.';
        errEl.style.display = 'block'; return;
      }
      errEl.style.display = 'none';
      const exp      = document.querySelector('input[name="dfy_exp"]:checked')?.value || '';
      const has_cv   = document.querySelector('input[name="dfy_cv"]:checked')?.value  || '';
      const start    = document.querySelector('input[name="dfy_start"]:checked')?.value || '';
      const countries = [...document.querySelectorAll('#dfy-countries input:checked')].map(c => c.value).join(', ');
      const extra    = document.getElementById('dfy-extra').value.trim();
      fetch('save-dfy.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name: name, email, target_role: role, experience: exp, has_cv, countries, start_when: start, extra_info: extra })
      })
      .then(r => r.json())
      .then(d => {
        if (d.ok) {
          document.getElementById('dfy-form-view').style.display = 'none';
          document.getElementById('dfy-success-view').style.display = 'block';
        } else {
          errEl.textContent = d.error || 'Something went wrong. Please try again.';
          errEl.style.display = 'block';
        }
      })
      .catch(() => { errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block'; });
    }
  </script>
</body>
</html>