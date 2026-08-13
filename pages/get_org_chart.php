<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'view_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$result = $conn->query("
    SELECT user_id, full_name, role, job_title, manager_id
    FROM users
    WHERE status = 'active'
    ORDER BY full_name ASC
");

$allUsers = [];
while ($row = $result->fetch_assoc()) {
    $allUsers[(int) $row['user_id']] = $row;
}

// manager_id already carries genuine reporting-line meaning for every
// role (it's what powers the "Reports to X" hint on the Employees page),
// not just for role = 'manager' - so children are grouped by whoever's
// actually referenced, regardless of that person's own role. This is
// what lets the tree show real multi-level chains (e.g. Staff -> Senior
// Manager -> VP) instead of the old flat Admin-tier/Manager-tier split.
$childrenOf = [];
foreach ($allUsers as $row) {
    $managerId = (int) ($row['manager_id'] ?? 0);
    if ($managerId && isset($allUsers[$managerId])) {
        $childrenOf[$managerId][] = $row;
    }
}
foreach ($childrenOf as &$kids) {
    usort($kids, function ($a, $b) { return strcmp($a['full_name'], $b['full_name']); });
}
unset($kids);

function formatUser($row) {
    return [
        'user_id' => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'role' => strtolower($row['role']),
        'job_title' => $row['job_title'] ?? '',
    ];
}

// Recursive - breaks a manager_id cycle (a user accidentally set as
// their own manager, directly or a few links up the chain) by refusing
// to revisit anyone already in the current chain, rather than recursing
// forever.
function buildNode($row, $childrenOf, $ancestry) {
    $userId = (int) $row['user_id'];
    $kids = [];
    if (!in_array($userId, $ancestry, true)) {
        foreach (($childrenOf[$userId] ?? []) as $child) {
            $kids[] = buildNode($child, $childrenOf, array_merge($ancestry, [$userId]));
        }
    }
    return [
        'user' => formatUser($row),
        'children' => $kids,
    ];
}

// Roots: admins (always, regardless of whether they have reports - they're
// leadership tier by definition) plus anyone else with no active manager
// of their own who does have reports. Everyone else with no manager and
// no reports is a genuine orphan - shown separately, not as a lone box in
// the tree.
$roots = [];
$unassigned = [];
foreach ($allUsers as $row) {
    $userId = (int) $row['user_id'];
    $managerId = (int) ($row['manager_id'] ?? 0);
    $hasActiveManager = $managerId && isset($allUsers[$managerId]);
    if ($hasActiveManager) continue; // reached below as someone's child

    $role = strtolower($row['role']);
    if ($role === 'admin' || !empty($childrenOf[$userId])) {
        $roots[] = $row;
    } else {
        $unassigned[] = $row;
    }
}

usort($roots, function ($a, $b) {
    $aAdmin = strtolower($a['role']) === 'admin' ? 0 : 1;
    $bAdmin = strtolower($b['role']) === 'admin' ? 0 : 1;
    if ($aAdmin !== $bAdmin) return $aAdmin <=> $bAdmin;
    return strcmp($a['full_name'], $b['full_name']);
});
usort($unassigned, function ($a, $b) { return strcmp($a['full_name'], $b['full_name']); });

$tree = array_map(function ($row) use ($childrenOf) {
    return buildNode($row, $childrenOf, []);
}, $roots);

echo json_encode([
    'success' => true,
    'tree' => $tree,
    'unassigned' => array_map('formatUser', $unassigned),
]);
