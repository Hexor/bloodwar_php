<?php
  /*
   * page: delete left
   */
  if (!defined("MANAGE_INTERFACE")) exit;
  
  if (!isset($delete_opts)){exit("param_not_exist");}
  
  $ret[] =sql_query("delete from sys_activity where `id` in (".implode(',',$delete_opts).")");
?>
