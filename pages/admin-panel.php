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
    <title>Settings</title>
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

<div class="header-text flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Settings</h3>
    <p class="text-muted mb-4">Configure system and scheduler options</p>

    <div class="container-fluid">

        <!-- SYSTEM SECTION -->
        <h5 class="section-title">System</h5>
        <div class="settings-row">
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-people"></i>
                    <div class="card-title">Manage Users</div>
                </div>
                <div class="card-desc">Add, edit, or remove system users and manage their profiles.</div>
            </div>
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-shield-lock"></i>
                    <div class="card-title">Role Permissions</div>
                </div>
                <div class="card-desc">Define what each role can access and modify within the system.</div>
            </div>
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-bell"></i>
                    <div class="card-title">Notifications</div>
                </div>
                <div class="card-desc">Configure email or in-app alerts for important events and updates.</div>
            </div>
            <!-- <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-gear"></i>
                    <div class="card-title">System Preferences</div>
                </div>
                <div class="card-desc">Adjust general settings like time zone, theme, and default behaviors.</div>
            </div> -->
        </div>

        <!-- SCHEDULER SETTINGS SECTION -->
        <h5 class="section-title">Scheduler Settings</h5>
        <div class="settings-row">
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-calendar-event"></i>
                    <div class="card-title">Company Holidays</div>
                </div>
                <div class="card-desc">Set official holidays and days off for the entire organization.</div>
            </div>
            <div class="settings-card">
                <div class="card-header">
                    <i class="bi bi-file-earmark-text"></i>
                    <div class="card-title">Reports</div>
                </div>
                <div class="card-desc">Generate and download scheduling, attendance, and engagement reports.</div>
            </div>
            
        </div>

    </div> <!-- container -->
</div> <!-- flex-grow -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>