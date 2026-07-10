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

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Self-service only: employees edit their own name, regardless of what
// user_id the client sends. Email is intentionally not editable here.
$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$firstName = trim($data['first_name'] ?? '');
$lastName = trim($data['last_name'] ?? '');

if ($firstName === '' || $lastName === '') {
    echo json_encode(['success' => false, 'error' => 'Please fill all required fields.']);
    exit;
}

$fullName = trim($firstName . ' ' . $lastName);

$stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
$stmt->bind_param("si", $fullName, $userId);

if ($stmt->execute()) {
    $stmt->close();

    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name']  = $lastName;
    $_SESSION['full_name']  = $fullName;

    $email = $_SESSION['email'] ?? '';
    $role = $_SESSION['user_role'] ?? '';
    $roleUc = ucfirst($role);

    logActivity($conn, "user_updated", $userId, $email, $fullName, "Profile Updated", "Updated own name ($roleUc)");

    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
