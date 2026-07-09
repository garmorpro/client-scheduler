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

if (!$canManageEmployees) {
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

    <div class="emp-stat-row">
        <div class="eng-stat-card">
            <div class="eng-stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="eng-stat-title">Total Employees</div>
            <div class="eng-stat-value"><?php echo $totalEmployees; ?></div>
        </div>
        <?php foreach ($roleOrder as $roleKey): ?>
        <div class="eng-stat-card">
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
                </div>
            </div>

            <div class="user-table">
                <table id="user-table" class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllUsers"></th>
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
                            <tr>
                                <td><input type="checkbox" class="selectUser" data-user-id="<?php echo $userrow['user_id']; ?>" data-role="<?php echo strtolower($userrow['role']); ?>" data-user-name="<?php echo htmlspecialchars($userrow['full_name']); ?>"></td>
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
                                    <!-- View Button -->
                                    <a href="#" class="action-icon-btn view-user-btn text-decoration-none"
                                       data-bs-toggle="modal"
                                       data-bs-target="#viewUserModal"
                                       data-user-id="<?php echo $userrow['user_id']; ?>"
                                       title="View Details">
                                       <i class="bi bi-eye text-success"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="#" class="action-icon-btn edit-user-btn text-decoration-none"
                                       data-bs-toggle="modal"
                                       data-bs-target="#editUserModal"
                                       data-user-id="<?php echo $userrow['user_id']; ?>"
                                       data-full-name="<?php echo htmlspecialchars($userrow['full_name']); ?>"
                                       data-email="<?php echo htmlspecialchars($userrow['email']); ?>"
                                       data-role="<?php echo strtolower($userrow['role']); ?>"
                                       data-job-title="<?php echo htmlspecialchars($userrow['job_title'] ?? ''); ?>"
                                       data-status="<?php echo strtolower($userrow['status']); ?>"
                                       title="Edit Employee">
                                       <i class="bi bi-pencil-square text-primary"></i>
                                    </a>

                                    <!-- More Actions (Direct Reports / Promote-Demote / Delete) -->
                                    <div class="dropdown">
                                        <a href="#" class="action-icon-btn text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
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
                                            switch ($role) {
                                                case 'staff':
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="senior"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Senior</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="manager"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Manager</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="admin"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Admin</a></li>';
                                                    break;
                                                case 'senior':
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="staff"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Staff</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="manager"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Manager</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="admin"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Admin</a></li>';
                                                    break;
                                                case 'manager':
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="staff"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Staff</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="senior"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Senior</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="admin"><i class="bi bi-arrow-up-circle me-2 text-success"></i>Promote to Admin</a></li>';
                                                    break;
                                                case 'admin':
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="staff"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Staff</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="senior"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Senior</a></li>';
                                                    echo '<li><a class="dropdown-item promote-user" href="#" data-user-id="'.$userrow['user_id'].'" data-user-name="'.$userrow['full_name'].'" data-new-role="manager"><i class="bi bi-arrow-down-circle me-2 text-danger"></i>Demote to Manager</a></li>';
                                                    break;
                                            }
                                            ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-user-btn" href="#"
                                                   data-user-id="<?php echo $userrow['user_id']; ?>"
                                                   data-user-name="<?php echo htmlspecialchars($userrow['full_name']); ?>">
                                                   <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No users found</td></tr>
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

<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/promote_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/direct_reports_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/bulk_delete_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/delete_user.js?v=<?php echo time(); ?>"></script>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
