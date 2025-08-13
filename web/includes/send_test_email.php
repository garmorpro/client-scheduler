<?php
session_start();

// Force JSON output
header('Content-Type: application/json');

// Log all errors instead of outputting them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

// Include DB and PHPMailer
require_once 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Verify user role
    $role = strtolower($_SESSION['user_role'] ?? '');
    if (!in_array($role, ['admin','manager'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $testEmail = filter_var($input['test_email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$testEmail) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }

    // Fetch email settings
    $settings = [];
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'email'";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    if (empty($settings['enable_email_notifications']) || $settings['enable_email_notifications'] !== 'true') {
        echo json_encode(['success' => false, 'message' => 'Email notifications are disabled']);
        exit();
    }

    // PHPMailer
    $mail = new PHPMailer(true);
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
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal Server Error: ' . $e->getMessage()]);
    error_log("send_test_email.php error: " . $e->getMessage());
}
exit();

