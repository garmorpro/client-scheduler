<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit;
}

// Validate input
if (empty($_POST['date']) || empty($_POST['hours'])) {
    echo json_encode(["success" => false, "error" => "Missing date or hours"]);
    exit;
}

$date  = $_POST['date'];
$hours = (float) $_POST['hours'];

// Insert into DB
$stmt = $conn->prepare("INSERT INTO global_pto (pto_date, hours) VALUES (?, ?)");
if (!$stmt) {
    echo json_encode(["success" => false, "error" => $conn->error]);
    exit;
}
$stmt->bind_param("sd", $date, $hours);
$ok = $stmt->execute();

if ($ok) {
    $entryId = $stmt->insert_id;

    // Fetch the inserted row
    $stmt2 = $conn->prepare("SELECT id, pto_date, hours FROM global_pto WHERE id = ?");
    $stmt2->bind_param("i", $entryId);
    $stmt2->execute();
    $result = $stmt2->get_result();
    $entry = $result->fetch_assoc();

    if ($entry) {
        echo json_encode([
            "success" => true,
            "entry"   => $entry
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Entry added, but could not fetch inserted row.",
            "entryId" => $entryId
        ]);
    }
    $stmt2->close();
} else {
    echo json_encode([
        "success" => false,
        "error"   => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
