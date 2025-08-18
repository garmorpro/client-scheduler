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

// Fetch clients from database
$stmt = $conn->prepare("SELECT id, company_name, contact_name, status, onboarded_date, active_engagements, total_engagements FROM clients ORDER BY company_name ASC");
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <style>
        .client-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .client-card .status-badge {
            text-transform: capitalize;
            font-size: 0.8rem;
        }
        .client-card .card-buttons button {
            margin-right: 5px;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .client-search {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="d-flex">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Client Management</h3>
            <p class="text-muted mb-0">Manage all onboarded clients and their engagement status</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2"><i class="bi bi-upload"></i> Bulk Import Client</button>
            <button class="btn btn-dark"><i class="bi bi-plus-lg"></i> Add New Client</button>
        </div>
    </div>

    <input type="text" id="searchInput" class="form-control client-search" placeholder="Search clients by name...">

    <div class="row" id="clientCards">
        <?php foreach($clients as $client): ?>
            <div class="col-md-4">
                <div class="client-card">
                    <div class="d-flex align-items-center mb-2">
                        <i class="bi bi-building me-2"></i>
                        <h5 class="mb-0"><?php echo htmlspecialchars($client['company_name']); ?></h5>
                    </div>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($client['contact_name']); ?></p>
                    <span class="badge bg-<?php echo $client['status'] === 'active' ? 'dark' : 'secondary'; ?> status-badge mb-2">
                        <?php echo htmlspecialchars($client['status']); ?>
                    </span>
                    <p class="mb-1"><i class="bi bi-people"></i> Active engagements: <?php echo $client['active_engagements']; ?></p>
                    <p class="mb-1"><i class="bi bi-calendar-event"></i> Total engagements: <?php echo $client['total_engagements']; ?></p>
                    <p class="mb-3"><i class="bi bi-clock"></i> Onboarded: <?php echo date("n/j/Y", strtotime($client['onboarded_date'])); ?></p>
                    <div class="card-buttons d-flex">
                        <button class="btn btn-outline-dark btn-sm flex-grow-1"><i class="bi bi-eye"></i> View</button>
                        <button class="btn btn-outline-secondary btn-sm flex-grow-1"><i class="bi bi-pencil"></i> Edit</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('searchInput');
    const clientCards = document.getElementById('clientCards');
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        Array.from(clientCards.children).forEach(cardCol => {
            const companyName = cardCol.querySelector('h5').innerText.toLowerCase();
            if (companyName.includes(query)) {
                cardCol.style.display = '';
            } else {
                cardCol.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>
