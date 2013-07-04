<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");

function getHeroBuffer($uid,$param)
{
	$cid=array_shift($param);
	$hid=array_shift($param);
	return sql_fetch_rows("select c.name,h.endtime from mem_hero_buffer h left join cfg_goods c on c.gid=h.buftype+25 where h.hid='$hid' and h.endtime>unix_timestamp()");
}

function dismissHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['dismissHero']['cant_dissmiss_this']);
    }   
    if ($hero['state'] != 0)
    {
        throw new Exception($GLOBALS['dismissHero']['only_dissmiss_free_hero']);
    }
    throwHeroToField($hero);
    
    updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid);
}            
function upgradeHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['upgradeHero']['cant_upgrade_this']);
    }   
    if ($hero["state"] > 1) //不在城池里
    {
        throw new Exception($GLOBALS['upgradeHero']['cant_upgrade_out_hero']);
    }
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    $total_exp = sql_fetch_one_cell("select total_exp from cfg_hero_level where level='$hero[level]'");
    $upgrade_exp = sql_fetch_one_cell("select upgrade_exp from cfg_hero_level where level=".($hero['level']+1));
    if (($hero['exp'] - $total_exp) >= $upgrade_exp)
    {
        sql_query("update sys_city_hero set level=level+1 where hid='$hid'");
    }
    else
    {
        throw new Exception($GLOBALS['upgradeHero']['no_enough_exp']);
    }
    regenerateHeroAttri($uid,$hid);
    completeTask($uid,86);
    updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid);
}

function addHeroPoint($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $affairs = array_shift($param);
    $bravery = array_shift($param);
    $wisdom = array_shift($param);
    
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['addHeroPoint']['cant_find_hero']);
    }   
    if ($hero["state"] > 1) //不在城池里
    {
        throw new Exception($GLOBALS['addHeroPoint']['cant_add_out_hero']);
    }
    if ($affairs + $bravery + $wisdom <= $hero['affairs_base'] + $hero['bravery_base'] + $hero['wisdom_base'] +$hero["level"])
    {
    	$affairs_add = $affairs - $hero['affairs_base'];
        $bravery_add = $bravery - $hero['bravery_base'];
        $wisdom_add  = $wisdom  - $hero['wisdom_base'];
        sql_query("update sys_city_hero set affairs_add='$affairs_add',bravery_add='$bravery_add',wisdom_add='$wisdom_add' where hid='$hid'");
    	regenerateHeroAttri($uid,$hid);
        updateCityHeroChange($uid,$cid);
        $chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
        if ($chiefhid == $hid)
        {
            updateCityChiefResAdd($cid,$hid);
        }
        completeTask($uid,87);
    }
    else
    {
        throw new Exception($GLOBALS['addHeroPoint']['no_extra_potential']);
    }
    return getCityInfoHero($uid,$cid);
}
function clearHeroPoint($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);   
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['clearHeroPoint']['cant_find_hero']);
    }   
    if ($hero["state"] > 1) //不在城池里
    {
        throw new Exception($GLOBALS['clearHeroPoint']['cant_clean_out_hero']);
    }
    useXiShuiDan($uid,$hid);
    regenerateHeroAttri($uid,$hid);
    updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid);
}
function changeHeroName($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $newName = trim(array_shift($param));
   	if (mb_strlen($newName,"utf-8") > 4)
    {
        throw new Exception($GLOBALS['changeHeroName']['name_too_long']);
    }
    else if ((!(strpos($newName,'\'')===false))||(!(strpos($newName,'\\')===false)))
    {
    	throw new Exception($GLOBALS['changeHeroName']['invalid_char']);
    }
    else if (strlen($newName) == 0)
    {
        throw new Exception($GLOBALS['changeHeroName']['input_valid_name']);
    }
    $lowername = strtolower($newName);
    if (sql_check("select * from cfg_baned_name where instr('$lowername',`name`)>0"))
    {
        throw new Exception($GLOBALS['changeHeroName']['invalid_char']);        
    }
    $newName=addslashes($newName);
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['changeHeroName']['cant_find_hero']);
    }   
    if ($hero["state"] > 1) //不在城池里
    {
        throw new Exception($GLOBALS['changeHeroName']['cant_change_out_hero']);
    }
    if ($hero['npcid'] != 0)
    {
        throw new Exception($GLOBALS['changeHeroName']['cant_change_famous_hero']);
    }
    
    sql_query("update sys_city_hero set name='$newName' where hid='$hid'");
    
    return getCityInfoHero($uid,$cid);
}
function largessHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param); 
    $typeidx = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where cid='$cid' and uid='$uid' and hid='$hid'");
    if (empty($hero))
    {
        throw new Exception($GLOBALS['largessHero']['cant_find_hero']);
    }   
    if ($hero["state"] > 1) //不在城池里
    {
        throw new Exception($GLOBALS['largessHero']['cant_largess_out_hero']);
    }
    $now = sql_fetch_one_cell("select unix_timestamp()");
    $last_largess = sql_fetch_one_cell("select last_largess from mem_hero_schedule where hid='$hid'");
    if ($now - $last_largess < 900) //15分钟内不能再次赏赐
    {
        $delta =900-($now - $last_largess);
        
        $msg = sprintf($GLOBALS['largessHero']['wait_duration'],MakeTimeLeft($delta));
        throw new Exception($msg);
    }
    if ($typeidx == 0)  //赏赐黄金
    {
        $city_gold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
        $salary=$hero['level']*20+(max($hero['affairs_base']+$hero['affairs_add']-90,0)+max($hero['bravery_base']+$hero['bravery_add']-90,0)+max($hero['wisdom_base']+$hero['wisdom_add']-90,0))*50;
		$largess_gold=$salary*5;
        if ($city_gold < $largess_gold)
        {
            throw new Exception($GLOBALS['largessHero']['no_enough_gold']);
        }
        if($salary<3500)	$loyaltyadd=mt_rand(1,20);
        else if($salary<6500) $loyaltyadd=mt_rand(1,10);
        else if ($hero['loyalty']<30) $loyaltyadd=mt_rand(1,5);
        else throw new Exception($GLOBALS['largessHero']['no_need_gold']);
        sql_query("update mem_city_resource set gold=gold-'$largess_gold' where cid='$cid'");
        sql_query("update sys_city_hero set loyalty=LEAST(100,loyalty+'$loyaltyadd') where hid='$hid'");
    }
    else if ($typeidx < 10)     //珍珠等
    {
        $gid = $typeidx + 29;
        $my_goods_count = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'");
        if (empty($my_goods_count)||($my_goods_count <= 0)) throw new Exception($GLOBALS['largessHero']['no_this_prop']);
        
        addGoods($uid,$gid,-1,7);
        $GOODS_LOYALTY_ADD = array(5,10,15,20,25,30,40,50,60);
        $loyaltyadd = $GOODS_LOYALTY_ADD[$typeidx-1];
        sql_query("update sys_city_hero set loyalty=LEAST(100,loyalty+$loyaltyadd) where hid='$hid'");
    }
    else if ($typeidx < 14) //虎符,文曲星符，武曲星符，智多星符
    {   
        $gid = $typeidx - 10 + 26;
        
        $hufu = sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$gid'");
        if (empty($hufu)||($hufu <= 0)) throw new Exception($GLOBALS['largessHero']['no_this_prop']);              
        addGoods($uid,$gid,-1,7);
        $buftype = $typeidx - 10 + 1;
        sql_query("insert into mem_hero_buffer (hid,buftype,endtime) values ('$hid','$buftype',unix_timestamp()+86400) on duplicate key update endtime=GREATEST(endtime,unix_timestamp())+86400");
        if($typeidx<12) //如果是虎符或文曲星，而且将领是城守的话，要重新算资源加成
        {
	        $chiefhid=sql_fetch_one_cell("select chiefhid from sys_city where cid='$cid'");
	        if(!empty($chiefhid)&&$chiefhid==$hid)
	        {
	        	sql_query("update sys_city_res_add set resource_changing=1 where cid='$cid'");
	        }
        }
        regenerateHeroAttri($uid,$hid);
    }
    sql_query("replace into mem_hero_schedule (hid,last_largess) values ('$hid','$now')");
    return getCityInfoHero($uid,$cid);
}
function releaseHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['releaseHero']['hero_not_exist']);
    if ($hero['state'] != 5) throw new Exception($GLOBALS['releaseHero']['hero_not_captive']);

    throwHeroToField($hero);
    sql_query("delete from mem_hero_summon where hid='$hid'");
    sql_query("delete from sys_hero_captive where hid='$hid'");
    //updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid); 
}

function rejectHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['releaseHero']['hero_not_exist']);
    if ($hero['state'] != 6) throw new Exception($GLOBALS['releaseHero']['hero_not_coming']);

    throwHeroToField($hero);
    //updateCityHeroChange($uid,$cid);
    sql_query("delete from mem_hero_summon where hid='$hid'");
    return getCityInfoHero($uid,$cid); 
}

function getNpcIntroduce($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['getNpcIntroduce']['no_hero_info']);
    if ($hero['npcid'] == 0) throw new Exception($GLOBALS['getNpcIntroduce']['not_famous_hero']);
    $npchero = sql_fetch_one("select * from cfg_npc_hero where npcid='$hero[npcid]'");
    if (empty($npchero)) throw new Exception($GLOBALS['getNpcIntroduce']['not_famous_hero']);
    $ret = array();
    $ret[] = $npchero;
    return $ret;  
}

function makeHeroSummonNeed($hid,$level)
{
    //生成需求
    $typeCount = 1 + floor(($level - 50) / 10);//生成几种道具
    $totalValue = $level*$level/10;
    $goodsArray = sql_fetch_rows("select * from cfg_goods where gid >= 30 and gid <= 38");
    $goodsCount=count($goodsArray);
    if($typeCount>$goodsCount) $typeCount=$goodsCount;
    while($typeCount > 0&&$totalValue>0)
    {
        $typeCount--;
    	$goodsCount=count($goodsArray);
    	$idx=mt_rand(0,$goodsCount-1);
        $goods=$goodsArray[$idx];
        $cnt = ceil($totalValue / $goods['value']);
        if ($typeCount == 0)    //最后一个了
        {
            $real_cnt = $cnt;
        }
        else
        {
            $real_cnt = mt_rand(1,$cnt);
            $totalValue -= $real_cnt * $goods['value'];
        }
        sql_query("insert into mem_hero_summon (hid,gid,name,count) values ('$hid','$goods[gid]','$goods[name]',$real_cnt)");
        array_splice($goodsArray,$idx,1);
    }
}

function getHeroSummonGold($hero)
{
	
	return ($hero['level']*20+(max($hero['affairs_base']+$hero['affairs_add']-90,0)+max($hero['bravery_base']+$hero['bravery_add']-90,0)+max($hero['wisdom_base']+$hero['wisdom_add']-90,0))*50)*50;
	/*	
    //黄金需求
    if ($hero['npcid'] > 0) 
    {
        return $hero['level'] * 100;
    }
    else
    {
        return $hero['level'] * 20;
    }*/
}

function getSummonNeed($hero)
{
	$hid=$hero['hid'];
	$need = $GLOBALS['trySummonHero']['hero_need'];
    $need .= $GLOBALS['trySummonHero']['gold'].getHeroSummonGold($hero);
    //宝物需求
    
    if ($hero['level'] >= 50)
    {              
        $goods = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");      
        if (count($goods) == 0)
        {
            makeHeroSummonNeed($hid,$hero['level']);
            $goods = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
        }
        foreach($goods as $good)
        {
            $need .= "，".$good['name'].$good['count'];
        }
    }
    $need .= "。";
    return $need;
}

function tryAcceptHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);     
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['trySummonHero']['no_hero_info']);
    if ($hero['state'] != 6) 
    {
    	throw new Exception($hero['name'].$GLOBALS['tryAcceptHero']['hero_not_coming'] );
    }
    $need=getSummonNeed($hero);
    $ret = array();
    $ret[] = $hid;
    $ret[] = $need;
    return $ret;
}

function sureAcceptHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)||($hero['cid'] != $cid)) throw new Exception($GLOBALS['sureSummonHero']['hero_not_exist']);
    if ($hero['state'] != 6) throw new Exception($GLOBALS['tryAcceptHero']['hero_not_coming']);

    $gold_need = getHeroSummonGold($hero); 
    $mygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
    if ($mygold < $gold_need) throw new Exception($GLOBALS['sureSummonHero']['no_enough_gold']);   
    $goodsList = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
    if (count($goodsList) > 0)
    {
        foreach($goodsList as $goods)
        {
            if (!sql_check("select * from sys_goods where uid='$uid' and gid='$goods[gid]' and count >= $goods[count]")) 
            {
            	$msg = sprintf($GLOBALS['sureSummonHero']['no_enough_goods'],$goods['name'],$hero['name']);
            	throw new Exception($msg);
            }
        }
        //有足够的宝物的话，开始扣
        foreach($goodsList as $goods)
        {        
            addGoods($uid,$goods['gid'],-$goods['count'],7);
        }
        //清招降要求
        sql_query("delete from mem_hero_summon where hid='$hid'");
    }
    //扣黄金
    addCityResources($cid,0,0,0,0,-$gold_need);
    
    //招人
    sql_query("update sys_city_hero set state=0,loyalty=80 where hid='$hid'");
	updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid);
}

function trySummonHero($uid,$param)
{
	$cid=array_shift($param);
    $hid = array_shift($param);     
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['trySummonHero']['no_hero_info']);
    if ($hero['state'] != 5) 
    {
    	throw new Exception($hero['name'].$GLOBALS['trySummonHero']['hero_not_captive']);
    }
    $nobility_need = floor(($hero['level'] - 1)/10);
    $mynobility = sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");    
    if ($mynobility < $nobility_need)
    {
        $nobility_name = sql_fetch_one_cell("select name from cfg_nobility where id='$nobility_need'");
        
        $msg = sprintf($GLOBALS['trySummonHero']['no_enough_nobility'],$nobility_name);
        //throw new Exception("我的主公，必定是威震天下的英雄，你连\"".$nobility_name."\"都没有达到，我是不会跟随你的。");
        throw new Exception($msg);
    }
    $need=getSummonNeed($hero);
    $ret = array();
    $ret[] = $hid;
    $ret[] = $need;
    return $ret;
}
function sureSummonHero($uid,$param)
{   
	$cid=array_shift($param);
    $hid = array_shift($param);
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)||($hero['cid'] != $cid)) throw new Exception($GLOBALS['sureSummonHero']['hero_not_exist']);
    if ($hero['state'] != 5) throw new Exception($GLOBALS['sureSummonHero']['hero_not_captive']);

    $gold_need = getHeroSummonGold($hero); 
    $mygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
    if ($mygold < $gold_need) throw new Exception($GLOBALS['sureSummonHero']['no_enough_gold']);   
    $goodsList = sql_fetch_rows("select * from mem_hero_summon where hid='$hid'");
    if (count($goodsList) > 0)
    {
        foreach($goodsList as $goods)
        {
            if (!sql_check("select * from sys_goods where uid='$uid' and gid='$goods[gid]' and count >= $goods[count]")) 
            {
            	$msg = sprintf($GLOBALS['sureSummonHero']['no_enough_goods'],$goods['name'],$hero['name']);
            	//throw new Exception("你没有足够的".$goods['name']."，不能招降".$hero['name']."。");
            	throw new Exception($msg);
            }
        }
        //有足够的宝物的话，开始扣
        foreach($goodsList as $goods)
        {        
            addGoods($uid,$goods['gid'],-$goods['count'],7);
        }
        //清招降要求
        sql_query("delete from mem_hero_summon where hid='$hid'");
    }
    //扣黄金
    addCityResources($cid,0,0,0,0,-$gold_need);
    
    //招人
    sql_query("update sys_city_hero set state=0,loyalty=50,uid='$uid' where hid='$hid'");
    //清除俘虏记录
    sql_query("delete from sys_hero_captive where hid='$hid'");
    if($hero['npcid']>0)
    {
    	$taskid=30000+$hero['npcid'];
    	$goalid=sql_fetch_one_cell("select id from cfg_task_goal where tid=$taskid");
    	completeTask($uid,$goalid);
    }
    updateCityHeroChange($uid,$cid);
    return getCityInfoHero($uid,$cid);
}

function regenerateHeroAttri($uid,$hid)
{
	$hero=sql_fetch_one("select * from sys_city_hero where hid='$hid'");
	$armors=sql_fetch_rows("select * from sys_user_armor u,sys_hero_armor h left join cfg_armor c on c.id=h.armorid where u.uid='$uid' and u.hid='$hid' and h.sid=u.sid and u.hp>0");
	
	$level=$hero['level'];
	$command=$hero['level']+$hero['command_base'];
	$affairs=$hero['affairs_base']+$hero['affairs_add'];
	$bravery=$hero['bravery_base']+$hero['bravery_add'];
	$wisdom=$hero['wisdom_base']+$hero['wisdom_add'];
	
	if(sql_check("select * from mem_hero_buffer where hid='$hid' and buftype=3 and endtime>unix_timestamp()"))
	{
		$bravery=floor($bravery*1.25);
	}
	if(sql_check("select * from mem_hero_buffer where hid='$hid' and buftype=4 and endtime>unix_timestamp()"))
	{
		$wisdom=floor($wisdom*1.25);
	}
	
	$commandAdd=0;
	$forceAdd=0;
	$energyAdd=0;
	$affairsAdd=0;
	$braveryAdd=0;
	$wisdomAdd=0;
	$speedAdd=0;
	$attackAdd=0;
	$defenceAdd=0;
	
	foreach($armors as $armor)
	{
		$attributes=explode(",",$armor['attribute']);
		$attriCount=count($attributes);
		if($attriCount==0||$attributes[0]*2+1!=$attriCount) continue;
		for($i=1;$i<$attriCount;$i=$i+2)
		{
			$type=$attributes[$i];
			if($type==1) $commandAdd=$commandAdd+$attributes[$i+1];
			else if($type==2) $affairsAdd=$affairsAdd+$attributes[$i+1];
			else if($type==3) $braveryAdd=$braveryAdd+$attributes[$i+1];
			else if($type==4) $wisdomAdd=$wisdomAdd+$attributes[$i+1];
			else if($type==5) $forceAdd=$forceAdd+$attributes[$i+1];
			else if($type==6) $energyAdd=$energyAdd+$attributes[$i+1];
			else if($type==8) $attackAdd=$attackAdd+$attributes[$i+1];
			else if($type==9) $defenceAdd=$defenceAdd+$attributes[$i+1];
			else if($type==11) $speedAdd=$speedAdd+$attributes[$i+1];
		}
	}
	
	$forcemax=100+floor($level/5)+floor(($bravery+$braveryAdd)/3)+$forceAdd;
	$energymax=100+floor($level/5)+floor(($wisdom+$wisdomAdd)/3)+$energyAdd;

	$sql="update sys_city_hero set command_add_on=$commandAdd, affairs_add_on=$affairsAdd, bravery_add_on=$braveryAdd, wisdom_add_on=$wisdomAdd";
	$sql=$sql.",force_max_add_on=$forceAdd,energy_max_add_on=$energyAdd,speed_add_on=$speedAdd,attack_add_on=$attackAdd,defence_add_on=$defenceAdd where hid='$hid'";
	sql_query($sql);
	sql_query("update mem_hero_blood set force_max=$forcemax, energy_max=$energymax,`force`=LEAST(`force`,$forcemax),`energy`=LEAST(`energy`,$energymax) where hid='$hid'");
	
}


?>