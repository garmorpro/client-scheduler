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
$sourceType = ($_POST['source_type'] ?? '') === 'pdf' ? 'pdf' : 'editor';

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Please add a title.']);
    exit;
}

if ($sourceType === 'pdf') {
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
        $stmt = $conn->prepare("UPDATE policies SET title = ?, source_type = 'pdf', doc_type = 'policy', effective_date = NULL, memo_to = NULL, memo_from = NULL, content = NULL, pdf_path = ?, pdf_original_name = ?, updated_by = ? WHERE policy_id = ?");
        $stmt->bind_param('sssii', $title, $pdfPath, $pdfOriginalName, $userId, $policyId);
    } else {
        $stmt = $conn->prepare("INSERT INTO policies (title, source_type, doc_type, pdf_path, pdf_original_name, created_by, updated_by) VALUES (?, 'pdf', 'policy', ?, ?, ?, ?)");
        $stmt->bind_param('sssii', $title, $pdfPath, $pdfOriginalName, $userId, $userId);
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }
    $policyId = $policyId ?: $stmt->insert_id;
    $stmt->close();
} else {
    $content = $_POST['content'] ?? '';
    $docType = ($_POST['doc_type'] ?? '') === 'memo' ? 'memo' : 'policy';

    $effectiveDate = trim($_POST['effective_date'] ?? '');
    $effectiveDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $effectiveDate) ? $effectiveDate : null;

    $memoTo = $docType === 'memo' ? trim($_POST['memo_to'] ?? '') : '';
    $memoFrom = $docType === 'memo' ? trim($_POST['memo_from'] ?? '') : '';
    $memoTo = $memoTo !== '' ? $memoTo : null;
    $memoFrom = $memoFrom !== '' ? $memoFrom : null;

    if (trim(strip_tags($content)) === '') {
        echo json_encode(['success' => false, 'error' => 'Please add some content.']);
        exit;
    }

    $oldPdfPath = null;
    if ($policyId) {
        $existingStmt = $conn->prepare("SELECT pdf_path FROM policies WHERE policy_id = ?");
        $existingStmt->bind_param('i', $policyId);
        $existingStmt->execute();
        $oldRow = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();
        $oldPdfPath = $oldRow['pdf_path'] ?? null;
    }

    if ($policyId) {
        $stmt = $conn->prepare("UPDATE policies SET title = ?, source_type = 'editor', doc_type = ?, effective_date = ?, memo_to = ?, memo_from = ?, content = ?, pdf_path = NULL, pdf_original_name = NULL, updated_by = ? WHERE policy_id = ?");
        $stmt->bind_param('ssssssii', $title, $docType, $effectiveDate, $memoTo, $memoFrom, $content, $userId, $policyId);
    } else {
        $stmt = $conn->prepare("INSERT INTO policies (title, source_type, doc_type, effective_date, memo_to, memo_from, content, created_by, updated_by) VALUES (?, 'editor', ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssii', $title, $docType, $effectiveDate, $memoTo, $memoFrom, $content, $userId, $userId);
    }

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
        exit;
    }
    $policyId = $policyId ?: $stmt->insert_id;
    $stmt->close();

    // Clean up an old PDF file if this document was previously a PDF upload.
    if (!empty($oldPdfPath)) {
        $oldFile = __DIR__ . '/../assets/uploads/policies/' . $oldPdfPath;
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }
}

echo json_encode(['success' => true, 'policy_id' => $policyId]);
