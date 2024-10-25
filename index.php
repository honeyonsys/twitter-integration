<?php
ob_start();
session_start();
require 'config.php';

$consumer_key = CONSUMER_KEY;
$consumer_secret = CONSUMER_SECRET;

// Step 1: Get request token
$request_token_url = "https://api.twitter.com/oauth/request_token";

// Generate signature
$oauth_nonce = bin2hex(random_bytes(16));
$oauth_timestamp = time();
$oauth_callback = CALLBACK_URL;

$base_string = "POST&" . rawurlencode($request_token_url) . "&" . rawurlencode("oauth_callback=" . rawurlencode($oauth_callback) .
    "&oauth_consumer_key=$consumer_key&oauth_nonce=$oauth_nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$oauth_timestamp&oauth_version=1.0");
$signing_key = rawurlencode($consumer_secret) . "&";
$oauth_signature = base64_encode(hash_hmac("sha1", $base_string, $signing_key, true));

$headers = [
    "Authorization: OAuth " .
    "oauth_callback=\"" . rawurlencode($oauth_callback) . "\", " .
    "oauth_consumer_key=\"$consumer_key\", " .
    "oauth_nonce=\"$oauth_nonce\", " .
    "oauth_signature=\"" . rawurlencode($oauth_signature) . "\", " .
    "oauth_signature_method=\"HMAC-SHA1\", " .
    "oauth_timestamp=\"$oauth_timestamp\", " .
    "oauth_version=\"1.0\""
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $request_token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

parse_str($response, $result);

// Check if response contains tokens
if (isset($result['oauth_token']) && isset($result['oauth_token_secret'])) {
    $_SESSION['oauth_token'] = $result['oauth_token'];
    $_SESSION['oauth_token_secret'] = $result['oauth_token_secret'];

    // Redirect to Twitter for authorization
    header("Location: https://api.twitter.com/oauth/authenticate?oauth_token=" . $result['oauth_token']);
    exit();
} else {
    echo "Error: Failed to get request tokens.";
    echo "<br>Response: " . $response;
}
