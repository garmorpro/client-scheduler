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
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) 
            VALUES (?, ?, ?, ?, ?, ?)";
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
    $rowNum = 1; // header row

    $header = fgetcsv($handle, 1000, ",");
    if (!$header) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to read CSV header']);
        exit();
    }

    // Expected CSV headers
    $expectedHeaders = ['week_start', 'assigned_hours', 'timeoff_note'];
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

    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = $_SESSION['full_name'] ?? '';

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;

        if (count(array_filter($data)) === 0) {
            continue; // skip empty rows
        }

        $weekStart = isset($data[$headerIndexes['week_start']]) ? trim($data[$headerIndexes['week_start']]) : '';
        $assignedHours = isset($data[$headerIndexes['assigned_hours']]) ? trim($data[$headerIndexes['assigned_hours']]) : '';
        $timeoffNote = isset($data[$headerIndexes['timeoff_note']]) ? trim($data[$headerIndexes['timeoff_note']]) : '';

        // Validate required fields
        if (empty($weekStart) || $assignedHours === '' || empty($timeoffNote)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Missing required fields (week_start, assigned_hours, timeoff_note).'];
            continue;
        }

        // Validate week_start date format (YYYY-MM-DD)
        $dateObj = date_create($weekStart);
        if (!$dateObj) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid date format for week_start.'];
            continue;
        }
        $weekStart = $dateObj->format('Y-m-d');

        // Validate assigned_hours as number
        if (!is_numeric($assignedHours) || $assignedHours < 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'assigned_hours must be a positive number.'];
            continue;
        }

        // Check for duplicate entry for same week_start
        $dupCheck = $conn->prepare("SELECT timeoff_id FROM time_off WHERE week_start = ?");
        if (!$dupCheck) {
            $errors[] = ['row' => $rowNum, 'message' => 'Database error: ' . $conn->error];
            continue;
        }
        $dupCheck->bind_param("s", $weekStart);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'Global PTO for this week_start already exists.'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // Insert into global_pto
        $stmt = $conn->prepare("INSERT INTO time_off (week_start, assigned_hours, timeoff_note, is_global_timeoff) VALUES (?, ?, ?, 1)");
        if (!$stmt) {
            $errors[] = ['row' => $rowNum, 'message' => 'Prepare failed: ' . $conn->error];
            continue;
        }
        $stmt->bind_param("sds", $weekStart, $assignedHours, $timeoffNote);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum, 'message' => 'Database insert error: ' . $stmt->error];
        }
        $stmt->close();
    }
    fclose($handle);

    // Log activity
    $description = "Successfully imported $successCount global PTO entries.";
    if (count($errors) > 0) {
        $description .= " Failed to import " . count($errors) . " entries.";
        $eventType = "bulk_global_pto_import_failed";
        $title = "Failed Bulk Global PTO Import";
    } else {
        $eventType = "bulk_global_pto_import";
        $title = "Bulk Global PTO Import";
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
