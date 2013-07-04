<?php
	//每日充值
	//参数列表：
	//startday:开始日期
	//endday:结束日期
	//返回
	//array[0]:array{orderid,passport,money,time}
	if (!defined("MANAGE_INTERFACE")) exit;
	if (!isset($passport)){exit("param_not_exist");}
	$passport = trim($passport);
	$ret = sql_fetch_one("select u.nobility as id,from_unixtime(o.lastupdate) as time from `sys_user` u left join sys_online o on (u.uid=o.uid) where u.passport='$passport'"); 
if(!empty($ret)){
		echo $ret['id'].'|'.$ret['time'];
	}else{
		echo 'no_info';
	}	
	exit;
?>