<?php
// ----------------------------------------------------------------------
// The authentication for the visistor is validated, but there is no
// Phorum user available that is associated to the authentication id.
// This script takes care of the registration process for a
// Phorum account.
// ----------------------------------------------------------------------

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// A small helper function for easier writing.
function noxss($str) {
    global $PHORUM;
    return htmlspecialchars($str, ENT_COMPAT, $PHORUM['DATA']['HCHARSET']);
}

// Retrieve profile data from the session.
// This data was set by check_phorum_association.php.
$profile = $_SESSION['mod_social_authentication'];

// Check at which step we are.
$step = empty($_POST['step']) ? 0 : (int) $_POST['step'];

// Step 0: try to generate as much field data as possible, based
// on the profile data that we received from the authentication provider.
if ($step === 0)
{
    // First, turn all profile data fields into clean strings.
    foreach ($profile as $field => $value) {
        $profile[$field] = $value === NULL ? '' : trim($value);
    }

    // If no nickname is available, but the fullname is set, then
    // generate a nickname based on the fullname. If no fullname
    // is available, then fallback to the email address. If all
    // this fails, then it's up to the user to use his imagination
    // for a new username.
    if (!isset($profile['nickname']) || $profile['nickname'] === '')
    {
        if (isset($profile['fullname']) && $profile['fullname'] !== '') {
            $nickname = preg_replace('/[^\w\.-]/', '', $profile['fullname']);
            $profile['nickname'] = $nickname;
        }
        elseif (isset($profile['email']) && $profile['email'] !== '') {
            list ($name, $domain) = explode('@', $profile['email']);
            $nickname = preg_replace('/[^\w\.-]/', '', $name);
            $profile['nickname'] = $nickname;
        }
    }

    // Search for a free username.
    if (isset($profile['nickname']) && $profile['nickname'] !== '')
    {
        $add = '';
        for (;;) {
            $user = phorum_api_user_search(
                'username', $profile['nickname'] . $add);
            if ($user) {
                $add ++;
                continue;
            } else {
                break;
            }
        }
        $profile['nickname'] = $profile['nickname'] . $add;
    }

    // If the email address is already taken by another Phorum user,
    // then clear this field.
    if (isset($profile['email']) && $profile['email'] !== '') {
        if (phorum_api_user_search("email", $profile['email'])) {
            $profile['email'] = '';
        }
    }

    $step = 1;
}
// Step 1: Check entered data.
elseif ($step === 1)
{
    $nickname = trim($_POST['nickname']);
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);

    $profile['nickname'] = $nickname;
    $profile['fullname'] = $fullname;
    $profile['email']    = $email;

    if ($nickname === '') {
        $data['error'] = $PHORUM["DATA"]["LANG"]["ErrUsername"];
    } elseif ($email === '' || !phorum_api_mail_check_address($email)) {
        $data['error'] = $PHORUM["DATA"]["LANG"]["ErrEmail"];
    } elseif (phorum_api_user_search("username", $nickname)) {
        $data['error'] = $PHORUM["DATA"]["LANG"]["ErrRegisterdName"];
    } elseif (phorum_api_user_search("email", $email)) {
        $data['error'] = $PHORUM["DATA"]["LANG"]["ErrRegisterdEmail"];
    } else {
        $step = 2;
    }
}
// Step 2: Create an account and associate it with the authentication id.
if ($step === 2)
{
    $user_id = phorum_api_user_save(array(
        'user_id'   => NULL,
        'username'  => $profile['nickname'],
        'password'  => md5(microtime(true) + $profile['auth_id']), //=bogus pwd
        'real_name' => $profile['fullname'],
        'email'     => $profile['email'],
        'active'    => PHORUM_USER_ACTIVE, 
    ));

    socialauth_db_associate($profile['auth_id'], $user_id);

    // The association checking code should now find the created user
    // and handle the user login.
    include dirname(__FILE__) . '/check_phorum_association.php';
    return;
}

$_SESSION['mod_social_authentication'] = $profile;

$PHORUM['DATA']['STEP'] = $step;

$PHORUM['DATA']['REGISTER'] = array(
    'provider' => noxss($provider_id),
    'auth_id'  => noxss($profile['auth_id']),
    'nickname' => noxss($profile['nickname']),
    'fullname' => noxss($profile['fullname']),
    'email'    => noxss($profile['email'])
);

$PHORUM['show_social_authentication_form'] = FALSE;
$data['heading'] = $lang['RegisterTitle'];
$data['template'] = 'social_authentication::register';

?>
