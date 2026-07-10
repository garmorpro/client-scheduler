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
    SELECT p.policy_id, p.title, p.pdf_path, p.pdf_original_name, p.created_at, p.updated_at,
           uu.full_name AS updated_by_name
    FROM policies p
    LEFT JOIN users uu ON p.updated_by = uu.user_id
    WHERE p.policy_id = ?
");
$stmt->bind_param('i', $policyId);
$stmt->execute();
$policy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$policy || empty($policy['pdf_path'])) {
    header("Location: policies.php");
    exit();
}

$pdfUrl = '../assets/uploads/policies/' . rawurlencode($policy['pdf_path']);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($policy['title']); ?> - Policies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <?php if ($canManagePolicies): ?>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="policy-doc-card is-pdf">
        <div class="policy-head">
            <a href="policies.php" class="policy-head-back"><i class="bi bi-arrow-left"></i> All Policies</a>

            <div class="policy-head-top">
                <div>
                    <span class="policy-head-chip">PDF</span>
                    <h1 class="policy-head-title"><?php echo htmlspecialchars($policy['title']); ?></h1>
                    <div class="policy-head-meta muted">
                        Last updated <?php echo date('F j, Y', strtotime($policy['updated_at'])); ?>
                        <?php if (!empty($policy['updated_by_name'])): ?>
                            by <?php echo htmlspecialchars($policy['updated_by_name']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="policy-head-actions">
                    <a href="<?php echo $pdfUrl; ?>" download="<?php echo htmlspecialchars($policy['pdf_original_name']); ?>" class="policy-head-btn">
                        <i class="bi bi-download me-2"></i>Download PDF
                    </a>
                    <?php if ($canManagePolicies): ?>
                    <button type="button" id="editPolicyBtn" class="policy-head-btn"
                        data-policy-id="<?php echo $policy['policy_id']; ?>"
                        data-policy-title="<?php echo htmlspecialchars($policy['title']); ?>"
                        data-policy-pdf-name="<?php echo htmlspecialchars($policy['pdf_original_name'] ?? ''); ?>">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                    </button>
                    <button type="button" id="deletePolicyBtn" class="policy-head-btn danger"
                        data-policy-id="<?php echo $policy['policy_id']; ?>">
                        <i class="bi bi-trash me-2"></i>Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="policy-pdf-viewer">
            <iframe src="<?php echo $pdfUrl; ?>" title="<?php echo htmlspecialchars($policy['title']); ?>"></iframe>
        </div>
    </div>
</div>

<?php if ($canManagePolicies): ?>
<?php include_once '../includes/modals/policy_modal.php'; ?>
<?php endif; ?>
<?php include_once '../includes/modals/viewProfileModal.php'; ?>
<?php include_once '../includes/modals/updateProfileDetailsModal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($canManagePolicies): ?>
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
