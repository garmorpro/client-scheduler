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
$entry_id = $data['entry_id'] ?? null;
$assigned_hours = $data['assigned_hours'] ?? '';

if (!$entry_id || !$assigned_hours) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE entries
        SET assigned_hours = :assigned_hours
        WHERE entry_id = :entry_id AND is_timeoff = 1
    ");
    $stmt->execute([
        ':assigned_hours' => $assigned_hours,
        ':entry_id' => $entry_id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
