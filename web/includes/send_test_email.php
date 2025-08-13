<?php
// Always start session
session_start();

// Force JSON output
header('Content-Type: application/json');

// Suppress HTML warnings/notices (important for JSON parsing)
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0);

// Include DB and PHPMailer
require_once 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Make sure user role is set and allowed
$role = strtolower($_SESSION['user_role'] ?? '');
if (!in_array($role, ['admin','manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$testEmail = filter_var($input['test_email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$testEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Fetch email settings from DB
function getEmailSettings($conn) {
    $settings = [];
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'email'";
    $result = $conn->query($sql);
    if (!$result) return $settings; // prevent fatal error if query fails

    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

$settings = getEmailSettings($conn);

// Check if notifications are enabled
if (empty($settings['enable_email_notifications']) || $settings['enable_email_notifications'] !== 'true') {
    echo json_encode(['success' => false, 'message' => 'Email notifications are disabled']);
    exit();
}

// Send test email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_server'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_username'] ?? '';
    $mail->Password   = $settings['smtp_password'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $settings['smtp_port'] ?? 587;

    $mail->setFrom($settings['sender_email'] ?? 'no-reply@example.com', $settings['sender_name'] ?? 'AARC-360');
    $mail->addAddress($testEmail);

    $mail->isHTML(true);
    $mail->Subject = "Test Email from AARC-360";
    $mail->Body    = "<p>This is a test email to verify your SMTP settings.</p>";

    $mail->send();

    echo json_encode(['success' => true, 'message' => "Test email sent to {$testEmail}"]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"]);
}
exit();
