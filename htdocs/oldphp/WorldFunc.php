<?php                      
require_once("./interface.php");
require_once("./utils.php");
                    
function doGetWorldInfo($uid,$cid)
{	
	return	$heroes = sql_fetch_rows("select * from sys_city_hero where `cid`='$cid'");
}

function getBlockData($uid,$param)
{
    $blockarray = array_shift($param);
    $ret = array();
    foreach($blockarray as $block)
    {
        $blockstart = $block*100;
        $blockend = $blockstart + 100;
        
        $data = array();
        $data[] = $blockstart;
        $data[] = sql_fetch_one_cell("select group_concat((wid - $blockstart),':',type,':',ownercid,':',state,':',level,':',province,':',jun) from mem_world where wid >= $blockstart and wid < $blockend");
        $ret[] = $data;
    }
    return $ret;
}
function getWorldCityInfo($uid,$param)
{
    $cityCount = array_shift($param);
    $citylist = array();
    if ($cityCount > 0)
    {
        $s = "";
        for($i = 0; $i < $cityCount; $i++)
        {
            $cities .= $s.array_shift($param);
            $s = ",";
        }
        $citylist =  sql_fetch_rows("select c.cid,c.type as citytype,c.name as cityname,u.uid,u.name as username,u.flagchar,u.union_id,n.name as unionname,u.prestige,u.state as userstate,u.face as userface,u.sex as usersex from sys_city c,sys_user u left join sys_union n on u.union_id=n.id where c.cid in ($cities) and c.uid=u.uid");
        $user = sql_fetch_one("select * from sys_user where uid='$uid'");
        foreach($citylist as &$city)
        {
            if ($city['uid'] == $user['uid'])
            {
                $city['flag'] = 0;
            }
            else if (($city['union_id'] == $user['union_id'])&&($city['union_id'] > 0))
            {
                $city['flag'] = 1;
            }
            else
            {
                $relation = sql_fetch_one("select * from sys_union_relation where unionid='$user[union_id]' and target='$city[union_id]'");                                                                        
                if (!empty($relation))
                {
                     if ($relation['type'] == 0)    //友好联盟
                     {
                         $city['flag'] = 2;
                     }
                     else if ($relation['type'] == 1)   //中立联盟
                     {
                         $city['flag'] = 3;
                     }
                     else if ($relation['type'] == 2)   //敌对联盟
                     {
                         $city['flag'] = 4;
                     }
                }
                else
                {
                    if (($city['uid'] < NPC_UID_END)||($city['citytype'] > 0))  //NPC城和特殊城可以直接占领
                    {
                        $city['flag'] = 5;
                    }
                    else
                    {
                    	if(sql_check("select * from mem_user_trickwar where endtime>unix_timestamp() and ((uid='$uid' and targetuid='$city[uid]') or (uid='$city[uid]' and targetuid='$uid'))"))
                        {
                            $city['flag'] = 6;  //个人宣战状态
                        }
                        else
                        {
	                    	$sql="select * from mem_user_inwar where endtime>unix_timestamp() and ((uid='$uid' and targetuid='$city[uid]') or (uid='$city[uid]' and targetuid='$uid'))";
	                    	$inwar=sql_fetch_one("select * from mem_user_inwar where endtime>unix_timestamp() and ((uid='$uid' and targetuid='$city[uid]') or (uid='$city[uid]' and targetuid='$uid'))");
	                    	if(!empty($inwar))
	                    	{
	                    		if($inwar['state']==0)
	                    		{
	                    			$city['flag']=8;
	                    		}
	                			else $city['flag']=6;
	                    	}
	                    	else
	                    	{
	                    		 $city['flag'] = 7; //没有小旗
            				}
                    	}
                    }
                }
            }
            if(is_null($city['flag']))
            {
            	$city['flag']=7;
            }
        }
    }
    return $citylist;
}
function getWorldFieldInfo($uid,$param)
{
    $wid = array_shift($param);
    $ret = array();
    $ret[] = sql_fetch_one("select w.wid,w.type,w.ownercid,w.province,w.level,c.uid,c.name as cityname,u.name as username,u.prestige,u.union_id,n.name as unionname from mem_world w left join sys_city c on c.cid=w.ownercid left join sys_user u on u.uid=c.uid left join sys_union n on n.id=u.union_id  where w.wid=$wid");
    return $ret;
}
function startWar($uid,$param)
{
    $targetuid = array_shift($param);
    $targetcid=array_shift($param);
    if (sql_check("select * from mem_user_inwar where (uid='$uid' and targetuid='$targetuid') or (targetuid='$uid' and uid='$targetuid')"))
    {
        throw new Exception($GLOBALS['startWar']['war_is_declared']);
    }
    $user=sql_fetch_one("select name,state,lastcid from sys_user where uid='$uid'");
    $mystate = $user['state'];
    if ($mystate == 1) throw new Exception("你处于新手保护状态，无法宣战。");
    $targetuser=sql_fetch_one("select name,state,lastcid from sys_user where uid='$targetuid'");
    $targetstate = $targetuser['state'];
    if ($targetstate == 1) throw new Exception("对方处于新手保护状态，无法宣战。");
    
    $now = sql_fetch_one_cell("select unix_timestamp()");
    
    sql_query("insert into mem_user_inwar (uid,targetuid,state,endtime) values ('$uid','$targetuid',0,unix_timestamp()+8*3600)");  
    
    $username = $user['name'];
    $targetusername = $targetuser['name'];
    
    $caution = sprintf($GLOBALS['startWar']['succ_caution'],$username,MakeEndTime($now + 8 * 3600),MakeEndTime($now + 56 * 3600));
    sendReport($targetuid,"startwar",22,$user['lastcid'],$targetcid,$caution);
    $report = sprintf($GLOBALS['startWar']['succ_report'],$targetusername,MakeEndTime($now + 8 * 3600),MakeEndTime($now + 56 * 3600));
    sendReport($uid,"startwar",22,$user['lastcid'],$targetcid,$report);
    
    $cities = array();
    $cities = sql_fetch_one_cell("select group_concat(cid) from sys_city where uid='$targetuid'");
    $cities = explode(",",$cities);
    $param2=array();
    $cityCount=array();
    $cityCount[]=count($cities);
    $param2=array_merge($cityCount,$cities);
    return getWorldCityInfo($uid,$param2);
}
function createCityFromLand($uid,$param)
{
    $targetwid = array_shift($param);
    $targetcid = wid2cid($targetwid);
    $worldInfo = sql_fetch_one("select * from mem_world where wid=".$targetwid);
    $lastcid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
    if ($worldInfo['type'] != 1)
    {
        throw new Exception($GLOBALS['createCityFromLand']['only_flatlands_can_build']);
    }
    if ($worldInfo['ownercid'] != $lastcid)
    {
        throw new Exception($GLOBALS['createCityFromLand']['target_flatlands_notYours']);
    }
    if ($worldInfo['state'] != 0)
    {
        throw new Exception($GLOBALS['createCityFromLand']['target_flatlands_in_war']);
    }
    $troops = sql_fetch_rows("select * from sys_troops where uid='$uid' and targetcid='$targetcid' and state=4");
    if (empty($troops)) throw new Exception($GLOBALS['createCityFromLand']['no_army']);
    $chiefhid = 0;
    $gold = 0;
    $food = 0;
    $wood = 0;
    $rock = 0;
    $iron = 0;  
    foreach($troops as $troop)
    {                                
        //合一下资源
        $res = explode(',',$troop['resource']);
        $gold += $res[0];
        $food += $res[1];
        $wood += $res[2];
        $rock += $res[3];
        $iron += $res[4];   
    }
    if (($gold < 10000)||($food < 10000)||($wood < 10000)||($rock < 10000)||($iron < 10000))
    {
        throw new Exception($GLOBALS['createCityFromLand']['no_enough_resource']);
    }
    $nobility = sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
    $nobilityinfo = sql_fetch_one("select name,city_count from cfg_nobility where id='$nobility'");
    $max_city_count=$nobilityinfo['city_count'];
    $current_city_count = sql_fetch_one_cell("select count(*) from sys_city where uid='$uid'");
    if ($current_city_count >= $max_city_count)
    {
    	$nextname=sql_fetch_one_cell("select name from cfg_nobility where id=".($nobility+1));
    	
    	$msg = sprintf($GLOBALS['createCityFromLand']['nobility_not_enough'],$nextname);
        throw new Exception($msg);
    }
    
    $gold -= 5000;
    $food -= 5000;
    $wood -= 5000;
    $rock -= 5000;
    $iron -= 5000;
    //新建城池
    sql_query("replace into sys_city (`cid`,`uid`,`name`,`type`,`state`,`province`) values ('$targetcid','$uid','新城池','0','0','$worldInfo[province]')");
    //自动建设1级官府
    sql_query("delete from sys_building where `cid`='$targetcid' and `xy`='120'");
    sql_query("replace into sys_building (`cid`,`xy`,`bid`,`level`) values ('$targetcid','120','6','1')");
    sql_query("replace into mem_city_resource (`cid`,`people`,`food`,`wood`,`rock`,`iron`,`gold`,`lastupdate`) values ('$targetcid','0','$food','$wood','$rock','$iron','$gold',unix_timestamp())");

    sql_query("replace into sys_city_res_add (`cid`) values ('$targetcid')");
    //修改所在地的属性
    sql_query("update mem_world set ownercid='$targetcid',type='0' where wid=".cid2wid($targetcid));

    //重新计算宝物加成
    resetCityGoodsAdd($uid,$targetcid);

    //军队入住
    $hasSetCheif = false;
    foreach($troops as $troop)
    {
        if ($troop['hid'] > 0)
        {
            if (!$hasSetCheif)
            {
                //第一支军队的首领作为城守
                $hasSetCheif = true;
                sql_query("update sys_city_hero set cid='$targetcid',state=1 where hid='$troop[hid]'");
                sql_query("update sys_city set chiefhid='$troop[hid]' where cid='$targetcid'");
            }
            else
            {                                 
                sql_query("update sys_city_hero set cid='$targetcid',state=0 where hid='$troop[hid]'");
            }
        }
        $soldiers = explode(',',$troop['soldiers']);
        if (count($soldiers) > 0)
        {
            for ($i = 0; $i < $soldiers[0]; $i++)
            {
                $sid = $soldiers[$i * 2 + 1];
                $cnt = $soldiers[$i * 2 + 2];
                sql_query("insert into sys_city_soldier (cid,sid,`count`) values ('$targetcid','$sid','$cnt') on duplicate key update `count`=`count`+'$cnt'");
            }
        }                                 
        
        sql_query("delete from sys_troops where id='$troop[id]'");
        sql_query("delete from sys_troop_tactics where troopid='$troop[id]'");  
    	updateCityResourceAdd($troop['cid']);
    }
    updateCityResourceAdd($targetcid);

                                                                                    
    updateCityHeroChange($uid,$lastcid);
    updateCityHeroChange($uid,$targetcid);
    //完成建立新城任务
    completeTask($uid,169);
    return array();
}

function addFavourites($uid,$param)
{
    $targetcid = array_shift($param);    
    
    if (sql_check("select * from sys_favourites where uid='$uid' and cid='$targetcid'"))
    {
        throw new Exception($GLOBALS['addFavourites']['already_in_fav']);
    }
    $cnt = sql_fetch_one_cell("select count(*) from sys_favourites where uid='$uid'");
    if ($cnt >= 10)
    {
        throw new Exception($GLOBALS['addFavourites']['fav_is_full']);
    }
    $wid = cid2wid($targetcid);
    $worldInfo = sql_fetch_one("select * from mem_world where wid='$wid'");
    if ($worldInfo['type'] == 0)
    {
        $name = sql_fetch_one_cell("select name from sys_city where cid='$targetcid'");
    }
    else
    {
        $name = sql_fetch_one_cell("select name from cfg_world_type where type='$worldInfo[type]'");
    }
    sql_query("insert into sys_favourites (uid,cid,name,comments) values ('$uid','$targetcid','$name','')");
    throw new Exception($GLOBALS['addFavourites']['succ']);
}
function getFavouritesList($uid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_rows("select cid,name from sys_city where uid='$uid'");
    $ret[] = sql_fetch_rows("select id,cid,name,comments from sys_favourites where uid='$uid'");
    return $ret;
}
function deleteFavourites($uid,$param)
{
    $id = array_shift($param);
    $fav = sql_fetch_one("select * from sys_favourites where id='$id'");
    if (empty($fav) || ($fav['uid'] != $uid))
    {
        throw new Exception($GLOBALS['deleteFavourites']['error_in_del_fav']);
    }
    sql_query("delete from sys_favourites where id='$id'");
    return getFavouritesList($uid,$param);
}
function setFavouritesComments($uid,$param)
{
    $id = array_shift($param);
    $comments = addslashes(array_shift($param));
    $fav = sql_fetch_one("select * from sys_favourites where id='$id'");
    if (empty($fav) || ($fav['uid'] != $uid))
    {
        throw new Exception($GLOBALS['setFavouritesComments']['already_exist']);
    }
    sql_query("update sys_favourites set comments='$comments' where id='$id'");
    throw new Exception($GLOBALS['setFavouritesComments']['succ']);
//    return getFavouritesList($uid,$param);
    
}
?>