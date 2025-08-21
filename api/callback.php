<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ---------------- AUTLOAD -----------------------------
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
require_once __DIR__ . '/../includes/db.php';      // $conn (mysqli)
require_once __DIR__ . '/api_helper.php';
// ------------------------------------------------------

// ---------------- ENVIRONMENT SETUP -------------------
use Dotenv\Dotenv;

$dotenvPath = '/var/www/client-scheduler';
if (!file_exists($dotenvPath . '/.env')) {
    die(json_encode([
        'error' => 'Missing .env file',
        'path'  => $dotenvPath . '/.env'
    ]));
}

$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->safeLoad(); // safeLoad avoids fatal errors if file missing

$clientId = $_ENV['MS_CLIENT_ID'] ?? getenv('MS_CLIENT_ID') ?? null;
$clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? getenv('MS_CLIENT_SECRET') ?? null;

if (empty($clientId) || empty($clientSecret)) {
    die(json_encode([
        'error' => 'Client ID or secret is empty',
        'clientId' => $clientId,
        'clientSecret' => $clientSecret
    ]));
}

$redirectUri = "https://scheduler.morganserver.com/api/callback.php";
// ------------------------------------------------------

// ---------------- GET AUTH CODE ----------------------
$code = $_GET['code'] ?? null;
if (!$code) {
    die(json_encode(['error' => 'No code provided']));
}

$codeVerifier = $_SESSION['code_verifier'] ?? null;
if (!$codeVerifier) {
    die(json_encode(['error' => 'Code verifier missing']));
}
// ------------------------------------------------------

// ---------------- EXCHANGE CODE FOR TOKEN -------------
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
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);

$response = curl_exec($ch);
if ($response === false) {
    die(json_encode(['error' => 'cURL failed', 'message' => curl_error($ch)]));
}
curl_close($ch);

$data = json_decode($response, true);
if (!$data || !isset($data['id_token'])) {
    die(json_encode(['error' => 'Failed to get ID token', 'response' => $data]));
}
// ------------------------------------------------------

// ---------------- DECODE JWT --------------------------
$idTokenParts = explode('.', $data['id_token']);
if (count($idTokenParts) !== 3) {
    die(json_encode(['error' => 'Invalid ID token format']));
}

$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);
if (!$payload) {
    die(json_encode(['error' => 'Failed to decode JWT payload']));
}

// ---- TOKEN EXPIRATION CHECK ----
$tokenExp = $payload['exp'] ?? null;
if ($tokenExp && time() >= $tokenExp) {
    session_unset();
    session_destroy();
    header("Location: /pages/logout.php?expired=1");
    exit;
}
// ------------------------------------------------------

// ---------------- EXTRACT USER INFO -------------------
$msId      = $conn->real_escape_string($payload['sub'] ?? '');
$email     = $conn->real_escape_string($payload['preferred_username'] ?? '');
$fullName  = $conn->real_escape_string($payload['name'] ?? '');
$role      = 'staff';
$now       = date('Y-m-d H:i:s');
// ------------------------------------------------------

// ---------------- DB USER LOOKUP ----------------------
$result = $conn->query("SELECT * FROM ms_users WHERE microsoft_id='$msId'");

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['user_id'];
    $fullName = $user['full_name'];
    $role = $user['role'] ?? 'staff';

    $updateSql = "UPDATE ms_users SET last_active='$now' WHERE user_id=$userId";
    if (!$conn->query($updateSql)) {
        die(json_encode(['error' => 'Failed to update last_active', 'sql_error' => $conn->error]));
    }

} else {
    // Insert new user
    $insertSql = "INSERT INTO ms_users (microsoft_id, email, full_name, role, last_active) 
                  VALUES ('$msId', '$email', '$fullName', '$role', '$now')";
    if (!$conn->query($insertSql)) {
        die(json_encode(['error' => 'Failed to insert user', 'sql_error' => $conn->error]));
    }
    $userId = $conn->insert_id;
}
// ------------------------------------------------------

// ---------------- CREATE SESSION ----------------------
$_SESSION['user_id']    = $userId;
$_SESSION['full_name']  = $fullName;
$_SESSION['email']      = $email;
$_SESSION['user_role']  = $role;
// ------------------------------------------------------

// ---------------- REDIRECT ---------------------------
if ($role === 'admin') {
    header("Location: /pages/admin-panel.php");
} else {
    header("Location: /pages/my-schedule.php");
}
exit;
