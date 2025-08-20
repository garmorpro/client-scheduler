<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();
header('Content-Type: application/json');

$client_id = $_GET['client_id'] ?? null;
if (!$client_id) {
    echo json_encode(['success' => false, 'message' => 'No client ID']);
    exit;
}

// Get client info
$stmt = $conn->prepare("
    SELECT client_name, onboarded_date,
        (SELECT COUNT(*) FROM client_engagement_history WHERE client_id = ?) AS total_engagements,
        (SELECT COUNT(*) FROM client_engagement_history WHERE client_id = ? AND status='confirmed') AS confirmed_engagements
    FROM clients
    WHERE client_id = ?
");
$stmt->bind_param('iii', $client_id, $client_id, $client_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Client not found']);
    exit;
}

// Get engagement history
$stmt = $conn->prepare("
    SELECT history_id, client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archive_date
    FROM client_engagement_history
    WHERE client_id = ?
    ORDER BY engagement_year DESC
");
$stmt->bind_param('i', $client_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'client' => $result,
    'history' => $history
]);
