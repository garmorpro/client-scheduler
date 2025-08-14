<?php
header('Content-Type: application/json');

try {
    require_once '../includes/db.php'; // make sure $mysqli is your MySQLi connection

    $currentUserId = isset($_GET['current_user_id']) ? intval($_GET['current_user_id']) : 0;
    $weekStart     = $_GET['week_start'] ?? '';
    $clientName    = $_GET['client_name'] ?? '';

    // Base query
    $sql = "SELECT 
                e.engagement_id,
                u.first_name,
                u.last_name
            FROM users u
            JOIN entries e ON e.user_id = u.user_id
            JOIN engagements g ON g.engagement_id = e.engagement_id
            WHERE g.client_name = ?";

    $types = "s"; // string for client_name
    $params = [$clientName];

    if ($currentUserId) {
        $sql .= " AND u.user_id != ?";
        $types .= "i"; // integer
        $params[] = $currentUserId;
    }

    if (!empty($weekStart)) {
        $sql .= " AND e.week_start = ?";
        $types .= "s";
        $params[] = $weekStart;
    }

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
    $teammates = $result->fetch_all(MYSQLI_ASSOC);

    // Return JSON
    echo json_encode($teammates);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
