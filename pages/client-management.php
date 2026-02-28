<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!$isAdmin && !$isManager) {
    header("Location: my-schedule.php");
    exit();
}

// Fetch all clients
$stmt = $conn->prepare("SELECT * FROM clients ORDER BY client_name ASC");
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count total active clients
$stmt1 = $conn->prepare("SELECT COUNT(*) AS active_clients FROM clients WHERE status = 'active'");
$stmt1->execute();
$result1 = $stmt1->get_result()->fetch_assoc();
$activeClientsCount = (int)$result1['active_clients'];
$stmt1->close();

// Count total inactive clients
$stmt2 = $conn->prepare("SELECT COUNT(*) AS inactive_clients FROM clients WHERE status = 'inactive'");
$stmt2->execute();
$result2 = $stmt2->get_result()->fetch_assoc();
$inactiveClientsCount = (int)$result2['inactive_clients'];
$stmt2->close();

// Count total clients overall
$stmt3 = $conn->prepare("SELECT COUNT(*) AS total_clients FROM clients");
$stmt3->execute();
$result3 = $stmt3->get_result()->fetch_assoc();
$totalClientsCount = (int)$result3['total_clients'];
$stmt3->close();

// Fetch all engagement counts in one query
$sql = "
    SELECT client_id,
           COUNT(*) AS total_engagements,
           SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_engagements
    FROM engagements
    GROUP BY client_id
";
$result = $conn->query($sql);

$engagementCounts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $engagementCounts[$row['client_id']] = [
            'total_engagements' => (int)$row['total_engagements'],
            'confirmed_engagements' => (int)$row['confirmed_engagements']
        ];
    }
}

// Map engagement counts to clients
foreach ($clients as &$client) {
    $clientId = $client['client_id'];
    $client['total_engagements'] = $engagementCounts[$clientId]['total_engagements'] ?? 0;
    $client['confirmed_engagements'] = $engagementCounts[$clientId]['confirmed_engagements'] ?? 0;
}
unset($client);
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
        .view-btn:hover {
          background-color: rgb(229,229,229) !important;
        }
        .delete-client-btn:hover {
            background-color: rgb(195,49,66) !important;
        }
        .delete-client-btn:hover i {
            color: white !important;
        }
    </style>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Client Management<span class="ms-2" style="font-size: 20px;">(<?php echo $activeClientsCount; ?>)</span></h3>
            <p class="mb-0">Manage all onboarded clients and their engagement status</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#" class="badge text-black p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem; border: 1px solid rgb(229,229,229);" 
               data-bs-toggle="modal" data-bs-target="#importClientsModal">
                <i class="bi bi-upload me-3"></i>Import Clients
            </a>
            <a href="#" class="badge p-2 text-decoration-none fw-medium" 
               style="font-size: .875rem;" 
               data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-person-plus me-3"></i>Add New Client
            </a>
        </div>
    </div>

    <input type="text" id="searchInput" class="form-control client-search" placeholder="Search clients by name...">

    <div id="clientCards" class="client-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: .5rem 1rem;">
        <?php foreach ($clients as $client): ?>
            <div class="client-card p-4 bg-card text-card-foreground flex flex-col gap-2 rounded-xl position-relative">
                <!-- Delete Button (Top Right) -->
                <button class="btn btn-sm position-absolute top-0 end-0 m-2 delete-client-btn"
                    data-client-id="<?php echo $client['client_id']; ?>"
                    data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                    data-confirmed-engagements="<?php echo $client['confirmed_engagements']; ?>"
                    data-total-engagements="<?php echo $client['total_engagements']; ?>"
                    title="Delete Client">
                <i class="bi bi-trash text-danger"></i>
            </button>
                    
                    
                <!-- Client Header -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="fs-6 fw-semibold mb-0 client-name"><?php echo htmlspecialchars($client['client_name']); ?></div>
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
                    <span class="text-muted"><i class="bi bi-check-circle me-2"></i> Confirmed engagements</span>
                    <span><?php echo $client['confirmed_engagements'] ?? 0; ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3 flex-wrap">
                    <span class="text-muted"><i class="bi bi-calendar-event me-2"></i> Total engagements</span>
                    <span><?php echo $client['total_engagements'] ?? 0; ?></span>
                </div>
                        
                <!-- Card Buttons -->
                 <button class="badge text-white btn-sm flex-grow-1 fw-normal p-2 mb-2 w-100" 
                        style="font-size: .875rem; background-color: rgb(3,2,18); border: none !important;"
                        data-bs-toggle="modal" 
                        data-bs-target="#addEngagementModal" 
                        data-client-id="<?php echo $client['client_id']; ?>"
                        data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>">
                    <i class="bi bi-plus-circle me-2"></i>Add Engagement
                </button>
                <div class="card-buttons d-flex flex-wrap gap-2">
                        
                    <button 
                        class="badge text-black btn-sm fw-medium flex-grow-1 me-0 p-2 view-btn" 
                        style="font-size: .875rem; background-color: white !important; border: 1px solid rgb(229,229,229) !important; outline: none !important;"
                        data-client-id="<?php echo $client['client_id']; ?>"
                    >
                        <i class="bi bi-eye me-2"></i>View
                    </button>
                    <button class="badge text-black btn-sm flex-grow-1 fw-medium p-2 edit-client-btn" 
                            style="font-size: .875rem; background-color: rgb(229,229,229); border: none !important;"
                            data-bs-toggle="modal" 
                            data-bs-target="#editClientModal"
                            data-client-id="<?php echo $client['client_id']; ?>"
                            data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                            data-onboarded-date="<?php echo $client['onboarded_date']; ?>"
                            data-status="<?php echo strtolower($client['status']); ?>"
                            data-notes="<?php echo htmlspecialchars($client['notes'] ?? ''); ?>">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                    </button>
                        
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




<?php include_once '../includes/modals/add_client_modal.php'; ?>
<?php include_once '../includes/modals/view_client_modal.php'; ?>
<?php include_once '../includes/modals/edit_client_modal.php'; ?>
<?php include_once '../includes/modals/import_client_modal.php'; ?>
<?php include_once '../includes/modals/delete_client_modal.php'; ?>
<?php include_once '../includes/modals/add_engagement_modal.php'; ?>


<script src="../assets/js/add_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/view_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/edit_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/import_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/delete_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/add_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>


<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
</body>
</html>
