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
$client_name = $_POST['client_name'] ?? null;
$onboarded_date = $_POST['onboarded_date'] ?? null;
$status = $_POST['status'] ?? null;
$notes = $_POST['notes'] ?? '';

if (!$client_name || !$onboarded_date || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Insert client
$stmt = $conn->prepare("INSERT INTO clients (client_name, onboarded_date, status, notes) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $client_name, $onboarded_date, $status, $notes);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
