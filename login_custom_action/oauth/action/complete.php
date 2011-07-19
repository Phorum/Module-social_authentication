<?php

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// When there is a "denied" parameter in the request, then the user
// did not grant access on the authentication provider's site.
if (isset($_GET['denied'])) {
    $data['error'] = $lang['ErrCancelled'];
    return;
}

// By now, an oauth_token is expected in the parameters.
if (!isset($_GET['oauth_token'])) {
    $data['error'] = $lang['ErrFailed'];
    return;
}
$oauthToken = $_GET["oauth_token"];

try
{
    // Try to retrieve an access token from the autentication provider,
    // which we can use to retrieve the user's profile information.
    $access_token = OAuthRequester::requestAccessToken(
        $provider['oauth']['consumer_key'], $oauthToken, 0, 'POST', $_GET
    );  

    // Try to retrieve the profile information for the user.
    $request = new OAuthRequester($provider['oauth']['profile_data_uri']);  
    $result  = $request->doRequest(0);
}   
catch (Exception $e)
{
    $data['error'] = $lang['ErrFailed'];
    return;
}   

// Decode the profile information data. 
switch ($provider['oauth']['profile_format'])
{
    case 'json':
        $input_data = json_decode($result['body'], TRUE);
        break;

    default:
        $data['error'] = "Internal error: no valid profile_format " .
                         "defined for oauth provider '$provider_id'"; 
        return;
}

// Extract profile field information, based on the config.
// The information is stored in the $profile variable, which will
// be processed by the check_phorum_association.php script.
$profile = array();
foreach ($provider['oauth'] as $profile_field => $input_field) {
    if (substr($profile_field, 0, 6) === 'field_') {
        $profile_field = substr($profile_field, 6); 
        $profile[$profile_field] = NULL;
        if ($input_field !== false && isset($input_data[$input_field])) {
            $profile[$profile_field] = $input_data[$input_field]; 
        }
    }
}

// Make the auth_id unique. If no auth_id is set by now, ignore this.
// The check script will handle the fault case for this.
if (isset($profile['auth_id'])) {
    $profile['auth_id'] = "{$provider_id}:{$profile['auth_id']}";
}

include dirname(__FILE__) . '/../../common/check_phorum_association.php';

?>
