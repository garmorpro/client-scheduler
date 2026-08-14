<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/audit_timeline_fields.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = intval($data['engagement_id'] ?? 0);
$rawValue = $data['independent'] ?? null;
// 'Y' / 'N' are the only real answers - anything else (including the
// explicit "Not answered yet" choice) means clear any existing attestation.
$value = in_array($rawValue, ['Y', 'N'], true) ? $rawValue : null;

if (!$engagementId) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

// Self-attestation only - this is each person confirming their own
// independence from the client, same as Engagement Tracker's own model
// (audit_team_independence is literally "one attestation per person per
// engagement"). There's no "set it for someone else" here by design;
// admins needing to correct a bad entry can do it at the DB level.
$userId = (int) $_SESSION['user_id'];

// Must actually be staffed on this engagement to attest to anything on it -
// an entries row, or being the engagement's named manager (a manager can
// be genuinely involved and need to attest without ever personally
// logging hours).
if (!user_is_staffed_on_engagement($conn, $engagementId, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You are not staffed on this engagement']);
    exit();
}

if ($value === null) {
    // "Not answered yet" - no row means unanswered, same as never having
    // attested at all, so clear rather than storing a third enum state.
    $stmt = $conn->prepare("DELETE FROM audit_team_independence WHERE engagement_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $engagementId, $userId);
} else {
    $stmt = $conn->prepare("
        INSERT INTO audit_team_independence (engagement_id, user_id, independent)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE independent = VALUES(independent)
    ");
    $stmt->bind_param('iis', $engagementId, $userId, $value);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not save.']);
}
$stmt->close();
