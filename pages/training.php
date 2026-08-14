<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

// manage_dol sees and edits everyone's training status. Anyone with just
// view_dol (which Staff/Intern get by default - see role_permissions) can
// still reach this page, but only ever sees their own row, read-only.
$canManageDol = user_has_permission($conn, 'manage_dol');
$canViewDol = user_has_permission($conn, 'view_dol');

if (!$canManageDol && !$canViewDol) {
    header("Location: my-schedule.php");
    exit();
}

$currentUserId = (int) $_SESSION['user_id'];

// Only Staff and Intern show up here - by the time someone's a Senior or
// Manager, training restrictions aren't tracked for them anymore (per
// Garrett). DOL Generator can still assign Senior/Manager work (see
// get-dol-setup.php), it just never checks a restriction list for them.
if ($canManageDol) {
    $usersql = "SELECT user_id, full_name, role FROM users WHERE role IN ('staff','intern') ORDER BY FIELD(role, 'staff','intern'), full_name ASC";
    $userresult = mysqli_query($conn, $usersql);
} else {
    $stmt = $conn->prepare("SELECT user_id, full_name, role FROM users WHERE user_id = ?");
    $stmt->bind_param('i', $currentUserId);
    $stmt->execute();
    $userresult = $stmt->get_result();
}

$roster = [];
$userIds = [];
while ($row = mysqli_fetch_assoc($userresult)) {
    $row['restricted'] = [];
    $roster[$row['user_id']] = $row;
    $userIds[] = $row['user_id'];
}

if (!empty($userIds)) {
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $types = str_repeat('i', count($userIds));
    $stmt = $conn->prepare("SELECT user_id, criterion FROM dol_training_restrictions WHERE user_id IN ($placeholders) ORDER BY criterion");
    $stmt->bind_param($types, ...$userIds);
    $stmt->execute();
    $restrictionResult = $stmt->get_result();
    while ($row = $restrictionResult->fetch_assoc()) {
        if (isset($roster[$row['user_id']])) {
            $roster[$row['user_id']]['restricted'][] = $row['criterion'];
        }
    }
    $stmt->close();
}

$totalCount = count($roster);
$restrictedCount = 0;
foreach ($roster as $person) {
    if (!empty($person['restricted'])) $restrictedCount++;
}
$fullyTrainedCount = $totalCount - $restrictedCount;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <h3 class="mb-0">Training</h3>
    <p class="text-muted mb-4">
        <?php echo $canManageDol
            ? 'Criteria someone hasn\'t completed training on yet - the DOL Generator won\'t assign these to them until cleared here.'
            : 'Your own training progress. Ask a manager or senior to update this once you\'ve completed training on something.'; ?>
    </p>

    <?php if ($canManageDol): ?>
    <div class="tr-stat-row">
        <div class="eng-stat-card">
            <div class="eng-stat-title">Staff and Interns</div>
            <div class="eng-stat-value" id="trStatTotal"><?php echo $totalCount; ?></div>
        </div>
        <div class="eng-stat-card">
            <div class="eng-stat-title">Fully Trained</div>
            <div class="eng-stat-value tr-stat-good" id="trStatTrained"><?php echo $fullyTrainedCount; ?></div>
        </div>
        <div class="eng-stat-card">
            <div class="eng-stat-title">With Restrictions</div>
            <div class="eng-stat-value tr-stat-warn" id="trStatRestricted"><?php echo $restrictedCount; ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($roster)): ?>
        <div class="ms-empty-week">
            <i class="bi bi-mortarboard"></i>
            <div class="t">Nothing to show</div>
            <div><?php echo $canManageDol ? 'No Staff or Interns yet.' : "Training tracking doesn't apply to your role."; ?></div>
        </div>
    <?php else: ?>
    <div class="tr-list" id="trainingList">
        <?php foreach ($roster as $person):
            $restricted = $person['restricted'];
        ?>
        <div class="tr-row" data-user-id="<?php echo $person['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($person['full_name']); ?>" data-role="<?php echo htmlspecialchars($person['role']); ?>" data-restricted="<?php echo htmlspecialchars(implode(',', $restricted)); ?>">
            <span class="tr-avatar" style="background-color:<?php echo htmlspecialchars(role_color($person['role'])); ?>;"><?php echo htmlspecialchars(avatar_initials($person['full_name'])); ?></span>
            <div class="tr-row-main">
                <div class="tr-row-name"><?php echo htmlspecialchars($person['full_name']); ?></div>
                <div class="tr-row-role"><?php echo htmlspecialchars(role_label($person['role'])); ?></div>
            </div>
            <div class="tr-row-status">
                <?php if (empty($restricted)): ?>
                    <span class="eng-status-pill confirmed"><span class="dot"></span>Fully trained</span>
                <?php else: ?>
                    <span class="eng-status-pill denied"><span class="dot"></span><?php echo count($restricted); ?> restricted</span>
                    <span class="tr-row-restricted-list"><?php echo htmlspecialchars(implode(', ', $restricted)); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($canManageDol): ?>
            <button type="button" class="client-icon-btn edit tr-edit-btn" title="Edit training status">
                <i class="bi bi-pencil"></i>
            </button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($canManageDol): ?>
<?php include_once '../includes/modals/training_status_modal.php'; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app_alerts.js?v=<?php echo time(); ?>"></script>
<?php if ($canManageDol): ?>
<script src="../assets/js/training.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
</body>
</html>
