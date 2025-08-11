<?php
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


$sql = "SELECT user_id, first_name, last_name, email, role, status, last_active 
        FROM users 
        ORDER BY last_active DESC";
$result = mysqli_query($conn, $sql);

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
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stat-title">Active Projects</div>
                    <div class="stat-value">12</div>
                    <div class="stat-sub">16 completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-title">System Utilization</div>
                    <div class="stat-value">87%</div>
                    <div class="util-bar mt-2"><div class="util-bar-fill" style="width:87%"></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-title">Total Hours</div>
                    <div class="stat-value">4,580</div>
                    <div class="stat-sub">All time logged</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="custom-tabs">
            <button class="active" data-tab="users">User Management</button>
            <button data-tab="activity">System Activity</button>
            <button class="overlay-red" data-tab="analytics">Analytics</button>
            <button class="overlay-red" data-tab="settings">Settings</button>
        </div>

        <!-- Tab Content -->
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


        <div id="tab-analytics" class="tab-content d-none">
            <div class="row g-3 ps-3 pe-3 mt-1">
                <div class="col-md-6">
                    <div class="analytic-card">
                        <div class="analytic-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>User Activity Overview</strong></p>
                                <p style="font-size: 14px;">Active vs Inactive users</p>
                            </div>
                        </div>
                        <div class="pb-4"></div>
                        <div class="d-flex justify-content-between pb-1">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                Active Users
                            </div>
                            <div class="float-end">
                                38
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-x-circle text-danger"></i>
                                Inactive Users
                            </div>
                            <div class="float-end">
                                7
                            </div>
                        </div>
                        <div class="analytic-util-bar mt-3"><div class="analytic-util-bar-fill" style="width:85%"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytic-card">
                        <div class="analytic-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>Engagement Status</strong></p>
                                <p style="font-size: 14px;">Current engagement distribution</p>
                            </div>
                        </div>
                        <div class="pb-4"></div>
                        <div class="d-flex justify-content-between pb-1">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-clipboard-check text-success"></i>
                                Assigned Engagements
                            </div>
                            <div class="float-end">
                                92
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-clipboard-x text-danger"></i>
                                Unassigned Engagements
                            </div>
                            <div class="float-end">
                                8
                            </div>
                        </div>
                        <div class="analytic-util-bar mt-3"><div class="analytic-util-bar-fill" style="width:92%"></div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 ps-3 pe-3 mt-2">
                <div class="col-md-12">
                    <div class="analytic-card" style="height: 215px !important;">
                        <div class="user-management-header">
                        <div class="titles">
                            <p class="text-black" style="font-size: 14px;"><strong>Advanced Reports</strong></p>
                            <p style="font-size: 14px;">Generate detailed system reports</p>
                        </div>
                        <div class="user-management-buttons">
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: 14px; border: 1px solid rgb(229,229,229);">
                                <i class="bi bi-download me-3"></i>Export All Data
                            </a>
                        </div>
                    </div>
                        <div class="d-flex justify-content-between pb-2">
                            <div class="reports-card">
                                <i class="bi bi-graph-up-arrow"></i>
                                <div class="analytic-title mt-2 fw-semibold">Utilization Report</div>
                                <div class="analytic-subtitle">Staff and engagement utilization</div>
                            </div>

                            <div class="reports-card">
                                <i class="bi bi-people"></i>
                                <div class="analytic-title mt-2 fw-semibold">User Activity Report</div>
                                <div class="analytic-subtitle">Login and engagement metrics</div>
                            </div>

                            <div class="reports-card">
                                <i class="bi bi-clock"></i>
                                <div class="analytic-title mt-2 fw-semibold">Time Tracking Report</div>
                                <div class="analytic-subtitle">Hours and productivity analysis</div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div id="tab-settings" class="tab-content d-none">
            <div class="row g-3 ps-3 pe-3 mt-1">
                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="analytic-header mb-4">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>System Configuration</strong></p>
                                <p style="font-size: 14px;">Manage system-wide settings</p>
                            </div>
                        </div>

                        <!-- Email Notifications -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Email Notifications</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Send system notifications via email</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>

                        <!-- Backup Settings -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Backup Settings</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Automated data backup configuration</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>

                        <!-- Security Policies -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Security Policies</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Password and access requirements</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card d-flex flex-column h-100">
                        <div class="settings-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>Engagement Status</strong></p>
                                <p style="font-size: 14px;">Current engagement distribution</p>
                            </div>
                        </div>

                        <div class="mb-4"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                Database Status
                            </div>
                            <span class="badge text-bg-success pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(226,251,232) !important; color: rgba(64, 109, 72, 1) !important;">
                                Healthy
                            </span>
                        </div>

                        <div class="mb-2"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                API Status
                            </div>
                            <span class="badge text-bg-success pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(226,251,232) !important; color: rgba(64, 109, 72, 1) !important;">
                                Operational
                            </span>
                        </div>

                        <div class="mb-2"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-exclamation-circle text-warning"></i>
                                Storage Usage
                            </div>
                            <span class="badge pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(253,249,200) !important; color: rgba(135,88,30) !important;">
                                75% Used
                            </span>
                        </div>

                        <!-- Spacer pushes button to bottom -->
                        <div class="flex-grow-1"></div>

                        <a href="#" class="badge text-black p-2 text-decoration-none fw-medium w-100 mt-3"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                <i class="bi bi-activity pe-3"></i>View Detailed Metrics
                            </a>
                    </div>
                </div>

            </div>
            
        
        </div>
    </div>
</div>


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
      <div class="modal-dialog">
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

            <div class="row">
                <div class="col-md-6">
                    <i class="bi bi-envelope"></i> Personal Information
                    <p class="text-muted">
                      <strong>First Name:</strong>
                      <span id="view_first_name" class="d-inline-block text-end" style="min-width: 100px;"></span>
                    </p>
                    <p><strong>Last Name:</strong> <span id="view_last_name"></span></p>
                    <p><strong>Email:</strong> <span id="view_email"></span></p>
                </div>
                <div class="col-md-6">
                    test
                </div>
            </div>
            <hr>

        

          <!-- For initials -->
<div id="view_user_initials"></div>

<!-- For full name and role -->
<div id="view_user_fullname"></div>
<div id="view_user_role"></div>

<!-- Individual user info -->
<p><strong>First Name:</strong> <span id="view_first_name"></span></p>
<p><strong>Last Name:</strong> <span id="view_last_name"></span></p>
<p><strong>Email:</strong> <span id="view_email"></span></p>
<p><strong>Status:</strong> <span id="view_status" class="badge-status"></span></p>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
<!-- end View user modal -->






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
    const paginationContainer = document.getElementById('pagination');

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
      }
    });
  });

  // Initialize paginations on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    initUserPagination();
    initActivityPagination();
  });

  // Reinit paginations on tab switch
  document.addEventListener('reinitUserPagination', initUserPagination);
  document.addEventListener('reinitActivityPagination', initActivityPagination);
</script>


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
      console.log('User data:', user);

      function setText(id, text) {
        const el = document.getElementById(id);
        if (el) {
          el.textContent = text;
        } else {
          console.warn(`Element with ID "${id}" not found.`);
        }
      }

      // Check for missing keys in user object
      ['first_name', 'last_name', 'email', 'role', 'status'].forEach(key => {
        if (!(key in user)) {
          console.warn(`Missing key "${key}" in user data.`);
        }
      });

      const firstInitial = user.first_name ? user.first_name.charAt(0).toUpperCase() : '';
      const lastInitial = user.last_name ? user.last_name.charAt(0).toUpperCase() : '';
      setText('view_user_initials', firstInitial + lastInitial);
      setText('view_user_fullname', `${user.first_name || ''} ${user.last_name || ''}`.trim());
      setText('view_user_role', user.role || '[No role]');
      setText('view_first_name', user.first_name || '[No first name]');
      setText('view_last_name', user.last_name || '[No last name]');
      setText('view_email', user.email || '[No email]');
      setText('view_status', user.status || '[No status]');

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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
