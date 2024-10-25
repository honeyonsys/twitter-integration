<?php
session_start();
require 'config.php';

$consumer_key = CONSUMER_KEY;
$consumer_secret = CONSUMER_SECRET;

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tweet_text = $_POST['tweet_text'] ?? '';
    $media = $_FILES['media'] ?? null;

    if (!$tweet_text || !$media) {
        die("Please enter tweet text and upload an image.");
    }

    // Validate file type
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $file_extension = strtolower(pathinfo($media['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
        die("Invalid file type. Only JPEG and PNG images are allowed.");
    }

    $temp_path = $media['tmp_name'];
    $access_token = $_SESSION['oauth_token'];
    $access_token_secret = $_SESSION['oauth_token_secret'];

    // Step 1: Upload media
    $media_url = "https://upload.twitter.com/1.1/media/upload.json";
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
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['media' => new CURLFile($temp_path)]);
    $media_response = curl_exec($ch);
    curl_close($ch);

    $media_data = json_decode($media_response, true);
    $media_id = $media_data['media_id_string'] ?? null;

    if (!$media_id) {
        die("Failed to upload media.");
    }

    // Step 2: Post the tweet with media
    $tweet_url = "https://api.twitter.com/2/tweets";
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

    if ($tweet_response === false) {
        die("Failed to post tweet.");
    }

    echo "Tweet posted successfully!";
}
?>
