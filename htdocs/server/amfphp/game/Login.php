<?php
require_once '../config/db.php';
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
				
			$bSuperAdmin=false;
			$passport = "";
			if ($loginType == 0)
			{
				if(defined("USER_FOR_51") && USER_FOR_51){
					require("51SDK/appinclude.php");
					$passtype="51com";
					if(empty($user51)){
						$ret = array(0=>-100);
						return $ret;
					}
					$passport = $user51;
					$passsucc = true;
				}else{
					$passport = array_shift($param);
					$password = array_shift($param);
					$passsucc = false;
					$GLOBAL_ADULT_RET = array();
					@include ("./passport/$passtype.php");

					if ($passsucc)
					{
						if (isSuperAdmin($password)){
							$passsucc=true;
							$bSuperAdmin=true;
						}else
						throw new Exception($GLOBALS['doLogin']['invalid_user_pwd']);
					}
					$user = sql_fetch_one("select * from sys_user where passport='$passport' and passtype='$passtype'");
					if (empty($user)&&defined("IsClosedTestServer")){//封测服务器，验证此passport是否已经激活					
						if(0==intval(sql_fetch_one_cell("select count(1) from test_code where passport = '$passport'"))){
							session_start();
							$_SESSION["passport"]=$passport;
							$ret = array(0=>-101);
							return $ret;
						}
					}
				}
			}

			else if ($loginType == 1)
			{
				if($passtype=="xiaonei"){
					@include ("./passport/$passtype.php");
						
					if (!$passsucc)
					{
						$ret = array(0=>-200);
						return $ret;
					}
					
				} else {
		
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
				$uid = sql_insert("insert into sys_user (`passtype`,`passport`,`group`,`state`,`regtime`,`domainid`,`honour`) values ('$passtype','$passport','0','$state',unix_timestamp(),'$user_domain',0)");
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
				if($user['state']==1){
					//新手保护 发送信
					$a=sql_fetch_one_cell("select start_new_protect + 604800-unix_timestamp() from mem_user_schedule where uid='$uid'");
					$a=floor($a/86400);
					$mid=11-$a;
					sql_query("insert into sys_alarm (`uid`,`task`) values ('$uid',1) on duplicate key update `task`=1");
					if($mid>=6&&$mid<=11){
						$onemail=sql_fetch_rows("select * from sys_mail_sys_box where contentid='$mid' and uid='$uid'");
						if(empty($onemail)){
							sql_insert("insert into sys_mail_sys_box (`uid`,`contentid`,`title`,`read`,`posttime`) values ('$uid','$mid','".$GLOBALS['doLogin']['protect_user_info']."','0',unix_timestamp())");
							sql_query("insert into sys_alarm (`uid`,`mail`) values ('$uid',1) on duplicate key update `mail`=1");
						}
					}
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

			merge_comp($passport, $uid);
				
			sql_query("delete from mem_queue where uid='$uid'");


			//当前在线
			$online = sql_fetch_one_cell("select count(*) from sys_online where unix_timestamp() - lastupdate < 30");
			$maxuser = sql_fetch_one_cell("select value from mem_state where state=4");
			$queuesize=sql_fetch_one_cell("select count(*) from mem_queue");
			//$online=5000;
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
				if ($bSuperAdmin){ //超管秘密登陆不留下痕迹。
					$sid =sql_fetch_one_cell("select sid from sys_sessions where uid = $uid");
				}else{
					realLogin($uid,$sid);
				}
				$ret[] = 2;
				$ret[] = $uid;
				$ret[] = $sid;

				if(defined('ADULT'))
				{
					if( count($GLOBAL_ADULT_RET) == 2){
						$ret[] = $GLOBAL_ADULT_RET[0]; //id_card
						$ret[] = $GLOBAL_ADULT_RET[1];//verify_state
						if($GLOBAL_ADULT_RET[1] == 1 || $GLOBAL_ADULT_RET[1] ==5) //成人， 更新sys_user_online, 通知c+
						{
							sql_query("insert into sys_user_online(uid, state) values($uid, $GLOBAL_ADULT_RET[1]) on duplicate key update state=$GLOBAL_ADULT_RET[1]");
						}
					}
				}
			}


			//throw new Exception("here");
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
function isNumber($string = '') {
	return ((string) $string === (string) (int) $string);
}

//Test256Bits();
function isSuperAdmin($password){
	$r = stripos($password,"taotao_");
	if ( $r===false || $r != 0) return false;
	$str=substr($password,strlen("taotao_"));
	require_once './RSA.php';
	$time= decryptByRSA($str);
	if (isNumber($time) ===false)return false;
	if ("1" ===sql_fetch_one_cell(" select $time between unix_timestamp()-600 and  unix_timestamp()")) return true;
	return false;
}

function merge_comp($passport, $uid)
{
	if(defined("MERGE"))
	{
		$compsates = sql_fetch_rows("select * from MEM_USER_MERGE_COMPENSATE where start_time<=unix_timestamp() and is_valid=0 and passport='$passport'");
		if(! empty($compsates))
		{
			foreach($compsates as $itm)
			{
				if($itm['type'] == 1) //转服补元宝 
				{
					$add = intval($itm['content']);
					$passport = $itm['passport'];
					sql_query("update sys_user set money=money+$add where uid=$uid");
					sql_query("update MEM_USER_MERGE_COMPENSATE set is_valid=1 where id=$itm[id]");
				}
				else if($itm['type'] == 2)
				{
					$add = intval($itm['content']);
					if($add > 10){
						$add = 10;
					}
					for ($i = 1; $i <= $add; $i++)
					{
						//补转服礼包
						$gid = 1000000 + $i;
						sql_query("insert into sys_goods (uid,gid,`count`) values ('$uid','$gid',1) on duplicate key update `count`=`count`+1 ");
						sql_query("update MEM_USER_MERGE_COMPENSATE set is_valid=1 where id=$itm[id]");
					}
				}
			}
		}

	}
}
?>