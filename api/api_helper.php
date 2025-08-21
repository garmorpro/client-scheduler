<?php
// Tell the browser we are returning JSON
header("Content-Type: application/json");

// Simple function to respond with JSON and optional HTTP status code
function respond($data = [], $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
