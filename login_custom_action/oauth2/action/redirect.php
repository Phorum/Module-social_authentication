<?php
// ----------------------------------------------------------------------------
// Redirect the user to the authorization page, to retrieve an access token.
// The authentication page will redirect back to us after the user has either
// accepted or declined the login operation.
// ----------------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// Build the redirect URL.
$redirect_url = $provider['oauth']['authorize_uri'] .
                "?client_id=" . $provider['oauth']['client_id'] .
                "&redirect_uri=" . urlencode($phorum_callback_url);

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
