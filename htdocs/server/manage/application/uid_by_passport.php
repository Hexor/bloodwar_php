<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($passport)){exit("param_not_exist");}

	$ret = sql_fetch_one_cell("select `uid` from sys_user where `passport`='$passport' limit 1");
?>