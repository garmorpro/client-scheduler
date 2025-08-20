<?php
require_once '../includes/db.php'; // should define $dbHost, $dbUser, $dbPass, $dbName
require_once '../api/api_helper.php';
session_start();

// Microsoft App settings
$clientId = "d27315bd-3815-48d6-a27b-aeaa9fe2105a";
$redirectUri = "https://10.10.254.127/api/callback.php";

// Connect to MySQL (mysqli)
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    respond(['error' => 'DB connection failed', 'message' => $mysqli->connect_error], 500);
}

// Get code from query
if (!isset($_GET['code'])) {
    respond(['error' => 'No code provided'], 400);
}
$code = $_GET['code'];

// Get code verifier from session
$codeVerifier = $_SESSION['code_verifier'] ?? null;
if (!$codeVerifier) {
    respond(['error' => 'Code verifier missing'], 400);
}

// Exchange code for tokens (PKCE flow)
$tokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$postFields = http_build_query([
    "client_id" => $clientId,
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
curl_close($ch);

$data = json_decode($response, true);
if (!isset($data['id_token'])) {
    respond(['error' => 'Failed to get ID token', 'response' => $data], 400);
}

// Decode JWT (simple, without verifying signature for local testing)
$idTokenParts = explode('.', $data['id_token']);
$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

// Extract user info
$msId = $mysqli->real_escape_string($payload['sub']);
$email = $mysqli->real_escape_string($payload['preferred_username']);
$name = $mysqli->real_escape_string($payload['name'] ?? '');
$role = 'employee';

// Check if user exists
$result = $mysqli->query("SELECT * FROM users WHERE microsoft_id='$msId'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $role = $user['role'] ?? 'employee';
} else {
    // Insert new user
    $insert = $mysqli->query("INSERT INTO users (microsoft_id, email, name, role) VALUES ('$msId', '$email', '$name', '$role')");
    if (!$insert) {
        respond(['error' => 'Failed to insert user', 'message' => $mysqli->error], 500);
    }
    $userId = $mysqli->insert_id;
}

// Create session
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role;

// Redirect to frontend dashboard
header("Location: /index.php");
exit;
