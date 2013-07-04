<?php                      
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");

function doGetCityHero($uid,$cid)
{	
	return getCityInfoHero($uid,$cid);
}

function doGetOfficeValidPosition($uid,$cid)
{
    $office_level = sql_fetch_one_cell("select level from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_OFFICE);
    $hero_count = sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and cid='$cid'");
    return $office_level - $hero_count;
}
function getOfficeInfo($uid,$cid)
{
	$office = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_OFFICE." order by level desc limit 1");
	if (empty($office))
	{   
		throw new Exception($GLOBALS['getOfficeInfo']['no_office_built']); 
	}
	return doGetBuildingInfo($uid,$cid,$office['xy'],ID_BUILDING_OFFICE,$office['level']);
}
function updateCityChiefResAdd($cid,$hid)
{
	updateCityResourceAdd($cid);
}
function setCityChief($uid,$cid,$param)
{                              
    $hid = array_shift($param);
    checkCityOwner($cid,$uid);
    
    if ($hid > 0)
    {
        if (!sql_check("select * from sys_city_hero where cid='$cid' and hid='$hid' and state=0"))
        {
            throw new Exception($GLOBALS['setCityChief']['set_chief_fail']);
        }
    }
    
    $oldChief = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
    if ($oldChief > 0)
    {
        sql_query("update sys_city_hero set state=0 where hid='$oldChief'");
    }
    if ($hid > 0)
    {
        sql_query("update sys_city_hero set state=1 where hid='$hid'");
        sql_query("update mem_city_resource m,sys_city_hero h set m.`chief_loyalty`=h.`loyalty` where m.cid='$cid' and h.hid='$hid'");
        updateCityChiefResAdd($cid,$hid);  
        completeTask($uid,85);  
        
    }
    else
    {
        sql_query("update mem_city_resource set `chief_loyalty`=0 where cid='$cid'");
        sql_query("update sys_city_res_add set `resource_changing`=1 where cid='$cid'");
    }
    sql_query("update sys_city set chiefhid='$hid' where cid='$cid'");     
                                  
    return getOfficeInfo($uid,$cid);    
}
?>