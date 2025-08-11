<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect non-admins to dashboard.php
// if (!isset($_SESSION['role']) || strtolower($_SESSION['user_role']) === 'admin') {
//     header("Location: dashboard.php");
//     exit();
// }
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
            color: #1a1a1a;
        }
        /* Stat cards */
        .stat-card {
            position: relative;
            height: 140px;
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

        /* Tab content wrapper */
        .tab-content {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
        }

        /* User Management Header & sub-header + buttons */
        .user-management-header, 
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 10px;
        }
        .user-management-header .titles, .activity-header .titles {
            flex-grow: 1;
            min-width: 200px;
        }
        .user-management-header h2, .activity-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.75rem;
        }
        .user-management-header p, .activity-header p {
            margin: 0;
            color: #6b7280;
            font-size: 1rem;
        }
        .user-management-buttons button,
        .user-management-buttons a {
            margin-left: 10px;
            min-width: 120px;
            font-weight: 600;
        }

        /* Vertically center all table cells */
        .user-table table tbody tr td {
            vertical-align: middle !important;
        }

        /* Role badge style */
        .badge-role {
            background-color: white;
            border: 1px solid #ddd;
            color: #333;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        /* Status badge */
        .badge-status {
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }
        .badge-status.active {
            background-color: rgb(226,251,232);
            color: rgb(50,107,61);
        }
        .badge-status.inactive {
            background-color: rgb(253, 249, 200);
            color: rgb(131,82,23);
        }

        /* Table actions icons */
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

        /* Fix for activity icon container */
        .activity-icon-container {
            flex-shrink: 0;
            width: 48px;
            display: flex;
            flex-direction: column;
            align-items: start;
            margin-top: 0;
            margin-right: 0;
        }

        /* Disable pointer events on disabled pagination */
        .page-item.disabled > .page-link {
            pointer-events: none;
            cursor: default;
        }

        .analytic-card {
            position: relative;
            height: 185px;
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e5e5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .analytic-util-bar {
            height: 7px;
            border-radius: 4px;
            background: #e0e0e0;
            overflow: hidden;
        }
        .analytic-util-bar-fill {
            background: #111;
            height: 100%;
        }
        .analytic-subtitle {
            font-size: 0.8rem;
            color: #555;
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
                        <!-- Extra rows for pagination -->
                        <tr>
                            <td>Extra User 1<br><small class="text-muted">extra1@company.com</small></td>
                            <td><span class="badge-role">Staff</span></td>
                            <td><span class="badge-status active">Active</span></td>
                            <td>1/3/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
                        <tr>
                            <td>Extra User 2<br><small class="text-muted">extra2@company.com</small></td>
                            <td><span class="badge-role">Manager</span></td>
                            <td><span class="badge-status inactive">Inactive</span></td>
                            <td>1/2/2025</td>
                            <td class="table-actions"><i class="bi bi-eye"></i><i class="bi bi-pencil"></i><i class="bi bi-trash"></i></td>
                        </tr>
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
            <div class="row g-3 ps-3 pe-3">
                <div class="col-md-6">
                    <div class="analytic-card">
                        <div class="analytic-title">User Activity Overview</div>
                        <div class="analytic-subtitle">Active vs Inactive users</div>
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
                    <div class="stat-card">
                        <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="stat-title">Active Projects</div>
                        <div class="stat-value">12</div>
                        <div class="stat-sub">16 completed</div>
                    </div>
                </div>   
            </div>
        </div>



        <div id="tab-settings" class="tab-content d-none">
            <p>Settings content placeholder</p>
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
