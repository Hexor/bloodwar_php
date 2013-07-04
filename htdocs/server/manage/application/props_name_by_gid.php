<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($gid)){exit("param_not_exist");}

	$ret = sql_fetch_one_cell("select `name` from cfg_goods where `gid`='$gid'");  
?>