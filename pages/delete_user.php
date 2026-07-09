<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

$deleteUserId = (int) $_POST['user_id'];

if ($deleteUserId === (int) $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account.']);
    exit();
}

$detailsStmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
if (!$detailsStmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}
$detailsStmt->bind_param('i', $deleteUserId);
$detailsStmt->execute();
$detailsStmt->bind_result($userFullName, $emailAddress);
if (!$detailsStmt->fetch()) {
    $detailsStmt->close();
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}
$detailsStmt->close();

$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param('i', $deleteUserId);

$currentUserId = $_SESSION['user_id'];
$currentUserEmail = $_SESSION['email'] ?? '';
$currentUserFullName = $_SESSION['full_name'] ?? '';

if ($stmt->execute()) {
    $title = "User Deleted";
    $description = "Deleted user $userFullName ($emailAddress).";

    logActivity($conn, "user_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => true]);
} else {
    $title = "Failed User Deletion";
    $description = "Failed to delete user $userFullName ($emailAddress).";

    logActivity($conn, "failed_user_deleted", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => false, 'error' => 'Failed to delete user']);
}
$stmt->close();
