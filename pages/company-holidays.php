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

    <style>
        .holidays-container {
            max-width: 100%;
            margin: 0;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .page-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .page-icon-bubble {
            width: 44px;
            height: 44px;
            background: #2563eb;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .holiday-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: background 0.15s ease;
        }

        .holiday-card:hover { background: #f3f4f6; }

        .holiday-card-icon {
            width: 38px;
            height: 38px;
            background: #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }

        .holiday-card-info { flex-grow: 1; }

        .holiday-card-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .holiday-card-meta {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.8;
        }

        .holiday-card-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-add-holiday {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease;
        }

        .btn-add-holiday:hover { background: #1d4ed8; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
            opacity: 0.4;
        }

        /* Dark mode */
        body.dark-mode .holiday-card {
            background: #2a2a3d;
            border-color: #3a3a50;
        }
        body.dark-mode .holiday-card:hover { background: #33334a; }
        body.dark-mode .holiday-card-meta { color: #9ca3af; }

        /* Swal day row */
        .day-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .remove-day-btn {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 18px;
            padding: 0 4px;
            flex-shrink: 0;
        }

        .add-day-btn-dashed {
            background: none;
            border: 1px dashed #2563eb;
            color: #2563eb;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 4px;
            width: 100%;
        }

        .add-day-btn-dashed:hover { background: #eff6ff; }
    </style>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="holidays-container">

        <!-- Single clean header -->
        <div class="page-header">
            <div class="header-text">
                <h3 class="mb-1">Company Holidays</h3>
                <p class="text-muted mb-4">Manage firm-wide holidays and closures</p>
                
            </div>
            <?php if ($isAdmin): ?>
            <button class="btn-add-holiday" id="addHolidayBtn">
                <i class="bi bi-plus"></i> Add Holiday
            </button>
            <?php endif; ?>
        </div>

        <!-- Holiday list -->
        <div id="holidaysList">
            <?php if (empty($holidays)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    No holidays added yet.
                </div>
            <?php else: ?>
                <?php foreach ($holidays as $holiday): ?>
                <?php
                    $daysJson = htmlspecialchars(json_encode($holiday['days']), ENT_QUOTES);
                    $totalDays = count($holiday['days']);
                ?>
                <div class="holiday-card">
                    <div class="holiday-card-icon">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div class="holiday-card-info">
                        <div class="holiday-card-name"><?= htmlspecialchars($holiday['name']) ?></div>
                        <div class="holiday-card-meta">
                            <?php foreach ($holiday['days'] as $i => $day): ?>
                                <?= date('l, F j, Y', strtotime($day['date'])) ?> &bull; <?= $day['hours'] ?> hrs off<?= ($i < $totalDays - 1) ? '<br>' : '' ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="holiday-card-actions">
                        <button class="btn btn-sm btn-outline-secondary edit-holiday-btn"
                            data-name="<?= htmlspecialchars($holiday['name']) ?>"
                            data-days="<?= $daysJson ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-holiday-btn"
                            data-name="<?= htmlspecialchars($holiday['name']) ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>

<script>
<?php if ($isAdmin): ?>
document.getElementById('addHolidayBtn').addEventListener('click', function() {
    const isDark = document.body.classList.contains('dark-mode');

    Swal.fire({
        title: 'Add Company Holiday',
        background: isDark ? '#2a2a3d' : '#fff',
        color: isDark ? '#e0e0e0' : '#1a1a1a',
        width: '520px',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Holiday Name</label>
                    <input type="text" id="swal-holiday-name" class="form-control" placeholder="e.g. Labor Day">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Days Off</label>
                    <div id="days-container">
                        <div class="day-row">
                            <input type="date" class="form-control holiday-date" style="flex:2;">
                            <input type="number" class="form-control holiday-hours" placeholder="Hrs" value="8" min="1" max="24" style="flex:1;">
                            <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                    <button class="add-day-btn-dashed" onclick="addDayRow()">
                        <i class="bi bi-plus"></i> Add Another Day
                    </button>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save Holiday',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: isDark ? '#555572' : '#6c757d',
        preConfirm: () => {
            const name = document.getElementById('swal-holiday-name').value.trim();
            const dateInputs = document.querySelectorAll('.holiday-date');
            const hoursInputs = document.querySelectorAll('.holiday-hours');

            const days = [];
            dateInputs.forEach((d, i) => {
                if (d.value) days.push({ date: d.value, hours: hoursInputs[i].value || 8 });
            });

            if (!name) { Swal.showValidationMessage('Please enter a holiday name.'); return false; }
            if (days.length === 0) { Swal.showValidationMessage('Please add at least one day off.'); return false; }

            return fetch('save_holiday.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, days })
            })
            .then(res => res.json())
            .catch(err => Swal.showValidationMessage(`Error: ${err}`));
        }
    }).then(result => {
        if (result.isConfirmed && result.value?.success) {
            Swal.fire({
                title: 'Holiday Added!',
                icon: 'success',
                background: isDark ? '#2a2a3d' : '#fff',
                color: isDark ? '#e0e0e0' : '#1a1a1a',
                confirmButtonColor: '#2563eb',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        }
    });
});

function addDayRow() {
    const container = document.getElementById('days-container');
    const row = document.createElement('div');
    row.className = 'day-row';
    row.innerHTML = `
        <input type="date" class="form-control holiday-date" style="flex:2;">
        <input type="number" class="form-control holiday-hours" placeholder="Hrs" value="8" min="1" max="24" style="flex:1;">
        <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
            <i class="bi bi-x-circle"></i>
        </button>
    `;
    container.appendChild(row);
}

function addEditDayRow() {
    const container = document.getElementById('edit-days-container');
    const row = document.createElement('div');
    row.className = 'day-row';
    row.innerHTML = `
        <input type="date" class="form-control edit-holiday-date" style="flex:2;">
        <input type="number" class="form-control edit-holiday-hours" placeholder="Hrs" value="8" min="1" max="24" style="flex:1;">
        <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
            <i class="bi bi-x-circle"></i>
        </button>
    `;
    container.appendChild(row);
}

document.querySelectorAll('.edit-holiday-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const name = this.dataset.name;
        const days = JSON.parse(this.dataset.days);
        const isDark = document.body.classList.contains('dark-mode');

        const existingDaysHtml = days.map(day => `
            <div class="day-row" data-id="${day.id}">
                <input type="date" class="form-control edit-holiday-date" value="${day.date}" style="flex:2;">
                <input type="number" class="form-control edit-holiday-hours" value="${day.hours}" min="1" max="24" style="flex:1;">
                <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `).join('');

        Swal.fire({
            title: 'Edit Holiday',
            background: isDark ? '#2a2a3d' : '#fff',
            color: isDark ? '#e0e0e0' : '#1a1a1a',
            width: '520px',
            html: `
                <div class="text-start">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Name</label>
                        <input type="text" id="edit-holiday-name" class="form-control" value="${name}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Days Off</label>
                        <div id="edit-days-container">${existingDaysHtml}</div>
                        <button class="add-day-btn-dashed" onclick="addEditDayRow()">
                            <i class="bi bi-plus"></i> Add Another Day
                        </button>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: isDark ? '#555572' : '#6c757d',
            preConfirm: () => {
                const newName = document.getElementById('edit-holiday-name').value.trim();
                if (!newName) { Swal.showValidationMessage('Please enter a holiday name.'); return false; }

                const rows = document.querySelectorAll('#edit-days-container .day-row');
                const updatedDays = [], newDays = [];

                rows.forEach(row => {
                    const id = row.dataset.id;
                    const date = row.querySelector('.edit-holiday-date').value;
                    const hours = row.querySelector('.edit-holiday-hours').value;
                    if (!date) return;
                    if (id) updatedDays.push({ id, date, hours });
                    else newDays.push({ date, hours });
                });

                const updatedIds = updatedDays.map(d => String(d.id));
                const deletedIds = days.filter(d => !updatedIds.includes(String(d.id))).map(d => d.id);

                if (updatedDays.length + newDays.length === 0) {
                    Swal.showValidationMessage('Please add at least one day off.');
                    return false;
                }

                return fetch('update_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ originalName: name, newName, updatedDays, newDays, deletedIds })
                })
                .then(res => res.json())
                .catch(err => Swal.showValidationMessage(`Error: ${err}`));
            }
        }).then(result => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire({
                    title: 'Updated!',
                    icon: 'success',
                    background: isDark ? '#2a2a3d' : '#fff',
                    color: isDark ? '#e0e0e0' : '#1a1a1a',
                    confirmButtonColor: '#2563eb',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            }
        });
    });
});

document.querySelectorAll('.delete-holiday-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const name = this.dataset.name;
        const isDark = document.body.classList.contains('dark-mode');

        Swal.fire({
            title: 'Delete Holiday?',
            text: `This will remove all days for "${name}".`,
            icon: 'warning',
            background: isDark ? '#2a2a3d' : '#fff',
            color: isDark ? '#e0e0e0' : '#1a1a1a',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: isDark ? '#555572' : '#6c757d',
            confirmButtonText: 'Yes, delete it'
        }).then(result => {
            if (result.isConfirmed) {
                fetch('delete_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                })
                .then(res => res.json())
                .then(data => { if (data.success) location.reload(); });
            }
        });
    });
});
<?php endif; ?>
</script>
</body>
</html>