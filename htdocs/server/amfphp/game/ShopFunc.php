<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");


function getShopInfo($uid,$param)
{
	$ret = array();
	$ret[] = sql_fetch_rows("select * from cfg_shop where onsale=1 and starttime<=unix_timestamp() and endtime>unix_timestamp() order by position,id");
	$ret[] = sql_fetch_rows("select * from cfg_armor where id in (select gid from cfg_shop where `onsale`=1 and `group`=6)");
	$ret[] = sql_fetch_rows("select * from cfg_hero where id in (select gid from cfg_shop where `onsale`=1 and `group`=6)");
	return $ret;
}

/**
 * add by jun zhao
 * @param $uid
 * @param $param
 * @return unknown_type
 */
function buyBattleGoods($uid, $param)
{
	$id = array_shift($param);
	$cnt = intval(array_shift($param));
	$battleGoodsType = array_shift($param);
	$cityId = array_shift($param);
	if ($cnt < 1) throw new Exception($GLOBALS['buyGoods']['invalid_amount']);

	if ($cnt < 1) throw new Exception($GLOBALS['buyGoods']['invalid_amount']);
	$goods = sql_fetch_one("select * from cfg_shop where id='$id' and onsale=1 and starttime<=unix_timestamp() and endtime>unix_timestamp()");
	if (empty($goods)) throw new Exception($GLOBALS['buyGoods']['stop_sale']);
	$creditNeed = $cnt * $goods['creditPrice'];
	$medalNeed = $cnt * $goods['medalPrice'];
	$medalTypeId = $goods['medalTypeId'];
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$userInfo = sql_fetch_one("select honour, nobility from sys_user where uid='$uid'");
	//用户有的荣誉
	$userCredit = $userInfo['honour'];
	if($userCredit < $creditNeed){
		throw new Exception($GLOBALS['buyGoods']['no_enough_Credit']);
	}
	$medalinfo = sql_fetch_one("select count from sys_things where uid='$uid' and tid='$medalTypeId'");
	$userMedal = 0;
	if(empty($medalinfo)){
	}
	else
	$userMedal = $medalinfo['count'];
	if($userMedal < $medalNeed){
		$mName = "";
		if($medalTypeId == 30000){
			$mName = $GLOBALS['thingsname']['hanshi_xunzhang'];
		}
		if($medalTypeId == 30001) {
			$mName = $GLOBALS['thingsname']['pingding_huangjin_xunzhang'];
		}
		if($medalTypeId == 30002){
			$mName = $GLOBALS['thingsname']['yuanjun_guandu_xunzhang'];
		}
		if($medalTypeId == 30003){
			$mName = $GLOBALS['thingsname']['caojun_guandu_xunzhang'];
		}
		$msg = sprintf($GLOBALS['buyGoods']['no_enough_Medal'], $mName);
		throw new Exception($msg);
	}

	//限制商品
	if(($id==121)&&($userInfo['nobility']<1))	throw new Exception($GLOBALS['buyGoods']['nobility_limit']);
	if($goods['totalCount']<2000000000)
	{
		if($goods['totalCount']==0) throw new Exception($GLOBALS['buyGoods']['sold_out']);
	}
	if(($goods['userbuycnt']>0)||($goods['battledaybuycnt']>0)) //属于限制商品
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

		if(($goods['battledaybuycnt']>0)&&($buycnt+$cnt>$goods['battledaybuycnt']))
		{
			if($goods['battledaybuycnt']>$buycnt)
			{
				$remain=$goods['battledaybuycnt']-$buycnt;
				$msg = sprintf($GLOBALS['buyGoods']['reach_remain_amount_todayLimit'],$goods['battledaybuycnt'],$buycnt,$remain);
				throw new Exception($msg);
			}
			else
			{
				$msg = sprintf($GLOBALS['buyGoods']['reach_buy_todayLimit'],$goods['battledaybuycnt']);
				throw new Exception($msg);
			}
		}
	}
	//一手交货 普通物品, 战场装备, 战场将领
	if($battleGoodsType == 0) //普通物品
	addGoods($uid,$goods['gid'],$goods['pack'] * $cnt,2);
	else if($battleGoodsType == 1) //属于战场装备
	{
		if($cnt > 1) throw new Exception($GLOBALS['buyGoods']['only_one_goods']);
		addBattleArmor($uid, $goods['gid'], $cnt); //gid这里其实对应 cfg_armor的arm id
	}
	else if($battleGoodsType == 2) //属于战场将领
	{
		if($cnt > 1) throw new Exception($GLOBALS['buyGoods']['only_one_goods']);
		addBattleHero($uid, $goods['gid'], $cnt, $cityId);//gid这里其实对应 cfg_hero的hero id
	}
	//一手交勋章
	addCreditAndMedal($uid,-$creditNeed, -$medalNeed, $medalTypeId);
	unlockUser($uid);
	sql_query("insert into log_shop_buy_cnt (`uid`,`sid`,`count`) values ('$uid','$id','$cnt') on duplicate key update `count`=`count`+'$cnt'");
	$ret = array();
	$ret[] = $userCredit = $creditNeed;
	return $ret;
}

function getGoodsHeroAttr($uid, $param)
{
	$hid = array_shift($param);
	$hinfo = sql_fetch_one("select * from cfg_hero where id=$hid");
	if(empty($hinfo)){
		throw new Exception($GLOBALS['buyGoods']['no_tip']);
	}
	$ret = array();
	$ret[] = $hinfo;
	return $ret;
}

function getGoodsArmorAttr($uid, $param)
{
	$aid = array_shift($param);
	$armorinfo = sql_fetch_one("select * from cfg_armor where id=$aid");
	if(empty($armorinfo)){
		throw new Exception($GLOBALS['buyGoods']['no_tip']);
	}
	$ret = array();
	$ret[] = $armorinfo;

	return $ret;
}

function addBattleHero($uid, $hid, $cnt, $cityId)
{
	$chid = $hid;
	$hero = sql_fetch_one("select * from cfg_hero where id='$hid'");
	if(empty($hero))
	throw new Exception($GLOBALS['buyGoods']['can_not_exchange']);

	if (cityHasHeroPosition($uid,$cityId)== false){ //招贤馆等级是否够
		throw new Exception($GLOBALS['recruitHero']['hotel_level_low']);
	}
	$_add = intval(intval($hero['level'])/3);
	$affairs = intval($hero['affairs_base']) ;//+ $_add; //"内政 "
	$bravery = intval($hero['bravery_base']) ;//+ $_add; //"勇武 "
	$levelLeft = intval($hero['level']) - $_add - $_add;
	$wisdom = intval($hero['wisdom_base']) ;//+ $levelLeft; //"智谋 "
	$affairs_add = $_add;
	$bravery_add = $_add;
	$wisdom_add = $levelLeft;

	$command = intval($hero['command_base']); //+ intval($hero['level']); // "统帅"




	$total_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$hero[level]'");

	$val = sprintf("'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','20000'",
	$uid, $hero['name'], $hero['npcid'], $hero['sex'], $hero['face'], $cityId, 0, $hero['level'],
	$total_exp, $affairs, $bravery, $wisdom,
	$affairs_add, $bravery_add, $wisdom_add, $hero['loyalty'], $command);

	//$val = sprintf("'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'",
	//		$uid, $hero['name'], $hero['npcid'], $hero['sex'], $hero['face'], $cityId, 0, $hero['level'],
	//		$hero['exp'], $hero['affairs_base'], $hero['bravery_base'], $hero['wisdom_base'],
	//		$hero['affairs_add'], $hero['bravery_add'], $hero['wisdom_add'], $hero['loyalty']);

	$sql = "insert into sys_city_hero (`uid`,`name`,`npcid`,`sex`,
	`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`, `command_base`, `herotype`) values ($val)";

	$hid = sql_insert($sql);
	$forcemax= intval(100+$hero['level']/5+($hero['bravery_base']+$hero['bravery_add'])/3 );
	$energymax= intval(100+$hero['level']/5+($hero['wisdom_base']+$hero['wisdom_add'])/3);
	sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`)
	values ('$hid', '$forcemax','$forcemax','$energymax','$energymax') on duplicate key update `force`='$forcemax',`energy`='$energymax',`force_max`='$forcemax', `energy_max`='$energymax' ");
	sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid','$chid','$cnt',unix_timestamp(), 10)"); //属于商场购买,但是属于勋章换来的Hero

}

function addBattleArmor($uid, $aid, $cnt)
{
	$arminfo = sql_fetch_one("select * from cfg_armor where id='$aid'");
	if(empty($arminfo))
	throw new Exception($GLOBALS['buyGoods']['can_not_exchange']);
	$hp = $arminfo['ori_hp_max'];
	sql_query("insert into sys_user_armor (uid,armorid,hp, hp_max, hid) values ('$uid','$aid',$hp*10, '$hp', 0)");
	sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid','$aid','$cnt',unix_timestamp(), 11)"); //属于商场购买,但是属于勋章换来的装备
}

function addCreditAndMedal($uid,$credit, $medal, $medalTypeId)
{
	sql_query("update sys_user set honour=honour+'$credit' where uid='$uid'");
	sql_query("update sys_things set count=count+'$medal' where uid='$uid' and tid='$medalTypeId'");
	//sql_query("insert into log_money (uid,count,time,type) values ('$uid','$money',unix_timestamp(),'$type')");
}

function medalChange($uid, $param)
{
	$count = array_shift($param);
	$medalTypeId = array_shift($param);
	/**
	 * 20平定黄巾勋章=1汉室勋章。
		10袁军官渡勋章=1汉室勋章。
		12曹军官渡勋章=1汉室勋章。
	 */
	$mName = "";
	$radio = 1;
	if($medalTypeId == 30001)
	{
		$mName = $GLOBALS['thingsname']['pingding_huangjin_xunzhang'];
		$radio = 20;
	}
	if($medalTypeId == 30002)
	{
		$mName = $GLOBALS['thingsname']['yuanjun_guandu_xunzhang'];
		$radio = 10;
	}
	if($medalTypeId == 30003)
	{
		$mName = $GLOBALS['thingsname']['caojun_guandu_xunzhang'];
		$radio = 12;
	}
	$medalinfo = sql_fetch_one("select * from sys_things where tid='$medalTypeId' and uid='$uid'");
	if(empty($medalinfo))
	{
		$emsg = sprintf($GLOBALS['buyGoods']['no_medal'], $mName, $count);
		throw new Exception($emsg);
	}
	if( intval($medalinfo['count']) < $radio * intval($count)){
		$msg = sprintf($GLOBALS['buyGoods']['no_medal'], $mName, $count);
		throw new Exception($msg);
	}
	$needMedal = $radio * intval($count);

	$hsMedal = sql_fetch_one("select * from sys_things where uid='$uid' and tid='30000'");

	if(empty($hsMedal)){
		sql_query("insert into sys_things(`uid`, `tid`, `count`) values('$uid', '30000' , '$count')"); //更新汉室勋章的数量
	}
	else{
		sql_query("update sys_things set count=count+'$count' where uid='$uid' and tid='30000'"); //更新汉室勋章的数量
	}
	sql_query("update sys_things set count=count-'$needMedal' where uid='$uid' and tid='$medalTypeId'");

	$ret = array();
	return $ret;
}

function buyGoods($uid,$param)
{
	$id = array_shift($param);
	$cnt = intval(array_shift($param));
	$paytype= intval(array_shift($param));
	if($paytype!=0&&$paytype!=1){
		throw new Exception($GLOBALS['buyGoods']['invalid_pay_type']);
	}

	if ($cnt < 1) throw new Exception($GLOBALS['buyGoods']['invalid_amount']);
	$goods = sql_fetch_one("select * from cfg_shop where id='$id' and onsale=1 and starttime<=unix_timestamp() and endtime>unix_timestamp()");
	if (empty($goods)) throw new Exception($GLOBALS['buyGoods']['stop_sale']);
	$moneyNeed = $cnt * $goods['price'];
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$userInfo = sql_fetch_one("select money,gift from sys_user where uid='$uid'");
	//推恩
	$userInfo['nobility']=getBufferNobility($uid,$userInfo['nobility']);
	//元宝
	$userMoney=$userInfo['money'];
	//礼金
	$userGift=$userInfo['gift'];
	if ($paytype==0&&($userMoney < $moneyNeed))	throw new Exception($GLOBALS['buyGoods']['no_enough_YuanBao']);
	if ($paytype==1&&($userGift < $moneyNeed))	throw new Exception($GLOBALS['buyGoods']['no_enough_Gift']);

	if(($id==121)&&($userInfo['nobility']<1))	throw new Exception($GLOBALS['buyGoods']['nobility_limit']);
	if($goods['totalCount']<2000000000)
	{
		if($goods['totalCount']==0) throw new Exception($GLOBALS['buyGoods']['sold_out']);
	}
	if(($goods['userbuycnt']>0)||($goods['daybuycnt']>0)) //属于限制商品
	{
		$buycnt=intval(sql_fetch_one_cell("select `count` from log_shop_buy_cnt where uid='$uid' and `sid`='$id'"));
		$todaybuycnt=intval(sql_fetch_one_cell("select sum(count) from log_shop where uid ='$uid' and shopid = '$id' and date(now())=date(from_unixtime(time))"));
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
		if(($goods['daybuycnt']>0)&&($todaybuycnt+$cnt>$goods['daybuycnt']))
		{
			if($goods['daybuycnt']>$todaybuycnt)
			{
				$remain=$goods['daybuycnt']-$todaybuycnt;
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
	if($paytype==0)
	addMoney($uid,-$moneyNeed,10);
	else if($paytype==1)
	addGift($uid,-$moneyNeed,10);
	sql_query("update sys_user set last_pay='$paytype' where uid='$uid'");
	sql_query("insert into log_shop (uid,shopid,count,price,time) values ('$uid','$id','$cnt','$goods[price]',unix_timestamp())");
	 
	completeTask($uid,366);

	unlockUser($uid);
	$ret = array();
	$ret[]=$paytype;
	if($paytype==0)
	$ret[] = $userMoney - $moneyNeed;
	else if($paytype==1){
		$ret[] = $userGift - $moneyNeed;
	}
	sql_query("update sys_user set last_pay='$paytype' where uid='$uid'");
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
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$isYuanBao=false;
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
			}else if($gid==-100){
				$money+=$cnt;
				$isYuanBao=true;
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
	if($money>0){
		if($isYuanBao)
		addMoney($uid,$money,3);
		else
		addGift($uid,$money,3);
	}
	unlockUser($uid);
	$ret2=array();
	$ret2[]=$ret;
	return $ret2;
}

function getMedalRecord($uid, $param)
{
	//30000~30003 tid: 汉室勋章	平定黄巾勋章	袁军官渡勋章	曹军官渡勋章
	return sql_fetch_rows("select * from sys_things as A, cfg_things as B where A.uid='$uid' and A.tid = B.tid and A.tid>=30000 and A.tid<=30003");
}
?>