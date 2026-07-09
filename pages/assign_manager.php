<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$userId = intval($_POST['user_id'] ?? 0);
$managerIdRaw = trim($_POST['manager_id'] ?? '');
$managerId = $managerIdRaw === '' ? null : intval($managerIdRaw);

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user.']);
    exit();
}

$userStmt = $conn->prepare("SELECT full_name, role FROM users WHERE user_id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found.']);
    exit();
}

if (!in_array(strtolower($user['role']), ['staff', 'senior'], true)) {
    echo json_encode(['success' => false, 'error' => 'Only staff and senior employees can be assigned a manager.']);
    exit();
}

if ($managerId !== null) {
    if ($managerId === $userId) {
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
}

$stmt = $conn->prepare("UPDATE users SET manager_id = ? WHERE user_id = ?");
$stmt->bind_param('ii', $managerId, $userId);

if ($stmt->execute()) {
    $stmt->close();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    $description = $managerId
        ? "Assigned a manager for {$user['full_name']}"
        : "Removed manager assignment for {$user['full_name']}";

    $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $eventType = 'manager_assigned';
        $title = 'Manager Assigned';
        $logStmt->bind_param('sissss', $eventType, $adminUserId, $adminEmail, $adminName, $title, $description);
        $logStmt->execute();
        $logStmt->close();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not update manager assignment.']);
    $stmt->close();
}

$conn->close();
