<?php
// ============================================================
// UKLOOLE Learning Hub - Shared Mailer
// Uses Resend SMTP (smtp.resend.com:587)
// ============================================================

$_pmCandidates = [
    __DIR__ . '/lib/PHPMailer/src/PHPMailer.php',
    __DIR__ . '/../cv-builder-app/backend/lib/PHPMailer/src/PHPMailer.php',
];
foreach ($_pmCandidates as $_pmPath) {
    if (file_exists($_pmPath)) {
        require_once dirname($_pmPath) . '/Exception.php';
        require_once dirname($_pmPath) . '/PHPMailer.php';
        require_once dirname($_pmPath) . '/SMTP.php';
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function lh_send_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): bool {

    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $headers = implode("\r\n", [
            'From: Ukloole <no-reply@mail.ukloole.com>',
            'Reply-To: learn@ukloole.com',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ]);
        return @mail($toEmail, $subject, $plainBody ?: strip_tags($htmlBody), $headers, '-f no-reply@mail.ukloole.com');
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host        = 'smtp.resend.com';
        $mail->Port        = 587;
        $mail->SMTPAuth    = true;
        $mail->Username    = 'resend';
        $mail->Password    = 're_XzNLPRof_F6g1D7xrgVngZUenHCpifdFp';
        $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAutoTLS = true;

        $mail->setFrom('no-reply@mail.ukloole.com', 'Ukloole');
        $mail->Sender = 'no-reply@mail.ukloole.com';
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo('learn@ukloole.com', 'Ukloole Support');
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Ukloole LH mailer] Failed to ' . $toEmail . ': ' . $mail->ErrorInfo);
        return false;
    }
}

function lh_email_template(string $title, string $bodyHtml): string {
    return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f2f5;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
    <tr><td align="center">
      <table width="540" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:10px;padding:40px;
                    box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <tr><td>
          <div style="text-align:center;margin-bottom:28px;">
            <span style="font-size:1.4rem;font-weight:800;color:#1a3c5e;">Ukloole</span>
          </div>
          <h2 style="color:#1a3c5e;margin:0 0 20px;font-size:1.3rem;">' . $title . '</h2>
          ' . $bodyHtml . '
          <hr style="border:none;border-top:1px solid #eee;margin:28px 0;">
          <p style="color:#aaa;font-size:12px;margin:0;text-align:center;">
            &mdash; Ukloole Learning Hub &bull;
            <a href="https://learn.ukloole.com" style="color:#aaa;">learn.ukloole.com</a>
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}