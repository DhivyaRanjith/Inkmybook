<?php
require_once '../../config/oauth.php';

$generated_uri = oauth_base_url() . '/modules/auth/oauth_callback.php';

echo "<h1>OAuth Configuration Check</h1>";
echo "<p><strong>Error Explanation:</strong> The 'redirect_uri_mismatch' error means the URL your website sends to Google doesn't match the one you put in the Google Cloud Console.</p>";
echo "<hr>";
echo "<h3>Your Application is generating this Redirect URI:</h3>";
echo "<code style='background: #f0f0f0; padding: 10px; display: block; font-size: 1.2em;'>" . htmlspecialchars($generated_uri) . "</code>";
echo "<hr>";
echo "<h3>What you need to do:</h3>";
echo "<ol>";
echo "<li>Copy the URL above exactly.</li>";
echo "<li>Go to your <a href='https://console.cloud.google.com/apis/credentials' target='_blank'>Google Cloud Console > Credentials</a>.</li>";
echo "<li>Edit your OAuth 2.0 Client ID.</li>";
echo "<li>Paste the URL into the <strong>Authorized redirect URIs</strong> section.</li>";
echo "<li>Save changes and try logging in again.</li>";
echo "</ol>";
