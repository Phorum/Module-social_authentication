<?php
// ----------------------------------------------------------------------
// Handle step 3 of the OpenID authentication mechanism:
// The consumer site sends the browser a redirect to the identity
// server. This is the authentication request as described in
// the OpenID specification.
//
// Note: This script is included from discover.php, making all variables
// from that script (like $auth_request) available in here too.
//
// The script is called via an Ajax request, so responses must be
// handled using Phorum Ajax functions.
// ----------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// Setup the SREG (Simple REGistration) extension to request
// for profile information.
$sreg_request = Auth_OpenID_SRegRequest::build(
    array(), array_keys($request_attributes)
);
$auth_request->addExtension($sreg_request);

// Setup the AX (Attribute eXchange) extension to request
// for profile information.
$ax_request = new Auth_OpenID_AX_FetchRequest;
foreach ($request_attributes as $alias => $uri) {
    $ax_request->add(
        Auth_OpenID_AX_AttrInfo::make($uri, 1, FALSE, $alias)
    );
}
$auth_request->addExtension($ax_request);

// Setup the Provider Authentication Policy Extension (PAPE).
$pape_request = new Auth_OpenID_PAPE_Request(array(
    PAPE_AUTH_PHISHING_RESISTANT
));
if ($pape_request) {
    $auth_request->addExtension($pape_request);      
}

// For OpenID 1, send a redirect to the provider's website.
if ($auth_request->shouldSendRedirect())
{
    $redirect_url = $auth_request->redirectURL(
        phorum_api_url(PHORUM_BASE_URL),
        $phorum_callback_url
    );

    // Check if there was a failure building the redirect URL.
    if (Auth_OpenID::isFailure($redirect_url)) {
        phorum_ajax_error($lang['ErrRedirect']);
    }

    // If you enter linkedin.com, then a URL is returned that looks like
    // "?querystring", so no protocol and host. That will not work, since it's
    // a redirect to this site itself. Add a crude check here to see if
    // something like a URL is returned.
    if (!preg_match('!^https?://.+!', $redirect_url)) {
        phorum_ajax_error($lang['ErrRedirect']);
    }

    // Setup redirection for the template.
    $PHORUM['DATA']['REDIRECT']['METHOD'] = 'redirect';
    $PHORUM['DATA']['REDIRECT']['URL']    = addslashes($redirect_url);
}
// For OpenID 2, we send a form to the provider's website,
// by means of a javascript triggered form POST. 
else
{
    // Generate form markup and render it.
    $form_id = 'openid_message';
    $form_html = $auth_request->formMarkup(
        phorum_api_url(PHORUM_BASE_URL),
        $phorum_callback_url,
        false, array('id' => $form_id)
    );

    // Check if the form markup could be generated.
    if (Auth_OpenID::isFailure($form_html)) {
        phorum_ajax_error($lang['ErrRedirect']);
    }

    // Setup POST redirection for the template.
    $PHORUM['DATA']['REDIRECT']['METHOD']  = 'post';
    $PHORUM['DATA']['REDIRECT']['FORM']    = $form_html;
    $PHORUM['DATA']['REDIRECT']['FORM_ID'] = $form_id;
}

ob_start();
include phorum_api_template('social_authentication::redirect');
$redirect = ob_get_contents();
ob_end_clean();

phorum_ajax_return($redirect);

?>
