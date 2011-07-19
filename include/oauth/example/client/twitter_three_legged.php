<?php

/**
 * oauth-php: Example OAuth client for accessing Google Docs
 *
 * @author BBG
 *
 * 
 * The MIT License
 * 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


include_once "../../library/OAuthStore.php";
include_once "../../library/OAuthRequester.php";

define("OAUTH_CONSUMER_KEY",      "UuEf7DBPdd2Ql1K3LFcNAw");
define("OAUTH_CONSUMER_SECRET",   "1341QKn56evopXbDFMZyB99oKKEtn4sIuVlMG2gEhA");

define("OAUTH_OAUTH_HOST",        "http://twitter.com");
define("OAUTH_REQUEST_TOKEN_URL", OAUTH_OAUTH_HOST . "/oauth/request_token");
define("OAUTH_AUTHORIZE_URL",     OAUTH_OAUTH_HOST . "/oauth/authenticate");
define("OAUTH_ACCESS_TOKEN_URL",  OAUTH_OAUTH_HOST . "/oauth/access_token");

define('OAUTH_TMP_DIR',
    function_exists('sys_get_temp_dir')
    ? sys_get_temp_dir() : realpath($_ENV["TMP"])
);

$store = OAuthStore::instance("Session", array(
	'consumer_key'      => OAUTH_CONSUMER_KEY, 
	'consumer_secret'   => OAUTH_CONSUMER_SECRET,
	'server_uri'        => OAUTH_OAUTH_HOST,
	'request_token_uri' => OAUTH_REQUEST_TOKEN_URL,
	'authorize_uri'     => OAUTH_AUTHORIZE_URL,
	'access_token_uri'  => OAUTH_ACCESS_TOKEN_URL
));

try
{
	//  STEP 1:  If we do not have an OAuth token yet, go get one
	if (empty($_GET["oauth_token"]))
	{
		// get a request token
		$tokenResultParams = OAuthRequester::requestRequestToken(
        OAUTH_CONSUMER_KEY, 0, array(
            'oauth_callback' => 'http://localhost/gitaar.net/mods/social_authentication/include/oauth/example/client/googledocs.php'
        )
    );

		//  redirect to the authorization page, they will redirect back
		header(
        "Location: " . OAUTH_AUTHORIZE_URL .
        "?oauth_token=" . $tokenResultParams['token']
    );
	}
	else
  {
		//  STEP 2:  Get an access token
		$oauthToken = $_GET["oauth_token"];
		$tokenResultParams = $_GET;

		try {
		    $access_token = OAuthRequester::requestAccessToken(
            OAUTH_CONSUMER_KEY, $oauthToken, 0,
            'POST', $_GET
        );
		}
		catch (OAuthException2 $e)
		{
			var_dump($e);
		    // Something wrong with the oauth_token.
		    // Could be:
		    // 1. Was already ok
		    // 2. We were not authorized
		    return;
		}

    $request = new OAuthRequester(
        OAUTH_OAUTH_HOST . '/account/verify_credentials.json'
    );
		$result = $request->doRequest(1);

    $data = json_decode($result['body']);

    print "<xmp>";
    print_r($data);
    print "</xmp>";
	}
}
catch(OAuthException2 $e) {
	echo "OAuthException:  " . $e->getMessage();
	var_dump($e);
}
?>
