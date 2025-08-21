<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

// Suppress direct error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

function send_json($data) {
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['engagement_id'])) {
    send_json(["success" => false, "message" => "Invalid request"]);
}

$engagement_id   = intval($_POST['engagement_id']);
$archived_by     = $_SESSION['full_name'] ?? 'Unknown';
$engagement_year = date("Y");
$archive_date    = date("Y-m-d");

// Get engagement details
$query = $conn->prepare("SELECT * FROM engagements WHERE engagement_id = ?");
if (!$query) send_json(["success" => false, "message" => "Prepare failed: " . $conn->error]);

$query->bind_param("i", $engagement_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    send_json(["success" => false, "message" => "Engagement not found"]);
}

$eng = $result->fetch_assoc();
$client_id      = $eng['client_id'] ?? null;
$budgeted_hours = $eng['budgeted_hours'] ?? 0;
$notes          = !empty($eng['notes']) ? $eng['notes'] : null;
$status         = !empty($eng['status']) ? $eng['status'] : null;

// Use manager from engagements table
$managerStr = !empty($eng['manager']) ? $eng['manager'] : null;

// Get entries for this engagement to calculate allocated_hours, seniors, and staff
$entriesQuery = $conn->prepare("
    SELECT e.assigned_hours, u.role, u.full_name
    FROM entries e
    JOIN ms_users u ON e.user_id = u.user_id
    WHERE e.engagement_id = ?
");
if (!$entriesQuery) send_json(["success" => false, "message" => "Prepare failed: " . $conn->error]);

$entriesQuery->bind_param("i", $engagement_id);
$entriesQuery->execute();
$entriesResult = $entriesQuery->get_result();

$seniors   = [];
$staffs    = [];
$allocated_hours = 0;

while ($row = $entriesResult->fetch_assoc()) {
    $role = strtolower($row['role']);
    $name = $row['full_name'];
    $hours = floatval($row['assigned_hours']);
    $allocated_hours += $hours;

    if ($role === 'senior' && !in_array($name, $seniors)) $seniors[] = $name;
    elseif ($role === 'staff' && !in_array($name, $staffs)) $staffs[] = $name;
}

$seniorStr  = !empty($seniors) ? implode(',', $seniors) : null;
$staffStr   = !empty($staffs) ? implode(',', $staffs) : null;

// Insert into history table
$insert = $conn->prepare("
    INSERT INTO client_engagement_history
    (client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$insert) send_json(["success" => false, "message" => "Prepare failed: " . $conn->error]);

$insert->bind_param(
    "isddssssss",
    $client_id,
    $engagement_year,
    $budgeted_hours,
    $allocated_hours,
    $managerStr,
    $seniorStr,
    $staffStr,
    $notes,
    $archived_by,
    $archive_date
);

if (!$insert->execute()) {
    send_json(["success" => false, "message" => "Insert failed: " . $insert->error]);
}

// Delete engagement from active table
$delete = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
if (!$delete) send_json(["success" => false, "message" => "Delete prepare failed: " . $conn->error]);

$delete->bind_param("i", $engagement_id);
$delete->execute();

send_json(["success" => true]);
?>
