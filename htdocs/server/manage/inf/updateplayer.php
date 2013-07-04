<?php
	if (!defined("MANAGE_INTERFACE")) exit;
	if (!isset())exit("param_not_exist");
	$ret = sql_fetch_one_cell("");
	if(empty($ret))$ret = 'no data';
?>