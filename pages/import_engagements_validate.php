<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/import_engagements_shared.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

$result = parse_and_validate_engagement_import($conn, $_FILES['import_file']['tmp_name']);

// The browser only needs the report to display + decide whether Confirm is
// enabled - the resolved insert-ready arrays are re-derived from scratch on
// the commit request instead of being trusted from this response.
echo json_encode([
    'ok' => $result['ok'],
    'errors' => $result['errors'],
    'warnings' => $result['warnings'],
    'summary' => $result['summary'],
]);
