<?php
	//设置登录公告
	//参数列表：
	//msg:公告内容
	//返回
	//array[0]:array{orderid,passport,money,time}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($msg)){exit("param_not_exist");}

	sql_query("update sys_announce set content='$msg' where id=1");
	$ret[] = "update_succ";

?>