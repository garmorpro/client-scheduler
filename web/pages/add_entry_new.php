<?php
require_once '../includes/db.php'; // adjust path if needed
session_start();

header('Content-Type: application/json');

// Only admins can add entries
$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

$user_id = intval($input['user_id'] ?? 0);
$week_start = $input['week_start'] ?? null;
$client_name = trim($input['client_name'] ?? '');
$assigned_hours = floatval($input['assigned_hours'] ?? 0);

if (!$user_id || !$week_start || !$client_name || $assigned_hours <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid parameters']);
    exit();
}

// Find engagement_id by client_name
$stmt = $conn->prepare("SELECT engagement_id FROM engagements WHERE client_name = ? LIMIT 1");
$stmt->bind_param('s', $client_name);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $engagement_id = $row['engagement_id'];
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Client not found']);
    exit();
}
$stmt->close();

// Insert new entry
$stmt = $conn->prepare("INSERT INTO entries (user_id, week_start, engagement_id, assigned_hours) VALUES (?, ?, ?, ?)");
$stmt->bind_param('isid', $user_id, $week_start, $engagement_id, $assigned_hours);

if ($stmt->execute()) {
    $entry_id = $stmt->insert_id;
    echo json_encode(['success' => true, 'entry_id' => $entry_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to insert entry']);
}

$stmt->close();
$conn->close();
