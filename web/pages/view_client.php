<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_GET['client_id'])) {
    echo json_encode(['error' => 'No client ID provided']);
    exit();
}

$client_id = intval($_GET['client_id']);

// Fetch client info
$stmt = $conn->prepare("
    SELECT client_id, client_name, onboarded_date, status,
        (SELECT COUNT(*) FROM engagements WHERE client_id = ?) AS total_engagements,
        (SELECT COUNT(*) FROM engagements WHERE client_id = ? AND status = 'confirmed') AS confirmed_engagements
    FROM clients
    WHERE client_id = ?
");
$stmt->bind_param("iii", $client_id, $client_id, $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();

if (!$client) {
    echo json_encode(['error' => 'Client not found']);
    exit();
}

// Fetch engagement history
$stmt2 = $conn->prepare("
    SELECT history_id, client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date
    FROM client_engagement_history
    WHERE client_id = ?
    ORDER BY engagement_year DESC
");
$stmt2->bind_param("i", $client_id);
$stmt2->execute();
$history = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// If no records, provide an empty array
if (empty($history)) {
    $history = [];
}

echo json_encode(['client' => $client, 'history' => $history]);
exit();
