<?php
header('Content-Type: application/json');

try {
    // Database connection
    require_once '../includes/db.php';

    $currentUserId = isset($_GET['current_user_id']) ? intval($_GET['current_user_id']) : 0;
    $weekStart     = $_GET['week_start'] ?? '';
    $clientName    = $_GET['client_name'] ?? '';

    $sql = "SELECT u.id, u.first_name, u.last_name, u.user_name
            FROM entries a
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
    // Always return JSON on error
    echo json_encode(['error' => $e->getMessage()]);
}
