<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the "Database Connection"
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the "default" group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/
$active_group = "default";
$active_record = TRUE;

$db['default']['hostname'] = "localhost";
$db['default']['username'] = "ctcweb9_userman";
$db['default']['password'] = "susanrulesok";
$db['default']['database'] = "ctcweb9_ctc";
$db['default']['dbdriver'] = "mysqli";
$db['default']['dbprefix'] = "";
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = "";
$db['default']['char_set'] = "utf8";
$db['default']['dbcollat'] = "utf8_general_ci";

$db['ctcweb9_joom1']['hostname'] = "localhost";
$db['ctcweb9_joom1']['username'] = "ctcweb9_userman";
$db['ctcweb9_joom1']['password'] = "susanrulesok";
$db['ctcweb9_joom1']['database'] = "ctcweb9_joom1";
$db['ctcweb9_joom1']['dbdriver'] = "mysqli";
$db['ctcweb9_joom1']['dbprefix'] = "";
$db['ctcweb9_joom1']['pconnect'] = TRUE;
$db['ctcweb9_joom1']['db_debug'] = TRUE;
$db['ctcweb9_joom1']['cache_on'] = FALSE;
$db['ctcweb9_joom1']['cachedir'] = "";
$db['ctcweb9_joom1']['char_set'] = "utf8";
$db['ctcweb9_joom1']['dbcollat'] = "utf8_general_ci";

$db['ctcweb9_tripreports']['hostname'] = "localhost";
$db['ctcweb9_tripreports']['username'] = "ctcweb9_userman";
$db['ctcweb9_tripreports']['password'] = "susanrulesok";
$db['ctcweb9_tripreports']['database'] = "ctcweb9_tripreports";
$db['ctcweb9_tripreports']['dbdriver'] = "mysqli";
$db['ctcweb9_tripreports']['dbprefix'] = "";
$db['ctcweb9_tripreports']['pconnect'] = TRUE;
$db['ctcweb9_tripreports']['db_debug'] = TRUE;
$db['ctcweb9_tripreports']['cache_on'] = FALSE;
$db['ctcweb9_tripreports']['cachedir'] = "";
$db['ctcweb9_tripreports']['char_set'] = "utf8";
$db['ctcweb9_tripreports']['dbcollat'] = "utf8_general_ci";


