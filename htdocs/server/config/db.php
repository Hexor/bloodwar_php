<?
////////////////////////////////////////////////////
//              Database Options               	  //
////////////////////////////////////////////////////

ini_set('include_path','/home/adaikiss/public_html/server/lib');

define('db_RDBMS', 'mysql');
define('db_Username', 'adaikiss_root');
define('db_Password', 'adaikiss_123456');
define('db_Server', '127.0.0.1');
define('db_Port', '3306');
define('db_Database', 'adaikiss_bloodwar');

$GLOBALS['dbcharset'] = 'utf8';

require_once("chathost.php");
define('chat_port','9933');
define('SERVER_ID',1);


?>