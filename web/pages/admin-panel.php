<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #fafafa;
            font-family: 'Inter', sans-serif;
            color: #1a1a1a;
        }
        /* Stat cards */
        .stat-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e5e5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .card-icon {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f6f6f7;
            color: #111;
            font-size: 1.05rem;
        }
        .stat-title {
            font-size: 0.9rem;
            color: #555;
        }
        .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0.25rem 0;
        }
        .stat-sub {
            font-size: 0.8rem;
            color: #888;
        }
        .util-bar {
            height: 5px;
            border-radius: 4px;
            background: #e0e0e0;
            overflow: hidden;
        }
        .util-bar-fill {
            background: #111;
            height: 100%;
        }

        .custom-tabs {
            display: flex;
            margin-top: 2rem;
            margin-bottom: 20px;
            background-color: rgb(235,235,239);
            border-radius: 15px;
            padding: 5px;
            justify-content: center;
        }

        .custom-tabs button {
            background: none;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;

            flex: 1;
            text-align: center;
            margin: 0 4px;
        }

        .custom-tabs button:hover {
            background: #ffffff95;
            color: black;
        }

        .custom-tabs button.active {
            background: #ffffff;
            color: black;
        }

        /* Tab content wrapper with background and border radius */
        .tab-content {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
        }

        /* User Management Header & sub-header + buttons */
        .user-management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 10px;
        }
        .user-management-header .titles {
            flex-grow: 1;
            min-width: 200px;
        }
        .user-management-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.75rem;
        }
        .user-management-header p {
            margin: 0;
            color: #6b7280;
            font-size: 1rem;
        }
        .user-management-buttons button {
            margin-left: 10px;
            min-width: 120px;
            font-weight: 600;
        }

        /* Table */
        .user-table .table {
            background: #fff;
            border-radius: 15px !important;
            border: 1px solid #e5e7eb;
        }
        .user-table th {
            background: #f9fafb;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
        }
        .user-table td {
            vertical-align: middle;
            font-size: 14px;
        }
        .badge-role {
            background: #f3f4f6;
            color: #374151;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-status.active {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-status.inactive {
            background: #fef3c7;
            color: #b45309;
        }
        .table-actions i {
            margin: 0 6px;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.2s;
        }
        .table-actions i:hover {
            color: #111827;
        }
        /* Activity Cards */
        .activity-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-info {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .activity-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
        }
        .activity-sub {
            font-size: 13px;
            color: #6b7280;
        }
    </style>
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
                    <div class="stat-value">45</div>
                    <div class="stat-sub">+7 this month</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stat-title">Active Projects</div>
                    <div class="stat-value">12</div>
                    <div class="stat-sub">16 completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-title">System Utilization</div>
                    <div class="stat-value">87%</div>
                    <div class="util-bar mt-2"><div class="util-bar-fill" style="width:87%"></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
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
            <button data-tab="analytics">Analytics</button>
            <button data-tab="settings">Settings</button>
        </div>

        <!-- Tab Content -->
        <div id="tab-users" class="tab-content">
            <div class="user-management-header">
                <div class="titles">
                    <p class="text-black"><strong>User Management</strong></p>
                    <p>Manage user accounts, roles, and permissions</p>
                </div>
                <div class="user-management-buttons">
                    <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);"><i class="bi bi-upload me-3"></i>Import Users</a>
                    <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);"><i class="bi bi-person-plus me-3"></i>Add User</a>
                </div>
            </div>

            <div class="user-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>User</th><th>Role</th><th>Status</th><th>Last Active</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>John Manager<br><small class="text-muted">john.manager@company.com</small></td>
                            <td><span class="badge-role">Manager</span></td>
                            <td><span class="badge-status active">Active</span></td>
                            <td>1/6/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
                        <tr>
                            <td>Sarah Senior<br><small class="text-muted">sarah.senior@company.com</small></td>
                            <td><span class="badge-role">Senior</span></td>
                            <td><span class="badge-status active">Active</span></td>
                            <td>1/6/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
                        <tr>
                            <td>Mike Staff<br><small class="text-muted">mike.staff@company.com</small></td>
                            <td><span class="badge-role">Staff</span></td>
                            <td><span class="badge-status active">Active</span></td>
                            <td>1/5/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
                        <tr>
                            <td>Lisa CRM<br><small class="text-muted">lisa.crm@company.com</small></td>
                            <td><span class="badge-role">CRM Member</span></td>
                            <td><span class="badge-status inactive">Inactive</span></td>
                            <td>1/4/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-activity" class="tab-content d-none">
            <div class="activity-card">
                <div class="activity-info">
                    <div class="activity-icon" style="background:#e0f2fe;">🔑</div>
                    <div>
                        <div class="activity-title">User Login</div>
                        <div class="activity-sub">By: Sarah Senior — Successful login from 192.168.1.100</div>
                    </div>
                </div>
                <div class="text-muted small">1/7/2025, 2:30 PM</div>
            </div>
            <div class="activity-card">
                <div class="activity-info">
                    <div class="activity-icon" style="background:#dcfce7;">📁</div>
                    <div>
                        <div class="activity-title">Project Created</div>
                        <div class="activity-sub">By: John Manager — Created project: ABC Corp Q1 Audit</div>
                    </div>
                </div>
                <div class="text-muted small">1/7/2025, 1:15 PM</div>
            </div>
        </div>

        <div id="tab-analytics" class="tab-content d-none">
            <div class="p-4 bg-white border rounded">Analytics content here...</div>
        </div>

        <div id="tab-settings" class="tab-content d-none">
            <div class="p-4 bg-white border rounded">Settings content here...</div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.custom-tabs button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.custom-tabs button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('d-none'));
            document.getElementById('tab-' + btn.dataset.tab).classList.remove('d-none');
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
