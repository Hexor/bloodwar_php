<?php
/**
 * @author 阮钰标
 * @模块 查询查看 -- 查询用户
 * @功能 查询用户战报列表
 * @参数 $uid int 用户的uid
 * @返回 array 战报列表
 *       如果为空就返回no data
 */
	if (!defined("MANAGE_INTERFACE")) exit;
	if (!isset($id))exit("param_not_exist");
	$ret = sql_fetch_one("select *,from_unixtime(time,'%Y%m%d') as datetime from sys_report where id='$id'");
	
?>