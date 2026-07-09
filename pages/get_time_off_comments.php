<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$requestGroup = trim($_GET['request_group'] ?? '');
if (!$requestGroup) {
    echo json_encode(['success' => false, 'error' => 'Missing request_group']);
    exit;
}

$stmt = $conn->prepare("
    SELECT c.user_id, u.full_name, c.comment, c.created
    FROM time_off_comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.request_group = ?
    ORDER BY c.created ASC
");
$stmt->bind_param('s', $requestGroup);
$stmt->execute();
$res = $stmt->get_result();

$comments = [];
while ($row = $res->fetch_assoc()) {
    $comments[] = [
        'user_id'   => (int) $row['user_id'],
        'full_name' => $row['full_name'],
        'comment'   => $row['comment'],
        'created'   => $row['created'],
    ];
}
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'comments' => $comments]);
