<?php
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

$id = intval($_GET['id'] ?? 0);
$week_start = $_POST['week_start'] ?? '';
$assigned_hours = intval($_POST['assigned_hours'] ?? 0);
$note = $_POST['timeoff_note'] ?? '';

if ($id <= 0 || empty($week_start) || $assigned_hours <= 0 || empty($note)) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$stmt = $conn->prepare("UPDATE time_off SET week_start=?, assigned_hours=?, timeoff_note=? WHERE timeoff_id=?");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => $conn->error]);
    exit;
}

$stmt->bind_param("sisi", $week_start, $assigned_hours, $note, $id);
$success = $stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(["success" => $success]);
