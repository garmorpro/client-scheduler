<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$holidays = [];
$sql = "SELECT * FROM time_off WHERE is_global_timeoff = 1 ORDER BY week_start ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $note = $row['timeoff_note'] ?? 'Holiday';
        if (!isset($holidays[$note])) {
            $holidays[$note] = ['name' => $note, 'days' => []];
        }
        $holidays[$note]['days'][] = [
            'id' => (int) $row['timeoff_id'],
            'date' => $row['holiday_date'] ?? $row['week_start'],
            'hours' => (float) $row['assigned_hours'],
        ];
    }
}

echo json_encode(['success' => true, 'holidays' => array_values($holidays)]);
