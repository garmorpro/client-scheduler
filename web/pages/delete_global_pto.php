<?php
require_once '../includes/db.php'; // $conn = mysqli_connect(...);
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "Unauthorized"]);
    exit();
}

// Validate input
if (!isset($_POST['timeoff_id']) || !is_numeric($_POST['timeoff_id'])) {
    echo json_encode(["success" => false, "error" => "Invalid ID"]);
    exit();
}

$id = (int) $_POST['timeoff_id'];

$stmt = mysqli_prepare($conn, "DELETE FROM time_off WHERE timeoff_id = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => mysqli_error($conn)]);
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $id);
if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Record not found"]);
    }
} else {
    echo json_encode(["success" => false, "error" => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
