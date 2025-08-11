<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get total users
$totalUsersQuery = "SELECT COUNT(*) AS total FROM users";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsersRow = mysqli_fetch_assoc($totalUsersResult);
$totalUsers = $totalUsersRow['total'];

// Get users added in last 30 days
$newUsersQuery = "SELECT COUNT(*) AS recent FROM users WHERE created >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$newUsersResult = mysqli_query($conn, $newUsersQuery);
$newUsersRow = mysqli_fetch_assoc($newUsersResult);
$newUsers = $newUsersRow['recent'];


$sql = "SELECT id, first_name, last_name, email, role, status, last_active 
        FROM users 
        ORDER BY last_active DESC";
$result = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css">
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
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-sub">+<?php echo $newUsers; ?> this month</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stat-title">Active Projects</div>
                    <div class="stat-value">12</div>
                    <div class="stat-sub">16 completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-title">System Utilization</div>
                    <div class="stat-value">87%</div>
                    <div class="util-bar mt-2"><div class="util-bar-fill" style="width:87%"></div></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card overlay-red">
                    <div class="card-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="stat-title">Total Hours</div>
                    <div class="stat-value">4,580</div>
                    <div class="stat-sub">All time logged</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="custom-tabs">
            <button class="overlay-red" class="active" data-tab="users">User Management</button>
            <button class="overlay-red" data-tab="activity">System Activity</button>
            <button class="overlay-red" data-tab="analytics">Analytics</button>
            <button class="overlay-red" data-tab="settings">Settings</button>
        </div>

        <!-- Tab Content -->
        <div id="tab-users" class="tab-content">
            <div class="user-management-header">
                <div class="titles">
                    <p class="text-black"><strong>User Management</strong></p>
                    <p>Manage user accounts, roles, and permissions</p>
                </div>
                <div class="user-management-buttons">
                    <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                        <i class="bi bi-upload me-3"></i>Import Users
                    </a>
                    <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" style="font-size: .875rem; background-color: rgb(3,2,18);">
                        <i class="bi bi-person-plus me-3"></i>Add User
                    </a>
                </div>
            </div>

            <div class="user-table">
                <table id="user-table" class="table table-hover mb-0">
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
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                <td>
                                    <span class="badge-role">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo strtolower($row['status']) === 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date("n/j/Y", strtotime($row['last_active'])); ?>
                                </td>
                                <td class="table-actions">
                                    <i class="bi bi-eye"></i>
                                    <i class="bi bi-pencil"></i>
                                    <i class="bi bi-trash"></i>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No users found</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
                    
            <!-- Pagination Controls -->
            <nav>
                <ul id="pagination" class="pagination justify-content-center mt-3"></ul>
            </nav>
        </div>

        <div id="tab-activity" class="tab-content d-none">
            <div class="activity-header mb-3">
                <div class="titles">
                    <p class="text-black"><strong>System Activity Log</strong></p>
                    <p>Recent system events and user activities</p>
                </div>
            </div>

            <div id="activity-list">
                <div class="activity-card justify-content-between">
                    <div class="activity-icon-container">
                        <div class="activity-icon" style="color:rgb(92,141,253); font-size: 16px; margin-top: -45px; margin-left: 15px !important;">
                            <i class="bi bi-shield"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <div class="activity-title fw-semibold mb-1">User Login</div>
                        <div class="activity-sub text-muted mb-1" style="font-size: 0.8rem;">By: Sarah Senior</div>
                        <div class="activity-sub text-black" style="font-size: 0.9rem;">Successful login from 192.168.1.100</div>
                    </div>
                    <div class="text-muted small flex-shrink-0" style="min-width: 130px; text-align: right;">1/7/2025, 2:30:00 PM</div>
                </div>

                <div class="activity-card d-flex justify-content-between">
                    <div class="activity-icon-container">
                        <div class="activity-icon" style="color:rgb(79,198,96); font-size: 16px; margin-top: -45px; margin-left: 15px !important;">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <div class="activity-title fw-semibold mb-1">Project Created</div>
                        <div class="activity-sub text-muted mb-1" style="font-size: 0.8rem;">By: John Manager</div>
                        <div class="activity-sub text-black" style="font-size: 0.9rem;">Created project: ABC Corp Q1 Audit</div>
                    </div>
                    <div class="text-muted small flex-shrink-0" style="min-width: 130px; text-align: right;">1/7/2025, 2:30:00 PM</div>
                </div>

                <div class="activity-card d-flex justify-content-between">
                    <div class="activity-icon-container">
                        <div class="activity-icon" style="color:rgb(161,77,253); font-size: 16px; margin-top: -45px; margin-left: 15px !important;">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <div class="activity-title fw-semibold mb-1">User Role Updated</div>
                        <div class="activity-sub text-muted mb-1" style="font-size: 0.8rem;">By: Admin</div>
                        <div class="activity-sub text-black" style="font-size: 0.9rem;">Changed Mike Staff role from Staff to Senior</div>
                    </div>
                    <div class="text-muted small flex-shrink-0" style="min-width: 130px; text-align: right;">1/7/2025, 2:30:00 PM</div>
                </div>

                <div class="activity-card d-flex justify-content-between">
                    <div class="activity-icon-container">
                        <div class="activity-icon" style="color:rgb(243,132,48); font-size: 16px; margin-top: -45px; margin-left: 15px !important;">
                            <i class="bi bi-database"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <div class="activity-title fw-semibold mb-1">System Backup</div>
                        <div class="activity-sub text-muted mb-1" style="font-size: 0.8rem;">By: System</div>
                        <div class="activity-sub text-black" style="font-size: 0.9rem;">Automated daily backup completed successfully</div>
                    </div>
                    <div class="text-muted small flex-shrink-0" style="min-width: 130px; text-align: right;">1/7/2025, 2:30:00 PM</div>
                </div>

                <!-- Add more activity cards as needed -->
            </div>

            <!-- Pagination Controls for activity -->
            <nav>
                <ul id="activity-pagination" class="pagination justify-content-center mt-3"></ul>
            </nav>
        </div>

        <div id="tab-analytics" class="tab-content d-none">
            <div class="row g-3 ps-3 pe-3 mt-1">
                <div class="col-md-6">
                    <div class="analytic-card">
                        <div class="analytic-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>User Activity Overview</strong></p>
                                <p style="font-size: 14px;">Active vs Inactive users</p>
                            </div>
                        </div>
                        <div class="pb-4"></div>
                        <div class="d-flex justify-content-between pb-1">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                Active Users
                            </div>
                            <div class="float-end">
                                38
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-x-circle text-danger"></i>
                                Inactive Users
                            </div>
                            <div class="float-end">
                                7
                            </div>
                        </div>
                        <div class="analytic-util-bar mt-3"><div class="analytic-util-bar-fill" style="width:85%"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="analytic-card">
                        <div class="analytic-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>Engagement Status</strong></p>
                                <p style="font-size: 14px;">Current engagement distribution</p>
                            </div>
                        </div>
                        <div class="pb-4"></div>
                        <div class="d-flex justify-content-between pb-1">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-clipboard-check text-success"></i>
                                Assigned Engagements
                            </div>
                            <div class="float-end">
                                92
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="float-start" style="font-size: 14px;">
                                <i class="bi bi-clipboard-x text-danger"></i>
                                Unassigned Engagements
                            </div>
                            <div class="float-end">
                                8
                            </div>
                        </div>
                        <div class="analytic-util-bar mt-3"><div class="analytic-util-bar-fill" style="width:92%"></div></div>
                    </div>
                </div>
            </div>
            <div class="row g-3 ps-3 pe-3 mt-2">
                <div class="col-md-12">
                    <div class="analytic-card" style="height: 215px !important;">
                        <div class="user-management-header">
                        <div class="titles">
                            <p class="text-black" style="font-size: 14px;"><strong>Advanced Reports</strong></p>
                            <p style="font-size: 14px;">Generate detailed system reports</p>
                        </div>
                        <div class="user-management-buttons">
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" style="font-size: 14px; border: 1px solid rgb(229,229,229);">
                                <i class="bi bi-download me-3"></i>Export All Data
                            </a>
                        </div>
                    </div>
                        <div class="d-flex justify-content-between pb-2">
                            <div class="reports-card">
                                <i class="bi bi-graph-up-arrow"></i>
                                <div class="analytic-title mt-2 fw-semibold">Utilization Report</div>
                                <div class="analytic-subtitle">Staff and engagement utilization</div>
                            </div>

                            <div class="reports-card">
                                <i class="bi bi-people"></i>
                                <div class="analytic-title mt-2 fw-semibold">User Activity Report</div>
                                <div class="analytic-subtitle">Login and engagement metrics</div>
                            </div>

                            <div class="reports-card">
                                <i class="bi bi-clock"></i>
                                <div class="analytic-title mt-2 fw-semibold">Time Tracking Report</div>
                                <div class="analytic-subtitle">Hours and productivity analysis</div>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div id="tab-settings" class="tab-content d-none">
            <div class="row g-3 ps-3 pe-3 mt-1">
                <div class="col-md-6">
                    <div class="settings-card">
                        <div class="analytic-header mb-4">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>System Configuration</strong></p>
                                <p style="font-size: 14px;">Manage system-wide settings</p>
                            </div>
                        </div>

                        <!-- Email Notifications -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Email Notifications</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Send system notifications via email</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>

                        <!-- Backup Settings -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Backup Settings</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Automated data backup configuration</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>

                        <!-- Security Policies -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-black mb-0" style="font-size: 14px;"><strong>Security Policies</strong></p>
                                <p class="mb-0" style="font-size: 14px;">Password and access requirements</p>
                            </div>
                            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                Configure
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="settings-card d-flex flex-column h-100">
                        <div class="settings-header">
                            <div class="titles">
                                <p class="text-black" style="font-size: 14px;"><strong>Engagement Status</strong></p>
                                <p style="font-size: 14px;">Current engagement distribution</p>
                            </div>
                        </div>

                        <div class="mb-4"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                Database Status
                            </div>
                            <span class="badge text-bg-success pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(226,251,232) !important; color: rgba(64, 109, 72, 1) !important;">
                                Healthy
                            </span>
                        </div>

                        <div class="mb-2"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-check2-circle text-success"></i>
                                API Status
                            </div>
                            <span class="badge text-bg-success pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(226,251,232) !important; color: rgba(64, 109, 72, 1) !important;">
                                Operational
                            </span>
                        </div>

                        <div class="mb-2"></div>

                        <div class="d-flex justify-content-between pb-1">
                            <div style="font-size: 14px;">
                                <i class="bi bi-exclamation-circle text-warning"></i>
                                Storage Usage
                            </div>
                            <span class="badge pe-3 ps-3" 
                                  style="font-size: 11px !important; background-color: rgb(253,249,200) !important; color: rgba(135,88,30) !important;">
                                75% Used
                            </span>
                        </div>

                        <!-- Spacer pushes button to bottom -->
                        <div class="flex-grow-1"></div>

                        <a href="#" class="badge text-black p-2 text-decoration-none fw-medium w-100 mt-3"
                               style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
                                <i class="bi bi-activity pe-3"></i>View Detailed Metrics
                            </a>
                    </div>
                </div>

            </div>
            
        
        </div>
    </div>
</div>

<script>
  // Helper: create pagination controls (Prev, pages, Next)
  function createPaginationControls(totalPages, currentPage, onPageChange) {
    const ul = document.createElement('ul');
    ul.className = 'pagination justify-content-center';

    function createPageItem(label, disabled, active, clickHandler) {
      const li = document.createElement('li');
      li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.innerText = label;
      if (!disabled) {
        a.addEventListener('click', e => {
          e.preventDefault();
          clickHandler();
        });
      }
      li.appendChild(a);
      return li;
    }

    // Prev button
    ul.appendChild(createPageItem('Prev', currentPage === 1, false, () => onPageChange(currentPage - 1)));

    // Page number buttons
    for (let i = 1; i <= totalPages; i++) {
      ul.appendChild(createPageItem(i, false, i === currentPage, () => onPageChange(i)));
    }

    // Next button
    ul.appendChild(createPageItem('Next', currentPage === totalPages, false, () => onPageChange(currentPage + 1)));

    return ul;
  }

  // User Management pagination (5 rows per page)
  function initUserPagination() {
    const rowsPerPage = 5;
    const table = document.getElementById('user-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const paginationContainer = document.getElementById('pagination');

    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / rowsPerPage);

    function renderTablePage(page) {
      currentPage = page;
      rows.forEach(row => (row.style.display = 'none'));
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      rows.slice(start, end).forEach(row => (row.style.display = ''));
      renderPagination();
    }

    function renderPagination() {
      paginationContainer.innerHTML = '';
      if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
      }
      paginationContainer.style.display = 'flex';

      const paginationControls = createPaginationControls(totalPages, currentPage, page => {
        renderTablePage(page);
      });
      paginationContainer.appendChild(paginationControls);
    }

    renderTablePage(1);
  }

  // System Activity pagination (3 cards per page)
  function initActivityPagination() {
  const cardsPerPage = 3;
  const activityList = document.getElementById('activity-list');
  if (!activityList) return;
  const cards = Array.from(activityList.querySelectorAll('.activity-card'));
  const paginationContainer = document.getElementById('activity-pagination');

  let currentPage = 1;
  const totalPages = Math.ceil(cards.length / cardsPerPage);

  function showPage(page) {
    currentPage = page;
    cards.forEach(card => {
      card.style.display = 'none';
      card.classList.remove('d-flex');
    });
    const start = (page - 1) * cardsPerPage;
    const end = start + cardsPerPage;
    for (let i = start; i < end && i < cards.length; i++) {
      cards[i].style.display = '';
      cards[i].classList.add('d-flex');
    }
    renderPagination();
  }

  function renderPagination() {
    paginationContainer.innerHTML = '';
    if (totalPages <= 1) {
      paginationContainer.style.display = 'none';
      return;
    }
    paginationContainer.style.display = 'flex';

    const paginationControls = createPaginationControls(totalPages, currentPage, page => {
      showPage(page);
    });
    paginationContainer.appendChild(paginationControls);
  }

  showPage(1);
}


  // Tab switching + reset pagination
  document.querySelectorAll('.custom-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.custom-tabs button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('d-none'));
      const currentTab = document.getElementById('tab-' + btn.dataset.tab);
      currentTab.classList.remove('d-none');

      if (btn.dataset.tab === 'users') {
        document.dispatchEvent(new Event('reinitUserPagination'));
      } else if (btn.dataset.tab === 'activity') {
        document.dispatchEvent(new Event('reinitActivityPagination'));
      }
    });
  });

  // Initialize paginations on DOM ready
  document.addEventListener('DOMContentLoaded', () => {
    initUserPagination();
    initActivityPagination();
  });

  // Reinit paginations on tab switch
  document.addEventListener('reinitUserPagination', initUserPagination);
  document.addEventListener('reinitActivityPagination', initActivityPagination);
</script>


</body>
</html>
