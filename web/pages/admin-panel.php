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

if (!$isAdmin && !$isManager) {
    header("Location: my-schedule.php");
    exit();
}



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
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
</head>
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Administrative Dashboard</h3>
    <p class="text-muted mb-4">System overview and user management for Admin User</p>

    <div class="container-fluid">

        <!-- Stat cards -->
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-sub">+<?php echo $newUsers; ?> this month</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-person-up"></i></div>
                        <div class="stat-title">Active Users</div>
                        <div class="stat-value"><?php echo $totalActiveUsers; ?></div>
                        <div class="stat-sub"><?php echo $totalInactiveUsers; ?> inactive users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="stat-title">Confirmed Engagements</div>
                        <div class="stat-value"><?php echo $totalConfirmedEngagements; ?></div>
                        <div class="stat-sub"><?php echo $totalPendingEngagements; ?> pending <i class="bi bi-dot"></i> <?php echo $totalNotConfirmedEngagements; ?> not confirmed</div>
                    </div>
                </div>
                <?php
                // Ensure totalEngagements is not zero to avoid division by zero
                if ($totalEngagements > 0) {
                    $percentageAssigned = round(($totalAssigned / $totalEngagements) * 100);
                } else {
                    $percentageAssigned = 0;
                }
                ?>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-title">Engagement Status</div>
                        <div class="stat-value"><?php echo $percentageAssigned; ?>%</div>
                        <div class="util-bar mt-2">
                            <div class="util-bar-fill" style="width: <?php echo $percentageAssigned; ?>%"></div>
                        </div>
                        <div class="stat-sub mt-2">
                            <?php echo $totalAssigned; ?> assigned <i class="bi bi-dot"></i> <?php echo $totalNotAssigned; ?> not assigned
                        </div>
                    </div>
                </div>
            </div>
        <!-- end stats cards -->

        <!-- Tabs -->
          <div class="custom-tabs">
              <a href="#employees" class=" text-decoration-none <?php if ($isManager) echo 'd-none'; ?> <?php if ($isAdmin) echo 'active'; ?>" data-tab="employees">Employee Management</a>
              <!-- <a href="#engagement" class=" text-decoration-none <?php if ($isManager) echo 'active'; ?>" data-tab="engagements">Engagement Management</a> -->
              <a href="#time_off" class=" text-decoration-none" data-tab="time_off">Global PTO Management</a>
              <a href="#activity" class=" text-decoration-none" data-tab="activity">System Activity</a>
              <a href="#settings" class=" text-decoration-none <?php if ($isManager) echo 'd-none'; ?>" data-tab="settings">Settings</a>
          </div>
        <!-- end Tabs -->

        <!-- employee management -->
            <div id="employees" class="tab-content <?php if ($isManager) echo 'd-none'; ?>">
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

        <!-- time off -->
            <div id="time_off" class="tab-content">
                <div class="global-pto-header mb-3 d-flex justify-content-between align-items-center">
                    <div class="titles">
                        <p class="text-black mb-0"><strong>Global PTO Management</strong></p>
                        <p class="mb-0">Create global time off entries for multiple weeks and days</p>
                    </div>
                    <div>
                        <a href="#" class="badge text-white p-2 text-decoration-none fw-normal" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#addGlobalPtoModal">
                            <i class="bi bi-plus-circle me-3"></i>Add Global PTO
                        </a>
                    </div>
                </div>
                <div id="global-pto-table"></div>
            </div>
        <!-- end time off -->

        <!-- system activty -->
            <div id="activity" class="tab-content">
                <div class="activity-header mb-3">
                    <div class="titles">
                        <p class="text-black"><strong>System Activity Log</strong></p>
                        <p>Recent system events and user activities</p>
                    </div>
                </div>

                <div id="activity-list">
                    <?php

                    // Fetch all activities, newest first
                    $sql = "SELECT event_type, full_name, title, description, created_at 
                            FROM system_activity_log 
                            ORDER BY created_at DESC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $icon = "";
                            $color = "";
                        
                            switch ($row['event_type']) {
                                case 'failed_login':
                                    $icon = 'bi-shield';
                                    $color = 'rgb(220,53,69)'; // red
                                    break;
                                
                                case 'successful_login':
                                    $icon = 'bi-shield';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'entry_created':
                                    $icon = 'bi-file-earmark-plus';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'entry_deleted':
                                    $icon = 'bi-file-earmark-minus';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;
                                
                                case 'entry_updated':
                                    $icon = 'bi-file-earmark-check';
                                    $color = 'rgb(161,77,253)'; // green
                                    break;
                                
                                case 'engagement_created':
                                    $icon = 'bi-building-add';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'engagement_deleted':
                                    $icon = 'bi-building-dash';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;
                                
                                case 'engagement_updated':
                                    $icon = 'bi-building-check';
                                    $color = 'rgb(161,77,253)'; // green
                                    break;
                                
                                case 'user_created':
                                    $icon = 'bi-person-add';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'user_deleted':
                                    $icon = 'bi-person-dash';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;
                                
                                case 'user_updated':
                                    $icon = 'bi-person-check';
                                    $color = 'rgb(161,77,253)'; // purple
                                    break;

                                case 'bulk_user_import':
                                    $icon = 'bi-clipboard2-check';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'bulk_engagement_import':
                                    $icon = 'bi-clipboard2-check';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'bulk_user_delete':
                                    $icon = 'bi-people';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;

                                case 'bulk_engagement_delete':
                                    $icon = 'bi-clipboard2-minus';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;

                                case 'bulk_engagement_import_failed':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break;

                                case 'failed_engagement_deleted':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break;

                                case 'bulk_user_import_failed':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break;

                                case 'bulk_engagement_delete_failed':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break; 
                                
                                case 'bulk_user_delete_failed':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break; 
                                
                                case 'failed_user_deleted':
                                    $icon = 'bi-exclamation-triangle';
                                    $color = 'rgb(243,132,48)'; // green
                                    break;

                                case 'backup':
                                    $icon = 'bi-database';
                                    $color = 'rgb(243,132,48)'; // orange
                                    break;
                                
                                default:
                                    $icon = 'bi-info-circle';
                                    $color = 'gray';
                            }
                        
                            echo '
                            <div class="activity-card d-flex justify-content-between">
                                <div class="activity-icon-container">
                                    <div class="activity-icon" style="color:'.$color.'; font-size: 18px; margin-top: -40px; margin-left: 15px !important;">
                                        <i class="bi '.$icon.'"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 mx-3">
                                    <div class="activity-title fw-semibold mb-1">'.htmlspecialchars($row['title']).'</div>
                                    <div class="activity-sub text-muted mb-1" style="font-size: 0.8rem;">By: '.htmlspecialchars($row['full_name']).'</div>
                                    <div class="activity-sub text-black" style="font-size: 0.9rem;">'.htmlspecialchars($row['description']).'</div>
                                </div>
                                <div class="text-muted small flex-shrink-0" style="min-width: 130px; text-align: right;">'.date("n/j/Y, g:i:s A", strtotime($row['created_at'])).'</div>
                            </div>';
                        }
                    } else {
                        echo '<p class="text-muted">No system activities found.</p>';
                    }
                    ?>
                </div>

                        <!-- Pagination Controls for activity -->
                <nav>
                    <ul id="activity-pagination" class="pagination justify-content-center mt-3"></ul>
                </nav>
            </div>
        <!-- end system activty -->

        <!-- Settings Tab -->
            <div id="settings" class="tab-content">
              <div class="row g-3 ps-3 pe-3 mt-1" style="height: 350px !important;">
                <!-- Left Column: System Configuration -->
                <div class="col-md-6">
                  <div class="settings-card d-flex flex-column h-100">
                    <div class="settings-header mb-4">
                      <div class="titles">
                        <p class="text-black fs-6 fw-bold mb-1">System Configuration</p>
                        <p class="fs-6 mb-0">Manage system-wide settings</p>
                      </div>
                    </div>

                    <!-- Email Notifications -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Email Notifications</p>
                        <p class="mb-0" style="font-size: 14px;">Send system notifications via email</p>
                      </div>
                      <a href="#" id="configureEmailBtn" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
                    </div>

                    <!-- Backup Settings -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Backup Settings</p>
                        <p class="mb-0" style="font-size: 14px;">Automated data backup configuration</p>
                      </div>
                      <a href="#" id="configureBackupBtn" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
                    </div>


                  </div>
                </div>

                <!-- Right Column: Engagement Status -->
                <div class="col-md-6">
                  <div class="settings-card d-flex flex-column h-100">
                    <div class="settings-header mb-2">
                      <div class="titles">
                        <p class="text-black fw-bold mb-1" style="font-size: 14px;">System Health</p>
                        <p class="fs-6" style="font-size: 14px;">Monitor system performance</p>
                      </div>
                    </div>

                    <div class="mb-2"></div>


                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-check2-circle text-success me-1"></i>Database Status
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(226,251,232); color: rgba(64,109,72,1);">
                        Healthy
                      </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-check2-circle text-success me-1"></i>API Status
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(226,251,232); color: rgba(64,109,72,1);">
                        Operational
                      </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-check2-circle text-success me-1"></i>Last Successful Backup
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(226,251,232); color: rgba(64,109,72,1);">
                        12 hrs ago
                      </span>
                    </div>
                    

                    <?php
                    $path = '/'; // Root filesystem for container/VM
                                      
                    $totalSpace = disk_total_space($path);
                    $freeSpace = disk_free_space($path);
                    $usedSpace = $totalSpace - $freeSpace;
                                      
                    // Make percent used a whole number
                    $percentUsed = round(($usedSpace / $totalSpace) * 100);
                                      
                    function formatSize($bytes) {
                        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                        $i = 0;
                        while ($bytes >= 1024 && $i < count($sizes) - 1) {
                            $bytes /= 1024;
                            $i++;
                        }
                        return round($bytes, 2) . ' ' . $sizes[$i];
                    }
                    
                    // Determine badge colors and icon based on usage
                    function getStorageColors($percent) {
                        if ($percent >= 0 && $percent <= 33) {
                            // Green
                            return [
                                'bg' => 'rgb(226,251,232)',
                                'color' => 'rgba(64,109,72,1)',
                                'icon' => 'bi bi-check2-circle text-success'
                            ];
                        } elseif ($percent >= 34 && $percent <= 66) {
                            // Yellow
                            return [
                                'bg' => 'rgb(253,249,200)',
                                'color' => 'rgba(135,88,30)',
                                'icon' => 'bi bi-exclamation-circle text-warning'
                            ];
                        } elseif ($percent >= 67 && $percent <= 100) {
                            // Red
                            return [
                                'bg' => 'rgb(254,228,228)',
                                'color' => 'rgba(136,0,0,1)',
                                'icon' => 'bi bi-exclamation-circle text-danger'
                            ];
                        } else {
                            // Default to red
                            return [
                                'bg' => 'rgb(254,228,228)',
                                'color' => 'rgba(136,0,0,1)',
                                'icon' => 'bi bi-exclamation-circle text-danger'
                            ];
                        }
                    }
                    
                    // Get colors and icon for this percent
                    $colors = getStorageColors($percentUsed);
                    ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 14px;">
                      <div>
                        <i class="<?= $colors['icon'] ?> me-1"></i>Storage Usage
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: <?= $colors['bg'] ?>; color: <?= $colors['color'] ?>;">
                        <?= $percentUsed ?>% Used
                      </span>
                    </div>



                    <?php
                    // Default values in case DB or query fails
                    $emailEnabled = false;
                                      
                    // Only run query if $conn exists
                    if (isset($conn) && $conn && $conn->ping()) {
                        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'enable_email_notifications' LIMIT 1";
                        $result = $conn->query($sql);
                        if ($result && $row = $result->fetch_assoc()) {
                            // Assuming setting_value is stored as 'true' / 'false' strings
                            $emailEnabled = ($row['setting_value'] === 'true');
                        }
                    }
                    
                    // Set colors and icon based on status
                    if ($emailEnabled) {
                        $iconClass = 'bi bi-check2-circle text-success';
                        $bgColor = 'rgb(226,251,232)';
                        $textColor = 'rgba(64,109,72,1)';
                        $statusText = 'Enabled';
                    } else {
                        $iconClass = 'bi bi-exclamation-circle text-danger';
                        $bgColor = 'rgb(254,228,228)';
                        $textColor = 'rgba(136,0,0,1)';
                        $statusText = 'Not Enabled';
                    }
                    ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3" style="font-size: 14px;">
                        <div>
                            <i class="<?= $iconClass ?> me-1"></i>Email Service
                        </div>
                        <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: <?= $bgColor ?>; color: <?= $textColor ?>;">
                            <?= $statusText ?>
                        </span>
                    </div>


                    <!-- Spacer pushes button to bottom -->
                    <div class="flex-grow-1"></div>

                    <a href="#" class="badge text-black p-2 text-decoration-none fw-medium w-100 mt-3 border overlay-red" style="font-size: .875rem;">
                      <i class="bi bi-activity pe-3"></i>View Detailed Metrics
                    </a>
                  </div>
                </div>
              </div>

              <div class="row g-3 ps-3 pe-3 mt-2">
                <div class="col-md-12">
                  <div class="analytic-card" style="height: 215px !important;">
                    <div class="user-management-header d-flex justify-content-between align-items-center mb-3">
                      <div class="titles">
                        <p class="text-black fs-6 fw-bold mb-1">Advanced Reports</p>
                        <p class="fs-6 mb-0">Generate detailed system reports</p>
                      </div>
                      <div class="user-management-buttons">
                        <a href="#" class="badge text-black p-2 text-decoration-none fw-medium border fs-6">
                          <i class="bi bi-download me-3 overlay-red"></i>Export All Data
                        </a>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pb-2">
                      <div class="reports-card text-center overlay-red">
                        <i class="bi bi-graph-up-arrow"></i>
                        <div class="analytic-title mt-2 fw-semibold">Utilization Report</div>
                        <div class="analytic-subtitle">Staff and engagement utilization</div>
                      </div>

                      <div class="reports-card text-center overlay-red">
                        <i class="bi bi-people"></i>
                        <div class="analytic-title mt-2 fw-semibold">User Activity Report</div>
                        <div class="analytic-subtitle">Login and engagement metrics</div>
                      </div>

                      <div class="reports-card text-center overlay-red">
                        <i class="bi bi-clock"></i>
                        <div class="analytic-title mt-2 fw-semibold">Time Tracking Report</div>
                        <div class="analytic-subtitle">Hours and productivity analysis</div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
        <!-- end settings tab -->

    </div> <!-- end container -->
</div> <!-- end flex-grow -->

<?php include_once '../includes/modals/global_pto_modal.php'; ?>
<?php //include_once '../includes/modals/viewProfileModal.php'; ?>
<?php //include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php include_once '../includes/modals/viewUserModal.php'; ?>
<?php //include_once '../includes/modals/security_policy_modal.php'; ?>
<?php include_once '../includes/modals/email_configuration_modal.php'; ?>
<?php include_once '../includes/modals/backup_configuration_modal.php'; ?>

<script src="../assets/js/global_pto.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/global_pto_table.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/admin_panel_tabs.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/promote_user.js?v=<?php echo time(); ?>"></script>
<!-- <script src="../assets/js/security_policy.js?v=<?php echo time(); ?>"></script> -->
<script src="../assets/js/backup_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/email_configurations.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/buld_delete_users.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/search_pagination.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
