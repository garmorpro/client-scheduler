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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$allowedRoles = ['admin', 'manager', 'senior', 'staff', 'intern', 'crm_team'];
$allowedStatuses = ['active', 'inactive'];

$userId = $_POST['user_id'] ?? null;
$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$role = strtolower(trim($_POST['role'] ?? ''));
$status = strtolower(trim($_POST['status'] ?? ''));

if (!$userId || !is_numeric($userId) || $fullName === '' || $email === '' || $role === '') {
    echo json_encode(['success' => false, 'error' => 'Full name, email, and role are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit();
}

if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid role.']);
    exit();
}

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'active';
}

$userId = (int) $userId;

$stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, job_title = ?, role = ?, status = ? WHERE user_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}
$stmt->bind_param("sssssi", $fullName, $email, $jobTitle, $role, $status, $userId);

if ($stmt->execute()) {
    $stmt->close();

    // Keep the editor's own session in sync if they edited themselves
    if ($userId === (int) ($_SESSION['user_id'] ?? 0)) {
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['user_role'] = $role;
    }

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    $role_uc = ucfirst($role);

    logActivity($conn, "user_updated", $adminUserId, $adminEmail, $adminName, "User Updated", "Updated account for $fullName ($role_uc)");

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error updating user.']);
}
