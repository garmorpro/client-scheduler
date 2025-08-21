<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Validate POST
$client_id     = $_POST['client_id'] ?? null;
$client_name   = $_POST['client_name'] ?? null;
$budget_hours  = $_POST['budget_hours'] ?? null;
$status        = $_POST['status'] ?? null;
$year          = $_POST['year'] ?? date('Y');
$manager       = $_POST['manager'] ?? null;

if (!$client_id || !$client_name || !$budget_hours || !$status || !$manager) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert into engagements including manager (as string)
$stmt = $conn->prepare("
    INSERT INTO engagements (client_id, client_name, budgeted_hours, status, year, manager) 
    VALUES (?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('isisss', $client_id, $client_name, $budget_hours, $status, $year, $manager);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
