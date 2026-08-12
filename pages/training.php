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

if ($canManageDol) {
    $usersql = "SELECT user_id, full_name, role FROM users WHERE role IN ('manager','senior','staff','intern') ORDER BY FIELD(role, 'manager','senior','staff','intern'), full_name ASC";
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Training</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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

    <div class="container-fluid">
        <div class="user-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Not Yet Trained On</th>
                        <?php if ($canManageDol): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="trainingTableBody">
                    <?php foreach ($roster as $person): ?>
                    <tr data-user-id="<?php echo $person['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($person['full_name']); ?>" data-role="<?php echo htmlspecialchars($person['role']); ?>">
                        <td>
                            <span class="emp-name-wrap">
                                <span class="tr-avatar" style="background-color:<?php echo htmlspecialchars(role_color($person['role'])); ?>;"><?php echo htmlspecialchars(avatar_initials($person['full_name'])); ?></span>
                                <?php echo htmlspecialchars($person['full_name']); ?>
                            </span>
                        </td>
                        <td><span class="badge-role"><?php echo htmlspecialchars(role_label($person['role'])); ?></span></td>
                        <td class="tr-restricted-cell">
                            <?php if (empty($person['restricted'])): ?>
                                <span class="text-muted" style="font-size:12.5px;">Fully trained</span>
                            <?php else: ?>
                                <?php foreach ($person['restricted'] as $criterion): ?>
                                    <span class="tr-chip"><?php echo htmlspecialchars($criterion); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <?php if ($canManageDol): ?>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-secondary tr-edit-btn" title="Edit training status">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<?php if ($canManageDol): ?>
<script src="../assets/js/training.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
</body>
</html>
