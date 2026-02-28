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

// Fetch holidays grouped by holiday_name
$holidays = [];
$sql = "SELECT * FROM time_off WHERE is_global_timeoff = 1 ORDER BY week_start ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $holidays[] = $row;
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
            max-width: 860px;
            margin: 0 auto;
        }

        .holidays-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .holidays-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .holidays-icon-bubble {
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

        .holiday-card:hover {
            background: #f3f4f6;
        }

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

        .holiday-card-info {
            flex-grow: 1;
        }

        .holiday-card-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .holiday-card-meta {
            font-size: 12px;
            color: #6b7280;
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

        .btn-add-holiday:hover {
            background: #1d4ed8;
        }

        /* Dark mode */
        body.dark-mode .holiday-card {
            background: #2a2a3d;
            border-color: #3a3a50;
        }

        body.dark-mode .holiday-card:hover {
            background: #33334a;
        }

        body.dark-mode .holiday-card-meta {
            color: #9ca3af;
        }

        /* Day pills inside expanded view */
        .day-pill {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 11px;
            font-weight: 500;
            margin: 2px 3px 2px 0;
        }

        body.dark-mode .day-pill {
            background: #1e3a5f;
            color: #93c5fd;
        }

        /* Swal day row */
        .day-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .day-row input {
            flex: 1;
        }

        .remove-day-btn {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 18px;
            padding: 0 4px;
        }

        #add-day-btn {
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

        #add-day-btn:hover {
            background: #eff6ff;
        }
    </style>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Company Holidays</h3>
    <p class="text-muted mb-4">Manage firm-wide holidays and closures</p>

    <div class="holidays-container">
        <div class="holidays-header">
            <div class="holidays-header-left">
                <div class="holidays-icon-bubble">
                    <i class="bi bi-calendar3"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:16px;">Company Holidays</div>
                    <div style="font-size:13px; color:#6b7280;">Manage firm-wide holidays and closures</div>
                </div>
            </div>
            <button class="btn-add-holiday" id="addHolidayBtn">
                <i class="bi bi-plus"></i> Add Holiday
            </button>
        </div>

        <div id="holidaysList">
            <?php if (empty($holidays)): ?>
                <div class="text-muted text-center py-5" id="emptyState">No holidays added yet.</div>
            <?php else: ?>
                <?php foreach ($holidays as $h): ?>
                <div class="holiday-card" data-id="<?= $h['id'] ?>">
                    <div class="holiday-card-icon">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div class="holiday-card-info">
                        <div class="holiday-card-name"><?= htmlspecialchars($h['timeoff_note'] ?? 'Holiday') ?></div>
                        <div class="holiday-card-meta">
                            <?= date('l, F j, Y', strtotime($h['week_start'])) ?> &bull; <?= $h['assigned_hours'] ?> hours off
                        </div>
                    </div>
                    <div class="holiday-card-actions">
    <?php if ($isAdmin): ?>
    <button class="btn btn-sm btn-outline-secondary edit-holiday-btn" 
        data-id="<?= $h['timeoff_id'] ?>"
        data-name="<?= htmlspecialchars($h['timeoff_note'] ?? '') ?>"
        data-date="<?= $h['week_start'] ?>"
        data-hours="<?= $h['assigned_hours'] ?>">
        <i class="bi bi-pencil"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger delete-holiday-btn" data-id="<?= $h['timeoff_id'] ?>">
        <i class="bi bi-trash"></i>
    </button>
    <?php endif; ?>
</div>
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
                    <input type="number" class="form-control holiday-hours" placeholder="Hours" value="8" min="1" max="24" style="flex:1;">
                    <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </div>
            </div>
            <button id="add-day-btn" onclick="addDayRow()">
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
        if (d.value) {
            days.push({ date: d.value, hours: hoursInputs[i].value || 8 });
        }
    });

    if (!name) {
        Swal.showValidationMessage('Please enter a holiday name.');
        return false;
    }
    if (days.length === 0) {
        Swal.showValidationMessage('Please add at least one day off.');
        return false;
    }

    return fetch('save_holiday.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, days })
    })
    .then(res => res.json())
    .catch(err => Swal.showValidationMessage(`Error: ${err}`));
}
    }).then(result => {
        if (result.isConfirmed && result.value && result.value.success) {
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
        <input type="number" class="form-control holiday-hours" placeholder="Hours" value="8" min="1" max="24" style="flex:1;">
        <button class="remove-day-btn" onclick="this.closest('.day-row').remove()">
            <i class="bi bi-x-circle"></i>
        </button>
    `;
    container.appendChild(row);
}

// Delete holiday
document.querySelectorAll('.delete-holiday-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const isDark = document.body.classList.contains('dark-mode');

        Swal.fire({
            title: 'Delete Holiday?',
            text: 'This will remove this holiday day for all employees.',
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
                    body: JSON.stringify({ id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                });
            }
        });
    });
});

document.querySelectorAll('.edit-holiday-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const date = this.dataset.date;
        const hours = this.dataset.hours;
        const isDark = document.body.classList.contains('dark-mode');

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
                        <label class="form-label fw-semibold">Day Off</label>
                        <div class="day-row">
                            <input type="date" id="edit-holiday-date" class="form-control" value="${date}" style="flex:2;">
                            <input type="number" id="edit-holiday-hours" class="form-control" value="${hours}" min="1" max="24" style="flex:1;">
                        </div>
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
                const newDate = document.getElementById('edit-holiday-date').value;
                const newHours = document.getElementById('edit-holiday-hours').value;

                if (!newName) {
                    Swal.showValidationMessage('Please enter a holiday name.');
                    return false;
                }
                if (!newDate) {
                    Swal.showValidationMessage('Please select a date.');
                    return false;
                }

                return fetch('update_holiday.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, name: newName, date: newDate, hours: newHours })
                })
                .then(res => res.json())
                .catch(err => Swal.showValidationMessage(`Error: ${err}`));
            }
        }).then(result => {
            if (result.isConfirmed && result.value && result.value.success) {
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
</script>
</body>
</html>