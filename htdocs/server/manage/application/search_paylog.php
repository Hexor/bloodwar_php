<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($passport)){exit("param_not_exist");}
	if (!isset($union_id)){exit("param_not_exist");}
	
	$ret['unionname']=sql_fetch_one_cell("select name from sys_union where id=".$union_id);
	$ret['paylog'] = sql_fetch_rows("select from_unixtime(time) as logtime,money from pay_log where passport='$passport' order by time desc");
?>