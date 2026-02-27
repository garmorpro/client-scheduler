<?php
require_once '../includes/db.php';
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'DB CONNECTION FAILED']);
    exit();
}

// Log activity function
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

$fileTmpPath = $_FILES['csv_file']['tmp_name'];
if (($_FILES['csv_file']['size'] > 2 * 1024 * 1024)) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Max 2MB']);
    exit();
}

$fileInfo = pathinfo($_FILES['csv_file']['name']);
if (strtolower($fileInfo['extension']) !== 'csv') {
    http_response_code(400);
    echo json_encode(['error' => 'Only CSV files allowed']);
    exit();
}

$errors = [];
$successCount = 0;

if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
    $header = fgetcsv($handle, 1000, ",");
    if (isset($header[0])) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

    $expectedHeaders = ['email', 'full_name', 'job_title', 'role'];
    $headerLower = array_map('strtolower', array_map('trim', $header));

    $headerIndexes = [];
    foreach ($expectedHeaders as $colName) {
        $pos = array_search($colName, $headerLower);
        if ($pos === false) {
            fclose($handle);
            echo json_encode(['error' => "CSV missing expected column: $colName"]);
            exit();
        }
        $headerIndexes[$colName] = $pos;
    }

    $defaultPasswordHash = password_hash("change_me", PASSWORD_DEFAULT);
    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = $_SESSION['full_name'] ?? '';
    $allowedRoles = ['admin', 'manager', 'senior', 'staff'];

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (count(array_filter($data)) === 0) continue;

        $rowAssoc = [];
        foreach ($expectedHeaders as $colName) {
            $rowAssoc[$colName] = trim($data[$headerIndexes[$colName]] ?? '');
        }

        $rowAssoc['email'] = strtolower($rowAssoc['email']);
        $rowAssoc['role'] = strtolower($rowAssoc['role']);

        if (empty($rowAssoc['email']) || empty($rowAssoc['full_name']) || empty($rowAssoc['job_title']) || empty($rowAssoc['role'])) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Missing required fields.'];
            continue;
        }

        if (!filter_var($rowAssoc['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Invalid email address.'];
            continue;
        }

        if (!in_array($rowAssoc['role'], $allowedRoles)) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Invalid role'];
            continue;
        }

        // Duplicate check
        $dupCheck = $conn->prepare("SELECT user_id FROM users WHERE LOWER(email) = ?");
        if (!$dupCheck) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Database error'];
            continue;
        }
        $dupCheck->bind_param("s", $rowAssoc['email']);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Email already exists'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        $stmt = $conn->prepare("INSERT INTO users (email, full_name, job_title, role, password) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Insert prepare failed'];
            continue;
        }
        $stmt->bind_param("sssss", $rowAssoc['email'], $rowAssoc['full_name'], $rowAssoc['job_title'], $rowAssoc['role'], $defaultPasswordHash);
        if (!$stmt->execute()) {
            $errors[] = ['row' => ftell($handle), 'message' => 'Insert failed: '.$stmt->error];
            $stmt->close();
            continue;
        }
        $successCount++;
        $stmt->close();
    }
    fclose($handle);

    $description = "Successfully imported $successCount users.";
    $eventType = count($errors) > 0 ? "bulk_user_import_failed" : "bulk_user_import";
    $title = count($errors) > 0 ? "Failed Bulk User Import" : "Bulk User Import";

    logActivity($conn, $eventType, $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode(['successCount' => $successCount, 'errors' => $errors]);

} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to open uploaded file']);
}