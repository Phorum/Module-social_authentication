<?php

if (!defined('PHORUM')) return;

/**
 * Load the configuration of all available authentication providers.
 *
 * @return array
 *   An array, containing a list of providers. Each provider in the
 *   list is an array, containing the data from the provider's
 *   config.ini file.
 */
function mod_social_authentication_list_providers()
{
    $providers = array();

    $dir = dirname(__FILE__) . '/providers';
    $dh = opendir($dir);
    if (!$dh) trigger_error("Cannot open directory: $dir", E_USER_ERROR);

    while ($entry = readdir($dh))
    {
        if ($entry[0] == '.' || $entry == 'TEMPLATE') continue;
        if (!file_exists("$dir/$entry/config.ini") ||
            !file_exists("$dir/$entry/button.png")) {
            phorum_admin_error(
                "Provider '" . htmlspecialchars($entry) . "' does not " .
                "contain both the files config.ini and button.png"
            );
            continue;
        }

        $providers[$entry] = parse_ini_file("$dir/$entry/config.ini", TRUE);
        $providers[$entry]['id'] = $entry;
    }

    uasort($providers, 'mod_social_authentication_cmp_providers');

    return $providers;
}

function mod_social_authentication_cmp_providers($a, $b)
{
    $name_a = strtolower($a['provider']['name']);
    $name_b = strtolower($b['provider']['name']);
    return strcmp($name_a, $name_b);
}

/**
 * Load the configuration for an authentication provider.
 *
 * @param string $id
 * @return array
 */
function mod_social_authentication_get_provider($id)
{
    $id = basename($id);
    $file = dirname(__FILE__) . '/providers/' . $id . '/config.ini';
    if (file_exists($file)) {
        $config = parse_ini_file($file, TRUE);
    } else {
        trigger_error("Illegal provider requested: $id", E_USER_ERROR);
    }

    return $config;
}

?>
