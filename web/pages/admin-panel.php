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

  /* Top cards */
  .stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e5e5e5;
  }
  .stat-title {
    font-size: 0.9rem;
    color: #555;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .stat-value {
    font-size: 1.75rem;
    font-weight: 600;
    margin: 0.25rem 0 0;
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

  /* Tabs */
  .custom-tabs {
    display: flex;
    border-radius: 8px;
    background: #f5f5f5;
    padding: 4px;
    gap: 4px;
    margin: 1.5rem 0;
  }
  .custom-tabs button {
    flex: 1;
    border: none;
    background: transparent;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.9rem;
    color: #444;
  }
  .custom-tabs button.active {
    background: #fff;
    color: #000;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  }

  /* Table */
  .user-table {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    overflow: hidden;
  }
  .user-table th {
    font-size: 0.85rem;
    font-weight: 500;
    color: #555;
    background: #fff;
    padding: 1rem;
  }
  .user-table td {
    padding: 1rem;
    font-size: 0.9rem;
    vertical-align: middle;
    border-top: 1px solid #f0f0f0;
  }
  .badge-role {
    font-size: 0.75rem;
    background: #f5f5f5;
    border-radius: 999px;
    padding: 0.3rem 0.75rem;
  }
  .badge-status.active {
    background: #d1fae5;
    color: #047857;
    border-radius: 999px;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem;
  }
  .badge-status.inactive {
    background: #fef3c7;
    color: #92400e;
    border-radius: 999px;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem;
  }
  .table-actions i {
    cursor: pointer;
    margin-right: 8px;
    color: #555;
  }

  /* Activity log */
  .activity-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
  }
  .activity-info {
    display: flex;
    gap: 0.75rem;
  }
  .activity-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
  }
  .activity-title {
    font-weight: 600;
    font-size: 0.9rem;
  }
  .activity-sub {
    font-size: 0.8rem;
    color: #666;
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
