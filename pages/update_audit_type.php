<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$color = trim($data['color'] ?? '');
$isActive = isset($data['is_active']) ? (!empty($data['is_active']) ? 1 : 0) : null;

if (!$id || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Name is required.']);
    exit();
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#4f8ef7';
}

if ($isActive === null) {
    $stmt = $conn->prepare("UPDATE audit_types SET name = ?, color = ? WHERE audit_type_id = ?");
    $stmt->bind_param('ssi', $name, $color, $id);
} else {
    $stmt = $conn->prepare("UPDATE audit_types SET name = ?, color = ?, is_active = ? WHERE audit_type_id = ?");
    $stmt->bind_param('ssii', $name, $color, $isActive, $id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'error' => 'An audit type with that name already exists.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not save audit type.']);
    }
}
$stmt->close();
