<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage policies']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$policyId = !empty($data['policy_id']) ? (int)$data['policy_id'] : 0;
$title = trim($data['title'] ?? '');
$content = $data['content'] ?? '';
$userId = $_SESSION['user_id'];

$effectiveDate = trim($data['effective_date'] ?? '');
$effectiveDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveDate) ? $effectiveDate : null;

if ($title === '' || trim(strip_tags($content)) === '') {
    echo json_encode(['success' => false, 'error' => 'Please add a title and some content.']);
    exit;
}

if ($policyId) {
    $stmt = $conn->prepare("UPDATE policies SET title = ?, effective_date = ?, content = ?, updated_by = ? WHERE policy_id = ?");
    $stmt->bind_param('sssii', $title, $effectiveDate, $content, $userId, $policyId);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO policies (title, effective_date, content, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssii', $title, $effectiveDate, $content, $userId, $userId);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }
    $policyId = $stmt->insert_id;
    $stmt->close();
}

echo json_encode(['success' => true, 'policy_id' => $policyId]);
