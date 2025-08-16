<?php
require_once '../includes/db.php'; // this should have your mysqli connection as $conn

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // match JS: timeoff_id
    $id = intval($_POST['timeoff_id'] ?? 0);

    if ($id > 0) {
        // prepare statement
        $stmt = mysqli_prepare($conn, "DELETE FROM time_off WHERE timeoff_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'error' => 'Database delete failed.']);
                exit;
            }
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid request.']);
