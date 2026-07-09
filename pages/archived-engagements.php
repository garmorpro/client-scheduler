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

$query = "
    SELECT h.history_id, h.client_id, h.engagement_year, h.budgeted_hours, h.allocated_hours,
           h.manager, h.senior, h.staff, h.notes, h.archived_by, h.archive_date,
           c.client_name
    FROM client_engagement_history h
    JOIN clients c ON h.client_id = c.client_id
    ORDER BY h.archive_date DESC
";
$result = mysqli_query($conn, $query);
$historyRows = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Archived Engagements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Archived Engagements <span class="ms-2" style="font-size: 20px;">(<?php echo count($historyRows); ?>)</span></h3>
            <p class="mb-0">Engagements that were archived off the active Engagement Management list</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="engagement-management.php" class="badge p-2 text-decoration-none fw-medium btn-outline-custom">
                <i class="bi bi-arrow-left me-3"></i>Back to Engagements
            </a>
        </div>
    </div>

    <div class="client-toolbar">
        <div class="client-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="archivedSearch" class="client-search-input" placeholder="Search by client or manager...">
        </div>
        <span class="client-toolbar-hint" id="archivedToolbarHint"></span>
    </div>

    <div class="client-table-shell">
        <div class="client-table-scroll">
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th class="num">Year</th>
                        <th class="num">Budgeted Hrs</th>
                        <th class="num">Allocated Hrs</th>
                        <th>Manager</th>
                        <th>Senior</th>
                        <th>Staff</th>
                        <th>Archived</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody id="archivedTableBody">
                    <?php if (count($historyRows) > 0): ?>
                        <?php foreach ($historyRows as $row): ?>
                            <?php
                                $avatarColor = avatar_color($row['client_name']);
                                $initials = avatar_initials($row['client_name']);
                                $searchText = strtolower($row['client_name'] . ' ' . $row['manager']);
                            ?>
                            <tr class="client-row" data-search="<?php echo htmlspecialchars($searchText); ?>">
                                <td>
                                    <div class="client-cell">
                                        <div class="client-tile" style="background-color: <?php echo $avatarColor; ?>;"><?php echo htmlspecialchars($initials); ?></div>
                                        <span class="client-name"><?php echo htmlspecialchars($row['client_name']); ?></span>
                                    </div>
                                </td>
                                <td class="num"><?php echo htmlspecialchars($row['engagement_year']); ?></td>
                                <td class="num"><span class="hours-value"><?php echo $row['budgeted_hours']; ?></span></td>
                                <td class="num"><span class="hours-value"><?php echo $row['allocated_hours']; ?></span></td>
                                <td><?php echo htmlspecialchars($row['manager'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['senior'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['staff'] ?? '-'); ?></td>
                                <td>
                                    <span class="client-onboarded-text">
                                        <?php echo date('n/j/Y', strtotime($row['archive_date'])); ?> by <?php echo htmlspecialchars($row['archived_by']); ?>
                                    </span>
                                </td>
                                <td class="num">
                                    <div class="client-row-actions">
                                        <button class="client-icon-btn add unarchive-btn"
                                            data-history-id="<?php echo $row['history_id']; ?>"
                                            data-client-name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                            title="Unarchive">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No archived engagements</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<div class="modal fade" id="unarchiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Unarchive Engagement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>
          Restore the archived engagement for "<strong id="unarchiveModalClientName"></strong>" back onto the
          active Engagement Management list? It will come back with a <strong>Pending</strong> status - you can
          update it from Edit afterward. This record will be removed from Archived Engagements.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="confirmUnarchiveBtn" class="btn btn-dark">Unarchive</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let unarchiveHistoryId = null;
    const unarchiveModal = new bootstrap.Modal(document.getElementById('unarchiveModal'));

    document.querySelectorAll('.unarchive-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            unarchiveHistoryId = btn.dataset.historyId;
            document.getElementById('unarchiveModalClientName').textContent = btn.dataset.clientName;
            unarchiveModal.show();
        });
    });

    document.getElementById('confirmUnarchiveBtn').addEventListener('click', () => {
        if (!unarchiveHistoryId) return;
        fetch('unarchive_engagement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ history_id: unarchiveHistoryId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Could not unarchive engagement.'));
            }
        })
        .catch(err => console.error('Error:', err));
    });

    const archivedSearch = document.getElementById('archivedSearch');
    const archivedRows = Array.from(document.getElementById('archivedTableBody').getElementsByClassName('client-row'));
    const toolbarHint = document.getElementById('archivedToolbarHint');

    function updateHint(visibleCount) {
        if (!toolbarHint) return;
        toolbarHint.textContent = visibleCount === archivedRows.length
            ? `Showing all ${archivedRows.length}`
            : `Showing ${visibleCount} of ${archivedRows.length}`;
    }

    archivedSearch.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const terms = query.split(',').map(t => t.trim()).filter(t => t.length > 0);

        let visibleCount = 0;
        archivedRows.forEach(row => {
            const haystack = row.dataset.search || '';
            const matches = terms.length === 0 || terms.some(t => haystack.includes(t));
            row.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });
        updateHint(visibleCount);
    });

    updateHint(archivedRows.length);
</script>

<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
