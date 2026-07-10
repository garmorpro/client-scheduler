<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

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

$currentUserId = (int) $_SESSION['user_id'];
$currentUserEmail = $_SESSION['email'] ?? '';
$currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

// Never allow self-delete via the bulk action, same as the single-delete path.
$skippedSelf = in_array($currentUserId, $userIds, true);
$userIds = array_values(array_filter($userIds, fn($id) => $id !== $currentUserId));

// Refuse to delete anyone who still has scheduled hours or time-off history -
// same reasoning as delete_user.php. Only the eligible IDs get deleted; the
// rest are reported back so nothing is silently skipped.
$skippedWithData = [];
$eligibleIds = [];
foreach ($userIds as $uid) {
    $depStmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM entries WHERE user_id = ?) AS entry_count,
            (SELECT COUNT(*) FROM time_off WHERE user_id = ?) AS timeoff_count
    ");
    $depStmt->bind_param('ii', $uid, $uid);
    $depStmt->execute();
    $depRow = $depStmt->get_result()->fetch_assoc();
    $depStmt->close();

    if ($depRow && ((int)$depRow['entry_count'] > 0 || (int)$depRow['timeoff_count'] > 0)) {
        $skippedWithData[] = $uid;
    } else {
        $eligibleIds[] = $uid;
    }
}

if (empty($eligibleIds)) {
    echo json_encode([
        'success' => false,
        'error' => 'None of the selected users could be deleted - they either have scheduled hours/time off, or you selected only yourself.',
        'skippedSelf' => $skippedSelf,
        'skippedWithData' => $skippedWithData
    ]);
    exit();
}

$placeholders = implode(',', array_fill(0, count($eligibleIds), '?'));
$sql = "DELETE FROM users WHERE user_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare error: ' . $conn->error]);
    exit();
}

$types = str_repeat('i', count($eligibleIds));
$stmt->bind_param($types, ...$eligibleIds);

if ($stmt->execute()) {
    $deletedCount = $stmt->affected_rows;

    $title = "Bulk User Delete";
    $description = "Deleted $deletedCount user(s).";
    logActivity($conn, "bulk_user_delete", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode([
        'success' => true,
        'deletedCount' => $deletedCount,
        'skippedSelf' => $skippedSelf,
        'skippedWithData' => $skippedWithData
    ]);
} else {
    $title = "Failed Bulk User Delete";
    $description = "Failed to bulk delete users.";
    logActivity($conn, "bulk_user_delete_failed", $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['success' => false, 'error' => 'Database execute error: ' . $stmt->error]);
}

$stmt->close();
