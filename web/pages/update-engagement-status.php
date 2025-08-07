<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $engagementId = $_POST['engagement_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($engagementId && in_array($status, ['confirmed', 'pending', 'not-confirmed'])) {
        $stmt = $conn->prepare("UPDATE engagements SET status = ? WHERE engagement_id = ?");
        $stmt->bind_param('si', $status, $engagementId);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
}
?>
