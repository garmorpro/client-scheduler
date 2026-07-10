<!-- sidebar.php -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/session_init.php';
}
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
if (!isset($conn)) {
    require_once __DIR__ . '/../includes/db.php';
}
require_once __DIR__ . '/../includes/permissions.php';

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isServiceAccount = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'service_account';
$currentPage = basename($_SERVER['PHP_SELF']);

$canManageEmployees = user_has_permission($conn, 'manage_employees');
$canManageClientsEngagements = user_has_permission($conn, 'manage_clients_engagements');
$canApproveTimeOff = user_has_permission($conn, 'approve_time_off');
$canAccessSystemSettings = user_has_permission($conn, 'access_system_settings');
$canSeeSettingsMenu = $canManageEmployees || $canApproveTimeOff || $canAccessSystemSettings;

$pendingTimeOffCount = 0;
if ($canApproveTimeOff) {
    // Non-admin reviewers only see pending counts for staff/seniors assigned to them; admins see everything.
    if (!$isAdmin) {
        $pendingCountStmt = $conn->prepare("
            SELECT COUNT(DISTINCT COALESCE(t.request_group, CONCAT('single-', t.timeoff_id))) AS cnt
            FROM time_off t
            JOIN users u ON t.user_id = u.user_id
            WHERE t.is_global_timeoff = 0 AND t.status = 'pending' AND u.manager_id = ?
        ");
        $pendingCountStmt->bind_param('i', $_SESSION['user_id']);
        $pendingCountStmt->execute();
        $pendingTimeOffCount = (int) $pendingCountStmt->get_result()->fetch_assoc()['cnt'];
        $pendingCountStmt->close();
    } else {
        $pendingCountResult = $conn->query("
            SELECT COUNT(DISTINCT COALESCE(request_group, CONCAT('single-', timeoff_id))) AS cnt
            FROM time_off
            WHERE is_global_timeoff = 0 AND status = 'pending'
        ");
        if ($pendingCountResult) {
            $pendingTimeOffCount = (int) $pendingCountResult->fetch_assoc()['cnt'];
        }
    }
}
?>
<script>window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
<script src="../assets/js/csrf_fetch.js"></script>

<div class="sidebar" style="width: 250px; height: 100vh; padding: 20px 14px; overflow-y: auto;">

    <div>
        <!-- Branding -->
        <div class="d-flex align-items-center justify-content-between" style="padding: 2px 6px 22px;">
            <div class="d-flex align-items-center">
                <div class="icon-bubble rounded d-flex align-items-center justify-content-center me-2">
                    <i class="bi bi-calendar2-week"></i>
                </div>
                <div class="side-header-text">
                    <h5 class="mb-0 fw-bold" style="font-size: 14.5px; letter-spacing: -0.01em;">AARC-360</h5>
                    <small class="text-muted" style="font-size: 11px;">Schedule Manager</small>
                </div>
            </div>
            <?php
            if (!isset($_SESSION['theme'])) {
                $_SESSION['theme'] = $user_theme ?? 'light';
            }
            $themeClass = $_SESSION['theme'] === 'dark' ? 'dark-mode' : '';
            ?>
            <div class="sidebar-theme-toggle">
                <i id="themeToggle" class="bi theme-icon <?= $_SESSION['theme'] === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>" style="cursor:pointer; font-size: 15px;"></i>
            </div>
        </div>

        <!-- Nav Links -->
        <?php if (!$isServiceAccount): ?>
        <ul class="sidebar-nav-group">
            <li class="nav-item <?php if ($isAdmin) echo 'd-none'; ?>">
                <a href="my-schedule.php" class="sidebar-link <?= $currentPage == 'my-schedule.php' ? 'active' : '' ?>">
                    <i class="bi bi-person"></i>
                    My Schedule
                </a>
            </li>
            <li class="nav-item">
                <a href="master-schedule.php" class="sidebar-link <?= $currentPage == 'master-schedule.php' ? 'active' : '' ?>">
                    <i class="bi bi-calendar-range"></i>
                    Master Schedule
                </a>
            </li>
            <li class="nav-item">
                <a href="request-time-off.php" class="sidebar-link <?= $currentPage == 'request-time-off.php' ? 'active' : '' ?>">
                    <i class="bi bi-airplane"></i>
                    Request Time Off
                </a>
            </li>
            <?php if ($canManageClientsEngagements): ?>
            <li class="nav-item">
                <a href="client-management.php" class="sidebar-link <?= $currentPage == 'client-management.php' ? 'active' : '' ?>">
                    <i class="bi bi-building-gear"></i>
                    Clients
                </a>
            </li>
            <li class="nav-item">
                <a href="engagement-management.php" class="sidebar-link <?= $currentPage == 'engagement-management.php' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    Engagements
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if ($canSeeSettingsMenu): ?>
        <ul class="sidebar-nav-group">
            <li class="nav-item">
                <?php
                    $settingsPages = ['company-holidays.php', 'service-settings.php', 'time-off-requests.php', 'employees.php'];
                    $isActive = in_array($currentPage, $settingsPages);
                ?>
                <a class="sidebar-link" data-bs-toggle="collapse" href="#settingsDropdown" role="button" aria-expanded="<?= $isActive ? 'true' : 'false' ?>" aria-controls="settingsDropdown">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <div class="collapse <?= $isActive ? 'show' : '' ?>" id="settingsDropdown">
                    <ul class="sidebar-submenu">
                        <?php if ($canManageEmployees): ?>
                        <li><a href="employees.php" class="sidebar-sublink <?= $currentPage == 'employees.php' ? 'active' : '' ?>">Employees</a></li>
                        <?php endif; ?>
                        <?php if ($canApproveTimeOff): ?>
                        <li><a href="time-off-requests.php" class="sidebar-sublink <?= $currentPage == 'time-off-requests.php' ? 'active' : '' ?>">
                            Time Off Requests
                            <?php if ($pendingTimeOffCount > 0): ?>
                                <span class="sidebar-badge"><?= $pendingTimeOffCount ?></span>
                            <?php endif; ?>
                        </a></li>
                        <?php endif; ?>
                        <li><a href="company-holidays.php" class="sidebar-sublink <?= $currentPage == 'company-holidays.php' ? 'active' : '' ?>">Company Holidays</a></li>
                        <?php if ($canAccessSystemSettings || $isAdmin): ?>
                        <li><a href="service-settings.php" class="sidebar-sublink <?= $currentPage == 'service-settings.php' ? 'active' : '' ?>">System Settings</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
        </ul>
        <?php endif; ?>
        <?php endif; // !$isServiceAccount ?>
        <?php if ($isServiceAccount): ?>
        <ul class="sidebar-nav-group">
            <li class="nav-item">
                <a href="service-settings.php" class="sidebar-link <?= $currentPage == 'service-settings.php' ? 'active' : '' ?>">
                    <i class="bi bi-shield"></i>
                    Service Dashboard
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>

    <!-- Bottom User Info -->
    <div class="sidebar-footer">
        <div class="sidebar-account" data-bs-toggle="modal" data-bs-target="#viewProfileModal" data-user-id="<?php echo $_SESSION['user_id']; ?>">
            <div class="sidebar-avatar">
                <?php
                $fullName = $_SESSION['full_name'] ?? '';
                $initials = '';
                $nameParts = explode(' ', $fullName);
                if (isset($nameParts[0])) {
                    $initials .= strtoupper($nameParts[0][0]);
                }
                if (isset($nameParts[1])) {
                    $initials .= strtoupper($nameParts[1][0]);
                }
                echo $initials;
                ?>
            </div>
            <div class="sidebar-account-meta">
                <div class="sidebar-fullname fw-semibold" style="font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo $_SESSION['full_name']; ?></div>
                <small class="sidebar-role text-muted" style="font-size: 11px;"><?php echo htmlspecialchars(role_label($_SESSION['user_role'] ?? '')); ?></small>
            </div>
            <a href="/auth/logout.php" class="sidebar-logout" aria-label="Log out" onclick="event.stopPropagation(); showLogoutOverlay();">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

</div>

<div class="app-logout-overlay" id="appLogoutOverlay">
    <div class="app-logout-spinner"></div>
    <div class="app-logout-text">Signing out&hellip;</div>
</div>
<script>
    function showLogoutOverlay() {
        document.getElementById('appLogoutOverlay').classList.add('show');
    }
</script>
