<?php
/**
 * @author 张昌彪
 * @模块 运营数据 -- 日付费比例统计
 * @功能 获得当前服务器日付费比例统计
 */
	if (!defined("MANAGE_INTERFACE")) exit;
    //当日付费人数
    $pay_count = sql_fetch_one_cell("select count(passport) from (select passport from pay_log where time <unix_timestamp('$day')+86400 and time > unix_timestamp('$day') group by passport) p");
    
    //当日上线人数
    $login_count = sql_fetch_one_cell("select count(uid) from (select uid from log_login where time <unix_timestamp('$day')+86400 and time > unix_timestamp('$day') group by uid) p");
    
    $ret = round(100*$pay_count/$login_count,4);
?>