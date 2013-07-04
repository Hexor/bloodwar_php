<?php
  if (!defined("MANAGE_INTERFACE")) exit;

if(!isset($add_array)){exit('params_not_exit');}
	$add_content = $add_array[1];
	$add_inuse = $add_array[0];
	$add_link = $add_array[2];
	$add_interval = $add_array[3];
/*  if(!isset($add_content)){exit('params_not_exit');}
  if(!isset($add_inuse)){exit('params_not_exit');}
  if(!isset($add_link)){exit('params_not_exit');}
  if(!isset($add_interval)){exit('params_not_exit');}*/
  $ret[] = sql_query("insert into sys_activity (`inuse`,`content`,`link`,`interval`) values ('$add_inuse','$add_content','$add_link','$add_interval')");
  $ret[].=mysql_error();
?>
