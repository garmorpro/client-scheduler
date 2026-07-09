<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['theme']) || !in_array($data['theme'], ['light', 'dark'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid theme']);
    exit;
}

$theme = $data['theme'];
$_SESSION['theme'] = $theme;

// Update DB for the logged-in user
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET theme_mode = ? WHERE user_id = ?");
    $stmt->bind_param("si", $theme, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['status' => 'success']);