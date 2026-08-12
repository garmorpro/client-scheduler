<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

// AUDIT_CALENDAR_DISABLED — Garrett asked to hide this page from everyone,
// including admin, until further notice (2026-08-12). Feature is built and
// working, just not wanted live yet. To re-enable: delete this block.
// See docs/client-scheduler-migration-plan.md (Engagement Tracker repo,
// Phase 3 step 4) and the matching sidebar link in templates/sidebar.php.
header("Location: my-schedule.php");
exit();

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

if (!user_has_permission($conn, 'view_audit_timeline')) {
    header("Location: my-schedule.php");
    exit();
}

// Same rule my-schedule.php already uses for the View Engagement modal's
// budgeted/allocated hours display.
$userRole = strtolower($_SESSION['user_role'] ?? '');
$restrictEngagementFinancials = in_array($userRole, ['staff', 'senior'], true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Calendar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Audit Calendar</h3>
    <p class="text-muted mb-4">Every key date and milestone across your engagements, one month at a time.</p>

    <div class="cal-toolbar">
        <button class="cal-nav-btn" id="calPrevBtn"><i class="bi bi-chevron-left"></i></button>
        <button class="cal-nav-btn" id="calNextBtn"><i class="bi bi-chevron-right"></i></button>
        <div class="cal-month-label" id="calMonthLabel"></div>
        <button class="cal-today-btn" id="calTodayBtn">Today</button>
        <div class="cal-legend">
            <div class="cal-legend-item"><span class="dot" style="background:#c0392b"></span> Overdue</div>
            <div class="cal-legend-item"><span class="dot" style="background:#d99a2b"></span> This week</div>
            <div class="cal-legend-item"><span class="dot" style="background:var(--primary-color)"></span> Upcoming</div>
            <div class="cal-legend-item"><span class="dot" style="background:#2f9e57"></span> Completed</div>
            <div class="cal-legend-item"><span class="dot" style="background:rgb(155,107,214)"></span> Weekly Call</div>
        </div>
    </div>

    <div class="cal-grid">
        <div class="cal-weekdays">
            <div class="cal-weekday">Sun</div><div class="cal-weekday">Mon</div><div class="cal-weekday">Tue</div>
            <div class="cal-weekday">Wed</div><div class="cal-weekday">Thu</div><div class="cal-weekday">Fri</div>
            <div class="cal-weekday">Sat</div>
        </div>
        <div class="cal-weeks" id="calWeeks">
            <div class="text-center text-muted py-5">Loading&hellip;</div>
        </div>
    </div>
</div>

<div class="day-popover-scrim" id="dayPopoverScrim">
    <div class="day-popover">
        <div class="day-popover-header">
            <h3 id="dayPopoverTitle"></h3>
            <button type="button" class="day-popover-close" id="dayPopoverCloseBtn">&times;</button>
        </div>
        <div class="day-popover-body" id="dayPopoverBody"></div>
    </div>
</div>

<script>window.restrictEngagementFinancials = <?php echo $restrictEngagementFinancials ? 'true' : 'false'; ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once '../includes/modals/view_engagement_modal.php'; ?>
<script src="../assets/js/view_engagement_modal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/audit_calendar.js?v=<?php echo time(); ?>"></script>
</body>
</html>
