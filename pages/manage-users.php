<?php
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

// Fetch users from database
$users = [];
$userSQL = "SELECT user_id, full_name, email, role, status, job_title, created_at, last_active 
            FROM users ORDER BY full_name ASC";
$userResult = mysqli_query($conn, $userSQL);
if ($userResult) {
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
}
$totalUsers = count($users);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">

   <!-- Grid.js CSS & JS at the top of <body> or in <head> -->
<link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
<script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>

   

    <style>
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        .header-bar .left {
            font-weight: 600;
        }
        .header-bar .right .btn {
            margin-left: 0.5rem;
        }
        table th, table td {
            vertical-align: middle;
        }
        .name-cell .job-title {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .action-dropdown .dropdown-menu {
            min-width: 120px;
        }
        .pagination-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        #userSearch {
    width: 200px;
}
    </style>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <!-- Page Title -->
    <h3 class="mb-1">Manage Users</h3>
    <p class="text-muted mb-4">View and manage all users in the system</p>

    <!-- Header Bar -->
    <div class="header-bar">
        <div class="left"><?= $totalUsers ?> Users</div>
        <div class="right d-flex align-items-center gap-2 flex-nowrap">
            <input type="text" id="userSearch" class="form-control form-control-sm" placeholder="Search users">
            <div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm"
        id="roleFilterBtn"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside">
    <i class="bi bi-filter"></i>
</button>

    <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 200px;">
        
        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="admin" id="roleAdmin" checked>
            <label class="form-check-label" for="roleAdmin">Admin</label>
        </div>

        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="manager" id="roleManager" checked>
            <label class="form-check-label" for="roleManager">Manager</label>
        </div>


        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="senior" id="roleSenior" checked>
            <label class="form-check-label" for="roleSenior">Senior</label>
        </div>

        <div class="form-check">
            <input class="form-check-input role-checkbox" type="checkbox" value="staff" id="roleStaff" checked>
            <label class="form-check-label" for="roleStaff">Staff</label>
        </div>

        <hr class="my-2">

        <button class="btn btn-sm btn-link p-0" id="clearRoles">Clear All</button>
    </div>
</div>
            <button id="openImportBtn" class="btn btn-outline-primary btn-sm">Import</button>
            <button class="btn btn-primary btn-sm">Invite User</button>
        </div>
    </div>


<!-- SweetAlert2 Preview & AJAX Script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    const openBtn = document.getElementById('openImportBtn');

    openBtn.addEventListener('click', function() {

        Swal.fire({
            title: 'Import Users (CSV)',
            html: `
                <p class="small text-muted">Upload a CSV file using the template format.</p>
                <a href="../assets/templates/bulk_import_user_template.csv" download class="btn btn-sm btn-link p-0 mb-3">
                    Download CSV Template
                </a>
                <div id="fileWrapper" style="border: 2px dashed #d1d5db; border-radius: 6px; padding: 20px; text-align:center; cursor:pointer; color:#6c757d;">
                    Click or drag CSV file here
                    <input type="file" id="csvFileInput" accept=".csv" style="display:none;">
                </div>
                <div id="csvPreview" style="margin-top:15px; max-height:200px; overflow:auto;"></div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Upload',
            cancelButtonText: 'Cancel',
            focusConfirm: false,
            width: '700px',
            didOpen: () => {
                const popup = Swal.getPopup();
                const fileInputEl = popup.querySelector('#csvFileInput');
                const previewEl = popup.querySelector('#csvPreview');
                const fileWrapper = popup.querySelector('#fileWrapper');

                // Open file dialog on click
                fileWrapper.addEventListener('click', () => fileInputEl.click());

                // Optional: drag & drop highlight
                fileWrapper.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    fileWrapper.style.borderColor = '#0d6efd';
                    fileWrapper.style.color = '#0d6efd';
                });
                fileWrapper.addEventListener('dragleave', () => {
                    fileWrapper.style.borderColor = '#d1d5db';
                    fileWrapper.style.color = '#6c757d';
                });
                fileWrapper.addEventListener('drop', (e) => {
                    e.preventDefault();
                    fileWrapper.style.borderColor = '#d1d5db';
                    fileWrapper.style.color = '#6c757d';
                    if (e.dataTransfer.files.length) {
                        fileInputEl.files = e.dataTransfer.files;
                        triggerPreview(fileInputEl.files[0]);
                    }
                });

                // File selection preview
                fileInputEl.addEventListener('change', function() {
                    if (!fileInputEl.files.length) {
                        previewEl.innerHTML = '';
                        return;
                    }
                    triggerPreview(fileInputEl.files[0]);
                });

                function triggerPreview(file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const text = event.target.result;
                        const lines = text.split(/\r\n|\n/).filter(l => l.trim() !== "");
                        if (lines.length < 2) {
                            previewEl.innerHTML = '<p class="text-muted">No data rows found.</p>';
                            return;
                        }
                        const headers = lines[0].split(',');
                        const rows = lines.slice(1);

                        let previewHtml = `<p><strong>Records found:</strong> ${rows.length}</p>`;
                        previewHtml += `<table class="table table-sm table-bordered"><thead><tr>`;
                        headers.forEach(h => previewHtml += `<th>${h.trim()}</th>`);
                        previewHtml += `</tr></thead><tbody>`;
                        rows.slice(0, 5).forEach(row => {
                            const cols = row.split(',');
                            previewHtml += '<tr>';
                            cols.forEach(c => previewHtml += `<td>${c.trim()}</td>`);
                            previewHtml += '</tr>';
                        });
                        previewHtml += '</tbody></table>';
                        if (rows.length > 5) previewHtml += `<p class="small text-muted">...and ${rows.length - 5} more rows</p>`;

                        previewEl.innerHTML = previewHtml;
                    };
                    reader.readAsText(file);
                }
            },
            preConfirm: () => {
                const popup = Swal.getPopup();
                const fileInputEl = popup.querySelector('#csvFileInput');
                if (!fileInputEl.files.length) {
                    Swal.showValidationMessage('Please select a CSV file');
                    return false;
                }

                const formData = new FormData();
                formData.append('csv_file', fileInputEl.files[0]);

                Swal.showLoading();

                return fetch('../../pages/import_users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .catch(err => {
                    Swal.showValidationMessage(`Request failed: ${err}`);
                });
            }
        }).then(result => {
            if (result.isConfirmed) {
                const data = result.value;
                let htmlMsg = `<p><strong>Successfully imported:</strong> ${data.successCount}</p>`;
                // Generic frontend-friendly error message
if (data.errors.length) {
    htmlMsg += `<p class="text-warning"><strong>Some rows could not be imported. Please check your CSV format and try again.</strong></p>`;
}

                Swal.fire({
                    title: 'Import Results',
                    html: htmlMsg,
                    icon: data.errors.length ? 'warning' : 'success',
                    confirmButtonText: 'OK'
                }).then(() => location.reload());
            }
        });

    });

});
</script>







<div id="usersGrid" class="mt-3"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const usersData = <?= json_encode(array_map(function($user) {
        return [
            '<input type="checkbox" class="user-checkbox" value="'. $user["user_id"] .'">',
            htmlspecialchars($user["full_name"]) . '<div class="job-title">' . htmlspecialchars($user["job_title"]) . '</div>',
            htmlspecialchars($user["email"]),
            htmlspecialchars($user["role"]),
            htmlspecialchars($user["status"]),
            date("Y-m-d", strtotime($user["created_at"])),
            date("Y-m-d", strtotime($user["last_active"])),
            '<div class="dropdown">' +
                '<a href="#" class="text-dark" role="button" data-bs-toggle="dropdown" aria-expanded="false">' +
                    '<i class="bi bi-three-dots-vertical"></i>' +
                '</a>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><a class="dropdown-item" href="#">Edit</a></li>' +
                    '<li><a class="dropdown-item" href="#">Deactivate</a></li>' +
                    '<li><a class="dropdown-item text-danger" href="#">Delete</a></li>' +
                '</ul>' +
            '</div>'
        ];
    }, $users), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    new gridjs.Grid({
        columns: [
            { name: "", width: "50px", formatter: (cell) => gridjs.html(cell) },
            { name: "Name", formatter: (cell) => gridjs.html(cell) },
            "Email",
            "Role",
            "Status",
            "Added Date",
            "Last Active",
            { name: "Actions", formatter: (cell) => gridjs.html(cell) }
        ],
        data: usersData,
        search: {
            selector: (cell) => cell.replace(/<[^>]+>/g, "")
        },
        sort: true,
        pagination: {
            enabled: true,
            limit: 10,
            summary: true
        },
        style: {
            table: { 'width': '100%', 'border-collapse': 'collapse' },
            th: { 'background-color': '#f8f9fa', 'text-align': 'center', 'padding': '8px' },
            td: { 'text-align': 'center', 'padding': '8px' }
        }
    }).render(document.getElementById("usersGrid"));
});
</script>

</div>


<?php include_once '../includes/modals/user_details.php'; ?>
    <?php include_once '../includes/modals/viewProfileModal.php'; ?>
    <?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
    
    <script src="../assets/js/dynamic_cell_input.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/drag_drop_function.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_custom_menu.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/timeoff_menu.js?v=<?php echo time(); ?>"></script>
    <?php if ($isAdmin): ?>
    <script src="../assets/js/employee_details.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
    <script src="../assets/js/filter_role.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search_manage_users.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/pagination_manage_users.js?v=<?php echo time(); ?>"></script>

    <script src="../assets/js/number_of_weeks.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/search.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/client_dropdown.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/show_entries.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/delete_entry.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/view_entry_modal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewUserModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/filter_employees.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/read_bulk_import_users.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/import_users.js?v=<?php echo time(); ?>"></script>

    
    <script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    

</body>
</html>