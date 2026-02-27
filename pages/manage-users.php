<?php
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

// Fetch users from database
$users = [];
$userSQL = "SELECT user_id, full_name, email, role, status, job_title, created_at, last_active 
            FROM users ORDER BY full_name ASC";
$userResult = mysqli_query($conn, $userSQL);
if ($userResult) {
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
}
$totalUsers = count($users);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <style>
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        .header-bar .left {
            font-weight: 600;
        }
        .header-bar .right .btn {
            margin-left: 0.5rem;
        }
        table th, table td {
            vertical-align: middle;
        }
        .name-cell .job-title {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .action-dropdown .dropdown-menu {
            min-width: 120px;
        }
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        #userSearch {
    width: 200px;
}
    </style>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <!-- Page Title -->
    <h3 class="mb-1">Manage Users</h3>
    <p class="text-muted mb-4">View and manage all users in the system</p>

    <!-- Header Bar -->
    <div class="header-bar">
        <div class="left"><?= $totalUsers ?> Users</div>
        <div class="right d-flex align-items-center gap-2 flex-nowrap">
            <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search users">
            <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm"
        id="roleFilterBtn"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside">
    <i class="bi bi-filter"></i>
</button>

    <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 200px;">
        
        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="admin" id="roleAdmin" checked>
            <label class="form-check-label" for="roleAdmin">Admin</label>
        </div>

        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="manager" id="roleManager" checked>
            <label class="form-check-label" for="roleManager">Manager</label>
        </div>


        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="senior" id="roleSenior" checked>
            <label class="form-check-label" for="roleSenior">Senior</label>
        </div>

        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="staff" id="roleStaff" checked>
            <label class="form-check-label" for="roleStaff">Staff</label>
        </div>

        <hr class="my-2">

        <button class="btn btn-sm btn-link p-0" id="clearRoles">Clear All</button>
    </div>
</div>
            <button class="btn btn-outline-primary btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#importUsersModal">
    Import
</button>
            <button class="btn btn-primary btn-sm">Invite User</button>
        </div>
    </div>



    <!-- import modal -->

    <div class="modal fade" id="importUsersModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="importUsersForm" method="POST" enctype="multipart/form-data">
        
        <div class="modal-header">
          <h5 class="modal-title">Import Users (CSV)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <p class="small text-muted">
            Upload a CSV file using the template format.
          </p>

          <a href="../assets/templates/bulk_import_user_template.csv" download class="btn btn-sm btn-link p-0 mb-3">
            Download CSV Template
          </a>

          <input type="file"
                 name="csv_file"
                 accept=".csv"
                 class="form-control"
                 required>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary btn-sm">
            Import Users
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


    <!-- end import modal -->







    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col"><input type="checkbox"></th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">User Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Added Date</th>
                    <th scope="col">Last Active</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><input type="checkbox" value="<?= $user['user_id'] ?>"></td>
                    <td class="name-cell">
                        <?= htmlspecialchars($user['full_name']) ?>
                        <div class="job-title"><?= htmlspecialchars($user['job_title']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= htmlspecialchars($user['status']) ?></td>
                    <td><?= date("Y-m-d", strtotime($user['created_at'])) ?></td>
                    <td><?= date("Y-m-d", strtotime($user['last_active'])) ?></td>
                    <td class="action-dropdown">
                        <div class="dropdown">
                            <a href="#" class="text-dark" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">Edit</a></li>
                                <li><a class="dropdown-item" href="#">Deactivate</a></li>
                                <li><a class="dropdown-item text-danger" href="#">Delete</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination & Info -->
    <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="pagination-info"></div>
    <nav>
        <ul id="pagination" class="pagination pagination-sm mb-0"></ul>
    </nav>
</div>

</div>


<?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
    
    <script src="../assets/js/dynamic_cell_input.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/drag_drop_function.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_custom_menu.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/timeoff_menu.js?v=<?php echo time(); ?>"></script>
    <?php if ($isAdmin): ?>
    <script src="../assets/js/employee_details.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    <script src="../assets/js/filter_role.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search_manage_users.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/pagination_manage_users.js?v=<?php echo time(); ?>"></script>

    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>

    <script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>