<?php
// get_teammates.php
header('Content-Type: application/json');
require_once '../includes/db.php'; // your DB connection

// Get query parameters
$clientName = $_GET['client_name'] ?? '';
$weekStart = $_GET['week_start'] ?? '';
$currentUserId = $_GET['current_user_id'] ?? null;

if (!$clientName || !$weekStart) {
    echo json_encode([]);
    exit;
}

try {
    // Adjust table/column names as needed
    $sql = "SELECT u.id AS user_id, u.user_name, u.first_name, u.last_name, e.assigned_hours
            FROM entries e
            JOIN users u ON e.user_id = u.id
            WHERE e.client_name = :client_name
              AND e.week_start = :week_start";

    if ($currentUserId) {
        $sql .= " AND u.id != :current_user_id";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':client_name', $clientName);
    $stmt->bindParam(':week_start', $weekStart);
    if ($currentUserId) {
        $stmt->bindParam(':current_user_id', $currentUserId);
    }

    $stmt->execute();
    $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($teamMembers);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
}
