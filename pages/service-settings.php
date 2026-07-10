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

    <div class="container-fluid">
        <div class="row g-3">
            <?php if ($isAdmin): ?>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-shield-lock me-2"></i>Role Permissions</h6>
                        <p class="text-muted" style="font-size: 13px;">Choose what each role is allowed to do across the app.</p>
                    </div>
                    <a href="#" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#rolePermissionsModal">
                        <i class="bi bi-gear me-2"></i>Manage
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($canAccessSystemSettings): ?>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-calendar2-week me-2"></i>Company Holidays</h6>
                        <p class="text-muted" style="font-size: 13px;">Manage firm-wide holidays and closures.</p>
                    </div>
                    <a href="company-holidays.php" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);">
                        <i class="bi bi-gear me-2"></i>Manage
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-hdd-stack me-2"></i>Backup Configuration</h6>
                        <p class="text-muted" style="font-size: 13px;">Automated backup schedule and local storage location.</p>
                    </div>
                    <a href="#" id="configureBackupBtn" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);">
                        <i class="bi bi-gear me-2"></i>Configure
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-shield-lock me-2"></i>Security Policy</h6>
                        <p class="text-muted" style="font-size: 13px;">Password rules, login attempts, session timeout, 2FA.</p>
                    </div>
                    <a href="#" id="configureSecurityBtn" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);">
                        <i class="bi bi-gear me-2"></i>Configure
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-envelope me-2"></i>Email Notifications</h6>
                        <p class="text-muted" style="font-size: 13px;">SMTP configuration and notification frequency.</p>
                    </div>
                    <a href="#" id="configureEmailBtn" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);">
                        <i class="bi bi-gear me-2"></i>Configure
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6><i class="bi bi-sun me-2"></i>Busy Season</h6>
                        <p class="text-muted" style="font-size: 13px;">
                            <?php if (!empty($busySeasonSettings['start_date']) && !empty($busySeasonSettings['end_date'])): ?>
                                Active <?php echo date('M j, Y', strtotime($busySeasonSettings['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($busySeasonSettings['end_date'])); ?> (50 hrs/week allowance).
                            <?php else: ?>
                                Raise the weekly hour allowance from 40 to 50 during a set date range.
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="#" id="configureBusySeasonBtn" class="badge text-white p-2 text-decoration-none fw-medium align-self-start" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#busySeasonModal">
                        <i class="bi bi-gear me-2"></i>Configure
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
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
