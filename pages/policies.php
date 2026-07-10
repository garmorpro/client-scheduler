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

$policies = [];
$result = $conn->query("
    SELECT p.policy_id, p.title, p.pdf_path, p.updated_at, u.full_name AS updated_by_name
    FROM policies p
    LEFT JOIN users u ON p.updated_by = u.user_id
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
            'has_pdf' => !empty($row['pdf_path']),
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

    <?php if (!empty($policies)): ?>
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
    </form>
    <?php endif; ?>

    <?php if (empty($policies)): ?>
        <div class="policy-empty">
            <i class="bi bi-journal-text"></i>
            <div class="t">No policies yet</div>
            <div><?php echo $canManagePolicies ? 'Click "New Policy" to upload the first one.' : 'Check back later.'; ?></div>
        </div>
    <?php else: ?>
        <div class="indexWrap">
            <?php foreach ($policies as $policy): ?>
                <div class="indexRow <?php echo !$policy['has_pdf'] ? 'disabled' : ''; ?>">
                    <?php if ($policy['has_pdf']): ?>
                        <a href="policy.php?id=<?php echo $policy['policy_id']; ?>" class="indexRowLink" aria-label="<?php echo htmlspecialchars($policy['title']); ?>"></a>
                    <?php endif; ?>
                    <div class="indexNum"><?php echo str_pad($policy['num'], 2, '0', STR_PAD_LEFT); ?></div>
                    <div class="indexIcon"><i class="bi bi-file-earmark-pdf"></i></div>
                    <div class="indexMain">
                        <div class="indexTitle"><?php echo htmlspecialchars($policy['title']); ?></div>
                        <div class="indexSub">
                            <?php if (!$policy['has_pdf']): ?>
                                No file uploaded - delete and re-add
                            <?php else: ?>
                                Updated <?php echo date('M j, Y', strtotime($policy['updated_at'])); ?>
                                <?php if (!empty($policy['updated_by_name'])): ?>
                                    by <?php echo htmlspecialchars($policy['updated_by_name']); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($canManagePolicies): ?>
                        <button type="button" class="indexDeleteBtn" data-policy-id="<?php echo $policy['policy_id']; ?>" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    <?php endif; ?>
                    <?php if ($policy['has_pdf']): ?>
                        <div class="indexArrow"><i class="bi bi-chevron-right"></i></div>
                    <?php endif; ?>
                </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/policy_modal.js?v=<?php echo time(); ?>"></script>
<?php endif; ?>
<script src="../assets/js/viewProfileModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/openUpdateProfileDetailsModal.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/inactivity_counter.js?v=<?php echo time(); ?>"></script>
<script src="../assets/js/theme_mode.js?v=<?php echo time(); ?>"></script>
</body>
</html>
