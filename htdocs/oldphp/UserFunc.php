<?php                      
require_once("./interface.php");
require_once("./utils.php");
require_once("./GoodsFunc.php");
require_once("./HeroFunc.php");

function loadGlobalData($uid,$param)
{    
	$ret = array();
    $ret[] = sql_fetch_one_cell("select unix_timestamp()");
	$ret[] = sql_fetch_rows("select bid,name,description from cfg_building order by bid");
	$ret[] = sql_fetch_rows("select tid,name from cfg_technic order by tid");
	$ret[] = sql_fetch_rows("select * from cfg_soldier order by sid");
	$ret[] = sql_fetch_rows("select * from cfg_defence order by did");    
    $ret[] = sql_fetch_rows("select level,upgrade_exp,total_exp from cfg_hero_level order by level");
    $ret[] = sql_fetch_rows("select gid,name from cfg_goods where inuse=1");
    $ret[] = sql_fetch_rows("select tid,name from cfg_things where inuse=1");
    $ret[] = sql_fetch_rows("select `count`,`desc` from cfg_count_desc order by `count` desc");
    $ret[] = sql_fetch_rows("select `id`,`name` from cfg_office_pos order by id");
    $ret[] = sql_fetch_rows("select `id`,`name` from cfg_nobility order by id");
    $ret[] = chat_host;
    $ret[] = chat_port;
    $ret[] = SERVER_ID;
	return $ret;
}
function getUserCities($uid,$param)
{
    return sql_fetch_rows("select c.*,m.people,(select count(*) from sys_city_hero h where h.cid=c.cid) as heroes from sys_city c,mem_city_resource m where c.uid='$uid' and c.cid=m.cid"); 
}

function loadUserInfo($uid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_one("select u.*,n.name as unionname from sys_user u left join sys_union n on n.id=u.union_id where u.uid=$uid");
    $ret[] = sql_fetch_one("select * from sys_user_state where uid='$uid'");
    $ret[] = getUserCities($uid,$param);
    return $ret;
}

function loadUserDetail($uid,$param)
{
	$useruid=array_shift($param);
	$info=sql_fetch_one("select u.uid as userid,u.`name`,u.`sex`,u.`face`,u.`union_id`,u.`union_pos`,u.`officepos`,u.`nobility`,un.`name` as `union`, r.`rank`,r.`prestige`,r.`city`,r.`people` from `sys_user` u left join `sys_union` un on un.`id`=u.`union_id` left join `rank_user` r on u.`uid`=r.`uid` where u.`uid`='$useruid'");
	$ret=array();
	$ret[]=$info;
	return $ret;
}

function loadUserDetailInfo($uid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_one_cell("select sum(m.people) from mem_city_resource m,sys_city c where m.cid=c.cid and c.uid='$uid'"); 
    $ret[] = sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid'"); 
    $ret[] = sql_fetch_one_cell("select state from sys_user where uid='$uid'");
    $ret[] = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=7");
    $ret[] = getUserCities($uid,$param);
    return $ret;
}
function doCreateCity($uid,$cityname,$oriprovince,$use_backup)
{
	if(stripos($cityname,'\'')!=false) throw new Exception($GLOBALS['changeCityName']['name_illegal']);
	
	if($oriprovince==0)
	{
	//	$cityCnt = sql_fetch_column("select count(*) from mem_world where type=0 or (type=1 and ownercid>0) and province > 0 group by province order by province");
   // 	$totalCnt = sql_fetch_column("select count(*) from mem_world where type < 2 and province > 0 group by province order by province");
		
		$province=intval(mt_rand(1,13));
		/*$maxRate=0;
		for($i=0;$i<13;$i++)
		{
			$rate=$cityCnt[$i]/$totalCnt[$i];
			if($rate<0.65&&$rate>$maxRate) $province=($i+1);
			$debugstr.=$rate."/";
		}*/
	}
	else
	{
    	$province = $oriprovince;
    }
    $provinceLandCount = sql_fetch_one_cell("select count(*) from mem_world where type=1 and ownercid=0 and province='$province' and state=0");
    
    if ($provinceLandCount == 0)
    {
    	if($oriprovince==0)
        {
        	$tryCount=0;
        	do
        	{
        		$province=intval(mt_rand(1,13));
        		$provinceLandCount = sql_fetch_one_cell("select count(*) from mem_world where type=1 and ownercid=0 and province='$province' and state=0");
        		$tryCount++;
        	}while($tryCount<10&&$provinceLandCount==0);
        	
        }
        if($provinceLandCount==0)
    	{
        	throw new Exception($GLOBALS['doCreateCity']['province_is_full']);
        }
    }
    else
    {
    	$targetcid=sql_fetch_one_cell("select cid from sys_city where uid>897 and province='$province' order by rand() limit 1");
    	if(empty($targetcid))
    	{
    		$targetwid=sql_fetch_one_cell("select wid from mem_world where type=0 and province='$province' order by rand() limit 1");
    		if(empty($targetwid))
    		{
    			$targetwid=sql_fetch_one_cell("select wid from mem_world where province='$province' order by rand() limit 1");
    		}
	    	$targetcid=wid2cid($targetwid);
	    }
    	$ypos=floor($targetcid/1000);
    	$xpos=floor($targetcid-$ypos*1000);
		
		$xrange=15;
		$yrange=15;

		$xmin=floor(($xpos-$xrange)/10);
		$xmax=floor(($xpos+$xrange)/10);

		$ymin=floor(($ypos-$yrange)/10);
		$ymax=floor(($ypos+$yrange)/10);
		
		$widarray=array();
		for($j=$ymin;$j<=$ymax;$j++)
		{
			for($k=$xmin;$k<=$xmax;$k++)
			{
				$widarray[]=($j*100+$k)*100;
			}
		}
    	
    	$arrsize=count($widarray);
    	if($arrsize==0) throw new Exception($GLOBALS['doCreateCity']['reType_city_name']);
    	$tryCount=0;
    	do
    	{
	    	$minwid=$widarray[mt_rand(0,$arrsize-1)];
	    	$maxwid=$minwid+100;
	    	
	        $wid = sql_fetch_one_cell("select wid from mem_world where type=1 and province='$province' and ownercid=0 and state=0 and wid>'$minwid' and wid<'$maxwid' order by rand() limit 1");
	        $tryCount++;
        }while(empty($wid)&&$tryCount<15);
        if(empty($wid)) throw new Exception($GLOBALS['doCreateCity']['reType_city_name']);
        $cid = wid2cid($wid);
        
        if (sql_check("select * from sys_city where cid='$cid'"))
        {                                                                           
             throw new Exception($GLOBALS['doCreateCity']['reType_city_name']);
        }
        else if (sql_check("select * from sys_city where uid='$uid' limit 1"))
        {
        	throw new Exception($GLOBALS['createRole']['cant_duplicate_create']);
        }
        else
        {
        	//清除在该地的武将和军队
        	$hero=sql_fetch_one("select hid from sys_city_hero where uid=0 and cid='$cid'");
        	if(!empty($hero))
        	{
        		throwHeroToField($hero);
        	}
        	sql_query("delete from sys_troops where uid=0 and state=4 and cid='$cid'");
            //修改所在地的属性
            sql_query("update mem_world set ownercid='$cid',`type`='0' where wid='$wid'");
            //新建城池
            sql_query("replace into sys_city (`cid`,`uid`,`name`,`type`,`state`,`province`) values ('$cid','$uid','$cityname','0','2','$province')");                                                                          
            //自动建设1级官府
            
            if($use_backup)
            {
            	
            	sql_query("replace into sys_building (`cid`,`xy`,`bid`,`level`) (select $cid,xy,bid,level from sys_building_backup where uid='$uid')");

            	$buildingCount=sql_fetch_one_cell("select count(*) from sys_building where cid='$cid'");
            	if(empty($buildingCount))
            	{
            		sql_query("replace into sys_building (`cid`,`xy`,`bid`,`level`) values ('$cid','120','6','1')");
            	}
            }
            else
            {
            	sql_query("delete from sys_building where `cid`='$cid'");
            	sql_query("replace into sys_building (`cid`,`xy`,`bid`,`level`) values ('$cid','120','6','1')");
            }
            
            sql_query("replace into sys_city_res_add (cid,food_rate,wood_rate,rock_rate,iron_rate,chief_add) values ('$cid',80,80,80,80,0)");

            //添加一定的资源
            sql_query("replace into mem_city_resource (`cid`,`people`,`food`,`wood`,`rock`,`iron`,`gold`,`food_max`,`wood_max`,`rock_max`,`iron_max`,`gold_max`,`food_add`,`wood_add`,`rock_add`,`iron_add`,`lastupdate`) values ('$cid','50','5000','5000','5000','5000','5000','10000','10000','10000','10000','1000000',100,100,100,100,unix_timestamp())");
            
            //城池定时器
            sql_query("replace into mem_city_schedule (`cid`,`create_time`,`next_good_event`,`next_bad_event`) values ('$cid',unix_timestamp(),unix_timestamp()-(unix_timestamp()+8*3600)%86400 + 86400 + 86400 * rand(),unix_timestamp()-(unix_timestamp()+8*3600)%86400 + 86400 + 86400 * rand())");         
            
            sql_query("insert into mem_user_schedule (uid,start_new_protect) values ('$uid',unix_timestamp()) on duplicate key update start_new_protect=unix_timestamp()");
            if($use_backup)
            {
            	updateCityResourceAdd($cid);
            	updateCityPeopleMax($cid);
            	updateCityGoldMax($cid);
            }
            return $cid;
        }               
    }
}

function createCity($uid,$param)
{
    $cityname = array_shift($param);
    $province = array_shift($param);    
    if (mb_strlen($cityname,"utf-8") > MAX_CITY_NAME)
	{
		throw new Exception($GLOBALS['createCity']['city_name_tooLong']);
	}
    else if ((!(strpos($cityname,'\'')===false))||(!(strpos($cityname,'\\')===false)))
    {
    	throw new Exception($GLOBALS['changeCityName']['name_illegal']);
    }
    else  if (sql_check("select * from cfg_baned_name where instr('$cityname',`name`)>0"))
    {
    	throw new Exception($GLOBALS['changeCityName']['name_illegal']);
    }
    $cityCount=sql_fetch_one_cell("select count(*) from sys_city where uid='$uid'");
    if(!empty($cityCount)||$cityCount>0)
    {
    	throw new Exception($GLOBALS['createRole']['cant_duplicate_create']);
    }
    $cityname=addslashes($cityname);
    $cid = doCreateCity($uid,$cityname,$province,true);
    sql_query("update sys_user set state=0, lastcid='$cid' where uid='$uid'");
    
    $mailTitle=$GLOBALS['sys']['restart_mail_title'];
    $sql="insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) values ('$uid','2','$mailTitle','0',unix_timestamp())";
    sql_insert($sql);
    sql_query("insert into sys_alarm (`uid`,`mail`) values ('$uid',1) on duplicate key update `mail`=1");
}

function createRole($uid,$param)
{
    $username = trim(array_shift($param));
    $cityname = trim(array_shift($param));
    $province = array_shift($param);
    $flagchar = array_shift($param);
    $sex = array_shift($param);
    $face = array_shift($param);
    $code = array_shift($param);
    $code = addslashes($code);
    
  /*  
    if (!preg_match("/^[0-9a-zA-Z]{10}$/",$code))
    {
         throw new Exception("无效的封测验证码!");
    }
    
    if (!sql_check("select * from sys_auth_serial where code='$code' and `used`=0"))
    {
         throw new Exception("该封测验证码已被使用!");
    }             
      */         
    
    
    $userstate = sql_fetch_one_cell("select state from sys_user where uid='$uid'");
    if ($userstate != 3)
    {
        throw new Exception($GLOBALS['createRole']['cant_duplicate_create']);
    }
                                                     
      
    if (mb_strlen($username) < 1) throw new Exception($GLOBALS['createRole']['city_holder_name_notNull']);   
    if (mb_strlen($username,"utf-8") > MAX_USER_NAME) throw new Exception($GLOBALS['createRole']['city_holder_name_tooLong']);
    if (mb_strlen($cityname,"utf-8") > MAX_CITY_NAME) throw new Exception($GLOBALS['createRole']['city_name_tooLong']);
          
    if (!(strpos($username,"<")===FALSE))
    {
    	throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if (!(strpos($username,"'")===FALSE))
    {
        throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if ((!(strpos($username,'\'')===false))||(!(strpos($username,'\\')===false)))
    {
        throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if (sql_check("select * from cfg_baned_name where instr('$username',`name`)>0"))
    {
        throw new Exception($GLOBALS['createRole']['invalid_char']);        
    } 
    if (mb_strlen($cityname,"utf-8") > MAX_CITY_NAME)
	{
		throw new Exception($GLOBALS['createCity']['city_name_tooLong']);
	}
    else if ((!(strpos($cityname,'\'')===false))||(!(strpos($cityname,'\\')===false)))
    {
    	throw new Exception($GLOBALS['changeCityName']['name_illegal']);
    }
    else  if (sql_check("select * from cfg_baned_name where instr('$cityname',`name`)>0"))
    {
    	throw new Exception($GLOBALS['changeCityName']['name_illegal']);
    }
    $flagcharlen = mb_strlen($flagchar);
    if ($flagcharlen == 0)
    {
        throw new Exception($GLOBALS['createRole']['enter_flag_char']);
    }
    else if ($flagcharlen > 1)
    {
        throw new Exception($GLOBALS['createRole']['single_char']);
    }
    $username=addslashes($username);
    $cityname=addslashes($cityname);
    $flagchar=addslashes($flagchar);
    //锁sys_user表
    sql_query("lock tables sys_user write");
    if (sql_check("select * from sys_user where name='$username' and uid <> '$uid'"))
    {
        //解锁
        sql_query("unlock tables");
        throw new Exception($GLOBALS['createRole']['used_city_holder_name']);
        
    }
    //解锁
    sql_query("unlock tables");
    
    $cid = doCreateCity($uid,$cityname,$province,false);
    
    //玩家进入新手保护状态
    sql_query("update sys_user set `state`=1,lastcid='$cid',`name`='$username',face='$face',sex='$sex',flagchar='$flagchar' where `uid`='$uid'");
    sql_insert("insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) values ('$uid','1','欢迎来到《热血三国》','0',unix_timestamp())");
    sql_query("insert into sys_alarm (`uid`,`mail`) values ('$uid',1) on duplicate key update `mail`=1");
    sql_query("insert into sys_user_task (uid,tid,state) values ('$uid',1,0) on duplicate key update state=state");
    //送新手礼包和升级礼包
    sql_query("insert into sys_goods (uid,gid,count) values ('$uid','10001',1)");
    sql_query("insert into sys_goods (uid,gid,count) values ('$uid','10002',1)");
//  		sql_query("update sys_auth_serial set `used`=1 where code='$code'");                          

}


//修改玩家状态
function changeUserState($uid,$param)
{
    $stateidx = intval(array_shift($param));
    $password = array_shift($param);
    $day=intval(array_shift($param));
	
	if($stateidx<0||$stateidx>2) throw new Exception($GLOBALS['sendCommand']['command_not_found']);
	
    if (!checkUserPassport($uid,$password))
    {
        throw new Exception($GLOBALS['changeUserState']['invalid_pwd']);
    }
    
    $targetstate = 0;
    if ($stateidx == 1) $targetstate = 2;//免战
    else if ($stateidx==2) $targetstate=6;//休假
    
    if($targetstate==6)
    {
    	if($day<2||$day>99) throw new Exception($GLOBALS['changeUserState']['vacation_limit']);
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
        $cost=$day*5+20;
        $mymoney=sql_fetch_one_cell("select money from sys_user where uid='$uid'");
     	if($cost>$mymoney)
     	{
     		throw new Exception($GLOBALS['sys']['not_enough_money']);
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
            /*if(sql_check("select uid from sys_troops where targetcid='$city[cid]' and state=4 and task=1")) //盟友在城池驻军
            {
            	throw new Exception($GLOBALS['changeUserState']['union_army_in_city'].$GLOBALS['changeUserState']['xiujia']);
            }*/
        }
        //开始休假，并扣钱
        $vactime=$day*86400;
        sql_query("insert into sys_user_state (uid,vacstart,vacend) values ('$uid',unix_timestamp(),unix_timestamp()+'$vactime') on duplicate key update vacstart=unix_timestamp(),vacend=unix_timestamp()+'$vactime'");
        sql_query("update mem_city_resource set vacation=1 where cid in ($mycids)");
        
     	sql_query("update sys_user set money=GREATEST(money-'$cost',0) where uid='$uid'");
     	sql_query("insert into log_money (`uid`,`count`,`time`,`type`) values ('$uid',-$cost,unix_timestamp(),75)");
     
     	$ret=array();
	    $ret[]=$targetstate;
		$ret[]=$vactime;
		return $ret;
    }
    else
    {
	    $userstate = sql_fetch_one_cell("select state from sys_user where uid='$uid'");
    	$invacation=false;
	    $vacend=sql_fetch_one_cell("select vacend from sys_user_state where uid='$uid' and vacend>unix_timestamp()");
    	if(!empty($vacend)) //从休假到正常
    	{
    		$invacation=true;
    	}
	    if(($targetstate==0)&&$invacation) //从休假到正常
	    {
	    	$delta=sql_fetch_one_cell("select vacstart+86400*2-unix_timestamp() from sys_user_state where uid='$uid'");
	    	if ((!empty($delta))&&$delta>0)
	    	{
	    		$msg=sprintf($GLOBALS['changeUserState']['vacation_cant_dismiss'],MakeTimeLeft($delta));
	    		throw new Exception($msg);
	    	}
    		sql_query("update sys_user_state set vacend=unix_timestamp() where uid='$uid'");
    		sql_query("update mem_city_resource set vacation=1 where cid in (select cid from sys_city where uid='$uid')");
    		return array();
	    }
	    else if (($userstate == 0)&&($targetstate==0))
	    {
	        throw new Exception($GLOBALS['changeUserState']['no_need_recovery']);
	    }
	    else if (($userstate == 2)&&($targetstate == 2))
	    {
	        throw new Exception($GLOBALS['changeUserState']['no_need_MianZhanPai']);
	    }                                   
	    else if (($userstate == 0)&&($targetstate == 2)) //从正常到免战
	    {
	        //查看是否有免战冷却BUFFER
	        $bufendtime = sql_fetch_one_cell("select endtime from mem_user_buffer where uid='$uid' and buftype=8"); 
	        if (!empty($bufendtime))
	        {
	            $delta = $bufendtime - sql_fetch_one_cell("select unix_timestamp()");
	            throw new Exception(MakeTimeLeft($delta).$GLOBALS['changeUserState']['wait_to_use_MianZhanPai']);
	        }                                
	        //某城池在战乱时不能使用
	        $mycities = sql_fetch_rows("select cid from sys_city where uid='$uid' and type=0");
	        foreach($mycities as $city)
	        {
	            if (sql_check("select * from mem_world where wid=".cid2wid($city['cid'])." and state=1"))
	            {
	                throw new Exception($GLOBALS['changeUserState']['some_city_in_war'].$GLOBALS['changeUserState']['mianzhan']);
	            }
	        }
	 /*     //现在不需要了。。。                                      
	        //有军队在外时不能免战
	        if (sql_check("select * from sys_troops where uid='$uid'"))
	        {
	            throw new Exception("你还有军队在外征战，不能免战。");
	     }
	  */         
	        UseMianZhanPai($uid);              
	    }
	    else if (($userstate == 2)&&($targetstate == 0)) //从免战到正常
	    {
	        sql_query("delete from mem_user_buffer where uid='$uid' and buftype=7");
	        $buftime = 6 * 3600;
	        sql_query("insert into mem_user_buffer (uid,buftype,endtime) values ('$uid',8,unix_timestamp() + $buftime) on duplicate key update endtime=unix_timestamp()+$buftime ");
	        
	        sql_query("update sys_user set state=0 where uid='$uid'");
	    }
    }
    $ret=array();
    $ret[]=$targetstate;
	$ret[]=loadUserGoods($uid,$param);
	return $ret;
}
function changeUserFlagchar($uid,$param)
{
    $newchar = addslashes(array_shift($param));
    useFlagChar($uid,$newchar);
    $ret = array();
    $ret[] = $newchar;
    return $ret;
}

function changeUserName($uid,$param)
{
	$username=array_shift($param);
	if (!(strpos($username,"<")===FALSE))
    {
    	throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if (!(strpos($username,"'")===FALSE))
    {
        throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if ((!(strpos($username,'\'')===false))||(!(strpos($username,'\\')===false)))
    {
        throw new Exception($GLOBALS['createRole']['no_illege_char']);
    }
    else if (sql_check("select * from cfg_baned_name where instr('$username',`name`)>0"))
    {
        throw new Exception($GLOBALS['createRole']['invalid_char']);        
    }
    $username=addslashes($username);
    if(sql_check("select name from sys_user where name='$username'"))
    {
    	throw new Exception($GLOBALS['createRole']['used_city_holder_name']);
    }
    useMingTie($uid,$username);
    $ret=array();
    $ret[]=$username;
    return $ret;
}

function loadProvinceInfo($uid,$param)
{
    $ret = array();
    $ret[] = sql_fetch_column("select count(distinct(uid)) from sys_city where province > 0 group by province order by province");
    $ret[] = sql_fetch_column("select count(*) from mem_world where type=0 or (type=1 and ownercid>0) and province > 0 group by province order by province");
    $ret[] = sql_fetch_column("select count(*) from mem_world where type < 2 and province > 0 group by province order by province");
    return $ret;
}
function changeCityPosition($uid,$param)
{
	$oriprovince=intval(array_shift($param));
	if($oriprovince==0)
	{
		$province=intval(mt_rand(1,13));
	}
	else
	{
    	$province = $oriprovince;
    }
    
    $provinceLandCount = sql_fetch_one_cell("select count(*) from mem_world where type=1 and ownercid=0 and province='$province'");
    if ($provinceLandCount == 0)
    {
    	if($oriprovince==0)
        {
        	$tryCount=0;
        	do
        	{
        		$province=intval(mt_rand(1,13));
        		$provinceLandCount = sql_fetch_one_cell("select count(*) from mem_world where type=1 and ownercid=0 and province='$province'");
        		$tryCount++;
        	}while(($tryCount<10)&&($provinceLandCount==0));
        	
        }
        if($provinceLandCount==0)
    	{
        	throw new Exception($GLOBALS['changeCityPosition']['province_is_full']);
        }
    }
    $tryCount=0;
    
	do
	{
    	$targetwid = sql_fetch_one_cell("select wid from mem_world where type=1 and province='$province' and ownercid=0 and state=0 order by rand() limit 1");
    	$tryCount++;
    }while(empty($targetwid)&&$tryCount<10);
    if(empty($targetwid)) throw new Exception($GLOBALS['changeCityPosition']['invalid_target_city']);
    $targetcid = wid2cid($targetwid);
    
    if (!checkGoodsCount($uid,24,1)) throw new Exception($GLOBALS['changeCityPosition']['no_QianChengLing']);

    $cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
    
    doChangeCityPosition($uid,$cid,$targetcid);
    
    reduceGoods($uid,24,1); 
    $ret = array();
    $ret[] = $targetcid;
    return $ret;
}

function changeCityPositionPointing($uid,$param)
{
	$xpos=intval(array_shift($param));
	$ypos=intval(array_shift($param));
	if($xpos<0||$ypos<0||$xpos>500||$ypos>500)
	{
		throw new Exception($GLOBALS['changeCityPosition']['invalid_target_city']);
	}
	$targetcid=$ypos*1000+$xpos;
    if (!checkGoodsCount($uid,82,1)) throw new Exception($GLOBALS['changeCityPosition']['no_adv_QianChengLing']);
    
    $cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");    
    
    doChangeCityPosition($uid,$cid,$targetcid);
    
    sql_query("insert into mem_city_schedule (cid,last_adv_move) values ('$targetcid',unix_timestamp()) on duplicate key update last_adv_move=unix_timestamp()");
    
    reduceGoods($uid,82,1); 
    $ret = array();
    $ret[] = $targetcid;
    return $ret;
}
?>