<?php

if(!empty($PHORUM['DBCONFIG']['charset'])) {
    $charset_str = " DEFAULT CHARACTER SET {$PHORUM['DBCONFIG']['charset']}";
} else {
    $charset_str = "";
}

$table = "{$PHORUM["DBCONFIG"]["table_prefix"]}_user_socialauth";

$upgrade_queries[]="
      CREATE TABLE $table (
           user_id        int unsigned NOT NULL default '0',
           auth_id        varchar(255) NOT NULL default '',  
           add_datetime   int unsigned NOT NULL default '0',

           PRIMARY KEY (user_id, auth_id),
           UNIQUE KEY (auth_id)
       )$charset_str
";

?>
