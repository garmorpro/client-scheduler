<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Activity logging function
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

    // Expected columns for engagements import
    $expectedHeaders = ['client_name', 'total_available_hours', 'status'];
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

    // Allowed statuses
    $allowedStatuses = ['confirmed', 'pending', 'not_confirmed'];

    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

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
        if (empty($rowAssoc['client_name']) || empty($rowAssoc['total_available_hours']) || empty($rowAssoc['status'])) {
            $errors[] = ['row' => $rowNum, 'message' => 'Missing required fields.'];
            continue;
        }
        if (!is_numeric($rowAssoc['total_available_hours']) || $rowAssoc['total_available_hours'] < 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid total_available_hours. Must be a positive number.'];
            continue;
        }
        if (!in_array(strtolower($rowAssoc['status']), $allowedStatuses)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid status. Allowed: confirmed, pending, not_confirmed'];
            continue;
        }

        // Check for duplicate client_name (optional - you can skip if you want duplicates)
        $dupCheck = $conn->prepare("SELECT engagement_id FROM engagements WHERE client_name = ?");
        if (!$dupCheck) {
            $errors[] = ['row' => $rowNum, 'message' => 'Database error: ' . $conn->error];
            continue;
        }
        $dupCheck->bind_param("s", $rowAssoc['client_name']);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'Client name already exists in database.'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // Insert engagement
        $stmt = $conn->prepare("INSERT INTO engagements (client_name, total_available_hours, status) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = ['row' => $rowNum, 'message' => 'Prepare statement failed: ' . $conn->error];
            continue;
        }
        $stmt->bind_param("sds", $rowAssoc['client_name'], $rowAssoc['total_available_hours'], $rowAssoc['status']);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum, 'message' => 'Database insert error: ' . $stmt->error];
        }
        $stmt->close();
    }
    fclose($handle);

    // Log activity
    $description = "Successfully imported $successCount engagements.";

    if (count($errors) > 0) {
        $description .= " Failed to import " . count($errors) . " engagements.";
        $eventType = "bulk_engagement_import_failed";
        $title = "Failed Bulk Engagement Import";
    } else {
        $eventType = "bulk_engagement_import";
        $title = "Bulk Engagement Import";
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
