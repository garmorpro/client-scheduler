<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';

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
           SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_engagements,
           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_engagements,
           SUM(CASE WHEN status = 'not_confirmed' THEN 1 ELSE 0 END) AS not_confirmed_engagements
    FROM engagements
    GROUP BY client_id
";
$result = $conn->query($sql);

$engagementCounts = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $engagementCounts[$row['client_id']] = [
            'total_engagements' => (int)$row['total_engagements'],
            'confirmed_engagements' => (int)$row['confirmed_engagements'],
            'pending_engagements' => (int)$row['pending_engagements'],
            'not_confirmed_engagements' => (int)$row['not_confirmed_engagements']
        ];
    }
}

// Map engagement counts to clients
foreach ($clients as &$client) {
    $clientId = $client['client_id'];
    $client['total_engagements'] = $engagementCounts[$clientId]['total_engagements'] ?? 0;
    $client['confirmed_engagements'] = $engagementCounts[$clientId]['confirmed_engagements'] ?? 0;
    $client['pending_engagements'] = $engagementCounts[$clientId]['pending_engagements'] ?? 0;
    $client['not_confirmed_engagements'] = $engagementCounts[$clientId]['not_confirmed_engagements'] ?? 0;
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
    <a href="#" class="badge p-2 text-decoration-none fw-medium btn-outline-custom" id="importClientsBtn">
        <i class="bi bi-upload me-3"></i>Import Clients
    </a>
    <a href="#" class="badge p-2 text-decoration-none fw-medium btn-dark-custom" 
       data-bs-toggle="modal" data-bs-target="#addClientModal">
        <i class="bi bi-person-plus me-3"></i>Add New Client
    </a>
</div>
    </div>

    <div class="client-toolbar">
        <div class="client-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" class="client-search-input" placeholder="Search clients by name...">
        </div>
        <span class="client-toolbar-hint" id="clientToolbarHint"></span>
    </div>

    <div class="client-table-shell">
        <div class="client-table-scroll">
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Onboarded</th>
                        <th>Engagements</th>
                        <th class="num">Total</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody id="clientCards">
                    <?php foreach ($clients as $client): ?>
                        <?php
                            $status = strtolower($client['status']);
                            $statusPillClass = $status === 'active' ? 'active' : 'inactive';
                            $avatarColor = avatar_color($client['client_name']);
                            $initials = avatar_initials($client['client_name']);

                            $onboarded = new DateTime($client['onboarded_date']);
                            $now = new DateTime();
                            $diff = $now->diff($onboarded);
                            if ($diff->y == 0 && $diff->m == 0) {
                                $onboardedText = "New client";
                            } elseif ($diff->y == 0) {
                                $onboardedText = $diff->m . " month" . ($diff->m > 1 ? "s" : "") . " onboarded";
                            } else {
                                $onboardedText = $diff->y . " year" . ($diff->y > 1 ? "s" : "");
                                if ($diff->m > 0) {
                                    $onboardedText .= " " . $diff->m . " month" . ($diff->m > 1 ? "s" : "");
                                }
                                $onboardedText .= " onboarded";
                            }
                        ?>
                        <tr class="client-row">
                            <td>
                                <div class="client-cell">
                                    <div class="client-tile" style="background-color: <?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($initials); ?></div>
                                    <span class="client-name"><?php echo htmlspecialchars($client['client_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="client-status-pill <?php echo $statusPillClass; ?>">
                                    <span class="dot"></span><?php echo ucfirst(htmlspecialchars($client['status'])); ?>
                                </span>
                            </td>
                            <td><span class="client-onboarded-text"><?php echo $onboardedText; ?></span></td>
                            <td>
                                <div class="client-engagement-breakdown">
                                    <span class="client-mini-pill confirmed <?php echo $client['confirmed_engagements'] == 0 ? 'zero' : ''; ?>"><?php echo $client['confirmed_engagements']; ?> Conf.</span>
                                    <span class="client-mini-pill pending <?php echo $client['pending_engagements'] == 0 ? 'zero' : ''; ?>"><?php echo $client['pending_engagements']; ?> Pend.</span>
                                    <span class="client-mini-pill not-confirmed <?php echo $client['not_confirmed_engagements'] == 0 ? 'zero' : ''; ?>"><?php echo $client['not_confirmed_engagements']; ?> N/C</span>
                                </div>
                            </td>
                            <td class="num"><span class="client-total-value"><?php echo $client['total_engagements'] ?? 0; ?></span></td>
                            <td class="num">
                                <div class="client-row-actions">
                                    <button class="client-icon-btn add add-engagement-btn"
                                        data-client-id="<?php echo $client['client_id']; ?>"
                                        data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                                        title="Add Engagement">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button class="client-icon-btn view-client-button view-btn"
                                        data-client-id="<?php echo $client['client_id']; ?>"
                                        title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="client-icon-btn edit-client-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editClientModal"
                                        data-client-id="<?php echo $client['client_id']; ?>"
                                        data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                                        data-onboarded-date="<?php echo $client['onboarded_date']; ?>"
                                        data-status="<?php echo strtolower($client['status']); ?>"
                                        data-notes="<?php echo htmlspecialchars($client['notes'] ?? ''); ?>"
                                        title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="client-icon-btn danger delete-client-btn"
                                        data-client-id="<?php echo $client['client_id']; ?>"
                                        data-client-name="<?php echo htmlspecialchars($client['client_name']); ?>"
                                        data-confirmed-engagements="<?php echo $client['confirmed_engagements']; ?>"
                                        data-total-engagements="<?php echo $client['total_engagements']; ?>"
                                        title="Delete Client">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const searchInput = document.getElementById('searchInput');
    const clientCards = document.getElementById('clientCards');
    const cards = Array.from(clientCards.getElementsByClassName('client-row'));
    const toolbarHint = document.getElementById('clientToolbarHint');

    function updateToolbarHint(visibleCount) {
        if (!toolbarHint) return;
        toolbarHint.textContent = visibleCount === cards.length
            ? `Showing all ${cards.length}`
            : `Showing ${visibleCount} of ${cards.length}`;
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();

        // split by comma, trim spaces, require at least 3 characters
        const searchTerms = query
            .split(',')
            .map(term => term.trim())
            .filter(term => term.length >= 3);

        let visibleCount = 0;
        cards.forEach(card => {
            const name = card.querySelector('.client-name').innerText.toLowerCase();

            if (searchTerms.length === 0 || searchTerms.some(term => name.includes(term))) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        updateToolbarHint(visibleCount);
    });

    updateToolbarHint(cards.length);
</script>

<script>
    const managersList = <?php 
    $managerQuery = $conn->query("SELECT full_name FROM users WHERE role='manager' ORDER BY full_name ASC");
    $managers = [];
    while ($row = $managerQuery->fetch_assoc()) {
        $managers[] = $row['full_name'];
    }
    echo json_encode($managers);
?>;
</script>




<?php include_once '../includes/modals/add_client_modal.php'; ?>
<?php include_once '../includes/modals/view_client_modal.php'; ?>
<?php include_once '../includes/modals/edit_client_modal.php'; ?>
<?php include_once '../includes/modals/import_client_modal.php'; ?>
<?php include_once '../includes/modals/delete_client_modal.php'; ?>
<?php include_once '../includes/modals/add_engagement_modal.php'; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>


<script src="../assets/js/add_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/view_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/edit_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/import_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/delete_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/add_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/swal-modals/import-clients-modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/swal-modals/add-engagement-modal.js?v=<?php echo time(); ?>"></script>

<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>
