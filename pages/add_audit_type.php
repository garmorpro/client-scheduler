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
$name = trim($data['name'] ?? '');
$color = trim($data['color'] ?? '') ?: '#4f8ef7';

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Name is required.']);
    exit();
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#4f8ef7';
}

$stmt = $conn->prepare("INSERT INTO audit_types (name, color) VALUES (?, ?)");
$stmt->bind_param('ss', $name, $color);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    if ($conn->errno === 1062) {
        echo json_encode(['success' => false, 'error' => 'An audit type with that name already exists.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not add audit type.']);
    }
}
$stmt->close();
