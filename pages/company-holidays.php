<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

// Company Holidays lives under the System Settings permission - it's a
// firm-wide setting, not a per-role carve-out, so access/edit rights both
// key off access_system_settings (admin always passes this).
$canEditHolidays = user_has_permission($conn, 'access_system_settings');

if (!$canEditHolidays) {
    header("Location: my-schedule.php");
    exit();
}

// Fetch holidays grouped by timeoff_note
$holidays = [];
$sql = "SELECT * FROM time_off WHERE is_global_timeoff = 1 ORDER BY week_start ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $note = $row['timeoff_note'] ?? 'Holiday';
        if (!isset($holidays[$note])) {
            $holidays[$note] = ['name' => $note, 'days' => []];
        }
        $holidays[$note]['days'][] = [
            'id' => $row['timeoff_id'],
            'date' => $row['holiday_date'] ?? $row['week_start'], // fallback for old records
            'week_start' => $row['week_start'],
            'hours' => $row['assigned_hours']
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Holidays</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Company Holidays</h3>
            <p class="mb-0">Manage firm-wide holidays and closures</p>
        </div>
        <?php if ($canEditHolidays): ?>
        <div class="d-flex align-items-center gap-2">
            <a href="#" id="addHolidayBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom">
                <i class="bi bi-plus-lg me-3"></i>Add Holiday
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="client-toolbar">
        <div class="client-search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="holidaySearch" class="client-search-input" placeholder="Search holidays...">
        </div>
        <span class="client-toolbar-hint" id="holidayToolbarHint"></span>
    </div>

    <div class="client-table-shell">
        <div class="client-table-scroll">
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Holiday</th>
                        <th>Dates</th>
                        <th class="num">Total Hours</th>
                        <?php if ($canEditHolidays): ?><th class="num">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="holidaysTableBody">
                    <?php if (empty($holidays)): ?>
                        <tr><td colspan="4" class="text-center">No holidays added yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($holidays as $holiday): ?>
                            <?php
                                $daysJson = htmlspecialchars(json_encode($holiday['days']), ENT_QUOTES);
                                $totalHours = array_sum(array_column($holiday['days'], 'hours'));
                            ?>
                            <tr class="client-row" data-search="<?php echo htmlspecialchars(strtolower($holiday['name'])); ?>">
                                <td>
                                    <div class="holiday-cell">
                                        <div class="holiday-tile"><i class="bi bi-calendar3"></i></div>
                                        <span class="holiday-name"><?php echo htmlspecialchars($holiday['name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-list">
                                        <?php foreach ($holiday['days'] as $day): ?>
                                            <div class="date-row">
                                                <span class="d"><?php echo date('D, M j, Y', strtotime($day['date'])); ?></span>
                                                <span class="hrs"><?php echo $day['hours']; ?>h</span>
                                                <?php if ($canEditHolidays): ?>
                                                    <button type="button" class="chip-del" title="Remove this date" data-id="<?php echo $day['id']; ?>">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td class="num"><span class="client-total-value"><?php echo $totalHours; ?>h</span></td>
                                <?php if ($canEditHolidays): ?>
                                <td class="num">
                                    <div class="client-row-actions">
                                        <button class="client-icon-btn edit-holiday-btn"
                                            data-name="<?php echo htmlspecialchars($holiday['name']); ?>"
                                            data-days="<?php echo $daysJson; ?>"
                                            title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="client-icon-btn danger delete-holiday-btn"
                                            data-name="<?php echo htmlspecialchars($holiday['name']); ?>"
                                            title="Delete Holiday">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEditHolidays): ?>
<?php include_once '../includes/modals/holiday_modal.php'; ?>
<?php endif; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($canEditHolidays): ?>
<script src="../assets/js/holiday_modal.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script>
    const holidaySearch = document.getElementById('holidaySearch');
    const holidayRows = Array.from(document.getElementById('holidaysTableBody').getElementsByClassName('client-row'));
    const holidayToolbarHint = document.getElementById('holidayToolbarHint');

    function updateHolidayHint(visibleCount) {
        if (!holidayToolbarHint) return;
        holidayToolbarHint.textContent = visibleCount === holidayRows.length
            ? `Showing all ${holidayRows.length}`
            : `Showing ${visibleCount} of ${holidayRows.length}`;
    }

    if (holidaySearch) {
        holidaySearch.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const terms = query.split(',').map(t => t.trim()).filter(t => t.length > 0);

            let visibleCount = 0;
            holidayRows.forEach(row => {
                const haystack = row.dataset.search || '';
                const matches = terms.length === 0 || terms.some(t => haystack.includes(t));
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });
            updateHolidayHint(visibleCount);
        });
        updateHolidayHint(holidayRows.length);
    }
</script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
