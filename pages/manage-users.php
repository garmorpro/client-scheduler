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

// Pagination setup (example: 10 per page)
$perPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start = ($page - 1) * $perPage;
$usersToShow = array_slice($users, $start, $perPage);
$lastPage = ceil($totalUsers / $perPage);
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
            flex-wrap: wrap;
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
        <div class="right d-flex align-items-center flex-wrap gap-2">
            <input type="text" class="form-control form-control-sm" placeholder="Search users">
            <button class="btn btn-outline-secondary btn-sm">Filter</button>
            <button class="btn btn-outline-primary btn-sm">Import</button>
            <button class="btn btn-primary btn-sm">Invite</button>
        </div>
    </div>

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
            <tbody>
                <?php foreach ($usersToShow as $user): ?>
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
        <div class="pagination-info">
            Showing <?= $start + 1 ?>–<?= min($start + $perPage, $totalUsers) ?> of <?= $totalUsers ?>
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                <li class="page-item <?= $page == $p ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>