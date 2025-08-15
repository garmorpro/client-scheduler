<?php
require_once '../includes/db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$week_start = $data['week_start'] ?? '';

if (!$week_start) {
    echo json_encode(['success' => false]);
    exit();
}

$stmt = $conn->prepare("SELECT assigned_hours FROM time_off WHERE is_global_timeoff = 1 AND week_start = ? LIMIT 1");
$stmt->bind_param('s', $week_start);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'assigned_hours' => $row['assigned_hours']]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
