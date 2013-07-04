<?php
require_once("./interface.php");
require_once("./global.php");

function MakeEndTime($endtime)
{
	//$localnow = time();
	//$remotenow = sql_fetch_one_cell("select unix_timestamp()");
	$str = "%Y".$GLOBALS['MakeEndTime']['year']."%m".$GLOBALS['MakeEndTime']['month']."%d".$GLOBALS['MakeEndTime']['day']." %H:%i:%s";
	return sql_fetch_one_cell("select from_unixtime($endtime,'$str')");
	//return date($str,$endtime-$remotenow+$localnow);
}
function MakeTimeLeft($timeleft)
{
	$hour = floor($timeleft / 3600);
	$minute = floor(($timeleft-$hour * 3600) / 60);
	$second = $timeleft % 60;
	if ($hour > 0)
	{
		$thetime = $hour.$GLOBALS['MakeTimeLeft']['hour'].$minute.$GLOBALS['MakeTimeLeft']['min'].$second.$GLOBALS['MakeTimeLeft']['sec'];
	}
	else if ($minute > 0)
	{
		$thetime = $minute.$GLOBALS['MakeTimeLeft']['min'].$second.$GLOBALS['MakeTimeLeft']['sec'];
	}
	else
	{
		$thetime = $second.$GLOBALS['MakeTimeLeft']['sec'];
	}
	return $thetime;
}

function MakeSelectSql($tableName,$whereArray,$limit="")
{
	$sql = "select * from $tableName where ";
	foreach ($whereArray as $name => $value)
	{
		$sql .= $name . "=" . $value . " and ";
	}
	$sql = substr($sql,0,strlen($sql) - 5);
	if (!empty($limit))
	{
		$sql .= $limit;
	}
	return $sql;
}
function encodeBuildingPosition($inner,$x,$y)
{
	return $inner * 100 + $x * 10 + $y;
}
function decodeBuildingPosition($xy,&$inner,&$x,&$y)
{
	$inner = (int)floor($xy / 100);
	$pos = $xy - $inner * 100;
	$x = (int)floor($pos / 10);
	$y = $pos - ($x * 10);
}

//群发系统信
/*
function sendAllSysMail($title,$content)
{
	$title = addslashes($title);
	$content = addslashes($content);
	$mid = sql_insert("insert into sys_mail_sys_content (`content`,`posttime`) values ('$content',unix_timestamp())");
	$sql="insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) (select `uid`,'$mid','$title','0',unix_timestamp() from `sys_user`)";
	sql_insert($sql);
	sql_query("insert into sys_alarm (`uid`,`mail`) (select `uid`,1 from `sys_user`) on duplicate key update `mail`=1");
}*/

//给某个玩家发系统信
function sendSysMail($touid,$title,$content)
{
	$title = addslashes($title);
	$content = addslashes($content);

	$mid = sql_insert("insert into sys_mail_sys_content (`content`,`posttime`) values ('$content',unix_timestamp())");
	$sql="insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) values ('$touid','$mid','$title','0',unix_timestamp())";
	sql_insert($sql);
	sql_query("insert into sys_alarm (`uid`,`mail`) values ('$touid',1) on duplicate key update `mail`=1");
}


function sendReport($touid,$type,$title,$origincid,$happencid,$content)
{
	if($origincid>0)
	{
		$origincity=sql_fetch_one_cell("select name from sys_city where cid='$origincid'");
		if (empty($origincity))
		{
			$origincity = sql_fetch_one_cell("select c.name from mem_world m left join cfg_world_type c on c.type=m.type where m.wid=".cid2wid($origincid));
		}
	}
	else $origincity="";

	if($origincid==$targetcid)
	{
		$happencity=$origincity;
	}
	else if($happencid>0)
	{
		$happencity=sql_fetch_one_cell("select name from sys_city where cid='$happencid'");
		if (empty($happencity))
		{
			$happencity = sql_fetch_one_cell("select c.name from mem_world m left join cfg_world_type c on c.type=m.type where m.wid=".cid2wid($happencid));
		}
	}
	else $happencity="";

	sendReportDetail($touid,$type,$title,$origincid,$origincity,$happencid,$happencity,$content);
}

function sendReportDetail($touid,$type,$title,$origincid,$origincity,$happencid,$happencity,$content)
{
	$content = addslashes($content);
	if($title<=11) $stype=0;
	else if($title>=12&&$title<=14) $stype=1;
	else if($title==19) $stype=2;
	else $stype=3;
	sql_query("insert into sys_report (`uid`,`origincid`,`origincity`,`happencid`,`happencity`,`title`,`type`,`time`,`read`,`battleid`,`content`) values ('$touid','$origincid','$origincity','$happencid','$happencity','$title','$stype',unix_timestamp(),'0','0','$content')");
	sql_query("insert into sys_alarm (uid,report) values ('$touid',1) on duplicate key update report=1");

}
function addCityResources($cid,$wood,$rock,$iron,$food,$gold)
{
	sql_query("update mem_city_resource set `wood`=`wood`+'$wood',`rock`=`rock`+'$rock',`iron`=`iron`+'$iron',`food`=`food`+'$food',`gold`=`gold`+'$gold' where `cid`='$cid'");
}
//检查城池内资源是否够征兵所用
function checkCityResource($cid,$wood,$rock,$iron,$food,$gold)
{
	$myres = sql_fetch_one("select * from mem_city_resource where `cid`='$cid'");
	if (empty($myres)) return false;
	if (($myres['wood'] < $wood)||
	($myres['rock'] < $rock)||
	($myres['iron'] < $iron)||
	($myres['food'] < $food)||
	($myres['gold'] < $gold))
	{
		return false;
	}
	return true;
}
function checkCityOwner($cid,$uid)
{
	if (!sql_check("select * from sys_city where `cid`='$cid' and `uid`='$uid'")) throw new Exception("not_user_city");
}
function checkCityExist($cid,$uid)
{
	if (!sql_check("select uid from sys_city where `cid`='$cid' and uid='$uid'")) throw new Exception($GLOBALS['checkCityExist']['no_city_info']);
}
function getCityPeopleFreeCount($cid)
{
	return sql_fetch_one_cell("select `people`-`people_working`-`people_building` from mem_city_resource where cid=$cid");
}
function getCityNamePosition($cid)
{
	$cityname = sql_fetch_one_cell("select name from sys_city where cid='$cid'");
	return $cityname ."（".($cid%1000).",".floor($cid/1000)."）";
}
function getPosition($cid)
{
	return "[".($cid%1000).",".floor($cid/1000)."]";
}
function getCityArea($cid)
{
	$level = sql_fetch_one_cell("select `level` from sys_building where cid=".$cid." and bid=".ID_BUILDING_WALL);
	if(empty($level)) return 0;

	$all = sql_fetch_one_cell("select area from cfg_wall where level='$level'");

	return $all;
}

function getCityAreaOccupied($cid)
{
	$curr = sql_fetch_one_cell("select sum(c.count * d.area_need) from sys_city_defence c,cfg_defence d where c.did=d.did and c.cid=$cid");
	$reinforcing = sql_fetch_one_cell("select sum(c.count * d.area_need) from sys_city_reinforcequeue c,cfg_defence d where c.did=d.did and c.cid=$cid");
	$wid=cid2wid($cid);
	$state=intval(sql_fetch_one_cell("select state from mem_world where wid='$wid'"));
	if($state==1)
	{
		$curr+=intval(sql_fetch_one_cell("select area from sys_city_area where cid='$cid'"));
	}
	return ($curr+$reinforcing);
}

function addCityPeople($cid,$count)
{
	sql_query("update mem_city_resource set `people`=`people`+'$count' where cid='$cid'");
}

//批量添加兵员
function addCitySoldiers($cid,$soldiers,$add)
{
	foreach($soldiers as $sid=>$count)
	{
		if ($add)
		{
			sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$sid','$count') on duplicate key update `count`=`count` + '$count'");
		}
		else
		{
			sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$sid','-$count') on duplicate key update `count`=`count` - '$count'");
		}
	}
	updateCityResourceAdd($cid);
}
//增加一个兵种
function addCitySoldier($cid,$sid,$count)
{
	sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$sid','$count') on duplicate key update `count`=`count` + '$count'");
	updateCityResourceAdd($cid);
}
function addCityDefence($cid,$did,$count)
{
	sql_query("insert into sys_city_defence (`cid`,`did`,`count`) values ('$cid','$did','$count') on duplicate key update `count`=`count` + '$count'");
}

function giveDalibao($uid)
{
	//发送新手礼包
	//    传音符20个
	//    神农锄2个
	//    鲁班斧2个
	//    开山锤2个
	//    玄铁炉2个
	//    墨家残卷10个  
	//    墨家图纸5个 
	//    青铜宝箱5个  
	//    青铜钥匙5个
	//    洗髓丹5个
	//    免战牌2个
	//    招贤榜10个
	//    另赠送200个元宝

	if (!sql_check("select * from hd_dalibao where uid='$uid'"))
	{
		addGoods($uid,1,20,6);
		addGoods($uid,2,2,6);
		addGoods($uid,3,2,6);
		addGoods($uid,4,2,6);
		addGoods($uid,5,2,6);
		addGoods($uid,8,10,6);
		addGoods($uid,9,5,6);
		addGoods($uid,16,5,6);
		addGoods($uid,19,5,6);
		addGoods($uid,22,5,6);
		addGoods($uid,12,2,6);
		addGoods($uid,23,10,6);
		addMoney($uid,200,2);
		sql_query("insert into hd_dalibao (uid,time) values ('$uid',unix_timestamp())");

	}
}
function sendPassportMail($uid)
{
	$user = sql_fetch_one("select * from sys_user where uid='$uid'");

	$mails = sql_fetch_rows("select * from sys_mail_passport where passport='$user[passport]' and passtype='$user[passtype]'");
	foreach($mails as $mail)
	{
		sendSysMail($uid,$mail['title'],$mail['content']);
		sql_query("delete from sys_mail_passport where id='$mail[id]'");
	}
}
function realLogin($uid,$sid)
{

	$ip = $GLOBALS['ip'];
	if (sql_check("select * from cfg_baned_ip where ip='$ip'")) throw new Exception($GLOBALS['realLogin']['ip_blocked']);

	sql_query("update sys_sessions set `sid`='$sid',`ip`='$ip' where uid='$uid'");
	file_put_contents("./sessions/".$uid,$sid);
	$_SESSION['currentLogin_uid'] = $uid; //在session里保存下用户名
	sql_query("update sys_online set onlinetime=onlinetime+GREATEST(0,lastupdate-onlineupdate),onlineupdate=unix_timestamp(),`lastupdate`=unix_timestamp() where uid='$uid'");

	$lastlogintime =sql_fetch_one_cell("select unix_timestamp()-time from log_login where uid='$uid' order by time desc limit 1");
	if($lastlogintime>86400*7){
		// 如果玩家七天没有登录了，则发送信件，并且还有城池	
		if(sql_check("select * from sys_city where uid='$uid'")){
			sql_insert("insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) values ('$uid','15','您已经七天没有登录游戏','0',unix_timestamp())");
			sql_query("insert into sys_alarm (`uid`,`mail`) values ('$uid',1) on duplicate key update `mail`=1");
		}
	}

	sql_query("insert into log_login (uid,ip,time) values ($uid,'$ip',unix_timestamp())");

	$user = sql_fetch_one("select * from sys_user where uid='$uid'");


	$rank = sql_fetch_one_cell("select rank from rank_user where uid='$uid'");
	if(empty($rank))
	{
		$rank=intval(sql_fetch_one_cell("select count(*) from rank_user"))+1;
	}
	sql_query("update sys_user set rank='$rank' where uid='$uid'");

	//    giveDalibao($uid);
	//    sendPassportMail($uid);

	if (($user['union_id'] > 0))   //如果玩家从属于一个联盟，刷新该联盟的声望和排名
	{
		updateUnionRank($user['union_id']);
	}


	return $sid;
}
function wid2cid($wid)
{
	$y = floor($wid / 10000) * 10 + floor((($wid % 100) / 10));
	$x = floor(($wid % 10000) / 100) * 10 + floor($wid % 10);
	return $y * 1000 + $x;

}
function cid2wid($cid)
{
	$y = floor($cid / 1000);
	$x = ($cid % 1000);
	return (floor($y / 10)) * 10000 + (floor($x / 10)) * 100 + ($y % 10) * 10 + ($x % 10);
}
function getCityDistance($cid1,$cid2)
{
	$x1 = $cid1 % 1000;
	$y1 = floor($cid1 / 1000);
	$x2 = $cid2 % 1000;
	$y2 = floor($cid2 / 1000);
	return sqrt(($x1-$x2)*($x1-$x2) + ($y1-$y2)*($y1-$y2));
}
function updateCityHeroChange($uid,$cid)
{
	//$hero_fee = sql_fetch_one_cell("select sum(level) * ".HERO_FEE_RATE." from sys_city_hero where cid='$cid' and uid='$uid' and npcid=0");
	//$npc_fee = sql_fetch_one_cell("select sum(level) * ".NPCHERO_FEE_RATE." from sys_city_hero where cid='$cid' and uid='$uid' and npcid > 0");
	//$hero_fee += $npc_fee;
	$hero_fee=sql_fetch_one_cell("select sum(level*20+(GREATEST(affairs_base+affairs_add-90,0)+GREATEST(bravery_base+bravery_add-90,0)+GREATEST(wisdom_base+wisdom_add-90,0))*50) from sys_city_hero where cid='$cid' and uid='$uid' and state<5");
	sql_query("update mem_city_resource set hero_fee='$hero_fee'  where cid='$cid'");
}
function completeTask($uid,$goalid)
{
	sql_query("replace into sys_user_goal (`uid`,`gid`) values ('$uid','$goalid')");
}
function updateUnionRank($unionid)
{
	sql_query("update sys_union n set n.prestige=(select sum(prestige) from sys_user u where u.union_id=n.id) where n.id='$unionid'");
	$union_prestige = sql_fetch_one_cell("select prestige from sys_union where id='$unionid'");
	$rank = sql_fetch_one_cell("select count(*) + 1 from sys_union where prestige > $union_prestige");
	sql_query("update sys_union set rank='$rank' where id='$unionid'");
}

function isHeroHasBuffer($hid,$buftype)
{
	return sql_check("select * from mem_hero_buffer where hid='$hid' and buftype='$buftype'");
}

function updateUserPrestige($uid)
{
	$prestige1 = sql_fetch_one_cell("select sum(r.people_building) from sys_user u,sys_city c,mem_city_resource r where c.cid=r.cid and c.uid=u.uid and u.uid=".$uid);
	$prestige2 = sql_fetch_one_cell("select sum(f.people_need*s.count)  from sys_user u,sys_city c,sys_city_soldier s,cfg_soldier f where s.cid=c.cid and s.sid=f.sid and c.uid=u.uid and u.uid=".$uid);
	$prestige3 = sql_fetch_one_cell("select sum(people) from sys_troops where uid=".$uid);
	$warprestige = sql_fetch_one_cell("select warprestige from sys_user where uid=".$uid);
	$prestige = $prestige1 + $prestige2 + $prestige3 + $warprestige;
	if ($prestige < 0) $prestige = 0;

	sql_query("update sys_user set prestige=$prestige where uid=".$uid);
}

function cityHasHeroPosition($uid,$cid)
{
	$officeLevel = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_OFFICE);
	if (empty($officeLevel)) return false;
	$heroCount = sql_fetch_one_cell("select count(*) from sys_city_hero where cid='$cid' and uid='$uid'");
	return $officeLevel>$heroCount;
}
//得到玩家拥有的所有城市
function getCities($uid)
{
	return sql_fetch_rows("select * from sys_city where `uid`='$uid'");
}

function getCityBuilding($uid,$param)
{
	$cid=array_shift($param);
	return getCityBuildingInfo($uid,$cid);
}

function getCityBuildingInfo($uid,$cid)
{
	//所有的建筑信息----20121204修改错误

	//sql_fetch_rows("update `sys_building` set `state` = '0',`level` = (`level` + '1') where `state` = '1' and `state_endtime` <= unix_timestamp()");
	upbuilding($cid);
	//upteach($cid);

	return sql_fetch_rows("select b.*,c.name as bname,c.description as buildingDescription,if(b.state_endtime>unix_timestamp(),b.state_endtime-unix_timestamp(),0) as state_timeleft,l.description as level_description,l.using_people from cfg_building c,sys_building b left join cfg_building_level l on l.bid=b.bid and l.`level`=b.`level` where b.`cid`='$cid' and c.`bid`=b.`bid`");
}

//更新升级拆除建筑---在七度的基础上修改的
function upbuilding($cid)
{
	sql_query("update `sys_building` set `state` = '0',`level` = (`level` + '1') where `state` = '1' and `cid`='$cid' and `state_endtime` <= unix_timestamp()");
	sql_query("delete from mem_building_upgrading where `cid`='$cid' and `state_endtime` <= unix_timestamp()");
	sql_query("update `sys_building` set `state` = '0',`level` = (`level` - '1') where `state` = '2' and `cid`='$cid' and `state_endtime` <= unix_timestamp()");
	sql_query("delete from mem_building_destroying where `cid`='$cid' and `state_endtime` <= unix_timestamp()");

	updateCityPeopleMax($cid);
	updateCityPeopleStable($cid);
	updateCityGoldMax($cid);
}

//资源信息
//军队信息
//城防信息
//将领信息
function doGetCityBaseInfo($uid,$cid)
{
	$ret = array();
	$ret[] = sql_fetch_one("select * from sys_user where uid='$uid'");
	$cityres = sql_fetch_one("select * from mem_city_resource where `cid`='$cid'");

	$ret[] = $cityres;

	//所有属于本城的将领列表
	$ret[] = sql_fetch_rows("select * from sys_city_hero h left join mem_hero_blood m on m.hid=h.hid where h.`cid`='$cid' and h.uid='$uid'");
	//本城拥有的军队
	$ret[] = sql_fetch_rows("select * from sys_city_soldier where `cid`='$cid' order by sid");
	//本城拥有的城防
	$ret[] = sql_fetch_rows("select * from sys_city_defence where `cid`='$cid' order by did");

	$ret[] = sql_fetch_one("select * from sys_alarm where uid='$uid'");

	return $ret;
}
//得到某个城市的全部信息
//基础信息:
//基本信息  
//建筑信息
function doGetCityAllInfo($uid,$cid)
{
	$ret = array();
	$cityinfo = sql_fetch_one("select * from sys_city where `cid`='$cid'");
	if (empty($cityinfo)) throw new Exception($GLOBALS['doGetCityAllInfo']['no_city_info']);

	//城市基本信息
	$ret[] = $cityinfo;

	$ret[] = doGetCityBaseInfo($uid,$cid);
	//所有的建筑信息
	$ret[] = getCityBuildingInfo($uid,$cid);
	//科技信息
	$ret[] = sql_fetch_rows("select tid,level from sys_city_technic where cid='$cid'");
	//州郡信息
	$ret[] = sql_fetch_rows("select province,jun from mem_world where wid=".cid2wid($cid));
	return $ret;
}

function updateCityPeopleStable($cid)
{
	//人口稳定值=人口上限*民心
	$people_max = sql_fetch_one_cell("select `people_max` from mem_city_resource where `cid`='$cid'");
	$city_morale = sql_fetch_one_cell("select `morale` from mem_city_resource where `cid`='$cid'");
	$people_stable = $people_max * $city_morale * 0.01;
	sql_query("update mem_city_resource set `people_stable`='$people_stable' where `cid`='$cid'");
}
function updateCityPeopleMax($cid)
{
	//民房 N级增长人口上限100*N
	$people_max = sql_fetch_one_cell("select sum(level*(level+1)*50) from sys_building where `cid`='$cid' and bid=".ID_BUILDING_HOUSE);
	sql_query("update mem_city_resource set `people_max`='$people_max' where `cid`='$cid'");
	updateCityPeopleStable($cid);
}
function updateCityGoldMax($cid)
{
	$gold_max = sql_fetch_one_cell("select level*(level+1)*500000 from sys_building where `cid`='$cid' and bid=".ID_BUILDING_GOVERMENT);
	sql_query("update mem_city_resource set `gold_max`='$gold_max' where `cid`='$cid'");
}
function checkUserPassport($uid,$password)
{
	$user = sql_fetch_one("select * from sys_user where uid='$uid'");
	$passport = $user['passport'];
	$passtype = $user['passtype'];
	$passsucc = true;//false;

	//@include ("./passport/$passtype.php");
	return $passsucc;
}

//更新所有资源产量
//需要从资源生产建筑里取人数，在资源建筑变化时要更新(ok)
//需要从科技里算加成，在升级科技时要更新(ok)
//需要取当前人口数，在人口不足且在变化时要更新(ok)
//需要取当前士兵数，要士兵变化时要更新(ok)
//需要取当前城守官的统率值，要在城守换人或城守官升统率的时候更新(todo)
//需要取当前城所占有的野地的数量，在占领或者被占领时更新(todo)
//需要取当前资源加成宝物当前状态，在使用或失效时更新(todo)
function updateCityResourceAdd($cid)
{
	$ownercid = sql_fetch_one_cell("select ownercid from mem_world where wid=".cid2wid($cid));
	if (empty($ownercid)) $ownercid = $cid;
	sql_query("update sys_city_res_add set resource_changing=1 where cid=".$ownercid);
}
function checkGoods($uid,$gid)
{
	return checkGoodsCount($uid,$gid,1);
}

function reduceGoods($uid,$gid,$count,$type=0)
{
	if ($count > 0)
	{
		sql_query("update sys_goods set `count`=GREATEST(0,`count`-$count) where uid='$uid' and gid='$gid'");
		sql_query("insert into log_goods (`uid`,`gid`,`count`,`time`,`type`) values ('$uid','$gid','-$count',unix_timestamp(),'$type')");
	}
	unlockUser($uid);
}

function addGoods($uid,$gid,$cnt,$type)
{
	sql_query("insert into sys_goods (uid,gid,count) values ('$uid','$gid','$cnt') on duplicate key update `count`=`count`+'$cnt'");
	sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid','$gid','$cnt',unix_timestamp(),$type)");
}

function addThings($uid,$tid,$cnt,$type)
{
	sql_query("insert into sys_things (uid,tid,count) values ('$uid','$tid','$cnt') on duplicate key update `count`=`count`+'$cnt'");
	sql_query("insert into log_things (uid,tid,count,time,type) values ('$uid','$tid','$cnt',unix_timestamp(),$type)");
}

function addArmor($uid,$armor,$cnt,$type)
{
	for($i=0;$i<$cnt;$i++){
		sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ($uid,$armor[id],$armor[ori_hp_max]*10,$armor[ori_hp_max],0)");
	}	
	sql_query("insert into log_armor (uid,armorid,count,time,type) values ($uid,$armor[id],$cnt,unix_timestamp(),$type)");
}

function checkMoney($uid,$money)
{
	$usermoney=sql_fetch_one_cell("select money from sys_user where uid='$uid'");
	if(empty($usermoney)||($usermoney<$money)) return false;
	else return true;
}
function addMoney($uid,$money,$type)
{
	sql_query("update sys_user set money=money+'$money' where uid='$uid'");
	sql_query("insert into log_money (uid,count,time,type) values ('$uid','$money',unix_timestamp(),'$type')");
}

function addGift($uid,$gift,$type)
{
	sql_query("update sys_user set gift=gift+'$gift' where uid='$uid'");
	sql_query("insert into log_gift (uid,count,time,type) values ('$uid','$gift',unix_timestamp(),'$type')");
}

function checkGoodsCount($uid,$gid,$need)
{
	if (!lockUser($uid)) throw new Exception($GLOBALS['checkGoodsCount']['server_busy']);
	$cnt = sql_fetch_one_cell("select `count` from sys_goods where uid='$uid' and gid='$gid'");
	if (empty($cnt)) $cnt = 0;

	if ($cnt < $need)
	{
		unlockUser($uid);
		return false;
	}
	else
	{
		return true;
	}

}
function checkGoodsArray($uid,$gidArray)
{
	if (!lockUser($uid)) throw new Exception($GLOBALS['checkGoodsArray']['server_busy']);
	foreach($gidArray as $gid => $need)
	{
		$cnt = sql_fetch_one_cell("select `count` from sys_goods where uid='$uid' and gid='$gid'");
		if ($cnt < $need)
		{
			unlockUser($uid);
			return false;
		}
	}
	return true;
}
function addHeroExp($hid,$exp)
{
	$expadd = floor($exp * HERO_EXP_RATE);
	sql_query("update sys_city_hero set exp=exp+$expadd where hid=".$hid);
}
function notifyUnionChange($uid,$unionid,$state)
{
	$username = sql_fetch_one_cell("select `name` from sys_user where uid='$uid'");
	$username=addslashes($username);
	sql_query("insert into mem_union_buf (uid,nick,union_id,state,updatetime) values ('$uid','$username','$unionid',$state,unix_timestamp())");
}
function lockUser($uid)
{
	$lockfile = './userlock/'.$uid.'.lock';
	if (file_exists($lockfile)&&( $GLOBALS['now'] - filemtime($lockfile) < 60))
	{
		return false;
	}
	touch($lockfile);
	return true;
}
function unlockUser($uid)
{
	$lockfile = './userlock/'.$uid.'.lock';
	@unlink($lockfile);
}

function throwHeroToField($hero)
{

	sql_query("delete from mem_hero_blood where hid='$hid'");
	sql_query("delete from sys_hero_armor where hid='$hid'");
	sql_query("update sys_user_armor set hid=0 where uid='$hero[uid]' and hid='$hid'");
	//把人往野地里面丢，如果没有人的话，就放在上面，如果有人的话，如果这个人也是NPC，则另外找地方，如果这个人不是NPC的话，就替代他的位置。
	$findtimes = 10;    //找十次，如果找不到的话就丢掉了
	$hid=$hero['hid'];
	while($findtimes > 0)
	{
		$findtimes--;
		$wid = sql_fetch_one_cell("select wid from mem_world where ownercid=0 and type > 1 and state=0 order by rand() limit 1");
		$newcid = wid2cid($wid);
		$oldhero = sql_fetch_one("select * from sys_city_hero where uid=0 and cid='$newcid'");
		if (empty($oldhero))    //该地点无人
		{
			sql_query("update sys_city_hero set cid='$newcid',state=4,uid=0,loyalty=70 where hid=$hid");
			break;
		}
		else    //有人
		{
			if ($oldhero['npcid'] > 0)    //也是一个NPC
			{
				//重新找过
				continue;
			}
			else    //不是NPC，算他倒霉，要被砍掉
			{
				sql_query("update sys_city_hero set cid=$newcid,state=4,uid=0,loyalty=70 where hid=$hid");

				sql_query("delete from sys_city_hero where hid=$oldhero[hid]");
				sql_query("delete from mem_hero_blood where hid='$oldhero[hid]'");

				sql_query("insert into sys_recruit_hero (`name`,`npcid`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_add`,`bravery_add`,`wisdom_add`,`affairs_base`,`bravery_base`,`wisdom_base`,`loyalty`,`gold_need`,`gen_time`) values ('$oldhero[name]','$oldhero[npcid]','$oldhero[sex]','$oldhero[face]','0','$oldhero[level]','$oldhero[exp]','$oldhero[affairs_add]','$oldhero[bravery_add]','$oldhero[wisdom_add]','$oldhero[affairs_base]','$oldhero[bravery_base]','$oldhero[wisdom_base]','60','0',unix_timestamp())");
				//扔池里不用算工资
				//$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");
				//sql_query("update sys_recruit_hero set gold_need=(`level`*20+(GREATEST(affairs_base+affairs_add-90,0)+GREATEST(bravery_base+bravery_add-90,0)+GREATEST(wisdom_base+wisdom_add-90,0))*50)*50 where id='$lastid'");
				$troop = sql_fetch_one("select * from sys_troops where uid=0 and cid='$cid' and hid=$oldhero[hid]");
				if (!empty($troop))
				{
					sql_query("update sys_troops set hid=$hid where id=$troop[id]");
				}
				break;
			}
		}
	}
	if ($findtimes == 0)    // 十次都没有找到，砍了
	{
		sql_query("update sys_troops set hid=0 where hid='$hid'");
		sql_query("delete from sys_city_hero where hid='$hid'");
		sql_query("insert into sys_recruit_hero (`name`,`npcid`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_add`,`bravery_add`,`wisdom_add`,`affairs_base`,`bravery_base`,`wisdom_base`,`loyalty`,`gold_need`,`gen_time`) values ('$hero[name]','$hero[npcid]','$hero[sex]','$hero[face]','0','$hero[level]','$hero[exp]','$hero[affairs_add]','$hero[bravery_add]','$hero[wisdom_add]','$hero[affairs_base]','$hero[bravery_base]','$hero[wisdom_base]','60','0',unix_timestamp())");

		//扔池里不用算工资
		//$lastid = sql_fetch_one_cell("select LAST_INSERT_ID()");
		//sql_query("update sys_recruit_hero set gold_need=(`level`*20+(GREATEST(affairs_base+affairs_add-90,0)+GREATEST(bravery_base+bravery_add-90,0)+GREATEST(wisdom_base+wisdom_add-90,0))*50)*50 where id='$lastid'");
	}

}

//迁城
function doChangeCityPosition($uid,$cid,$targetcid)
{
	set_time_limit(3600);
	
	//检查有没有城外驻军
	if (sql_check("select uid from sys_troops where uid='$uid' and cid='$cid'")) throw new Exception($GLOBALS['changeCityPosition']['has_army_outside']);

	//检查有没有在战场里
	if (sql_check("select uid from sys_troops where uid='$uid' and startcid='$cid'")) throw new Exception($GLOBALS['changeCityPosition']['has_army_outside']);

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
		if(sql_check("select uid from sys_troops where targetcid in ($fieldcids) and state=4 and uid<>'$uid' and uid > 0")) throw new Exception($GLOBALS['changeCityPosition']['has_ally_force']);
		if(sql_check("select uid from sys_troops where targetcid in ($fieldcids) and state=4 and uid ='$uid' and cid<>'$cid'")) throw new Exception($GLOBALS['changeCityPosition']['has_other_city_force']);
	}
	$wid = cid2wid($cid);
	$worldState = sql_fetch_one_cell("select state from mem_world where wid='$wid'");
	if ($worldState == 1) throw new Exception($GLOBALS['changeCityPosition']['city_in_battle']);

	$citytype = sql_fetch_one_cell("select type from sys_city where cid='$cid'");
	if ($citytype > 0) throw new Exception($GLOBALS['changeCityPosition']['cant_move_great_city']);

	if (sql_check("select cid from sys_city where cid='$targetcid'"))
	{
		throw new Exception($GLOBALS['changeCityPosition']['invalid_target_city']);
	}

	$targetwid = cid2wid($targetcid);
	$targetprovince = sql_fetch_one_cell("select province from mem_world where wid='$targetwid'");

	if(!sql_check("select type from mem_world where wid='$targetwid' and type=1 and state=0 and ownercid=0"))
	{
		throw new Exception($GLOBALS['changeCityPosition']['invalid_target_city']);
	}

	sql_query("insert into log_move_city (time,uid,fromcid,tocid) values (unix_timestamp(),'$uid','$cid','$targetcid')");
	/*
	 sql_query("call change_city_position('$cid','$targetcid','$wid','$targetwid')");
	 sql_query("update sys_city set province='$targetprovince' where cid='$targetcid'");
	 */

	$heros=sql_fetch_rows("select * from sys_city_hero where cid='$targetcid'");
	foreach($heros as $hero)
	{
		throwHeroToField($hero);
	}
	

	sql_query("update sys_city set cid='$targetcid' where cid='$cid'");
	sql_query("update sys_city set province='$targetprovince' where cid='$targetcid'");

	sql_query("update mem_world set ownercid='0' where ownercid='$cid' and type>0");
	sql_query("update mem_world set type=1,ownercid=0 where wid='$wid'");
	sql_query("update mem_world set type=0,ownercid='$targetcid' where wid='$targetwid'");

	sql_query("update sys_user set lastcid='$targetcid' where uid='$uid'");

	sql_query("delete from sys_building where cid='$targetcid'");
	sql_query("update sys_building set cid='$targetcid' where cid='$cid'");
	sql_query("delete from mem_building_destroying where cid='$targetcid'");
	sql_query("update mem_building_destroying set cid='$targetcid' where cid='$cid'");
	sql_query("delete from mem_building_upgrading where cid='$targetcid'");
	sql_query("update mem_building_upgrading set cid='$targetcid' where cid='$cid'");

	sql_query("delete from mem_technic_upgrading where cid='$targetcid'");
	sql_query("update mem_technic_upgrading set cid='$targetcid' where cid='$cid'");
	sql_query("delete from sys_city_technic where cid='$targetcid'");
	sql_query("update sys_city_technic set cid='$targetcid' where cid='$cid'");
	sql_query("update sys_technic set cid='$targetcid' where cid='$cid'");

	sql_query("delete from mem_city_buffer where cid='$targetcid'");
	sql_query("update mem_city_buffer set cid='$targetcid' where cid='$cid'");

	sql_query("delete from sys_city_soldier where cid='$targetcid'");
	sql_query("update sys_city_soldier set cid='$targetcid' where cid='$cid'");
	sql_query("delete from sys_city_draftqueue where cid='$targetcid'");
	sql_query("update sys_city_draftqueue set cid='$targetcid' where cid='$cid'");
	sql_query("delete from mem_city_draft where cid='$targetcid'");
	sql_query("update mem_city_draft set cid='$targetcid' where cid='$cid'");

	sql_query("delete from mem_city_wounded where cid='$targetcid'");
	sql_query("update mem_city_wounded set cid='$targetcid' where cid='$cid'");
	sql_query("delete from mem_city_lamster where cid='$targetcid'");
	sql_query("update mem_city_lamster set cid='$targetcid' where cid='$cid'");

	sql_query("update sys_city_trade set cid='$targetcid' where cid='$cid'");
	sql_query("update sys_city_trade set buycid='$targetcid' where buycid='$cid'");

	sql_query("delete from sys_city_defence where cid='$targetcid'");
	sql_query("update sys_city_defence set cid='$targetcid' where cid='$cid'");
	sql_query("delete from sys_city_reinforcequeue where cid='$targetcid'");
	sql_query("update sys_city_reinforcequeue set cid='$targetcid' where cid='$cid'");
	sql_query("delete from mem_city_reinforce where cid='$targetcid'");
	sql_query("update mem_city_reinforce set cid='$targetcid' where cid='$cid'");

	sql_query("update sys_city_hero set cid='$targetcid' where cid='$cid' and uid='$uid'");

	sql_query("delete from sys_city_tactics where cid='$targetcid'");
	sql_query("update sys_city_tactics set cid='$targetcid' where cid='$cid'");

	sql_query("update sys_troops set cid='$targetcid' where cid='$cid'");

	sql_query("delete from sys_city_res_add where cid='$targetcid'");
	sql_query("update sys_city_res_add set cid='$targetcid',resource_changing=1,field_food_add=0,field_wood_add=0,field_rock_add=0,field_iron_add=0 where cid='$cid'");
	sql_query("delete from mem_city_resource where cid='$targetcid'");
	sql_query("update mem_city_resource set cid='$targetcid' where cid='$cid'");

	sql_query("delete from mem_city_schedule where cid='$targetcid'");
	sql_query("update mem_city_schedule set cid='$targetcid' where cid='$cid'");

	sql_query("update sys_battle set cid='$targetcid' where cid='$cid'");

	sql_query("delete from sys_city_rumor where cid='$targetcid'");
	sql_query("update sys_city_rumor set cid='$targetcid' where cid='$cid'");

	sql_query("delete from sys_recruit_hero where cid='$targetcid'");
	sql_query("update sys_recruit_hero set cid='$targetcid' where cid='$cid'");
	
	sql_query("update sys_hero_expr set cid='$targetcid' where cid='$cid'");

}

function resetCityGoodsAdd($uid,$cid)
{
	$buffers = sql_fetch_rows("select * from mem_user_buffer where uid='$uid' and buftype <= 4 and endtime > unix_timestamp()");
	$food_add = 0;
	$wood_add = 0;
	$rock_add = 0;
	$iron_add = 0;
	foreach($buffers as $buffer)
	{
		$buftype = $buffer['buftype'];
		if ($buftype == 1)
		{
			$food_add = 25;
		}
		else if ($buftype == 2)
		{
			$wood_add = 25;
		}
		else if ($buftype == 3)
		{
			$rock_add = 25;
		}
		else if ($buftype == 4)
		{
			$iron_add = 25;
		}
	}
	sql_query("update sys_city_res_add set goods_food_add=$food_add,goods_wood_add=$wood_add,goods_rock_add=$rock_add,goods_iron_add=$iron_add,resource_changing=1 where cid=".$cid);
}

//取得用户推恩令以后的爵位
function getBufferNobility($uid,$realnobility){

	//检查是不是有推恩令
	$bufparam = sql_fetch_one_cell("select bufparam from mem_user_buffer where uid='$uid' and (buftype=16 or buftype=18) order by bufparam desc limit 1");
	if(!empty($bufparam)){
		//如果有
		//推恩后的爵位
		$nobility = $realnobility+$bufparam;
		//推恩爵位不能大于关内侯    	
		if($bufparam==5){
			if($nobility>19){
				$nobility=19;
			}
		}
		if($bufparam==2){
			if($nobility>18){
				$nobility=18;
			}
		}
		//如果推恩后的爵位大于实际爵位
		if($nobility>$realnobility)
		return $nobility;
	}
	return $realnobility;
}


function sendOpenBoxInform($goodNames,$goodsvalue,$uid,$gid){
	if($gid==50)
	return ;
	$name=sql_fetch_one_cell("select name from sys_user where uid='$uid'");

	$allname="";
	$boxName=sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");

	foreach($goodNames as $goodName){
		$allname.=$goodName." ";
	}
	$msg = sprintf($GLOBALS['open_box']['msg'],$name,$boxName,$allname,$goodsvalue);
	sql_query("insert into sys_inform (`type`,`inuse`,`starttime`,`endtime`,`interval`,`scrollcount`,`color`,`msg`) values (0,1,unix_timestamp(),unix_timestamp()+600,50000,1,49151,'$msg')");

	if(defined("USER_FOR_51") && USER_FOR_51){
		require_once("51utils.php");
		add51GoodsEvent($allname,$goodsvalue);
	}	
	if (defined("PASSTYPE")){
		require_once 'game/agents/AgentServiceFactory.php';
	    AgentServiceFactory::getInstance($uid)->addGoodsEvent($allname,$goodsvalue);
	}   
		
}

function isSentGood($gid){
	if($gid==10||$gid==12||$gid==17||$gid==18||$gid==20||$gid==21||$gid==25||$gid==26||$gid==40||$gid==52||$gid==56||$gid==71||$gid==72||$gid==82)
	return true;
	if($gid==97||$gid==100||$gid==115||$gid==117||$gid==120)
	return true;
	return false;
}

function getFieldName($ftype){
	$fieldname=$GLOBALS['fileName']['0'];
	if($ftype==1)
	$fieldname =$GLOBALS['fileName']['1'];
	if($ftype==2)
	$fieldname =$GLOBALS['fileName']['2'];
	if($ftype==3)
	$fieldname =$GLOBALS['fileName']['3'];
	if($ftype==4)
	$fieldname =$GLOBALS['fileName']['4'];
	if($ftype==5)
	$fieldname =$GLOBALS['fileName']['5'];
	if($ftype==6)
	$fieldname =$GLOBALS['fileName']['6'];
	if($ftype==7)
	$fieldname =$GLOBALS['fileName']['7'];
	return $fieldname;
}

function battleid2cid($battleid,$citypos){
	return ($battleid+600)*1000+$citypos;
}

//随机创建部队
function createSoldier($npcValue,$soldiers,$level){
	$times=pow(2,$level);
	$npcValue=$npcValue*$times;
	$soldiersarray = explode(",", $soldiers);
	//$count=count($soldiers);

	$soldiervalue=array(0,23,31 ,70,90,135,140,298,285,875,1000,1375,2900,31,90,135,140,285,26,89,127,128,263);
	$totalRnd = 0;
	$valueMap = array();
	$npcSoldiers ="";
	$typecount=0;
	foreach ($soldiersarray as $sid){
		$rnd = rand() % (10 - $totalRnd);
		$rnd = ($totalRnd + $rnd)>10?(10-$totalRnd):$rnd;
		if($rnd!=0){
			$valueMap[$sid] = $rnd;

			$totalRnd += $rnd;
			$typecount++;
		}
		if ($totalRnd >= 10){
			break;
		}
	}

	foreach ($valueMap as $k=>$v){
		$npcSoldiers.=$k.",";
		$npcSoldiers.= (int)ceil($npcValue *$v *0.1 /$soldiervalue[$k]).",";
	}
	$npcSoldiers=$typecount.",".$npcSoldiers;
	return $npcSoldiers;
}




?>