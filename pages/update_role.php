<?php
require_once '../includes/db.php'; // mysqli connection
session_start();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$userId = intval($data['user_id'] ?? 0);
$newRole = strtolower($data['new_role'] ?? '');

$allowedRoles = ['staff','senior','manager','admin'];

if (!$userId || !in_array($newRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

// Optional: prevent non-admins from updating roles
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'service_account') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized.']);
    exit;
}

// Update the role
$stmt = $conn->prepare("UPDATE ms_users SET role=? WHERE user_id=?");
$stmt->bind_param("si", $newRole, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
