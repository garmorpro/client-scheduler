<?php
require_once '../includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $assignmentId = intval($_POST['assignment_id']);
    $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $assignmentId);
    echo $stmt->execute() ? 'success' : 'error';
}
?>
