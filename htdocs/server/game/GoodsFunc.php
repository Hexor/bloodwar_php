<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./HeroFunc.php");
require_once("EquipmentFunc.php");

function loadUserGoods($uid,$param)
{
	$ret = array();
	$ret[] = sql_fetch_rows("select * from sys_goods g left join cfg_goods f on f.gid=g.gid where g.uid='$uid' and g.`count` > 0 order by f.`group`,f.position");
	$ret[] = sql_fetch_rows("select * from sys_things t left join cfg_things f on f.tid=t.tid where t.uid='$uid' and t.`count` > 0 order by f.position");
	return $ret;
}

function useGoods($uid,$param)
{
	$gid = array_shift($param);
	$ret = array();
	$ret[]=$gid;
	$cnt = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'");
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	if (empty($cnt))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	if ($gid == 1)
	{
		throw new Exception($GLOBALS['useGoods']['acoustic_used_in_world_channel']);
	}
	else if (($gid == 2)||($gid == 44)) //神农锄    粮食产量增加25%，持续24小时。
	{
		$endtime = useShenNongChu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['ShenNongChu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid == 3)||($gid == 45)) //鲁班斧    木材产量增加25%，持续24小时。
	{
		$endtime = useLuBanFu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['LuBanFu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid == 4)||($gid == 46)) //开山锤    石料产量增加25%，持续24小时。  
	{
		$endtime = useKaiShanCui($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['KaiShanCui_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid == 5)||($gid == 47)) //玄铁炉    铁锭产量增加25%，持续24小时。    
	{
		$endtime = useXuanTieLu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['XuanTieLu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid == 6)||($gid == 48))
	{
		$endtime = useXianZhenZhaoGu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		if($gid==10084) $ret[] = $GLOBALS['useGoods']['XianZhenZhaoGu_qiang_date'];
		else $ret[] = $GLOBALS['useGoods']['XianZhenZhaoGu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid == 7)||($gid == 49)||($gid==10085)) //八阵图
	{
		$endtime = useBaGuaZhenTu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		if($gid==10085) $ret[] = $GLOBALS['useGoods']['BaGuaZhenTu_qiang_date'];
		else $ret[] = $GLOBALS['useGoods']['BaGuaZhenTu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if ($gid == 8)   //墨家残卷
	{
		throw new Exception($GLOBALS['useGoods']['MoJiaCanJuan']);
	}
	else if ($gid == 9)
	{
		throw new Exception($GLOBALS['useGoods']['MojiaTuZhi']);
	}
	else if ($gid == 10)
	{
		throw new Exception($GLOBALS['useGoods']['MoJiaDianJi']);
	}
	else if ($gid == 12)
	{
		throw new Exception($GLOBALS['useGoods']['MianZhanPai']);
	}
	else if ($gid == 13)
	{
		throw new Exception($GLOBALS['useGoods']['JinNang']);
	}
	else if ($gid == 15)
	{
		useMenzhulin($uid,$cid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $GLOBALS['useGoods']['use_MengZhuLing_succ'];
	}
	else if ($gid == 22)  // 洗髓丹
	{
		throw new Exception($GLOBALS['useGoods']['XiSuiDan_used_for_reset_hero']);
	}
	else if ($gid == 23)    //招贤榜
	{
		throw new Exception($GLOBALS['useGoods']['ZhaoXianBang_used_for_hire_hero']);
	}
	else if (($gid == 16)||($gid == 19))    //青铜宝箱
	{
		$ret[] = 1;     //代表开宝箱
		$ret[] = useCopperBox($uid);
	}
	else if (($gid == 17)||($gid == 20))
	{
		$ret[] = 1;     //代表开宝箱
		$ret[] = useSilverBox($uid);
	}
	else if (($gid == 18)||($gid == 21))
	{
		$ret[] = 1;     //代表开宝箱
		$ret[] = useGoldBox($uid);
	}
	else if ($gid == 25 || $gid==10083)	//青囊书
	{
		$endtime = useQingNangShu($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		if($gid==10083) $ret[] = $GLOBALS['useGoods']['QingNangShu_shangji_date'];
		else $ret[] = $GLOBALS['useGoods']['QingNangShu_valid_date'];
		$ret[] = intval($endtime);
	}
	else if (($gid >=41)&&($gid<=43)) //珠宝盒
	{
		$ret[]=1;
		$ret[]=openGemBox($uid,$gid);
	}
	else if ($gid == 50) //古朴木盒
	{
		$ret[] = 1; //代表显示宝物列表
		$ret[] = useOldWoodBox($uid);
	}
	else if ($gid==51) //清仓令
	{
		$endtime=useQingCangLing($uid);
		$ret[]=2;
		$ret[]=$GLOBALS['useGoods']['QingCangLing_valid_date'];
		$ret[]=intval($endtime);
	}
	else if ($gid==52) //墨家秘笈
	{
		throw new Exception($GLOBALS['useGoods']['MoJiaMiJi']);
	}
	else if ($gid==54||$gid==55) //税吏鞭
	{
		$endtime = useShuiLiBian($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['ShuiLiBian_valid_date'];
		$ret[] = intval($endtime);
	}
	else if ($gid==56) //徭役令
	{
		$endtime = useYaoYiLin($uid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['YaoYiLin_valid_date'];
		$ret[] = intval($endtime);
	}

	else if ($gid==133) //军令状
	{
		$endtime = useJunLingZhuang($uid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['Junlingzhuang_valid_date'];
		$ret[] = intval($endtime);
	}

	else if ($gid==117) //高级推恩令
	{
		$tuienling=useGaojiTuiEnLing($uid,$gid);
		$endtime = $tuienling[1];
		$ret[] = 4; //代表弹出剩余时间提示框
		$ret[] = intval($endtime);
		$ret[] = $tuienling[0];
	}
	else if ($gid==124) //推恩令
	{
		$tuienling=useTuiEnLing($uid,$gid);
		$endtime = $tuienling[1];
		$ret[] = 5; //代表弹出剩余时间提示框
		$ret[] = intval($endtime);
		$ret[] = $tuienling[0];
	}
	else if ($gid==119) //宝藏盒
	{
		$ret[] = 1; //代表显示宝物列表
		$ret[] = useTreasureBox($uid,$gid);
	}
	else if ($gid==120) //商队契约
	{
		$endtime = useShangDuiQiYue($uid,$gid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['ShangDuiQiYue_valid_date'];
		$ret[] = intval($endtime);
	}
	else if ($gid==57) //典民令
	{
		$msg=useDianMinLin($uid,$cid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if ($gid==139) //典民令
	{
		$msg=useTaiPingYaoShu($uid,$cid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if ($gid==142) //巡查令
	{
		$endtime=useXunChaLin($uid,$cid);
		$ret[] = 2; //代表弹出剩余时间提示框
		$ret[] = $GLOBALS['useGoods']['xuncha_valid_date'];
		$ret[] = intval($endtime);
	}	
	else if ($gid==58) //安民告示
	{
		$msg=useAnMingGaoShi($uid,$cid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if($gid==59) //军旗
	{
		throw new Exception($GLOBALS['useGoods']['JunQi_used_for_army']);
	}
	else if (($gid==60)||($gid==61)||($gid==62)) //三个考工记
	{
		$endtime=useKaoGongJi($uid,$gid);
		$name=sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");
		$ret[]=2;
		$ret[]=sprintf($GLOBALS['useGoods']['KaoGongJi_valid_date'],$name);
		$ret[]=intval($endtime);
	}
	else if($gid==63)
	{
		throw new Exception($GLOBALS['useGoods']['HanXinSanPian_used_for_army']);
	}
	else if ($gid==64)
	{
		throw new Exception($GLOBALS['useGoods']['BeiChengMen_used_for_army']);
	}
	else if($gid==85||$gid==86)	//金砖、金条
	{
		$msg=openGoldBar($uid,$cid,$gid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if($gid>=87&&$gid<=94) //辎重包、辎重箱
	{
		$msg=openResBox($uid,$cid,$gid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if ($gid==134) //赦免文书
	{
		$msg=useSheMianWenShu($uid,$cid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}else if ($gid==138) //请战书
	{
		$msg=useQingZhanShu($uid,$gid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}		
	else if($gid==95||$gid==96||$gid==97||($gid>=101&&$gid<=112)) //装备箱
	{
		$ret[]=3; //代表开出装备
		$ret[]=useArmorBox($uid,$gid);
	}
	else if($gid==145 || $gid==146){ //武器架
		$count = addArmorShelf($uid, $gid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = sprintf($GLOBALS['goods']['armor_column_add'], $count);
	}
	else if ((($gid > 10000)&&($gid <= 10012))||($gid == 10072)||($gid == 10016)||(($gid>=10018)&&($gid<=10024))||($gid==10040))
	{
		$ret[] = 1; //代表显示宝物列表
		$ret[] = useHuodongGoods($uid,$gid);
	}
	else if ($gid == 10013)
	{
		$ret[] = 0; //直接显示框
		$ret[] = useResourcePackage($uid,$gid);
	}
	else if ($gid == 10014)
	{
		$ret[] = 1; //代表显示宝物列表
		$ret[] = useLoveBean($uid);
	}
	else if ($gid == 10015)
	{
		$ret[] = 0;
		$ret[] = useLoveRain($uid);
	}
	else if ($gid == 10017) //钥匙链
	{
		$ret[] = 1;
		$ret[] = useKeyChain($uid);
	}
	else if($gid==10035)	//聚贤包
	{
		$msg=openHeroBox($uid,$cid,$gid);
		$ret[] = 0; //代表弹出信息框
		$ret[] = $msg;
	}
	else if ($gid==10036)	//圣诞袜子
	{
		$ret[]=1;
		$ret[]=openChristmasHose($uid,$cid,$gid);
	}
	else if($gid==10037)	//圣诞礼包
	{
		$ret[]=1;
		$ret[]=openChristmasPack($uid,$cid,$gid);
	}
	else if($gid==10038)	//圣诞套装8件
	{
		$ret[]=1;
		$ret[]=openChristmasCloth($uid,$cid,$gid);
	}
    else if ($gid==10039)	//饺子
    {
    	$ret[]=1;
    	$ret[]=openJiaoZi($uid,$cid,$gid);
    }
    else if($gid==10041)
    {
    	$ret[]=1;
    	$ret[]=openChiBiPack($uid,$cid,$gid);
    }
    else if($gid==10042)
    {
    	$ret[]=1;
    	$ret[]=openJianDu($uid,$cid,$gid);
    }
    else if($gid==10043)
    {
    	$ret[]=1;
    	$ret[]=openJianCe($uid,$cid,$gid);
    }
    else if ($gid==10044)
    {
    	$ret[]=1;
    	$ret[]=openMissMail($uid,$cid,$gid);
    }
    else if($gid==10045)
    {
    	$ret[]=1;
    	$ret[]=openLoverBox($uid,$cid,$gid);
    }
    else if($gid==10046)
    {
    	$ret[]=1;
    	$ret[]=openMissPack($uid,$cid,$gid);
    }
    else if($gid==10047)
    {
    	$ret[]=1;
    	$ret[]=openNiuSheng($uid,$cid,$gid);
    }
    else if ($gid==10048||$gid==10049)
    {
    	$msg=openBaiYueHero($uid,$cid,$gid);
    	$ret[]=0;
    	$ret[]=$msg;
    }
    else if($gid==10050||$gid==10051)
    {
    	$ret[]=1;
    	$ret[]=openBaiYueArmorBox($uid,$cid,$gid);
    }
    else if($gid==10052)
    {
    	$ret[]=1;
    	$ret[]=openPayGiftBox($uid,$cid,$gid);
    }
    else if($gid==10053)
    {
    	$ret[]=1;
    	$ret[]=openJinNiuBox($uid,$cid,$gid);
    }
    else if($gid==10054)
    {
    	$ret[]=1;
    	$ret[]=openShengXiaoYuPai($uid,$cid,$gid);
    }
    else if($gid==10055||$gid==10056)
    {
    	$ret[]=1;
    	$ret[]=openShengXiaoHe($uid,$cid,$gid);
    }
    else if($gid==121||$gid==122)
    {
		$msg=useXiuJiaFu($uid,$cid,$gid);
		$ret[] = 0; //代表弹出信息框
        $ret[] = $msg;
    }
    else if($gid>=50101 && $gid<=50110){
    	$need_level = $gid-50100;    	
		$government_level = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_GOVERMENT);
		if ($government_level < $need_level)  throw new Exception(sprintf($GLOBALS['useGiftGoods']['govenment_lessThen_needlevel'],$need_level,$need_level));		
		$ret[]=1;
		$ret[]= openDynamicBox($uid,$gid);
    }
	else if ($gid>=50000)
	{
		$ret[]=1;
		$ret[]=openDynamicBox($uid,$gid);
	}else{ //默认打开道具，一般为活动道具
		$ret[]=1;
		$ret[]=openDefaultBox($uid,$cid,$gid,0);
	}
	/*else
	{
		throw new Exception($GLOBALS['useGoods']['func_not_in_use']);
	}*/
	return $ret;
}

function sellGoods($uid,$param)
{
	$cid=array_shift($param);
	$gid=array_shift($param);
	$goodsCount=array_shift($param);

	$level=sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_MARKET);
	if($level<5)
	{
		throw new Exception($GLOBALS['sellGoods']['building_level']);
	}
	$nobility=sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	//推恩 
	$nobility=getBufferNobility($uid,$nobility);
	if($nobility<1)
	{
		throw new Exception($GLOBALS['sellGoods']['nobility_low']);
	}

	$goldAdd=intval(sql_fetch_one_cell("select value from cfg_goods where gid='$gid'"));
	$goldAdd=$goldAdd*500*$goodsCount;
	$myCount=intval(sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'"));
	if($goodsCount<=0||$myCount<$goodsCount)
	{
		throw new Exception($GLOBALS['sellGoods']['not_enough_goods']);
	}

	sql_query("update mem_city_resource set `gold`=`gold`+$goldAdd where cid='$cid'");
	reduceGoods($uid,$gid,$goodsCount,9);
	$ret=array();
	$ret[]=sql_fetch_one("select * from sys_goods g left join cfg_goods f on f.gid=g.gid where g.uid='$uid' and g.gid='$gid'");
	$ret[]=$cid;
	$ret[]=sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	return $ret;
}

function getUserTypeGoods($uid,$param)
{
	$type=array_shift($param);
	if($type==0) return getLuBanGoods($uid,$param);
	else if($type==1){ return getMojiaGoods($uid,$param);}
	else
	{
		$ret=array();
		$gids="";
		switch($type)
		{
			case 2://体力药品
				$gids="74,75,76,77"; break;
			case 3://精力药品
				$gids="78,79,80,81"; break;
			case 4://民心
				$gids="58";	break;
			case 5://黄金
				$gids="54,55"; break;
			case 6://人口
				$gids="57";	break;
			case 7://粮食
				$gids="2,44"; break;
			case 8://木材
				$gids="3,45"; break;
			case 9://石料
				$gids="4,46"; break;
			case 10://铁锭
				$gids="5,47"; break;
			case 11://武将加经验
				$gids="113,114,115";break;
			case 12://12,陷阵战鼓
				$gids="6,48"; break;
			case 13://13八卦阵图
				$gids="7,49"; break;
			case 14://14,青曩书
				$gids="25";break;
			case 15://15清仓令
				$gids="51";break;
			case 16://16徭役令
				$gids="56";break;
			case 17://17考工记1
				$gids="60";break;
			case 18://18考工记2
				$gids="61";break;
			case 19://19考工记3
				$gids="62";break;
			case 20:// 20 高级推恩令
				$gids="117";break;
			case 21:// 20 商队契约
				$gids="120";break;
			case 22:// 20 推恩令
				$gids="124";break;
			case 23:// 23 军令状
				$gids="133";break;
			case 24: //打孔器
				$gids="206,207"; break;
			case 25: //高级打孔器
				$gids="207"; break;
			case 26:
				$gids="201"; break;
			
			case 30://武器架
				$gids="145, 146"; break;
			case 142://巡查令
				$gids="142"; break;
			//活动道具
			case 10083:
			case 10084:
			case 10085:
				$gids=$type;break;
		}
		$ret[]= sql_fetch_rows("select c.*,g.count from cfg_goods c left join sys_goods g on g.gid=c.gid and g.uid='$uid' where c.gid in ($gids) order by c.value");
		return $ret;
	}
}

function useTypeGoods($uid,$param)
{
	$type=array_shift($param);
	$gid=array_shift($param);
	$cid=array_shift($param);
	switch($type)
	{
		case 0: return useLuBanGoods($uid,$cid,$gid,$param); break;
		case 1: return useMojiaGoods($uid,$cid,$gid,$param); break;
		case 2: return useForceGoods($uid,$cid,$gid,$param); break;
		case 3: return useEnergyGoods($uid,$cid,$gid,$param); break;
		case 4:
		case 5:
		case 6:
		case 7:
		case 8:
		case 9:
		case 10:
		case 142:	
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 11:
			return useAddHeroExpBook($uid,$cid,$gid,$param); break;
		case 12://12,陷阵战鼓
		case 13://13八卦阵图
		case 14://14,青曩书
		case 15://15清仓令
		case 16://16徭役令
		case 17://17考工记1
		case 18://18考工记2
		case 19://19考工记3
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 20://20 高级推恩令
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 21://21 商队契约
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 22://21 推恩令
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 23://23 军令状
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;
		case 24: //打孔器
		case 25:
		case 26: //腐蚀
			$param2=array();
			$param2[] = array_shift($param); //armor sid
			$param2[]=$gid;
			$param2[]= array_shift($param); //position
			return openHole($uid, $param2);
			break;
		case 30: //武器架
			$count = addArmorShelf($uid, $gid);
			$ret =array();
			$ret[] = $count;
			return $ret;
			break;	
		//活动道具
		case 10083:
		case 10084:
		case 10085:
			$param2=array();
			$param2[]=$gid;
			return useGoods($uid,$param2);
			break;	
	}
}

function getMojiaGoods($uid,$param)
{
	$timeleft=intval(array_shift($param));
	if($timeleft<0) $timeleft=0;
	$ret=array();
	$ret[]=floor(($timeleft*5/3600)+30);
	$goodsList = sql_fetch_rows("select c.*,g.count from cfg_goods c left join sys_goods g on g.gid=c.gid and g.uid='$uid' where c.gid in (8,9,10,52,53,65,66) order by c.value");
	$finalList=array();
	foreach($goodsList as $goods)
	{
		if (empty($goods['count']))
		{
			//if($goods['gid']!=10)
			{
				$goods['count'] = 0;
				if($goods['gid']==53)
				{
					$goods['count']=$GLOBALS['getMoJiaGoods']['complete_quickly'];
					array_unshift($finalList,$goods);
				}
				else $finalList[]=$goods;
			}
		}
		else
		{
			if($goods['gid']==53) array_unshift($finalList,$goods);
			else $finalList[]=$goods;
		}
	}
	$ret[]=$finalList;
	return $ret;
}
function useMojiaGoods($uid,$cid,$gid,$param)
{
	$cid = array_shift($param);
	$inner = array_shift($param);
	$x = array_shift($param);
	$y = array_shift($param);
	$bid = array_shift($param);
	$useType = array_shift($param);//０：建筑，１：科技

	if (!(($gid>=8&&$gid<=10)||$gid==53||$gid==52||$gid==65||$gid==66)) throw new Exception($GLOBALS['useMojiaGoods']['invalid_param']);

	if($gid==53) //墨家弟子，直接完成，扣除元宝
	{
		$cost=0;
			
		$technic = sql_fetch_one("select *,unix_timestamp() as nowtime from sys_technic where uid='$uid' and cid='$cid' and state=1");
		if (empty($technic)) throw new Exception($GLOBALS['useMojiaGoods']['no_need_to_use']);
		$timeleft=$technic['state_endtime']-$technic['nowtime'];
		if($timeleft<=1) throw new Exception($GLOBALS['useMojiaGoods']['no_need_to_use']);
		$cost=intval(floor($timeleft*5/3600)+30);
		$money=sql_fetch_one_cell("select money from sys_user where uid='$uid'");
		if($cost>$money) throw new Exception($GLOBALS['sys']['not_enough_money']);
		sql_query("update sys_technic set state_endtime = unix_timestamp()-1 where id='$technic[id]'");
		sql_query("update mem_technic_upgrading set state_endtime=unix_timestamp()-1 where id='$technic[id]'");

		if($cost>0)
		{
			sql_query("update sys_user set money=GREATEST(money-$cost,0) where uid='$uid'");
			sql_query("insert into log_money (`uid`,`count`,`time`,`type`) values ('$uid',-$cost,unix_timestamp(),70)");
		}
	}
	else
	{

		//查找正在建造的建筑或科技
		$technic = sql_fetch_one("select * from sys_technic where uid='$uid' and cid='$cid' and state=1");
		if (empty($technic)) throw new Exception($GLOBALS['useMojiaGoods']['technique_no_need']);
			
		if (!checkGoods($uid,$gid)) throw new Exception($GLOBALS['useMojiaGoods']['no_enough_goods']);
			

		$timeadd = 900;
		if($gid==10) //墨家宝典，缩短35%
		{
			sql_query("update sys_technic set state_endtime=unix_timestamp() + FLOOR(0.65*(state_endtime-unix_timestamp())) where id='$technic[id]'");
			sql_query("update mem_technic_upgrading set state_endtime=unix_timestamp() + FLOOR(0.65*(state_endtime-unix_timestamp())) where id='$technic[id]'");
		}
		else
		{
			if($gid==8) $timeadd=900; //墨家残卷
			else if($gid==9) $timeadd=10800; //墨家图纸,3小时
			else if ($gid==52)//墨家秘笈，随机缩短6-30小时
			{
				$valrange=mt_rand(1000,2000);
				if ($valrange<1500)//50%落在12-16小时
				{
					$timeadd=mt_rand(12,16);
				}
				else if ($valrange<1800)//30%落在16-24小时
				{
					$timeadd=mt_rand(16,24);
				}
				else if ($valrange<1950)//10%落在24-28小时
				{
					$timeadd=mt_rand(24,28);
				}
				else //5%落在28-36小时
				{
					$timeadd=mt_rand(28,36);
				}
				$timeadd=$timeadd*3600;
			}
			else if ($gid==65) $timeadd=3600;	//墨家散页，减少1小时
			else if ($gid==66) $timeadd=36000;	//墨家古典，减少10小时

			sql_query("update sys_technic set state_endtime = GREATEST(unix_timestamp()-1,state_endtime - $timeadd) where id='$technic[id]'");
			sql_query("update mem_technic_upgrading set state_endtime=GREATEST(unix_timestamp()-1,state_endtime-$timeadd) where id='$technic[id]'");

			if ($gid == 8) //墨家残卷
			{
				completeTask($uid,365);
			}
		}
		reduceGoods($uid,$gid,1);
	}
	return getCollegeInfo($uid,$cid);

}

function getLuBanGoods($uid,$param)
{
	$timeleft=intval(array_shift($param));
	if($timeleft<0) $timeleft=0;
	$ret=array();
	$ret[]=floor(($timeleft*4.5/3600)+25);
	$goodsList = sql_fetch_rows("select c.*,g.count from cfg_goods c left join sys_goods g on g.gid=c.gid and g.uid='$uid' where c.gid>=67 and c.gid<=73 order by c.value");
	$finalList=array();
	foreach($goodsList as $goods)
	{
		if (empty($goods['count']))
		{
			$goods['count'] = 0;
			if($goods['gid']==73)
			{
				$goods['count']=$GLOBALS['getMoJiaGoods']['complete_quickly'];
				array_unshift($finalList,$goods);
			}
			else $finalList[]=$goods;
		}
		else
		{
			if($goods['gid']==73) array_unshift($finalList,$goods);
			else $finalList[]=$goods;
		}
	}
	$ret[]=$finalList;
	return $ret;
}

function useLuBanGoods($uid,$cid,$gid,$param)
{
	$cid = array_shift($param);
	$inner = array_shift($param);
	$x = array_shift($param);
	$y = array_shift($param);
	$bid = array_shift($param);
	$useType = array_shift($param);//０：建筑，１：科技

	if (!($gid>=67&&$gid<=73)) throw new Exception($GLOBALS['useLuBanGoods']['invalid_param']);

	if($gid==73) //鲁班传人，直接完成，扣除元宝
	{
		$cost=0;
		$xy = encodeBuildingPosition($inner,$x,$y);
		$building = sql_fetch_one("select *,unix_timestamp() as nowtime from sys_building where cid='$cid' and bid='$bid' and xy='$xy' and state <> 0");
		if (empty($building)) throw new Exception($GLOBALS['useLuBanGoods']['no_need_to_use']);
		$timeleft=$building['state_endtime']-$building['nowtime'];
		if($timeleft<=1) throw new Exception($GLOBALS['useLuBanGoods']['no_need_to_use']);
		$cost=intval(floor($timeleft*4.5/3600)+25);
		$money=sql_fetch_one_cell("select money from sys_user where uid='$uid'");
		if($cost>$money) throw new Exception($GLOBALS['sys']['not_enough_money']);
		sql_query("update sys_building set state_endtime = unix_timestamp()-1 where id='$building[id]'");
		if ($building['state'] == 1)    // upgrading
		{
			sql_query("update mem_building_upgrading set state_endtime=unix_timestamp()-1 where id='$building[id]'");
		}
		else if ($building['state'] == 2)   //destroying
		{
			sql_query("update mem_building_destroying set state_endtime=unix_timestamp()-1 where id='$building[id]'" );
		}
		if($cost>0)
		{
			sql_query("update sys_user set money=GREATEST(money-$cost,0) where uid='$uid'");
			sql_query("insert into log_money (`uid`,`count`,`time`,`type`) values ('$uid',-$cost,unix_timestamp(),71)");
		}
	}
	else
	{

		//查找正在建造的建筑或科技

		$xy = encodeBuildingPosition($inner,$x,$y);
		$building = sql_fetch_one("select * from sys_building where cid='$cid' and bid='$bid' and xy='$xy' and state <> 0");
		if (empty($building))   throw new Exception($GLOBALS['useLuBanGoods']['no_need_to_use']);
			
		if (!checkGoods($uid,$gid)) throw new Exception($GLOBALS['useLuBanGoods']['no_enough_goods']);
		if($gid==72)	//鲁班全集，缩短30%
		{
			sql_query("update sys_building set state_endtime=unix_timestamp() + FLOOR(0.7*(state_endtime-unix_timestamp())) where id='$building[id]'");
			if ($building['state'] == 1)    // upgrading
			{
				sql_query("update mem_building_upgrading set state_endtime=unix_timestamp() + FLOOR(0.7*(state_endtime-unix_timestamp())) where id='$building[id]'");
			}
			else if ($building['state'] == 2)   //destroying
			{
				sql_query("update mem_building_destroying set state_endtime=unix_timestamp() + FLOOR(0.7*(state_endtime-unix_timestamp())) where id='$building[id]'" );
			}
		}
		else
		{
			$timeadd=900;
			if($gid==67) $timeadd=900;	//鲁班残页，15分钟
			else if ($gid == 68) $timeadd = 3600;	//鲁班便笺，1小时
			else if ($gid==69)	$timeadd=9000;		//鲁班草图,2个半小时
			else if ($gid==70) $timeadd=28800;		//鲁班书册,8小时
			else if ($gid==71)//鲁班秘录随机缩短10-30小时
			{
				$valrange=mt_rand(1000,2000);
				if ($valrange<1500)//50%落在10-15小时
				{
					$timeadd=mt_rand(10,15);
				}
				else if ($valrange<1800)//30%落在15-20小时
				{
					$timeadd=mt_rand(15,20);
				}
				else if ($valrange<1950)//15%落在20-25小时
				{
					$timeadd=mt_rand(20,25);
				}
				else //5%落在25-30小时
				{
					$timeadd=mt_rand(25,30);
				}
				$timeadd=$timeadd*3600;
			}
			sql_query("update sys_building set state_endtime = GREATEST(unix_timestamp()-1,state_endtime - $timeadd) where id='$building[id]'");
			if ($building['state'] == 1)    // upgrading
			{
				sql_query("update mem_building_upgrading set state_endtime=GREATEST(unix_timestamp()-1,state_endtime-$timeadd) where id='$building[id]'");
			}
			else if ($building['state'] == 2)   //destroying
			{
				sql_query("update mem_building_destroying set state_endtime=GREATEST(unix_timestamp()-1,state_endtime-$timeadd) where id='$building[id]'" );
			}
			if ($gid == 67)  //鲁班残页
			{
				completeTask($uid,364);
			}
		}
		reduceGoods($uid,$gid,1);
	}
	return getCityBuildingInfo($uid,$cid);
}

function useForceGoods($uid,$cid,$gid,$param)
{
	$hid=array_shift($param);
	if(!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	$heroState=sql_fetch_one_cell("select state from sys_city_hero where hid='$hid'");
	//if($heroState>1)
	if(isHeroInCity($heroState)==0)
	{
		throw new Exception($GLOBALS['useGoods']['hero_state_wrong']);
	}
	$percent=0;
	if($gid==74) $percent=0.1;
	else if($gid==75) $percent=0.3;
	else if($gid==76) $percent=0.6;
	else if ($gid==77) $percent=1;
	sql_query("update mem_hero_blood set `force`=LEAST(`force`+`force_max`*$percent,`force_max`) where hid='$hid'");
	reduceGoods($uid,$gid,1);
	return getCityInfoHero($uid,$cid);
}

function useEnergyGoods($uid,$cid,$gid,$param)
{
	$hid=array_shift($param);
	if(!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	$heroState=sql_fetch_one_cell("select state from sys_city_hero where hid='$hid'");
	//if($heroState>1)
	if(isHeroInCity($heroState)==0)
	{
		throw new Exception($GLOBALS['useGoods']['hero_state_wrong']);
	}
	$percent=0;
	if($gid==78) $percent=0.1;
	else if($gid==79) $percent=0.3;
	else if($gid==80) $percent=0.6;
	else if ($gid==81) $percent=1;
	sql_query("update mem_hero_blood set `energy`=LEAST(`energy`+`energy_max`*$percent,`energy_max`) where hid='$hid'");
	reduceGoods($uid,$gid,1);
	return getCityInfoHero($uid,$cid);
}

function useAddHeroExpBook($uid,$cid,$gid,$param)
{
	if(!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	$hid=array_shift($param);
	$heroInfo=sql_fetch_one("select * from sys_city_hero where hid='$hid' and cid='$cid'");
	if(empty($heroInfo)) throw new Exception($GLOBALS['addHeroPoint']['cant_find_hero']);
	if($heroInfo['level']>=100) throw new Exception($GLOBALS['useGoods']['hero_level_full']);
	if( isHeroInCity($heroInfo['state']) == 0 )//$heroInfo['state']>1)
	{
		throw new Exception($GLOBALS['useGoods']['hero_state_wrong']);
	}
	$expadd=0;
	if($gid==113) $expadd=3000;
	else if($gid==114) $expadd=30000;
	else if($gid==115) $expadd=300000;
	sql_query("update sys_city_hero set exp=exp+$expadd where hid='$hid'");
	reduceGoods($uid,$gid,1);
	return getCityInfoHero($uid,$cid);
}

function useMenzhulin($uid,$cid)
{

	if (!checkGoods($uid,15))
	{
		throw new Exception($GLOBALS['useMenzhulin']['no_MenZhuLin']);
	}
	$user = sql_fetch_one("select * from sys_user where uid='$uid'");
	if ($user['union_id'] == 0) throw new Exception($GLOBALS['useMenzhulin']['not_join_union']);

	$union = sql_fetch_one("select * from sys_union where id='$user[union_id]'");
	if (empty($union)) throw new Exception($GLOBALS['useMenzhulin']['union_not_exist']);

	if ($union['chieforder'] != 0) throw new Exception($GLOBALS['useMenzhulin']['already_used']);
	sql_query("update sys_union set chieforder=1 where id='$union[id]'");

	reduceGoods($uid,15,1);
}
function useXiShuiDan($uid,$hid)
{
	$hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	if (empty($hero)) throw new Exception($GLOBALS['useXiShuiDan']['no_hero_info']);

	$goodsNeed = ceil($hero['level'] / 10);
	if (!checkGoodsCount($uid,22,$goodsNeed))
	{
		throw new Exception($GLOBALS['useXiShuiDan']['no_enough_XiShuiDan']);
	}

	sql_query("update sys_city_hero set affairs_add=0,bravery_add=0,wisdom_add=0 where hid='$hid'");

	reduceGoods($uid,22,$goodsNeed);
}
function useZhaoXinLin($uid,$cid)
{
	if (!checkGoods($uid,23))
	{
		throw new Exception($GLOBALS['useZhaoXinLin']['no_ZhaoXinLin']);
	}
	sql_query("update mem_city_schedule set `last_reset_recruit`=0 where `cid`='$cid'");
	completeTask($uid,89);
	reduceGoods($uid,23,1);
}

function useTaskMagic($uid,$cid)
{
    //check goods
    sql_query("update mem_city_schedule set `last_reset_sys_task`=0 where cid=$cid");
}


function UseMianZhanPai($uid)
{
	//$userCityCount = sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type=0");
	if (!checkGoodsCount($uid,12,1))
	{
		throw new Exception($GLOBALS['UseMianZhanPai']['no_MianZhanPai']);
	}
	sql_query("update sys_user set state=2 where uid='$uid'");
	$usetime = 12 * 3600;   //12小时免战
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','7',unix_timestamp()+$usetime) on duplicate key update endtime=unix_timestamp() + $usetime");
	reduceGoods($uid,12,1);
}

function useShenNongChu($uid,$gid)//神农锄    粮食产量增加25%，持续24小时。
{
	$delay = 86400;
	$goodsname = $GLOBALS['useShenNongChu']['ShenNongChu'];
	if ($gid==44)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useShenNongChu']['advanced_ShenNongChu'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useShenNongChu']['no_ShenNongChu'],$goodsname);
		throw new Exception($msg);
	}
	sql_query("update sys_city_res_add a,sys_city c set a.goods_food_add=25,a.resource_changing=1 where c.uid='$uid' and a.cid=c.cid");
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','1',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=1");
}
function useLuBanFu($uid,$gid)//鲁班斧    木材产量增加25%，持续24小时。     
{
	$delay = 86400;
	$goodsname = $GLOBALS['useLuBanFu']['LuBanFu'];
	if ($gid==45)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useLuBanFu']['advanced_LuBanFu'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useLuBanFu']['no_LuBanFu'],$goodsname);
		throw new Exception($msg);
	}
	sql_query("update sys_city_res_add a,sys_city c set a.goods_wood_add=25,a.resource_changing=1 where c.uid='$uid' and a.cid=c.cid");
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','2',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=2");
}
function useKaiShanCui($uid,$gid)//开山锤    石料产量增加25%，持续24小时。  
{
	$delay = 86400;
	$goodsname = $GLOBALS['useKaiShanCui']['KaiShanCui'];
	if ($gid==46)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useKaiShanCui']['advanced_KaiShanCui'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useKaiShanCui']['no_KaiShanCui'],$goodsname);
		throw new Exception($msg);
	}
	sql_query("update sys_city_res_add a,sys_city c set a.goods_rock_add=25,a.resource_changing=1 where c.uid='$uid' and a.cid=c.cid");
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','3',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=3");

}
function useXuanTieLu($uid,$gid)//玄铁炉    铁锭产量增加25%，持续24小时。  
{
	$delay = 86400;
	$goodsname = $GLOBALS['useXuanTieLu']['XuanTieLu'];
	if ($gid==47)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useXuanTieLu']['advanced_XuanTieLu'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useXuanTieLu']['no_XuanTieLu'],$goodsname);
		throw new Exception($msg);
	}
	sql_query("update sys_city_res_add a,sys_city c set a.goods_iron_add=25,a.resource_changing=1 where c.uid='$uid' and a.cid=c.cid");
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','4',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=4");
}
function useXianZhenZhaoGu($uid,$gid)//陷阵战鼓军队攻击增加10%，持续24小时。
{
	$delay = 86400;
	$oldtype=10084;$buftype=5;
	$goodsname = $GLOBALS['useXianZhenZhaoGu']['XianZhenZhaoGu'];
	if ($gid==48)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useXianZhenZhaoGu']['advanced_XianZhenZhaoGu'];
	}
	if ($gid==10084)
	{
		$oldtype=5;$buftype=10084;
		$delay = 86400 / 24;
		$goodsname = $GLOBALS['useXianZhenZhaoGu']['qiang_XianZhenZhaoGu'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useXianZhenZhaoGu']['no_XianZhenZhaoGu'],$goodsname);
		throw new Exception($msg);
	}
	if(1==(sql_fetch_one_cell("select count(1) from mem_user_buffer where uid='$uid' and buftype='$oldtype'"))){//不可同时使用
		throw new Exception($GLOBALS['useXianZhenZhaoGu']['nouse_XianZhenZhaoGu']);
	}
	
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','$buftype',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype='$buftype'");
}
function useBaGuaZhenTu($uid,$gid)//八卦阵图军队防御增加10%，持续24小时。
{
	$delay = 86400;
	$oldtype=10085;$buftype=6;
	$goodsname = $GLOBALS['useBaGuaZhenTu']['BaGuaZhenTu'];
	if ($gid==49)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useBaGuaZhenTu']['advanced_BaGuaZhenTu'];
	}
	if ($gid==10085)
	{
		$oldtype=6;$buftype=10085;
		$delay = 86400 / 24;
		$goodsname = $GLOBALS['useBaGuaZhenTu']['qiang_BaGuaZhenTu'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useBaGuaZhenTu']['no_BaGuaZhenTu'],$goodsname);
		throw new Exception($msg);
	}
	$tType=sql_fetch_one_cell("select count(1) from mem_user_buffer where uid='$uid' and buftype='$oldtype'");
	
	if(1==(sql_fetch_one_cell("select count(1) from mem_user_buffer where uid='$uid' and buftype='$oldtype'"))){//不可同时使用
		throw new Exception($GLOBALS['useBaGuaZhenTu']['nouse_BaGuaZhenTu']);
	}
	
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','$buftype',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype='$buftype'");
}
function useQingNangShu($uid,$gid)   //青囊书   可以恢复的伤兵人数增加30%，50%，效果持续24小时
{
	if (!checkGoods($uid,$gid))
	{
		if($gid=25)
			throw new Exception($GLOBALS['useQingNangShu']['no_QingNangShu']);
		if($gid=10083)		//05.25端午节活动道 具
			throw new Exception($GLOBALS['useQingNangShu']['no_ShangJiQingNangShu']);
	}
	if($gid==25){
		$oldtype=10083;$buftype=9;$time=86400;
	}
	if($gid==10083){
		$oldtype=9;$buftype=10083;$time=3600;
	}
	if(1==(sql_fetch_one_cell("select count(1) from mem_user_buffer where uid='$uid' and buftype='$oldtype'"))){//不可同时使用
		throw new Exception($GLOBALS['useQingNangShu']['nouse_QingNangShu']);
	}
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','$buftype',unix_timestamp()+$time) on duplicate key update endtime=endtime + $time");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype='$buftype'");
}
//打开只掉落一个道具的箱子，比如古朴木盒
function openSimpleGoodsBox($uid,$dropRate,$type)
{
	$allrate = 0;
	foreach($dropRate as $goodsRate)
	{
		$allrate += $goodsRate['rate'];
	}
	$rnd = mt_rand() % $allrate;
	$ratesum = 0;

	$ret = array();
	$cnt = 1;
	for($i=0;$i<count($dropRate);$i++)
	{
		$goods=$dropRate[$i];
		$ratesum += $goods['rate'];

		if ($rnd < $ratesum)
		{
			$gid = $goods['gid'];
			addGoods($uid,$gid,$cnt,3);
			$oneGood= sql_fetch_one("select *,$cnt as count,value from cfg_goods where gid='$gid'");
			$ret[] =$oneGood;

			//如果大于50 发送公
			if(isSentGood($oneGood['gid'])){
				$sendNames=array();
				$sendNames[]="“".$oneGood["name"]."”".$cnt;
				sendOpenBoxInform($sendNames,$oneGood['value']*$cnt,$uid,$type);
			}
			break;
		}
	}
	return $ret;
}

function useQingCangLing($uid)
{
	if (!checkGoods($uid,51))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}

	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','10',unix_timestamp()+604800) on duplicate key update endtime=endtime + 604800");
	reduceGoods($uid,51,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=10");
}

function useShuiLiBian($uid,$gid)//税吏鞭   黄金产量增加25%，持续24(7*24)小时。
{
	$delay = 86400;
	$goodsname = $GLOBALS['useShuiLiBian']['ShuiLiBian'];
	if ($gid==55)
	{
		$delay = 86400 * 7;
		$goodsname = $GLOBALS['useShuiLiBian']['advanced_ShuiLiBian'];
	}
	if (!checkGoods($uid,$gid))
	{
		$msg = sprintf($GLOBALS['useShuiLiBian']['no_ShuiLiBian'],$goodsname);
		throw new Exception($msg);
	}
	sql_query("update mem_city_resource m, sys_city c set m.gold_rate=125 where c.uid='$uid' and m.cid=c.cid");
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','15',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=15");
}

function useGaojiTuiEnLing($uid,$gid){
	if (!checkGoods($uid,117))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}


	$nobility =  sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	if($nobility<5){
		throw new Exception($GLOBALS['gaojituienling']['dafuyishang']);
	}
	$inuse = sql_fetch_one_cell("select endtime-unix_timestamp() from mem_user_buffer where uid='$uid' and buftype=16 ");
	if(!empty($inuse)){
		//正在使用 则时间延长;
		sql_query("update mem_user_buffer set endtime=endtime + 864000 where uid='$uid' and buftype=16 ");
		reduceGoods($uid,117,1);
		$ret=array();

		$nobility= getBufferNobility($uid,$nobility);
		$ret[]=$nobility;
		$ret[]=sql_fetch_one_cell("select endtime-unix_timestamp() from  mem_user_buffer where uid='$uid' and buftype=16");
		return $ret;
	}

	if($nobility>19){
		throw new Exception($GLOBALS['userTuiEnling']['guanneihou']);
	}

	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid','16',5,unix_timestamp()+864000) on duplicate key update endtime=endtime + 864000");
	reduceGoods($uid,117,1);
	$nobility= getBufferNobility($uid,$nobility);
	$ret[]=$nobility;
	$ret[]=sql_fetch_one_cell("select endtime-unix_timestamp() from  mem_user_buffer where uid='$uid' and buftype=16");
	return $ret;
}

function useTuiEnLing($uid,$gid){
	if (!checkGoods($uid,124))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}


	$nobility =  sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	$inuse = sql_fetch_one_cell("select endtime-unix_timestamp() from mem_user_buffer where uid='$uid' and buftype=18 ");
	if(!empty($inuse)){
		//正在使用 则时间延长;
		sql_query("update mem_user_buffer set endtime=endtime + 86400*3 where uid='$uid' and buftype=18 ");
		reduceGoods($uid,124,1);
		$ret=array();

		$nobility= getBufferNobility($uid,$nobility);
		$ret[]=$nobility;
		$ret[]=sql_fetch_one_cell("select endtime-unix_timestamp() from  mem_user_buffer where uid='$uid' and buftype=18");
		return $ret;
	}

	if($nobility>19){
		throw new Exception($GLOBALS['userTuiEnling']['guanneihou']);
	}

	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid','18',2,unix_timestamp()+86400*3) on duplicate key update endtime=endtime + 86400*3");
	reduceGoods($uid,124,1);
	$nobility= getBufferNobility($uid,$nobility);
	$ret[]=$nobility;
	$ret[]=sql_fetch_one_cell("select endtime-unix_timestamp() from  mem_user_buffer where uid='$uid' and buftype=18");
	return $ret;
}
function useSheMianWenShu($uid,$gid)
{
	if (!checkGoods($uid,134))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}	
	
	
	$result=sql_query("update sys_user set honour=0 where uid='$uid'");
	if($result){
		reduceGoods($uid,134,1);
		return $GLOBALS['useGoods']['shemian_suc'];
	}else{
		unlockUser($uid);
		return $GLOBALS['useGoods']['shemian_fail'];
	}
	
	
}

function useQingZhanShu($uid,$gid)
{
	if (!checkGoods($uid,138))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}	
	$today_war_count = sql_fetch_one_cell("select today_war_count from mem_user_schedule where uid = $uid");
	if (empty($today_war_count))$today_war_count= 0 ;
	if ($today_war_count==0)  throw new Exception($GLOBALS['useGoods']['today_war_count_zero']);
	$result=sql_query("update mem_user_schedule set today_war_count=0 where uid='$uid'");
	if($result){
		reduceGoods($uid,138,1);
		return $GLOBALS['useGoods']['qingzhan_suc'];
	}else{
		unlockUser($uid);
		throw new Exception($GLOBALS['useGoods']['qingzhan_fail']);
	}	
}
function useShangDuiQiYue($uid,$gid){
	if (!checkGoods($uid,120))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}

	$inuse = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=17 ");
	if(!empty($inuse)){
		//正在使用 则时间延长;
		sql_query("update mem_user_buffer set endtime=endtime + 259200 where uid='$uid' and buftype=17 ");
		reduceGoods($uid,120,1);
		return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=17");
	}

	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid','17',0,unix_timestamp()+259200) on duplicate key update endtime=endtime + 259200");
	reduceGoods($uid,120,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=17");
}

function useYaoYiLin($uid)
{
	if (!checkGoods($uid,56))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}

	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid','11',0,unix_timestamp()+259200) on duplicate key update endtime=endtime + 259200");
	reduceGoods($uid,56,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=11");
}

function useJunLingZhuang($uid)
{
	if (!checkGoods($uid,133))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}

	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid','19',3,unix_timestamp()+86400) on duplicate key update endtime=endtime + 86400");
	reduceGoods($uid,133,1);
	return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=19");
}


function useDianMinLin($uid,$cid)
{
	if(!checkGoods($uid,57)) throw new Exception($GLOBALS['addPeople']['no_goods'] );
	$owner=sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
	if(empty($owner)||$owner!=$uid) throw new Exception($GLOBALS['addPeople']['not_your_city']);
	$cityinfo=sql_fetch_one("select people,people_max from mem_city_resource where cid='$cid'");
	$people_max=$cityinfo['people_max'];
	$people=$cityinfo['people'];
	if($people>=$people_max) throw new Exception($GLOBALS['addPeople']['city_full']);
	$add=ceil($people_max*0.2);
	if($add<100) $add=100;
	sql_query("update mem_city_resource set people=people+$add where cid='$cid'");
	reduceGoods($uid,57,1);
	updateCityResourceAdd($cid);
	return (sprintf($GLOBALS['addPeople']['succ'],$add));
}

function useTaiPingYaoShu($uid,$cid)
{
	if(!checkGoods($uid,139)) throw new Exception($GLOBALS['addPeople']['no_taiping'] );
	$owner=sql_fetch_one_cell("select uid from sys_city where cid='$cid'");
	if(empty($owner)||$owner!=$uid) throw new Exception($GLOBALS['addPeople']['not_your_city']);
	$cityinfo=sql_fetch_one("select people,people_max from mem_city_resource where cid='$cid'");
	$people_max=$cityinfo['people_max'];
	$people=$cityinfo['people'];
	if($people>=$people_max) throw new Exception($GLOBALS['addPeople']['city_full']);
	$add=ceil($people_max*0.1);
	if($add<100) $add=100;
	sql_query("update mem_city_resource set people=people+$add where cid='$cid'");
	reduceGoods($uid,139,1);
	updateCityResourceAdd($cid);
	return (sprintf($GLOBALS['addPeople']['succ'],$add));
}

//巡查令
function useXunChaLin($uid,$cid)
{
	if(!checkGoods($uid,142)) throw new Exception($GLOBALS['heroexpr']['no_XunChaLin'] );
	$delay=86400*3;	
	sql_query("insert into mem_user_buffer (uid,buftype,bufparam,endtime) values ('$uid',100,0,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay,bufparam=0");	
	reduceGoods($uid,142,1);	
	return sql_fetch_one_cell("select endtime from mem_user_buffer where uid = $uid and buftype=100");
}

function useAnMingGaoShi($uid,$cid)
{
	if (!checkGoods($uid,58))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	$anminginfo=sql_fetch_one("select last_anming,unix_timestamp() as nowtime from mem_city_schedule where cid='$cid'");
	$lasttime=$anminginfo['last_anming'];
	$nowtime=$anminginfo['nowtime'];

	if((!empty($lasttime)&&($nowtime-$lasttime<259200)))
	{
		throw new Exception(sprintf($GLOBALS['useGoods']['AnMingGaoShi_cool_down'],MakeTimeLeft(259200-($nowtime-$lasttime))));
	}
	sql_query("update mem_city_resource set morale=100, complaint=0, morale_stable=100-`tax`,`people_stable`=`people_max` where cid='$cid'");
	sql_query("insert into mem_city_schedule (cid,last_anming) values ('$cid',unix_timestamp()) on duplicate key update last_anming=unix_timestamp()");
	reduceGoods($uid,58,1);
	return $GLOBALS['useGoods']['AnMingGaoShi_succ'];

}

function useKaoGongJi($uid,$gid)
{
	if(!checkGoods($uid,$gid)) throw new Exception($GLOBALS['useGoods']['no_this_good']);
	$buftype=12+($gid-60);
	sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','$buftype',unix_timestamp()+86400) on duplicate key update endtime=endtime+86400");
	reduceGoods($uid,$gid,1);
	return sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype='$buftype'");
}
function openDynamicBox($uid,$mygid) //动态生产的礼包
{
	$record=sql_fetch_one("select * from cfg_pack_goods where gid='$mygid'");
	
	if(empty($record)) throw new Exception($GLOBALS['useGoods']['no_pack_good']);
	
	$ret=array();	
	$res = $record['res'];	
	if ($res!=""){
		$reslist=explode(',',$res);
		$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
		include_once 'TaskFunc.php';
		//黄金,粮食,木材,石料,铁锭		
		for ($i = 0; $i < 5; $i++){
			$type = $i+1;
			$cnt = $reslist[$i];
			if ($cnt==0) continue;
			giveResource($uid,$cid,$type,$cnt);
			if ($type==1) $gid = 85;
			else if ($type==2) $gid = 87;
			else if ($type==3) $gid = 88;
			else if ($type==4) $gid = 89;
			else if ($type==5) $gid = 90;						
			$ret[] = array("name"=>$GLOBALS['resPackage']['res_'.$type],"count"=>$cnt,"gid"=>$gid,"description"=>$GLOBALS['resPackage']['res_'.$type]);
		}
	}
	$goods = $record['goods'];
	
	$goodslist=explode(',',$goods);
	$goodcnt=$goodslist[0];
	$money=0;
	//if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	
	for ($i = 1; $i < $goodcnt*2; $i+=2)
	{
			$gid=$goodslist[$i];
			$cnt=$goodslist[$i+1];
			if($gid==0)
			{
				$money+=$cnt;
				$ret[] = array("name"=>$GLOBALS['sys']['LiJin'],"count"=>$cnt,"gid"=>0,"description"=>$GLOBALS['sys']['description_of_LiJin']);
	
			}
			else
			{
				addGoods($uid,$gid,$cnt,8);
				$oneGood=sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
				$ret[] = $oneGood;
	
			}
	}
	
	$armors = $record['armor'];
	if($armors != '')
	{
		$armorlist = explode(',', $armors);
		$armorcnt = $armorlist[0];
		for($i=1; $i<$armorcnt*2; $i+=2)
		{
			$aid = $armorlist[$i];
			$cnt = $armorlist[$i+1];
			
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			$armor['cnt']= $cnt;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			addArmor($uid,$armor,$cnt,3);
			$ret[]=$armor;
		}
	}
	
	if($money>0) {
		addGift($uid,$money,3);
	}
	unlockUser($uid);
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openTreasureBox($uid,$totalValue,$dropRate,$type)
{
	$dropCount = 9;
	$tryCount=50;
	$goodsGet = array();
	$ret = array();

	while(($totalValue > 0)&&($dropCount > 0)&&(count($dropRate) > 0)&&($tryCount>0))
	{

		$allrate = 0;
		foreach($dropRate as $goodsRate)
		{
			$allrate += $goodsRate['rate'];
		}

		$rnd = mt_rand() % $allrate;
		$ratesum = 0;
		$tryCount--;

		for($i=0;$i<count($dropRate);$i++)
		{
			$goods=$dropRate[$i];
			$ratesum += $goods['rate'];
			if ($rnd < $ratesum)
			{
				$countmax = floor($totalValue / $goods['value']);
				if ($goods['rate'] <= 5)
				{
					$cnt = 1;
				}
				else
				{
					$cnt = mt_rand(1,ceil($countmax/2));
				}
				if ($cnt > 0&&$countmax>0)
				{
					$goodsGet[$goods['gid']] = $cnt;
					$totalValue -= $goods['value'] * $cnt;
					$dropCount--;
					array_splice($dropRate,$i,1);
					break;
				}
			}
		}
	}
	if ($totalValue > 0&&$type>0)
	{
		if($type==0)
		{
			if($totalValue>5) $totalValue=mt_rand(1,5);
		}
		else if($type==1)
		{
			if($totalValue>10) $totalValue=mt_rand(1,10);
		}
		else if ($type==2)
		{
			if($totalValue>25) $totalValue=mt_rand(1,25);
		}
		addGift($uid,$totalValue,20);
		$goodsGet['0']=$totalValue;
	}
	$sendGoodNames = array();
	$values=0;
	foreach($goodsGet as $gid=>$cnt)
	{
		if($gid>0)
		{
			addGoods($uid,$gid,$cnt,3);
		}
		$oneGood=sql_fetch_one("select *,$cnt as count,value from cfg_goods where gid='$gid'");
		if(isSentGood($oneGood['gid'])){
			$sendGoodNames[]="“".$oneGood["name"]."”".$cnt;
			$values+=$oneGood["value"]*$cnt;
		}
		$ret[] =  $oneGood;
	}

	if(count($sendGoodNames)>0)
	sendOpenBoxInform($sendGoodNames,$values,$uid,$type+16);
	return $ret;
}
function getValueRand($min1,$max1,$min2,$max2,$min3,$max3,$min4,$max4)
{
	$rnd = mt_rand() % 100;
	if ($rnd < 65)
	{
		return mt_rand($min1,$max1);
	}
	else if ($rnd < 80)
	{
		return mt_rand($min2,$max2);
	}
	else if ($rnd < 95)
	{
		return mt_rand($min3,$max3);
	}
	else
	{
		return mt_rand($min4,$max4);
	}
}
function openCopperBox($uid)
{
	//$totalValue = getValueRand(21,30,31,50,15,20,51,75);
	$totalValue = getValueRand(21,30,31,35,15,20,36,60);
	$dropRate = sql_fetch_rows("select gid,copperbox as rate,value from cfg_goods where inuse=1 and copperbox > 0 and value > 0 and value <= $totalValue order by rand()");
	return openTreasureBox($uid,$totalValue,$dropRate,0);
}
function openSilverBox($uid,$type=1)
{
	//$totalValue = getValueRand(101,150,151,200,90,100,201,360);
	$totalValue = getValueRand(121,180,181,210,90,120,211,360);
	$dropRate = sql_fetch_rows("select gid,silverbox as rate,value from cfg_goods where inuse=1 and silverbox > 0 and value > 0 and value <= $totalValue order by rand()");

	return openTreasureBox($uid,$totalValue,$dropRate,$type);
}
function openGoldBox($uid)
{
	//$totalValue = getValueRand(401,500,501,600,360,400,601,1050);
	$totalValue = getValueRand(321,480,481,560,240,320,561,960);
	$dropRate = sql_fetch_rows("select gid,goldbox as rate,value from cfg_goods where inuse=1 and goldbox > 0 and value > 0 and value <= $totalValue order by rand()");
	return openTreasureBox($uid,$totalValue,$dropRate,2);
}

function openTreasure($uid)
{
	$dropRate = sql_fetch_rows("select gid,treasurebox as rate,value from cfg_goods where inuse=1 and treasurebox > 0 and value > 0 order by rand()");
	return openSimpleGoodsBox($uid,$dropRate,119);
}
function useCopperBox($uid)
{
	if (!checkGoods($uid,16))
	{
		throw new Exception($GLOBALS['useCopperBox']['no_CopperBox']);
	}
	unlockUser($uid);
	if (!checkGoods($uid,19))
	{
		throw new Exception($GLOBALS['useCopperBox']['no_CopperKey']);
	}
	//打开青铜宝箱
	//1%的机率变成白银宝箱
	if ((mt_rand() % 100) == 0)
	{
		$ret = openSilverBox($uid,0);
	}
	else
	{
		$ret = openCopperBox($uid);
	}
	reduceGoods($uid,16,1);
	reduceGoods($uid,19,1);

	return $ret;
}
function useSilverBox($uid)
{
	if (!checkGoods($uid,17))
	{
		throw new Exception($GLOBALS['useSilverBox']['no_SiverBox']);
	}
	unlockUser($uid);
	if (!checkGoods($uid,20))
	{
		throw new Exception($GLOBALS['useSilverBox']['no_SiverKey']);
	}
	//打开白银宝箱
	//1%的机率变成黄金宝箱
	if ((mt_rand() % 100) == 0)
	{
		$ret = openGoldBox($uid);
	}
	else
	{
		$ret = openSilverBox($uid);
	}
	reduceGoods($uid,17,1);
	reduceGoods($uid,20,1);

	return $ret;
}
function useTreasureBox($uid)
{
	if (!checkGoods($uid,119))
	{
		throw new Exception($GLOBALS['useTreasureBox']['no_TreasureBox']);
	}
	unlockUser($uid);
	//打开宝藏盒    
	$ret = openTreasure($uid);
	reduceGoods($uid,119,1);

	return $ret;
}
function useGoldBox($uid)
{
	if (!checkGoods($uid,18))
	{
		throw new Exception($GLOBALS['useGoldBox']['no_GoldBox']);
	}
	unlockUser($uid);
	if (!checkGoods($uid,21))
	{
		throw new Exception($GLOBALS['useGoldBox']['no_GoldKey']);
	}
	//打开黄金宝箱      

	$ret = openGoldBox($uid);

	reduceGoods($uid,18,1);
	reduceGoods($uid,21,1);

	return $ret;
}
function openOldWoodBox($uid)
{
	$dropRate = sql_fetch_rows("select gid,woodbox as rate,value from cfg_goods where inuse=1 and woodbox > 0 and value > 0 order by rand()");
	return openSimpleGoodsBox($uid,$dropRate,50);
}
function openLoveBean($uid)
{
	$dropRate = sql_fetch_rows("select gid,lovebean as rate,value from cfg_goods where inuse=1 and lovebean > 0 and value > 0 order by rand()");
	return openSimpleGoodsBox($uid,$dropRate,10014);
}
//古朴木盒
function useOldWoodBox($uid)
{
	if (!checkGoods($uid,50))
	{
		throw new Exception($GLOBALS['useOldWoodBox']['no_OldWoodBox']);
	}
	$ret = openOldWoodBox($uid);
	reduceGoods($uid,50,1);
	return $ret;
}
//相思豆
function useLoveBean($uid)
{
	if (!checkGoods($uid,10014))
	{
		throw new Exception($GLOBALS['useLoveBean']['no_LoveBean']);
	}
	$ret = openLoveBean($uid);
	reduceGoods($uid,10014,1);
	return $ret;
}

//相思雨滴
function useLoveRain($uid)
{
	$mygid = 10015;
	if (!checkGoods($uid,$mygid))
	{
		throw new Exception($GLOBALS['useLoveRain']['no_LoveRain']);
	}
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");

	$rnd = mt_rand(0,150);
	if ($rnd < 50)
	{
		$msg = $GLOBALS['useLoveRain']['gain_food'];
		addCityResources($cid,0,0,0,10000,0);
	}
	else if ($rnd < 100)
	{
		$msg = $GLOBALS['useLoveRain']['gain_wood'];
		addCityResources($cid,10000,0,0,0,0);
	}
	else if ($rnd < 125)
	{
		$msg = $GLOBALS['useLoveRain']['gain_rock'];
		addCityResources($cid,0,10000,0,0,0);
	}
	else if ($rnd < 145)
	{
		$msg = $GLOBALS['useLoveRain']['gain_iron'];
		addCityResources($cid,0,0,10000,0,0);
	}
	else
	{
		$msg = $GLOBALS['useLoveRain']['gain_gold'];
		addCityResources($cid,0,0,0,0,10000);
	}

	reduceGoods($uid,$mygid,1);
	return $msg;
}
function useKeyChain($uid)
{
	$mygid = 10017;
	if (!checkGoods($uid,$mygid))
	{
		throw new Exception($GLOBALS['useKeyChain']['no_KeyChain']);
	}
	$dropCount = 10;
	$goodsGet = array();
	$ret = array();

	while($dropCount > 0)
	{

		$dropCount--;
		$rnd = mt_rand() % 1000;
		if ($rnd < 5)
		{
			if (isset($goodsGet[21]))
			{
				$goodsGet[21] += 1;
			}
			else
			{
				$goodsGet[21] = 1;
			}
		}
		else if ($rnd < 55)
		{
			if (isset($goodsGet[20]))
			{
				$goodsGet[20] += 1;
			}
			else
			{
				$goodsGet[20] = 1;
			}
		}
		else
		{
			if (isset($goodsGet[19]))
			{
				$goodsGet[19] += 1;
			}
			else
			{
				$goodsGet[19] = 1;
			}
		}
	}
	foreach($goodsGet as $gid=>$cnt)
	{
		addGoods($uid,$gid,$cnt,3);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}

	reduceGoods($uid,$mygid,1);
	return $ret;
}
function openGemBox($uid,$mygid)
{
	if($mygid==41)
	{
		$gems=array(30=>9,31=>8,32=>7);
		$totalValue=80;
	}
	else if ($mygid==42)
	{
		$gems=array(33=>12,34=>10,35=>8);
		$totalValue=140;
	}
	else if ($mygid==43)
	{
		$gems=array(36=>15,37=>10,38=>5);
		$totalValue=240;
	}

	$dropRate=array();
	foreach($gems as $gid=>$rate)
	{
		$goodsRate=array();
		$goodsRate['gid']=$gid;
		$goodsRate['value']=sql_fetch_one_cell("select value from cfg_goods where gid='$gid'");
		$goodsRate['rate']=$rate;
		$dropRate[]=$goodsRate;
		$allrate+=$rate;
	}

	$dropCount = 3;
	$tryCount=20;
	$goodsGet = array();
	$ret = array();

	while(($totalValue > 0)&&($dropCount > 0)&&(count($dropRate) > 0)&&($tryCount>0))
	{
		$allrate=0;
		foreach($dropRate as $goodsRate)
		{
			$allrate += $goodsRate['rate'];
		}
		$rnd = mt_rand() % $allrate;
		$ratesum = 0;
		$tryCount--;
		for($i=0;$i<count($dropRate);$i++)
		{
			$goods=$dropRate[$i];
			$ratesum += $goods['rate'];
			if ($rnd < $ratesum)
			{
				$countmax = floor($totalValue / $goods['value']);
				if ($goods['rate'] <= 5)
				{
					$cnt = 1;
				}
				else
				{
					$cnt = mt_rand(1,$countmax);
				}
				if ($cnt > 0&&$countmax>0)
				{
					$goodsGet[$goods['gid']] = $cnt;
					$totalValue -= $goods['value'] * $cnt;
					$dropCount--;
					array_splice($dropRate,$i,1);
					break;
				}
			}
		}
	}
	foreach($goodsGet as $gid=>$cnt)
	{
		addGoods($uid,$gid,$cnt,3);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}

	reduceGoods($uid,$mygid,1);

	return $ret;
}

function useFlagChar($uid,$newchar)
{
	if (!checkGoods($uid,39))
	{
		throw new Exception($GLOBALS['useFlagChar']['no_FlagChar']);
	}

	$charlen = mb_strlen($newchar);
	if ($charlen == 0)
	{
		throw new Exception($GLOBALS['useFlagChar']['type_flag_name']);
	}
	else if ($charlen > 1)
	{
		throw new Exception($GLOBALS['useFlagChar']['only_one_char']);
	}

	sql_query("update sys_user set flagchar='$newchar' where uid='$uid'");

	completeTask($uid,371);
	reduceGoods($uid,39,1);
}

function useMingTie($uid,$username)
{
	if (!checkGoods($uid,84))
	{
		throw new Exception($GLOBALS['useMingTie']['no_goods']);
	}
	sql_query("update sys_user set name='$username' where uid='$uid'");
	reduceGoods($uid,84,1);
}

function useFireBarrel($uid,$building,$cid,$xy)
{
	if (!checkGoods($uid,83))
	{
		throw new Exception($GLOBALS['useFireBarrel']['no_goods']);
	}
	$bid=$building['bid'];
	$real_time_need=0;
	sql_query("update sys_building set `state`='2',`state_starttime`=unix_timestamp(),
	`state_endtime`=unix_timestamp()+'$real_time_need'
	where `cid`='$cid' and `xy`='$xy'");
	$dstlevel = 0;	//将降级后的级别填入，结束时直接用这个级别计算
	sql_query("insert into mem_building_destroying (id,cid,xy,bid,level,state_endtime) values ('$building[id]','$cid','$xy','$bid','$dstlevel',unix_timestamp()+'$real_time_need')
	on duplicate key update `state_endtime`=unix_timestamp()+'$real_time_need'");
	
	if($building['bid']==20){
		sql_query("delete from sys_city_defence where cid=$cid "); //20表示城墙
		sql_query("delete from mem_city_reinforce where cid=$cid ");
	}

	//sql_query("delete from mem_building_upgrading where cid='$cid' and xy='$xy'");
	//sql_query("delete from mem_building_destroying where cid='$cid' and xy='$xy'");
	//sql_query("delete from sys_building where cid='$cid' and xy='$xy'");
	reduceGoods($uid,83,1);
}

function openGoldBar($uid,$cid,$gid)
{
	if (!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	if($gid==85) $goldAdd=100000;
	else $goldAdd=1000000;
	addCityResources($cid,0,0,0,0,$goldAdd);

	reduceGoods($uid,$gid,1);
	return sprintf($GLOBALS['resPackage']['gain_gold'],$goldAdd);
}

function openResBox($uid,$cid,$gid)
{
	if (!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	switch($gid)
	{
		case 87: $resAdd=100000; addCityResources($cid,0,0,0,$resAdd,0); $msg=sprintf($GLOBALS['resPackage']['gain_food'],$resAdd); break;
		case 88: $resAdd=100000; addCityResources($cid,$resAdd,0,0,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_wood'],$resAdd);break;
		case 89: $resAdd=100000; addCityResources($cid,0,$resAdd,0,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_rock'],$resAdd); break;
		case 90: $resAdd=100000; addCityResources($cid,0,0,$resAdd,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_iron'],$resAdd); break;
		case 91: $resAdd=1000000; addCityResources($cid,0,0,0,$resAdd,0); $msg=sprintf($GLOBALS['resPackage']['gain_food'],$resAdd);break;
		case 92: $resAdd=1000000; addCityResources($cid,$resAdd,0,0,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_wood'],$resAdd);break;
		case 93: $resAdd=1000000; addCityResources($cid,0,$resAdd,0,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_rock'],$resAdd);break;
		case 94: $resAdd=1000000; addCityResources($cid,0,0,$resAdd,0,0); $msg=sprintf($GLOBALS['resPackage']['gain_iron'],$resAdd);break;
	}
	reduceGoods($uid,$gid,1);
	return $msg;
}

//装备包
function useArmorBox($uid,$gid)
{
	if (!checkGoods($uid,$gid))
	{
		throw new Exception($GLOBALS['useGoods']['no_this_good']);
	}
	$armor_column = sql_fetch_one_cell("select `armor_column` from sys_user where uid=$uid");
	$curCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and hid=0");
	if($curCount>=$armor_column)
	{
		throw new Exception($GLOBALS['useGoods']['armor_box_full']);
	}

	$levelrate=mt_rand(1,100);

	$levelcondition="";
	if($levelrate<80){
		//80%概率落到最高将领5级以内的
		$maxlevel= sql_fetch_one_cell("select max(level)  from sys_city_hero where uid='$uid'");
		if(empty($maxlevel)){
			$maxlevel=1;
		}
		$maxlevel+=5;
		$levelcondition=" and hero_level<'$maxlevel' ";
	}

	if($gid<101)
	{
		$rate=mt_rand(1,100);
		$type=$gid-95+1;
		if($gid==95)
		{
			if($rate<=90) $type=1;
			else if($rate<=99) $type=2;
			else $type=3;
		}
		else if($gid==96)
		{
			if($rate<=90) $type=2;
			else $type=3;
		}
		$armor=sql_fetch_one("select * from cfg_armor where type='$type' and inuse=1 and box_drop=1 $levelcondition order by rand() limit 1");
		if(empty($armor)){
			$armor=sql_fetch_one("select * from cfg_armor where type='$type' and inuse=1 and box_drop=1  order by rand() limit 1");
		}
	}
	else
	{
		$part=$gid-100;
		$randvalue=mt_rand(0,100);
		if($randvalue<=75) $type=1;
		else if($randvalue<=95) $type=2;
		else $type=3;
		$armor=sql_fetch_one("select * from cfg_armor where part='$part' and type='$type' and inuse=1 and box_drop=1 $levelcondition order by rand() limit 1");
		if(empty($armor)){
			$armor=sql_fetch_one("select * from cfg_armor where part='$part' and type='$type' and inuse=1 and box_drop=1  order by rand() limit 1");
		}
	}
	if(empty($armor))
	{
		throw new Exception($GLOBALS['useGoods']['invalid_data']);
	}
	if(isSentGood($armor['gid'])){
		$sendNames=array();
		$sendNames[]="“".$armor["name"]."”".$cnt;
		sendOpenBoxInform($sendNames,$armor['value']*$cnt,$uid,$gid);
	}
	addArmor($uid,$armor,1,3);
	$armor['hp']=$armor['ori_hp_max'];
	$armor['hp_max']=$armor['ori_hp_max'];
	reduceGoods($uid,$gid,1);
	$ret=array();
	$armor['gtype']=1;
	$ret[]=$armor;
	return $ret;
}

//资源礼包
function useResourcePackage($uid,$mygid)
{
	if (!checkGoods($uid,$mygid))
	{
		$name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");

		$msg = sprintf($GLOBALS['useResourcePackage']['no_ResourcePackage'],$name);
		throw new Exception($msg);
	}
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	addCityResources($cid,100000,100000,100000,100000,10000);

	reduceGoods($uid,$mygid,1);
	return $GLOBALS['useResourcePackage']['gain_resource'];
}
//GID>10000的活动宝物
function useHuodongGoods($uid,$mygid)
{
	if (!checkGoods($uid,$mygid))
	{
		$name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
		$msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
		throw new Exception($msg);
	}
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	$government_level = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=".ID_BUILDING_GOVERMENT);

	$ret = array();
	if ($mygid == 10001)  //新手礼包
	{
		return openDynamicBox($uid,$mygid);
	}
	else if ($mygid == 10002) //升级礼包，需要官府３
	{
		if ($government_level < 3)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_three']);
		return openDynamicBox($uid,$mygid);
	}
	/*else if ($mygid == 10003) //白银礼包I，需要官府２   
	 {
	 if ($government_level < 2)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_two']);
	 $goodslist = array(2=>1,3=>1,4=>1,5=>1,6=>1,7=>1,8=>3,9=>1,12=>1,13=>5,22=>2,23=>5,24=>1);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 }
	 else if ($mygid == 10004) //白银礼包II，需要官府４   
	 {
	 if ($government_level < 4)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_four']);
	 $goodslist = array(1=>20,16=>4,17=>2,19=>4,20=>2);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 addMoney($uid,100,2);
	 $ret[] = array("name"=>"元宝","count"=>"100","gid"=>0);
	 }
	 else if ($mygid == 10005) //黄金礼包I，需要官府５   
	 {
	 if ($government_level < 5)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_five']);
	 $goodslist = array(1=>30,2=>5,3=>5,4=>5,5=>5,6=>5,7=>5,8=>5,9=>3,12=>2,13=>10,22=>2,23=>10,24=>2);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 }
	 else if ($mygid == 10006) //黄金礼包II，需要官府７   
	 {
	 if ($government_level < 7)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_seven']);
	 $goodslist = array(1=>50,8=>5,9=>5,10=>2,13=>20,15=>1,25=>2,26=>2,27=>2,28=>2,29=>2);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 }
	 else if ($mygid == 10007) //黄金礼包III，需要官府９   
	 {
	 if ($government_level < 9)  throw new Exception($GLOBALS['useHuodongGoods']['govenment_lessThen_nine']);
	 $goodslist = array(16=>20,17=>5,18=>2,19=>20,20=>5,21=>2);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 addMoney($uid,500,2);
	 $ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"500","gid"=>0);
	 }
	 else if ($mygid == 10008) //超值建设礼包
	 {
	 $goodslist = array(8=>20,9=>5,10=>2,11=>5);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 }
	 else if ($mygid == 10009)//超值城主礼包      
	 {
	 $goodslist = array(2=>5,3=>5,4=>5,5=>5,6=>5,7=>5,8=>50,9=>20,10=>5,11=>10,12=>2,13=>10,22=>2,23=>10,24=>1);
	 foreach($goodslist as $gid=>$cnt)
	 {
	 addGoods($uid,$gid,$cnt,6);
	 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	 }
	 }*/
	else if ($mygid == 10010) //功勋礼包
	{
		if ($government_level < 3)  throw new Exception($GLOBALS['useHuodongGoods']['lessThen_three_for_GongXun']);
		addGift($uid,50,2);
		$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"50","gid"=>0);
	}
	else if ($mygid == 10011) //生产礼包
	{
		return openDynamicBox($uid,$mygid);
	}
	else if ($mygid == 10012) //高级生产礼包
	{
		return openDynamicBox($uid,$mygid);
	}
	else if ($mygid == 10016) //伯乐包
	{
		$goodslist = array(22=>10,23=>10);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}
	else if ($mygid==10018) //建设礼包
	{
		return openDynamicBox($uid,$mygid);
	}
	else if ($mygid==10019) //城主礼包
	{
		return openDynamicBox($uid,$mygid);
	}
	else if ($mygid==10020) //天御礼包内有“八卦阵图”1、“智多星”1、“虎符”1
	{
		$goodslist = array(7=>1,29=>1,26=>1);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}
	else if ($mygid==10021) //武神礼包内有“陷阵战鼓”1、“武曲星”1、“虎符”1
	{
		$goodslist = array(6=>1,28=>1,26=>1);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}
	else if ($mygid==10022) //遁世礼包内有“陷阵战鼓”1、“迁城令”2、“免战牌”2
	{
		$goodslist = array(24=>2,12=>2);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}
	else if ($mygid==10023) //中包洗髓丹内有“洗髓丹”5
	{
		$goodslist = array(22=>5);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}
	else if ($mygid==10024) //中包锦囊内有“锦囊”20
	{
		$goodslist = array(13=>20);
		foreach($goodslist as $gid=>$cnt)
		{
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
	}else if ($mygid == 10072) //“礼上加礼”新手大礼包需要玩家脱离新手保护时才能使用。
	 {
	 	 $state =sql_fetch_one_cell("select state from sys_user where uid =$uid ");
		 if ($state == 1 )  throw new Exception("你未脱离新手保护期，不能打开礼包。");
		 $goodslist =  array(1=>1,2=>1,3=>1,4=>1,5=>1,54=>1,12=>3,56=>1,24=>3,67=>1,68=>1,69=>1,8=>1,65=>1,9=>1,63=>1,64=>1,40=>1);
		 foreach($goodslist as $gid=>$cnt)
		 {
			 addGoods($uid,$gid,$cnt,6);
			 $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		 }
	 }

	reduceGoods($uid,$mygid,1);
	return $ret;
}


function openHeroBox($uid,$cid,$gid)
{
	if (cityHasHeroPosition($uid,$cid))
	{
		$alreadyCount=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and `herotype`=2");
		if($alreadyCount>=10)
		{
			throw new Exception($GLOBALS['activity']['hero_count_limit']);
		}
		$levelRand=mt_rand(1,100);
		$level=20;
		if($levelRand<35) $level=mt_rand(20,29);
		else if($levelRand<65) $level=mt_rand(30,39);
		else if($levelRand<85) $level=mt_rand(40,49);
		else if($levelRand<95) $level=mt_rand(50,59);
		else $level=mt_rand(60,70);

		$attriLevel=mt_rand(1,100);
		$attri1=30;
		$attri2=30;
		$attri3=70;
		$heroNamePre=$GLOBALS['activity']['hero_type_name1'];
		if($attriLevel<35)
		{
			$attri3=70;
			$heroNamePre=$GLOBALS['activity']['hero_type_name1'];
		}
		else if($attriLevel<65)
		{
			$attri3=75;
			$heroNamePre=$GLOBALS['activity']['hero_type_name2'];
		}
		else if($attriLevel<85)
		{
			$attri3=80;
			$heroNamePre=$GLOBALS['activity']['hero_type_name3'];
		}
		else if($attriLevel<95)
		{
			$attri3=85;
			$heroNamePre=$GLOBALS['activity']['hero_type_name4'];
		}
		else
		{
			$attri3=89;
			$heroNamePre=$GLOBALS['activity']['hero_type_name5'];
		}

		$totalAttri=$attri1+$attri2+$attri3;
		$attriadd1=floor(($attri1/$totalAttri)*$level);
		$attriadd2=floor(($attri2/$totalAttri)*$level);
		$attriadd3=$level-$attriadd1-$attriadd2;
		//生成一个随机性别
		$sex = (mt_rand(0,9) == 0)?0:1;//10分之一的机率
		//男人有859个头像，女人有105个头像 
		$face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);
		$hero_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$level'");
		$loyalty=70;
		$heroType=(mt_rand()%3);
		if($heroType==0) //谋士
		{
			$heroname = $heroNamePre.$GLOBALS['activity']['wisdom_hero'];
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri2','$attri3','$attriadd1','$attriadd2','$attriadd3','$loyalty',2)";
			$forcemax=100+floor($level/5)+floor(($attri2+$attriadd2)/3);
			$energymax=100+floor(level/5)+floor(($attri3+$attriadd3)/3);
		}
		else if($heroType==1)	//政客
		{
			$heroname = $heroNamePre.$GLOBALS['activity']['affairs_hero'];
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri3','$attri1','$attri2','$attriadd3','$attriadd1','$attriadd2','$loyalty',2)";
			$forcemax=100+floor($level/5)+floor(($attri1+$attriadd1)/3);
			$energymax=100+floor(level/5)+floor(($attri2+$attriadd2)/3);
		}
		else if($heroType==2)	//武将
		{
			$heroname = $heroNamePre.$GLOBALS['activity']['bravery_hero'];
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri3','$attri2','$attriadd1','$attriadd3','$attriadd2','$loyalty',2)";
			$forcemax=100+floor($level/5)+floor(($attri3+$attriadd3)/3);
			$energymax=100+floor(level/5)+floor(($attri2+$attriadd2)/3);
		}
		//招人
		$hid = sql_insert($sql);
		sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`) values ('$hid',100,$forcemax,100,$energymax)");
		updateCityHeroChange($uid,$cid);
		reduceGoods($uid,$gid,1);
		return sprintf($GLOBALS['activity']['get_hero_tip'],$heroname);
	}
	else
	{
		throw new Exception($GLOBALS['recruitHero']['hotel_level_low']);
	}
}
function openChristmasHose($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
	{
		$name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
		$msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
		throw new Exception($msg);
	}
	$rate=mt_rand(0,100);
	$gtype=0;
	$ret=array();
	if($rate>98)
	{
		$hasCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and armorid='1413'");
		if($hasCount<3)
		{
			$gid=1413;
			$gtype=1;
		}
		else
		{
			$rate=mt_rand(0,90);
		}
	}
	else if($rate>96)
	{
		$hasCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and armorid='1414'");
		if($hasCount<1)
		{
			$gid=1414;
			$gtype=1;
		}
		else
		{
			$rate=mt_rand(0,90);
		}
	}
	if($rate<70)
	{
		$gid=mt_rand(0,4);
		if($gid<4) $gid=87+$gid;
		else $gid=113;
		$gtype=0;
	}
	else if($rate<86)
	{
		$gid=114;
		$gtype=0;
	}
	else if($rate<=96)
	{
		$gid=115;
		$gtype=0;
	}
	if($gtype==0)
	{
		$ret[]=sql_fetch_one("select *,1 as count from cfg_goods where gid='$gid'");
		addGoods($uid,$gid,1,3);
	}
	else
	{
		/*$curCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and hid=0");
		 if($curCount>=50)
		 {
		 throw new Exception($GLOBALS['useGoods']['armor_box_full']);
		 }*/
		$armor=sql_fetch_one("select *,1 as count from cfg_armor where id='$gid'");
		if(empty($armor))
		{
			throw new Exception($GLOBALS['useGoods']['invalid_data']);
		}
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		addArmor($uid,$armor,1,3);
		$ret[]=$armor;
	}
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openChristmasPack($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
	{
		$name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
		$msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
		throw new Exception($msg);
	}
	$curCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and hid=0");
	if($curCount>=50)
	{
		throw new Exception($GLOBALS['useGoods']['armor_box_full']);
	}
	$goods=array(2,3,4,5,56,40);
	$armors=array(1411,1412);
	$ret=array();
	foreach($goods as $gid)
	{
		addGoods($uid,$gid,1,3);
		$ret[]=sql_fetch_one("select *,1 as count from cfg_goods where gid='$gid'");
	}
	foreach($armors as $gid)
	{
		$armor=sql_fetch_one("select *,1 as count from cfg_armor where id='$gid'");
		if(empty($armor))
		{
			throw new Exception($GLOBALS['useGoods']['invalid_data']);
		}
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		addArmor($uid,$armor,1,3);
		$ret[]=$armor;
	}
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openChristmasCloth($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
	{
		$name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
		$msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
		throw new Exception($msg);
	}
	$curCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and hid=0");
	if($curCount>=50)
	{
		throw new Exception($GLOBALS['useGoods']['armor_box_full']);
	}
	$ret=array();
	$armors=array(1403,1404,1405,1406,1407,1408,1409,1410);
	foreach($armors as $gid)
	{
		$armor=sql_fetch_one("select *,1 as count from cfg_armor where id='$gid'");
		if(empty($armor))
		{
			throw new Exception($GLOBALS['useGoods']['invalid_data']);
		}
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		addArmor($uid,$armor,1,3);
		$ret[]=$armor;
	}
	reduceGoods($uid,$mygid,1);
	return $ret;
}
function openJiaoZi($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
	if ($rate<1000)
	{
		sql_query("update mem_city_resource set food=food+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_food'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<2000)
	{
		sql_query("update mem_city_resource set wood=wood+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_wood'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<3000)
	{
		sql_query("update mem_city_resource set rock=rock+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_rock'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<4000)
	{
		sql_query("update mem_city_resource set iron=iron+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_iron'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<5000)
	{
		sql_query("update mem_city_resource set gold=gold+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_gold'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<6000)
	{
		$gid=113;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<6900)
	{
		$gid=114;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<9000)
	{
		$gid=0;
		$aid=0;
		if($rate<7400)
		{
			$gid=115;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<7700)
		{
			$gid=56;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<8000)
		{
			$gid=40;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<8200)
		{
			$gid=82;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<8300)
		{
			$gid=25;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<8350)
		{
			$gid=117;
			$cnt=1;
			addGoods($uid,$gid,$cnt,6);
			$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		}
		else if($rate<8450)	//萌萌
		{
			$aid=1401;
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		else if($rate<8500)	//黑手战袍
		{
			$aid=1415;
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		else if($rate<8550)	//极品一戒
		{
			$aid=1400;
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		else if($rate<8650)	//桃园兄弟会之剑
		{
			$aid=1402;
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		else if($rate<8700) //红鬃马
		{
			$aid=1392;
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		else if($rate<8900)	//50元宝
		{
			addMoney($uid,50,20);
			$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"50","gid"=>0);
		}
		else if($rate<9000)	//100元宝
		{
			addMoney($uid,100,20);
			$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"100","gid"=>0);
		}
		if($gid!=0)	$goodsname=sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");
		else if($aid!=0)	$goodsname=sql_fetch_one_cell("select name from cfg_armor where id='$aid'");
		else if($rate<8900)	$goodsname='元宝50';
		else if($rate<9000) $goodsname='元宝100';
		
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$msg="恭喜【".$uname."】打开饺子获得【".$goodsname."】！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	}
	else if($rate<=10000)	//10元宝
	{
		addMoney($uid,10,20);
		$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"10","gid"=>0);
	}
    return $ret;
}

function openChiBiPack($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
    if($rate<8000)
    {
    	$thing=sql_fetch_one("select * from cfg_things where tid='15010'");
	   	$thing['count']=1;
    	$thing['gtype']=2;
	   	$ret[] = $thing;
	    addThings($uid,15010,1,3);
    }
    else
    {
	    if($rate<8100)
	    {
	    	$aid=1423;
	    }
	    else if($rate<8200)
	    {
	    	$aid=1424;
	    }
	    else if($rate<8300)
	    {
	    	$aid=1425;
	    }
	    else if($rate<8400)
	    {
	    	$aid=1426;
	    }
	    else if($rate<8500)
	    {
	    	$aid=1427;
	    }
	    else if($rate<8600)
	    {
	    	$aid=1428;
	    }
	    else if($rate<8700)
	    {
	    	$aid=1429;
	    }
	    else if($rate<8900)
	    {
	    	$aid=1430;
	    }
	    else if($rate<9200)
	    {
	    	$aid=1431;
	    }
	    else if($rate<9250)
	    {
	    	$aid=1432;
	    }
	    else if($rate<9280)
	    {
	    	$aid=1433;
	    }
	    else if($rate<9480)
	    {
	    	$aid=1434;
	    }
	    else if($rate<9980)
	    {
	    	$aid=1435;
	    }
	    else if($rate<=10000)
	    {
	    	$aid=1436;
	    }
	   	$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
		
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
    	$uname=addslashes($uname);
    	$msg="恭喜【".$uname."】打开赤壁装备箱获得【".$armor['name']."】！";
    	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
    	
    }
    return $ret;
}

function openJianDu($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $aid=1423+intval(mt_rand(0,13));
    $armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
	addArmor($uid,$armor,1,6);
	$armor['cnt']=1;
	$armor['gtype']=1;
	$armor['hp']=$armor['ori_hp_max'];
	$armor['hp_max']=$armor['ori_hp_max'];
	$ret[] = $armor;
	
	$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
	$msg="恭喜【".$uname."】打开简牍获得【".$armor['name']."】！";
	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	
	
	return $ret;
}

function openJianCe($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
    
    $aids=array(1423,1424,1425,1426,1427,1428,1429,1430,1431,1431,1432,1432,1433,1434,1435,1436);
    
    foreach($aids as $aid)
    {
	    $armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
    }
    
    $uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
	$msg="恭喜【".$uname."】打开简策获得【赤壁套装（16件）】！";
	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	
	return $ret;
}

function xiuJia($uid,$day)
{
    //军队在外，不能休假
    if (sql_check("select uid from sys_troops where uid='$uid'"))
    {
    	throw new Exception($GLOBALS['changeUserState']['army_out'].$GLOBALS['changeUserState']['xiujia']);
    }
 	//科技在升级，不能休假
 	if(sql_check("select uid from sys_technic where uid='$uid' and state=1"))
 	{
 		throw new Exception($GLOBALS['changeUserState']['technic_upgrading'].$GLOBALS['changeUserState']['xiujia']);
 	}
	$mycities = sql_fetch_rows("select cid from sys_city where uid='$uid'");
	$comma="";
	$mycids="";
	foreach($mycities as $city)
	{
		$mycids .=$comma.$city['cid'];
		$comma=",";
	}
 	//建筑在升级，不能休假
 	if(sql_check("select id from sys_building where cid in ($mycids) and state<>0"))
 	{
 		throw new Exception($GLOBALS['changeUserState']['building_upgrading'].$GLOBALS['changeUserState']['xiujia']);
 	}
 	
    //有兵营训练队列，不能休假
    if(sql_check("select id from sys_city_draftqueue where cid in ($mycids)"))
 	{
 		throw new Exception($GLOBALS['changeUserState']['soldier_queue'].$GLOBALS['changeUserState']['xiujia']);
 	}
    //有城防制造队列，不能休假
    if(sql_check("select id from sys_city_reinforcequeue where cid in ($mycids)"))
 	{
 		throw new Exception($GLOBALS['changeUserState']['defence_queue'].$GLOBALS['changeUserState']['xiujia']);
 	}
 	
 	
 	//有城池在战乱，不能休假
    foreach($mycities as $city)
    {
        if (sql_check("select * from mem_world where wid=".cid2wid($city['cid'])." and state=1"))
        {
            throw new Exception($GLOBALS['changeUserState']['some_city_in_war'].$GLOBALS['changeUserState']['xiujia']);
        }
    }
    //自动把盟友的军队遣返
    foreach($mycities as $city)
    {
    	//联盟在本城的驻军
    	$cityid=$city['cid'];
		sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where targetcid='$cityid' and state=4 and task=1 and cid <> '$cityid'");
		//联盟在野地的驻军
		$myfields=sql_fetch_rows("select wid from mem_world where ownercid='$cityid' and type > 1");
		if(!empty($myfields))
		{
			$fieldcids="";
			$comma="";
			foreach($myfields as $field)
			{
				$fieldcids .=$comma.wid2cid($field['wid']);
				$comma=",";
			}
			sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where targetcid in ('$fieldcids') and task=1 and state=4 and cid <>'$cityid'");
		}
    }
    //开始休假，并扣钱
    $vactime=$day*86400;
    sql_query("insert into sys_user_state (uid,vacstart,vacend) values ('$uid',unix_timestamp(),unix_timestamp()+'$vactime') on duplicate key update vacstart=unix_timestamp(),vacend=unix_timestamp()+'$vactime'");
    sql_query("update mem_city_resource set vacation=1 where cid in ($mycids)");
}

function useXiuJiaFu($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
	$day=3;
	if($mygid==122) $day=10;
	xiuJia($uid,$day);
    reduceGoods($uid,$mygid,1);
	$endtime=intval(sql_fetch_one_cell("select unix_timestamp()"))+$day*86400;
	file_put_contents("./sessions/".$uid,"0");
	file_put_contents("/bloodwar/server/game/sessions/".$uid,"0");
    return "休假开始，你会自动掉线。休假将于".MakeEndTime($endtime)."结束！";
}

function openMissMail($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
	$gid=0;
	$aid=0;
	if ($rate<1000)
	{
		$aid=1437;
		$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
	}
	else if($rate<5000)
	{
		$gid=113;
		$cnt=5;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<7000)
	{
		$gid=114;
		$cnt=2;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<8000)
	{
		$gid=115;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else
	{
		$gid=118;
		$cnt=5;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	if($aid==1437)
	{
		if($gid!=0)	$goodsname=sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");
		else if($aid!=0)	$goodsname=sql_fetch_one_cell("select name from cfg_armor where id='$aid'");
		
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$msg="恭喜【".$uname."】打开小姐书信获得【".$goodsname."】！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	}
    return $ret;
}

function openLoverBox($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    
	$aid=1438;
	$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
	addArmor($uid,$armor,1,6);
	$armor['cnt']=1;
	$armor['gtype']=1;
	$armor['hp']=$armor['ori_hp_max'];
	$armor['hp_max']=$armor['ori_hp_max'];
	$ret[] = $armor;
	if($aid!=0)	$goodsname=sql_fetch_one_cell("select name from cfg_armor where id='$aid'");
		
	$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
	$msg="恭喜【".$uname."】打开情人节礼盒获得【".$goodsname."】！";
	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");

    return $ret;
}

function openMissPack($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
	$gid=0;
	$aid=0;
	
	if($rate<5000)
	{
		$gid=113;
		$cnt=10;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<8000)
	{
		$gid=114;
		$cnt=3;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<9700)
	{
		$gid=115;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else
	{
		$tid=15012;
		$cnt=1;
		addThings($uid,$tid,1,3);
		$thing=sql_fetch_one("select *,$cnt as count from cfg_things where tid='$tid'");
		$thing['gtype']=2;
		$ret[] = $thing;
		
		$thingname=sql_fetch_one_cell("select name from cfg_things where tid='$tid'");
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$msg="恭喜【".$uname."】打开小姐的包袱获得【".$thingname."】！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	}
    return $ret;
}

function openNiuSheng($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    reduceGoods($uid,$mygid,1);
    $ret=array();
    $rate=mt_rand(1,10000);
    $gid=-1;
    $cnt=0;
    $aid=-1;
	if ($rate<1200)	//粮食10000
	{
		sql_query("update mem_city_resource set food=food+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_food'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<2400)	//木材10000
	{
		sql_query("update mem_city_resource set wood=wood+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_wood'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<3600)	//石料10000
	{
		sql_query("update mem_city_resource set rock=rock+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_rock'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<4800)	//铁锭10000
	{
		sql_query("update mem_city_resource set iron=iron+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_iron'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<5800)	//黄金10000
	{
		sql_query("update mem_city_resource set gold=gold+10000 where cid='$cid'");
		$msg=sprintf($GLOBALS['resPackage']['gain_gold'],10000);
		throw new Exception ($msg);
	}
	else if ($rate<6600)	// 木牛流马*3
	{
		$gid=11;
		$cnt=3;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<7700)		//元宝10
	{
		$gid=0;
		$cnt=10;
		addMoney($uid,10,20);
		$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"10","gid"=>0);
	}
	else if($rate<8200)		//火药桶
	{
		$gid=83;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<8700)		//洗髓丹
	{
		$gid=22;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<8900)		//徭役令
	{
		$gid=56;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<9100)		//建筑图纸
	{
		$gid=40;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<9190)		//黄金钥匙
	{
		$gid=21;
		$cnt=1;
		addGoods($uid,$gid,$cnt,6);
		$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
	}
	else if($rate<9800)	//金牛装
	{
		$aid=1447;
		if($rate<9270)	//金牛戒指
		{
			$aid=1447;
		}
		else if($rate<9350)	//金牛挂饰
		{
			$aid=1448;
		}
		else if($rate<9430)	//金牛披肩
		{
			$aid=1443;
		}
		else if($rate<9500)	//金牛护腕
		{
			$aid=1445;
		}
		else if($rate<9570)	//金牛项链
		{
			$aid=1440;
		}
		else if($rate<9620)	//金牛护肩
		{
			$aid=1441;
		}
		else if($rate<9670)	//金牛战盔
		{
			$aid=1439;
		}
		else if($rate<9700)	//金牛铠甲
		{
			$aid=1442;
		}
		else if($rate<9730)	//金牛腰带
		{
			$aid=1444;
		}
		else if($rate<9760)	//金牛战靴
		{
			$aid=1446;
		}
		else if($rate<9780)	//金牛长剑
		{
			$aid=1449;
		}
		else if($rate<9800)	//金牛
		{
			$aid=1450;
		}
		$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
	}
	else if($rate<9950)	//50元宝
	{
		$gid=0;
		$cnt=50;
		addMoney($uid,$cnt,20);
		$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"50","gid"=>0);
	}
	else if($rate<=10000)	//100元宝
	{
		$gid=0;
		$cnt=100;
		addMoney($uid,$cnt,20);
		$ret[] = array("name"=>$GLOBALS['useHuodongGoods']['YuanBao'],"count"=>"100","gid"=>0);
	}
	if($rate>=5800)
	{
		if($gid>=0)	$goodsname=sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");
		else if($aid>=0)	$goodsname=sql_fetch_one_cell("select name from cfg_armor where id='$aid'");
		if($cnt>1)
		{
			$goodsname=$goodsname."*".$cnt;
		}
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$msg="恭喜【".$uname."】打开拴牛缰绳获得【".$goodsname."】！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	}
    return $ret;
}
function openBaiYueHero($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
	if (cityHasHeroPosition($uid,$cid))
    {
		$level=50;
		$attri1=30;
		$attri2=30;
		$attri3=85;
		$heroname = '拜月王妃';
		$herotype=27;
		if($mygid==10049)
		{
			$level=70;
			$attri3=89;
			$heroname = '拜月王后';
			$herotype=28;
		}

		$totalAttri=$attri1+$attri2+$attri3;
		$attriadd1=floor(($attri1/$totalAttri)*$level);
		$attriadd2=floor(($attri2/$totalAttri)*$level);
		$attriadd3=$level-$attriadd1-$attriadd2;
		//生成一个随机性别
		$sex = 0;//10分之一的机率
		//男人有859个头像，女人有105个头像 
		$face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);
		$hero_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$level'");
		$loyalty=70;
		$heroType=(mt_rand()%3);
		if($heroType==0) //谋士
		{
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri2','$attri3','$attriadd1','$attriadd2','$attriadd3','$loyalty','$herotype')";
			$forcemax=100+floor($level/5)+floor(($attri2+$attriadd2)/3);
			$energymax=100+floor(level/5)+floor(($attri3+$attriadd3)/3);
		}
		else if($heroType==1)	//政客
		{
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri3','$attri1','$attri2','$attriadd3','$attriadd1','$attriadd2','$loyalty','$herotype')";
			$forcemax=100+floor($level/5)+floor(($attri1+$attriadd1)/3);
			$energymax=100+floor(level/5)+floor(($attri2+$attriadd2)/3);
		}
		else if($heroType==2)	//武将
		{
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri3','$attri2','$attriadd1','$attriadd3','$attriadd2','$loyalty','$herotype')";
			$forcemax=100+floor($level/5)+floor(($attri3+$attriadd3)/3);
			$energymax=100+floor(level/5)+floor(($attri2+$attriadd2)/3);
		}
		//招人
		$hid = sql_insert($sql);
		sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`) values ('$hid',100,$forcemax,100,$energymax)");
		updateCityHeroChange($uid,$cid);
		reduceGoods($uid,$mygid,1);
		return "恭喜你获得“".$heroname."”一名，请去招贤馆查看。";
    }
    else
    {
        throw new Exception($GLOBALS['recruitHero']['hotel_level_low']);
    }
}
function openBaiYueArmorBox($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    $ret=array();
	if($mygid==10050)
	{
		$aid=mt_rand(1452,1463);
		$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
	}
	else
	{
		for($aid=1452;$aid<=1463;$aid++)
		{
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
	}
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openPayGiftBox($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    $ret=array();
    $goodsArray=array(0,0,0,67,68,69,70,71,72,8,65,9,66,52,10,118,56,40,12,28,29,6,7,22,82,25,1402,1415,1400,1401,1392);
    $cntArray=array(10,50,100,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,5,1,1,1,1,1,1,1);
    $rateArray=array(800,100,50,1500,800,500,100,80,30,1500,800,400,90,50,20,500,100,100,300,200,200,700,700,70,70,30,50,50,50,50,10);
    $showArray=array(0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1,0,0,0,0,0,1,0,1,1,1,1,1);
    $totalRate=10000;
	$arrSize=31;
    $rateValue=mt_rand(1,10000);
    $sumRate=0;
    $gid=-1;
    $cnt=0;
    $isShow=0;
    for($i=0;$i<$arrSize;$i++)
    {
    	if($sumRate+$rateArray[$i]>=$rateValue)
    	{
    		$gid=$goodsArray[$i];
    		$cnt=$cntArray[$i];
    		$isShow=$showArray[$i];
    		break;
    	}
    	$sumRate=$sumRate+$rateArray[$i];
    }
    $goodsname='';
    if($gid>1000)
    {
    	$aid=$gid;
		$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=$cnt;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
		$goodsname=$armor['name'];
    }
    else
    {
    	if($gid==0)
    	{
    		addMoney($uid,$cnt,20);
    	}
    	else
    	{
			addGoods($uid,$gid,$cnt,6);
		}
		$goods=sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
		$ret[]=$goods;
		$goodsname=$goods['name'];
    }
    if($isShow>0)
    {
		$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
		$uname=addslashes($uname);
		$msg="恭喜【".$uname."】打开愿望礼包获得【".$goodsname."】*".$cnt."！";
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
    }
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openJinNiuBox($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    $ret=array();
    $armors=array(1439,1440,1441,1442,1443,1444,1445,1446,1447,1448,1449,1450);
    $rateArray=array(80,90,90,80,100,80,100,80,100,100,50,50);
    $sumRate=0;
    $rateValue=mt_rand(1,1000);
    $aid=$armors[0];
    for($i=0;$i<12;$i++)
    {
    	if($sumRate+$rateArray[$i]>=$rateValue)
    	{
    		$aid=$armors[$i];
    		break;
    	}
    	$sumRate=$sumRate+$rateArray[$i];
    }
    $armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
	addArmor($uid,$armor,1,6);
	$armor['cnt']=$cnt;
	$armor['gtype']=1;
	$armor['hp']=$armor['ori_hp_max'];
	$armor['hp_max']=$armor['ori_hp_max'];
	$ret[] = $armor;
	$goodsname=$armor['name'];
	
	$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
	$msg="恭喜【".$uname."】打开金牛装备箱获得【".$goodsname."】！";
	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	
	reduceGoods($uid,$mygid,1);
	return $ret;
}

function openShengXiaoYuPai($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    
    $thingArray=array(15027,15028,15029,15030,15031,15032,15033,15034,15035,15036,15037,15038);
    $rateArray=array(70,30,200,500,2000,70,50,100,300,500,2000,4180);
    $sumRate=0;
    $arrCnt=12;
    $rate=mt_rand(1,10000);
    $tid=15038;
    for($i=0;$i<$arrCnt;$i++)
    {
    	if($sumRate+$rateArray[$i]>=$rate)
    	{
    		$tid=$thingArray[$i];
    		break;
    	}
    	$sumRate=$sumRate+$rateArray[$i];
    }
    
    $ret=array();
	$cnt=1;
	addThings($uid,$tid,1,3);
	$thing=sql_fetch_one("select *,$cnt as count from cfg_things where tid='$tid'");
	$thing['gtype']=2;
	$ret[] = $thing;

	reduceGoods($uid,$mygid,1);
	
	return $ret;
}

function openShengXiaoHe($uid,$cid,$mygid)
{
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
    
    
    $uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
	
    $ret=array();
	if($mygid==10055)
	{
		$aid=mt_rand(1465,1476);
		$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
		addArmor($uid,$armor,1,6);
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
		
		
		$msg="恭喜【".$uname."】打开生肖装备箱获得【".$armor['name']."】！";
	}
	else
	{
		for($aid=1465;$aid<=1476;$aid++)
		{
			$armor=sql_fetch_one("select * from cfg_armor where id='$aid'");
			addArmor($uid,$armor,1,6);
			$armor['cnt']=1;
			$armor['gtype']=1;
			$armor['hp']=$armor['ori_hp_max'];
			$armor['hp_max']=$armor['ori_hp_max'];
			$ret[] = $armor;
		}
		
		$msg="恭喜【".$uname."】打开生肖套装盒获得生肖套装！";
	}
   	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");
	reduceGoods($uid,$mygid,1);
	
	return $ret;
}


/**
 * 
 * 打开设置了cfg_box_details的道具，一般会活动中的道具
 * $srctype=0 表示道具宝箱，$srcid对应cfg_goods表中的gid, 主要用于大乐透活动
 * $srctype=1 表示从宝藏图中开出， $srcid对应cfg_act表中的id， 主要用于寻宝活动
 * $srctype=2 表示客栈刷将领开出， $srcid对应表cfg_recruit_hero中的id， 主要用于客栈刷将领活动
 */
function openDefaultBox($uid,$cid,$srcid,$srctype){	
	include_once 'TaskFunc.php';
	$rows=sql_fetch_rows("select * from cfg_box_details where srctype=$srctype and srcid='$srcid'");
	if (empty($rows)==false){
		foreach($rows as $row){ 
			$sort = $row["sort"];
			$type = $row["type"];
			if ($sort==7){ //如果打开的有活动将领
				return openDefaultHeroBox($uid,$cid,$srcid,$srctype);
			}
		}
	}

	if ($srctype==0){
		$gid = $srcid;
		if (!checkGoods($uid,$srcid)){			
	        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$gid'");
	        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
	        if ($srctype==0) throw new Exception($msg);
	        else return $msg;	        
	    }
	    $boxCon=sql_fetch_one("select * from cfg_box_open_condition where boxid= $gid or keyid = $gid");
	    if (empty($boxCon)==false){
	    		unlockUser($uid);
	        	if ($gid==$boxCon["boxid"]){
	        		if (!checkGoods($uid,$boxCon["keyid"])) throw new Exception($boxCon["nokeydesc"]);	        		        
	        	}else{
	        		if (!checkGoods($uid,$boxCon["boxid"])) throw new Exception($boxCon["noboxdesc"]);	        	
	        		$srcid=$boxCon["boxid"];	
	        	}
	        	reduceGoods($uid,$boxCon["boxid"],1);
	        	reduceGoods($uid,$boxCon["keyid"],1);	        	
	    }else	        	       
	    	reduceGoods($uid,$srcid,1);	    
	}	
	if($srctype==2&&empty($rows)) return false;//招将可能不给东西
	$row =  getOpenDefaultGoodsResult($uid, $srctype,$srcid);
	if($srctype==2 && !$row){
		return false;//达到上限了不得东西
	}
    $sort = $row["sort"];
    $type = $row["type"];
    $count = $row["count"];
    return openDefaultGoodsNow($uid,$cid,$srcid,$srctype,$sort,$type,$count);
}

function getOpenDefaultGoodsResult($uid,$srctype,$srcid){
   $rows=sql_fetch_rows("select * from cfg_box_details where srctype=$srctype and srcid='$srcid'");
   	if (!$rows){
	    	$msg=$GLOBALS['useGoods']['func_not_in_use'];
	    	if ($srctype==0) throw new Exception($msg);
		    else return $msg;
	}	
   $rateSum = 0 ;
   foreach($rows as $row)
	  $rateSum+=$row["rate"]; 	
    $rate=mt_rand(1,$rateSum);
    $curRateSum = 0 ;
    $row=null;
    foreach($rows as $temprow){
    	$curRateSum+=$temprow["rate"];
    	if ($curRateSum<$rate) continue;    	
    	$dayopencount=  $temprow["dayopencount"];
	    $totalopencount=  $temprow["totalopencount"];
	    $owncount=  $temprow["owncount"];
	    $sort = $temprow["sort"];
	    $type = $temprow["type"];
		if ($dayopencount>0){
			$todayOpenCount=0;
			if ($sort=="2"){ 
			    	$todayOpenCount=sql_fetch_one_cell("select sum(count) from log_goods where uid = $uid and type=6 and  gid = $type and  curdate()=date(from_unixtime(time))");
			}else if ($sort=="5"){
			    	$todayOpenCount=sql_fetch_one_cell("select sum(count) from log_things where uid = $uid and type=6 and  tid = $type and  curdate()=date(from_unixtime(time))");
			}else if ($sort=="6"){
			    	$todayOpenCount=sql_fetch_one_cell("select sum(count) from log_armor where uid = $uid and type=6 and  armorid = $type and  curdate()=date(from_unixtime(time))");
			}
		    if ($todayOpenCount>=$dayopencount) continue; //今天获得了的该物品已经超过数目
		}
		if ($totalopencount>0){
			$user_totalopencount=0;
			if ($sort=="2"){ 
			    	$user_totalopencount=sql_fetch_one_cell("select sum(count) from log_goods where uid = $uid and type=6 and  gid = $type");
			}else if ($sort=="5"){
			    	$user_totalopencount=sql_fetch_one_cell("select sum(count) from log_things where uid = $uid and type=6 and  tid = $type ");
			}else if ($sort=="6"){
			    	$user_totalopencount=sql_fetch_one_cell("select sum(count) from log_armor where uid = $uid and type=6 and  armorid = $type");
			}
		    if ($user_totalopencount>=$totalopencount) continue; //总共获得的该物品已经超过数目
		}
		if ($owncount>0){
			$user_owncount=0;
			if ($sort=="2"){ 
			    	$user_owncount=sql_fetch_one_cell("select sum(count) from sys_goods where uid = $uid and  gid = $type");
			}else if ($sort=="5"){
			    	$user_owncount=sql_fetch_one_cell("select sum(count) from sys_things where uid = $uid  and  tid = $type ");
			}else if ($sort=="6"){
			    	$user_owncount=sql_fetch_one_cell("select count(1) from sys_user_armor where uid = $uid and  armorid = $type");
			}
		    if ($user_owncount>=$owncount) continue; //用户拥有该物品数目超过限制
		}
		
    	$row=$temprow;    	
    	break;
    }
    return $row;
}
function openDefaultGoodsNow($uid,$cid,$srcid,$srctype,$sort,$type,$count){
	$ret=array();
    $row = sql_fetch_one("select * from cfg_box_details where srcid = $srcid and srctype = $srctype and sort= $sort and type = $type and count= $count");
    $cnt = $row["count"];
    $inform = $row["inform"];    	
    
	$goodgetsname=giveReward($uid,$cid,$row,6,20,true);        
	$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
	$uname=addslashes($uname);
   
	if ($sort == 2){
		if ($type==0) $ret[] = array("name"=>$GLOBALS['sys']['LiJin'],"count"=>$cnt,"gid"=>0);
		else $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$type'");
	}else if ($sort == 5){
		$thing=sql_fetch_one("select *,$cnt as count from cfg_things where tid='$type'");
	   $thing['gtype']=2;
	   $ret[] = $thing;
	}else if ($sort==6){
		$armor=sql_fetch_one("select * from cfg_armor where id='$type'");		
		$armor['cnt']=1;
		$armor['gtype']=1;
		$armor['hp']=$armor['ori_hp_max'];
		$armor['hp_max']=$armor['ori_hp_max'];
		$ret[] = $armor;
	}
	        	
	$goodname = sql_fetch_one_cell("select name from cfg_goods where gid='$srcid'");
	if ($srctype>=100 && $srctype<=103) //每日登录奖励
		$msg = sprintf($GLOBALS['dailyreward']['inform'] ,$uname,$goodgetsname);
	else if ($srctype==0){
		$msg = sprintf($GLOBALS['goodsopen']['inform'] ,$uname,$goodname.$goodgetsname);
	}else
		$msg = sprintf($GLOBALS['treasureResult']['inform'] ,$uname,$goodgetsname);		
	if ($inform) //inform==1表示需要通知用户
		sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (0,1,unix_timestamp(),unix_timestamp()+300,1800,1,49151,'$msg')");		
  	if ($srctype==0){    	
  		if (count($ret)==0) throw new Exception($goodgetsname);	
  		return $ret;
  	}
    else return $goodgetsname;
}
function  openDefaultHeroBox($uid,$cid,$srcid,$srctype)
{
	unlockUser($uid);
	$mygid=$srcid;
	if (!checkGoods($uid,$mygid))
    {
        $name = sql_fetch_one_cell("select name from cfg_goods where gid='$mygid'");
        $msg = sprintf($GLOBALS['useHuodongGoods']['no_HuoDongGoods'],$name);
        throw new Exception($msg);
    }
	if (!cityHasHeroPosition($uid,$cid))
		 throw new Exception($GLOBALS['recruitHero']['hotel_level_low']);
		 

	$rows=sql_fetch_rows("select * from cfg_box_details where srctype=$srctype and srcid='$srcid'");
    $rateSum = 0 ;
    foreach($rows as $row)
	  $rateSum+=$row["rate"]; 
	
    $rate=mt_rand(1,$rateSum);
    $curRateSum = 0 ;
    foreach($rows as $row){
    	$curRateSum+=$row["rate"];
    	if ($curRateSum<$rate) continue;
    	
    	$type = $row["type"];
    	$hero = sql_fetch_one("select * from cfg_recruit_hero where id = $type");   	
		$level=$hero["level"];				
    	if (isset($hero["min_level"])){				
	    	$min_level=$hero["min_level"];
			$max_level=$hero["max_level"];
			if 	($min_level>0 && $max_level>0){
				$level = mt_rand($min_level,$max_level);			
			}
		}
		$attri1=mt_rand($hero["min_affairs_base"],$hero["max_affairs_base"]);
		$attri2=mt_rand($hero["min_bravery_base"],$hero["max_bravery_base"]);
		$attri3=mt_rand($hero["min_wisdom_base"],$hero["max_wisdom_base"]);
		$loyalty=mt_rand($hero["min_loyalty"],$hero["max_loyalty"]);	
		$heroname = $hero["heroname"];
	    $herotype = $hero["herotype"];
		$totalAttri=$attri1+$attri2+$attri3;
		$attriadd1=floor(($attri1/$totalAttri)*$level);
		$attriadd2=floor(($attri2/$totalAttri)*$level);
		$attriadd3=$level-$attriadd1-$attriadd2;
		//生成一个随机性别
		$sex = $hero["sex"];//10分之一的机率
		//男人有859个头像，女人有105个头像 
		$face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);
		$hero_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$level'");
		$loyalty=70;
		
		
		$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri2','$attri3','$attriadd1','$attriadd2','$attriadd3','$loyalty','$herotype')";
		$forcemax=100+floor($level/5)+floor(($attri2+$attriadd2)/3);
		$energymax=100+floor($level/5)+floor(($attri3+$attriadd3)/3);
		
	
		//招人
		$hid = sql_insert($sql);
		sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`) values ('$hid',100,$forcemax,100,$energymax)");
		updateCityHeroChange($uid,$cid);
		reduceGoods($uid,$mygid,1);
    
    	$msg = "恭喜你获得“".$heroname."”一名，请去招贤馆查看。";
		if ($srctype==0){  
		  throw new Exception($msg);
  		}
		return $msg;
    }
}

function addArmorShelf($uid, $gid){
	$goods = sql_fetch_one("select * from sys_goods where uid=$uid and gid=$gid");
	if(empty($goods) || $goods['count']<=0){
		throw new Exception($GLOBALS['goods']['no_shelf_goods']);
	}
	$count = sql_fetch_one_cell("select `armor_column` from sys_user where uid=$uid");
	if($count >= 500){
		throw new Exception( $GLOBALS['goods']['armor_column_full'] );
	}
	$add = 0;
	if($gid == 145) $add = 5;
	if($gid == 146) $add = 50;
	$target = $count;
	if($count+$add > 500)$target = 500;
	else
		$target = $count+$add;
	sql_query("update sys_user set armor_column=$target where uid=$uid");
	sql_query("update sys_goods set `count`=`count`-1 where uid=$uid and gid=$gid");
	return $target;
	//return sprintf($GLOBALS['goods']['armor_column_add'], $target);
}

?>