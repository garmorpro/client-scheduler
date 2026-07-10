<?php
require_once '../includes/db.php'; // mysqli connection
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$userId = intval($data['user_id'] ?? 0);
$newRole = strtolower($data['new_role'] ?? '');

$allowedRoles = ['staff','senior','manager','admin'];

if (!$userId || !in_array($newRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

// Promoting someone to admin is a bigger action than ordinary role management -
// require the caller to actually be an admin, not just hold the manage_employees
// permission (which a non-admin manager could otherwise use to self-promote).
if ($newRole === 'admin' && strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only an admin can grant admin access.']);
    exit;
}

// Update the role
$stmt = $conn->prepare("UPDATE users SET role=? WHERE user_id=?");
$stmt->bind_param("si", $newRole, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
