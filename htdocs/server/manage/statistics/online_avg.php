<?php
	//在线平均人数统计
	//参数列表：
	//startday:开始日期
	//endday:结束日期
	//返回
	//array[0]:array{day,online}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($startday)){exit("param_not_exist");}
	if (!isset($endday)){exit("param_not_exist");}
	
	$ret[] = sql_fetch_rows("select from_unixtime(time,'%Y-%m-%d') as day,avg(online) as online from log_online where time >= unix_timestamp($startday) and time < unix_timestamp($endday)+86400 and time % 3600=0 and not ((time+8*3600)%86400 > 3600 and (time+8*3600)%86400 < 7 * 3600) group by floor((time+8*3600)/86400)","bloodwarlog");

?>