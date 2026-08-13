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

$engagementId = (int) ($_POST['engagement_id'] ?? 0);
if ($engagementId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!audit_can_act_on_engagement($conn, $engagementId, 'manage_audit_timeline')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
if ($file['size'] > PLANNING_DOC_MAX_BYTES) {
    echo json_encode(['success' => false, 'error' => 'File is too large (20MB max)']);
    exit;
}

$originalName = $file['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($ext, PLANNING_DOC_ALLOWED_EXTENSIONS, true)) {
    echo json_encode(['success' => false, 'error' => 'Unsupported file type']);
    exit;
}

$safeRoot = planning_doc_safe_root();
$engagementDir = $safeRoot . '/' . $engagementId;
if (!is_dir($engagementDir)) {
    mkdir($engagementDir, 0755, true);
}

// Embed a sanitized version of the original filename (not the raw value)
// so downloads can show something recognizable without a separate DB column.
$baseName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
$baseName = substr($baseName, 0, 60) ?: 'file';
$storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $baseName . '.' . $ext;
$destination = $engagementDir . '/' . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Could not save file']);
    exit;
}

// Only the generated relative path ever gets stored - never anything
// derived directly from the client-supplied filename.
$relativePath = $engagementId . '/' . $storedName;

$stmt = $conn->prepare("
    INSERT INTO audit_engagement_details (engagement_id, planning_doc_url)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE planning_doc_url = VALUES(planning_doc_url)
");
$stmt->bind_param('is', $engagementId, $relativePath);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok, 'original_name' => $originalName]);
$conn->close();
