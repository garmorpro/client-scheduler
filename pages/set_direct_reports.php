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
$managerId = intval($input['manager_id'] ?? 0);
$userIds = array_values(array_unique(array_filter(array_map('intval', $input['user_ids'] ?? []), fn($id) => $id > 0)));

if ($managerId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid manager.']);
    exit();
}

$mgrStmt = $conn->prepare("SELECT role, full_name FROM users WHERE user_id = ?");
$mgrStmt->bind_param('i', $managerId);
$mgrStmt->execute();
$mgr = $mgrStmt->get_result()->fetch_assoc();
$mgrStmt->close();

if (!$mgr || strtolower($mgr['role']) !== 'manager') {
    echo json_encode(['success' => false, 'error' => 'Selected user is not a manager.']);
    exit();
}

$conn->begin_transaction();
try {
    // Assign everyone checked to this manager.
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $conn->prepare("UPDATE users SET manager_id = ? WHERE user_id IN ($placeholders) AND role IN ('staff', 'senior')");
        $types = 'i' . str_repeat('i', count($userIds));
        $stmt->bind_param($types, $managerId, ...$userIds);
        $stmt->execute();
        $stmt->close();
    }

    // Unassign anyone previously reporting to this manager who was unchecked.
    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $conn->prepare("UPDATE users SET manager_id = NULL WHERE manager_id = ? AND user_id NOT IN ($placeholders)");
        $types = 'i' . str_repeat('i', count($userIds));
        $stmt->bind_param($types, $managerId, ...$userIds);
    } else {
        $stmt = $conn->prepare("UPDATE users SET manager_id = NULL WHERE manager_id = ?");
        $stmt->bind_param('i', $managerId);
    }
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    logActivity($conn, 'direct_reports_updated', $adminUserId, $adminEmail, $adminName, 'Direct Reports Updated', "Updated direct reports for {$mgr['full_name']}");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not update direct reports.']);
}

$conn->close();
