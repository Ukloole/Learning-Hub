<?php
/**
 * UKLOOLE — Send Assessment Link
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo 'Method Not Allowed'; exit;
}

require_once __DIR__ . '/mailer.php';

$name  = htmlspecialchars(trim($_POST['name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$name || !$email) {
    http_response_code(400); echo 'error'; exit;
}

$subject   = 'Your Ukloole Assessment Link';
$plainBody = "Hello $name,\n\n"
           . "You're one step away from getting certified!\n\n"
           . "Click the link below to take your assessment:\n"
           . "https://learn.ukloole.com/assessment.html\n\n"
           . "Good luck!\n"
           . "— The Ukloole Team";

$htmlBody = lh_email_template('Your Assessment Link', '
  <p style="color:#444;line-height:1.7;">Hello <strong>' . $name . '</strong>,</p>
  <p style="color:#444;line-height:1.7;">
    You\'re one step away from getting certified! 🎓<br>
    Click the button below to take your Ukloole assessment.
  </p>
  <p style="text-align:center;margin:28px 0;">
    <a href="https://learn.ukloole.com/assessment.html"
       style="background:#1a3c5e;color:#fff;padding:14px 32px;border-radius:6px;
              text-decoration:none;font-weight:bold;font-size:15px;display:inline-block;">
      Take Assessment
    </a>
  </p>
  <p style="color:#888;font-size:13px;">Good luck! We\'re rooting for you.</p>
');

$sent = lh_send_email($email, $name, $subject, $htmlBody, $plainBody);
echo $sent ? 'success' : 'error';