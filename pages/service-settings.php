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

    <div class="settings-grid">
        <?php if ($isAdmin): ?>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-shield-lock"></i></div>
            <div class="settings-card-title">Role Permissions</div>
            <p class="settings-card-desc">Choose what each role is allowed to do across the app.</p>
            <a href="#" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn" data-bs-toggle="modal" data-bs-target="#rolePermissionsModal">
                <i class="bi bi-gear me-2"></i>Manage
            </a>
        </div>
        <?php endif; ?>
        <?php if ($canAccessSystemSettings): ?>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-calendar2-week"></i></div>
            <div class="settings-card-title">Company Holidays</div>
            <p class="settings-card-desc">Manage firm-wide holidays and closures.</p>
            <a href="company-holidays.php" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn">
                <i class="bi bi-gear me-2"></i>Manage
            </a>
        </div>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-hdd-stack"></i></div>
            <div class="settings-card-title">Backup Configuration</div>
            <p class="settings-card-desc">Automated backup schedule and local storage location.</p>
            <a href="#" id="configureBackupBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn">
                <i class="bi bi-gear me-2"></i>Configure
            </a>
        </div>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-shield-lock"></i></div>
            <div class="settings-card-title">Security Policy</div>
            <p class="settings-card-desc">Password rules, login attempts, session timeout, 2FA.</p>
            <a href="#" id="configureSecurityBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn">
                <i class="bi bi-gear me-2"></i>Configure
            </a>
        </div>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-envelope"></i></div>
            <div class="settings-card-title">Email Notifications</div>
            <p class="settings-card-desc">SMTP configuration and notification frequency.</p>
            <a href="#" id="configureEmailBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn">
                <i class="bi bi-gear me-2"></i>Configure
            </a>
        </div>
        <div class="settings-card">
            <div class="settings-card-icon"><i class="bi bi-sun"></i></div>
            <div class="settings-card-title">Busy Season</div>
            <p class="settings-card-desc">
                <?php if (!empty($busySeasonSettings['start_date']) && !empty($busySeasonSettings['end_date'])): ?>
                    Active <?php echo date('M j, Y', strtotime($busySeasonSettings['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($busySeasonSettings['end_date'])); ?> (50 hrs/week allowance).
                <?php else: ?>
                    Raise the weekly hour allowance from 40 to 50 during a set date range.
                <?php endif; ?>
            </p>
            <a href="#" id="configureBusySeasonBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom settings-card-btn" data-bs-toggle="modal" data-bs-target="#busySeasonModal">
                <i class="bi bi-gear me-2"></i>Configure
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php if ($isAdmin): ?>
<?php include_once '../includes/modals/role_permissions_modal.php'; ?>
<?php endif; ?>
<?php if ($canAccessSystemSettings): ?>
<?php include_once '../includes/modals/backup_configuration_modal.php'; ?>
<?php include_once '../includes/modals/security_policy_modal.php'; ?>
<?php include_once '../includes/modals/email_configuration_modal.php'; ?>
<?php include_once '../includes/modals/busy_season_modal.php'; ?>
<?php endif; ?>

<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<?php if ($isAdmin): ?>
<script src="../assets/js/role_permissions.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
<?php if ($canAccessSystemSettings): ?>
<script src="../assets/js/backup_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/security_policy.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/email_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/busy_season.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
