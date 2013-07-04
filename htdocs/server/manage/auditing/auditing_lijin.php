<?php

	if (!defined("MANAGE_INTERFACE")) exit;
	
	if (!isset($count)){exit("param_not_exist");}
	if (!isset($uid)){exit("param_not_exist");}
	if (!isset($opration_content)){exit("param_not_exist");}
	if (!isset($name)){exit("param_not_exist");}
	sql_query("update sys_user set `gift` = `gift` + $count where `uid` = $uid");
    sql_query("insert into log_gift (uid,count,time,type) values ('$uid','$count',unix_timestamp(),4)");
    $ret[]=sql_insert("insert into adm_log (`adm_name`,`opration`,`opration_content`,`oprate_time`) values ('$name','verify_lijin','$opration_content',unix_timestamp())");
?>