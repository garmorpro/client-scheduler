<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

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

// Fetch engagements
$engagementQuery = "
    SELECT 
        e.engagement_id,
        c.client_name,
        e.status,
        e.budgeted_hours,
        COALESCE(SUM(en.assigned_hours), 0) AS total_assigned_hours
    FROM engagements e
    JOIN clients c ON e.client_id = c.client_id
    LEFT JOIN entries en ON e.engagement_id = en.engagement_id
    GROUP BY e.engagement_id, c.client_name, e.status, e.budgeted_hours
    ORDER BY c.client_name ASC
";

$engagementResult = mysqli_query($conn, $engagementQuery);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Engagement Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="user-management-header d-flex justify-content-between align-items-center">
        <!-- Left -->
        <div class="titles">
            <p class="text-black mb-0"><strong>Engagement Management</strong></p>
            <p class="mb-0">Monitor all client engagements and details</p>
        </div>

        <!-- Middle (Search) -->
        

        <!-- Right -->
        <div class="user-management-buttons d-flex align-items-center gap-2">
            <a href="#" id="bulkDeleteEngagementsBtn" class="badge text-white p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; background-color: darkred; display:none;">
              <i class="bi bi-trash me-3"></i>Delete Selected (<span id="selectedEngagementCount">0</span>)
            </a>

            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
               data-bs-toggle="modal" data-bs-target="#importEngagementsModal">
                <i class="bi bi-upload me-3"></i>Import Engagements
            </a>

            <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; background-color: rgb(3,2,18);" 
               data-bs-toggle="modal" data-bs-target="#addEngagementModal">
                <i class="bi bi-plus-circle me-3"></i>Add Engagement
            </a>
        </div>
    </div>

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
                <div class="stat-card">
                    <div class="card-icon"><i class="bi bi-person-up"></i></div>
                    <div class="stat-title">Active Users</div>
                    <div class="stat-value"><?php echo $totalActiveUsers; ?></div>
                    <div class="stat-sub"><?php echo $totalInactiveUsers; ?> inactive users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="card-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="stat-title">Confirmed Engagements</div>
                    <div class="stat-value"><?php echo $totalConfirmedEngagements; ?></div>
                    <div class="stat-sub"><?php echo $totalPendingEngagements; ?> pending <i class="bi bi-dot"></i> <?php echo $totalNotConfirmedEngagements; ?> not confirmed</div>
                </div>
            </div>
            <?php
            // Ensure totalEngagements is not zero to avoid division by zero
            if ($totalEngagements > 0) {
                $percentageAssigned = round(($totalAssigned / $totalEngagements) * 100);
            } else {
                $percentageAssigned = 0;
            }
            ?>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="card-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-title">Engagement Status</div>
                    <div class="stat-value"><?php echo $percentageAssigned; ?>%</div>
                    <div class="util-bar mt-2">
                        <div class="util-bar-fill" style="width: <?php echo $percentageAssigned; ?>%"></div>
                    </div>
                    <div class="stat-sub mt-2">
                        <?php echo $totalAssigned; ?> assigned <i class="bi bi-dot"></i> <?php echo $totalNotAssigned; ?> not assigned
                    </div>
                </div>
            </div>
        </div>
    <!-- end stats cards -->
     
        <div class="flex-grow-1 p-4" style="margin-left: 250px;">
            <div class="user-search mx-3" style="flex: 1; max-width: 300px;">
                <input type="text" id="engagementSearch" class="form-control form-control-sm" placeholder="Search engagements..." minlength="3">
            </div>
        </div>

    <!-- Engagements Table -->
        <div class="user-table mt-3">
            <table id="engagement-table" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllEngagements"></th>
                        <th>Client</th>
                        <th>Budgeted Hours</th>
                        <th>Allocated Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($engagementResult) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($engagementResult)): ?>
                        <tr>
                            <td><input type="checkbox" class="selectEngagement" data-engagement-id="<?php echo $row['engagement_id']; ?>"></td>
                            <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                            <td><?php echo $row['budgeted_hours']; ?></td>
                            <td><?php echo $row['total_assigned_hours']; ?></td>
                            <td>
                                <?php
                                $status = strtolower($row['status']);
                                switch ($status) {
                                    case 'confirmed':
                                        $badgeClass = 'badge-confirmed';   
                                        break;
                                    case 'pending':
                                        $badgeClass = 'badge-pending';     
                                        break;
                                    case 'not_confirmed':
                                        $badgeClass = 'badge-not-confirmed'; 
                                        break;
                                    default:
                                        $badgeClass = 'badge-default';    
                                        break;
                                }
                                ?>
                                <span class="badge-status <?php echo $badgeClass; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a href="#" class="view-engagement-btn text-decoration-none" 
                                   data-bs-toggle="modal" data-bs-target="#viewEngagementModal" 
                                   data-engagement-id="<?php echo $row['engagement_id']; ?>">
                                    <i class="bi bi-eye text-success"></i>
                                </a>
                                <a href="#" class="edit-engagement-btn text-decoration-none" 
                                   data-bs-toggle="modal" data-bs-target="#editEngagementModal" 
                                   data-engagement-id="<?php echo $row['engagement_id']; ?>">
                                    <i class="bi bi-pencil text-purple"></i>
                                </a>
                                <a href="#" class="delete-engagement-btn text-decoration-none" 
                                   data-engagement-id="<?php echo $row['engagement_id']; ?>">
                                    <i class="bi bi-trash text-danger"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No engagements found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
                
        <!-- Pagination Controls -->
        <nav>
            <ul id="pagination-engagements" class="pagination justify-content-center mt-3"></ul>
        </nav>
    <!-- end engagement table -->


</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Engagement Search
    const engagementSearch = document.getElementById('engagementSearch');
    const engagementTable = document.getElementById('engagement-table').getElementsByTagName('tbody')[0];

    engagementSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const rows = engagementTable.getElementsByTagName('tr');

        Array.from(rows).forEach(row => {
            const text = row.innerText.toLowerCase();
            if (query.length < 3 || text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Bulk select engagements
    document.getElementById('selectAllEngagements').addEventListener('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.selectEngagement').forEach(cb => cb.checked = checked);
    });
</script>
</body>
</html>
