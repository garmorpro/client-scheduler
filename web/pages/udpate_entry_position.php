<?php
// update_entry_position.php
// AJAX endpoint to update an entry's user_id and week_start when dragged/dropped.

require_once '../includes/db.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

// Basic auth check - only admins can move entries
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$entry_id = isset($input['entry_id']) ? intval($input['entry_id']) : 0;
$target_user_id = isset($input['target_user_id']) ? intval($input['target_user_id']) : 0;
$target_week_start = isset($input['target_week_start']) ? trim($input['target_week_start']) : '';

if ($entry_id <= 0 || $target_user_id <= 0 || empty($target_week_start)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate date format (expect YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_week_start) || strtotime($target_week_start) === false) {
    echo json_encode(['success' => false, 'error' => 'Invalid date format for week_start']);
    exit;
}

$target_week_start = date('Y-m-d', strtotime($target_week_start));

// Prepare update
$updateStmt = $conn->prepare("UPDATE entries SET user_id = ?, week_start = ? WHERE entry_id = ?");
if (!$updateStmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$updateStmt->bind_param('isi', $target_user_id, $target_week_start, $entry_id);
$ok = $updateStmt->execute();

if ($ok === false) {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $updateStmt->error]);
    $updateStmt->close();
    exit;
}

// Optionally check affected_rows (0 can mean no change or not found)
if ($updateStmt->affected_rows < 0) {
    echo json_encode(['success' => false, 'error' => 'No rows updated']);
    $updateStmt->close();
    exit;
}
$updateStmt->close();

// Fetch updated entry to return to client (optional)
$getStmt = $conn->prepare("SELECT entry_id, user_id, week_start, engagement_id, assigned_hours FROM entries WHERE entry_id = ?");
if ($getStmt) {
    $getStmt->bind_param('i', $entry_id);
    $getStmt->execute();
    $res = $getStmt->get_result();
    $row = $res->fetch_assoc();
    $getStmt->close();

    echo json_encode(['success' => true, 'entry' => $row]);
    exit;
} else {
    // If select fails, still return success
    echo json_encode(['success' => true]);
    exit;
}
