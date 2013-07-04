<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($touid)){exit("param_not_exist");}

	$ret = sql_fetch_one("select * from sys_user where uid='$touid'");
?>