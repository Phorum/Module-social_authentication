<?php

if (!defined("PHORUM_ADMIN")) return;

require_once './include/admin/PhorumInputForm.php';
require_once dirname(__FILE__) . '/api.php';

// Get the current list of available authentication providers.
$providers = mod_social_authentication_list_providers();

if (!isset($PHORUM['mod_social_authentication'])) {
    $PHORUM['mod_social_authentication'] = $providers;
}

// ---------------------------------------------------------------------------
// Process posted settings
// ---------------------------------------------------------------------------

if (!empty($_POST))
{
    foreach ($providers as $id => $provider) {
        if (isset($_POST['provider'][$id])) {
            $providers[$id]['active'] = 1;
        } else {
            $providers[$id]['active'] = 0;
        }
    }
    $PHORUM['mod_social_authentication'] = array(
        'providers' => $providers
    );

    phorum_db_update_settings(array(
        'mod_social_authentication' => $PHORUM['mod_social_authentication']
    ));
    phorum_admin_okmsg('Your settings have been saved successfully');
}

// ---------------------------------------------------------------------------
// Display the settings page
// ---------------------------------------------------------------------------

$frm = new PhorumInputForm ("", "post", 'Save settings');
$frm->hidden("module", "modsettings");
$frm->hidden("mod", "social_authentication");

$frm->addbreak("Social Authentication module Settings");
$frm->addbreak("Select the authentication providers to present to the user");

$select_providers = '';
foreach ($providers as $id => $provider)
{
    $select_providers .=
        "<div style=\"float:left; padding: 10px 15px\">" .
        "<label>" .
        $frm->checkbox(
            "provider[$id]", "1", "",
            !empty($PHORUM["mod_social_authentication"]["providers"][$id]['active'])
        ) . '&nbsp;' .
        "<img align=\"absmiddle\" " .
        "src=\"{$PHORUM['http_path']}/mods/social_authentication/" .
        "providers/$id/button.png\" title=\"" .
        htmlspecialchars($provider['provider']['name']) . "\"/>" .
        "</label>" .
        "</div>";

}
$frm->addmessage($select_providers);

$frm->show();

?>
