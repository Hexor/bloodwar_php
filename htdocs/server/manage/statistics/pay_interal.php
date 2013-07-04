<?php
	//玩家充值区间
	//参数列表：
	//day_start:开始日期
	//day_end:结束日期
	//返回
	/** 通过时间（天）查询一天或多天的充值区间数；
	  * 分析各额度充值玩家人数
	  * ret[0]:无充值
	  * ret[1]:1-10元
	  * ret[2]:10-30元
	  * ret[3]:30-50元
	  * ret[4]:50-100元
	  * ret[5]:100-200元
	  * ret[6]:200-300元
	  * ret[7]:300-500元
	  * ret[8]:500以上
	  */
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($day_start)){exit("param_not_exist");}
	if (!isset($day_end)){exit("param_not_exist");}
	$user = sql_fetch_one_cell("select count(*) from sys_user");
	$ret[0] = $user - sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 group by passport) as p");
	$ret[1] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>0 and b.money<10 group by passport) as p"); 
	$ret[2] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>10 and b.money<30 group by passport) as p"); 
	$ret[3] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>30 and b.money<50 group by passport) as p"); 
	$ret[4] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>50 and b.money<100 group by passport) as p"); 
	$ret[5] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>100 and b.money<200 group by passport) as p"); 
	$ret[6] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>200 and b.money<300 group by passport) as p"); 
	$ret[7] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>300 and b.money<500 group by passport) as p"); 
	$ret[8] = sql_fetch_one_cell("select count(*) from (select passport from pay_log b where b.time>=unix_timestamp($day_start) and b.time< unix_timestamp($day_end)+86400 and b.money>500 group by passport) as p"); 
?>