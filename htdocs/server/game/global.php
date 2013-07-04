<?php         
require_once("utils.php");

function checkUserAuth($uid,$sid)
{
    $ip = $GLOBALS['ip'];
    $sessionfile = "./sessions/".$uid;
    $sessionid = @file_get_contents($sessionfile);
    if ($sessionid === FALSE)
    {
        $sessionid = sql_fetch_one_cell("select sid from sys_sessions where uid='$uid'");
        @file_put_contents($sessionfile,$sessionid);
    }    
    session_start();
    //throw new Exception($sid.":".$sessionid);     
    if ($sid <= 0 || $uid <= 0 || $sid != $sessionid /*|| $uid != $_SESSION['currentLogin_uid']*/)
    {   
    	
        throw new Exception("invalid_user_auth");
    }
    
   /* 
//    if (!sql_check("select * from sys_sessions where uid='$uid' and sid='$sid' and ip='$ip'"))
    if (!sql_check("select * from sys_sessions where uid='$uid' and sid='$sid'"))
    {
        throw new Exception("invalid_user_auth");
    }  */                                                                                   
}

function getCityInfoRes($uid,$cid)
{
    $ret = array();
    $ret[] = sql_fetch_one("select * from sys_user where uid='$uid'");
    //推恩
 	$ret[0]["real_nobility"]="";
    $tempNobility=getBufferNobility($uid,$ret[0]["nobility"]);
    if($tempNobility!=$ret[0]["nobility"]){
    	$ret[0]["real_nobility"]=$ret[0]["nobility"];
		$ret[0]["nobility"]=$tempNobility;
    }
    
    $ret[] = sql_fetch_one("select m.*,b.endtime as shuilibian from mem_city_resource m left join mem_user_buffer b on b.uid='$uid' and b.buftype=15 where m.`cid`='$cid'");
    $ret[] = sql_fetch_one("select * from sys_alarm where uid='$uid'"); 
    return $ret;
}                            
function getCityInfoHero($uid,$cid)
{
	/*
    $ret = array();
    //所有属于本城的将领列表
    $ret[] = sql_fetch_rows("select * from sys_city_hero where `cid`='$cid' and uid='$uid'");    
    return $ret;
    */
    $heroes=sql_fetch_rows("select * from sys_city_hero h left join mem_hero_blood m on m.hid=h.hid where h.`cid`='$cid' and h.uid='$uid'");
	$ret = array();
	foreach($heroes as $hero)
	{
		$buffers=sql_fetch_rows("select * from mem_hero_buffer where hid='$hero[hid]' and endtime>unix_timestamp()");
		$newhero=&$hero;
		foreach($buffers as $buf)
		{
			$typeidx="buf".$buf['buftype'];
			$newhero[$typeidx]=$buf['endtime'];
		}
		$ret[] = $newhero;
	}
	return $ret;
}
function getCityInfoArmy($uid,$cid)
{
    $ret = array();                                                                          
    //本城拥有的军队                                     
    $ret[] = sql_fetch_rows("select * from sys_city_soldier where `cid`='$cid' order by sid");
    return $ret;
}
function getCityInfoDefence($uid,$cid)
{
    $ret = array();
    //本城拥有的城防                  
    $ret[] = sql_fetch_rows("select * from sys_city_defence where `cid`='$cid' order by did");
    return $ret;
}
?>