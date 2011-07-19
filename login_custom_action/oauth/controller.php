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

// Setup the include path for the oAuth include files.
ini_set('include_path', 
    dirname(__FILE__) . '/../../include'        . PATH_SEPARATOR .
    dirname(__FILE__) . '/../../include/oauth/library' . PATH_SEPARATOR .
    ini_get('include_path')
);

// Load the oAuth related libraries.
include_once "OAuthStore.php";
include_once "OAuthRequester.php";

// When set, override the consumer key and secret with the ones from the
// module configuration. Otherwise, use the defaults from the
// provider's config.ini file.
$mod_config = $PHORUM['mod_social_authentication'];
if (isset($mod_config["conf_$provider_id"])) {
    $config = $mod_config["conf_$provider_id"];
    if (!empty($config['consumer_key']) &&
        !empty($config['consumer_secret'])) {
        $provider['oauth']['consumer_key'] = $config['consumer_key']; 
        $provider['oauth']['consumer_secret'] = $config['consumer_secret']; 
    }
}

// Create the oAuth store.
$store = OAuthStore::instance("Session", array(
    'consumer_key'      => $provider['oauth']['consumer_key'],
    'consumer_secret'   => $provider['oauth']['consumer_secret'],
    'server_uri'        => $provider['oauth']['server_uri'],
    'request_token_uri' => $provider['oauth']['request_token_uri'],
    'authorize_uri'     => $provider['oauth']['authorize_uri'],
    'access_token_uri'  => $provider['oauth']['access_token_uri']
));

if ($step === 'initialize') {
    include dirname(__FILE__) . '/action/request_token.php';
} elseif ($step === 'complete') {
    include dirname(__FILE__) . '/action/complete.php';
}

