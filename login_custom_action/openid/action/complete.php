<?php
// ----------------------------------------------------------------------
// Handle step 4 of the OpenID authentication mechanism:
// The identity server's site sends the browser a redirect back
// to the consumer site.  This redirect contains the server's
// response to the authentication request.
// ----------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

$response = $consumer->complete($phorum_callback_url);

// Check if the authentication was cancelled (normally by the user).
if ($response->status == Auth_OpenID_CANCEL) {
    $data['error'] = $lang['ErrCancelled'];
    return;
}

// Check if the authentication failed.
if ($response->status == Auth_OpenID_FAILURE) {
    $data['error'] = $lang['ErrFailed'];
    return;
}

if ($response->status == Auth_OpenID_SUCCESS)
{
    // Gather profile data that was returned by the OpenID provider.
    $profile = array(
        'auth_id'  => 'openid:' . $response->getDisplayIdentifier(),
        'nickname' => NULL,
        'fullname' => NULL,
        'email'    => NULL
    );

    // Retrieve possible SREG response data.
    $sreg = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
    if (!empty($sreg)) {
        $contents = $sreg->contents();
        foreach ($contents as $key => $val) {
            $profile[$key] = $val;
        }
    }

    // Retrieve possible AX response data.
    $ax = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response);
    if ($ax) {
        foreach ($request_attributes as $key => $uri) {
            $val = $ax->getSingle($uri);
            if (!$val instanceof Auth_OpenID_AX_Error && $val !== NULL) {
                $profile[$key] = $val;
            }
        }
    }

    // Authentication and data retrieval was successful.
    // Include the script that checks if the openid is already associated
    // with a Phorum login.
    include dirname(__FILE__) . '/../../common/check_phorum_association.php';

    return;
}

$data['error'] =
    'Internal error: unexpected OpenID response status: ' .
    $response->status;

?>
