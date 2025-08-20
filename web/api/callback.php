<?php
require_once '../vendor/autoload.php'; // phpdotenv
require_once '../includes/db.php'; // defines $conn
require_once '../api/api_helper.php';
session_start();

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

// Microsoft App settings
$clientId = getenv('CLIENT_ID');
$clientSecret = getenv('CLIENT_SECRET');
$redirectUri = "https://scheduler.morganserver.com/api/callback.php";

// Get code from query
if (!isset($_GET['code'])) {
    die(json_encode(['error' => 'No code provided']));
}
$code = $_GET['code'];

// Get code verifier from session (if using PKCE)
$codeVerifier = $_SESSION['code_verifier'] ?? null;

// Exchange code for tokens
$tokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$postFields = http_build_query([
    "client_id" => $clientId,
    "scope" => "openid profile email",
    "code" => $code,
    "redirect_uri" => $redirectUri,
    "grant_type" => "authorization_code",
    "client_secret" => $clientSecret
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

// Decode JWT (simple, no signature verification for local testing)
$idTokenParts = explode('.', $data['id_token']);
$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

// Extract user info
$msId = mysqli_real_escape_string($conn, $payload['sub']);
$email = mysqli_real_escape_string($conn, $payload['preferred_username']);
$name = mysqli_real_escape_string($conn, $payload['name'] ?? '');
$role = 'employee';

// Check if user exists
$result = $conn->query("SELECT * FROM users WHERE microsoft_id='$msId'");
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $role = $user['role'] ?? 'employee';
} else {
    $insert = $conn->query("INSERT INTO users (microsoft_id, email, name, role) VALUES ('$msId', '$email', '$name', '$role')");
    if (!$insert) {
        die(json_encode(['error' => 'Failed to insert user', 'message' => $conn->error]));
    }
    $userId = $conn->insert_id;
}

// Create session
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $role;

// Redirect or debug
// header("Location: /index.php");
// exit;
echo json_encode([
    'message' => 'Login successful',
    'user_id' => $userId,
    'email' => $email,
    'role' => $role
]);
