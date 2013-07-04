<?php
/**
 * @作者：张昌彪
 * @模块: 产品数据 -- 每日战场数据
 * @功能: 获得每日的战场数据
 * @返回：
 * ret[0]:每日勋章获得总数量
 * ret[1]:每日荣誉获得总数量
 * ret[2]:每日战场参与人数
 * ret[3]:每日战场开启总数量
 */
	if (!defined("MANAGE_INTERFACE")) exit;
	
	$ret[0] = sql_fetch_one_cell("select sum(metal) as sum from log_battle_honour where starttime>unix_timestamp('$today') and starttime<unix_timestamp('$today')+86400");
	$day_uids = sql_fetch_rows("select uid from log_battle_honour where starttime>unix_timestamp('$today') and starttime<unix_timestamp('$today')+86400 group by uid");
    $sum = 0;
    foreach($day_uids as $row)
	{
        $uid = $row['uid'];
        $last_honour = 0;
        $today_honour = 0;
        $last_honour = sql_fetch_one_cell("select honour from log_battle_honour where uid = $uid and starttime<unix_timestamp('$today') order by starttime desc limit 1");
        $today_honour = sql_fetch_one_cell("select honour from log_battle_honour where uid = $uid and starttime<unix_timestamp('$today')+86400 order by starttime desc limit 1");
       	$m = (int)$today_honour-(int)$last_honour;
       	$sum =$sum + $m;
	}
	$ret[1] = $sum;
	$ret[2] = sql_fetch_one_cell("select count(uid) from (select uid from log_battle_honour where starttime>unix_timestamp('$today') and starttime<unix_timestamp('$today')+86400 group by uid) as p");
	$ret[3] = sql_fetch_one_cell("select count(battlefieldid) from (select battlefieldid from log_battle_honour where starttime>unix_timestamp('$today') and starttime<unix_timestamp('$today')+86400 group by battlefieldid) as p");
?>