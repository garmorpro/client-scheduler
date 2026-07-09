<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request Time Off</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Request Time Off</h3>
            <p class="mb-0">Submit a request and track its approval status</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#" id="newTimeOffRequestBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom" data-bs-toggle="modal" data-bs-target="#requestTimeOffModal">
                <i class="bi bi-plus-lg me-3"></i>New Request
            </a>
        </div>
    </div>

    <div class="client-toolbar">
        <span class="client-toolbar-hint" id="myRequestsHint"></span>
    </div>

    <div class="client-table-shell">
        <div class="client-table-scroll">
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="num">Hours</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody id="myRequestsTableBody">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="requestTimeOffModal" tabindex="-1" aria-labelledby="requestTimeOffModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <form id="requestTimeOffForm">
        <div class="modal-body position-relative p-0">
          <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

          <div class="eng-edit-hero">
            <div class="eng-edit-title" id="requestTimeOffModalTitle">Request Time Off</div>
          </div>

          <div class="eng-edit-body">
            <div class="eng-edit-row">
              <div class="eng-edit-field">
                <label for="tor_date">Date</label>
                <input type="date" class="eng-edit-input" id="tor_date" name="date" required>
              </div>
              <div class="eng-edit-field">
                <label for="tor_hours">Hours</label>
                <input type="number" min="1" max="24" step="0.5" class="eng-edit-input" id="tor_hours" name="hours" value="8" required>
              </div>
            </div>
            <div class="eng-edit-field">
              <label for="tor_reason">Reason</label>
              <textarea class="eng-edit-input" id="tor_reason" name="reason" rows="3" placeholder="e.g. Personal day, appointment, vacation..."></textarea>
            </div>
          </div>

          <div class="eng-edit-footer">
            <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="eng-edit-btn-save">Submit Request</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/request_time_off.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
