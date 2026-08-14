<?php
require_once __DIR__ . '/../includes/session_init.php';
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/engagement_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$isAdmin = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'admin';
$isServiceAccount = isset($_SESSION['user_role']) && strtolower($_SESSION['user_role']) === 'service_account';

if ($isAdmin) {
    header("Location: master-schedule.php");
    exit();
}
if ($isServiceAccount) {
    header("Location: employees.php");
    exit();
}

// Same audience as My Schedule - this is "your own" data, not a
// clients/engagements management view.
if (!user_has_permission($conn, 'view_my_schedule')) {
    $fallback = user_has_permission($conn, 'view_clients_engagements') ? 'client-management.php' : 'policies.php';
    header("Location: $fallback");
    exit();
}

$userRole = strtolower($_SESSION['user_role'] ?? '');
$restrictEngagementFinancials = in_array($userRole, ['staff', 'senior'], true);
$userId = $_SESSION['user_id'];

// Every active engagement this person has ever logged hours to, regardless
// of week - unlike My Schedule (which is scoped to one week at a time),
// this is meant as a lookup list for planning/status calls: "what am I on
// and who's on it with me", not a scheduling view. Archiving deletes the
// engagements row itself (see archive_engagement.php), so nothing extra is
// needed here to exclude archived engagements - they're simply gone from
// this join already.
$engagementsQuery = "
    SELECT e.engagement_id, e.client_name, e.engagement_name, e.status, e.manager, SUM(en.assigned_hours) AS my_hours
    FROM entries en
    JOIN engagements e ON en.engagement_id = e.engagement_id
    WHERE en.user_id = ?
    GROUP BY e.engagement_id, e.client_name, e.engagement_name, e.status, e.manager
    ORDER BY FIELD(e.status, 'confirmed', 'pending', 'not_confirmed'), e.client_name ASC
";
$stmt = $conn->prepare($engagementsQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$myEngagements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Everyone else (not the viewer) who has ever logged hours to this
// engagement - all-time total hours (summed across every week), not scoped
// to a single week like My Schedule's own getTeamMembers(). Also includes
// the engagement's manager even if they've never logged an hour themselves -
// manager is a separate field on `engagements`, not derived from entries,
// same reasoning as My Schedule's - shown with no hours suffix, matching
// how My Schedule already displays a manager with nothing logged.
function getEngagementTeammates($conn, $engagement_id, $currentUserId, $managerName = null) {
    $stmt = $conn->prepare("
        SELECT u.full_name, SUM(e.assigned_hours) AS total_hours
        FROM entries e
        JOIN users u ON e.user_id = u.user_id
        WHERE e.engagement_id = ? AND e.user_id != ?
        GROUP BY u.user_id, u.full_name
        ORDER BY u.full_name ASC
    ");
    $stmt->bind_param('ii', $engagement_id, $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $members = [];
    $seenNames = [];
    while ($row = $res->fetch_assoc()) {
        // Bare name when there's nothing logged yet (e.g. someone staged
        // via Add Team Member with a placeholder 0-hour entry) - same
        // treatment the manager below already gets.
        $hours = (float) $row['total_hours'];
        $members[] = $hours > 0 ? $row['full_name'] . ' (' . $hours . ')' : $row['full_name'];
        $seenNames[strtolower($row['full_name'])] = true;
    }
    $stmt->close();

    $managerName = trim((string) $managerName);
    if ($managerName !== '' && !isset($seenNames[strtolower($managerName)]) && strcasecmp($managerName, $_SESSION['full_name'] ?? '') !== 0) {
        array_unshift($members, $managerName);
    }

    return $members;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Engagements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">
  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4" style="margin-left: 250px;">

    <!-- Page header -->
    <div class="ms-header">
      <div class="ms-who">
        <div class="ms-avatar"><?php echo htmlspecialchars(avatar_initials($_SESSION['full_name'] ?? '')); ?></div>
        <div>
          <h3>My Engagements</h3>
          <p class="ms-role-line">
            Quick reference for planning and status calls
            <span class="ms-role-chip"><?php echo htmlspecialchars(role_label($_SESSION['user_role'] ?? '')); ?></span>
          </p>
        </div>
      </div>
      <a href="#" onclick="location.reload(); return false;" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-clockwise me-2"></i> Refresh
      </a>
    </div>

    <div class="ms-detail-head" style="margin-top: 20px;">
      <div class="ms-title-group">
        <h5>Active Engagements</h5>
      </div>
      <div class="ms-summary-inline">
        <?php echo count($myEngagements); ?> engagement<?php echo count($myEngagements) === 1 ? '' : 's'; ?>
      </div>
    </div>

    <?php if (empty($myEngagements)): ?>
      <div class="ms-empty-week">
        <i class="bi bi-briefcase"></i>
        <div class="t">Not staffed on anything yet</div>
        <div>Engagements you're assigned to will show up here.</div>
      </div>
    <?php else: ?>
      <div class="ms-entry-list">
        <?php foreach ($myEngagements as $eng):
          $teammates = getEngagementTeammates($conn, $eng['engagement_id'], $userId, $eng['manager'] ?? null);
          $clientName = $eng['client_name'] ?? 'Unknown';
          // Combined label so this lookup list stays unambiguous when
          // staffed on more than one of a client's engagements - avatar
          // color/initials stay keyed to the client alone.
          $displayName = engagement_combined_label($clientName, $eng['engagement_name'] ?? null);
          $status = strtolower($eng['status'] ?? 'confirmed');
          $statusClass = in_array($status, ['confirmed', 'pending', 'not_confirmed'], true) ? str_replace('_', '-', $status) : 'confirmed';
          $statusLabel = $status === 'not_confirmed' ? 'Not Confirmed' : ucfirst($status);
        ?>
          <div class="ms-entry-row view-engagement-btn" role="button" tabindex="0"
               data-engagement-id="<?php echo $eng['engagement_id']; ?>"
               data-avatar-color="<?php echo avatar_color($clientName); ?>"
               data-initials="<?php echo htmlspecialchars(avatar_initials($clientName)); ?>"
               data-restrict-financials="<?php echo $restrictEngagementFinancials ? '1' : '0'; ?>">
            <div class="ms-entry-avatar" style="background-color:<?php echo avatar_color($clientName); ?>;"><?php echo htmlspecialchars(avatar_initials($clientName)); ?></div>
            <div class="ms-entry-main">
              <div class="ms-entry-name"><?php echo htmlspecialchars($displayName); ?></div>
              <div class="ms-entry-team">Team: <b><?php echo !empty($teammates) ? htmlspecialchars(implode(', ', $teammates)) : 'Just you'; ?></b></div>
            </div>
            <span class="eng-status-pill <?php echo $statusClass; ?>"><span class="dot"></span><?php echo htmlspecialchars($statusLabel); ?></span>
            <div class="ms-entry-hours"><?php echo $eng['my_hours']; ?>h</div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>
<?php include_once '../includes/modals/view_engagement_modal.php'; ?>

<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app_alerts.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/view_engagement_modal.js?v=<?php echo time(); ?>"></script>
</body>
</html>
