<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    header("Location: /");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    if (!csrf_valid()) {
        header("Location: client-management.php");
        exit();
    }

    $clientId = intval($_POST['client_id']);

    // First, delete engagements tied to the client
    $stmt = $conn->prepare("DELETE FROM engagements WHERE client_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    // Then, delete the client
    $stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Client and all related engagements were deleted successfully.";
    header("Location: client-management.php"); // redirect back to client management page
    exit();
}

header("Location: client-management.php");
exit();
?>
