<?php
require_once '../includes/db.php';
session_start();

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