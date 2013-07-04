<?php
/**
 * @作者：张昌彪
 * @模块：游戏操作 -- 增加荣誉
 * @功能：给多个用户添加战场荣誉
 * @参数：passports：回车间隔的用户账号（通行证）列表
 * 		  names：回车间隔的玩家名称列表
 *		  honour：增加的荣誉数量
 * @返回：
 */
	if (!defined("MANAGE_INTERFACE")) exit;
	function Add_honour($uid,$honour)
    {
		sql_query("update sys_user set honour = honour + $honour where uid = '$uid'");
		sql_insert("insert into log_battle_honour (uid,battlefieldid,honour,battleid,result,quittime) values ($uid,'-1','$honour','-1','-1',unix_timestamp(now()))");
    }

	if (!isset($passports)){exit("param_not_exist");}
	if (!isset($names)){exit("param_not_exist");}
	if (!isset($honour)){exit("param_not_exist");}
	
	if ((empty($passports))&&(empty($names)))
	{
		$ret[] = "没有君主名或通行证";
	}
	else
	{
		
		if (!empty($passports))
		{
			$passports = explode("\n",$passports);
			foreach($passports as $passport)
			{
				$passport=addslashes(trim($passport));
				$user = sql_fetch_one("select * from sys_user where uid > 1000 and passport='$passport' limit 1");

				if (empty($user))
				{
					$ret[] = "不存在帐号：".$passport."。";
				}
				else
				{
					Add_honour($user['uid'],$honour);
					$ret[] = '成功为'.$user['passport']."[".$user['name']."]添加荣誉[".$honour."] 。";
				}
			}
		}
		else
		{
			$names = explode("\n",$names);
			foreach($names as $name)
			{
				$name=addslashes(trim($name));
				$user = sql_fetch_one("select * from sys_user where uid > 1000 and name='$name' limit 1");
				if (empty($user))
				{
					$ret[] = "不存在君主名：".$name;
				}
				else
				{
					Add_honour($user['uid'],$honour);
					$ret[] = '成功为'.$user['passport']."[".$user['name']."]添加荣誉[".$honour."] 。";
				}
			}
		}
	}
?>