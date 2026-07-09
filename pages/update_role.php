<?php
require_once '../includes/db.php'; // mysqli connection
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

$currentRole = strtolower($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || ($currentRole !== 'admin' && $currentRole !== 'service_account')) {
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
