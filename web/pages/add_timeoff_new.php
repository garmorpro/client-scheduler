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
$user_id = $data['user_id'] ?? null;
$week_start = $data['week_start'] ?? null;
$assigned_hours = $data['assigned_hours'] ?? '';
$is_timeoff = 1;

header('Content-Type: application/json');

if (!$user_id || !$week_start || !$assigned_hours) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Escape input for MySQLi
$user_id = mysqli_real_escape_string($conn, $user_id);
$week_start = mysqli_real_escape_string($conn, $week_start);
$assigned_hours = mysqli_real_escape_string($conn, $assigned_hours);
$is_timeoff = (int)$is_timeoff;

// Insert entry
$sql = "
    INSERT INTO entries (user_id, week_start, is_timeoff, assigned_hours)
    VALUES ('$user_id', '$week_start', $is_timeoff, '$assigned_hours')
";

if (mysqli_query($conn, $sql)) {
    $entry_id = mysqli_insert_id($conn);
    echo json_encode(['success' => true, 'entry_id' => $entry_id]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}
