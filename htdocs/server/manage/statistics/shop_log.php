<?php
	//道具记录
	//参数列表：
	//startday:开始日期
	//endday:结束日期
	//返回
	//array[0]:array{goodsname,count,price,totalprice,rate}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($startday)){exit("param_not_exist");}
	if (!isset($endday)){exit("param_not_exist");}
	
	$sumall = sql_fetch_one_cell("select sum(price*count) as totalprice from log_shop where time >= unix_timestamp($startday) and time < unix_timestamp($endday)+86400");
	$ret[] = sql_fetch_rows("select f.name as goodsname,sum(l.count) as count,l.price as price,sum(l.price*l.count) as totalprice,(sum(l.price*l.count)*100.0/".$sumall.") as rate from log_shop l left join cfg_shop f on f.id=l.shopid where l.time >= unix_timestamp($startday) and l.time < unix_timestamp($endday)+86400 group by l.shopid");
?>