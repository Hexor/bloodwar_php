<?php
//副本，战场
require_once("./interface.php");
require_once("./utils.php");


/**
 * 任何战场行动以前取得战场信息
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @param unknown_type $emptythrow
 * @return unknown
 */
function firstGetUserBattleInfo($uid,$emptythrow=1){
	//cfg_battle_field 里 读name
	//sys_user_battle_field里读 bid,createuid,level,maxpeople,endtime,type,state
	//sys_user_battle_state里读 battlefieldid,honour,unionid,startcid
	$userbattleinfo=sql_fetch_one("select su.honour,u.unionid,u.battlefieldid,b.bid,b.createuid,b.level,b.maxpeople,b.endtime,b.type,b.state,c.name
	from sys_user_battle_state u left join sys_user_battle_field b on u.battlefieldid=b.id left join cfg_battle_field c on b.bid=c.id left join sys_user su on u.uid=su.uid where u.uid='$uid'");
	if(empty($userbattleinfo)){
		if($emptythrow)
		throw new Exception($GLOBALS['battle']['user_not_in_battle']);
		return $userbattleinfo;
	}
	if($userbattleinfo["state"]==1){
		throw new Exception($GLOBALS['battle']['battle_froze']);
	}

	return $userbattleinfo;
}

/**
 * 判断用户的战场是否已经不能行动
 *
 * @param unknown_type $uid
 */
function ifFrozeExit($uid){
	$userbattleinfo=sql_fetch_one("select s.unionid,s.battlefieldid,su.honour,f.state,f.bid from sys_user_battle_state s left join sys_user_battle_field f on s.battlefieldid=f.id left join sys_user su on s.uid=su.uid where s.uid=$uid");
	if(empty($userbattleinfo)){
		throw new Exception($GLOBALS['battle']['user_not_in_battle']);
	}
	if($userbattleinfo["state"]==1){
		throw new Exception($GLOBALS['battle']['battle_froze']);
	}
	return $userbattleinfo;
}

function refreshUserBattleState($uid,$param){
	try{
		$currentbattle=sql_fetch_one("select su.honour,u.unionid,u.battlefieldid,b.bid,b.createuid,b.level,b.maxpeople,b.endtime,b.type,b.state,c.name
		from sys_user_battle_state u left join sys_user_battle_field b on u.battlefieldid=b.id left join cfg_battle_field c on b.bid=c.id left join sys_user su on u.uid=su.uid where u.uid='$uid'");
		$ret=array();
		$ret[]=1;
		$ret[]=$currentbattle;
		//$cityinfo=array();
		$cityinfo=sql_fetch_rows("select * from sys_battle_city where battlefieldid='$currentbattle[battlefieldid]' ");
		$cityinfo=setFlags($uid,$currentbattle['unionid'],$cityinfo);
		$ret[]=$cityinfo;
		$ret[]=sql_fetch_rows("select t.soldiers,t.cid,t.id,h.face,h.sex,h.name as heroname,h.level from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.uid='$uid' and t.battlefieldid='$currentbattle[battlefieldid]' order by t.id asc");
		$ret[]=getBattleStartCityInfo($currentbattle['bid'],$currentbattle['unionid']);
		if($currentbattle['bid']==2001){
			//官渡之战
	
			$ret[]=sql_fetch_rows("select * from sys_battle_winpoint where battlefieldid='$currentbattle[battlefieldid]'");
		}
		return $ret;
	}
	catch(Exception $e){
		$ret=array();
		$ret[] = 0;
	}
}


/**
 * 取得用户是否处在战场中的信息，如果没有则给用户打开选择战场对话框。
 * 如果有则给用户他当前战场的最新信息。
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function getUserBattleState($uid,$param){
	$nobility = intval(sql_fetch_one_cell("select nobility from sys_user  where uid = $uid"));
	//推恩
	$nobility=getBufferNobility($uid,$nobility);
	if ($nobility<1)
	throw new Exception($GLOBALS['battle']['nobility_not_rearch']);
	$currentbattle=firstGetUserBattleInfo($uid,0);
	$ret=array();
	if(empty($currentbattle)){
		//沒有參加過任何戰役
		$ret[]=0;
		$cfgbattles=sql_fetch_rows("select * from cfg_battle_field ");		
		foreach($cfgbattles as $cfgbattle){
			if($cfgbattle['type']==0){
				$battlecount=sql_fetch_one_cell("select count(*) from sys_user_battle_field where bid='$cfgbattle[id]' and state=0");
				//判断战场是否已满，满了就灰掉
				if($battlecount>=$cfgbattle['maxcount']){
					$cfgbattle['canCreate']=false;
				}else{
					$cfgbattle['canCreate']=true;
				}
			}
			$ret[1][]=$cfgbattle;
		}
		
		$maxWarCount=5;
		$todayWarCount = sql_fetch_one_cell("select today_war_count from mem_user_schedule where uid = $uid");
		if (empty($todayWarCount))$todayWarCount=0;
		$ret[]=$todayWarCount;
		$ret[]=5;
	}else{
		$ret[]=1;
		$ret[]=$currentbattle;
		//$cityinfo=array();
		$cityinfo=sql_fetch_rows("select * from sys_battle_city where battlefieldid='$currentbattle[battlefieldid]' ");
		$cityinfo=setFlags($uid,$currentbattle['unionid'],$cityinfo);
		$ret[]=$cityinfo;
		$ret[]=sql_fetch_rows("select t.soldiers,t.cid,t.id,h.face,h.sex,h.name as heroname,h.level from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.uid='$uid' and t.battlefieldid='$currentbattle[battlefieldid]' order by t.id asc");
		$ret[]=getBattleStartCityInfo($currentbattle['bid'],$currentbattle['unionid']);
		if($currentbattle['bid']==2001){
			//官渡之战

			$ret[]=sql_fetch_rows("select * from sys_battle_winpoint where battlefieldid='$currentbattle[battlefieldid]'");
		}
	}
	return $ret;
}
function resetTodayWarCount($uid,$param){
	return useGoods($uid,$param);
}
function getPVPBattleInfo($uid,$param){
	$bid=intval(array_shift($param));
	$ret=array();
	$now =sql_fetch_one_cell("select unix_timestamp()");
	$allbattles=sql_fetch_rows("select u.id,u.bid,c.name,u.level,u.maxpeople,u.minpeople,u.state  from sys_user_battle_field u left join cfg_battle_field c on u.bid = c.id  where u.bid = $bid order by u.level desc " );
	$battles=array();
	foreach($allbattles as $battle){
		$battlefieldid=$battle['id'];
		$people=sql_fetch_one_cell("select count(*) from sys_user_battle_state where battlefieldid=$battlefieldid");
		$battle['people']=$people;
		$battles[]=$battle;
	}
	$ret[]=$battles;
	$ret[]=sql_fetch_rows("select unionid,name from cfg_battle_union where bid = $bid ");

	return $ret;
}
/**
 * 创建一个剧情战场，仅限type=1的情况。
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function createBattle($uid,$param){
	$bid=intval(array_shift($param));

	$level=intval(array_shift($param));
	if(sql_check("select * from sys_user_battle_state where uid='$uid'")){
		throw new Exception($GLOBALS['battle']['user_already_in_battle']);
	}
	$battlecfg=sql_fetch_one("select * from cfg_battle_field where id='$bid'");
	if(empty($battlecfg)){
		throw new Exception($GLOBALS['battle']['no_battle_field']);
	}

	$battlecount=sql_fetch_one_cell("select count(*) from sys_user_battle_field where bid='$bid' and state=0");
	if($battlecount>=$battlecfg['maxcount']){
		throw new Exception($GLOBALS['battle']['too_many_battle']);
	}

	$todayWarCont = sql_fetch_one_cell("select today_war_count from mem_user_schedule where uid = $uid");
	if (empty($todayWarCont))$todayWarCont=0;
	if ($todayWarCont>=$battlecfg['maxdaycount']){
		throw new Exception($GLOBALS['battle']['today_war_count_reach_limit']);
	}

	$currentuser=sql_fetch_one("select honour,name from sys_user where uid='$uid'  ");
	////$currenthonour=0;


	$currenthonour=$currentuser['honour'];
	$username=$currentuser['name'];
	if($currenthonour<0){
		throw new Exception($GLOBALS['battle']['honour_invalid']);
	}
	//等级检查
	if($level>$battlecfg['maxlevel']){
		$level=$battlecfg['maxlevel'];
	}
	if($level<1){
		$level=1;
	}

	$needTaoFa=0;
	if($level>10){
		$needTaoFa=$level-10;
		//11级以上 要有足够的讨伐令
		if(!checkGoodsCount($uid,137,$needTaoFa)){
			throw new Exception($GLOBALS['battle']['no_enough_taofa']);
		}

	}
	//人数不能大于最大值
	if($people>$battlecfg['maxpeople']){
		$people=$battlecfg['maxpeople'];
	}
	if($people<1){
		$people=1;
	}


	$cfgbattlecitys=0;
	//生成战场信息		
	$now=time();
	$endtime=$now+$battlecfg['maxtime'];
	$result=sql_query("insert into sys_user_battle_field (bid,createuid,level,maxpeople,state,starttime,endtime,type) values('$bid','$uid','$level','$battlecfg[maxpeople]',0,$now,$endtime,'$battlecfg[type]')");
	if($result){
		$battlefieldid = sql_fetch_one_cell("select LAST_INSERT_ID()");
		//写入sys_battle_city
		$currentbattlecityinfo=array();

		$cfgbattlecitys=sql_fetch_rows("select * from cfg_battle_city where bid='$bid'");
		foreach($cfgbattlecitys as $cfgbattlecity){

			$cid=battleid2cid($battlefieldid,$cfgbattlecity['xy']);
			//生成城市
			sql_query("insert into sys_battle_city (cid,battlefieldid,nextxy,name,uid,unionid,`drop`,rate,xy,image,winpoint,losepoint) values($cid,$battlefieldid,'$cfgbattlecity[nextxy]','$cfgbattlecity[name]',0, '$cfgbattlecity[unionid]', '$cfgbattlecity[drop]', '$cfgbattlecity[rate]','$cfgbattlecity[xy]','$cfgbattlecity[image]','$cfgbattlecity[winpoint]','$cfgbattlecity[losepoint]')");
			$battlecityinfo=array("image"=>$cfgbattlecity['image'],"cid"=>$cid,"battlefieldid"=>$battlefieldid,"nextxy"=>$cfgbattlecity["nextxy"],"name"=>$cfgbattlecity["name"],"uid"=>0,"unionid"=>$cfgbattlecity['unionid'],"drop"=>$cfgbattlecity['drop'],"rate"=>$cfgbattlecity['rate'],"flag"=>$cfgbattlecity['flag'],"flagchar"=>$cfgbattlecity['flagchar'],"xy"=>$cfgbattlecity[xy]);
			$currentbattlecityinfo[]=$battlecityinfo;
			$cfgtroops=sql_fetch_rows("select * from cfg_battle_troop where bid='$bid' and xy='$cfgbattlecity[xy]' and type=0 ");
			//$cfghero = sql_fetch_one ("select * from cfg_battle_hero where hid='$cfgheroid' ");
			//将领对部队的加成只需要通过$cfghero读取
			//sql_query("insert into sys_city_hero (uid,name,npcid,sex,face,state,command_base,affairs_base,bravary_base,wisdom_base,level) values (0,'$cfghero[name]',0,'$cfghero[sex]','$cfghero[face]',10,'$cfghero[name]','$cfghero[command_base]','$cfghero[bravary_base]','$cfghero[wisdom_base]','$cfghero[level]' ) ");

			//生成驻守的部队
			foreach($cfgtroops as $cfgtroop){
				$soldiers=createSoldier($cfgtroop['npcvalue'],$cfgtroop['soldiers'],$level);
				sql_query("insert into sys_troops (cid,uid,hid,soldiers,state,`drop`,rate,battlefieldid,battleunionid,bid)  values  ($cid,0,'$cfgtroop[hid]','$soldiers',4,'$cfgtroop[drop]','$cfgtroop[rate]',$battlefieldid,'$cfgtroop[unionid]',$bid) ");

			}
			//读取以前此类战场的记录

			//更新用户的战场状态，插入当前战场荣誉


		}
		$unionid=1;
		if($bid==1001){
			//黄巾之乱，以后可能会有别的
			$unionid=1;
		}else{
			$unionid=3;
		}
		$startcid=battleid2cid($battlefieldid,$battlecfg['startcid']);
		sql_query("insert into sys_user_battle_state (uid,bid,battlefieldid,unionid,level) values($uid,$bid,$battlefieldid,$unionid,$level) on duplicate key update  battlefieldid=$battlefieldid,bid=$bid,unionid=$unionid,level=$level");
		if($needTaoFa>0){
			reduceGoods($uid,137,$needTaoFa);
		}

		if($bid==1001){
			//黄巾之乱，添加任务
			$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60000,60001,60002,60003,60004) and pretid=-1" );
			foreach($tasks as $task){
				sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
			}
		}if($bid==2001){
			//官渡，添加任务，添加point记录
			sql_query("insert into sys_battle_winpoint (battlefieldid,bid,point,unionid,nextreset) values($battlefieldid,2001,10000,3,unix_timestamp())");
			sql_query("insert into sys_battle_winpoint (battlefieldid,bid,point,unionid,nextreset) values($battlefieldid,2001,10000,4,unix_timestamp())");
			if($unionid==3){
				//添加袁绍一方任务
				$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60005,60006,60007,60008,60009,600010) and pretid=-1" );
				foreach($tasks as $task){
					sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
				}
			}else if($unionid==4){
				//添加曹操一方任务
				$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60011,60012,60013,60014,60015,60016,60017,60018) and pretid=-1" );
				foreach($tasks as $task){
					sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
				}
			}
		}
		$fieldname=$GLOBALS['battle']['union_name'][$unionid];
		playerAttendBattle($battlefieldid, $unionid, $uid, $username,$fieldname);
		$currentbattleinfo=array("battlefieldid"=>$battlefieldid,"bid"=>$bid,"createuid"=>$uid,"level"=>$level,"maxpeople"=>$people,"endtime"=>$endtime,"honour"=>$currenthonour,"name"=>$battlecfg['name'],"type"=>$battlecfg['type'],"startcid"=>$battlecfg['startcid'],"state"=>0,"unionid"=>$unionid);
		$currentbattlecityinfo=setFlags($uid,$unionid,$currentbattlecityinfo);
		$ret=array();
		$ret[]=$currentbattleinfo;
		$ret[]=$currentbattlecityinfo;
		$ret[]=getBattleStartCityInfo($bid,$unionid);
		if($bid==2001){
			//官渡之战
			$ret[]=sql_fetch_rows("select * from sys_battle_winpoint where battlefieldid=$battlefieldid");
		}
		sql_query("insert into mem_user_schedule (`uid`,`today_war_count`) values ('$uid',1) on duplicate key update `today_war_count`=today_war_count+1");

		$id=sql_fetch_one_cell("select id from cfg_task_goal where  tid=282");
		sql_query("insert into sys_user_goal(`uid`,`gid`) values ($uid,$id) on duplicate key update gid=$id");

		return $ret;
		//
	}
	unlockUser($uid);
	throw new Exception($GLOBALS['battle']['create_failed']);
	//返回当前战场的状态
}


/**
 * 取得某个据点当前的军队
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function getBattleFieldState($uid,$param){
	$ret=array();
	$battlefieldid=intval(array_shift($param));
	$unionid=intval(array_shift($param));
	$cid=intval(array_shift($param));
	$cityname=array_shift($param);
	$troops1=sql_fetch_rows("select s.*,u.name as name,u.name as `union`,h.name as hero,h.level as level from sys_troops s left join cfg_battle_union u on s.battleunionid=u.unionid left join cfg_battle_hero h on (s.hid=h.hid ) where s.uid<897  and s.battlefieldid='$battlefieldid' and s.cid='$cid' and (s.state=4 or s.state=3)");
	$troops2=sql_fetch_rows("select s.*,u2.name as name,u.name as `union`,h.name as hero,h.level as level from sys_troops s left join cfg_battle_union u on s.battleunionid=u.unionid left join sys_city_hero h on (s.hid=h.hid ) left join sys_user u2 on s.uid=u2.uid where s.uid>897  and s.battlefieldid='$battlefieldid' and s.cid='$cid' and ((s.state=4 or s.state=3) or s.targetcid=$cid)");
	$ret[]=$cityname;
	$ret[]=$cid;
	$troops3=array_merge($troops1,$troops2);
	$ret[]=$troops3;

	$cityinfo=sql_fetch_rows("select * from sys_battle_city where battlefieldid='$battlefieldid' ");
	$cityinfo=setFlags($uid,$unionid,$cityinfo);
	$ret[]=$cityinfo;
	$ret[]=sql_fetch_rows("select t.soldiers,t.cid,t.id,h.face,h.sex,h.name as heroname,h.level from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.uid='$uid' and t.battlefieldid='$battlefieldid' ");



	return $ret;

}

//从空白将领出点击出征
function getCurrentCityGroundLevel1($uid,$param){
	$cid=intval(array_shift($param));
	$groundLevel = intval(sql_fetch_one_cell("select level from sys_building where cid=$cid and bid='".ID_BUILDING_GROUND."'"));
	if(empty($groundLevel)||$groundLevel==0){
		throw new Exception($GLOBALS['battle']['not_ground']);
	}
	$ret=array();
	$ret[]=$groundLevel;
	return $ret;
}
//从
function getCurrentCityGroundLevel2($uid,$param){
	return getCurrentCityGroundLevel1($uid,$param);
}

/**
 * 军队从城池出发前往某个战场
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function startBattleTroop($uid,$param){
	$cid = array_shift($param);
	$hid = array_shift($param);
	$soldiers = array_shift($param);
	$usegoods=array_shift($param);
	$xy=array_shift($param);

	if($hid<=0){
		throw new Exception($GLOBALS['start_battle_troop']['no_hero']);
	}
	//检查目标战场的有效性
	$battleinfo=firstGetUserBattleInfo($uid);

	//官渡，要等人数够了开启才行
	if($battleinfo['bid']==2001&&$battleinfo['state']==2){
		throw new Exception($GLOBALS['battle']['battle_in_ready']);
	}


	$startcityinfo=sql_fetch_one("select * from cfg_battle_start_city where bid='$battleinfo[bid]' and xy='$xy' and unionid='$battleinfo[unionid]' ");
	if(empty($startcityinfo)){
		throw new Exception($GLOBALS['start_battle_troop']['city_not_allow']);
	}
	if($startcityinfo['needhonour']>$battleinfo['honour']){
		throw new Exception($GLOBALS['start_battle_troop']['not_enought_honour']);
	}

	$targetcid=battleid2cid($battleinfo['battlefieldid'],$xy);

	$troopcount=sql_fetch_one_cell("select count(*) from sys_troops where uid='$uid' and battlefieldid='$battleinfo[battlefieldid]' ");
	if($troopcount>=2){
		throw new Exception($GLOBALS['start_battle_troop']['max_troop']);
	}


	//检查是否有军旗，军旗的id是59
	if(($usegoods)&&!checkGoods($uid,59)) throw new Exception($GLOBALS['StartTroop']['no_flag']);
	//当前城池已经发生战斗了，就不能出发了。
	/*if (sql_check("select * from mem_world where wid='".cid2wid($cid)."' and state='1'")){
		throw new Exception($GLOBALS['StartTroop']['city_in_battle']);
		}*/

	//检查一下是暗渡陈仓状态，如果没有暗渡陈仓，中了十面埋伏的话就不能出兵了
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

	//校场的等级不够的话就不能出发
	$groundLevel = intval(sql_fetch_one_cell("select level from sys_building where cid=$cid and bid='".ID_BUILDING_GROUND."'"));

	$troopCount = intval(sql_fetch_one_cell("select count(*) from sys_troops where   uid='$uid' and cid=$cid "));
	if ($troopCount >= $groundLevel)
	{
		throw new Exception($GLOBALS['StartTroop']['insufficient_ground_level']);
	}


	/////////////// need test
	//$taskname = array($GLOBALS['StartTroop']['transport'],$GLOBALS['StartTroop']['send'],$GLOBALS['StartTroop']['detect'],$GLOBALS['StartTroop']['harry'],$GLOBALS['StartTroop']['occupy']);
	$forceNeed=5;
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
				throw new Exception(sprintf($GLOBALS['StartTroop']['hero_not_enough_force'],$GLOBALS['StartTroop']['goto_battle'],$forceNeed));
			}
		}
	}

	//要检查自己是不是刚使用过高级迁城令
	$lastMoveCD=intval(sql_fetch_one_cell("select last_adv_move+86400-unix_timestamp() from mem_city_schedule where cid='$cid'"));
	if($lastMoveCD>0){
		$msg=sprintf($GLOBALS['StartTroop']['adv_move_cooldown'],MakeTimeLeft($lastMoveCD));
		throw new Exception($msg);
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
		//实际军队人数<客户端传来的人数
		if ($citySoldiers[$sid]['count'] < $cnt)
		{
			throw new Exception($GLOBALS['StartTroop']['no_so_many_army']);
		}
		$soldierAllCount += $cnt;
		if ($sid == 3) $cihouCount+=$cnt;
	}

	if ($soldierAllCount <= 0) throw new Exception($GLOBALS['StartTroop']['no_soldier']);

	//不能斥候独立出征
	if (($cihouCount >= $soldierAllCount))
	throw new Exception($GLOBALS['battle']['spy_cant_alone']);

	//出征人数限制
	$groundLevelLimit = $groundLevel * 10000 * GAME_SPEED_RATE;
	$limitadd=0;
	//使用军旗
	if(!empty($usegoods))
	{
		$limitadd+=25;
	}
	$myCityInfo=sql_fetch_one("select * from sys_city where cid='$cid'");
	//名城出征人数
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

	//是否超过人数
	if ($soldierAllCount > $groundLevelLimit)
	{
		throw new Exception(sprintf($GLOBALS['StartTroop']['no_enough_ground_level'],$groundLevelLimit));
	}

	//////////////////TODO  军队速度的计算，是否应该放在最后

	//行军技巧和驾驭技巧

	//步兵速度加成
	$speedAddRate1=1+intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=12"))*0.1;
	//骑兵速度加成
	$speedAddRate2=1+intval(sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=13"))*0.05;
	//将领速度加成
	$speedAddRate3=1;
	if($hid!=0)
	{
		$speedAddRate3=1+$heroInfo['speed_add_on']*0.01;
	}


	//单程时间 ＝ 每格子距离/最慢兵种速度+宿营时间（每格距离＝60000/game_speed_rate）
	$pathLength = 162000;
	$minSpeed = 999999999;
	// TODO 可以优化和缓存
	$soldierConfig = sql_fetch_rows("select * from cfg_soldier where fromcity=1 order by sid","sid");
	foreach ($soldierConfig as $soldier)        //找到当前军队里最慢的
	{
		if (!empty($takeSoldiers[$soldier->sid]))
		{
			//除了斥候外的步兵速度加成
			if($soldier->sid<7&&$soldier->sid!=3)
			{
				$minSpeed = min($soldier->speed*$speedAddRate1*$speedAddRate3,$minSpeed);
			}
			//骑兵加成
			else
			{
				$minSpeed = min($soldier->speed*$speedAddRate2*$speedAddRate3,$minSpeed);
			}
		}
	}

	$pathNeedTime = $pathLength / $minSpeed;    //需要多少时间

	$pathNeedTime=intval(floor($pathNeedTime));

	//出征耗粮 ＝ 兵的耗粮/小时*12*单程时间   
	$foodUse = 0;
	$allpeople = 0;

	foreach ($soldierConfig as $soldier)        //找到当前军队里最慢的
	{
		if (!empty($takeSoldiers[$soldier->sid]))
		{
			$foodUse += $soldier->food_use * $takeSoldiers[$soldier->sid];

			$allpeople += $soldier->people_need * $takeSoldiers[$soldier->sid];
		}
	}

	$now = sql_fetch_one_cell("select unix_timestamp()");
	$foodRate = ceil(($battleinfo['endtime']-$now)/3600);
	if($foodRate<=0)
	$foodRate=1;
	//    if ($task == 4) $foodRate = 5;    //现在不用了
	$foodUse *= $foodRate ;
	//军队行程耗粮量
	//检查一下当前城池是否有足够的军粮，直接吃掉
	$food = sql_fetch_one_cell("select food from mem_city_resource where cid='$cid'");
	if ($food < $foodUse) throw new Exception($GLOBALS['StartTroop']['no_enough_food']);

	//  throw new Exception($food.":".$foodUse);


	if ($hid != 0)  //让将领置成出征状态
	{
		sql_query("update sys_city_hero set state=2 where hid='$hid'");
		sql_query("update mem_hero_blood set `force`=GREATEST(0,`force`-$forceNeed) where hid='$hid'");
	}
	//减资源
	addCityResources($cid,0,0,0,-$foodUse,0,0);
	//减兵员
	addCitySoldiers($cid,$takeSoldiers,false);
	//减军旗
	if($usegoods) reduceGoods($uid,59,1);

	$troopid = sql_insert("insert into sys_troops (`uid`,`cid`,`hid`,`task`,`state`,`starttime`,`pathtime`,`endtime`,`soldiers`,`resource`,`people`,`fooduse`,`battlefieldid`,`battleunionid`,`targetcid`,`startcid`,bid) values ('$uid','$cid','$hid',7,'0',unix_timestamp(),'$pathNeedTime',unix_timestamp()+30,'$soldiers','0','$allpeople','$foodUse','$battleinfo[battlefieldid]','$battleinfo[unionid]','$targetcid','$cid','$battleinfo[bid]')");
	//设置当前军队的战术为玩家当前战述
	$tactics = sql_fetch_one("select * from sys_user_tactics where uid='$uid'");
	if ($tactics)
	{
		sql_query("replace into sys_troop_tactics (`troopid`,`plunder`,`invade`,`patrol`,`field`) values ('$troopid','$tactics[plunder]','$tactics[invade]','$tactics[patrol]','$tactics[field]')");
	}

	$day = sql_fetch_one_cell("select floor((unix_timestamp()-unix_timestamp('2008-09-08 19:00:00'))/86400)");
	if (($day >= 0)&&($day < 4))
	{
		if (sql_check("select * from evt_zq_yt where day='$day' and wid='$battleinfo[battlefieldid]'"))
		{
			sql_query("insert into evt_zq_ytlog (time,day,wid,uid,cid) values (unix_timestamp(),'$day','$battleinfo[battlefieldid]','$uid','$cid')");
		}
	}

	$ret=array();
	$ret[]=$GLOBALS['StartTroop']['succ'];
	return $ret;
}

function getCityBattleTroopDetail($uid,$param){
	$troopid=intval(array_shift($param));
	$troop = sql_fetch_one("select id,cid,uid,soldiers,hid,targetcid,battleunionid from sys_troops where id='$troopid'");
	if (empty($troop)) throw new Exception($GLOBALS['callBackTroop']['invalid_army']);
	$info=array();
	$info['id']=$troop['id'];
	$info['uid']=$troop['uid'];
	$info['soldiers']=$troop['soldiers'];
	$info['unionname']=sql_fetch_one_cell("select name from cfg_battle_union where unionid='$troop[battleunionid]'");
	if($troop['uid']<=897){
		//君主的
		$info['name']=$info['unionname'];
		$info['heroinfo']=	sql_fetch_one("select name,level from cfg_battle_hero where hid=$troop[hid]");
	}else{
		$info['name']=sql_fetch_one_cell("select name from sys_user where uid='$troop[uid]' ");
		$info['heroinfo']=	sql_fetch_one("select name,level from sys_city_hero where hid=$troop[hid]");
	}

	$buffers = sql_fetch_rows("select buftype,bufparam from mem_troops_buffer where troopid='$troopid' ");

	if(!empty($buffers)){
		$info['buffers']=$buffers;
	}

	$ret=array();
	$ret[]=$info;
	return $ret;
}

/**
 * 读取一只军队的详细信息
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function getBattleTroopDetail($uid,$param){
	$troopid=intval(array_shift($param));

	$troop = sql_fetch_one("select id,cid,uid,soldiers,hid,targetcid,battleunionid,pathtime,endtime,state from sys_troops where id='$troopid'");

	if (empty($troop)) throw new Exception($GLOBALS['callBackTroop']['invalid_army']);
	$info=array();

	$info['id']=$troop['id'];
	$info['uid']=$troop['uid'];
	$info['soldiers']=$troop['soldiers'];
	$info['pathtime']=$troop['pathtime'];
	$info['endtime']=$troop['endtime'];
	$info['state']=$troop['state'];

	$info['unionname']=sql_fetch_one_cell("select name from cfg_battle_union where unionid='$troop[battleunionid]'");

	if($troop['uid']<=897){
		//君主的
		$info['name']=$info['unionname'];
		$info['heroinfo']=	sql_fetch_one("select name,level from cfg_battle_hero where hid=$troop[hid]");
	}else{
		$info['name']=sql_fetch_one_cell("select name from sys_user where uid='$troop[uid]' ");
		$info['heroinfo']=	sql_fetch_one("select name,level from sys_city_hero where hid=$troop[hid]");
	}

	//前往派遣或者前往攻击
	if($troop['state']==0||$troop['state']==1||$troop['state']==2||$troop['state']==3){
		$info['targetcityname']=sql_fetch_one_cell("select name from sys_battle_city where cid='$troop[targetcid]' ");
	}else if ($troop['state']==4){
		$info['targetcityname']=sql_fetch_one_cell("select name from sys_battle_city where cid='$troop[cid]'");
	}
	else {
		$info['targetcityname']="--";
	}

	if($troop['state']==0){
		$info['state']=$GLOBALS['battle']['state_0'];
	}else if($troop['state']==1){
		$info['state']=$GLOBALS['battle']['state_1'];
	}else if($troop['state']==2){
		$info['state']=$GLOBALS['battle']['state_2'];
		$info['pathtime']="--";
		$info['endtime']="--";
	}else if($troop['state']==3){
		$info['state']=$GLOBALS['battle']['state_3'];
		$info['pathtime']="--";
		$info['endtime']="--";
	}else if($troop['state']==4){
		$info['state']=$GLOBALS['battle']['state_4'];
		$info['pathtime']="--";
		$info['endtime']="--";
	}

	$buffers = sql_fetch_rows("select buftype,bufparam from mem_troops_buffer where troopid='$troopid' ");

	if(!empty($buffers)){
		$info['buffers']=$buffers;
	}

	$ret=array();
	$ret[]=$info;
	return $ret;
}

/**
 * 军队撤离战场
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function callBackArmy($uid,$param){
	$troopid=intval(array_shift($param));
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid'");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	$troopstate=$troop['state'];
	if($troopstate!=4){
		throw new Exception($GLOBALS['battle']['troop_not_stay']);
	}

	//援军返还
	$SOLDIER_PEOPLE =array(0,1,1,1,1,1,2,3,6,4,5,10,8);
	//返还援军
	$reinforce=sql_fetch_one_cell("select group_concat(sid,',',count) as soldiers from sys_battle_reinforce where troopid='$troop[id]' ");
	//返还的战场荣誉
	$backhonour=0;
	//返还后部队是否还有士兵
	$emptytroop=true;
	//返还后的士兵
	$newsoldiers="";
	//返还后的士兵兵种数目
	$newsoldierstypecount=0;
	if(!empty($reinforce)){
		//剩下的兵种map
		$orisoldiersmap=getSoldierMap($troop['soldiers']);
		//原来的士兵
		//$orisoldiers=$troop['soldiers'];
		//援兵的兵种
		$reinforcearray=explode(",",$reinforce);

		$reinforcearraycount=count($reinforcearray)/2;

		//计算每个兵种的返还，如果返还后小于0，则视为0。
		for($i=0;$i<$reinforcearraycount;$i++){
			$sid=array_shift($reinforcearray);
			//调遣过的援军数目
			$reinforcemapcount=array_shift($reinforcearray);
			//现在剩下的数目
			$oricount=$orisoldiersmap[$sid];
			$newcount=0;
			if(!empty($oricount)){
				$newcount=$oricount-$reinforcemapcount;
				if($newcount<0){
					$newcount=0;
				}
				//该兵种返还的数值
				$backcount=$oricount-$newcount;
				$backhonour+=floor($backcount*$SOLDIER_PEOPLE[$sid]/100);
			}
			$orisoldiersmap[$sid]=$newcount;
		}
		if(!$emptytroop){
			$newsoldiers=$newsoldierstypecount.$newsoldiers;
		}
		$newsoldiers=getSoldierString($orisoldiersmap);

	}else{
		$emptytroop=false;
		$newsoldiers=$troop['soldiers'];
	}
	//$newsoldiers=getSoldierString($orisoldiersmap);

	if($newsoldiers!=""){
		//所有军队设置为返回状态
		$result=sql_query("update sys_troops set starttime=unix_timestamp(),task=7,state=1,soldiers='$newsoldiers',endtime=unix_timestamp()+30,battlefieldid=0,cid=startcid where id='$troop[id]'");
		if($result){
			if($backhonour>0){
				sql_query("update sys_user set honour=honour+$backhonour where uid='$uid'");
			}
		}
	}else{
		//返还援军后军队为0，则直接把将领扔回城。
		$result=sql_query("update sys_city_hero set state=0 where hid='$troop[hid]' ");
		//$result=
		if($result){
			//删除军队
			sql_query("delete from sys_troops where id='$troop[id]'");
			if($backhonour>0){
				sql_query("update sys_user set honour=honour+$backhonour where uid='$uid'");
					
			}
		}
	}


	//$result=sql_query("update sys_troops set task=7,state=1,endtime=unix_timestamp()+pathtime,battlefieldid=0,cid=startcid where id='$troopid'");
	$ret=array();
	if($result){
		$ret[]=$GLOBALS['battle']['callback_succ'];
		resetBattleFieldUid($troop['cid']);
	}
	else
	$ret[]=$GLOBALS['battle']['callback_fail'];
	return $ret;
}


function callBackToField($uid,$param){
	$troopid=intval(array_shift($param));
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid'");
	$troopstate= $troop['state'];
	if(empty($troop) ){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}

	if($troopstate!=0){
		throw new Exception($GLOBALS['battle']['troop_not_ahead']);
	}

	if ($troop["cid"]==$troop["startcid"]){
		//援军返还
		$SOLDIER_PEOPLE =array(0,1,1,1,1,1,2,3,6,4,5,10,8);
		//返还援军
		$reinforce=sql_fetch_one_cell("select group_concat(sid,',',count) as soldiers from sys_battle_reinforce where troopid='$troop[id]' ");
		//返还的战场荣誉
		$backhonour=0;
		//返还后部队是否还有士兵
		$emptytroop=true;
		//返还后的士兵
		$newsoldiers="";
		//返还后的士兵兵种数目
		$newsoldierstypecount=0;
		if(!empty($reinforce)){
			//剩下的兵种map
			$orisoldiersmap=getSoldierMap($troop['soldiers']);
			//原来的士兵
			//$orisoldiers=$troop['soldiers'];
			//援兵的兵种
			$reinforcearray=explode(",",$reinforce);
	
			$reinforcearraycount=count($reinforcearray)/2;
	
			//计算每个兵种的返还，如果返还后小于0，则视为0。
			for($i=0;$i<$reinforcearraycount;$i++){
				$sid=array_shift($reinforcearray);
				//调遣过的援军数目
				$reinforcemapcount=array_shift($reinforcearray);
				//现在剩下的数目
				$oricount=$orisoldiersmap[$sid];
				$newcount=0;
				if(!empty($oricount)){
					$newcount=$oricount-$reinforcemapcount;
					if($newcount<0){
						$newcount=0;
					}
					//该兵种返还的数值
					$backcount=$oricount-$newcount;
					$backhonour+=floor($backcount*$SOLDIER_PEOPLE[$sid]/100);
				}
				$orisoldiersmap[$sid]=$newcount;
			}
			if(!$emptytroop){
				$newsoldiers=$newsoldierstypecount.$newsoldiers;
			}
			$newsoldiers=getSoldierString($orisoldiersmap);
	
		}else{
			$emptytroop=false;
			$newsoldiers=$troop['soldiers'];
		}
		//$newsoldiers=getSoldierString($orisoldiersmap);
	
		if($newsoldiers!=""){
			
		}
		//返还荣誉
		if($backhonour>0){
			sql_query("update sys_user set honour=honour+$backhonour where uid='$uid'");
		}
	
	
		$result=sql_query("update sys_troops set state=1,soldiers='$newsoldiers',endtime=unix_timestamp() - starttime + unix_timestamp(),starttime=unix_timestamp() where id='$troopid'");
	}
	else{
		$result=sql_query("update sys_troops set state=1,endtime=unix_timestamp() - starttime + unix_timestamp(),starttime=unix_timestamp() where id='$troopid'");
	}
	
	$ret=array();
	if($result)
	$ret[]=$GLOBALS['battle']['callback_succ'];
	else
	$ret[]=$GLOBALS['battle']['callback_fail'];
	return $ret;
}


/**
 * 战场派遣
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function battleTroopDispatch($uid,$param){
	$troopid=intval(array_shift($param));
	$targetcid=intval(array_shift($param));
	$heroname=array_shift($param);
	$targetName=array_shift($param);
	$battlefieldinfo=ifFrozeExit($uid);
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid' and battlefieldid='$battlefieldinfo[battlefieldid]' ");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	if($troop['state']!=4){
		throw new Exception($GLOBALS['battle']['troop_not_stay']);
	}


	//检查目的据点
	$targetcity=sql_fetch_one("select * from sys_battle_city where cid=$targetcid" );
	if(empty($targetcity)){
		throw new Exception($GLOBALS['battle']['city_not_exist']);
	}

	//检查出发据点
	$origincity=sql_fetch_one("select * from sys_battle_city where cid='$troop[cid]'" );
	if(empty($origincity)){
		throw new Exception($GLOBALS['battle']['city_not_exist']);
	}

	//是否是我方阵营
	/*$unionid=$troop['battleunionid'];
	 if($targetcity['unionid']!=$unionid&&$targetcity['unionid']>0){
		throw new Exception($GLOBALS['battle']['not_same_union']);
		}*/

	//是否连通
	if(!canGoto($targetcid,$origincity['nextxy'])){
		throw new Exception($GLOBALS['battle']['city_cannot_goto']);
	}

	$result=sql_query("update sys_troops set starttime=unix_timestamp(),state=0,targetcid='$targetcid',task=8,endtime=unix_timestamp()+pathtime where id='$troopid' ");
	if($result){
		$ret[]=$GLOBALS['StartTroop']['succ'];
		//$fieldname=$GLOBALS['battle']['union_name'][$battlefieldinfo['unionid']];
		//playTroopLeave($battlefieldinfo['battlefieldid'], $battlefieldinfo['unionid'], $fieldname, $targetName,$heroname)	;
		resetBattleFieldUid($troop['cid']);
	}
	else
	$ret[]=$GLOBALS['StartTroop']['fail'];
	return $ret;

}

//点击攻击按钮
function getInfoForBattleArmyAttack($uid,$param){
	$troopid=intval(array_shift($param));
	$targettroopid=intval(array_shift($param));
	$targetname=array_shift($param);
	$troop = sql_fetch_one("select t.cid,t.id,t.uid,t.soldiers,t.pathtime,t.state,h.name as heroname,h.level from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.id='$troopid' and t.uid='$uid'");
	if (empty($troop)) throw new Exception($GLOBALS['battle']['troop_not_exist']);
	if($troop['state']!=4){
		throw new Exception($GLOBALS['battle']['troop_not_stay']);
	}

	$targettroop = sql_fetch_one("select cid,id,uid,soldiers,hid,state from sys_troops where id='$targettroopid'");
	if (empty($targettroop)) throw new Exception($GLOBALS['battle']['troop_not_exist']);
	if($troop["cid"]==$targettroop["cid"]){
		//同据点攻击,30秒后开始 对手不能在攻击状
		if($targettroop['state']!=4){
			throw new Exception($GLOBALS['battle']['troop_in_same_city_not_stay']);
		}
		$troop["pathtime"]=10;
	}else{
		$targettroop['soldiers']="";
	}

	if($targettroop["uid"]>0){
		//被攻击的部队是玩家部队
		$heroinfo = sql_fetch_one("select name,level from sys_city_hero where hid='$targettroop[hid]'");
		$targettroop['heroname']=$heroinfo['name'];
		$targettroop['level']=$heroinfo['level'];
	}else{
		//防守方的部队
		//被攻击的部队是玩家部队
		$heroinfo = sql_fetch_one("select name,level from cfg_battle_hero where hid='$targettroop[hid]'");
		$targettroop['heroname']=$heroinfo['name'];
		$targettroop['level']=$heroinfo['level'];
	}

	$ret[]=$troop;
	$ret[]=$targettroop;
	$ret[]=$targetname;

	return $ret;

}


//点击攻击按钮
function battlePatrol($uid,$param){
	$troopid=intval(array_shift($param));
	$targettroopid=intval(array_shift($param));

	//先检查有没有信鸽
	if(!checkGoods($uid,140,1)){
		throw new Exception($GLOBALS['battle']['not_enought_gezi']);
	}

	$troop = sql_fetch_one("select t.cid,t.id,t.uid,t.soldiers,t.pathtime,t.state,h.name as heroname,h.level from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.id='$troopid' and t.uid='$uid'");
	if (empty($troop)) {
		unlockuser($uid);
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	$targettroop = sql_fetch_one("select cid,id,uid,soldiers,hid,bid,state from sys_troops where id='$targettroopid'");
	if (empty($targettroop)) {
		unlockuser($uid);
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	if($targettroop["uid"]>0){
		//被攻击的部队是玩家部队
		$heroinfo = sql_fetch_one("select name,level from sys_city_hero where hid='$targettroop[hid]'");
		$targettroop['heroname']=$heroinfo['name'];
		$targettroop['level']=$heroinfo['level'];
	}else{
		//防守方的部队
		//被攻击的部队是玩家部队
		$heroinfo = sql_fetch_one("select name,level from cfg_battle_hero where hid='$targettroop[hid]'");
		$targettroop['heroname']=$heroinfo['name'];
		$targettroop['level']=$heroinfo['level'];
	}

	$xy=$targettroop['cid']%1000;
	$targetcityname=sql_fetch_one_cell("select name from cfg_battle_city where bid='$targettroop[bid]' and xy=$xy ");

	$msg=sprintf($GLOBALS['battle']['patrol_report'],$targetcityname,$targettroop['heroname'],$targettroop['level']);
	$soldiers=$targettroop['soldiers'];

	$soldiersarray=explode(",", $soldiers);
	$soldierscount=array_shift($soldiersarray);
	for($i=0;$i<$soldierscount;$i++){
		$sid=array_shift($soldiersarray);
		$count=array_shift($soldiersarray);
		$msg.=$GLOBALS['battle']['patrol_report_soldier'][$sid]." ".$count."<br/>";
	}


	//发战报
	sendReport($uid,0,45,0,0,$msg);

	reduceGoods($uid,140,1);

	throw new Exception($GLOBALS['battle']['patrol_report_suc']);
	//return $ret;
}

//计算目标是否连通
function canGoto($targetcid,$nextcids){
	//return true;
	$targetxy=$targetcid%1000;
	$nextcidarray = explode(",", $nextcids);
	$nextcount=array_shift($nextcidarray);
	$cangoto=false;
	for($i=0;$i<$nextcount;$i++){
		$temp=array_shift($nextcidarray);
		if($temp==$targetxy){
			$cangoto=true;
			break;
		}else if($temp==(0-$targetxy)){
			throw new Exception($GLOBALS['battle']['road_not_opens']);
		}
	}
	return $cangoto;
}

//选择一只军队，只更新他的当前位置
function selectOneTroop($uid,$param){
	$troopid=intval(array_shift($param));
	$troop = sql_fetch_one("select id,cid from sys_troops where id= '$troopid' and uid='$uid'");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	$ret[]=array();
	$ret[]=$troop;
	return $ret;
}

function getInfoForBattleArmySend($uid,$param){
	$troopid=intval(array_shift($param));
	$targetid=intval(array_shift($param));
	$targetname=array_shift($param);
	$troop = sql_fetch_one("select t.id,t.uid,t.soldiers,t.pathtime,t.state,h.name as heroname,h.level,t.cid from sys_troops t left join sys_city_hero h on t.hid=h.hid where t.id='$troopid' and t.uid='$uid'");
	if (empty($troop)) throw new Exception($GLOBALS['battle']['troop_not_exist']);

	if($troop['state']!=4){
		throw new Exception($GLOBALS['battle']['troop_not_stay']);
	}
	if($troop['cid']==$targetid){
		throw new Exception($GLOBALS['battle']['troop_in_same_city']);
	}
	$troop['targetname']=$targetname;
	$troop['targetid']=$targetid;

	$ret[]=$troop;
	return $ret;
}


function getQuitResult($uid,$param){
	$battleinfo=sql_fetch_one("select f.winner,f.state,s.unionid from sys_user_battle_state s left join sys_user_battle_field f on s.battlefieldid=f.id where s.uid=$uid");	
	$ret=array();
	if($battleinfo['state']==2){
		$ret[]=2;
	}else if($battleinfo['winner']==$battleinfo['unionid']){
		$ret[]=1;
	}else if($battleinfo['winner']==-1){
		$ret[]=-1;
	}else{
		$ret[]=0;
	}
	return $ret;
}
/**
 * 中途退出战场，遣返援军，重新计算战场荣誉。
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function quitBattle($uid,$param){
	$nick=array_shift($param);
	$battleinfo=sql_fetch_one("select s.unionid,s.battlefieldid,u.honour,f.starttime,f.level,f.state,f.winner,f.bid,f.type,f.createuid from sys_user_battle_state s left join sys_user_battle_field f on s.battlefieldid=f.id left join sys_user u on s.uid=u.uid where s.uid=$uid");
	if(empty($battleinfo)){
		//你已经不在战场中	
		throw new Exception($GLOBALS['battle']['user_not_in_battle']);
	}


	//清理该用户的数据	
	$troops=sql_fetch_rows("select * from sys_troops where uid='$uid' and battlefieldid='$battleinfo[battlefieldid]' ");
	foreach($troops as $troop){
		if($troop['state']!=4){
			throw new Exception($GLOBALS['battle']['troop_in_fight_when_quit']);
		}
	}
	$level=$battleinfo["level"];
	//退出后的战场荣誉
	$orihonour=$battleinfo['honour'];
	//援军返还荣誉
	$newhonour=0;

	$SOLDIER_PEOPLE =array(0,1,1,1,1,1,2,3,6,4,5,10,8);
	foreach($troops as $troop){
		//返还援军
		$reinforce=sql_fetch_one_cell("select group_concat(sid,',',count) as soldiers from sys_battle_reinforce where troopid='$troop[id]' ");
		//返还的战场荣誉
		$backhonour=0;
		//返还后部队是否还有士兵
		$emptytroop=true;
		//返还后的士兵
		$newsoldiers="";
		//返还后的士兵兵种数目
		$newsoldierstypecount=0;
		if(!empty($reinforce)){
			//剩下的兵种map
			$orisoldiersmap=getSoldierMap($troop['soldiers']);
			//原来的士兵
			//$orisoldiers=$troop['soldiers'];

			//援兵的兵种
			$reinforcearray=explode(",",$reinforce);

			$reinforcearraycount=count($reinforcearray)/2;

			//计算每个兵种的返还，如果返还后小于0，则视为0。
			for($i=0;$i<$reinforcearraycount;$i++){
				$sid=array_shift($reinforcearray);
				//调遣过的援军数目
				$reinforcemapcount=array_shift($reinforcearray);
				//现在剩下的数目
				$oricount=$orisoldiersmap[$sid];
				$newcount=0;
				if(!empty($oricount)){
					$newcount=$oricount-$reinforcemapcount;
					if($newcount<0){
						$newcount=0;
					}
					//该兵种返还的数值
					$backcount=$oricount-$newcount;
					$backhonour+=floor($backcount*$SOLDIER_PEOPLE[$sid]/100);
				}
				$orisoldiersmap[$sid]=$newcount;
			}
			if(!$emptytroop){
				$newsoldiers=$newsoldierstypecount.$newsoldiers;
			}
			$newsoldiers=getSoldierString($orisoldiersmap);

		}else{
			$emptytroop=false;
			$newsoldiers=$troop['soldiers'];
		}
		//$newsoldiers=getSoldierString($orisoldiersmap);
		$newhonour+=$backhonour;
		if($newsoldiers!=""){
			//所有军队设置为返回状态
			$result=sql_query("update sys_troops set starttime=unix_timestamp(),task=7,state=1,soldiers='$newsoldiers',endtime=unix_timestamp()+pathtime,battlefieldid=0,cid=startcid where id='$troop[id]'");
		}else{
			//返还援军后军队为0，则直接把将领扔回城。
			sql_query("delete from sys_troops  where id='$troop[id]' ");
			sql_query("update sys_city_hero set state=0 where hid='$troop[hid]' ");
		}
		resetBattleFieldUid($troop['cid']);
	}

	$result=$battleinfo['winner'];
	$quittype=0;
	//胜利，失败，或者逃跑扣除的荣誉


	$winhonour=0;


	$canwin=true;
	//黄巾 要检查刷子
	if($battleinfo['unionid']==1){
		//看是不是胜利推出过
		if(!sql_check("select * from log_battle_honour where uid='$uid' and battlefieldid='$battleinfo[battlefieldid]' and result=0 "))
			$canwin=true;
		if($canwin){
			//如果没有胜利退出过，要参与完成主线任务，否则不能胜利		
			$goalsid=sql_fetch_one_cell("select group_concat(id) from cfg_task_goal where tid in (60000,60001,60002) ");
			if(sql_fetch_one_cell("select count(*) from sys_user_goal where uid=$uid and gid in ($goalsid) ")!=3){
				//三个主线任务都完成
				$canwin=false;
			}
		}
	}


	if($result==$battleinfo['unionid']){
		//胜利退出
		$winhonour=$level*$level*8;
		if(!$canwin){
			$winhonour=0;
		}
	}else if($result==-1){
		//中途逃跑
		$winhonour=0-$level*$level*20;
		$quittype=2;
	}else{
		//失败退出
		$winhonour=0-$level*$level*5;
		$quittype=1;
	}
	
	if(defined('ADULT') )
	{
		$punish = punishNotAdult($uid);
		if($quittype !=1 && $quittype!=2){
			$newhonour = intval($newhonour * $punish);
			$winhonour = intval($winhonour * $punish);
		}
	}

	$nowhonour=$orihonour+$newhonour+$winhonour;
	if ($battleinfo["state"] == 2) //如果是准备状态的
	$quittype=3;
	if ($quittype!=3)
	sql_query("update sys_user set honour=$nowhonour where uid='$uid'");

	if($uid!=$battleinfo['createuid'])
	autoTransferCaptain($uid);

	
	
	//$battleId = sql_fetch_one_cell("select  battlefieldid from sys_user_battle_state where uid='$uid'");
	
	sql_query("delete from sys_user_battle_state where uid='$uid'");
	
	if($battleinfo['bid']==1001){
		//如果是黄巾，没人了就直接给关掉
		$peopleCount = sql_fetch_one_cell("select count(*) from sys_user_battle_state where battlefieldid='$battleinfo[battlefieldid]'");
		if ($peopleCount==0){
	   		sql_query("update sys_user_battle_field set state=1 where id='$battleinfo[battlefieldid]'");
		}
	}

	//勋章数目
	$xunzhangcount=0;
	//发送消息
	$msg="";
	$battlefieldname=$GLOBALS['battle']['name'][$battleinfo['bid']];
	//勋章名
	$metalname=$GLOBALS['battle']['metal_name'][$battleinfo['unionid']];
	$metalgid=$GLOBALS['battle']['metal_gid'][$battleinfo['unionid']];
	$taskgroup=$GLOBALS['battle']['task_group'][$battleinfo['unionid']];

	$tasks=sql_fetch_one_cell(" select group_concat(id) from cfg_task where `group` in ($taskgroup) " );
	if(!empty($tasks)){
		sql_query("delete from sys_user_task where uid=$uid and tid in ($tasks)");
		$taskgoals=sql_fetch_one_cell(" select group_concat(id) from cfg_task_goal where tid in ($tasks) " );
		sql_query("delete from sys_user_goal where uid=$uid and gid in ($taskgoals)");
	}

	$msg="";
	if($quittype==0){
		//如果胜利，加勋章
		$xunzhangcount=$battleinfo["level"];
		if($uid==$battleinfo['createuid']){
			//创建者可多获得勋章
			if($battleinfo["level"]>10)
			$xunzhangcount+=$battleinfo["level"]-10;
		}
		if(!$canwin){
			$xunzhangcount=0;
		}
		sql_query("insert into sys_things (uid,tid,count) values ($uid,$metalgid,$xunzhangcount) on duplicate key update count=count+$xunzhangcount");
		$msg=sprintf($GLOBALS['battle']['quit_win'],$battlefieldname,$winhonour,$newhonour,$metalname,$xunzhangcount,$nowhonour);
		
		try {
			$msg =$msg."<br>".$ext_msg;
		} catch ( Exception $e ) {
			error_log ( $e );
		}		
	}else if($quittype==1){
		$msg=sprintf($GLOBALS['battle']['quit_lose'],$battlefieldname,(0-$winhonour),$newhonour,$nowhonour);
	}else if($quittype==2){
		$msg=sprintf($GLOBALS['battle']['quit_leave'],$battlefieldname,(0-$winhonour),$newhonour,$nowhonour);
	}else if($quittype==3){
		$msg=sprintf($GLOBALS['battle']['quit_leave_notstartbattle'],$battlefieldname,$orihonour);
	}

	//荣誉和勋章log
	//$thishonour=$nowhonour-$orihonour;
	if ($quittype !=3)
	sql_query("insert into log_battle_honour (uid,battleid,battlefieldid,starttime,quittime,result,honour,metal,unionid,level) values ($uid,'$battleinfo[bid]','$battleinfo[battlefieldid]','$battleinfo[starttime]',unix_timestamp(),$quittype,$nowhonour,$xunzhangcount,'$battleinfo[unionid]','$battleinfo[level]') ");

	$unionname=$GLOBALS['battle']['union_name'][$battleinfo['unionid']];
	playerExitBattle($battleinfo['battlefieldid'], $battleinfo['unionid'], $uid, $nick,$unionname);

	//发战报
	sendReport($uid,0,40,0,0,$msg);

	$ret=array();
	$ret[]=$GLOBALS['battle']['exit_suc'];



	return $ret;
	//战场荣誉减少
}

/**
 * 调遣援军
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 * @return unknown
 */
function callArmy($uid,$param){

	$troopid=intval(array_shift($param));
	$sid=intval(array_shift($param));
	$count=intval(array_shift($param));
	$battlefieldinfo=ifFrozeExit($uid);

	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid' and battlefieldid='$battlefieldinfo[battlefieldid]' ");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	if($troop['state']==3){
		throw new Exception($GLOBALS['battle']['troop_in_fight']);
	}
	if($troop['state']==1){
		throw new Exception($GLOBALS['battle']['troop_in_back_no_call']);
	}
	
	if($troop['task']==7&&($troop['state']==0||$troop['state']==1)){
		throw new Exception($GLOBALS['battle']['troop_in_forward_no_call']);
	}
	$SOLDIER_PEOPLE =array(0,1,1,1,1,1,2,3,6,4,5,10,8);
	$SOLDIER_VALUE=array(0,22.5 ,30.5 ,69.5 ,90 ,135 ,140 ,285 ,875 ,297.5 , 1000 , 1375 ,2900 );

	$count1=$battlefieldinfo["honour"]*100/$SOLDIER_PEOPLE[$sid];
	$allpeople=getSoldierPeople($troop["soldiers"]);
	$count2=100000-$allpeople;

	$maxCall=min($count1,$count2);

	if($count>$maxCall){
		throw new Exception($GLOBALS['battle']['call_army_max']);
	}

	$callPeople=$count*$SOLDIER_PEOPLE[$sid];

	$needHonour=ceil($callPeople/100);
	if($needHonour>$battlefieldinfo["honour"]){
		throw new Exception($GLOBALS['battle']['call_army_not_enough_honour']);
	}

	$yuanjun=0;
	$value = $count*$SOLDIER_VALUE[$sid];

	if($value<=100000)
	$yuanjun=1;
	else if($value<=1000000)
	$yuanjun=2;
	else if($value<=10000000)
	$yuanjun=3;
	else if($value<=100000000)
	$yuanjun=4;
	else $yuanjun=5;

	if(!checkGoodsCount($uid,135,$yuanjun)){
		throw new Exception($GLOBALS['battle']['call_army_not_enough_yuanjunling']);
	}
	$ret=array();
	$newsoldiers=enhanceArmy($troop["id"],$troop["soldiers"],$sid,$count);
	if($newsoldiers){
		reduceGoods($uid,135,$yuanjun);
		//减去战场荣誉

		sql_query("update sys_user set honour=honour-$needHonour where uid='$uid'");
		$ret[]=1;
		$ret[]=$troopid;
		$ret[]=$newsoldiers;
		$ret[]=$battlefieldinfo["honour"]-$needHonour;
		$ret[]=$GLOBALS['battle']['call_army_suc'];
	}else{
		$ret[]=0;
		$ret[]=$GLOBALS['battle']['call_army_fail'];
		unlockUser($uid);
	}

	return $ret;

}

/**
 * 加速行军
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 */
function fasterArmy($uid,$param){
	$troopid=intval(array_shift($param));
	$battlefieldinfo=ifFrozeExit($uid);

	$troop = sql_fetch_one("select *,unix_timestamp() as now from sys_troops where id='$troopid' and uid='$uid' and battlefieldid='$battlefieldinfo[battlefieldid]' ");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	if($troop['state']!=0&&$troop['state']!=1){
		throw new Exception($GLOBALS['battle']['troop_not_in_move']);
	}
	
	if($troop['endtime']-$troop['now']<10){
		throw new Exception($GLOBALS['battle']['troop_not_need_faster']);
	}

	if(!checkGoods($uid,136,1)){
		throw new Exception($GLOBALS['battle']['call_army_not_enough_jixingjun']);
	}

	
	$result=sql_query("update sys_troops set endtime=unix_timestamp()+10 where id='$troopid'");
	$ret=array();
	$troopinfo=sql_fetch_one("select pathtime,endtime,state from sys_troops where id='$troopid'");


	if($troopinfo['state']==0){
		$troopinfo['state']=$GLOBALS['battle']['state_0'];
	}else if($troopinfo['state']==1){
		$troopinfo['state']=$GLOBALS['battle']['state_1'];
	}else if($troopinfo['state']==2){
		$troopinfo['state']=$GLOBALS['battle']['state_2'];
		$troopinfo['pathtime']="--";
		$troopinfo['endtime']="--";
	}else if($troopinfo['state']==3){
		$troopinfo['state']=$GLOBALS['battle']['state_3'];
		$troopinfo['pathtime']="--";
		$troopinfo['endtime']="--";
	}else if($troopinfo['state']==4){
		$troopinfo['state']=$GLOBALS['battle']['state_4'];
		$troopinfo['pathtime']="--";
		$troopinfo['endtime']="--";
	}
	$ret[]=$troopinfo;
	if($result){
		reduceGoods($uid,136,1);
		$ret[]=$GLOBALS['battle']['faster_army_suc'];
	}else{
		$ret[]=$GLOBALS['battle']['faster_army_fail'];
		unlockUser($uid);
	}
	return $ret;

}
function battleAttack($uid,$param){
	$troopid=intval(array_shift($param));
	$targettroopid=intval(array_shift($param));
	$heroname=array_shift($param);
	$targetcityname=array_shift($param);

	$battlefieldinfo=ifFrozeExit($uid);
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid' and battlefieldid='$battlefieldinfo[battlefieldid]' ");
	if(empty($troop)){
		throw new Exception($GLOBALS['battle']['troop_not_exist']);
	}
	if($troop['state']!=4){
		throw new Exception($GLOBALS['battle']['troop_not_stay']);
	}




	//检查目的部队
	$targettroop = sql_fetch_one("select * from sys_troops where id='$targettroopid'");
	if(empty($targettroop)){
		throw new Exception($GLOBALS['battle']['targettroop_not_exist']);
	}

	if($battlefieldinfo['bid']==2001){
		//如果是官渡，则要检查
		$xy=$targettroop['cid']%1000;
		if($xy==767){
			//攻击许都，检查曹军粮草是否耗尽
			$point=sql_fetch_one_cell("select point from sys_battle_winpoint where battlefieldid='$battlefieldinfo[battlefieldid]' and unionid=4");
			if($point>0){
				throw new Exception($GLOBALS['battle']['cao_has_food']);
			}
		}else if($xy==101){
			//攻击袁绍1，或者袁绍3，检查曹军粮草是否耗尽
			$point=sql_fetch_one_cell("select point from sys_battle_winpoint where battlefieldid='$battlefieldinfo[battlefieldid]' and unionid=3");
			if($point>0){
				throw new Exception($GLOBALS['battle']['yuan_has_food']);
			}
		}
	}
	//检查出发据点
	$origincity=sql_fetch_one("select * from sys_battle_city where cid='$troop[cid]'" );


	if(empty($origincity)){
		throw new Exception($GLOBALS['battle']['city_not_exist']);
	}
	$targetcity=sql_fetch_one("select * from sys_battle_city where cid='$targettroop[cid]'" );
	if(empty($targetcity)){
		throw new Exception($GLOBALS['battle']['city_not_exist']);
	}
	$targetcid=$targetcity['cid'];

	//是否是我方阵营
	$unionid=$battlefieldinfo['unionid'];
	if($targettroop['battleunionid']==$unionid){
		throw new Exception($GLOBALS['battle']['same_union']);
	}

	$endtime="unix_timestamp()+pathtime";
	if($targetcity['cid']==$origincity['cid']){
		//同一据点内相互攻击
		//TODO
		$endtime="unix_timestamp()+10";
	}else if(!canGoto($targetcid,$origincity['nextxy'])){
		throw new Exception($GLOBALS['battle']['city_cannot_goto']);
	}

	$result=sql_query("update sys_troops set starttime=unix_timestamp(),state=0,targetcid='$targetcity[cid]',task=9,targettroopid='$targettroopid',endtime=$endtime where id='$troopid' ");
	if($result){
		$ret[]=$GLOBALS['StartTroop']['succ'];

		if($targetcity['cid']!=$origincity['cid']){
			//$fieldname=$GLOBALS['battle']['union_name'][$unionid];
			//playTroopLeave($battlefieldinfo['battlefieldid'], $unionid, $fieldname, $targetcityname,$heroname)	;
		}
		resetBattleFieldUid($troop['cid']);

	}
	else
	$ret[]=$GLOBALS['StartTroop']['fail'];
	return $ret;

}

/**
 * 计算调遣援军的信息
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 */
function getCallArmyInfo($uid,$param){
	$battlefieldinfo=ifFrozeExit($uid);

}



function userJiXingJun($uid,$param){
	$troopid=intval(array_shift($param));
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid' ");
}

function getBattleInvite($uid,$param){
	$ret=array();
	$ret[]=sql_fetch_rows("select * from sys_battle_invite where touid='$uid' and state=0 order by time desc");
	return $ret;
}

function joinToBattle($uid,$param){
	$inviteid=intval(array_shift($param));
	$inviteinfo=sql_fetch_one("select id, battlefieldid,toname,touid from sys_battle_invite where id='$inviteid' and touid='$uid'");

	if(empty($inviteinfo)){
		throw new Exception($GLOBALS['battle']['no_such_invite']);
	}

	if(sql_check("select * from sys_user_battle_state where uid='$uid'")){
		throw new Exception($GLOBALS['battle']['join_user_already_in_battle']);
	}

	$todayWarCont = sql_fetch_one_cell("select today_war_count from mem_user_schedule where uid = $uid");
	if (empty($todayWarCont))$todayWarCont=0;
	if ($todayWarCont>=5){
		throw new Exception($GLOBALS['battle']['today_war_count_reach_limit']);
	}
	
	$battlefieldid=$inviteinfo['battlefieldid'];
	$battlefieldinfo=sql_fetch_one("select * from sys_user_battle_field where id='$battlefieldid' and state=0");
	if(empty($battlefieldinfo)){
		throw new Exception($GLOBALS['battle']['no_battle_field']);
	}

	$joincount=sql_fetch_one_cell("select count(*) from sys_user_battle_state where battlefieldid='$battlefieldid'");
	if($joincount>=5){
		throw new Exception($GLOBALS['battle']['user_full']);
	}

	$bid=$battlefieldinfo['bid'];
	$currenthonour=sql_fetch_one_cell("select honour from sys_user where uid='$uid'  ");
	////$currenthonour=0;
	if(empty($currenthonour)){
		$currenthonour=0;
	}
	if($currenthonour<0){
		throw new Exception($GLOBALS['battle']['honour_invalid']);
	}

	$unionid=1;
	if($bid=1001){
		//黄巾之乱，以后可能会有别的
		$unionid=1;
	}else{
		$unionid=3;
	}
	$fieldname=$GLOBALS['battle']['union_name'][$unionid];
	//$battlestartcid=sql_fetch_one_cell("select * from cfg_battle_field where id=$bid");
	//$startcid=battleid2cid($battlefieldid,$battlestartcid);

	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$result=sql_query("insert into sys_user_battle_state (uid,bid,battlefieldid,unionid,level) values($uid,$bid,$battlefieldid,$unionid,'$battlefieldinfo[level]') on duplicate key update battlefieldid=$battlefieldid,bid=$bid,unionid=$unionid,level='$battlefieldinfo[level]'");
	if($result){
		if($bid==1001){
			//黄巾之乱，添加任务
			$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60000,60001,60002,60003,60004)  and pretid=-1" );
			foreach($tasks as $task){
				sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
			}
		}

		sql_query("delete from sys_battle_invite where id='$inviteinfo[id]' ");
		playerAttendBattle($battlefieldid, $unionid, $inviteinfo['touid'], $inviteinfo['toname'],$fieldname);
		$currentbattleinfo=firstGetUserBattleInfo($uid);


		$cityinfo=sql_fetch_rows("select * from sys_battle_city where battlefieldid='$battlefieldid' ");
		$cityinfo=setFlags($uid,$unionid,$cityinfo);

		$ret=array();
		$ret[]=$currentbattleinfo;
		$ret[]=$cityinfo;
		$ret[]=getBattleStartCityInfo($bid,$unionid);
		sql_query("insert into mem_user_schedule (`uid`,`today_war_count`) values ('$uid',1) on duplicate key update `today_war_count`=today_war_count+1");
		unlockUser($uid);
		return $ret;
	}
	unlockUser($uid);
	throw new Exception($GLOBALS['battle']['join_fail']);

}



function joinToPVPBattle($uid,$param){
	$bid = intval(array_shift($param));
	$battlefieldid = intval(array_shift($param));
	$unionid = intval(array_shift($param));

	if(sql_check("select * from sys_user_battle_state where uid='$uid'")){
		throw new Exception($GLOBALS['battle']['join_user_already_in_battle']);
	}

	$userinfo=sql_fetch_one("select honour,name from sys_user where uid='$uid' ");
	$currenthonour=$userinfo["honour"];

	if($currenthonour<0){
		throw new Exception($GLOBALS['battle']['honour_invalid']);
	}

	$battle = sql_fetch_one("select * from cfg_battle_field where id=$bid");
	$maxpeople = $battle["maxpeople"];

	if ($battlefieldid==0){
		$battlefieldid = intval(sql_fetch_one_cell("select battlefieldid  from sys_user_battle_state where unionid= $unionid and  bid = $bid group by battlefieldid having count(1)<$maxpeople order by count(1) limit 1"));
		if ( empty($battlefieldid) || $battlefieldid==0)$battlefieldid= intval(sql_fetch_one_cell("select id  from sys_user_battle_field where bid = $bid limit 1"));
	}
	$battlefieldinfo=sql_fetch_one("select * from sys_user_battle_field where id='$battlefieldid'");
	if(empty($battlefieldinfo)){
		throw new Exception($GLOBALS['battle']['no_battle_field']);
	}
	if($battlefieldinfo['state']==1||$battlefieldinfo['state']==3){
		throw new Exception($GLOBALS['battle']['no_battle_field_froze']);
	}

	$curCount = intval(sql_fetch_one_cell("select count(1) from sys_user_battle_state where battlefieldid=$battlefieldid and unionid= $unionid "));
	if ($curCount >= intval($maxpeople))
	throw new Exception($GLOBALS['battle']['max_people']);

	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$result=sql_query("insert into sys_user_battle_state (uid,bid,battlefieldid,unionid,level) values($uid,$bid,$battlefieldid,$unionid,'$battlefieldinfo[level]') on duplicate key update  battlefieldid=$battlefieldid,bid=$bid,unionid=$unionid,level='$battlefieldinfo[level]'");
	if($result){
		/*$taskgroup = sql_fetch_one_cell("select taskgroup from cfg_battle_union where  unionid = $unionid");
		 if($taskgroup){ //加任务
		 $groupArray  = explode(",", $taskgroup);
		 if ($groupArray){
		 foreach ($groupArray as $group) {
		 if ($group <=10) continue;
		 $tasks=sql_fetch_rows("select id from cfg_task where `group` in (60000,60001,60002,60003,60004) and pretid=-1" );
		 foreach($tasks as $task){
		 sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
		 }
		 }
		 }
		 }*/
		if($bid==2001){
			if($unionid==3){
				//添加袁绍一方任务
				$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60005,60006,60007,60008,60009,600010) and pretid=-1" );
				foreach($tasks as $task){
					sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
				}
			}else if($unionid==4){
				//添加曹操一方任务
				$tasks=sql_fetch_rows("select id from cfg_task where `group` in (60011,60012,60013,60014,60015,60016,60017,60018) and pretid=-1" );
				foreach($tasks as $task){
					sql_query("insert into sys_user_task (uid,tid,state) values ($uid,'$task[id]',0) on duplicate key update state=0");
				}
			}
		}

		$currentbattleinfo=firstGetUserBattleInfo($uid);
		$cityinfo=sql_fetch_rows("select * from sys_battle_city where battlefieldid='$battlefieldid' ");
		$cityinfo=setFlags($uid,$unionid,$cityinfo);
		$fieldname=$GLOBALS['battle']['union_name'][$unionid];
		playerAttendBattle($battlefieldid, $unionid, $uid, $userinfo['name'],$fieldname);
		$ret=array();
		$ret[]=$currentbattleinfo;
		$ret[]=$cityinfo;
		$ret[]=getBattleStartCityInfo($bid,$unionid);
		//load血条
		//官渡之战
		$ret[]=sql_fetch_rows("select * from sys_battle_winpoint where battlefieldid='$battlefieldid'");

		$id=sql_fetch_one_cell("select id from cfg_task_goal where  tid=285");
		sql_query("insert into sys_user_goal(`uid`,`gid`) values ($uid,$id) on duplicate key update gid=$id");
		unlockUser($uid);
		return $ret;
	}
	unlockUser($uid);
	throw new Exception($GLOBALS['battle']['join_fail']);

}

function getBattleGroups($uid,$param){
	$type=intval(array_shift($param));
	$ret=array();
	$ret[]=sql_fetch_rows("select * from cfg_battle_field where type='$type'");
	return ret;
}


function getSoldierPeople($soldiers){
	//$SOLDIER_PEOPLE =array(0,1,1,1,1,1,2,3,6,4,5,10,8);
	$soldiersarray = explode(",", $soldiers);
	$count = array_shift($soldiersarray);
	$people=0;
	for($i=0;$i<$count;$i++){
		$sid=array_shift($soldiersarray);
		$people+=array_shift($soldiersarray);
	}
	return $people;
}

/**
 * 给部队增加援军 并且记录
 *
 * @param unknown_type $troopid
 * @param unknown_type $orisoldiers
 * @param unknown_type $sid
 * @param unknown_type $count
 * @return unknown
 */
function enhanceArmy($troopid,$orisoldiers,$sid,$count){
	$orisoldiersarray = explode(",", $orisoldiers);
	$orisoldiertypecount = array_shift($orisoldiersarray);
	$people=0;
	$contain=false;
	$newsoldiers="";
	for($i=0;$i<$orisoldiertypecount;$i++){
		$orisid=array_shift($orisoldiersarray);
		$oricount=array_shift($orisoldiersarray);
		if($orisid==$sid){
			$oricount+=$count;
			$contain=true;
		}
		$newsoldiers=$newsoldiers.",".$orisid.",".$oricount;
	}
	if($contain==false){
		$orisoldiertypecount++;
		$newsoldiers=$newsoldiers.",".$sid.",".$count;
	}
	$newsoldiers=$orisoldiertypecount.$newsoldiers;
	$result=sql_query("update sys_troops set soldiers='$newsoldiers' where id='$troopid'" );
	if($result){
		sql_query("insert into sys_battle_reinforce (troopid,sid,count) values ($troopid,$sid,$count) on duplicate key update count=count+$count");
		return $newsoldiers;
	}
	return false;
}



/**
 * 判断战场结束时候阵营是否胜利
 *
 * @param unknown_type $uid
 * @param unknown_type $bid
 */
function checkBattleResult($bid,$battlefieldid){
	if($bid==1001){
		return check1001($battlefieldid);
	}
	return 0;
}

/**
 * 检查黄巾之乱的战场结果,条件：判断战场里是否还有张角三兄弟的军队
 *
 * @param unknown_type $uid
 */
function check1001($battlefieldid){
	$result=sql_fetch_rows("select * from sys_troops where uid=0 and battlefieldid='$battlefieldid' and hid in(1001,1002,1005) " );
	if(empty($result))
	return 1;
	return 2;
}

function getSoldierMap($orisoldiers){

	$reinforcearray=explode(",", $orisoldiers);
	$reinforcecount=array_shift($reinforcearray);
	$reinforcemap=array();

	for($i=0;$i<$reinforcecount;$i++){
		$sid=array_shift($reinforcearray);
		$count=array_shift($reinforcearray);
		$sidindex=$sid."";
		$reinforcemap[$sid]=$count;
	}
	return 	$reinforcemap;
}

function getSoldierString($soldiersmap){

	$soldiersmapcount=count($soldiersmap);
	$newsoldierscount=0;
	$newsoldiers="";
	foreach($soldiersmap as $sid => $count){
		//$sid=array_shift($soldier);
		//$count=array_shift($soldier);
		if($count>0){
			$newsoldiers=$newsoldiers.",".$sid.",".$count;
			$newsoldierscount++;
		}
	}
	if($newsoldierscount>0){
		$newsoldiers=$newsoldierscount.$newsoldiers;
	}
	return 	$newsoldiers;
}
/**
 *
 *
 * @param unknown_type $uid
 * @param unknown_type $unionid
 * @param unknown_type $troops
 * @return unknown
 */
function setFlags($uid,$unionid,$cityinfos){
	//默认白旗帜，无人
	$resultcityinfo=array();

	foreach($cityinfos as $cityinfo){
		$cityunionid=$cityinfo['unionid'];
		$cityuid=$cityinfo['uid'];
		if($cityunionid==-1){
			//空据点，白旗
			$cityinfo['flag']=5;
			$cityinfo['flagchar']="";
		}else if($cityunionid==0){
			//争夺中,玫瑰色
			$cityinfo['flag']=3;
			$cityinfo['flagchar']="";
		}else if($cityunionid==$unionid){
			//同一阵营

			$cityinfo['flagchar']=$GLOBALS['battle']['union_flag_text'][$unionid];
			if(!$cityinfo['hasuser']){
				//npc城
				$cityinfo['flag']=2;
			}else {
				//我方据点
				$cityinfo['flag']=1;

			}
		}else{
			//敌人阵营

			$cityinfo['flagchar']=$GLOBALS['battle']['union_flag_text'][$cityunionid];

			if(!$cityinfo['hasuser']){
				$cityinfo['flag']=0;

			}else{
				//敌方玩家据点
				$cityinfo['flag']=4;

			}
		}
		$resultcityinfo[]=$cityinfo;
	}

	return $resultcityinfo;
}


/**
 * 读取战场成员列表
 *
 * @param unknown_type $uid
 * @param unknown_type $param
 */
function getBattleFieldUsers($uid,$param){
	$battlefiledid=intval(array_shift($param));

	$battleinfo=sql_fetch_one("select f.bid,f.createuid,s.battlefieldid,f.state,f.type from sys_user_battle_state s left join sys_user_battle_field f on s.battlefieldid=f.id where uid=$uid");
	if(empty($battleinfo)){
		//你已经不在战场中	
		throw new Exception($GLOBALS['battle']['user_not_in_battle']);
	}

	$ret=array();

	$iscreator=false;
	if($uid==$battleinfo['createuid']){
		$iscreator=true;
	}

	//战场中的用户
	//$userin=sql_fetch_rows("select s.uid,s.honour,u.name from sys_user_battle_state s left join sys_user u on s.uid=u.uid where s.battlefieldid='$battleinfo[battlefieldid]' ");
	$userin = sql_fetch_rows("select s.uid,u.honour,u.name, c.name as camp from sys_user_battle_state s left join sys_user u on s.uid=u.uid
	left join cfg_battle_union c on c.bid=s.bid and c.unionid=s.unionid  where s.battlefieldid='$battleinfo[battlefieldid]'");
	$userininfo=array();
	foreach($userin as $oneuser){
		$herocount=sql_fetch_one_cell("select count(*) from sys_troops where  uid='$oneuser[uid]' and battlefieldid='$battleinfo[battlefieldid]' ");

		//阵营
		//$oneuser['camp']

		$oneuser['herocount']=$herocount;
		$oneuser['state']=$GLOBALS['battle']['state_in'];
		$oneuser['cancel']=false;
		if(empty($oneuser['honour'])){
			$oneuser['honour']=0;
		}
		$userininfo[]=$oneuser;
	}

	//已经参与的人数

	$incount=count($userininfo);
	//如果是创建者
	$userinvite=sql_fetch_rows("select i.id,i.toname as name,t.honour from sys_battle_invite i left join sys_user t on i.touid=t.uid where battlefieldid='$battleinfo[battlefieldid]'");
	$userinviteinfo=array();
	foreach($userinvite as $oneuser){
		$oneuser['herocount']=0;
		$oneuser['state']=$GLOBALS['battle']['state_invite'];
		if($iscreator)
		$oneuser['cancel']=true;
		else
		$oneuser['cancel']=false;
		if(empty($oneuser['honour'])){
			$oneuser['honour']=0;
		}
		$userinviteinfo[]=$oneuser;

	}


	$userinfo=array_merge($userininfo,$userinviteinfo);
	$ret[]=$userinfo;
	$ret[]=$incount;
	$ret[] = $iscreator;
	return $ret;
}


function inviteBattleUser($uid,$param){
	$battlefieldid=	intval(array_shift($param));
	$invitename = array_shift($param);
	$myname = array_shift($param);

	$battleinfo=sql_fetch_one("select * from sys_user_battle_field where id='$battlefieldid' ");
	if(empty($battleinfo)){
		//你已经不在战场中	
		throw new Exception($GLOBALS['battle']['user_not_in_battle']);
	}
	if($battleinfo["state"]==1){
		throw new Exception($GLOBALS['battle']['battle_froze']);
	}
	if($uid!=$battleinfo['createuid']){
		throw new Exception($GLOBALS['battle']['invite_not_creator']);
	}


	$touser=sql_fetch_one("select uid,honour from sys_user where name='$invitename' ");

	if(empty($touser)){
		throw new Exception($GLOBALS['battle']['invite_user_not_exist']);
	}
	$touserid=$touser['uid'];

	$honour=$touser['honour'];
	if($honour<0){
		throw new Exception($GLOBALS['battle']['invite_not_enough_honour']);
	}

	if(sql_check("select id from sys_battle_invite where battlefieldid='$battleinfo[id]' and touid='$touserid' ")){
		throw new Exception($GLOBALS['battle']['invite_user_already']);
	}

	$count=sql_fetch_one_cell("select count(*) from sys_user_battle_state where battlefieldid=$battleinfo[id]");

	if($count>=$battleinfo['maxpeople']){
		throw new Exception($GLOBALS['battle']['invite_max_people']);
	}



	$fieldname=$GLOBALS['battle']['name'][$battleinfo['bid']];
	$resultid=sql_insert("insert into sys_battle_invite(fromuid ,battlename,battlefieldid ,level,time ,touid ,fromname,toname )
	values ($uid,'$fieldname','$battleinfo[id]','$battleinfo[level]',unix_timestamp(),$touserid,'$myname','$invitename')");
	$ret=array();
	if($resultid){
		$ret[]=1;
		$ret[]=$GLOBALS['battle']['invite_user_suc'];
		$ret[]=$count;
		$ret[]=array("fromuid"=>$uid,"id"=>$resultid,"touid"=>$touserid,"name"=>$invitename,"herocount"=>0,"honour"=>$honour,"cancel"=>true,"state"=>$GLOBALS['battle']['state_invite']);

		$id=sql_fetch_one_cell("select id from cfg_task_goal where  tid=283");
		sql_query("insert into sys_user_goal(`uid`,`gid`) values ($uid,$id) on duplicate key update gid=$id");
	}else{
		$ret[]=0;
		$ret[]=$GLOBALS['battle']['invite_user_fail'];
		$ret[]=$count;


	}

	return $ret;
}

function ignoreBattle($uid,$param){
	$inviteid=intval(array_shift($param));
	sql_query("delete from  sys_battle_invite where id='$inviteid' ");
	$ret=array();
	$ret[]=sql_fetch_rows("select * from sys_battle_invite where touid='$uid' and state=0 order by time desc");
	return $ret;
}

function cancelBattleInvite($uid,$param){
	$inviteid=intval(array_shift($param));
	$invite = sql_fetch_one("select * from sys_battle_invite where id=$inviteid and fromuid=$uid");
	if(empty($invite)){
		throw new Exception($GLOBALS['battle']['invite_not_exist']);
	}

	$ret=array();

	$result=sql_query("delete from sys_battle_invite where id=$inviteid");
	if($result){
		$ret[]=$GLOBALS['battle']['cancel_invite_suc'];
		$ret[]=$inviteid;
	}else{
		$ret[]=$GLOBALS['battle']['cancel_invite_suc'];
		$ret[]=0;
	}
	return $ret;
}



///jun.zhao
/**
 * 玩家加入战场
 * @param $battleid
 * @param $unionid
 * @param $nick
 * @return unknown_type
 */
function playerAttendBattle($battleid, $unionid, $uid, $nick, $camp_name)
{
	$content = sprintf($GLOBALS['battle']['login'], $camp_name, $nick);
	$content=addslashes($content);
	#echo "insert into `mem_war_buf` (`battleid`, `unionid`,`uid`,`nick`, `state`,`updatetime`) values('$battleid','$unionid','$uid','$nick', '1', unix_timestamp())";
	$error = sql_insert("insert into `mem_war_buf` (`battleid`, `unionid`,`uid`,`nick`, `state`, `updatetime`) values('$battleid','$unionid','$uid','$nick', 1, unix_timestamp())");
	if($error == 0)
	return 0;
	$error = sql_insert("insert into `mem_war_event` (`battleid`, `unionid`,`type`,`content`,`evttime`) values ('$battleid', '$unionid', 2, '$content',unix_timestamp())");
	//sql_query("INSERT INTO `log_battle_news` (`battleid`, `unionid`, `content`, `log_time`)  VALUES ('$battleid', '$unionid', '$content', unix_timestamp())");
	if($error == 0)
	return 0;
	return 1;
}
function playerExitBattle($battleid, $unionid, $uid, $nick, $camp_name){
	#$content = "玩家:".$nick."退出了战场";
	$content = sprintf($GLOBALS['battle']['logout'], $camp_name, $nick);
	$content=addslashes($content);
	#echo "insert into `mem_war_buf` (`battleid`, `unionid`,`uid`,`nick`,`updatetime`) values('$battleid','$unionid','$uid','$nick', '0', unix_timestamp())";
	$error = sql_insert("insert into `mem_war_buf` (`battleid`, `unionid`,`uid`,`nick`, `state`, `updatetime`) values('$battleid','$unionid','$uid','$nick', 0, unix_timestamp())");
	if($error == 0)
	return 0;
	$error = sql_insert("insert into `mem_war_event` (`battleid`, `unionid`,`type`,`content`,`evttime`) values ('$battleid', '$unionid', 2, '$content',unix_timestamp())");
	//sql_query("INSERT INTO `log_battle_news` (`battleid`, `unionid`, `content`, `log_time`)  VALUES ('$battleid', '$unionid', '$content', unix_timestamp())");
	if($error == 0)
	return 0;
	return 1;
}

/*function playTroopLeave($battleid, $unionid, $camp_name, $battlePlace,$heroname)
 {
 $content =  sprintf($GLOBALS['battle']['troop_leave'], $camp_name,$heroname, $battlePlace);
 $sqll="insert into `mem_war_event` (`battleid`, `unionid`,`type`,`content`,`evttime`) values ('$battleid ', '$unionid', 2, '$content',unix_timestamp())";
 sql_query($sqll);
 sql_query("INSERT INTO `log_battle_news` (`battleid`, `unionid`, `content`, `log_time`)  VALUES ('$battleid', '$unionid', '$content', unix_timestamp())");

 }*/

function getBattleNews($uid, $param)
{
	$battleid= array_shift($param);
	$unionid = array_shift($param);
	$page = array_shift($param);
	$pageCount = array_shift($param);
	$start = intval($page) * intval($pageCount);
	$ret = Array();
	$ret[] = sql_fetch_one_cell("select count(*) from log_battle_news where battleid='$battleid'");
	$ret[] = sql_fetch_rows("select * from log_battle_news where battleid='$battleid' order by log_time desc LIMIT $start,$pageCount");
	return $ret;
}

function getBattleInfor($uid, $param)
{
	$bid = array_shift($param);
	$battleid= array_shift($param);
	$unionid = array_shift($param);
	#$ret = array();
	$info1 = sql_fetch_one("select name, minpeople, maxpeople, maxlevel, content from cfg_battle_field where id ='$bid'");
	if(empty($info1)){
		throw new Exception($GLOBALS['battle']['no_battle_infor']);
	}
	#	$ret[] = $info;

	$info2 = sql_fetch_one("select count(*) as total  from sys_user_battle_state where battlefieldid=$battleid");
	if(empty($info2)){
		throw new Exception($GLOBALS['battle']['no_battle_infor']);
	}

	//$info3 = sql_fetch_one("select *  from sys_user_battle_state where battlefieldid=$battleid and unionid=$unionid and uid=$uid");
	$info3 = sql_fetch_one("select *  from sys_user_battle_field where id=$battleid");
	if(empty($info3)){
		throw new Exception($GLOBALS['battle']['no_battle_infor']);
	}
	$ret[] = $info1['name'];
	$ret[] = $info1['minpeople'];
	$ret[] = $info1['maxpeople'];
	$ret[] = $info3["level"];
	$ret[] = $info1["content"];
	$ret[] = $info2['total'];
	return $ret;
}

function getBattleStartCityInfo($bid,$unionid){
	return sql_fetch_rows("select * from cfg_battle_start_city where bid=$bid and unionid=$unionid order by needhonour asc");
}


/**
 * 军队离开某个据点以后重新判定据点的归属
 *
 * @param unknown_type $cid
 * @return unknown
 */
function resetBattleFieldUid($cid){

	$result=array();
	$result["oriuid"]=0;
	$result["oriunionid"]=0;
	$result["uid"]=0;
	$result["unionid"]=0;

	//据点里有没有wa
	$hasuser=0;

	$uid=-1;
	$unionid=-1;
	$sameUid=true;

	//在这个据点里战斗的部队或者驻守的部队 都算作这个据点的部队
	$troops=sql_fetch_rows("select * from sys_troops where cid=$cid and ((state=4 or state=3) or targetcid=$cid)");

	$oricidinfo=sql_fetch_one("select uid,unionid from sys_battle_city where cid=$cid");
	if(empty($oricidinfo)){
		return $result;
	}
	$result["oriuid"]=$oricidinfo["uid"];
	$result["oriunionid"]=$oricidinfo["unionid"];

	if(empty($troops)){
		//空白据点
		$result["uid"]="-1";
		$result["unionid"]="-1";
		sql_query("update sys_battle_city set uid=-1,unionid=-1,hasuser=0 where cid=$cid");
		return $result;
	}

	$isFirst=true;
	foreach($troops as $troop)
	{
		if($isFirst){
			$uid=$troop["uid"];
			$unionid=$troop["battleunionid"];
			$isFirst=false;
		}else{
			if($troop["battleunionid"]!=$unionid){
				//不属于同一阵营的驻扎
				sql_query("update sys_battle_city set uid=0,unionid=0 where cid=$cid");
				return result;
			}
			if($troop["uid"]!=$uid){
				if($troop["uid"]>0&&$uid>0){
					//属于同一阵营但是uid不同,并且都不是NPC军队
					$sameUid=false;
				}
				$uid=$troop["uid"];
				$unionid=$troop["battleunionid"];

			}

		}
		if($troop["uid"]>0){
			//是否有NPC部队
			$hasuser=1;
		}


	}

	//所有的都是同一阵营
	if($sameUid){
		//属于同一个用户
		sql_query("update sys_battle_city set uid=$uid,unionid=$unionid,hasuser='$hasuser' where cid=$cid");
		$result["uid"]=$uid;
		$result["unionid"]=$unionid;
	}else{
		//同一阵营的不同用户
		sql_query("update sys_battle_city set uid=0,unionid=$unionid,hasuser='$hasuser' where cid=$cid");
		$result["uid"]=0;
		$result["unionid"]=$unionid;
	}
	return result;

}

/**
 * 如果队长退出，自动转移队长，
 * @param uid, 队长的uid
 * @return unknown_type
 */
function autoTransferCaptain($uid)
{

	$battlefield = sql_fetch_one("select * from sys_user_battle_field where createuid='$uid' and id=(select battlefieldid from sys_user_battle_state where uid='$uid') ");
	if(!empty($battlefield))
	{
		$battlefieldid = $battlefield['id'];
		$userinfo = sql_fetch_one("select * from sys_user_battle_state where battlefieldid='$battlefieldid' and uid!=$uid order by jointime limit 1");
		if(!empty($userinfo)){
			$touid = $userinfo['uid'];
			sql_query("update sys_user_battle_field set createuid='$touid' where id=$battlefieldid " );
			//发战报
			sendReport($touid,0,40,0,0, $GLOBALS['battle']['become_captain']);
		}
	}


}

	function punishNotAdult($uid)
	{
		if($uid == "") return 1;
		$row = sql_fetch_one("select * from sys_user_online where uid=$uid");
		if(empty($row))
			return 1;
		$login_time =  $row["login_time"];
		$logout_time = $row["logout_time"];
		$online_time = $row["online_time"];
		$offline_time = $row["offline_time"];

		if($row["state"] == 1) //成人
		{
			return 1;
		}
		$online_time = intval(intval($online_time)/60);

		if($online_time>3*30 && $online_time<5*60)
		{
			return 0.5;
		}
		else if($online_time>=5*60){
			return 0;
		}
		return 1;
	}


?>