<?php
// ----------------------------------------------------------------------------
// Redirect the user to the authentication page, to retrieve an access token.
// The authentication page will redirect back to us after the user has either
// accepted or declined the login operation.
// ----------------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// Retrieve a request token.
$tokenResultParams = OAuthRequester::requestRequestToken(
    $provider['oauth']['consumer_key'], 0,
    array('oauth_callback' => $phorum_callback_url)
);

// Build the redirect URL.
$redirect_url = $provider['oauth']['authorize_uri'] .
                "?oauth_token=" . $tokenResultParams['token'];

// Setup redirection for the template.
$PHORUM['DATA']['REDIRECT']['METHOD'] = 'redirect';
$PHORUM['DATA']['REDIRECT']['URL']    = addslashes($redirect_url);

// Build the redirection code.
ob_start();
include phorum_api_template('social_authentication::redirect');
$redirect = ob_get_contents();
ob_end_clean();

// Return the redirection code to the GUI.
// The GUI will handle the actual redirection.
phorum_ajax_return($redirect);

?>
