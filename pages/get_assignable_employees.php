<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_employees')) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("
    SELECT u.user_id, u.full_name, u.role, u.manager_id, m.full_name AS manager_name
    FROM users u
    LEFT JOIN users m ON u.manager_id = m.user_id
    WHERE u.role IN ('staff', 'senior')
    ORDER BY u.full_name ASC
");
$stmt->execute();
$res = $stmt->get_result();

$employees = [];
while ($row = $res->fetch_assoc()) {
    $employees[] = [
        'user_id' => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'role' => $row['role'],
        'manager_id' => $row['manager_id'] !== null ? (int) $row['manager_id'] : null,
        'manager_name' => $row['manager_name'],
    ];
}
$stmt->close();
$conn->close();

echo json_encode(['employees' => $employees]);
