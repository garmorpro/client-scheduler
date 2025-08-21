<?php
require_once '../includes/db.php';
session_start();

// Only allow admins
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$timeoff_id = $data['timeoff_id'] ?? null;
$assigned_hours = $data['assigned_hours'] ?? '';

header('Content-Type: application/json');

if (!$timeoff_id || !$assigned_hours) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Escape input for MySQLi
$timeoff_id = (int)$timeoff_id;
$assigned_hours = mysqli_real_escape_string($conn, $assigned_hours);

// Update the timeoff
$sql = "
    UPDATE time_off
    SET assigned_hours = '$assigned_hours'
    WHERE timeoff_id = $timeoff_id
";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
