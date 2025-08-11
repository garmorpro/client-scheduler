<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$user_id = (int)$_GET['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role, status, created, last_active, mfa_enabled FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // Fetch last 3 activity logs for this user
    $activityStmt = $conn->prepare("SELECT description, created_at FROM system_activity_log WHERE user_id = ? ORDER BY created_at ASC LIMIT 3");
    $activityStmt->bind_param("i", $user_id);
    $activityStmt->execute();
    $activityResult = $activityStmt->get_result();

    $activities = [];
    while ($row = $activityResult->fetch_assoc()) {
        $activities[] = $row;
    }
    $activityStmt->close();

    $user['recent_activities'] = $activities;

    header('Content-Type: application/json');
    echo json_encode($user);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
}
$stmt->close();
$conn->close();
?>
