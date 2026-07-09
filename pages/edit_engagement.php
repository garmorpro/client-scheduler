<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';

header('Content-Type: application/json');

$userRole = strtolower($_SESSION['user_role'] ?? '');
if (!isset($_SESSION['user_id']) || ($userRole !== 'admin' && $userRole !== 'manager')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$engagement_id = intval($_POST['engagement_id'] ?? 0);
$budgeted_hours = $_POST['budgeted_hours'] ?? null;
$status = $_POST['status'] ?? null;
$manager = $_POST['manager'] ?? null;
$notes = $_POST['notes'] ?? '';

if (!$engagement_id || $budgeted_hours === null || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$stmt = $conn->prepare("UPDATE engagements SET budgeted_hours = ?, status = ?, manager = ?, notes = ? WHERE engagement_id = ?");
$stmt->bind_param('ssssi', $budgeted_hours, $status, $manager, $notes, $engagement_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
