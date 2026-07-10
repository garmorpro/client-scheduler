<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!user_has_permission($conn, 'access_system_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You do not have permission to manage policies']);
    exit;
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$policyId = !empty($_POST['policy_id']) ? (int)$_POST['policy_id'] : 0;
$title = trim($_POST['title'] ?? '');
$userId = $_SESSION['user_id'];

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Please add a title.']);
    exit;
}

$pdfPath = null;
$pdfOriginalName = null;

// Editing without a new file keeps the existing upload.
if ($policyId) {
    $existingStmt = $conn->prepare("SELECT pdf_path, pdf_original_name FROM policies WHERE policy_id = ?");
    $existingStmt->bind_param('i', $policyId);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();
    if ($existing) {
        $pdfPath = $existing['pdf_path'];
        $pdfOriginalName = $existing['pdf_original_name'];
    }
}

if (!empty($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['pdf_file'];

    if ($file['size'] > 20 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'PDF must be smaller than 20MB.']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($mime !== 'application/pdf' || $ext !== 'pdf') {
        echo json_encode(['success' => false, 'error' => 'Please upload a PDF file.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/policies/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $storedName = 'policy_' . uniqid('', true) . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
        echo json_encode(['success' => false, 'error' => 'Could not save the uploaded file.']);
        exit;
    }

    // Replacing an existing file - remove the old one now that the new one is saved.
    if ($pdfPath && file_exists($uploadDir . $pdfPath)) {
        unlink($uploadDir . $pdfPath);
    }

    $pdfPath = $storedName;
    $pdfOriginalName = basename($file['name']);
}

if (!$pdfPath) {
    echo json_encode(['success' => false, 'error' => 'Please choose a PDF to upload.']);
    exit;
}

if ($policyId) {
    $stmt = $conn->prepare("UPDATE policies SET title = ?, source_type = 'pdf', pdf_path = ?, pdf_original_name = ?, updated_by = ? WHERE policy_id = ?");
    $stmt->bind_param('sssii', $title, $pdfPath, $pdfOriginalName, $userId, $policyId);
} else {
    $stmt = $conn->prepare("INSERT INTO policies (title, source_type, pdf_path, pdf_original_name, created_by, updated_by) VALUES (?, 'pdf', ?, ?, ?, ?)");
    $stmt->bind_param('sssii', $title, $pdfPath, $pdfOriginalName, $userId, $userId);
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    exit;
}
$policyId = $policyId ?: $stmt->insert_id;
$stmt->close();

echo json_encode(['success' => true, 'policy_id' => $policyId]);
