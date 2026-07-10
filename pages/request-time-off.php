<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$userId = $_SESSION['user_id'];

// ------------------------------------------------------
// Time off stats for dashboard tiles (same queries as My Schedule)
$sqlPendingTO = "
    SELECT COUNT(DISTINCT COALESCE(request_group, CONCAT('single-', timeoff_id))) AS cnt
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0 AND status IN ('pending', 'changes_requested')
";
$stmt = $conn->prepare($sqlPendingTO);
$stmt->bind_param('i', $userId);
$stmt->execute();
$pendingTimeOffCount = (int) $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$sqlUpcomingTO = "
    SELECT MIN(holiday_date) AS next_date
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0 AND status = 'approved' AND holiday_date >= CURDATE()
";
$stmt = $conn->prepare($sqlUpcomingTO);
$stmt->bind_param('i', $userId);
$stmt->execute();
$upcomingTimeOffDate = $stmt->get_result()->fetch_assoc()['next_date'];
$stmt->close();

$sqlYearTO = "
    SELECT COALESCE(SUM(assigned_hours), 0) AS total_hours
    FROM time_off
    WHERE user_id = ? AND is_global_timeoff = 0 AND status = 'approved' AND YEAR(holiday_date) = YEAR(CURDATE())
";
$stmt = $conn->prepare($sqlYearTO);
$stmt->bind_param('i', $userId);
$stmt->execute();
$yearTimeOffHours = floatval($stmt->get_result()->fetch_assoc()['total_hours']);
$stmt->close();
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
            <a href="#" id="newTimeOffRequestBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom">
                <i class="bi bi-plus-lg me-3"></i>New Request
            </a>
        </div>
    </div>

    <div class="ms-stat-row">
        <div class="ms-stat-card">
            <div class="ms-stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="ms-stat-title">Pending Time Off</div>
            <div class="ms-stat-value" id="statPendingValue"><?php echo $pendingTimeOffCount; ?></div>
            <div class="ms-stat-sub" id="statPendingSub"><?php echo $pendingTimeOffCount > 0 ? ($pendingTimeOffCount === 1 ? 'request needs your attention' : 'requests need your attention') : 'nothing awaiting action'; ?></div>
        </div>
        <div class="ms-stat-card accent">
            <div class="ms-stat-icon"><i class="bi bi-airplane-fill"></i></div>
            <div class="ms-stat-title">Upcoming Time Off</div>
            <div class="ms-stat-value" id="statUpcomingValue"><?php echo $upcomingTimeOffDate ? date('M j', strtotime($upcomingTimeOffDate)) : 'None'; ?></div>
            <div class="ms-stat-sub" id="statUpcomingSub"><?php echo $upcomingTimeOffDate ? 'next approved day off' : 'no approved time off scheduled'; ?></div>
        </div>
        <div class="ms-stat-card">
            <div class="ms-stat-icon"><i class="bi bi-calendar2-check-fill"></i></div>
            <div class="ms-stat-title">Taken This Year</div>
            <div class="ms-stat-value" id="statYearValue"><?php echo $yearTimeOffHours; ?> hrs</div>
            <div class="ms-stat-sub" id="statYearSub">approved &amp; taken in <?php echo date('Y'); ?></div>
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
                        <th>Dates</th>
                        <th>Category</th>
                        <th class="num">Hours</th>
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
            <div class="eng-edit-field">
              <label for="tor_category">Category</label>
              <select class="eng-edit-input" id="tor_category" name="category" required>
                <option value="vacation">Vacation</option>
                <option value="sick">Sick</option>
                <option value="parental">Parental</option>
                <option value="volunteer">Volunteer</option>
              </select>
            </div>

            <div class="hol-days-label">
              <span>Days</span>
              <button type="button" class="hol-add-day" id="torAddDayBtn">
                <i class="bi bi-plus"></i> Add Another Day
              </button>
            </div>
            <div id="torDaysContainer"></div>

            <div class="eng-edit-field" style="margin-top:14px;">
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

<div class="modal fade" id="viewMyTimeOffModal" tabindex="-1" aria-labelledby="vmtoTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
    <div class="modal-content">
      <div class="modal-body position-relative p-0">
        <button type="button" class="btn-close emp-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>

        <div class="ud-hero" style="padding-bottom: 18px;">
          <div class="ud-header" style="margin-bottom: 0;">
            <div class="ud-avatar" id="vmtoIcon"><i class="bi bi-airplane"></i></div>
            <div>
              <div class="ud-name" id="vmtoTitle">Time Off Request</div>
              <div class="ud-pills" style="margin-top: 4px;">
                <span class="category-pill" id="vmtoCategory"></span>
                <span class="eng-status-pill" id="vmtoStatus"><span class="dot"></span><span id="vmtoStatusText"></span></span>
              </div>
            </div>
          </div>
        </div>

        <div class="eng-edit-body">
          <div class="stat-row" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
              <div class="stat-title">Total Hours</div>
              <div class="stat-value" id="vmtoTotalHours"></div>
            </div>
            <div class="stat-card">
              <div class="stat-title">Submitted</div>
              <div class="stat-value" id="vmtoSubmitted" style="font-size:13px;"></div>
            </div>
          </div>

          <div class="detail-section-title">Days</div>
          <div class="eng-vm-emp-list" id="vmtoDaysList" style="margin-bottom:16px;"></div>

          <div class="detail-section-title">Your Reason</div>
          <p id="vmtoReason" style="font-size:13px; margin-bottom:16px;"></p>

          <div id="vmtoCommentWrap" style="display:none;">
            <div class="detail-section-title">Notes</div>
            <div class="timeoff-comment-thread" id="vmtoCommentHistory"></div>
          </div>
        </div>

        <div class="eng-edit-footer" id="vmtoFooter">
          <button type="button" class="eng-edit-btn-cancel" data-bs-dismiss="modal">Close</button>
          <button type="button" class="timeoff-cancel-inline-btn" id="vmtoCancelBtn">
            <i class="bi bi-x-lg"></i> Cancel Request
          </button>
          <button type="button" class="eng-edit-btn-save" id="vmtoEditBtn">
            <i class="bi bi-pencil-square"></i> Edit &amp; Resubmit
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script>window.CURRENT_USER_NAME = <?= json_encode($_SESSION['full_name'] ?? 'You') ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/request_time_off.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
