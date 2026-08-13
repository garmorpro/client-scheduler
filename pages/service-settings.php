<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$canAccessSystemSettings = user_has_permission($conn, 'access_system_settings');

if (!$canAccessSystemSettings && !$isAdmin) {
    header("Location: my-schedule.php");
    exit();
}

$busySeasonSettings = [];
if ($canAccessSystemSettings) {
    $busySeasonResult = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_master_key = 'busy_season'");
    if ($busySeasonResult) {
        while ($bsRow = $busySeasonResult->fetch_assoc()) {
            $busySeasonSettings[$bsRow['setting_key']] = $bsRow['setting_value'];
        }
    }
}

// backup_configuration_modal.php, security_policy_modal.php, and
// email_configuration_modal.php all read a bare $settings array (scoped to
// their own setting_master_key) to prefill their forms, but this page
// never actually populated it - meaning every field in all three has
// always rendered empty/default on load, regardless of what was actually
// saved (saving itself works fine, it's a completely separate JS/backend
// path - this was purely a "form never shows what's already saved" bug).
function fetchSettingsGroup(mysqli $conn, string $masterKey): array {
    $group = [];
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_master_key = ?");
    $stmt->bind_param('s', $masterKey);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $group[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
    return $group;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">System Settings</h3>
    <p class="text-muted mb-4">Permissions, backups, security policy, and email notifications</p>

    <div class="settings-grid-b">
        <?php if ($isAdmin): ?>
        <a href="#" class="settings-tile" data-bs-toggle="modal" data-bs-target="#rolePermissionsModal">
            <div class="settings-tile-icon"><i class="bi bi-shield-lock"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Role Permissions</div>
                <div class="settings-tile-desc">What each role can do</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <?php endif; ?>
        <?php if ($canAccessSystemSettings): ?>
        <a href="#" class="settings-tile" data-bs-toggle="modal" data-bs-target="#companyHolidaysModal">
            <div class="settings-tile-icon"><i class="bi bi-calendar2-week"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Company Holidays</div>
                <div class="settings-tile-desc">Firm-wide holidays &amp; closures</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" id="configureBackupBtn" class="settings-tile">
            <div class="settings-tile-icon"><i class="bi bi-hdd-stack"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Backup Configuration</div>
                <div class="settings-tile-desc">Schedule &amp; storage location</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" id="configureSecurityBtn" class="settings-tile">
            <div class="settings-tile-icon"><i class="bi bi-shield-lock"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Security Policy</div>
                <div class="settings-tile-desc">Passwords, lockout, 2FA</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" id="configureEmailBtn" class="settings-tile">
            <div class="settings-tile-icon"><i class="bi bi-envelope"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Email Notifications</div>
                <div class="settings-tile-desc">SMTP &amp; notification frequency</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" id="configureAuditNotificationBtn" class="settings-tile">
            <div class="settings-tile-icon"><i class="bi bi-clock-history"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Audit Notification Schedule</div>
                <div class="settings-tile-desc">When the due-date digest emails send</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" class="settings-tile" data-bs-toggle="modal" data-bs-target="#auditTypesModal">
            <div class="settings-tile-icon"><i class="bi bi-clipboard2-check"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Audit Types</div>
                <div class="settings-tile-desc">SOC, ISO, HIPAA &amp; other engagement types</div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <a href="#" id="configureBusySeasonBtn" class="settings-tile" data-bs-toggle="modal" data-bs-target="#busySeasonModal">
            <div class="settings-tile-icon"><i class="bi bi-sun"></i></div>
            <div class="settings-tile-info">
                <div class="settings-tile-title">Busy Season</div>
                <div class="settings-tile-desc">
                    <?php if (!empty($busySeasonSettings['start_date']) && !empty($busySeasonSettings['end_date'])): ?>
                        Active <?php echo date('M j, Y', strtotime($busySeasonSettings['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($busySeasonSettings['end_date'])); ?>
                    <?php else: ?>
                        50 hrs/week allowance window
                    <?php endif; ?>
                </div>
            </div>
            <i class="bi bi-chevron-right settings-tile-arrow"></i>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php if ($isAdmin): ?>
<?php include_once '../includes/modals/role_permissions_modal.php'; ?>
<?php endif; ?>
<?php if ($canAccessSystemSettings): ?>
<?php include_once '../includes/modals/company_holidays_modal.php'; ?>
<?php include_once '../includes/modals/holiday_modal.php'; ?>
<?php $settings = fetchSettingsGroup($conn, 'backup_config'); ?>
<?php include_once '../includes/modals/backup_configuration_modal.php'; ?>
<?php $settings = fetchSettingsGroup($conn, 'security_policy'); ?>
<?php include_once '../includes/modals/security_policy_modal.php'; ?>
<?php $settings = fetchSettingsGroup($conn, 'email'); ?>
<?php include_once '../includes/modals/email_configuration_modal.php'; ?>
<?php include_once '../includes/modals/busy_season_modal.php'; ?>
<?php include_once '../includes/modals/audit_types_modal.php'; ?>
<?php include_once '../includes/modals/audit_notification_schedule_modal.php'; ?>
<?php endif; ?>

<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<?php if ($isAdmin): ?>
<script src="../assets/js/role_permissions.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
<?php if ($canAccessSystemSettings): ?>
<script src="../assets/js/company_holidays_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/holiday_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/backup_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/security_policy.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/email_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/busy_season.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/audit_notification_schedule_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/audit_types_modal.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
