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

// Fetch all clients
$stmt = $conn->prepare("SELECT client_id, client_name, status, onboarded_date FROM clients ORDER BY client_name ASC");
$stmt->execute();
$clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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


<?php include_once '../includes/modals/add_engagement_modal.php'; ?>
<?php include_once '../includes/modals/edit_client_modal.php'; ?>
<?php include_once '../includes/modals/add_client_modal.php'; ?>
<script src="../assets/js/add_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/edit_client_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/add_client_modal.js?v=<?php echo time(); ?>"></script>




<!-- View Client Modal -->
<div class="modal fade" id="viewClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="viewClientModalBody">
        <!-- Filled dynamically by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const viewButtons = document.querySelectorAll('.view-btn');
    const modal = new bootstrap.Modal(document.getElementById('viewClientModal'));
    const modalBody = document.getElementById('viewClientModalBody');

    viewButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const clientId = button.dataset.clientId;

            try {
                const res = await fetch(`view_client.php?client_id=${clientId}`);
                const data = await res.json();

                if (data.error) {
                    alert(data.error);
                    return;
                }

                const client = data.client;
                const history = data.history;

                // Generate initials from client name
                const initials = client.client_name
                    .split(' ')
                    .map(n => n[0].toUpperCase())
                    .slice(0, 2)
                    .join('');

                    function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

                // Fill modal content with styled top details
                let html = `
    <div class="align-items-center" style="background-color: rgb(245,245,247); border-radius: 15px; display: flex; align-items: center; gap: 10px; padding: 10px; margin-top: -20px;">
        <div class="justify-content-between d-flex" style="flex-grow: 1;">
            <div id="view_client_name" class="fw-semibold">${client.client_name}<br><span>${ucfirst(client.status)}</span></div>
            <small id="view_onboarded_date" class="text-end">Onboarded<br><span class="text-muted">${new Date(client.onboarded_date).toLocaleDateString()}</span></small>
            
            
        </div>
    </div>
    <div class="d-flex gap-2 mt-2">

                <div style="flex:1; background-color: white; border-radius: 10px; padding: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="fw-semibold">${client.total_engagements}</div>
                    <div class="text-muted" style="font-size: 12px;">Total Engagements</div>
                </div>
                <div style="flex:1; background-color: white; border-radius: 10px; padding: 10px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="fw-semibold">${client.confirmed_engagements}</div>
                    <div class="text-muted" style="font-size: 12px;">Confirmed Engagements</div>
                </div>
            </div>
    <hr>
    <div id="engagementHistoryContainer"></div>
`;


                modalBody.innerHTML = html;

                const historyContainer = document.getElementById('engagementHistoryContainer');

                if (history.length === 0) {
                    historyContainer.innerHTML = `<p class="text-muted">No records available.</p>`;
                } else {
                    history.forEach(h => {
                        historyContainer.innerHTML += `
                            <div class="card p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>${h.engagement_year}</span>
                                    <span>${h.status || 'Archived'}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Budgeted: ${h.budgeted_hours}</span>
                                    <span>Allocated: ${h.allocated_hours}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Manager: ${h.manager}</span>
                                    <span>Senior: ${h.senior}</span>
                                    <span>Staff: ${h.staff}</span>
                                </div>
                                <hr>
                                <div>Archived: ${h.archive_date || 'N/A'}</div>
                            </div>
                        `;
                    });
                }

                modal.show();

            } catch (err) {
                alert('Error fetching client details.');
                console.error(err);
            }
        });
    });
});


</script>







<!-- import engagements modal -->

    <div class="modal fade" id="importEngagementsModal" tabindex="-1" aria-labelledby="importEngagementsModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="importEngagementsForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="importEngagementsModalLabel">Import Engagements from CSV</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
              <p>
                Please use the <a href="../assets/templates/bulk_engagement_template.csv" download>CSV template</a> to ensure correct format.
              </p>

              <div class="mb-3">
                <label for="engagements_csv_file" class="form-label">Select CSV File</label>
                <input type="file" class="form-control" id="engagements_csv_file" name="csv_file" accept=".csv" required>
              </div>

              <div class="alert alert-info small">
                Only CSV files are supported. Required columns: 
                <strong>client_name, budgeted_hours, status</strong><br>
                Allowed status values: <em>confirmed, pending, not_confirmed</em>
              </div>

              <!-- Import Summary Container -->
              <div id="engagementsImportSummary" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
                <!-- Filled dynamically by JS -->
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" id="importEngagementsSubmitBtn">Import</button>
              <button type="button" class="btn btn-success d-none" id="importEngagementsCloseBtn">OK</button>
            </div>
          </form>
        </div>
      </div>
    </div>


<!-- end import engagements modal -->


<!-- import engagements csv -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
          const importForm = document.getElementById('importEngagementsForm');
          const fileInput = document.getElementById('engagements_csv_file');
          const importSummary = document.getElementById('engagementsImportSummary');
          const importSubmitBtn = document.getElementById('importEngagementsSubmitBtn');
          const importCloseBtn = document.getElementById('importEngagementsCloseBtn');
          const importModal = new bootstrap.Modal(document.getElementById('importEngagementsModal'));

          importForm.addEventListener('submit', async (e) => {
            e.preventDefault();
        
            importSummary.style.display = 'none';
            importSummary.innerHTML = '';
            importCloseBtn.classList.add('d-none');
            importSubmitBtn.classList.remove('d-none');
        
            const file = fileInput.files[0];
            if (!file) {
              alert('Please select a CSV file to upload.');
              return;
            }
            if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
              alert('Only CSV files are allowed.');
              return;
            }
        
            const formData = new FormData();
            formData.append('csv_file', file);
        
            try {
              const response = await fetch('import_engagements.php', {
                method: 'POST',
                body: formData
              });
              const result = await response.json();
          
              importSummary.style.display = 'block';
          
              let html = `<p><strong>Import Results:</strong></p>`;
              html += `<p>Successfully imported: ${result.successCount}</p>`;
          
              if (result.errors.length > 0) {
                html += `<p class="text-danger">Errors (${result.errors.length}):</p><ul>`;
                result.errors.forEach(err => {
                  html += `<li>Row ${err.row}: ${err.message}</li>`;
                });
                html += `</ul>`;
              } else {
                html += `<p class="text-success">No errors found.</p>`;
              }
          
              importSummary.innerHTML = html;
          
              importCloseBtn.classList.remove('d-none');
              importSubmitBtn.classList.add('d-none');
          
              fileInput.value = '';
          
            } catch (error) {
              alert('Error processing import: ' + error.message);
            }
          });
      
          importCloseBtn.addEventListener('click', () => {
            importModal.hide();
            location.reload(); // reload page to show new engagements
          });
        });

    </script>
<!-- end import engagements csv -->


<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
</body>
</html>
