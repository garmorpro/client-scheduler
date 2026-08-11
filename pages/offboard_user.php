<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit();
}

$offboardUserId = (int) $_POST['user_id'];

if ($offboardUserId === (int) $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot offboard your own account.']);
    exit();
}

$detailsStmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$detailsStmt->bind_param('i', $offboardUserId);
$detailsStmt->execute();
$detailsStmt->bind_result($userFullName, $emailAddress);
if (!$detailsStmt->fetch()) {
    $detailsStmt->close();
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}
$detailsStmt->close();

// Offboarding is the "safe" everyday alternative to Delete: it deactivates
// the account (blocks login, per includes/auth.php's status check) and
// clears their current staffing, but - unlike Delete - keeps their entries'
// and time off's history intact so past hours/reports still add up. This is
// exactly the fallback delete_user.php already points admins toward when a
// hard delete is refused for having history attached.
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
    $stmt->bind_param('i', $offboardUserId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM entries WHERE user_id = ?");
    $stmt->bind_param('i', $offboardUserId);
    $stmt->execute();
    $unassignedCount = $stmt->affected_rows;
    $stmt->close();

    // If they managed anyone, don't leave those people reporting to an
    // offboarded account - clear the assignment rather than silently
    // orphaning it (mirrors set_direct_reports.php's own clearing logic).
    $stmt = $conn->prepare("UPDATE users SET manager_id = NULL WHERE manager_id = ?");
    $stmt->bind_param('i', $offboardUserId);
    $stmt->execute();
    $reportsCleared = $stmt->affected_rows;
    $stmt->close();

    $conn->commit();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    $description = "Offboarded $userFullName ($emailAddress) - set inactive, unassigned $unassignedCount schedule "
        . ($unassignedCount === 1 ? 'entry' : 'entries')
        . ($reportsCleared > 0 ? ", cleared manager for $reportsCleared direct report" . ($reportsCleared === 1 ? '' : 's') : '')
        . '.';
    logActivity($conn, "user_offboarded", $adminUserId, $adminEmail, $adminName, "Employee Offboarded", $description);

    echo json_encode([
        'success' => true,
        'unassigned' => $unassignedCount,
        'reports_cleared' => $reportsCleared,
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not offboard employee.']);
}
