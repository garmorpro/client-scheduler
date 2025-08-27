<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

// if (!$isAdmin && !$isManager) {
//     header("Location: my-schedule.php");
//     exit();
// }



// Get total users
$totalUsersQuery = "SELECT COUNT(*) AS total FROM ms_users";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsersRow = mysqli_fetch_assoc($totalUsersResult);
$totalUsers = $totalUsersRow['total'];

// Get users added in last 30 days
$newUsersQuery = "SELECT COUNT(*) AS recent FROM ms_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$newUsersResult = mysqli_query($conn, $newUsersQuery);
$newUsersRow = mysqli_fetch_assoc($newUsersResult);
$newUsers = $newUsersRow['recent'];

// Get total active users
$totalActiveUsersQuery = "SELECT COUNT(*) AS total FROM ms_users WHERE status = 'active'";
$totalActiveUsersResult = mysqli_query($conn, $totalActiveUsersQuery);
$totalActiveUsersRow = mysqli_fetch_assoc($totalActiveUsersResult);
$totalActiveUsers = $totalActiveUsersRow['total'];

// Get total inactive users
$totalInactiveUsersQuery = "SELECT COUNT(*) AS total FROM ms_users WHERE status = 'inactive'";
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


$usersql = "SELECT user_id, full_name, email, role, status, last_active 
        FROM ms_users 
        ORDER BY full_name ASC";
$userresult = mysqli_query($conn, $usersql);

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
    INNER JOIN ms_users u ON t.user_id = u.user_id
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
    <title>Service Dashboard</title>
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
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Service Account Dashboard</h3>
    <p class="text-muted mb-4">User management for service accounts</p>

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
                                    <td><input type="checkbox" class="selectUser" data-user-id="<?php echo $userrow['user_id']; ?>"></td>
                                    <td>
                                        <?php echo htmlspecialchars($userrow['full_name']); ?><br>
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
                            <tr><td colspan="5" class="text-center">No users found</td></tr>
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

        

        

    </div> <!-- end container -->
</div> <!-- end flex-grow -->


<?php include_once '../includes/modals/viewUserModal.php'; ?>


<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/promote_user.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/buld_delete_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/search_pagination.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
