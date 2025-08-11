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
    .stat-card {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 1.5rem;
      background: #fff;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .stat-title {
      font-size: 0.9rem;
      color: #6c757d;
    }
    .stat-value {
      font-size: 1.5rem;
      font-weight: bold;
    }
    .tab-content {
      border: 1px solid #e0e0e0;
      border-top: none;
      border-radius: 0 0 12px 12px;
      background: #fff;
      padding: 1.5rem;
    }
    .nav-tabs .nav-link {
      border: none;
      border-bottom: 2px solid transparent;
      font-weight: 500;
      color: #6c757d;
    }
    .nav-tabs .nav-link.active {
      border-bottom: 2px solid #000;
      color: #000;
    }
    .status-badge {
      padding: 0.35em 0.65em;
      font-size: 0.75rem;
      border-radius: 0.5rem;
    }
    .status-active {
      background-color: #d1fae5;
      color: #065f46;
    }
    .status-inactive {
      background-color: #fef3c7;
      color: #92400e;
    }
    .activity-item {
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1rem;
      background: #fff;
    }
    .activity-icon {
      font-size: 1.2rem;
      margin-right: 0.5rem;
    }
  </style>

</head>
<body class="d-flex">

  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h3 class="mb-0">Administrative Dashboard</h3>
    <p class="text-muted mb-4">System overview and user management for Admin User</p>

    
    <div class="container">
  
  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-title">Total Users</div>
        <div class="stat-value">45</div>
        <small class="text-success">+7 this month</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-title">Active Projects</div>
        <div class="stat-value">12</div>
        <small class="text-muted">16 completed</small>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-title">System Utilization</div>
        <div class="stat-value">87%</div>
        <div class="progress" style="height: 6px;">
          <div class="progress-bar bg-dark" style="width: 87%"></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="stat-title">Total Hours</div>
        <div class="stat-value">4,580</div>
        <small class="text-muted">All time logged</small>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs" id="adminTabs">
    <li class="nav-item">
      <a class="nav-link active" data-bs-toggle="tab" href="#users">User Management</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#activity">System Activity</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#analytics">Analytics</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-bs-toggle="tab" href="#settings">Settings</a>
    </li>
  </ul>

  <div class="tab-content">
    <!-- User Management Tab -->
    <div class="tab-pane fade show active" id="users">
      <h6>User Management</h6>
      <p class="text-muted">Manage user accounts, roles, and permissions</p>
      <table class="table align-middle">
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
          <tr>
            <td>John Manager<br><small class="text-muted">john.manager@company.com</small></td>
            <td>Manager</td>
            <td><span class="status-badge status-active">Active</span></td>
            <td>1/6/2025</td>
            <td>
              <button class="btn btn-sm btn-outline-secondary">👁</button>
              <button class="btn btn-sm btn-outline-secondary">✏</button>
              <button class="btn btn-sm btn-outline-danger">🗑</button>
            </td>
          </tr>
          <tr>
            <td>Lisa CRM<br><small class="text-muted">lisa.crm@company.com</small></td>
            <td>CRM Member</td>
            <td><span class="status-badge status-inactive">Inactive</span></td>
            <td>1/4/2025</td>
            <td>
              <button class="btn btn-sm btn-outline-secondary">👁</button>
              <button class="btn btn-sm btn-outline-secondary">✏</button>
              <button class="btn btn-sm btn-outline-danger">🗑</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- System Activity Tab -->
    <div class="tab-pane fade" id="activity">
      <h6>System Activity Log</h6>
      <p class="text-muted">Recent system events and user activities</p>
      <div class="activity-item">
        <div><strong>👤 User Login</strong> - By: Sarah Senior</div>
        <small>Successful login from 192.168.1.100</small><br>
        <small class="text-muted">1/7/2025, 2:30 PM</small>
      </div>
      <div class="activity-item">
        <div><strong>📄 Project Created</strong> - By: John Manager</div>
        <small>Created project: ABC Corp Q1 Audit</small><br>
        <small class="text-muted">1/7/2025, 1:15 PM</small>
      </div>
    </div>

    <!-- Analytics Tab -->
    <div class="tab-pane fade" id="analytics">
      <h6>Data Analytics</h6>
      <p class="text-muted">Analytics and insights will appear here.</p>
    </div>

    <!-- Settings Tab -->
    <div class="tab-pane fade" id="settings">
      <h6>Settings</h6>
      <p class="text-muted">System configuration options go here.</p>
    </div>
  </div>

</div>
    



  </div>

</body>
</html>
