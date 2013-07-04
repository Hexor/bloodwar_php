<?php                      
require_once("./interface.php");
require_once("./utils.php");

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
        $ret[] = $GLOBALS['useGoods']['XianZhenZhaoGu_valid_date'];
        $ret[] = intval($endtime);   
    }                      
    else if (($gid == 7)||($gid == 49))
    {           
        $endtime = useBaGuaZhenTu($uid,$gid); 
        $ret[] = 2; //代表弹出剩余时间提示框
        $ret[] = $GLOBALS['useGoods']['BaGuaZhenTu_valid_date'];
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
    else if ($gid == 25)
    {
        $endtime = useQingNangShu($uid); 
        $ret[] = 2; //代表弹出剩余时间提示框
        $ret[] = $GLOBALS['useGoods']['QingNangShu_valid_date'];
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
    else if ($gid==57) //典民令
    {
    	$msg=useDianMinLin($uid,$cid);
		$ret[] = 0; //代表弹出信息框
        $ret[] = $msg;
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
    else if($gid==95||$gid==96||$gid==97||($gid>=101&&$gid<=112)) //装备箱
    {
    	$ret[]=3; //代表开出装备
    	$ret[]=useArmorBox($uid,$gid);
    }
    else if ((($gid > 10000)&&($gid <= 10012))||($gid == 10016)||(($gid>=10018)&&($gid<=10024)))
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
    else if ($gid>=50000)
    {
    	$ret[]=1;
    	$ret[]=openDynamicBox($uid,$gid);
    }
    else
    {
        throw new Exception($GLOBALS['useGoods']['func_not_in_use']);
    }
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
	else if($type==1) return getMojiaGoods($uid,$param);
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
	if($heroState>1)
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
	if($heroState>1)
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
	if($heroInfo['state']>1)
	{
		throw new Exception($GLOBALS['useGoods']['hero_state_wrong']);
	}
	$expadd=0;
	if($gid==113) $expadd=1000;
	else if($gid==114) $expadd=10000;
	else if($gid==115) $expadd=100000;
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
    $goodsname = $GLOBALS['useXianZhenZhaoGu']['XianZhenZhaoGu'];
    if ($gid==48)
    {
        $delay = 86400 * 7;
        $goodsname = $GLOBALS['useXianZhenZhaoGu']['advanced_XianZhenZhaoGu'];
    }  
    if (!checkGoods($uid,$gid))
    {
    	$msg = sprintf($GLOBALS['useXianZhenZhaoGu']['no_XianZhenZhaoGu'],$gid);
        throw new Exception($msg);
    }                                          
    
    sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','5',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
    reduceGoods($uid,$gid,1);
    return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=5"); 
}
function useBaGuaZhenTu($uid,$gid)//八卦阵图军队防御增加10%，持续24小时。
{
    $delay = 86400;
    $goodsname = $GLOBALS['useBaGuaZhenTu']['BaGuaZhenTu'];
    if ($gid==49)
    {
        $delay = 86400 * 7;
        $goodsname = $GLOBALS['useBaGuaZhenTu']['advanced_BaGuaZhenTu'];
    }  
    if (!checkGoods($uid,$gid))
    {
    	$msg = sprintf($GLOBALS['useBaGuaZhenTu']['no_BaGuaZhenTu'],$goodsname);
        throw new Exception($msg);
    }
    
    sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','6',unix_timestamp()+$delay) on duplicate key update endtime=endtime + $delay");
    reduceGoods($uid,$gid,1);
    return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=6"); 
}
function useQingNangShu($uid)   //青囊书   可以恢复的伤兵人数增加50%，效果持续24小时
{
    if (!checkGoods($uid,25))
    {
        throw new Exception($GLOBALS['useGoods']['no_QingNangShu']);
    }
    
    sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','9',unix_timestamp()+86400) on duplicate key update endtime=endtime + 86400");
    reduceGoods($uid,25,1);
    return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=9");
}
//打开只掉落一个道具的箱子，比如古朴木盒
function openSimpleGoodsBox($uid,$dropRate)
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
            $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
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

function useYaoYiLin($uid)
{
	if (!checkGoods($uid,56))
    {
        throw new Exception($GLOBALS['useGoods']['no_this_good']);
    }
    
    sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid','11',unix_timestamp()+259200) on duplicate key update endtime=endtime + 259200");
    reduceGoods($uid,56,1);
    return sql_fetch_one_cell("select endtime from  mem_user_buffer where uid='$uid' and buftype=11");
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
	$goods=sql_fetch_one_cell("select goods from cfg_pack_goods where gid='$mygid'");
	if(empty($goods)) throw new Exception($GLOBALS['useGoods']['no_pack_good']);
	$goodslist=explode(',',$goods);
	$goodcnt=$goodslist[0];
    $money=0;
    $ret=array();
    lockUser($uid);
    for ($i = 1; $i < $goodcnt*2; $i+=2)
    {
		$gid=$goodslist[$i];
		$cnt=$goodslist[$i+1];
    	if($gid==0)
    	{
    		$money+=$cnt;
        	$ret[] = array("name"=>$GLOBALS['sys']['YuanBao'],"count"=>$cnt,"gid"=>0,"description"=>$GLOBALS['sys']['description_of_YuanBao']);
    	}
    	else
    	{
	    	addGoods($uid,$gid,$cnt,8);
	    	$ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
    	}
    }
    if($money>0) addMoney($uid,$money,3);
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
        addMoney($uid,$totalValue,20);
        $goodsGet['0']=$totalValue;
    }
    foreach($goodsGet as $gid=>$cnt)
    {;
    	if($gid>0)
    	{
        	addGoods($uid,$gid,$cnt,3);
        }
        $ret[] = sql_fetch_one("select *,$cnt as count from cfg_goods where gid='$gid'");
    }
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
function openSilverBox($uid)
{
    //$totalValue = getValueRand(101,150,151,200,90,100,201,360);
    $totalValue = getValueRand(121,180,181,210,90,120,211,360);
    $dropRate = sql_fetch_rows("select gid,silverbox as rate,value from cfg_goods where inuse=1 and silverbox > 0 and value > 0 and value <= $totalValue order by rand()");
               
    return openTreasureBox($uid,$totalValue,$dropRate,1);    
}
function openGoldBox($uid)
{
    //$totalValue = getValueRand(401,500,501,600,360,400,601,1050);
    $totalValue = getValueRand(321,480,481,560,240,320,561,960);
    $dropRate = sql_fetch_rows("select gid,goldbox as rate,value from cfg_goods where inuse=1 and goldbox > 0 and value > 0 and value <= $totalValue order by rand()");
    return openTreasureBox($uid,$totalValue,$dropRate,2);
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
        $ret = openSilverBox($uid);
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
    return openSimpleGoodsBox($uid,$dropRate);
}
function openLoveBean($uid)
{                                                               
    $dropRate = sql_fetch_rows("select gid,lovebean as rate,value from cfg_goods where inuse=1 and lovebean > 0 and value > 0 order by rand()");
    return openSimpleGoodsBox($uid,$dropRate);
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
    $curCount=sql_fetch_one_cell("select count(*) from sys_user_armor where uid='$uid' and hid=0");
    if($curCount>=50)
    {
    	throw new Exception($GLOBALS['useGoods']['armor_box_full']);
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
    	$armor=sql_fetch_one("select * from cfg_armor where type='$type' and inuse=1 and box_drop=1 order by rand() limit 1");
    }
    else
    {
    	$part=$gid-100;
    	$randvalue=mt_rand(0,100);
    	if($randvalue<=75) $type=1;
    	else if($randvalue<=95) $type=2;
		else $type=3;
		$armor=sql_fetch_one("select * from cfg_armor where part='$part' and type='$type' and inuse=1 and box_drop=1 order by rand() limit 1");
    }
    if(empty($armor))
    {
    	throw new Exception($GLOBALS['useGoods']['invalid_data']);
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
        addMoney($uid,50,2);
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
	}

    reduceGoods($uid,$mygid,1);
    return $ret;
}

function openHeroBox($uid,$cid,$gid)
{
	if (cityHasHeroPosition($uid,$cid))
    {
    	$alreadyCount=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and `juxian`=1");
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
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`juxian`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri2','$attri3','$attriadd1','$attriadd2','$attriadd3','$loyalty',1)";
	        $forcemax=100+floor($level/5)+floor(($attri2+$attriadd2)/3);
	        $energymax=100+floor(level/5)+floor(($attri3+$attriadd3)/3);
		}
		else if($heroType==1)	//政客
		{
	    	$heroname = $heroNamePre.$GLOBALS['activity']['affairs_hero'];
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`juxian`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri3','$attri1','$attri2','$attriadd3','$attriadd1','$attriadd2','$loyalty',1)";
	        $forcemax=100+floor($level/5)+floor(($attri1+$attriadd1)/3);
	        $energymax=100+floor(level/5)+floor(($attri2+$attriadd2)/3);
		}
		else if($heroType==2)	//武将
		{
	    	$heroname = $heroNamePre.$GLOBALS['activity']['bravery_hero'];
			$sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`juxian`) values ('$uid','$heroname','$sex','$face','$cid','0','$level','$hero_exp','$attri1','$attri3','$attri2','$attriadd1','$attriadd3','$attriadd2','$loyalty',1)";
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
	    /*
	    $uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
    	$uname=addslashes($uname);
    	$msg="恭喜【".$uname."】打开圣诞袜子获得【".$armor['name']."*1】！";
    	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (1,1,unix_timestamp(),unix_timestamp()+300,1800,2,16776960,'$msg')");
    	*/
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


?>