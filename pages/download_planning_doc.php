<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/planning_doc_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$engagementId = (int) ($_GET['engagement_id'] ?? 0);
if ($engagementId <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

// Same view-level access rule as engagement-details.php: permission holders
// can view any engagement, everyone else only ones they're staffed on.
if (!user_has_permission($conn, 'view_clients_engagements')) {
    $accessStmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
    $accessStmt->bind_param('ii', $engagementId, $_SESSION['user_id']);
    $accessStmt->execute();
    $hasAccess = (bool) $accessStmt->get_result()->fetch_row();
    $accessStmt->close();
    if (!$hasAccess) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

$stmt = $conn->prepare("SELECT planning_doc_url FROM audit_engagement_details WHERE engagement_id = ?");
$stmt->bind_param('i', $engagementId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$relativePath = $row['planning_doc_url'] ?? null;
// Only accept the exact shape this module generates itself
// (engagement_id/timestamp_hex_name.ext) - never trust the DB value blindly
// as a filesystem path.
if (!$relativePath || !preg_match('#^(\d+)/([A-Za-z0-9_\-]+\.[A-Za-z0-9]+)$#', $relativePath, $m) || (int) $m[1] !== $engagementId) {
    http_response_code(404);
    exit('File not found');
}

$safeRoot = planning_doc_safe_root();
$fullPath = $safeRoot . '/' . $relativePath;
$resolved = realpath($fullPath);
if ($resolved === false || strpos($resolved, $safeRoot) !== 0 || !is_file($resolved)) {
    http_response_code(404);
    exit('File not found');
}

// Strip the generated timestamp_hex_ prefix back off for a cleaner download name.
$displayName = preg_replace('/^\d{8}_\d{6}_[0-9a-f]{8}_/', '', $m[2]);

// ?mode=view asks to render inline (a popup <iframe>/<img> in the app)
// instead of triggering a download - only actually honored for formats a
// browser can render natively. Office formats (doc/xlsx/pptx/etc.) always
// fall back to a real download since there's no in-browser renderer for
// them here; the frontend already knows this and shows a "download instead"
// message rather than opening an iframe for those extensions.
$ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
$viewableInline = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'], true);
$mode = ($_GET['mode'] ?? '') === 'view' && $viewableInline ? 'view' : 'download';
$disposition = $mode === 'view' ? 'inline' : 'attachment';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . $displayName . '"');
header('Content-Length: ' . filesize($resolved));
readfile($resolved);
exit;
