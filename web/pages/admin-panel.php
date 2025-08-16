<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
$newUsersQuery = "SELECT COUNT(*) AS recent FROM users WHERE created >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
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


$sql = "SELECT user_id, first_name, last_name, email, role, status, last_active 
        FROM users 
        ORDER BY first_name ASC";
$result = mysqli_query($conn, $sql);

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
              <a href="#users" class=" text-decoration-none <?php if ($isManager) echo 'd-none'; ?> <?php if ($isAdmin) echo 'active'; ?>" data-tab="users">User Management</a>
              <a href="#engagement" class=" text-decoration-none <?php if ($isManager) echo 'active'; ?>" data-tab="engagements">Engagement Management</a>
              <a href="#time_off" class=" text-decoration-none" data-tab="time_off">Time Off</a>
              <a href="#activity" class=" text-decoration-none" data-tab="activity">System Activity</a>
              <a href="#settings" class=" text-decoration-none <?php if ($isManager) echo 'd-none'; ?>" data-tab="settings">Settings</a>
          </div>
        <!-- end Tabs -->

        <script src="../assets/js/admin_panel_tabs.js?v=<?php echo time(); ?>"></script>

        <!-- user management -->
            <div id="users" class="tab-content <?php if ($isManager) echo 'd-none'; ?>">
                <div class="user-management-header d-flex justify-content-between align-items-center">
                    <!-- Left -->
                    <div class="titles">
                        <p class="text-black mb-0"><strong>User Management</strong></p>
                        <p class="mb-0">Manage user accounts, roles, and permissions</p>
                    </div>

                    <!-- Middle (Search) -->
                    <div class="user-search mx-3" style="flex: 1; max-width: 300px;">
                        <input type="text" id="userSearch" class="form-control form-control-sm" 
                               placeholder="Search users..." minlength="3">
                    </div>

                    <!-- Right -->
                    <div class="user-management-buttons d-flex align-items-center gap-2">
                        <a href="#" id="bulkDeleteBtn" class="badge text-white p-2 text-decoration-none fw-medium" 
                           style="font-size: .875rem; background-color: darkred; display:none;">
                          <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedCount">0</span>)
                        </a>

                        <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" 
                           style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                           data-bs-toggle="modal" data-bs-target="#importUsersModal">
                            <i class="bi bi-upload me-3"></i>Import Users
                        </a>

                        <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" 
                           style="font-size: .875rem; background-color: rgb(3,2,18);" 
                           data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus me-3"></i>Add User
                        </a>
                    </div>
                </div>

                <div class="user-table">
                    <table id="user-table" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllUsers"></th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Active</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><input type="checkbox" class="selectUser" data-user-id="<?php echo $row['user_id']; ?>"></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-role">
                                            <?php echo ucfirst(htmlspecialchars($row['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-status <?php echo strtolower($row['status']) === 'active' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            if (empty($row['last_active']) || $row['last_active'] === null) {
                                                echo "Never";
                                            } else {
                                                echo date("n/j/Y", strtotime($row['last_active']));
                                            }
                                        ?>
                                    </td>
                                    <td class="table-actions">
                                        <a href="#" class="view-user-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#viewUserModal" data-user-id="<?php echo $row['user_id']; ?>">
                                            <i class="bi bi-eye text-success"></i>
                                        </a>
                                        <a href="#" class="edit-user-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#updateUserModal" data-user-id="<?php echo $row['user_id']; ?>">
                                            <i class="bi bi-pencil text-purple "></i>
                                        </a>

                                        <a href="#" class="delete-user-btn text-decoration-none" data-user-id="<?php echo $row['user_id']; ?>">
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
                <nav>
                    <ul id="pagination-users" class="pagination justify-content-center mt-3"></ul>
                </nav>
            </div>
        <!-- end user management -->

        <!-- engagement management -->

            <div id="engagements" class="tab-content">
                <div class="user-management-header d-flex justify-content-between align-items-center">
                        <!-- Left -->
                        <div class="titles">
                            <p class="text-black mb-0"><strong>Engagement Management</strong></p>
                            <p class="mb-0">Manage all engagements and entries</p>
                        </div>

                        <!-- Middle (Search) -->
                        <div class="user-search mx-3" style="flex: 1; max-width: 300px;">
                            <input type="text" id="engagementSearch" class="form-control form-control-sm" 
                                   placeholder="Search engagements..." minlength="3">
                        </div>

                        <!-- Right -->
                        <div class="user-management-buttons d-flex align-items-center gap-2">
                            <a href="#" id="bulkDeleteEngagementBtn" class="badge text-white p-2 text-decoration-none fw-medium" 
                               style="font-size: .875rem; background-color: darkred; display:none;">
                              <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedEngagementCount">0</span>)
                            </a>

                            <a href="#" 
                               class="badge text-black p-2 text-decoration-none fw-medium" 
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                               data-bs-toggle="modal" data-bs-target="#importEngagementsModal">
                                <i class="bi bi-upload me-3"></i>Import Engagements
                            </a>

                            <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" 
                               style="font-size: .875rem; background-color: rgb(3,2,18);" 
                               data-bs-toggle="modal" data-bs-target="#addEngagementModal">
                                <i class="bi bi-person-plus me-3"></i>Add Engagement
                            </a>
                        </div>
                    </div>


                <div class="user-table">
                    <table id="engagement-table" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllEngagements"></th>
                                <th>Client</th>
                                <th>Budgeted Hours</th>
                                <th>Allocated Hours</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($engagementResults) > 0): ?>
                            <?php while ($E_row = mysqli_fetch_assoc($engagementResults)): ?>
                                <tr>
                                    <td><input type="checkbox" class="selectEngagement" data-engagement-id="<?php echo $E_row['engagement_id']; ?>"></td>
                                    <td>
                                        <?php echo $E_row['client_name']; ?><br>
                                    </td>
                                    <td>
                                        <?php echo (int)$E_row['budgeted_hours']; ?> hours
                                    </td>
                                    <td>
                                        <?php echo $E_row['total_assigned_hours']; ?> hours
                                    </td>
                                    <td>
                                        <?php
                                        $status = strtolower($E_row['status']);
                                        switch ($status) {
                                            case 'confirmed':
                                                $badgeClass = 'badge-confirmed';   // e.g., green badge
                                                break;
                                            case 'pending':
                                                $badgeClass = 'badge-pending';     // e.g., yellow badge
                                                break;
                                            case 'not_confirmed':
                                                $badgeClass = 'badge-not-confirmed'; // e.g., red badge
                                                break;
                                            default:
                                                $badgeClass = 'badge-default';     // fallback class
                                                break;
                                        }
                                        ?>

                                        <span class="badge-status <?php echo $badgeClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $E_row['status'])); ?>
                                        </span>

                                    </td>
                                    <td class="table-actions">
                                        <a href="#" class="view-engagement-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#viewEngagementModal" data-engagement-id="<?php echo $E_row['engagement_id']; ?>">
                                            <i class="bi bi-eye text-success"></i>
                                        </a>
                                        <a href="#" class="edit-user-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#updateEngagementModal" data-engagement-id="<?php echo $E_row['engagement_id']; ?>">
                                            <i class="bi bi-pencil text-purple "></i>
                                        </a>

                                        <a href="#" class="delete-engagement-btn text-decoration-none" data-engagement-id="<?php echo $E_row['engagement_id']; ?>">
                                          <i class="bi bi-trash"></i>
                                        </a>

                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No engagements found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                        
                <!-- Pagination Controls -->
                <nav>
                  <ul id="pagination-engagements" class="pagination justify-content-center mt-3"></ul>
                </nav>
                            
            </div>

        <!-- end engagement management -->

        <!-- Search + Pagination Script -->
          <script src="../assets/js/search_pagination.js?v=<?php echo time(); ?>"></script>

        <!-- time off -->
          <div id="time_off" class="tab-content">
              <div class="activity-header mb-3">
                  <div class="titles">
                      <p class="text-black"><strong>Time Off Management</strong></p>
                      <p>Manage employee and global time off entries</p>
                  </div>
              </div>

              <!-- Nested Tabs -->
              <div class="nested-tabs mb-3">
                  <a href="#time_off#individual_pto" class="text-decoration-none active" data-tab="individual_pto">Individual Time Off</a>
                  <a href="#time_off#global_pto" class="text-decoration-none" data-tab="global_pto">Global Time Off</a>
              </div>

              <!-- Nested Tab Contents -->
<div id="individual_pto" class="nested-tab-content">
    <div class="timeoff-header d-flex justify-content-between align-items-center mb-3">
        <!-- Left -->
        <div class="titles">
            <p class="text-black mb-0"><strong>Individual Time Off</strong></p>
            <p class="mb-0">Manage employee time off entries</p>
        </div>

        <!-- Middle (Search) -->
        <div class="timeoff-search mx-3" style="flex: 1; max-width: 300px;">
            <input type="text" id="timeoffSearch" class="form-control form-control-sm" 
                   placeholder="Search time off..." minlength="3">
        </div>

        <!-- Right -->
        <div class="timeoff-buttons d-flex align-items-center gap-2">
            <a href="#" id="bulkDeleteTimeoffBtn" class="badge text-white p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; background-color: darkred; display:none;">
              <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedTimeoffCount">0</span>)
            </a>

            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
               data-bs-toggle="modal" data-bs-target="#importTimeoffModal">
                <i class="bi bi-upload me-3"></i>Import Time Off
            </a>

            <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; background-color: rgb(3,2,18);" 
               data-bs-toggle="modal" data-bs-target="#addTimeoffModal">
                <i class="bi bi-plus-circle me-3"></i>Add Time Off
            </a>
        </div>
    </div>

    <div class="timeoff-table">
        <table id="timeoff-table" class="table table-hover mb-0">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllTimeoff"></th>
                    <th>Employee</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Hours</th>
                    <th>Reason / Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($timeoffEntries)): ?>
                <?php foreach ($timeoffEntries as $entry): ?>
                    <tr>
                        <td><input type="checkbox" class="selectTimeoff" data-timeoff-id="<?php echo $entry['timeoff_id']; ?>"></td>
                        <td>
                            <?php echo htmlspecialchars($entry['employee_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($entry['employee_email']); ?></small>
                        </td>
                        <td><?php echo date("n/j/Y", strtotime($entry['start_date'])); ?></td>
                        <td><?php echo date("n/j/Y", strtotime($entry['end_date'])); ?></td>
                        <td><?php echo htmlspecialchars($entry['hours']); ?> hrs</td>
                        <td><?php echo htmlspecialchars($entry['note']); ?></td>
                        <td class="table-actions">
                            <a href="#" class="edit-timeoff-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#editTimeoffModal" data-timeoff-id="<?php echo $entry['timeoff_id']; ?>">
                                <i class="bi bi-pencil text-purple"></i>
                            </a>
                            <a href="#" class="delete-timeoff-btn text-decoration-none" data-timeoff-id="<?php echo $entry['timeoff_id']; ?>">
                                <i class="bi bi-trash text-danger"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No time off entries found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <nav>
        <ul id="pagination-timeoff" class="pagination justify-content-center mt-3"></ul>
    </nav>
</div>


              <div id="global_pto" class="nested-tab-content" style="display:none;">
                  <p>Manage global PTO entries here.</p>
              </div>


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

                    <!-- Global PTO Settings -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Global PTO</p>
                        <p class="mb-0" style="font-size: 14px;">View and manage company-wide paid time off</p>
                      </div>
                      <a href="#" data-bs-toggle="modal" data-bs-target="#globalPTOModal" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
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


                    <!-- Security Policies -->
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Security Policies</p>
                        <p class="mb-0" style="font-size: 14px;">Password and access requirements</p>
                      </div>
                      <a href="#" id="configureSecurityBtn" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
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

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>


<!-- Modal for Adding User -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="addUserForm" action="add_user.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <div class="mb-3">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" required>
              </div>

              <div class="mb-3">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" required>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>

              <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-select" required>
                  <option value="" disabled selected>Select role</option>
                  <option value="admin">Admin</option>
                  <option value="manager">Manager</option>
                  <option value="senior">Senior</option>
                  <option value="staff">Staff</option>
                </select>
              </div>

              <!-- <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div> -->

              <div class="alert alert-info small">
                Default password will be set to <strong>change_me</strong>.  
                User will be prompted to update it on first login.
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add User</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- end add user modal -->

<!-- add engagement modal -->
    <div class="modal fade" id="addEngagementModal" tabindex="-1" aria-labelledby="addEngagementModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="addEngagementForm" action="add_engagement.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="addEngagementModalLabel">Add New Engagement</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <!-- Client Name -->
              <div class="mb-3">
                <label for="client_name" class="form-label">Client Name</label>
                <input type="text" class="form-control" id="client_name" name="client_name" required>
              </div>

              <!-- Total Available Hours -->
              <div class="mb-3">
                <label for="budgeted_hours" class="form-label">Budgeted Hours</label>
                <input type="number" class="form-control" id="budgeted_hours" name="budgeted_hours" min="1" required>
              </div>

              <!-- Status -->
              <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                  <option value="" disabled selected>Select status</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="pending">Pending</option>
                  <option value="not_confirmed">Not Confirmed</option>
                </select>
              </div>

              <!-- Notes -->
              <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
              </div>

              <div class="alert alert-info small">
                You can assign team members to this engagement after creating it.
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add Engagement</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- end add engagement modal -->

<!-- Update User Modal -->
    <div class="modal fade" id="updateUserModal" tabindex="-1" aria-labelledby="updateUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="updateUserForm" action="update_user.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserModalLabel">
                <i class="bi bi-pencil-square"></i> Edit User <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Update user information and permissions</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <div class="modal-body">

            <input type="hidden" id="update_user_id" name="user_id" required>

            <div class="mb-3">
              <label for="update_first_name" class="form-label">First Name</label>
              <input type="text" class="form-control" id="update_first_name" name="first_name" required>
            </div>

            <div class="mb-3">
              <label for="update_last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="update_last_name" name="last_name" required>
            </div>

            <div class="mb-3">
              <label for="update_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="update_email" name="email" required>
            </div>

            <div class="mb-3">
              <label for="update_role" class="form-label">Role</label>
              <select class="form-select" id="update_role" name="role" required>
                <option value="" disabled>Select role</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="senior">Senior</option>
                <option value="staff">Staff</option>
                <!-- Add more roles as needed -->
              </select>
            </div>

            <div class="mb-3">
              <label for="update_status" class="form-label">Status</label>
              <select class="form-select" id="update_status" name="status" required>
                <option value="" disabled>Select status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <!-- Add more statuses as needed -->
              </select>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);">Update User</button>
          </div>
        </form>
      </div>
    </div>
<!-- end update user modal -->

<!-- update engagement modal -->
    <div class="modal fade" id="updateEngagementModal" tabindex="-1" aria-labelledby="updateEngagementModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <form id="updateEngagementForm" action="update_engagement.php" method="POST" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateEngagementModalLabel">
              <i class="bi bi-pencil-square"></i> Edit Engagement <br>
              <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">Update engagement details</span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">

            <input type="hidden" id="update_engagement_id" name="engagement_id" required>

            <div class="mb-3">
              <label for="update_client_name" class="form-label">Client Name</label>
              <input type="text" class="form-control" id="update_client_name" name="client_name" required>
            </div>

            <div class="mb-3">
              <label for="update_budgeted_hours" class="form-label">Estimated Hours</label>
              <input type="number" min="0" class="form-control" id="update_budgeted_hours" name="budgeted_hours" required>
            </div>

            <div class="mb-3">
              <label for="update_status" class="form-label">Status</label>
              <select class="form-select" id="update_status" name="status" required>
                <option value="" disabled>Select status</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="not_confirmed">Not Confirmed</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="update_notes" class="form-label">Notes</label>
              <textarea class="form-control" id="update_notes" name="notes" rows="3"></textarea>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);"   data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);">Update     Engagement</button>
          </div>
        </form>
      </div>
    </div>
<!-- end update engagement modal -->

<?php include_once '../includes/modals/viewUserModal.php'; ?>

<!-- View Engagement Modal -->
    <div class="modal fade" id="viewEngagementModal" tabindex="-1" aria-labelledby="viewEngagementModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="min-width: 600px !important;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateEngagementModalLabel">
                <i class="bi bi-file-earmark-text"></i> Engagement Details <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Complete engagement details for <span id="view_engagement_client_name"></span></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; padding: 10px;">
              <div>
                <h5 id="view_client_name" class="fw-semibold mt-3"></h5>
                <span class="text-capitalize badge-status mb-3 mt-2" style="font-size: 12px;" id="view_engagement_status">...</span>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-people"></i> Team Overview
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Team Size:</strong>
                  <span id="view_engagement_team_size" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Estimated Hours:</strong>
                  <span id="view_engagement_estimated_hours" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Allocated Hours:</strong>
                  <span id="view_engagement_allocated_hours" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-clipboard2"></i> Engagement Details
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Created:</strong>
                  <span id="view_engagement_created" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Updated:</strong>
                  <span id="view_engagement_updated" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Status:</strong>
                  <span id="view_eng_status" class="text-capitalize" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
            </div>

            <div class="col-md-12">
              <h6>Notes</h6>
              <div id="view_engagement_notes" style="max-height: 150px; overflow-y: auto;"></div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn text-muted" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<!-- end View user modal -->

<!-- import users modal -->
    <div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="importUsersForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="importUsersModalLabel">Import Users from CSV</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p>
                Please use the <a href="../assets/templates/bulk_user_template.csv" download>CSV template</a> to ensure correct format.
              </p>

              <div class="mb-3">
                <label for="csv_file" class="form-label">Select CSV File</label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
              </div>

              <div class="alert alert-info small">
                Only CSV files are supported. Required columns: 
                <strong>first_name, last_name, email, role</strong>
              </div>

              <!-- Import Summary Container -->
              <div id="importSummary" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
                <!-- Filled dynamically by JS -->
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" id="importSubmitBtn">Import</button>
              <button type="button" class="btn btn-success d-none" id="importCloseBtn">OK</button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- end import users modal -->

<!-- import engagements modal -->

    <div class="modal fade" id="importEngagementsModal" tabindex="-1" aria-labelledby="importEngagementsModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="importEngagementsForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="importEngagementsModalLabel">Import Engagements from CSV</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p>
                Please use the <a href="../assets/templates/bulk_engagement_template.csv" download>CSV template</a> to ensure correct format.
              </p>

              <div class="mb-3">
                <label for="engagements_csv_file" class="form-label">Select CSV File</label>
                <input type="file" class="form-control" id="engagements_csv_file" name="csv_file" accept=".csv" required>
              </div>

              <div class="alert alert-info small">
                Only CSV files are supported. Required columns: 
                <strong>client_name, budgeted_hours, status</strong><br>
                Allowed status values: <em>confirmed, pending, not_confirmed</em>
              </div>

              <!-- Import Summary Container -->
              <div id="engagementsImportSummary" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
                <!-- Filled dynamically by JS -->
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" id="importEngagementsSubmitBtn">Import</button>
              <button type="button" class="btn btn-success d-none" id="importEngagementsCloseBtn">OK</button>
            </div>
          </form>
        </div>
      </div>
    </div>


<!-- end import engagements modal -->

<!-- Email Notifications Modal -->
    <div class="modal fade" id="emailNotifConfigModal" tabindex="-1" aria-labelledby="emailNotifConfigLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form id="emailNotifConfigForm" action="settings_backend.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="emailNotifConfigLabel">
                <i class="bi bi-envelope"></i> Email Notification Settings <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">Configure email notifications and SMTP settings</span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <!-- General Settings -->
              <h6 class="mb-2">General Settings</h6>
              <div class="form-check form-switch mb-4" style="padding-left: 0; margin-left: 0;">
                <label class="form-check-label float-start m-0" for="enableEmailNotifications">
                  Enable Email Notifications (Master switch) <br>
                  <span class="text-muted" style="font-size: 12px;">
                    Master switch for all email notifications
                  </span>
                </label>
                <input class="form-check-input float-end" type="checkbox" id="enableEmailNotifications" name="enable_email_notifications" <?php if (!empty($settings    ['enable_email_notifications']) && $settings['enable_email_notifications'] === 'true') echo 'checked'; ?>>
              </div>

              <!-- Notification Types -->
              <!-- <h6 class="mb-2">Notification Types</h6>
              <div class="row mb-2">
                <div class="col-6">
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[entry_updates]" value="false">
                    <input class="form-check-input" type="checkbox" id="entryUpdates" name="notification_types[entry_updates]" value="true" <?php if (!empty($settings['notification_types[entry_updates]']) && $settings['notification_types[entry_updates]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="entryUpdates">Entry Notifications</label>
                  </div>
                                
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[timeoff_notifications]" value="false">
                    <input class="form-check-input" type="checkbox" id="timeoffNotifications" name="notification_types[timeoff_notifications]" value="true" <?php if (!empty($settings['notification_types[timeoff_notifications]']) && $settings['notification_types[timeoff_notifications]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="timeoffNotifications">Timeoff Notifications</label>
                  </div>
                </div>
                                
                <div class="col-6">
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[user_notifications]" value="false">
                    <input class="form-check-input" type="checkbox" id="userNotifications" name="notification_types[user_notifications]" value="true" <?php if (!empty($settings['notification_types[user_notifications]']) && $settings['notification_types[user_notifications]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="userNotifications">User Notifications</label>
                  </div>
                                
                  <div class="form-check form-switch mb-2">
                    <input type="hidden" name="notification_types[login_alerts]" value="false">
                    <input class="form-check-input" type="checkbox" id="loginAlerts" name="notification_types[login_alerts]" value="true" <?php if (!empty($settings['notification_types[login_alerts]']) && $settings['notification_types[login_alerts]'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" for="loginAlerts">Login Alerts</label>
                  </div>
                </div>
              </div> -->


              <!-- Notification Frequency -->
              <h6 class="mb-2">Notification Frequency</h6>
              <select class="form-select mb-4" id="notificationFrequency" name="notification_frequency" required>
                <option value="immediately" <?php if (($settings['notification_frequency'] ?? '') === 'immediately') echo 'selected'; ?>>Immediately</option>
                <option value="hourly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'hourly_digest') echo 'selected'; ?>>Hourly Digest</option>
                <option value="daily_digest" <?php if (($settings['notification_frequency'] ?? '') === 'daily_digest') echo 'selected'; ?>>Daily Digest</option>
                <option value="weekly_digest" <?php if (($settings['notification_frequency'] ?? '') === 'weekly_digest') echo 'selected'; ?>>Weekly Digest</option>
              </select>

              <!-- SMTP Configuration -->
              <h6 class="mb-3">SMTP Configuration</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label for="smtpServer" class="form-label">SMTP Server</label>
                  <input type="text" class="form-control" id="smtpServer" name="smtp_server" placeholder="smtp.example.com" value="<?php echo htmlspecialchars($settings    ['smtp_server'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpPort" class="form-label">SMTP Port</label>
                  <input type="number" class="form-control" id="smtpPort" name="smtp_port" placeholder="587" value="<?php echo htmlspecialchars($settings   ['smtp_port'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="smtpUsername" name="smtp_username" placeholder="user@example.com" value="<?php echo htmlspecialchars  ($settings['smtp_username'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="smtpPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="smtpPassword" name="smtp_password" placeholder="••••••••" value="<?php echo htmlspecialchars($settings    ['smtp_password'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="senderName" class="form-label">Sender Name</label>
                  <input type="text" class="form-control" id="senderName" name="sender_name" placeholder="Your Company" value="<?php echo htmlspecialchars($settings    ['sender_name'] ?? '', ENT_QUOTES); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="senderEmail" class="form-label">Sender Email</label>
                  <input type="email" class="form-control" id="senderEmail" name="sender_email" placeholder="no-reply@example.com" value="<?php echo htmlspecialchars   ($settings['sender_email'] ?? '', ENT_QUOTES); ?>" required>
                </div>
              </div>

              <!-- Test Configuration -->
              <h6 class="mb-3">Test Configuration</h6>
              <div class="mb-3">
                <input type="email" class="form-control mb-3" id="testEmail" placeholder="test@example.com" aria-label="Test email">

                <a href="#"
                   id="sendTestEmailBtn"
                   class="badge text-black p-2 text-decoration-none fw-medium disabled"
                   style="font-size: .875rem; border: 1px solid rgb(229,229,229); pointer-events: none; opacity: 0.5;">
                  <i class="bi bi-envelope me-3"></i>Send Test Email
                </a>
              </div>

              <div id="testEmailStatus" class="small text-success d-none mb-3"></div>
            </div>

            <div class="modal-footer">
              <a href="#" 
                 class="badge text-black p-2 text-decoration-none fw-medium" 
                 style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                 data-bs-dismiss="modal">
                Cancel
              </a>

              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
                <i class="bi bi-person-plus me-3"></i>Save Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- end Email Notification Modal -->

<!-- Global PTO Modal -->
  <div class="modal fade" id="globalPTOModal" tabindex="-1" aria-labelledby="globalPTOModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-sm">

      <div class="modal-header border-0">
        <h5 class="modal-title" id="globalPTOModalLabel">
          <i class="bi bi-calendar-week me-2"></i>Global PTO
          <br>
          <span class="text-muted" style="font-size: 12px; font-weight: 400;">View and manage company-wide paid time off</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">

        <!-- Add New Global PTO Section -->
        <div class="p-3 mb-4 bg-light border" style="border-radius: 4px;">
          <form id="addGlobalPTOForm" method="POST" class="d-flex flex-column gap-2">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Week Start (Monday)</label>
                <input type="date" name="week_start" class="form-control form-control-sm" required 
                       oninput="if(new Date(this.value).getDay() !== 0) { alert('Please select a Monday'); this.value=''; }">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Assigned Hours</label>
                <input type="number" name="assigned_hours" class="form-control form-control-sm" placeholder="Hours" min="0" required>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Reason</label>
                <input type="text" name="timeoff_note" class="form-control form-control-sm" placeholder="Reason">
              </div>
            </div>

            <button type="submit" class="btn btn-dark btn-sm mt-3 align-self-start">
              <i class="bi bi-plus-circle me-1"></i>Add PTO
            </button>
          </form>
        </div>

        <!-- Current Global PTO Entries -->
        <div class="accordion d-flex flex-column gap-2" id="ptoAccordion">
          <?php include 'get_global_pto.php'; ?>
        </div>

      </div>

      <div class="modal-footer border-0">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/global_pto_functions.js?v=<?php echo time(); ?>"></script>




<!-- Backup Configuration Modal -->
    <div class="modal fade" id="backupConfigModal" tabindex="-1" aria-labelledby="backupConfigLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form id="backupConfigForm" action="settings_backend.php" method="POST" novalidate>
            <div class="modal-header">
              <h5 class="modal-title" id="backupConfigLabel">
                <i class="bi bi-hdd-stack"></i> Backup Configuration Settings <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">
                  Configure automated backup schedule and storage options
                </span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">

              <!-- Last Backup Info Card -->
              <div class="d-flex align-items-center p-2 mb-4 rounded-3" style="background-color: #f7f7f7; border: 1px solid #ddd;">
                <div class="me-3" style="font-size: 14px !important; color: #6c757d;">
                  <i class="bi bi-check2-circle"></i>
                </div>
                <div style="color: #6c757d; font-size: 14px !important;">
                  Last backup: <?php echo htmlspecialchars($settings['last_backup_datetime'] ?? 'Never'); ?> 
                  &nbsp; (<?php echo htmlspecialchars($settings['last_backup_size'] ?? '0 GB'); ?>)
                </div>
              </div>

              <!-- Backup Schedule -->
              <h6 class="mb-3">Backup Schedule</h6>
              <div class="form-check form-switch mb-4" style="padding-left: 0; margin-left: 0;">
                <input class="form-check-input float-end" style="font-size: 14px !important;" type="checkbox" id="enableAutomatedBackups"   name="enable_automated_backups" value="true" <?php if (!empty($settings['enable_automated_backups']) && $settings['enable_automated_backups'] ===     'true') echo 'checked'; ?>>
                <label class="form-check-label float-start" style="font-size: 14px !important;" for="enableAutomatedBackups">
                  Enable Automated Backups <br>
                  <span class="text-muted" style="font-size: 12px;">Master switch for automated backups</span>
                </label>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="backupFrequency" class="form-label" style="font-size: 14px !important;">Backup Frequency</label>
                  <select class="form-select" id="backupFrequency" style="font-size: 14px !important;" name="backup_frequency" required>
                    <option value="hourly" <?php if (($settings['backup_frequency'] ?? '') === 'hourly') echo 'selected'; ?>>Every Hour</option>
                    <option value="daily" <?php if (($settings['backup_frequency'] ?? '') === 'daily') echo 'selected'; ?>>Daily</option>
                    <option value="weekly" <?php if (($settings['backup_frequency'] ?? '') === 'weekly') echo 'selected'; ?>>Weekly</option>
                    <option value="monthly" <?php if (($settings['backup_frequency'] ?? '') === 'monthly') echo 'selected'; ?>>Monthly</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="backupTime" class="form-label" style="font-size: 14px !important;">Backup Time</label>
                  <input type="time" class="form-control" id="backupTime" style="font-size: 14px !important;" name="backup_time" value="<?php echo htmlspecialchars ($settings['backup_time'] ?? '', ENT_QUOTES); ?>" required>
                </div>
              </div>

              <div class="mb-4">
                <label for="retentionPeriod" style="font-size: 14px !important;" class="form-label">Retention Period (days)</label>
                <input type="number" min="1" class="form-control" style="font-size: 14px !important;" id="retentionPeriod" name="retention_period_days" value="<?php    echo htmlspecialchars($settings['retention_period_days'] ?? '', ENT_QUOTES); ?>" required>
              </div>

              <hr>

              <!-- Backup Content -->
              <h6 class="mb-3">Backup Content</h6>
              <div class="row mb-4">
                <?php
                $contentKeys = ['backup_users', 'backup_engagements', 'backup_entries', 'backup_settings'];
                $contentLabels = ['Users', 'Engagements', 'Entries', 'Settings'];

                $chunks = array_chunk($contentKeys, 3);
                $labelChunks = array_chunk($contentLabels, 3);

                foreach ($chunks as $colIndex => $chunk):
                ?>
                  <div class="col-md-4">
                    <?php foreach ($chunk as $i => $key):
                      $label = $labelChunks[$colIndex][$i];
                    ?>
                      <div class="form-check form-switch mb-2">
                        <input class="form-check-input" style="font-size: 14px !important;" type="checkbox" id="<?php echo $key; ?>" name="<?php echo $key; ?>"     value="true" <?php if (!empty($settings[$key]) && $settings[$key] === 'true') echo 'checked'; ?>>
                        <label class="form-check-label" style="font-size: 14px !important;" for="<?php echo $key; ?>"><?php echo $label; ?></label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <hr>

              <!-- Storage Location -->
              <h6 class="mb-3">Storage Location</h6>
              <select class="form-select mb-3" style="font-size: 14px !important;" id="storageLocation" name="storage_location" required>
                <?php 
                $storageOptions = ['local' => 'Local Storage', 'cloud' => 'Cloud Storage', 'network' => 'Network Storage'];
                foreach ($storageOptions as $val => $label) {
                  $selected = (($settings['storage_location'] ?? '') === $val) ? 'selected' : '';
                  echo "<option value=\"$val\" $selected>$label</option>";
                }
                ?>
              </select>

              <!-- Cloud Storage Settings -->
              <div id="cloudStorageSettings" style="display: none;">
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="cloudProvider" style="font-size: 14px !important;" class="form-label">Cloud Provider</label>
                    <select class="form-select" style="font-size: 14px !important;" id="cloudProvider" name="cloud_provider">
                      <?php
                      $cloudProviders = ['aws' => 'Amazon S3', 'azure' => 'Azure Blob', 'gcp' => 'Google Cloud', 'dropbox' => 'Dropbox'];
                      foreach ($cloudProviders as $val => $label) {
                        $selected = (($settings['cloud_provider'] ?? '') === $val) ? 'selected' : '';
                        echo "<option value=\"$val\" $selected>$label</option>";
                      }
                      ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label for="bucketName" style="font-size: 14px !important;" class="form-label">Bucket/Container Name</label>
                    <input type="text" style="font-size: 14px !important;" class="form-control" id="bucketName" name="bucket_name" value="<?php echo htmlspecialchars   ($settings['bucket_name'] ?? '', ENT_QUOTES); ?>" placeholder="Enter bucket or container name">
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="accessKey" style="font-size: 14px !important;" class="form-label">Access Key</label>
                    <input type="text" style="font-size: 14px !important;" class="form-control" id="accessKey" name="access_key" value="<?php echo htmlspecialchars ($settings['access_key'] ?? '', ENT_QUOTES); ?>" placeholder="Enter access key">
                  </div>
                  <div class="col-md-6">
                    <label for="secretKey" style="font-size: 14px !important;" class="form-label">Secret Key</label>
                    <input type="password" style="font-size: 14px !important;" class="form-control" id="secretKey" name="secret_key" value="<?php echo htmlspecialchars ($settings['secret_key'] ?? '', ENT_QUOTES); ?>" placeholder="Enter secret key">
                  </div>
                </div>

                <div class="mb-3">
                  <label for="region" style="font-size: 14px !important;" class="form-label">Region</label>
                  <input type="text" style="font-size: 14px !important;" class="form-control" id="region" name="region" value="<?php echo htmlspecialchars($settings    ['region'] ?? '', ENT_QUOTES); ?>" placeholder="Enter region">
                </div>
              </div>

              <hr>

              <!-- Test Configuration -->
              <h6 class="mb-3">Test Configuration</h6>
              <button type="button" style="font-size: 14px !important;" id="runTestBackupBtn" class="btn btn-primary mb-3">Run Test Backup</button>

            </div>

            <div class="modal-footer">
              <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);"   data-bs-dismiss="modal">Cancel</a>
              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
                <i class="bi bi-save me-2"></i>Save Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
<!-- end backup configuration modal -->

<!-- Security Policy Modal -->
    <div class="modal fade" id="securityPolicyConfigModal" tabindex="-1" aria-labelledby="securityPolicyConfigLabel" aria-hidden="true">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form id="securityPolicyConfigForm" action="settings_backend.php" method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="securityPolicyConfigLabel">
                <i class="bi bi-shield-lock"></i> Security Policy Settings <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important;">
                  Configure password policies, access control, and two-factor authentication
                </span>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <!-- Password Policy Section -->
              <h6 class="mb-3">Password Policy</h6>

              <div class="mb-3">
                <label for="minPasswordLength" style="font-size: 14px;" class="form-label">Minimum Password Length</label>
                <input type="number" style="font-size: 14px;" class="form-control" id="minPasswordLength" name="min_password_length" min="1" max="128" value="<?php echo    htmlspecialchars($settings['min_password_length'] ?? 8); ?>" required>
              </div>

              <div class="row mb-3">
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireNumbers" name="require_numbers" <?php if (!empty($settings  ['require_numbers']) && $settings['require_numbers'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireNumbers">Require Numbers</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireSymbols" name="require_symbols" <?php if (!empty($settings  ['require_symbols']) && $settings['require_symbols'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireSymbols">Require Symbols</label>
                  </div>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireUppercase" name="require_uppercase" <?php if (!empty($settings  ['require_uppercase']) && $settings['require_uppercase'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireUppercase">Require Uppercase</label>
                  </div>
                </div>
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="requireLowercase" name="require_lowercase" <?php if (!empty($settings  ['require_lowercase']) && $settings['require_lowercase'] === 'true') echo 'checked'; ?>>
                    <label class="form-check-label" style="font-size: 14px;" for="requireLowercase">Require Lowercase</label>
                  </div>
                </div>
              </div>

              <div class="mb-4">
                <label for="passwordExpiration" style="font-size: 14px;" class="form-label">Password Expiration (days)</label>
                <input type="number" style="font-size: 14px;" class="form-control" id="passwordExpiration" name="password_expiration_days" min="0" value="<?php echo    htmlspecialchars($settings['password_expiration_days'] ?? 0); ?>" required>
                <small  style="font-size: 12px;" class="text-muted">Set to 0 to disable password expiration</small>
              </div>

              <hr>

              <!-- Access Control Section -->
              <h6 class="mb-3">Access Control</h6>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="maxLoginAttempts" style="font-size: 14px;" class="form-label">Max Login Attempts</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="maxLoginAttempts" name="max_login_attempts" min="1" value="<?php echo  htmlspecialchars($settings['max_login_attempts'] ?? 5); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="lockoutDuration" style="font-size: 14px;" class="form-label">Lockout Duration (minutes)</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="lockoutDuration" name="lockout_duration_minutes" min="1" value="<?php echo     htmlspecialchars($settings['lockout_duration_minutes'] ?? 30); ?>" required>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-md-6">
                  <label for="sessionTimeout" style="font-size: 14px;" class="form-label">Session Timeout (minutes)</label>
                  <input type="number" style="font-size: 14px;" class="form-control" id="sessionTimeout" name="session_timeout_minutes" min="1" value="<?php echo   htmlspecialchars($settings['session_timeout_minutes'] ?? 60); ?>" required>
                </div>
                <div class="col-md-6">
                  <label for="apiRateLimit" class="form-label">API Rate Limit (per minute)</label>
                  <input type="number" class="form-control" id="apiRateLimit" name="api_rate_limit_per_minute" min="1" value="<?php echo htmlspecialchars($settings ['api_rate_limit_per_minute'] ?? 60); ?>" required>
                </div>
              </div>

              <hr>

              <!-- Two-Factor Authentication Section -->
              <h6 class="mb-3">Two-Factor Authentication (2FA)</h6>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="require2FAAllUsers" name="require_2fa_all_users" <?php if (!empty($settings    ['require_2fa_all_users']) && $settings['require_2fa_all_users'] === 'true') echo 'checked'; ?>>
                <label class="form-check-label" style="font-size: 14px;" for="require2FAAllUsers">
                  Require 2FA for all users
                  <br>
                  <small style="font-size: 12px;" class="text-muted">Force all users to enable two-factor authentication</small>
                </label>
              </div>

              <div class="form-check form-switch mb-3">
                <input class="form-check-input" style="font-size: 14px;" type="checkbox" id="require2FAAdmins" name="require_2fa_admins" <?php if (!empty($settings ['require_2fa_admins']) && $settings['require_2fa_admins'] === 'true') echo 'checked'; ?>>
                <label class="form-check-label" style="font-size: 14px;" for="require2FAAdmins">
                  Require 2FA for admins
                  <br>
                  <small style="font-size: 12px;" class="text-muted">Force admin users to enable two-factor authentication</small>
                </label>
              </div>
            </div>

            <div class="modal-footer">
              <a href="#" 
                 class="badge text-black p-2 text-decoration-none fw-medium" 
                 style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
                 data-bs-dismiss="modal">
                Cancel
              </a>

              <button type="submit" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18); border:none;">
                <i class="bi bi-save me-3"></i>Save Settings
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

<!-- end Security Policy Modal -->




<!-- Pagination -->
    <script>
        // Helper: create pagination controls (Prev, pages, Next)
          function createPaginationControls(totalPages, currentPage, onPageChange) {
            const ul = document.createElement('ul');
            ul.className = 'pagination justify-content-center';
        
            function createPageItem(label, disabled, active, clickHandler) {
              const li = document.createElement('li');
              li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
              const a = document.createElement('a');
              a.className = 'page-link';
              a.href = '#';
              a.innerText = label;
              if (!disabled) {
                a.addEventListener('click', e => {
                  e.preventDefault();
                  clickHandler();
                });
              }
              li.appendChild(a);
              return li;
            }
        
            // Prev button
            ul.appendChild(createPageItem('Prev', currentPage === 1, false, () => onPageChange(currentPage - 1)));
        
            // Determine visible page range (max 10 pages)
            const maxVisiblePages = 10;
            let startPage = 1;
            let endPage = totalPages;
        
            if (totalPages > maxVisiblePages) {
              if (currentPage < maxVisiblePages) {
                // Show pages 1 to maxVisiblePages
                startPage = 1;
                endPage = maxVisiblePages;
              } else {
                // Show sliding window ending on currentPage
                endPage = currentPage;
                startPage = currentPage - maxVisiblePages + 1;
            
                // Clamp to valid page range
                if (endPage > totalPages) {
                  endPage = totalPages;
                  startPage = endPage - maxVisiblePages + 1;
                }
              }
            }
        
            // Add page number buttons
            for (let i = startPage; i <= endPage; i++) {
              ul.appendChild(createPageItem(i, false, i === currentPage, () => onPageChange(i)));
            }
        
            // Next button
            ul.appendChild(createPageItem('Next', currentPage === totalPages, false, () => onPageChange(currentPage + 1)));
        
            return ul;
          }
      
          // User Management pagination (5 rows per page)
          function initUserPagination() {
            const rowsPerPage = 5;
            const table = document.getElementById('user-table');
            if (!table) return;
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const paginationContainer = document.getElementById('pagination-users');
        
            let currentPage = 1;
            const totalPages = Math.ceil(rows.length / rowsPerPage);
        
            function renderTablePage(page) {
              currentPage = page;
              rows.forEach(row => (row.style.display = 'none'));
              const start = (page - 1) * rowsPerPage;
              const end = start + rowsPerPage;
              rows.slice(start, end).forEach(row => (row.style.display = ''));
              renderPagination();
            }
        
            function renderPagination() {
              paginationContainer.innerHTML = '';
              if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
              }
              paginationContainer.style.display = 'flex';
          
              const paginationControls = createPaginationControls(totalPages, currentPage, page => {
                renderTablePage(page);
              });
              paginationContainer.appendChild(paginationControls);
            }
        
            renderTablePage(1);
          }
      
          // Engagement Management pagination (5 rows per page)
          function initEngagementPagination() {
            const rowsPerPage = 5;
            const table = document.querySelector('#tab-engagements table');
            if (!table) return;
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const paginationContainer = document.getElementById('pagination-engagements');
        
            let currentPage = 1;
            const totalPages = Math.ceil(rows.length / rowsPerPage);
        
            function renderTablePage(page) {
              currentPage = page;
              rows.forEach(row => (row.style.display = 'none'));
              const start = (page - 1) * rowsPerPage;
              const end = start + rowsPerPage;
              rows.slice(start, end).forEach(row => (row.style.display = ''));
              renderPagination();
            }
        
            function renderPagination() {
              paginationContainer.innerHTML = '';
              if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
              }
              paginationContainer.style.display = 'flex';
          
              const paginationControls = createPaginationControls(totalPages, currentPage, page => {
                renderTablePage(page);
              });
              paginationContainer.appendChild(paginationControls);
            }
        
            renderTablePage(1);
          }
      
          // System Activity pagination (3 cards per page)
          function initActivityPagination() {
            const cardsPerPage = 3;
            const activityList = document.getElementById('activity-list');
            if (!activityList) return;
            const cards = Array.from(activityList.querySelectorAll('.activity-card'));
            const paginationContainer = document.getElementById('activity-pagination');
        
            let currentPage = 1;
            const totalPages = Math.ceil(cards.length / cardsPerPage);
        
            function showPage(page) {
              currentPage = page;
              cards.forEach(card => {
                card.style.display = 'none';
                card.classList.remove('d-flex');
              });
              const start = (page - 1) * cardsPerPage;
              const end = start + cardsPerPage;
              for (let i = start; i < end && i < cards.length; i++) {
                cards[i].style.display = '';
                cards[i].classList.add('d-flex');
              }
              renderPagination();
            }
        
            function renderPagination() {
              paginationContainer.innerHTML = '';
              if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
              }
              paginationContainer.style.display = 'flex';
          
              const paginationControls = createPaginationControls(totalPages, currentPage, page => {
                showPage(page);
              });
              paginationContainer.appendChild(paginationControls);
            }
        
            showPage(1);
          }
      
          // Tab switching + reset pagination
          document.querySelectorAll('.custom-tabs button').forEach(btn => {
            btn.addEventListener('click', () => {
              document.querySelectorAll('.custom-tabs button').forEach(b => b.classList.remove('active'));
              btn.classList.add('active');
            
              document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('d-none'));
              const currentTab = document.getElementById('tab-' + btn.dataset.tab);
              currentTab.classList.remove('d-none');
            
              if (btn.dataset.tab === 'users') {
                document.dispatchEvent(new Event('reinitUserPagination'));
              } else if (btn.dataset.tab === 'activity') {
                document.dispatchEvent(new Event('reinitActivityPagination'));
              } else if (btn.dataset.tab === 'engagements') {
                document.dispatchEvent(new Event('reinitEngagementPagination'));
              }
            });
          });
      
          // Initialize paginations on DOM ready
          document.addEventListener('DOMContentLoaded', () => {
            initUserPagination();
            initActivityPagination();
            initEngagementPagination();
          });
      
          // Reinit paginations on tab switch
          document.addEventListener('reinitUserPagination', initUserPagination);
          document.addEventListener('reinitActivityPagination', initActivityPagination);
          document.addEventListener('reinitEngagementPagination', initEngagementPagination);
        </script>


<!-- end Pagination -->

<!-- update modal ajax -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const updateUserModal = document.getElementById('updateUserModal');
          const updateUserForm = document.getElementById('updateUserForm');

          updateUserModal.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget; // Button that triggered the modal
            const userId = button.getAttribute('data-user-id');

            // Clear previous values
            updateUserForm.reset();

            if (!userId) return;

            try {
              const response = await fetch(`get_user.php?user_id=${userId}`);
              if (!response.ok) throw new Error('Network response was not ok');

              const user = await response.json();

              // Populate form fields using querySelector for accuracy
              updateUserForm.querySelector('#update_user_id').value = user.user_id;
              updateUserForm.querySelector('#update_first_name').value = user.first_name;
              updateUserForm.querySelector('#update_last_name').value = user.last_name;
              updateUserForm.querySelector('#update_email').value = user.email;
              updateUserForm.querySelector('#update_role').value = user.role;
              updateUserForm.querySelector('#update_status').value = user.status;
            } catch (error) {
              console.error('Failed to load user data:', error);
              // Optionally show an alert or close modal here
            }
          });
        });
    </script>
<!-- end update modal ajax -->

<script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>

<!-- update engagement modal ajax -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const updateEngagementModal = document.getElementById('updateEngagementModal');
      const updateEngagementForm = document.getElementById('updateEngagementForm');

      updateEngagementModal.addEventListener('show.bs.modal', async (event) => {
        const button = event.relatedTarget; // Button that triggered the modal
        const engagementId = button.getAttribute('data-engagement-id');

        // Clear previous values
        updateEngagementForm.reset();

        if (!engagementId) return;

        try {
          const response = await fetch(`get_engagements.php?engagement_id=${engagementId}`);
          if (!response.ok) throw new Error('Network response was not ok');

          const engagement = await response.json();

          // Populate form fields using querySelector for accuracy
          updateEngagementForm.querySelector('#update_engagement_id').value = engagement.engagement_id;
          updateEngagementForm.querySelector('#update_client_name').value = engagement.client_name;
          updateEngagementForm.querySelector('#update_budgeted_hours').value = parseInt(engagement.budgeted_hours, 10);
          updateEngagementForm.querySelector('#update_status').value = engagement.status;
          updateEngagementForm.querySelector('#update_notes').value = engagement.notes || '';
        } catch (error) {
          console.error('Failed to load engagement data:', error);
          // Optionally show an alert or close modal here
        }
      });
    });
    </script>
<!-- end update engagement modal ajax -->

<!-- view engagement modal ajax -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
          const viewEngagementModal = document.getElementById('viewEngagementModal');

          viewEngagementModal.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const engagementId = button.getAttribute('data-engagement-id');
            if (!engagementId) return;
        
            try {
              const response = await fetch(`get_engagements.php?engagement_id=${encodeURIComponent(engagementId)}`);
              if (!response.ok) throw new Error('Network response was not ok');
            
              const engagement = await response.json();
            
              function setText(id, text) {
                const el = document.getElementById(id);
                if (!el) {
                  console.warn(`Element with ID "${id}" not found.`);
                  return;
                }
                el.textContent = (text && text.toString().trim()) ? text : '-';
              }

              function formatDate(dateString) {
                if (!dateString) return '-';
                const d = new Date(dateString);
                if (isNaN(d)) return '-';
                const month = d.getMonth() + 1;
                const day = d.getDate();
                const year = d.getFullYear();
                return `${month}/${day}/${year}`;
                }
          
              function formatHours(hours) {
                return hours ? parseInt(hours, 10) : 0;
              }

              function formatStatus(status) {
                if (!status) return '-';
                return status
                  .replace(/_/g, ' ')          // Replace underscores with spaces
                  .replace(/\b\w/g, c => c.toUpperCase()); // Capitalize first letter of each word
              }
          
              // Set engagement details
              setText('view_engagement_client_name', engagement.client_name);
              setText('view_client_name', engagement.client_name);
              const count = engagement.assigned_user_count || 0;
              const memberText = count === 1 ? 'member' : 'members';
              setText('view_engagement_team_size', `${count} ${memberText}`);
              setText('view_engagement_estimated_hours', formatHours(engagement.budgeted_hours) + ' hrs');
              setText('view_engagement_allocated_hours', formatHours(engagement.total_assigned_hours) + ' hrs');
              setText('view_engagement_created', formatDate(engagement.created));
              setText('view_engagement_updated', formatDate(engagement.last_updated));
              setText('view_eng_status', formatStatus(engagement.status));
              setText('view_engagement_notes', engagement.notes);
          
              // Handle status text and badge class
              const statusEl = document.getElementById('view_engagement_status');
              if (statusEl) {
                // Clean previous badge classes
                statusEl.classList.remove('badge-confirmed', 'badge-pending', 'badge-not-confirmed', 'badge-default');
            
                // Normalize status for comparison
                const statusNormalized = engagement.status.toLowerCase();
            
                // Map status to badge classes
                let badgeClass = 'badge-default'; // default fallback
                switch (statusNormalized) {
                  case 'confirmed':
                    badgeClass = 'badge-confirmed';   // green or whatever you define
                    break;
                  case 'pending':
                    badgeClass = 'badge-pending';     // yellow
                    break;
                  case 'not_confirmed':
                    badgeClass = 'badge-not-confirmed'; // red
                    break;
                }
            
                // Add the badge class
                statusEl.classList.add(badgeClass);
            
                // Set formatted status text
                statusEl.textContent = engagement.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
              }
          
            } catch (error) {
              console.error('Failed to load engagement data:', error);
            }
          });
        });

    </script>
<!-- end view engagement modal ajax -->

<!-- delete user -->
    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-user-btn')) {
                e.preventDefault();
                const userId = e.target.closest('.delete-user-btn').dataset.userId;
                if (confirm('Are you sure you want to delete this user?')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'user_id=' + encodeURIComponent(userId)
                    })
                    .then(res => res.text())
                    .then(response => {
                        if (response.trim() === 'success') {
                            // alert('User deleted successfully');
                            location.reload();
                        } else {
                            alert('Error deleting user');
                        }
                    });
                }
            }
        });
    </script>
<!-- end delete user -->

<!-- delete engagement -->
    <script>
        document.addEventListener('click', function(e) {
          if (e.target.closest('.delete-engagement-btn')) {
            e.preventDefault();
            const engagementId = e.target.closest('.delete-engagement-btn').dataset.engagementId;
            if (confirm('Are you sure you want to delete this engagement?')) {
              fetch('delete_engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'engagement_id=' + encodeURIComponent(engagementId)
              })
              .then(res => res.json())
              .then(response => {
                if (response.success) {
                  location.reload();
                } else {
                  alert('Error deleting engagement: ' + (response.error || 'Unknown error'));
                }
              })
              .catch(() => alert('Network or server error'));
            }
          }
        });

    </script>
<!-- end delete engagement -->

<!-- import users csv -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const importForm = document.getElementById('importUsersForm');
          const fileInput = document.getElementById('csv_file');
          const importSummary = document.getElementById('importSummary');
          const importSubmitBtn = document.getElementById('importSubmitBtn');
          const importCloseBtn = document.getElementById('importCloseBtn');
          const importUsersModal = new bootstrap.Modal(document.getElementById('importUsersModal'));
                
          importForm.addEventListener('submit', async (e) => {
            e.preventDefault();
        
            // Hide summary & OK button, show Import button
            importSummary.style.display = 'none';
            importSummary.innerHTML = '';
            importCloseBtn.classList.add('d-none');
            importSubmitBtn.classList.remove('d-none');
            
            const file = fileInput.files[0];
            if (!file) {
              alert('Please select a CSV file to upload.');
              return;
            }
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
              alert('Only CSV files are allowed.');
              return;
            }
        
            const formData = new FormData();
            formData.append('csv_file', file);
        
            try {
              const response = await fetch('import_users.php', {
                method: 'POST',
                body: formData
              });
              const result = await response.json();
          
              // Show import summary in modal
              importSummary.style.display = 'block';
          
              let html = `<p><strong>Import Results:</strong></p>`;
              html += `<p>Successfully imported: ${result.successCount}</p>`;
          
              if (result.errors.length > 0) {
                html += `<p class="text-danger">Errors (${result.errors.length}):</p><ul>`;
                result.errors.forEach(err => {
                  html += `<li>Row ${err.row}: ${err.message}</li>`;
                });
                html += `</ul>`;
              } else {
                html += `<p class="text-success">No errors found.</p>`;
              }
          
              importSummary.innerHTML = html;
          
              // Show OK button, hide Import button
              importCloseBtn.classList.remove('d-none');
              importSubmitBtn.classList.add('d-none');
          
              // Clear file input so user can re-import if needed
              fileInput.value = '';
          
            } catch (error) {
              alert('Error processing import: ' + error.message);
            }
          });
      
          importCloseBtn.addEventListener('click', () => {
            importUsersModal.hide();
            location.reload(); // Reload page to show new users
          });
        });

    </script>
<!-- end import users csv -->

<!-- import engagements csv -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const importForm = document.getElementById('importEngagementsForm');
          const fileInput = document.getElementById('engagements_csv_file');
          const importSummary = document.getElementById('engagementsImportSummary');
          const importSubmitBtn = document.getElementById('importEngagementsSubmitBtn');
          const importCloseBtn = document.getElementById('importEngagementsCloseBtn');
          const importModal = new bootstrap.Modal(document.getElementById('importEngagementsModal'));

          importForm.addEventListener('submit', async (e) => {
            e.preventDefault();
        
            importSummary.style.display = 'none';
            importSummary.innerHTML = '';
            importCloseBtn.classList.add('d-none');
            importSubmitBtn.classList.remove('d-none');
        
            const file = fileInput.files[0];
            if (!file) {
              alert('Please select a CSV file to upload.');
              return;
            }
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
              alert('Only CSV files are allowed.');
              return;
            }
        
            const formData = new FormData();
            formData.append('csv_file', file);
        
            try {
              const response = await fetch('import_engagements.php', {
                method: 'POST',
                body: formData
              });
              const result = await response.json();
          
              importSummary.style.display = 'block';
          
              let html = `<p><strong>Import Results:</strong></p>`;
              html += `<p>Successfully imported: ${result.successCount}</p>`;
          
              if (result.errors.length > 0) {
                html += `<p class="text-danger">Errors (${result.errors.length}):</p><ul>`;
                result.errors.forEach(err => {
                  html += `<li>Row ${err.row}: ${err.message}</li>`;
                });
                html += `</ul>`;
              } else {
                html += `<p class="text-success">No errors found.</p>`;
              }
          
              importSummary.innerHTML = html;
          
              importCloseBtn.classList.remove('d-none');
              importSubmitBtn.classList.add('d-none');
          
              fileInput.value = '';
          
            } catch (error) {
              alert('Error processing import: ' + error.message);
            }
          });
      
          importCloseBtn.addEventListener('click', () => {
            importModal.hide();
            location.reload(); // reload page to show new engagements
          });
        });

    </script>
<!-- end import engagements csv -->

<!-- bulk delete users -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
      const selectAllCheckbox = document.getElementById('selectAllUsers');
      const userCheckboxes = document.querySelectorAll('.selectUser');
      const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

      function updateBulkDeleteVisibility() {
        const anyChecked = Array.from(userCheckboxes).some(cb => cb.checked);
        bulkDeleteBtn.style.display = anyChecked ? 'inline-block' : 'none';
      }

      function updateBulkDeleteVisibility() {
        const checkedCheckboxes = Array.from(userCheckboxes).filter(cb => cb.checked);
        const count = checkedCheckboxes.length;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-block' : 'none';
          
        // Update the number displayed
        const selectedCountSpan = document.getElementById('selectedCount');
        if (selectedCountSpan) {
          selectedCountSpan.textContent = count;
        }
      }

      selectAllCheckbox.addEventListener('change', () => {
        userCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        updateBulkDeleteVisibility();
      });

      userCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          if (!cb.checked) {
            selectAllCheckbox.checked = false;
          } else if (Array.from(userCheckboxes).every(cb => cb.checked)) {
            selectAllCheckbox.checked = true;
          }
          updateBulkDeleteVisibility();
        });
      });

      bulkDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const selectedIds = Array.from(userCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.getAttribute('data-user-id'));

        if (selectedIds.length === 0) return;

        if (!confirm(`Are you sure you want to delete ${selectedIds.length} user(s)? This action cannot be undone.`)) {
          return;
        }

        try {
          const response = await fetch('bulk_delete_users.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_ids: selectedIds })
          });
          const result = await response.json();

          if (result.success) {
            // alert(`Deleted ${result.deletedCount} user(s) successfully.`);
            // Optionally reload page or remove deleted rows from table
            location.reload();
          } else {
            alert('Error deleting users: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network or server error: ' + error.message);
        }
      });
    });

    </script>
<!-- end bulk delete users -->

<!-- bulk delete engagements -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
      const selectAllCheckbox = document.getElementById('selectAllEngagements');
      const engagementCheckboxes = document.querySelectorAll('.selectEngagement');
      const bulkDeleteBtn = document.getElementById('bulkDeleteEngagementBtn');

      function updateBulkDeleteVisibility() {
        const checkedCheckboxes = Array.from(engagementCheckboxes).filter(cb => cb.checked);
        const count = checkedCheckboxes.length;
        bulkDeleteBtn.style.display = count > 0 ? 'inline-block' : 'none';

        // Update count display
        const selectedCountSpan = document.getElementById('selectedEngagementCount');
        if (selectedCountSpan) selectedCountSpan.textContent = count;
      }

      if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
          engagementCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
          updateBulkDeleteVisibility();
        });
      }

      engagementCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          if (!cb.checked && selectAllCheckbox) {
            selectAllCheckbox.checked = false;
          } else if (selectAllCheckbox && Array.from(engagementCheckboxes).every(cb => cb.checked)) {
            selectAllCheckbox.checked = true;
          }
          updateBulkDeleteVisibility();
        });
      });

      bulkDeleteBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const selectedIds = Array.from(engagementCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.getAttribute('data-engagement-id'));

        if (selectedIds.length === 0) return;

        if (!confirm(`Are you sure you want to delete ${selectedIds.length} engagement(s)? This action cannot be undone.`)) {
          return;
        }

        try {
          const response = await fetch('bulk_delete_engagements.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ engagement_ids: selectedIds })
          });
          const result = await response.json();

          if (result.success) {
            location.reload();
          } else {
            alert('Error deleting engagements: ' + (result.error || 'Unknown error'));
          }
        } catch (error) {
          alert('Network or server error: ' + error.message);
        }
      });
    });
    </script>
<!-- end bulk delete engagements -->

<!-- email notifications script -->
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------
    // Modal show/hide logic
    // ----------------------------
    const configureBtn = document.getElementById('configureEmailBtn');
    const modalEl = document.getElementById('emailNotifConfigModal');
    const modal = new bootstrap.Modal(modalEl);

    configureBtn.addEventListener('click', (e) => {
      e.preventDefault();
      modal.show();
    });

    // ----------------------------
    // Elements for test email
    // ----------------------------
    const testEmailInput = document.getElementById('testEmail');
    const sendTestEmailBtn = document.getElementById('sendTestEmailBtn');
    const testEmailStatus = document.getElementById('testEmailStatus');

    // Enable/disable Send Test Email button
    testEmailInput.addEventListener('input', () => {
      const email = testEmailInput.value.trim();
      if (email.length > 0) {
        sendTestEmailBtn.classList.remove('disabled');
        sendTestEmailBtn.style.pointerEvents = 'auto';
        sendTestEmailBtn.style.opacity = '1';
      } else {
        sendTestEmailBtn.classList.add('disabled');
        sendTestEmailBtn.style.pointerEvents = 'none';
        sendTestEmailBtn.style.opacity = '0.5';
      }
      testEmailStatus.classList.add('d-none');
      testEmailStatus.textContent = '';
    });

    // ----------------------------
    // Send Test Email click
    // ----------------------------
    sendTestEmailBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      const email = testEmailInput.value.trim();
      if (!email) return;

      // Show immediate feedback
      testEmailStatus.textContent = 'Sending test email...';
      testEmailStatus.classList.remove('d-none', 'text-success', 'text-danger');
      testEmailStatus.classList.add('text-info');

      try {
        const resp = await fetch('../includes/send_test_email.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ test_email: email })
        });

        const text = await resp.text();
        let result;

        // Safely parse JSON
        try {
          result = JSON.parse(text);
        } catch (err) {
          testEmailStatus.textContent = 'Server returned invalid response';
          testEmailStatus.classList.remove('text-info');
          testEmailStatus.classList.add('text-danger');
          console.error('JSON parse error:', err, text);
          return;
        }

        if (result.success) {
          testEmailStatus.textContent = result.message || 'Test email sent successfully!';
          testEmailStatus.classList.remove('text-info', 'text-danger');
          testEmailStatus.classList.add('text-success');
        } else {
          testEmailStatus.textContent = result.message || 'Failed to send test email';
          testEmailStatus.classList.remove('text-info', 'text-success');
          testEmailStatus.classList.add('text-danger');
        }

      } catch (err) {
        testEmailStatus.textContent = 'Network error: ' + err.message;
        testEmailStatus.classList.remove('text-info', 'text-success');
        testEmailStatus.classList.add('text-danger');
        console.error(err);
      }
    });

    // ----------------------------
    // Save Settings form submit
    // ----------------------------
    const emailForm = document.getElementById('emailNotifConfigForm');
    emailForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData.entries());
      data.enable_email_notifications = formData.get('enable_email_notifications') === 'on' ? 'true' : 'false';

      const payload = {
        setting_master_key: 'email',
        settings: data
      };

      console.log('Submitting email settings:', payload);

      try {
        const resp = await fetch('settings_backend.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const text = await resp.text();
        let result;

        try {
          result = JSON.parse(text);
        } catch (err) {
          alert('Server returned invalid response while saving settings');
          console.error('JSON parse error:', err, text);
          return;
        }

        if (result.success) {
          modal.hide();
          console.log('Email settings saved successfully.');
        } else {
          alert('Failed to save settings: ' + (result.error || 'Unknown error'));
        }
      } catch (err) {
        alert('Network error: ' + err.message);
        console.error(err);
      }
    });
  });
  </script>
<!-- end email notification script -->



<!-- backup configuration -->
    <script>
  // Show/hide cloud storage options based on storageLocation select
  function updateCloudStorageVisibility() {
    const storageSelect = document.getElementById('storageLocation');
    const cloudSettings = document.getElementById('cloudStorageSettings');
    if (storageSelect.value === 'cloud') {
      cloudSettings.style.display = 'block';
    } else {
      cloudSettings.style.display = 'none';
    }
  }

  // Show Backup Configuration Modal on configure button click
  document.getElementById('configureBackupBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const modalEl = document.getElementById('backupConfigModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  });

  document.getElementById('storageLocation').addEventListener('change', updateCloudStorageVisibility);

  // Initialize visibility on page load
  updateCloudStorageVisibility();

  // Run Test Backup button handler (example)
  document.getElementById('runTestBackupBtn').addEventListener('click', async () => {
    const btn = document.getElementById('runTestBackupBtn');
    btn.disabled = true;
    btn.textContent = 'Running Test Backup...';

    try {
      const resp = await fetch('/api/run_test_backup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({})
      });
      const result = await resp.json();

      if (result.success) {
        alert('Test backup ran successfully!');
      } else {
        alert('Test backup failed: ' + (result.error || 'Unknown error'));
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }

    btn.disabled = false;
    btn.textContent = 'Run Test Backup';
  });

  // AJAX form submission for backup settings
  document.getElementById('backupConfigForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);

    // Convert FormData to plain object
    const data = {};
    formData.forEach((value, key) => {
      // For unchecked checkboxes, no value submitted; we want to record 'false'
      if (data[key] === undefined) {
        data[key] = value;
      }
    });

    // Handle unchecked checkboxes: ensure keys exist and are 'false'
    ['enable_automated_backups', 'backup_users', 'backup_engagements', 'backup_entries', 'backup_settings'].forEach(key => {
      if (!formData.has(key)) {
        data[key] = 'false';
      }
    });

    const payload = {
      setting_master_key: 'backup',
      settings: data
    };

    try {
      const resp = await fetch('settings_backend.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      const result = await resp.json();

      if (result.success) {
        // Hide modal on success
        const modalEl = document.getElementById('backupConfigModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance.hide();
        // alert('Backup settings saved successfully!');
      } else {
        alert('Failed to save settings: ' + (result.error || 'Unknown error'));
      }
    } catch (err) {
      alert('Network error: ' + err.message);
    }
  });
    </script>

<!-- end backup configuration -->

<!-- Security Policy Script -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const configureBtn = document.getElementById('configureSecurityBtn');
        const modalEl = document.getElementById('securityPolicyConfigModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('securityPolicyConfigForm');

        // Open modal on Configure button click
        configureBtn.addEventListener('click', (e) => {
          e.preventDefault();
          modal.show();
        });

        // Helper to get form data and convert checkboxes to "true"/"false"
        function getFormData(formElement) {
          const data = {};
          const formData = new FormData(formElement);
          for (const [key, value] of formData.entries()) {
            data[key] = value;
          }

          // Convert unchecked checkboxes (not present in formData) to false
          formElement.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (!formData.has(cb.name)) {
              data[cb.name] = "false";
            } else {
              data[cb.name] = "true";
            }
          });

          return data;
        }

        // Handle form submission via AJAX
        form.addEventListener('submit', async (e) => {
          e.preventDefault();

          const submitBtn = form.querySelector('button[type="submit"]');
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

          const settings = getFormData(form);
          const payload = {
            setting_master_key: 'security_policy',
            settings: settings
          };

          try {
            const response = await fetch('settings_backend.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (result.success) {
            //   alert('Security policy settings saved successfully!');
              modal.hide();
              // Optionally refresh page or update UI here
            } else {
              alert('Error saving settings: ' + (result.error || 'Unknown error'));
            }
          } catch (err) {
            alert('Network error: ' + err.message);
          } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-save me-3"></i>Save Settings';
          }
        });
      });
    </script>

<!-- Security Policy Script -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
