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

    // Refuse to delete a client that still has engagements - deleting them
    // silently would orphan any schedule entries logged against those
    // engagements. Archive/remove the engagements first.
    $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM engagements WHERE client_id = ?");
    $countStmt->bind_param("i", $clientId);
    $countStmt->execute();
    $engagementCount = (int) $countStmt->get_result()->fetch_assoc()['cnt'];
    $countStmt->close();

    if ($engagementCount > 0) {
        $_SESSION['error_message'] = "Can't delete this client - it still has $engagementCount engagement(s). Remove or reassign them first.";
        header("Location: client-management.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM clients WHERE client_id = ?");
    $stmt->bind_param("i", $clientId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Client deleted successfully.";
    header("Location: client-management.php"); // redirect back to client management page
    exit();
}

header("Location: client-management.php");
exit();
?>
