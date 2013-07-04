<?php
require_once("./interface.php");
require_once("./utils.php");

function doGetStoreInfo($uid,$cid)
{
	$ret=array();
	$foodbase=sql_fetch_one_cell("select sum(level*(level+1)*50) from sys_building where cid='$cid' and bid='1'");
	$woodbase=sql_fetch_one_cell("select sum(level*(level+1)*50) from sys_building where cid='$cid' and bid='2'");
	$rockbase=sql_fetch_one_cell("select sum(level*(level+1)*50) from sys_building where cid='$cid' and bid='3'");
	$ironbase=sql_fetch_one_cell("select sum(level*(level+1)*50) from sys_building where cid='$cid' and bid='4'");

	$foodbase=$foodbase*100*GAME_SPEED_RATE;
	$woodbase=$woodbase*100*GAME_SPEED_RATE;
	$rockbase=$rockbase*100*GAME_SPEED_RATE;
	$ironbase=$ironbase*100*GAME_SPEED_RATE;
	$storageTechLevel =sql_fetch_one_cell("select level from sys_city_technic where cid='$cid' and tid=15");
	if(empty($storageTechLevel))
	{
		$storageTechLevel=1;
	}
	else if ($storageTechLevel > 0)
	{
		$storageTechLevel=1.0 + 0.1 * $storageTechLevel;
	}
	$ret[]=$foodbase;
	$ret[]=$woodbase;
	$ret[]=$rockbase;
	$ret[]=$ironbase;
	$ret[]=$storageTechLevel;
	$ret[]=sql_fetch_one_cell("select count(*) from sys_building  where cid='$cid' and bid=17");
	$ret[]=GAME_SPEED_RATE*sql_fetch_one_cell("select sum(level*(level+1)*5000) from sys_building where cid='$cid' and bid=17");
	$ret[]=sql_fetch_one("select `food_store`,`wood_store`,`rock_store`,`iron_store` from `sys_city_res_add` where cid='$cid'");
	

	return $ret;
}

function modifyStoreRate($uid,$cid,$param)
{
	$foodrate=array_shift($param);
	$woodrate=array_shift($param);
	$rockrate=array_shift($param);
	$ironrate=array_shift($param);
	if($foodrate<0||$woodrate<0||$rockrate<0||$ironrate<0)
	{
		throw new Exception($GLOBALS['modifyStoreRate']['negative_store_rate']);
	}
	else if($foodrate+$woodrate+$rockrate+$ironrate>100)
	{
		throw new Exception($GLOBALS['modifyStoreRate']['resource_total_100']);
	}
    //仓库资源上限设定
    $cksx=GAME_SPEED_RATE*sql_fetch_one_cell("select sum(level*(level+1)*5000) from sys_building where cid='$cid' and bid=17");
	$fd=$cksx*$foodrate/100;
	$wd=$cksx*$woodrate/100;
	$rk=$cksx*$rockrate/100;
	$irn=$cksx*$ironrate/100;
    sql_query("update mem_city_resource set food_max='$fd',wood_max='$wd',rock_max='$rk',iron_max='$irn' where cid='$cid'");
    //更新资源比例
	sql_query("update sys_city_res_add set food_store='$foodrate',wood_store='$woodrate',rock_store='$rockrate',iron_store='$ironrate',resource_changing=1 where cid='$cid'");
	throw new Exception($GLOBALS['modifyStoreRate']['succ_change_rate']);

	
	
}

function payToPack($uid,$cid,$param){
	$index = intval(array_shift($param));
	$count=  intval(array_shift($param));
	$isLarge=intval(array_shift($param));
	$resType="gold";
	
	if($count<=0)
		throw new Exception($GLOBALS['payToPack']['count_error']);
	if($index>=5||index<0) throw new Exception($GLOBALS['payToPack']['res_type_error']);

	$cost=10;
	if($index!=0) $cost=2;
	$cost=$isLarge? $cost*10:$cost;
	if($isLarge&&$index==0) $cost=80;
	if((!$isLarge)&&$index==0) $cost=8;
	$cost=$cost*$count;

	$nameArray=array("gold","food","wood","rock","iron");

	//检查资源够不够
	$res = sql_fetch_one_cell("select ".$nameArray[$index]." from mem_city_resource where cid='$cid'");
	//资源不足时候
	$resCount=$count*($isLarge?1000000:100000);
	if ($res < $resCount) throw new Exception($GLOBALS['payToPack']['not_enough_res']);

	$usermoney = sql_fetch_one_cell("select money from sys_user where uid='$uid'");

	//金钱不足
	if ($usermoney <$cost) throw new Exception($GLOBALS['payToPack']['not_enough_moeny']);

	//金钱资源都满足了。。。一手交货。。。
	$baseUid=85;
	if($index==0){
		if($isLarge)
		$baseUid+=1;
	}else{
		$baseUid=86;
		$baseUid+=$index+($isLarge?4:0);
	}


	//一手交钱
	addMoney($uid,(0-$cost),52);
	//一手交货
	addGoods($uid,$baseUid,$count,2);
	if($index==0)
	addCityResources($cid,0,0,0,0,(0-$resCount));
	else if($index==1)
	addCityResources($cid,0,0,0,(0-$resCount),0);
	else if($index==2)
	addCityResources($cid,(0-$resCount),0,0,0,0);
	else if($index==3)
	addCityResources($cid,0,(0-$resCount),0,0,0);
	else if($index==4)
	addCityResources($cid,0,0,(0-$resCount),0,0);
	$ret=array();
	$ret[]=$resCount;
	$ret[]=$index;
	return $ret;
}


function testPayToPack($uid,$cid,$index,$count,$isLarge){
	$param[]=array($index,$count,$isLarge);
	payToPack($uid,$cid,$param);
}

//testPayToPack(1,1,1,1,1);


?>