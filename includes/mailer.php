<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ─── CONFIGURE THESE ─────────────────────────────────────────────────────────
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_USERNAME', 'aestusreroll1@gmail.com');   // ← change
define('MAIL_PASSWORD', 'bter znpx tifz ticb');       // ← change (Gmail App Password)
define('MAIL_PORT',     587);
define('MAIL_FROM',     'aestusreroll1@gmail.com');    // ← change
define('MAIL_FROM_NAME','QCSim');
define('APP_URL',       'https://qc-sim.org/');  // ← change to your domain
// ─────────────────────────────────────────────────────────────────────────────

function sendConfirmationEmail($toEmail, $toName, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $confirmLink = APP_URL . '/verify.php?token=' . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Confirm your QCSim Account';
        $mail->Body    = "
        <div style='font-family:sans-serif;max-width:560px;margin:auto;padding:32px;'>
          <div style='display:flex;align-items:center;gap:10px;margin-bottom:24px;'>
            <img src='".APP_URL."/Assets/MainWebsite/logo.png' alt='QCSim' style='height:36px;'>
            <span style='font-size:20px;font-weight:700;color:#1a6bb5;'>QCSim</span>
          </div>
          <h2 style='color:#0d2d4e;'>Welcome, {$toName}!</h2>
          <p style='color:#444;line-height:1.6;'>Thank you for signing up for <strong>QCSim</strong> — your virtual laboratory for learning pharmaceutical quality control.</p>
          <p style='color:#444;line-height:1.6;'>Please confirm your email address by clicking the button below:</p>
          <a href='{$confirmLink}' style='display:inline-block;margin:20px 0;padding:14px 32px;background:#1a6bb5;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;'>Confirm My Account</a>
          <p style='color:#888;font-size:13px;'>If you did not create an account, you can safely ignore this email.</p>
          <p style='color:#bbb;font-size:12px;margin-top:32px;border-top:1px solid #eee;padding-top:16px;'>This link expires in 24 hours.</p>
        </div>";
        $mail->AltBody = "Confirm your QCSim account: {$confirmLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
