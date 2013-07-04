<?php                      
require_once("./interface.php");
require_once("./utils.php"); 

define("TITLE_TRICK",23);
function getTrickList($uid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_rows("select * from cfg_trick order by id");
    $ret[] = sql_fetch_one_cell("select sum(count) from sys_goods where uid='$uid' and gid=13");
    return $ret;
}
//草木皆兵  城池显示军队人数为真实的5~10倍。持续wisdom*10分钟。
function trickCaoMuJieBin($cid,$wisdom)
{                     
    $rate = mt_rand(5,10);
    $delay = $wisdom * 600;
    sql_query("insert into mem_city_buffer (cid,buftype,bufparam,endtime) values ('$cid',1,$rate,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay,bufparam=$rate");
    
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$cid' and buftype=1");
    
    return sprintf($GLOBALS['trickCaoMuJieBin']['succ'],$rate,MakeEndTime($endtime));
}
//空城计  城池显示军队人数为真实的10%~20%。持续wisdom*10分钟。   
function trickKongCheng($cid,$wisdom)
{
    $rate = mt_rand(10,20);
    $delay = $wisdom * 600;
    sql_query("insert into mem_city_buffer (cid,buftype,bufparam,endtime) values ('$cid',2,$rate,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay,bufparam=$rate");
    
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$cid' and buftype=2");
    return sprintf($GLOBALS['trickKongCheng']['succ'],$rate,MakeEndTime($endtime));
    
}
//抛砖引玉 城池显示资源为真实的5~10。持续wisdom*10分钟。
function trickPaoZhuangYingYu($cid,$wisdom)
{
    $rate = mt_rand(5,10);
    $delay = $wisdom * 600;
    sql_query("insert into mem_city_buffer (cid,buftype,bufparam,endtime) values ('$cid',3,$rate,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay,bufparam=$rate");
    
    
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$cid' and buftype=3");

    return sprintf($GLOBALS['trickPaoZhuangYingYu']['succ'],$rate,MakeEndTime($endtime));
    
}
//坚壁清野  城池显示资源为真实的10%~20% 持续wisdom*10分钟。  
function trickJinBiQingYe($cid,$wisdom)
{
    $rate = mt_rand(10,20);
    $delay = $wisdom * 600;
    sql_query("insert into mem_city_buffer (cid,buftype,bufparam,endtime) values ('$cid',4,$rate,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay,bufparam=$rate");  
    
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$cid' and buftype=4");
    return sprintf($GLOBALS['trickJinBiQingYe']['succ'],$rate,MakeEndTime($endtime));
}
//暗度陈仓 打破敌人封锁，可以从被敌人围困的城池内调动军队出城。
function trickAnDuChenChang($cid,$wisdom)
{
    $delay = $wisdom * 60;                              
    sql_query("insert into mem_city_buffer(cid,buftype,bufparam,endtime) values ('$cid','5',0,unix_timestamp()+$delay) on duplicate key update endtime=endtime+$delay");
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$cid' and buftype=5");   
    
    return sprintf($GLOBALS['trickAnDuChenChang']['succ'],MakeEndTime($endtime));
    
}
//妖言惑众：成功后，立刻降低城池民心。10点智谋可降低1点民心。中计的城池在24小时内不会再次中计。
function trickYaoYinHuoZhong($uid,$targetcid,$wisdom)
{
	$user=sql_fetch_one("select name,lastcid from sys_user where uid='$uid'");
    $username = $user['name'];
    $targetcity = getCityNamePosition($targetcid);
    $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'");
                                        
    $delta = sql_fetch_one_cell("select unix_timestamp() - last_trick_morale from mem_city_schedule where cid='$targetcid'");
    if ($delta < 86400)
    {
        $caution = sprintf($GLOBALS['trickYaoYinHuoZhong']['fail_caution'],$username,$targetcity);
        sendReport($targetuid,"trick",TITLE_TRICK,$user['lastcid'],$targetcid,$caution);
        return sprintf($GLOBALS['trickYaoYinHuoZhong']['fail'],$targetcity);
    }
    $morale_reduce = ceil($wisdom / 10.0);
    
    $citymorale = sql_fetch_one_cell("select morale from mem_city_resource where cid='$targetcid'");
    if ($citymorale < $morale_reduce) $morale_reduce = $citymorale;
    
    sql_query("update mem_city_resource set morale=morale-$morale_reduce where cid='$targetcid'");
    
                       
    sql_query("update mem_city_schedule set last_trick_morale=unix_timestamp() where cid='$targetcid'");
                                                   
    $caution = sprintf($GLOBALS['trickYaoYinHuoZhong']['succ_caution'],
        $username,$targetcity,$targetcity,$morale_reduce);
    sendReport($targetuid,"trick",TITLE_TRICK,$user['lastcid'],$targetcid,$caution);
                                                                      
    return sprintf($GLOBALS['trickYaoYinHuoZhong']['succ'],$targetcity,$morale_reduce);
    
}
//挑拨离间：成功后，降低对方城池内一个随机将领的忠诚。 10点智谋可降低1点忠诚
function trickTiaoBoLiJian($uid,$targetcid,$wisdom)
{
    $user=sql_fetch_one("select name,lastcid from sys_user where uid='$uid'"); 
    $username = $user['name'];
    $targetcity = getCityNamePosition($targetcid);    
    $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'");
    
    $ret = "";
    $caution = "";
    $targethero = sql_fetch_one("select * from sys_city_hero where cid='$targetcid' and uid='$targetuid' and (state=0 or state=1) order by rand() limit 1");        //在城里的将领
    if (empty($targethero)) //无人中计
    {
        $ret = $GLOBALS['trickTiaoBoLiJian']['fail_nohero'];
        $caution = $targetcity.$GLOBALS['trickTiaoBoLiJian']['fail_caution_nohero'];   
    }
    else
    {
        $last_trick = sql_fetch_one_cell("select last_trick from mem_hero_schedule where hid='$targethero[hid]'");
        $now = sql_fetch_one_cell("select unix_timestamp()");
        //24小时冷却时间
        if ($now - $last_trick < 86400)
        {
            $ret = $GLOBALS['trickTiaoBoLiJian']['fail'];
            $caution = sprintf($GLOBALS['trickTiaoBoLiJian']['fail_caution'],$username,$targetcity);
        }     
        else                                           
        {      
            //对比一下智谋                               
            if (mt_rand(1,$wisdom + $targethero['wisdom_base']+$targethero['wisdom_add']) > $wisdom)
            {
                $ret = $GLOBALS['useTrick']['fail_no_wisdom'];
                $caution = sprintf($GLOBALS['trickTiaoBoLiJian']['fail_caution'],$username,$targetcity);
            }
            else//中计了
            {
                $loyalty_reduce = ceil($wisdom / 10);
                $ret = $GLOBALS['trickTiaoBoLiJian']['succ'];
                $caution = sprintf($GLOBALS['trickTiaoBoLiJian']['succ_caution'],$username,$targetcity);
                sql_query("update sys_city_hero set loyalty=GREATEST(0,loyalty-($loyalty_reduce)) where hid='$targethero[hid]'");
                
                
                /*
                if ($targethero['loyalty'] <= $loyalty_reduce)  //忠诚减到0了,可以招降了
                {
                    $loyalty_reduce = $targethero['loyalty'];
                    $mycid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
                    $myofficelevel = sql_fetch_one_cell("select level from sys_building where cid='$mycid' and bid=".ID_BUILDING_OFFICE." limit 1");
                    $mycityheroCount = sql_fetch_one_cell("select count(*) from sys_city_hero where cid='$mycid' and uid='$uid'");
                    if ($myofficelevel - $mycityheroCount > 0)  //有空位
                    {
                        sql_query("update sys_city_hero set uid='$uid',cid='$mycid',state=0,loyalty=10 where hid='$targethero[hid]'");
                        if ($targethero['state'] == 1)  //城守
                        {
                            sql_query("update sys_city set chiefhid=0 where cid='$targetcid'");
                            sql_query("update mem_city_resource set chief_loyalty=0 where cid='$targetcid'");
                        }
                        
                        $ret .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_surrender'],$targethero['name']);
                        $caution .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_caution_surrender'],$targethero['name']);
                    }
                    else
                    {
                        $ret .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_nooffice'],$targethero['name']);
                        $caution .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_caution_nooffice'],$targethero['name']); 
                    }
                }
                else
                */
                {
                     $ret .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_reduceloyalty'],$targethero['name'],$loyalty_reduce);
                     $caution .= sprintf($GLOBALS['trickTiaoBoLiJian']['succ_caution_reduceloyalty'],$targethero['name'],$loyalty_reduce); 
                }
                $caution .= $GLOBALS['trickTiaoBoLiJian']['succ_caution_tail']; 
                sql_query("insert into mem_hero_schedule (hid,last_trick) values ('$targethero[hid]',unix_timestamp()) on duplicate key update last_trick=unix_timestamp()");
            }
        }
    }
    sendReport($targetuid,"trick",TITLE_TRICK,$user['lastcid'],$targetcid,$caution);
    return $ret;
    
}
//十面埋伏：中计的敌人城池无法调动军队出城。1点智谋可持续10分钟。连续使用时间延长。
function trickShiMianMaiFu($uid,$targetcid,$wisdom)
{
   	$user=sql_fetch_one("select name,lastcid from sys_user where uid='$uid'");
    $username = $user['name'];
    $targetcity = getCityNamePosition($targetcid);  
    $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'");  
                                                                 
    $delta = sql_fetch_one_cell("select unix_timestamp() - last_trick_maifu from mem_city_schedule where cid='$targetcid'");
    if ($delta < 21600)
    {      
        $caution = sprintf($GLOBALS['trickShiMianMaiFu']['fail_caution'],$username,$targetcity);
        sendReport($targetuid,"trick",TITLE_TRICK,$user['lastcid'],$targetcid,$caution);
        return sprintf($GLOBALS['trickShiMianMaiFu']['fail'],$targetcity);
    }                                                                                        
    
    $delay = $wisdom * 60;

    sql_query("insert into mem_city_buffer (cid,buftype,bufparam,endtime) values ('$targetcid',8,0,unix_timestamp()+'$delay') on duplicate key update endtime=`endtime`+'$delay'");
    
    $endtime = sql_fetch_one_cell("select endtime from mem_city_buffer where cid='$targetcid' and buftype=8");
    sql_query("update mem_city_schedule set last_trick_maifu=unix_timestamp() where cid='$targetcid'");
    
    $ret = sprintf($GLOBALS['trickShiMianMaiFu']['succ'],$targetcity,MakeTimeLeft($delay));
    $caution = sprintf($GLOBALS['trickShiMianMaiFu']['succ_caution'],$username,$targetcity,$targetcity,MakeTimeLeft($delay),MakeEndTime($endtime));
    
    sendReport($targetuid,"trick",TITLE_TRICK,$user['lastcid'],$targetcid,$caution);
    return $ret;
}
//不宣而战
function trickBuXuanErZhan($uid,$targetcid,$wisdom)
{   
	
    $user = sql_fetch_one("select name,prestige,lastcid from sys_user where uid='$uid'"); 
    $username=$user['name'];
    $myprestige=$user['prestige'];
    $targetcity = getCityNamePosition($targetcid);  
    $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'"); 
    $targetuser= sql_fetch_one("select name,prestige from sys_user where uid='$targetuid'");
	$targetusername=$targetuser['name'];
	$targetprestige=$targetuser['prestige'];
    $delay = $wisdom * 60;
    sql_query("insert into mem_user_trickwar (uid,targetuid,endtime) values ('$uid','$targetuid',unix_timestamp()+$delay) on duplicate key update endtime=unix_timestamp()+$delay");
    sql_query("insert into mem_user_schedule (uid,last_trick_war) values ('$targetuid',unix_timestamp()) on duplicate key update last_trick_war=unix_timestamp()");
    
    if(empty($targetprestige))
    {
    	$prestige_reduce_rate=1;
    }
    else
    {
    	$prestige_reduce_rate = $myprestige / $targetprestige;
    }
    if ($prestige_reduce_rate > 5) $prestige_reduce_rate = 5;
    if ($prestige_reduce_rate < 1) $prestige_reduce_rate = 1;
    $prestige_reduce = $myprestige * $prestige_reduce_rate * 0.01;
    sql_query("update sys_user set warprestige=GREATEST(0,warprestige-$prestige_reduce) where uid='$uid'");
    //给自己的战报
    $now = sql_fetch_one_cell("select unix_timestamp()");
    $report = sprintf($GLOBALS['trickBuXuanErZhan']['succ_report'],$targetusername,
        MakeTimeLeft($delay),MakeEndTime($now),MakeEndTime($now + $delay),$prestige_reduce);
    sendReport($uid,"trick",24,$user['lastcid'],$targetcid,$report);
    
    //给对方的战报 
    $caution = sprintf($GLOBALS['trickBuXuanErZhan']['succ_caution'],$username,
        MakeTimeLeft($delay),MakeEndTime($now),MakeEndTime($now + $delay));  
     sendReport($targetuid,"trick",24,$user['lastcid'],$targetcid,$caution);
    return sprintf($GLOBALS['trickBuXuanErZhan']['succ'],$targetusername,MakeTimeLeft($delay));
}
//金蝉脱壳：让军队快速返回，缩短返回时间。1点智谋可缩短时间1分钟
function trickJinChaoTuoQiao($uid,$troop,$wisdom)
{
    if ($troop['state'] == 0)   //前进中的先返回
    {
        sql_query("update sys_troops set `state`=1,endtime=unix_timestamp() - starttime + unix_timestamp(),starttime=unix_timestamp() where id='$troop[id]'");
    }
    $delay = $wisdom * 60;
    sql_query("update sys_troops set endtime=endtime-'$delay',lastrun=unix_timestamp() where id='$troop[id]'");
    
    return $GLOBALS['trickJinChaoTuoQiao']['succ'];
}
//八门金锁：将一支敌人军队困住，使其行军时间延长。1点智谋可延长时间1分钟。中计军队在6小时内不会再次中计。
function trickBaMemJinShuo($uid,$troop,$wisdom)
{
     $now = sql_fetch_one_cell("select unix_timestamp()");
     $user = sql_fetch_one("select name,prestige,lastcid from sys_user where uid='$uid'"); 
     $username=$user['name'];
     if ($now - $troop['lastlock'] < 6 * 3600)
     {
         $caution = sprintf($GLOBALS['trickBaMemJinShuo']['fail_caution'],$username);
         sendReport($troop['uid'],'trick',23,$user['lastcid'],0,$caution);
         return $GLOBALS['trickBaMemJinShuo']['fail'];
     }
     if ($troop['hid'] > 0)
     {
         $hero = sql_fetch_one("select * from sys_city_hero where hid='$troop[hid]'");
         if (!empty($hero))
         {
               if (mt_rand(1,$wisdom + $hero['wisdom_base']+$hero['wisdom_add']) > $wisdom)
               {
                   
                   $caution = sprintf($GLOBALS['trickBaMemJinShuo']['fail_caution'],$username);
                   sendReport($troop['uid'],'trick',23,$user['lastcid'],0,$caution);
                   return $GLOBALS['useTrick']['fail_no_wisdom']; 
               }
         }
     }
     
     $delay = $wisdom * 60;
     sql_query("update sys_troops set endtime=endtime + $delay,lastlock=unix_timestamp() where id='$troop[id]'");
     
     $caution = sprintf($GLOBALS['trickBaMemJinShuo']['succ_caution'],$username,MakeTimeLeft($delay));
     sendReport($troop['uid'],'trick',23,$user['lastcid'],0,$caution);
     
     return sprintf($GLOBALS['trickBaMemJinShuo']['succ'],MakeTimeLeft($delay));
     
}
//关门打狗
function trickGuanMemDaGou($uid,$troop,$wisdom)
{
	$user=sql_fetch_one("select name,lastcid from sys_user where uid='$uid'");
    $username = $user['name'];
    if ($troop['hid'] > 0)
    {
         $hero = sql_fetch_one("select * from sys_city_hero where hid='$troop[hid]'");
         if (!empty($hero))
         {
               if (mt_rand(1,$wisdom + $hero['wisdom_base']+$hero['wisdom_add']) > $wisdom)
               {                   
                   $caution = sprintf($GLOBALS['trickGuanMemDaGou']['fail_caution'],$username);
                   sendReport($troop['uid'],'trick',23,$user['lastcid'],0,$caution);
                   return $GLOBALS['useTrick']['fail_no_wisdom']; 
               }
         }
    }
    sql_query("update sys_troops set noback=1 where id='$troop[id]'");
    
    $caution = sprintf($GLOBALS['trickGuanMemDaGou']['succ_caution'],$username);
    sendReport($troop['uid'],'trick',23,$user['lastcid'],0,$caution);
     
    return $GLOBALS['trickGuanMemDaGou']['succ'];
    
}
//千里奔袭
function trickQianLiBenXi($uid,$troop,$wisdom)
{
	$user=sql_fetch_one("select name,lastcid from sys_user where uid='$uid'");
    $username = $user['name'];
    if ($troop['hid'] > 0)
    {
    	$addrate=100/(100+$wisdom);
    	if(($troop['state']!=0)&&($troop['state']!=1)) throw new Exception($GLOBALS['trickQianLiBenXi']['wrong_state']);
	    sql_query("update sys_troops set endtime=unix_timestamp()+(endtime-unix_timestamp())*$addrate,lastacc=unix_timestamp() where id='$troop[id]'");
    }
    return $GLOBALS['trickQianLiBenXi']['succ'];
}


function useTrick($uid,$param)
{
    $hid = array_shift($param);
    $trickid = array_shift($param);
    $tricktype = array_shift($param);
    $tricktarget = array_shift($param);


    $trick = sql_fetch_one("select * from cfg_trick where id='$trickid'");
    if (empty($trick)||($trick['usetype'] != $tricktype))  throw new Exception($GLOBALS['useTrick']['trick_not_exist']);    
    
    lockUser($uid);
    $userjinnan = sql_fetch_one_cell("select sum(count) from sys_goods where uid='$uid' and gid=13"); 
    if ($userjinnan < $trick['cost'])
    {
        throw new Exception($GLOBALS['useTrick']['no_enough_bag']);
    }
    $hero = sql_fetch_one("select * from sys_city_hero where hid='$hid'");
    if (empty($hero)) throw new Exception($GLOBALS['useTrick']['hero_not_exist']);
    if($trickid!=13) //不是千里奔袭的话，用城里的智将
    {
    	if ($hero['state'] > 1&&$tricktype!=2) throw new Exception($GLOBALS['useTrick']['hero_not_incity']);
    }
    
    $wisdom = $hero['wisdom_base'] + $hero['wisdom_add'];
    if (isHeroHasBuffer($hid,4))    //智多星符
    {
        $wisdom = $wisdom * 1.25;
    }
    $wisdom=$wisdom+$hero['wisdom_add_on'];
    if ($tricktype == 0)
    {
        $targetcid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
    }
    else if ($tricktype == 1) //对城用
    {
        $targetcid = $tricktarget;
        
        $targetuser = sql_fetch_one("select * from sys_user where uid=(select uid from sys_city where cid='$targetcid')");
        if ($targetuser['uid'] == $uid)
        {
            throw new Exception($GLOBALS['useTrick']['target_is_mine']);
        }
        $myunion = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
        if (($targetuser['union_id'] == $myunion)&&($myunion > 0))
        {
            throw new Exception($GLOBALS['useTrick']['target_is_union']);
        }
        if(sql_check("select vacend from sys_user_state where uid='$targetuser[uid]' and vacend>unix_timestamp()")) //对方在休假
        {
        	throw new Exception($GLOBALS['useTrick']['target_in_vacation']);
        }
        if(sql_check("select forbiend from sys_user_state where uid='$targetuser[uid]' and forbiend>unix_timestamp()"))
        {
        	throw new Exception($GLOBALS['useTrick']['target_be_locked']);
        }
    }
    else if ($tricktype == 2)   //对己方军队使用的
    {
        $troopid = $tricktarget;
        $troop = sql_fetch_one("select * from sys_troops where id='$troopid'");
        if (empty($troop)||($troop['uid'] != $uid)) throw new Exception($GLOBALS['useTrick']['target_is_not_my_troop']);                                                                          
        if ($troop['hid'] == 0) throw new Exception($GLOBALS['useTrick']['target_has_no_hero']);
        if ($trickid == 10)
        {                      
            if ($troop['state'] > 1) throw new Exception($GLOBALS['useTrick']['target_is_not_on_way']);
            $now = sql_fetch_one_cell("select unix_timestamp()");            
            if ($now - $troop['lastrun'] < 3600) 
            {
                $msg = sprintf($GLOBALS['useTrick']['target_is_just_run'],MakeTimeLeft(3600-$now + $troop['lastrun']));
                throw new Exception($msg);
            }
        }
    }
    else if ($tricktype == 3)
    {
        $troopid = $tricktarget;
        $troop = sql_fetch_one("select * from sys_troops where id='$troopid'");
        if (empty($troop)||($troop['state'] != 0)) 
        {
            if ($trickid == 11)
            { 
                throw new Exception($GLOBALS['useTrick']['target_not_coming_1']);
            }
            else if ($trickid == 12)
            {
                throw new Exception($GLOBALS['useTrick']['target_not_coming_2']);
            }
        }
        
    }
    //不宣而战要先判断冷却期
    if ($trickid == 9)
    {
        $targetuid = sql_fetch_one_cell("select uid from sys_city where cid='$targetcid'");
        $now = sql_fetch_one_cell("select unix_timestamp()");
        $last_trick_war = sql_fetch_one_cell("select last_trick_war from mem_user_schedule where uid='$targetuid'");
        if ($now - $last_trick_war < 21600)
        {
            throw new Exception($GLOBALS['trickBuXuanErZhan']['cool_down']);
        }
    }
    else if ($trickid==10) //金蝉脱壳
    {
    	$now = sql_fetch_one_cell("select unix_timestamp()");
    	if($now-$troop['lastrun']<3600) throw new Exception($GLOBALS['trickJinChaoTuoQiao']['cool_down']);
    }
    else if($trickid==13) //千里奔袭
    {
    	$now = sql_fetch_one_cell("select unix_timestamp()");
    	if($now-$troop['lastacc']<3600) throw new Exception($GLOBALS['trickQianLiBenXi']['cool_down']);
    }
    $curEnergy=sql_fetch_one_cell("select energy from mem_hero_blood where hid='$hid'");
    if($curEnergy<$trick['cost'])
    {
    	throw new Exception($GLOBALS['useTrick']['hero_no_energy']);
    }
    sql_query("update mem_hero_blood set `energy`=GREATEST(0,`energy`-'$trick[cost]') where hid='$hid'");
    //先把锦囊扣掉
    addGoods($uid,13,-$trick['cost'],0);
    $trickmsg = "";
    $ret = array();
    $cantrick = true;
    if ($tricktype == 1)    //对敌城
    {
        if ($trickid != 7) //挑拨离间不跟城守比，而是跟其中一个将领比
        {
            $chiefhid = sql_fetch_one_cell("select chiefhid from sys_city where cid='$targetcid'");
            $chiefhero = sql_fetch_one("select * from sys_city_hero where hid='$chiefhid'");
            if (!empty($chiefhero))
            {
        		$chiefWisdom=$chiefhero['wisdom_base']+$chiefhero['wisdom_add'];
        	    if (isHeroHasBuffer($hid,4))    //智多星符
			    {
			        $chiefWisdom = $chiefWisdom * 1.25;
			    }
			    $chiefWisdom=$chiefWisdom+$chiefhero['wisdom_add_on'];
                if (mt_rand(1,$chiefWisdom) > $wisdom)
                {
                    $trickmsg = $GLOBALS['useTrick']['fail_no_wisdom'];
                    $cantrick = false;
                }
            }
        }
    }
    
    if ($cantrick)
    {
        mt_srand(time());
        switch($trickid)
        {
            case 1:
                $trickmsg = trickCaoMuJieBin($targetcid,$wisdom);
                break;
            case 2:
                $trickmsg = trickKongCheng($targetcid,$wisdom);
                break;
            case 3:
                $trickmsg = trickPaoZhuangYingYu($targetcid,$wisdom);
                break;
            case 4:
                $trickmsg = trickJinBiQingYe($targetcid,$wisdom);
                break;
            case 5:
                $trickmsg = trickAnDuChenChang($targetcid,$wisdom);
                break;
            case 6:
                $trickmsg = trickYaoYinHuoZhong($uid,$targetcid,$wisdom);
                break;
            case 7:
                $trickmsg = trickTiaoBoLiJian($uid,$targetcid,$wisdom);
                break;
            case 8:
                $trickmsg = trickShiMianMaiFu($uid,$targetcid,$wisdom);
                break;
            case 9:
                $trickmsg = trickBuXuanErZhan($uid,$targetcid,$wisdom);
                break;
            case 10:
                $trickmsg = trickJinChaoTuoQiao($uid,$troop,$wisdom);
                break;
            case 11:
                $trickmsg = trickBaMemJinShuo($uid,$troop,$wisdom);
                break;
            case 12:
                $trickmsg = trickGuanMemDaGou($uid,$troop,$wisdom);
                break;
            case 13:
            	$trickmsg =  trickQianLiBenXi($uid,$troop,$wisdom);
            	break;
            default:
                $trickmsg = $GLOBALS['useTrick']['trick_not_exist'];
        }      
    }                              
    unlockUser($uid); 
    completeTask($uid,88);                
                    
    $ret[] = $trickmsg;
    $ret[] = sql_fetch_one_cell("select sum(count) from sys_goods where uid='$uid' and gid=13");
    return $ret;
}

?>