<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
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
$userIds = array_filter(array_map('intval', $input['user_ids'] ?? []));
$managerId = intval($input['manager_id'] ?? 0);

if (empty($userIds)) {
    echo json_encode(['success' => false, 'error' => 'No employees selected.']);
    exit();
}
if ($managerId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Please select a manager.']);
    exit();
}
if (in_array($managerId, $userIds, true)) {
    echo json_encode(['success' => false, 'error' => 'An employee cannot be their own manager.']);
    exit();
}

$mgrStmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$mgrStmt->bind_param('i', $managerId);
$mgrStmt->execute();
$mgr = $mgrStmt->get_result()->fetch_assoc();
$mgrStmt->close();

if (!$mgr || strtolower($mgr['role']) !== 'manager') {
    echo json_encode(['success' => false, 'error' => 'Selected user is not a manager.']);
    exit();
}

// Only staff/senior employees can be assigned a manager - silently skip anyone else in the selection.
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$types = str_repeat('i', count($userIds));
$eligibleStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id IN ($placeholders) AND role IN ('staff', 'senior')");
$eligibleStmt->bind_param($types, ...$userIds);
$eligibleStmt->execute();
$eligibleRes = $eligibleStmt->get_result();
$eligibleIds = [];
while ($row = $eligibleRes->fetch_assoc()) {
    $eligibleIds[] = (int) $row['user_id'];
}
$eligibleStmt->close();

if (empty($eligibleIds)) {
    echo json_encode(['success' => false, 'error' => 'None of the selected employees can be assigned a manager.']);
    exit();
}

$updatePlaceholders = implode(',', array_fill(0, count($eligibleIds), '?'));
$updateTypes = 'i' . str_repeat('i', count($eligibleIds));
$stmt = $conn->prepare("UPDATE users SET manager_id = ? WHERE user_id IN ($updatePlaceholders)");
$stmt->bind_param($updateTypes, $managerId, ...$eligibleIds);

if ($stmt->execute()) {
    $updatedCount = $stmt->affected_rows;
    $stmt->close();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    logActivity($conn, 'manager_assigned', $adminUserId, $adminEmail, $adminName, 'Manager Assigned', "Assigned a manager for $updatedCount employee(s)");

    echo json_encode(['success' => true, 'updatedCount' => $updatedCount]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not update manager assignment.']);
    $stmt->close();
}

$conn->close();
