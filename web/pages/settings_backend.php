<?php
require_once '../includes/db.php'; // your mysqli connection $conn
session_start();

// TODO: Check admin permissions here

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['setting_master_key'], $data['settings']) || !is_array($data['settings'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$masterKey = $data['setting_master_key'];
$settings = $data['settings'];

// Prepare statements
$stmtCheck = mysqli_prepare($conn, "SELECT id FROM settings WHERE setting_master_key = ? AND setting_key = ?");
$stmtUpdate = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE id = ?");
$stmtInsert = mysqli_prepare($conn, "INSERT INTO settings (setting_master_key, setting_key, setting_value) VALUES (?, ?, ?)");

if (!$stmtCheck || !$stmtUpdate || !$stmtInsert) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statements']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    foreach ($settings as $key => $value) {
        // Check if setting exists
        mysqli_stmt_bind_param($stmtCheck, "ss", $masterKey, $key);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_bind_result($stmtCheck, $id);
        mysqli_stmt_store_result($stmtCheck);

        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            mysqli_stmt_fetch($stmtCheck);
            // Update
            mysqli_stmt_bind_param($stmtUpdate, "si", $value, $id);
            mysqli_stmt_execute($stmtUpdate);
        } else {
            // Insert
            mysqli_stmt_bind_param($stmtInsert, "sss", $masterKey, $key, $value);
            mysqli_stmt_execute($stmtInsert);
        }
        mysqli_stmt_free_result($stmtCheck);
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Close statements
mysqli_stmt_close($stmtCheck);
mysqli_stmt_close($stmtUpdate);
mysqli_stmt_close($stmtInsert);
