<?php
	require_once("./interface.php");
	require_once("./utils.php");
	/*
	function getUserBuffer($uid,$param)
	{
		$ret=array();
		$nowtime=sql_fetch_one_cell("select unix_timestamp()");
		$bufs=sql_fetch_rows("select * from mem_user_buffer where uid='$uid' and endtime>'$nowtime'");
		$ret[]=$nowtime;
		$ret[]=$bufs;
		return $ret;
	}
	
	function getCityBuffer($uid,$param)
	{
		$cid=array_shift($param);
		$userid=sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
		if($uid!=$userid)
		{
			throw new Exception($GLOBALS['getCityBuffer']['not_your_city']);
		}
		$ret=array();
		$nowtime=sql_fetch_one_cell("select unix_timestamp()");
		$bufs=sql_fetch_rows("select * from mem_city_buffer where cid='$cid' and endtime>'$nowtime'");
		$ret[]=$nowtime;
		$ret[]=$bufs;
		return $ret;
	}
	*/
	function getCraftBuffer($uid,$param)
	{
		$ret=array();
		$nowtime=sql_fetch_one_cell("select unix_timestamp()");
		$bufs=sql_fetch_rows("select * from mem_user_buffer where uid='$uid' and endtime>'$nowtime'");
		$ret[]=$nowtime;
		$ret[]=$bufs;
		return $ret;
	}
	
	function getTrickBuffer($uid,$param)
	{
		$cid=array_shift($param);
		$userid=sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
		if($uid!=$userid)
		{
			throw new Exception($GLOBALS['getCityBuffer']['not_your_city']);
		}
		$ret=array();
		$nowtime=sql_fetch_one_cell("select unix_timestamp()");
		$bufs=sql_fetch_rows("select * from mem_city_buffer where cid='$cid' and endtime>'$nowtime'");
		$ret[]=$nowtime;
		$ret[]=$bufs;
		return $ret;
	}
	
	function getYaoYiLinTime($uid,$param)
	{
		$ret=array();
		$ret[]=intval(sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=11 and endtime>unix_timestamp()"));
		return $ret;
	}
?>
