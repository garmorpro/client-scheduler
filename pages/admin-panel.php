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

// Fetch settings and data as in your original code
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
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .settings-card {
            flex: 1 1 22%;
            min-width: 180px;
            padding: 1.5rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .settings-card:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }
        .settings-card i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #495057;
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
                <i class="bi bi-people"></i>
                <div>Manage Users</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-shield-lock"></i>
                <div>Role Permissions</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-bell"></i>
                <div>Notifications</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-gear"></i>
                <div>System Preferences</div>
            </div>
        </div>

        <!-- SCHEDULER SETTINGS SECTION -->
        <h5 class="section-title">Scheduler Settings</h5>
        <div class="settings-row">
            <div class="settings-card">
                <i class="bi bi-calendar-event"></i>
                <div>Company Holidays</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-file-earmark-text"></i>
                <div>Reports</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-clock"></i>
                <div>Time Off Rules</div>
            </div>
            <div class="settings-card">
                <i class="bi bi-calendar-check"></i>
                <div>Default Schedules</div>
            </div>
        </div>

    </div> <!-- container -->
</div> <!-- flex-grow -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>