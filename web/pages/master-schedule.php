<?php
require_once '../includes/db.php';
session_start();

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isManager = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'manager';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Default week offset
$weekOffset = isset($_GET['week_offset']) ? intval($_GET['week_offset']) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

    <style>
        .timeoff-cell { background-color: rgb(217,217,217) !important; }
        <?php if ($isAdmin): ?>
        .timeoff-cell:hover { background-color: #e0f7fa !important; }
        <?php endif; ?>
        .timeoff-corner { position: absolute; top: 2px; right: 6px; font-size: .50rem; }
        .timeoff-card { border: 2px dashed rgb(209,226, 159) !important; background: rgb(246, 249, 236) !important; }
    </style>
</head>
<body class="d-flex">
<?php include_once '../templates/sidebar.php'; ?>
<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Master Schedule</h3>
            <p class="text-muted mb-0">Complete overview of all client engagements and team assignments</p>
        </div>
        <div class="header-buttons">
            <a href="#" onclick="location.reload();" class="badge text-black p-2 text-decoration-none fw-medium me-1" style="font-size: .875rem; border: 1px solid rgb(229,229,229);">
              <i class="bi bi-arrow-clockwise me-3"></i>Refresh
            </a>
        </div>
    </div>

    <!-- upper search and week slider -->
    <div class="bg-white border rounded p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <input type="text" id="searchInput" class="form-control" placeholder="Search employees..." />
            </div>
            <div class="col-md-6 d-flex justify-content-end align-items-center gap-3">
                <input type="range" id="weekSlider" min="-2" max="10" value="<?php echo $weekOffset; ?>" style="width: 200px;">
                <span id="weekLabel" class="fw-semibold">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Master Schedule table -->
    <div id="scheduleContainer" class="table-responsive">
        <!-- Table will load dynamically via AJAX -->
        <div class="text-center py-5">Loading schedule...</div>
    </div>

    <?php if ($isAdmin): ?>
        <?php include_once '../includes/modals/manage_entries_prompt.php'; ?>
        <?php include_once '../includes/modals/manage_entries.php'; ?>
        <?php include_once '../includes/modals/editEntryModal.php'; ?>
        <?php include_once '../includes/modals/add_entry.php'; ?>
        <?php include_once '../includes/modals/add_engagement.php'; ?>
    <?php endif; ?>

    <?php include_once '../includes/modals/engagement_details.php'; ?>
    <?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const slider = document.getElementById('weekSlider');
    const weekLabel = document.getElementById('weekLabel');
    const container = document.getElementById('scheduleContainer');

    // Function to fetch table data
    function loadSchedule(offset) {
        fetch(`load_schedule.php?week_offset=${offset}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                updateLabel(offset);
            });
    }

    // Function to update the label
    function updateLabel(offset) {
        const today = new Date();
        const currentMonday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
        const startMonday = new Date(currentMonday);
        startMonday.setDate(startMonday.getDate() - 14 + (offset * 7));
        const endMonday = new Date(startMonday);
        endMonday.setDate(endMonday.getDate() + 6 * 7);
        const options = { month: 'numeric', day: 'numeric' };
        weekLabel.textContent = `Week of ${startMonday.toLocaleDateString('en-US', options)} - Week of ${endMonday.toLocaleDateString('en-US', options)}`;
    }

    // Initial load
    loadSchedule(slider.value);

    slider.addEventListener('input', () => {
        loadSchedule(slider.value);
    });

    document.getElementById('searchInput').addEventListener('keyup', () => {
        const filter = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#scheduleContainer tbody tr').forEach(row => {
            const name = row.querySelector('.employee-name').textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
});
</script>
</body>
</html>
