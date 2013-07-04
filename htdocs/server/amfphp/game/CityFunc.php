<?php
require_once("interface.php");
require_once("utils.php");
require_once("TechnicFunc.php");
require_once("BuildingFunc.php");

function getActivityList($uid,$param)
{
	return sql_fetch_rows("select `content`,`link`,`interval` from sys_activity where inuse=1 order by id");
}

function getCityInfo($uid,$param)
{
	$cid = array_shift($param);
	
	if (sql_check("select cid from sys_city where cid='$cid' and uid='$uid'"))
	{
		$ret = doGetCityAllInfo($uid,$cid);
		sql_query("update sys_user set `lastcid`='$cid' where uid='$uid'");
		return $ret;
	}
	else
	{
		$cityCount = sql_fetch_one_cell("select count(*) from sys_city where uid='$uid'");
		if ($cityCount == 0)
		{
			sql_query("update sys_user set state=4 where uid='$uid' and state <> 3");
			throw new Exception($GLOBALS['getCityInfo']['not_your_city']);
		}
		else
		{
			$othercity = sql_fetch_one_cell("select cid from sys_city where uid='$uid' limit 1");
			sql_query("update sys_user set lastcid='$othercity' where uid='$uid'");
			throw new Exception($GLOBALS['getCityInfo']['city_be_invaded']);
		}
	}
	levyResource($uid,$cid);
	getCityzy($cid,$uid);
}

function getCityBaseInfo($uid,$param)
{
	//在每10秒的查看基本信息里检查服务器状态
	$serverState = sql_fetch_one_cell("select value from mem_state where state=2");
	if ($serverState == 0)
	{
		throw new Exception("server_is_updating");
	}

	sql_query("update sys_online set `lastupdate`=unix_timestamp() where uid='$uid'");

	$cid = array_shift($param);
    
	$ret = doGetCityBaseInfo($uid,$cid);
	//$ret = levyResource($uid,$cid);
    // levyResource($uid,$cid);
	if ($serverState > 1)
	{
		$ret[] = 1;
	}
	else
	{
		$ret[] = 0;
	}


	return $ret;

	
}

function getCityProduct($uid,$param)
{
	$cid = array_shift($param);
	$city = sql_fetch_one("select * from sys_city_res_add where cid=".$cid);
	if (empty($city))
	{
		sql_query("insert into sys_city_res_add (cid) values ('$cid')");
		$city = sql_fetch_one("select * from sys_city_res_add where cid='$cid'");
	}
	//需要劳力
	$food_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_FARMLAND);
	$wood_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_WOOD);
	$rock_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_ROCK);
	$iron_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_IRON);
	//生产能力
	$food_add_base = GLOBAL_FOOD_RATE * $food_all_people * GAME_SPEED_RATE;
	$wood_add_base = GLOBAL_WOOD_RATE * $wood_all_people * GAME_SPEED_RATE;
	$rock_add_base = GLOBAL_ROCK_RATE * $rock_all_people * GAME_SPEED_RATE;
	$iron_add_base = GLOBAL_IRON_RATE * $iron_all_people * GAME_SPEED_RATE;
	//科技加成
	$food_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_FOOD." and cid='".$cid."'");
	$wood_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_WOOD." and cid='".$cid."'");
	$rock_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_ROCK." and cid='".$cid."'");
	$iron_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_IRON." and cid='".$cid."'");

	//将领加成

	$chief_add = 0;
	$chiefHero = sql_fetch_one("select c.chiefhid,h.* from sys_city c left join sys_city_hero h on c.chiefhid=h.hid where c.cid=".$cid);
	if ($chiefHero['chiefhid'] > 0)    //有将领的情况下
	{
		$chief_add = $chiefHero['affairs_add']+$chiefHero['affairs_base']+$chiefHero['affairs_add_on'];
		$heroCommand = $chiefHero["level"]+$chiefHero["command_base"]+$chiefHero["command_add_on"];
		$cityPeopleMax = sql_fetch_one_cell("select people_max from mem_city_resource where cid=".$cid);
		$hufu=1;
		if(sql_check("select hid from mem_hero_buffer where hid='$chiefHero[chiefhid]' and buftype=1 and endtime>unix_timestamp()"))
		{
			$hufu=1.5;
		}
		$leaderTechLevel = intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=6"));
		$peoplerate = $heroCommand*10.0* (100*$hufu + $leaderTechLevel * 10) / ($cityPeopleMax+1);

		if ($peoplerate > 1.0) $peoplerate = 1.0;
		$chief_add =  $chief_add * $peoplerate;

		//文曲星符增加内政25%
		if(sql_check("select hid from mem_hero_buffer where hid='$chiefHero[chiefhid]' and buftype=2 and endtime>unix_timestamp()"))
		{
			$chief_add=$chief_add*1.25;
		}
	}

	//当兵吃粮
	//    $food_army_use = sql_fetch_one_cell("select sum(c.food_use*s.count) from sys_city_soldier s,cfg_soldier c where s.cid='".$cid."' and s.sid=c.sid");
	$food_army_use = sql_fetch_one_cell("select food_army_use from mem_city_resource where cid='$cid'");


	$goods_food_endtime = 0;
	if ($city['goods_food_add'] > 0)
	{
		$goods_food_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=1");
	}
	$goods_wood_endtime = 0;
	if ($city['goods_wood_add'] > 0)
	{
		$goods_wood_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=2");
	}
	$goods_rock_endtime = 0;
	if ($city['goods_rock_add'] > 0)
	{
		$goods_rock_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=3");
	}
	$goods_iron_endtime = 0;
	if ($city['goods_iron_add'] > 0)
	{
		$goods_iron_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=4");
	}
      /*  $peolecount = sql_fetch_one_cell("select people from mem_city_resource where cid='$cid'");
     $aa= $food_all_people*($city['food_rate']/100)+$wood_all_people*$city['wood_rate']/100+$rock_all_people*$city['rock_rate']/100+$iron_all_people*$city['iron_rate']/100;
     if ($peolecount/$aa>1){
        $bl=1;
     }else{
     $bl=$peolecount/$aa;
     }
    $foodadd=$food_add_base*($city['food_rate']/100)*$bl;
     $woodadd=$wood_add_base*($city['wood_rate']/100)*$bl;
     $rockadd=$rock_add_base*($city['rock_rate']/100)*$bl;
     $ironadd=$iron_add_base*($city['iron_rate']/100)*$bl;
     sql_query("update mem_city_resource set people_working='$aa',food_add='$foodadd',wood_add='$woodadd',rock_add='$rockadd',iron_add='$ironadd' where cid='$cid'");
	*/
	$ret = array();
	$ret[] = $city;
	$ret[] = $food_all_people;
	$ret[] = $wood_all_people;
	$ret[] = $rock_all_people;
	$ret[] = $iron_all_people;
	$ret[] = $food_add_base;
	$ret[] = $wood_add_base;
	$ret[] = $rock_add_base;
	$ret[] = $iron_add_base;
	$ret[] = $food_add_rate_technic;
	$ret[] = $wood_add_rate_technic;
	$ret[] = $rock_add_rate_technic;
	$ret[] = $iron_add_rate_technic;
	$ret[] = $food_army_use;
	$ret[] = $chief_add;

	$ret[] = $goods_food_endtime;
	$ret[] = $goods_wood_endtime;
	$ret[] = $goods_rock_endtime;
	$ret[] = $goods_iron_endtime;

	return $ret;
}

function setCityProductRate($uid,$param)
{
	$cid = array_shift($param);
	$food_rate = array_shift($param);
	$wood_rate = array_shift($param);
	$rock_rate = array_shift($param);
	$iron_rate = array_shift($param);

	sql_query("update sys_city_res_add set food_rate='$food_rate',wood_rate='$wood_rate',rock_rate='$rock_rate',iron_rate='$iron_rate',resource_changing=1 where cid='$cid'");
    getCityzy($cid,$uid);//更新产量
	if ($food_rate == 100)
	{
		completeTask($uid,219);
	}
	if ($wood_rate == 100)
	{
		completeTask($uid,220);
	}
	if ($rock_rate == 100)
	{
		completeTask($uid,221);
	}
	if ($iron_rate == 100)
	{
		completeTask($uid,222);
	}

	return array();
}
function changeTax($uid,$param)
{
	$cid = array_shift($param);
	$newtax = array_shift($param);
	$newtax = intval($newtax);
	sql_query("update mem_city_resource set tax='$newtax' where cid='$cid'");
	sql_query("update mem_city_resource set `morale_stable`=GREATEST(0,LEAST(100-`tax`-`complaint`,100)) where cid='$cid'");

	if ($newtax == 20)
	{
		completeTask($uid,20);
	}
	return array();
}
function changeCityName($uid,$param)
{
	$cid = array_shift($param);
	$newName = array_shift($param);
	checkCityOwner($cid,$uid);

	$citytype = sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	if ($citytype > 0) throw new Exception($GLOBALS['changeCityName']['bigcity_norename']);

	if (mb_strlen($newName,"utf-8") > MAX_CITY_NAME)
	{
		throw new Exception($GLOBALS['changeCityName']['name_too_long']);
	}
	else if ((!(strpos($newName,'\'')===false))||(!(strpos($newName,'\\')===false)))
	{
		throw new Exception($GLOBALS['changeCityName']['name_illegal']);
	}
	else  if (sql_check("select * from cfg_baned_name where instr('$newName',`name`)>0"))
	{
		throw new Exception($GLOBALS['changeCityName']['name_illegal']);
	}
	$newName=addslashes($newName);
	$can_change = false;
	$last_change_name = sql_fetch_one_cell("select last_change_name from mem_city_schedule where cid=".$cid);
	if (empty($last_change_name))
	{
		$can_change = true;
	}
	else
	{
		$now = sql_fetch_one_cell("select unix_timestamp()");
		if (floor(($now + 8 * 3600) / 86400 ) > floor(($last_change_name + 8 * 3600) / 86400))
		{
			$can_change = true;
		}
	}
	if (!$can_change)
	{
		throw new Exception($GLOBALS['changeCityName']['today_changed']);
	}

	sql_query("update sys_city set `name`='$newName' where cid=".$cid);
	sql_query("update mem_city_schedule set `last_change_name`=unix_timestamp() where cid=".$cid);

	completeTask($uid,21);
	$ret = array();
	$ret[] = sprintf($GLOBALS['changeCityName']['change_name_to'],$newName);
	return $ret;
}
function levyResource($uid,$param)
{
	$cid = array_shift($param);
	$resid = array_shift($param);
	checkCityOwner($cid,$uid);
	$delta = sql_fetch_one_cell("select unix_timestamp() - `last_levy_resource` from mem_city_schedule where cid='$cid'");
	if (!((empty($delta))||($delta > 900)))
	{
		throw new Exception(sprintf($GLOBALS['levyResource']['time_limit'],MakeTimeLeft(900-$delta)));
	}
	$desc = "";
	$cityInfo=sql_fetch_one("select people,morale from mem_city_resource where cid='$cid'");
	$people =$cityInfo['people'];
	$morale=$cityInfo['morale'];
	if($morale<=20)
	{
		throw new Exception($GLOBALS['levyResource']['not_enough_morale']);
	}
	if ($resid == 0)    //黄金
	{
		$gold = $people * GAME_SPEED_RATE * GLOBAL_GOLD_RATE * 0.1 ;
		sql_query("update mem_city_resource set gold=gold+$gold where cid=".$cid);
		$desc = $GLOBALS['levyResource']['gold'].floor($gold);
		completeTask($uid,10);
	}
	else if($resid == 1)
	{
		$food = $people * GAME_SPEED_RATE*GLOBAL_FOOD_RATE * 0.1 ;
		sql_query("update mem_city_resource set food=food + $food where cid='$cid'");
		$desc = $GLOBALS['levyResource']['food'].floor($food);
		completeTask($uid,11);
	}
	else if ($resid == 2)
	{
		$wood = $people * GAME_SPEED_RATE*GLOBAL_WOOD_RATE * 0.1 ;
		sql_query("update mem_city_resource set wood=wood+$wood where cid=".$cid);
		$desc = $GLOBALS['levyResource']['wood'].floor($wood);
		completeTask($uid,12);
	}
	else if ($resid == 3)
	{
		$rock = $people * GAME_SPEED_RATE*GLOBAL_ROCK_RATE * 0.1 ;
		sql_query("update mem_city_resource set rock=rock+$rock where cid=".$cid);
		$desc = $GLOBALS['levyResource']['rock'].floor($rock);
		completeTask($uid,13);
	}
	else if ($resid == 4)
	{
		$iron = $people * GAME_SPEED_RATE * GLOBAL_IRON_RATE * 0.1 ;
		sql_query("update mem_city_resource set iron=iron+$iron where cid=".$cid);
		$desc = $GLOBALS['levyResource']['iron'].floor($iron);
		completeTask($uid,14);
	}
	sql_query("update mem_city_resource set morale=GREATEST(0,morale-20) where cid='$cid'");
	sql_query("update mem_city_resource set `people_stable`=`people_max` * morale * 0.01  where cid='$cid'");
	sql_query("insert into mem_city_schedule (`cid`,`last_levy_resource`) values ('$cid',unix_timestamp()) on duplicate key update `last_levy_resource`=unix_timestamp()");
	$ret = array();
	$msg = sprintf($GLOBALS['levyResource']['succ_levy'],$desc);
	$ret[] = $msg;
	return $ret;
}
function pacifyPeople($uid,$param)
{
	$cid = array_shift($param);
	$action = array_shift($param);

	checkCityOwner($cid,$uid);
	if (!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);

	$delta = sql_fetch_one_cell("select unix_timestamp() - `last_pacify_people` from mem_city_schedule where cid='$cid'");
	if (!((empty($delta))||($delta > 900)))
	{
		$msg = sprintf($GLOBALS['pacifyPeople']['wait_more_secs'],MakeTimeLeft(900-$delta));
		throw new Exception($msg);
	}
	switch($action)
	{
		case 0: //赈灾消耗粮食=人口上限，提升民心5，减少民怨15。
			$delta = sql_fetch_one_cell("select food-people_max * ".GAME_SPEED_RATE." from mem_city_resource where cid='$cid'");
			if ($delta >= 0)
			{
				//TODO Morale
				sql_query("update mem_city_resource set food=GREATEST(0,food-people_max * ".GAME_SPEED_RATE."),morale=LEAST(100,morale+5),complaint=GREATEST(0,complaint-15),`morale_stable`=GREATEST(0,LEAST(100-`tax`-`complaint`,100)) where cid=".$cid);

				completeTask($uid,6);
				$chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
				if ($chiefhid > 0)
				{
					$value = sql_fetch_one_cell("select people_max from mem_city_resource where cid='$cid'");
					addHeroExp($chiefhid,$value * GAME_SPEED_RATE * FOOD_PRICE);
				}
			}
			else
			{
				throw new Exception($GLOBALS['pacifyPeople']['no_enough_food']);
			}
			break;
		case 1: //祈福消耗黄金=人口上限，提升民心25，减少民怨5。
			$delta = sql_fetch_one_cell("select gold-people_max * ".GAME_SPEED_RATE." from mem_city_resource where cid='$cid'");
			if ($delta >= 0)
			{
				sql_query("update mem_city_resource set gold=GREATEST(0,gold-people_max * ".GAME_SPEED_RATE."),morale=LEAST(100,morale+25),complaint=GREATEST(0,complaint-5),`morale_stable`=GREATEST(0,LEAST(100-`tax`-`complaint`,100)) where cid=".$cid);

				completeTask($uid,7);

				//给城守加经验
				$chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
				if ($chiefhid > 0)
				{
					$value = sql_fetch_one_cell("select people_max from mem_city_resource where cid='$cid'");
					addHeroExp($chiefhid,$value * GAME_SPEED_RATE);
				}
			}
			else
			{
				throw new Exception($GLOBALS['pacifyPeople']['no_enough_gold']);
			}
			break;
		case 2:  //祭天消耗粮食=人口上限，消耗黄金=人口上限*10%，避免1次天灾（将天灾推迟1天），有机会增加1次天赐（几率10%）。
			$fooddelta = sql_fetch_one_cell("select food-people_max * ".GAME_SPEED_RATE." from mem_city_resource where cid='$cid'");
			$golddelta = sql_fetch_one_cell("select gold-people_max * 0.1 * ".GAME_SPEED_RATE." from mem_city_resource where cid='$cid'");
			if (($fooddelta >= 0)&&($golddelta >= 0))
			{
				$next_bad_event = sql_fetch_one_cell("select next_bad_event from mem_city_schedule where cid='$cid'");
				$next_bad_event = $next_bad_event - ($next_bad_event + 8 * 3600) % 86400 + 86400 + rand(0,259200);
				sql_query("update mem_city_schedule set `next_bad_event`='$next_bad_event' where cid='$cid'");

				if (rand(0,9) == 0)
				{
					sql_query("update mem_city_schedule set `next_good_event`=unix_timestamp() where cid='$cid'");
				}

				sql_query("update mem_city_resource set food=GREATEST(0,food-people_max * ".GAME_SPEED_RATE."),gold=GREATEST(0,gold-people_max * 0.1 * ".GAME_SPEED_RATE.") where cid=".$cid);

				completeTask($uid,8);
				//加城守经验
				$chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
				if ($chiefhid > 0)
				{
					$value = sql_fetch_one_cell("select people_max from mem_city_resource where cid='$cid'");
					addHeroExp($chiefhid,$value * GAME_SPEED_RATE * (FOOD_PRICE+1));
				}
			}
			else
			{
				if ($fooddelta < 0)
				{
					throw new Exception($GLOBALS['pacifyPeople']['no_enough_food']);
				}
				else
				{
					throw new Exception($GLOBALS['pacifyPeople']['no_enough_gold']);
				}
			}
			break;
		case 3:   //增丁：增丁消耗粮食=人口上限*5，增加人口=人口上限*5%。不超过人口上限。
			$fooddelta = sql_fetch_one_cell("select food-people_max * 5 * ".GAME_SPEED_RATE." from mem_city_resource where cid='$cid'");
			if ($fooddelta >= 0)
			{
				sql_query("update mem_city_resource set food=food-people_max * 5 * ".GAME_SPEED_RATE.",people=LEAST(people_max,people+floor(people_max*".GAME_SPEED_RATE."*0.05)) where cid='$cid'");

				$chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
				if ($chiefhid > 0)
				{
					$value = sql_fetch_one_cell("select people_max * 5 from mem_city_resource where cid='$cid'");
					addHeroExp($chiefhid,$value * GAME_SPEED_RATE * FOOD_PRICE);
				}

				completeTask($uid,9);
			}
			else
			{
				throw new Exception($GLOBALS['pacifyPeople']['no_enough_food']);
			}
			break;
	}

	sql_query("update mem_city_resource set `people_stable`=`people_max` * morale * 0.01  where cid='$cid'");
	sql_query("insert into mem_city_schedule (`cid`,`last_pacify_people`) values ('$cid',unix_timestamp()) on duplicate key update `last_pacify_people`=unix_timestamp()");
	unlockUser($uid);
	$ret = array();
	$ret[] = $GLOBALS['pacifyPeople']['succ_pacify'];
	return $ret;
}

function getCityField($uid,$param)
{
	$cid = array_shift($param);
	return sql_fetch_rows("select m.*,g.troopid from mem_world m left join sys_gather g on g.wid=m.wid where m.ownercid='$cid' and m.type>0");
}
function getFieldDetail($uid,$param)
{
	$wid = array_shift($param);
	$ret = array();
	$ret[] = sql_fetch_rows("select t.*,u.name as username,h.name as heroname from sys_user u,sys_troops t left join sys_city_hero h on h.hid=t.hid where t.uid=u.uid and t.targetcid='".wid2cid($wid)."' and t.state=4 and t.uid > 0");
	$ret[] = sql_fetch_one("select * from sys_gather where wid='$wid'");
	return $ret;
}
function gatherFieldStart($uid,$param)
{
	$oriparam = $param;
	$wid = array_shift($param);
	$worldInfo = sql_fetch_one("select * from mem_world where wid='$wid'");
	if ($worldInfo['type'] < 1) throw new Exception($GLOBALS['gatherFieldStart']['field_is_city']);
	else if($worldInfo['type']==1) throw new Exception($GLOBALS['gatherFieldStart']['field_is_pingdi']);
	if ($worldInfo['state'] != 0) throw new Exception($GLOBALS['gatherFieldStart']['field_in_battle']);
	if ($worldInfo['level'] == 0) throw new Exception($GLOBALS['gatherFieldStart']['field_level_0']);
	$owneruid = sql_fetch_one_cell("select uid from sys_city where cid='$worldInfo[ownercid]'");

	if ($owneruid != $uid) throw new Exception($GLOBALS['gatherFieldStart']['not_your_field']);
	$troop = sql_fetch_one("select * from sys_troops where targetcid=".wid2cid($wid)." and state=4 and uid='$uid' limit 1");
	if (empty($troop)) throw new Exception($GLOBALS['gatherFieldStart']['no_army']);
	if ($troop['hid'] == 0) throw new Exception($GLOBALS['gatherFieldStart']['no_hero']);
	if (sql_check("select * from sys_gather where wid='$wid' and troopid='$troop[id]'")) throw new Exception($GLOBALS['gatherFieldStart']['you_are_gathering']);

	sql_query("replace into sys_gather (wid,troopid,fooduse,level,starttime) values ('$wid','$troop[id]','$troop[fooduse]','$worldInfo[level]',unix_timestamp())");
	return getFieldDetail($uid,$oriparam);
}
function gatherFieldResult($uid,$cid,$delta,$fooduse,$fieldtype,$fieldlevel,$heroLevel,$heroid,$wid)
{
	$FIELD_RATE = array(0,0.06,0.045,0.09,0.07,0.035,0.09,0.08);
	$GOODS_RATE = array(array(6,7,5,5,3,4,2,2,1),
	array(2,2,10,2,6,3,3,6,1),
	array(3,4,3,10,3,5,4,2,1),
	array(3,3,8,7,3,5,2,3,1),
	array(1,1,1,1,10,10,5,5,1),
	array(15,10,1,2,1,1,1,1,3),
	array(5,6,3,4,4,2,8,1,2));
	/////////////////
	//need test
	//@ming
	//$GOODS_NAME = array($GLOBALS['trickGuanMemDaGou']['ZhenZhu'],$GLOBALS['trickGuanMemDaGou']['ShanHu'],$GLOBALS['trickGuanMemDaGou']['LiuLi'],$GLOBALS['trickGuanMemDaGou']['HuPo'],$GLOBALS['trickGuanMemDaGou']['MaNao'] ,$GLOBALS['trickGuanMemDaGou']['ShuiJing'],$GLOBALS['trickGuanMemDaGou']['FeiCui'],$GLOBALS['trickGuanMemDaGou']['YuShi'],$GLOBALS['trickGuanMemDaGou']['YeMingZhu']);
	$GOODS_NAME = array();
	$GOODS_NAME[30] = $GLOBALS['gatherFieldResult']['ZhenZhu'];
	$GOODS_NAME[31] = $GLOBALS['gatherFieldResult']['ShanHu'];
	$GOODS_NAME[32] = $GLOBALS['gatherFieldResult']['LiuLi'];
	$GOODS_NAME[33] = $GLOBALS['gatherFieldResult']['HuPo'];
	$GOODS_NAME[34] = $GLOBALS['gatherFieldResult']['MaNao'];
	$GOODS_NAME[35] = $GLOBALS['gatherFieldResult']['ShuiJing'];
	$GOODS_NAME[36] = $GLOBALS['gatherFieldResult']['FeiCui'];
	$GOODS_NAME[37] = $GLOBALS['gatherFieldResult']['YuShi'];
	$GOODS_NAME[38] = $GLOBALS['gatherFieldResult']['YeMingZhu'];
	$GOODS_NAME[50] = $GLOBALS['gatherFieldResult']['GuPuMuHe'];
	$GOODS_NAME[119] = $GLOBALS['gatherFieldResult']['CangBaoHe'];
	$GOODS_NAME[10014] = $GLOBALS['gatherFieldResult']['XiangSiDou'];
	$GOODS_NAME[10015] = $GLOBALS['gatherFieldResult']['XiangSiYuDi'];


	$now = sql_fetch_one_cell("select unix_timestamp()");
	mt_srand(time());
	$fieldRate = $FIELD_RATE[$fieldtype];
	$resCount = floor($fieldlevel * $fooduse * $fieldRate *$delta / 3600);
	if ($fieldtype == 1)
	{
		$restype = mt_rand() & 3;
	}
	else if (($fieldtype == 4)||($fieldtype == 6)||($fieldtype == 7))
	{
		$restype = 0;//粮食
	}
	else if ($fieldtype == 3)
	{
		$restype = 1;//木材
	}
	else if ($fieldtype == 2)
	{
		$restype = 2;   //石料
	}
	else if ($fieldtype == 5)
	{
		$restype = 3;   //铁锭
	}
	$exp = 0;
	if ($restype == 0)
	{
		$exp = $resCount * FOOD_VALUE *0.01;
	}
	else if ($restype == 1)
	{
		$exp = $resCount * WOOD_VALUE * 0.01;
	}
	else if ($restype == 2)
	{
		$exp = $resCount * ROCK_VALUE * 0.01;
	}
	else if ($restype == 3)
	{
		$exp = $resCount * IRON_VALUE * 0.01;
	}
	addHeroExp($heroid,$exp);
	$ret = "";
	if ($restype == 0)
	{
		addCityResources($cid,0,0,0,$resCount,0);
		$ret = $GLOBALS['gatherFieldResult']['food'].$resCount;
	}
	else if ($restype == 1)
	{
		addCityResources($cid,$resCount,0,0,0,0);
		$ret = $GLOBALS['gatherFieldResult']['wood'].$resCount;
	}
	else if ($restype == 2)
	{
		addCityResources($cid,0,$resCount,0,0,0);
		$ret = $GLOBALS['gatherFieldResult']['rock'].$resCount;
	}
	else if ($restype == 3)
	{
		addCityResources($cid,0,0,$resCount,0,0);
		$ret = $GLOBALS['gatherFieldResult']['iron'].$resCount;
	}
	//宝物
	$dropCount = floor($delta / 3600);

//	$dropCount=24;

	if ($dropCount > 24) $dropCount = 24;
	$goodGet = array();
	$allCount = 0;

	$fetchrate=0.05 + 0.01 *($fieldlevel+floor($heroLevel/10)+floor($fooduse/10000));//每次获得宝物几率=(N+10)%
	if($fetchrate>0.6) $fetchrate=0.6;


	//如果采集时间大于一小时，检查是否有藏宝图
	if($delta>=3600){
		$fieldcid=wid2cid($wid);
		$rows=sql_fetch_rows("select id from mem_treasure_map where cid='$fieldcid' and uid='$uid'");
		if(!empty($rows)){
			//同一块野地可能有很多藏宝图,从第一个开始用
			$row=$rows[0];
			sql_query("delete from mem_treasure_map where id='$row[id]'");
			$goodsGet[119] = 1;
			$now = sql_fetch_one_cell("select unix_timestamp()");
			$tresult = sql_fetch_one("select * from cfg_act where $now>= starttime and $now <= endtime and type = 0 ");
			
			if($tresult)
			{
				$getrate=mt_rand(1,100); 
				if($getrate<=$tresult["rate"]){
					$ret=$ret."，".openDefaultBox($uid,$cid,$tresult["actid"],1);
				}
			}
		}
	}


	for ($i = 0; $i < $dropCount; $i++)
	{
		if (mt_rand(0,1000) < 1000 *  $fetchrate)
		{
			$goodsRate = $GOODS_RATE[$fieldtype-1];
			$rnd = mt_rand() % array_sum($goodsRate);
			$goodsCount = count($goodsRate);
			$sumRate = 0;

			for ($j = 0;$j < $goodsCount; $j++)
			{
				$sumRate += $goodsRate[$j];
				if ($rnd < $sumRate)
				{
					$gid = $j + 30;
					if (isset($goodsGet[$gid]))
					{
						$goodsGet[$gid] += 1;
					}
					else
					{
						$goodsGet[$gid] = 1;
					}
					$allCount++;
					break;
				}
			}

		}
	}
	//单次采集时间>=10小时，获得珍宝数量<=2，就有可能额外获得古朴木盒。作为采集的补偿。
	//获得几率=（50 +军队消耗粮食/2000）/（1+M+N）%，最高几率100%。每次获得一个古朴木盒。N为24小时内获得的古朴木盒数量。
	//如果珍宝数量>2，获得几率=50 /（1+M+N）%。
	//M为本次获得的珍宝数量。N为24小时内获得的古朴木盒数量。
	if (($dropCount >= 10) )
	{
		$todayWoodBoxCount = getTodayGatherCount($uid,50);
		$rate=0;
		if($allCount <=2){
			$rate=(50 + ($fooduse / 2000))/(1+$allCount+$todayWoodBoxCount);
		}
		else{
			$rate=50/(1+$allCount+$todayWoodBoxCount);
		}
		if (mt_rand(0,1000) <($rate*10) ){
			$goodsGet[50] = 1;
			sql_query("insert into log_gather_count (gid,uid,count,time) values (50,'$uid',1,unix_timestamp()) on duplicate key update count=count+1 ");
		}
	}
	//相思豆  8.7-8.20
	if (($now >= 1218038400)&&($now <  1219248000))
	{
		if ($dropCount >= 10)
		{
			if (mt_rand(0,100) < 50 + ($fooduse / 10000))
			{
				$goodsGet[10014] = 1;
			}
		}
		if ($dropCount >= 5)
		{
			if (mt_rand(0,100) < 50 + ($fooduse / 10000))
			{
				$goodsGet[10015] = 1;
			}
		}
	}

	//获得道具
	if(!empty($goodsGet))
	{
		foreach($goodsGet as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,1);
			$ret .= "，".$GOODS_NAME[$gid].$cnt; 
		}
	}
	//中秋月兔
	$yuetuday = sql_fetch_one_cell("select floor((unix_timestamp() - unix_timestamp('2008-09-08 19:00:00'))/86400)");
	if (($yuetuday >= 0)&&($yuetuday < 4))
	{
		if (sql_check("select * from evt_zq_yt where day='$yuetuday' and wid='$wid'"))
		{
			$yuetuCount = floor(log10($fooduse) * $delta / 3600);
			if ($yuetuCount > 0)
			{
				sql_query("insert into sys_things (uid,tid,count) values ('$uid','10022','$yuetuCount') on duplicate key update `count`=`count`+'$yuetuCount'");
				$ret .= "，月兔".$yuetuCount;
			}
		}
	}
	return $ret."。";
}
function gatherFieldResultForEndAll($uid,$cid,$delta,$fooduse,$fieldtype,$fieldlevel,$heroLevel,$heroid,$wid,$para)
{
	$FIELD_RATE = array(0,0.06,0.045,0.09,0.07,0.035,0.09,0.08);
	$GOODS_RATE = array(array(6,7,5,5,3,4,2,2,1),
	array(2,2,10,2,6,3,3,6,1),
	array(3,4,3,10,3,5,4,2,1),
	array(3,3,8,7,3,5,2,3,1),
	array(1,1,1,1,10,10,5,5,1),
	array(15,10,1,2,1,1,1,1,3),
	array(5,6,3,4,4,2,8,1,2));
	/////////////////
	//need test
	//@ming
	//$GOODS_NAME = array($GLOBALS['trickGuanMemDaGou']['ZhenZhu'],$GLOBALS['trickGuanMemDaGou']['ShanHu'],$GLOBALS['trickGuanMemDaGou']['LiuLi'],$GLOBALS['trickGuanMemDaGou']['HuPo'],$GLOBALS['trickGuanMemDaGou']['MaNao'] ,$GLOBALS['trickGuanMemDaGou']['ShuiJing'],$GLOBALS['trickGuanMemDaGou']['FeiCui'],$GLOBALS['trickGuanMemDaGou']['YuShi'],$GLOBALS['trickGuanMemDaGou']['YeMingZhu']);
	$GOODS_NAME = array();
	$GOODS_NAME[30] = $GLOBALS['gatherFieldResult']['ZhenZhu'];
	$GOODS_NAME[31] = $GLOBALS['gatherFieldResult']['ShanHu'];
	$GOODS_NAME[32] = $GLOBALS['gatherFieldResult']['LiuLi'];
	$GOODS_NAME[33] = $GLOBALS['gatherFieldResult']['HuPo'];
	$GOODS_NAME[34] = $GLOBALS['gatherFieldResult']['MaNao'];
	$GOODS_NAME[35] = $GLOBALS['gatherFieldResult']['ShuiJing'];
	$GOODS_NAME[36] = $GLOBALS['gatherFieldResult']['FeiCui'];
	$GOODS_NAME[37] = $GLOBALS['gatherFieldResult']['YuShi'];
	$GOODS_NAME[38] = $GLOBALS['gatherFieldResult']['YeMingZhu'];
	$GOODS_NAME[50] = $GLOBALS['gatherFieldResult']['GuPuMuHe'];
	$GOODS_NAME[119] = $GLOBALS['gatherFieldResult']['CangBaoHe'];
	$GOODS_NAME[10014] = $GLOBALS['gatherFieldResult']['XiangSiDou'];
	$GOODS_NAME[10015] = $GLOBALS['gatherFieldResult']['XiangSiYuDi'];


	$now = sql_fetch_one_cell("select unix_timestamp()");
	$fieldRate = $FIELD_RATE[$fieldtype];
	$resCount = floor($fieldlevel * $fooduse * $fieldRate *$delta / 3600);
	if ($fieldtype == 1)
	{
		$restype = mt_rand() & 3;
	}
	else if (($fieldtype == 4)||($fieldtype == 6)||($fieldtype == 7))
	{
		$restype = 0;//粮食
	}
	else if ($fieldtype == 3)
	{
		$restype = 1;//木材
	}
	else if ($fieldtype == 2)
	{
		$restype = 2;   //石料
	}
	else if ($fieldtype == 5)
	{
		$restype = 3;   //铁锭
	}
	$exp = 0;
	if ($restype == 0)
	{
		$exp = $resCount * FOOD_VALUE *0.01;
	}
	else if ($restype == 1)
	{
		$exp = $resCount * WOOD_VALUE * 0.01;
	}
	else if ($restype == 2)
	{
		$exp = $resCount * ROCK_VALUE * 0.01;
	}
	else if ($restype == 3)
	{
		$exp = $resCount * IRON_VALUE * 0.01;
	}
	addHeroExp($heroid,$exp);
	$ret = "";
	if ($restype == 0)
	{
		addCityResources($cid,0,0,0,$resCount,0);
		$ret = $GLOBALS['gatherFieldResult']['food'].$resCount;
	}
	else if ($restype == 1)
	{
		addCityResources($cid,$resCount,0,0,0,0);
		$ret = $GLOBALS['gatherFieldResult']['wood'].$resCount;
	}
	else if ($restype == 2)
	{
		addCityResources($cid,0,$resCount,0,0,0);
		$ret = $GLOBALS['gatherFieldResult']['rock'].$resCount;
	}
	else if ($restype == 3)
	{
		addCityResources($cid,0,0,$resCount,0,0);
		$ret = $GLOBALS['gatherFieldResult']['iron'].$resCount;
	}
	//宝物
	$dropCount = floor($delta / 3600);

//	$dropCount=24;

	if ($dropCount > 24) $dropCount = 24;
	$goodGet = array();
	$allCount = 0;

	$fetchrate=0.05 + 0.01 *($fieldlevel+floor($heroLevel/10)+floor($fooduse/10000));//每次获得宝物几率=(N+10)%
	if($fetchrate>0.6) $fetchrate=0.6;
	
	//如果采集时间大于一小时，检查是否有藏宝图
	if($delta>=3600){
		$fieldcid=wid2cid($wid);
		$rows=sql_fetch_rows("select id from mem_treasure_map where cid='$fieldcid' and uid='$uid'");
		if(!empty($rows)){
			//同一块野地可能有很多藏宝图,从第一个开始用
			$row=$rows[0];
			sql_query("delete from mem_treasure_map where id='$row[id]'");
			$goodsGet[119] = 1;
			$now = sql_fetch_one_cell("select unix_timestamp()");
			$tresult = sql_fetch_one("select * from cfg_act where $now>= starttime and $now <= endtime and type = 0 ");
			
			if($tresult)
			{
				$getrate=mt_rand(1,100); 
				if($getrate<=$tresult["rate"]){
					$ret=$ret."，".openDefaultBox($uid,$cid,$tresult["actid"],1);					
				}
			}
		}
	}
	
	

	for ($i = 0; $i < $dropCount; $i++)
	{
		$randPara=mt_rand(0,$para*10);
		if (mt_rand(0,1000*$randPara) < 1000 * $fetchrate*$randPara)
		{
			$goodsRate = $GOODS_RATE[$fieldtype-1];
			$rnd = (mt_rand(0,$para*100)) % array_sum($goodsRate);
			$goodsCount = count($goodsRate);
			$sumRate = 0;

			for ($j = 0;$j < $goodsCount; $j++)
			{
				$sumRate += $goodsRate[$j];
				if ($rnd < $sumRate)
				{
					$gid = $j + 30;
					if (isset($goodsGet[$gid]))
					{
						$goodsGet[$gid] += 1;
					}
					else
					{
						$goodsGet[$gid] = 1;
					}
					$allCount++;
					break;
				}
			}

		}
	}
	//单次采集时间>=10小时，获得珍宝数量<=2，就有可能额外获得古朴木盒。作为采集的补偿。
	//获得几率=（50 +军队消耗粮食/2000）/（1+M+N）%，最高几率100%。每次获得一个古朴木盒。N为24小时内获得的古朴木盒数量。
	//如果珍宝数量>2，获得几率=50 /（1+M+N）%。
	//M为本次获得的珍宝数量。N为24小时内获得的古朴木盒数量。
	if (($dropCount >= 10) )
	{
		$randPara=mt_rand(0,$para*10);
		$todayWoodBoxCount = getTodayGatherCount($uid,50);
		$rate=0;
		if($allCount <=2){
			$rate=(50 + ($fooduse / 2000))/(1+$allCount+$todayWoodBoxCount);
		}
		else{
			$rate=50/(1+$allCount+$todayWoodBoxCount);
		}
		if (mt_rand(0,1000*$randPara) <($rate*10*$randPara) ){
			$goodsGet[50] = 1;
			sql_query("insert into log_gather_count (gid,uid,count,time) values (50,'$uid',1,unix_timestamp()) on duplicate key update count=count+1 ");
		}
	}
	//相思豆  8.7-8.20
	if (($now >= 1218038400)&&($now <  1219248000))
	{
		if ($dropCount >= 10)
		{
			if (mt_rand(0,100) < 50 + ($fooduse / 10000))
			{
				$goodsGet[10014] = 1;
			}
		}
		if ($dropCount >= 5)
		{
			if (mt_rand(0,100) < 50 + ($fooduse / 10000))
			{
				$goodsGet[10015] = 1;
			}
		}
	}

	//获得道具
	if(!empty($goodsGet))
	{
		foreach($goodsGet as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,1);
			$ret .= "，".$GOODS_NAME[$gid].$cnt; 
		}
	}
	//中秋月兔
	$yuetuday = sql_fetch_one_cell("select floor((unix_timestamp() - unix_timestamp('2008-09-08 19:00:00'))/86400)");
	if (($yuetuday >= 0)&&($yuetuday < 4))
	{
		if (sql_check("select * from evt_zq_yt where day='$yuetuday' and wid='$wid'"))
		{
			$yuetuCount = floor(log10($fooduse) * $delta / 3600);
			if ($yuetuCount > 0)
			{
				sql_query("insert into sys_things (uid,tid,count) values ('$uid','10022','$yuetuCount') on duplicate key update `count`=`count`+'$yuetuCount'");
				$ret .= "，月兔".$yuetuCount;
			}
		}
	}
	return $ret."。";
}
function gatherFieldEnd($uid,$param)
{
	$oriparam = $param;
	$wid = array_shift($param);
	$worldInfo = sql_fetch_one("select * from mem_world where wid='$wid'");
	if ($worldInfo['state'] != 0) throw new Exception($GLOBALS['gatherFieldEnd']['field_in_battle']);
	$owneruid = sql_fetch_one_cell("select uid from sys_city where cid='$worldInfo[ownercid]'");
	if ($owneruid != $uid) throw new Exception($GLOBALS['gatherFieldEnd']['not_your_field']);
	lockUser($uid);
	$gather = sql_fetch_one("select * from sys_gather where wid='$wid'");
	sql_query("delete from sys_gather where wid='$wid'");
	$msg = "";
	if (!empty($gather))
	{
		$troop = sql_fetch_one("select * from sys_troops where id='$gather[troopid]'");
		$heroLevel = 0;
		if ($troop['hid'] > 0)
		{
			$heroLevel = sql_fetch_one_cell("select level from sys_city_hero where hid='$troop[hid]'");
		}
		if (empty($troop)||($troop['state']!=4))
		{
			$msg = $GLOBALS['gatherFieldEnd']['no_people_gather'];
		}
		else if ($gather['level'] == 0)
		{
			$msg = $GLOBALS['gatherFieldEnd']['field_level_0'];
		}
		else
		{
			$now = sql_fetch_one_cell("select unix_timestamp()");
			$delta = $now - $gather['starttime'];
			if ($delta < 3600)
			{
				$msg = $GLOBALS['gatherFieldEnd']['gather_time_lessThen_1'];
			}
			else
			{

				$msg = sprintf($GLOBALS['gatherFieldEnd']['through_gathering'],MakeTimeLeft($delta));
				$msg .= gatherFieldResult($uid,$worldInfo['ownercid'],$delta,$gather['fooduse'],$worldInfo['type'],$gather['level'],$heroLevel,$troop['hid'],$wid);

			}
		}
	}
	else
	{
		$msg = $GLOBALS['gatherFieldEnd']['already_got'];
	}
	unlockUser($uid);
	$ret = array();
	$ret[] =  getFieldDetail($uid,$oriparam);
	$ret[] = $msg;
	return $ret;
}

function updateFieldResourceAdd($cid)
{
	$fields = sql_fetch_rows("select * from mem_world where type>1 and ownercid=".$cid);
	$food_add = 0;
	$wood_add = 0;
	$rock_add = 0;
	$iron_add = 0;
	foreach ($fields as $field)
	{
		$type = $field['type'];
		$level = $field['level'];
		if($level>0)
		{
			switch($type)
			{
				case WT_DESERT:
					$rock_add += (3+2 * $level);
					break;
				case WT_FOREST:
					$wood_add += (3+2 * $level);
					break;
				case WT_GRASS:
					$food_add += (2+ $level);
					break;
				case WT_HILL:
					$iron_add += (3+2 * $level);
					break;
				case WT_LAKE:
					$food_add += (5+3 * $level);
					break;
				case WT_SWAMP:
					$food_add += (3+2 * $level);
					break;
			}
		}
	}
	sql_query("update sys_city_res_add set field_food_add='$food_add',field_wood_add='$wood_add',field_rock_add='$rock_add',field_iron_add='$iron_add' where cid=".$cid);
	updateCityResourceAdd($cid);

}
//更新资源
function getCityzy($cid,$uid)
{
	//$cid = array_shift($param);
	$city = sql_fetch_one("select * from sys_city_res_add where cid=".$cid);
	if (empty($city))
	{
		sql_query("insert into sys_city_res_add (cid) values ('$cid')");
		$city = sql_fetch_one("select * from sys_city_res_add where cid='$cid'");
	}
	//需要劳力
	$food_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_FARMLAND);
	$wood_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_WOOD);
	$rock_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_ROCK);
	$iron_all_people = sql_fetch_one_cell("select sum(l.using_people) from sys_building b,cfg_building_level l where b.bid=l.bid and b.level=l.level and b.cid='".$cid."' and b.bid=".ID_BUILDING_IRON);
	//生产能力
	$food_add_base = GLOBAL_FOOD_RATE * $food_all_people * GAME_SPEED_RATE;
	$wood_add_base = GLOBAL_WOOD_RATE * $wood_all_people * GAME_SPEED_RATE;
	$rock_add_base = GLOBAL_ROCK_RATE * $rock_all_people * GAME_SPEED_RATE;
	$iron_add_base = GLOBAL_IRON_RATE * $iron_all_people * GAME_SPEED_RATE;
	//科技加成
	$food_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_FOOD." and cid='".$cid."'");
	$wood_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_WOOD." and cid='".$cid."'");
	$rock_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_ROCK." and cid='".$cid."'");
	$iron_add_rate_technic = sql_fetch_one_cell("select level*10 from sys_city_technic t where tid=".ID_TECHNIC_IRON." and cid='".$cid."'");

	//将领加成

	$chief_add = 0;
	$chiefHero = sql_fetch_one("select c.chiefhid,h.* from sys_city c left join sys_city_hero h on c.chiefhid=h.hid where c.cid=".$cid);
	if ($chiefHero['chiefhid'] > 0)    //有将领的情况下
	{
		$chief_add = $chiefHero['affairs_add']+$chiefHero['affairs_base']+$chiefHero['affairs_add_on'];
		$heroCommand = $chiefHero["level"]+$chiefHero["command_base"]+$chiefHero["command_add_on"];
		$cityPeopleMax = sql_fetch_one_cell("select people_max from mem_city_resource where cid=".$cid);
		$hufu=1;
		if(sql_check("select hid from mem_hero_buffer where hid='$chiefHero[chiefhid]' and buftype=1 and endtime>unix_timestamp()"))
		{
			$hufu=1.5;
		}
		$leaderTechLevel = intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=6"));
		$peoplerate = $heroCommand*10.0* (100*$hufu + $leaderTechLevel * 10) / ($cityPeopleMax+1);

		if ($peoplerate > 1.0) $peoplerate = 1.0;
		$chief_add =  $chief_add * $peoplerate;

		//文曲星符增加内政25%
		if(sql_check("select hid from mem_hero_buffer where hid='$chiefHero[chiefhid]' and buftype=2 and endtime>unix_timestamp()"))
		{
			$chief_add=$chief_add*1.25;
		}
	}

	//当兵吃粮
	//    $food_army_use = sql_fetch_one_cell("select sum(c.food_use*s.count) from sys_city_soldier s,cfg_soldier c where s.cid='".$cid."' and s.sid=c.sid");
	$food_army_use = sql_fetch_one_cell("select food_army_use from mem_city_resource where cid='$cid'");

/*
	$goods_food_endtime = 0;
	if ($city['goods_food_add'] > 0)
	{
		$goods_food_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=1");
	}
	$goods_wood_endtime = 0;
	if ($city['goods_wood_add'] > 0)
	{
		$goods_wood_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=2");
	}
	$goods_rock_endtime = 0;
	if ($city['goods_rock_add'] > 0)
	{
		$goods_rock_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=3");
	}
	$goods_iron_endtime = 0;
	if ($city['goods_iron_add'] > 0)
	{
		$goods_iron_endtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=4");
	}*/
     $peolecount = sql_fetch_one_cell("select people from mem_city_resource where cid='$cid'");
     $tm = sql_fetch_one("select lastupdate from mem_city_resource where cid='$cid'");
     $aa= $food_all_people*($city['food_rate']/100)+$wood_all_people*$city['wood_rate']/100+$rock_all_people*$city['rock_rate']/100+$iron_all_people*$city['iron_rate']/100;
     if ($peolecount/$aa>1){
        $bl=1;
     }else{
     $bl=$peolecount/$aa;
     }
     $foodadd=$food_add_base*($city['food_rate']/100)*$bl;
     $woodadd=$wood_add_base*($city['wood_rate']/100)*$bl;
     $rockadd=$rock_add_base*($city['rock_rate']/100)*$bl;
     $ironadd=$iron_add_base*($city['iron_rate']/100)*$bl;
      
     //$foodad=$foodadd/3600;
     //$woodad=$woodadd/3600
     //$rockad=$rockadd/3600
     //$ironad=$ironadd/3600
      //sql_query("update mem_city_resource set people_working='$aa',food=food+'$foodad'*(unix_timestamp()-$tm),wood=wood+'$woodad'*(unix_timestamp()-$tm),rock=rock+'$rockad'*(unix_timestamp()-$tm),iron=iron+'$ironad'*(unix_timestamp()-$tm),food_add='$foodadd',wood_add='$woodadd',rock_add='$rockadd',iron_add='$ironadd' where cid='$cid'");
     sql_query("update mem_city_resource set people_working='$aa',food_add='$foodadd',wood_add='$woodadd',rock_add='$rockadd',iron_add='$ironadd' where cid='$cid'");
     //lastupdate=unix_timestamp(),
     	 
}
function discardField($uid,$param)
{
	$param2 = $param;
	$cid = array_shift($param2);
	$wid = array_shift($param2);
	checkCityOwner($cid,$uid);
	$originOwner = sql_fetch_one_cell("select ownercid from mem_world where wid='$wid'");
	if ($originOwner != $cid)
	{
		throw new Exception($GLOBALS['gatherFieldResult']['not_your_field']);
	}
	if (sql_check("select * from sys_troops where targetcid='".wid2cid($wid)."' and state=4 and uid > 0"))
	{
		throw new Exception($GLOBALS['gatherFieldResult']['cant_dismiss_with_army']);
	}
	sql_query("update mem_world set ownercid=0 where wid='$wid'");
	sql_query("delete from sys_gather where wid='$wid'");
	updateFieldResourceAdd($cid);

	return getCityField($uid,$param);
}
function callBackFieldTroop($uid,$param)
{

	callBackTroop($uid,$param);

	return getFieldDetail($uid,$param);
}
function kickBackFieldTroop($uid,$param)
{
	$cid = array_shift($param);
	$troopid = array_shift($param);
	$targetcid = wid2cid($param[0]);

	if (!sql_check("select * from sys_troops where id='$troopid' and task=1 and targetcid='$targetcid' and state=4"))
	{
		throw new Exception($GLOBALS['kickBackFieldTroop']['army_not_exist']);
	}
	sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where id='$troopid'");

	updateCityResourceAdd($cid);
	return getFieldDetail($uid,$param);
}
function getCityList($uid,$param)
{
	return sql_fetch_rows("select c.*,h.name as chiefname,m.people,m.morale from mem_city_resource m,sys_city c left join sys_city_hero h on c.chiefhid=h.hid where m.cid=c.cid and c.uid='$uid'");
}
function discardCity($uid,$param)
{
	$cid = array_shift($param);
	//$password = addslashes(trim(array_shift($param)));

	require_once("../config/db.php");
	
	//废弃城池，全部取消输入密码的限制
	/*if (!USER_FOR_51 && !checkUserPassport($uid,$password))
	{
		throw new Exception($GLOBALS['discardCity']['invalid_pwd']);
	}*/
	$cityInfo=sql_fetch_one("select * from `sys_city` where `cid`='$cid'");
	$userid=$cityInfo['uid'];
	if($userid!=$uid)
	{
		throw new Exception($GLOBALS['discardCity']['not_your_city']);
	}
	if (sql_check("select * from mem_world where wid=".cid2wid($cid)." and state=1"))
	{
		throw new Exception($GLOBALS['discardCity']['city_in_battle']);
	}
	if (sql_check("select * from sys_troops where cid='$cid' and uid ='$uid'"))
	{
		throw new Exception($GLOBALS['discardCity']['has_army_outside']);
	}


	$ownerfields=sql_fetch_rows("select wid from mem_world where ownercid='$cid'");
	if(!empty($ownerfields))
	{
		$comma="";
		foreach($ownerfields as $mywid)
		{
			$fieldcids.=$comma;
			$fieldcids.=wid2cid($mywid['wid']);
			$comma=",";
		}
		if(sql_check("select uid from sys_troops where targetcid in ($fieldcids) and state=4 and uid <>'$uid' and uid>0")) throw new Exception($GLOBALS['discardCity']['has_union_army']);
	}
	$npcuid = 895;
	$governmentLevel = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=6");

	$heros=sql_fetch_rows("select hid from sys_city_hero where cid='$cid' and uid='$uid'");
	if(!empty($heros))
	{
		$heroids="";
		$comma="";
		foreach($heros as $hero)
		{
			$heroids=$heroids.$comma.$hero['hid'];
			$comma=",";
		}
		sql_query("delete from sys_hero_armor where hid in ($heroids)");
		sql_query("update sys_user_armor set hid=0 where uid='$uid' and hid in ($heroids)");
		sql_query("delete from mem_hero_blood where hid in ($heroids)");
		sql_query("update sys_city_hero set uid='$npcuid' where cid='$cid' and uid='$uid'");
	}

	sql_query("update sys_troops set uid='$npcuid' where cid='$cid' and uid='$uid'");
	sql_query("update mem_city_resource set wood=0,rock=0,iron=0,food=0,gold=0,people=0,tax=10,complaint=0,morale=90 where cid='$cid'");
	
	if($cityInfo['type']>0)
	{
		sql_query("update sys_union_city set `count`=GREATEST(0,`count`-1) where unionid=(select union_id from sys_user where uid='$uid')");
	}
	sql_query("update sys_city set uid='$npcuid',`discardtime`=unix_timestamp()+$governmentLevel*86400 where cid='$cid'");
	sql_query("update sys_technic set state=0 where cid='$cid'");
	sql_query("delete from mem_technic_upgrading where cid='$cid'");
	sql_query("update sys_building set state=0 where cid='$cid'");
	sql_query("delete from mem_building_upgrading where cid='$cid'");
	sql_query("delete from mem_building_destroying where cid='$cid'");
	sql_query("delete from mem_city_draft where cid='$cid'");
	sql_query("delete from sys_city_draftqueue where cid='$cid'");
	sql_query("delete from mem_city_reinforce where cid='$cid'");
	sql_query("delete from sys_city_reinforcequeue where cid='$cid'");
	sql_query("delete from sys_city_soldier where cid='$cid'");
	sql_query("delete from sys_city_trade where cid='$cid' and state=0 and buycid=0");
	sql_query("insert into mem_city_schedule (cid,last_change_army) values ('$cid',unix_timestamp()) on duplicate key update last_change_army=unix_timestamp()");


	$wid=cid2wid($cid);
	sql_query("delete from sys_gather where wid='$wid'");


	$targetOfficePos=sql_fetch_one_cell("select officepos from sys_user where uid='$userid'") ;
	$targetCityType=$cityInfo['type'];
	if($targetCityType>0)
	{
		$oriTargetOfficePos=$targetOfficePos;
		if ($targetCityType==4)	//都城
		{
			if ($targetOfficePos==13)	//丞相
			{
				$targetOfficePos=12;
				//官复丞相
				sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',278,0) on duplicate key update state=0");
			}
		}
		if ($targetCityType>=3)	//州城
		{
			if ($targetOfficePos==12)	//州牧
			{
				$citycount=sql_fetch_one_cell("select count(*) from sys_city where type=3 and uid='$userid'");
				if ($citycount==0)
				{
					$targetOfficePos=11;
					//官复州牧
					sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',277,0) on duplicate key update state=0");
				}
			}
		}
		if (targetCityType>=2)	//郡城
		{
			if (targetOfficePos>=9&&targetOfficePos<12)
			{
				$citycount=sql_fetch_one_cell("select count(*) from sys_city where type=2 and uid='$userid'");
				if (citycount==0)
				{
					if ($targetOfficePos>=9)
					{
						//官复太守
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',274,0) on duplicate key update state=0");
					}
					if ($targetOfficePos>=10)
					{
						//官复别驾
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',275,0) on duplicate key update state=0");
					}
					if ($targetOfficePos>=11)
					{
						//官复刺史
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',276,0) on duplicate key update state=0");
					}
					$targetOfficePos=8;
				}
			}
		}
		if($targetCityType>=1)
		{
			if($targetOfficePos>=6&&$targetOfficePos<9)	//县长、县令、都邮三级，需要判断是否降级
			{
				$citycount=sql_fetch_one_cell("select count(*) from sys_city where type=1 and uid='$userid'");
				if ($citycount==0)
				{
					if ($targetOfficePos>=6)
					{
						//官复县长
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',271,0) on duplicate key update state=0");
					}
					if ($targetOfficePos>=7)
					{
						//官复县令
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',272,0) on duplicate key update state=0");
					}
					if ($targetOfficePos>=8)
					{
						//官复都邮
						sql_query("insert into sys_user_task (uid,tid,state) values ('$userid',273,0) on duplicate key update state=0");
					}
					$targetOfficePos=5;
				}
			}
		}
		if ($oriTargetOfficePos!=$targetOfficePos)
		{
			sql_query("update sys_user set officepos='$targetOfficePos' where uid='$userid'");
		}
	}


	$msg = sprintf($GLOBALS['discardCity']['giveup_city'],getCityNamePosition($cid));
	$report = $msg;

	sendReport($uid,"city",25,$cid,$cid,$report);
	$param2=array();
	return getCityList($uid,$param2);
}

//开始所有采集
function gatherStartAll($uid,$param){
	//等级>0，状态为“和平”且有将领驻军的，进行采集。
	$troops = sql_fetch_rows("select id,fooduse,targetcid from sys_troops where  state=4  and uid='$uid' and hid>0 ");
	$ret =array();
	$hasGather=false;
	foreach($troops as $troop){
		$wid=cid2wid($troop["targetcid"]);
		$worldInfo = sql_fetch_one("select state,type,level from mem_world where wid='$wid' and type>1 and state=0 and level>0  ");
		if(!empty($worldInfo)){
			$param2 = array();
			$param2[]=$wid;

			if (!sql_check("select * from sys_gather where wid='$wid' and troopid='$troop[id]'")){
				$hasGather=true;
				sql_query("replace into sys_gather (wid,troopid,fooduse,level,starttime) values ('$wid','$troop[id]','$troop[fooduse]','$worldInfo[level]',unix_timestamp())");
			}
		}
	}
	$ret[]=$hasGather;
	return $ret;
}

//收获所有野地
function gatherEndAll($uid,$param){	
	$gathers = sql_fetch_rows("select g.wid,g.fooduse,g.level,g.starttime,t.hid,m.type,m.ownercid from sys_gather g left join sys_troops t on g.troopid=t.id left join mem_world m on g.wid=m.wid where  t.state=4 and t.uid='$uid' ");
	$ret=array();
	$now = sql_fetch_one_cell("select unix_timestamp()");
	$msg="";
	$shouHuo=false;
	$number=1;
	mt_srand(time());
	foreach($gathers as $gather){
		$shouHuo=true;
		$fieldName="";
		if($gather['type']==0){
			$fieldName=$GLOBALS['fileName']['0'];
		}
		if($gather['type']==1){
			$fieldName=$GLOBALS['fileName']['1'];
		}
		if($gather['type']==2){
			$fieldName=$GLOBALS['fileName']['2'];
		}
		if($gather['type']==3){
			$fieldName=$GLOBALS['fileName']['3'];
		}
		if($gather['type']==4){
			$fieldName=$GLOBALS['fileName']['4'];
		}
		if($gather['type']==5){
			$fieldName=$GLOBALS['fileName']['5'];
		}
		if($gather['type']==6){
			$fieldName=$GLOBALS['fileName']['6'];
		}
		if($gather['type']==7){
			$fieldName=$GLOBALS['fileName']['7'];
		}
		$delta = $now - $gather['starttime'];
		if ($delta < 3600){
			$msg.=$fieldName.getPosition(wid2cid($gather["wid"]))." ".$GLOBALS['gatherFieldEnd']['gather_time_lessThen_1']."<br/>";
		}else{
			$heroLevel = sql_fetch_one_cell("select level from sys_city_hero where hid='$gather[hid]'");
			$msg .= $fieldName.getPosition(wid2cid($gather["wid"]))." ".sprintf($GLOBALS['gatherFieldEnd']['through_gathering'],MakeTimeLeft($delta));
			$msg .= gatherFieldResultForEndAll($uid,$gather["ownercid"],$delta,$gather['fooduse'],$gather['type'],$gather['level'],$heroLevel,$gather['hid'],$gather['wid'],$number)."<br/>";
		}
		sql_query("delete from sys_gather where wid='$gather[wid]'");
		$number++;
	}
	if($shouHuo==false)
	$msg=$GLOBALS['gatherFieldEnd']['no_people_gather'];
	
	sendReport($uid,"gather",33,0,0,$msg);
	$ret[] = $GLOBALS['gather']['end_all'];
	return $ret;
}

function allowAutoDiscard($uid,$param){
	$allow=intval(array_shift($param));
	sql_query("insert into sys_allow_auto_discard (uid,`allow`) values ('$uid','$allow') on duplicate key update `allow`='$allow'");
	$ret=array();
	$ret[]=getAllowAutoDiscard($uid);
	return $ret;
}

function getAllowAutoDiscard($uid){
	$allow=sql_fetch_one_cell("select * from sys_allow_auto_discard where uid='$uid'");
	if(empty($allow)){
		sql_query("insert into sys_allow_auto_discard(allow,uid) values(1,'$uid') ");
		return 1;
	}
	return $allow['allow'];
}

function getTodayGatherCount($uid,$gid){
	$row = sql_fetch_one("select *,unix_timestamp() as now from log_gather_count where uid='$uid' and gid='$gid'");
	if(empty($row)){
		sql_query("insert into log_gather_count (gid,uid,count) values ('$gid','$uid',0)");
		return 0;
	}
	$lastTime = $row['time'];
	$now = $row['now'];
	if($now-$lastTime>=86400){
		sql_query("update log_gather_count set time=unix_timestamp(),count=0 where id='$row[id]'");
		return 0;
	}
	return $row['count'];
}


?>
