<?php                      
require_once("./interface.php");
require_once("./utils.php");

define ("MERCHANT_INIT_FOOD",100);
define ("MERCHANT_INIT_WOOD",100);
define ("MERCHANT_INIT_ROCK",100);
define ("MERCHANT_INIT_IRON",100);
define ("MERCHANT_INIT_GOLD",10000);

define ("MARKET_LIST_CPP",10);
                    
function doGetCityTrade($uid,$cid)
{	
	return	sql_fetch_rows("select * from sys_city_trade where `cid`='$cid' or `buycid`='$cid' ");
}     
       
function getMarketInfo($uid,$cid)
{
	$market = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_MARKET." order by level desc limit 1");
	if (empty($market))
	{   
		throw new Exception($GLOBALS['getMarketInfo']['no_market_built']); 
	}
	return doGetBuildingInfo($uid,$cid,$market['xy'],ID_BUILDING_MARKET,$market['level']);
}
function cancelSell($uid,$cid,$param)
{
    $tradeid = array_shift($param);
    $info = sql_fetch_one("select * from sys_city_trade where id='$tradeid' and cid='$cid' and state=0");
    if (empty($info))
    {
        throw new Exception($GLOBALS['cancelSell']['cant_cancel']);
    }
    if ($info['restype'] == 0)  //food
    {
        sql_query("update mem_city_resource set food=food+'$info[count]' where cid='$cid'");
    }                 
    else if ($info['restype'] == 1)
    {
        sql_query("update mem_city_resource set wood=wood+'$info[count]' where cid='$cid'");                     
    }
    else if ($info['restype'] == 2)
    {                 
        sql_query("update mem_city_resource set rock=rock+'$info[count]' where cid='$cid'");                     
    }
    else if ($info['restype'] == 3)
    {                 
        sql_query("update mem_city_resource set iron=iron+'$info[count]' where cid='$cid'");                     
    }
    sql_query("delete from sys_city_trade where id='$tradeid'");
    return getMarketInfo($uid,$cid);
}

function accelerateSell($uid,$cid,$param)
{
	$tradeid=array_shift($param);
	if(!checkGoods($uid,11))
	{
		throw new Exception($GLOBALS['accelerateSell']['no_MuNiuLiuMa']);
	}
	$trade=sql_fetch_one("select cid,buycid from sys_city_trade where id='$tradeid' and state=1");
	if(empty($trade)||($trade['cid']!=$cid&&$trade['buycid']!=$cid))
	{
        throw new Exception($GLOBALS['accelerateSell']['trade_not_exist']);
	}
	sql_query("update sys_city_trade set endtime=(endtime-unix_timestamp())/10+unix_timestamp() where id='$tradeid'");
	sql_query("update mem_city_trade set endtime=(endtime-unix_timestamp())/10+unix_timestamp() where id='$tradeid'");
	reduceGoods($uid,11,1);
	return getMarketInfo($uid,$cid);
}

function getMerchantInfo($uid,$cid,$param)
{
    $today = intval(date("Ymd"));
    $merchant = sql_fetch_one("select * from sys_city_merchant where `cid`='$cid' and trade_day='$today'"); 
    if (empty($merchant))         //has no today trade data
    {
        sql_query("insert into sys_city_merchant (`cid`,`food`,`wood`,`rock`,`iron`,`gold`,`trade_day`) values ('$cid','".MERCHANT_INIT_FOOD."','".MERCHANT_INIT_WOOD."','".MERCHANT_INIT_ROCK."','".MERCHANT_INIT_IRON."','".MERCHANT_INIT_GOLD."',$today) on duplicate key update `food`='".MERCHANT_INIT_FOOD."',`wood`='".MERCHANT_INIT_WOOD."',`rock`='".MERCHANT_INIT_ROCK."',`iron`='".MERCHANT_INIT_IRON."',`gold`='".MERCHANT_INIT_GOLD."',`trade_day`='$today'");
        $merchant = array('cid'=>$cid,
            'food'=>MERCHANT_INIT_FOOD,
            'wood'=>MERCHANT_INIT_WOOD,
            'rock'=>MERCHANT_INIT_ROCK,
            'iron'=>MERCHANT_INIT_IRON,
            'gold'=>MERCHANT_INIT_GOLD,
            'trade_day'=>$today);
    }
    $merchant['food_buy_price'] = MERCHANT_FOOD_BUY_PRICE;
    $merchant['wood_buy_price'] = MERCHANT_WOOD_BUY_PRICE;
    $merchant['rock_buy_price'] = MERCHANT_ROCK_BUY_PRICE;
    $merchant['iron_buy_price'] = MERCHANT_IRON_BUY_PRICE;
    $merchant['food_buy_price'] = MERCHANT_FOOD_SELL_PRICE;
    $merchant['wood_buy_price'] = MERCHANT_WOOD_SELL_PRICE;
    $merchant['rock_buy_price'] = MERCHANT_ROCK_SELL_PRICE;
    $merchant['iron_buy_price'] = MERCHANT_IRON_SELL_PRICE;
    return $merchant;
}

function buyFromMerchant($uid,$cid,$param)
{
    $food = intval(array_shift($param));
    $wood = intval(array_shift($param));
    $rock = intval(array_shift($param));
    $iron = intval(array_shift($param));   
    if (($food < 0)||($wood < 0)||($rock < 0)||($iron < 0)||($food==0&&$wood==0&&$rock==0&&$iron==0))
    {
        throw new Exception($GLOBALS['buyFromMerchant']['input_amount']);
    }
    $gold = $food * FOOD_PRICE + $wood * WOOD_PRICE + $rock * ROCK_PRICE + $iron * IRON_PRICE;
    $res = sql_fetch_one("select * from mem_city_resource where cid='$cid'");
    if ($gold > $res['gold'])
    {
        throw new Exception($GLOBALS['buyFromMerchant']['no_enough_gold']);
    }
    $level=sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_MARKET);
    if($gold>$level*100000)
    {
    	throw new Exception(sprintf($GLOBALS['buyFromMerchant']['buy_limit'],$level,$level));
    }
    $usermoney = sql_fetch_one_cell("select money from sys_user where uid='$uid'");
    if ($usermoney < 5)
    {
        throw new Exception($GLOBALS['buyFromMerchant']['no_enough_YuanBao']);
    }
    sql_query("update mem_city_resource set gold=gold-$gold,food=food+$food,wood=wood+$wood,rock=rock+$rock,iron=iron+$iron where cid='$cid'");
    completeTask($uid,24);
    addMoney($uid,-5,50);
    throw new Exception($GLOBALS['buyFromMerchant']['succ']);      
    return getMarketInfo($uid,$cid);
}
function sellToMerchant($uid,$cid,$param)
{
    $food = intval(array_shift($param));
    $wood = intval(array_shift($param));
    $rock = intval(array_shift($param));
    $iron = intval(array_shift($param));  
    if (($food < 0)||($wood < 0)||($rock < 0)||($iron < 0)||($food==0&&$wood==0&&$rock==0&&$iron==0))
    {
        throw new Exception($GLOBALS['sellToMerchant']['input_amount']);
    }
    $res = sql_fetch_one("select * from mem_city_resource where cid='$cid'");
    if ($food > $res['food']) throw new Exception($GLOBALS['sellToMerchant']['no_enough_food']);
    if ($wood > $res['wood']) throw new Exception($GLOBALS['sellToMerchant']['no_enough_wood']);
    if ($rock > $res['rock']) throw new Exception($GLOBALS['sellToMerchant']['no_enough_rock']);
    if ($iron > $res['iron']) throw new Exception($GLOBALS['sellToMerchant']['no_enough_iron']);
    
    $usermoney = sql_fetch_one_cell("select money from sys_user where uid='$uid'");
    if ($usermoney < 5)
    {
        throw new Exception($GLOBALS['sellToMerchant']['no_enough_YuanBao']);
    }
    
    $gold = $food * FOOD_PRICE + $wood * WOOD_PRICE + $rock * ROCK_PRICE + $iron * IRON_PRICE;
    $level=sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_MARKET);
    if($gold>$level*100000)
    {
    	throw new Exception(sprintf($GLOBALS['buyFromMerchant']['buy_limit'],$level,$level));
    }
    
    
    sql_query("update mem_city_resource set gold=gold+$gold,food=food-$food,wood=wood-$wood,rock=rock-$rock,iron=iron-$iron where cid='$cid'");   
    completeTask($uid,23); 
    addMoney($uid,-5,51);
    throw new Exception($GLOBALS['sellToMerchant']['succ']);      
}
function getCityTradeUsing($cid)
{
    return sql_fetch_one_cell("select count(*) from sys_city_trade where cid='$cid' or buycid='$cid'");  
}
function getCityMarketLevel($cid)
{
    return sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_MARKET." limit 1"); 
}
function sellToUser($uid,$cid,$param)
{
    $resType = array_shift($param);
    $rescount = array_shift($param);
    $gold = array_shift($param);
    $hour = intval(array_shift($param));
    //$minute = array_shift($param);
    $unionsel=array_shift($param);
    $price = $gold / $rescount;
    $price = round($price * 100) / 100.0;
    
    if ($rescount <= 0)
    {
    	throw new Exception($GLOBALS['sellToUser']['invalid_amount']);
    }
    if ($hour<1)
    {
    	throw new Exception($GLOBALS['sellToUser']['trade_time_limit']);
    }
    else if($hour>10000)
    {
    	$hour=10000;
    }
    $count = getCityTradeUsing($cid) ;
    $marketLevel = getCityMarketLevel($cid);
    
    if ($count >= $marketLevel)
    {
        throw new Exception($GLOBALS['sellToUser']['no_free_caravan']);
    }
    
    if ($rescount > $marketLevel * 100000)
    {
    	$msg = sprintf($GLOBALS['sellToUser']['single_trade_upperLimit'],$marketLevel,$marketLevel);
        throw new Exception($msg);
    }
    
    $res = sql_fetch_one("select * from mem_city_resource where cid='$cid'");
    if ($resType == 0)
    {
        if ($rescount > $res['food']) throw new Exception($GLOBALS['sellToUser']['no_enough_food']);
        if (($price < FOOD_PRICE * 0.79)||($price > FOOD_PRICE * 1.21))
        {
            throw new Exception($GLOBALS['sellToUser']['price_runaway']);
        }
        sql_query("update mem_city_resource set food=food-$rescount where cid='$cid'");
    }
    else if ($resType == 1)
    {
        if ($rescount > $res['wood']) throw new Exception($GLOBALS['sellToUser']['no_enough_wood']);
        if (($price < WOOD_PRICE * 0.79)||($price > WOOD_PRICE * 1.21))
        {
            throw new Exception($GLOBALS['sellToUser']['price_runaway']);
        }
        sql_query("update mem_city_resource set wood=wood-$rescount where cid='$cid'");
    }
    else if ($resType == 2)
    {
        if ($rescount > $res['rock']) throw new Exception($GLOBALS['sellToUser']['no_enough_rock']);
        if (($price < ROCK_PRICE * 0.79)||($price > ROCK_PRICE * 1.21))
        {
            throw new Exception($GLOBALS['sellToUser']['price_runaway']);
        }
        sql_query("update mem_city_resource set rock=rock-$rescount where cid='$cid'");
    }
    else if ($resType == 3)
    {
        if ($rescount > $res['iron']) throw new Exception($GLOBALS['sellToUser']['no_enough_iron']);
        if (($price < IRON_PRICE * 0.79)||($price > IRON_PRICE * 1.21))
        {
            throw new Exception($GLOBALS['sellToUser']['price_runaway']);
        }
        sql_query("update mem_city_resource set iron=iron-$rescount where cid='$cid'");
    }
    $unionid=0;
    if($unionsel>0)
    {
    	$unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
    }
    $second =  $hour * 3600;// + $minute*60;                
    $gridtime = GRID_DISTANCE / (1.0 *MERCHANT_MOVE_SPEED); 
    $distance = ($second / $gridtime) * ($second / $gridtime);     
    sql_query("insert into sys_city_trade (cid,state,restype,count,price,gold,distance,`unionid`,limittime) values ('$cid','0','$resType','$rescount','$price','$gold','$distance','$unionid','$second')");
    return getMarketInfo($uid,$cid);
}
function buyFromUser($uid,$cid,$param)
{
    $tradeid = array_shift($param);
    $trade = sql_fetch_one("select * from sys_city_trade where id='$tradeid'");
    if (empty($trade))
    {
        throw new Exception($GLOBALS['buyFromUser']['trade_not_exist']);
    }
    if ($trade['state'] == 1)
    {
        throw new Exception($GLOBALS['buyFromUser']['bought_by_others']);
    }
    //同一玩家不能买卖
    $tradeuid = sql_fetch_one_cell("select uid from sys_city where cid='$trade[cid]'");
    if ($tradeuid == $uid)
    {
        throw new Exception($GLOBALS['buyFromUser']['cant_buy_from_yourself']);
    }
    
    $res = sql_fetch_one("select * from mem_city_resource where cid='$cid'");
    if ($res['gold'] < $trade['gold'])
    {
        throw new Exception($GLOBALS['buyFromUser']['no_enough_gold']);
    }
    $count = getCityTradeUsing($cid) ;
    $marketLevel = getCityMarketLevel($cid);
    if ($count >= $marketLevel)
    {
        throw new Exception($GLOBALS['buyFromUser']['no_free_caravan']);
    }   

    $currx = $cid % 1000;
    $curry = floor($cid / 1000);
    $targx = $trade['cid'] % 1000;
    $targy = floor($trade['cid'] / 1000);
    if ((($currx - $targx)*($currx - $targx) +($curry - $targy)*($curry - $targy)) > $trade['distance'])
    {
        throw new Exception($GLOBALS['buyFromUser']['distance_too_far']);
    }
    
    if($trade['unionid']>0)
    {
    	$unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
    	if($trade['unionid']!=$unionid)
    	{
    		throw new Exception($GLOBALS['buyFromUser']['sell_to_union_only']);
    	}
    }
 
    sql_query("update sys_city_trade set state=1,buycid='$cid' where id='$tradeid' and state=0");
    $buycid = sql_fetch_one_cell("select buycid from sys_city_trade where id='$tradeid'");
    if ($buycid == $cid)
    {
         sql_query("update mem_city_resource set `gold`=`gold`-'$trade[gold]' where cid='$cid'");
         $needtime = getCityDistance($trade['cid'],$cid) * GRID_DISTANCE / MERCHANT_MOVE_SPEED;
         sql_query("update sys_city_trade set endtime=unix_timestamp() + $needtime where id='$tradeid'");
         sql_query("replace into mem_city_trade (`id`,`endtime`) values ('$tradeid',unix_timestamp() + $needtime)");
         completeTask($tradeuid,25);
         completeTask($uid,26);
    }
    else
    {
        throw new Exception($GLOBALS['buyFromUser']['bought_by_others']);
    }
    return getMarketInfo($uid,$cid);
}
function getUserBuyList($uid,$cid,$param)
{
    $page = intval(array_shift($param));
    $filter = intval(array_shift($param));
    $filterResource = "";
    $filterResource2 = "";
    if ($filter > 0)
    {
        $filterResource = "and restype = ".($filter-1);
        $filterResource2 = "and t.restype = ".($filter-1);
    }
    
    $unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
    
    $ret = array();
    $ret[] = getCityTradeUsing($cid);
    $ret[] = getCityMarketLevel($cid);
    $itemCount = sql_fetch_one_cell("select count(*) from sys_city_trade where state=0 and (unionid=0 or unionid='$unionid') $filterResource and distance > ((`cid`%1000-'$cid'%1000)*(`cid`%1000-'$cid'%1000)+(floor(`cid`/1000)-floor('$cid'/1000))*(floor(`cid`/1000)-floor('$cid'/1000)))");
    $pageCount = ceil($itemCount /MARKET_LIST_CPP);
    if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    	$pageCount=0;
    }
    if($itemCount>0)
    {
    	$pagestart = $page * MARKET_LIST_CPP;
    	$ret[]=$pageCount;
    	$ret[]=$page;
    	$ret[] = sql_fetch_rows("select t.*,u.name as sellername,un.name as unionname from sys_city_trade t left join sys_city c on c.cid=t.cid left join sys_user u on u.uid=c.uid left join sys_union un on un.id=t.unionid where t.cid<>'$cid' and t.state=0 and (t.unionid=0 or t.unionid='$unionid') $filterResource2 and t.distance >= ((t.`cid`%1000-'$cid'%1000)*(t.`cid`%1000-'$cid'%1000)+(floor(t.`cid`/1000)-floor('$cid'/1000))*(floor(t.`cid`/1000)-floor('$cid'/1000))) order by ((t.`cid`%1000-'$cid'%1000)*(t.`cid`%1000-'$cid'%1000)+(floor(t.`cid`/1000)-floor('$cid'/1000))*(floor(t.`cid`/1000)-floor('$cid'/1000))) limit $pagestart,".MARKET_LIST_CPP);
    }
    else
    {
    	$ret[]=0;
    	$ret[]=0;
    	$ret[]=array();
    }
    return $ret;
}
function getUserSellData($uid,$cid,$param)
{
    $ret = array();
    //商队
    $ret[] = getCityTradeUsing($cid);
    $ret[] = getCityMarketLevel($cid);
    
    //官价
    $ret[] = FOOD_PRICE;
    $ret[] = WOOD_PRICE;
    $ret[] = ROCK_PRICE;
    $ret[] = IRON_PRICE;
    return $ret;
}

?>