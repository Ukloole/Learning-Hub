<?php
/**
 * UKLOOLE — Community Member Dashboard v4
 * Threaded Q&A, Updates, Job Links, Webinars, Resources, DFY
 */
session_start();
if (empty($_SESSION['community_member_id'])) { header('Location: community-login.php'); exit; }
$member_id   = intval($_SESSION['community_member_id']);
$member_name = htmlspecialchars($_SESSION['community_member_name'] ?? 'Member');
if (isset($_GET['logout'])) { session_destroy(); header('Location: community-login.php'); exit; }

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'ukloolec_lbuser';
$db_pass = getenv('DB_PASS') ?: 'Admin4ukloole';
$db_name = getenv('DB_NAME') ?: 'ukloolec_learninghub';
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn || $conn->connect_error) die('Server error. Please refresh.');
$conn->set_charset('utf8mb4');

// Updates
$updates = [];
$ur = $conn->query("SELECT * FROM community_updates WHERE is_active=1 ORDER BY created_at DESC LIMIT 20");
if ($ur) while ($r = $ur->fetch_assoc()) $updates[] = $r;

// Job links
$job_links = [];
$jl = $conn->query("SELECT id,title,expires_at FROM community_job_links WHERE is_active=1 AND (expires_at IS NULL OR expires_at>=CURDATE()) ORDER BY sort_order ASC,created_at DESC");
while ($row = $jl->fetch_assoc()) {
    $token = hash_hmac('sha256', $row['id'].$member_id.date('Y-m-d'), 'ukloole_secret_2026');
    $stmt = $conn->prepare("INSERT IGNORE INTO community_link_tokens (token,job_link_id) VALUES (?,?)");
    $stmt->bind_param("si",$token,$row['id']); $stmt->execute(); $stmt->close();
    $row['token']=$token; $job_links[]=$row;
}

// Webinars
$webinars = [];
$wb = $conn->query("SELECT * FROM community_webinars WHERE is_active=1 ORDER BY event_date ASC,created_at DESC LIMIT 5");
while ($r = $wb->fetch_assoc()) $webinars[] = $r;

// Threads (this member's conversations)
$threads = [];
$stmt = $conn->prepare("SELECT t.*, (SELECT COUNT(*) FROM community_thread_messages m WHERE m.thread_id=t.id) AS msg_count, (SELECT body FROM community_thread_messages m WHERE m.thread_id=t.id ORDER BY m.created_at DESC LIMIT 1) AS last_msg, (SELECT sender FROM community_thread_messages m WHERE m.thread_id=t.id ORDER BY m.created_at DESC LIMIT 1) AS last_sender FROM community_threads t WHERE t.member_id=? ORDER BY t.updated_at DESC");
$stmt->bind_param("i",$member_id); $stmt->execute();
$tr = $stmt->get_result(); while ($r=$tr->fetch_assoc()) $threads[]=$r; $stmt->close();

// Load messages for open thread (if ?thread=ID)
$open_thread = null; $thread_messages = [];
if (!empty($_GET['thread'])) {
    $tid = intval($_GET['thread']);
    $stmt = $conn->prepare("SELECT * FROM community_threads WHERE id=? AND member_id=?");
    $stmt->bind_param("ii",$tid,$member_id); $stmt->execute();
    $open_thread = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($open_thread) {
        $stmt = $conn->prepare("SELECT * FROM community_thread_messages WHERE thread_id=? ORDER BY created_at ASC");
        $stmt->bind_param("i",$tid); $stmt->execute();
        $mr = $stmt->get_result(); while ($r=$mr->fetch_assoc()) $thread_messages[]=$r; $stmt->close();
    }
}

// Resources
$resources = [];
$rr = $conn->query("SELECT title,description,url FROM community_resources WHERE is_active=1 ORDER BY sort_order ASC,created_at DESC");
while ($r=$rr->fetch_assoc()) $resources[]=$r;

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
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>My Guidance Area — Ukloole</title>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    :root{--navy:#0D1B3E;--navy-mid:#1A2D5A;--gold:#C9A84C;--gold-pale:#FFF8E7;--bg:#F0F4FA;--card:#fff;--border:#E2E8F0;--muted:#6B7A99;--success:#16a34a;}
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--navy);}
    a{text-decoration:none;color:inherit;}
    .navbar{background:white;border-bottom:1px solid var(--border);padding:0 24px;height:68px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(13,27,62,.06);}
    .nb-brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:1.2rem;color:var(--navy);}
    .nb-brand img{width:34px;}
    .nb-right{display:flex;align-items:center;gap:16px;}
    .nb-name{font-size:.88rem;color:var(--muted);}
    .btn-logout{padding:8px 16px;border:1px solid var(--border);background:white;border-radius:8px;font-size:.84rem;color:var(--muted);cursor:pointer;font-family:'DM Sans',sans-serif;transition:.15s;}
    .btn-logout:hover{border-color:#dc2626;color:#dc2626;}
    .hero{background:linear-gradient(135deg,var(--navy),#1A2D5A);padding:50px 24px;text-align:center;color:white;}
    .hero-sub{color:rgba(255,255,255,.5);font-size:.88rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
    .hero h1{font-family:'EB Garamond',serif;font-size:2.4rem;}
    .hero p{color:rgba(255,255,255,.7);margin-top:10px;font-size:1rem;}
    .badge-member{display:inline-block;background:rgba(201,168,76,.15);color:#92400e;border:1px solid rgba(201,168,76,.3);border-radius:20px;padding:4px 14px;font-size:.78rem;font-weight:700;letter-spacing:.04em;margin-top:10px;}
    .main{max-width:1080px;margin:0 auto;padding:40px 24px;}
    .section-title{font-family:'EB Garamond',serif;font-size:1.4rem;margin-bottom:16px;color:var(--navy);display:flex;align-items:center;gap:10px;}
    .section-title i{color:var(--gold);font-size:1.1rem;}
    .card{background:var(--card);border-radius:16px;border:1px solid var(--border);padding:24px;box-shadow:0 4px 20px rgba(13,27,62,.06);margin-bottom:24px;}
    .btn{display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border:none;border-radius:9px;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.88rem;cursor:pointer;transition:.15s;text-decoration:none;}
    .btn-navy{background:var(--navy);color:white;} .btn-navy:hover{background:var(--navy-mid);}
    .btn-gold{background:var(--gold-pale);color:var(--navy);border:1px solid rgba(201,168,76,.4);} .btn-gold:hover{background:rgba(201,168,76,.25);}
    /* UPDATES */
    .updates-list{display:flex;flex-direction:column;gap:14px;}
    .update-item{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:18px 20px;border-left:4px solid var(--navy);}
    .update-item.opportunity{border-left-color:var(--gold);}
    .update-item.announcement{border-left-color:var(--success);}
    .update-tag{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;color:var(--muted);}
    .update-title{font-weight:700;font-size:.98rem;color:var(--navy);margin-bottom:6px;}
    .update-body{font-size:.88rem;color:var(--muted);line-height:1.7;white-space:pre-wrap;}
    .update-date{font-size:.73rem;color:var(--muted);margin-top:8px;}
    /* JOBS */
    .job-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;}
    .job-item{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:10px;}
    .job-item-title{font-weight:700;font-size:.92rem;color:var(--navy);}
    .job-open-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:10px 16px;background:linear-gradient(135deg,var(--navy),#1A2D5A);color:white;border:none;border-radius:9px;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.88rem;cursor:pointer;width:100%;transition:.15s;user-select:none;}
    .job-open-btn:hover{background:linear-gradient(135deg,var(--navy-mid),var(--gold));}
    /* WEBINARS */
    .webinar-list{display:flex;flex-direction:column;gap:12px;}
    .webinar-item{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
    .webinar-info{flex:1;min-width:180px;}
    .webinar-title{font-weight:700;font-size:.95rem;color:var(--navy);}
    .webinar-desc{font-size:.82rem;color:var(--muted);margin-top:3px;}
    .webinar-date{font-size:.78rem;color:var(--gold);font-weight:700;margin-top:4px;}
    /* THREADS */
    .thread-list{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
    .thread-item{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:14px 18px;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:space-between;gap:12px;}
    .thread-item:hover{border-color:var(--gold);background:var(--gold-pale);}
    .thread-item.has-reply{border-left:3px solid var(--success);}
    .thread-subject{font-weight:700;font-size:.92rem;color:var(--navy);}
    .thread-preview{font-size:.78rem;color:var(--muted);margin-top:3px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:300px;}
    .thread-meta{font-size:.72rem;color:var(--muted);text-align:right;flex-shrink:0;}
    .thread-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.7rem;font-weight:700;}
    .tb-open{background:rgba(217,119,6,.1);color:#b45309;}
    .tb-answered{background:rgba(22,163,74,.1);color:#15803d;}
    /* CHAT VIEW */
    .chat-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border);}
    .chat-back{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:.84rem;font-weight:700;cursor:pointer;color:var(--navy);font-family:'DM Sans',sans-serif;transition:.15s;}
    .chat-back:hover{background:var(--border);}
    .chat-subject{font-family:'EB Garamond',serif;font-size:1.2rem;color:var(--navy);}
    .messages{display:flex;flex-direction:column;gap:12px;margin-bottom:20px;max-height:420px;overflow-y:auto;padding:4px 0;}
    .msg{max-width:80%;display:flex;flex-direction:column;gap:4px;}
    .msg.member{align-self:flex-end;align-items:flex-end;}
    .msg.admin{align-self:flex-start;align-items:flex-start;}
    .msg-bubble{padding:12px 16px;border-radius:14px;font-size:.9rem;line-height:1.6;position:relative;}
    .msg.member .msg-bubble{background:var(--navy);color:white;border-bottom-right-radius:4px;}
    .msg.admin .msg-bubble{background:white;border:1px solid var(--border);color:var(--navy);border-bottom-left-radius:4px;}
    .msg-meta{font-size:.7rem;color:var(--muted);display:flex;align-items:center;gap:8px;}
    .msg-edit-btn{background:none;border:none;cursor:pointer;color:rgba(255,255,255,.5);font-size:.7rem;padding:0 4px;font-family:'DM Sans',sans-serif;}
    .msg-edit-btn:hover{color:white;}
    .msg-edited{font-size:.68rem;color:rgba(255,255,255,.5);font-style:italic;}
    .reply-form{display:flex;gap:10px;align-items:flex-end;}
    .reply-input{flex:1;padding:12px 16px;border:2px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.92rem;resize:vertical;min-height:60px;max-height:120px;outline:none;transition:border-color .2s;}
    .reply-input:focus{border-color:var(--gold);}
    .reply-send{padding:12px 18px;background:var(--navy);color:white;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.88rem;cursor:pointer;flex-shrink:0;transition:.15s;}
    .reply-send:hover{background:var(--navy-mid);}
    /* NEW THREAD FORM */
    .new-thread-form{background:var(--bg);border-radius:12px;padding:20px;border:1px solid var(--border);}
    .nt-input{width:100%;padding:11px 14px;border:2px solid var(--border);border-radius:9px;font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;margin-bottom:10px;}
    .nt-input:focus{border-color:var(--gold);}
    .qa-alert{padding:12px 16px;border-radius:9px;font-size:.87rem;margin-top:10px;display:none;}
    .qa-alert.success{background:#f0fdf4;border:1px solid #86efac;color:#15803d;display:block;}
    .qa-alert.error{background:#fef2f2;border:1px solid #fca5a5;color:#dc2626;display:block;}
    /* RESOURCES */
    .res-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;}
    .res-item{background:var(--bg);border-radius:10px;padding:14px;border:1px solid var(--border);display:flex;flex-direction:column;gap:8px;}
    .res-item-title{font-size:.9rem;font-weight:700;color:var(--navy);}
    .res-item-desc{font-size:.78rem;color:var(--muted);line-height:1.5;}
    /* CONTACT */
    .contact-box{background:linear-gradient(135deg,rgba(13,27,62,.05),rgba(201,168,76,.08));border:1px solid rgba(201,168,76,.3);border-radius:14px;padding:24px;text-align:center;}
    .contact-box h3{font-family:'EB Garamond',serif;font-size:1.4rem;margin-bottom:8px;}
    .contact-box p{color:var(--muted);font-size:.9rem;margin-bottom:16px;}
    .contact-links{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
    .empty{text-align:center;padding:28px;color:var(--muted);font-size:.88rem;}
    .empty i{display:block;font-size:2rem;margin-bottom:10px;opacity:.3;}
    @media(max-width:640px){
      .nb-name{display:none;}
      .webinar-item{flex-direction:column;align-items:flex-start;}
      .hero h1{font-size:1.8rem;}
      .main{padding:20px 16px;}
      .card{padding:18px;}
      .msg{max-width:92%;}
      .thread-preview{max-width:160px;}
    }
  </style>
</head>
<body>

<nav class="navbar">
  <a href="/" class="nb-brand"><img src="/logo.png" alt="Ukloole"><span>Ukloole</span></a>
  <div class="nb-right">
    <span class="nb-name">Hello, <?= $member_name ?></span>
    <a href="?logout=1" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>

<div class="hero">
  <div class="hero-sub">Private Community</div>
  <h1>Welcome back, <?= $member_name ?></h1>
  <p>Your exclusive guidance area — updates, job links, Q&amp;A, webinars, and resources.</p>
  <span class="badge-member">&#11088; Premium Member</span>
</div>

<div class="main">

  <?php if (!empty($updates)): ?>
  <div class="card">
    <h2 class="section-title"><i class="fas fa-bullhorn"></i> Updates &amp; Opportunities</h2>
    <div class="updates-list">
      <?php foreach ($updates as $upd):
        $tm=['update'=>['&#128226; Update','update'],'opportunity'=>['&#128188; Opportunity','opportunity'],'announcement'=>['&#128227; Announcement','announcement']];
        $tag=$tm[$upd['type']]??['Update','update'];
      ?>
      <div class="update-item <?= $tag[1] ?>">
        <div class="update-tag"><?= $tag[0] ?></div>
        <div class="update-title"><?= htmlspecialchars($upd['title']) ?></div>
        <div class="update-body"><?= htmlspecialchars($upd['body']) ?></div>
        <div class="update-date"><i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($upd['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <h2 class="section-title"><i class="fas fa-briefcase"></i> This Week's Job Links</h2>
    <?php if (empty($job_links)): ?>
      <div class="empty"><i class="fas fa-briefcase"></i> New job links will appear here each week.</div>
    <?php else: ?>
      <div class="job-grid">
        <?php foreach ($job_links as $job): ?>
        <div class="job-item">
          <div class="job-item-title"><?= htmlspecialchars($job['title']) ?></div>
          <?php if ($job['expires_at']): ?><div style="font-size:.75rem;color:var(--muted);"><i class="fas fa-clock"></i> Expires <?= date('M j, Y', strtotime($job['expires_at'])) ?></div><?php endif; ?>
          <button class="job-open-btn" data-token="<?= htmlspecialchars($job['token']) ?>" onclick="openJobLink(this)" oncontextmenu="return false"><i class="fas fa-external-link-alt"></i> Open Job</button>
        </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:.75rem;color:var(--muted);margin-top:14px;"><i class="fas fa-shield-halved"></i> For Premium members only. Please do not share.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2 class="section-title"><i class="fas fa-video"></i> Webinars &amp; Live Sessions</h2>
    <?php if (empty($webinars)): ?>
      <div class="empty"><i class="fas fa-video"></i> No upcoming sessions right now.</div>
    <?php else: ?>
      <div class="webinar-list">
        <?php foreach ($webinars as $wb): ?>
        <div class="webinar-item">
          <div class="webinar-info">
            <div class="webinar-title"><?= htmlspecialchars($wb['title']) ?></div>
            <?php if ($wb['description']): ?><div class="webinar-desc"><?= htmlspecialchars($wb['description']) ?></div><?php endif; ?>
            <?php if ($wb['event_date']): ?><div class="webinar-date"><i class="fas fa-calendar"></i> <?= date('D, M j Y \a\t g:ia', strtotime($wb['event_date'])) ?></div><?php endif; ?>
          </div>
          <a href="<?= htmlspecialchars($wb['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-navy"><i class="fas fa-video"></i> Join Session</a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Q&A THREADS -->
  <div class="card" id="qa-section">
    <h2 class="section-title"><i class="fas fa-comments"></i> Q&amp;A</h2>

    <?php if ($open_thread): ?>
    <!-- ---- OPEN THREAD VIEW ---- -->
    <div class="chat-header">
      <button class="chat-back" onclick="window.location.href='community-dashboard.php#qa-section'"><i class="fas fa-arrow-left"></i> Back</button>
      <span class="chat-subject"><?= htmlspecialchars($open_thread['subject']) ?></span>
    </div>
    <div class="messages" id="messages-box">
      <?php foreach ($thread_messages as $msg): ?>
      <div class="msg <?= $msg['sender'] ?>" id="msg-<?= $msg['id'] ?>">
        <div class="msg-bubble">
          <span id="msg-body-<?= $msg['id'] ?>"><?= nl2br(htmlspecialchars($msg['body'])) ?></span>
          <?php if ($msg['edited_at']): ?><span class="msg-edited"> (edited)</span><?php endif; ?>
          <?php if ($msg['sender'] === 'member'): ?>
            <button class="msg-edit-btn" onclick="startEdit(<?= $msg['id'] ?>, <?= time() - strtotime($msg['created_at']) ?>)" title="Edit (3 min window)"><i class="fas fa-pen"></i></button>
          <?php endif; ?>
        </div>
        <div class="msg-meta">
          <?= $msg['sender'] === 'admin' ? '<i class="fas fa-headset"></i> Ukloole' : 'You' ?>
          &bull; <?= date('M j, g:ia', strtotime($msg['created_at'])) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="reply-form">
      <textarea class="reply-input" id="reply-input" placeholder="Type your reply..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendReply();}"></textarea>
      <button class="reply-send" onclick="sendReply()"><i class="fas fa-paper-plane"></i></button>
    </div>
    <div class="qa-alert" id="reply-alert"></div>

    <?php else: ?>
    <!-- ---- THREAD LIST + NEW THREAD ---- -->
    <?php if (!empty($threads)): ?>
    <div class="thread-list">
      <?php foreach ($threads as $t):
        $has_reply = false;
        foreach ($thread_messages as $tm) {} // not loaded here, use last_sender
        $answered = $t['last_sender'] === 'admin';
      ?>
      <div class="thread-item <?= $answered ? 'has-reply' : '' ?>" onclick="window.location.href='community-dashboard.php?thread=<?= $t['id'] ?>#qa-section'">
        <div style="flex:1;min-width:0;">
          <div class="thread-subject"><?= htmlspecialchars($t['subject']) ?></div>
          <div class="thread-preview"><?= htmlspecialchars($t['last_msg'] ?? '') ?></div>
        </div>
        <div class="thread-meta">
          <span class="thread-badge <?= $answered ? 'tb-answered' : 'tb-open' ?>"><?= $answered ? 'Replied' : 'Awaiting reply' ?></span>
          <div style="margin-top:4px;"><?= date('M j', strtotime($t['updated_at'])) ?></div>
          <div style="margin-top:2px;"><?= $t['msg_count'] ?> message<?= $t['msg_count']!=1?'s':'' ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p style="font-size:.88rem;color:var(--muted);margin-bottom:14px;">Ask a new question — we reply within 24–48 hours.</p>
    <div class="new-thread-form">
      <input type="text" class="nt-input" id="nt-subject" placeholder="Subject — e.g. How do I tailor my CV for remote jobs?">
      <textarea class="nt-input" id="nt-body" style="min-height:80px;resize:vertical;" placeholder="Describe your question in detail..."></textarea>
      <button class="btn btn-navy" onclick="startThread()"><i class="fas fa-paper-plane"></i> Submit Question</button>
    </div>
    <div class="qa-alert" id="nt-alert"></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($resources)): ?>
  <div class="card">
    <h2 class="section-title"><i class="fas fa-folder-open"></i> Your Premium Resources</h2>
    <div class="res-grid">
      <?php foreach ($resources as $r): ?>
      <div class="res-item">
        <div class="res-item-title"><i class="fas fa-file-alt" style="color:var(--gold);margin-right:6px;"></i><?= htmlspecialchars($r['title']) ?></div>
        <?php if ($r['description']): ?><div class="res-item-desc"><?= htmlspecialchars($r['description']) ?></div><?php endif; ?>
        <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-gold" style="margin-top:4px;justify-content:center;"><i class="fas fa-external-link-alt"></i> Access</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card" style="background:linear-gradient(135deg,var(--navy),#1A2D5A);color:white;text-align:center;">
    <h2 style="font-family:'EB Garamond',serif;font-size:1.8rem;margin-bottom:10px;">&#127891; Get Your Certificate</h2>
    <p style="color:rgba(255,255,255,.7);margin-bottom:20px;">Complete the assessment, pay &#8358;10,000 and receive your verifiable Ukloole Certificate instantly.</p>
    <a href="#" class="btn" style="background:var(--gold);color:var(--navy);padding:14px 32px;font-size:1rem;" onclick="openAssessmentModal(event)"><i class="fas fa-certificate"></i> Start Assessment &amp; Get Certified</a>
  </div>

  <div class="contact-box">
    <h3>Need Personal Help?</h3>
    <p>We're here every step of the way.</p>
    <div class="contact-links">
      <a href="mailto:learn@ukloole.com" class="btn btn-navy"><i class="fas fa-envelope"></i> learn@ukloole.com</a>
      <a href="https://wa.me/message/IQZ6JVHJGNETE1" target="_blank" class="btn btn-gold"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
    </div>
  </div>

</div>

<!-- ASSESSMENT MODAL -->
<div id="assess-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center;padding:16px;">
  <div style="background:white;border-radius:20px;padding:36px;max-width:460px;width:100%;">
    <h2 style="font-family:'EB Garamond',serif;font-size:1.6rem;color:var(--navy);margin-bottom:8px;">Start Certification Assessment</h2>
    <p style="color:var(--muted);font-size:.9rem;margin-bottom:20px;">Enter your details to receive your assessment link via email.</p>
    <input type="text" id="assess-name" placeholder="Full Name" style="width:100%;padding:12px 16px;border:2px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.92rem;margin-bottom:12px;outline:none;">
    <input type="email" id="assess-email" placeholder="Email Address" style="width:100%;padding:12px 16px;border:2px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:.92rem;margin-bottom:16px;outline:none;">
    <div style="display:flex;gap:10px;">
      <button class="btn btn-navy" style="flex:1;justify-content:center;" onclick="sendAssessmentFromDash()"><i class="fas fa-envelope"></i> Send Assessment Link</button>
      <button class="btn" style="background:var(--bg);color:var(--muted);border:1px solid var(--border);" onclick="document.getElementById('assess-modal').style.display='none'">Cancel</button>
    </div>
  </div>
</div>

<script>
const THREAD_ID = <?= $open_thread ? $open_thread['id'] : 'null' ?>;

function openJobLink(btn){const t=btn.getAttribute('data-token');if(!t)return;window.open('community-redirect.php?t='+encodeURIComponent(t),'_blank','noopener,noreferrer');}

// ---- New thread ----
function startThread(){
  const subj=document.getElementById('nt-subject').value.trim();
  const body=document.getElementById('nt-body').value.trim();
  if(!subj||!body){showAlert('nt-alert','Please enter a subject and your question.','error');return;}
  fetch('community-thread-reply.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'new_thread',subject:subj,body})})
  .then(r=>r.json()).then(d=>{
    if(d.ok){window.location.href='community-dashboard.php?thread='+d.thread_id+'#qa-section';}
    else showAlert('nt-alert',d.error||'Error.','error');
  }).catch(()=>showAlert('nt-alert','Network error.','error'));
}

// ---- Reply ----
function sendReply(){
  const body=document.getElementById('reply-input').value.trim();
  if(!body)return;
  fetch('community-thread-reply.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'reply',thread_id:THREAD_ID,body})})
  .then(r=>r.json()).then(d=>{
    if(d.ok){location.reload();}
    else showAlert('reply-alert',d.error||'Error.','error');
  }).catch(()=>showAlert('reply-alert','Network error.','error'));
}

// ---- Edit message (3-min window) ----
function startEdit(msgId, ageSeconds){
  if(ageSeconds>180){alert('Sorry, the 3-minute edit window has expired.');return;}
  const bodyEl=document.getElementById('msg-body-'+msgId);
  const currentText=bodyEl.innerText;
  bodyEl.innerHTML='<textarea id="edit-ta-'+msgId+'" style="width:100%;padding:8px;border:1px solid rgba(255,255,255,.3);border-radius:6px;background:rgba(255,255,255,.1);color:white;font-family:DM Sans,sans-serif;font-size:.88rem;min-height:60px;outline:none;resize:vertical;">'+currentText+'</textarea><button onclick="submitEdit('+msgId+')" style="margin-top:6px;padding:5px 12px;background:var(--gold);color:var(--navy);border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:.8rem;">Save</button>';
}
function submitEdit(msgId){
  const body=document.getElementById('edit-ta-'+msgId).value.trim();
  if(!body)return;
  fetch('community-thread-reply.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'edit_message',message_id:msgId,body})})
  .then(r=>r.json()).then(d=>{
    if(d.ok)location.reload();
    else alert(d.error||'Could not edit.');
  });
}

// ---- Assessment ----
function openAssessmentModal(e){e.preventDefault();document.getElementById('assess-modal').style.display='flex';}
function sendAssessmentFromDash(){
  const name=document.getElementById('assess-name').value.trim();const email=document.getElementById('assess-email').value.trim();
  if(!name||!email){alert('Please fill in your name and email.');return;}
  fetch('send-assessment.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'name='+encodeURIComponent(name)+'&email='+encodeURIComponent(email)})
  .then(r=>r.text()).then(d=>{
    if(d.trim()==='success'){alert('Assessment link sent! Check your email.');document.getElementById('assess-modal').style.display='none';}
    else alert('Could not send. Please contact us directly.');
  });
}

function showAlert(id,msg,type){const el=document.getElementById(id);el.textContent=msg;el.className='qa-alert '+type;setTimeout(()=>{el.className='qa-alert';},5000);}

// Scroll messages to bottom
const mb=document.getElementById('messages-box');if(mb)mb.scrollTop=mb.scrollHeight;
</script>
</body>
</html>
