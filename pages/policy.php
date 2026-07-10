<?php
date_default_timezone_set('America/Chicago');
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/avatar_helpers.php';
require_once __DIR__ . '/../includes/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$canManagePolicies = user_has_permission($conn, 'access_system_settings');

$policyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$policyId) {
    header("Location: policies.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT p.policy_id, p.title, p.content, p.created_at, p.updated_at,
           cu.full_name AS created_by_name, uu.full_name AS updated_by_name
    FROM policies p
    LEFT JOIN users cu ON p.created_by = cu.user_id
    LEFT JOIN users uu ON p.updated_by = uu.user_id
    WHERE p.policy_id = ?
");
$stmt->bind_param('i', $policyId);
$stmt->execute();
$policy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$policy) {
    header("Location: policies.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($policy['title']); ?> - Policies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <?php if ($canManagePolicies): ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<div class="policy-print-only">
    <h1><?php echo htmlspecialchars($policy['title']); ?></h1>
    <div class="policy-print-meta">Last updated <?php echo date('F j, Y', strtotime($policy['updated_at'])); ?><?php echo !empty($policy['updated_by_name']) ? ' by ' . htmlspecialchars($policy['updated_by_name']) : ''; ?></div>
    <div class="policy-print-content"><?php echo $policy['content']; ?></div>
</div>

<div class="policy-screen-only d-flex w-100">
<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <a href="policies.php" class="policy-back-link"><i class="bi bi-arrow-left"></i> All Policies</a>

    <div class="policy-detail-head">
        <div>
            <h3 class="mb-1"><?php echo htmlspecialchars($policy['title']); ?></h3>
            <p class="mb-0 text-muted" style="font-size: 12.5px;">
                Last updated <?php echo date('F j, Y', strtotime($policy['updated_at'])); ?>
                <?php if (!empty($policy['updated_by_name'])): ?>
                    by <?php echo htmlspecialchars($policy['updated_by_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="downloadPolicyPdfBtn" class="badge p-2 text-decoration-none fw-medium btn-outline-custom">
                <i class="bi bi-download me-2"></i>Download PDF
            </button>
            <?php if ($canManagePolicies): ?>
            <button type="button" id="editPolicyBtn" class="badge p-2 text-decoration-none fw-medium btn-outline-custom"
                data-policy-id="<?php echo $policy['policy_id']; ?>"
                data-policy-title="<?php echo htmlspecialchars($policy['title']); ?>">
                <i class="bi bi-pencil-square me-2"></i>Edit
            </button>
            <button type="button" id="deletePolicyBtn" class="badge p-2 text-decoration-none fw-medium btn-outline-danger-custom"
                data-policy-id="<?php echo $policy['policy_id']; ?>">
                <i class="bi bi-trash me-2"></i>Delete
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="policy-detail-body">
        <?php echo $policy['content']; ?>
    </div>
</div>
</div>

<?php if ($canManagePolicies): ?>
<div id="editPolicyContentSeed" style="display:none;"><?php echo $policy['content']; ?></div>
<?php include_once '../includes/modals/policy_modal.php'; ?>
<?php endif; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($canManagePolicies): ?>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/policy_modal.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/policy_detail.js?v=<?php echo time(); ?>"></script>
</body>
</html>
