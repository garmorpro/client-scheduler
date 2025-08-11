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
    $rowNum = 0;
    // Read header row and verify columns if you want here (optional)
    $header = fgetcsv($handle, 1000, ",");
    $expectedHeaders = ['first_name', 'last_name', 'email', 'role'];
    $headerLower = array_map('strtolower', $header);
    if (array_diff($expectedHeaders, $headerLower)) {
        http_response_code(400);
        echo json_encode(['error' => 'CSV headers do not match expected format.', 'expected' => $expectedHeaders, 'found' => $header]);
        fclose($handle);
        exit();
    }

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $rowNum++;
        // Map columns by header index to allow flexible column order
        $rowAssoc = [];
        foreach ($expectedHeaders as $index => $colName) {
            $rowAssoc[$colName] = $data[$index] ?? '';
        }

        // Validate required fields
        if (empty($rowAssoc['first_name']) || empty($rowAssoc['last_name']) || empty($rowAssoc['email']) || empty($rowAssoc['role'])) {
            $errors[] = ['row' => $rowNum + 1, 'message' => 'Missing required fields.'];
            continue;
        }
        if (!filter_var($rowAssoc['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['row' => $rowNum + 1, 'message' => 'Invalid email address.'];
            continue;
        }
        // Optional: check if role is allowed here, e.g. in ['admin', 'manager', 'staff']

        // Check for duplicate email in DB
        $dupCheck = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $dupCheck->bind_param("s", $rowAssoc['email']);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) {
            $errors[] = ['row' => $rowNum + 1, 'message' => 'Email already exists in database.'];
            $dupCheck->close();
            continue;
        }
        $dupCheck->close();

        // Insert user with default password hash
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, role, status, password) VALUES (?, ?, ?, ?, 'active', ?)");
        $defaultPasswordHash = password_hash("change_me", PASSWORD_DEFAULT);
        $stmt->bind_param("sssss", $rowAssoc['first_name'], $rowAssoc['last_name'], $rowAssoc['email'], $rowAssoc['role'], $defaultPasswordHash);

        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errors[] = ['row' => $rowNum + 1, 'message' => 'Database insert error.'];
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
