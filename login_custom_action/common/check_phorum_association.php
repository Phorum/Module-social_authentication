<?php
// ----------------------------------------------------------------------
// This script can be included after the authentication of the user has
// been checked and found valid. Also, the profile information for the
// user must be retreived by now. In this script, we will check if the
// authentication is associated to a Phorum user.
//
// - If not, then a registration step has to be performed
//   to let the user associate the OpenID to a (possibly new) Phorum
//   account.
//
// - If yes, then an authenticated user session is setup for
//   the associated Phorum user.
//
// The script expects the variable $profile to be filled with information
// about the user. Keys in this array are:
//
// - auth_id
// - nickname
// - fullname
// - email
//
// The auth_id field is mandatory for this process step. The other fields
// provide defaults for the registration process (in register.php)
// ----------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// The auth_id field must be set in the profile data.
if (empty($profile['auth_id'])) {
    $data['error'] = 'Internal error: the auth_id field is not set ' .
                     'in the profile data';
    return;
}

// Check if there is an association available in the database for
// the checked authentication. If yes, then setup a user session for the
// associated user.
$association = socialauth_db_get($profile['auth_id']);
if ($association !== NULL)
{
    // Check if the user from the association exists.
    $user = phorum_api_user_get($association['user_id']);

    // If not, then cleanup the association data. After this,
    // the user will be redirected to the profile registration page.
    if ($user === NULL) {
        socialauth_db_disassociate(
            $profile['auth_id'], $association['user_id']
        );
    }
    // If a user does exist, then activate a Phorum user session for this user.
    // We will do so by setting up some data and handing over control to the
    // login script. We will authenticate the user from the user_authenticate
    // hook in this openid module.
    else
    {
        $PHORUM['authenticated_via_socialauth'] = $user;
        $_POST['redir']    = $data['redir'];
        $_POST['username'] = $user['username'];
        $_POST['password'] = 'DUMMYPASSWORD';
        return;
    }
}

// If we get here, then there is no existing association available
// for the authentication id. Enter the profile registration process.
// The data for this process is stored in a PHP session.
$_SESSION['mod_social_authentication'] = $profile;

include dirname(__FILE__) . '/register.php';

?>
