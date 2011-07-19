<?php

if (!defined('PHORUM_MOD_SOCIAL_AUTHENTICATION')) return;

// Check if the required PHP extensions are available.
if (!extension_loaded('json')) {
    phorum_ajax_error(
        'Internal error: your PHP installation does not load the "json" ' .
        'extension. This extension is required for handling the oAuth protocol.'
    );
}   
if (!extension_loaded('curl')) {
    phorum_ajax_error(
        'Internal error: your PHP installation does not load the "curl" ' .
        'extension. This extension is required for handling the oAuth protocol.'
    );
}   

// When set, override the client id and secret with the ones from the
// module configuration. Otherwise, use the defaults from the
// provider's config.ini file.
$mod_config = $PHORUM['mod_social_authentication'];
if (isset($mod_config["conf_$provider_id"])) {
    $config = $mod_config["conf_$provider_id"];
    if (!empty($config['client_id']) &&
        !empty($config['client_secret'])) {
        $provider['oauth']['client_id'] = $config['client_id']; 
        $provider['oauth']['client_secret'] = $config['client_secret']; 
    }
}

if ($step === 'initialize') {
    include dirname(__FILE__) . '/action/redirect.php';
} elseif ($step === 'complete') {
    include dirname(__FILE__) . '/action/complete.php';
}

