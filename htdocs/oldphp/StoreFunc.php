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
		
		sql_query("update sys_city_res_add set food_store='$foodrate',wood_store='$woodrate',rock_store='$rockrate',iron_store='$ironrate',resource_changing=1 where cid='$cid'");
		throw new Exception($GLOBALS['modifyStoreRate']['succ_change_rate']);
	}
	
?>