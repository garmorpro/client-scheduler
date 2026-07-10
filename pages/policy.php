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
    SELECT p.policy_id, p.title, p.doc_type, p.source_type, p.effective_date, p.memo_to, p.memo_from,
           p.content, p.pdf_path, p.pdf_original_name, p.created_at, p.updated_at,
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

$isPdf = $policy['source_type'] === 'pdf';
$pdfUrl = $isPdf ? '../assets/uploads/policies/' . rawurlencode($policy['pdf_path']) : null;
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

<?php if (!$isPdf): ?>
<div class="policy-print-only">
    <div class="policy-letterhead">
        <div class="policy-letterhead-info">
            <div class="policy-letterhead-name">AARC-360</div>
            <div>8000 Avalon Boulevard, Suite 100, Alpharetta, GA 30009</div>
            <div>Tel: +1 866 576 4414</div>
            <div>www.AARC-360.com</div>
        </div>
        <img src="../assets/images/aarc-360-logo-1.webp" alt="AARC-360" class="policy-letterhead-logo">
    </div>
    <div class="policy-letterhead-rule"></div>

    <?php if ($policy['doc_type'] === 'memo'): ?>
        <div class="policy-memo-block">
            <div class="policy-memo-heading">MEMORANDUM</div>
            <div class="policy-memo-row"><span>To:</span><span><?php echo htmlspecialchars($policy['memo_to'] ?: 'AARC-360 Employees'); ?></span></div>
            <div class="policy-memo-row"><span>From:</span><span><?php echo htmlspecialchars($policy['memo_from'] ?: ''); ?></span></div>
            <div class="policy-memo-row"><span>Subject:</span><span><?php echo htmlspecialchars($policy['title']); ?></span></div>
        </div>
        <div class="policy-memo-rule"></div>
    <?php else: ?>
        <div class="policy-print-title-block">
            <h1><?php echo htmlspecialchars($policy['title']); ?></h1>
            <?php if (!empty($policy['effective_date'])): ?>
                <div class="policy-print-effective">Effective <?php echo date('F j, Y', strtotime($policy['effective_date'])); ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="policy-print-content"><?php echo $policy['content']; ?></div>
</div>
<?php endif; ?>

<div class="policy-screen-only d-flex w-100">
<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="policy-doc-card <?php echo $isPdf ? 'is-pdf' : ''; ?>">
        <div class="policy-head">
            <a href="policies.php" class="policy-head-back"><i class="bi bi-arrow-left"></i> All Policies</a>

            <div class="policy-head-top">
                <div>
                    <span class="policy-head-chip"><?php echo $isPdf ? 'PDF' : ($policy['doc_type'] === 'memo' ? 'Memo' : 'Policy'); ?></span>
                    <h1 class="policy-head-title"><?php echo htmlspecialchars($policy['title']); ?></h1>

                    <?php if (!$isPdf && $policy['doc_type'] === 'memo'): ?>
                        <div class="policy-head-meta">
                            To: <strong><?php echo htmlspecialchars($policy['memo_to'] ?: 'AARC-360 Employees'); ?></strong>
                            &nbsp;·&nbsp; From: <strong><?php echo htmlspecialchars($policy['memo_from'] ?: '-'); ?></strong>
                        </div>
                    <?php elseif (!$isPdf && !empty($policy['effective_date'])): ?>
                        <div class="policy-head-meta">Effective <strong><?php echo date('F j, Y', strtotime($policy['effective_date'])); ?></strong></div>
                    <?php endif; ?>
                    <div class="policy-head-meta muted">
                        Last updated <?php echo date('F j, Y', strtotime($policy['updated_at'])); ?>
                        <?php if (!empty($policy['updated_by_name'])): ?>
                            by <?php echo htmlspecialchars($policy['updated_by_name']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="policy-head-actions">
                    <?php if ($isPdf): ?>
                        <a href="<?php echo $pdfUrl; ?>" download="<?php echo htmlspecialchars($policy['pdf_original_name']); ?>" class="policy-head-btn">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </a>
                    <?php else: ?>
                        <button type="button" id="downloadPolicyPdfBtn" class="policy-head-btn">
                            <i class="bi bi-download me-2"></i>Download PDF
                        </button>
                    <?php endif; ?>
                    <?php if ($canManagePolicies): ?>
                    <button type="button" id="editPolicyBtn" class="policy-head-btn"
                        data-policy-id="<?php echo $policy['policy_id']; ?>"
                        data-policy-title="<?php echo htmlspecialchars($policy['title']); ?>"
                        data-policy-effective-date="<?php echo htmlspecialchars($policy['effective_date'] ?? ''); ?>"
                        data-policy-doc-type="<?php echo htmlspecialchars($policy['doc_type']); ?>"
                        data-policy-memo-to="<?php echo htmlspecialchars($policy['memo_to'] ?? ''); ?>"
                        data-policy-memo-from="<?php echo htmlspecialchars($policy['memo_from'] ?? ''); ?>"
                        data-policy-source-type="<?php echo htmlspecialchars($policy['source_type']); ?>"
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

        <?php if ($isPdf): ?>
            <div class="policy-pdf-viewer">
                <iframe src="<?php echo $pdfUrl; ?>" title="<?php echo htmlspecialchars($policy['title']); ?>"></iframe>
            </div>
        <?php else: ?>
            <div class="policy-detail-body">
                <?php echo $policy['content']; ?>
            </div>
        <?php endif; ?>
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
