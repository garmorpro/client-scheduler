<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate POST
$client_id = $_POST['client_id'] ?? null;
$client_name = $_POST['client_name'] ?? null;
$onboarded_date = $_POST['onboarded_date'] ?? null;
$status = $_POST['status'] ?? null;
$notes = $_POST['notes'] ?? '';

if (!$client_id || !$client_name || !$onboarded_date || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Update client record
$stmt = $conn->prepare("UPDATE clients SET client_name = ?, onboarded_date = ?, status = ?, notes = ? WHERE client_id = ?");
$stmt->bind_param('ssssi', $client_name, $onboarded_date, $status, $notes, $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
