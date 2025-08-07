<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $engagementId = $_POST['id'] ?? null;
    $newStatus = $_POST['status'] ?? null;

    if ($engagementId && $newStatus) {
        $stmt = $conn->prepare("UPDATE engagements SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $engagementId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
