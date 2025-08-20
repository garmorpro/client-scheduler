<?php
require_once '../api/api_helper.php';
session_start();

// Generate code verifier and challenge
$verifier = bin2hex(random_bytes(64));
$_SESSION['code_verifier'] = $verifier;
$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

$clientId = "d27315bd-3815-48d6-a27b-aeaa9fe2105a";
$redirectUri = "https://10.10.254.127/api/callback.php";
$scopes = "openid profile email";

$authUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
    "client_id" => $clientId,
    "response_type" => "code",
    "redirect_uri" => $redirectUri,
    "response_mode" => "query",
    "scope" => $scopes,
    "code_challenge" => $challenge,
    "code_challenge_method" => "S256"
]);

header("Location: $authUrl");
exit;
