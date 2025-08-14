<?php
header('Content-Type: application/json');

try {
    require_once '../includes/db.php';

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
            WHERE g.client_name = :client_name";

    // Filter out current user if provided
    if ($currentUserId) {
        $sql .= " AND u.user_id != :current_user_id";
    }

    // Filter by week_start if provided
    if (!empty($weekStart)) {
        $sql .= " AND e.week_start = :week_start";
    }

    $stmt = $pdo->prepare($sql);

    // Bind parameters safely
    $stmt->bindParam(':client_name', $clientName, PDO::PARAM_STR);
    if ($currentUserId) {
        $stmt->bindParam(':current_user_id', $currentUserId, PDO::PARAM_INT);
    }
    if (!empty($weekStart)) {
        $stmt->bindParam(':week_start', $weekStart, PDO::PARAM_STR);
    }

    $stmt->execute();
    $teammates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Optional: debug log for browser console (works if you open PHP directly)
    // echo "<script>console.log('Teammates:', " . json_encode($teammates) . ");</script>";

    // Return JSON for fetch() calls
    echo json_encode($teammates);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
