<?php
/**
 * @作者：张昌彪
 * @模块：游戏操作 -- 增加荣誉
 * @功能：给多个用户添加战场荣誉
 * @参数：passports：回车间隔的用户账号（通行证）列表
 * 		  names：回车间隔的玩家名称列表
 *		  han_medal：增加的汉室勋章数量
 *		  huang_medal：增加的平定黄巾勋章数量
 *		  yuan_medal：增加的袁军官渡勋章数量
 *		  cao_medal：增加的曹军官渡勋章数量
 * @返回：
 */
	if (!defined("MANAGE_INTERFACE")) exit;
	function Add_medal($uid,$han_medal,$huang_medal,$yuan_medal,$cao_medal)
    {
		
		$record = sql_fetch_rows("select * from sys_things where tid in ('30000','30001','30002','30003') and uid = $uid");
		$medal = array('30000'=>$han_medal,'30001'=>$huang_medal,'30002'=>$yuan_medal,'30003'=>$cao_medal);
        $medalidlist = array('30000','30001','30002','30003');

        foreach($medalidlist as $tid)
        {
            sql_query("insert into sys_things (uid,tid,count) values ($uid,$tid,".$medal[$tid].") ON DUPLICATE KEY UPDATE `count`=`count`+'".$medal[$tid]."';");
        	sql_insert("insert into log_things (uid,tid,count,time,type) values ($uid,$tid,".$medal[$tid].",unix_timestamp(),'5')");
        }
        if(!mysql_error())
        {
            return true;
        }
        else
        {
            return false;
        }
    }

	if (!isset($passports)){exit("param_not_exist");}
	if (!isset($names)){exit("param_not_exist");}
	if (!isset($han_medal)){exit("param_not_exist");}
	if (!isset($huang_medal)){exit("param_not_exist");}
	if (!isset($yuan_medal)){exit("param_not_exist");}
	if (!isset($cao_medal)){exit("param_not_exist");}
	
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
					$add = Add_medal($user['uid'],$han_medal,$huang_medal,$yuan_medal,$cao_medal);
                    if($add)
					{
                        $ret[] = '成功为'.$user['passport']."[".$user['name']."]添加:</br>汉室勋章数量:$han_medal </br>平定黄巾勋章数量:$huang_medal </br>袁军官渡勋章数量:$yuan_medal </br>曹军官渡勋章数量:$cao_medal </br>";
					}
					else
					{
                        $ret[] = $user['passport']."[".$user['name']."]的勋章添加 失败</br>".mysql_error();
					}
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
					$add = Add_medal($user['uid'],$han_medal,$huang_medal,$yuan_medal,$cao_medal);
                    if($add)
					{
                        $ret[] = '成功为'.$user['passport']."[".$user['name']."]添加:</br>汉室勋章数量:$han_medal </br>平定黄巾勋章数量:$huang_medal </br>袁军官渡勋章数量:$yuan_medal </br>曹军官渡勋章数量:$cao_medal </br>";
					}
					else
					{
                        $ret[] = $user['passport']."[".$user['name']."]的勋章添加 失败</br>".mysql_error();
					}
				}
			}
		}
	}
?>