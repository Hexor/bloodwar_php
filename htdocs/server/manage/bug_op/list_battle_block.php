<?php
//列表卡住的战斗
//参数列表：无
//返回
//array
if (!defined("MANAGE_INTERFACE")) exit();

	$ret=sql_fetch_rows("select distinct targetcid from sys_troops where state=3 and battleid not in ( select id from mem_battle)");
	
?>