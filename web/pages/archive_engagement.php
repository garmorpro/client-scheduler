<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require '../includes/db.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['engagement_id'])) {
    $engagement_id   = intval($_POST['engagement_id']);
    $archived_by     = $_SESSION['full_name'];
    $engagement_year = date("Y");
    $archive_date    = date("Y-m-d");

    // Get engagement details
    $query = $conn->prepare("SELECT * FROM engagements WHERE engagement_id = ?");
    $query->bind_param("i", $engagement_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Engagement not found"]);
        exit;
    }

    $eng = $result->fetch_assoc();

    // Ensure NULL values for basic fields
    $client_id       = $eng['client_id'] ?? null;
    $budgeted_hours  = $eng['budgeted_hours'] ?? null;
    $allocated_hours = $eng['allocated_hours'] ?? null;
    $notes           = $eng['notes'] ?? null;
    $status          = $eng['status'] ?? null;

    // Get all entries for this engagement
    $entriesQuery = $conn->prepare("
        SELECT e.user_id, u.role, u.full_name
        FROM entries e
        JOIN users u ON e.user_id = u.user_id
        WHERE e.engagement_id = ?
    ");
    $entriesQuery->bind_param("i", $engagement_id);
    $entriesQuery->execute();
    $entriesResult = $entriesQuery->get_result();

    $managers = [];
    $seniors  = [];
    $staffs   = [];

    while ($row = $entriesResult->fetch_assoc()) {
        $role = strtolower($row['role']);
        $name = $row['full_name'];

        if ($role === 'manager' && !in_array($name, $managers)) {
            $managers[] = $name;
        } elseif ($role === 'senior' && !in_array($name, $seniors)) {
            $seniors[] = $name;
        } elseif ($role === 'staff' && !in_array($name, $staffs)) {
            $staffs[] = $name;
        }
    }

    // Convert arrays to comma-separated strings or NULL if empty
    $managerStr = !empty($managers) ? implode(',', $managers) : null;
    $seniorStr  = !empty($seniors) ? implode(',', $seniors) : null;
    $staffStr   = !empty($staffs) ? implode(',', $staffs) : null;

    // Insert into client_engagement_history
    $insert = $conn->prepare("
        INSERT INTO client_engagement_history
        (client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param(
        "isddsssssss",
        $client_id,
        $engagement_year,
        $budgeted_hours,
        $allocated_hours,
        $managerStr,
        $seniorStr,
        $staffStr,
        $notes,
        $archived_by,
        $archive_date,
        $status
    );

    if ($insert->execute()) {
        // Delete from active engagements
        $delete = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
        $delete->bind_param("i", $engagement_id);
        $delete->execute();

        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Insert failed: " . $insert->error]);
    }
}
?>
