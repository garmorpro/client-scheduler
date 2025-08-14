<?php
// get_teammates.php
header('Content-Type: application/json');

// Disable direct HTML error output
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../includes/db.php'; // ensure $pdo is defined

$clientName = $_GET['client_name'] ?? '';
$weekStart = $_GET['week_start'] ?? '';
$currentUserId = $_GET['current_user_id'] ?? null;

if (!$clientName || !$weekStart) {
    echo json_encode([]);
    exit;
}

try {
    $currentUserId = $_GET['current_user_id'] ?? null;

$sql = "SELECT u.id, u.first_name, u.last_name, u.user_name
        FROM assignments a
        JOIN users u ON a.user_id = u.id
        WHERE a.client_name = :client_name
        AND a.week_start = :week_start";

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
$teammates = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($teammates);

} catch (Exception $e) {
    // Log the error somewhere safe
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
