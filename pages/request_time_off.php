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

$data = json_decode(file_get_contents('php://input'), true);
$category = strtolower(trim($data['category'] ?? ''));
$reason = trim($data['reason'] ?? '');
$days = $data['days'] ?? [];

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

$userId = $_SESSION['user_id'];
$requestGroup = bin2hex(random_bytes(16));

$stmt = $conn->prepare("
    INSERT INTO time_off (user_id, request_group, category, timeoff_note, week_start, holiday_date, assigned_hours, is_global_timeoff, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending')
");

$conn->begin_transaction();
try {
    foreach ($cleanDays as $day) {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($day['date'])));
        $stmt->bind_param('isssssd', $userId, $requestGroup, $category, $reason, $weekStart, $day['date'], $day['hours']);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }
    $conn->commit();
    echo json_encode(['success' => true, 'request_group' => $requestGroup]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Could not save request: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
