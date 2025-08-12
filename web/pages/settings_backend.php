<?php
require_once '../includes/db.php'; // $conn as mysqli connection
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Read and decode input
$data = json_decode(file_get_contents('php://input'), true);
error_log("Received data: " . print_r($data, true));

if (!isset($data['setting_master_key'], $data['settings']) || !is_array($data['settings'])) {
    error_log("Invalid input detected");
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$masterKey = $data['setting_master_key'];
$settings = $data['settings'];

$stmtCheck = mysqli_prepare($conn, "SELECT id FROM settings WHERE setting_master_key = ? AND setting_key = ?");
$stmtUpdate = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE id = ?");
$stmtInsert = mysqli_prepare($conn, "INSERT INTO settings (setting_master_key, setting_key, setting_value) VALUES (?, ?, ?)");

if (!$stmtCheck || !$stmtUpdate || !$stmtInsert) {
    error_log("Failed to prepare statements");
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statements']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    foreach ($settings as $key => $value) {
        error_log("Processing setting key=$key, value=$value");

        mysqli_stmt_reset($stmtCheck);
        mysqli_stmt_bind_param($stmtCheck, "ss", $masterKey, $key);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);

        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            mysqli_stmt_bind_result($stmtCheck, $id);
            mysqli_stmt_fetch($stmtCheck);
            error_log("Found existing setting id=$id for key=$key, updating");

            mysqli_stmt_reset($stmtUpdate);
            mysqli_stmt_bind_param($stmtUpdate, "si", $value, $id);
            if (!mysqli_stmt_execute($stmtUpdate)) {
                $err = mysqli_stmt_error($stmtUpdate);
                error_log("Update failed for key $key: $err");
                throw new Exception('Update failed for key ' . $key . ': ' . $err);
            }
        } else {
            error_log("Setting key=$key not found, inserting new");
            mysqli_stmt_reset($stmtInsert);
            mysqli_stmt_bind_param($stmtInsert, "sss", $masterKey, $key, $value);
            if (!mysqli_stmt_execute($stmtInsert)) {
                $err = mysqli_stmt_error($stmtInsert);
                error_log("Insert failed for key $key: $err");
                throw new Exception('Insert failed for key ' . $key . ': ' . $err);
            }
        }
        mysqli_stmt_free_result($stmtCheck);
    }

    mysqli_commit($conn);
    error_log("All settings saved successfully");
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_stmt_close($stmtCheck);
mysqli_stmt_close($stmtUpdate);
mysqli_stmt_close($stmtInsert);
