<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage policies']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$policyId = !empty($data['policy_id']) ? (int)$data['policy_id'] : 0;

if (!$policyId) {
    echo json_encode(['success' => false, 'error' => 'Missing policy ID']);
    exit;
}

$pdfStmt = $conn->prepare("SELECT pdf_path FROM policies WHERE policy_id = ?");
$pdfStmt->bind_param('i', $policyId);
$pdfStmt->execute();
$pdfRow = $pdfStmt->get_result()->fetch_assoc();
$pdfStmt->close();

$stmt = $conn->prepare("DELETE FROM policies WHERE policy_id = ?");
$stmt->bind_param('i', $policyId);
if ($stmt->execute()) {
    if (!empty($pdfRow['pdf_path'])) {
        $pdfFile = __DIR__ . '/../assets/uploads/policies/' . $pdfRow['pdf_path'];
        if (file_exists($pdfFile)) {
            unlink($pdfFile);
        }
    }
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
