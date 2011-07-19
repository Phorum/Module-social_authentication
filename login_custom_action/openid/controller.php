<?php

// Setup the include path for the OpenID include files.
ini_set('include_path', 
    dirname(__FILE__) . '/../../include'        . PATH_SEPARATOR .
    dirname(__FILE__) . '/../../include/openid' . PATH_SEPARATOR .
    ini_get('include_path')
);

// Load the OpenID related libraries.
require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/SReg.php";
require_once "Auth/OpenID/AX.php";
require_once "Auth/OpenID/PAPE.php";
require_once "PhorumStore.php";

// Setup the OpenID consumer using the Phorum store.
$store    = new Auth_OpenID_PhorumStore();
$consumer = new Auth_OpenID_Consumer($store);

// Profile attributes to request from the OpenID provider.
// The keys must be valid for the SREG (Simple REGistration)
// prototol. The values must be valid URIs for the AX (Attribute
// eXchange protocol).
$request_attributes = array(
    'nickname' => 'http://axschema.org/namePerson/friendly',
    'fullname' => 'http://axschema.org/namePerson',
    'email'    => 'http://axschema.org/contact/email'
);

// Handle step 2 (discover) of the OpenID authentication mechanism.
// This step is called via a Phorum Ajax request.
if ($step === 'initialize') {
    include dirname(__FILE__) . '/action/discover.php';
}
// Handle step 4 (response from OpenID provider) when the
// mod_openid=complete parameter is set.
elseif (isset($PHORUM['args']['_sas']) &&
        $PHORUM['args']['_sas'] === 'complete') {
    include dirname(__FILE__) . '/action/complete.php';
}
// Handle the account registration step.
elseif (isset($_POST['_sas']) &&
        $_POST['_sas'] === 'register') {
    include dirname(__FILE__) . '/action/register.php';
}

