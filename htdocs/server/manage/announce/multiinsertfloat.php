<?php
  if (!defined("MANAGE_INTERFACE")) exit;
  
  if (!isset($add_array)){exit("param_not_exist");}
  if (!empty($add_array[3])){
  		$add_array[0]= html_entity_decode($add_array[0]);
      $ret[] =sql_query("insert into sys_inform set `msg`='$add_array[0]',`type`='$add_array[1]',`color`='$add_array[2]',`starttime`=UNIX_TIMESTAMP('$add_array[3]'),`endtime`=UNIX_TIMESTAMP('$add_array[4]'),`scrollcount`='$add_array[5]',`inuse`='$add_array[6]',`interval`='$add_array[7]'");
  $ret[].=mysql_error();
  }
  
?>
