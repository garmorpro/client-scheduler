<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? null;
$week_start = $data['week_start'] ?? null;

if (!$user_id || !$week_start) {
    echo json_encode(['success' => false, 'error' => 'Missing user_id or week_start']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT entry_id FROM entries WHERE user_id = :user_id AND week_start = :week_start AND is_timeoff = 1 LIMIT 1");
    $stmt->execute([
        ':user_id' => $user_id,
        ':week_start' => $week_start
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'entry_id' => $row['entry_id']]);
    } else {
        echo json_encode(['success' => true, 'entry_id' => null]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
