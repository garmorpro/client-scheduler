<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit_timeline_fields.php';
require_once __DIR__ . '/../includes/planning_doc_helpers.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = (int) ($data['engagement_id'] ?? 0);
if ($engagementId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!audit_can_act_on_engagement($conn, $engagementId, 'manage_audit_timeline')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT planning_doc_url FROM audit_engagement_details WHERE engagement_id = ?");
$stmt->bind_param('i', $engagementId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Same path validation as download_planning_doc.php - only ever delete a
// path matching exactly what upload_planning_doc.php itself generates.
$relativePath = $row['planning_doc_url'] ?? null;
if ($relativePath && preg_match('#^(\d+)/([A-Za-z0-9_\-]+\.[A-Za-z0-9]+)$#', $relativePath, $m) && (int) $m[1] === $engagementId) {
    $safeRoot = planning_doc_safe_root();
    $resolved = realpath($safeRoot . '/' . $relativePath);
    if ($resolved !== false && strpos($resolved, $safeRoot) === 0 && is_file($resolved)) {
        @unlink($resolved);
    }
}

$stmt = $conn->prepare("UPDATE audit_engagement_details SET planning_doc_url = NULL WHERE engagement_id = ?");
$stmt->bind_param('i', $engagementId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
