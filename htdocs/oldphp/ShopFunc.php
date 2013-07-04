<?php                      
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");


function getShopInfo($uid,$param)
{
    return sql_fetch_rows("select * from cfg_shop where onsale=1 and starttime<=unix_timestamp() and endtime>unix_timestamp() order by position,id");
}
function buyGoods($uid,$param)
{
    $id = array_shift($param);
    $cnt = intval(array_shift($param));
    
    if ($cnt < 1) throw new Exception($GLOBALS['buyGoods']['invalid_amount']);
    $goods = sql_fetch_one("select * from cfg_shop where id='$id' and onsale=1 and starttime<=unix_timestamp() and endtime>unix_timestamp()");
    if (empty($goods)) throw new Exception($GLOBALS['buyGoods']['stop_sale']);
    $moneyNeed = $cnt * $goods['price'];
    lockUser($uid);
    $userInfo = sql_fetch_one("select nobility,money from sys_user where uid='$uid'");
    $userMoney=$userInfo['money'];
    if ($userMoney < $moneyNeed)	throw new Exception($GLOBALS['buyGoods']['no_enough_YuanBao']);
    if(($id==121)&&($userInfo['nobility']<1))	throw new Exception($GLOBALS['buyGoods']['nobility_limit']);
   	if($goods['totalCount']<2000000000)
	{
		if($goods['totalCount']==0) throw new Exception($GLOBALS['buyGoods']['sold_out']);
	}
	if(($goods['userbuycnt']>0)||($goods['daybuycnt']>0)) //属于限制商品
	{
		$buycnt=intval(sql_fetch_one_cell("select `count` from log_shop_buy_cnt where uid='$uid' and `sid`='$id'"));
		if(($goods['userbuycnt']>0)&&($buycnt+$cnt>$goods['userbuycnt']))
		{
			if($goods['userbuycnt']>$buycnt)
			{
				$remain=$goods['userbuycnt']-$buycnt;
				$msg = sprintf($GLOBALS['buyGoods']['reach_remain_amountLimit'],$goods['userbuycnt'],$buycnt,$remain);
				throw new Exception($msg);
			}
			else 
			{
				$msg = sprintf($GLOBALS['buyGoods']['reach_buy_limit'],$goods['userbuycnt']);
				throw new Exception($msg);
			}
		}
		if(($goods['daybuycnt']>0)&&($buycnt+$cnt>$goods['daybuycnt']))
		{
			if($goods['daybuycnt']>$buycnt)
			{
				$remain=$goods['daybuycnt']-$buycnt;
				$msg = sprintf($GLOBALS['buyGoods']['reach_remain_amount_todayLimit'],$goods['daybuycnt'],$buycnt,$remain);
				throw new Exception($msg);
			}
			else 
			{
				$msg = sprintf($GLOBALS['buyGoods']['reach_buy_todayLimit'],$goods['daybuycnt']);
				throw new Exception($msg);
			}
		}
		sql_query("insert into log_shop_buy_cnt (`uid`,`sid`,`count`) values ('$uid','$id','$cnt') on duplicate key update `count`=`count`+'$cnt'");
	}
    //一手交货 
    addGoods($uid,$goods['gid'],$goods['pack'] * $cnt,2);
    //一手交钱
    addMoney($uid,-$moneyNeed,10);

    sql_query("insert into log_shop (uid,shopid,count,price,time) values ('$uid','$id','$cnt','$goods[price]',unix_timestamp())");             
    completeTask($uid,366);

    unlockUser($uid);
    $ret = array();
    $ret[] = $userMoney - $moneyNeed;
    return $ret;
}
function exchangeLiquan($uid,$param)
{
    $code = addslashes(trim(array_shift($param)));
    if (empty($code)) throw new Exception($GLOBALS['exchangeLiquan']['code_notNull']);
    
    if (!preg_match("/[a-zA-Z0-9]{10}/",$code)) throw new Exception($GLOBALS['exchangeLiquan']['invalid_code']);

    $item=sql_fetch_one("select * from sys_ticket where code='$code' limit 1");
    if(empty($item))
    {
        throw new Exception($GLOBALS['exchangeLiquan']['invalid_code']);
    }
    else if ($item['uid']>0)
    {
        throw new Exception($GLOBALS['exchangeLiquan']['used_code']);
    }
    else if(($item['binduid']>0)&&($item['binduid']!=$uid))
    {
        throw new Exception($GLOBALS['exchangeLiquan']['code_bind']);
    }
    $content=sql_fetch_one("select * from sys_ticket_content where id='$item[contentid]'");
    sql_query("update sys_ticket set uid='$uid',time=unix_timestamp() where id='$item[id]'");
    $goods=explode(",",$content['content']);
    $goodcnt=$goods[0];
    $money=0;
    $ret=array();
    lockUser($uid);
    for ($i = 1; $i < $goodcnt*3; $i+=3)
    {
    	$type=$goods[$i];
		$gid=$goods[$i+1];
		$cnt=$goods[$i+2];
		if($type==0)
		{
	    	if($gid==0)
	    	{
	    		$money+=$cnt;
	    	}
	    	else
	    	{
		    	addGoods($uid,$gid,$cnt,8);
	    	}
	    	$good=sql_fetch_one("select *,$cnt as count,'0' as gype from cfg_goods where gid='$gid'");
	    	$good['count']=$cnt;
	    	$good['gtype']=0;
		   	$ret[] = $good;
    	}
    	else if($type==1)
    	{
    		$armor=sql_fetch_one("select * from cfg_armor where id='$gid'");
    		$armor['count']=$cnt;
	    	$armor['gtype']=1;
	    	$armor['hp']=$armor['ori_hp_max'];
	    	$armor['hp_max']=$armor['ori_hp_max'];
		   	$ret[] = $armor;
    		addArmor($uid,$armor,1,3);
    	}
    	else if($type==2)
    	{
    		$thing=sql_fetch_one("select * from cfg_things where tid='$gid'");
		   	$thing['count']=$cnt;
	    	$thing['gtype']=2;
		   	$ret[] = $thing;
		    addThings($uid,$gid,$cnt,8);
    	}
    }
    if($money>0) addMoney($uid,$money,3);
    unlockUser($uid);
    $ret2=array();
    $ret2[]=$ret;
    return $ret2;
}
?>