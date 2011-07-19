<?php
// ----------------------------------------------------------------------
// Handle step 2 of the OpenID authentication mechanism:
// The consumer site discovers the user's OpenID server
// using the YADIS protocol.
//
// This step is called via Ajax from the GUI, so responses must
// be sent using the Phorum Ajax functions.
// ----------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// Construct the openid URL to use.
$openid = $provider['provider']['url'];
if ($provider['gui']['type'] == 'username') {
    $openid = str_replace('{username}', $PHORUM['args']['authid'], $openid);
} elseif ($provider['gui']['type'] == 'url') {
    $openid = $PHORUM['args']['authid'];
}

/**
 * This mapping contains a regular expression map, which is used to
 * convert OpenID values that users might enter into a correct
 * OpenID URL to use. For some well-known OpenID providers, we can
 * handle things like this here (e.g. users who enter their e-mail
 * address instead of an OpenID URL.)
 *
 * This is to strict enforcement of OpenID URL's that must be entered
 * by the user, but it surely makes the user experience better IMO.
 * When I first started out working with OpenID, I tried to login using
 * my gmail.com address and had to read up on OpenID to find out why that
 * didn't work. Using this OpenID rewriting, my experience would have
 * been a lot better.
 *
 * @var array
 */
// TODO use data from provider eini-files
$openid_map = array
(
    // gmail.com e-mail addresses.
    '/\@gmail\.com$/i' =>
        'https://www.google.com/accounts/o8/id',

    // Google's URL, which does not provide discovery info
    // of its own at the time of writing this code.
    '/^(https?:\/\/)?(www\.)?google\.com$/i' =>
        'https://www.google.com/accounts/o8/id', 

    // Simply the string "google".
    '/^google$/i' =>
        'https://www.google.com/accounts/o8/id', 

    // yahoo.com e-mail addresses.
    '/\@yahoo\.com$/i' =>
        'https://me.yahoo.com/',

    // Yahoo's URL, which does not (always?) provide discovery info. See:
    // http://developer.yahoo.net/forum/?showtopic=6874&endsession=1
    '/^(https?:\/\/)?(www\.)?yahoo\.com$/i' =>
        'https://me.yahoo.com/',

    // Simply the string "yahoo".
    '/^yahoo$/i' =>
        'https://me.yahoo.com/'

);

// Apply the openid map to the OpenID to handle rewriting. 
foreach ($openid_map as $match => $replace)
{
    if (preg_match($match, $openid)) {
        $openid = $replace; 
    }
}

// Check if the OpenID field is filled in.
// If not, then present an error to the user.
if ($openid === '') {
  $msg = str_replace('%authid%', $lang['OpenID'], $lang['AuthIDEmpty']);
  phorum_ajax_error($msg);
}

// Handle OpenID discovery and association.
// When this fails, then most likely the OpenID is invalid.
$auth_request = $consumer->begin($openid);
if (!$auth_request) {
  phorum_ajax_error($lang['AuthIDInvalid']);
}

// Include the redirect script to build the data that the client needs
// for the OpenID redirect step.
include dirname(__FILE__) . '/redirect.php';

