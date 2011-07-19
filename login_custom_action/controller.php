<?php
// ----------------------------------------------------------------------
// This is the central controller script for the custom login handling.
// It is included from the main social_authentication.php module code.
// ----------------------------------------------------------------------

// This variable can be set to FALSE to suppress showing the
// login form below the regular login form.
$PHORUM['show_social_authentication_form'] = TRUE;

// Initialize template variable store.
$PHORUM['DATA']['MOD_SOCIAL_AUTHENTICATION'] = array();

// No handling required when no social authentication data was posted.
// For all requests in the authentication and registration workflow,
// we expect the social_authentication_provider parameter to be available
// in the request.
if (!isset($_POST['_sap']) && !isset($PHORUM['args']['_sap'])) {
    return;
}

// Both the OpenID and oAuth libraries require a PHP session.
if (!session_id()) session_start();

// Setup an easy access variable for module language strings.
$lang = $PHORUM['DATA']['LANG']['mod_social_authentication'];

// Lookup the configuration for the authentication provider,
// when a provider is available for the request.
$provider_id = NULL;
if (isset($PHORUM['args']['_sap'])) {
    $provider_id = $PHORUM['args']['_sap'];
}
if (isset($_POST['_sap'])) {
    $provider_id = $_POST['_sap'];
}
$provider = $provider_id
          ? mod_social_authentication_get_provider($provider_id) : NULL;

// When no provider config is found, then no further handling is required.
if (!$provider) {
    return;
}

// Grab the current authentication step from the request.
// The authentication step is stored in the "mod_social_authentication"
// parameter.
$step = NULL;
if (isset($PHORUM['args']['_sas'])) {
    $step = $PHORUM['args']['_sas'];
}
if (isset($_POST['_sas'])) {
    $step = $_POST['_sas'];
}
if ($step === NULL) trigger_error(
    'Illegal social authentication request: no authentication step ' .
    'was provided in the request (via the "_sas" parameter)', E_USER_ERROR
);

$PHORUM['DATA']['AUTHENTICATION_PROVIDER'] = htmlspecialchars($provider_id);

// The register step is handled by common code.
if ($step === 'register') {
  return include dirname(__FILE__) . '/common/register.php';
}

// Construct the callback URL for the authentication provider to call after
// the provider is done handling the auth request. We encode our
// arguments into a single request parameter, so we can use it as a
// standard GET parameter in the callback URL. When using standard Phorum
// parameter formatting, the data might get crippled, because of the
// mix between Phorum and GET args.
$redir = isset($PHORUM['args']['redir'])
       ? $PHORUM['args']['redir'] : $data['redir'];
$phorum_callback_url = phorum_api_url(
    PHORUM_LOGIN_URL,
    'redir=' . urlencode(urlencode($redir)),
    '_sas=complete',
    '_sap=' . urlencode($provider_id)
);

// Include the controller code that handles authentication for the
// provider's protocol type.
$proto = $provider['provider']['protocol'];
$proto_controller = dirname(__FILE__) . '/' . $proto . '/controller.php';
if (file_exists($proto_controller)) {
    include $proto_controller;
}

?>
