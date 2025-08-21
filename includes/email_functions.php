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

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
