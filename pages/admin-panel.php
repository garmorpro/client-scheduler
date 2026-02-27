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

// Fetch users and engagements data (your existing queries)
$totalUsersQuery = "SELECT COUNT(*) AS total FROM users";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'];

$newUsersQuery = "SELECT COUNT(*) AS recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$newUsersResult = mysqli_query($conn, $newUsersQuery);
$newUsers = mysqli_fetch_assoc($newUsersResult)['recent'];

$totalActiveUsersQuery = "SELECT COUNT(*) AS total FROM users WHERE status='active'";
$totalActiveUsersResult = mysqli_query($conn, $totalActiveUsersQuery);
$totalActiveUsers = mysqli_fetch_assoc($totalActiveUsersResult)['total'];

$totalInactiveUsersQuery = "SELECT COUNT(*) AS total FROM users WHERE status='inactive'";
$totalInactiveUsersResult = mysqli_query($conn, $totalInactiveUsersQuery);
$totalInactiveUsers = mysqli_fetch_assoc($totalInactiveUsersResult)['total'];

// Engagement stats
$totalConfirmedEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status='confirmed'";
$totalConfirmedEngagements = mysqli_fetch_assoc(mysqli_query($conn, $totalConfirmedEngagementsQuery))['total'];

$totalPendingEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status='pending'";
$totalPendingEngagements = mysqli_fetch_assoc(mysqli_query($conn, $totalPendingEngagementsQuery))['total'];

$totalNotConfirmedEngagementsQuery = "SELECT COUNT(*) AS total FROM engagements WHERE status='not_confirmed'";
$totalNotConfirmedEngagements = mysqli_fetch_assoc(mysqli_query($conn, $totalNotConfirmedEngagementsQuery))['total'];

$totalEngagements = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM engagements"))['total'];

$totalAssigned = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT e.engagement_id) AS total_assigned
    FROM engagements e
    JOIN entries a ON e.engagement_id = a.engagement_id
"))['total_assigned'];

$totalNotAssigned = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_not_assigned
    FROM engagements e
    LEFT JOIN entries a ON e.engagement_id = a.engagement_id
    WHERE a.engagement_id IS NULL
"))['total_not_assigned'];

$usersql = "SELECT user_id, full_name, email, role, status, theme_mode last_active FROM users ORDER BY full_name ASC";
$userresult = mysqli_query($conn, $usersql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* Dark mode body */
body.dark-mode {
    background-color: #12122b;
    color: #e0e0e0;
}

/* Card styling */
.stat-card, .settings-card, .analytic-card {
    background-color: #1f1f2f;
    border-radius: 12px;
    padding: 20px;
    color: #e0e0e0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    transition: transform 0.2s ease;
}
.stat-card:hover, .settings-card:hover, .analytic-card:hover {
    transform: translateY(-3px);
}

/* Stat card inner */
.stat-card .card-icon {
    font-size: 28px;
    margin-bottom: 12px;
    color: #6c63ff;
}
.stat-card .stat-title { font-weight: 500; font-size: 14px; }
.stat-card .stat-value { font-size: 24px; font-weight: 600; }
.stat-card .stat-sub { font-size: 12px; color: #9a9a9a; }
.util-bar { height: 6px; background: #2a2a3f; border-radius: 3px; overflow: hidden; }
.util-bar-fill { height: 6px; background: #6c63ff; }

/* Badge styling */
.badge-status.active { background-color: #28a745; color: #fff; }
.badge-status.inactive { background-color: #dc3545; color: #fff; }
.badge-role { background-color: #6c63ff; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 12px; }

/* Employee table hover */
#user-table tbody tr:hover { background-color: #24243b; }
#user-table th { background-color: #1c1c2a; color: #ccc; }

/* Tabs */
.custom-tabs a { margin-right: 16px; padding: 6px 12px; border-radius: 6px; color: #ccc; cursor: pointer; }
.custom-tabs a.active { background-color: #6c63ff; color: #fff; }

/* Activity log card */
.activity-card {
    background-color: #1f1f2f;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 12px;
    display: flex;
    gap: 12px;
    align-items: center;
}
.activity-icon { font-size: 20px; }
</style>
</head>
<body class="<?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<div class="container-fluid p-4">
    <h3>Administrative Dashboard</h3>
    <p class="text-muted">System overview and user management for Admin User</p>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="card-icon"><i class="bi bi-people"></i></div>
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?= $totalUsers ?></div>
                <div class="stat-sub">+<?= $newUsers ?> this month</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="card-icon"><i class="bi bi-person-up"></i></div>
                <div class="stat-title">Active Users</div>
                <div class="stat-value"><?= $totalActiveUsers ?></div>
                <div class="stat-sub"><?= $totalInactiveUsers ?> inactive</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-title">Confirmed Engagements</div>
                <div class="stat-value"><?= $totalConfirmedEngagements ?></div>
                <div class="stat-sub"><?= $totalPendingEngagements ?> pending <i class="bi bi-dot"></i> <?= $totalNotConfirmedEngagements ?> not confirmed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card text-center">
                <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stat-title">Engagement Status</div>
                <?php
                    $percentageAssigned = $totalEngagements > 0 ? round(($totalAssigned/$totalEngagements)*100) : 0;
                ?>
                <div class="stat-value"><?= $percentageAssigned ?>%</div>
                <div class="util-bar mt-2">
                    <div class="util-bar-fill" style="width: <?= $percentageAssigned ?>%"></div>
                </div>
                <div class="stat-sub mt-2"><?= $totalAssigned ?> assigned <i class="bi bi-dot"></i> <?= $totalNotAssigned ?> not assigned</div>
            </div>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="tab-content mb-4">
        <div class="user-management-header d-flex justify-content-between align-items-center mb-2">
            <div>
                <strong>Employee Management</strong><br>
                <small>Manage employee accounts, roles, and permissions</small>
            </div>
            <div class="ms-3" style="flex:1; max-width:400px;">
                <input type="text" class="form-control form-control-sm" placeholder="Search employees..." minlength="3">
            </div>
        </div>

        <table id="user-table" class="table table-hover text-light">
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>Employee Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($userrow = mysqli_fetch_assoc($userresult)): ?>
                <tr>
                    <td><input type="checkbox"></td>
                    <td><?= htmlspecialchars($userrow['full_name']) ?><br><small class="text-muted"><?= htmlspecialchars($userrow['email']) ?></small></td>
                    <td><span class="badge-role"><?= ucfirst($userrow['role']) ?></span></td>
                    <td><span class="badge-status <?= strtolower($userrow['status']) ?>"><?= ucfirst($userrow['status']) ?></span></td>
                    <td><?= empty($userrow['last_active']) ? 'Never' : date("n/j/Y", strtotime($userrow['last_active'])) ?></td>
                    <td>
                        <a href="#" class="text-success me-2"><i class="bi bi-eye"></i></a>
                        <a href="#" class="text-primary me-2"><i class="bi bi-person-up"></i></a>
                        <a href="#" class="text-danger"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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
