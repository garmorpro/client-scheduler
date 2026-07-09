<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!$isAdmin && !$isManager) {
    header("Location: my-schedule.php");
    exit();
}

// Fetch engagements (single query — reused for the table and every stat below)
$engagementQuery = "
    SELECT
        e.engagement_id,
        c.client_name,
        e.status,
        e.budgeted_hours,
        e.manager,
        e.notes,
        COALESCE(SUM(en.assigned_hours), 0) AS total_assigned_hours
    FROM engagements e
    JOIN clients c ON e.client_id = c.client_id
    LEFT JOIN entries en ON e.engagement_id = en.engagement_id
    GROUP BY e.engagement_id, c.client_name, e.status, e.budgeted_hours, e.manager, e.notes
    ORDER BY c.client_name ASC
";
$engagementResult = mysqli_query($conn, $engagementQuery);
$engagementRows = mysqli_fetch_all($engagementResult, MYSQLI_ASSOC);

$statusCounts = ['confirmed' => 0, 'pending' => 0, 'not_confirmed' => 0];
$totalBudgetedHours = 0;
$totalAllocatedHours = 0;
$overBudgetList = [];
$unassignedCount = 0;

foreach ($engagementRows as $row) {
    $status = strtolower($row['status']);
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }

    $budgeted = (float)$row['budgeted_hours'];
    $allocated = (float)$row['total_assigned_hours'];
    $totalBudgetedHours += $budgeted;
    $totalAllocatedHours += $allocated;

    if ($allocated > $budgeted) {
        $overBudgetList[] = [
            'client_name' => $row['client_name'],
            'budgeted_hours' => $budgeted,
            'allocated_hours' => $allocated,
            'over_hours' => $allocated - $budgeted
        ];
    }

    if ($allocated == 0) {
        $unassignedCount++;
    }
}

$totalEngagements = count($engagementRows);
$overBudgetCount = count($overBudgetList);
$utilizationPct = $totalBudgetedHours > 0 ? round(($totalAllocatedHours / $totalBudgetedHours) * 100) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Engagement Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">


    <!-- header -->
        <div class="user-management-header d-flex justify-content-between align-items-center">
            <!-- Left -->
            <div>
                <h3 class="mb-0">Engagement Management</h3>
                <p class="mb-0">Monitor all client engagements and details</p>
            </div>

            <!-- Middle (Search) -->


            <!-- Right -->
            <div class="user-management-buttons d-flex align-items-center gap-2">
                <a href="archived-engagements.php" class="badge p-2 text-decoration-none fw-medium btn-outline-custom">
                    <i class="bi bi-archive me-3"></i>View Archived
                </a>
                <a href="#" id="bulkDeleteEngagementsBtn" class="badge text-white p-2 text-decoration-none fw-medium"
                   style="font-size: .875rem; background-color: darkred; display:none;">
                  <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedEngagementCount">0</span>)
                </a>
            </div>
        </div>
    <!-- end header -->

    <!-- Stat cards -->
    <div class="eng-stat-row">
        <div class="eng-stat-card">
            <div class="eng-stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="eng-stat-title">Status Breakdown</div>
            <div class="eng-stat-value"><?php echo $totalEngagements; ?></div>
            <div class="eng-stat-breakdown">
                <span class="client-mini-pill confirmed <?php echo $statusCounts['confirmed'] == 0 ? 'zero' : ''; ?>"><?php echo $statusCounts['confirmed']; ?> Conf.</span>
                <span class="client-mini-pill pending <?php echo $statusCounts['pending'] == 0 ? 'zero' : ''; ?>"><?php echo $statusCounts['pending']; ?> Pend.</span>
                <span class="client-mini-pill not-confirmed <?php echo $statusCounts['not_confirmed'] == 0 ? 'zero' : ''; ?>"><?php echo $statusCounts['not_confirmed']; ?> N/C</span>
            </div>
        </div>

        <div class="eng-stat-card">
            <div class="eng-stat-icon"><i class="bi bi-bullseye"></i></div>
            <div class="eng-stat-title">Budget Utilization</div>
            <div class="eng-stat-value"><?php echo $utilizationPct; ?>%</div>
            <div class="eng-stat-bar"><div class="eng-stat-bar-fill" style="width: <?php echo min(100, $utilizationPct); ?>%"></div></div>
            <div class="eng-stat-sub"><?php echo number_format($totalAllocatedHours); ?> of <?php echo number_format($totalBudgetedHours); ?> hrs allocated</div>
        </div>

        <div class="eng-stat-card <?php echo $overBudgetCount > 0 ? 'clickable' : ''; ?>" id="overBudgetCard">
            <div class="eng-stat-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="eng-stat-title">Over-Budget</div>
            <div class="eng-stat-value"><?php echo $overBudgetCount; ?></div>
            <div class="eng-stat-sub">Allocated exceeds budgeted</div>
            <?php if ($overBudgetCount > 0): ?>
                <div class="eng-stat-hint"><i class="bi bi-eye"></i> Click to view</div>
            <?php endif; ?>
        </div>

        <div class="eng-stat-card">
            <div class="eng-stat-icon warn"><i class="bi bi-slash-circle"></i></div>
            <div class="eng-stat-title">Unassigned</div>
            <div class="eng-stat-value"><?php echo $unassignedCount; ?></div>
            <div class="eng-stat-sub">No hours assigned yet</div>
        </div>
    </div>
    <!-- end stats cards -->

    <!-- search bar and filter dropdown -->
    <div class="eng-toolbar">
        <div class="client-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="engagementSearch" class="client-search-input" placeholder="Search engagements..." minlength="3">
        </div>

        <!-- Status Filter Dropdown -->
        <div class="dropdown ms-auto">
            <button class="eng-filter-btn dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Filter Status
            </button>
            <ul class="dropdown-menu p-3" aria-labelledby="statusDropdown" style="min-width: 200px;">
                <li>
                    <label class="form-check d-flex align-items-center">
                        <input type="checkbox" class="form-check-input status-filter me-2" value="confirmed" checked>
                        Confirmed
                    </label>
                </li>
                <li>
                    <label class="form-check d-flex align-items-center">
                        <input type="checkbox" class="form-check-input status-filter me-2" value="pending" checked>
                        Pending
                    </label>
                </li>
                <li>
                    <label class="form-check d-flex align-items-center">
                        <input type="checkbox" class="form-check-input status-filter me-2" value="not_confirmed" checked>
                        Not Confirmed
                    </label>
                </li>
            </ul>
        </div>
    </div>
    <!-- end search bar and filter dropdown -->


    <!-- Engagements Table -->
        <div class="client-table-shell mt-3">
          <div class="client-table-scroll">
            <table id="engagement-table" class="client-table mb-0">
                <thead>
                    <tr>
                        <th class="check"><input type="checkbox" id="selectAllEngagements"></th>
                        <th>Client</th>
                        <th class="num">Budgeted Hrs</th>
                        <th class="num">Allocated Hrs</th>
                        <th>Utilization</th>
                        <th>Status</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($engagementRows) > 0): ?>
                    <?php foreach ($engagementRows as $row): ?>
                        <?php
                            $status = strtolower($row['status']);
                            $statusClass = str_replace('_', '-', $status);
                            $statusLabel = $status === 'not_confirmed' ? 'Not Confirmed' : ucfirst($status);
                            $avatarColor = avatar_color($row['client_name']);
                            $initials = avatar_initials($row['client_name']);

                            $rowBudgeted = (float)$row['budgeted_hours'];
                            $rowAllocated = (float)$row['total_assigned_hours'];
                            $rowPct = $rowBudgeted > 0 ? ($rowAllocated / $rowBudgeted) * 100 : 0;
                            $rowBarWidth = min(100, $rowPct);
                            if ($rowAllocated > $rowBudgeted) {
                                $rowUtilColor = 'red';
                            } elseif ($rowPct >= 75) {
                                $rowUtilColor = 'yellow';
                            } else {
                                $rowUtilColor = 'green';
                            }
                        ?>
                        <tr data-status="<?php echo $status; ?>" class="client-row">
                            <td><input type="checkbox" class="selectEngagement" data-engagement-id="<?php echo $row['engagement_id']; ?>"></td>
                            <td>
                                <div class="client-cell">
                                    <div class="client-tile" style="background-color: <?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($initials); ?></div>
                                    <span class="client-name"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                </div>
                            </td>
                            <td class="num"><span class="hours-value"><?php echo $row['budgeted_hours']; ?></span></td>
                            <td class="num"><span class="hours-value"><?php echo $row['total_assigned_hours']; ?></span></td>
                            <td>
                                <div class="eng-util-cell">
                                    <div class="eng-util-track">
                                        <div class="eng-util-fill <?php echo $rowUtilColor; ?>" style="width: <?php echo $rowBarWidth; ?>%"></div>
                                    </div>
                                    <span class="eng-util-pct <?php echo $rowUtilColor; ?>"><?php echo round($rowPct); ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="eng-status-pill <?php echo $statusClass; ?>">
                                    <span class="dot"></span><?php echo $statusLabel; ?>
                                </span>
                            </td>
                            <td class="num">
                                <div class="client-row-actions">
                                    <button class="client-icon-btn view-engagement-btn"
                                        data-engagement-id="<?php echo $row['engagement_id']; ?>"
                                        data-avatar-color="<?php echo $avatarColor; ?>"
                                        data-initials="<?php echo htmlspecialchars($initials); ?>"
                                        title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="client-icon-btn edit edit-engagement-btn"
                                        data-bs-toggle="modal" data-bs-target="#editEngagementModal"
                                        data-engagement-id="<?php echo $row['engagement_id']; ?>"
                                        data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                        data-budgeted-hours="<?php echo $row['budgeted_hours']; ?>"
                                        data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                        data-manager="<?php echo htmlspecialchars($row['manager'] ?? ''); ?>"
                                        data-notes="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>"
                                        title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <div class="dropdown">
                                        <button class="client-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="More">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item archive-engagement-btn" href="#"
                                                    data-engagement-id="<?php echo $row['engagement_id']; ?>"
                                                    data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                                    data-engagement-year="<?php echo date('Y'); ?>"
                                                    data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                                    <i class="bi bi-archive me-2"></i>Archive
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-engagement-btn" href="#"
                                                    data-engagement-id="<?php echo $row['engagement_id']; ?>"
                                                    data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No engagements found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination Controls -->
        <nav>
            <ul id="pagination-engagements" class="pagination justify-content-center mt-3"></ul>
        </nav>
    <!-- end engagement table -->

    <!-- Over-Budget Engagements modal (server-rendered from the same engagement fetch above) -->
    <div class="modal fade" id="overBudgetModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
        <div class="modal-content">
          <div class="modal-body position-relative" style="max-height: 70vh !important; overflow-y: auto !important;">
            <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="eng-vm-header">
                <div class="eng-vm-client-name">Over-Budget Engagements</div>
                <span class="eng-stat-sub">Allocated hours exceed the budget</span>
            </div>
            <div class="eng-vm-emp-list">
                <?php if (count($overBudgetList) > 0): ?>
                    <?php foreach ($overBudgetList as $ob): ?>
                        <div class="eng-ob-row">
                            <div class="client-tile" style="background-color: <?php echo avatar_color($ob['client_name']); ?>; width:26px; height:26px; font-size:10px;"><?php echo htmlspecialchars(avatar_initials($ob['client_name'])); ?></div>
                            <div class="eng-ob-client"><?php echo htmlspecialchars($ob['client_name']); ?></div>
                            <div class="eng-ob-hours"><?php echo $ob['allocated_hours']; ?> of <?php echo $ob['budgeted_hours']; ?>h</div>
                            <div class="eng-ob-over">+<?php echo $ob['over_hours']; ?>h</div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="eng-ob-row"><div class="eng-ob-client text-muted">No engagements are over budget</div></div>
                <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

</div>


<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title">Archive Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p>
          Are you sure you want to archive the current engagement for 
          "<strong id="modalClientName"></strong>"? This will move the engagement to archived status 
          and it will no longer appear in active lists.
        </p>

        <div class="small">
          <div><strong>Client:</strong> <span id="modalClient"></span></div>
          <div><strong>Year:</strong> <span id="modalYear"></span></div>
          <div><strong>Status:</strong> <span id="modalStatus"></span></div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmArchiveBtn" class="btn btn-danger">Archive Engagement</button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="deleteEngagementModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>
          Are you sure you want to permanently delete the engagement for
          "<strong id="deleteModalClientName"></strong>"? This removes it and all assigned
          hours immediately - unlike Archive, <strong>no record is kept</strong> and this cannot be undone.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmDeleteEngagementBtn" class="btn btn-danger">Delete Permanently</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    let engagementId, clientName, year, status;

    document.querySelectorAll(".archive-engagement-btn").forEach(btn => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            engagementId = this.dataset.engagementId;
            clientName = this.dataset.clientName;
            year = this.dataset.engagementYear;
            status = this.dataset.status;

            // Fill modal fields
            document.getElementById("modalClientName").textContent = clientName;
            document.getElementById("modalClient").textContent = clientName;
            document.getElementById("modalYear").textContent = year;
            document.getElementById("modalStatus").textContent = status;

            // Show modal
            let archiveModal = new bootstrap.Modal(document.getElementById("archiveModal"));
            archiveModal.show();
        });
    });

    // Confirm archive button
    document.getElementById("confirmArchiveBtn").addEventListener("click", function () {
        fetch("archive_engagement.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `engagement_id=${engagementId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload(); // refresh page
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => console.error("Error:", err));
    });

    // Delete (permanent) flow
    let deleteEngagementId, deleteClientName;
    document.querySelectorAll(".delete-engagement-btn").forEach(btn => {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            deleteEngagementId = this.dataset.engagementId;
            deleteClientName = this.dataset.clientName;
            document.getElementById("deleteModalClientName").textContent = deleteClientName;
            new bootstrap.Modal(document.getElementById("deleteEngagementModal")).show();
        });
    });

    document.getElementById("confirmDeleteEngagementBtn").addEventListener("click", function () {
        fetch("delete_engagement_permanent.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ engagement_id: deleteEngagementId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert("Error: " + (data.message || "Could not delete engagement."));
            }
        })
        .catch(err => console.error("Error:", err));
    });
});

</script>


<!-- search, filter, and pagination -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const engagementSearch = document.getElementById('engagementSearch');
        const engagementTable = document.getElementById('engagement-table').getElementsByTagName('tbody')[0];
        const statusFilters = document.querySelectorAll('.status-filter');
        const paginationContainer = document.getElementById('pagination-engagements');

        const rowsPerPage = 10;
        let currentPage = 1;
        let allRows = Array.from(engagementTable.getElementsByTagName('tr'));
        let filteredRows = [...allRows];

        // ✅ Create pagination controls (build li inside existing <ul>)
        function createPaginationControls(totalPages, currentPage, onPageChange) {
            paginationContainer.innerHTML = '';

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
                paginationContainer.appendChild(li);
            }

            // Prev button
            createPageItem('Prev', currentPage === 1, false, () => onPageChange(currentPage - 1));

            // Max 10 visible pages
            const maxVisiblePages = 10;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = startPage + maxVisiblePages - 1;
            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                createPageItem(i, false, i === currentPage, () => onPageChange(i));
            }

            // Next button
            createPageItem('Next', currentPage === totalPages, false, () => onPageChange(currentPage + 1));
        }

        // ✅ Show rows for current page
        function renderTablePage(page) {
            currentPage = page;
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            allRows.forEach(row => (row.style.display = 'none'));
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filteredRows.slice(start, end).forEach(row => (row.style.display = ''));

            if (totalPages > 1) {
                paginationContainer.style.display = 'flex';
                createPaginationControls(totalPages, currentPage, renderTablePage);
            } else {
                paginationContainer.style.display = 'none';
            }
        }

        // ✅ Filtering logic (search + status)
        function filterEngagements() {
            const query = engagementSearch.value ? engagementSearch.value.toLowerCase() : '';
            const searchTerms = query.split(',').map(term => term.trim()).filter(term => term.length >= 3);
            const activeStatuses = Array.from(statusFilters).filter(cb => cb.checked).map(cb => cb.value);

            filteredRows = allRows.filter(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');

                const statusMatch = activeStatuses.length === 0 || activeStatuses.includes(rowStatus);
                let searchMatch = searchTerms.length === 0 || searchTerms.some(term => text.includes(term));

                return statusMatch && searchMatch;
            });

            renderTablePage(1);
        }

        // ✅ Event listeners
        if (engagementSearch) {
            engagementSearch.addEventListener('input', filterEngagements);
        }
        statusFilters.forEach(cb => cb.addEventListener('change', filterEngagements));

        // ✅ Bulk select engagements
        document.getElementById('selectAllEngagements').addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.selectEngagement').forEach(cb => cb.checked = checked);
        });

        // ✅ Initialize
        filterEngagements();
    });
    </script>
<!-- end search, filter, and pagination -->

<?php include_once '../includes/modals/view_engagement_modal.php'; ?>
<?php include_once '../includes/modals/edit_engagement_modal.php'; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/view_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/edit_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const overBudgetCard = document.getElementById('overBudgetCard');
        if (overBudgetCard && overBudgetCard.classList.contains('clickable')) {
            overBudgetCard.addEventListener('click', () => {
                new bootstrap.Modal(document.getElementById('overBudgetModal')).show();
            });
        }
    });
</script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
