<?php                      
require_once("./interface.php");
require_once("./utils.php");

function getUnionFamousCityGold($uid)
{
	$unionid=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
	if($unionid==0) return 0;
	$cities=sql_fetch_rows("select type,count(*) from sys_city c, sys_user u where u.uid=c.uid and u.union_id='$unionid' and c.type>0 group by c.type");
	$gold=0;
	if(!empty($cities))
	{
		foreach($cities as $city)
		{
			if($city['type']==1) $gold=$gold+10000;
			else if($city['type']==2) $gold=$gold+30000;
			else if($city['type']==3) $gold=$gold+100000;
			else if($city['type']==4) $gold=$gold+300000;
		}
	}
	return $gold;
}

function checkGoalComplete($uid,$goal)
{
    if (!empty($goal['uid']))
    {
        return true;
    }

    if ($goal['sort'] == 1)    //资源  1黄金,  2粮食,3木材,4石料,5铁锭,6人口,7民心,8民怨,9声望,
    {
        if ($goal['type'] == 9)     //声望
        {
            return sql_fetch_one_cell("select prestige from sys_user where uid='$uid'") >= $goal['count']; 
        }
        else if ($goal['type'] == 11)       //联盟人数
        {   
            $union_id  = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
            if ($union_id == 0) return false;
            
            return sql_fetch_one_cell("select member from sys_union where id='$union_id'") >= $goal['count'];
        }
        else if ($goal['type'] == 17)   //官职
        {
            return sql_fetch_one_cell("select officepos from sys_user where uid='$uid'") >= $goal['count'];
        }
        else if ($goal['type'] == 18)   //爵位
        {
            return sql_fetch_one_cell("select nobility from sys_user where uid='$uid'") >= $goal['count'];
        }
        else if ($goal['type'] == 19)
        {
            return sql_fetch_one_cell("select money from sys_user where uid='$uid'") >= $goal['count'];
        }
        else if ($goal['type']==20)
        {
        	return sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type>0")>=$goal['count'];
        }
        else if ($goal['type']==21)
        {
        	$union_id  = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
            if ($union_id == 0) return false;
            return sql_fetch_one_cell("select count(*) from sys_city where type>0 and uid in (select uid from sys_user where union_id='$union_id')")>=$goal['count'];
        }
        else if (($goal['type'] >= 12)&&($goal['type'] <= 15))  //四种基础产量
        {
            $lastcid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
            if ($goal['type'] == 12)
            {
                return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_FARMLAND) >= $goal['count'];
            }
            else if ($goal['type'] == 13)
            {
                return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_WOOD) >= $goal['count'];
            }
            else if ($goal['type'] == 14)
            {
                return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_ROCK) >= $goal['count'];
            }
            else if ($goal['type'] == 15)
            {
                return sql_fetch_one_cell("select sum(b.level*(b.level+1)*50) from sys_city c,sys_building b where c.cid='$lastcid' and b.cid='$lastcid' and b.bid=".ID_BUILDING_IRON) >= $goal['count'];
            }     
        }
        else
        {
            $cityres = sql_fetch_one("select c.* from mem_city_resource c,sys_user u where c.cid=u.lastcid and u.uid='$uid'");
            switch ($goal['type'])
            {
                case 1:
                    return $cityres['gold'] >= $goal['count'];
                case 2:
                    return $cityres['food'] >= $goal['count'];
                case 3:
                    return $cityres['wood'] >= $goal['count']; 
                case 4:
                    return $cityres['rock'] >= $goal['count']; 
                case 5:
                    return $cityres['iron'] >= $goal['count']; 
                case 6:
                    return $cityres['people'] >= $goal['count']; 
                case 7:
                    return $cityres['morale'] >= $goal['count']; 
                case 8:
                    return $cityres['complaint'] >= $goal['count']; 
                case 10:    //人口上限
                    return $cityres['people_max'] >= $goal['count'];
                case 16:    //黄金产量
                    return ($cityres['people']*$cityres['tax']*0.01) >= $goal['count'];
            }
        }                
    }
    else if ($goal['sort'] == 2)   //2:宝物  
    {
        return sql_fetch_one_cell("select count from sys_goods where uid='$uid' and gid='$goal[type]'") >= $goal['count'];
    }
    else if ($goal['sort'] == 3)  //3:军队    
    {
        return sql_fetch_one_cell("select count from sys_city_soldier s,sys_user u where u.uid='$uid' and u.lastcid=s.cid and s.sid='$goal[type]'") >= $goal['count'];
    }
    else if ($goal['sort'] == 4)   //4:城防     
    {
        return sql_fetch_one_cell("select count from sys_city_defence d,sys_user u where u.uid='$uid' and u.lastcid=d.cid and d.did='$goal[type]'") >= $goal['count'];
    }
    else if ($goal['sort'] == 5)  //5:任务物品  
    {
        return sql_fetch_one_cell("select count from sys_things where uid='$uid' and tid='$goal[type]'") >= $goal['count'];
    }
    else if ($goal['sort'] == 8)	//8:名城
    {
    	return sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type='$goal[type]'")>=$goal['count'];
    }
    else if($goal['sort'] == 9)	//9:名将
    {
    	return sql_fetch_one_cell("select count(*) from sys_city_hero where uid='$uid' and npcid='$goal[type]'")>=$goal['count'];
    }
    
    return false;
}          
function checkTaskComplete($uid,&$tasklist)
{
    foreach($tasklist as &$task)
    {
        $goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$task[id]'");
       
        $complete = true;
        foreach($goals as $goal)
        {
            if (!checkGoalComplete($uid,$goal))
            {
                $complete = false;
                break;
            }
        }
        
        $task['state'] = $complete;
    }
}

function dropTask($uid,$param)
{
	$taskgroup=array_shift($param);
	$npcid=intval(floor(($taskgroup-20000)/10));
	$taskid1=20000+$npcid;
	$taskid2=30000+$npcid;
	sql_query("delete from sys_user_goal where uid='$uid' and gid in (select id from cfg_task_goal where (tid='$taskid1' or tid='$taskid2'))");
    sql_query("delete from sys_user_task where uid='$uid' and (tid='$taskid1' or tid='$taskid2')");
    $param2=array();
    $param2[]=2;
    return getTaskTypeGroupList($uid,$param2);
}

function getTaskTypeGroupList($uid,$param)
{
	$type=intval(array_shift($param));
	if($type<0||$type>3) $type=0;
	$ret = array();
    $ret[] =  sql_fetch_rows("select * from cfg_task_group where id in (select distinct(t.`group`) as id from sys_user_task u,cfg_task t where u.tid=t.id and u.uid='$uid' and u.state=0) and type='$type' order by id");
    return $ret;
}

function getTaskList($uid,$param)
{
    $groupid = array_shift($param);   
    $tasklist = sql_fetch_rows("select t.* from sys_user_task u,cfg_task t where u.uid='$uid' and u.tid=t.id and t.group='$groupid' and u.state=0");                 
    checkTaskComplete($uid,$tasklist);
    
    $ret = array();
    $ret[] = $tasklist;
    return $ret;
}

function getTaskDetail($uid,$param)
{                                 
    $tid = array_shift($param);  
    $ret = array();

    $ret[] = sql_fetch_one("select * from cfg_task where id='$tid'");
    
    $goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$tid' order by g.id");

    foreach ($goals as &$goal)
    {
         $goal['state'] = checkGoalComplete($uid,$goal);
    }
    if ($tid == 250)    //食君之禄
    {
        foreach ($goals as &$goal)
        {
             $goal['content'] = sql_fetch_one_cell("select p.name from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'");           
        }    
    }
    else if ($tid == 251)   //采食封邑
    {
        foreach ($goals as &$goal)
        {
             $goal['content'] = sql_fetch_one_cell("select n.name from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");
        }
    }
    
    
    $ret[] = $goals;
    
    if ($tid == 250)//食君之禄   
    {
        $salary = sql_fetch_one_cell("select p.salary from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'"); 
        $reward = array();
        $reward[] = array('sort'=>1,'type'=>1,'count'=>$salary);
        $ret[] = $reward;
    }
    else if ($tid == 251)  //采食封邑
    {
        $salary = sql_fetch_one_cell("select n.salary from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");   
        $reward = array();
        $reward[] = array('sort'=>1,'type'=>2,'count'=>$salary);
        $reward[] = array('sort'=>1,'type'=>3,'count'=>$salary);
        $reward[] = array('sort'=>1,'type'=>4,'count'=>$salary);
        $reward[] = array('sort'=>1,'type'=>5,'count'=>$salary);
        $ret[] = $reward;
    }
    else if ($tid==279)	//共享利益
    {
    	$gold=getUnionFamousCityGold($uid);
    	$reward=array();
        $reward[] = array('sort'=>1,'type'=>1,'count'=>$gold);
        $ret[] = $reward;
    }
    else
    {
        $ret[] = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
    }

    
    return $ret;
}      
function giveResource($uid,$cid,$type,$count)
{                                                                                           
    switch($type)
    {
        case 1:                                                                                   
            sql_query("update mem_city_resource set gold=gold+'$count' where cid='$cid'");
            break;
        case 2:                                                                         
            sql_query("update mem_city_resource set food=food+'$count' where cid='$cid'");
            break;
        case 3:           
            sql_query("update mem_city_resource set wood=wood+'$count' where cid='$cid'");  
            break;
        case 4:
            sql_query("update mem_city_resource set rock=rock+'$count' where cid='$cid'");
            break;
        case 5:
            sql_query("update mem_city_resource set iron=iron+'$count' where cid='$cid'");
            break; 
        case 6:                                                                          
            sql_query("update mem_city_resource set people=people+'$count' where cid='$cid'");
            break;   
        case 7:                                                                               
            sql_query("update mem_city_resource set morale=LEAST(100,morale+'$count') where cid='$cid'");
            break;   
        case 8:                                                                               
            sql_query("update mem_city_resource set complaint=GREATEST(0,complaint+'$count') where cid='$cid'");
            break;     
        case 9:                                                                                      
            sql_query("update sys_user set prestige=prestige+'$count',warprestige=warprestige+'$count' where uid='$uid'");
            break;
        case 17:
            sql_query("update sys_user set officepos='$count' where uid='$uid'");
            break;                 
        case 18:
            sql_query("update sys_user set nobility='$count' where uid='$uid'");
            break;
        case 19:
            addMoney($uid,$count,60);
            break;
    }
}
function giveGoods($uid,$type,$count)
{
   sql_query("insert into sys_goods (uid,gid,`count`) values ('$uid','$type','$count') on duplicate key update `count`=`count`+'$count'"); 
   sql_query("insert into log_goods (`uid`,`gid`,`count`,`time`,`type`) values ('$uid','$type','$count',unix_timestamp(),4)");
}
function giveArmy($cid,$type,$count)
{
    sql_query("insert into sys_city_soldier (`cid`,`sid`,`count`) values ('$cid','$type','$count') on duplicate key update `count`=`count`+'$count'");
	updateCityResourceAdd($cid);
}
function giveDefence($cid,$type,$count)
{
    sql_query("insert into sys_city_defence (`cid`,`did`,`count`) values ('$cid','$type','$count') on duplicate key update `count`=`count`+'$count'");
}
function giveThings($uid,$type,$count)
{
   sql_query("insert into sys_things (`uid`,`tid`,`count`) values ('$uid','$type','$count') on duplicate key update `count`=`count`+'$count'");    
}

function giveArmor($uid,$type,$count)
{
	$armor=sql_fetch_one("select * from cfg_armor where id='$type'");
	for($i=0;$i<$count;$i++)
	{
		sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ($uid,$armor[id],$armor[ori_hp_max]*10,$armor[ori_hp_max],0)");
	}
	sql_query("insert into log_armor (uid,armorid,count,time,type) values ($uid,$armor[id],$count,unix_timestamp(),4)");
}

//给任务奖励
function giveReward($uid,$cid,$reward)
{                                       
    if ($reward['sort'] == 1)
    {
        giveResource($uid,$cid,$reward['type'],$reward['count']);
    }
    else if ($reward['sort'] == 2)
    {
        giveGoods($uid,$reward['type'],$reward['count']);
    }
    else if ($reward['sort'] == 3)
    {
        giveArmy($cid,$reward['type'],$reward['count']);
    }
    else if ($reward['sort'] == 4)
    {
        giveDefence($cid,$reward['type'],$reward['count']);
    }
    else if ($reward['sort'] == 5)
    {
        giveThings($uid,$reward['type'],$reward['count']);
    }
    else if ($reward['sort']==6)
    {
    	giveArmor($uid,$reward['type'],$reward['count']);
    }
}
function reduceGoal($uid,$cid,$goal)
{
    switch ($goal['sort'])
    {
        case 1: 
            giveResource($uid,$cid,$goal['type'],-$goal['count']);
            break;
        case 2: 
            giveGoods($uid,$goal['type'],-$goal['count']);
            break;
        case 3:
            giveArmy($cid,$goal['type'],-$goal['count']);
            break;
        case 4:
            giveDefence($cid,$goal['type'],-$goal['count']);  
            break;
        case 5:                                            
            giveThings($uid,$goal['type'],-$goal['count']);
            break;
    }      
}

function taskCanRecomplete($tid)
{
	return (($tid>10000&&$tid<10600)||($tid>11000&&$tid<15000)||($tid>100021&&$tid<100025));
}

function isHuangJinJuanXian($tid)//黄巾捐献任务
{
	return ($tid>11000&&$tid<15000);
}

function isHuangJinDuiHuan($tid)//黄巾捐献任务的兑换任务
{
	return ($tid>10000&&$tid<10500);
}

function isHuangJinKill($tid)//消灭黄金军任务，在捐献任务完成后触发
{
	return ($tid>10500&&$tid<11000);
}

function triggerHuangJinCityTask()
{
	sql_query("insert into sys_user_task (uid,tid,state) (select uid,10501,0 from sys_user_task where tid=243 and state=1) on duplicate key update state=0");
}

function getReward($uid,$param)
{
    $tid = array_shift($param);
    lockUser($uid);
	
	//非黄巾史诗任务，只能交一次
    if (sql_check("select * from sys_user_task where `uid`='$uid' and `tid`='$tid' and state=1")&&(!taskCanRecomplete($tid)))
    {
        throw new Exception($GLOBALS['getReward']['already_got']);
    }
    $task = sql_fetch_one("select * from cfg_task where `id`='$tid'");
    
    $goals = sql_fetch_rows("select g.*,u.uid from cfg_task_goal g left join sys_user_goal u on u.gid=g.id and u.uid='$uid' where g.tid='$tid'");
    $complete = true;
    foreach($goals as $goal)
    {
        if (!checkGoalComplete($uid,$goal))
        {
            $complete = false;
            break;
        }
    }
    if (!$complete) throw new Exception($GLOBALS['getReward']['task_not_finished']);

    $cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");          
    if ($tid == 250)//食君之禄   
    {
        $salary = sql_fetch_one_cell("select p.salary from sys_user u left join cfg_office_pos p on p.id=u.officepos where u.uid='$uid'"); 
        giveResource($uid,$cid,1,$salary);
    }
    else if ($tid == 251)  //采食封邑
    {
        $salary = sql_fetch_one_cell("select n.salary from sys_user u left join cfg_nobility n on n.id=u.nobility where u.uid='$uid'");  
        giveResource($uid,$cid,2,$salary); 
        giveResource($uid,$cid,3,$salary);
        giveResource($uid,$cid,4,$salary);
        giveResource($uid,$cid,5,$salary);  
    }
    else if($tid==279)
    {
    	$gold=getUnionFamousCityGold($uid);
    	giveResource($uid,$cid,1,$gold);
    }
    else if ($tid>11000&&$tid<15000) //黄巾军史诗捐献任务
    {
    	$progress=sql_fetch_one("select maxvalue,curvalue from huangjin_progress where tid='$tid'");
    	if($progress['curvalue']>=$progress['maxvalue'])
    	{
        	//sql_query("update sys_user_task set state=1 where uid='$uid' and tid='$tid'");
        	sql_query("update sys_user_task set state=1 where tid='$tid'");
    		$ret=array();
		    $ret[] = 0;
		    $ret[]=$GLOBALS['getReward']['global_task_end'];
		    return $ret;
    	}
    	$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
    	$union_id=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
        foreach($rewards as $reward)
        {
        	if($reward['sort']==5) //记录捐献后获得的物品
        	{
        		if($reward['type']==11001)
        		{
        			sql_query("insert into huangjin_task_log (uid,jungong) values ('$uid',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,jungong) values ('$union_id',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
        		}
        		else if($reward['type']==12001)
        		{
        			sql_query("insert into huangjin_task_log (uid,juanxian) values ('$uid',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,juanxian) values ('$union_id',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
        		}
        		else if($reward['type']==13001)
        		{
        			sql_query("insert into huangjin_task_log (uid,qinwang) values ('$uid',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,qinwang) values ('$union_id',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
        		}
        		else if($reward['type']==14001)
        		{
        			sql_query("insert into huangjin_task_log (uid,gongpin) values ('$uid',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,gongpin) values ('$union_id',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
        		}
        	}
            giveReward($uid,$cid,$reward);
        }
        sql_query("update huangjin_progress set curvalue=LEAST(maxvalue,curvalue+1) where tid='$tid'");
        if($progress['curvalue']>=$progress['maxvalue']-1)
        {
        	sql_query("update sys_user_task set state=1 where tid='$tid'");
        	$unfinish=sql_fetch_one_cell("select count(*) from huangjin_progress where curvalue<maxvalue");
        	if($unfinish==0)
        	{
        		sql_query("update mem_state set value=1 where state=5");
        		triggerHuangJinCityTask();
        	}
        }
    }
    else 
    {
        $rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
        foreach($rewards as $reward)
        {
            giveReward($uid,$cid,$reward);
        }
    }
    
    if(!taskCanRecomplete($tid)) //黄巾军史诗任务，永久开放
    {
    	sql_query("insert into sys_user_task (`uid`,`tid`,`state`) values ('$uid','$tid',1) on duplicate key update `state`=1");
    }
    //如果任务目标要扣除相应的数据，则在这里扣除
    $goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
    foreach($goals as $goal)
    {
        if ($goal['reduce'] == 1)
        {
            reduceGoal($uid,$cid,$goal);
        }
    }
    
    $triggers = sql_fetch_rows("select * from cfg_task where pretid='$task[id]'");
    $huangjinProgress=sql_fetch_one_cell("select value from mem_state where state=5");
    foreach($triggers as $trigger)
    {
        $triggerout=true;
        $state = $trigger['default'];
        if($tid==243)
	    {
	    	if(!empty($huangjinProgress)) //如果黄巾史诗任务已经完成了，就不勾出捐献和兑换任务了
	    	{
	    		if(isHuangJinJuanXian($trigger['id'])||isHuangJinDuiHuan($trigger['id']))
	    		{
	    			$triggerout=false;
	    		}
	    	}
	    	if($tid==10501) $triggerout=(!empty($huangjinProgress));
	    }
	    if($triggerout)
	    {
        	sql_query("insert into sys_user_task (uid,tid,state) values ('$uid','$trigger[id]','$state') on duplicate key update state=state");
        }
    }

    unlockUser($uid);
    
    
    if($tid==100021)	//圣诞套装任务，发送广播
    {
    	$uname=sql_fetch_one_cell("select name from sys_user where uid='$uid'");
    	$uname=addslashes($uname);
    	$content="恭喜【".$uname."】招募了7个圣诞将领，得到7个印章，并领取了圣诞套装（将领装备）奖励！";
    	sql_query("insert into sys_inform (type,inuse,starttime,endtime,`interval`,scrollcount,color,msg) values (1,1,unix_timestamp(),unix_timestamp()+300,1800,2,16776960,'$content')");
    }
    
    $ret=array();
    $ret[] = 1;
    return $ret;
}



function getMultiReward($uid,$param)
{
    $tid = array_shift($param);
    $cnt = intval(array_shift($param));
    if($cnt<=0||$cnt>=1000)
    {
    	throw new Exception($GLOBALS['getReward']['invalid_count']);
    }
    else if($cnt==1)
    {
    	$param2=array();
    	$param2[]=$tid;
    	return getReward($uid,$param2);
    }
    lockUser($uid);
	
	//非黄巾史诗任务，只能交一次
    if (!taskCanRecomplete($tid))
    {
        throw new Exception($GLOBALS['getReward']['not_allowed_multi']);
    }
    $task = sql_fetch_one("select * from cfg_task where `id`='$tid'");
    
    $goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
    $complete = true;
    foreach($goals as &$goal)
    {
    	$goal['count']=$cnt*$goal['count'];
        if (!checkGoalComplete($uid,$goal))
        {
            $complete = false;
            break;
        }
    }
    if (!$complete)
    {
    	$msg=sprintf($GLOBALS['getReward']['not_enough_things'],$cnt);
    	 throw new Exception($msg);
    }
	$cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
	if (isHuangJinJuanXian($tid)) //黄巾军史诗捐献任务
    {
    	$progress=sql_fetch_one("select maxvalue,curvalue from huangjin_progress where tid='$tid'");
    	if($progress['curvalue']+$cnt>=$progress['maxvalue'])
    	{
        	throw new Exception($GLOBALS['getReward']['not_enough_remain']);
    	}
    	$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
    	$union_id=sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
        foreach($rewards as &$reward)
        {
        	$reward['count']=$reward['count']*$cnt;
        	if($reward['sort']==5) //记录捐献后获得的物品
        	{
        		if($reward['type']==11001)
        		{
        			sql_query("insert into huangjin_task_log (uid,jungong) values ('$uid',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,jungong) values ('$union_id',$reward[count]) on duplicate key update jungong=jungong+$reward[count]");
        		}
        		else if($reward['type']==12001)
        		{
        			sql_query("insert into huangjin_task_log (uid,juanxian) values ('$uid',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,juanxian) values ('$union_id',$reward[count]) on duplicate key update juanxian=juanxian+$reward[count]");
        		}
        		else if($reward['type']==13001)
        		{
        			sql_query("insert into huangjin_task_log (uid,qinwang) values ('$uid',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,qinwang) values ('$union_id',$reward[count]) on duplicate key update qinwang=qinwang+$reward[count]");
        		}
        		else if($reward['type']==14001)
        		{
        			sql_query("insert into huangjin_task_log (uid,gongpin) values ('$uid',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
        			if($union_id>0) sql_query("insert into huangjin_task_log_union (unionid,gongpin) values ('$union_id',$reward[count]) on duplicate key update gongpin=gongpin+$reward[count]");
        		}
        	}
            giveReward($uid,$cid,$reward);
        }
        sql_query("update huangjin_progress set curvalue=LEAST(maxvalue,curvalue+$cnt) where tid='$tid'");
        if($progress['curvalue']>=$progress['maxvalue']-$cnt)
        {
        	sql_query("update sys_user_task set state=1 where tid='$tid'");
        	$unfinish=sql_fetch_one_cell("select count(*) from huangjin_progress where curvalue<maxvalue");
        	if($unfinish==0)
        	{
        		sql_query("update mem_state set value=1 where state=5");
        		triggerHuangJinCityTask();
        	}
        }
    }
    else
    {
    	$rewards = sql_fetch_rows("select * from cfg_task_reward where tid='$tid'");
        foreach($rewards as $reward)
        {
        	$reward['count']=$reward['count']*$cnt;
            giveReward($uid,$cid,$reward);
        }
    }
    //如果任务目标要扣除相应的数据，则在这里扣除
    $goals = sql_fetch_rows("select * from cfg_task_goal where tid='$tid'");
    foreach($goals as &$goal)
    {
    	$goal['count']=$cnt*$goal['count'];
        if ($goal['reduce'] == 1)
        {
            reduceGoal($uid,$cid,$goal);
        }
    }
    
    unlockUser($uid);
    
    $ret=array();
    $ret[] = 1;
    return $ret;
}

?>