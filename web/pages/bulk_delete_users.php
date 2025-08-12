<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Simple authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['user_ids']) || !is_array($input['user_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Sanitize input user IDs as integers
$userIds = array_filter(array_map('intval', $input['user_ids']));
if (empty($userIds)) {
    echo json_encode(['success' => false, 'error' => 'No valid user IDs provided']);
    exit();
}

// Prepare placeholders for prepared statement
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$sql = "DELETE FROM users WHERE user_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit();
}

// Bind parameters dynamically
$types = str_repeat('i', count($userIds));
$stmt->bind_param($types, ...$userIds);

$currentUserId = $_SESSION['user_id'];
$currentUserEmail = $_SESSION['email'] ?? '';
$currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

if ($stmt->execute()) {
    $deletedCount = $stmt->affected_rows;

    // Log success activity
    $title = "Successful Bulk User Delete";
    $description = "Successfully deleted $deletedCount user(s) by {$currentUserFullName} (ID: $currentUserId).";
    logActivity($conn, "bulk_user_delete_success", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => true, 'deletedCount' => $deletedCount]);
} else {
    // Log failure activity
    $title = "Failed Bulk User Delete";
    $description = "Failed to bulk delete users.";
    logActivity($conn, "bulk_user_delete_failed", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => false, 'error' => 'Database execute error: ' . $stmt->error]);
}

$stmt->close();
?>
