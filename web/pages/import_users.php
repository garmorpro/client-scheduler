<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// UNIQUE IDNO GENERATOR
function generateUniqueIdno($conn) {
    do {
        $idno = random_int(100000, 999999); // 6-digit number

        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE idno = ?");
        if (!$checkStmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $checkStmt->bind_param("i", $idno);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();
    } while ($count > 0);

    return $idno;
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

$fileTmpPath = $_FILES['csv_file']['tmp_name'];

$errors = [];
$successCount = 0;

if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
    $rowNum = 1; // Header row

    $header = fgetcsv($handle, 1000, ",");
    if (!$header) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to read CSV header']);
        exit();
    }

    $expectedHeaders = ['first_name', 'last_name', 'email', 'role'];
    $headerLower = array_map('strtolower', $header);

    $headerIndexes = [];
    foreach ($expectedHeaders as $colName) {
        $pos = array_search($colName, $headerLower);
        if ($pos === false) {
            http_response_code(400);
            echo json_encode(['error' => "CSV missing expected column: $colName"]);
            fclose($handle);
            exit();
        }
        $headerIndexes[$colName] = $pos;
    }

    $defaultPasswordHash = password_hash("change_me", PASSWORD_DEFAULT);

    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

    $allowedRoles = ['admin', 'manager', 'senior', 'staff'];

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;

        if (count(array_filter($data)) === 0) {
            continue; // skip empty rows
        }

        $rowAssoc = [];
        foreach ($expectedHeaders as $colName) {
            $rowAssoc[$colName] = isset($data[$headerIndexes[$colName]]) ? trim($data[$headerIndexes[$colName]]) : '';
        }

        // Validate required fields
        if (empty($rowAssoc['first_name']) || empty($rowAssoc['last_name']) || empty($rowAssoc['email']) || empty($rowAssoc['role'])) {
            $errors[] = ['row' => $rowNum, 'message' => 'Missing required fields.'];
            continue;
        }
        if (!filter_var($rowAssoc['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid email address.'];
            continue;
        }
        if (!in_array(strtolower($rowAssoc['role']), $allowedRoles)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid role. Allowed: ' . implode(', ', $allowedRoles)];
            continue;
        }

        // Check for duplicate email
        $dupCheck = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$dupCheck) {
            $errors[] = ['row' => $rowNum, 'message' => 'Database error: ' . $conn->error];
            continue;
        }
        $dupCheck->bind_param("s", $rowAssoc['email']);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'Email already exists in database.'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // Generate unique idno
        $idno = generateUniqueIdno($conn);

        $stmt = $conn->prepare("INSERT INTO users (idno, first_name, last_name, email, role, status, password) VALUES (?, ?, ?, ?, ?, 'active', ?)");
        if (!$stmt) {
            $errors[] = ['row' => $rowNum, 'message' => 'Prepare statement failed: ' . $conn->error];
            continue;
        }
        $stmt->bind_param("isssss", $idno, $rowAssoc['first_name'], $rowAssoc['last_name'], $rowAssoc['email'], $rowAssoc['role'], $defaultPasswordHash);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum, 'message' => 'Database insert error: ' . $stmt->error];
        }
        $stmt->close();
    }
    fclose($handle);

    // Determine event type based on errors
    $description = "Successfully imported $successCount users.";

    if (count($errors) > 0) {
        $description .= " Failed to import " . count($errors) . " users.";
        $eventType = "bulk_user_import_failed";
        $title = "Failed Bulk User Import";
    } else {
        $eventType = "bulk_user_import";
        $title = "Bulk User Import";
    }

    logActivity($conn, $eventType, $currentUserId, $currentUserEmail, $currentUserFullName, $title, $description);

    echo json_encode([
        'successCount' => $successCount,
        'errors' => $errors
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to open uploaded file']);
}
