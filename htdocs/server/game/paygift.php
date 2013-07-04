<?php 

require_once("./utils.php");
require_once './TaskFunc.php';

$uid = $user['uid'];
$now = sql_fetch_one_cell("select unix_timestamp()");
$endtime = sql_fetch_one_cell("select value from mem_state where state=21");
if ($now < $endtime)
{
	if ((!sql_check("select * from pay_user_gift where uid='$uid'"))&&($money>=50))
    {
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',24,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',24,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',40,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',40,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',56,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',56,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',30,2) on duplicate key update count=count+2");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',30,2,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',96,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',96,1,unix_timestamp(),6)");
        
        sql_query("insert into pay_user_gift(uid,time) values ('$uid',unix_timestamp())");
        sendSysMail($uid,$GLOBALS['paygift']['firstpay_title'],$GLOBALS['paygift']['firstpay_content']);
    }
}
@include("./actpaygift.php");

?>