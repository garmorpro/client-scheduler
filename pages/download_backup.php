<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    exit('Unauthorized');
}

$settingsRow = $conn->query("SELECT setting_value FROM settings WHERE setting_master_key = 'backup' AND setting_key = 'local_backup_directory'")->fetch_assoc();
$backupDir = backup_resolve_dir($settingsRow['setting_value'] ?? null);

// Strip any path component from the requested name and require it to match
// the expected backup filename pattern - prevents path traversal (e.g.
// "../../etc/passwd") regardless of how the name arrives.
$requested = basename($_GET['file'] ?? '');
if (!preg_match('/^db_backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $requested)) {
    http_response_code(400);
    exit('Invalid backup filename');
}

$filePath = $backupDir . '/' . $requested;
if (!is_file($filePath)) {
    http_response_code(404);
    exit('Backup not found');
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $requested . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
readfile($filePath);
