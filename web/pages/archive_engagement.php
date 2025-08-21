<?php
session_start();
require 'db.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['engagement_id'])) {
    $engagement_id = intval($_POST['engagement_id']);
    $archived_by = $_SESSION['full_name'];
    $archive_date = date("Y-m-d");

    // Get engagement details
    $query = $conn->prepare("SELECT * FROM engagements WHERE engagement_id = ?");
    $query->bind_param("i", $engagement_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $eng = $result->fetch_assoc();

        // Insert into history table
        $insert = $conn->prepare("
            INSERT INTO client_engagement_history
            (client_id, engagement_year, budgeted_hours, allocated_hours, manager, senior, staff, notes, archived_by, archive_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->bind_param(
            "isddsssssss",
            $eng['client_id'],
            $eng['engagement_year'],
            $eng['budgeted_hours'],
            $eng['allocated_hours'],
            $eng['manager'],
            $eng['senior'],
            $eng['staff'],
            $eng['notes'],
            $archived_by,
            $archive_date,
            $eng['status']
        );

        if ($insert->execute()) {
            // Delete from active engagements
            $delete = $conn->prepare("DELETE FROM engagements WHERE engagement_id = ?");
            $delete->bind_param("i", $engagement_id);
            $delete->execute();

            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Insert failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Engagement not found"]);
    }
}
?>
