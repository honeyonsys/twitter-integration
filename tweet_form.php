<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tweet with Image</title>
</head>
<body>
    <h1>Post a Tweet with Image</h1>
    <form action="post_tweet.php" method="POST" enctype="multipart/form-data">
        <label for="tweet_text">Tweet Message:</label><br>
        <textarea id="tweet_text" name="tweet_text" rows="4" cols="50" maxlength="280" required></textarea><br><br>
        
        <label for="media">Upload Image (JPEG, PNG only):</label><br>
        <input type="file" id="media" name="media" accept=".jpg,.jpeg,.png" required><br><br>
        
        <button type="submit">Post Tweet</button>
    </form>
</body>
</html>
