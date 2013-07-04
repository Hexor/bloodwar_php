<?php                      
require_once("./interface.php");
require_once("./utils.php");

function getTechnicSpeedRate($cid)
{
    $speed_add = 0;                                                                                
        
    //智谋：1点增加军队防御1%。加快城池研究速度1%。 检查顺序：军师，城守，主将
	$cityhids = sql_fetch_one("select chiefhid,generalid,counsellorid from sys_city where cid='$cid'");
    $chiefhid=0;
    if(!empty($cityhids)){
    	if($cityhids['counsellorid']>0)
    		$chiefhid=$cityhids['counsellorid'];    	
    	else if($cityhids['generalid']>0)
    		$chiefhid=$cityhids['generalid'];
    	else if($cityhids['chiefhid']>0)
    		$chiefhid=$cityhids['chiefhid'];
    	
    }
    if ($chiefhid > 0)
    {
        $chief = sql_fetch_one("select * from sys_city_hero where hid='$chiefhid'");
        if (!empty($chief))
        {
            $bufadd = 1.0;
            if (isHeroHasBuffer($chiefhid,4))   //智多星符
            {
                $bufadd = 1.25;
            }
            $speed_add += ($chief['wisdom_base'] + $chief['wisdom_add'])*$bufadd + $chief['wisdom_add_on'];
        }
    } 
        
    return 1.0 / (1.0 + (0.01 * $speed_add));
}


function doGetTechnicInfo($uid,$cid)
{	
	$technics = sql_fetch_rows("select c.*,s.`uid`,s.`cid`,s.`level`,s.`state`,s.`state_starttime`,s.`state_endtime`,t.level as sharelevel,unix_timestamp() as nowtime from cfg_technic c left join sys_technic s on s.tid=c.tid and s.uid='$uid' left join sys_city_technic t on t.cid='$cid' and t.tid=c.tid");

	$ret = array();
    $techlist = array();
    $speedRate = getTechnicSpeedRate($cid); 

    $has_one_upgrading = sql_check("select * from sys_technic where cid=$cid and state=1");
    $temp="";
	foreach($technics as $technic)
	{
		$state = new TechnicState();
		$state->tid = (int)$technic['tid'];
		$state->tname = $technic['name'];
		$state->cid = $technic['cid'];
		$state->description = $technic['description'];
		$state->level = (int)(empty($technic['level'])?0:$technic['level']);
        $state->sharelevel = (int)(empty($technic['sharelevel'])?0:$technic['sharelevel']);
		$state->state = (int)(empty($technic['state'])?0:$technic['state']);                          
		$state->state_endtime = (int)(empty($technic['state_endtime'])?0:$technic['state_endtime']); 
		$state->state_timeleft = $state->state_endtime - $technic['nowtime']; 
		$state->can_upgrade = true;
		
		if ($state->level > 0)
		{
			$state->levelDescription = sql_fetch_one_cell("select description from cfg_technic_level where tid=$technic[tid] and level=".$state->level);
		}
		
		$dstlevel = $state->level + 1;
        if ($dstlevel > 10) $state->can_upgrade = false;
		$need = sql_fetch_one("select * from cfg_technic_level where tid=$technic[tid] and level=$dstlevel");
		if (!empty($need))
		{
			$state->woodNeed = (double)$need['upgrade_wood'];
			$state->rockNeed = (double)$need['upgrade_rock'];
			$state->ironNeed = (double)$need['upgrade_iron'];
			$state->foodNeed = (double)$need['upgrade_food'];
			$state->goldNeed = (double)$need['upgrade_gold'];
			$state->upgrade_time = ceil(($need['upgrade_time'] * $speedRate));
			$state->nextLevelDescription = $need['description'];
			$state->can_upgrade = checkCityResource($cid,$state->woodNeed,$state->rockNeed,$state->ironNeed,$state->foodNeed,$state->goldNeed);

			//判断其它条件是否满足
			$state->conditions = array();
            if ($dstlevel <= 10)
            {
			    $conditions = sql_fetch_rows("select * from cfg_technic_condition where tid='$technic[tid]' and `level`='$dstlevel' order by `pre_type`");	//先建筑后科技
			    foreach($conditions as $condition)
			    {
				    $cond = new UpgradeCondition();
				    if ($condition['pre_type'] == 0)	//building
				    {
					    $cond->type = $GLOBALS['doGetTechnicInfo']['pre_building'];
					    $pre_building_id = $condition['pre_id'];
					    $curr_building_level = sql_fetch_one_cell("select max(`level`) from sys_building where cid='$cid' and `bid`='$pre_building_id'");
					    $cond->canUpgrade = true;
					    if (empty($curr_building_level) || $curr_building_level < $condition['pre_level'])
					    {
						    $cond->canUpgrade = false;
						    $state->can_upgrade = false;
					    }
					    $buildingName = sql_fetch_one_cell("select `name` from cfg_building where bid='$pre_building_id'");
					    $cond->upgradeNeed = $buildingName . "(".$GLOBALS['doGetTechnicInfo']['level'] . $condition['pre_level'] . ")";
					    $cond->currentOwn = $GLOBALS['doGetTechnicInfo']['level'] . (empty($curr_building_level)?0:$curr_building_level);
				    }
				    else if ($condition['pre_type'] == 1) //technic
				    {
					    $cond->type = $GLOBALS['doGetTechnicInfo']['pre_technic'];
					    $pre_technic_id = $condition['pre_id'];
					    $curr_technic_level = sql_fetch_one_cell("select max(`level`) from sys_technic where uid='$uid' and `tid`='$pre_technic_id'");
					    $cond->canUpgrade = true;
					    if (empty($curr_technic_level) || $curr_technic_level < $condition['pre_level'])
					    {
						    $cond->canUpgrade = false;
						    $state->can_upgrade = false;
					    }
					    $technicName = sql_fetch_one_cell("select `name` from cfg_technic where tid='$pre_technic_id'");
					    $cond->upgradeNeed = $technicName . "(".$GLOBALS['doGetTechnicInfo']['level'] . $condition['pre_level'] . ")";
					    $cond->currentOwn = $GLOBALS['doGetTechnicInfo']['level'] . (empty($curr_technic_level)?0:$curr_technic_level);
				    }
				    $state->conditions[] = $cond;
			    }     
            }                             
		}
		
        if ($has_one_upgrading) $state->can_upgrade = false;
       

        if($state->state == 1 && $state->state_timeleft < 0)//如果小于0 则更新状态
        {
        	//$tid = $state->tid;
        	//file_put_contents('c:/testupdate'.time(), $state->tid);
        	//更新已经升级好的科技
        	//sql_query("update sys_technic set `state`=0 , `level`=`level`+1 where `tid`='$tid' and `uid`='$uid'");
        	//删除升级队列
        	//sql_query("delete from mem_technic_upgrading where `cid`='$cid' and `tid`='$tid'");

        	//更新城池可以生效的科技]
        	//$level = $state->level;
        	//updateCityTechnic($uid,$tid,$level);

        }
		$techlist[] = $state;                                       
	}
    $ret[] = $techlist;
    $ret[] = sql_fetch_one_cell("select count(*) from sys_building b,sys_city c where b.cid=c.cid and c.uid='$uid' and b.bid=".ID_BUILDING_COLLEGE);
    //file_put_contents('c:/tech',var_export($ret,true));
	return $ret;
}





function getCollegeInfo($uid,$cid)
{
	//判断是否存在书院
	$college = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_COLLEGE." order by level desc limit 1");
	if (empty($college))
	{   
		throw new Exception($GLOBALS['getCollegeInfo']['no_college_built']); 
	}
	return doGetBuildingInfo($uid,$cid,$college['xy'],ID_BUILDING_COLLEGE,$college['level']);
}

//开始建造科技
function startUpgradeTechnic($uid,$cid,$param)
{
	$tid = array_shift($param);
	
	checkCityExist($cid,$uid);
	//判断是否存在此科技
	if (!sql_check("select * from cfg_technic where `tid`='$tid'")) throw new Exception($GLOBALS['startUpgradeTechnic']['no_technic_info']);			
	
	//如果本城已经有科技在建的话，则不能修建
	if (sql_check("select * from sys_technic where cid='$cid' and `state`=1")) throw new Exception($GLOBALS['startUpgradeTechnic']['only_analysis_1_tech']);
	
	

	$dsttid = $tid;  
	$dstlevel = 1;
	$existTechnic = sql_fetch_one("select * from sys_technic where `uid`='$uid' and `tid`='$tid'");  //already has a technic
	if (!empty($existTechnic))
	{                                       
		$dstlevel = $existTechnic['level'] + 1;
	}
	if($dstlevel>10)
	{
		throw new Exception($GLOBALS['startUpgradeTechnic']['technic_full']);
	}

	$upgrade_need = sql_fetch_one("select * from cfg_technic_level where `tid`='$dsttid' and `level`='$dstlevel'");
	if (!empty($upgrade_need) && !empty($upgrade_need['upgrade_time']))
	{
		if (!checkCityResource($cid,$upgrade_need['upgrade_wood'],$upgrade_need['upgrade_rock'],$upgrade_need['upgrade_iron'],$upgrade_need['upgrade_food'],$upgrade_need['upgrade_gold']))
		{
			throw new Exception($GLOBALS['startUpgradeTechnic']['no_enough_resource']);
		}
		
		//其它前提条件是否满足
		$conditions = sql_fetch_rows("select * from cfg_technic_condition where tid='$dsttid' and `level`='$dstlevel' order by `pre_type`");	//先建筑后科技
		foreach($conditions as $condition)
		{                                     
			if ($condition['pre_type'] == 0)	//building
			{                            
				$pre_building_id = $condition['pre_id'];
				$curr_building_level = sql_fetch_one_cell("select max(`level`) from sys_building where cid='$cid' and `bid`='$pre_building_id'");
				if (empty($curr_building_level) || $curr_building_level < $condition['pre_level'])
				{
					throw new Exception($GLOBALS['startUpgradeTechnic']['no_pre_building']);
				}                                                                                     
			}
			else if ($condition['pre_type'] == 1) //technic
			{                           
				$pre_technic_id = $condition['pre_id'];
				$curr_technic_level = sql_fetch_one_cell("select max(`level`) from sys_technic where uid='$uid' and `tid`='$pre_technic_id'");
				if (empty($curr_technic_level) || $curr_technic_level < $condition['pre_level'])
				{                              
					throw new Exception($GLOBALS['startUpgradeTechnic']['no_pre_technic']);
				}                                                                            
			}                           
		}      
		
		$real_time_need	 = $upgrade_need['upgrade_time'];
        
        $real_time_need *= getTechnicSpeedRate($cid);
        
        $real_time_need /= GAME_SPEED_RATE;
        
        $real_time_need=floor($real_time_need);
		
		//扣钱开建 
		addCityResources($cid,  -$upgrade_need['upgrade_wood'],
								-$upgrade_need['upgrade_rock'],
								-$upgrade_need['upgrade_iron'],
								-$upgrade_need['upgrade_food'],
								-$upgrade_need['upgrade_gold']);
								   
		$lastid = 0;      
		if ($existTechnic)
		{
			sql_query("update sys_technic set `cid`='$cid',`state`='1',`state_starttime`=unix_timestamp(),
				`state_endtime`=unix_timestamp()+'$real_time_need' 
				where `tid`='$tid' and `uid`='$uid'");
			$lastid = $existTechnic['id'];
		}
		else
		{
			sql_query("insert into sys_technic (cid,tid,uid,level,state,state_starttime,state_endtime) values ('$cid','$tid','$uid',0,1,unix_timestamp(),unix_timestamp()+'$real_time_need')");
			$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");
		}
		sql_query("insert into mem_technic_upgrading (id,cid,tid,level,state_endtime) values ('$lastid','$cid','$tid','$dstlevel',unix_timestamp()+'$real_time_need')
		on duplicate key update cid='$cid',level='$dstlevel',`state_endtime`=unix_timestamp()+'$real_time_need'");
	}
	return getCollegeInfo($uid,$cid);
}

//停止正在建造的科技
function stopUpgradeTechnic($uid,$cid,$param)      
{             
					
	$tid = array_shift($param);		   
	$technic = sql_fetch_one("select * from sys_technic where `cid`='$cid' and `tid`='$tid' and `state`=1");
	
	if (empty($technic)) return $GLOBALS['stopUpgradeTechnic']['no_upgrading_tech_info'];

	$dsttid = $technic['tid'];
	$dstlevel = $technic['level'] + 1;
	$upgrade_need = sql_fetch_one("select * from cfg_technic_level where `tid`='$dsttid' and `level`='$dstlevel'");
	if (!empty($upgrade_need))    
	{
		//停止建造
		if ($technic['level'] == 0)
		{
			sql_query("delete from sys_technic where `uid`='$uid' and `cid`='$cid' and `tid`='$tid'");
		}
		else
		{
			sql_query("update sys_technic set `state`='0' where `cid`='$cid' and `tid`='$tid'");
		}                                                                                                  
					
		//删除内存表中相应数据                       
		sql_query("delete from mem_technic_upgrading where `id`='$technic[id]'");
		
		//返还所有资源
		addCityResources($cid,$upgrade_need['upgrade_wood'] * 0.66
            ,$upgrade_need['upgrade_rock'] * 0.66
            ,$upgrade_need['upgrade_iron'] * 0.66
            ,$upgrade_need['upgrade_food'] * 0.66
            ,$upgrade_need['upgrade_gold'] * 0.66);
	}			                  
	return getCollegeInfo($uid,$cid);  
}
  
?>