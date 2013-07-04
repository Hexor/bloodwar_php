<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");

function getHeroBuffer($uid,$param)
{
	$cid=array_shift($param);
	$hid=array_shift($param);
	return sql_fetch_rows("select c.name,h.endtime from mem_hero_buffer h left join cfg_goods c on c.gid=h.buftype+25 where h.hid='$hid' and h.endtime>unix_timestamp()");
}

function dismissHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
	if (empty($hero))
	{
		throw new Exception($GLOBALS['dismissHero']['cant_dissmiss_this']);
	}
	if ($hero['state'] != 0)
	{
		throw new Exception($GLOBALS['dismissHero']['only_dissmiss_free_hero']);
	}
	//解决将领装备丢失的问题
	if(sql_check("select * from sys_user_armor where uid=$uid and hid=$hid")){
		throw new Exception($GLOBALS['dismissHero']['has_armor']);
	}
	throwHeroToField($hero);

	updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}
function upgradeHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");

	if (empty($hero))
	{
		throw new Exception($GLOBALS['upgradeHero']['cant_upgrade_this']);
	}
	//if ($hero["state"] > 1 ) //不在城池里
	if(isHeroInCity($hero["state"]) == 0)
	{
		throw new Exception($GLOBALS['upgradeHero']['cant_upgrade_out_hero']);
	}

	if($hero['level']>=100){
		//强制避免将领等级过大的bug
		if($hero['level']>100){
			sql_query("update sys_city_hero set level=100 where hid='$hid'");
		}
		throw new Exception($GLOBALS['upgradeHero']['level_100']);
	}
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	$total_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$hero[level]'");
	$upgrade_exp = sql_fetch_one_cell("select upgrade_exp from cfg_hero_level where level=".($hero['level']+1));
	if (($hero['exp'] - $total_exp) >= $upgrade_exp)
	{
		sql_query("update sys_city_hero set level=level+1 where hid='$hid'");
	}
	else
	{
		throw new Exception($GLOBALS['upgradeHero']['no_enough_exp']);
	}
	regenerateHeroAttri($uid,$hid);
	completeTask($uid,86);
	updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}

function addHeroPoint($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$affairs = array_shift($param);
	$bravery = array_shift($param);
	$wisdom = array_shift($param);

	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
	if (empty($hero))
	{
		throw new Exception($GLOBALS['addHeroPoint']['cant_find_hero']);
	}

	//if ($hero["state"] > 1 ) //不在城池里
	if(isHeroInCity($hero["state"]) == 0)
	{
		throw new Exception($GLOBALS['addHeroPoint']['cant_add_out_hero']);
	}
	if ($affairs + $bravery + $wisdom <= $hero['affairs_base'] + $hero['bravery_base'] + $hero['wisdom_base'] +$hero["level"])
	{
		$affairs_add = $affairs - $hero['affairs_base'];
		$bravery_add = $bravery - $hero['bravery_base'];
		$wisdom_add  = $wisdom  - $hero['wisdom_base'];
		
		if($hero['affairs_add']>$affairs_add || $hero['bravery_add']>$bravery_add || $hero['wisdom_add']>$wisdom_add)
		{
			$msg = "$uid:"."affairs_add=$affairs_add, bravery_add=$bravery_add, wisdom_add=$wisdom_add";
			system("echo \"$msg\" >> /waigua.uid");
			throw new Exception($GLOBALS['hero']['xidian_unvalid']);
		}
		
		sql_query("update sys_city_hero set affairs_add='$affairs_add',bravery_add='$bravery_add',wisdom_add='$wisdom_add' where hid='$hid'");
		regenerateHeroAttri($uid,$hid);
		updateCityHeroChange($uid,$cid);
		$chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
		if ($chiefhid == $hid)
		{
			updateCityChiefResAdd($cid,$hid);
		}
		completeTask($uid,87);
	}
	else
	{
		throw new Exception($GLOBALS['addHeroPoint']['no_extra_potential']);
	}
	return getCityInfoHero($uid,$cid);
}
function clearHeroPoint($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
	if (empty($hero))
	{
		throw new Exception($GLOBALS['clearHeroPoint']['cant_find_hero']);
	}
	if(isHeroInCity($hero["state"]) == 0)
	//if ($hero["state"] > 1) //不在城池里
	{
		throw new Exception($GLOBALS['clearHeroPoint']['cant_clean_out_hero']);
	}
	useXiShuiDan($uid,$hid);
	regenerateHeroAttri($uid,$hid);
	updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}
function changeHeroName($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$newName = trim(array_shift($param));
	if (mb_strlen($newName,"utf-8") > 4)
	{
		throw new Exception($GLOBALS['changeHeroName']['name_too_long']);
	}
	else if ((!(strpos($newName,'\'')===false))||(!(strpos($newName,'\\')===false)))
	{
		throw new Exception($GLOBALS['changeHeroName']['invalid_char']);
	}
	else if (strlen($newName) == 0)
	{
		throw new Exception($GLOBALS['changeHeroName']['input_valid_name']);
	}
	$lowername = strtolower($newName);
	if (sql_check("select * from cfg_baned_name where instr('$lowername',`name`)>0"))
	{
		throw new Exception($GLOBALS['changeHeroName']['invalid_char']);
	}
	$newName=addslashes($newName);
	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
	if (empty($hero))
	{
		throw new Exception($GLOBALS['changeHeroName']['cant_find_hero']);
	}
	if(isHeroInCity($hero["state"]) == 0)
	//if ($hero["state"] > 1) //不在城池里
	{
		throw new Exception($GLOBALS['changeHeroName']['cant_change_out_hero']);
	}
	if ($hero['npcid'] != 0)
	{
		throw new Exception($GLOBALS['changeHeroName']['cant_change_famous_hero']);
	}
	if ($hero['herotype'] != 0)
	{
		throw new Exception($GLOBALS['changeHeroName']['cant_change_act_hero']);
	}
	
	sql_query("update sys_city_hero set name='$newName' where hid='$hid'");

	return getCityInfoHero($uid,$cid);
}
function largessHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$typeidx = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
	if (empty($hero))
	{
		throw new Exception($GLOBALS['largessHero']['cant_find_hero']);
	}
	//if ($hero["state"] > 1) //不在城池里
	if(isHeroInCity($hero["state"]) == 0)
	{
		throw new Exception($GLOBALS['largessHero']['cant_largess_out_hero']);
	}
	$now = sql_fetch_one_cell("select unix_timestamp()");
	$last_largess = sql_fetch_one_cell("select last_largess from mem_hero_schedule where hid='$hid'");
	if ($now - $last_largess < 900) //15分钟内不能再次赏赐
	{
		$delta =900-($now - $last_largess);

		$msg = sprintf($GLOBALS['largessHero']['wait_duration'],MakeTimeLeft($delta));
		throw new Exception($msg);
	}
	if ($typeidx == 0)  //赏赐黄金
	{
		$city_gold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
		$salary=$hero['level']*20+(max($hero['affairs_base']+$hero['affairs_add']-90,0)+max($hero['bravery_base']+$hero['bravery_add']-90,0)+max($hero['wisdom_base']+$hero['wisdom_add']-90,0))*50;
		$largess_gold=$salary*5;
		if ($city_gold < $largess_gold)
		{
			throw new Exception($GLOBALS['largessHero']['no_enough_gold']);
		}
		if($salary<3500)	$loyaltyadd=mt_rand(1,20);
		else if($salary<6500) $loyaltyadd=mt_rand(1,10);
		else if ($hero['loyalty']<30) $loyaltyadd=mt_rand(1,5);
		else throw new Exception($GLOBALS['largessHero']['no_need_gold']);
		sql_query("update mem_city_resource set gold=gold-'$largess_gold' where cid='$cid'");
		sql_query("update sys_city_hero set loyalty=LEAST(100,loyalty+'$loyaltyadd') where hid='$hid'");
	}
	else if ($typeidx < 10)     //珍珠等
	{
		$gid = $typeidx + 29;
		$my_goods_count = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'");
		if (empty($my_goods_count)||($my_goods_count <= 0)) throw new Exception($GLOBALS['largessHero']['no_this_prop']);

		addGoods($uid,$gid,-1,7);
		$GOODS_LOYALTY_ADD = array(5,10,15,20,25,30,40,50,60);
		$loyaltyadd = $GOODS_LOYALTY_ADD[$typeidx-1];
		sql_query("update sys_city_hero set loyalty=LEAST(100,loyalty+$loyaltyadd) where hid='$hid'");
	}
	else if ($typeidx < 15) //虎符,文曲星符，武曲星符，智多星符,猛油火罐
	{
		//靠 什么鸟代码 居然这么写
		$gid = $typeidx - 10 + 26;
		//猛油火罐,没办法我也只好写死了。。。
		if($typeidx==14)
		$gid=116;
		$hufu = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'");
		if (empty($hufu)||($hufu <= 0)) throw new Exception($GLOBALS['largessHero']['no_this_prop']);
		addGoods($uid,$gid,-1,7);
		$buftype = $typeidx - 10 + 1;
		if($typeidx==14)
		$buftype=91;
		sql_query("insert into mem_hero_buffer (hid,buftype,endtime) values ('$hid','$buftype',unix_timestamp()+86400) on duplicate key update endtime=GREATEST(endtime,unix_timestamp())+86400");
		if($typeidx<12) //如果是虎符或文曲星，而且将领是城守的话，要重新算资源加成
		{
			$chiefhid=sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
			if(!empty($chiefhid)&&$chiefhid==$hid)
			{
				sql_query("update sys_city_res_add set resource_changing=1 where cid='$cid'");
			}
		}
		regenerateHeroAttri($uid,$hid);
	}
	sql_query("insert into mem_hero_schedule (hid,last_trick,last_largess) values ('$hid',0,'$now') on duplicate key update last_largess=$now");
	return getCityInfoHero($uid,$cid);
}
function releaseHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['releaseHero']['hero_not_exist']);
	if ($hero['state'] != 5) throw new Exception($GLOBALS['releaseHero']['hero_not_captive']);

	throwHeroToField($hero);
	sql_query("delete from mem_hero_summon where hid='$hid'");
	sql_query("delete from sys_hero_captive where hid='$hid'");
	//updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}

function rejectHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['releaseHero']['hero_not_exist']);
	if ($hero['state'] != 6) throw new Exception($GLOBALS['releaseHero']['hero_not_coming']);

	throwHeroToField($hero);
	//updateCityHeroChange($uid,$cid);
	sql_query("delete from mem_hero_summon where hid='$hid'");
	return getCityInfoHero($uid,$cid);
}

function getNpcIntroduce($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['getNpcIntroduce']['no_hero_info']);
	if ($hero['npcid'] == 0) throw new Exception($GLOBALS['getNpcIntroduce']['not_famous_hero']);
	$npchero = sql_fetch_one("select * from cfg_npc_hero where npcid='$hero[npcid]'");
	if (empty($npchero)) throw new Exception($GLOBALS['getNpcIntroduce']['not_famous_hero']);
	$ret = array();
	$ret[] = $npchero;
	return $ret;
}

function makeHeroSummonNeed($hid,$level)
{
	//生成需求
	$typeCount = 1 + floor(($level - 50) / 10);//生成几种道具
	$totalValue = $level*$level/10;
	$goodsArray = sql_fetch_rows("select * from cfg_goods where gid >= 30 and gid <= 38");
	$goodsCount=count($goodsArray);
	if($typeCount>$goodsCount) $typeCount=$goodsCount;
	while($typeCount > 0&&$totalValue>0)
	{
		$typeCount--;
		$goodsCount=count($goodsArray);
		$idx=mt_rand(0,$goodsCount-1);
		$goods=$goodsArray[$idx];
		$cnt = ceil($totalValue / $goods['value']);
		if ($typeCount == 0)    //最后一个了
		{
			$real_cnt = $cnt;
		}
		else
		{
			$real_cnt = mt_rand(1,$cnt);
			$totalValue -= $real_cnt * $goods['value'];
		}
		sql_query("insert into mem_hero_summon (hid,gid,name,count) values ('$hid','$goods[gid]','$goods[name]',$real_cnt)");
		array_splice($goodsArray,$idx,1);
	}
}

function getHeroSummonGold($hero)
{

	return ($hero['level']*20+(max($hero['affairs_base']+$hero['affairs_add']-90,0)+max($hero['bravery_base']+$hero['bravery_add']-90,0)+max($hero['wisdom_base']+$hero['wisdom_add']-90,0))*50)*50;
	/*
	 //黄金需求
	 if ($hero['npcid'] > 0)
	 {
	 return $hero['level'] * 100;
	 }
	 else
	 {
	 return $hero['level'] * 20;
	 }*/
}

function getSummonNeed($hero)
{
	$hid=$hero['hid'];
	$need = $GLOBALS['trySummonHero']['hero_need'];
	$need .= $GLOBALS['trySummonHero']['gold'].getHeroSummonGold($hero);
	//宝物需求

	if ($hero['level'] >= 50)
	{
		$goods = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
		if (count($goods) == 0)
		{
			makeHeroSummonNeed($hid,$hero['level']);
			$goods = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
		}
		foreach($goods as $good)
		{
			$need .= "，".$good['name'].$good['count'];
		}
	}
	$need .= "。";
	return $need;
}

function tryAcceptHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['trySummonHero']['no_hero_info']);
	if ($hero['state'] != 6)
	{
		throw new Exception($hero['name'].$GLOBALS['tryAcceptHero']['hero_not_coming'] );
	}
	$need=getSummonNeed($hero);
	$ret = array();
	$ret[] = $hid;
	$ret[] = $need;
	return $ret;
}

function sureAcceptHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)||($hero['cid'] != $cid)) throw new Exception($GLOBALS['sureSummonHero']['hero_not_exist']);
	if ($hero['state'] != 6) throw new Exception($GLOBALS['tryAcceptHero']['hero_not_coming']);

	$gold_need = getHeroSummonGold($hero);
	$mygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	if ($mygold < $gold_need) throw new Exception($GLOBALS['sureSummonHero']['no_enough_gold']);
	$goodsList = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
	if (count($goodsList) > 0)
	{
		foreach($goodsList as $goods)
		{
			if (!sql_check("select * from sys_goods where uid='$uid' and gid='$goods[gid]' and count >= $goods[count]"))
			{
				$msg = sprintf($GLOBALS['sureSummonHero']['no_enough_goods'],$goods['name'],$hero['name']);
				throw new Exception($msg);
			}
		}
		//有足够的宝物的话，开始扣
		foreach($goodsList as $goods)
		{
			addGoods($uid,$goods['gid'],-$goods['count'],7);
		}
		//清招降要求
		sql_query("delete from mem_hero_summon where hid='$hid'");
	}
	//扣黄金
	addCityResources($cid,0,0,0,0,-$gold_need);

	//招人
	sql_query("update sys_city_hero set state=0,loyalty=80 where hid='$hid'");
	updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}

function trySummonHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['trySummonHero']['no_hero_info']);
	if ($hero['state'] != 5)
	{
		throw new Exception($hero['name'].$GLOBALS['trySummonHero']['hero_not_captive']);
	}
	$nobility_need = floor(($hero['level'] - 1)/10);
	$mynobility = sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	//推恩
	$mynobility= getBufferNobility($uid,$mynobility);
	if ($mynobility < $nobility_need)
	{
		$nobility_name = sql_fetch_one_cell("select name from cfg_nobility where id='$nobility_need'");

		$msg = sprintf($GLOBALS['trySummonHero']['no_enough_nobility'],$nobility_name);
		//throw new Exception("我的主公，必定是威震天下的英雄，你连\"".$nobility_name."\"都没有达到，我是不会跟随你的。");
		throw new Exception($msg);
	}
	$need=getSummonNeed($hero);
	$ret = array();
	$ret[] = $hid;
	$ret[] = $need;
	return $ret;
}
function sureSummonHero($uid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");

	if (empty($hero)||($hero['cid'] != $cid)) throw new Exception($GLOBALS['sureSummonHero']['hero_not_exist']);
	if ($hero['state'] != 5) throw new Exception($GLOBALS['sureSummonHero']['hero_not_captive']);

	
	$nobility_need = floor(($hero['level'] - 1)/10);
	$mynobility = sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	//推恩
	$mynobility= getBufferNobility($uid,$mynobility);
	if ($mynobility < $nobility_need)
	{
		$nobility_name = sql_fetch_one_cell("select name from cfg_nobility where id='$nobility_need'");

		$msg = sprintf($GLOBALS['trySummonHero']['no_enough_nobility'],$nobility_name);
		//throw new Exception("我的主公，必定是威震天下的英雄，你连\"".$nobility_name."\"都没有达到，我是不会跟随你的。");
		throw new Exception($msg);
	}
	
	$gold_need = getHeroSummonGold($hero);
	$mygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	if ($mygold < $gold_need) throw new Exception($GLOBALS['sureSummonHero']['no_enough_gold']);
	$goodsList = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
	if (count($goodsList) > 0)
	{
		foreach($goodsList as $goods)
		{
			if (!sql_check("select * from sys_goods where uid='$uid' and gid='$goods[gid]' and count >= $goods[count]"))
			{
				$msg = sprintf($GLOBALS['sureSummonHero']['no_enough_goods'],$goods['name'],$hero['name']);
				//throw new Exception("你没有足够的".$goods['name']."，不能招降".$hero['name']."。");
				throw new Exception($msg);
			}
		}
		//有足够的宝物的话，开始扣
		foreach($goodsList as $goods)
		{
			addGoods($uid,$goods['gid'],-$goods['count'],7);
		}
		//清招降要求
		sql_query("delete from mem_hero_summon where hid='$hid'");
	}
	//扣黄金
	addCityResources($cid,0,0,0,0,-$gold_need);

	//招人
	sql_query("update sys_city_hero set state=0,loyalty=50,uid='$uid' where hid='$hid'");
	//清除俘虏记录
	sql_query("delete from sys_hero_captive where hid='$hid'");
	if($hero['npcid']>0)
	{
		$taskid=30000+$hero['npcid'];
		$goalid=sql_fetch_one_cell("select id from cfg_task_goal where tid=$taskid");
		completeTask($uid,$goalid);
	}
	updateCityHeroChange($uid,$cid);
	if($hero["npcid"]>0){
		//招降名将 发公告
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid';");
		$msg = sprintf($GLOBALS['summon_hero']['npc'],$hero["name"],$uname);
		sql_query("insert into sys_inform (`type`,`inuse`,`starttime`,`endtime`,`interval`,`scrollcount`,`color`,`msg`) values (0,1,unix_timestamp(),unix_timestamp()+600,50000,1,16738740,'$msg')");

		if(defined("USER_FOR_51") && USER_FOR_51){
    		require_once("51utils.php");
    		add51HeroEvent($hero["name"]);   
    	}
    	if (defined("PASSTYPE")){	
	    	require_once 'game/agents/AgentServiceFactory.php';
	    	AgentServiceFactory::getInstance($uid)->addHeroEvent($hero["name"]);
    	}  	
	}
	return getCityInfoHero($uid,$cid);
}

/**
 * 装备属性排序，为了计算强化
 * @param $attr
 * @return unknown_type
 */
function sortArmorAttr($armor)
{
	$ret = array();
	$attributes=explode(",",$armor['attribute']);
	$attriCount=count($attributes);
	if($attriCount==0||$attributes[0]*2+1!=$attriCount)
	{ 
		return $ret;
	}
	for($i=1;$i<$attriCount;$i=$i+2)
	{
		$type=$attributes[$i];
		$value = $attributes[$i+1];
		$ret["$type"] = $value;
	}
	asort($ret, SORT_NUMERIC);
	return $ret;
}

function regenerateHeroAttri($uid,$hid)
{
	$hero=sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	
	$armors=sql_fetch_rows("select * from sys_user_armor u left join sys_hero_armor h on h.hid=u.hid and h.sid=u.sid left join cfg_armor c on c.id=h.armorid where u.uid='$uid' and u.hid='$hid' and u.hp>0");
	
	//$armors=sql_fetch_rows("select * from sys_user_armor u,sys_hero_armor h left join cfg_armor c on c.id=h.armorid where u.uid='$uid' and u.hid='$hid' and h.sid=u.sid and u.hp>0");

	$level=$hero['level'];
	$command=$hero['level']+$hero['command_base'];
	$affairs=$hero['affairs_base']+$hero['affairs_add'];
	$bravery=$hero['bravery_base']+$hero['bravery_add'];
	$wisdom=$hero['wisdom_base']+$hero['wisdom_add'];

	if(sql_check("select * from mem_hero_buffer where hid='$hid' and buftype=3 and endtime>unix_timestamp()"))
	{
		$bravery=floor($bravery*1.5);
	}
	if(sql_check("select * from mem_hero_buffer where hid='$hid' and buftype=4 and endtime>unix_timestamp()"))
	{
		$wisdom=floor($wisdom*1.25);
	}

	$commandAdd=0;
	$forceAdd=0;
	$energyAdd=0;
	$affairsAdd=0;
	$braveryAdd=0;
	$wisdomAdd=0;
	$speedAdd=0;
	$attackAdd=0;
	$defenceAdd=0;

	foreach($armors as $armor)
	{
		$attributes=explode(",",$armor['attribute']);
		$attriCount=count($attributes);
		if($attriCount==0||$attributes[0]*2+1!=$attriCount) continue;
		for($i=1;$i<$attriCount;$i=$i+2)
		{
			$type=$attributes[$i];
			if($type==1) $commandAdd=$commandAdd+$attributes[$i+1];
			else if($type==2) $affairsAdd=$affairsAdd+$attributes[$i+1];
			else if($type==3) $braveryAdd=$braveryAdd+$attributes[$i+1];
			else if($type==4) $wisdomAdd=$wisdomAdd+$attributes[$i+1];
			else if($type==5) $forceAdd=$forceAdd+$attributes[$i+1];
			else if($type==6) $energyAdd=$energyAdd+$attributes[$i+1];
			else if($type==8) $attackAdd=$attackAdd+$attributes[$i+1];
			else if($type==9) $defenceAdd=$defenceAdd+$attributes[$i+1];
			else if($type==11) $speedAdd=$speedAdd+$attributes[$i+1];
		}
		/*
		//强化加成
		//1：统帅\r\n2：内政\r\n3：勇武\r\n4：智谋\r\n5：体力\r\n6：精力\r\n7：生命\r\n8：攻击\r\n9：防御\r\n10：射程\r\n11：速度\r\n12：负重',
		$strong_value = intval($armor['strong_value']);
		$sattr = sortArmorAttr($armor);
		while($strong_value>0)
		{	
			foreach ($sattr as $key => $val){
				$type = $key;
				if($type==1) $commandAdd=$commandAdd+1;
				else if($type==2) $affairsAdd=$affairsAdd+1;
				else if($type==3) $braveryAdd=$braveryAdd+1;
				else if($type==4) $wisdomAdd=$wisdomAdd+1;
				else if($type==5) $forceAdd=$forceAdd+1;
				else if($type==6) $energyAdd=$energyAdd+1;
				else if($type==8) $attackAdd=$attackAdd+1;
				else if($type==9) $defenceAdd=$defenceAdd+1;
				else if($type==11) $speedAdd=$speedAdd+1;
				$strong_value--;
			}
		}
		//镶嵌加成
		$embedPearls = sql_fetch_rows("select * from cfg_goods where gid in ($armor[embed_pearls])");
		foreach ($embedPearls as $ePearl){
			if($ePearl['gid']==0) continue;
			$attrs = explode(",", $ePearl['attr']);
			for($i=1;$i<count($attrs);$i=$i+2){
				$type=$attrs[$i];
				if($type==1) $commandAdd=$commandAdd+$attrs[$i+1];
				else if($type==2) $affairsAdd=$affairsAdd+$attrs[$i+1];
				else if($type==3) $braveryAdd=$braveryAdd+$attrs[$i+1];
				else if($type==4) $wisdomAdd=$wisdomAdd+$attrs[$i+1];
				else if($type==5) $forceAdd=$forceAdd+$attrs[$i+1];
				else if($type==6) $energyAdd=$energyAdd+$attrs[$i+1];
				else if($type==8) $attackAdd=$attackAdd+$attrs[$i+1];
				else if($type==9) $defenceAdd=$defenceAdd+$attrs[$i+1];
				else if($type==11) $speedAdd=$speedAdd+$attrs[$i+1];
			}
		}*/
		
	}

	$forcemax=100+floor($level/5)+floor(($bravery+$braveryAdd)/3)+$forceAdd;
	$energymax=100+floor($level/5)+floor(($wisdom+$wisdomAdd)/3)+$energyAdd;

	$sql="update sys_city_hero set command_add_on=$commandAdd, affairs_add_on=$affairsAdd, bravery_add_on=$braveryAdd, wisdom_add_on=$wisdomAdd";
	$sql=$sql.",force_max_add_on=$forceAdd,energy_max_add_on=$energyAdd,speed_add_on=$speedAdd,attack_add_on=$attackAdd,defence_add_on=$defenceAdd where hid='$hid'";
	sql_query($sql);
	sql_query("update mem_hero_blood set force_max=$forcemax, energy_max=$energymax,`force`=LEAST(`force`,$forcemax),`energy`=LEAST(`energy`,$energymax) where hid='$hid'");

}
//流亡在外的将领列表
function getExileHeros($uid,$param){
	$heros = sql_fetch_rows("select a.loyalty as loyalty,h.hid,h.name as name,h.level,(h.command_base+h.level+h.command_add_on) as command,(h.affairs_base+h.affairs_add+h.affairs_add_on) as affairs,(h.bravery_base+h.bravery_add+h.bravery_add_on) as bravery,(h.wisdom_base+h.wisdom_add+h.wisdom_add_on) as wisdom from mem_hero_exile a join sys_city_hero h on a.hid = h.hid where a.uid = $uid and h.uid<=897");
	$ret = array();
	$ret[] = $heros;
	return $ret;
}
//召回旧部
function tryCallbackHero($uid,$cid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['tryCallback']['no_hero_info']);
	$heroexile=sql_fetch_one("select * from mem_hero_exile where hid = $hid and uid = $uid");
	if (empty($heroexile)){
		throw new Exception($hero['name'].$GLOBALS['tryCallback']['hero_not_exile']);
	}
	if (cityHasHeroPosition($uid,$cid)== false){ //招贤馆等级是否够
		throw new Exception($GLOBALS['tryCallbackHero']['hotel_level_low']);
	}
	$need=getSummonNeed($hero);
	$ret = array();
	$ret[] = $hid;
	$ret[] = $need;
	return $ret;
}
function sureCallbackHero($uid,$cid,$param)
{
	$cid=array_shift($param);
	$hid = array_shift($param);
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");

	if (empty($hero)) throw new Exception($GLOBALS['tryCallback']['hero_not_exist']);

	$exilehero = sql_fetch_one("select * from mem_hero_exile where hid='$hid'");
	if (empty($exilehero)) throw new Exception($GLOBALS['tryCallback']['hero_not_exile']);

	if (cityHasHeroPosition($uid,$cid)== false){ //招贤馆等级是否够
		throw new Exception($GLOBALS['tryCallbackHero']['hotel_level_low']);
	}


	$loyalty=intval($exilehero["loyalty"]);
	$gold_need = getHeroSummonGold($hero);
	$mygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	if ($mygold < $gold_need) throw new Exception($GLOBALS['sureSummonHero']['no_enough_gold']);
	$goodsList = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
	if (count($goodsList) > 0)
	{
		foreach($goodsList as $goods)
		{
			if (!sql_check("select * from sys_goods where uid='$uid' and gid='$goods[gid]' and count >= $goods[count]"))
			{
				$msg = sprintf($GLOBALS['sureSummonHero']['no_enough_goods'],$goods['name'],$hero['name']);
				throw new Exception($msg);
			}
		}
		//有足够的宝物的话，开始扣
		foreach($goodsList as $goods)
		{
			addGoods($uid,$goods['gid'],-$goods['count'],7);
		}

	}
	//扣黄金
	addCityResources($cid,0,0,0,0,-$gold_need);
	if ($loyalty>=100) $loyalty=100;
	//忠诚值+10
	$loyalty = $loyalty+10;
	//召回
	sql_query("update sys_city_hero set state=0,uid='$uid',cid='$cid',loyalty='$loyalty' where hid='$hid'");
	//清将领流亡状态
	sql_query("delete from mem_hero_exile where hid='$hid'");
	if($hero['npcid']>0)
	{
		$taskid=30000+$hero['npcid'];
		$goalid=sql_fetch_one_cell("select id from cfg_task_goal where tid=$taskid");
		completeTask($uid,$goalid);
	}
	
	$forcemax=100+floor($hero['level']/5)+floor(($hero['bravery_base']+$hero['bravery_add'])/3);
	$energymax=100+floor($hero['level']/5)+floor(($hero['wisdom_base']+$hero['wisdom_add'])/3);
	sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`) values ('$hid',100,$forcemax,100,$energymax)");
	/*
	 * 重新洗点
	sql_query("update sys_city_hero set affairs_add=0,bravery_add=0,wisdom_add=0 where hid='$hid'");		
	regenerateHeroAttri($uid,$hid);
    */ 
	updateCityHeroChange($uid,$cid);
	return getCityInfoHero($uid,$cid);
}

function isHeroInCity($state)
{
	if($state==0 || $state==1 || $state==7 || $state==8)
	return 1;
	return 0;
}

//将领历练类型列表
function getHeroExprTypes($uid,$cid,$param){
	$exprTypes = sql_fetch_rows("select * from cfg_hero_expr_types");
	return $exprTypes;
}

//城池内正在历练的将领
function getExprHeros($uid,$cid,$param){	 
	$exprHeros = sql_fetch_rows("select a.name as heroname,unix_timestamp() as curtime,a.hid,a.cid,a.uid,a.state,b.starttime,b.endtime,b.hours,c.name as exprname from sys_city_hero a left join sys_hero_expr b  on a.hid = b.hid left join cfg_hero_expr_types c on b.type=c.type where a.cid =$cid and a.uid = $uid");
	$exprTypes = sql_fetch_rows("select * from cfg_hero_expr_types");
	$ret = array();
	$nowtime=sql_fetch_one_cell("select unix_timestamp()");
	$ret[]=$nowtime;
	$ret[]=$exprHeros;
	$ret[]=$exprTypes;
	return $ret;
}

//将领开始修炼
function beginExprHero($uid,$cid,$param){
	$hid = array_shift($param);
	$cid = array_shift($param);
	$exprType = array_shift($param);
	$hours = array_shift($param);
	$carraymoney = array_shift($param);		
	
	$heroState = sql_fetch_one_cell("select state from sys_city_hero where hid = $hid");
	if ($heroState!=0)
	   throw  new Exception($GLOBALS['heroexpr']['hero_expr_hero_not_kong']);
	   
	$maxHeroCount = 2;
	if (sql_fetch_one_cell("select count(1) from mem_user_buffer where buftype=100 and uid = $uid")) $maxHeroCount=5;
	$heroCount=sql_fetch_one_cell("select count(1) from sys_hero_expr where cid = $cid");
	if ($heroCount==5)
	   throw  new Exception($GLOBALS['heroexpr']['hero_expr_count_reach_max']);
	if ($heroCount>=$maxHeroCount){
		throw  new Exception($GLOBALS['heroexpr']['toomany_hero_expr']);
	}	
	
	$exprTypeData = sql_fetch_one("select * from cfg_hero_expr_types where type=$exprType");
	$need_money= $hours*intval($exprTypeData["hour_money"]);
	$need_gold= $hours*intval($exprTypeData["hour_gold"]);
	
	$city_Gold = sql_fetch_one_cell("select gold from mem_city_resource where cid = $cid");
	if($city_Gold<$need_gold) throw  new Exception($GLOBALS['heroexpr']['hero_expr_not_enough_gold']);
	
	$have_money=sql_fetch_one_cell("select money from sys_user where uid = $uid");
	if($have_money<$carraymoney+$need_money) throw  new Exception($GLOBALS['heroexpr']['hero_expr_not_enough_money']);
	sql_query("update mem_city_resource set gold = gold - $need_gold where cid = $cid");
	
	addMoney($uid,0-($carraymoney+$need_money),120);
	
	sql_query("insert into sys_hero_expr (`uid`,`cid`,`hid`,`type`,`starttime`,`endtime`,`hours`,`carrymoney`,`accTimes`,`state`) values ('$uid','$cid','$hid',$exprType,unix_timestamp(),unix_timestamp()+3600*$hours,$hours,$carraymoney,0,0)");
	sql_query("update sys_city_hero set state = 10 where hid = $hid");		
}
//取消历练
function cancelHeroExpr($uid,$cid,$param){
    $hid = array_shift($param);
    $item = sql_fetch_one("select b.hour_expr*(unix_timestamp()-starttime)/3600 as exp_add,(unix_timestamp()-starttime)/3600 as hours  from sys_hero_expr a,cfg_hero_expr_types b  where a.type = b.type and  hid = $hid");
    if (empty($item)) return;
    sql_query("update sys_hero_expr set state = 1,endtime= if(unix_timestamp()-starttime>hours*1800,endtime,2*unix_timestamp()-starttime ) where hid = $hid");
    //获得经验 = 已经历练时间× 每小时获得经验数H + 10a 。    
    $exp_add= intval($item["exp_add"])+10*rand($item["hours"],2*$item["hours"]);
    sql_query("update sys_city_hero set state = 11,exp=exp+$exp_add where hid = $hid and state = 10");		
    
}

function fasterHeroExpr($uid,$cid,$param){
	$hid = array_shift($param);
	$item = sql_fetch_one("select * from sys_hero_expr where hid = $hid");
	if (empty($item)) return;
	if ($item["state"]==0){//通关文书
		if(!checkGoods($uid,143)) throw new Exception($GLOBALS['heroexpr']['no_TongGuanWenShu'] );	
		sql_query("update sys_hero_expr set endtime= unix_timestamp()+Floor(0.7*(endtime-unix_timestamp())),accTimes=accTimes+1 where hid = $hid");
		reduceGoods($uid,143,1);		
	}else{//急召令
		if(!checkGoods($uid,144)) throw new Exception($GLOBALS['heroexpr']['no_JiZhaoLin'] );
		 sql_query("delete from sys_hero_expr where hid = $hid");
		 sql_query("update sys_city_hero set state = 0 where hid = $hid");		
		 $carraymoney=$item["carrymoney"];
		 if ($carraymoney>0)
		 	addMoney($uid,$carraymoney,121);
		 reduceGoods($uid,144,1);	
	}
	unlockUser($uid);
}
?>