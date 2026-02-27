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

// Fetch settings
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
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <style>
        .settings-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .settings-card {
            flex: 1 1 22%;
            max-width: 350px;
            max-height: 150px;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        .settings-card:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        .card-header {
    display: flex;
    align-items: center;
    justify-content: center; /* centers both icon + text horizontally */
    gap: 0.4rem;             /* small space between icon and heading */
    margin-bottom: 0.5rem;
}

        .card-header i {
            font-size: 1.6rem;
            color: #495057;
        }

        .card-title {
            font-weight: 600;
            font-size: 1rem;
        }

        .card-desc {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .section-title {
            margin-top: 2rem;
            margin-bottom: 1rem;
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