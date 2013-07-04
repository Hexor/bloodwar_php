<?php
	//每日注册人数统计
	//参数列表：
	//startday:开始日期
	//endday:结束日期
	//返回
	//array[0]:array{day,count}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($startday)){exit("param_not_exist");}
	if (!isset($endday)){exit("param_not_exist");}
	
	$ret[] = sql_fetch_rows("select from_unixtime(regtime-(regtime+8*3600)%86400,'%Y-%m-%d') as day,count(*) as count from sys_user where uid > 1000 and regtime >= unix_timestamp($startday) and regtime < unix_timestamp($endday)+86400 group by regtime-(regtime+8*3600)%86400");
?>