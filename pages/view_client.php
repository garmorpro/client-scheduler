<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'view_clients_engagements')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

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

// Archived engagement history
$stmt2 = $conn->prepare("
    SELECT history_id, client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date
    FROM client_engagement_history
    WHERE client_id = ?
    ORDER BY engagement_year DESC
");
$stmt2->bind_param("i", $client_id);
$stmt2->execute();
$archivedRows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Current (non-archived) engagements - these live in `engagements`, not
// `client_engagement_history`, so the modal's history list was previously
// only ever showing archived years and missing every active engagement
// regardless of its status (confirmed/pending/not_confirmed).
$stmt4 = $conn->prepare("
    SELECT e.engagement_id, e.status, e.budgeted_hours, e.manager, e.notes, e.year,
        COALESCE(SUM(en.assigned_hours), 0) AS allocated_hours
    FROM engagements e
    LEFT JOIN entries en ON e.engagement_id = en.engagement_id
    WHERE e.client_id = ?
    GROUP BY e.engagement_id, e.status, e.budgeted_hours, e.manager, e.notes, e.year
");
$stmt4->bind_param("i", $client_id);
$stmt4->execute();
$activeRows = $stmt4->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt4->close();

$history = [];
foreach ($activeRows as $row) {
    $history[] = [
        'type' => 'active',
        'engagement_year' => $row['year'],
        'sort_id' => (int) $row['engagement_id'],
        'status' => $row['status'],
        'budgeted_hours' => $row['budgeted_hours'],
        'allocated_hours' => $row['allocated_hours'],
        'manager' => $row['manager'],
        'senior' => null,
        'staff' => null,
        'notes' => $row['notes'],
        'archived_by' => null,
        'archive_date' => null,
    ];
}
foreach ($archivedRows as $row) {
    $history[] = [
        'type' => 'archived',
        'engagement_year' => $row['engagement_year'],
        'sort_id' => (int) $row['history_id'],
        'status' => null,
        'budgeted_hours' => $row['budgeted_hours'],
        'allocated_hours' => $row['allocated_hours'],
        'manager' => $row['manager'],
        'senior' => $row['senior'],
        'staff' => $row['staff'],
        'notes' => $row['notes'],
        'archived_by' => $row['archived_by'],
        'archive_date' => $row['archive_date'],
    ];
}

// Newest first: by year, then active engagements before archived ones in
// the same year, then by id so the most recently added/archived wins.
usort($history, function ($a, $b) {
    if ($a['engagement_year'] != $b['engagement_year']) {
        return $b['engagement_year'] <=> $a['engagement_year'];
    }
    if ($a['type'] !== $b['type']) {
        return $a['type'] === 'active' ? -1 : 1;
    }
    return $b['sort_id'] <=> $a['sort_id'];
});

echo json_encode([
    'client' => $client,
    'history' => $history
]);
exit();
