<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload
require_once '../includes/db.php'; // defines $conn (mysqli)
require_once '../api/api_helper.php';
session_start();

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Microsoft App settings
$clientId = $_ENV['MS_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['MS_CLIENT_SECRET'] ?? null;
$redirectUri = "https://scheduler.morganserver.com/api/callback.php";

// DEBUG: ensure client ID/secret are loaded
if (empty($clientId) || empty($clientSecret)) {
    die(json_encode(['error'=>'Client ID or secret is empty','clientId'=>$clientId]));
}

// Check code from query
if (!isset($_GET['code'])) {
    die(json_encode(['error'=>'No code provided']));
}
$code = $_GET['code'];

// Get code verifier from session
$codeVerifier = $_SESSION['code_verifier'] ?? null;
if (!$codeVerifier) {
    die(json_encode(['error'=>'Code verifier missing']));
}

// Exchange code for tokens (PKCE flow)
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
    die(json_encode(['error'=>'cURL failed','message'=>curl_error($ch)]));
}
curl_close($ch);

$data = json_decode($response, true);
if (!$data || !isset($data['id_token'])) {
    die(json_encode(['error'=>'Failed to get ID token','response'=>$data]));
}

// Decode JWT (without signature verification for local testing)
$idTokenParts = explode('.', $data['id_token']);
$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

// Extract user info
$msId = $conn->real_escape_string($payload['sub']);
$email = $conn->real_escape_string($payload['preferred_username']);
$name = $conn->real_escape_string($payload['name'] ?? '');
$role = 'employee';

// Check if user exists
$result = $conn->query("SELECT * FROM users WHERE microsoft_id='$msId'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $role = $user['role'] ?? 'employee';
} else {
    // Insert new user
    $insert = $conn->query("INSERT INTO users (microsoft_id, email, name, role) VALUES ('$msId', '$email', '$name', '$role')");
    if (!$insert) {
        die(json_encode(['error'=>'Failed to insert user','message'=>$conn->error]));
    }
    $userId = $conn->insert_id;
}

// Create session
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role;

// DEBUG: show success
echo json_encode([
    'message'=>'Login successful',
    'user_id'=>$userId,
    'email'=>$email,
    'role'=>$role
]);

// Or redirect to frontend
// header("Location: /index.php");
// exit;
