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

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['engagement_ids']) || !is_array($input['engagement_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Sanitize input engagement IDs as integers
$engagementIds = array_filter(array_map('intval', $input['engagement_ids']));
if (empty($engagementIds)) {
    echo json_encode(['success' => false, 'error' => 'No valid engagement IDs provided']);
    exit();
}

$placeholders = implode(',', array_fill(0, count($engagementIds), '?'));
$types = str_repeat('i', count($engagementIds));

$conn->begin_transaction();

try {
    // Step 1: Delete related entries first
    $sqlEntries = "DELETE FROM entries WHERE engagement_id IN ($placeholders)";
    $stmtEntries = $conn->prepare($sqlEntries);
    if (!$stmtEntries) throw new Exception("Prepare failed for entries delete: " . $conn->error);
    $stmtEntries->bind_param($types, ...$engagementIds);
    if (!$stmtEntries->execute()) throw new Exception("Execute failed for entries delete: " . $stmtEntries->error);
    $stmtEntries->close();

    // Step 2: Delete engagements
    $sqlEngagements = "DELETE FROM engagements WHERE engagement_id IN ($placeholders)";
    $stmtEngagements = $conn->prepare($sqlEngagements);
    if (!$stmtEngagements) throw new Exception("Prepare failed for engagements delete: " . $conn->error);
    $stmtEngagements->bind_param($types, ...$engagementIds);
    if (!$stmtEngagements->execute()) throw new Exception("Execute failed for engagements delete: " . $stmtEngagements->error);
    $deletedCount = $stmtEngagements->affected_rows;
    $stmtEngagements->close();

    $conn->commit();

    // Log success
    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $title = "Bulk Engagement Delete";
    $description = "Deleted $deletedCount engagement(s) and their entries.";
    logActivity($conn, "bulk_engagement_delete", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => true, 'deletedCount' => $deletedCount]);
} catch (Exception $e) {
    $conn->rollback();

    // Log failure
    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    $title = "Failed Bulk Engagement Delete";
    $description = "Failed to bulk delete engagements.";
    logActivity($conn, "bulk_engagement_delete_failed", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
