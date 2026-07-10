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

$sortOptions = [
    'upload'     => 'p.created_at ASC',
    'newest'     => 'p.created_at DESC',
    'updated'    => 'p.updated_at DESC',
    'title_asc'  => 'p.title ASC',
    'title_desc' => 'p.title DESC',
];
$sort = isset($_GET['sort']) && isset($sortOptions[$_GET['sort']]) ? $_GET['sort'] : 'upload';

$typeOptions = ['all', 'policy', 'memo', 'pdf'];
$type = isset($_GET['type']) && in_array($_GET['type'], $typeOptions, true) ? $_GET['type'] : 'all';

$where = '';
if ($type === 'pdf') {
    $where = "WHERE p.source_type = 'pdf'";
} elseif ($type === 'policy') {
    $where = "WHERE p.source_type != 'pdf' AND p.doc_type = 'policy'";
} elseif ($type === 'memo') {
    $where = "WHERE p.source_type != 'pdf' AND p.doc_type = 'memo'";
}

$anyPoliciesExist = (int) $conn->query("SELECT COUNT(*) AS cnt FROM policies")->fetch_assoc()['cnt'] > 0;

$policies = [];
$result = $conn->query("
    SELECT p.policy_id, p.title, p.doc_type, p.source_type, p.effective_date, p.memo_from, p.updated_at, u.full_name AS updated_by_name
    FROM policies p
    LEFT JOIN users u ON p.updated_by = u.user_id
    $where
    ORDER BY {$sortOptions[$sort]}
");
if ($result) {
    $i = 0;
    while ($row = $result->fetch_assoc()) {
        $i++;
        $policies[] = [
            'num' => $i,
            'policy_id' => (int)$row['policy_id'],
            'title' => $row['title'],
            'doc_type' => $row['doc_type'],
            'source_type' => $row['source_type'],
            'effective_date' => $row['effective_date'],
            'memo_from' => $row['memo_from'],
            'updated_at' => $row['updated_at'],
            'updated_by_name' => $row['updated_by_name'],
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Policies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css?v=<?php echo time(); ?>">
    <?php if ($canManagePolicies): ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="d-flex <?= ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '' ?>">

<?php include_once '../templates/sidebar.php'; ?>

<div class="flex-grow-1 p-4" style="margin-left: 250px;">
    <div class="header-row">
        <div>
            <h3 class="mb-0">Policies</h3>
            <p class="mb-0">Company policies, procedures, and reference documents</p>
        </div>
        <?php if ($canManagePolicies): ?>
        <div class="d-flex align-items-center gap-2">
            <a href="#" id="newPolicyBtn" class="badge p-2 text-decoration-none fw-medium btn-dark-custom">
                <i class="bi bi-plus-lg me-3"></i>New Policy
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($anyPoliciesExist): ?>
    <form method="GET" class="policy-filter-bar" id="policyFilterForm">
        <div class="policy-filter-field">
            <label for="policySortSelect">Sort by</label>
            <select class="policy-filter-select" id="policySortSelect" name="sort" onchange="this.form.submit()">
                <option value="upload" <?php echo $sort === 'upload' ? 'selected' : ''; ?>>Upload order</option>
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                <option value="updated" <?php echo $sort === 'updated' ? 'selected' : ''; ?>>Recently updated</option>
                <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title A–Z</option>
                <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Title Z–A</option>
            </select>
        </div>
        <div class="policy-filter-field">
            <label for="policyTypeSelect">Type</label>
            <select class="policy-filter-select" id="policyTypeSelect" name="type" onchange="this.form.submit()">
                <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All types</option>
                <option value="policy" <?php echo $type === 'policy' ? 'selected' : ''; ?>>Policy</option>
                <option value="memo" <?php echo $type === 'memo' ? 'selected' : ''; ?>>Memo</option>
                <option value="pdf" <?php echo $type === 'pdf' ? 'selected' : ''; ?>>PDF</option>
            </select>
        </div>
    </form>
    <?php endif; ?>

    <?php if (empty($policies)): ?>
        <div class="policy-empty">
            <i class="bi bi-journal-text"></i>
            <?php if ($anyPoliciesExist): ?>
                <div class="t">No documents match this filter</div>
                <div>Try a different type filter above.</div>
            <?php else: ?>
                <div class="t">No policies yet</div>
                <div><?php echo $canManagePolicies ? 'Click "New Policy" to publish the first one.' : 'Check back later.'; ?></div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="indexWrap">
            <?php foreach ($policies as $policy): ?>
                <a href="policy.php?id=<?php echo $policy['policy_id']; ?>" class="indexRow <?php echo $policy['source_type'] === 'pdf' ? 'pdf' : $policy['doc_type']; ?>">
                    <div class="indexNum"><?php echo str_pad($policy['num'], 2, '0', STR_PAD_LEFT); ?></div>
                    <div class="indexIcon"><i class="bi <?php echo $policy['source_type'] === 'pdf' ? 'bi-file-earmark-pdf' : ($policy['doc_type'] === 'memo' ? 'bi-file-earmark-text' : 'bi-journal-text'); ?>"></i></div>
                    <div class="indexMain">
                        <div class="indexTitle"><?php echo htmlspecialchars($policy['title']); ?></div>
                        <div class="indexSub">
                            <?php if ($policy['source_type'] === 'pdf'): ?>
                                Updated <?php echo date('M j, Y', strtotime($policy['updated_at'])); ?>
                            <?php elseif ($policy['doc_type'] === 'memo'): ?>
                                <?php if (!empty($policy['memo_from'])): ?>From <?php echo htmlspecialchars($policy['memo_from']); ?> · <?php endif; ?>Updated <?php echo date('M j, Y', strtotime($policy['updated_at'])); ?>
                            <?php elseif (!empty($policy['effective_date'])): ?>
                                Effective <?php echo date('M j, Y', strtotime($policy['effective_date'])); ?>
                            <?php else: ?>
                                Updated <?php echo date('M j, Y', strtotime($policy['updated_at'])); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="indexType"><?php echo $policy['source_type'] === 'pdf' ? 'PDF' : ($policy['doc_type'] === 'memo' ? 'Memo' : 'Policy'); ?></div>
                    <div class="indexArrow"><i class="bi bi-chevron-right"></i></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($canManagePolicies): ?>
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
</body>
</html>
