<?php                      
require_once("./interface.php");
require_once("./utils.php");

function getGroundInfo($uid,$cid)
{
	$ground = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_GROUND." order by level desc limit 1");
	if (empty($ground))
	{   
		throw new Exception($GLOBALS['getGroundInfo']['no_ground_built']); 
	}
	return doGetBuildingInfo($uid,$cid,$ground['xy'],ID_BUILDING_GROUND,$ground['level']);
}
       
function getTacticsSetting($uid,$cid,$param)
{                                
    $hid = array_shift($param);
    $ret =  sql_fetch_one("select * from sys_hero_tactics where `hid`='$hid'");    
    if (empty($ret))
    {
        $ret = sql_fetch_one("select * from sys_hero_tactics where `hid`='0'");
    }
    return $ret;
}                        
function setTacticsSetting($uid,$cid,$param)
{
    $hid = array_shift($param);
    $plunder = array_shift($param);
    $invade = array_shift($param);
    $deplunder = array_shift($param);
    $deinvade = array_shift($param);
    $field = array_shift($param);
    sql_query("insert into sys_user_tactics (`hid`,`plunder`,`invade`,`deplunder`,`deinvade`,`field`) values ('$hid','$plunder','$invade','$deplunder','$deinvade','$field') on duplicate key update `plunder`='$plunder',`invade`='$invade',`deplunder`='$deplunder',`deinvade`='$deinvade',`field`='$field'");
    return getGroundInfo($uid,$cid);
}

function startManeuver($uid,$cid,$param)
{
    $attack = array_shift($param); 
    $resist = array_shift($param);
    $mid = sql_insert("insert into mem_maneuver (`state`,`attacksoldiers`,`resistsoldiers`) values ('0','$attack','$resist')");
    $ret = array();
    $ret[] = $mid;
    return $ret;
}
function getManeuverResult($uid,$cid,$param)
{
    $mid = array_shift($param);
    $maneuver = sql_fetch_one("select * from mem_maneuver where id='$mid' and `state`=1");
    $ret = array();
    if (empty($maneuver))    //还在演习中
    {
        $ret[] = 0;
    }
    else
    {
        $ret[] = 1;
        $ret[] = $maneuver;
        sql_query("delete from mem_maneuver where id='$maneuver[id]'");
    }
    return $ret;
}


function StartTroop($uid,$cid,$param)
{
    $hid = array_shift($param);
    $targetcid = array_shift($param);
    $task = array_shift($param);  
    $secondAdd = array_shift($param);    
    $soldiers = array_shift($param);
    $resource = array_shift($param);
    $usegoods=array_shift($param);
    if ($targetcid == $cid)
    {
        throw new Exception($GLOBALS['StartTroop']['target_cant_be_current']);
    }
    
    if(($usegoods)&&!checkGoods($uid,59)) throw new Exception($GLOBALS['StartTroop']['no_flag']);
    
    //当前城池已经发生战斗了，就不能出发了。
    if (sql_check("select * from mem_world where wid='".cid2wid($cid)."' and state='1'"))
    {
        throw new Exception($GLOBALS['StartTroop']['city_in_battle']);
    }
    
    //检查一下是暗渡陈仓状态
    $anduTime=sql_fetch_one_cell("select `endtime` from mem_city_buffer where cid='$cid' and buftype=5 and `endtime`>unix_timestamp()");
    if(empty($anduTime))
    {
	    //检查一下十面埋伏状态
	    $shimianTime=sql_fetch_one_cell("select `endtime`-unix_timestamp() from mem_city_buffer where cid='$cid' and buftype=8 and `endtime`>unix_timestamp()");
	    if(!empty($shimianTime))
	    {
	    	$msg = sprintf($GLOBALS['StartTroop']['suffer_ShiMianMaiFu'],MakeTimeLeft($shimianTime));
	    	throw new Exception($msg);
	    }
    }
    $targetwid = cid2wid($targetcid);
    $worldinfo = sql_fetch_one("select * from mem_world where wid=$targetwid");
    if ($worldinfo == false) throw new Exception($GLOBALS['StartTroop']['invalid_target'].$task);

    $targetCityInfo=sql_fetch_one("select * from sys_city where cid='$worldinfo[ownercid]'");
    
    $targetcitytype = $targetCityInfo['type'];
    //校场的等级不够的话就不能出发
    $groundLevel = intval(sql_fetch_one_cell("select level from sys_building where cid=$cid and bid='".ID_BUILDING_GROUND."'"));
    $troopCount = intval(sql_fetch_one_cell("select count(*) from sys_troops where cid=$cid and uid='$uid'"));
    if ($troopCount >= $groundLevel)
    {
        throw new Exception($GLOBALS['StartTroop']['insufficient_ground_level']);
    }

    /////////////// need test    
    $taskname = array($GLOBALS['StartTroop']['transport'],$GLOBALS['StartTroop']['send'],$GLOBALS['StartTroop']['detect'],$GLOBALS['StartTroop']['harry'],$GLOBALS['StartTroop']['occupy']);
    $forceNeed=array(2,1,3,4,5);
    //检查一下英雄的有效性。
    if ($hid != 0)
    {
    	$heroInfo=sql_fetch_one("select * from sys_city_hero h left join mem_hero_blood m on m.hid=h.hid where h.hid='$hid' and h.uid='$uid' and h.cid='$cid'");
        if (empty($heroInfo))
        {
           throw new Exception($GLOBALS['StartTroop']['hero_not_found']); 
        }
        else if($heroInfo['state']!=0)
        {
        	throw new Exception($GLOBALS['StartTroop']['hero_is_busy']);
        }
        else
        {
        	$force=$heroInfo['force'];
        	if($force<$forceNeed[$task])
        	{
        		throw new Exception(sprintf($GLOBALS['StartTroop']['hero_not_enough_force'],$taskname[$task],$forceNeed[$task]));
        	}
        }
    }
    
    //检查一下目标是否可以执行当前任务
    //运输到自己和同盟的城
    //派遣到自己和同盟归属的城和野地
    //侦察可以到非盟友的城和野地
    //掠夺可以到非盟友的城和野地
    //占领可以到非盟友的城和野地
    $targetIsUnion = false; //非盟友
    $targetuid = 0;
    if ($worldinfo['ownercid'] == $cid)  //自己城池归属的城或野地
    {     
        $targetIsUnion = true;
        $targetuid = $uid;
    }
    else if ($worldinfo['type'] == 0) //是城池
    {
    	$targetuid = $targetCityInfo['uid'];    //对方城所属的玩家
    }
    else if ($worldinfo['ownercid'] != 0)  //非无主的城或野地
    {
        $targetuid = $targetCityInfo['uid'];    //对方城所属的玩家
    }
    
    
	if(!(($task==0)&&($targetuid==$uid)&&($worldinfo['type']==0)))
	{
	    //目标是城池的话，要检查自己是不是刚使用过高级迁城令
	    $lastMoveCD=intval(sql_fetch_one_cell("select last_adv_move+86400-unix_timestamp() from mem_city_schedule where cid='$cid'"));
	    if($lastMoveCD>0)
	    {
	    	$msg=sprintf($GLOBALS['StartTroop']['adv_move_cooldown'],MakeTimeLeft($lastMoveCD));
	    	throw new Exception($msg);
	    }
    }
    
    
    $myUserInfo=sql_fetch_one("select * from sys_user where uid='$uid'");
    $targetUserInfo=sql_fetch_one("select * from sys_user where uid='$targetuid'");
    if(!empty($targetuid))
    {
        $targetunion = $targetUserInfo['union_id'];
        $myunion = $myUserInfo['union_id'];
        if ($uid == $targetuid)
        {
            $targetIsUnion = true;
        }
        else
        {
            if (!empty($targetunion))
            {
                if (($myunion == $targetunion)&&$myunion>0)
                {
                    $targetIsUnion = true;
                }
            }
        }
        
        if (($worldinfo['type'] == 0)&&(!$targetIsUnion))
        {
            //首先看是不是敌对联盟
            $union_relation = sql_fetch_one("select * from sys_union_relation where unionid='$myunion' and target='$targetunion'");
            if ((!empty($union_relation))&&($union_relation['type']==0)&&($task == 2))
            {
                throw new Exception($GLOBALS['StartTroop']['cant_detect_friendly_union']);
            }
            
            if ((empty($union_relation)||($union_relation['type'] != 2)))   //没有关系或者不是敌对状态的话，盟之间是不能打的
            {
                //看对方是不是NPC或是郡城以上城，如果是的话，则直接可以出征
                if (!(($targetuid < NPC_UID_END)||($targetcitytype > 0)))
                {
                    if ($task > 2)   //"掠夺","占领"需要宣战或不宣而战       
                    {
                        $user_trickwar  = sql_fetch_one("select * from mem_user_trickwar where (uid='$uid' and targetuid='$targetuid') or (uid='$targetuid' and targetuid='$uid')");
                        if (empty($user_trickwar))      //没有不宣而战的话，看有没有宣战过
                        {
                            $user_inwar = sql_fetch_one("select * from mem_user_inwar where (uid='$uid' and targetuid='$targetuid') or (uid='$targetuid' and targetuid='$uid')");
                            
                            if (empty($user_inwar))
                            {
                                throw new Exception($GLOBALS['StartTroop']['not_in_battle_condition']);
                            }
                            else
                            {
                                if ($user_inwar['state'] == 0)
                                {
                                	$msg = sprintf($GLOBALS['StartTroop']['wait_to_battle'],MakeEndTime($user_inwar['endtime']));
                                    throw new Exception($msg);
                                }   
                            }
                        }
                    }
                }
            }
        }
    }
    
    //查看封禁、休假状态
    if($targetcitytype==0)
    {
	    $myuserstate=sql_fetch_one("select forbiend,vacend,unix_timestamp() as nowtime from sys_user_state where uid='$targetuid' and (forbiend>unix_timestamp() or vacend>unix_timestamp())");
	    if(!empty($myuserstate))
	    {
	    	if($myuserstate['forbiend']>$myuserstate['nowtime'])
	    	{
	    		throw new Exception($GLOBALS['StartTroop']['target_be_locked']);
	    	}
	    	else if ($myuserstate['vacend']>$myuserstate['nowtime'])
	    	{
	    		throw new Exception($GLOBALS['StartTroop']['target_in_vacation']);
	    	}
	    }
    }
    
    //所有名城必须在完成黄巾史诗任务后才能"掠夺","占领"
    if ($task>2)
    {
    	$targetcitytype=$targetCityInfo['type'];
    	if($targetcitytype==4)
    	{
    		throw new Exception($GLOBALS['StartTroop']['capital']);
    	}
    	else if ($targetcid==225185) //长安暂时不能打
    	{
    		throw new Exception($GLOBALS['StartTroop']['changan']);
    	}
        else if($targetcitytype>0)
        {
        	$huangjinProgress=sql_fetch_one_cell("select value from mem_state where state=5");
        	if(empty($huangjinProgress))
        	{
        		throw new Exception($GLOBALS['StartTroop']['huangjin_unfinished']);
        	}
        	$chiefhid=$targetCityInfo['chiefhid'];
	        if ($chiefhid > 0) //如果有名将做城守，必须打败所有部将才能攻击
	        {
	            $chiefhero = sql_fetch_one("select * from sys_city_hero where hid='$chiefhid'");
	            if ((!empty($chiefhero))&&($chiefhero['npcid']>0)&&($chiefhero['npcid']==$chiefhero['uid']))
	            {
	            	$followingCnt=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$chiefhero[uid]'");
	            	if($followingCnt>1)
	            	{
	            		$msg=sprintf($GLOBALS['StartTroop']['has_following'],$chiefhero['name'],$followingCnt-1);
	            		throw new Exception($msg);
	            	}
	                 //throw new Exception($GLOBALS['StartTroop']['has_great_hero'] );
	            }
	        }
        }
    }
    
    
    $mystate = $myUserInfo['state'];
    //当目标是一个城池的时候
    if (($worldinfo['type'] == 0)&&($targetuid <> $uid))    //向其它人的城池进发的
    {
        if ($task>1)    //侦察，掠夺或占领。
        {
            if ($mystate == 1)
            {
                throw new Exception($GLOBALS['StartTroop']['still_in_protection']);
            }
            else if ($mystate == 2)
            {
                throw new Exception($GLOBALS['StartTroop']['in_peace_condition']); 
            }
            $targetstate = $targetUserInfo['state'];
            if ($targetstate == 1)
            {
                throw new Exception($GLOBALS['StartTroop']['target_in_protection']);
            }
            else if ($targetstate == 2&&$targetcitytype==0)
            {
                throw new Exception($GLOBALS['StartTroop']['target_in_peace']);
            }
        }
    }
    if ($task == 0)  //运输
    {
        if (!($targetIsUnion && ($worldinfo['type'] == 0))) 
        {
            throw new Exception($GLOBALS['StartTroop']['only_transport_to_friendly']);
        }
        else 
        {  
            if ($targetuid != $uid)
            {
                if (($mystate==2)||($mystate==1)) throw new Exception($GLOBALS['StartTroop']['transport_in_peace_or_protection']);        
            }
        }
    }
    else if ($task == 1) //派遣
    {
        if (!$targetIsUnion)
        {
            throw new Exception($GLOBALS['StartTroop']['only_send_to_friendly']);
        }
        else 
        {
            if ($targetuid != $uid)
            {
                if (($mystate==2)||($mystate==1)) throw new Exception($GLOBALS['StartTroop']['send_in_peace_or_protection']);
                $allowUnionTroop=getAllowUnionTroop($targetuid,$targetcid);
                if(empty($allowUnionTroop)) //检查对方是否允许盟友驻军
                {
                	throw new Exception($GLOBALS['StartTroop']['not_allow_union_troop']);
                }
            }
        }
    }
    else
    {
    	$msg = sprintf($GLOBALS['StartTroop']['only_towards_enemy'],$taskname[$task]);
        if ($targetIsUnion) throw new Exception($msg);
    }
    
    //检查一下当前城池是否有这么多军队
    $citySoldiers = sql_fetch_map("select * from sys_city_soldier where cid='$cid'","sid");
    
    $soldierArray = explode(",",$soldiers);
    $numSoldiers = array_shift($soldierArray);
    $takeSoldiers = array();    //真正带出去的军队
    $soldierAllCount = 0;
    $cihouCount = 0;
    for ($i = 0; $i < $numSoldiers; $i++)
    {
        $sid = array_shift($soldierArray);
        $cnt = array_shift($soldierArray);
        if ($cnt < 0) $cnt = 0;
        $takeSoldiers[$sid] = $cnt;
        if ($citySoldiers[$sid]['count'] < $cnt)
        {
            throw new Exception($GLOBALS['StartTroop']['no_so_many_army']);
        }
        $soldierAllCount += $cnt;    
        if ($sid == 3) $cihouCount+=$cnt;     
    }
    
    if ($soldierAllCount <= 0) throw new Exception($GLOBALS['StartTroop']['no_soldier']);
    if (($task == 2)&&($cihouCount<= 0)) throw new Exception($GLOBALS['StartTroop']['army_with_spy']);
    if (($task > 2)&&($cihouCount >= $soldierAllCount)) throw new Exception($GLOBALS['StartTroop']['spy_cant_alone']);
    
    $groundLevelLimit = $groundLevel * 10000 * GAME_SPEED_RATE;
    $limitadd=0;
    if(!empty($usegoods))
    {
    	$limitadd+=25;
    }
    $myCityInfo=sql_fetch_one("select * from sys_city where cid='$cid'");
    if($myCityInfo['type']>0)
    {
    	if($myCityInfo['type']==1) $limitadd+=25;
    	else if($myCityInfo['type']==2) $limitadd+=50;
    	else if($myCityInfo['type']==3) $limitadd+=75;
    	else if($myCityInfo['type']==4) $limitadd+=100;
    }
    if($limitadd>0)
    {
    	$groundLevelLimit=ceil($groundLevelLimit*(100+$limitadd)/100);
    }
    if ($soldierAllCount > $groundLevelLimit)
    {
        throw new Exception(sprintf($GLOBALS['StartTroop']['no_enough_ground_level'],$groundLevelLimit));
    }
    
    
    //行军技巧和驾驭技巧
    $speedAddRate1=1+intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=12"))*0.1;
    $speedAddRate2=1+intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=13"))*0.05;
    $speedAddRate3=1;
    if($hid!=0)
    {
    	$speedAddRate3=1+$heroInfo['speed_add_on']*0.01;
    }
    $currentX = $cid % 1000;
    $currentY = floor($cid / 1000);
    $targetX  = $targetcid % 1000;
    $targetY  = floor($targetcid / 1000);
    
    //单程时间 ＝ 每格子距离/最慢兵种速度+宿营时间（每格距离＝60000/game_speed_rate）
    $pathLength = sqrt(($targetX - $currentX)*($targetX - $currentX) + ($targetY - $currentY)*($targetY - $currentY));
    $minSpeed = 999999999;
    $soldierConfig = sql_fetch_rows("select * from cfg_soldier where fromcity=1 order by sid","sid");
    foreach ($soldierConfig as $soldier)        //找到当前军队里最慢的
    {
        if (!empty($takeSoldiers[$soldier->sid]))
        {
        	if($soldier->sid<7&&$soldier->sid!=3)
        	{
        		$minSpeed = min($soldier->speed*$speedAddRate1*$speedAddRate3,$minSpeed);
        	}
            else
            {
            	$minSpeed = min($soldier->speed*$speedAddRate2*$speedAddRate3,$minSpeed);
            }
        }
    }
    
    if ($targetIsUnion)	//同盟才能用
    {
	    $speedAddRate = sql_fetch_one_cell("select (1.0 + level * 0.5) from sys_building where cid='$cid' and bid='".ID_BUILDING_DAK."'");
	    if (!empty($speedAddRate))
	    {
	        $minSpeed *= $speedAddRate;
	    }
    }
    $pathNeedTime = $pathLength * GRID_DISTANCE / $minSpeed;    //需要多少时间
    if ($secondAdd < 0) $secondAdd = 0;
    $pathNeedTime += $secondAdd;
    $pathNeedTime=intval(floor($pathNeedTime));
    //出征耗粮 ＝ 兵的耗粮/小时*2*单程时间   
    $foodUse = 0;
    $allpeople = 0;
    $allcarry = 0;
    foreach ($soldierConfig as $soldier)        //找到当前军队里最慢的
    {
        if (!empty($takeSoldiers[$soldier->sid]))
        {
            $foodUse += $soldier->food_use * $takeSoldiers[$soldier->sid];
            $allpeople += $soldier->people_need * $takeSoldiers[$soldier->sid];
            $allcarry += $soldier->carry * $takeSoldiers[$soldier->sid];
        }
    }
    $hourfooduse = $foodUse * 2;        //每小时总耗粮食量
    
    $foodRate = 2;
//    if ($task == 4) $foodRate = 5;    //现在不用了
    $foodUse *= $foodRate * $pathNeedTime;
    $foodUse = floor($foodUse/3600);    //军队行程耗粮量
    //检查一下当前城池是否有足够的军粮，直接吃掉
    $cityresource = sql_fetch_one("select * from mem_city_resource where cid='$cid'");
    if ($cityresource['food'] < $foodUse) throw new Exception($GLOBALS['StartTroop']['no_enough_food']);                                                                                 
    //检查一下当前城池是否有这么多资源，并把这些资源砍掉
    $resources = explode(",",$resource);
    $gold = $resources[0];
    $food = $resources[1];
    $wood = $resources[2];
    $rock = $resources[3];
    $iron = $resources[4];
    
    if (($gold < 0)||($food < 0)||($wood < 0)||($rock < 0)||($iron < 0))
    {
        throw new Exception($GLOBALS['StartTroop']['cant_carry_negative']);
    }
    if (($cityresource['gold'] < $gold)||
        ($cityresource['food'] < $food + $foodUse)||
        ($cityresource['wood'] < $wood)||
        ($cityresource['rock'] < $rock)||
        ($cityresource['iron'] < $iron))
    {
    	throw new Exception($GLOBALS['StartTroop']['no_enough_resource']);
    }
    //负重技术(11)：每升1级，军队负重增加10%。
    $carryTechLevel = sql_fetch_one_cell("select level from sys_city_technic where cid=$cid and tid=11");
    if ($carryTechLevel > 0)
    {
        $allcarry *= (1 + $carryTechLevel * 0.1);
    }
    if ($allcarry+10 < $gold +$food + $wood + $rock +$iron + $foodUse)
    {
        throw new Exception($GLOBALS['StartTroop']['army_carry_limit']);
    }
    
    
    if ($hid != 0)  //让将领置成出征状态
    {
        sql_query("update sys_city_hero set state=2 where hid='$hid'");
        $forceReduce=$forceNeed[$task];
        sql_query("update mem_hero_blood set `force`=GREATEST(0,`force`-$forceReduce) where hid='$hid'");
    }
    //减资源
    addCityResources($cid,-$wood,-$rock,-$iron,-$foodUse-$food,-$gold);
    //减兵员
    addCitySoldiers($cid,$takeSoldiers,false);                                                             
    
    $troopid = sql_insert("insert into sys_troops (`uid`,`cid`,`hid`,`targetcid`,`task`,`state`,`starttime`,`pathtime`,`endtime`,`soldiers`,`resource`,`people`,`fooduse`) values ('$uid','$cid','$hid','$targetcid','$task','0',unix_timestamp(),'$pathNeedTime',unix_timestamp()+$pathNeedTime,'$soldiers','$resource','$allpeople','$hourfooduse')");
    
    //设置当前军队的战术为玩家当前战述
    $tactics = sql_fetch_one("select * from sys_user_tactics where uid='$uid'");
    if ($tactics)
    {
        sql_query("replace into sys_troop_tactics (`troopid`,`plunder`,`invade`,`patrol`,`field`) values ('$troopid','$tactics[plunder]','$tactics[invade]','$tactics[patrol]','$tactics[field]')");
    }                  
    
    //对方城池可能可以收到警报
    
    if ($worldinfo['type'] == WT_CITY)
    {
        $targetbalefireLevel = sql_fetch_one_cell("select level from sys_building where cid='".$targetcid."' and bid=".ID_BUILDING_BALEFIRE." limit 1");
        if ($targetbalefireLevel > 0)
        {
            $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'");
            if($targetuid != $uid&&$targetuid>0)
            {
                sql_query("insert into sys_alarm (uid,enemy) values ('$targetuid',1) on duplicate key update enemy=1");
            }
        }
    }
    else
    {
    	if ($worldinfo['ownercid'] > 0)
    	{
    		$targetbalefireLevel = sql_fetch_one_cell("select level from sys_building where cid='".$worldinfo['ownercid']."' and bid=".ID_BUILDING_BALEFIRE." limit 1");
    		if ($targetbalefireLevel > 0)
	        {
	            if($targetuid != $uid&&$targetuid>0)
	            {
	                sql_query("insert into sys_alarm (uid,enemy) values ('$targetuid',1) on duplicate key update enemy=1");
	            }
	        }
    	}
    }
    if($usegoods) reduceGoods($uid,59,1);
    //如果目标是野地，并且正在活动中，记录一下该次出征
    if (($worldinfo['type'] != WT_CITY)&&($task == 4))
    {
    	$combat = sql_fetch_one("select * from fc_combat where starttime <= unix_timestamp() and unix_timestamp() < endtime and open=1 and havefield=1 limit 1");
    	if (!empty($combat))
    	{
    		$fieldinfo = sql_fetch_one("select * from fc_fields where combatid='$combat[id]' and wid='$targetwid'");
    		if (!empty($fieldinfo))
    		{
    			sql_query("insert into fc_log (time,combatid,wid,uid,cid) values (unix_timestamp(),$combat[id],$targetwid,$uid,$cid)");
    		}
    	}
    }
    $day = sql_fetch_one_cell("select floor((unix_timestamp()-unix_timestamp('2008-09-08 19:00:00'))/86400)");
    if (($day >= 0)&&($day < 4))
    {
    	if (sql_check("select * from evt_zq_yt where day='$day' and wid='$targetwid'"))
    	{
    		sql_query("insert into evt_zq_ytlog (time,day,wid,uid,cid) values (unix_timestamp(),'$day','$targetwid','$uid','$cid')");
    	}
    }
    
    $ret=array();
    $ret[]=$GLOBALS['StartTroop']['succ'];
    return $ret;
}
function getAttackTactics($uid,$cid,$param)
{
    $ret = array();
    $tac = sql_fetch_one("select * from sys_user_tactics where uid='$uid'");
    if (!empty($tac))
    {
        $ret[] = $tac;
    }
    return $ret;
}
function setAttackTactics($uid,$cid,$param)
{
    $plunderCount = array_shift($param);
    $plunderArray = array();
    for ($i = 0; $i < $plunderCount; $i++)
    {
        $stype = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);                          
        $plunderArray[] = $stype.",".$action.",".$target;
    }
    $plunder = implode(";",$plunderArray);
                                                                     
    $invadeCount = array_shift($param);
    $invadeArray = array();
    for ($i = 0; $i < $invadeCount; $i++)
    {
        $stype = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);
        $action2 = array_shift($param);
        $target2 = array_shift($param);
        
        $invadeArray[] = $stype.",".$action.",".$target.",".$action2.",".$target2;   
    }
    $invade = implode(";",$invadeArray);
    
    $fieldCount = array_shift($param);
    $fieldArray = array();
    for ($i = 0; $i < $fieldCount; $i++)
    {
        $stype = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);
        $fieldArray[] = $stype.",".$action.",".$target;                         
    }
    $field = implode(";",$fieldArray);

    $action = array_shift($param);  
    $patrol = "3,".$action.",3";  
    
    //$sql="insert into sys_user_tactics (`uid`,`plunder`,`invade`,`field`,`patrol`) values ('$uid','$plunder','$invade','$field','$patrol') on duplicate key update `plunder`='$plunder',`invade`='$invade',`field`='$field',`patrol`='$patrol'";
    //throw new Exception($sql);
    sql_query("insert into sys_user_tactics (`uid`,`plunder`,`invade`,`field`,`patrol`) values ('$uid','$plunder','$invade','$field','$patrol') on duplicate key update `plunder`='$plunder',`invade`='$invade',`field`='$field',`patrol`='$patrol'");
    
    throw new Exception($GLOBALS['setAttackTactics']['succ']);
}
function getResistTactics($uid,$cid,$param)
{                                                                               
    $ret = array();
    $tac = sql_fetch_one("select * from sys_city_tactics where cid='$cid'");
    if (!empty($tac))
    {
        $ret[] = $tac;
    }
    return $ret;
}                 
function setResistTactics($uid,$cid,$param)
{
    $deplunderCount = array_shift($param);
    $deplunderArray = array();
    $deplunderJoinArray = array();
    for ($i = 0; $i < $deplunderCount; $i++)
    {
        $stype = array_shift($param);
        $join = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);    
        if ($join) $deplunderJoinArray[] = $stype;
        $deplunderArray[] = $stype.",".$action.",".$target;                               
    }
    $deplunder = implode(";",$deplunderArray);
    $deplunder_join = implode(",",$deplunderJoinArray);
    
    
    $deinvadeCount = array_shift($param);
    $deinvadeArray = array();
    $deinvadeJoinArray = array();
    for ($i = 0; $i < $deinvadeCount; $i++)
    {
        $stype = array_shift($param);
        $join = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);
        $action2 = array_shift($param);
        $target2 = array_shift($param);
                                                                            
        if ($join) $deinvadeJoinArray[] = $stype;
        $deinvadeArray[] = $stype.",".$action.",".$target.",".$action2.",".$target2;   
    }                                              
    
    $defenceCount = array_shift($param);
    for ($i = 0; $i < $defenceCount; $i++)
    {
        $stype = array_shift($param);
        $action = array_shift($param);
        $target = array_shift($param);
        $deinvadeArray[] = $stype.",".$action.",".$target;                     
    }
    
    $deinvade = implode(";",$deinvadeArray);
    $deinvade_join = implode(",",$deinvadeJoinArray);
        
    
    $join = array_shift($param);              
    $action = array_shift($param); 
    
    if ($join) $depatrol_join = "3"; else $depatrol_join = "";
    $depatrol = "3,".$action.",3";  
    
    sql_query("insert into sys_city_tactics (`cid`,`deplunder_join`,`deplunder`,`depatrol_join`,`depatrol`,`deinvade_join`,`deinvade`) values ('$cid','$deplunder_join','$deplunder','$depatrol_join','$depatrol','$deinvade_join','$deinvade') on duplicate key update `deplunder_join`='$deplunder_join',`deplunder`='$deplunder',`depatrol_join`='$depatrol_join',`depatrol`='$depatrol',`deinvade_join`='$deinvade_join',`deinvade`='$deinvade'");
    
    throw new Exception($GLOBALS['setResistTactics']['succ']);
}
function getWoundedSoldierGoldNeed($cid)
{
    return intval(sql_fetch_one_cell("select 0.1 * sum(s.count * (f.wood_need*".WOOD_VALUE."+f.food_need*".FOOD_VALUE."+f.rock_need*".ROCK_VALUE."+f.iron_need*".IRON_VALUE.")) from mem_city_wounded s left join cfg_soldier f on f.sid=s.sid where s.cid='$cid' and s.sid<13 and s.count>0"));
}

function getWoundedSoldierPeople($cid)
{
	return intval(sql_fetch_one_cell("select sum(c.people_need*w.`count`) from mem_city_wounded w left join cfg_soldier c on c.sid=w.sid where w.cid='$cid' and w.sid<13 and w.count>0"));
}

function getWoundedSoldier($uid,$cid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_rows("select w.sid,s.name,w.count from mem_city_wounded w left join cfg_soldier s on w.sid=s.sid where w.cid='$cid' and w.sid<13 and w.count > 0");
    $ret[] = getWoundedSoldierGoldNeed($cid);
    return $ret;
}
function cureWoundedSoldier($uid,$cid,$param)
{
    $goldneed =getWoundedSoldierGoldNeed($cid); 
    $citygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");    
    if ($citygold < $goldneed) throw new Exception($GLOBALS['cureWoundedSoldier']['no_enough_gold']);
    
    $soldiers = sql_fetch_rows("select * from mem_city_wounded where cid='$cid' and sid<13");
    if (empty($soldiers)) throw new Exception($GLOBALS['cureWoundedSoldier']['no_wounded_soldier']);
    foreach($soldiers as $soldier)
    {
        sql_query("insert into sys_city_soldier (cid,sid,count) values ('$cid','$soldier[sid]','$soldier[count]') on duplicate key update count=count+'$soldier[count]'");
    }
    sql_query("delete from mem_city_wounded where cid='$cid'");
    updateUserPrestige($uid);
    addCityResources($cid,0,0,0,0,-$goldneed);
    completeTask($uid,372);
    throw new Exception($GLOBALS['cureWoundedSoldier']['succ']);
}
function dismissWoundedSoldier($uid,$cid,$param)
{
	$people=getWoundedSoldierPeople($cid);
	sql_query("update mem_city_resource set people=people+'$people' where cid='$cid'");
    sql_query("delete from mem_city_wounded where cid='$cid'");
    updateCityResourceAdd($cid);
    throw new Exception($GLOBALS['dismissWoundedSoldier']['succ']);
}     
?>
