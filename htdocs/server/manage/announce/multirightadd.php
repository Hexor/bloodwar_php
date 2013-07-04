<?php
  /*
   * page: multileftrolling
   * return: the list of left-top rolling announce
   */
  if (!defined("MANAGE_INTERFACE")) exit;    
  
    $ret=sql_fetch_rows("select * from sys_activity order by id asc");

?>
