<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$client_id = intval($_GET['client_id'] ?? 0);
if ($client_id <= 0) {
    echo json_encode(['error' => 'Invalid client ID']);
    exit();
}

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
$stmt->close();

if (!$client) {
    echo json_encode(['error' => 'Client not found']);
    exit();
}

// Optional: current manager
$stmt3 = $conn->prepare("SELECT manager FROM engagements WHERE client_id = ? ORDER BY engagement_id DESC LIMIT 1");
$stmt3->bind_param("i", $client_id);
$stmt3->execute();
$res = $stmt3->get_result()->fetch_assoc();
$client['current_manager'] = $res['manager'] ?? null;
$stmt3->close();

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
$stmt2->close();

echo json_encode([
    'client' => $client,
    'history' => $history ?: []
]);
exit();
