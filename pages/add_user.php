<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_employees')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        $stmt->execute();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit();
}

$allowedRoles = ['admin', 'manager', 'senior', 'staff', 'intern', 'crm_team'];

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$jobTitle = trim($_POST['job_title'] ?? '');
$role = strtolower(trim($_POST['role'] ?? ''));

if ($fullName === '' || $email === '' || $role === '') {
    echo json_encode(['success' => false, 'error' => 'Full name, email, and role are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
    exit();
}

if (!in_array($role, $allowedRoles, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid role.']);
    exit();
}

$lowerEmail = strtolower($email);
$dupCheck = $conn->prepare("SELECT user_id FROM users WHERE LOWER(email) = ?");
$dupCheck->bind_param("s", $lowerEmail);
$dupCheck->execute();
$dupCheck->store_result();
if ($dupCheck->num_rows > 0) {
    $dupCheck->close();
    echo json_encode(['success' => false, 'error' => 'A user with that email already exists.']);
    exit();
}
$dupCheck->close();

$defaultPassword = password_hash("change_me", PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (full_name, email, job_title, role, password) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit();
}
$stmt->bind_param("sssss", $fullName, $email, $jobTitle, $role, $defaultPassword);

if ($stmt->execute()) {
    $newUserId = $stmt->insert_id;
    $stmt->close();

    // Staff/Intern start out restricted on every criterion, not fully
    // trained - training.php's own default assumed "no restrictions row =
    // fully trained," which was backwards for someone who just started.
    // Cleared one at a time (click the x) as they're actually tested and
    // documented on each. Same canonical list used everywhere else
    // (dol_generator.js's SOC2_CRITERIA_ORDER, training.php's
    // sort_training_criteria()) - only Staff/Intern get these since
    // they're the only roles the Training page manages.
    if (in_array($role, ['staff', 'intern'], true)) {
        $allCriteria = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Privacy', 'Processing Integrity'];
        $restrictStmt = $conn->prepare("INSERT INTO dol_training_restrictions (user_id, criterion) VALUES (?, ?)");
        if ($restrictStmt) {
            foreach ($allCriteria as $criterion) {
                $restrictStmt->bind_param('is', $newUserId, $criterion);
                $restrictStmt->execute();
            }
            $restrictStmt->close();
        }
    }

    $adminUserId = $_SESSION['user_id'] ?? null;
    $adminEmail  = $_SESSION['email'] ?? '';
    $adminName   = $_SESSION['full_name'] ?? '';
    $role_uc = ucfirst($role);

    logActivity($conn, "user_created", $adminUserId, $adminEmail, $adminName, "User Created", "Created account for $fullName ($role_uc)");

    echo json_encode(['success' => true, 'user_id' => $newUserId]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error creating user.']);
}
