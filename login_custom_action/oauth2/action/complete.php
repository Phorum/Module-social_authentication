<?php

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// When there is an "error" parameter in the request, then (at least
// in case of Facebook) the user did not grant access on the
// authentication provider's site.
if (isset($_GET['error'])) {
    $data['error'] = $lang['ErrCancelled'];
    return;
}

// A code is expected in the request parameters.
if (!isset($_GET['code'])) {
    $data['error'] = $lang['ErrFailed'];
    return;
}
$code = $_GET['code'];

// The code can be used to retrieve an access token.
$url = $provider['oauth']['access_token_uri'] . '?' .
       'client_id=' .     urlencode($provider['oauth']['client_id']) . '&' .
       'redirect_uri=' .  urlencode($phorum_callback_url) . '&' .
       'client_secret=' . urlencode($provider['oauth']['client_secret']) . '&' .
       'code=' .          urlencode($code);
include PHORUM_PATH . '/include/api/http_get.php';
$body = phorum_api_http_get($url);
if (!$body) {
    $data['error'] = $lang['ErrFailed'];
    return;
}
if (preg_match('/access_token=([^\&]+)/', $body, $m)) {
    $access_token = $m[1];
} else {
    $data['error'] = $lang['ErrFailed'];
    return;
}

// Using the access token, we must be able to retrieve the profile information.
$url = $provider['oauth']['profile_data_uri'] .
       '?access_token=' . urlencode($access_token);
$body = phorum_api_http_get($url);
if (!$body) {
    $data['error'] = $lang['ErrFailed'];
    return;
}

// Decode the profile information data. 
switch ($provider['oauth']['profile_format'])
{
    case 'json':
        $input_data = json_decode($body, TRUE);
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
