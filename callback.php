<?php
ob_start();
session_start();
require 'config.php';

$consumer_key = CONSUMER_KEY;
$consumer_secret = CONSUMER_SECRET;

if (!isset($_GET['oauth_token']) || !isset($_GET['oauth_verifier'])) {
    die("Invalid callback request. Missing OAuth token or verifier.");
}

$oauth_token = $_GET['oauth_token'] ?? null;
$oauth_token_secret = $_SESSION['oauth_token_secret'] ?? null;
$oauth_verifier = $_GET['oauth_verifier'] ?? null;

if (!$oauth_token || !$oauth_token_secret || !$oauth_verifier) {
    die("Required OAuth parameters are missing.");
}

// Step 3: Exchange request token for access token
$access_token_url = "https://api.twitter.com/oauth/access_token";

$oauth_nonce = bin2hex(random_bytes(16));
$oauth_timestamp = time();

$base_string = "POST&" . rawurlencode($access_token_url) . "&" . rawurlencode("oauth_consumer_key=$consumer_key&oauth_nonce=$oauth_nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$oauth_timestamp&oauth_token=$oauth_token&oauth_verifier=$oauth_verifier&oauth_version=1.0");
$signing_key = rawurlencode($consumer_secret) . "&" . rawurlencode($oauth_token_secret);
$oauth_signature = base64_encode(hash_hmac("sha1", $base_string, $signing_key, true));

$headers = [
    "Authorization: OAuth " .
    "oauth_consumer_key=\"$consumer_key\", " .
    "oauth_nonce=\"$oauth_nonce\", " .
    "oauth_signature=\"" . rawurlencode($oauth_signature) . "\", " .
    "oauth_signature_method=\"HMAC-SHA1\", " .
    "oauth_timestamp=\"$oauth_timestamp\", " .
    "oauth_token=\"$oauth_token\", " .
    "oauth_verifier=\"$oauth_verifier\", " .
    "oauth_version=\"1.0\""
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $access_token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);

parse_str($response, $access_token_data);

if (!isset($access_token_data['oauth_token']) || !isset($access_token_data['oauth_token_secret'])) {
    die("Failed to obtain access tokens.");
}

$access_token = $access_token_data['oauth_token'];
$access_token_secret = $access_token_data['oauth_token_secret'];

// Step 4: Upload media to get media_id
$media_url = "https://upload.twitter.com/1.1/media/upload.json";
$media_file_path = './airbus-a320.jpg';

$oauth_nonce = bin2hex(random_bytes(16));
$oauth_timestamp = time();

$base_string = "POST&" . rawurlencode($media_url) . "&" . rawurlencode("oauth_consumer_key=$consumer_key&oauth_nonce=$oauth_nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$oauth_timestamp&oauth_token=$access_token&oauth_version=1.0");
$signing_key = rawurlencode($consumer_secret) . "&" . rawurlencode($access_token_secret);
$oauth_signature = base64_encode(hash_hmac("sha1", $base_string, $signing_key, true));

$headers = [
    "Authorization: OAuth " .
    "oauth_consumer_key=\"$consumer_key\", " .
    "oauth_nonce=\"$oauth_nonce\", " .
    "oauth_signature=\"" . rawurlencode($oauth_signature) . "\", " .
    "oauth_signature_method=\"HMAC-SHA1\", " .
    "oauth_timestamp=\"$oauth_timestamp\", " .
    "oauth_token=\"$access_token\", " .
    "oauth_version=\"1.0\""
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $media_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['media' => new CURLFile($media_file_path)]);
$media_response = curl_exec($ch);
curl_close($ch);

$media_data = json_decode($media_response, true);
$media_id = $media_data['media_id_string'] ?? null;

if (!$media_id) {
    die("Failed to upload media.");
}

// Step 5: Post a Tweet with media
$tweet_url = "https://api.twitter.com/2/tweets";
$tweet_text = "Hello from my Twitter API integration with media!";

$oauth_nonce = bin2hex(random_bytes(16));
$oauth_timestamp = time();

$base_string = "POST&" . rawurlencode($tweet_url) . "&" . rawurlencode("oauth_consumer_key=$consumer_key&oauth_nonce=$oauth_nonce&oauth_signature_method=HMAC-SHA1&oauth_timestamp=$oauth_timestamp&oauth_token=$access_token&oauth_version=1.0");
$signing_key = rawurlencode($consumer_secret) . "&" . rawurlencode($access_token_secret);
$oauth_signature = base64_encode(hash_hmac("sha1", $base_string, $signing_key, true));

$headers = [
    "Authorization: OAuth " .
    "oauth_consumer_key=\"$consumer_key\", " .
    "oauth_nonce=\"$oauth_nonce\", " .
    "oauth_signature=\"" . rawurlencode($oauth_signature) . "\", " .
    "oauth_signature_method=\"HMAC-SHA1\", " .
    "oauth_timestamp=\"$oauth_timestamp\", " .
    "oauth_token=\"$access_token\", " .
    "oauth_version=\"1.0\"",
    "Content-Type: application/json"
];

$postfields = json_encode([
    'text' => $tweet_text,
    'media' => ['media_ids' => [$media_id]]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tweet_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
$tweet_response = curl_exec($ch);
curl_close($ch);

if ($tweet_response) {
    echo "Tweet posted successfully!";
    echo "<br>Response: " . $tweet_response;
} else {
    echo "Failed to post tweet.";
}
