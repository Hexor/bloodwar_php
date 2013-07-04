<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./TechnicFunc.php");
require_once("./SoldierFunc.php");
require_once("./HotelFunc.php");
require_once("./OfficeFunc.php");
require_once("./MarketFunc.php");
require_once("./UnionFunc.php");
require_once("./StoreFunc.php");
require_once("./TroopFunc.php");

//得到建筑升级缩短时间
function getBuildingSpeedRate($cid)
{
    $speed_add = 0;
    //建筑技术(17)：每升1级，加快建筑、城防工事的建造速度10%。所有建筑和城防。
    $buildingTechLevel = sql_fetch_one_cell("select level from sys_city_technic where cid=".$cid." and tid=17");
    if (!empty($buildingTechLevel))   //检查本城是否有有效的制造技术
    {                                                        
        $speed_add += $buildingTechLevel * 10;
    }
    
    
    //城守内政：加快城池建筑、城防工事建造速度1%。检查顺序：城守，军师，主将。
    
    $cityhids = sql_fetch_one("select chiefhid,generalid,counsellorid from sys_city where cid='$cid'");
    $chiefhid=0;
    if(!empty($cityhids)){
    	if($cityhids['chiefhid']>0)
    		$chiefhid=$cityhids['chiefhid'];
    	else if($cityhids['counsellorid']>0)
    		$chiefhid=$cityhids['counsellorid'];
    	else if($cityhids['generalid']>0)
    		$chiefhid=$cityhids['generalid'];
    }
  
    
    $chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
    
    if ($chiefhid > 0)
    {
        $chief = sql_fetch_one("select * from sys_city_hero where hid='$chiefhid'");
        if (!empty($chief))
        {
            $bufadd = 1.0;
            if (isHeroHasBuffer($chiefhid,2)) $bufadd = 1.25;   //文曲星符
            $speed_add += ($chief['affairs_base'] + $chief['affairs_add'])*$bufadd+ $chief['affairs_add_on'];;
        }
    } 
    
    return (1.0 / (1.0 + 0.01 * $speed_add));
}

function isUsingKaoGongJi($uid,$cid,$dstlevel)
{
	$haskaogongji=false;

	if($dstlevel<=5)
	{
		if(sql_check("select endtime from mem_user_buffer where uid='$uid' and (buftype=12 or buftype=13 or buftype=14) and endtime>unix_timestamp()"))
		{
			$haskaogongji=true;
		}
	}
	else if($dstlevel<=8)
	{
		if(sql_check("select endtime from mem_user_buffer where uid='$uid' and (buftype=13 or buftype=14) and endtime>unix_timestamp()"))
		{
			$haskaogongji=true;
		}
	}
	else
	{
		if(sql_check("select endtime from mem_user_buffer where uid='$uid' and buftype=14 and endtime>unix_timestamp()"))
		{
			$haskaogongji=true;
		}
	}
	return $haskaogongji;
}

function getBuildingMaxLevel($citytype,$buildingid)
{
	$maxlevel=10;
	if($buildingid<5)
	{
		if($citytype==1) $maxlevel=12;
		else if($citytype==2) $maxlevel=15;
		else if ($citytype==3) $maxlevel=18;
		else if ($citytype==4) $maxlevel=20;
	}
	return $maxlevel;
}

function doGetSimpleBuildingInfo($uid,$cid,$building,$dstlevel,$haskaogongji,$citytype)
{

	$info = new BuildingInfo();

	$info->bid = (int)$building['bid'];
	$info->name = $building['name'];
	$info->description = $building['description'];
	$info->level = $dstlevel;
	$info->canUpgrade = true;
	
	$buildingid=$building['bid'];
	
	$maxlevel=getBuildingMaxLevel($citytype,$buildingid);
	
	$rate=1;
	if($haskaogongji) $rate=0.7;
	if($dstlevel<=$maxlevel)
	{
		$resNeed = sql_fetch_one("select * from cfg_building_level where `bid`='$buildingid' and `level`='$dstlevel'");
	}
	if (!empty($resNeed))
	{
        $speedRate = getBuildingSpeedRate($cid);
		$info->woodNeed = (int)$resNeed['upgrade_wood']*$rate;
		$info->rockNeed = (int)$resNeed['upgrade_rock']*$rate;
		$info->ironNeed = (int)$resNeed['upgrade_iron']*$rate;
		$info->foodNeed = (int)$resNeed['upgrade_food']*$rate;
		$info->goldNeed = (int)$resNeed['upgrade_gold']*$rate;
		$info->peopleNeed = (int)$resNeed['upgrade_people'];
		$info->upgradeTime = ceil($resNeed['upgrade_time'] * $speedRate);
		$info->levelDescription = $resNeed['description'];
	}
	else if ($dstlevel > 0)
	{
		$info->canUpgrade = false;
	}
	//判断资源够不够
	$cityresource = sql_fetch_one("select * from mem_city_resource where `cid`='$cid'");
	if (!empty($cityresource))
	{
		if (($cityresource['wood'] < $info->woodNeed)||
			($cityresource['rock'] < $info->rockNeed)||
			($cityresource['iron'] < $info->ironNeed)||
			($cityresource['food'] < $info->foodNeed)||
			($cityresource['gold'] < $info->goldNeed)||
			($cityresource['people'] < $info->peopleNeed))
		{
			$info->canUpgrade = false;
		}
	}
	else
	{
		throw new Exception($GLOBALS['doGetSimpleBuildingInfo']['no_resource']);
	}
	$info->conditions = array();
	//其它条件
	$conditions = sql_fetch_rows("select * from cfg_building_condition where bid='$buildingid' and `levelid`='$dstlevel' order by `pre_type`");	//先建筑后科技
	foreach($conditions as $condition)
	{
		$cond = new UpgradeCondition();
		if ($condition['pre_type'] == 0)	//building
		{
			$cond->type = $GLOBALS['doGetSimpleBuildingInfo']['pre_building'];
			$pre_building_id = $condition['pre_id'];
			$curr_building_level = sql_fetch_one_cell("select max(`level`) from sys_building where cid='$cid' and `bid`='$pre_building_id'");
			$cond->canUpgrade = true;
			if (empty($curr_building_level) || $curr_building_level < $condition['pre_level'])
			{
				$cond->canUpgrade = false;
				$info->canUpgrade = false;
			}
			$buildingName = sql_fetch_one_cell("select `name` from cfg_building where bid='$pre_building_id'");
			$cond->upgradeNeed = $buildingName . "(".$GLOBALS['doGetSimpleBuildingInfo']['level'] . $condition['pre_level'] . ")";
			$cond->currentOwn = $GLOBALS['doGetSimpleBuildingInfo']['level'] . (empty($curr_building_level)?0:$curr_building_level);
		}
		else if ($condition['pre_type'] == 1) //technic
		{
			$cond->type = $GLOBALS['doGetSimpleBuildingInfo']['pre_technic'];
			$pre_technic_id = $condition['pre_id'];
			$curr_technic_level = sql_fetch_one_cell("select max(`level`) from sys_technic where uid='$uid' and `tid`='$pre_technic_id'");
			$cond->canUpgrade = true;
			if (empty($curr_technic_level) || $curr_technic_level < $condition['pre_level'])
			{
				$cond->canUpgrade = false;
				$info->canUpgrade = false;
			}
			$technicName = sql_fetch_one_cell("select `name` from cfg_technic where tid='$pre_technic_id'");
			$cond->upgradeNeed = $technicName . "(".$GLOBALS['doGetSimpleBuildingInfo']['level'] . $condition['pre_level'] . ")";
			$cond->currentOwn = $GLOBALS['doGetSimpleBuildingInfo']['level'] . (empty($curr_technic_level)?0:$curr_technic_level);
		}
        else if ($condition['pre_type'] == 2)   //things
        {
            $cond->type = $GLOBALS['doGetSimpleBuildingInfo']['pre_thing'];
            $pre_gid = $condition['pre_id'];
            $curr_goods_count = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$pre_gid'");
            $cond->canUpgrade = true;
            if (empty($curr_goods_count)||$curr_goods_count < $condition['pre_level'])
            {
                $cond->canUpgrade = false;
                $info->canUpgrade = false;
            }
            $goodsName = sql_fetch_one_cell("select name from cfg_goods where gid='$pre_gid'");
            $cond->upgradeNeed = $goodsName."(".$GLOBALS['doGetSimpleBuildingInfo']['count'].$condition['pre_level'].")";
            $cond->currentOwn = $GLOBALS['doGetSimpleBuildingInfo']['count'] .(empty($curr_goods_count)?0:$curr_goods_count);
        }
		$info->conditions[] = $cond;
	}
	return $info;
}

function doGetBuildingInfo($uid,$cid,$xy,$bid,$level)
{
	$dstlevel = $level;
	$building = sql_fetch_one("select * from cfg_building where `bid`='$bid'");
	$haskaogonji=isUsingKaoGongJi($uid,$cid,$dstlevel+1);
	$citytype=sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	$ret = array();
	$ret[] = doGetSimpleBuildingInfo($uid,$cid,$building,$dstlevel,false,$citytype);
	$ret[] = doGetSimpleBuildingInfo($uid,$cid,$building,$dstlevel+1,$haskaogonji,$citytype);
	if ($bid == ID_BUILDING_COLLEGE)	//学院，附加科技信息
	{
		$ret[] = doGetTechnicInfo($uid,$cid);
	}
	else if ($bid == ID_BUILDING_ARMY)
	{
		$ret[] = doGetSoldierInfo($uid,$cid,$xy);
		$ret[] = doGetDraftQueue($uid,$cid,$xy);
	}
	else if ($bid == ID_BUILDING_HOTEL)
	{
		$ret[] = doGetRecruitHero($uid,$cid,$dstlevel);
        $ret[] = doGetOfficeValidPosition($uid,$cid);
        $nobility=sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
        $nobility = getBufferNobility($uid,$nobility);
        if ($nobility<5){
            $ret[] = false; 
        }
        else{
        	$ret[]=true;
        }
	}
    else if ($bid == ID_BUILDING_OFFICE)
    {
        $ret[] = doGetCityHero($uid,$cid);
    }
	else if ($bid == ID_BUILDING_WALL)
	{
		$ret[] = doGetDefenceInfo($uid,$cid);
		$ret[] = doGetReinforceQueue($uid,$cid);
		$ret[] = getCityArea($cid);
		$ret[] = getCityAreaOccupied($cid);
	}
	else if ($bid == ID_BUILDING_MARKET)
	{
		$ret[] = doGetCityTrade($uid,$cid);
	}
    else if ($bid == ID_BUILDING_HONGLU)
    {
        $ret[] = doGetUnionTroops($uid,$cid);
        $ret[] = getAllowUnionTroop($uid,$cid);
        $ret[] = getAllowAntiPlunder($uid,$cid);
        $ret[] = getAllowAntiInvade($uid,$cid);
    }
	else if ($bid == ID_BUILDING_GROUND)
	{                                        
	}
	else if ($bid==ID_BUILDING_STORE)
	{
		$ret[]=doGetStoreInfo($uid,$cid);
	}
	return $ret;
}

function getAllValidBuilding($uid,$cid,$param)
{
	if (count($param) == 0) throw new Exception("no param indicate whether get inner city building list.");
	$inner = $param[0];
	if ($inner == 2)	//城墙
	{
		$buildings = sql_fetch_rows("select * from cfg_building where `bid`=20");
	}
	else if ($inner == 1)    //其它城内建筑
	{
		$buildings = sql_fetch_rows("select * from cfg_building b where b.`inner`=1 and b.bid <> 20 and (b.bid=5 or b.bid=9 or b.bid=17 or (select count(*) from sys_building s where s.cid=$cid and s.bid = b.bid) = 0) order by b.bid");
	}
	else
	{
		$buildings = sql_fetch_rows("select * from cfg_building where `inner`=0 order by bid");
	}
	$haskaogonji=isUsingKaoGongJi($uid,$cid,1);
	$citytype=sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	$ret = array();
	foreach ($buildings as $building)
	{
		$ret[] = doGetSimpleBuildingInfo($uid,$cid,$building,1,$haskaogonji,$citytype);
	}
	return $ret;
}
function getBuildingInfo($uid,$cid,$param)
{
	$inner = array_shift($param);
	$x 	   = array_shift($param);
	$y	   = array_shift($param);
	$bid   = array_shift($param);

	$xy = encodeBuildingPosition($inner,$x,$y);
	$cur_building = sql_fetch_one("select * from sys_building where `cid`='$cid' and `xy`='$xy' and `bid`='$bid'");
	if (empty($cur_building)) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);

	return doGetBuildingInfo($uid,$cid,$xy,$bid,$cur_building['level']);
}

//开始建造建筑
function startUpgradeBuilding($uid,$param)
{                             
    
    $VALID_GRID_ARRAY = array(
        0=>array(0,1,0,0,0,0,1,0,0,0,0,1,1,1,1,1,1,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        1=>array(0,1,0,0,0,0,1,1,0,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        2=>array(0,1,0,0,0,0,1,1,0,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        3=>array(0,1,0,0,0,0,1,1,0,0,0,1,1,1,1,1,1,1,1,0,0,0,1,1,1,1,1,1,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        4=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        5=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        6=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,1,0,0,0,1,1,1,1,1,1,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0),
        7=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,1,0,0,0,1,1,1,1,1,1,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0),
        8=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,0,1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0),
        9=>array(0,1,0,0,0,0,1,1,0,0,1,1,1,1,1,1,1,1,1,0,1,1,1,1,1,1,1,1,1,0,0,1,1,1,1,1,1,1,0,0,0,0,1,1,1,1,1,0,0,0,0,0,0,1,1,1,1,0,0,0,0,0,0,0,1,1,0,0,0,0)
        );
        
    $cid   = array_shift($param);
	$inner = array_shift($param)?1:0;
	$x	   = array_shift($param);
	$y 	   = array_shift($param);
	$bid   = array_shift($param);

	checkCityExist($cid,$uid);
                                       
	if (!sql_check("select * from cfg_building where `bid`='$bid' and `inner`='$inner'")) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);
                                               
	$xy = encodeBuildingPosition($inner,$x,$y);
	$dstbid = $bid;
	$dstlevel = 1;

	//检查是否已经有某建筑在建了
	$existbuilding = sql_fetch_one("select * from sys_building where `cid`='$cid' and `xy`='$xy' and `bid`='$bid'");  //already has a building
	if (!empty($existbuilding))
	{
		if ($existbuilding['state'] != 0) throw new Exception($GLOBALS['getBuildingInfo']['upgrading']);	//如果不是在正常状态下,不能够建造
		$dstbid = $existbuilding['bid'];
		$dstlevel = $existbuilding['level'] + 1;
	}
    else
    {
        if (sql_check("select * from sys_building where `cid`='$cid' and `xy`='$xy'"))  //不是该建筑，存在其它建筑
        {
            throw new Exception($GLOBALS['getBuildingInfo']['building_error']);
        }
    }
    
	//如果是刚开始建造的话，则看一下这个建筑是不是唯一的
	if ($dstlevel == 1)
	{
		if (sql_check("select * from sys_building where `bid` not in (1,2,3,4,5,9,17) and `bid`='$bid' and `cid`='$cid'")) throw new Exception($GLOBALS['getBuildingInfo']['same_building_has_build']);
	}
	else
	{
		$citytype=sql_fetch_one_cell("select type from sys_city where cid='$cid'");
		$maxlevel=getBuildingMaxLevel($citytype,$bid);
		if($dstlevel>$maxlevel)
		{
			throw new Exception($GLOBALS['getBuildingInfo']['building_error']);
		}
	}

    
	$upgrade_need = sql_fetch_one("select * from cfg_building_level where `bid`='$dstbid' and `level`='$dstlevel'");
	if (!empty($upgrade_need) && !empty($upgrade_need['upgrade_time']))
	{
		$haskaogongji=isUsingKaoGongJi($uid,$cid,$dstlevel);
		if($haskaogongji)
		{
			$upgrade_need['upgrade_wood']=floor($upgrade_need['upgrade_wood']*0.7);
			$upgrade_need['upgrade_rock']=floor($upgrade_need['upgrade_rock']*0.7);
			$upgrade_need['upgrade_iron']=floor($upgrade_need['upgrade_iron']*0.7);
			$upgrade_need['upgrade_food']=floor($upgrade_need['upgrade_food']*0.7);
		}
		if (!checkCityResource($cid,$upgrade_need['upgrade_wood'],$upgrade_need['upgrade_rock'],$upgrade_need['upgrade_iron'],
			$upgrade_need['upgrade_food'],$upgrade_need['upgrade_gold']))
		{
			throw new Exception($GLOBALS['getBuildingInfo']['resource_not_enough']);
		}
        $cityPeople = sql_fetch_one_cell("select people from mem_city_resource where cid='$cid'");
        if ($cityPeople < $upgrade_need['upgrade_people'])
        {              
            throw new Exception($GLOBALS['getBuildingInfo']['people_not_enough']);
        }
        
        //如果是城外建筑的话，看看当前官府等级够不够
        $govermentLevel = sql_fetch_one_cell("select level from sys_building where cid=$cid and bid=".ID_BUILDING_GOVERMENT);
        if ($inner == 0)
        {                                                                      
            if ($VALID_GRID_ARRAY[$govermentLevel-1][$y*10+$x] == 0) throw new Exception($GLOBALS['getBuildingInfo']['government_not_enough']);
        }
         //同时建造的建筑数量不能超过官府等级
         //同时建造的建筑数量不能超过当前上限
        $upgradingCount = sql_fetch_one_cell("select count(*) from sys_building where cid='$cid' and state > 0");
        $limitCount=2;
   		$endtime=sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=11 and endtime>unix_timestamp()");
        if(!empty($endtime))
        {
        	$limitCount=5;
        }
        if($upgradingCount>=$limitCount)
        {
        	if(!empty($endtime)) throw new Exception($GLOBALS['getBuildingInfo']['upgrading_queue_full']);
        	else throw new Exception($GLOBALS['getBuildingInfo']['upgrading_queue_full2']);
        }
        
		//其它前提条件是否满足
		$conditions = sql_fetch_rows("select * from cfg_building_condition where bid='$dstbid' and `levelid`='$dstlevel' order by `pre_type`");	//先建筑后科技
		foreach($conditions as $condition)
		{
			if ($condition['pre_type'] == 0)	//building
			{
				$pre_building_id = $condition['pre_id'];
				$curr_building_level = sql_fetch_one_cell("select max(`level`) from sys_building where cid='$cid' and `bid`='$pre_building_id'");
				if (empty($curr_building_level) || $curr_building_level < $condition['pre_level'])
				{
					throw new Exception($GLOBALS['getBuildingInfo']['no_pre_building']);
				}
			}
			else if ($condition['pre_type'] == 1) //technic
			{
				$pre_technic_id = $condition['pre_id'];
				$curr_technic_level = sql_fetch_one_cell("select max(`level`) from sys_technic where uid='$uid' and `tid`='$pre_technic_id'");
				if (empty($curr_technic_level) || $curr_technic_level < $condition['pre_level'])
				{
					throw new Exception($GLOBALS['getBuildingInfo']['no_pre_technic']);
				}
			}
            else if ($condition['pre_type'] == 2)
            {
                $pre_goods_id = $condition['pre_id'];
                $curr_goods_count = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$pre_goods_id'");
                if (empty($curr_goods_count)||($curr_goods_count < $condition['pre_level']))
                {
                    throw new Exception($GLOBALS['getBuildingInfo']['no_pre_thing']);
                }
                addGoods($uid,$pre_goods_id,-1,0);
            }
		}                 
		
        $real_time_need = $upgrade_need['upgrade_time'];  //真实需要的时间  
        $real_time_need *= getBuildingSpeedRate($cid);
        $real_time_need /= GAME_SPEED_RATE;
    	$real_time_need = floor($real_time_need);
        

		$state_starttime = sql_fetch_one_cell("select unix_timestamp()");
		//扣钱开建
		addCityResources($cid,  -$upgrade_need['upgrade_wood'],
								-$upgrade_need['upgrade_rock'],
								-$upgrade_need['upgrade_iron'],
								-$upgrade_need['upgrade_food'],
								-$upgrade_need['upgrade_gold']);

		$lastid = 0;

		if ($existbuilding)
		{
			sql_query("update sys_building set `bid`='$bid',`state`='1',`state_starttime`=unix_timestamp(),
				`state_endtime`=unix_timestamp()+'$real_time_need'
				where `cid`='$cid' and `xy`='$xy'");
			$lastid = $existbuilding['id'];
		}
		else
		{
			sql_query("insert into sys_building (cid,xy,bid,level,`state`,state_starttime,state_endtime) values ('$cid','$xy','$bid',0,1,unix_timestamp(),unix_timestamp()+'$real_time_need')");
			$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");
		}
		sql_query("insert into mem_building_upgrading (id,cid,xy,bid,level,state_endtime) values ('$lastid','$cid','$xy','$bid','$dstlevel',unix_timestamp()+'$real_time_need')
		on duplicate key update `state_endtime`=unix_timestamp()+'$real_time_need'");
	}
	return getCityBuildingInfo($uid,$cid);
}
//停止正在建造的建筑
function stopUpgradeBuilding($uid,$param)
{
    $cid   = array_shift($param);
	$inner = array_shift($param);
	$x	   = array_shift($param);
	$y 	   = array_shift($param);
	$bid   = array_shift($param);

	checkCityExist($cid,$uid);
	if (!sql_check("select * from cfg_building where `bid`='$bid' and `inner`='$inner'")) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);

	$xy = encodeBuildingPosition($inner,$x,$y);

	$building = sql_fetch_one("select * from sys_building  where `cid`='$cid' and `xy`='$xy' and `state`=1");

	if (empty($building)) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);

	$dstbid = $building['bid'];
	$dstlevel = $building['level'] + 1;
	$upgrade_need = sql_fetch_one("select * from cfg_building_level where `bid`='$dstbid' and `level`='$dstlevel'");
	if (!empty($upgrade_need))
	{
		//停止建造
		if ($building['level'] == 0)
		{
			sql_query("delete from sys_building where `cid`='$cid' and `xy`='$xy'");
		}
		else
		{
			sql_query("update sys_building set `state`='0' where `cid`='$cid' and `xy`='$xy'");
		}
		//删除内存表中相应数据
		sql_query("delete from mem_building_upgrading where `id`='$building[id]'");
		//返还所有资源
		addCityResources($cid,$upgrade_need['upgrade_wood']*0.66
        ,$upgrade_need['upgrade_rock']*0.66
        ,$upgrade_need['upgrade_iron']*0.66
        ,$upgrade_need['upgrade_food']*0.66
        ,$upgrade_need['upgrade_gold']*0.66);
	}
	return getCityBuildingInfo($uid,$cid);
}

function startDestroyBuildingAll($uid,$param)
{
	$cid   = array_shift($param);
	$inner = array_shift($param);
	$x	   = array_shift($param);
	$y 	   = array_shift($param);
	$bid   = array_shift($param);
	
	checkCityExist($cid,$uid);
	
	if($bid==ID_BUILDING_GOVERMENT)
	{
        throw new Exception($GLOBALS['getBuildingInfo']['govenment_all_destroy']);
	}

	$xy = encodeBuildingPosition($inner,$x,$y);
	$building = sql_fetch_one("select * from sys_building  where `cid`='$cid' and `xy`='$xy' and `bid`='$bid'");

	if (empty($building)) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);
    if ($building['state'] == 1) throw new Exception($GLOBALS['getBuildingInfo']['upgrading']);
    if ($building['state'] == 2) throw new Exception($GLOBALS['getBuildingInfo']['destroying']);
    
    useFireBarrel($uid,$building,$cid,$xy);
    //sleep(1);
	return getCityBuildingInfo($uid,$cid);
}

//开始拆除一个建筑
function startDestroyBuilding($uid,$param)
{
    $cid   = array_shift($param);
	$inner = array_shift($param);
	$x	   = array_shift($param);
	$y 	   = array_shift($param);
	$bid   = array_shift($param);

	checkCityExist($cid,$uid);
	
	$xy = encodeBuildingPosition($inner,$x,$y);
	$building = sql_fetch_one("select * from sys_building  where `cid`='$cid' and `xy`='$xy' and `bid`='$bid'");

	if (empty($building)) throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);
    if ($building['state'] == 1) throw new Exception($GLOBALS['getBuildingInfo']['upgrading']);
    if ($building['state'] == 2) throw new Exception($GLOBALS['getBuildingInfo']['destroying']);
	$upgradingCount = sql_fetch_one_cell("select count(*) from sys_building where cid='$cid' and state > 0");
	$limitCount=2;
	$endtime=sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=11 and endtime>unix_timestamp()");
    if(!empty($endtime))
    {
    	$limitCount=5;
    }
    if($upgradingCount>=$limitCount)
    {
    	if(!empty($endtime)) throw new Exception($GLOBALS['getBuildingInfo']['upgrading_queue_full']);
    	else throw new Exception($GLOBALS['getBuildingInfo']['upgrading_queue_full2']);
    }
    
    
	$dstbid = $building['bid'];
	$dstlevel = $building['level'];
    
    if (($dstbid == ID_BUILDING_GOVERMENT)&&($dstlevel == 1))
    {
        throw new Exception($GLOBALS['getBuildingInfo']['govenment_1_destroy']);
    }
    
	$state_starttime = sql_fetch_one_cell("select unix_timestamp()");
	$upgrade_need = sql_fetch_one("select * from cfg_building_level where `bid`='$dstbid' and `level`='$dstlevel'");

    $real_time_need = $upgrade_need['upgrade_time'] * 0.5;  //真实需要的时间 
    
    $real_time_need *= getBuildingSpeedRate($cid);
    $real_time_need /= GAME_SPEED_RATE;
    $real_time_need = floor($real_time_need);
	if (!empty($upgrade_need))
	{
		sql_query("update sys_building set `state`='2',`state_starttime`=unix_timestamp(),
				`state_endtime`=unix_timestamp()+'$real_time_need'
				where `cid`='$cid' and `xy`='$xy'");
		$dstlevel -= 1;	//将降级后的级别填入，结束时直接用这个级别计算
		sql_query("insert into mem_building_destroying (id,cid,xy,bid,level,state_endtime) values ('$building[id]','$cid','$xy','$bid','$dstlevel',unix_timestamp()+'$real_time_need')
			on duplicate key update `state_endtime`=unix_timestamp()+'$real_time_need'");
		return getCityBuildingInfo($uid,$cid);
	}
	else
	{
		throw new Exception($GLOBALS['getBuildingInfo']['nobuilding']);
	}

}
//停止正在拆除的建筑
function stopDestroyBuilding($uid,$param)
{
    $cid   = array_shift($param);
	$inner = array_shift($param);
	$x	   = array_shift($param);
	$y 	   = array_shift($param);
	$bid   = array_shift($param);
	$xy = encodeBuildingPosition($inner,$x,$y);

	//删除内存表中相应数据
	sql_query("delete from mem_building_destroying where `id`=(select id from sys_building where `cid`='$cid' and `xy`='$xy' and `state`=2)");

	sql_query("update sys_building set `state`='0' where `cid`='$cid' and `xy`='$xy' and `state`=2");
	return getCityBuildingInfo($uid,$cid);
}

?>