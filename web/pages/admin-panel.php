<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    JOIN assignments a ON e.engagement_id = a.engagement_id
";

$assignedResult = mysqli_query($conn, $assignedEngagementsQuery);
$assignedRow = mysqli_fetch_assoc($assignedResult);
$totalAssigned = $assignedRow['total_assigned'];

// Total not assigned
$notAssignedEngagementsQuery = "
    SELECT COUNT(*) AS total_not_assigned
    FROM engagements e
    LEFT JOIN assignments a ON e.engagement_id = a.engagement_id
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
    e.total_available_hours,
    e.status,
    e.notes,
    COALESCE(SUM(a.assigned_hours), 0) AS total_assigned_hours
  FROM engagements e
  LEFT JOIN assignments a ON e.engagement_id = a.engagement_id
  GROUP BY e.engagement_id, e.client_name, e.total_available_hours, e.status, e.notes
  ORDER BY e.client_name ASC
";

$engagementResults = mysqli_query($conn, $engagementSQL);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4">
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
                        <div class="stat-title">Cnnfirmed Engagements</div>
                        <div class="stat-value"><?php echo $totalConfirmedEngagements; ?></div>
                        <div class="stat-sub"><?php echo $totalPendingEngagements; ?> pending <i class="bi bi-dot"></i> <?php echo $totalNotConfirmedEngagements; ?> not confirmed</div>
                    </div>
                </div>
                <?php

                // Avoid division by zero
                $percentageAssigned = ($totalUsers > 0) ? round(($totalAssigned / $totalEngagements) * 100) : 0;
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
                <button class="active" data-tab="users">User Management</button>
                <button class="overlay-red" data-tab="engagements">Engagement Management</button>
                <button data-tab="activity">System Activity</button>
                <button class="overlay-red" data-tab="settings">Settings</button>
            </div>
        <!-- end Tabs -->

        <!-- user management -->
            <div id="tab-users" class="tab-content">
                <div class="user-management-header">
                    <div class="titles">
                        <p class="text-black"><strong>User Management</strong></p>
                        <p>Manage user accounts, roles, and permissions</p>
                    </div>
                    <div class="user-management-buttons">
                        <a href="#" class="badge text-black p-2 text-decoration-none fw-medium overlay-red" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                            <i class="bi bi-upload me-3"></i>Import Users
                        </a>
                        <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus me-3"></i>Add User
                        </a>
                    </div>
                </div>


                <div class="user-table">
                    <table id="user-table" class="table table-hover mb-0">
                        <thead>
                            <tr>
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

                                        <i class="bi bi-trash overlay-red"></i>
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
                    <ul id="pagination" class="pagination justify-content-center mt-3"></ul>
                </nav>
            </div>
        <!-- end user management -->

        <!-- engagement management -->

            <div id="tab-engagements" class="tab-content d-none">
                <div class="user-management-header">
                    <div class="titles">
                        <p class="text-black"><strong>Engagement Management</strong></p>
                        <p>Manage all engagements and assignments</p>
                    </div>
                    <div class="user-management-buttons">
                        <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);" data-bs-toggle="modal" data-bs-target="#addEngagementModal">
                            <i class="bi bi-person-plus me-3"></i>Add Engagement
                        </a>
                    </div>
                </div>


                <div class="user-table">
                    <table id="user-table" class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Estimated Hours</th>
                                <th>Assigned Hours</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($engagementResults) > 0): ?>
                            <?php while ($E_row = mysqli_fetch_assoc($engagementResults)): ?>
                                <tr>
                                    <td>
                                        <?php echo $E_row['client_name']; ?><br>
                                    </td>
                                    <td>
                                        <?php echo (int)$E_row['total_available_hours']; ?> hours
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
                                        <a href="#" class="edit-user-btn text-decoration-none" data-bs-toggle="modal" data-bs-target="#updateUserModal" data-engagement-id="<?php echo $row['user_id']; ?>">
                                            <i class="bi bi-pencil text-purple "></i>
                                        </a>

                                        <i class="bi bi-trash overlay-red"></i>
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
                  <ul id="pagination-engagements" class="pagination justify-content-center mt-3"></ul>
                </nav>
                            
            </div>

        <!-- end engagement management -->

        <!-- system activty -->
            <div id="tab-activity" class="tab-content d-none">
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
                                
                                case 'assignment_created':
                                    $icon = 'bi-file-earmark-plus';
                                    $color = 'rgb(40,167,69)'; // green
                                    break;
                                
                                case 'assignment_deleted':
                                    $icon = 'bi-file-earmark-minus';
                                    $color = 'rgb(220,53,69)'; // green
                                    break;
                                
                                case 'assignment_updated':
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
            <div id="tab-settings" class="tab-content d-none">
              <div class="row g-3 ps-3 pe-3 mt-1">
                <!-- Left Column: System Configuration -->
                <div class="col-md-6">
                  <div class="settings-card">
                    <div class="analytic-header mb-4">
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
                      <a href="#" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
                    </div>

                    <!-- Backup Settings -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Backup Settings</p>
                        <p class="mb-0" style="font-size: 14px;">Automated data backup configuration</p>
                      </div>
                      <a href="#" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
                    </div>

                    <!-- Security Policies -->
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <p class="text-black fw-semibold mb-0" style="font-size: 14px;">Security Policies</p>
                        <p class="mb-0" style="font-size: 14px;">Password and access requirements</p>
                      </div>
                      <a href="#" class="badge text-black p-2 text-decoration-none fw-medium border" style="font-size: 14px;">
                        Configure
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Engagement Status -->
                <div class="col-md-6">
                  <div class="settings-card d-flex flex-column h-100">
                    <div class="settings-header mb-4">
                      <div class="titles">
                        <p class="text-black fw-bold mb-1" style="font-size: 14px;">Engagement Status</p>
                        <p class="fs-6" style="font-size: 14px;">Current engagement distribution</p>
                      </div>
                    </div>

                    <div class="mb-4"></div>

                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-check2-circle text-success me-1"></i>Database Status
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(226,251,232); color: rgba(64,109,72,1);">
                        Healthy
                      </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-check2-circle text-success me-1"></i>API Status
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(226,251,232); color: rgba(64,109,72,1);">
                        Operational
                      </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center" style="font-size: 14px;">
                      <div>
                        <i class="bi bi-exclamation-circle text-warning me-1"></i>Storage Usage
                      </div>
                      <span class="badge pe-3 ps-3" style="font-size: 11px; background-color: rgb(253,249,200); color: rgba(135,88,30);">
                        75% Used
                      </span>
                    </div>

                    <!-- Spacer pushes button to bottom -->
                    <div class="flex-grow-1"></div>

                    <a href="#" class="badge text-black p-2 text-decoration-none fw-medium w-100 mt-3 border" style="font-size: .875rem;">
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
                          <i class="bi bi-download me-3"></i>Export All Data
                        </a>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pb-2">
                      <div class="reports-card text-center">
                        <i class="bi bi-graph-up-arrow"></i>
                        <div class="analytic-title mt-2 fw-semibold">Utilization Report</div>
                        <div class="analytic-subtitle">Staff and engagement utilization</div>
                      </div>

                      <div class="reports-card text-center">
                        <i class="bi bi-people"></i>
                        <div class="analytic-title mt-2 fw-semibold">User Activity Report</div>
                        <div class="analytic-subtitle">Login and engagement metrics</div>
                      </div>

                      <div class="reports-card text-center">
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

<!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
      <div class="modal-dialog" style="min-width: 600px !important;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserModalLabel">
                <i class="bi bi-people"></i> User Details <br>
                <span class="text-muted" style="font-size: 12px !important; font-weight: 400 !important; padding-top: -10px !important;">Complete profile information for <span id="view_first_name"></span> <span id="view_last_name"></span></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">

            <div style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; gap: 10px; padding: 10px; margin-top: -20px;">
              <div id="view_user_initials" 
                   class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="padding: 25px !important; width: 50px; height: 50px; font-weight: 500; font-size: 20px;">
                <!-- Initials will go here -->
              </div>
              <div>
                <div id="view_user_fullname" class="fw-semibold"></div>
                <small id="view_email" class="text-muted"></small><br>
                <small class="text-capitalize badge-role mt-2" style="font-size: 12px;" id="view_user_role">...</small>
                <small class="text-capitalize badge-status mt-2" style="font-size: 12px;" id="view_status">...</small>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-envelope"></i> Personal Information
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">First Name:</strong>
                  <span id="view_first_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Name:</strong>
                  <span id="view_last_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Email:</strong>
                  <span id="view_email_detail" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-person-lock"></i> Account Details
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Created:</strong>
                  <span id="view_acct_created" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Active:</strong>
                  <span id="view_acct_last_active" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Status:</strong>
                  <span id="view_acct_status" class="text-capitalize" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <h6 class="mb-3">
                        <i class="bi bi-shield"></i> Access & Permissions
                    </h6>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Role:</strong>
                        <span id="view_acct_role" class="text-capitalize" style="float: right;"></span>
                     </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Access Level:</strong>
                        <span id="view_acct_access_level" class="text-capitalize" style="float: right;"></span>
                    </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Two-Factor Auth:</strong>
                        <span id="view_acct_mfa" style="float: right;"></span>
                    </p>
                </div>
                <div class="col-md-6"></div>
            </div>

            <hr>

            <div class="col-md-12">
              <h6>Recent Activity</h6>
              <div id="view_recent_activity" style="max-height: 150px; overflow-y: auto;">
                <!-- Activities will be inserted here as cards -->
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn text-muted" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<!-- end View user modal -->

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

            <div style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; gap: 10px; padding: 10px; margin-top: -20px;">
              <div id="view_user_initials" 
                   class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                   style="padding: 25px !important; width: 50px; height: 50px; font-weight: 500; font-size: 20px;">
                <!-- Initials will go here -->
              </div>
              <div>
                <div id="view_user_fullname" class="fw-semibold"></div>
                <small id="view_email" class="text-muted"></small><br>
                <small class="text-capitalize badge-role mt-2" style="font-size: 12px;" id="view_user_role">...</small>
                <small class="text-capitalize badge-status mt-2" style="font-size: 12px;" id="view_status">...</small>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-envelope"></i> Personal Information
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">First Name:</strong>
                  <span id="view_first_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Name:</strong>
                  <span id="view_last_name_detail" class="text-capitalize" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Email:</strong>
                  <span id="view_email_detail" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
              <div class="col-md-6">
                <h6 class="mb-3">
                  <i class="bi bi-person-lock"></i> Account Details
                </h6>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Created:</strong>
                  <span id="view_acct_created" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Last Active:</strong>
                  <span id="view_acct_last_active" style="float: right;"></span>
                </p>
                <p class="text-muted mb-1" style="overflow: hidden;">
                  <strong style="float: left;">Status:</strong>
                  <span id="view_acct_status" class="text-capitalize" style="float: right;"></span>
                </p>
                <div class="mt-3"></div>
                <hr>
              </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <h6 class="mb-3">
                        <i class="bi bi-shield"></i> Access & Permissions
                    </h6>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Role:</strong>
                        <span id="view_acct_role" class="text-capitalize" style="float: right;"></span>
                     </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Access Level:</strong>
                        <span id="view_acct_access_level" class="text-capitalize" style="float: right;"></span>
                    </p>
                    <p class="text-muted mb-1" style="overflow: hidden;">
                        <strong style="float: left;">Two-Factor Auth:</strong>
                        <span id="view_acct_mfa" style="float: right;"></span>
                    </p>
                </div>
                <div class="col-md-6"></div>
            </div>

            <hr>

            <div class="col-md-12">
              <h6>Recent Activity</h6>
              <div id="view_recent_activity" style="max-height: 150px; overflow-y: auto;">
                <!-- Activities will be inserted here as cards -->
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn text-muted" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<!-- end View user modal -->




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

        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
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

<!-- view user modal ajax -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const viewUserModal = document.getElementById('viewUserModal');
        
          viewUserModal.addEventListener('show.bs.modal', async (event) => {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            if (!userId) return;
        
            try {
              const response = await fetch(`get_user.php?user_id=${encodeURIComponent(userId)}`);
                  if (!response.ok) throw new Error('Network response was not ok');
            
                  const user = await response.json();
            
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
          
              function timeSince(dateString) {
          if (!dateString) return '-';
          const now = new Date();
              const past = new Date(dateString);
            
              if (isNaN(past.getTime())) return '-';  // invalid date
            
              let seconds = Math.floor((now - past) / 1000);
            
              if (seconds < 0) seconds = 0;  // if future date, treat as now
            
          if (seconds < 5) return 'just now';
              if (seconds < 60) return `${seconds}s ago`;
            
          const minutes = Math.floor(seconds / 60);
              if (minutes < 60) return `${minutes}m ago`;
            
          const hours = Math.floor(minutes / 60);
              if (hours < 24) return `${hours}h ago`;
            
          const days = Math.floor(hours / 24);
              if (days < 7) return `${days}d ago`;
            
          // fallback: show formatted date
          return formatDate(dateString);
        }


              const firstInitial = user.first_name ? user.first_name.charAt(0).toUpperCase() : '-';
              const lastInitial = user.last_name ? user.last_name.charAt(0).toUpperCase() : '-';
              setText('view_user_initials', firstInitial + lastInitial);

              setText('view_user_fullname', `${user.first_name || '-'} ${user.last_name || '-'}`);
              setText('view_email', user.email);
              setText('view_user_role', user.role);

              setText('view_first_name_detail', user.first_name);
              setText('view_last_name_detail', user.last_name);
              setText('view_email_detail', user.email);

              setText('view_status', user.status);
              setText('view_acct_status', user.status);
              setText('view_acct_created', formatDate(user.created));
              setText('view_acct_last_active', formatDate(user.last_active));

              function getAccessLevel(role) {
                switch(role.toLowerCase()) {
                  case 'admin': return 'Full Access';
                  case 'manager': return 'High Access';
                  case 'senior': return 'Restricted Access';
                  case 'staff': return 'Restricted Access';
                  case 'intern': return 'Restricted Access';
                  default: return 'Unknown Access';
                }
                }
          
              setText('view_acct_role', user.role);
                setText('view_acct_access_level', getAccessLevel(user.role || ''));
          
              function boolToEnabledDisabled(value) {
                return value == 1 ? 'Enabled' : 'Disabled';
                }
          
              const mfaEl = document.getElementById('view_acct_mfa');
              if (mfaEl) {
                const statusText = boolToEnabledDisabled(user.mfa_enabled);
                mfaEl.textContent = statusText;
                mfaEl.classList.remove('text-success', 'text-danger');
                if (statusText === 'Enabled') {
                  mfaEl.classList.add('text-success');
                } else {
                  mfaEl.classList.add('text-danger');
                }
                }
          
              // Render recent activities as cards with description ellipsis + relative time
              const activityList = document.getElementById('view_recent_activity');
              if (activityList) {
                activityList.innerHTML = ''; // clear previous
                if (user.recent_activities && user.recent_activities.length > 0) {
                  user.recent_activities.forEach(act => {
                    const card = document.createElement('div');
                        card.className = 'activity-card';
                
                    const desc = document.createElement('div');
                    desc.className = 'activity-description';
                    desc.title = act.description;
                        desc.textContent = act.description;
                
                    const time = document.createElement('div');
                    time.className = 'activity-time';
                        time.textContent = timeSince(act.created_at);
                
                    card.appendChild(desc);
                    card.appendChild(time);
                    activityList.appendChild(card);
                  });
                } else {
                  const empty = document.createElement('div');
                  empty.className = 'text-muted px-3';
                  empty.textContent = 'No recent activity found.';
                  activityList.appendChild(empty);
                }
                }
          
              // Update badge class for status
              const statusEl = document.getElementById('view_status');
              if (statusEl) {
                statusEl.classList.remove('active', 'inactive');
                if (user.status && user.status.toLowerCase() === 'active') {
                  statusEl.classList.add('active');
                } else {
                  statusEl.classList.add('inactive');
                }
                }
          
            } catch (error) {
              console.error('Failed to load user data:', error);
            }
          });
        });
    </script>
<!-- end view user modal ajax -->


<!-- view engagement modal ajax -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const viewEngagementModal = document.getElementById('viewEngagementModal');

        viewEngagementModal.addEventListener('show.bs.modal', async (event) => {
          const button = event.relatedTarget;
          const engagementId = button.getAttribute('data-engagement-id');
          if (!engagementId) return;

          try {
            const response = await fetch(`get_engagement.php?engagement_id=${encodeURIComponent(engagementId)}`);
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

            // Format hours to integer (like your 400, not 400.00)
            function formatHours(hours) {
              return hours ? parseInt(hours, 10) : 0;
            }

            setText('view_engagement_client_name', engagement.client_name);
            setText('view_engagement_status', engagement.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()));
            setText('view_engagement_estimated_hours', formatHours(engagement.total_available_hours) + ' hours');
            setText('view_engagement_assigned_hours', formatHours(engagement.total_assigned_hours) + ' hours');
            setText('view_engagement_notes', engagement.notes);

          } catch (error) {
            console.error('Failed to load engagement data:', error);
          }
        });
      });
    </script>
<!-- end view engagement modal ajax -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
