<?php
	//添加声望
	//参数列表：
	//passports:通行证列表
	//names:君主名列表
	//prestige:声望值
	//返回
	//array[]:results of send_sys_mail
	if (!defined("MANAGE_INTERFACE")) exit;
	function Add_prestige($uid,$prestige)
    {
		sql_query("update sys_user set prestige = prestige + $prestige, warprestige = warprestige+ $prestige where uid = '$uid'");
    }

	if (!isset($passports)){exit("param_not_exist");}
	if (!isset($names)){exit("param_not_exist");}
	if (!isset($prestige)){exit("param_not_exist");}
	
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
					Add_prestige($user['uid'],$prestige);
					$ret[] = '成功为'.$user['passport']."[".$user['name']."]添加声望[".$prestige."] 。";
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
					Add_prestige($user['uid'],$prestige);
					$ret[] = '成功为'.$user['passport']."[".$user['name']."]添加声望[".$prestige."] 。";
				}
			}
		}
	}
?>