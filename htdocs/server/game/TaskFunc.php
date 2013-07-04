<?php
require_once("./interface.php");
require_once("./utils.php");

function getUnionFamousCityGold($uid)
{
	$unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
	if($unionid==0) return 0;
	$cities=sql_fetch_rows("select type,count(*) from sys_city c, sys_user u where u.uid=c.uid and u.union_id='$unionid' and c.type>0 group by c.type");
	$gold=0;
	if(!empty($cities))
	{
		foreach($cities as $city)
		{
			if($city['type']==1) $gold=$gold+10000;
			else if($city['type']==2) $gold=$gold+30000;
			else if($city['type']==3) $gold=$gold+100000;
			else if($city['type']==4) $gold=$gold+300000;
		}
	}
	return $gold;
}

function checkGoalComplete($uid,$goal)
{
	file_put_contents('c:/x', var_export($goal,true));
	if (!empty($goal['uid']))
	{
		return true;
	}

	if ($goal['sort'] == 1)    //资源  1黄金,  2粮食,3木材,4石料,5铁锭,6人口,7民心,8民怨,9声望,
	{
		if ($goal['type'] == 9)     //声望
		{
			return sql_fetch_one_cell("select prestige from sys_user where uid='$uid'") >= $goal['count'];
		}
		else if ($goal['type'] == 11)       //联盟人数
		{
			$union_id  = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
			if ($union_id == 0) return false;

			return sql_fetch_one_cell("select member from sys_union where id='$union_id'") >= $goal['count'];
		}
		else if ($goal['type'] == 17)   //官职
		{
			return sql_fetch_one_cell("select officepos from sys_user where uid='$uid'") >= $goal['count'];
		}
		else if ($goal['type'] == 18)   //爵位
		{
			$nobility = sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
			//推恩
			$nobility = getBufferNobility($uid,$nobility);
			return  $nobility >= $goal['count'];
		}
		else if ($goal['type'] == 19)
		{
			return sql_fetch_one_cell("select money from sys_user where uid='$uid'") >= $goal['count'];
		}
		else if ($goal['type']==20)
		{
			return sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type>0")>=$goal['count'];
		}
		else if ($goal['type']==21)
		{
			$union_id  = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
			if ($union_id == 0) return false;
			return sql_fetch_one_cell("select count(*) from sys_city where type>0 and uid in (select uid from sys_user where union_id='$union_id')")>=$goal['count'];
		}
		else if ($goal['type'] == 22)
		{
			return sql_fetch_one_cell("select money from sys_user where uid='$uid'") >= $goal['count'];
		}
		else if ($goal['type'] == 122)
		{
			return sql_fetch_one_cell("select money from sys_user where uid='$uid'") >= $goal['count'];
		}
		else if (($goal['type'] >= 12)&&($goal['type'] <= 15))  //四种基础产量
		{
			$lastcid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
			if ($goal['type'] == 12)
			{
				return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_FARMLAND) >= $goal['count'];
			}
			else if ($goal['type'] == 13)
			{
				return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_WOOD) >= $goal['count'];
			}
			else if ($goal['type'] == 14)
			{
				return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_ROCK) >= $goal['count'];
			}
			else if ($goal['type'] == 15)
			{
				return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_IRON) >= $goal['count'];
			}
		}
		else if ($goal['type'] ==31 ){
			//累计获得50次战场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}

			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and quittime>=$start and quittime<=$end and (battleid=1001 or battleid=2001)");
			if ($count>=50){
				return true;
			}
		}
		else if ($goal['type'] ==32 ){
			//在黄巾之乱战场（难度10级）中获得1场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;

			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end");
			if (!empty($ret)){
				return false;
			}
			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and level=10 and battleid=1001 and quittime>=$start and quittime<=$end");
			if ($count>=1){
				return true;
			}
		}
		else if ($goal['type'] ==33 ){
			//在黄巾之乱战场获得10场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}
			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and  battleid=1001 and quittime>=$start and quittime<=$end");
			if ($count>=10){
				return true;
			}
		}
		else if ($goal['type'] ==34 ){
			//在官渡之战战场（难度10级）中作为袁军获得1场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}

			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and unionid=3 and level=10 and battleid=2001 and quittime>=$start and quittime<=$end");
			if ($count>=1){
				return true;
			}
		}
		else if ($goal['type'] ==35 ){
			//在官渡之战战场中作为袁军获得10场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}

			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and unionid=3 and battleid=2001 and quittime>=$start and quittime<=$end");
			if ($count>=10){
				return true;
			}

		}
		else if ($goal['type'] ==36 ){
			//在官渡之战战场（难度10级）中作为曹军获得1场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}

			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and unionid=4 and level=10 and battleid=2001 and quittime>=$start and quittime<=$end");
			if ($count>=1){
				return true;
			}
		}
		else if ($goal['type'] ==37 ){
			//在官渡之战战场中作为曹军获得10场胜利
			$time = sql_fetch_one_cell("select unix_timestamp()");
			$start = $time - $time%86400 + 1;
			$end   = $start+86400;
			$ret=sql_fetch_one_cell("select uid from log_everyday_task where uid=$uid and tid=".$goal['tid']." and gettime>=$start and gettime<=$end ");
			if (!empty($ret)){
				return false;
			}

			$start = sql_fetch_one_cell("select gettime from log_everyday_task where uid=$uid and tid=".$goal['tid']);
			$end = $time;
			if (empty($start)){
				$start = 0;
			}

			$count = sql_fetch_one_cell("select count(*) from log_battle_honour where uid=$uid and result=0 and unionid=4 and battleid=2001 and quittime>=$start and quittime<=$end");
			if ($count>=10){
				return true;
			}

		}
		else if ( $goal['type'] ==38 ){
			//获得1次剧情战场胜利
			return sql_fetch_one_cell("select uid from log_battle_honour where uid=$uid and battleid=1001 and result=0 limit 1");
		}
		else if ( $goal['type'] ==39){
			//获得1次据点战场胜利
			return sql_fetch_one_cell("select uid from log_battle_honour where uid=$uid and battleid=2001 and result=0 limit 1");
		}
		else
		{
			$cityres = sql_fetch_one("select c.* from mem_city_resource c,sys_user u where c.cid=u.lastcid and u.uid='$uid'");
			switch ($goal['type'])
			{
				case 1:
					return $cityres['gold'] >= $goal['count'];
				case 2:
					return $cityres['food'] >= $goal['count'];
				case 3:
					return $cityres['wood'] >= $goal['count'];
				case 4:
					return $cityres['rock'] >= $goal['count'];
				case 5:
					return $cityres['iron'] >= $goal['count'];
				case 6:
					return $cityres['people'] >= $goal['count'];
				case 7:
					return $cityres['morale'] >= $goal['count'];
				case 8:
					return $cityres['complaint'] >= $goal['count'];
				case 10:    //人口上限
					return $cityres['people_max'] >= $goal['count'];
				case 16:    //黄金产量
					return ($cityres['people']*$cityres['tax']*0.01) >= $goal['count'];
			}
		}
	}
	else if ($goal['sort'] == 2)   //2:宝物  
	{
		return sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$goal[type]'") >= $goal['count'];
	}
	else if ($goal['sort'] == 3)  //3:军队  
	{
		return sql_fetch_one_cell("select count from sys_city_soldier s,sys_user u where u.uid='$uid' and u.lastcid=s.cid and s.sid='$goal[type]'") >= $goal['count'];
	}
	else if ($goal['sort'] == 4)   //4:城防  
	{
		return sql_fetch_one_cell("select count from sys_city_defence d,sys_user u where u.uid='$uid' and u.lastcid=d.cid and d.did='$goal[type]'") >= $goal['count'];
	}
	else if ($goal['sort'] == 5)  //5:任务物品  
	{
		return sql_fetch_one_cell("select count from sys_things where uid='$uid' and tid='$goal[type]'") >= $goal['count'];
	}
	else if ($goal['sort'] == 6)  //6:装备  城池建筑物品
	{
		//if($goal['type'] == 5){
			return sql_fetch_one_cell("select count(1) from sys_building b,sys_city c where c.uid='$uid' and b.cid=c.cid and b.level>='$goal[count]' and b.bid='$goal[type]'") >0;
		//}else{
		//	return sql_fetch_one_cell("select count(1) from sys_user_armor where uid='$uid' and hid=0 and armorid='$goal[type]'") >= $goal['count'];
		//}
		
	}else if ($goal['sort'] == 7)
	{
			return sql_fetch_one_cell("select level from sys_technic where uid='$uid' and tid='$goal[type]'")>$goal['count'];
	}
	else if ($goal['sort'] == 8)	//8:名城
	{
		return sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type='$goal[type]'")>=$goal['count'];
	}
	else if($goal['sort'] == 9)	//9:名将
	{
		return sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and npcid='$goal[type]'")>=$goal['count'];
	}
	else if($goal['sort'] == 101)	//101:活动临时事件
	{
		return sql_fetch_one_cell("select count from temp_act_event where uid='$uid' and eid='$goal[type]'")>=$goal['count'];
	}
	return false;
}
function checkTaskComplete($uid,&$tasklist)
{
	$completeTask = false;
	$firstSet = true;


	foreach($tasklist as &$task)
	{
		$goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$task[id]'");
		//file_put_contents('c:/tasks', var_export($goals,true));	
		$complete = true;
		foreach($goals as $goal)
		{
			if (!checkGoalComplete($uid,$goal))
			{
				//file_put_contents('c:/tasks1', $complete);
				$complete = false;
				break;
			}
		}

		if (($goal["tid"]>=60000&&$goal["tid"]<=60024) || ($goal["tid"]>=60100&&$goal["tid"]<=60144)){
			if (empty($goal["uid"])){
				$complete = false;
			}
			else{
				$complete = true;

			}
		}
			
		$task['state'] = $complete;

		
		if( true==$complete && true==$firstSet){
			sql_query("insert into sys_alarm (`uid`,`task`) values ('$uid',1) on duplicate key update `task`=1");
			$firstSet=false;
		}
	}
	
	return $firstSet;
}

function dropTask($uid,$param)
{
	$taskgroup=array_shift($param);
	if ($taskgroup>=200000){
		$param1=array();
		$param1[] = 4;
		sql_query("delete from sys_user_reward_task where uid='$uid' and tid=$taskgroup");
		sql_query("update sys_pub_reward_task set number=number-1 where id='$taskgroup'");
		return getTaskTypeGroupList($uid,$param1);
	}

	$npcid=intval(floor(($taskgroup-20000)/10));
	$taskid1=20000+$npcid;
	$taskid2=30000+$npcid;
	sql_query("delete from sys_user_goal where uid='$uid' and gid in (select id from cfg_task_goal where (tid='$taskid1' or tid='$taskid2'))");
	sql_query("delete from sys_user_task where uid='$uid' and (tid='$taskid1' or tid='$taskid2')");
	$param2=array();
	$param2[]=2;
	return getTaskTypeGroupList($uid,$param2);
}

function getTaskTypeGroupList($uid,$param)
{

	//checkCompletedTaskList($uid);

	$type=intval(array_shift($param));
	if( $type<0||$type>7 || $type==5) $type=0;
	$ret = array();
	if($type<=3 || $type>=6){
		$groupStr = sql_fetch_one_cell("select group_concat(distinct(t.`group`)) as id from sys_user_task u,cfg_task t where u.tid=t.id and u.uid='$uid' and u.state=0");		
		$ret[] =  sql_fetch_rows("select * from cfg_task_group where id in ($groupStr) and type='$type' order by id");
	}
	else if($type==4){
		//委托任务
			
		$tasks =  sql_fetch_rows("select * from sys_pub_reward_task where id in (select tid from sys_user_reward_task where uid='$uid' and state<=0 and tid>=200000)");
		$index=0;
		$ret[]=array();
		foreach($tasks as $task){
			$task['groupid']=$task['id'];
			$task['description']=$GLOBALS['recordTask']['task_group_description'];
			if($task['type']=="0")
			$task['name']=$GLOBALS['recordTask']['task_group_name_0'];
			else if($task['type']=="1")
			$task['name']=$GLOBALS['recordTask']['task_group_name_1'];
			else if($task['type']=="2")
			$task['name']=$GLOBALS['recordTask']['task_group_name_2'];
			$ret[0][$index++]=$task;
		}
	}

	return $ret;
}

function getTaskList($uid,$param)
{
	$groupid = array_shift($param);

	if($groupid>=200000){
		return getUserRewardTaskList($uid,$groupid);
	}

	$tasklist = sql_fetch_rows("select t.* from sys_user_task u,cfg_task t where u.uid='$uid' and u.tid=t.id and t.group='$groupid' and u.state=0");
	
	checkTaskComplete($uid,$tasklist);
	$ret = array();
	$ret[] = $tasklist;

	return $ret;
}

function checkCompletedTaskList( $uid )
{
	$tasklist = sql_fetch_rows("select t.* from sys_user_task u,cfg_task t where u.uid='$uid' and u.tid=t.id and u.state=0");
	$firstSet = checkTaskComplete($uid,$tasklist);


	if (true==$firstSet){
		//check 委托任务
		$tasks=sql_fetch_rows("select * from sys_user_reward_task where uid='$uid' and state=-1");
		if(!empty($tasks)){
			sql_query("insert into sys_alarm (`uid`,`task`) values ('$uid',1) on duplicate key update `task`=1");
		}
		else{
			sql_query("update sys_alarm set `task`='0' where `uid`='$uid'");
		}
	}
}

function getUserRewardTaskList($uid,$groupid){
	$ret=array();
	$tasks=sql_fetch_rows("select * from sys_user_reward_task where uid='$uid' and tid='$groupid' and state<=0");
	$ret[]=array();
	//checkTaskComplete($uid,$tasklist);
	$index=0;
	foreach($tasks as $task){
			
		if($task['type']=="0")
		$task['name']=$GLOBALS['recordTask']['task_group_name_0'];
		else if($task['type']=="1")
		$task['name']=$GLOBALS['recordTask']['task_group_name_1'];
		else if($task['type']=="2")
		$task['name']=$GLOBALS['recordTask']['task_group_name_2'];

		if($task['state']=="0"){
			//未完成
			$task['state']=false;
		}else if($task['state']=="-1"){
			//完成未领取奖励
			$task['state']=true;
		}
		$task['id']=$task['tid'];
		$ret[0][$index++]=$task;
	}
	return $ret;
}
function getRewardTaskDetail($uid,$tid,$param)
{
	$ret = array();

	$taskinfo = sql_fetch_one("select * from sys_pub_reward_task where id='$tid'");
	$todo = $GLOBALS['recordTask']['task_content_prefix'].$taskinfo['todo'];
	$taskinfo['content']=$todo;
	$taskinfo['todo']=$GLOBALS['recordTask']['task_group_description'];

	if($taskinfo['state']=="0" || $taskinfo['finishuid']!=$uid){
		//未完成
		$taskinfo['state']=false;
	}else if($taskinfo['state']=="-1"){
		//完成为领取奖励
		$taskinfo['state']=true;
	}
	if($taskinfo['type']=="0")
	$taskinfo['name']=$GLOBALS['recordTask']['task_group_name_0'];
	else if($taskinfo['type']=="1")
	$taskinfo['name']=$GLOBALS['recordTask']['task_group_name_1'];
	else if($taskinfo['type']=="2")
	$taskinfo['name']=$GLOBALS['recordTask']['task_group_name_2'];

	$ret[]=$taskinfo;

	$goals=array();
	$goals[0]["content"]=genRewardTaskGoal($taskinfo['targetcid'],$taskinfo['targetname'],$taskinfo['type'],$taskinfo['goal']);
	$goals[0]["state"]=$taskinfo['state'];

	$reward=array();
	$reward[0]['sort']=1;
	$reward[0]['type']=20;
	$reward[0]['count']=$taskinfo["money"];

	$ret[]=$goals;
	$ret[]=$reward;


	return $ret;
}
function genRewardTaskContent($targetcid,$targetname,$endtime,$type,$goal){
	$result="";
	$pos=getPosition($targetcid);
	$time=MakeEndTime($endtime);
	if($type==0)
	return sprintf($GLOBALS['recordTask']['task_content_0'],"",$time,$targetname,$pos,$goal);
	else if($type==1)
	return sprintf($GLOBALS['recordTask']['task_content_1'],"",$time,$targetname,$pos,$goal);
	else if($type==2){
		if($goal==0)
		return sprintf($GLOBALS['recordTask']['task_content_2'],"",$time,$targetname,$pos);
		else if($goal==1)
		return sprintf($GLOBALS['recordTask']['task_content_3'],"",$time,$targetname,$pos);
	}
}
function genRewardTaskGoal($targetcid,$targetname,$type,$goal){
	$pos=getPosition($targetcid);
	if($type==0)
	return sprintf($GLOBALS['recordTask']['goal_0'],$targetname,$pos,$goal);
	else if($type==1)
	return sprintf($GLOBALS['recordTask']['goal_1'],$targetname,$pos,$goal);
	else if($type==2){
		if($goal==0)
		return sprintf($GLOBALS['recordTask']['goal_2'],$targetname,$pos);
		else if($goal==1)
		return sprintf($GLOBALS['recordTask']['goal_3'],$targetname,$pos);
	}
}


function getTaskDetail($uid,$param)
{
	$tid = array_shift($param);
	//悬赏任务
	if($tid>=200000){
		return getRewardTaskDetail($uid,$tid,$param);
	}
	$ret = array();

	$ret[] = sql_fetch_one("select * from cfg_task where id='$tid'");

	$goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$tid' order by g.id");

	foreach ($goals as &$goal)
	{
		$goal['state'] = checkGoalComplete($uid,$goal);
		if (($goal["tid"]>=60000&&$goal["tid"]<=60024) || ($goal["tid"]>=60100&&$goal["tid"]<=60144)){
			if (empty($goal["uid"])){
				$goal['state']=false;
			}
			else{
				$goal['state']=true;
			}
		}
	}

	if ($tid == 250)    //食君之禄
	{
		foreach ($goals as &$goal)
		{
			$goal['content'] = sql_fetch_one_cell("select p.name from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'");
		}
	}
	else if ($tid == 251)   //采食封邑
	{
		foreach ($goals as &$goal)
		{
			$goal['content'] = sql_fetch_one_cell("select n.name from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");
		}
	}


	$ret[] = $goals;

	if ($tid == 250)//食君之禄   
	{
		$salary = sql_fetch_one_cell("select p.salary from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'");
		$reward = array();
		$reward[] = array('sort'=>1,'type'=>1,'count'=>$salary);
		$ret[] = $reward;
	}
	else if ($tid == 251)  //采食封邑
	{
		$salary = sql_fetch_one_cell("select n.salary from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");
		$reward = array();
		$reward[] = array('sort'=>1,'type'=>2,'count'=>$salary);
		$reward[] = array('sort'=>1,'type'=>3,'count'=>$salary);
		$reward[] = array('sort'=>1,'type'=>4,'count'=>$salary);
		$reward[] = array('sort'=>1,'type'=>5,'count'=>$salary);
		$ret[] = $reward;
	}
	else if ($tid==279)	//共享利益
	{
		$gold=getUnionFamousCityGold($uid);
		$reward=array();
		$reward[] = array('sort'=>1,'type'=>1,'count'=>$gold);
		$ret[] = $reward;
	}
	else
	{
		$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid' order by type asc");
		$battleLevel = 0;
		foreach( $rewards as &$reward){
			if ( ($reward["tid"]>=60000 && $reward["tid"]<=60024) || ($reward["tid"]>=60100 && $reward["tid"]<=60144) ){
				if ($battleLevel==0){
					$battleLevel = sql_fetch_rows("select level from sys_user_battle_state where uid='$uid'");
				}
				$reward["count"] = $battleLevel[0]["level"]*$battleLevel[0]["level"]*$reward["count"];
				//if ($reward["type"]==9){
				//	$reward["count"] = $battleLevel[0]["level"]*$reward["count"];
				//}
			}
		}
		$ret[] = $rewards;
	}


	return $ret;
}

function giveResource($uid,$cid,$type,$count,$log_money_type=60,$retmsg=false)
{
	$msg="";
	switch($type)
	{
		case 1:
			sql_query("update mem_city_resource set gold=gold+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_gold'],$count);
			break;
		case 2:
			sql_query("update mem_city_resource set food=food+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_food'],$count);
			break;
		case 3:
			sql_query("update mem_city_resource set wood=wood+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_wood'],$count);
			break;
		case 4:
			sql_query("update mem_city_resource set rock=rock+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_rock'],$count);
			break;
		case 5:
			sql_query("update mem_city_resource set iron=iron+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_iron'],$count);
			break;
		case 6:
			sql_query("update mem_city_resource set people=people+'$count' where cid='$cid'");
			if($retmsg)$msg=sprintf($GLOBALS['resPackage']['gain_people'],$count);
			break;
		case 7:
			if($retmsg)
			$msg=sprintf($GLOBALS['resPackage']['gain_morale'],sql_fetch_one_cell("select LEAST(100-morale,'$count') from mem_city_resource where cid='$cid' "));
			//TODO Morale
			sql_query("update mem_city_resource set morale=LEAST(100,morale+'$count'),`people_stable`=`people_max` * LEAST(100,morale+'$count') * 0.01 where cid='$cid'");
			break;
		case 8:
			sql_query("update mem_city_resource set complaint=GREATEST(0,complaint+'$count') where cid='$cid'");
			break;
		case 9:
			if($retmsg) $msg=sprintf($GLOBALS['resPackage']['gain_prestige'],sql_fetch_one_cell("select GREATEST(100-morale,'$count') from mem_city_resource where cid='$cid' "));
			sql_query("update sys_user set prestige=prestige+'$count',warprestige=warprestige+'$count' where uid='$uid'");
			break;
		case 17:
			sql_query("update sys_user set officepos='$count' where uid='$uid'");
			if($retmsg) $msg=sprintf($GLOBALS['resPackage']['gain_officepos'],sql_fetch_one_cell("select name from cfg_office_pos where id='$count' "));
			$officepos = sql_fetch_one_cell("select name from cfg_office_pos where id='$count'");
			if (defined("PASSTYPE")){
				require_once 'game/agents/AgentServiceFactory.php';
				AgentServiceFactory::getInstance($uid)->addOfficePosEvent($officepos);
			}
			if(defined("USER_FOR_51") && USER_FOR_51){
				require_once("51utils.php");
				return add51OfficePosEvent($officepos);
			}
			break;
		case 18:
			sql_query("update sys_user set nobility='$count' where uid='$uid'");
			if($retmsg) $msg=sprintf($GLOBALS['resPackage']['gain_nobility'],sql_fetch_one_cell("select name from cfg_nobility where id='$count' "));
			$nobilityname = sql_fetch_one_cell("select name from cfg_nobility where id='$count'");
			if (defined("PASSTYPE")){
				require_once 'game/agents/AgentServiceFactory.php';
				AgentServiceFactory::getInstance($uid)->addNobilityEvent($nobilityname);
			}
			if(defined("USER_FOR_51") && USER_FOR_51){
				require_once("51utils.php");
				return add51NobilityEvent($nobilityname);
			}
			break;
		case 19:
			addGift($uid,$count,$log_money_type);
			if($retmsg) $msg=sprintf($GLOBALS['resPackage']['gain_yuanbao'],$count);
			break;
		case 20:
			sql_query("update sys_user set money=money+'$count' where uid='$uid'");
			sql_query("insert into log_money (uid,count,time,type) values ('$uid','$count',unix_timestamp(),'54')");
			break;
		case 122:
			sql_query("update sys_user set money=money+'$count' where uid='$uid'");
			sql_query("insert into log_money (uid,count,time,type) values ('$uid','$count',unix_timestamp(),'$log_money_type')");
			break;				
		case 30:
			sql_query("update sys_user set honour=honour+'$count' where uid='$uid'");
			break;
	}
	return $msg;
}
function giveGoods($uid,$type,$count,$log_type=4,$retmsg=false)
{
	$msg="";
	addGoods($uid,$type,$count,$log_type);
	if($retmsg){
		$goodname=sql_fetch_one_cell("select name from cfg_goods where gid = $type");
		if ($count>1) $goodname=$goodname."*".$count;
		$msg=sprintf($GLOBALS['resPackage']['gain_goods'],$goodname);
	}
	return $msg;
}
function giveArmy($cid,$type,$count,$retmsg=false)
{
	$msg="";
	sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$type','$count') on duplicate key update `count`=`count`+'$count'");
	updateCityResourceAdd($cid);
	if($retmsg){
		$name=sql_fetch_one_cell("select name from cfg_soldier where sid = $type");
		if ($count>1) $name=$name."*".$count;
		$msg=sprintf($GLOBALS['resPackage']['gain_soldier'],$name);
	}
	return $msg;
}
function giveDefence($cid,$type,$count,$retmsg=false)
{
	$msg="";
	sql_query("insert into sys_city_defence (`cid`,`did`,`count`) values ('$cid','$type','$count') on duplicate key update `count`=`count`+'$count'");
	if($retmsg){
		$name=sql_fetch_one_cell("select name from cfg_defence where did = $type");
		if ($count>1) $name=$name."*".$count;
		$msg=sprintf($GLOBALS['resPackage']['gain_defence'],$name);
	}
	return $msg;
}
function giveThings($uid,$type,$count,$log_type=4,$retmsg=false)
{
	$msg="";
	addThings($uid,$type,$count,$log_type);
	if($retmsg){
		$name=sql_fetch_one_cell("select name from cfg_things where tid = $type");
		if ($count>1) $name=$name."*".$count;
		$msg=sprintf($GLOBALS['resPackage']['gain_things'],$name);
	}
	return $msg;
}
function cutArmor($uid,$id,$count,$log_type=4)
{
	sql_query("delete from sys_user_armor where uid='$uid' and armorid='$id' and hid=0 limit $count");
	sql_query("insert into log_armor (uid,armorid,count,time,type) values ($uid,$id,-$count,unix_timestamp(),$log_type)");
}
function giveArmor($uid,$type,$count,$log_type=4,$retmsg=false)
{
	$msg="";
	$armor=sql_fetch_one("select * from cfg_armor where id='$type'");
	addArmor($uid,$armor,$count,$log_type);
	sql_query("insert into log_armor (uid,armorid,count,time,type) values ($uid,$armor[id],$count,unix_timestamp(),$log_type)");
	if($retmsg){
		$name=sql_fetch_one_cell("select name from cfg_armor where id = $type");
		if ($count>1) $name=$name."*".$count;
		$msg=sprintf($GLOBALS['resPackage']['gain_armor'],$name);
	}
	return $msg;
}
function giveMoney($uid,$count,$log_money_type=60,$retmsg=false){
	$msg="";
	addGift($uid,$count,$log_money_type);
	if($retmsg) $msg=sprintf($GLOBALS['resPackage']['gain_yuanbao'],$count);
	return $msg;
}
function cutActEvent($uid,$type,$eid,$count,$log_type=4)
{
	sql_query("insert into temp_act_event (uid,type,eid,count) values ('$uid','$type','$eid','-$count') on duplicate key update count=count-$count");
}
//给任务奖励
function giveReward($uid,$cid,$reward,$log_type=4,$log_money_type=60,$retmsg=false)
{
	if ($reward['sort'] == 1)
	{
		return  giveResource($uid,$cid,$reward['type'],$reward['count'],$log_money_type,$retmsg);
	}
	else if ($reward['sort'] == 2)
	{
		if ($reward["type"]==0)
		return giveMoney($uid,$reward['count'],$log_money_type,$retmsg);
		return    giveGoods($uid,$reward['type'],$reward['count'],$log_type,$retmsg);
	}
	else if ($reward['sort'] == 3)
	{
		return  giveArmy($cid,$reward['type'],$reward['count'],$retmsg);
	}
	else if ($reward['sort'] == 4)
	{
		return  giveDefence($cid,$reward['type'],$reward['count'],$retmsg);
	}
	else if ($reward['sort'] == 5)
	{
		return  giveThings($uid,$reward['type'],$reward['count'],$log_type,$retmsg);
	}
	else if ($reward['sort']==6)
	{
		return  giveArmor($uid,$reward['type'],$reward['count'],$log_type,$retmsg);
	}
}
function reduceGoal($uid,$cid,$goal)
{
	switch ($goal['sort'])
	{
		case 1:
			giveResource($uid,$cid,$goal['type'],-$goal['count']);
			break;
		case 2:
			giveGoods($uid,$goal['type'],-$goal['count']);
			break;
		case 3:
			giveArmy($cid,$goal['type'],-$goal['count']);
			break;
		case 4:
			giveDefence($cid,$goal['type'],-$goal['count']);
			break;
		case 5:
			giveThings($uid,$goal['type'],-$goal['count']);
			break;
		case 6:
			cutArmor($uid,$goal['type'],$goal['count']);
			break;
		case 101://活动临时事件
			cutActEvent($uid,$goal['sort'],$goal['type'],$goal['count']);
	}
}

function taskCanRecomplete($tid)
{
	return (($tid>10000&&$tid<10600)||($tid>11000&&$tid<15000)||($tid>100021&&$tid<100025)||($tid>100140&&$tid<100144)||($tid==100171)||($tid==100201));
}

function isHuangJinJuanXian($tid)//黄巾捐献任务
{
	return ($tid>11000&&$tid<15000);
}

function isHuangJinDuiHuan($tid)//黄巾捐献任务的兑换任务
{
	return ($tid>10000&&$tid<10500);
}

function isHuangJinKill($tid)//消灭黄金军任务，在捐献任务完成后触发
{
	return ($tid>10500&&$tid<11000);
}

function triggerHuangJinCityTask()
{
	sql_query("insert into sys_user_task (uid,tid,state) (select uid,10501,0 from sys_user_task where tid=243 and state=1) on duplicate key update state=0");
}
//取得用户悬赏任务的奖励
function getUserRewardTaskReward($uid,$tid){

	$task = sql_fetch_one("select * from sys_user_reward_task where `tid`='$tid' and uid=$uid");
	if(empty($task)){
		throw new Exception($GLOBALS['getReward']['already_got']);
	}

	if($task["state"]==0){
		throw new Exception($GLOBALS['getReward']['task_not_finished']);
	}else if($task["state"]==1){
		throw new Exception($GLOBALS['getReward']['already_got']);
	}else if($task["state"]==-1){
		//未领取奖励状态
		$reward=sql_fetch_one("select uid,todo,money,targetcid from sys_pub_reward_task where id=$task[tid] ");
		if(empty($reward)){
			//已经过期，或者非法
			throw new Exception($GLOBALS['getReward']['already_got']);
		}
		else{
			if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
			giveResource($uid,-1,20,$reward['money']*9/10);
			//sql_query("update sys_user_reward_task set state=1 where tid=$task[tid] and uid=$uid ");
			//sql_query("update sys_pub_reward_task set state=1 where id=$tid");
			sql_query("delete from  sys_user_reward_task where tid=$task[tid]");
			sql_query("delete from  sys_pub_reward_task  where id=$tid");
			//sql_query("insert into log_reward_city_temp values($uid,0,1) on duplicate key update count=count+1;");
			$username=sql_fetch_one_cell("select name from sys_user where uid=$uid ");
			//发送报告
			$content = sprintf($GLOBALS['recordTask']['report'],$username,$reward['todo'],$reward['money']);
			sendReport($reward['uid'],"reward_task",34,$task['targetcid'],$task['targetcid'],$content);
			unlockUser($uid);
		}
	}

	$ret=array();
	$ret[] = 1;
	return $ret;
}
function getReward($uid,$param)
{
	$tid = array_shift($param);
	if($tid>=200000){
		//用户悬赏任务
		return getUserRewardTaskReward($uid,$tid);
	}
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);

	$msg = "";

	//非黄巾史诗任务，只能交一次
	if (sql_check("select * from sys_user_task where `uid`='$uid' and `tid`='$tid' and state=1")&&(!taskCanRecomplete($tid)))
	{
		throw new Exception($GLOBALS['getReward']['already_got']);
	}
	$task = sql_fetch_one("select * from cfg_task where `id`='$tid'");
	$inform = intval($task["inform"]);
	if ($inform) $retmsg=true;
	$goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$tid'");
	$complete = true;
	foreach($goals as $goal)
	{
		if (!checkGoalComplete($uid,$goal))
		{
			$complete = false;
			break;
		}
	}

	if (($goal["tid"]>=60000&&$goal["tid"]<=60024) || ($goal["tid"]>=60100&&$goal["tid"]<=60144)){
		if (!empty($goal["uid"])){
			$complete = true;
		}
	}

	if (!$complete) throw new Exception($GLOBALS['getReward']['task_not_finished']);

	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	if ($tid == 250)//食君之禄   
	{
		$salary = sql_fetch_one_cell("select p.salary from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'");
		giveResource($uid,$cid,1,$salary);
	}
	else if ($tid == 251)  //采食封邑
	{
		$salary = sql_fetch_one_cell("select n.salary from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");
		giveResource($uid,$cid,2,$salary);
		giveResource($uid,$cid,3,$salary);
		giveResource($uid,$cid,4,$salary);
		giveResource($uid,$cid,5,$salary);
	}
	else if($tid==279)
	{
		$gold=getUnionFamousCityGold($uid);
		giveResource($uid,$cid,1,$gold);
	}
	else if ($tid>11000&&$tid<15000) //黄巾军史诗捐献任务
	{
		$progress=sql_fetch_one("select maxvalue,curvalue from huangjin_progress where tid='$tid'");
		if($progress['curvalue']>=$progress['maxvalue'])
		{
			//sql_query("update sys_user_task set state=1 where uid='$uid' and tid='$tid'");
			sql_query("update sys_user_task set state=1 where tid='$tid'");
			$ret=array();
			$ret[] = 0;
			$ret[]=$GLOBALS['getReward']['global_task_end'];
			return $ret;
		}
		$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
		$union_id=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
		foreach($rewards as $reward)
		{
			if($reward['sort']==5) //记录捐献后获得的物品
			{
				if($reward['type']==11001)
				{
					sql_query("insert into huangjin_task_log (uid,jungong) values ('$uid',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,jungong) values ('$union_id',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
				}
				else if($reward['type']==12001)
				{
					sql_query("insert into huangjin_task_log (uid,juanxian) values ('$uid',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,juanxian) values ('$union_id',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
				}
				else if($reward['type']==13001)
				{
					sql_query("insert into huangjin_task_log (uid,qinwang) values ('$uid',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,qinwang) values ('$union_id',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
				}
				else if($reward['type']==14001)
				{
					sql_query("insert into huangjin_task_log (uid,gongpin) values ('$uid',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,gongpin) values ('$union_id',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
				}
			}
			giveReward($uid,$cid,$reward);
		}
		sql_query("update huangjin_progress set curvalue=LEAST(maxvalue,curvalue+1) where tid='$tid'");
		if($progress['curvalue']>=$progress['maxvalue']-1)
		{
			sql_query("update sys_user_task set state=1 where tid='$tid'");
			$unfinish=sql_fetch_one_cell("select count(*) from huangjin_progress where curvalue<maxvalue");
			if($unfinish==0)
			{
				sql_query("update mem_state set value=1 where state=5");
				triggerHuangJinCityTask();
			}
		}
	}
	else
	{
		$log_type=4;
		$log_money_type=60;
		$retmsg=false;
		if ($inform==1) $retmsg=true;
			
		$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
		$battleLevel = 0;
		foreach($rewards as $reward)
		{
			if ( ($reward["tid"]>=60000&&$reward["tid"]<=60024) || ($reward["tid"]>=60100&&$reward["tid"]<=60144) ){
				if ($battleLevel==0){
					$battleLevel = sql_fetch_rows("select level from sys_user_battle_state where uid='$uid'");
				}
				$reward["count"] = $battleLevel[0]["level"]*$battleLevel[0]["level"]*$reward["count"];
				//if ($reward["type"]==9){
				//	$reward["count"] = $battleLevel[0]["level"]*$reward["count"];
				//}
			}
			$msg=$msg.giveReward($uid,$cid,$reward,$log_type,$log_money_type,$retmsg);
		}

		if ( ($tid==6001) || ($tid==6101) || ($tid==6102) || ($tid==6201) || ($tid==6202) || ($tid==6211) || ($tid==6212) ){
			sql_query("insert into log_everyday_task values($uid,$tid,unix_timestamp()) on duplicate key update gettime=unix_timestamp()");
		}
	}

	if(!taskCanRecomplete($tid)) //黄巾军史诗任务，永久开放
	{
		sql_query("insert into sys_user_task (`uid`,`tid`,`state`) values ('$uid','$tid',1) on duplicate key update `state`=1");
	}
	//如果任务目标要扣除相应的数据，则在这里扣除
	$goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
	foreach($goals as $goal)
	{
		if ($goal['reduce'] == 1)
		{
			reduceGoal($uid,$cid,$goal);
		}
	}

	$triggers = sql_fetch_rows("select * from cfg_task where pretid='$task[id]'");
	$huangjinProgress=sql_fetch_one_cell("select value from mem_state where state=5");
	foreach($triggers as $trigger)
	{
		$triggerout=true;
		$state = $trigger['default'];
		if($tid==243)
		{
			if(!empty($huangjinProgress)) //如果黄巾史诗任务已经完成了，就不勾出捐献和兑换任务了
			{
				if(isHuangJinJuanXian($trigger['id'])||isHuangJinDuiHuan($trigger['id']))
				{
					$triggerout=false;
				}
			}
			if($tid==10501) $triggerout=(!empty($huangjinProgress));
		}
		if($triggerout)
		{
			sql_query("insert into sys_user_task (uid,tid,state) values ('$uid','$trigger[id]','$state') on duplicate key update state='$state'");
		}
	}

	unlockUser($uid);


	$inform = intval($task["inform"]);
	if($inform==1)	//发送广播
	{
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$content="恭喜玩家【".$uname."】完成".$task["name"]."任务，".$msg."！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (1,1,unix_timestamp(),unix_timestamp()+300,1800,2,16776960,'$content')");
	}
	$ret=array();
	$ret[] = 1;
	return $ret;
}



function getMultiReward($uid,$param)
{
	$tid = array_shift($param);
	$cnt = intval(array_shift($param));
	if($cnt<=0||$cnt>=1000)
	{
		throw new Exception($GLOBALS['getReward']['invalid_count']);
	}
	else if($cnt==1)
	{
		$param2=array();
		$param2[]=$tid;
		return getReward($uid,$param2);
	}
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);

	//非黄巾史诗任务，只能交一次
	if (!taskCanRecomplete($tid))
	{
		throw new Exception($GLOBALS['getReward']['not_allowed_multi']);
	}
	$task = sql_fetch_one("select * from cfg_task where `id`='$tid'");

	$goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
	$complete = true;
	foreach($goals as &$goal)
	{
		$goal['count']=$cnt*$goal['count'];
		if (!checkGoalComplete($uid,$goal))
		{
			$complete = false;
			break;
		}
	}
	if (!$complete)
	{
		$msg=sprintf($GLOBALS['getReward']['not_enough_things'],$cnt);
		throw new Exception($msg);
	}
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	if (isHuangJinJuanXian($tid)) //黄巾军史诗捐献任务
	{
		$progress=sql_fetch_one("select maxvalue,curvalue from huangjin_progress where tid='$tid'");
		if($progress['curvalue']+$cnt>=$progress['maxvalue'])
		{
			throw new Exception($GLOBALS['getReward']['not_enough_remain']);
		}
		$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
		$union_id=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
		foreach($rewards as &$reward)
		{
			$reward['count']=$reward['count']*$cnt;
			if($reward['sort']==5) //记录捐献后获得的物品
			{
				if($reward['type']==11001)
				{
					sql_query("insert into huangjin_task_log (uid,jungong) values ('$uid',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,jungong) values ('$union_id',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
				}
				else if($reward['type']==12001)
				{
					sql_query("insert into huangjin_task_log (uid,juanxian) values ('$uid',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,juanxian) values ('$union_id',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
				}
				else if($reward['type']==13001)
				{
					sql_query("insert into huangjin_task_log (uid,qinwang) values ('$uid',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,qinwang) values ('$union_id',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
				}
				else if($reward['type']==14001)
				{
					sql_query("insert into huangjin_task_log (uid,gongpin) values ('$uid',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
					if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,gongpin) values ('$union_id',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
				}
			}
			giveReward($uid,$cid,$reward);
		}
		sql_query("update huangjin_progress set curvalue=LEAST(maxvalue,curvalue+$cnt) where tid='$tid'");
		if($progress['curvalue']>=$progress['maxvalue']-$cnt)
		{
			sql_query("update sys_user_task set state=1 where tid='$tid'");
			$unfinish=sql_fetch_one_cell("select count(*) from huangjin_progress where curvalue<maxvalue");
			if($unfinish==0)
			{
				sql_query("update mem_state set value=1 where state=5");
				triggerHuangJinCityTask();
			}
		}
	}
	else
	{
		$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
		foreach($rewards as $reward)
		{
			$reward['count']=$reward['count']*$cnt;
			giveReward($uid,$cid,$reward);
		}
	}
	//如果任务目标要扣除相应的数据，则在这里扣除
	$goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
	foreach($goals as &$goal)
	{
		$goal['count']=$cnt*$goal['count'];
		if ($goal['reduce'] == 1)
		{
			reduceGoal($uid,$cid,$goal);
		}
	}

	unlockUser($uid);

	$ret=array();
	$ret[] = 1;
	return $ret;
}

?>