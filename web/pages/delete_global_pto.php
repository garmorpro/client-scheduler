<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid ID"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM time_off WHERE timeoff_id=?");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(["success" => $success]);
