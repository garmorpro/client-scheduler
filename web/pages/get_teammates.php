<?php
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../includes/db.php'; // makes $conn available

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $currentUserId = isset($_GET['current_user_id']) ? intval($_GET['current_user_id']) : 0;
    $weekStart     = $_GET['week_start'] ?? '';
    $clientName    = $_GET['client_name'] ?? '';

    $sql = "SELECT 
            u.user_id,
            u.first_name,
            u.last_name,
            e.assigned_hours
        FROM users u
        JOIN entries e ON e.user_id = u.user_id
        JOIN engagements g ON g.engagement_id = e.engagement_id
        WHERE g.client_name = ?";

$types = "s";
$params = [$clientName];

if ($currentUserId) {
    $sql .= " AND u.user_id != ?";
    $types .= "i";
    $params[] = $currentUserId;
}

if (!empty($weekStart)) {
    $sql .= " AND e.week_start = ?";
    $types .= "s";
    $params[] = $weekStart;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$teammates = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($teammates);


} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
