<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM time_off WHERE timeoff_id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Database delete failed.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request.']);
