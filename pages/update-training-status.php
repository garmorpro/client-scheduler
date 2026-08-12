<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_dol')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = (int) ($input['user_id'] ?? 0);
$restricted = array_filter(array_map('trim', $input['restricted'] ?? []));

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id']);
    exit();
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("DELETE FROM dol_training_restrictions WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    if (!empty($restricted)) {
        $stmt = $conn->prepare("INSERT INTO dol_training_restrictions (user_id, criterion) VALUES (?, ?)");
        foreach ($restricted as $criterion) {
            $stmt->bind_param('is', $userId, $criterion);
            $stmt->execute();
        }
        $stmt->close();
    }

    $conn->commit();

    $nameStmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $nameStmt->bind_param('i', $userId);
    $nameStmt->execute();
    $targetName = $nameStmt->get_result()->fetch_assoc()['full_name'] ?? "user $userId";
    $nameStmt->close();

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail = $_SESSION['email'] ?? '';
    $adminName = $_SESSION['full_name'] ?? '';
    $description = empty($restricted)
        ? "Cleared all training restrictions for {$targetName}"
        : "Updated training restrictions for {$targetName}: " . implode(', ', $restricted);
    $logStmt = $conn->prepare("INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $eventType = 'training_status_updated';
        $title = 'Training Status Updated';
        $logStmt->bind_param('sissss', $eventType, $adminUserId, $adminEmail, $adminName, $title, $description);
        $logStmt->execute();
        $logStmt->close();
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not save training status.']);
}

$conn->close();
