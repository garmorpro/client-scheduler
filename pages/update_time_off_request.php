<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$allowedCategories = ['vacation', 'sick', 'parental', 'volunteer'];
$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$requestGroup = trim($data['request_group'] ?? '');
$category = strtolower(trim($data['category'] ?? ''));
$reason = trim($data['reason'] ?? '');
$days = $data['days'] ?? [];

if (!$requestGroup || strpos($requestGroup, 'single-') === 0) {
    echo json_encode(['success' => false, 'error' => 'This request cannot be edited.']);
    exit;
}

if (!in_array($category, $allowedCategories, true)) {
    echo json_encode(['success' => false, 'error' => 'Please choose a valid category.']);
    exit;
}

if (!is_array($days) || count($days) === 0) {
    echo json_encode(['success' => false, 'error' => 'Please add at least one day.']);
    exit;
}

$cleanDays = [];
foreach ($days as $day) {
    $date = trim($day['date'] ?? '');
    $hours = $day['hours'] ?? '';
    if (!$date || !is_numeric($hours) || (float)$hours <= 0) {
        echo json_encode(['success' => false, 'error' => 'Each day needs a valid date and a positive number of hours.']);
        exit;
    }
    if (!DateTime::createFromFormat('Y-m-d', $date)) {
        echo json_encode(['success' => false, 'error' => "Invalid date: $date"]);
        exit;
    }
    $cleanDays[] = ['date' => $date, 'hours' => (float)$hours];
}

// Only the requester can edit, and only while the request is in
// "changes_requested" status - i.e. a reviewer explicitly sent it back.
$check = $conn->prepare("SELECT COUNT(*) AS cnt FROM time_off WHERE request_group = ? AND user_id = ? AND status = 'changes_requested'");
$check->bind_param('si', $requestGroup, $userId);
$check->execute();
$owns = $check->get_result()->fetch_assoc()['cnt'] > 0;
$check->close();

if (!$owns) {
    echo json_encode(['success' => false, 'error' => 'This request cannot be edited right now.']);
    exit;
}

$conn->begin_transaction();
try {
    $del = $conn->prepare("DELETE FROM time_off WHERE request_group = ? AND user_id = ?");
    $del->bind_param('si', $requestGroup, $userId);
    $del->execute();
    $del->close();

    $ins = $conn->prepare("
        INSERT INTO time_off (user_id, request_group, category, timeoff_note, week_start, holiday_date, assigned_hours, is_global_timeoff, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending')
    ");
    foreach ($cleanDays as $day) {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($day['date'])));
        $ins->bind_param('isssssd', $userId, $requestGroup, $category, $reason, $weekStart, $day['date'], $day['hours']);
        if (!$ins->execute()) {
            throw new Exception($ins->error);
        }
    }
    $ins->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not update request: ' . $e->getMessage()]);
}

$conn->close();
