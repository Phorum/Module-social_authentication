<?php

if (!defined("PHORUM")) return;

// The table name for storing authentication associations.
$GLOBALS["PHORUM"]["socialauth_table"] =
    "{$GLOBALS["PHORUM"]["DBCONFIG"]["table_prefix"]}_user_socialauth";

/**
 * Retrieve the information for an authentication id association.
 *
 * @param string $auth_id
 *   The authentication id for which to retrieve information.
 * @return NULL|array
 *   An array containing data for the OpenID (fields are user_id,
 *   add_datetime and openid) or NULL if the OpenID is not available.
 */
function socialauth_db_get($auth_id)
{
    global $PHORUM;

    $auth_id = phorum_db_interact(DB_RETURN_QUOTED, $auth_id);

    return phorum_db_interact(
        DB_RETURN_ASSOC,
        "SELECT *
         FROM   {$PHORUM['socialauth_table']}
         WHERE  auth_id = '$auth_id'"
    );
}

/**
 * Retrieve the authentication id's that are associated to a given user.
 *
 * @param integer $user_id
 *   The id of the user for which to retrieve the authentication associations.
 * @return array
 *   An array containing data record for the authentication associations.
 *   Each data record is an array that contains the fields user_id,
 *   add_datetime and auth_id.
 */
function socialauth_get_for_user($user_id)
{
    global $PHORUM;

    settype($user_id, 'int');

    return phorum_db_interact(
        DB_RETURN_ASSOCS,
        "SELECT *
         FROM   {$PHORUM['socialauth_table']}
         WHERE  user_id = $user_id"
    );
}

/**
 * Associate an authentication id with a user.
 *
 * @param string $auth_id
 *   The authentication id to associate with a user.
 * @param integer $user_id
 *   The id of the user to associate the OpenID with.
 */
function socialauth_db_associate($auth_id, $user_id)
{
    global $PHORUM;

    settype($user_id, 'int');

    // Check if the user exists.
    $user = phorum_db_user_get($user_id);
    if ($user === NULL) trigger_error(
        "socialauth_db_associate(): " .
        "cannot associate $auth_id with user " .
        "id $user_id: no such user", E_USER_ERROR
    );

    // Check if the association already exists.
    $assoc = socialauth_db_get($auth_id);
    if ($assoc !== NULL) {
        if ($assoc['user_id'] === $user_id) {
            return; // already set to the correct user_id
        } else trigger_error(
            "socialauth_db_associate(): " .
            "cannot associate $auth_id with user " .
            "id $user_id: the OpenID is already associated with user " .
            "id {$assoc['user_id']}", E_USER_ERROR
        );
    }

    // All is ok. Create the association.
    $auth_id = phorum_db_interact(DB_RETURN_QUOTED, $auth_id);
    phorum_db_interact(
        DB_RETURN_RES,
        "INSERT INTO {$PHORUM['socialauth_table']}
                     (auth_id, user_id, add_datetime)
         VALUES      ('$auth_id', $user_id, " . time() . ")"
    );
}

/**
 * Disassociate an authentication id from a user.
 *
 * @param string $auth_id
 *   The authentication id to disassociate from a user.
 * @param integer $user_id
 *   The id of the user to disassociate the authentication id from.
 */
function socialauth_db_disassociate($auth_id, $user_id)
{
    global $PHORUM;

    $auth_id = phorum_db_interact(DB_RETURN_QUOTED, $auth_id);
    settype($user_id, 'int');

    phorum_db_interact(
        DB_RETURN_RES,
        "DELETE FROM {$PHORUM['socialauth_table']}
         WHERE  auth_id = '$auth_id'
                AND
                user_id = $user_id"
    );
}
?>
