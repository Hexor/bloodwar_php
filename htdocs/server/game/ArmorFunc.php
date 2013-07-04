<?php

require_once("./interface.php");
require_once("./utils.php");
require_once("./HeroFunc.php");

function getArmorEmbedGoods($armors)
{
	return array();
	/*$gid_str = "";
   for($i=0; $i<count($armors);$i++)
    {
    	$armor = $armors[$i];
    	$pearls = $armor['embed_pearls'];
    	//$gids = explode(",", $pearls);
    	$gid_str = $gid_str."$pearls";
    	if($i<count($rows)-1)
    		$gid_str = $gid_str.",";
    }
    if($gid_str != "")
    	return sql_fetch_rows("select * from cfg_goods where gid in ($gid_str)");
    else
    	return array();*/
}

function loadUserArmor($uid,$param)
{
	$ret = array();
	$armor_column = sql_fetch_one_cell("select armor_column from sys_user where uid=$uid");
	$ret[]= $armor_column; //50;
    $armors = sql_fetch_rows("select * from sys_user_armor a left join cfg_armor c on c.id=a.armorid where a.uid='$uid' and a.`hid`=0");
    $ret[] = $armors;
    $ret[] = getArmorEmbedGoods($armors);
    
    return $ret;
}

function loadUserPartArmor($uid,$param)
{
	$part=array_shift($param);
	$ret=array();
	$armors = sql_fetch_rows("select * from sys_user_armor a , cfg_armor c where a.uid='$uid' and a.`hid`=0 and c.id=a.armorid and c.part=$part");
	 $ret[] = $armors;
	$ret[] = getArmorEmbedGoods($armors);
	
	return $ret;
}

function getHeroArmor($uid,$param)
{
	$hid=array_shift($param);
	return doGetHeroArmor($hid);
}

function doGetHeroArmor($hid)
{
	$ret=array();
	$ret[]=$hid;
	$armors = sql_fetch_rows("select * from sys_hero_armor h left join sys_user_armor u on u.sid=h.sid and u.hid=h.hid left join cfg_armor c on c.id=u.armorid where h.hid='$hid'");
	$ret[] = $armors;
	getArmorEmbedGoods($armors);
	return $ret;
}

function getHeroDetail($hid)
{
	$hero=sql_fetch_one("select * from sys_city_hero h left join mem_hero_blood m on m.hid=h.hid where h.`hid`='$hid'");
	$buffers=sql_fetch_rows("select * from mem_hero_buffer where hid='$hero[hid]' and endtime>unix_timestamp()");
	foreach($buffers as $buf)
	{
		$typeidx="buf".$buf['buftype'];
		$hero[$typeidx]=$buf['endtime'];
	}
	return $hero;
}

function equipArmor($uid,$param)
{
	$hid=array_shift($param);
	$sid=array_shift($param);
	$spart=array_shift($param);
 
	
	$armorInfo=sql_fetch_one("select * from sys_user_armor u left join cfg_armor c on c.id=u.armorid where u.sid='$sid' and u.uid='$uid'");
	
	if($armorInfo["part"]!=floor($spart/10)){
		throw new Exception($GLOBALS['equipArmor']['not_right_part']);
	}
	
	if(empty($armorInfo)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	else if ($armorInfo['hid']!=0) throw new Exception($GLOBALS['equipArmor']['arm_in_use']);
	$hp=ceil($armorInfo['hp']/10);
	if($hp<=0)
	{
		throw new Exception($GLOBALS['equipArmor']['no_hp_max']);
	}
	$heroInfo=sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if(empty($heroInfo)|| isHeroInCity($heroInfo['state'])==0)//$heroInfo['state']>1)
	{
		throw new Exception($GLOBALS['equipArmor']['hero_state_wrong']);
	}
	$heroLevel=$heroInfo['level'];
	if($armorInfo['hero_level']>$heroLevel)
	{
		throw new Exception(sprintf($GLOBALS['equipArmor']['level'],$armorInfo['hero_level']));
	}
	$armorid=$armorInfo['armorid'];

	$oldarmor=sql_fetch_one("select * from sys_hero_armor h left join cfg_armor c on c.id=h.armorid where h.hid='$hid' and h.spart='$spart'");
	if(!empty($oldarmor))	//把旧的装备换下来
	{
		$oldid=$oldarmor['sid'];
		sql_query("update sys_user_armor set hid=0 where sid='$oldid'");
	}
	sql_query("update sys_user_armor set hid='$hid' where sid='$sid'");
	sql_query("insert into sys_hero_armor (hid,spart,sid,armorid) values ($hid,$spart,$sid,$armorid) on duplicate key update sid=$sid,armorid=$armorid");
	if($heroInfo['state']==1)
	{
		updateCityResourceAdd($heroInfo['cid']);
	}
	regenerateHeroAttri($uid,$hid);
	$ret=array();
	$ret[]=getHeroDetail($hid);
	$ret[]=doGetHeroArmor($hid);
	return $ret;
}

function offloadArmor($uid,$param)
{
	$hid=array_shift($param);
	$spart=array_shift($param);
	
	$armorInfo=sql_fetch_one("select * from sys_hero_armor h left join sys_user_armor u on u.sid=h.sid left join cfg_armor c on c.id=u.armorid where h.hid='$hid' and h.spart='$spart'");
	if(empty($armorInfo)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$heroInfo=sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if(empty($heroInfo)||isHeroInCity($heroInfo['state'])==0)//$heroInfo['state']>1)
	{
		throw new Exception($GLOBALS['equipArmor']['hero_state_wrong']);
	}
	
	$sid=$armorInfo['sid'];
	sql_query("update sys_user_armor set hid=0 where sid='$sid'");
	sql_query("delete from sys_hero_armor where hid='$hid' and spart='$spart'");
	
	regenerateHeroAttri($uid,$hid);
	if($heroInfo['state']==1)
	{
		updateCityResourceAdd($heroInfo['cid']);
	}
	
	$ret=array();
	$ret[]=getHeroDetail($hid);;
	$ret[]=doGetHeroArmor($hid);
	return $ret;
}

function repairArmor($uid,$param)
{
	$cid=array_shift($param);
	$sid=array_shift($param);
	
	$armorInfo=sql_fetch_one("select * from sys_user_armor where sid='$sid' and uid='$uid'");
	if(empty($armorInfo)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$hp=ceil($armorInfo['hp']/10);
	$hpmax=$armorInfo['hp_max'];
	if($hp<=0)
	{
		throw new Exception($GLOBALS['repairArmor']['no_hp_max']);
	}
	$goldNeed=($hpmax-$hp)*100;
	if($goldNeed<=0) throw new Exception($GLOBALS['repairArmor']['no_need']);
	$cityGold=sql_fetch_one_cell("select gold from mem_city_resource where cid=$cid");
	if($goldNeed>$cityGold) throw new Exception($GLOBALS['repairArmor']['no_gold']);
	$reduce=max(1,ceil(($hpmax-$hp)/10));
	$hpmax=max(0,$hpmax-$reduce);
	sql_query("update sys_user_armor set  hp=$hpmax*10,hp_max=$hpmax where sid='$sid'");
	sql_query("update mem_city_resource set `gold`=GREATEST(0,`gold`-'$goldNeed') where `cid`='$cid'");
	if($armorInfo['hid']!=0)
	{
		regenerateHeroAttri($uid,$armorInfo['hid']);
	}
	$ret=array();
	$ret[]=$sid;
	$ret[]=$hpmax;
	$ret[]=$cid;
	$ret[]=intval(floor(sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'")));
	return $ret;
}

function renovateArmor($uid,$param)
{
	$sid=array_shift($param);
	$armorInfo=sql_fetch_one("select * from sys_user_armor u left join cfg_armor c on c.id=u.armorid where u.sid='$sid' and u.uid='$uid'");
	if(empty($armorInfo)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$hp=ceil($armorInfo['hp']/10);
	$hpmax=$armorInfo['hp_max'];
	$orihpmax=$armorInfo['ori_hp_max'];
	$moneyNeed=($orihpmax-$hpmax)+ceil(($hpmax-$hp)/10);
	if($moneyNeed<=0) throw new Exception($GLOBALS['renovateArmor']['no_need']);
	if(!checkMoney($uid,$moneyNeed))
	{
		throw new Exception($GLOBALS['renovateArmor']['no_money']);
	}
	sql_query("update sys_user_armor set hp=$orihpmax*10,hp_max=$orihpmax where sid='$sid'");
	if($armorInfo['hid']!=0)
	{
		regenerateHeroAttri($uid,$armorInfo['hid']);
	}
	addMoney($uid,-$moneyNeed,100);
	$ret=array();
	$ret[]=$sid;
	return $ret;
}

function sellArmor($uid,$param)
{
	$cid=array_shift($param);
	$sid=array_shift($param);
	$marketLevel=sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_MARKET);
	if($marketLevel<5)
	{
		throw new Exception($GLOBALS['sellArmor']['market_level_low']);
	}
	
	$nobility=sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	//推恩
	$nobility=getBufferNobility($uid,$nobility);
	
	if($nobility<1)
	{
		throw new Exception($GLOBALS['sellArmor']['nobility_low']);
	}
	$armorInfo=sql_fetch_one("select * from sys_user_armor u, cfg_armor c where u.sid='$sid' and u.uid='$uid' and c.id=u.armorid");
	if(empty($armorInfo)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$hp=ceil($armorInfo['hp']/10);
	$hpmax=$armorInfo['hp_max'];
	$orihpmax=$armorInfo['ori_hp_max'];
	$goldAdd=intval(max(1,floor($hp/$orihpmax))*$armorInfo['value'])*500;
	
	sql_query("update mem_city_resource set `gold`=`gold`+$goldAdd where cid='$cid'");
	sql_query("delete from sys_user_armor where sid='$sid'");
	sql_query("insert into log_armor (uid,armorid,count,time,type) values ($uid,$armorInfo[armorid],-1,unix_timestamp(),9)");
	$ret=array();
	$ret[]=$sid;
	$ret[]=$cid;
	$ret[]=intval(floor(sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'")));
	return $ret;
}

function repairAllArmor($uid,$param)
{
	$cid=array_shift($param);
	$type=array_shift($param);
	$ids=array_shift($param);
	if(empty($ids)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$armors=sql_fetch_rows("select * from sys_user_armor u left join cfg_armor c on c.id=u.armorid where u.uid='$uid' and u.sid in ($ids)");
	$goldNeed=0;
	foreach($armors as $armorInfo)
	{
		$hp=ceil($armorInfo['hp']/10);
		$hpmax=$armorInfo['hp_max'];
		if($hp<=0)
		{
			throw new Exception($GLOBALS['repairArmor']['no_hp_max']);
		}
		$hpmax=max(0,$hpmax-$reduce);
		$goldNeed=$goldNeed+($hpmax-$hp)*100;
	}
	if($goldNeed<=0) throw new Exception($GLOBALS['repairArmor']['no_need']);
	$cityGold=sql_fetch_one_cell("select gold from mem_city_resource where cid=$cid");
	if($goldNeed>$cityGold) throw new Exception($GLOBALS['repairArmor']['no_gold']);
	foreach($armors as $armorInfo)
	{
		$hp=ceil($armorInfo['hp']/10);
		$hpmax=$armorInfo['hp_max'];
		$reduce=max(1,(ceil($hpmax-$hp)/10));
		$hpmax=max(0,$hpmax-$reduce);
		sql_query("update sys_user_armor set  hp=$hpmax*10,hp_max=$hpmax where sid='$armorInfo[sid]'");
	}
	sql_query("update mem_city_resource set `gold`=GREATEST(0,`gold`-'$goldNeed') where `cid`='$cid'");
	if((!empty($armors))&&$armors[0]['hid']!=0)
	{
		regenerateHeroAttri($uid,$armors[0]['hid']);
	}
	$ret=array();
	$ret[]=$type;
	$ret[]=$cid;
	$ret[]=intval(floor(sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'")));
	return $ret;
}

function renovateAllArmor($uid,$param)
{
	$type=array_shift($param);
	$ids=array_shift($param);
	if(empty($ids)) throw new Exception($GLOBALS['equipArmor']['arm_not_exist']);
	$armors=sql_fetch_rows("select * from sys_user_armor u left join cfg_armor c on c.id=u.armorid where u.uid='$uid' and u.sid in ($ids)");
	$moneyNeed=0;
	foreach($armors as $armorInfo)
	{
		$hp=ceil($armorInfo['hp']/10);
		$hpmax=$armorInfo['hp_max'];
		$orihpmax=$armorInfo['ori_hp_max'];
		$moneyNeed=$moneyNeed+($orihpmax-$hpmax)+ceil(($hpmax-$hp)/10);
	}
	if($moneyNeed<=0) throw new Exception($GLOBALS['renovateArmor']['no_need']);
	if(!checkMoney($uid,$moneyNeed))
	{
		throw new Exception($GLOBALS['renovateArmor']['no_money']);
	}
	foreach($armors as $armorInfo)
	{
		$orihpmax=$armorInfo['ori_hp_max'];
		$sid=$armorInfo['sid'];
		sql_query("update sys_user_armor set hp=$orihpmax*10,hp_max=$orihpmax where sid='$sid'");
	}
	if((!empty($armors))&&$armors[0]['hid']!=0)
	{
		regenerateHeroAttri($uid,$armors[0]['hid']);
	}
	addMoney($uid,-$moneyNeed,100);
	$ret=array();
	$ret[]=$type;
	return $ret;
}


?>