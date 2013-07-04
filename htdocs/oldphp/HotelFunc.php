<?php                      
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");

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
/*
function generateHuangJinHero($cid,$level)
{
	$sex = 1;
	$heroname = array(
	array('黄巾随员','黄巾文员','黄巾幕僚','黄巾辅臣','黄巾渠帅'),
	array('黄巾老兵','黄巾亲卫','黄巾小校','黄巾悍将','黄巾勇将'),
	array('黄巾参谋','黄巾智囊','黄巾谋士','黄巾谋臣','黄巾军师'));
	$basepoints = array(70,75,80,85,89);
	
	$type = mt_rand() % 3;
	$rnd = mt_rand() % 100;
	if ($rnd < 50) $leveltype = 0;
	else if ($rnd < 80) $leveltype = 1;
	else if ($rnd < 95) $leveltype = 2;
	else if ($rnd < 99) $leveltype = 3;
	else $leveltype = 4;
	
	$name = $heroname[$type][$leveltype];
	$face = mt_rand(1001,1070);
	$hlevel = $level * 5;
	
	if ($type == 0)
	{
		$affairs_base = $basepoints[$leveltype];
		$left = 90 - $affairs_base;
		$bravery_base = 30 + (mt_rand() % $left);
		$wisdom_base = 150 - $affairs_base - $bravery_base;
	}
	else if ($type == 1)
	{
		$bravery_base = $basepoints[$leveltype];
		$left = 90 - $bravery_base;
		$affairs_base = 30 + (mt_rand() % $left);
		$wisdom_base = 150 - $affairs_base - $bravery_base;
	}
	else if ($type == 2)
	{
		$wisdom_base = $basepoints[$leveltype];
		$left = 90 - $wisdom_base;
		$affairs_base = 30 + (mt_rand() % $left);
		$bravery_base = 150 - $affairs_base - $wisdom_base;
	}
	$affairs_add = round($hlevel * $affairs_base / 150);
	$bravery_add = round($hlevel * $bravery_base / 150);
	$wisdom_add = $hlevel - $affairs_add - $bravery_add;
	$hero_level_info = sql_fetch_one("select * from cfg_hero_level where level='$hlevel'");
    if (empty($hero_level_info)) throw new Exception($GLOBALS['generateRecruitHero']['no_data_of_this_level']);
    $hero_exp = $hero_level_info['total_exp'];
    //忠诚度默认70
    $loyalty = 70;
        $gold_need=($hlevel*20+(max($affairs_base+$affairs_add-90,0)+max($bravery_base+$bravery_add-90,0)+max($wisdom_base+$wisdom_add-90,0))*50)*50;
    $sql = "insert into sys_recruit_hero (`name`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`gold_need`,`gen_time`,`huangjin`) values ('$name','$sex','$face','$cid','$hlevel','$hero_exp','$affairs_base','$bravery_base','$wisdom_base','$affairs_add','$bravery_add','$wisdom_add','$loyalty','$gold_need',unix_timestamp(),1)";
    sql_query($sql);
}
*/

function generateChristmasHero($cid,$level)
{
	$sex = 1;
	$heroname = array('圣诞庸官','圣诞辅臣','圣诞悍将','圣诞谋臣','圣诞渠帅','圣诞勇将','圣诞军师');
	$basepoints = array(70,75,80,85,89);
	
	$rnd = mt_rand() % 100;
	$herotype=0;
	if ($rnd < 60)
	{
		$herotype = 0;
	}
	else if ($rnd < 90)
	{
		$herotype = 1+mt_rand(0,2);
	}
	else
	{
		$herotype=4+mt_rand(0,2);
	}
	
	$name = $heroname[$herotype];
	$face = mt_rand(1001,1070);
	$hlevel = mt_rand(5,$level * 5);
	
	switch($herotype)
	{
		case 0:
			$affairs_base=49;
			$bravery_base=49;
			$wisdom_base=49;
			break;
		case 1:
			$affairs_base=80;
			$bravery_base=30;
			$wisdom_base=30;
			break;
		case 2:
			$affairs_base=30;
			$bravery_base=80;
			$wisdom_base=30;
			break;
		case 3:
			$affairs_base=30;
			$bravery_base=30;
			$wisdom_base=80;
			break;
		case 4:
			$affairs_base=89;
			$bravery_base=30;
			$wisdom_base=30;
			break;
		case 5:
			$affairs_base=30;
			$bravery_base=89;
			$wisdom_base=30;
			break;
		case 6:
			$affairs_base=30;
			$bravery_base=30;
			$wisdom_base=89;
			break;
	}
	$total=$affairs_base+$bravery_base+$wisdom_base;
	
	$affairs_add = round($hlevel * $affairs_base / $total);
	$bravery_add = round($hlevel * $bravery_base / $total);
	$wisdom_add = $hlevel - $affairs_add - $bravery_add;
	$hero_level_info = sql_fetch_one("select * from cfg_hero_level where level='$hlevel'");
    if (empty($hero_level_info)) throw new Exception($GLOBALS['generateRecruitHero']['no_data_of_this_level']);
    $hero_exp = $hero_level_info['total_exp'];
    //忠诚度默认70
    $loyalty = 70;
    $gold_need=($hlevel*20+(max($affairs_base+$affairs_add-90,0)+max($bravery_base+$bravery_add-90,0)+max($wisdom_base+$wisdom_add-90,0))*50)*50;
    $herotype=3+$herotype;
    $sql = "insert into sys_recruit_hero (`name`,`sex`,`face`,`cid`,`level`,`exp`,`affairs_base`,`bravery_base`,`wisdom_base`,`affairs_add`,`bravery_add`,`wisdom_add`,`loyalty`,`gold_need`,`gen_time`,`herotype`) values ('$name','$sex','$face','$cid','$hlevel','$hero_exp','$affairs_base','$bravery_base','$wisdom_base','$affairs_add','$bravery_add','$wisdom_add','$loyalty','$gold_need',unix_timestamp(),'$herotype')";
    sql_query($sql);
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
	        mt_srand($GLOBALS['now']);
	        /*
	        $hashuangjin = sql_check("select * from sys_recruit_hero where cid='$cid' and `huangjin`=1");
	        $hjCount = sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and `huangjin`=1");
	        if (($now >= 1222444800)&&($now <= 1223395200))	//0927-1008
	        {
				for ($i = $heroCount; $i < $level; $i++)
		        {
	        		//每次有0.1 *（30 – N * 10）%的几率出现黄巾将领
		        	if ((!$hashuangjin)&&(mt_rand() < mt_getrandmax() * (0.03 - 0.01 * $hjCount)))
		        	{
		        		generateHuangJinHero($cid,$level);
		        		$hashuangjin = true;
		        	}
		        	else
		        	{
		            	generateRecruitHero($cid,$level);
		            }
		        }  
	        }*/
			$hasChris=intval(sql_fetch_one_cell("select count(*) from sys_recruit_hero where cid='$cid' and herotype>=3 and herotype<=9"))>0;
	        //$ChrisCount=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and herotype>=3 and herotype<=9");
	        if (($now >= 1229428800)&&($now <= 1230048000))	//2008-12-16 20:00 - 2008-12-24 00:00
	        {
				for ($i = $heroCount; $i < $level; $i++)
		        {
	        		//每次有0.1 *（70 – N * 10）%的几率出现圣诞将领
		        	if ((!$hasChris)&&(mt_rand() < mt_getrandmax() * 0.02))
		        	{
		        		generateChristmasHero($cid,$level);
		        		$hasChris = true;
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
            if(!empty($tmpHero['herotype']))
        	{
	        	/*$hjCount=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and `huangjin`=1");
	        	if($hjCount>=3)
	        	{
	        		throw new Exception("最多只能招募3名黃巾武將！你已經招募了".$hjCount."名黃巾武將，需要解僱掉一名才能再招募更多的黃巾武將！");
	        	}*/
	        	$ChrisCount=sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and herotype='$tmpHero[herotype]'");
	        	if($ChrisCount>0)
	        	{
	        		throw new Exception("你已经招募了一名“$tmpHero[name]”，需要解雇原来的将领才能重新招募！");
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
            if($tmpHero['herotype']>=3&&$tmpHero['herotype']<=9)	//圣诞将领，招募的时候获得一枚印章
            {
            	if(!sql_check("select * from sys_user_task where uid='$uid' and tid='100021' and state=1"))
            	{
            		sql_query("insert into sys_user_task (uid,tid,state) values ('$uid','100021',0) on duplicate key update state=state");
					$thingid=15001+$tmpHero['herotype']-3;
            		sql_query("insert into sys_things (uid,tid,count) values ('$uid','$thingid',1) on duplicate key update count=count");
            	}
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
        }
        else
        {
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
    lockUser($uid);
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
    lockUser($uid);
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
    $rumor = sql_fetch_one("select * from sys_city_rumor where id='$id'");
    if (empty($rumor)) throw new Exception($GLOBALS['askDetail']['never_heard']);
    $ret=array();
    $ret[] = $rumor;
    lockUser($uid);
    $money = sql_fetch_one_cell("select money from sys_user where uid='$uid'");
    if ($money < $rumor['price']) throw new Exception($GLOBALS['askDetail']['no_enough_YuanBao']);
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
        addMoney($uid,-$rumor['price'],90);
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
        		addMoney($uid,-$rumor['price'],90);
            }
        }
        else if ($thinginfo['type'] == 2)   //特殊物品
        {
            $ret[] = $thinginfo['detail'];
            $ret[] = false;
            $reportcontent = $thinginfo['description']."<br/>".$thinginfo['detail'];
            //扣钱
        	addMoney($uid,-$rumor['price'],90);
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
?>