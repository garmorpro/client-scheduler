<?php
session_start();
require '../includes/db.php'; // your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['engagement_id'])) {
    $engagement_id = intval($_POST['engagement_id']);
    $archived_by   = $_SESSION['full_name'];
    $archive_date  = date("Y-m-d");

    // Get engagement details
    $query = $conn->prepare("SELECT * FROM engagements WHERE engagement_id = ?");
    $query->bind_param("i", $engagement_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $eng = $result->fetch_assoc();

        // Ensure NULL values are properly handled
        $client_id       = $eng['client_id'] ?? null;
        $engagement_year = $eng['engagement_year'] ?? null;
        $budgeted_hours  = $eng['budgeted_hours'] !== null ? $eng['budgeted_hours'] : null;
        $allocated_hours = $eng['allocated_hours'] !== null ? $eng['allocated_hours'] : null;
        $manager         = !empty($eng['manager']) ? $eng['manager'] : null;
        $senior          = !empty($eng['senior']) ? $eng['senior'] : null;
        $staff           = !empty($eng['staff']) ? $eng['staff'] : null;
        $notes           = !empty($eng['notes']) ? $eng['notes'] : null;
        $status          = !empty($eng['status']) ? $eng['status'] : null;

        // Insert into history table
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
            $manager,
            $senior,
            $staff,
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
    } else {
        echo json_encode(["success" => false, "message" => "Engagement not found"]);
    }
}
?>
