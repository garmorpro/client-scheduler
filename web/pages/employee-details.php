<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing employee ID']);
    exit;
}

$employeeId = (int)$_GET['id'];

// Example query - make sure you replace with your real one
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

$data = $result->fetch_assoc();

// No extra output before this line
echo json_encode($data);
