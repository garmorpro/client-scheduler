<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
require_once '../includes/db.php'; // defines $conn (mysqli)
require_once '../api/api_helper.php';
session_start();

// ---------------- SESSION TIMEOUT (INACTIVITY) ----------------
$timeout_duration = 60; // 30 minutes

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];
    if ($elapsed_time > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: /pages/logout.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();
// --------------------------------------------------------------

// ---------------- ENVIRONMENT SETUP ---------------------------
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$clientId = $_ENV['MS_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? null;

if (empty($clientId) || empty($clientSecret)) {
    die(json_encode([
        'error' => 'Client ID or secret is empty',
        'clientId' => $clientId,
        'clientSecret' => $clientSecret
    ]));
}

$redirectUri = "https://scheduler.morganserver.com/api/callback.php";
// --------------------------------------------------------------

// ---------------- EXCHANGE CODE FOR TOKENS --------------------
if (!isset($_GET['code'])) {
    die(json_encode(['error' => 'No code provided']));
}
$code = $_GET['code'];

$codeVerifier = $_SESSION['code_verifier'] ?? null;
if (!$codeVerifier) {
    die(json_encode(['error' => 'Code verifier missing']));
}

$tokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$postFields = http_build_query([
    "client_id" => $clientId,
    "client_secret" => $clientSecret,
    "scope" => "openid profile email",
    "code" => $code,
    "redirect_uri" => $redirectUri,
    "grant_type" => "authorization_code",
    "code_verifier" => $codeVerifier
]);

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);

if ($response === false) {
    die(json_encode(['error' => 'cURL failed', 'message' => curl_error($ch)]));
}

curl_close($ch);
$data = json_decode($response, true);

if (!$data || !isset($data['id_token'])) {
    die(json_encode(['error' => 'Failed to get ID token', 'response' => $data]));
}
// --------------------------------------------------------------

// ---------------- DECODE JWT PAYLOAD --------------------------
$idTokenParts = explode('.', $data['id_token']);
$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

// ---- TOKEN EXPIRATION CHECK ----
if (isset($payload['exp']) && time() >= $payload['exp']) {
    session_unset();
    session_destroy();
    header("Location: /pages/logout.php?expired=1");
    exit;
}
// --------------------------------------------------------------

// ---------------- EXTRACT USER INFO ---------------------------
$msId = $conn->real_escape_string($payload['sub'] ?? '');
$email = $conn->real_escape_string($payload['preferred_username'] ?? '');
$full_name = $conn->real_escape_string($payload['name'] ?? '');
$role = 'staff';
// --------------------------------------------------------------

// ---------------- DB USER LOOKUP ------------------------------
$result = $conn->query("SELECT * FROM ms_users WHERE microsoft_id='$msId'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['user_id'];
    $fullName = $user['full_name'];
    $role = $user['role'] ?? 'staff';
} else {
    $insert = $conn->query("INSERT INTO ms_users (microsoft_id, email, full_name, role) 
                            VALUES ('$msId', '$email', '$full_name', '$role')");
    if (!$insert) {
        die(json_encode(['error' => 'Failed to insert user', 'message' => $conn->error]));
    }
    $userId = $conn->insert_id;
    $fullName = $full_name; // fallback for session
}
// --------------------------------------------------------------

// ---------------- CREATE SESSION ------------------------------
$_SESSION['user_id'] = $userId;
$_SESSION['full_name'] = $fullName;
$_SESSION['email'] = $email;
$_SESSION['user_role'] = $role;
$_SESSION['token_exp'] = $payload['exp'] ?? null; // store token expiry
// --------------------------------------------------------------

// ---------------- REDIRECT ------------------------------
if ($role === 'admin') {
    header("Location: /pages/admin-panel.php");
    exit;
} else {
    header("Location: /pages/my-schedule.php");
    exit;
}
