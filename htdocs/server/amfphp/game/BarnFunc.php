<?php 

function loadBarnGoods($uid)
{
	$ret = array();
	
	$ret[] = sql_fetch_rows("select *, (select `count`  from sys_goods where uid=$uid and gid = b.gid) from cfg_goods b where b.gid in (212, 213, 214)");
	
	return $ret;
}

function doBarnStrong($uid, $param)
{
	$tianGongFu_count = array_shift($param); //0, 1
	$qianKun_count = array_shift($param); //0, 1
	if($tianGongFu_count==1)
	{
		$tianGongFu_goods = sql_fetch_one("select * from sys_goods where gid=212 and uid=$uid");
		if(empty($tianGongFu_goods) || $tianGongFu_goods['count']<=0)
			throw new Exception($GLOBALS['blacksmith']['no_bolefu_goods']);
	}
	
	if( $qianKun_count==1 )
	{
		$qianKun_goods = sql_fetch_one("select * from sys_goods where gid=213 and uid=$uid");
		if(empty($qianKun_goods) || $qianKun_goods['count']<=0)
			throw new Exception($GLOBALS['blacksmith']['no_shz_goods']);
	}
	
	$strong_pearl = sql_fetch_one("select * from sys_goods where gid=214 and uid=$uid");
	if(empty($strong_pearl) || $strong_pearl['count']<=0)
	{
		throw new Exception($GLOBALS['equipment']['no_tlgc_goods']);
	}
	
	$sid = array_shift($param);
	
	$ret = array();
	
	$next_level_infor = sql_fetch_one("select * from cfg_strong_probability where level=(select strong_level+1 from sys_user_armor where sid=$sid and uid=$uid)");
	
	if(empty($next_level_infor))
		throw new Exception($GLOBALS['equipment']['cannot_strong']);
	
	
	$succ_add = $tianGongFu_count * 30; //提高30%成功率	
	

	$is_succ = isSucc(1, intval($next_level_infor['suc_value']) + $succ_add );
	if( $is_succ )
	{
		sql_query("update sys_user_armor set strong_value=$next_level_infor[strong_value], strong_level=$next_level_infor[level] where uid=$uid and sid=$sid");
		$ret[] = 0;
	}
	else
	{
		$zero_value =  intval($next_level_infor['zero_value']);
		$degrade_value = intval($next_level_infor['degrade_value']);
		$intact_value = intval($next_level_infor['intact_value']);
		
		$p_value = pValue();
		
		if($qianKun_count==0)
		{//归零，降级，完整
			if( fitPValue($p_value, 1, $zero_value) ){//归零
				sql_query("update sys_user_armor set strong_value=0, strong_level=0 where uid=$uid and sid=$sid");
				$ret[]=1;
			}
			else if(fitPValue($p_value, $zero_value+1, $zero_value+$degrade_value)){	//降级
				$before_level = intval($next_level_infor['level']) - 2;
				$before_level_infor = sql_fetch_one("select cfg_strong_probability where level=$before_level");
				sql_query("update sys_user_armor set strong_value=$before_level_infor[strong_value], strong_level=$before_level_infor[level] where uid=$uid and sid=$sid");
				$ret[]=2;
			}
			else if( fitPValue($p_value, $zero_value+$degrade_value+1, $zero_value+$degrade_value+$intact_value) ){//无损失
				$ret[]=3;
			}
		}
		else{ //可以购买道具“乾坤宝珠”，在强化失败后装备不会消失
			if(fitPValue($p_value, $zero_value+1, $zero_value+$degrade_value)){	//降级
				$before_level = intval($next_level_infor['level']) - 2;
				$before_level_infor = sql_fetch_one("select cfg_strong_probability where level=$before_level");
				sql_query("update sys_user_armor set strong_value=$before_level_infor[strong_value], strong_level=$before_level_infor[level] where uid=$uid and sid=$sid");
				$ret[]=2;
			}
			else { //if( fitPValue($p_value, $zero_value+$degrade_value+1, $zero_value+$degrade_value+$intact_value) ){//无损失
				$ret[]=3;
			}
		}
	}
	
	if($tianGongFu_count==1)
	{
		sql_query("update sys_goods set `count`=`count`-1 where gid=212 and uid=$uid");
	}
	
	if( $qianKun_count==1 )
	{
		sql_query("update sys_goods set `count`=`count`-1 where gid=213 and uid=$uid");
	}
	
	sql_query("update sys_goods set `count`=`count`-1 where gid=214 and uid=$uid");
	
	
	return $ret; 
}

function loadZuojiArmor($uid, $param)
{
	$zuoji_type = array_shift($param);
	$ret = array();
	$ret[] = sql_fetch_rows("select * from sys_goods g left join cfg_goods f on f.gid=g.gid where g.uid='$uid' and f.zuoji_type=$zuoji_type");
	return $ret;
}

?>