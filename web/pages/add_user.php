<?php
require_once '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// LOG ACTIVITY FUNCTION
function logActivity($conn, $eventType, $user_id, $email, $full_name, $title, $description) {
    $sql = "INSERT INTO system_activity_log (event_type, user_id, email, full_name, title, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sissss", $eventType, $user_id, $email, $full_name, $title, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Generate unique 6-digit idno
function generateUniqueIdno($conn) {
    do {
        $idno = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE idno = ?");
        $stmt->bind_param("s", $idno);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } while ($count > 0);
    return $idno;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? '';

    if ($firstName === '' || $lastName === '' || $email === '' || $role === '') {
        die("Please fill all required fields.");
    }

    // Generate IDNO
    $idno = generateUniqueIdno($conn);

    // Set default password ("change_me") hashed
    $defaultPassword = password_hash("change_me", PASSWORD_DEFAULT);

    // Default values for new user
    $isVerified = 0; // Force verification/change password later
    $lastActive = NULL; // Not active yet

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (idno, first_name, last_name, email, password, role, is_verified, last_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    // Bind params: s = string, i = integer, etc.
    $stmt->bind_param(
        "ssssssis",
        $idno,
        $firstName,
        $lastName,
        $email,
        $defaultPassword,
        $role,
        $isVerified,
        $lastActive,
    );

    if ($stmt->execute()) {
        $stmt->close();

        // Log activity
        $adminUserId = $_SESSION['user_id'] ?? null;
        $adminEmail  = $_SESSION['email'] ?? '';
        $adminName   = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

        $title = "User Created";
        $description = "Created new user for $firstName $lastName ($role)";

        logActivity($conn, "user_created", $adminUserId, $adminEmail, $adminName, $title, $description);

        header("Location: admin-panel.php?status=success");
        exit();
    } else {
        die("Error creating user: " . $stmt->error);
    }
} else {
    die('Invalid request.');
}
?>
