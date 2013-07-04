<?php
	//每时注册人数统计
	//参数列表：
	//day:日期
	//返回
	//array[0]:array{day,count}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($day)){exit("param_not_exist");}
	
	$ret[] = sql_fetch_rows("select from_unixtime(regtime-(regtime%3600),'%H:%i') as hour,count(*) as count from sys_user where uid > 1000 and regtime >= unix_timestamp($day) and regtime < unix_timestamp($day)+86400 group by regtime-(regtime%3600)");
?>