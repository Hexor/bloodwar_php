<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($name)){exit("param_not_exist");}

	$ret = sql_fetch_one_cell("select `uid` from sys_user where `name`='$name' limit 1");
?>