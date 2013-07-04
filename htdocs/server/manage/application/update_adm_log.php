<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($name)){exit("param_not_exist");}
	if (!isset($content)){exit("param_not_exist");}
	
	$ret = sql_query_multi("insert into adm_log (`adm_name`,`opration`,`opration_content`,`oprate_time`) values ('$name','verify_yuanbao','$content',unix_timestamp())");
?>