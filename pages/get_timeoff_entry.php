<?php
require_once '../includes/db.php'; // assumes $conn is mysqli connection
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$week_start = $data['week_start'] ?? null;

if (!$user_id || !$week_start) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id or week_start']);
    exit;
}

$sql = "SELECT timeoff_id, assigned_hours
        FROM time_off
        WHERE user_id = ?
          AND week_start = ?
          AND is_global_timeoff = 0
          AND status = 'approved'
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $user_id, $week_start);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'timeoff_id' => $row['timeoff_id'],
            'assigned_hours' => $row['assigned_hours'] // <-- include assigned_hours
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'entrtimeoff_idy_id' => null,
            'assigned_hours' => 0 // <-- no timeoff, return 0
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
