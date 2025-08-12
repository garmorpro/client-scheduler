<?php
ob_start();  // Start output buffering to catch any unexpected output

require_once '../includes/db.php'; // $conn as mysqli connection
session_start();

header('Content-Type: application/json');

// Uncomment to enable error reporting to log file (recommended for production)
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// ini_set('error_log', __DIR__ . '/error_log.txt');

// You can add your admin/session checks here
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
//     exit;
// }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['setting_master_key'], $data['settings']) || !is_array($data['settings'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$masterKey = $data['setting_master_key'];
$settings = $data['settings'];

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
        mysqli_stmt_reset($stmtCheck);
        mysqli_stmt_bind_param($stmtCheck, "ss", $masterKey, $key);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);

        if (mysqli_stmt_num_rows($stmtCheck) > 0) {
            mysqli_stmt_bind_result($stmtCheck, $id);
            mysqli_stmt_fetch($stmtCheck);

            mysqli_stmt_reset($stmtUpdate);
            mysqli_stmt_bind_param($stmtUpdate, "si", $value, $id);
            if (!mysqli_stmt_execute($stmtUpdate)) {
                throw new Exception('Update failed for key ' . $key . ': ' . mysqli_stmt_error($stmtUpdate));
            }
        } else {
            mysqli_stmt_reset($stmtInsert);
            mysqli_stmt_bind_param($stmtInsert, "sss", $masterKey, $key, $value);
            if (!mysqli_stmt_execute($stmtInsert)) {
                throw new Exception('Insert failed for key ' . $key . ': ' . mysqli_stmt_error($stmtInsert));
            }
        }
        mysqli_stmt_free_result($stmtCheck);
    }
    mysqli_commit($conn);
    ob_end_clean(); // clear output buffer before sending JSON
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_stmt_close($stmtCheck);
mysqli_stmt_close($stmtUpdate);
mysqli_stmt_close($stmtInsert);
