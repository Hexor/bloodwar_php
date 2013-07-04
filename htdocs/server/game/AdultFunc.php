<?php

function checkOnline($uid,$param)
{
	$online_time = -1;
	$key = "01B01668FC9D6A5894FA15172A728200";
	 $url = "http://10.0.0.4:8000/GameInterface_sg_f/GameOnlineInfo.aspx"; 
	 $cardid = urlencode(array_shift($param));
	$time  = urlencode(array_shift($param));
	$interval = intval(array_shift($param));
	 if($cardid != ""){	 
		 $sign = md5($cardid."$time"."$key");
		 $uparams = "?id_card=$cardid&time_stamp=$time&sign=$sign";
		 $url .= $uparams;
		 $info = file_get_contents($url); 
		 if($info != NULL){
		     $ret_ary = explode("&", $info);
		     $verify_state = explode("=", $ret_ary[1]);
			 $online_times = explode("=", $ret_ary[2]);
		     
			 $online_time = $online_times[1]; //从返回值取
			 
			 if($verify_state[1]==1 || $verify_state[1]==5){ //更新sys_user_online c++那边用到
			 	sql_query("insert into sys_user_online(uid, state) values($uid, $verify_state[1]) on duplicate key update state=$verify_state[1]");
			 }
			 
			 if($online_time == NULL){
			 	$online_time = -1;
			 }
			 else{
			 	$online_time = 60 * intval($online_time);
			 }
		 }
		 //insert into online time into 
		//sql_query("insert into sys_user_online(uid, online_time) values($uid, $online_time) on duplicate key update online_time=$online_time ");
		sql_query("update sys_user_online set online_time=$online_time where uid=$uid ");
	 }
	 else{
	 	//sql_query("insert into sys_user_online(uid, online_time) values($uid, $online_time) on duplicate key update online_time +=$interval ");
	 	sql_query("update sys_user_online set online_time=online_time+$interval where uid=$uid ");
	 	$online_time = -1;
	 }
	 
	//return online time
	$ret = array();
	$ret[] = $online_time;
	$ret[] = $verify_state[1];
	return $ret;
}

function updateLoginTime($uid, $param)
{
	$user = sql_fetch_one("select * from sys_user_online where uid='$uid'");
	$now = sql_fetch_one_cell("select unix_timestamp()");
	
	if(!empty($user))
	{
		$offline_time = $now - $user['login_time'] + $user['offline_time'] - $user['online_time'];
		if($offline_time > 5*60*60)
			sql_query("update sys_user_online set login_time=$now, offline_time=0, online_time=0 where uid=$uid");
		else
			sql_query("update sys_user_online set login_time=$now, offline_time=$offline_time where uid=$uid");
	}
	else{
		sql_query("insert into sys_user_online(uid, login_time) values($uid, $now) on duplicate key update login_time=$now ");
	}
	$ret = array();
	$online_time = 0;
	if($offline_time > 5*60*60)
		$online_time = 0;
	else
		$online_time =  $user['online_time'];
	$ret[] = $online_time;
	return $ret;
}

?>