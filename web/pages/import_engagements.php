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
    $expectedHeaders = ['client_name', 'onboarded_date'];
    $optionalHeaders = ['notes'];
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

    foreach ($optionalHeaders as $colName) {
        $pos = array_search($colName, $headerLower);
        if ($pos !== false) {
            $headerIndexes[$colName] = $pos;
        }
    }

    $currentUserId = $_SESSION['user_id'];
    $currentUserEmail = $_SESSION['email'] ?? '';
    $currentUserFullName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;

        if (count(array_filter($data)) === 0) {
            continue; // skip empty rows
        }

        $clientName = isset($data[$headerIndexes['client_name']]) ? trim($data[$headerIndexes['client_name']]) : '';
        $onboardedDate = isset($data[$headerIndexes['onboarded_date']]) ? trim($data[$headerIndexes['onboarded_date']]) : '';
        $notes = isset($headerIndexes['notes']) && isset($data[$headerIndexes['notes']]) 
                 ? trim($data[$headerIndexes['notes']]) : null;

        // Validate required fields
        if (empty($clientName) || empty($onboardedDate)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Missing required fields (client_name, onboarded_date).'];
            continue;
        }

        // Validate date format (YYYY-MM-DD or something PHP can parse)
        $dateObj = date_create($onboardedDate);
        if (!$dateObj) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid date format for onboarded_date.'];
            continue;
        }
        $onboardedDate = $dateObj->format('Y-m-d');

        // Check for duplicate client_name
        $dupCheck = $conn->prepare("SELECT client_id FROM clients WHERE client_name = ?");
        if (!$dupCheck) {
            $errors[] = ['row' => $rowNum, 'message' => 'Database error: ' . $conn->error];
            continue;
        }
        $dupCheck->bind_param("s", $clientName);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => $rowNum, 'message' => 'Client name already exists in database.'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // Insert into clients
        $stmt = $conn->prepare("INSERT INTO clients (client_name, onboarded_date, notes) VALUES (?, ?, ?)");
        if (!$stmt) {
            $errors[] = ['row' => $rowNum, 'message' => 'Prepare failed: ' . $conn->error];
            continue;
        }
        $stmt->bind_param("sss", $clientName, $onboardedDate, $notes);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum, 'message' => 'Database insert error: ' . $stmt->error];
        }
        $stmt->close();
    }
    fclose($handle);

    // Log activity
    $description = "Successfully imported $successCount clients.";
    if (count($errors) > 0) {
        $description .= " Failed to import " . count($errors) . " clients.";
        $eventType = "bulk_client_import_failed";
        $title = "Failed Bulk Client Import";
    } else {
        $eventType = "bulk_client_import";
        $title = "Bulk Client Import";
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
