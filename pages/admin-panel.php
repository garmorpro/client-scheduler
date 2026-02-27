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


$usersql = "SELECT user_id, full_name, email, role, status, theme_mode last_active 
        FROM users 
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
    <title>Admin Panel</title>
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
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="header-text flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Administrative Dashboard</h3>
    <p class="text-muted mb-4">System overview and user management for Admin User</p>

    

    <div class="container-fluid">

        <!-- Stat cards -->
            <!-- <div class="row g-3">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-title">Total Users</div>
                        <div class="stat-value"><?php //echo $totalUsers; ?></div>
                        <div class="stat-sub">+<?php //echo $newUsers; ?> this month</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-person-up"></i></div>
                        <div class="stat-title">Active Users</div>
                        <div class="stat-value"><?php //echo $totalActiveUsers; ?></div>
                        <div class="stat-sub"><?php //echo $totalInactiveUsers; ?> inactive users</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="stat-title">Confirmed Engagements</div>
                        <div class="stat-value"><?php //echo $totalConfirmedEngagements; ?></div>
                        <div class="stat-sub"><?php //echo $totalPendingEngagements; ?> pending <i class="bi bi-dot"></i> <?php //echo $totalNotConfirmedEngagements; ?> not confirmed</div>
                    </div>
                </div> -->
                <?php
                // if ($totalEngagements > 0) {
                //     $percentageAssigned = round(($totalAssigned / $totalEngagements) * 100);
                // } else {
                //     $percentageAssigned = 0;
                // }
                ?>
                <!-- <div class="col-md-3">
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-title">Engagement Status</div>
                        <div class="stat-value"><?php //echo $percentageAssigned; ?>%</div>
                        <div class="util-bar mt-2">
                            <div class="util-bar-fill" style="width: <?php //echo $percentageAssigned; ?>%"></div>
                        </div>
                        <div class="stat-sub mt-2">
                            <?php //echo $totalAssigned; ?> assigned <i class="bi bi-dot"></i> <?php //echo $totalNotAssigned; ?> not assigned
                        </div>
                    </div>
                </div>
            </div> -->
        <!-- end stats cards -->


        <!-- New Sections -->

     <div class="settings-container">

  <!-- Company Holidays -->
  <div class="card">
    <div class="card-header">
      <h2>Company Holidays</h2>
      <button class="add-btn">+ Add</button>
    </div>
    <div class="holiday">Memorial Day<br><small>Sunday, May 24, 2026 · 8 hours off</small></div>
    <div class="holiday">Independence Day (Observed)<br><small>Thursday, July 2, 2026 · 8 hours off</small></div>
    <div class="holiday">Labor Day<br><small>Sunday, September 6, 2026 · 8 hours off</small></div>
  </div>

  <!-- Active Clients -->
  <div class="card">
    <div class="card-header">
      <h2>Active Clients</h2>
      <small>13 active clients</small>
    </div>
    <div class="client">Horizon Ltd<br><span class="client-tag">HORZ</span></div>
    <div class="client">Nexus Systems<br><span class="client-tag">NEXS</span></div>
    <div class="client">Catalyst Co<br><span class="client-tag">CATA</span></div>
    <div class="client">Vertex Holdings<br><span class="client-tag">VERT</span></div>
    <div class="client">Sterling Enterprises<br><span class="client-tag">STER</span></div>
    <div class="client">Meridian Financial<br><span class="client-tag">MERI</span></div>
    <div class="client">PTO<br><span class="client-tag">PTO</span></div>
  </div>

  <!-- Role Permissions -->
  <div class="card">
    <h2>Role Permissions</h2>
    <p>Access control and permission levels</p>
    <div class="role role-admin">
      <strong>Admin</strong> · Full Access
      <ul>
        <li>Edit all schedules</li>
        <li>Manage holidays</li>
        <li>Configure settings</li>
        <li>View all employee details</li>
      </ul>
    </div>
    <div class="role role-manager">
      <strong>Manager</strong> · Edit Access
      <ul>
        <li>Edit schedules</li>
        <li>Approve PTO requests</li>
        <li>View employee details</li>
        <li>No settings access</li>
      </ul>
    </div>
    <div class="role role-staff">
      <strong>Senior & Staff</strong> · View Only
      <ul>
        <li>View own schedule</li>
        <li>View master schedule</li>
        <li>No edit access</li>
        <li>No settings access</li>
      </ul>
    </div>
  </div>

</div>



        <!-- end New Sections -->





        
    </div> <!-- end container -->
</div> <!-- end flex-grow -->

<?php include_once '../includes/modals/global_pto_modal.php'; ?>
<?php include_once '../includes/modals/import_global_pto_modal.php'; ?>
<?php //include_once '../includes/modals/viewProfileModal.php'; ?>
<?php //include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php include_once '../includes/modals/viewUserModal.php'; ?>
<?php //include_once '../includes/modals/security_policy_modal.php'; ?>
<?php include_once '../includes/modals/email_configuration_modal.php'; ?>
<?php include_once '../includes/modals/backup_configuration_modal.php'; ?>

<script src="../assets/js/import_global_pto_modal.js?v=<?php echo time(); ?>"></script>
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
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
