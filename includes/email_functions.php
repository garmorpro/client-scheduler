<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'db.php'; // make sure $conn is available

function getEmailSettings($conn) {
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'email'";
    $result = $conn->query($sql);
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// AARC-360's brand logo, embedded (not linked) so it renders reliably in
// Outlook and other clients that don't reliably load external images or
// base64 data URIs in HTML email - PHPMailer's addEmbeddedImage() attaches
// it as a real MIME part referenced via cid:, the standard, well-supported
// way to put a logo in an email.
const AARC360_EMAIL_LOGO_PATH = __DIR__ . '/../assets/images/aarc360-logo.png';
const AARC360_EMAIL_LOGO_CID = 'aarc360logo';

/**
 * Wraps arbitrary inner HTML in AARC-360's branded email shell: a dark
 * teal header with the logo, a lime accent bar, a white content area, and
 * a footer with the firm's tagline. Table-based layout with inline styles
 * throughout, not CSS classes/flexbox/grid - email clients (especially
 * Outlook, which uses Word's rendering engine) don't reliably support
 * modern CSS or <style> blocks, so this deliberately uses the
 * lowest-common-denominator patterns email HTML actually needs.
 */
function wrapBrandedEmailHtml(string $innerHtml): string
{
    $logoImg = file_exists(AARC360_EMAIL_LOGO_PATH)
        ? '<img src="cid:' . AARC360_EMAIL_LOGO_CID . '" alt="AARC-360" width="160" style="display:block; max-width:160px; height:auto; border:0;">'
        : '<span style="color:#ffffff; font-family:Arial, Helvetica, sans-serif; font-size:20px; font-weight:700; letter-spacing:.02em;">AARC-360</span>';

    return <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f5; padding:32px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e3e7e5;">
        <tr>
          <td align="center" style="background-color:#003f47; padding:28px 24px;">
            {$logoImg}
          </td>
        </tr>
        <tr>
          <td style="background-color:#a3cc38; height:4px; line-height:4px; font-size:0;">&nbsp;</td>
        </tr>
        <tr>
          <td style="padding:32px 28px; font-family:Arial, Helvetica, sans-serif; color:#16211f; font-size:14.5px; line-height:1.6;">
            {$innerHtml}
          </td>
        </tr>
        <tr>
          <td style="padding:20px 28px; background-color:#f8f9f9; border-top:1px solid #e3e7e5; text-align:center;">
            <div style="font-family:Arial, Helvetica, sans-serif; font-size:11.5px; font-weight:700; color:#003f47; letter-spacing:.03em;">AARC-360</div>
            <div style="font-family:Arial, Helvetica, sans-serif; font-size:10.5px; color:#6b7570; margin-top:2px;">High-Quality, Value-Added Compliance Experience</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
HTML;
}

function sendEmail($to, $subject, $body, $conn) {
    $settings = getEmailSettings($conn);

    if (empty($settings['enable_email_notifications']) || $settings['enable_email_notifications'] !== 'true') {
        return false; // email notifications disabled
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_server'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'] ?? '';
        $mail->Password   = $settings['smtp_password'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $settings['smtp_port'] ?? 587;

        $mail->setFrom($settings['sender_email'] ?? 'no-reply@example.com', $settings['sender_name'] ?? 'My Company');
        $mail->addAddress($to);

        if (file_exists(AARC360_EMAIL_LOGO_PATH)) {
            $mail->addEmbeddedImage(AARC360_EMAIL_LOGO_PATH, AARC360_EMAIL_LOGO_CID);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = wrapBrandedEmailHtml($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
