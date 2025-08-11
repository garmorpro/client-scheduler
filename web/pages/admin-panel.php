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

 
  
  .stat-card {
  position: relative;         /* needed for absolute icon */
  background: #fff;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e5e5e5;
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
  background: #f6f6f7;        /* subtle grey circle */
  color: #111;
  font-size: 1.05rem;
  box-shadow: none;
}

.stat-title {
  font-size: 0.9rem;
  color: #555;
  margin-bottom: 6px;
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
                <div class="card-icon">
                    <i class="bi bi-people-fill"></i>
                </div>

                <div class="stat-title">Total Users</div>
                <div class="stat-value">45</div>
                <div class="stat-sub">+7 this month</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div class="stat-title">Active Projects</div>
                <div class="stat-value">12</div>
                <div class="stat-sub">16 completed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                
                <div class="stat-title">System Utilization</div>
                <div class="stat-value">87%</div>
                <div class="util-bar mt-2">
                    <div class="util-bar-fill" style="width:87%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="card-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

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

  <!-- Tab content -->
  <div id="tab-users" class="tab-content">
    <div class="user-table">
      <table class="table mb-0">
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
