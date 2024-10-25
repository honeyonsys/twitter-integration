#Steps to configure this app

1. Configure your KEY and SECRET you received from you twitter app into confir.php
2. use ngrok to expose your localhost application over an https url (incase working with this code locally).
3. configure the callback url into your twitter application
4. navigate to you local/ngrok host url /twitter-integration, it should take you to twitter authentication page and after authenticating your account it should reach back to your callback.php page and then finally at tweet_form.php.
5. You can use tweet_form to post tweet along with image until the session persist.