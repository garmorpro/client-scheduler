<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'audit_notifications'");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Whether a matching line currently exists in the crontab (belt-and-
// suspenders visibility - the settings row and the actual crontab could in
// theory drift, e.g. if someone edited cron by hand outside this page).
$existing = shell_exec('crontab -l 2>/dev/null') ?? '';
$installed = strpos($existing, '# AUDIT_NOTIFICATION_CRON') !== false;

$days = isset($settings['days']) && $settings['days'] !== ''
    ? array_map('intval', explode(',', $settings['days']))
    : []; // frontend defaults to Mon-Fri when this is empty (never configured yet)

echo json_encode([
    'success' => true,
    'enabled' => ($settings['enabled'] ?? 'false') === 'true',
    'hour' => isset($settings['hour']) ? (int) $settings['hour'] : 8,
    'minute' => isset($settings['minute']) ? (int) $settings['minute'] : 0,
    'days' => $days,
    'installed_in_crontab' => $installed,
]);
