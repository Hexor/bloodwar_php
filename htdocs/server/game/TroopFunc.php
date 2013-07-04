<?php
require_once("./interface.php");
require_once("./utils.php");

function doGetUnionTroops($uid,$cid)
{
	$ret = array();
	$ret[] = sql_fetch_one_cell("select n.name from sys_user u left join sys_union n on n.id=u.union_id where u.uid='$uid'");
	$ret[] = sql_fetch_rows("select t.*,t.uid as userid,u.name as username,h.name as hero,h.level as herolevel from sys_user u,sys_troops t left join sys_city_hero h on h.hid=t.hid where u.uid=t.uid and t.targetcid=$cid and t.task=1 and t.state=4");
	return $ret;
}

function kickTroop($uid,$param)
{
	$troopid = array_shift($param);
	$troop=sql_fetch_one("select * from sys_troops where id='$troopid' and task=1 and state=4");
	if (empty($troop))
	{
		throw new Exception($GLOBALS['kickUnionTroop']['army_not_exist']);
	}
	sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp() where id='$troopid'");
	updateCityResourceAdd($troop['targetcid']);
	return array();
}

function allowUnionTroop($uid,$cid,$param)
{
	$allow=intval(array_shift($param));
	sql_query("insert into sys_allow_union_troop (uid,`allow`) values ('$uid','$allow') on duplicate key update `allow`='$allow'");
	$ret=array();
	$ret[]=getAllowUnionTroop($uid,$cid);
	return $ret;
}
function allowAntiPlunder($uid,$cid,$param)
{
	$allow=intval(array_shift($param));
	sql_query("insert into sys_allow_union_troop (uid,`anti_plunder`) values ('$uid','$allow') on duplicate key update `anti_plunder`='$allow'");
	$ret=array();
	$ret[]=getAllowAntiPlunder($uid,$cid);
	return $ret;
}
function allowAntiInvade($uid,$cid,$param)
{
	$allow=intval(array_shift($param));
	sql_query("insert into sys_allow_union_troop (uid,`anti_invade`) values ('$uid','$allow') on duplicate key update `anti_invade`='$allow'");
	$ret=array();
	$ret[]=getAllowAntiInvade($uid,$cid);
	return $ret;
}

//task 3 掠夺
//task 4 占领
//state 0 前往
//state 1 返回
//state 2 侦查
//state 3 战斗
//state 4 驻军

//得到军队队列
function getArmyTroops($uid,$param)
{
	sql_query("update sys_alarm set troops=0 where uid='$uid'");
	$troops=sql_fetch_rows("select t.*,c.name as fromcity from sys_troops t left join sys_city c on c.cid=t.cid where t.uid='$uid' and t.state<4");
	foreach($troops as &$troop){
		$troop['resource']="";
		$troop['soldier']="";
		if($troop['task']==7){
			//派遣到某个战场
			$xy=$troop['targetcid']%1000;
			$bid=$troop['bid'];

			$troop['targetcity']=sql_fetch_one_cell("select name from cfg_battle_city where bid=$bid and xy=$xy");

		}else if($troop['task']==8){
			//派遣到某个战场

			$troop['targetcity']=sql_fetch_one_cell("select name from sys_battle_city where cid=".$troop['targetcid']);

			$troop['fromcity']=sql_fetch_one_cell("select name from sys_battle_city where cid=".$troop['cid']);
		}else if($troop['task']==9){
			//派遣到某个战场
			$troop['targetcity']=sql_fetch_one_cell("select name from sys_battle_city where cid=".$troop['targetcid']);
			$troop['fromcity']=sql_fetch_one_cell("select name from sys_battle_city where cid=".$troop['cid']);
		}else{
			$troop['wtype']=sql_fetch_one_cell("select type from mem_world where `wid`=".cid2wid($troop['targetcid']));
			if($troop['wtype']==0)
			{
				$troop["targetcity"] = sql_fetch_one_cell("select name from sys_city where cid=".$troop['targetcid']);
			}
		}

		$troop['userid']=$troop['uid'];

		//处理回城队列
		if($troop['endtime'] - time() < 0 && $troop['state'] == 1)
		{
			getArmyTroops($uid,$param);//返回最新队列
		}

	}

	//file_put_contents('c:/troops'.time(), var_export($troops,true));
	return $troops;
}




//取得敌人的队列
function getEnemyTroops($uid,$param)
{


	
	sql_query("update sys_alarm set enemy=0 where uid='$uid'");
	$troops1 = sql_fetch_rows("select t.*,t.targetcid as targetownercid,c.name as targetcity from sys_troops t,sys_city c where t.targetcid=c.cid and c.uid='$uid' and t.uid <> '$uid' and t.state<4 and t.task in (2,3,4)");
	$cidStr = sql_fetch_one_cell("select group_concat(cid) from sys_city where uid='$uid'");
	if (empty($cidStr)||$cidStr=="")$ownerfields=array();
	else $ownerfields=sql_fetch_rows("select wid from mem_world where ownercid in (".$cidStr.") and type>0");
	if(!empty($ownerfields))
	{
		$comma="";
		foreach($ownerfields as $mywid)
		{
			$fieldcids.=$comma;
			$fieldcids.=wid2cid($mywid['wid']);
			$comma=",";
		}
		$troops2 = sql_fetch_rows("select * from sys_troops where uid <> '$uid' and state<4 and task in (2,3,4) and targetcid in ($fieldcids)");
		foreach($troops2 as &$troop)
		{
			$worldinfo=sql_fetch_one("select type,ownercid from mem_world where `wid`=".cid2wid($troop['targetcid']));
			$troop['wtype']=$worldinfo['type'];
			$troop['targetownercid']=$worldinfo['ownercid'];
		}
	}
	foreach($troops1 as &$troop)
	{
		$troop['wtype']=0;
	}
	if(!empty($troops2)) $troops = array_merge($troops1,$troops2);
	else $troops=$troops1;
	if(count($troops)==0)
	{
		return $troops;
	}
	foreach($troops as &$troop)
	{
		$troop['userid']=$troop['uid'];
		$troop['resource']="";
		$troop['soldier']="";
		$viewLevel = sql_fetch_one_cell("select level from sys_building where cid='".$troop['targetownercid']."' and bid=".ID_BUILDING_BALEFIRE." limit 1");
		if(empty($viewLevel))
		{
			$viewLevel=0;
		}
		$troop["viewLevel"] = $viewLevel;
		if ($viewLevel >= 4)    //�Է�����
		{
			$troop["enemyuser"]=sql_fetch_one_cell("select name from sys_user where uid=".$troop['uid']);
		}
		if ($viewLevel >= 5)    //���
		{
			$troop["origincity"] = sql_fetch_one_cell("select name from sys_city where cid=".$troop['cid']);
		}
	}
	//file_put_contents('c:/enemy_troops'.time(), var_export($troops,true));
	return $troops;
}

function getStayTroops($uid,$param)
{
	$ret = array();
	$ret[]=getAllowAutoDiscard($uid);
	$troops=sql_fetch_rows("select t.*,c.name as origincity,g.starttime from sys_troops t left join sys_city c on c.cid=t.cid left join sys_gather g on g.troopid=t.id where t.uid='$uid' and t.state=4");
	foreach($troops as &$troop)
	{
		$troop['userid']=$troop['uid'];
		$troop['resource']="";
		$troop['soldier']="";
		$troop['wtype']=sql_fetch_one_cell("select type from mem_world where `wid`=".cid2wid($troop['targetcid']));
		if($troop['wtype']==0)
		{
			$troop['targetcity'] = sql_fetch_one_cell("select name from sys_city where cid=".$troop['targetcid']);
		}
		 
		if($troop[task]==7 || $troop[task]==8 || $troop[task]==9){
			//派遣到某个战场
			$troop['targetcity']=sql_fetch_one_cell("select name from sys_battle_city where cid=".$troop['targetcid']);
			$troop['origincity']=sql_fetch_one_cell("select name from sys_city where cid=".$troop['startcid']);
			$troop['cid']= $troop['startcid'];
		}

		if(!empty($troop['starttime']))
		{
			$troop['state']=5;
			$troop['endtime']=sql_fetch_one_cell("select starttime from sys_gather where troopid='$troop[id]'");
		}
	}
	$ret[]=$troops;
	return $ret;
}

function getUnionTroops($uid,$param)
{

	$troops1 = sql_fetch_rows("select t.*,c.name as targetcity from sys_troops t,sys_city c where t.targetcid=c.cid and c.uid='$uid' and t.uid <> '$uid' and (t.task=0 or t.task=1)");

	$cidStr = sql_fetch_one_cell("select group_concat(cid) from sys_city where uid='$uid'");
	if (empty($cidStr)||$cidStr=="")$ownerfields=array();
	else $ownerfields=sql_fetch_rows("select wid from mem_world where ownercid in (".$cidStr.") and type>0");
	if(!empty($ownerfields))
	{
		$comma="";
		foreach($ownerfields as $mywid)
		{
			$fieldcids.=$comma;
			$fieldcids.=wid2cid($mywid['wid']);
			$comma=",";
		}
		$troops2 = sql_fetch_rows("select * from sys_troops where uid <> '$uid' and (task=0 or task=1) and targetcid in ($fieldcids)");
		foreach($troops2 as &$troop)
		{
			$worldinfo=sql_fetch_one_cell("select type from mem_world where `wid`=".cid2wid($troop['targetcid']));
			$troop['wtype']=$worldinfo['type'];
		}
	}
	if(!empty($troops2)) $troops = array_merge($troops1,$troops2);
	else $troops=$troops1;
	if(count($troops)==0)
	{
		return $troops;
	}

	foreach($troops as &$troop)
	{
		$troop['userid']=$troop['uid'];
		$troop['resource']="";
		$troop['soldier']="";
		$troop['fromcity']=sql_fetch_one_cell("select name from sys_city where cid='$troop[cid]'");
		$troop['username']=sql_fetch_one_cell("select name from sys_user where uid='$troop[uid]'");
	}
	return $troops;
}

//召回军队
function callBackTroop($uid,$param)
{
	$troopid = array_shift($param);
	if (sql_check("select * from sys_gather where troopid='$troopid'"))
	{
		throw new Exception($GLOBALS['callBackTroop']['gather']);
	}
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid' and uid='$uid'");
	if (empty($troop)) throw new Exception($GLOBALS['callBackTroop']['invalid_army']);
	else if ($troop['state']==0&&$troop['noback'] > 0)
	{
		throw new Exception($GLOBALS['callBackTroop']['on_back']);
	}
	if ($troop['state']==0) //ǰ���з��أ�����ʱ�����ǰ��ʱ��
	{
		sql_query("update sys_troops set `state`=1,endtime=unix_timestamp() - starttime + unix_timestamp(),starttime=unix_timestamp() where id='$troop[id]'");
	}
	else if (($troop['state']==4)||($troop['state']==2))    //פ���ȴ�ս���ٻ�
	{
		sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where id='$troop[id]'");
		updateCityResourceAdd($troop['cid']);
		updateCityResourceAdd($troop['targetcid']);
		if ($troop['hid'] > 0)
		{
			sql_query("update sys_city_hero set state=2 where hid='$troop[hid]'");
		}
	}
	else if ($troop['state'] == 3)
	{
		throw new Exception($GLOBALS['callBackTroop']['army_in_battle']);
	}
	else if ($troop['state'] == 1)
	{
		throw new Exception($GLOBALS['callBackTroop']['army_on_way_back']);
	}
	return array();
}

//取得军队详情
function getTroopDetail($uid,$param)
{
	$info=array();
	$troopid=array_shift($param);
	$troop = sql_fetch_one("select * from sys_troops where id='$troopid'");
	//是不是使用了虚张声势或者偃旗息鼓


	if (empty($troop)) throw new Exception($GLOBALS['callBackTroop']['invalid_army']);
	$info['troop']=$troop;
	$ownerinfo=sql_fetch_one("select name,union_id from sys_user where uid='$troop[uid]'");
	$info['troopowner']=$ownerinfo['name'];
	if($ownerinfo['union_id']>0)
	{
		$info['troopunion']=sql_fetch_one_cell("select name from sys_union where id='$ownerinfo[union_id]'");
	}
	$info['viewLevel']=10;
	if(($troop['uid']!=$uid) && (($troop['task']==2)||($troop['task']==3)||($troop['task']==4)))
	{
		$mycid=sql_fetch_one_cell("select ownercid from mem_world where wid=".cid2wid($troop['targetcid']));
		$viewLevel = sql_fetch_one_cell("select level from sys_building where cid='".$mycid."' and bid=".ID_BUILDING_BALEFIRE." limit 1");
		if(empty($viewLevel))
		{
			$viewLevel=0;
		}
		$info['viewLevel']=$viewLevel;
		if ($viewLevel >= 9)
		{
			if($troop['hid']>0)
			{
				$info['hero']=sql_fetch_one("select name,level,npcid from sys_city_hero where hid='$troop[hid]'");
			}
		}
		if($viewLevel>=10)
		{
			$info["technic"] = sql_fetch_rows("select tid,level from sys_city_technic where cid='$troop[cid]' and tid in(7,8,10,12,13,14,16,20)");
		}

	}
	else if($troop['hid']>0)
	{
		$info['hero']=sql_fetch_one("select name,level,npcid from sys_city_hero where hid='$troop[hid]'");
	}

	$buffers = sql_fetch_rows("select buftype,bufparam from mem_troops_buffer where troopid='$troopid' ");

	if(!empty($buffers)){
		$info['buffers']=$buffers;
	}

	$ret=array();
	$ret[]=$info;
	return $ret;
}









//每次到0秒的时候将被调用
//取得战斗报告
function getBattleReport($uid,$param)
{
	
	//更新战场时间

	$battleid = array_shift($param);//战斗id
	$lastround = array_shift($param);//上一回合数

	//updateBattle($battleid);

	$report = sql_fetch_rows("select * from sys_battle_report where battleid=".$battleid." and round >".$lastround." order by round");

	$ret = array();
//	if (!empty($report))
//	{
		$sysbattle = sql_fetch_one("select state,result from sys_battle where id='$battleid'");
		$membattle = sql_fetch_one("select * from mem_battle where id='$battleid'");
		$ret[] = $sysbattle;
		$ret[] = $membattle;
		$ret[] = $report;
		$ret[] = sql_fetch_rows("select * from mem_battle_tactics where battleid='$battleid' and attack=1");
		$ret[] = sql_fetch_rows("select * from mem_battle_tactics where battleid='$battleid' and attack=0");
//	}

	return $ret;
}

//取得战斗结果
function getBattleData($uid,$param)
{
	//file_put_contents('c:/b'.time(),var_export($param,true));	
	//sleep();
	$battleid = array_shift($param);
	$sysbattle = sql_fetch_one("select * from sys_battle where id='$battleid'");

	if ($sysbattle['state'] == 1)
	{
		throw new Exception($GLOBALS['getBattleData']['battle_end']);
	}
	$membattle = sql_fetch_one("select * from mem_battle where id='$battleid'");
	$xx = $membattle['round'];
	if (empty($sysbattle)||empty($membattle))
	{
		throw new Exception($GLOBALS['getBattleData']['battle_data_lost']);
	}


	$ret = array();
	$ret[] = $sysbattle['type'];
	$attackuid = intval($sysbattle['attackuid']);
	$resistuid = intval($sysbattle['resistuid']);
	$attackcid = intval($membattle['attackcid']);
	$resistcid = intval($membattle['resistcid']);
	$attackhid = intval($membattle['attackhid']);
	$resisthid = intval($membattle['resisthid']);
	$ret[] = $attackuid;

	if($sysbattle['type']==4){
		//战场战
		if ($attackuid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_user where uid='$attackuid'");
		}
		$ret[] = $resistuid;
		if ($resistuid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_user where uid='$resistuid'");
		}
		$ret[] = $attackcid;
		if ($attackcid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_battle_city where cid='$attackcid'");
		}
		$ret[] = $resistcid;
		if ($resistcid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_battle_city where cid='$resistcid'");
		}
		$ret[] = $attackhid;
		if ($attackhid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_city_hero where hid='$attackhid'");
		}
		$ret[] = $resisthid;
		if ($resisthid)
		{
			if($resistuid > 0)
			$ret[] = sql_fetch_one("select * from sys_city_hero where hid='$resisthid'");
			else
			$ret[] = sql_fetch_one("select * from cfg_battle_hero where hid='$resisthid'");
		}
	}else{
		if ($attackuid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_user where uid='$attackuid'");
		}
		$ret[] = $resistuid;
		if ($resistuid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_user where uid='$resistuid'");
		}
		$ret[] = $attackcid;
		if ($attackcid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_city where cid='$attackcid'");
		}
		$ret[] = $resistcid;
		if ($resistcid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_city where cid='$resistcid'");
		}
		$ret[] = $attackhid;
		if ($attackhid > 0)
		{
			$ret[] = sql_fetch_one("select * from sys_city_hero where hid='$attackhid'");
		}
		$ret[] = $resisthid;
		if ($resisthid)
		{
			$ret[] = sql_fetch_one("select * from sys_city_hero where hid='$resisthid'");
		}
	}
	$param2=array();
	$param2[]=$battleid;
	$param2[]=0;
	$ret[]=getBattleReport($uid,$param2);
	//file_put_contents('c:/xxx'.$xx.rand(1,1000), var_export($ret,true));
	return $ret;
}

//设置是前进还是防御还是后退
function setSoldierTactics($uid,$param)
{
	//file_put_contents('c:/b'.time(),var_export($param,true));
	$battleid = array_shift($param);//战斗id
	$userIsAttack = array_shift($param);//是否是进攻方
	$stype = array_shift($param);//军队的id
	$action = array_shift($param);//2防守 1前进 3 后退
	$target = array_shift($param);//攻击目标

	$action2 = 0;
	$target2 = 0;

	$attack = $userIsAttack?"1":"0";
	$sysbattle = sql_fetch_one("select * from sys_battle where id='$battleid'");//取出战斗信息
	if (($userIsAttack&&($uid != $sysbattle['attackuid']))||
	((!$userIsAttack)&&($uid != $sysbattle['resistuid'])))
	{
		throw new Exception($GLOBALS['setSoldierTactics']['cant_change_enemy_tactics']);
	}
	//取得城墙hp  需要更新mem_battle
	$wallhp = sql_fetch_one_cell("select wallhp from mem_battle where id='$battleid'");

	if ($wallhp <= 0)
	{
		$action2 = $action;//如果城墙没有破 则全部攻击目标都是城墙
		$target2 = $target;
	}
	//重新设定指挥方案
	sql_query("replace into mem_battle_tactics  (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$battleid','$attack','$stype','$action','$target','$action2','$target2')");
}
?>