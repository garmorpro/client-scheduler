<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['entry_id'], $data['client_name'], $data['assigned_hours'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit();
}

$entry_id    = intval($data['entry_id']);
$client_name = trim($data['client_name']);
$hours       = floatval($data['assigned_hours']);

// 1. Find engagement_id from engagements table
$stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE client_name = ?");
$stmt->bind_param("s", $client_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Client not found in engagements table']);
    exit();
}

$row = $result->fetch_assoc();
$engagement_id = intval($row['engagement_id']);
$stmt->close();

// 2. Update entries with engagement_id and hours
$stmt = $conn->prepare("UPDATE entries SET engagement_id = ?, assigned_hours = ? WHERE entry_id = ?");
$stmt->bind_param("idi", $engagement_id, $hours, $entry_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'engagement_id' => $engagement_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database update failed']);
}

$stmt->close();
$conn->close();
