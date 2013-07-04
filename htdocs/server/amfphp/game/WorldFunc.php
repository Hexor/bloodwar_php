<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./UnionFunc.php");

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
		$citylist =  sql_fetch_rows("select c.cid,c.type as citytype,c.name as cityname,u.uid,u.name as username,u.passport,u.flagchar,u.union_id,n.name as unionname,u.prestige,u.state as userstate,u.face as userface,u.sex as usersex from sys_city c,sys_user u left join sys_union n on u.union_id=n.id where c.cid in ($cities) and c.uid=u.uid");
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
					else{
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
    if ($mystate == 1) throw new Exception($GLOBALS['startWar']['new_protect']);
    $targetuser=sql_fetch_one("select name,state,lastcid,union_id from sys_user where uid='$targetuid'");
    $targetstate = $targetuser['state'];
    if ($targetstate == 1) throw new Exception($GLOBALS['startWar']['target_new_protect']);

	$now = sql_fetch_one_cell("select unix_timestamp()");

	sql_query("insert into mem_user_inwar (uid,targetuid,state,endtime) values ('$uid','$targetuid',0,unix_timestamp()+8*3600)");

	$username = $user['name'];
	$targetusername = $targetuser['name'];

	$caution = sprintf($GLOBALS['startWar']['succ_caution'],$username,MakeEndTime($now + 8 * 3600),MakeEndTime($now + 56 * 3600));
	sendReport($targetuid,"startwar",22,$user['lastcid'],$targetcid,$caution);
	$report = sprintf($GLOBALS['startWar']['succ_report'],$targetusername,MakeEndTime($now + 8 * 3600),MakeEndTime($now + 56 * 3600));
	sendReport($uid,"startwar",22,$user['lastcid'],$targetcid,$report);
	
	if($targetuser["union_id"]>0){
		$msg=sprintf($GLOBALS['start_war']['union_msg'],$targetuser["name"],$user["name"]);
		addUnionEvent($targetuser["union_id"],11,$msg);
	}
	
	if(defined("USER_FOR_51") && USER_FOR_51){
    	require_once("51utils.php");
    	add51StartWarEvent($targetuser["name"]);   
    }
	if (defined("PASSTYPE")){
	    require_once 'game/agents/AgentServiceFactory.php';
		AgentServiceFactory::getInstance($uid)->addStartWarEvent($targetuser["name"]);   
    }
	
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
	//推恩
	$nobility = getBufferNobility($uid,$nobility);

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
	$username=sql_fetch_one_cell("select name from sys_user where uid='$uid``'");
	if(defined("USER_FOR_51") && USER_FOR_51){
		require_once("51utils.php");	
    	add51CreateCityEvent($username);   
	}
	if (defined("PASSTYPE")){	 
		require_once 'game/agents/AgentServiceFactory.php';
		AgentServiceFactory::getInstance($uid)->addCreateCityEvent($username);
    }
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

function getMaxCountByOfficePos($cityType,$officepos){	
	$maxCount=0;
	if($cityType==1){					
		if ($officepos==6) $maxCount=1;
		else if ($officepos==7) $maxCount=2;
		else if ($officepos>=8) $maxCount=3;	
	}
	else if($cityType==2){				
		if ($officepos==9) $maxCount=4;
		else if ($officepos==10) $maxCount=5;
		else if ($officepos>=11) $maxCount=6;
	}else if($cityType==3) {
		if ($officepos>=12) $maxCount=8;
	}else {
		if ($officepos>=13) 
			$maxCount=10;
	}
	return $maxCount;
}
//点击政令按钮时候，取得今天已经下达的次数
function getGovernInfo($uid,$param){
	$cid=intval(array_shift($param));
	$count_and_time=sql_fetch_one("select govern_count,last_govern_time from mem_city_schedule where cid='$cid'");
	
	$officepos =sql_fetch_one_cell("select officepos from sys_user where uid='$uid'");
		
	$officename =sql_fetch_one_cell("select name from cfg_office_pos where id='$officepos'");
	$cityType = sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	//每天最大下达次数
	$maxCount=getMaxCountByOfficePos($cityType,$officepos);	
	
	$ret=array();
	$ret[]=$officename;
	$ret[]=$maxCount;
	
	if(empty($count_and_time)){
		//从来没有下达过政令
		$ret[]=0;		
		return $ret;
	}
	$count=$count_and_time['govern_count'];
	$now = sql_fetch_one_cell("select unix_timestamp()");

	if (floor(($now + 8 * 3600) / 86400 ) > floor(($count_and_time['last_govern_time'] + 8 * 3600) / 86400)){
		$ret[]=0;
		return $ret;
	}
	$ret[]=$count;	
	return $ret;

}

//下达政令
function governOthers($uid,$param){

	//政令类型 0 收税 1 抽丁 2 征粮 3 收编  4裁军
	$type=intval(array_shift($param));
	//目标城池id
	$tcid=intval(array_shift($param));
	//目标用户ID
	$tuid=intval(array_shift($param));
	//当前城池id
	$cid=intval(array_shift($param));
	

	$cityname=array_shift($param);
	

	//检查发布政令的城池是不是州郡县

	//普通城不能下达政令
	$cityType = sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	if($cityType==0) throw new Exception($GLOBALS['governOthers']['city_cannot_govern']);

	//官府没有达到10级，不能下达
	$governLevel = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=6" );
	if($governLevel<10) throw new Exception($GLOBALS['governOthers']['not_enouth_government_level']);

	
	//目标城池级别大于自己的级别，也不能下达
	$targetcitytype = sql_fetch_one_cell("select type from sys_city where cid='$tcid'");
	if($cityType<=$targetcitytype) throw new Exception($GLOBALS['governOthers']['not_enough_level']);
	
	//不能对新手、免战、休假、封禁的城池下达政令。（名城不在保护之列）
	//$state = sql_fetch_one_cell("select state from sys_user where uid='$tuid'" );
	//if($state!=0 && $targetcitytype==0) throw new Exception($GLOBALS['governOthers']['target_not_in_war']);
	
	//查看封禁、休假状态
	if($targetcitytype==0)
	{
		$myuserstate=sql_fetch_one("select forbiend,vacend,unix_timestamp() as nowtime from sys_user_state where uid='$tuid' and (forbiend>unix_timestamp() or vacend>unix_timestamp())");
		if(!empty($myuserstate))
		{
			if($myuserstate['forbiend']>$myuserstate['nowtime'])
			{
				//封禁
				throw new Exception($GLOBALS['governOthers']['target_not_in_war']);
			}
			else if ($myuserstate['vacend']>$myuserstate['nowtime'])
			{
				//休假
				throw new Exception($GLOBALS['governOthers']['target_not_in_war']);
			}
		}
	}
	
	$cityTypeNameField ="big_city_"+$cityType; 
	$cityTypeName = sql_fetch_one_cell("select value from cfg_name where  name='$cityTypeNameField'");
	$officepos =sql_fetch_one_cell("select officepos from sys_user where uid='$uid'");
	$officename =sql_fetch_one_cell("select name from cfg_office_pos where id='$officepos'");
	
	$wid = cid2wid($cid);
	$twid = cid2wid($tcid);
	
	$world = sql_fetch_one("select province,jun from mem_world where wid = $wid" );
	$tworld = sql_fetch_one("select province,jun from mem_world where wid = $twid" );
	$province = $world["province"];	
	$jun = $world["jun"];	
	$tprovince = $tworld["province"];
	$tjun = $tworld["jun"];
	
	$x=$cid%1000;
	$y=floor($cid/1000);
	

	
	
	//每天最大下达次数
	$maxCount=getMaxCountByOfficePos($cityType,$officepos);	
	if($cityType==1){				
		//检查是不是在同一个县
		if ($jun != $tjun || $province != $tprovince) 	throw new Exception($GLOBALS['governOthers']['not_enough_level']);		
	}
	else if($cityType==2){
		//检查是不是在同一个郡 
		if ($jun != $tjun || $province != $tprovince) 	throw new Exception($GLOBALS['governOthers']['not_enough_level']);					
	}

	else if($cityType==3) {
		//检查是不是在同一个州
		if ($province != $tprovince) 	throw new Exception($GLOBALS['governOthers']['not_enough_level']);
	}

	$now = sql_fetch_one_cell("select unix_timestamp()");
	
	$lastBeGovernTime =  sql_fetch_one_cell("select last_be_govern_time from mem_city_schedule where cid='$tcid'");
	if (!empty($lastBeGovernTime)){
		//一天以内被下达过政令 则不能下达	 	
		if (!(floor(($now + 8 * 3600) / 86400 ) > floor(($lastBeGovernTime + 8 * 3600) / 86400)))
		throw new Exception($GLOBALS['governOthers']['target_has_been_govern']);
	}

	$timeandcount =  sql_fetch_one("select govern_count,last_govern_time from mem_city_schedule where cid='$cid'");

	$todayFirst=false;
	$todayCount=0;
	
	
	if (empty($timeandcount)){
		$todayFirst = true;
	}
	else{
		$lastTime=$timeandcount["last_govern_time"];
		if (floor(($now + 8 * 3600) / 86400 ) > floor(($lastTime + 8 * 3600) / 86400)){
			$todayFirst = true;
		}else{
			$todayCount =  $timeandcount['govern_count'];
			//下达次数太多
			if($todayCount>=$maxCount) {
				throw new Exception($GLOBALS['governOthers']['too_many_time'],$officename,$cityTypeName,$maxCount,$maxCount);
			}

		}
	}

	//今天又增加一次
	$todayCount++;

	$msg="";

	if($type==0){//征税
	
		$totalCount = sql_fetch_one_cell("select gold from mem_city_resource where cid='$tcid'");
		$addCount=	floor($totalCount /10);	
	
		//收税
		//减别人的
		addCityResources($tcid,0,0,0,0,(0-$addCount));
		$report=sprintf($GLOBALS['governOthers']['gold_report'],$cityname,$x,$y,$addCount);
		sendReport($tuid,3,26,$cid,$tcid,$report);
		//加自己的
		addCityResources($cid,0,0,0,0,($addCount));

		$msg=sprintf($GLOBALS['governOthers']['gold_suc'],$addCount);
	}else if($type==1) {//抽丁
		$totalCount = sql_fetch_one_cell("select people from mem_city_resource where cid='$tcid'");
		$addCount=	floor($totalCount/5);
		
		addCityPeople($tcid,(0-$addCount));
	
		addCityPeople($cid,$addCount);
		$report=sprintf($GLOBALS['governOthers']['people_report'],$cityname,$x,$y,$addCount);
		sendReport($tuid,3,28,$cid,$tcid,$report);
		$msg=sprintf($GLOBALS['governOthers']['people_suc'],$addCount);
	}else if($type==2) {//征粮
		$totalCount = sql_fetch_one_cell("select food from mem_city_resource where cid='$tcid'");		
		$addCount=	floor($totalCount /10);
	
		addCityResources($tcid,0,0,0,(0-$addCount),0);
		$report=sprintf($GLOBALS['governOthers']['food_report'],$cityname,$x,$y,$addCount);
		sendReport($tuid,3,42,$cid,$tcid,$report);
		//加自己的
		addCityResources($cid,0,0,0,($addCount),0);
		$msg=sprintf($GLOBALS['governOthers']['food_suc'],$addCount);
		
	}else if($type==3) { //收编 
		$row = sql_fetch_one("select sid,count from sys_city_soldier where cid='$tcid' order by count desc  limit 1;");
		$sid=1;
		$totalCount=0;
		if ($row){
			$sid = $row["sid"];
			$totalCount = $row["count"];
		}
		$sname = sql_fetch_one_cell("select name from cfg_soldier where sid = $sid");
		$addCount=	floor($totalCount/50);		
		addCitySoldier($tcid,$sid,(0-$addCount));
		$report=sprintf($GLOBALS['governOthers']['incorporation_report'],$cityname,$x,$y,$sname,$addCount);
		sendReport($tuid,3,43,$cid,$tcid,$report);
		
		$meaddcount= floor($addCount/2);
		//加自己的
		addCitySoldier($cid,$sid,$meaddcount);
		$msg=sprintf($GLOBALS['governOthers']['incorporation_suc'],$sname,$meaddcount);		
	}else if($type==4) { //裁军
		$row = sql_fetch_one("select sid,count from sys_city_soldier where cid='$tcid' order by count desc  limit 1;");
		$sid=1;
		$totalCount=0;
		if ($row){
			$sid = $row["sid"];
			$totalCount = $row["count"];
		}
		$sname = sql_fetch_one_cell("select name from cfg_soldier where sid = $sid");
		$addCount=	floor($totalCount/20);
		addCitySoldier($tcid,$sid,(0-$addCount));
		$report=sprintf($GLOBALS['governOthers']['disarmament_report'],$cityname,$x,$y,$sname,$addCount);	
		sendReport($tuid,3,44,$cid,$tcid,$report);
		$msg=sprintf($GLOBALS['governOthers']['disarmament_suc'],$sname,$addCount);		
	}


	sql_query("insert into mem_city_schedule (cid,last_be_govern_time) values('$tcid',unix_timestamp()) on duplicate key update last_be_govern_time=unix_timestamp()");

	if($todayFirst){
		//重新累计24小时
		sql_query("insert into mem_city_schedule (cid,govern_count,last_be_govern_time) values('$cid','$todayCount',unix_timestamp()) on duplicate key update last_govern_time=unix_timestamp(), govern_count='$todayCount' ");
	}else{
		sql_query("insert into mem_city_schedule (cid,govern_count) values('$cid','$todayCount') on duplicate key update govern_count='$todayCount' ");
	}

	//sql_query("insert into log_reward_city_temp values($uid,1,1) on duplicate key update count=count+1;");
	
	throw new Exception($msg);

}

function getMapCity($uid,$param){
	return sql_fetch_rows("select c.name,c.cid,c.type,u.name as ownername ,un.name as union_name from sys_city c left join sys_user u on c.uid=u.uid left join sys_union un on u.union_id= un.id where c.type>1 ");
}



?>