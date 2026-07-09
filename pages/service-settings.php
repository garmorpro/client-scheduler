<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!$isAdmin && !$isManager) {
    header("Location: my-schedule.php");
    exit();
}



// Get total users
$totalUsersQuery = "SELECT COUNT(*) AS total FROM users";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsersRow = mysqli_fetch_assoc($totalUsersResult);
$totalUsers = $totalUsersRow['total'];

// Get users added in last 30 days
$newUsersQuery = "SELECT COUNT(*) AS recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$newUsersResult = mysqli_query($conn, $newUsersQuery);
$newUsersRow = mysqli_fetch_assoc($newUsersResult);
$newUsers = $newUsersRow['recent'];

// Get total active users
$totalActiveUsersQuery = "SELECT COUNT(*) AS total FROM users WHERE status = 'active'";
$totalActiveUsersResult = mysqli_query($conn, $totalActiveUsersQuery);
$totalActiveUsersRow = mysqli_fetch_assoc($totalActiveUsersResult);
$totalActiveUsers = $totalActiveUsersRow['total'];

// Get total inactive users
$totalInactiveUsersQuery = "SELECT COUNT(*) AS total FROM users WHERE status = 'inactive'";
$totalInactiveUsersResult = mysqli_query($conn, $totalInactiveUsersQuery);
$totalInactiveUsersRow = mysqli_fetch_assoc($totalInactiveUsersResult);
$totalInactiveUsers = $totalInactiveUsersRow['total'];

// Total Confirmed engagements
$totalConfirmedEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status = 'confirmed'";
$totalConfirmedResult = mysqli_query($conn, $totalConfirmedEngagementsQuery);
$totalConfirmedRow = mysqli_fetch_assoc($totalConfirmedResult);
$totalConfirmedEngagements = $totalConfirmedRow['total'];

// Total Pending engagements
$totalPendingEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status = 'pending'";
$totalPendingResult = mysqli_query($conn, $totalPendingEngagementsQuery);
$totalPendingRow = mysqli_fetch_assoc($totalPendingResult);
$totalPendingEngagements = $totalPendingRow['total'];

// Total Confirmed engagements
$totalNotConfirmedEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status = 'not_confirmed'";
$totalNotConfirmedResult = mysqli_query($conn, $totalNotConfirmedEngagementsQuery);
$totalNotConfirmedRow = mysqli_fetch_assoc($totalNotConfirmedResult);
$totalNotConfirmedEngagements = $totalNotConfirmedRow['total'];

// Total engagements
$totalEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements";
$totalResult = mysqli_query($conn, $totalEngagementsQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalEngagements = $totalRow['total'];

// Total assigned
$assignedEngagementsQuery = "
    SELECT COUNT(DISTINCT e.engagement_id) AS total_assigned
    FROM engagements e
    JOIN entries a ON e.engagement_id = a.engagement_id
";

$assignedResult = mysqli_query($conn, $assignedEngagementsQuery);
$assignedRow = mysqli_fetch_assoc($assignedResult);
$totalAssigned = $assignedRow['total_assigned'];

// Total not assigned
$notAssignedEngagementsQuery = "
    SELECT COUNT(*) AS total_not_assigned
    FROM engagements e
    LEFT JOIN entries a ON e.engagement_id = a.engagement_id
    WHERE a.engagement_id IS NULL
";

$notAssignedResult = mysqli_query($conn, $notAssignedEngagementsQuery);
$notAssignedRow = mysqli_fetch_assoc($notAssignedResult);
$totalNotAssigned = $notAssignedRow['total_not_assigned'];


$usersql = "SELECT u.user_id, u.full_name, u.email, u.role, u.status, u.last_active,
                   u.manager_id, m.full_name AS manager_name
        FROM users u
        LEFT JOIN users m ON u.manager_id = m.user_id
        ORDER BY u.full_name ASC";
$userresult = mysqli_query($conn, $usersql);

$managersql = "SELECT user_id, full_name FROM users WHERE role = 'manager' ORDER BY full_name ASC";
$managerresult = mysqli_query($conn, $managersql);
$availableManagers = [];
while ($mrow = mysqli_fetch_assoc($managerresult)) {
    $availableManagers[] = $mrow;
}

$engagementSQL = "
  SELECT 
    e.engagement_id,
    e.client_name,
    e.budgeted_hours,
    e.status,
    e.notes,
    COALESCE(SUM(a.assigned_hours), 0) AS total_assigned_hours
  FROM engagements e
  LEFT JOIN entries a ON e.engagement_id = a.engagement_id
  GROUP BY e.engagement_id, e.client_name, e.budgeted_hours, e.status, e.notes
  ORDER BY e.client_name ASC
";

$engagementResults = mysqli_query($conn, $engagementSQL);


$settings = [];
$settingSQL = "SELECT setting_key, setting_value FROM settings";
$settingResult = $conn->query($settingSQL);

if ($settingResult) {
    while ($S_row = $settingResult->fetch_assoc()) {
        $settings[$S_row['setting_key']] = $S_row['setting_value'];
    }
}

// Fetch individual time off entries
$timeoffEntries = [];
$sql = "
    SELECT t.timeoff_id, t.user_id, t.week_start, t.assigned_hours, t.timeoff_note, t.created, t.last_updated,
           u.full_name, u.email
    FROM time_off t
    INNER JOIN users u ON t.user_id = u.user_id
    WHERE t.is_global_timeoff = 0
    ORDER BY t.week_start DESC
";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $timeoffEntries[] = [
            'timeoff_id' => $row['timeoff_id'],
            'employee_name' => $row['full_name'],
            'employee_email' => $row['email'],
            'week_start' => $row['week_start'],
            'hours' => $row['assigned_hours'],
            'note' => $row['timeoff_note'], // not used for individual PTO, but still show it
            'created' => $row['created'],
            'last_updated' => $row['last_updated']
        ];
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

    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>

    <style>
        .save-btn-hover:hover {
            background-color: rgb(64,133,88) !important;
        }
        .save-btn-hover:hover i,
        .delete-btn-hover:hover i {
            color: white !important;
        }
        .delete-btn-hover:hover {
            background-color: rgb(203,68,74) !important;
        }
    </style>
</head>
<body class="d-flex  <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">System Settings</h3>
    <p class="text-muted mb-4">Employee management and system configuration</p>

    <div class="container-fluid">

        <!-- employee management -->
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
                        <?php if ($isAdmin): ?>
                        <a href="#" class="badge p-2 text-decoration-none fw-medium btn-outline-custom" data-bs-toggle="modal" data-bs-target="#rolePermissionsModal">
                            <i class="bi bi-shield-lock me-3"></i>Role Permissions
                        </a>
                        <a href="#" class="badge p-2 text-decoration-none fw-medium btn-outline-custom" data-bs-toggle="modal" data-bs-target="#importUsersModal">
                            <i class="bi bi-upload me-3"></i>Import Users
                        </a>
                        <a href="#" class="badge p-2 text-decoration-none fw-medium btn-dark-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus me-3"></i>Add User
                        </a>
                        <?php endif; ?>
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
                                        </span><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($userrow['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-role">
                                            <?php echo ucfirst(htmlspecialchars($userrow['role'])); ?>
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
                                        <!-- View Button -->
                                        <a href="#" class="view-user-btn text-decoration-none" 
                                           data-bs-toggle="modal" 
                                           data-bs-target="#viewUserModal" 
                                           data-user-id="<?php echo $userrow['user_id']; ?>">
                                           <i class="bi bi-eye text-success"></i>
                                        </a>

                                        <?php if (strtolower($userrow['role']) === 'manager'): ?>
                                        <!-- Direct Reports Button -->
                                        <a href="#" class="direct-reports-btn text-decoration-none"
                                           data-bs-toggle="modal"
                                           data-bs-target="#directReportsModal"
                                           data-manager-id="<?php echo $userrow['user_id']; ?>"
                                           data-manager-name="<?php echo htmlspecialchars($userrow['full_name']); ?>"
                                           title="Manage Direct Reports">
                                           <i class="bi bi-diagram-2 text-primary"></i>
                                        </a>
                                        <?php endif; ?>

                                        <!-- Promote/Role Dropdown -->
                                        <div class="dropdown d-inline">
                                            <a href="#" class="text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-person-up text-primary"></i>
                                            </a>
                                            <ul class="dropdown-menu">
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
                                            </ul>
                                        </div>
                                              
                                        <!-- Delete Button -->
                                        <a href="#" class="delete-user-btn text-decoration-none" data-user-id="<?php echo $userrow['user_id']; ?>">
                                            <i class="bi bi-trash text-danger"></i>
                                        </a>
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
                 <style>
                  /* Space between buttons */

                 </style>
                <nav>
                    <ul id="pagination-users" class="pagination justify-content-center mt-3"></ul>
                </nav>
            </div>
        <!-- end employee management -->

        <?php if ($isAdmin): ?>
        <!-- system settings -->
        <div id="system-settings" class="mt-5">
            <div class="titles mb-3">
                <p class="text-black mb-0"><strong>System Settings</strong></p>
                <p class="mb-0">Configure backups, security policy, and email notifications</p>
            </div>

            <div class="row g-3">
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
            </div>
        </div>
        <!-- end system settings -->
        <?php endif; ?>

    </div> <!-- end container -->
</div> <!-- end flex-grow -->


<?php include_once '../includes/modals/viewUserModal.php'; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php include_once '../includes/modals/direct_reports_modal.php'; ?>
<?php if ($isAdmin): ?>
<?php include_once '../includes/modals/backup_configuration_modal.php'; ?>
<?php include_once '../includes/modals/security_policy_modal.php'; ?>
<?php include_once '../includes/modals/email_configuration_modal.php'; ?>
<?php include_once '../includes/modals/add_user_modal.php'; ?>
<?php include_once '../includes/modals/import_users_modal.php'; ?>
<?php include_once '../includes/modals/role_permissions_modal.php'; ?>
<?php endif; ?>


<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/promote_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/direct_reports_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/bulk_delete_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/search_pagination.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<?php if ($isAdmin): ?>
<script src="../assets/js/backup_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/security_policy.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/email_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/add_user_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/import_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/read_bulk_import_users.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
