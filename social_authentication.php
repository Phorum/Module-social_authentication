<?php

if (!defined("PHORUM")) return;

define('PHORUM_MOD_SOCIAL_AUTHENTICATION', TRUE);

require_once PHORUM_PATH . '/mods/social_authentication/db.php';
require_once PHORUM_PATH . '/mods/social_authentication/api.php';

/**
 * Register the CSS code that is required for this module.
 *
 * @param array $data
 * @return array
 */
function phorum_mod_social_authentication_css_register($data)
{
    // Load the CSS code for this module.
    $data['register'][] = array(
        'module' => 'social_authentication',
        'where'  => 'after',
        'source' => 'template(social_authentication::css)'
    );

    return $data;
}

/**
 * Register the JavaScript code that is required for this module.
 *
 * @param array $data
 * @return array
 */
function phorum_mod_social_authentication_javascript_register($data)
{
    $data[] = array(
        'module' => 'social_authentication',
        'source' => 'file(mods/social_authentication/openid.js)'
    );

    return $data;
}

/**
 * When building a callback URL for the OpenID provider, then wrap up
 * the Phorum query parameters, to make them compatible with standard
 * GET parameter handling. The parse_request hook will be used to unwrap
 * the request data.
 */
function phorum_mod_social_authentication_url_build(
    $url, $name, $query_params, $suffix, $pathinfo)
{
    if ($name === 'login' &&
        in_array('_sas=complete', $query_params, TRUE))
    {
        $wrapped = '_saw=' .
                   base64_encode(implode(",", $query_params));
        $url = phorum_api_url(PHORUM_LOGIN_ACTION_URL, $wrapped);
    }

    return $url;
}

/**
 * Handle callback requests from authentication providers, which add info to our
 * callback URL using standard GET parameters. In the url_build function,
 * we have wrapped the Phorum parameters. Here, we need to unwrap them.
 */
function phorum_mod_social_authentication_parse_request()
{
    global $PHORUM;

    // Fix the query string for Phorum use.
    $query = $_SERVER['QUERY_STRING'];
    if (isset($_GET['_saw'])) {
        $_SERVER['QUERY_STRING'] = base64_decode($_GET['_saw']);
        unset($_GET['_saw']);
    }

    // Prepare the query string for the OpenID modules, which access the
    // query string directly.
    $params = array();
    if (trim($query) !== '')
    {
        $parts = explode("&", $query);
        foreach ($parts as $part) {
            $parts = explode("=", $part, 2);
            if (count($parts) != 2) {
                continue;
            }
            list($k, $v) = $parts;
            if ($k == '_saw') continue;
            $params[urldecode($k)] = urldecode($v);
        }
    }

    $parts = array();
    foreach ($params as $k => $v) {
        $parts[] = urlencode($k) . '=' . urlencode($v);
    }
    $PHORUM['MOD_SOCIAL_AUTHENTICATION_QUERY_STRING'] = implode("&", $parts);
}

/**
 * On the login page, bootstrap the social authentication code and handle
 * social authentication page requests.
 *
 * @param array $data
 * @return array
 */
function phorum_mod_social_authentication_login_custom_action($data)
{
    global $PHORUM;

    // Restore the query path for the OpenID and oAuth libraries,
    // which (just like Phorum) access the raw query string.
    // The query string was backupped by the parse_request hook code.
    $_SERVER['QUERY_STRING'] =
        $PHORUM['MOD_SOCIAL_AUTHENTICATION_QUERY_STRING'];

    // Because of the complexity of the code for this hook, the handling
    // is scripted in separate files.
    include dirname(__FILE__) . '/login_custom_action/controller.php';

    // Provide errors to the social authentication login form, instead
    // of using the standard global error message handling of the
    // Phorum login form.
    if (!empty($data['error'])) {
        $PHORUM['DATA']['SOCIAL_AUTHENTICATION_ERROR'] = $data['error'];
        $data['error'] = null;
    }

    return $data;
}

function phorum_mod_social_authentication_ajax($args)
{
    global $PHORUM;

    $PHORUM['args']['_sap'] =
        phorum_ajax_getarg('provider', 'string');

    $PHORUM['args']['authid'] =
        phorum_ajax_getarg('authid', 'string');

    $PHORUM['args']['_sas'] =
        phorum_ajax_getarg('action', 'string');

    $PHORUM['args']['redir'] = phorum_ajax_getarg(
        'redir', 'string', phorum_api_url(PHORUM_BASE_URL));

    // Handling for Ajax calls is done from the login_custom_action includes.
    include dirname(__FILE__) . '/login_custom_action/controller.php';

    phorum_ajax_error('Internal error: Ajax request not processed');
}

/**
 * Handle authenticating users that were authenticated via the
 * OpenID mechanism. The code from the action/ subdir will have
 * setup the variable $PHORUM['authenticated_via_socialauth'] with
 * a Phorum user if a valid association for a checked OpenID
 * was found.
 */
function phorum_mod_social_authentication_user_authenticate($data)
{
    global $PHORUM;

    if ($data['type'] !== PHORUM_FORUM_SESSION) {
        return $data;
    }

    if (isset($PHORUM['authenticated_via_socialauth']) &&
        !empty($PHORUM['authenticated_via_socialauth']['user_id'])) {
        $data['user_id'] = $PHORUM['authenticated_via_socialauth']['user_id'];
    }

    return $data;
}

/**
 * Add the social authentication login form before the footer on the login page.
 */
function phorum_mod_social_authentication_before_footer_login()
{
    global $PHORUM;

    $PHORUM['DATA']['AUTHPROVIDERS'] =
        empty($PHORUM['mod_social_authentication']['providers'])
        ? array() : $PHORUM['mod_social_authentication']['providers'];

    // If no authentication providers have been setup, then do not
    // show the social authentication login form.
    if (empty($PHORUM['DATA']['AUTHPROVIDERS'])) {
      return;
    }

    foreach ($PHORUM['DATA']['AUTHPROVIDERS'] as $id => $data) {
        $PHORUM['DATA']['AUTHPROVIDERS'][$id]['provider']['name'] =
            htmlspecialchars(
                $PHORUM['DATA']['AUTHPROVIDERS'][$id]['provider']['name']);
    }

    if ($PHORUM['show_social_authentication_form']) {
        include phorum_api_template('social_authentication::login');
    }
}

/**
 * Cleanup associations when a user is deleted.
 */
function phorum_mod_social_authentication_user_delete($user_id)
{
    $assocs = socialauth_get_for_user($user_id);
    foreach ($assocs as $assoc) {
        socialauth_db_disassociate($assoc['auth_id'], $user_id);
    }

    return $user_id;
}

?>
