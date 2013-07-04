<?php
ignore_user_abort(TRUE);
require_once("./interface.php");
require_once("./utils.php");
file_put_contents('cfg', '1');
set_time_limit(0);
$isstart = file_get_contents('cfg');
//7 主将
//1 城守
//8 军师
//$troops = sql_fetch_rows("select id from sys_troops where endtime<=unix_timestamp()");

//foreach ($troops as $key => $value) {
	
//}
//print_r($troops);

while ( $isstart == 1) {
	//服务列表
HandleArmy();//处理招兵
HandleTech();//处理科技
HandleBackTroops();//处理反城队列
HandleBattleTroops();//处理战斗 侦查队列
HandleBattle();//处理战斗
sleep(2);
$isstart = file_get_contents('cfg');
}



//处理队列
function HandleBattleTroops()
{
	$time = time();
	$troops = sql_fetch_rows("select * from sys_troops where `state`=0 and `endtime`-'$time'<=1 and `task`<5");//task5为被动防守出城
	foreach ($troops as $key => $troop) {
			//掠夺
			if($troop['task'] == 3){
				$tid = $troop['id'];
				$bid = buildBattle($tid);//创建战场
				sql_query("update sys_troops set `state`=3,`battleid`='$bid' where `id`='$tid'");//猜想战斗状态码 更新战场id
			}
			//占领
			if($troop['task'] == 4){
				$tid = $troop['id'];
				$bid = buildBattle($tid);//创建战场
				sql_query("update sys_troops set `state`=3,`battleid`='$bid' where `id`='$tid'");//猜想战斗状态码 更新战场id
			}
			//侦查
			if($troop['task'] == 2){
				zhenchaInfo($troop);
			}
			//派遣
			if($troop['task'] == 1){
				//判断对象是不是野地
				$wid = cid2wid($troop['targetcid']);
				$type = sql_fetch_one_cell("select type from mem_world where wid='$wid'");
				if($type == 0){
					//城池
					$hid = $troop['hid'];
					if($hid != 0 && !empty($hid) ){
						sql_query("update sys_city_hero set cid='$troop[targetcid]',state=0 where hid='$hid'");//移动将领
					}
					//加资源
					$resource = $troop['resource'];
					$resources = explode(',', $resource);
					addCityResources($troop['targetcid'],$resources[2],$resources[3],$resources[4],$resources[1],$resources[0]);

					//加士兵
					$soldierArray = explode(",",$soldiers);
					$numSoldiers = array_shift($soldierArray);
					$takeSoldiers = array();    //真正带出去的军队
					for ($i = 0; $i < $numSoldiers; $i++)
					{
						$sid = array_shift($soldierArray);
						$cnt = array_shift($soldierArray);
						if ($cnt < 0) $cnt = 0;
						$takeSoldiers[$sid] = $cnt;
					}	
					addCitySoldiers($cid,$takeSoldiers,true);//加兵

				}else{
					//野地
					sql_query("update sys_troops set state=4 where id='$troop[id]'");
				}
				
			}	
	}
}

//处理战斗
function HandleBattle()
{
	$time = time();
	$battles = sql_fetch_rows("select id from mem_battle where `nexttime`-$time<=1");
	if(!empty($battles))
	{
		foreach ($battles as $key => $battle) {
			if(sql_fetch_one_cell("select state from sys_battle where id='$battle[id]'")==1){
				sql_query("delete from mem_battle where id='$battle[id]'");
			}else{
				updateBattle($battle['id']);
			}
			
		}		
	}


}


//更新战场 传入战场id
function updateBattle($battleid)
{
	if(!isset($_SESSION['battle'][$battleid])){

		$_SESSION['battle'][$battleid]['mem'] = sql_fetch_one("select * from mem_battle where id='$battleid'");

		$_SESSION['battle'][$battleid]['sys'] = sql_fetch_one("select * from sys_battle where id='$battleid'");
	}
	
	$fieldrange = $_SESSION['battle'][$battleid]['mem']['fieldrange'];//战场距离	
	$round = sql_fetch_one_cell("select round from mem_battle where id='$battleid'");//取得回合
	return_new_place($battleid,$round);

}

//取得双方将领的统帅值
function get_heroinfo($hid)
{
	if($hid == 0 || $hid == ''){return 0;}
	$heroinfo = sql_fetch_one("select * from sys_city_hero where hid='$hid'");

	return $heroinfo['level']+$heroinfo['command_base']+$heroinfo['command_add_on'];//返回武将统帅
}

//是否是npc玩家
function is_npc($uid)
{
	if($uid == '' || $uid == 0 || empty($uid))
	{
		return true;
	}

	$user_info = sql_fetch_one("select * from sys_user where `uid`='$uid'");

	if($user_info['group'] != 0)
	{
		return true;//是npc

	}else{
		return false;//正常玩家
	}
}

//战场id 士兵当前位置信息 是否是进攻方 1 进攻方 0防守方 是否是npc
function return_new_place($battleid,$round)
{

	if(!isset($_SESSION['battle'][$battleid])){

		$_SESSION['battle'][$battleid]['mem'] = sql_fetch_one("select * from mem_battle where id='$battleid'");

		$_SESSION['battle'][$battleid]['sys'] = sql_fetch_one("select * from sys_battle where id='$battleid'");		

	}

	$minfo = $_SESSION['battle'][$battleid]['mem'];

	$sinfo = $_SESSION['battle'][$battleid]['sys'];

	$attackpos = $minfo['attackpos'];//攻击方军队位置

	$resistpos = $minfo['resistpos'];//防守放军队位置

	$fieldrange = $minfo['fieldrange'];//战场距离

	$attackuid = $sinfo['attackuid'];//进攻方uid

	$resistuid = $sinfo['resistuid'];//防守方uid

	$attackhid = $minfo['attackhid'];//进攻方将领

	$resisthid = $minfo['resisthid'];//防守方将领

//得到军队速度表

if(!isset($_SESSION['battle'][$battleid]['fightsoldier'])){


	if(isset($_SESSION['battle'][$battleid]['attackspeed'])){
		$attackspeed = $_SESSION['battle'][$battleid]['attackspeed'];
	}else{
		$_SESSION['battle'][$battleid]['attackspeed'] = getspeed($attackhid);//攻击方速度
		$attackspeed = $_SESSION['battle'][$battleid]['attackspeed'];
	}

	if(isset($_SESSION['battle'][$battleid]['resistspeed'])){
		$resistspeed = $_SESSION['battle'][$battleid]['resistspeed'];
	}else{
		$_SESSION['battle'][$battleid]['resistspeed'] = getspeed($resisthid);//防守方速度
		$resistspeed = $_SESSION['battle'][$battleid]['resistspeed'];
	}

	if(isset($_SESSION['battle'][$battleid]['resistcommand'])){
		$resistcommand = $_SESSION['battle'][$battleid]['resistcommand'];
	}else{
		$_SESSION['battle'][$battleid]['resistcommand'] = get_heroinfo($resisthid);
		$resistcommand = $_SESSION['battle'][$battleid]['resistcommand'];
	}

	if(isset($_SESSION['battle'][$battleid]['attackcommand'])){
		$attackcommand = $_SESSION['battle'][$battleid]['attackcommand'];
	}else{
		$_SESSION['battle'][$battleid]['attackcommand'] = get_heroinfo($attackhid);
		$attackcommand = $_SESSION['battle'][$battleid]['attackcommand'];
	}

	$attacksoldiers = $minfo['attacksoldiers'];//进攻方军队//注意战斗一回合后更新session

	$resistsoldiers = $minfo['resistsoldiers'];//防守方军队

	$attackCount = getsoldierCount($attacksoldiers);//进攻方兵力总数

	$resistCount = getsoldierCount($resistsoldiers);//防守方兵力总数

//取得攻击方统帅科技
	if(isset($_SESSION['battle'][$battleid]['attackTechCommand'])){
		$attackTechCommand = $_SESSION['battle'][$battleid]['attackTechCommand'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechCommand'] = sql_fetch_one_cell("select level from sys_city_technic where tid=6 and cid='$minfo[attackcid]'");
		$attackTechCommand = $_SESSION['battle'][$battleid]['attackTechCommand'];
	}

//取得防守方统帅科技
	if(isset($_SESSION['battle'][$battleid]['resistTechCommand'])){
		$resistTechCommand = $_SESSION['battle'][$battleid]['resistTechCommand'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechCommand'] = sql_fetch_one_cell("select level from sys_city_technic where tid=6 and cid='$minfo[resistcid]'");
		$resistTechCommand = $_SESSION['battle'][$battleid]['resistTechCommand'];
	}

//取得攻击方行军科技
	if(isset($_SESSION['battle'][$battleid]['attackTechXingjun'])){
		$attackTechXingjun = $_SESSION['battle'][$battleid]['attackTechXingjun'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechXingjun'] = sql_fetch_one_cell("select level from sys_city_technic where tid=12 and cid='$minfo[attackcid]'");
		$attackTechXingjun = $_SESSION['battle'][$battleid]['attackTechXingjun'];
	}

//取得防守方行军科技
	if(isset($_SESSION['battle'][$battleid]['resistTechXingjun'])){
		$resistTechXingjun = $_SESSION['battle'][$battleid]['resistTechXingjun'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechXingjun'] = sql_fetch_one_cell("select level from sys_city_technic where tid=12 and cid='$minfo[resistcid]'");
		$resistTechXingjun = $_SESSION['battle'][$battleid]['resistTechXingjun'];
	}

//取得攻击方驾驭科技
	if(isset($_SESSION['battle'][$battleid]['attackTechJiayu'])){
		$attackTechJiayu = $_SESSION['battle'][$battleid]['attackTechJiayu'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechJiayu'] = sql_fetch_one_cell("select level from sys_city_technic where tid=13 and cid='$minfo[attackcid]'");
		$attackTechJiayu = $_SESSION['battle'][$battleid]['attackTechJiayu'];
	}

//取得防守方驾驭科技
	if(isset($_SESSION['battle'][$battleid]['resistTechJiayu'])){
		$resistTechJiayu = $_SESSION['battle'][$battleid]['resistTechJiayu'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechJiayu'] = sql_fetch_one_cell("select level from sys_city_technic where tid=13 and cid='$minfo[resistcid]'");
		$resistTechJiayu = $_SESSION['battle'][$battleid]['resistTechJiayu'];
	}

//取得攻击方战斗科技
	if(isset($_SESSION['battle'][$battleid]['attackTechZhandou'])){
		$attackTechZhandou = $_SESSION['battle'][$battleid]['attackTechZhandou'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechZhandou'] = sql_fetch_one_cell("select level from sys_city_technic where tid=9 and cid='$minfo[attackcid]'");
		$attackTechZhandou = $_SESSION['battle'][$battleid]['attackTechZhandou'];
	}

//取得防守方战斗科技
	if(isset($_SESSION['battle'][$battleid]['resistTechZhandou'])){
		$resistTechZhandou = $_SESSION['battle'][$battleid]['resistTechZhandou'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechZhandou'] = sql_fetch_one_cell("select level from sys_city_technic where tid=9 and cid='$minfo[resistcid]'");
		$resistTechZhandou = $_SESSION['battle'][$battleid]['resistTechZhandou'];
	}

//取得攻击方防御科技
	if(isset($_SESSION['battle'][$battleid]['attackTechFangyu'])){
		$attackTechFangyu = $_SESSION['battle'][$battleid]['attackTechFangyu'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechFangyu'] = sql_fetch_one_cell("select level from sys_city_technic where tid=10 and cid='$minfo[attackcid]'");
		$attackTechFangyu = $_SESSION['battle'][$battleid]['attackTechFangyu'];
	}

//取得防守方防御科技
	if(isset($_SESSION['battle'][$battleid]['resistTechFangyu'])){
		$resistTechFangyu = $_SESSION['battle'][$battleid]['resistTechFangyu'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechFangyu'] = sql_fetch_one_cell("select level from sys_city_technic where tid=10 and cid='$minfo[resistcid]'");
		$resistTechFangyu = $_SESSION['battle'][$battleid]['resistTechFangyu'];
	}

//取得攻击方补给科技
	if(isset($_SESSION['battle'][$battleid]['attackTechBuji'])){
		$attackTechBuji = $_SESSION['battle'][$battleid]['attackTechBuji'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechBuji'] = sql_fetch_one_cell("select level from sys_city_technic where tid=16 and cid='$minfo[attackcid]'");
		$attackTechBuji = $_SESSION['battle'][$battleid]['attackTechBuji'];
	}

//取得防守方补给科技
	if(isset($_SESSION['battle'][$battleid]['resistTechBuji'])){
		$resistTechBuji = $_SESSION['battle'][$battleid]['resistTechBuji'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechBuji'] = sql_fetch_one_cell("select level from sys_city_technic where tid=16 and cid='$minfo[resistcid]'");
		$resistTechBuji = $_SESSION['battle'][$battleid]['resistTechBuji'];
	}

//取得攻击方抛射科技
	if(isset($_SESSION['battle'][$battleid]['attackTechPaoshe'])){
		$attackTechPaoshe = $_SESSION['battle'][$battleid]['attackTechPaoshe'];
	}else{
		$_SESSION['battle'][$battleid]['attackTechPaoshe'] = sql_fetch_one_cell("select level from sys_city_technic where tid=14 and cid='$minfo[attackcid]'");
		$attackTechPaoshe = $_SESSION['battle'][$battleid]['attackTechPaoshe'];
	}

//取得防守方抛射科技
	if(isset($_SESSION['battle'][$battleid]['resistTechPaoshe'])){
		$resistTechPaoshe = $_SESSION['battle'][$battleid]['resistTechPaoshe'];
	}else{
		$_SESSION['battle'][$battleid]['resistTechPaoshe'] = sql_fetch_one_cell("select level from sys_city_technic where tid=14 and cid='$minfo[resistcid]'");
		$resistTechPaoshe = $_SESSION['battle'][$battleid]['resistTechPaoshe'];
	}


//考虑是否使用道具虎符
	
//计算攻击方兵是否超统

	$attacksoldiersArray = getsoldierarray($attacksoldiers,$attackpos);


// 	满统:基础速度*（1+科技加成+马速）—1=行军距离
// 超统：基础速度*（1+科技加成）+基础速度*（马速/*满统兵力/总兵力）

	if(!isset($_SESSION['soldierInfo'])){
		$_SESSION['soldierInfo'] = sql_fetch_rows("select * from cfg_soldier order by `sid`");//二维数组 key+1做sid使用
	}
	$attackcommandSoldier = $attackcommand * 100 * (1+$attackTechCommand/10);//攻击方满统兵力

	if($attackCount > $attackcommand * 100 * (1+$attackTechCommand/10)){
		foreach ($attacksoldiersArray as $key => $value) {
			$attacksoldiersArray[$key]['type'] = 1;
			//步兵
			$sid = $value['sid'];
			if($sid<7){
				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$attackTechXingjun/10) + $_SESSION['soldierInfo'][$sid-1]['speed']*($attackspeed/100*$attackcommandSoldier/$attackCount);
			}
			//器械
			if($sid>6 && $sid<13){

				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$attackTechJiayu/10) + $_SESSION['soldierInfo'][$sid-1]['speed']*($attackspeed/100*$attackcommandSoldier/$attackCount);
			}
			if($sid >12){
				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed'];
			}
			if($sid!=6 && $sid!=10 && $sid!=12){
				$attacksoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range'];	
			}else{
				$attacksoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range']*(1+$attackTechPaoshe/20);	
			}
			$attacksoldiersArray[$key]['hp'] = $_SESSION['soldierInfo'][$sid-1]['hp'];
			
			$attacksoldiersArray[$key]['ap'] = $_SESSION['soldierInfo'][$sid-1]['ap'];

			$attacksoldiersArray[$key]['dp'] = $_SESSION['soldierInfo'][$sid-1]['dp'];

			$attacksoldiersArray[$key]['stype'] = $_SESSION['soldierInfo'][$sid-1]['type'];
		}
	}else{
		foreach ($attacksoldiersArray as $key => $value) {
			$attacksoldiersArray[$key]['type'] = 1;
			//步兵
			$sid = $value['sid'];
			if($sid<7){
				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$attackTechXingjun/10 + $attackspeed/100);
			}
			//器械
			if($sid>6 && $sid<13){
				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$attackTechJiayu/10 + $attackspeed/100);
			}
			if($sid >12){
				$attacksoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed'];
			}
			if($sid!=6 && $sid!=10 && $sid!=12){
				$attacksoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range'];	
			}else{
				$attacksoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range']*(1+$attackTechPaoshe/20);	
			}

			$attacksoldiersArray[$key]['hp'] = $_SESSION['soldierInfo'][$sid-1]['hp'];
			
			$attacksoldiersArray[$key]['ap'] = $_SESSION['soldierInfo'][$sid-1]['ap'];

			$attacksoldiersArray[$key]['dp'] = $_SESSION['soldierInfo'][$sid-1]['dp'];

			$attacksoldiersArray[$key]['stype'] = $_SESSION['soldierInfo'][$sid-1]['type'];						
		}
	}

//计算防守方兵是否超统
	$resistsoldiersArray = getsoldierarray($resistsoldiers,$resistpos);
	$resistcommandSoldier = $resistcommand * 100 * (1+$resistTechCommand/10);//防守方满统兵力
	if($resistCount > $resistcommand * 100 * (1+$resistTechCommand/10)){
		foreach ($resistsoldiersArray as $key => $value) {
			$resistsoldiersArray[$key]['type'] = 1;
			//步兵
			$sid = $value['sid'];
			if($sid<7){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$resistTechXingjun/10) + $_SESSION['soldierInfo'][$sid-1]['speed']*($resistspeed/100*$resistcommandSoldier/$resistCount);
			}
			//器械
			if($sid>6 && $sid<13){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$resistTechJiayu/10) + $_SESSION['soldierInfo'][$sid-1]['speed']*($resistspeed/100*$resistcommandSoldier/$resistCount);
			}
			if($sid >12){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed'];
			}
			if($sid!=6 && $sid!=10 && $sid!=12){
				$resistsoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range'];	
			}else{
				$resistsoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range']*(1+$resistTechPaoshe/20);	
			}
			$resistsoldiersArray[$key]['hp'] = $_SESSION['soldierInfo'][$sid-1]['hp'];
			
			$resistsoldiersArray[$key]['ap'] = $_SESSION['soldierInfo'][$sid-1]['ap'];

			$resistsoldiersArray[$key]['dp'] = $_SESSION['soldierInfo'][$sid-1]['dp'];

			$resistsoldiersArray[$key]['stype'] = $_SESSION['soldierInfo'][$sid-1]['type'];			
		}		
	}else{
		foreach ($resistsoldiersArray as $key => $value) {
			$resistsoldiersArray[$key]['type'] = 1;
			//步兵
			$sid = $value['sid'];
			if($sid<7){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$resistTechXingjun/10 + $resistspeed/100);
			}
			//器械
			if($sid>6 && $sid<13){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed']*(1+$resistTechJiayu/10 + $resistspeed/100);
			}
			if($sid >12){
				$resistsoldiersArray[$key]['speed'] = $_SESSION['soldierInfo'][$sid-1]['speed'];
			}	
			if($sid!=6 && $sid!=10 && $sid!=12){
				$resistsoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range'];	
			}else{
				$resistsoldiersArray[$key]['gongjifanwei'] = $_SESSION['soldierInfo'][$sid-1]['range']*(1+$resistTechPaoshe/20);	
			}
			$resistsoldiersArray[$key]['hp'] = $_SESSION['soldierInfo'][$sid-1]['hp'];
			
			$resistsoldiersArray[$key]['ap'] = $_SESSION['soldierInfo'][$sid-1]['ap'];

			$resistsoldiersArray[$key]['dp'] = $_SESSION['soldierInfo'][$sid-1]['dp'];	

			$resistsoldiersArray[$key]['stype'] = $_SESSION['soldierInfo'][$sid-1]['type'];					
		}
	}

	if(!isset($_SESSION['battle']['defence']))
	{
		$_SESSION['battle']['defence'] = sql_fetch_rows("select * from cfg_defence");//取得城防基本信息
	}
	if($_SESSION['battle'][$battleid]['mem']['wallhp'] !=0){
		$defence = $_SESSION['battle'][$battleid]['mem']['resistdefence'];
		//计入城墙
		$defence_key = count($resistsoldiersArray);
		$resistsoldiersArray[$defence_key]['sid'] = 18;
		$resistsoldiersArray[$defence_key]['type'] = 3;
		$resistsoldiersArray[$defence_key]['count'] = $_SESSION['battle'][$battleid]['mem']['wallhp'];//城墙生命;
		$resistsoldiersArray[$defence_key]['range'] = 100;
		$resistsoldiersArray[$defence_key]['speed'] = 0;
		$resistsoldiersArray[$defence_key]['gongjifanwei'] = 0;
		$resistsoldiersArray[$defence_key]['hp'] = $_SESSION['battle'][$battleid]['mem']['wallhp'];//城墙生命
		$resistsoldiersArray[$defence_key]['attack'] = 0;

		$defenceArray = explode(',', $defence);
		$defenceCount = array_shift($defenceArray);

		for ($i = 1; $i < $defenceCount+1; $i++)
		{
			$did = array_shift($defenceArray);//取城防类型
			$resistsoldiersArray[$defence_key+$i]['sid'] = array_shift($defenceArray);//sid
			$resistsoldiersArray[$defence_key+$i]['type'] = 2;
			$resistsoldiersArray[$defence_key+$i]['did'] = $did;
			$resistsoldiersArray[$defence_key+$i]['range'] = 100;//位置
			$cnt = array_shift($defenceArray);
			if ($cnt < 0) $cnt = 0;	
			$resistsoldiersArray[$defence_key+$i]['count'] = $cnt;
			$resistsoldiersArray[$defence_key+$i]['speed'] = 0;
			$resistsoldiersArray[$defence_key+$i]['gongjifanwei'] = $_SESSION['battle']['defence'][$did-1]['range'];
			$resistsoldiersArray[$defence_key+$i]['hp'] = $_SESSION['battle']['defence'][$did-1]['hp'];
			$resistsoldiersArray[$defence_key+$i]['ap'] = $_SESSION['battle']['defence'][$did-1]['ap'];
			$resistsoldiersArray[$defence_key+$i]['dp'] = $_SESSION['battle']['defence'][$did-1]['dp'];
			$resistsoldiersArray[$defence_key+$i]['attack'] = 0;
		}	


	}

//速度处理结束
	foreach ($attacksoldiersArray as $key => $value) {
		$endsoldier[$key] = $value;
		$endsoldier[$key]['attack'] = 1;//攻击方
	}
	
	$newcount = count($endsoldier);

	foreach ($resistsoldiersArray as $k => $v) {
		$endsoldier[$newcount+$k] = $v;
		$endsoldier[$newcount+$k]['attack'] = 0;//防守方
	}
//得到合并后的数组 按照速度冒泡 从快到慢
	$newcount = count($endsoldier);
	for ($i=0; $i < $newcount ; $i++) { 
		for ($j=$newcount-1; $j>$i ; $j--) { 
			if($endsoldier[$j]['speed'] > $endsoldier[$j-1]['speed'])
			{
				$x = $endsoldier[$j];
				$endsoldier[$j] = $endsoldier[$j-1];
				$endsoldier[$j-1] = $x;
			}
		}
	}

	$_SESSION['battle'][$battleid]['fightsoldier'] = $endsoldier;//压入session
}


	if(!isset($_SESSION['battle'][$battleid]['resistnpc']))
	{
		if(is_npc($resistuid)){
			$resistnpc = 1;//判断防守方是否是npc
		}else{
			$resistnpc = 0;
		}

		$_SESSION['battle'][$battleid]['resistnpc'] = $resistnpc;

	}else{
		$resistnpc = $_SESSION['battle'][$battleid]['resistnpc'];//判断防守方是否是npc
	}

	if(!isset($_SESSION['battle'][$battleid]['attacknpc']))
	{
		if(is_npc($attackuid)){
			$attacknpc = 1;//判断防守方是否是npc
		}else{
			$attacknpc = 0;
		}

		$_SESSION['battle'][$battleid]['attacknpc'] = $attacknpc;	
	}else{
		$attacknpc = $_SESSION['battle'][$battleid]['attacknpc'];//判断攻击方是否是npc
	}

//file_put_contents('c:/server', var_export($_SESSION,true));


	$fightsoldier = $_SESSION['battle'][$battleid]['fightsoldier'];
	
$report = '';
foreach ($fightsoldier as $key => $value) {
	if(empty($fightsoldier[$key]) || $value['type'] == 3){
		continue;
	}
	if($value['type'] == 1){
		$sid = $value['sid'];
	}else{
		$sid = $value['did'];
	}
	
	$stype = $value['stype'];
	$speed = $value['speed'];
	$pos = $value['range'];
	$range = getrangbetween($fightsoldier,$fieldrange,$key);//返回两军距离
	$gongjifanwei = $value['gongjifanwei'];//攻击范围
	$istarget = 0;//是否可以攻击
	$shanghai = 0;//攻击力
	$siwang = 0;//攻击对方死亡 伤兵数
	$target_sid = 0;//攻击目标
	$target_type = 0;//攻击类型
	$target_start = 0;//原兵数
	$target_end = 0;//留存数
	$able_fanji = 0;//是否可以反击
	$fanji_shanghai = 0;//反击伤害
	$fanji_start = 0;//反击原兵数
	$fanji_siwang = 0;//反击伤兵
	$fanji_end = 0;//反击留存
	if($value['type'] == 1){
		$is_city = 1;//是否是城防
	}else{
		$is_city = 2;//是否是城防
	}
	

	if($value['attack']==1){
//进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方
		$is_attack=1;
		//取得指挥--------------------------------
		if($attacknpc == 1){
			$tactics['action'] = 1;
		}else{
			$tactics = sql_fetch_one("select * from mem_battle_tactics where battleid='$battleid' and attack=1 and stype='$stype'");
		}

		if($tactics['action'] == 1){
			$newrange = $pos - $speed;
			//前进
			if($speed < $range){
				$fightsoldier[$key]['range'] = $newrange;
			}else{
				$speed = $range;
				$fightsoldier[$key]['range'] = $pos - $range;
			}	 
		}

		if($tactics['action'] == 3){
			$newrange = $pos + $speed;
			//后退
			if($newrange > $fieldrange){
				$speed = $fieldrange - $pos;
				$fightsoldier[$key]['range'] = $fieldrange;
			}else{
				$fightsoldier[$key]['range'] = $newrange;
			}	 
		}
//进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方进攻方
	}else{
		$is_attack=2;
		if($resistnpc == 1){
			$tactics['action'] = 1;
		}else{
			$tactics = sql_fetch_one("select * from mem_battle_tactics where battleid='$battleid' and attack=0 and stype='$stype'");
		}

		if($tactics['action'] == 1){
			$newrange = $pos + $speed;
			//前进
			if($newrange < $pos+$range){
				$fightsoldier[$key]['range'] = $newrange;
			}else{
				$speed = $range;
				$fightsoldier[$key]['range'] = $pos+$range;
			}	 
		}
		if($tactics['action'] == 3){
			$newrange = $pos - $speed;
			//后退
			if($newrange > 0){
				$fightsoldier[$key]['range'] = $newrange;
			}else{
				$speed = $pos;
				$fightsoldier[$key]['range'] = 0;
			}	 
		}

	//取得指挥--------------------------------		
	}

		$target = $tactics['target'];//取得攻击目标
		$abletarget = getabletarget($fightsoldier,$key);//取得可攻击目标数组
		if(count($abletarget)>0){
			$is_has = array_search($target,$abletarget);
			if($is_has != false){
				$target_key = $is_has;
			}else{
				$target_keys = array_keys($abletarget);
				$fuck = rand(1,count($target_keys));
				$target_key = $target_keys[$fuck-1];//就是被打的兵的键值				
			}
			$target_pos = $fightsoldier[$target_key]['range'];//取得目标位置
			$target_count = $fightsoldier[$target_key]['count'];
			$target_hp = $fightsoldier[$target_key]['hp'];
			$target_ap = $fightsoldier[$target_key]['ap'];
			$target_dp = $fightsoldier[$target_key]['dp'];
			$istarget = 1;
			$target_sid = $fightsoldier[$target_key]['sid'];//攻击目标
			$target_type = $fightsoldier[$target_key]['type'];//攻击类型
			$target_start = $target_count;
			if($value['type'] == 2 && $value['did'] != 3){
				//城防 触发型攻击
				$fanji_siwang = rand(0,$value['count']);
				$fightsoldier[$key]['count'] = $value['count'] - $fanji_siwang;//更新
				if($fightsoldier[$key]['count'] <= 0){
					$fightsoldier[$key] = null;
					unset($fightsoldier[$key]);
				}
				if($value['ap'] == 0){
					$siwang = $fanji_siwang;
				}else{
					$shanghai = $fanji_siwang*$value['ap']*$value['ap']/($value['ap']+$target_dp);
					$siwang = $shanghai/$target_hp;
					$siwang = floor($siwang);
				}
				$target_end = $target_count - $siwang;

				//file_put_contents('c:/cesi'.$value['did'], $fanji_siwang);

			}else{
				//正常攻击或箭塔攻击
					$shanghai = $value['count']*$value['ap']*$value['ap']/($value['ap']+$target_dp);
					if($target_type == 3){
						//打城墙
						$shanghai = floor($shanghai);
						$siwang = $shanghai;
						$target_end = $target_count - $siwang;
						if($target_end <= 0){
							$_SESSION['battle'][$battleid]['mem']['wallhp'] = 0;
							//将所有的城防全部删除
							foreach ($fightsoldier as $key1 => $value1) {
								if($value1['type'] == 2){
									$fightsoldier[$key1] = null;
									unset($fightsoldier[$key1]);
								}
							}
						}else{
							$_SESSION['battle'][$battleid]['mem']['wallhp'] = $target_end;
						}
					}else{
						$shanghai = floor($shanghai);
						$siwang = $shanghai/$target_hp;
						$siwang = floor($siwang);
						$target_end = $target_count - $siwang;
			
					}

			}



			if($target_end > 0){
				//城防类的只有箭塔能反击
			if($fightsoldier[$target_key]['type'] == 1 || ($fightsoldier[$target_key]['type']==2 && $fightsoldier[$target_key]['did'] == 3)){

				//file_put_contents('c:/yz', 'data');
						$fightsoldier[$target_key]['count'] = $target_end;
						$fanji_gongjifanwei = $fightsoldier[$target_key]['gongjifanwei'];//取得被打兵的攻击范围
						$fanji_attack = $fightsoldier[$target_key]['attack'];//是攻击方还是防守方
						if($fanji_attack == 1){
							//攻击方
							//file_put_contents('c:/fanjiable', $target_pos.'///'.$fanji_gongjifanwei.'///'.$value['range']);
							if($fightsoldier[$key]['did'] == 3 && $fightsoldier[$key]['type'] == 2){
								$fanji_stype = $fightsoldier[$target_key]['stype'];
								if($fanji_stype==6 || $fanji_stype==10 || $fanji_stype==12){
									//当为城防类型时只有箭塔为反击目标 并且当反击兵种为远程进攻兵种时才能反击
									if($target_pos - $fanji_gongjifanwei < $fightsoldier[$key]['range']){
										$able_fanji = 1;//是否可以反击
										$fanji_shanghai = $fightsoldier[$target_key]['count']*$fightsoldier[$target_key]['ap']*$fightsoldier[$target_key]['ap']/($fightsoldier[$target_key]['ap']+$value['dp']);
										$fanji_shanghai = floor($fanji_shanghai);
										$fanji_start = $value['count'];//反击原兵数
										$fanji_siwang = $fanji_shanghai/$value['hp'];//反击伤兵
										$fanji_siwang = floor($fanji_siwang);
										if($value['count'] - $fanji_siwang > 0){
											$fanji_end = $value['count'] - $fanji_siwang;//反击留存
											$fightsoldier[$key]['count'] = $fanji_end;
										}else{
											$fanji_siwang = $value['count'];//反击死亡
											$fanji_end = 0;//反击留存	
											$fightsoldier[$key]=null;
											unset($fightsoldier[$key]);
										}
															
									}									
								}
							}else{
								if($target_pos - $fanji_gongjifanwei < $fightsoldier[$key]['range']){
									$able_fanji = 1;//是否可以反击
									$fanji_shanghai = $fightsoldier[$target_key]['count']*$fightsoldier[$target_key]['ap']*$fightsoldier[$target_key]['ap']/($fightsoldier[$target_key]['ap']+$value['dp']);
									$fanji_shanghai = floor($fanji_shanghai);
									$fanji_start = $value['count'];//反击原兵数
									$fanji_siwang = $fanji_shanghai/$value['hp'];//反击伤兵
									$fanji_siwang = floor($fanji_siwang);
									if($value['count'] - $fanji_siwang > 0){
										$fanji_end = $value['count'] - $fanji_siwang;//反击留存
										$fightsoldier[$key]['count'] = $fanji_end;
									}else{
										$fanji_siwang = $value['count'];//反击死亡
										$fanji_end = 0;//反击留存	
										$fightsoldier[$key]=null;
										unset($fightsoldier[$key]);
									}
														
								}								
							}

						}else{
							//防御方
							if($target_pos + $fanji_gongjifanwei > $fightsoldier[$key]['range']){
								$able_fanji = 1;//是否可以反击
								$fanji_shanghai = $fightsoldier[$target_key]['count']*$fightsoldier[$target_key]['ap']*$fightsoldier[$target_key]['ap']/($fightsoldier[$target_key]['ap']+$value['dp']);
								$fanji_shanghai = floor($fanji_shanghai);
								$fanji_start = $value['count'];//反击原兵数
								$fanji_siwang = $fanji_shanghai/$value['hp'];//反击伤兵
								$fanji_siwang = floor($fanji_siwang);
								if($value['count'] - $fanji_siwang > 0){
									$fanji_end = $value['count'] - $fanji_siwang;//反击留存
									$fightsoldier[$key]['count'] = $fanji_end;
								}else{
									$fanji_siwang = $value['count'];//反击死亡
									$fanji_end = 0;//反击留存
									$fightsoldier[$key]=null;
									unset($fightsoldier[$key]);								
								}						
							}
						}
			}else{
				//被触发型城防干掉的
				$fightsoldier[$target_key]['count'] = $fanji_end;
			}

			}else{

				$siwang = $target_start;
				$target_end = 0;
				$fightsoldier[$target_key] = null;
				unset($fightsoldier[$target_key]);//删除
			}			
		}
//战报
//第二个参数 1 军队 2 城池 既城防
		//file_put_contents('c:/cesi222'.$value['did'], $fanji_siwang);
$report .= $is_attack.'.000000,'.$is_city.'.000000,'.$sid.'.000000,'.$tactics['action'].'.000000,'.$speed.'.000000,'.$istarget.'.000000,'.$shanghai.'.000000,'.$target_type.'.000000,'.$target_sid.'.000000,'.$target_start.'.000000,'.$siwang.'.000000,'.$target_end.'.000000,'.$able_fanji.'.000000,'.$fanji_shanghai.'.000000,'.$fanji_start.'.000000,'.$fanji_siwang.'.000000,'.$fanji_end.'.000000;';
}

$_SESSION['battle'][$battleid]['fightsoldier'] = array_values($fightsoldier);//重新索引数组
//更新mem_battle
$attackmem = 0;
$resistmem = 0;
$defencemem = 0;
$attackstr = '';
$resiststr = '';
$attackstrpos = '';
$resiststrpos = '';
$defencestrpos = '';
foreach ($fightsoldier as $key => $value) {
	if($value['attack'] == 1){
		if($value['count'] >0){
			$attackmem = $attackmem+1;
			$attackstr .= $value['sid'].','.$value['count'].',';
			$attackstrpos .= $value['sid'].','.$value['range'].',';
		}
	}
	if($value['attack'] == 0){
		if($value['count'] >0 && $value['type']==1){
			$resistmem = $resistmem+1;
			$resiststr .= $value['sid'].','.$value['count'].',';
			$resiststrpos .= $value['sid'].','.$value['range'].',';
		}
		if($value['count'] >0 && $value['type']==2){
			$defencemem = $defencemem+1;
			$defencestrpos .= $value['did'].','.$value['range'].','.$value['count'].',';
		}
	}

}
//防守方胜利
if($attackmem == 0){
	$_SESSION['battle'][$battleid]['battle_end'] = 0;
}
//攻击方成功
if($resistmem == 0  && $_SESSION['battle'][$battleid]['mem']['wallhp'] == 0){
	$_SESSION['battle'][$battleid]['battle_end'] = 1;
}
if($attackmem == 0 && $resistmem == 0){
	$_SESSION['battle'][$battleid]['battle_end'] = 3;
}
//平局
if($round>=30){
	$_SESSION['battle'][$battleid]['battle_end'] = 2;
}

$attackstrpos = $attackmem.','.$attackstrpos;//最新攻击方位置
$resiststrpos = $resistmem.','.$resiststrpos;//最新防守方位置
$attackstr = $attackmem.','.$attackstr;//最新攻击方数量
$resiststr = $resistmem.','.$resiststr;//最新防守方数量
$defencestrpos = $defencemem.','.$defencestrpos;//最新城防信息
$time = time()+30;
$newwallhp = $_SESSION['battle'][$battleid]['mem']['wallhp'];//最新城墙生命
//sql_query("update mem_battle set nexttime='$time',round=round+1 where `id`='$battleid'"); //更新战场
sql_query("update mem_battle set resistpos='$resiststrpos',wallhp='$newwallhp',attackpos='$attackstrpos',attacksoldiers='$attackstr',resistsoldiers='$resiststr',nexttime='$time',`round`=`round`+1,`resistdefence`='$defencestrpos'  where id='$battleid'");
sql_query("insert into sys_battle_report (`battleid`,`round`,`report`) values('$battleid','$round','$report')");

if(isset($_SESSION['battle'][$battleid]['battle_end']))
{

		$battle_end = $_SESSION['battle'][$battleid]['battle_end'];//战斗结果
		$time = time();
		$wid = cid2wid($_SESSION['battle'][$battleid]['sys']['cid']);//出战城

		$tid = sql_fetch_one_cell("select attacktroop from sys_battle where id='$battleid'");
		$attacktroop = $_SESSION['battle'][$battleid]['sys']['attacktroop'];
		$attacktroop_info = sql_fetch_one("select * from sys_troops where id='$attacktroop'");
		$attacktroop_task = $attacktroop_info['task'];//攻击类型
		$attacktroop_targetcid = $attacktroop_info['targetcid'];//攻击目的地
		$attacktroop_fromcid = $attacktroop_info['cid'];//来源城池
		if($attacktroop_task == 3){
			if($battle_end == 1){
				sql_query("update sys_troops set state=1,starttime='$time',endtime='$time'+`pathtime`,soldiers='$attackstr' where id='$tid'");
				//分战斗结果 胜利与平局处理结果
				//将原野地的兵全部清空
				sql_query("delete from sys_city_soldier where cid='$attacktroop_targetcid'");
			}else if($battle_end == 0){
				//战斗失败
				sql_query("delete from sys_troops where id='$tid'");//删除队列
				sql_query("update sys_city_hero set state=0 where hid='$attackhid'");	//使将领回城
				sql_query("delete from sys_city_soldier where cid='$attacktroop_targetcid'");//先删除再插入。可以一步完成
				foreach ($fightsoldier as $key => $value) {
					if($value['attack'] == 0 && $value['count'] != 0 ){
						sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$attacktroop_targetcid','$value[sid]','$value[count]')");
					}
				}							
			}else if($battle_end == 2){
				//战斗平局
				sql_query("update sys_troops set state=1,starttime='$time',endtime='$time'+`pathtime`,soldiers='$attackstr' where id='$tid'");
				//删除野地兵力
				sql_query("delete from sys_city_soldier where cid='$attacktroop_targetcid'");//先删除再插入。可以一步完成
				foreach ($fightsoldier as $key => $value) {
					if($value['attack'] == 0 && $value['count'] != 0 ){
						sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$attacktroop_targetcid','$value[sid]','$value[count]')");
					}
				}				
			}
		}

		if($attacktroop_task == 4){
			if ($battle_end  == 1) {
				$target_wid = cid2wid($attacktroop_targetcid);
				$w_info = sql_fetch_one("select * from mem_world where wid='$target_wid'");
				if($w_info['type'] == 0){
					//是城池 检查民心
					$city_info = sql_fetch_one("select * from mem_city_resource where cid='$attacktroop_targetcid'");
					$morale = $city_info['morale'] - 10;//打一次降10点民心
					if($morale <= 0){
						//进驻 占领成功
						//如果是名城 发布世界公告
						$uid = $_SESSION['battle'][$battleid]['sys']['attackuid'];
						sql_query("update sys_city set uid='$uid' where cid='$attacktroop_targetcid'");
						//将原城池的兵全部清空
						sql_query("delete from sys_city_soldier where cid='$attacktroop_targetcid'");
						//将新兵放入城中
						foreach ($fightsoldier as $key => $value) {
							if($value['attack'] == 1){
								sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$attacktroop_targetcid','$value[sid]','$value[count]')");
							}
						}
						//删除队列
						sql_query("delete from sys_troops where id='$tid'");
						//将带队将领放入新城
						sql_query("update sys_city_hero set cid='$attacktroop_targetcid',state=0 where hid='attackhid'");
						//向防守方发送占领通告
						//其他问题 删除城池野地兵
						// 遣回联盟兵
						//未被俘虏的将就近城池安置

					}else{
						$complaint = $city_info['complaint']+10;
						if($ccomplaint >100){
							$complaint = 100;//计算民怨
						}
						//将领的忠诚度问题
						sql_query("update mem_city_resource set morale='$morale' , complaint='$complaint' where cid='$attacktroop_targetcid'");
						sql_query("update sys_troops set state=1,starttime='$time',endtime='$time'+`pathtime` where id='$tid'");
					}
				}else{
					//野地 使军队驻守
					sql_query("update mem_world set ownercid='$attacktroop_fromcid' where wid='$target_wid'");//取得野地管辖
					//改部队状态为驻扎 并更新为战后士兵数量
					sql_query("update sys_troops set `state`=4,`starttime`='$time',`soldiers`='$attackstr' where id='$tid'");
					//删除原有野地兵力（规划为删除队列）
					//插入新兵力
					//将原野地的兵全部清空
					sql_query("delete from sys_city_soldier where cid='$attacktroop_targetcid'");
					//将新兵放入野地
					foreach ($fightsoldier as $key => $value) {
						if($value['attack'] == 1){
							sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$attacktroop_targetcid','$value[sid]','$value[count]')");
						}
					}
					//未按照队列式来规划兵力					
				}
				
			}else if($battle_end == 2){
				//失败 平局
				sql_query("update sys_troops set `state`=1,`starttime`='$time',`endtime`='$time'+`pathtime`,`soldiers`='$attackstr' where id='$tid'");
			}else if($battle_end == 0){
				sql_query("delete from sys_troops where id='$tid'");//删除队列
				//让将领回城
				sql_query("update sys_city_hero set state=0 where hid='$attackhid'");				
			}
		}
		
		
		if($battle_end == 1){
			sql_query("update sys_battle set state=1,result=0 where id='$battleid'");//战斗胜利
		}else if($battle_end == 0){
			sql_query("update sys_battle set state=1,result=1 where id='$battleid'");//战斗失败
		}else{
			sql_query("update sys_battle set state=1,result=2 where id='$battleid'");//战斗平局
		}
		//sql_query("delete from mem_battle where id='$battleid'");
		sql_query("delete from mem_battle_tactics where battleid='$battleid'");//删除指挥
		sql_query("update mem_world set `state`=0 where wid='$wid'");//设置为和平

		$_SESSION['battle'][$battleid] = null;
		unset($_SESSION['battle'][$battleid]);//删除session	
}
	
}

function getabletarget($fightsoldier,$xx)
{
	$attack = $fightsoldier[$xx]['attack'];

	$stype = $fightsoldier[$xx]['stype'];

	if($attack == 1){
		$abletarget = $fightsoldier[$xx]['range']-$fightsoldier[$xx]['gongjifanwei'];//可攻击范围
	}else{
		$abletarget = $fightsoldier[$xx]['range']+$fightsoldier[$xx]['gongjifanwei'];//可攻击范围
	}

	foreach ($fightsoldier as $key => $value) {
		if($attack == 1){
			if($stype == 6 || $stype == 10 || $stype == 12){
				if($value['attack'] == 0 && $value['range'] > $abletarget){
					if($value['type']==2 && $value['did']!= 3){
						continue;//不是箭塔直接跳过
					}
					$rt[$key] = $value['sid'];
				}				
			}else{
				if($value['attack'] == 0 && $value['range'] > $abletarget && $value['type'] != 2){
					$rt[$key] = $value['sid'];
				}				
			}

		}else{
			if($value['attack'] == 1 && $value['range'] < $abletarget){
				$rt[$key] = $value['sid'];
	
			}
		}
	}

	return $rt;//返回可攻击数组
}

//返回马速
function getspeed($hid)
{
	if($hid == 0 || empty($hid)){
		return 0;
	}else{

		return sql_fetch_one_cell("select speed_add_on from sys_city_hero where hid='$hid'");
	}
}

//返回兵力数量
function getsoldierCount($soldiers)
{
	$s_info = explode(',', $soldier);

	$new_s_info = $s_info['0'].',';

	$count = 0;

	for ($i=0; $i <$s_info['0'] ; $i++) { 
		
		$m= $i*2;

		$sid = $s_info[$m+1];

		$count = $count + $s_info[$m+2];

	}	

	return $count;//返回兵力
}

//返回两军距离
function getrangbetween($fightsoldier,$fightrange,$xx)
{

	$attack = $fightrange;
	$resist = 0;
	foreach ($fightsoldier as $key => $value) {
		if ($value['attack'] == 1 && $value['count'] > 0) {
			if($value['range']<$attack){
				$attack = $value['range'];
			}
		}
		if($value['attack'] == 0 && $value['count'] > 0){
			if($value['range'] >$resist){
				$resist = $value['range'];
			}
		}
	}

	//$range = $attack - $resist;
	if($fightsoldier[$xx]['attack'] == 1){
		$range = $fightsoldier[$xx]['range'] - $resist;
	}else{
		$range = $attack - $fightsoldier[$xx]['range'];
	}

	return $range;//返回两军距离
}

//返回兵力数组
function getsoldierarray($soldiers,$soldierspos)
{
	$s_info = explode(',', $soldiers);

	$pos = explode(',', $soldierspos);

	for ($i=0; $i <$s_info['0'] ; $i++) { 
		
		$m= $i*2;

		$sid = $s_info[$m+1];

		$count = $s_info[$m+2];

		$new[$i]['sid'] = $sid;

		$new[$i]['count'] = $count;

		$new[$i]['range'] = $pos[$m+2];
	}

	return $new;
}

//生成战场 传入部队id
function buildBattle($tid)
{
	$tinfo = sql_fetch_one("select * from sys_troops where id='$tid'");

	$uid = $tinfo['uid'];

	//设置战斗提醒
	sql_query("update sys_alarm set `enemy`='1' where `uid`='$uid'");//攻击方

	$fieldrange = getRang($tid);//取得战场距离

	$resistsoldiers = getenemyinfo($tid);//取得敌方兵力

	$attacksoldiers = $tinfo['soldiers'];//取得我方兵力

	$attackhid = $tinfo['hid'];//我方将领

	$attackcid = $tinfo['cid'];//进攻方城池

	$s_info = explode(',', $attacksoldiers);

	$attackpos = $s_info['0'].',';

	for ($i=0; $i <$s_info['0'] ; $i++) {

		$m= $i*2;

		$sid = $s_info[$m+1];

		$attackpos = $attackpos.$sid.','.$fieldrange.',';
	} 
	//进攻方军队位置

	unset($s_info);

	$s_info = explode(',', $resistsoldiers);

	$resistpos = $s_info['0'].',';

	for ($i=0; $i <$s_info['0'] ; $i++) {

		$m= $i*2;

		$sid = $s_info[$m+1];

		$resistpos = $resistpos.$sid.','.'0,';
	} 
	//防守方军队位置


	$resistcid = $tinfo['targetcid'];//取得攻击目标

	$wid = cid2wid($resistcid);

	sql_query("update mem_world set `state`=1 where wid='$wid'");//设置为战乱

	if(!sql_check("select * from sys_city where cid='$resistcid'"))
	{
		if(sql_check("select * from mem_world where wid='$wid'"))
		{
			$resistcid = sql_fetch_one_cell("select ownercid from mem_world where wid='$wid'");
		}else{
			$resistcid = $tinfo['targetcid'];//先默认设置为野地id 当打无归属野地的时候
		}
	}else{

		$resistcid = $tinfo['targetcid'];//取得攻击目标
	}
	//取得被攻击方城池
	$resisthid = '';//防守方将领 先设置为空

	$attackstartcid = $attackcid;

	$resiststartcid = $resistcid;

	$widtype = sql_fetch_one_cell("select type from mem_world where wid='$wid'");

	if($widtype == 0 && $tinfo['task'] == 4){

		$walllevel = sql_fetch_one_cell("select level from sys_building where cid='$resistcid' and bid=20");
		//取得城防科技
		$walltech = sql_fetch_one_cell("select level from sys_city_technic where cid='$resistcid' and tid=18");

		$wallhp = $walllevel*1000000*($walltech*0.1+1);
//取得城防
		$resistdefence_tmp  = sql_fetch_rows("select * from sys_city_defence c,cfg_defence d  where c.cid='$resistcid' and c.did=d.did");
//select count(1) from sys_building b,sys_city c where c.uid='$uid' and b.cid=c.cid and b.level>='$goal[count]' and b.bid='$goal[type]'
		//file_put_contents('c:/xxxxxx', $resistcid);

		$resistdefence = count($resistdefence_tmp).',';

		foreach ($resistdefence_tmp as $k => $v) 
		{
			$resistdefence .= $v['did'].','.$v['type'].','.$v['count'].',';
		}

		//file_put_contents('c:/yyyyyy', $resistdefence);

	}else{
		$wallhp = 0;

		$walllevel = 0;//野地战 不考虑此项

		$resistdefence = '';//野地战不考虑城防		
	}

	$state = 2;//未知参数

	$level = 0;//未知参数

	$round = 1;//第一回合

	$nexttime = time()+31;//下一回合时间

	//0 野战 1 攻城战 2 抢掠战

	if($tinfo['task'] == 3 )
	{
		$type = 2; //先修复掠夺战

	}else if($tinfo['task'] == 4){

		$type = 1; //占领

	}else{
		$type = 0;//侦查战
	}
	
//插入sys_battle
	$starttime = time();
	
	$cid = $tinfo['targetcid'];

	$attackuid = $tinfo['uid'];

	$attacktroop = $tinfo['id'];

//	$resistdefence = '';//城防

	if(sql_check("select * from sys_city where cid='$cid'"))
	{
		$resistuid = sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
		//城池
	}else{
		//野地
		$wid = cid2wid($cid);

		$ownercid = sql_fetch_one_cell("select ownercid from mem_world where wid='$wid'");

		if($ownercid == 0){
			//无人的野地
			$resistuid = 0;
		}else{
			//有人占领了

			$resistuid = sql_fetch_one_cell("select uid from sys_city where cid='$ownercid'");

		}
	}


	if($resistuid != 0){
		//设置战斗提醒
		sql_query("update sys_alarm set `enemy`='1' where `uid`='$resistuid'");//攻击方		
	}

	$resisttroops_arr = sql_fetch_rows("select id from sys_troops where cid='$resistcid' and state=6");

	if(!empty($resisttroops_arr)){
		$resisttroops = implode(',', $resisttroops_arr);
	}else{
		$resisttroops = '0';//防守军队
	}

	sql_query("insert into sys_battle (`type`,`starttime`,`cid`,`attackuid`,`resistuid`,`attacktroop`,`resisttroops`,`resistdefence`,`origindefence`,`state`,`result`) values ('$type','$starttime','$cid','$attackuid','$resistuid','$attacktroop','$resisttroops','$resistdefence','0','0','0')");
	
	$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");//取得战斗id

	$bid = $lastid;



//插入战斗表
	sql_query("insert into mem_battle (`id`,`type`,`nexttime`,`round`,`attackcid`,`attackhid`,`attacksoldiers`,`attackpos`,`resistcid`,`resisthid`,`resistsoldiers`,`resistpos`,`resistdefence`,`wallhp`,`walllevel`,`fieldrange`,`state`,`level`,`attackstartcid`,`resiststartcid`) values('$bid','$type','$nexttime','$round','$attackcid','$attackhid','$attacksoldiers','$attackpos','$resistcid','$resisthid','$resistsoldiers','$resistpos','$resistdefence','$wallhp','$walllevel','$fieldrange','$state','$level','$attackstartcid','$resiststartcid')");
	
	
	//插入战术 默认全部防守 需要细化 从玩家的预设战术中读取来设置到此处
	for ($i=1; $i < 13; $i++) { 
		if(is_npc($attackuid)){
			sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','1','$i','1','$i','1','$i')");

		}else{
			sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','1','$i','2','$i','2','$i')");

		}
	}

	if($widtype == 0 && $tinfo['task'] == 4){
		for ($i=1; $i < 18; $i++) { 		
			if(is_npc($resistuid)){
				if($i >12){$mjt=1;}else{$mjt=$i;}
				sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','0','$i','1','$mjt','1','$mjt')");
			}else{
				if($i >12){$mjt=1;}else{$mjt=$i;}				
				sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','0','$i','2','$mjt','2','$mjt')");
			}
		}		

	}else{
		for ($i=1; $i < 13; $i++) { 		
			if(is_npc($resistuid)){
				sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','0','$i','1','$mjt','1','$mjt')");

			}else{
				sql_query("insert into mem_battle_tactics (`battleid`,`attack`,`stype`,`action`,`target`,`action2`,`target2`) values ('$lastid','0','$i','2','$mjt','2','$mjt')");
			}
		}		
	}


	return $bid;
}
//取得战场距离 军队队列 是否是进攻方 1 进攻 0防守
function getRang($tid)
{
	$info = sql_fetch_one("select * from sys_troops where id='$tid'");
	$soldiers = $info['soldiers'];
	$s_info = explode(',', $soldiers);
	$range = 0;//使战场距离为0
	//进攻方 取得进攻方城池的抛射技术14
	$cid = $info['cid'];
	$tech_info = sql_fetch_one("select * from sys_city_technic where cid='$cid' and tid=14");
	$tech_level = $tech_info['level'];//取得科技抛射等级
	for ($i=0; $i <$s_info['0'] ; $i++) 
	{ 
		$m= $i*2;
		$sid = $s_info[$m+1];//取得sid
		$soldier_info = sql_fetch_one("select * from cfg_soldier where sid='$sid'");
		$tmp_range = $soldier_info['range'];
		if($sid == 6 || $sid == 10 || $sid == 12){
			$tmp_range = $tmp_range+$tmp_range*0.05*$tech_level;
		}
		if($tmp_range>$range){$range = $tmp_range;}
	}
	$range = $range+399;
	//进攻方距离计算完毕
	//防守方战场距离
	$target_range=0;
	$targetcid = $info['targetcid'];
	$targetwid = cid2wid($targetcid);
	$targettype = sql_fetch_one_cell("select type from mem_world where wid='$targetwid'");

	if($targettype == 0){
		//城池
		if(sql_check("select * from sys_city_technic where cid='$cid' and tid=14")){
			//有科技的成
			$tech_level = sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=14");
		}else{
			//没有科技
			$tech_level = 0;
		}
		//取得城池城墙等级bid20 城墙等级每级加成3%射程
		if($info['task'] == 4){
			//占领才出城墙
			$wall_level = sql_fetch_one_cell("select level from sys_building where cid='$targetcid' and bid=20");
		}else{
			$wall_level = 0;
		}
		$target_s = sql_fetch_rows("select * from sys_city_soldier where cid='$targetcid'");
		foreach ($target_s as $key => $value) {
			$sid = $value['sid'];//取得sid
			$soldier_info = sql_fetch_one("select * from cfg_soldier where sid='$sid'");
			$tmp_range = $soldier_info['range'];
			if($sid == 6 || $sid == 10 || $sid == 12){
				$tmp_range = $tmp_range+$tmp_range*0.05*$tech_level+$tmp_range*0.05*$wall_level;
			}
			if($tmp_range>$target_range){$target_range = $tmp_range;}			
		}
		//考虑城防器械的攻击范围
		if(sql_check("select * from sys_city_defence where cid='$targetcid' and did=1")){
			if($target_range<2000){
				$target_range = 2000;
			}
		}
		if(sql_check("select * from sys_city_defence where cid='$targetcid' and did=3")){
			$jianta_range = 1250 * (1+$tech_level*0.05+$wall_level*0.05);
			if($target_range<$jianta_range){
				$target_range = $jianta_range;
			}
		}
		if(sql_check("select * from sys_city_defence where cid='$targetcid' and did=2")){
			if($target_range<800){
				$target_range = 800;
			}
		}
		if(sql_check("select * from sys_city_defence where cid='$targetcid' and did=4")){
			if($target_range<500){
				$target_range = 500;
			}
		}
		if(sql_check("select * from sys_city_defence where cid='$targetcid' and did=5")){
			if($target_range<250){
				$target_range = 250;
			}
		}		
	}else{
		//野地
		$owner = sql_fetch_one_cell("select ownercid from mem_world where wid='$wid'");//查询野地的拥有者
		if($owner == 0){
			$tech_level=0;
		}else{
			$tech_level = sql_fetch_one_cell("select level from sys_city_technic where cid='$targetcid' and tid=14");
		}
		$target_s = sql_fetch_rows("select * from sys_city_soldier where cid='$targetcid'");
		foreach ($target_s as $key => $value) {
			$sid = $value['sid'];//取得sid
			$soldier_info = sql_fetch_one("select * from cfg_soldier where sid='$sid'");
			$tmp_range = $soldier_info['range'];
			if($sid == 6 || $sid == 10 || $sid == 12){
				$tmp_range = $tmp_range+$tmp_range*0.05*$tech_level;
			}
			if($tmp_range>$target_range){$target_range = $tmp_range;}			
		}				
	}

	$target_range = $target_range+399;
	if($target_range>$range){
		return $target_range;
	}else{
		return $range;
	}
}

function getenemyinfo($tid)
{
	$troop = sql_fetch_one("select targetcid,task from sys_troops where id='$tid'");

	$cid = $troop['targetcid'];

	$task = $troop['task'];

	$wid = cid2wid($cid);

	$world = sql_fetch_one("select ownercid,type from mem_world where wid='$wid'");

	$ownercid = $world['ownercid'];

	$type = $world['type'];//判断是否是野地 0为城池 1其他为野地

	$resistuid = sql_fetch_one_cell("select uid from sys_city where cid='$ownercid'");
//取得玩家防守策略
	if(is_npc($resistuid)){
		//如果是npc 全部出战
		$s_info = sql_fetch_rows("select * from sys_city_soldier where cid='$cid'");

		sql_query("delete from sys_city_soldier where cid='$cid'");//删除兵力 都出战了哇，所以删除
	}else{
		//如果是玩家就取出防守策略 既是否出战
		if($type != 0){
			//野地 没辙 只能全部战斗了。
			$s_info = sql_fetch_rows("select * from sys_city_soldier where cid='$cid'");
			sql_query("delete from sys_city_soldier where cid='$cid'");//删除兵力 都出战了哇，所以删除
			sql_query("update sys_troops set `state`=3,`task`=5 where `targetcid`='$cid' and `state`=4");//将停在这个野地上的军队全部设置为战斗状态
		}else{
			//城池 取出防守策略
			if($task == 3){
				//掠夺
				$s = sql_fetch_one_cell("select deplunder_join from sys_city_tactics where cid='$cid'");
			}

			if($task == 4){
				//占领
				$s = sql_fetch_one_cell("select deinvade_join from sys_city_tactics where cid='$cid'");
			}
			if($task == 2){
				//侦查
				$s = sql_fetch_one_cell("select depatrol_join from sys_city_tactics where cid='$cid'");
			}
			$s_info = sql_fetch_rows("select * from sys_city_soldier where cid='$cid' and sid in('$s')");
			foreach ($s_info as $key => $value) {
				$takeSoldiers[$value['sid']] = $takeSoldiers[$value['count']];
			}
			addCitySoldiers($cid,$takeSoldiers,false);//减兵
			

		}
		
	}
	

	$count = count($s_info);
	if($count > 0){
		$soldier =$count.',';
		foreach ($s_info as $key => $value) {
			$soldier = $soldier.$value['sid'].','.$value['count'].',';
		}
		if(!is_npc($resistuid) && $type==0){
			//生成临时战斗队列
			$tmp = sql_fetch_one_cell("select hid from sys_city_hero where cid='$cid' and state='7'");//拿来当主将用
			if(empty($tmp)){
				$hid = 0;
			}else{
				$hid = $tmp;
			}
			sql_query("insert into sys_troops (`uid`,`cid`,`hid`,`task`,`soldiers`) values('$resistuid','$cid','$hid',6,'$soldier')");

			$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");

		}
		return $soldier;
	}else{
		return null;
	}
}

function zhenchaInfo($troop)
{
	//先写不战斗的侦查
	$tid = $troop['id'];

	$uid = $troop['uid'];

	sql_query("update sys_troops set state=1,starttime=unix_timestamp(),endtime=unix_timestamp()+`pathtime` where id='$tid'");
	
	$title = 5;

	$origincid = $troop['cid'];

	$happencid = $troop['targetcid'];

	$content ='测试侦查邮件';

	sendReport($uid,0,$title,$origincid,$happencid,$content);
}







//处理返回队列
function HandleBackTroops()
{
	$time = time();
	$troops = sql_fetch_rows("select * from sys_troops where `state`=1 and `endtime`-'$time'<=1 and `task`<5");//task5为被动防守出城
	foreach ($troops as $key => $troop) {
		//将士兵返回到城池中
		$soldiers = $troop['soldiers'];
		$cid = $troop['cid'];
		//加士兵
		$soldierArray = explode(",",$soldiers);
		$numSoldiers = array_shift($soldierArray);
		$takeSoldiers = array();    //真正带出去的军队
		for ($i = 0; $i < $numSoldiers; $i++)
		{
			$sid = array_shift($soldierArray);
			$cnt = array_shift($soldierArray);
			if ($cnt < 0) $cnt = 0;
			$takeSoldiers[$sid] = $cnt;
		}	
		addCitySoldiers($cid,$takeSoldiers,true);//加兵


		//将将领设为空闲
		$hid = $troop['hid'];//取得将领id
		//更新将领状态
		if($hid != 0 && $hid != ''){
			sql_query("update sys_city_hero set state=0 where hid='$hid'");
		}
		//增加资源
		$resource = $troop['resource'];
		$resources = explode(',', $resource);
		addCityResources($troop['targetcid'],$resources['2'],$resources['3'],$resources['4'],$resources['1'],$resources['0']);		

		//删除该队列
		$tid = $troop['id'];
		sql_query("delete from sys_troops where `id`='$tid'");
		//发送报告
		sendemail($troop);		
	}
}

function HandleTech()
{
	$time = time();
	$technics = sql_fetch_rows("select * from mem_technic_upgrading where `state_endtime`-'$time'<=1");
	foreach ($technics as $key => $value) {
		$tid = $value['tid'];
		$cid = $value['cid'];
		$uid = sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
		$id = $value['id'];
		$level = $value['level'];
		//更新已经升级好的科技
		sql_query("update sys_technic set `state`=0 , `level`=`level`+1 where `tid`='$tid' and `uid`='$uid'");
		//删除升级队列
        sql_query("delete from mem_technic_upgrading where id='$id'");
        updateCityTechnic($uid,$tid,$level);
	}

}


//处理招兵
function HandleArmy()
{
	$time = time();
	$armys = sql_fetch_rows("select * from mem_city_draft where `state_endtime`-'$time'<=1");
	if(!empty($armys)){
		foreach ($armys as $key => $value) {
			update_army($value['cid'],$value['count'],$value['sid'],$value['id'],$value['xy']);
		}
	}
}

//传入城市id 士兵数量 士兵类型 招兵队列 建筑标示
 function update_army($cid,$count,$sid,$qid,$xy)
 {
	//查找是否已经存在士兵信息
	if(!sql_check("select * from sys_city_soldier where `cid`='$cid' and `sid`='$sid'"))
	{
			sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$sid','$count')");
	}else{
		sql_query("update sys_city_soldier set `count`=`count`+'$count' where `cid`='$cid' and `sid`='$sid'"); 
	}

	//删除招兵 队列
	sql_query("delete from sys_city_draftqueue where id='$qid'");
	//删除正在招兵的队列
	sql_query("delete from mem_city_draft where id='$qid'");
//如果还有队列在排队，则开始新的招兵
	if (sql_check("select * from sys_city_draftqueue where `cid`='$cid' and `state`=0 and `xy`='$xy'"))	//如果该兵营已经有兵在造了
	{

		if (!sql_check("select * from sys_city_draftqueue where `cid`='$cid' and `xy`='$xy' and `state`=1"))	//万一不知道怎么回事没有队在训练，拉个最先的来
		{
			$id = sql_fetch_one_cell("select id from sys_city_draftqueue where `cid`='$cid' and `xy`='$xy' order by queuetime limit 1");																 
			if (!empty($id))
			{                                                                       
				sql_query("update sys_city_draftqueue set state=1,state_starttime=unix_timestamp() where `id`='$id'"); 
				sql_query("insert into mem_city_draft (select id,cid,xy,sid,count,state_starttime+needtime from sys_city_draftqueue where `id`='$id')");
			} 
		}
	}


 }

//更新每一个城池的科技
function updateCityTechnic($uid,$tid,$level)
{
//查找用户所有的城池
	$level = $level+1;//科技当前等级
	$citys = sql_fetch_rows("select * from sys_city where uid='$uid'");
	foreach ($citys as $city) {
		$cid = $city['cid'];
		$newl = getCityTechnicLevel($cid,$tid,$level);
		while (!$newl) 
		{
			$level = $level - 1;
			if($level == 0)
			{
				$newl = 0;
				break;
			}
			$newl = getCityTechnicLevel($cid,$tid,$level);
		}
		//如果匹配出来的最新的科技等级不为0则对该城池的科技进行更新操作
		if($newl != 0)
		{
			//查找该城池是否已经拥有该科技
			if(sql_check("select * from sys_city_technic where cid='$cid' and tid='$tid'"))
			{
				//有 则更新
				sql_query("update sys_city_technic set `level`='$newl' where cid='$cid' and tid='$tid'");
			}else{
				//没有则插入
				sql_query("insert into sys_city_technic (cid,tid,level) values('$cid','$tid','$newl')");
			}
			
		}

	}
}
//从上往下匹配符合城市的科技等级
function getCityTechnicLevel($cid,$tid,$level)
{
		$conditions = sql_fetch_rows("select * from cfg_technic_condition where tid='$tid' and `level`='$level' and `pre_type`=0 order by `pre_type`");	//先建筑后科技
		
		foreach($conditions as $key => $condition)
		{                                     
                            
			$pre_building_id = $condition['pre_id'];
			$curr_building_level = sql_fetch_one_cell("select max(`level`) from sys_building where cid='$cid' and `bid`='$pre_building_id'");
			if (empty($curr_building_level) || $curr_building_level < $condition['pre_level'])
			{
				return false;
			
			}                                                                                     
                          
		}
		return $level;
}
//发送报告
function sendemail($troop)
{
	$origincid = $troop['cid'];

	$happencid = $troop['targetcid'];

	$uid = $troop['uid'];	

	$soldier = $troop['soldiers'];
	$s_info = explode(',', $soldier);
	$soldier_info = '';
	for ($i=0; $i <$s_info['0'] ; $i++) {
		$m= $i*2;
		$sid = $s_info[$m+1];//取sid
		$sname = sql_fetch_one_cell("select name from cfg_soldier where sid='$sid'");
		$soldier_info .= '<tr><td width="120" height="25" align="center" valign="middle" class="TextArmyCount">'.$sname.'</td><td width="129" height="25" align="center" valign="middle" class="TextArmyCount">'.$s_info[$m+2].'</td></tr>';
	}
	//返回的兵种
	//侦查返回
	if($troop['task'] == 2){
		$title = 6;
		$content = '向'.getCityNamePosition($happencid).'执行侦察任务的军队已返回'.getCityNamePosition($origincid).'。<br/><table width="567" border=0 cellpadding=1 cellspacing=1 bgcolor="#FFFFFF"><tr><td height="25" colspan="2" align="center" class="TitleBlueWhite">部队返回</td></tr><tr></tr><tr><td height="25" colspan="2" align="center" class="TextArmyCount"><table width="249" border="0" cellpadding="0" cellspacing="0"><tr class="TitleBattleYellow"><td width="120" height="25" align="center" valign="middle">军队</td><td width="129" height="25" align="center" valign="middle">数量</td></tr>'.$soldier_info.'</table></td></tr></table>';
	}

	if($troop['task'] == 1){
		$title = 4;//派遣
	}	
	if($troop['task'] == 3){
		$title = 8;//掠夺
	}
	if($troop['task'] == 4){
		$title = 10;//占领
	}
	if($troop['task'] == 0){
		$title = 2;//运输
	}				
	sendReport($uid,0,$title,$origincid,$happencid,$content);

}

?>