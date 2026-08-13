<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

if (!user_has_permission($conn, 'manage_dol')) {
    header("Location: my-schedule.php");
    exit();
}

// Engagement picker, scoped to whoever's logged in: only engagements they
// personally have an entries row on (i.e. they're actually staffed there),
// not every engagement in the firm - admin is the only exception. The
// generator itself separately checks per-engagement whether it actually
// has a DOL-capable audit type and eligible team before letting anything
// be generated.
$isAdmin = strtolower($_SESSION['user_role'] ?? '') === 'admin';
$currentUserId = (int) $_SESSION['user_id'];

$engagements = [];
if ($isAdmin) {
    $res = $conn->query("SELECT engagement_id, client_name, year FROM engagements ORDER BY client_name ASC, year DESC");
} else {
    $stmt = $conn->prepare("
        SELECT DISTINCT e.engagement_id, e.client_name, e.year
        FROM engagements e
        JOIN entries en ON en.engagement_id = e.engagement_id
        WHERE en.user_id = ?
        ORDER BY e.client_name ASC, e.year DESC
    ");
    $stmt->bind_param('i', $currentUserId);
    $stmt->execute();
    $res = $stmt->get_result();
}
while ($row = $res->fetch_assoc()) {
    $engagements[] = $row;
}

// Optional deep-link from the View Engagement panel's "Manage Team" link
// (?engagement_id=X). Only pre-selects if it's actually in this user's own
// $engagements list above, so the scoping (admin vs. staffed-only) still
// holds - a non-admin can't get linked into an engagement they're not on.
$preselectEngagementId = isset($_GET['engagement_id']) ? (int) $_GET['engagement_id'] : 0;
// array_map('intval', ...) here matters - mysqli can hand back engagement_id
// as a numeric string depending on driver config, and in_array's strict
// (true) mode would then never match an int against "57", silently
// resetting the preselect to 0 even though the id really is in the list.
$engagementIds = array_map('intval', array_column($engagements, 'engagement_id'));
if ($preselectEngagementId && !in_array($preselectEngagementId, $engagementIds, true)) {
    $preselectEngagementId = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DOL Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px; max-width: 900px;">
    <h3 class="mb-0">DOL Generator</h3>
    <p class="text-muted mb-4">Split criteria across your team by hours</p>

    <!-- Step 1: Engagement -->
    <div class="card dolgen-card mb-3">
        <div class="card-body">
            <h6 class="dolgen-step-title"><span class="dolgen-step-num">1</span>Engagement</h6>
            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size:10.5px; letter-spacing:.05em;">Select Engagement</label>
            <select class="form-select" id="engagementSelect">
                <option value="">Select an engagement&hellip;</option>
                <?php foreach ($engagements as $eng): ?>
                    <option value="<?php echo (int) $eng['engagement_id']; ?>" <?php echo ($preselectEngagementId === (int) $eng['engagement_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($eng['client_name']); ?> &mdash; <?php echo (int) $eng['year']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div id="setupSections" class="d-none">
        <!-- Step 2: Audit Type -->
        <div class="card dolgen-card mb-3">
            <div class="card-body">
                <h6 class="dolgen-step-title"><span class="dolgen-step-num">2</span>Audit Type</h6>
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size:10.5px; letter-spacing:.05em;">Which DOL does this split apply to?</label>
                <div class="dolgen-pills" id="auditTypePills"></div>
                <div class="form-text">Only audit types assigned to this engagement show up here.</div>
            </div>
        </div>

        <!-- Step 3: Team Hours -->
        <div class="card dolgen-card mb-3">
            <div class="card-body">
                <h6 class="dolgen-step-title"><span class="dolgen-step-num">3</span>Team Hours</h6>
                <div id="teamHoursList"></div>
                <div class="form-text">Prefilled from this engagement's scheduled hours &mdash; edit as needed, just used to compute this split.</div>
            </div>
        </div>

        <!-- Step 4: Criteria -->
        <div class="card dolgen-card mb-3">
            <div class="card-body">
                <h6 class="dolgen-step-title"><span class="dolgen-step-num">4</span>Criteria to Split</h6>
                <label class="form-label small fw-bold text-muted text-uppercase" style="font-size:10.5px; letter-spacing:.05em;">Criteria (comma or line separated)</label>
                <textarea class="form-control" id="criteriaInput" rows="3"></textarea>
                <div class="form-text" id="criteriaHint"></div>

                <label class="form-label small fw-bold text-muted text-uppercase mt-3" style="font-size:10.5px; letter-spacing:.05em;">Weight Each Criterion</label>
                <div id="weightEditorList"></div>
                <div class="form-text">Higher weight = bigger/denser section. The split matches each person's share of total <em>weight</em> to their share of hours, handing out items one at a time to whoever's furthest under target.</div>
            </div>
        </div>

        <div id="genErrorBox" class="alert alert-danger d-none py-2 small"></div>
        <button class="btn dolgen-btn-primary w-100" id="generateBtn"><i class="bi bi-magic"></i> Generate Split</button>
    </div>

    <!-- Results -->
    <div id="resultSection" class="d-none">
        <hr>
        <div id="resultSummary" class="text-muted small mb-3"></div>
        <div id="unassignableWarning" class="alert alert-danger d-none py-2 small"></div>
        <div id="resultMembers"></div>
        <div class="alert alert-warning d-flex gap-2 align-items-start py-2 small">
            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
            <div id="saveWarningText"></div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary flex-fill" id="backToEditBtn">Back &amp; Adjust</button>
            <button class="btn dolgen-btn-primary flex-fill" id="saveBtn"><i class="bi bi-check-lg"></i> Save to Engagement</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app_alerts.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/dol_generator.js?v=<?php echo time(); ?>"></script>
</body>
</html>
