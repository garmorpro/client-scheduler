<?php
session_start();
require_once 'db.php';       // Adjust path if needed
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure PHP errors don't break JSON output
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Always return JSON
header('Content-Type: application/json');

// Only allow admin/manager
$role = strtolower($_SESSION['user_role'] ?? '');
if (!in_array($role, ['admin','manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);
$testEmail = filter_var($input['test_email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$testEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Fetch email settings from DB
function getEmailSettings($conn) {
    $sql = "SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'email'";
    $result = $conn->query($sql);
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

$settings = getEmailSettings($conn);

// Check if email notifications are enabled
if (empty($settings['enable_email_notifications']) || $settings['enable_email_notifications'] !== 'true') {
    echo json_encode(['success' => false, 'message' => 'Email notifications are disabled']);
    exit();
}

// Send test email using PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $settings['smtp_server'] ?? '';
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['smtp_username'] ?? '';
    $mail->Password   = $settings['smtp_password'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($settings['smtp_port'] ?? 587);

    $mail->setFrom($settings['sender_email'] ?? 'no-reply@example.com', $settings['sender_name'] ?? 'My Company');
    $mail->addAddress($testEmail);

    $mail->isHTML(true);
    $mail->Subject = "Test Email from Your Application";
    $mail->Body    = "<p>This is a test email to verify your SMTP settings.</p>";

    $mail->send();

    echo json_encode(['success' => true, 'message' => "Test email sent to {$testEmail}"]);
    exit(); // <--- important to stop any further output
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}"
    ]);
    exit(); // <--- important
}
