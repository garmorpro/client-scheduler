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
$stmt = $conn->prepare("SELECT client_id, client_name, status, onboarded_date FROM clients ORDER BY client_name ASC");
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
    <div class="client-card p-3">
        <!-- Client Header -->
        <div class="d-flex align-items-center mb-4">
            <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                <i class="bi bi-building"></i>
            </div>
            <div class="fs-6 mb-0"><?php echo htmlspecialchars($client['client_name']); ?></div>
        </div>

        <!-- Status and Onboarded Duration -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <?php
                $status = strtolower($client['status']);
                switch ($status) {
                    case 'active':
                        $badgeClass = 'badge-confirmed';   
                        break;
                    case 'inactive':
                        $badgeClass = 'badge-inactive';     
                        break;
                    default:
                        $badgeClass = 'badge-default';    
                        break;
                }
            ?>
            <span class="badge-status <?php echo $badgeClass; ?>">
                <?php echo ucfirst(htmlspecialchars($client['status'])); ?>
            </span>
            <span class="text-muted">
                <?php
                    $onboarded = new DateTime($client['onboarded_date']);
                    $now = new DateTime();
                    $diff = $now->diff($onboarded);

                    if ($diff->y == 0 && $diff->m == 0) {
                        echo "New client";
                    } elseif ($diff->y == 0) {
                        echo $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " onboarded";
                    } else {
                        echo $diff->y . " year" . ($diff->y > 1 ? "s" : "");
                        if ($diff->m > 0) {
                            echo " " . $diff->m . " month" . ($diff->m > 1 ? "s" : "");
                        }
                        echo " onboarded";
                    }
                ?>
            </span>
        </div>

        <!-- Engagements Info -->
        <div class="d-flex justify-content-between mb-1">
            <span class="text-muted"><i class="bi bi-people me-2"></i> Active engagements</span>
            <span>2</span>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span class="text-muted"><i class="bi bi-calendar-event me-2"></i> Total engagements</span>
            <span>5</span>
        </div>

        <!-- Card Buttons -->
        <div class="card-buttons d-flex">
            <button class="btn btn-outline-dark btn-sm flex-grow-1 me-2" style="border-color: rgb(242,242,242) !important;"><i class="bi bi-eye"></i> View</button>
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
