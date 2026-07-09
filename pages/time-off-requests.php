<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Time Off Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Time Off Requests</h3>
            <p class="mb-0">Review and respond to employee time off requests</p>
        </div>
    </div>

    <div class="tor-tabs">
        <div class="tor-tab active" data-tor-tab="pending">
            Awaiting Approval <span class="count-chip" id="torPendingCount">0</span>
        </div>
        <div class="tor-tab" data-tor-tab="all">
            All Requests <span class="count-chip" id="torAllCount">0</span>
        </div>
    </div>

    <div class="client-table-shell">
        <div class="client-table-scroll">
            <table class="client-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Dates</th>
                        <th>Category</th>
                        <th class="num">Hours</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody id="torTableBody">
                    <tr><td colspan="7" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewTimeOffModal" tabindex="-1" aria-labelledby="reviewTimeOffModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="ud-hero" style="padding-bottom: 18px;">
          <div class="ud-header" style="margin-bottom: 0;">
            <div class="ud-avatar" id="rtoAvatar"></div>
            <div>
              <div class="ud-name" id="rtoName"></div>
              <div class="ud-pills" style="margin-top: 4px;">
                <span class="category-pill" id="rtoCategory"></span>
                <span class="eng-status-pill" id="rtoStatus"><span class="dot"></span><span id="rtoStatusText"></span></span>
              </div>
            </div>
          </div>
        </div>

        <div class="eng-edit-body">
          <div class="stat-row">
            <div class="stat-card">
              <div class="stat-title">Total Hours</div>
              <div class="stat-value" id="rtoTotalHours"></div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Requested</div>
              <div class="stat-value" id="rtoRequested" style="font-size:13px;"></div>
            </div>
          </div>

          <div class="detail-section-title">Days</div>
          <div class="eng-vm-emp-list" id="rtoDaysList" style="margin-bottom:16px;"></div>

          <div class="detail-section-title">Employee's Reason</div>
          <p id="rtoReason" style="font-size:13px; margin-bottom:16px;"></p>

          <div id="rtoHistoryWrap" style="display:none;">
            <div class="detail-section-title">Notes</div>
            <div class="timeoff-comment-thread" id="rtoCommentHistory" style="margin-bottom:16px;"></div>
          </div>

          <div class="eng-edit-field" id="rtoCommentField">
            <label for="rtoComment">Comment</label>
            <textarea class="eng-edit-input" id="rtoComment" rows="2" placeholder="Add a note for the employee... (required to Request Changes)"></textarea>
          </div>
        </div>

        <div class="eng-edit-footer" id="rtoFooter" style="flex-wrap: wrap;">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
          <button type="button" class="rto-btn-changes" id="rtoChangesBtn" title="Request Changes">
            <i class="bi bi-arrow-repeat"></i> Request Changes
          </button>
          <button type="button" class="rto-btn-deny" id="rtoDenyBtn" title="Deny">
            <i class="bi bi-x-lg"></i> Deny
          </button>
          <button type="button" class="rto-btn-approve" id="rtoApproveBtn">
            <i class="bi bi-check-lg"></i> Approve
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/time_off_requests_admin.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
