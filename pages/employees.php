<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$canManageEmployees = user_has_permission($conn, 'manage_employees');
$canViewEmployees = user_has_permission($conn, 'view_employees');

if (!$canViewEmployees) {
    header("Location: my-schedule.php");
    exit();
}

// System/service accounts (email contains "admin") are excluded from the
// employee roster - they're not real employees to manage day-to-day.
$usersql = "SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.last_active, u.job_title,
                   u.manager_id, m.full_name AS manager_name
        FROM users u
        LEFT JOIN users m ON u.manager_id = m.user_id
        WHERE u.email NOT LIKE '%admin%'
        ORDER BY u.full_name ASC";
$userresult = mysqli_query($conn, $usersql);

$managersql = "SELECT user_id, full_name FROM users WHERE role = 'manager' ORDER BY full_name ASC";
$managerresult = mysqli_query($conn, $managersql);
$availableManagers = [];
while ($mrow = mysqli_fetch_assoc($managerresult)) {
    $availableManagers[] = $mrow;
}

$roleOrder = ['admin', 'manager', 'senior', 'staff', 'intern', 'crm_team'];
$roleIcons = [
    'admin' => 'bi-shield-lock-fill',
    'manager' => 'bi-person-badge-fill',
    'senior' => 'bi-person-check-fill',
    'staff' => 'bi-person-fill',
    'intern' => 'bi-mortarboard-fill',
    'crm_team' => 'bi-headset',
];
$roleCounts = array_fill_keys($roleOrder, 0);
$totalEmployees = 0;
$roleCountResult = mysqli_query($conn, "SELECT role, COUNT(*) AS cnt FROM users WHERE email NOT LIKE '%admin%' GROUP BY role");
while ($rcRow = mysqli_fetch_assoc($roleCountResult)) {
    $roleKey = strtolower($rcRow['role']);
    $roleCounts[$roleKey] = (int) $rcRow['cnt'];
    $totalEmployees += (int) $rcRow['cnt'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Employees</h3>
    <p class="text-muted mb-4">Manage employee accounts, roles, and permissions</p>

    <div class="emp-stat-row" id="empStatRow">
        <div class="eng-stat-card role-filter-tile active" data-role-filter="all">
            <div class="eng-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="eng-stat-title">Total Employees</div>
            <div class="eng-stat-value"><?php echo $totalEmployees; ?></div>
        </div>
        <?php foreach ($roleOrder as $roleKey): ?>
        <div class="eng-stat-card role-filter-tile" data-role-filter="<?php echo $roleKey; ?>">
            <div class="eng-stat-icon"><i class="bi <?php echo $roleIcons[$roleKey]; ?>"></i></div>
            <div class="eng-stat-title"><?php echo htmlspecialchars(role_label($roleKey)); ?></div>
            <div class="eng-stat-value"><?php echo $roleCounts[$roleKey]; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="container-fluid">
        <div id="employees" class="tab-content">
            <div class="user-management-header d-flex justify-content-between align-items-center">
                <!-- Left -->
                <div class="titles">
                    <p class="text-black mb-0"><strong>Employee Management</strong></p>
                    <p class="mb-0">Manage employee accounts, roles, and permissions</p>
                </div>

                <!-- Middle (Search) -->
                <div class="user-search mx-3" style="flex: 1; max-width: 600px;">
                    <input type="text" id="userSearch" class="form-control form-control-sm"
                           placeholder="Search employees..." minlength="3">
                </div>

                <!-- Right -->
                <div class="user-management-buttons d-flex align-items-center gap-2">
                    <a href="#" id="openOrgChartBtn" class="badge p-2 text-decoration-none fw-medium btn-outline-custom">
                        <i class="bi bi-diagram-3 me-3"></i>Org Chart
                    </a>
                    <?php if ($canManageEmployees): ?>
                    <a href="#" id="bulkDeleteBtn" class="badge text-white p-2 text-decoration-none fw-medium"
                       style="font-size: .875rem; background-color: darkred; display:none;">
                      <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedCount">0</span>)
                    </a>
                    <a href="#" class="badge p-2 text-decoration-none fw-medium btn-outline-custom" data-bs-toggle="modal" data-bs-target="#importUsersModal">
                        <i class="bi bi-upload me-3"></i>Import Users
                    </a>
                    <a href="#" class="badge p-2 text-decoration-none fw-medium btn-dark-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-person-plus me-3"></i>Add Employee
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="user-table">
                <table id="user-table" class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php if ($canManageEmployees): ?><th><input type="checkbox" id="selectAllUsers"></th><?php endif; ?>
                            <th>Employee Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($userresult) > 0): ?>
                        <?php while ($userrow = mysqli_fetch_assoc($userresult)): ?>
                            <tr class="user-row" data-role="<?php echo strtolower($userrow['role']); ?>">
                                <?php if ($canManageEmployees): ?><td><input type="checkbox" class="selectUser" data-user-id="<?php echo $userrow['user_id']; ?>" data-role="<?php echo strtolower($userrow['role']); ?>" data-user-name="<?php echo htmlspecialchars($userrow['full_name']); ?>"></td><?php endif; ?>
                                <td>
                                    <span class="emp-name-wrap">
                                        <?php echo htmlspecialchars($userrow['full_name']); ?>
                                        <?php if (in_array(strtolower($userrow['role']), ['staff', 'senior'], true) && $userrow['manager_name']): ?>
                                            <span class="reports-to-hint" data-tooltip="Reports to <?php echo htmlspecialchars($userrow['manager_name']); ?>">
                                                <i class="bi bi-diagram-2"></i>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                    <?php if (!empty($userrow['job_title'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($userrow['job_title']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-role">
                                        <?php echo htmlspecialchars(role_label($userrow['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo strtolower($userrow['status']) === 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($userrow['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        if (empty($userrow['last_active']) || $userrow['last_active'] === null) {
                                            echo "Never";
                                        } else {
                                            echo date("n/j/Y", strtotime($userrow['last_active']));
                                        }
                                    ?>
                                </td>
                                <td class="table-actions">
                                    <div class="table-actions-inner">
                                    <!-- Eye icon retired now that the whole row opens the View panel
                                         (see the row-click handler below); kept as a hidden trigger so
                                         Bootstrap's data-bs-toggle wiring doesn't need to be duplicated. -->
                                    <a href="#" class="action-icon-btn view-user-btn text-decoration-none" style="display:none;"
                                       data-bs-toggle="offcanvas"
                                       data-bs-target="#viewUserModal"
                                       data-user-id="<?php echo $userrow['user_id']; ?>"
                                       title="View Details">
                                       <i class="bi bi-eye text-success"></i>
                                    </a>

                                    <?php if ($canManageEmployees): ?>
                                    <!-- All row actions now live in one dropdown, including Edit - no
                                         separate icon buttons sitting outside the menu. -->
                                    <div class="dropdown">
                                        <a href="#" class="action-icon-btn text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item edit-user-btn" href="#"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#editUserModal"
                                                   data-user-id="<?php echo $userrow['user_id']; ?>"
                                                   data-full-name="<?php echo htmlspecialchars($userrow['full_name']); ?>"
                                                   data-email="<?php echo htmlspecialchars($userrow['email']); ?>"
                                                   data-role="<?php echo strtolower($userrow['role']); ?>"
                                                   data-job-title="<?php echo htmlspecialchars($userrow['job_title'] ?? ''); ?>"
                                                   data-status="<?php echo strtolower($userrow['status']); ?>">
                                                   <i class="bi bi-pencil-square me-2 text-primary"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if (strtolower($userrow['role']) === 'manager'): ?>
                                            <li>
                                                <a class="dropdown-item direct-reports-btn" href="#"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#directReportsModal"
                                                   data-manager-id="<?php echo $userrow['user_id']; ?>"
                                                   data-manager-name="<?php echo htmlspecialchars($userrow['full_name']); ?>">
                                                   <i class="bi bi-diagram-2 me-2"></i>Direct Reports
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            <?php
                                            $role = strtolower($userrow['role']);
                                            // Escaped once here since it's reused across every item below - the
                                            // raw full_name was previously interpolated straight into these
                                            // data-* attributes, letting a quote character in a name (e.g. from
                                            // CSV import) break out and inject arbitrary HTML/JS.
                                            $safeName = htmlspecialchars($userrow['full_name']);
                                            $safeId = (int)$userrow['user_id'];
                                            $rankedRoles = ['staff', 'senior', 'manager', 'admin'];
                                            // "Change role" - a single item with a hover flyout listing every
                                            // other role, replacing the old flat Promote-to-X/Demote-to-X list
                                            // (which forced you to scan up/down arrows and red/green to find the
                                            // right one). Only shown for the ranked roles; CRM Team has no ladder.
                                            if (in_array($role, $rankedRoles, true)):
                                            ?>
                                            <li class="dropdown-submenu">
                                                <a class="dropdown-item role-submenu-trigger" href="#" tabindex="-1">
                                                    <span><i class="bi bi-arrow-left-right me-2"></i>Change Role</span>
                                                    <i class="bi bi-chevron-right role-submenu-chevron"></i>
                                                </a>
                                                <ul class="role-submenu">
                                                    <?php foreach ($rankedRoles as $r): ?>
                                                        <?php if ($r === $role): ?>
                                                        <li class="role-submenu-current"><?php echo ucfirst($r); ?><i class="bi bi-check2"></i></li>
                                                        <?php else: ?>
                                                        <li><a class="dropdown-item promote-user" href="#" data-user-id="<?php echo $safeId; ?>" data-user-name="<?php echo $safeName; ?>" data-new-role="<?php echo $r; ?>"><?php echo ucfirst($r); ?></a></li>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php endif; ?>
                                            <?php if (strtolower($userrow['status']) === 'active'): ?>
                                            <li>
                                                <a class="dropdown-item text-warning offboard-user-btn" href="#"
                                                   data-user-id="<?php echo $safeId; ?>"
                                                   data-user-name="<?php echo $safeName; ?>">
                                                   <i class="bi bi-box-arrow-right me-2"></i>Offboard
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item text-danger delete-user-btn" href="#"
                                                   data-user-id="<?php echo $userrow['user_id']; ?>"
                                                   data-user-name="<?php echo htmlspecialchars($userrow['full_name']); ?>">
                                                   <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="<?php echo $canManageEmployees ? 6 : 5; ?>" class="text-center">No users found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <nav>
                <ul id="pagination-users" class="pagination justify-content-center mt-3"></ul>
            </nav>
        </div>
    </div>
</div>

<?php include_once '../includes/modals/viewUserModal.php'; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php include_once '../includes/modals/direct_reports_modal.php'; ?>
<?php include_once '../includes/modals/add_user_modal.php'; ?>
<?php include_once '../includes/modals/edit_user_modal.php'; ?>
<?php include_once '../includes/modals/import_users_modal.php'; ?>
<?php include_once '../includes/modals/org_chart_modal.php'; ?>

<script>
// Whole row opens the View User panel, same click-the-row pattern used on
// Engagements/Clients - delegated on the tbody (rather than bound per-row)
// since search_pagination.js only shows/hides existing rows, but this way
// nothing has to be re-wired if that ever changes. Excludes the actions
// cell and the select-checkbox so Edit/Offboard/Delete/select still work.
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('#user-table tbody');
    if (!tbody) return;
    tbody.addEventListener('click', (e) => {
        const row = e.target.closest('tr.user-row');
        if (!row) return;
        if (e.target.closest('.table-actions') || e.target.closest('input[type="checkbox"]')) return;
        const viewBtn = row.querySelector('.view-user-btn');
        if (viewBtn) viewBtn.click();
    });
});
</script>
<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/org_chart_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/promote_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/direct_reports_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/bulk_delete_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/delete_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/offboard_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/search_pagination.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/add_user_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/edit_user_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/import_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/read_bulk_import_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app_alerts.js?v=<?php echo time(); ?>"></script>

</body>
</html>
