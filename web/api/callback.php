<?php
require_once '../includes/db.php';
require_once '../api/api_helper.php';
session_start();

// Microsoft App settings
$clientId = "YOUR_MICROSOFT_APP_CLIENT_ID";
$clientSecret = "YOUR_MICROSOFT_APP_CLIENT_SECRET";
$redirectUri = "http://localhost/api/callback.php";

// Get code from query
if (!isset($_GET['code'])) {
    respond(['error' => 'No code provided'], 400);
}
$code = $_GET['code'];

// Exchange code for tokens
$tokenUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
$postFields = http_build_query([
    "client_id" => $clientId,
    "scope" => "openid profile email",
    "code" => $code,
    "redirect_uri" => $redirectUri,
    "grant_type" => "authorization_code",
    "client_secret" => $clientSecret,
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
    respond(['error' => 'Failed to get ID token'], 400);
}

// Decode JWT (simple, without verifying signature for local testing)
$idTokenParts = explode('.', $data['id_token']);
$payload = json_decode(base64_decode(strtr($idTokenParts[1], '-_', '+/')), true);

// Extract user info
$msId = $payload['sub'];
$email = $payload['preferred_username'];
$name = $payload['name'] ?? '';

// Check if user exists in MySQL
$stmt = $pdo->prepare("SELECT * FROM users WHERE microsoft_id = ?");
$stmt->execute([$msId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Create new user
    $stmt = $pdo->prepare("INSERT INTO users (microsoft_id, email, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$msId, $email, $name, 'employee']);
    $userId = $pdo->lastInsertId();
} else {
    $userId = $user['id'];
}

// Create session
$_SESSION['user_id'] = $userId;
$_SESSION['email'] = $email;
$_SESSION['role'] = $user['role'] ?? 'employee';

// Redirect to frontend dashboard (adjust as needed)
header("Location: /index.php");
exit;
