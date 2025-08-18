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


?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

</head>
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Client Management</h3>
    <p class="text-muted mb-4">Manage all onboarded clients and engagements</p>

    <div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Left: Bulk Import -->
    <div>
        <a href="#" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#importClientsModal">
            <i class="bi bi-upload me-1"></i> Bulk Import Clients
        </a>
    </div>

    <!-- Right: Add Client -->
    <div>
        <a href="#" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addClientModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Client
        </a>
    </div>
</div>

<div class="row g-3">
<?php
$query = "SELECT * FROM clients ORDER BY onboarded_date DESC";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($client = mysqli_fetch_assoc($result)) {
        // Calculate years onboard
        $onboarded = new DateTime($client['onboarded_date']);
        $now = new DateTime();
        $interval = $onboarded->diff($now);
        $yearsOnboard = $interval->y;

        // Example: get active and total engagements (replace with real queries)
        $client_id = $client['client_id'];
        $engagementQuery = "SELECT COUNT(*) as total, 
                                   SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active 
                            FROM engagements WHERE client_id = $client_id";
        $engagementResult = mysqli_query($conn, $engagementQuery);
        $engagementData = mysqli_fetch_assoc($engagementResult);
        $totalEngagements = $engagementData['total'];
        $activeEngagements = $engagementData['active'];

        // Determine status
        $status = $activeEngagements > 0 ? 'Active' : 'Inactive';
        $statusBadge = $status === 'Active' ? 'bg-dark' : 'bg-secondary';
?>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-building me-2 fs-4 text-muted"></i>
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($client['client_name']); ?></h5>
                </div>
                <?php if (!empty($client['notes'])): ?>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($client['notes']); ?></p>
                <?php endif; ?>

                <span class="badge <?php echo $statusBadge; ?> mb-2"><?php echo $status; ?></span>
                <p class="mb-1"><i class="bi bi-people me-1"></i> Active engagements: <?php echo $activeEngagements; ?></p>
                <p class="mb-1"><i class="bi bi-calendar-check me-1"></i> Total engagements: <?php echo $totalEngagements; ?></p>
                <p class="mb-0"><i class="bi bi-clock me-1"></i> Onboarded: <?php echo date('n/j/Y', strtotime($client['onboarded_date'])); ?> (<?php echo $yearsOnboard; ?> years)</p>
            </div>
            <div class="card-footer bg-transparent border-top d-flex gap-2">
                <a href="view_client.php?id=<?php echo $client['client_id']; ?>" class="btn btn-outline-secondary flex-grow-1">
                    <i class="bi bi-eye me-1"></i> View
                </a>
                <a href="#" class="btn btn-outline-dark flex-grow-1" data-bs-toggle="modal" data-bs-target="#editClientModal" data-client-id="<?php echo $client['client_id']; ?>">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            </div>
        </div>
    </div>
<?php
    }
} else {
    echo '<div class="col-12 text-center text-muted">No clients found.</div>';
}
?>
</div>

        

    </div> <!-- end container -->
</div> <!-- end flex-grow -->



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
