<?php
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");

define("XUANSHANGLING_ID",141);

//检查并重载姓名表
function reloadNameTable($tableName,$fileName)
{
	if (0 == sql_fetch_one_cell("select count(*) from $tableName"))
	{
		$lines = file("../data/$fileName");
		$sql = "insert into $tableName values ";
		$id = 1;
		foreach($lines as $line)
		{
			$line = substr($line,0,strlen($line) - 2);
			if (strlen($line) > 0)
			{
				$sql .= "('".$id."','".$line."'),";
				$id++;
			}
		}
		$sql = substr($sql,0,strlen($sql)-1);
		sql_query($sql);
	}
}

function generateName($tableName)
{
	$cnt = sql_fetch_one_cell("select count(*) from $tableName");
	$id = mt_rand(1,$cnt);
	return sql_fetch_one_cell("select `name` from $tableName where `id`='$id'");
}
function generateHeroName($sex)
{
	if ($sex == 0)	//girl
	{
		return generateName("mem_cfg_firstname").generateName("mem_cfg_girlname");
	}
	else
	{
		return generateName("mem_cfg_firstname").generateName("mem_cfg_boyname");
	}
}
function generateBaiYueHero($cid,$level)
{
	$heroname = array('司马贤妃','诸葛贤妃','完颜贤妃','宇文贤妃','欧阳贤妃','呼延贤妃','上官贤妃','西门贤妃','司徒贤妃','太史贤妃','夏侯贤妃','长孙贤妃');
    //生成一个随机性别
    $sex = 0;//10分之一的机率
    //生成将领姓名
    $htype=mt_rand(0,100);
    if($htype<30) $idx=mt_rand(6,11);
    else $idx=mt_rand(0,5);
    $herotype=15+$idx;
    $name = $heroname[$idx];
    //男人有859个头像，女人有105个头像 
    $face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);
    //生成1到客栈级别*5的英雄   
    $hlevel = mt_rand(1,$level * 5);
    
    //生成三项基本属性的比值
    $affairs_rate = mt_rand(300,900);
    $bravery_rate = mt_rand(300,900);
    $wisdom_rate = mt_rand(300,900);
    
    $all_rate = $affairs_rate + $bravery_rate + $wisdom_rate;
    
    $hero_level_info = sql_fetch_one("select * from cfg_hero_level where level='$hlevel'");
    if (empty($hero_level_info)) throw new Exception($GLOBALS['generateRecruitHero']['no_data_of_this_level']);
    
    
    $all_base = rand(50,150);
    $affairs_base = floor($all_base * $affairs_rate / $all_rate);
    $bravery_base = floor($all_base * $bravery_rate / $all_rate);
    $wisdom_base  = floor($all_base * $wisdom_rate  / $all_rate);
    
    $affairs_add = round($hlevel * $affairs_rate / $all_rate);
    $bravery_add = round($hlevel * $bravery_rate / $all_rate);
    $wisdom_add  = $hlevel - $affairs_add - $bravery_add;
    
    $hero_exp = $hero_level_info['total_exp'];
    
    //忠诚度默认70
    $loyalty = 70;
    //需要黄金＝等级*1000
    //$gold_need = $hlevel * 1000;
    $gold_need=($hlevel*20+(max($affairs_base+$affairs_add-90,0)+max($bravery_base+$bravery_add-90,0)+max($wisdom_base+$wisdom_add-90,0))*50)*50;
    $sql = "insert into sys_recruit_hero (`name`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`gold_need`,`gen_time`,`herotype`) values ('$name','$sex','$face','$cid','$hlevel','$hero_exp','$affairs_base','$bravery_base','$wisdom_base','$affairs_add','$bravery_add','$wisdom_add','$loyalty','$gold_need',unix_timestamp(),'$herotype')";
    sql_query($sql);
}
/**
 * 从cfg_recruit_hero表中的配置来生成招募英雄，一般对应 客栈招将领活动
 * 
 * return false:表示没有触发招募将领
 *        true 招募到了
 */
function generateHeroFromConfig($cid,$level)
{
	$now =sql_fetch_one_cell("select unix_timestamp()");
	$tresult = sql_fetch_one("select * from cfg_act where $now>= starttime and $now <= endtime and type = 1 ");
	if (!$tresult)return false;
	$actid = $tresult["actid"];
	//$rate = $tresult["rate"];
	//if (mt_rand(1,100)>$rate) return false;
	
	$rows = sql_fetch_rows("select * from cfg_recruit_hero where actid = $actid");	
	$rateSum = 0 ;
    foreach($rows as $row) $rateSum+=$row["rate"];
    $rate=mt_rand(1,$rateSum);
    $hero =null; 
	foreach ($rows as $row) {
		$curRateSum+=$row["rate"];
    	if ($curRateSum>=$rate){
			$hero =$row;		
    		break;
    	}
	}
	
	$name=$hero["heroname"];
	$herotype=$hero["herotype"];
	$userhavecnt = $hero["userhavecnt"];
	//if ($userhavecnt>0 && $userhavecnt<=intval(sql_fetch_one_cell("select count(*) from sys_city_hero where cid='$cid' and herotype = $herotype"))) 
	//	return false;

	$affairs_base=mt_rand($hero["min_affairs_base"],$hero["max_affairs_base"]);
	$bravery_base=mt_rand($hero["min_bravery_base"],$hero["max_bravery_base"]);
	$wisdom_base=mt_rand($hero["min_wisdom_base"],$hero["max_wisdom_base"]);
	$loyalty=mt_rand($hero["min_loyalty"],$hero["max_loyalty"]);
	$face = mt_rand($hero["min_face"],$hero["max_face"]);
	$hlevel = $hero["level"];
	$sex = $hero["sex"];
    if ($hlevel<=0){//生成1到客栈级别*5的英雄    	   
	    $hlevel = mt_rand(1,$level * 5);    
    }
	$hero_level_info = sql_fetch_one("select * from cfg_hero_level where level='$hlevel'");
	if (empty($hero_level_info)) throw new Exception($GLOBALS['generateRecruitHero']['no_data_of_this_level']);
	if ($affairs_base==0&&$bravery_base==0&&$wisdom_base==0 ){ //属性都随机
	   	//生成一个随机性别
	    $sex = (mt_rand(0,9) == 0)?0:1;//10分之一的机率
	         	
	 
	    
	    //生成三项基本属性的比值
	    $affairs_rate = mt_rand(300,900);
	    $bravery_rate = mt_rand(300,900);
	    $wisdom_rate = mt_rand(300,900);	    
	    $all_rate = $affairs_rate + $bravery_rate + $wisdom_rate;
	    $all_base = rand(50,150);
	    $affairs_base = floor($all_base * $affairs_rate / $all_rate);
	    $bravery_base = floor($all_base * $bravery_rate / $all_rate);
	    $wisdom_base  = floor($all_base * $wisdom_rate  / $all_rate);
	    
	    $affairs_add = round($hlevel * $affairs_rate / $all_rate);
	    $bravery_add = round($hlevel * $bravery_rate / $all_rate);
	    $wisdom_add  = $hlevel - $affairs_add - $bravery_add;
	    	    
	    //忠诚度默认70
	    $loyalty = 70;
	    		
   }else{ //属性在一定范围内随机
   		$total=$affairs_base+$bravery_base+$wisdom_base;
		$affairs_add = round($hlevel * $affairs_base / $total);
		$bravery_add = round($hlevel * $bravery_base / $total);
		$wisdom_add = $hlevel - $affairs_add - $bravery_add;
   }
   if ($face ==0) //男人有859个头像，女人有105个头像 
		$face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);    
	    
	$hero_exp = $hero_level_info['total_exp'];
	$gold_need=($hlevel*20+(max($affairs_base+$affairs_add-90,0)+max($bravery_base+$bravery_add-90,0)+max($wisdom_base+$wisdom_add-90,0))*50)*50;
	$sql = "insert into sys_recruit_hero (`name`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`gold_need`,`gen_time`,`herotype`) values ('$name','$sex','$face','$cid','$hlevel','$hero_exp','$affairs_base','$bravery_base','$wisdom_base','$affairs_add','$bravery_add','$wisdom_add','$loyalty','$gold_need',unix_timestamp(),'$herotype')";
	sql_query($sql);
	return true;
}


function generateRecruitHero($cid,$level)
{
	//生成一个随机性别
	$sex = (mt_rand(0,9) == 0)?0:1;//10分之一的机率
	//生成将领姓名
	$name = generateHeroName($sex);
	//男人有859个头像，女人有105个头像 
	$face = ($sex==0)?mt_rand(1,9):mt_rand(1001,1070);
	//生成1到客栈级别*5的英雄   
	$hlevel = mt_rand(1,$level * 5);

	//生成三项基本属性的比值
	$affairs_rate = mt_rand(300,900);
	$bravery_rate = mt_rand(300,900);
	$wisdom_rate = mt_rand(300,900);

	$all_rate = $affairs_rate + $bravery_rate + $wisdom_rate;

	$hero_level_info = sql_fetch_one("select * from cfg_hero_level where level='$hlevel'");
	if (empty($hero_level_info)) throw new Exception($GLOBALS['generateRecruitHero']['no_data_of_this_level']);


	$all_base = rand(50,150);
	$affairs_base = floor($all_base * $affairs_rate / $all_rate);
	$bravery_base = floor($all_base * $bravery_rate / $all_rate);
	$wisdom_base  = floor($all_base * $wisdom_rate  / $all_rate);

	$affairs_add = round($hlevel * $affairs_rate / $all_rate);
	$bravery_add = round($hlevel * $bravery_rate / $all_rate);
	$wisdom_add  = $hlevel - $affairs_add - $bravery_add;

	$hero_exp = $hero_level_info['total_exp'];

	//忠诚度默认70
	$loyalty = 70;
	//需要黄金＝等级*1000
	//$gold_need = $hlevel * 1000;
	$gold_need=($hlevel*20+(max($affairs_base+$affairs_add-90,0)+max($bravery_base+$bravery_add-90,0)+max($wisdom_base+$wisdom_add-90,0))*50)*50;
	$sql = "insert into sys_recruit_hero (`name`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`gold_need`,`gen_time`) values ('$name','$sex','$face','$cid','$hlevel','$hero_exp','$affairs_base','$bravery_base','$wisdom_base','$affairs_add','$bravery_add','$wisdom_add','$loyalty','$gold_need',unix_timestamp())";
	sql_query($sql);
}
function regenerateRecruitHero($cid,$level)
{
	sql_query("delete from sys_recruit_hero where `cid`='$cid'");
	//将领生成算法
	//	reloadNameTable("mem_cfg_firstname","name_first.txt");
	//	reloadNameTable("mem_cfg_boyname","name_boy.txt");
	//	reloadNameTable("mem_cfg_girlname","name_girl.txt");
	for ($i = 0; $i < $level; $i++)
	{
		generateRecruitHero($cid,$level);
	}
}
function doGetRecruitHero($uid,$cid,$level)
{

	$ret = array();
	if ($level > 0)
	{
	    $last_reset_recruit = sql_fetch_one_cell("select last_reset_recruit from mem_city_schedule where cid='$cid'");

	    if (empty($last_reset_recruit))
	    {
	    	sql_query("insert into mem_city_schedule (cid,last_reset_recruit) values ('$cid',unix_timestamp()) on duplicate key update last_reset_recruit=unix_timestamp()");
	    	$last_reset_recruit = 0;
	    }
	    $now = sql_fetch_one_cell("select unix_timestamp()");
	    $blocksize = (10800/GAME_SPEED_RATE) / $level;
	    $last_block = floor(($last_reset_recruit+8*3600) / $blocksize);
	    $curr_block = floor(($now+8*3600) / $blocksize);
	    $blockdelta = $curr_block - $last_block;
	    if ($blockdelta > 0)
	    {
	        $oldheroes = sql_fetch_rows("select * from sys_recruit_hero where `cid`='$cid' order by id limit $blockdelta");  
	        foreach($oldheroes as $hero)
	        {
	            sql_query("delete from sys_recruit_hero where id=".$hero['id']);
	        }           
	        $heroCount = sql_fetch_one_cell("select count(*) from sys_recruit_hero where cid='$cid'");
	      	$now = sql_fetch_one_cell("select unix_timestamp()");
	      	$rate=5;
		
			$tresult = sql_fetch_one("select * from cfg_act where $now>= starttime and $now <= endtime and type = 1 ");	
	    	if($last_reset_recruit==0){
				$rate=$tresult["rate"];
			}
									
	        if ($tresult&&(mt_rand(1,100)<$rate))	
	        {
	        	$hasActHero=false;
	        	mt_srand(mt_rand());
	        	$idx=mt_rand($heroCount,$level-1);
	        	for ($i = $heroCount; $i < $level; $i++)
		        {
		        	if ((!$hasActHero)&&$i==$idx)
		        	{
		        		mt_srand(mt_rand());
		    			generateHeroFromConfig($cid,$level);
		        		$hasActHero = true;
		        	}
		        	else
		        	{
		            	generateRecruitHero($cid,$level);
		            }
		        }
	        }
	        else
	        {
		        for ($i = $heroCount; $i < $level; $i++)
		        {
		            generateRecruitHero($cid,$level);
		        }  
	        }
	        sql_query("update mem_city_schedule set last_reset_recruit=unix_timestamp() where cid='$cid'");      
	    }

	    $heroes = sql_fetch_rows("select * from sys_recruit_hero where `cid`='$cid' order by id desc");
		foreach($heroes as $hero)
		{
			$recruit = new HeroRecruit();
			$recruit->id = $hero['id'];
			$recruit->hname = $hero['name'];
			$recruit->sex = (int)$hero['sex'];
			$recruit->face = (int)$hero['face'];
			$recruit->cid = $hero['cid'];
			$recruit->level = $hero['level'];
			$recruit->affairs_base = $hero['affairs_base'];
			$recruit->bravery_base = $hero['bravery_base'];
			$recruit->wisdom_base = $hero['wisdom_base'];
			$recruit->affairs_add = $hero['affairs_add'];
			$recruit->bravery_add = $hero['bravery_add'];
			$recruit->wisdom_add = $hero['wisdom_add'];
			$recruit->loyalty = $hero['loyalty'];
			$recruit->gold_need = $hero['gold_need'];
			$ret[] = $recruit;
		}
    }
	return $ret;
}
function getHotelInfo($uid,$cid)
{
	//在这里做一个手脚，在玩家取客栈信息的时候，自动补齐一个野兵的将领
	$npcHeroCount = sql_fetch_one_cell("select count(*) from sys_recruit_hero where `cid`=0");
	if ($npcHeroCount < 1000)
	{
		mt_srand(time());
		generateRecruitHero(0,5);
	}


	$hotel = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_HOTEL." order by level desc limit 1");
	if (empty($hotel))
	{
		throw new Exception($GLOBALS['getHotelInfo']['no_hotel_built']);
	}
	return doGetBuildingInfo($uid,$cid,$hotel['xy'],ID_BUILDING_HOTEL,$hotel['level']);
}

//开始招人
function recruitHero($uid,$cid,$param)
{
	$id = array_shift($param);
    $tmpHero = sql_fetch_one("select * from sys_recruit_hero where `id`='$id'");
    if (!empty($tmpHero))
    {                             
        if (cityHasHeroPosition($uid,$cid))
        {
            $citygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
            if ($tmpHero['gold_need'] > $citygold)
            {
                throw new Exception($GLOBALS['recruitHero']['no_enough_gold']);
            }
            
            if($tmpHero[herotype]>10){//活动将领
            	$hero=sql_fetch_one("select * from cfg_recruit_hero where herotype = '$tmpHero[herotype]'");
            	$userhavecnt = $hero["userhavecnt"];
            	if ($userhavecnt>0 && $userhavecnt<=intval(sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and herotype = '$tmpHero[herotype]'"))) {
            		throw new Exception(sprintf($GLOBALS['recruitHero']['already_Have_One'],$tmpHero["name"]));
            	}
            }
            
            //花钱
            addCityResources($cid,0,0,0,0,-$tmpHero['gold_need']);
            //招人
            $sql = "insert into sys_city_hero (`uid`,`name`,`sex`,`face`,`cid`,`state`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`herotype`) values ('$uid','$tmpHero[name]','$tmpHero[sex]','$tmpHero[face]','$cid','0','$tmpHero[level]','$tmpHero[exp]','$tmpHero[affairs_base]','$tmpHero[bravery_base]','$tmpHero[wisdom_base]','$tmpHero[affairs_add]','$tmpHero[bravery_add]','$tmpHero[wisdom_add]','$tmpHero[loyalty]','$tmpHero[herotype]')";
            $hid = sql_insert($sql);
            $forcemax=100+floor($tmpHero['level']/5)+floor(($tmpHero['bravery_base']+$tmpHero['bravery_add'])/3);
            $energymax=100+floor($tmpHero['level']/5)+floor(($tmpHero['wisdom_base']+$tmpHero['wisdom_add'])/3);
            sql_query("insert into mem_hero_blood (`hid`,`force`,`force_max`,`energy`,`energy_max`) values ('$hid',100,$forcemax,100,$energymax)");
            if($tmpHero['herotype']>10)	//客栈招募将领，招募的时候获得相应任务物品
            {
            	openDefaultBox($uid,$cid,$tmpHero['herotype'],2);
            }
            //砍人
            sql_query("delete from sys_recruit_hero where id='$id'");
            updateCityHeroChange($uid,$cid);  
            completeTask($uid,84);
        }
        else
        {
            throw new Exception($GLOBALS['recruitHero']['hotel_level_low']);
        }
    }
	return getHotelInfo($uid,$cid);          
}                        
function resetRecruitHero($uid,$cid,$param)
{
	useZhaoXinLin($uid,$cid);
	return getHotelInfo($uid,$cid);
}
function addHeroRumor($cid,$npcid,$price)
{
	$heroname = sql_fetch_one_cell("select name from cfg_npc_hero where npcid='$npcid'");
	sql_query("insert into sys_city_rumor (cid,name,type,typeid,price) values ('$cid','$heroname',0,$npcid,$price)");
}
function generateHeroRumor($cid)
{

	$cityHeroRumors = sql_fetch_one_cell("select group_concat(typeid) from sys_city_rumor where cid='$cid' and type=0");
	if (empty($cityHeroRumors))
	{
		$filter = "";
	}
	else
	{
		$filter = "where npcid not in ($cityHeroRumors) ";
	}
	$rumor = sql_fetch_one("select * from cfg_rumor_hero $filter order by rand() limit 1");
	addHeroRumor($cid,$rumor['npcid'],$rumor['price']);
}
function addThingRumor($cid,$tid,$price)
{
	$thingname = sql_fetch_one_cell("select name from cfg_things where tid='$tid'");
	sql_query("insert into sys_city_rumor (cid,name,type,typeid,price) values ('$cid','$thingname',1,$tid,$price)");
}
function generateThingRumor($cid)
{

	$cityThingRumors = sql_fetch_one_cell("select group_concat(typeid) from sys_city_rumor where cid='$cid' and type=1");

	if (empty($cityThingRumors))
	{
		$filter = "";
	}
	else
	{
		$filter = "where tid not in ($cityThingRumors) ";
	}
	$rumor = sql_fetch_one("select * from cfg_rumor_thing $filter order by rand() limit 1");
	if (empty($rumor)) return false;
	addThingRumor($cid,$rumor['tid'],$rumor['price']);
	return true;
}
//市井传闻
function getRumorList($uid,$cid,$param)
{
	$hotellevel = sql_fetch_one_cell("select b.level from sys_building b,sys_user u where b.cid=u.lastcid and b.bid=".ID_BUILDING_HOTEL." and u.uid='$uid' limit 1");
	if($hotellevel<5)
	{
		throw new Exception($GLOBALS['getRumor']['hotel_level_low']);
	}
	else
	{
		$last_reset_rumor = sql_fetch_one_cell("select last_reset_rumor from mem_city_schedule where cid='$cid'");
		if (empty($last_reset_rumor))
		{
			sql_query("insert into mem_city_schedule (cid,last_reset_rumor) values ('$cid',unix_timestamp()) on duplicate key update last_reset_rumor=unix_timestamp()");
			$last_reset_rumor = 0;
		}
		$now = sql_fetch_one_cell("select unix_timestamp()");
		$blocksize = (3600/GAME_SPEED_RATE) / $hotellevel;
		$last_block = floor(($last_reset_rumor+8*3600) / $blocksize);
		$curr_block = floor(($now+8*3600) / $blocksize);
		$blockdelta = $curr_block - $last_block;
		if ($blockdelta > 0)
		{
			sql_query("delete from sys_city_rumor where cid='$cid' order by id limit $blockdelta");
			$rumorCount = sql_fetch_one_cell("select count(*) from sys_city_rumor where cid='$cid'");


			for ($i = $rumorCount; $i < $hotellevel; $i++)
			{
				//if (mt_rand() & 1) //武将
				{
					generateHeroRumor($cid);
				}
				/*else
				 {
				 if (!generateThingRumor($cid))      //如果任务物品没有新的话，就再放一个将领
				 {
				 generateHeroRumor($cid);
				 }
				 }*/
			}
			sql_query("update mem_city_schedule set last_reset_rumor=unix_timestamp() where cid='$cid'");
		}
	}

	$rumors = sql_fetch_rows("select * from sys_city_rumor where `cid`='$cid' order by id desc");
	/*
	 foreach($rumors as &$rumor)
	 {
	 if ($rumor['type'] == 0)    //武将消息
	 {
	 $rumor['intro'] = sql_fetch_one_cell("select introduce from cfg_npc_hero where id='$rumor[typeid]'");
	 }
	 else if ($rumor['type'] == 1) //任务物品
	 {
	 $rumor['intro'] = sql_fetch_one_cell("select description from cfg_things where id='$rumor[typeid]'");
	 }
	 }
	 */
	return $rumors;
}
function getRumor($uid,$cid,$param)
{
	$id = array_shift($param);
	$rumor = sql_fetch_one("select * from sys_city_rumor where id='$id'");
	if (empty($rumor)) throw new Exception($GLOBALS['getRumor']['never_heard']);
	$ret = array();
	$ret[] = $rumor;
	if ($rumor['type'] == 0)  //将领
	{
		$ret[] = sql_fetch_one("select * from cfg_npc_hero where npcid='$rumor[typeid]'");
		$hero  = sql_fetch_one("select * from sys_city_hero where npcid='$rumor[typeid]'");		
		if (empty($hero))
		{
			$ret[] = $GLOBALS['getRumor']['dont_know_where_he_is'];
			$ret[] = false;     //是否有详细信息
		}else{
			$herowid = cid2wid($hero['cid']);
			$provincename = sql_fetch_one_cell("select p.name from mem_world w,cfg_province p where w.province=p.id and w.wid='$herowid'");

			$msg = sprintf($GLOBALS['getRumor']['pay_for_hero'],$hero['name'],$provincename,$rumor['price']);
			$ret[] = $msg;
			$ret[] = true;   //是否有详细信息
		}
		//$ret[] = sql_check("select * from cfg_npc_task where npcid='$rumor[typeid]'");
		$ret[]=true;
	}
	else if ($rumor['type'] == 1)   //任务物品
	{
		$rumorthing = sql_fetch_one("select f.*,r.type,r.price,r.introduce from cfg_rumor_thing r,cfg_things f where r.tid='$rumor[typeid]' and f.tid=r.tid");
		$ret[] = $rumorthing;
		if ($rumorthing['type'] == 0)   //一般物品
		{
			$ret[] = "";
			$ret[] = false; //打听详细    
		}
		else if ($rumorthing['type'] == 1)  //有地点的物品
		{
			$thingcid = sql_fetch_one_cell("select cid from sys_thing_position where thingid=".$rumorthing['tid']);
			if (empty($thingcid))
			{
				$ret[] = $GLOBALS['getRumor']['dont_know_where_it_is'];
				$ret[] = false; //打听详细
			}
			else
			{
				$thingwid = cid2wid($thingcid);
				$provincename = sql_fetch_one_cell("select p.name from mem_world w,cfg_province p where w.province=p.id and w.wid='$thingwid'");

				$msg = sprintf($GLOBALS['getRumor']['pay_for_staff'],$rumorthing['name'],$provincename,$rumorthing['price']);
				//$ret[] = "听说，".$rumorthing['name']."在".$provincename."。如果你给我".$rumorthing['price']."个元宝，我就告诉你更准确的情报。";  
				$ret[] = $msg;
				$ret[] = true; //打听详细
			}
		}
		else if ($rumorthing['type'] == 2)  //特殊说明的物品
		{
			$ret[] = $rumorthing['introduce'];
			$ret[] = true;
		}
		//$ret[] = sql_check("select * from cfg_thing_task where thingid='$rumorthing[tid]'"); //任务 
		$ret[]=false;
	}
	return $ret;
}
function searchRumor($uid,$cid,$param)
{
	$input = addslashes(trim(array_shift($param)));
	if (empty($input)) throw new Exception($GLOBALS['searchRumor']['input_name_to_seartch']);
	$hotellevel = sql_fetch_one_cell("select level from sys_building where cid=$cid and bid=".ID_BUILDING_HOTEL." limit 1");
	if ($hotellevel <= 0) throw new Exception($GLOBALS['searchRumor']['no_hotel_built']);
	//判断城池黄金
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	
	$citygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	if ($citygold < 1000) throw new Exception($GLOBALS['searchRumor']['no_enough_gold']);

	$rumorHero = sql_fetch_rows("select * from cfg_rumor_hero r,cfg_npc_hero n where r.npcid=n.npcid and n.name like '%$input%' order by rand() limit $hotellevel");

	$rumorThing = array();
	/*if (count($rumorHero) < $hotellevel)
	 {
	 $delta = $hotellevel - count($rumorHero);
	 $rumorThing = sql_fetch_rows("select * from cfg_rumor_thing r,cfg_things t where r.tid=t.tid and t.name like '%$input%' order by rand() limit $delta");
	 }*/

	$newRumorCount = count($rumorHero) + count($rumorThing);
	if ($newRumorCount <= 0)
	{
		throw new Exception($GLOBALS['searchRumor']['no_useful_info']);
	}
	sql_query("delete from sys_city_rumor where cid='$cid' order by id limit $newRumorCount");
	foreach($rumorHero as $rumor)
	{
		addHeroRumor($cid,$rumor['npcid'],$rumor['price']);
	}
	foreach($rumorThing as $rumor)
	{
		addThingRumor($cid,$rumor['tid'],$rumor['price']);
	}
	sql_query("update mem_city_schedule set last_reset_rumor=unix_timestamp() where cid='$cid'");
	sql_query("update mem_city_resource set gold=gold-1000 where cid='$cid'");
	unlockUser($uid);
	return sql_fetch_rows("select * from sys_city_rumor where `cid`='$cid' order by id desc");
}
function moreRumor($uid,$cid,$param)
{
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	$citygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
	if ($citygold < 100) throw new Exception($GLOBALS['moreRumor']['no_enough_gold']);
	sql_query("update mem_city_resource set gold=gold-100 where cid='$cid'");
	sql_query("update mem_city_schedule set last_reset_rumor=0 where cid='$cid'");
	unlockUser($uid);
	return getRumorList($uid,$cid,$param);
}
function getPosDescription($cid)
{
	$ret = "";
	$worldtype = sql_fetch_one_cell("select type from mem_world where wid=".cid2wid($cid));
	if ($worldtype == 0)
	{
		$ret .= sql_fetch_one_cell("select name from sys_city where cid='$cid'");
	}
	else
	{
		$ret .= sql_fetch_one_cell("select name from cfg_world_type where type='$worldtype'");
	}

	$ret .= "[".($cid % 1000).",".floor($cid/1000)."]";
	return $ret;
}
function askDetail($uid,$cid,$param)
{
	$id = array_shift($param);
	$paytype=array_shift($param);
	if($paytype!=0&&$paytype!=1){
		throw new Exception($GLOBALS['buyGoods']['invalid_pay_type']);
	}
	
	$rumor = sql_fetch_one("select * from sys_city_rumor where id='$id'");
	if (empty($rumor)) throw new Exception($GLOBALS['askDetail']['never_heard']);
	$ret=array();
	$ret[] = $rumor;
	if(!lockUser($uid)) throw new Exception($GLOBALS['pacifyPeople']['server_busy']);
	
	
	$userInfo = sql_fetch_one("select money,gift from sys_user where uid='$uid'");
	
	
	//元宝
	$userMoney=$userInfo['money'];
	//礼金
	$userGift=$userInfo['gift'];
	//if ($money < $rumor['price']) throw new Exception($GLOBALS['askDetail']['no_enough_YuanBao']);
	
	if ($paytype==0&&($userMoney < $rumor['price']))	throw new Exception($GLOBALS['askDetail']['no_enough_YuanBao']);
	if ($paytype==1&&($userGift < $rumor['price']))	throw new Exception($GLOBALS['askDetail']['no_enough_Gift']);
	
	
	if ($rumor['type'] == 0)  //将领
	{
		$hero  = sql_fetch_one("select * from sys_city_hero where npcid='$rumor[typeid]'");
		if (empty($hero)) throw new Exception($GLOBALS['askDetail']['no_info_of_hero']);
		$heroinfo = sql_fetch_one("select * from cfg_npc_hero where npcid='$rumor[typeid]'");

		$ret[] = $heroinfo ;

		$heropos = getPosDescription($hero['cid']);

		$msg = sprintf($GLOBALS['askDetail']['hero_location'],$rumor['name'],$heropos);
		//$msg = "据可靠消息，".$rumor['name']."在".$heropos."。行动一定要快，不要被别人抢先了。";    

		$ret[] = $msg;
		$ret[] = false; //详细消息已经看过了         
		$ret[] = true;//sql_check("select * from cfg_npc_task where npcid='$rumor[typeid]'");//是否有抓将任务

		//添加一个公文公告
		$reportcontent = $heroinfo['name'];
		if (!empty($heroinfo['zi']))
		{
			$reportcontent .= $GLOBALS['askDetail']['word'].$heroinfo['zi'];
		}
		$reportcontent .= "。".$heroinfo['introduce']."<br/>";
		$reportcontent .= $msg;
		//扣钱
		$tid=$heroinfo['npcid']+20000;
		if($paytype==0)
			addMoney($uid,-$rumor['price'],90);
		else if($paytype==1)
			addGift($uid,-$rumor['price'],90);
			
		sql_query("insert into sys_things (uid,tid,count) values ('$uid','$tid','1') on duplicate key update `count`='1'");
		//addThings($uid,$heroinfo['npcid']+20000,1,0);
	}
	else if ($rumor['type'] == 1)  //任务物品   
	{
		$thinginfo = sql_fetch_one("select f.*,r.type,r.detail from cfg_rumor_thing r,cfg_things f where r.tid='$rumor[typeid]' and f.tid=r.tid");
		$ret[] = $thinginfo;
		if ($thinginfo['type'] == 0)    //一般物品
		{
			$ret[] = "";
			$ret[] = false; //打听详细 
		}
		else if ($thinginfo['type'] == 1)   //有地点的物品
		{
			$thingcid = sql_fetch_one_cell("select cid from sys_thing_position where thingid=".$thinginfo['tid']);
			if (empty($thingcid))
			{
				$msg = sprintf($GLOBALS['askDetail']['no_info_of_staff'],$thinginfo['name']);
				//$ret[] = "我没有关于".$thinginfo['name']."的消息，不劳您费元宝啦。";
				$ret[] = $msg;
				$ret[] = false; //打听详细
			}
			else
			{

				$thingpos = getPosDescription($thingcid);

				$msg = sprintf($GLOBALS['askDetail']['staff_location'],$rumor['name'],$thingpos);
				//$msg = "据可靠消息，".$rumor['name']."在".$thingpos."。行动一定要快，不要被别人抢先了。"; 
				$ret[] = $msg;
				$ret[] = false; //详细消息已经看过了
				$reportcontent = $thinginfo['description']."<br/>".$msg;
				//扣钱
				//addMoney($uid,-$rumor['price'],90);
				if($paytype==0)
					addMoney($uid,-$rumor['price'],90);
				else if($paytype==1)
					addGift($uid,-$rumor['price'],90);
			}
		}
		else if ($thinginfo['type'] == 2)   //特殊物品
		{
			$ret[] = $thinginfo['detail'];
			$ret[] = false;
			$reportcontent = $thinginfo['description']."<br/>".$thinginfo['detail'];
			//扣钱
			//addMoney($uid,-$rumor['price'],90);
			
			if($paytype==0)
				addMoney($uid,-$rumor['price'],90);
			else if($paytype==1)
				addGift($uid,-$rumor['price'],90);
		}
	}

	if (!empty($reportcontent))
	{
		sendReport($uid,'rumor',19,$cid,$cid,$reportcontent);
		//completeTask($uid,367);
	}
	unlockUser($uid);
	return $ret;
}
function recordTask($uid,$cid,$param)
{
	$id = array_shift($param);
	$rumor = sql_fetch_one("select * from sys_city_rumor where id='$id'");
	if ($rumor['type'] == 0)
	{
		$npcid = $rumor['typeid'];
		$taskid1 = 20000+$npcid;
		$taskid2 = 30000+$npcid;
		if(sql_check("select uid from sys_user_task where uid='$uid' and (tid='$taskid1' or tid='$taskid2') and state=0"))
		{
			throw new Exception($GLOBALS['recordTask']['task_already_recorded']);
		}
		$npcowner=sql_fetch_one_cell("select uid from sys_city_hero where hid='$npcid'");
		if($npcowner==$uid)
		{
			throw new Exception($GLOBALS['recordTask']['npc_hero_exist']);
		}
		$taskcount=sql_fetch_one_cell("select count(distinct(t.`group`)) from sys_user_task u,cfg_task t where u.tid=t.id and u.uid='$uid' and u.state=0 and u.tid>20000 and u.tid<40000");
		if($taskcount>=25)
		{
			throw new Exception($GLOBALS['recordTask']['task_list_full']);
		}
		sql_query("delete from sys_user_goal where uid='$uid' and gid in (select id from cfg_task_goal where (tid='$taskid1' or tid='$taskid2'))");
		sql_query("insert into sys_user_task (uid,tid,state) values ($uid,$taskid1,0) on duplicate key update state=0");
		sql_query("insert into sys_user_task (uid,tid,state) values ($uid,$taskid2,0) on duplicate key update state=0");
	}
	else if ($rumor['type'] == 1)
	{
		$thingid = $rumor['typeid'];
		$taskid = sql_fetch_one_cell("select taskid from cfg_thing_task where thingid='$thingid'");
		if (empty($taskid))
		{
			throw new Exception($GLOBALS['recordTask']['no_task_related_to_staff']);
		}
	}
	else
	{
		throw new Exception($GLOBALS['recordTask']['no_rumor_to_record']);
	}
	/*$existtask = sql_fetch_one("select * from sys_user_task where uid='$uid' and tid='$taskid'");
	 if (!empty($existtask))
	 {
	 if ($existtask['state'] == 0)
	 {
	 throw new Exception($GLOBALS['recordTask']['task_already_recorded']);
	 }
	 else if ($existtask['state'] == 1)
	 {
	 throw new Exception($GLOBALS['recordTask']['task_accomplished']);
	 }
	 }*/
	throw new Exception($GLOBALS['recordTask']['task_record_succ']);
}
//鉴定宝藏
function treasureIdentify($uid,$cid,$param){
	//判断是否有鉴宝图
	if (!checkGoods($uid,118))
	{
		throw new Exception($GLOBALS['treasure']['not_enough_map']);
	}
	
	$paytype=array_shift($param);
	if($paytype!=0&&$paytype!=1){
		throw new Exception($GLOBALS['buyGoods']['invalid_pay_type']);
	}
	
	$userInfo = sql_fetch_one("select money,gift from sys_user where uid='$uid'");
	//元宝
	$userMoney=$userInfo['money'];
	//礼金
	$userGift=$userInfo['gift'];
	//if ($money < $rumor['price']) throw new Exception($GLOBALS['askDetail']['no_enough_YuanBao']);
	
	if ($paytype==0&&($userMoney < 10))	throw new Exception($GLOBALS['treasure']['not_enough_money']);
	if ($paytype==1&&($userGift < 10))	throw new Exception($GLOBALS['treasure']['not_enough_Gift']);
	
	
	
	//钱够不够
	/*if(!checkMoney($uid,10)){
		throw new Exception($GLOBALS['treasure']['not_enough_money']);
	}*/
	//隨機
	$y = floor($cid / 1000);
	$x = ($cid % 1000);

	$y = floor($y / 10);
	$x = floor($x / 10);


	//100个格子以内
	$wstart=($y*100+$x)*100;
	$wend=$wstart+100;

	//选择非平地,随机选一种野地
	$worlds= sql_fetch_rows("select type,wid from mem_world where wid>'$wstart' and wid < '$wend' and type>1 and level>0");
	$max=count($worlds);
	//预防所有野地都是0
	if($max==0){
		unlockUser($uid);
		throw new Exception($GLOBALS['treasure']['has_not']);
	}

	//随机选一个
	$index=mt_rand(0,$max-1);

	$wid=$worlds[$index]['wid'];

	$targetcid=wid2cid($wid);

	//加入这个用户宝藏图，一天失效
	sql_query("insert into mem_treasure_map (uid,cid,endtime) values('$uid','$targetcid',unix_timestamp()+86400 ) ");
	//隨機
	$y = floor($targetcid / 1000);
	$x = ($targetcid % 1000);

	$ftype=$worlds[$index]['type'];
	if($ftype==2)
	$fieldname =$GLOBALS['fileName']['2'];
	if($ftype==3)
	$fieldname =$GLOBALS['fileName']['3'];
	if($ftype==4)
	$fieldname =$GLOBALS['fileName']['4'];
	if($ftype==5)
	$fieldname =$GLOBALS['fileName']['5'];
	if($ftype==6)
	$fieldname =$GLOBALS['fileName']['6'];

	if($ftype==7)
	$fieldname =$GLOBALS['fileName']['7'];

	$msg=sprintf($GLOBALS['treasure']['report'],$fieldname,$x,$y,MakeEndTime(sql_fetch_one_cell("select unix_timestamp()+86400")));
	sendReport($uid,"trick",31,$cid,$cid,$msg);
	reduceGoods($uid,118,1);
	if($paytype==0)
		addMoney($uid,(0-10),53);
	else if($paytype==1)
		addGift($uid,(0-10),53);
	unlockUser($uid);
	throw new Exception($GLOBALS['treasure']['succ']);
}

function addRewardTask($uid,$cid,$param){
	$targetcidx = intval(array_shift($param));
	$targetcidy = intval(array_shift($param));
	$tasktype = intval(array_shift($param));
	$goal = intval(array_shift($param));
	$day = intval(array_shift($param));
	$money = intval(array_shift($param));

	$level = sql_fetch_one_cell("select level from sys_building where cid='$cid' and bid=10");
	if($level<5){
		throw new Exception($GLOBALS['reward_task']['no_level']);
	}
	
	//爵位必须达到大夫	
	$nobility=sql_fetch_one_cell("select nobility from sys_user where uid='$uid'");
	$nobility = getBufferNobility($uid,$nobility);
	if ($nobility<5){
	    throw new Exception($GLOBALS['reward_task']['nobility_low']);
	}
	
	//判断是否有委托文书
	if (!checkGoods($uid,XUANSHANGLING_ID)){
		throw new Exception($GLOBALS['reward_task']['no_goods']);
	}
	//悬赏奖励不能是0
	if($money<10){
		throw new Exception($GLOBALS['reward_task']['money_zero']);
	}
	//检查有没有发布超过十个

	$count=sql_fetch_one_cell("select count(*) from sys_pub_reward_task where uid='$uid' and state=0 ");
	if($count>=10){
		throw new Exception($GLOBALS['reward_task']['too_much']);
	}

	//悬赏天数检查
	if($day<=0||$day>10){
		throw new Exception($GLOBALS['reward_task']['day_error']);
	}

	if($tasktype<0||$tasktype>2){
		//不存在的任务类型
		throw new Exception($GLOBALS['reward_task']['task_type_error']);
	}else if($tasktype==2){
		//占领土地
		if($goal!=0&&$goal!=1){
			throw new Exception($GLOBALS['reward_task']['goal_error']);
		}
	}else if($tasktype==0||$tasktype==1){
		//掠夺或者消灭兵力
		if($goal<0||$goal>100000000){
			throw new Exception($GLOBALS['reward_task']['goal_error']);
		}
	}
	//钱够不够
	if(!checkMoney($uid,$money)){
		throw new Exception($GLOBALS['reward_task']['not_enough_money']);
	}

	$targetcid=$targetcidx+$targetcidy*1000;
	$targetname=sql_fetch_one_cell("select name from sys_city where cid='$targetcid'");
	if(empty($targetname)){
		$wid=cid2wid($targetcid);
		$type=sql_fetch_one_cell("select type from mem_world where wid='$wid'");
		$targetname=getFieldName($type);
	}
	$endtime=$day*86400;
	$now = sql_fetch_one_cell("select unix_timestamp();");
	$endtime+=$now;
	$endtime=$endtime + 3600-$endtime%3600;
	$todo= genRewardTaskTodo($targetcid,$targetname,$endtime,$tasktype,$goal);
	$sqlcode="insert into sys_pub_reward_task (uid,targetcid,targetname,type,goal,endtime,money,number,state,todo) values('$uid','$targetcid','$targetname','$tasktype','$goal','$endtime','$money',0,0,'$todo') ";
	sql_query($sqlcode);
	reduceGoods($uid,XUANSHANGLING_ID,1);
	addMoney($uid,(0-$money),54);

	$ret=array();
	$ret[]=$sqlcode;
	return $ret;

}

function genRewardTaskTodo($targetcid,$targetname,$endtime,$type,$goal){
	$result="";
	$pos=getPosition($targetcid);
	$time=MakeEndTime($endtime);
	if($type==0)
	return sprintf($GLOBALS['recordTask']['task_content_0'],"",$time,$targetname,$pos,$goal);
	else if($type==1)
	return sprintf($GLOBALS['recordTask']['task_content_1'],"",$time,$targetname,$pos,$goal);
	else if($type==2){
		if($goal==0)
		return sprintf($GLOBALS['recordTask']['task_content_2'],"",$time,$targetname,$pos);
		else if($goal==1)
		return sprintf($GLOBALS['recordTask']['task_content_3'],"",$time,$targetname,$pos);
	}
}

function resetSystemRewardTask($uid,$cid,$param)
{
	useTaskMagic($uid,$cid);
	return getSystemRewardTaskList($uid,$cid,$param);
}

function fetchSystemRewardTask($uid,$cid,$param)
{
	$id=intval(array_shift($param));
	$tid=intval(array_shift($param));
	
	$task = sql_fetch_one_cell("select tid from sys_city_sys_task where cid=$cid and tid=$tid and state=1");
	if(!empty($task)){
		throw new Exception($GLOBALS['sysRecordTask']['task_already_exist']);
	}
	sql_query("update sys_city_sys_task set state=1 where id=$id");
	sql_query("insert into  sys_user_task values($uid,$tid,0) on duplicate key update state=0");
	
	
	throw new Exception($GLOBALS['sysRecordTask']['task_record_succ']);
	
}

function  generateSysTask($cid,$actid)
{             
	// $tids = sql_fetch_one("select min(id) as mintid, max(id) as maxid from cfg_task where `group`>=5000 and `group`<=5500");
	// if(!empty($tids)){
	// 	$mintid=$tids['mintid'];
	// 	$maxtid=$tids['maxid'];
	// 	$userTasks=sql_fetch_rows("select tid from sys_user_task where state=1 and tid>=$mintid and tid<=$maxtid");
	// 	foreach($userTasks as $task){
	// 		sql_query("delete from sys_city_sys_task where cid=$cid and tid=".$task['tid']);	
	// 	}	   
	 //}
	 
     $rows = sql_fetch_rows("select tid,rate from sys_sys_task where `actid`=$actid and tid not in (select tid from sys_city_sys_task where cid=$cid)");
     
     if(empty($rows)){
     	return false;
     }
     
     $sumRate = 0;
	 foreach($rows as $row){
     	$sumRate += $row['rate'];
     }
     
     $rate = mt_rand(1,$sumRate);
     
     $curRate=0;
     $sysTask=null;
     foreach($rows as $row){
     	$curRate += $row['rate'];
     	if ($curRate>=$rate){
     		$sysTask=$row;
     		break;
     	}
     }
     
     sql_query("insert into sys_city_sys_task (cid,tid,state) values($cid,".$sysTask['tid'].",0) ");
     
}

function doGetResetSystemTask($uid,$cid)
{
	$level = sql_fetch_one_cell("select level from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_HOTEL." order by level desc limit 1");
	if (empty($level))
	{
		throw new Exception($GLOBALS['getHotelInfo']['no_hotel_built']);
	}
	
	$last_reset_sys_task = sql_fetch_one_cell("select last_reset_sys_task from mem_city_schedule where cid='$cid'");

    if (empty($last_reset_sys_task))
    {
    	sql_query("insert into mem_city_schedule (cid,last_reset_sys_task) values ('$cid',unix_timestamp()) on duplicate key update last_reset_sys_task=unix_timestamp()");
    	$last_reset_sys_task = 0;
    }
    $now = sql_fetch_one_cell("select unix_timestamp()");
    $blocksize = (10800/GAME_SPEED_RATE) / $level;
    $last_block = floor(($last_reset_sys_task+8*3600) / $blocksize);
    $curr_block = floor(($now+8*3600) / $blocksize);
    $blockdelta = $curr_block - $last_block;
    if ($blockdelta > 0)
    {
        $oldSystasks = sql_fetch_rows("select * from sys_city_sys_task where `cid`='$cid' order by id limit $blockdelta");  
        foreach($oldSystasks as $task)
        {
            sql_query("delete from sys_city_sys_task where id=".$task['id']);
        }           
        $taskCount = sql_fetch_one_cell("select count(*) from sys_city_sys_task where cid='$cid'");
      	$now = sql_fetch_one_cell("select unix_timestamp()");
      	$rate=5;
	
		$tresult = sql_fetch_one("select * from cfg_act where $now>= starttime and $now <= endtime and type = 4 ");	
    	if($last_reset_sys_task==0){
			$rate=$tresult["rate"];
		}
								
        if ($tresult&&(mt_rand(1,100)<$rate))	
        {
        	$hasActTask=false;
        	mt_srand(mt_rand());
        	$idx=mt_rand($taskCount,$level-1);
        	for ($i = $heroCount; $i < $level; $i++)
	        {
	        	if ((!$hasActTask)&&$i==$idx)
	        	{
	        		mt_srand(mt_rand());
	    			generateSysTask($cid,$tresult['actid']);
	        		$hasActTask = true;
	        	}
	        	else
	        	{
	            	generateSysTask($cid,0);
	            }
	        }
        }
        else
        {
	        for ($i = $taskCount; $i < $level; $i++)
	        {
	            generateSysTask($cid,0);
	        }  
        }
        sql_query("update mem_city_schedule set last_reset_sys_task=unix_timestamp() where cid='$cid'");      
    }
	
	    
}





function getSystemRewardTaskList($uid,$cid,$param)
{
	doGetResetSystemTask($uid,$cid);
	$page = intval(array_shift($param));

	$itemCount = sql_fetch_one_cell("select count(*) from sys_city_sys_task where cid=$cid");
	$pageCount = ceil($itemCount /10);
	if($page>=$pageCount)
	{
		$page=$pageCount-1;
	}
	if($page<0)
	{
		$page=0;
		$pageCount=0;
	}
		
	$ret=Array();
	
	if($itemCount>0)
	{
		$pagestart = $page * 10;
		$ret[]=$pageCount;
		$ret[]=$page;		
		$ret[] = sql_fetch_rows("select * from sys_city_sys_task c left join  sys_sys_task t on c.tid=t.tid where c.cid=$cid  limit $pagestart,10");		
	}
	else
	{
		$ret[]=0;
		$ret[]=0;
		$ret[]=array();
	}	  

    return $ret;
}



function getRewardTaskList($uid,$cid,$param){
	$page = intval(array_shift($param));
	$filter = intval(array_shift($param));
	$orderby = intval(array_shift($param));
	//$unionOnly = intval(array_shift($param));
	//$sellName = array_shift($param);
	$filterResource = "";
	// $filterResource2 = "";
	if ($filter > 0){
		$filterResource = "and type = ".($filter-1);
		// $filterResource2 = "and t.restype = ".($filter-1);
	}

	//$unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");

	$ret = array();
	//$ret[] = getCityTradeUsing($cid);
	//$ret[] = getCityMarketLevel($cid);
	$itemCount = sql_fetch_one_cell("select count(*) from sys_pub_reward_task where state=0  $filterResource ");
	$pageCount = ceil($itemCount /10);
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
		$pagestart = $page * 10;
		$ret[]=$pageCount;
		$ret[]=$page;
		if($orderby==1)
		$ret[] = sql_fetch_rows("select * from sys_pub_reward_task where state=0  $filterResource order by endtime asc limit $pagestart,10");
		else if($orderby==2)
		$ret[] = sql_fetch_rows("select * from sys_pub_reward_task where state=0  $filterResource order by money desc limit $pagestart,10");
		else
		$ret[] = sql_fetch_rows("select * from sys_pub_reward_task where state=0  $filterResource order by ((`targetcid`%1000-'$cid'%1000)*(`targetcid`%1000-'$cid'%1000)+(floor(`targetcid`/1000)-floor('$cid'/1000))*(floor(`targetcid`/1000)-floor('$cid'/1000))) asc limit $pagestart,10");
	}
	else
	{
		$ret[]=0;
		$ret[]=0;
		$ret[]=array();
	}
	return $ret;
}
function getMyRewardTaskList($uid,$cid,$param){
	$ret=array();
	$ret[] = sql_fetch_rows("select * from sys_pub_reward_task where state=0 and uid='$uid' order by endtime asc ");
	return $ret;
}
function fetchRewardTask($uid,$cid,$param){
	$tid=intval(array_shift($param));
	$task=sql_fetch_one("select uid,type,targetcid,goal from sys_pub_reward_task where id='$tid'");
	if(empty($task)){
		throw new Exception($GLOBALS['fetchRewardTask']['no_task']);
	}
	
	if($task['uid']==$uid){
		throw new Exception($GLOBALS['fetchRewardTask']['my_task']);
	}

	if(sql_check("select uid from sys_user_reward_task where uid='$uid' and tid='$tid'")){
		throw new Exception($GLOBALS['fetchRewardTask']['task_already_recorded']);
	}
	$taskcount=sql_fetch_one_cell("select count(*) from sys_user_reward_task where uid='$uid' ");
	if($taskcount>=25){
		throw new Exception($GLOBALS['fetchRewardTask']['task_list_full']);
	}
	sql_query("insert into sys_user_reward_task (uid,tid,state,type,targetcid,goal) values ($uid,$tid,0,$task[type],$task[targetcid],$task[goal]) on duplicate key update state=0");
	//sql_query("insert into sys_user_task (uid,tid,state) values ($uid,$taskid2,0) on duplicate key update state=0");
	sql_query("update sys_pub_reward_task set number=number+1 where id='$tid'");
	 
	throw new Exception($GLOBALS['recordTask']['task_record_succ']);
}

/*function regenerateCommission($cid)
 {
 sql_query("delete from sys_recruit_hero where `cid`='$cid'");
 for ($i = 0; $i < $level; $i++)
 {
 generateRecruitHero($cid);
 }
 }
 //委托任务
 function getCommissions($uid,$cid)
 {
 $last_reset_commission_and_now = sql_fetch_one("select last_reset_commission,unix_timestamp() as now from mem_city_schedule where cid='$cid'");
 $last_reset_commission = 0;
 $now=0;
 if (empty($last_reset_commission_and_now)){
 sql_query("insert into mem_city_schedule (cid,last_reset_commission) values ('$cid',unix_timestamp()) on duplicate key update last_reset_commission=unix_timestamp() ");
 }else{
 $last_reset_commission=$last_reset_commission_and_now['last_reset_commission'];
 $now=$last_reset_commission_and_now['now'];
 if($now-$last_reset_commission>=1800){
 //大于半个小时
 regenerateCommission($cid);
 }
 }
 $commissions=sql_fetch_rows("select * from sys_city_commission_task where cid='$cid'");
 if(empty($commissions)){
 regenerateCommission($cid);
 }
 $ret=array();
 $ret[]=$commissions;
 return $ret[];
 } */
 
?>