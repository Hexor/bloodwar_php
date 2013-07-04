<?php
require_once("interface.php");
require_once("utils.php");
require_once("MailFunc.php");

class Login
{
    function getLoginAnnouncement($param)
    {
    	require_once('../lib/Cache/Lite.php');
		// Set a few options
		$cache_options = array(
		    'cacheDir' => '../tmp/',
		    'lifeTime' => 60
		);

		// Create a Cache_Lite object
		$GLOBALS['cache'] = new Cache_Lite($cache_options);
		
		if ($data = $GLOBALS['cache']->get("log_announce")) 
		{
			$ann = $data;
		}
		else
		{ 
			$ann = stripcslashes(sql_fetch_one_cell("select content from sys_announce where id=1"));
		    $GLOBALS['cache']->save($ann);
		}
        return $ann;
    }
	function doLogin($param)
	{
        try
        {
            $version = array_shift($param);   
            $loginType = array_shift($param);
            $passtype = array_shift($param);   
            $user_domain = 0;
            $server_version = sql_fetch_one_cell("select value from mem_state where state=3");
            if ($version != $server_version)
            {
            	if ($loginType == 1)
				{
                	$ret = array(0=>2);
					return $ret;
            	}
            	else
            	{
                	throw new Exception($GLOBALS['doLogin']['client_version_old']); 
                }
            }
            
            $serverState=sql_fetch_one_cell("select value from mem_state where state=2");
            if ($serverState!=1)
            {
            	if ($loginType == 1)
            	{
                	$ret = array(0=>2);
					return $ret;
            	}
            	else
            	{
            		if($serverState==0)
            		{
            			throw new Exception(sql_fetch_one_cell("select content from sys_announce where id=2"));
            		}
            		else if($serverState==2)
            		{
            			throw new Exception(sql_fetch_one_cell("select content from sys_announce where id=3"));
            		}
                	//throw new Exception($GLOBALS['doLogin']['server_not_start']) ; 
                }
            }
            
            if ($loginType == 0)
            { 
                $passport = array_shift($param);
                $password = array_shift($param);
                $passsucc = false;
                @include ("./passport/$passtype.php");
                                                               
                if (!$passsucc)
                {
                    throw new Exception($GLOBALS['doLogin']['invalid_user_pwd']);
                }
            }
            else if ($loginType == 1)
            {
                $auth = array_shift($param);  
                $passsucc = false;  
                @include ("./passport/$passtype.php");
                                                               
                if (!$passsucc)
                {
                	$ret = array(0=>2);
					return $ret;
                }     
                
                $arr = explode("|",$auth);
                $passport = $arr[0];
            }

            $ret = array(0=>1);
            $state = 0;
            $user = sql_fetch_one("select * from sys_user where passport='$passport' and passtype='$passtype'");
            
            $sid = rand();
            $ip = $GLOBALS['ip'];
            if (empty($user))  //还没有创建角色
            {
                $userCount = sql_fetch_one_cell("select count(*) from sys_user where uid > 1000");
                $maxUserCount = sql_fetch_one_cell("select value from mem_state where state=100");
                if ($userCount >= $maxUserCount)
                {
                    throw new Exception($GLOBALS['doLogin']['server_full']);
                }
                $state = 3;     //刚创建角色，还没有城池
                $uid = sql_insert("insert into sys_user (`passtype`,`passport`,`group`,`state`,`regtime`,`domainid`) values ('$passtype','$passport','0','$state',unix_timestamp(),'$user_domain')");   
                sql_query("insert into sys_sessions (`uid`,`sid`,`ip`) values ('$uid','$sid','$ip')");   
                sql_query("insert into sys_online (`uid`,`lastupdate`,`onlineupdate`,`onlinetime`) values ('$uid',unix_timestamp(),unix_timestamp(),0)");         
                @touch("./sessions/".$uid);
            }
            else
            {
                $uid = $user['uid'];   
                $forbidden_delta = sql_fetch_one_cell("select login - unix_timestamp() from sys_user_forbidden where uid='$uid'");
                                                         
                if ($forbidden_delta > 0)
                {         
                	$msg = sprintf($GLOBALS['doLogin']['account_temp_locked'],MakeTimeLeft($forbidden_delta));                                 
                    throw new Exception($msg);
                }            
                                      
                if ($user['state'] == 5||sql_check("select uid from sys_user_state where uid='$uid' and forbiend>unix_timestamp()"))    //5：锁定，不能登录
                {
                     throw new Exception($GLOBALS['doLogin']['account_locked']);
                }
	            //每日送宝
	            $last_daily_gift = sql_fetch_one_cell("select `last_daily_gift` from mem_user_schedule where uid='$uid'");
	            $now = sql_fetch_one_cell("select unix_timestamp()");
	            $endtime=sql_fetch_one_cell("select value from mem_state where state=20");
	            if((!empty($endtime))&&($endtime>$now))
	            {
		            if (floor(($now + 8 * 3600) / 86400) != floor(($last_daily_gift + 8 * 3600) / 86400))
		            {
		                //现在是送50个传音符
		                //sql_query("insert into sys_goods (`uid`,`gid`,`count`) values ('$uid','1','50') on duplicate key update `count`=GREATEST(`count`,50)");
		                $gid=rand(27,29);
		                sql_query("insert into sys_goods (`uid`,`gid`,`count`) values ('$uid','$gid',1) on duplicate key update `count`=`count`+1");
		                sql_query("insert into mem_user_schedule (`uid`,`last_daily_gift`) values ('$uid',unix_timestamp()) on duplicate key update `last_daily_gift`=unix_timestamp()");
		            }
	            }
            }
            
            sql_query("delete from mem_queue where uid='$uid'");
            
            
            //当前在线
            $online = sql_fetch_one_cell("select count(*) from sys_online where unix_timestamp() - lastupdate < 30"); 
            $maxuser = sql_fetch_one_cell("select value from mem_state where state=4"); 
            $queuesize=sql_fetch_one_cell("select count(*) from mem_queue");
            if (($online >= $maxuser)||($queuesize>100)||(($queuesize>0)&&($queuesize+$online+200>=$maxuser)))
            {
                $qid = sql_insert("insert into mem_queue (`uid`,`sid`,`ip`,`lastupdate`) values ('$uid','$sid','$GLOBALS[ip]',unix_timestamp())");
                $queueCount = sql_fetch_one_cell("select count(*) from mem_queue where id < '$qid'");
                $ret[] = 1;
                $ret[] = $uid;
                $ret[] = $sid; 
                $ret[] = $queueCount;
            }
            else
            {
                realLogin($uid,$sid);
                $ret[] = 2;
                $ret[] = $uid;
                $ret[] = $sid; 
            }                         
            return $ret;
        }
        catch(Exception $e)
        {
            $ret = array(0=>0);
            $ret[] = $e->getMessage();
            return $ret;
        }

	}

    function checkQueue($param)
    {
        $uid = intval(array_shift($param));
        $sid = intval(array_shift($param));
        if (!sql_check("select * from mem_state where state=2 and value=1"))
        {
            throw new Exception("server_is_updating");    
        }
        $ret = array();
        $queue = sql_fetch_one("select * from mem_queue where uid='$uid' and sid='$sid' and ip='$GLOBALS[ip]'");
        if (empty($queue))
        {                     
            $ret[] = 0;
        }
        else
        {
            $ret[] = 1;
            //当前在线
            $online = sql_fetch_one_cell("select count(*) from sys_online where unix_timestamp() - lastupdate < 30");
            $maxuser = sql_fetch_one_cell("select value from mem_state where state=4");
            //前面还有多少人   
            $queueorder = sql_fetch_one_cell("select count(*) from mem_queue where id < $queue[id]");      
            if ($maxuser - $online > $queueorder)
            {
                sql_query("delete from mem_queue where id='$queue[id]'");
                //login                    
                realLogin($uid,$sid);
                $ret[] = 0;
            }    
            else//continue queue
            {
                sql_query("update mem_queue set `lastupdate`=unix_timestamp() where id='$queue[id]'");
                $ret[] = 1;
                $ret[] = $queueorder;                  
            }
        }
        return $ret;
    }
}

 
?>