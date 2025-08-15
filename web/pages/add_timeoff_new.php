<?php
require_once '../includes/db.php';
session_start();

// Only allow admins
if (!isset($_SESSION['user_role']) || strtolower($_SESSION['user_role']) !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$week_start = $data['week_start'] ?? null;
$timeoff_note = $data['timeoff_note'] ?? '';
$is_timeoff = $data['is_timeoff'] ?? 1;

if (!$user_id || !$week_start || !$timeoff_note) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $db->prepare("
        INSERT INTO entries (user_id, week_start, is_timeoff, timeoff_note)
        VALUES (:user_id, :week_start, :is_timeoff, :timeoff_note)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':week_start' => $week_start,
        ':is_timeoff' => $is_timeoff,
        ':timeoff_note' => $timeoff_note
    ]);

    $entry_id = $db->lastInsertId();

    echo json_encode(['success' => true, 'entry_id' => $entry_id]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
