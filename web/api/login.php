<?php
require_once '../api/api_helper.php';

// Microsoft App settings
$clientId = "d27315bd-3815-48d6-a27b-aeaa9fe2105a";
$redirectUri = "http://10.10.254.127/api/callback.php"; // must match your app registration
$scopes = "openid profile email";

// Build Microsoft login URL
$authUrl = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
    "client_id" => $clientId,
    "response_type" => "code",
    "redirect_uri" => $redirectUri,
    "response_mode" => "query",
    "scope" => $scopes,
]);

// Redirect user to Microsoft login
header("Location: $authUrl");
exit;
