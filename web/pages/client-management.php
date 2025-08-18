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
        <div class="d-flex align-items-center gap-2">
            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
               data-bs-toggle="modal" data-bs-target="#importClientsModal">
                <i class="bi bi-upload me-3"></i>Import Clients
            </a>
            <a href="#" class="badge text-white p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; background-color: rgb(3,2,18);" 
               data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-person-plus me-3"></i>Add New Client
            </a>
            <!-- <button class="btn btn-outline-secondary me-2"><i class="bi bi-upload"></i> Bulk Import Client</button>
            <button class="btn btn-dark"><i class="bi bi-plus-lg"></i> Add New Client</button> -->
        </div>
    </div>

    <input type="text" id="searchInput" class="form-control client-search" placeholder="Search clients by name...">

    <div id="clientCards" class="client-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: .5rem 1rem;">
        <?php foreach ($clients as $client): ?>
            <div class="client-card p-4 bg-card text-card-foreground flex flex-col gap-2 rounded-xl">
                <!-- Client Header -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="fs-6 mb-0 client-name"><?php echo htmlspecialchars($client['client_name']); ?></div>
                </div>

                <!-- Status and Onboarded Duration -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
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
                    <span class="text-muted mt-1 mt-md-0">
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
                <div class="d-flex justify-content-between mb-1 flex-wrap">
                    <span class="text-muted"><i class="bi bi-people me-2"></i> Active engagements</span>
                    <span><?php echo $client['active_engagements'] ?? 2; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 flex-wrap">
                    <span class="text-muted"><i class="bi bi-calendar-event me-2"></i> Total engagements</span>
                    <span><?php echo $client['total_engagements'] ?? 5; ?></span>
                </div>

                <!-- Card Buttons -->
                <div class="card-buttons d-flex flex-wrap gap-2">
                    <button class="badge text-black btn-sm fw-medium flex-grow-1 me-0 p-2" style="font-size: .875rem; border: none !important;"><i class="bi bi-eye"></i> View</button>
                    <button class="badge text-white btn-sm flex-grow-1 fw-medium p-2" style="font-size: .875rem; background-color: rgb(3,2,18); border: none !important;"><i class="bi bi-pencil"></i> Edit</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('searchInput');
    const clientCards = document.getElementById('clientCards');
    const cards = Array.from(clientCards.getElementsByClassName('client-card'));

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();

        // split by comma, trim spaces, require at least 3 characters
        const searchTerms = query
            .split(',')
            .map(term => term.trim())
            .filter(term => term.length >= 3);

        cards.forEach(card => {
            const name = card.querySelector('.client-name').innerText.toLowerCase();

            if (searchTerms.length === 0 || searchTerms.some(term => name.includes(term))) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>


</body>
</html>
