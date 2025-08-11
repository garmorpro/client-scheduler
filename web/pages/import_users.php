<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
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
    $rowNum = 1; // Start with 1 for header row

    // Read header row
    $header = fgetcsv($handle, 1000, ",");
    if (!$header) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to read CSV header']);
        exit();
    }

    $expectedHeaders = ['first_name', 'last_name', 'email', 'role'];
    $headerLower = array_map('strtolower', $header);

    // Map expected columns to their indexes in CSV
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

    // Prepare default password hash once
    $defaultPasswordHash = password_hash("change_me", PASSWORD_DEFAULT);

    // Loop through each data row
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;

        // Skip empty rows
        if (count(array_filter($data)) === 0) {
            continue;
        }

        // Map CSV columns dynamically by header
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

        // Optional: Validate role against allowed values
        $allowedRoles = ['admin', 'manager', 'senior', 'staff'];
        if (!in_array(strtolower($rowAssoc['role']), $allowedRoles)) {
            $errors[] = ['row' => $rowNum, 'message' => 'Invalid role. Allowed: ' . implode(', ', $allowedRoles)];
            continue;
        }

        // Check for duplicate email in DB
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

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, role, status, password) VALUES (?, ?, ?, ?, 'active', ?)");
        if (!$stmt) {
            $errors[] = ['row' => $rowNum, 'message' => 'Prepare statement failed: ' . $conn->error];
            continue;
        }
        $stmt->bind_param("sssss", $rowAssoc['first_name'], $rowAssoc['last_name'], $rowAssoc['email'], $rowAssoc['role'], $defaultPasswordHash);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum, 'message' => 'Database insert error: ' . $stmt->error];
        }
        $stmt->close();
    }
    fclose($handle);

    echo json_encode([
        'successCount' => $successCount,
        'errors' => $errors
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to open uploaded file']);
}
