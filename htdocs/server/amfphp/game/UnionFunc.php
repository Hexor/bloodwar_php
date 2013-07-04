<?php                      
require_once("./interface.php");
require_once("./utils.php");

define("APPLY_PAGE_CPP",10);
define("EVENT_PAGE_CPP",10);
define("UNION_REPORT_PAGE_CPP",10);
define("EVT_ADD_UNION",0);
define("EVT_QUIT_UNION",1);
define("EVT_KICK_MENBER",2);
define("EVT_CHANGE_LEADER",3);
define("EVT_CHANGE_NAME",4);
define("EVT_RELATION_FRIEND",5);
define("EVT_RELATION_NEUTRAL",6);
define("EVT_RELATION_ENEMY",7);
define("EVT_WAR",8);
define("EVT_PROVICY",9);
define("EVT_DEMISSION",10);

function getHongLuInfo($uid,$cid)
{
    $honglu = sql_fetch_one("select * from sys_building where `cid`='$cid' and `bid`=".ID_BUILDING_HONGLU." order by level desc limit 1");
    if (empty($honglu))
    {   
        throw new Exception($GLOBALS['getHongLuInfo']['no_HongLu_built']); 
    }
    return doGetBuildingInfo($uid,$cid,$honglu['xy'],ID_BUILDING_HONGLU,$office['level']);
}

function getAllowUnionTroop($uid,$cid)
{
	$allow=sql_fetch_one_cell("select `allow` from sys_allow_union_troop where uid='$uid'");
	if($allow===false) $allow=0;
	
	return $allow;
}
function getAllowAntiPlunder($uid,$cid)
{
	$allow=sql_fetch_one_cell("select `anti_plunder` from sys_allow_union_troop where uid='$uid'");
	if($allow===false) $allow=1;
	
	return $allow;
}
function getAllowAntiInvade($uid,$cid)
{
	$allow=sql_fetch_one_cell("select `anti_invade` from sys_allow_union_troop where uid='$uid'");
	if($allow===false) $allow=1;
	
	return $allow;
}
function createUnion($uid,$param)
{
    $user = sql_fetch_one("select `name`,`union_id` from sys_user where uid='$uid'");
    $userunion=$user['union_id'];
    if ($userunion > 0) throw new Exception($GLOBALS['createUnion']['already_joined_other_union']);
    $unionname = trim(array_shift($param));
    if(empty($unionname))
    {
        throw new Exception($GLOBALS['createUnion']['union_name_notNull']);
    }
    else if (mb_strlen($unionname,"utf-8") > 8)
    {
        throw new Exception($GLOBALS['createUnion']['union_name_tooLong']);
    }
    else if ((!(strpos($unionname,'\'')===false))||(!(strpos($unionname,'\\')===false)))
    {
    	throw new Exception($GLOBALS['createUnion']['has_ivalid_char']);
    }
    else if (sql_check("select * from cfg_baned_name where instr('$unionname',`name`)>0"))
    {
    	throw new Exception($GLOBALS['createUnion']['has_ivalid_char']);
    }
    $unionname=addslashes($unionname);
    //需要联盟2级
    $cid = sql_fetch_one_cell("select lastcid from sys_user where uid='$uid'");
    $honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$uid'");   
    if ($honglulevel < 2)
    {
        throw new Exception($GLOBALS['createUnion']['level_lessThen_2']);
    }
    
    $citygold = sql_fetch_one_cell("select gold from mem_city_resource where cid='$cid'");
    if ($citygold < 10000)
    {
        throw new Exception($GLOBALS['createUnion']['gold_not_enough']);
    }
    
    if (sql_check("select * from sys_union where name='$unionname'"))
    {
        throw new Exception($GLOBALS['createUnion']['use_another_name']);
    }
    sql_query("update mem_city_resource set gold=gold-10000 where cid='$cid'");
    
    $unionid = sql_insert("insert into sys_union (name,leader,creator,createtime) values ('$unionname','$uid','$uid',unix_timestamp())");
    
    sql_query("update sys_user set union_id='$unionid',union_pos=1 where uid='$uid' ");
    
    notifyUnionChange($uid,$unionid,1);
                            
    completeTask($uid,67);                                                              
    updateUnionRank($unionid);
    
    //addUnionEvent($unionid,EVT_ADD_UNION,"$user[name] 创建联盟 $unionname ！");
    $evtMsg = $user[name].$GLOBALS['createUnion']['add_union_event'].$unionname." !";
    addUnionEvent($unionid,EVT_ADD_UNION,$evtMsg);


    
    $ret = array();
    $ret[] = $unionid;
    $ret[] = 0;
    return $ret;
}
//返回自己发出的申请和向自己的邀请
function getUnionApplyInvite($uid,$param)
{
	$user=sql_fetch_one("select `union_id`,`union_pos` from `sys_user` where `uid`='$uid'");
    $ret=array();
    $unionid=$user['union_id'];
    $unionpos=$user['union_pos'];
    $ret[]=$unionid;
    $ret[]=$unionpos;
	if ($unionid>0)
	{
		$unionname=sql_fetch_one_cell("select `name` from `sys_union` where `id`='$unionid'");
		$ret[]=$unionname;
		$ret[]="［".$unionname.$GLOBALS['getUnionApplyInvite']['succ'];
	}
	else
	{
		$name=sql_fetch_one_cell("select `name` from `sys_union_apply` where `uid`='$uid'");
		if(empty($name))
		{
			$ret[]="";
		}
		else
		{
			$ret[]=$name;
		}
	    $ret[]=sql_fetch_rows("select u.* from sys_union_invite i left join sys_union u on u.id=i.unionid where i.uid='$uid'");
    }
    return $ret;
}                

//申请加入联盟
function applyJoin($uid,$param)
{
	$honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$uid'");
    if (empty($honglulevel))
    {   
        throw new Exception($GLOBALS['applyJoin']['no_HongLu_built']); 
    }
	$userunion = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
    if ($userunion > 0)
    {
    	throw new Exception($GLOBALS['applyJoin']['already_joined_other_union']);
    }
	$unionid=array_shift($param);
	$unionname=sql_fetch_one_cell("select `name` from sys_union_apply where `uid`='$uid'");
	if(!empty($unionname))
	{
		$msg = sprintf($GLOBALS['applyJoin']['reset_application'],$unionname);
		//throw new Exception("你已经申请加入［".$unionname."］,去鸿胪寺撤消原申请之后才能重新申请。");
		throw new Exception($msg);
	}
	$unionname=sql_fetch_one_cell("select `name` from `sys_union` where `id`='$unionid'");
	if(empty($unionname))
	{
		throw new Exception($GLOBALS['applyJoin']['union_not_exist']);
	}
	sql_insert("insert into `sys_union_apply` values ('$uid','$unionid','$unionname',unix_timestamp())");
	throw new Exception($GLOBALS['applyJoin']['send_application_succ']);
}
//取得本联盟的申请列表
function getApplyList($uid,$param)
{
	$user = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$user['union_id'];
    if ($unionid<=0||$user['union_pos']==0)
    {
        throw new Exception($GLOBALS['getApplyList']['not_official']);
    }
    $page=array_shift($param);
    $rowCount=sql_fetch_one_cell("select count(*) from `sys_union_apply` where `unionid`='$unionid'");
    $pageCount=ceil($rowCount/APPLY_PAGE_CPP);
    if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    	$pageCount=0;
    }
    $ret=array();
    $ret[]=$pageCount;
    $ret[]=$page;
    if($rowCount>0)
    {
    	$start=$page*APPLY_PAGE_CPP;
    	$ret[]=sql_fetch_rows("select u.`uid` as userid,u.`name`,a.`time`,r.`prestige`,r.`rank`,r.`city` from `sys_union_apply` a left join `sys_user` u on a.`uid`=u.`uid` left join `rank_user` r on u.`name`=r.`name` where a.`unionid`='$unionid' order by a.`time` limit $start,".APPLY_PAGE_CPP);
    }
    else
    {
    	$ret[]=array();
    }
    return $ret;
}
function acceptApply($uid,$param)
{
	$page=array_shift($param);
	$auid=array_shift($param);
    $user=sql_fetch_one("select `name`,`union_id` from `sys_user` where `uid`='$auid'");
    $userunion=$user['union_id'];
    if ($userunion > 0)
    {
    	throw new Exception($GLOBALS['acceptApply']['taget_joined_other_union']);
    }
    $honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$auid'");
    if (empty($honglulevel))
    {   
        throw new Exception($GLOBALS['acceptApply']['target_has_no_HongLu']); 
    }
	$myinfo = sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$uid'");
	$unionid=$myinfo['union_id'];
	if ($myinfo['union_id']<=0||$myinfo['union_pos']==0)
    {
        throw new Exception($GLOBALS['acceptApply']['not_official']);
    }
    $union = sql_fetch_one("select `leader`,`member`,`chieforder` from sys_union where id='$unionid'");
    if (empty($union))
    {
    	throw new Exception($GLOBALS['acceptApply']['union_not_exist']);
    }
    if(!sql_check("select `uid` from `sys_union_apply` where `uid`='$auid' and `unionid`='$unionid'"))
    {
    	throw new Exception($GLOBALS['acceptApply']['data_record_not_exist']);
    }
	if(empty($union['chieforder']))
	{
    	$honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$union[leader]'");
	    if(empty($honglulevel))
	    {
	    	throw new Exception($GLOBALS['acceptApply']['no_HongLu_built']);
	    }
    	$maxmember = $honglulevel * HONGLU_LEVEL_RATE;
	}
	else $maxmember=100;
    $inviteCount = sql_fetch_one_cell("select count(*) from sys_union_invite where unionid='$unionid'");
    if ($union['member'] + $inviteCount >= $maxmember)
    {
        throw new Exception($GLOBALS['acceptApply']['union_is_full']);
    }
    
    //加入联盟
    
    sql_query("update `sys_user` set `union_id`='$unionid',`union_pos`=0 where `uid`='$auid'");
    sql_query("update `sys_union` set `member`=`member`+1 where `id`='$unionid'");
    $famouscity=sql_fetch_one_cell("select count(*) from sys_city where uid='$auid' and type>0");
    if($famouscity>0)
    {
    	sql_query("update sys_union_city set `count`=GREATEST(0,`count`+$famouscity) where unionid='$unionid'");
    }
    completeTask($auid,67);
    notifyUnionChange($auid,$unionid,1);
    updateUnionRank($unionid);
    sql_query("delete from sys_union_apply where uid='$auid'");

//	addUnionEvent($unionid,EVT_ADD_UNION,"$myinfo[name] 通过了 $user[name] 入盟申请！");
    $msg = sprintf($GLOBALS['acceptApply']['addUnionEvent'],$myinfo[name],$user[name]);
	addUnionEvent($unionid,EVT_ADD_UNION,$msg );
	
	$param2=array();
	$param[]=$page;
    return getApplyList($uid,$param2);
}

function rejectApply($uid,$param)
{
	$page=array_shift($param);
	$auid=array_shift($param);
	$union = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
	$unionid=$union['union_id'];
	$unionpos=$union['union_pos'];
    if ($unionid<=0||$unionpos==0)
    {
        throw new Exception($GLOBALS['rejectApply']['not_official']);
    }
    sql_query("delete from sys_union_apply where uid='$auid'");

	$param2=array();
	$param[]=$page;
    return getApplyList($uid,$param2);
}

function cancelApply($uid,$param)
{
	sql_query("delete from `sys_union_apply` where `uid`='$uid'");
	$ret=array();
	return $ret;
}

function acceptInvite($uid,$param)
{
    $unionid = array_shift($param);
    
    //查看请求是否还有效
    $invite=sql_fetch_one("select * from sys_union_invite where unionid='$unionid' and uid='$uid'");

    if(empty($invite))
    {
         throw new Exception($GLOBALS['acceptInvite']['invalid_invitation']);  
    }
    if (!sql_check("select * from sys_union where id='$unionid'"))
    {
         throw new Exception($GLOBALS['acceptInvite']['union_not_exist']);  
    }
	$honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$uid'");
    if(empty($honglulevel))
    {
    	throw new Exception($GLOBALS['acceptInvite']['no_HongLu_built']);
    }
    $user = sql_fetch_one("select `name`,`union_id` from sys_user where uid='$uid'");
    $userunion=$user['union_id'];
    if ($userunion > 0) throw new Exception($GLOBALS['acceptInvite']['already_joined_other_union']);

    //加入联盟
    
    sql_query("update sys_user set union_id='$unionid',union_pos=0 where uid='$uid'");
    sql_query("update sys_union set member=member+1 where id='$unionid'");    
    $famouscity=sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type>0");
    if($famouscity>0)
    {
    	sql_query("update sys_union_city set `count`=GREATEST(0,`count`+$famouscity) where unionid='$unionid'");
    }
    completeTask($uid,67);
    notifyUnionChange($uid,$unionid,1);
    updateUnionRank($unionid);
    sql_query("delete from sys_union_invite where uid='$uid'");
    
    $msg = sprintf($GLOBALS['acceptInvite']['addUnionEvent'],$invite[inviter],$user[name]);
    addUnionEvent($unionid,EVT_ADD_UNION,$msg);
    $ret = array();
    $ret[] = $unionid;
    $ret[] = 1;
    return $ret;
}
function rejectInvite($uid,$param)
{
    $unionid = array_shift($param);
    if (!sql_check("select * from sys_union_invite where unionid='$unionid' and uid='$uid'"))
    {
         throw new Exception($GLOBALS['rejectInvite']['invalid_invitation']);  
    }
    if (!sql_check("select * from sys_union where id='$unionid'"))
    {
         throw new Exception($GLOBALS['rejectInvite']['union_not_exist']);  
    }
    sql_query("delete from sys_union_invite where unionid='$unionid' and uid='$uid'");                                                                   
    return array();
}

function loadUnionDetail($uid,$param)
{
	$unionid=array_shift($param);
	if (!sql_check("select id from sys_union where id='$unionid'"))
    {
        $ret[] = 0;
        $ret[] = $GLOBALS['loadUnionDetail']['union_dissmissed'];
        return $ret;
    }
	return getUnionDetail($unionid,0);
}

function loadUnionInfo($uid,$param)
{
    $union = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$union['union_id'];
    $ret = array();
    if (empty($union)||$unionid<=0)
    {
        $ret[] = 0;
        $ret[] = $GLOBALS['loadUnionInfo']['not_belongTo_union'];
        return $ret;
    }
    else if (!sql_check("select id from sys_union where id='$unionid'"))
    {
        $ret[] = 0;
        $ret[] = $GLOBALS['loadUnionInfo']['your_union_is_out'];
        return $ret;
    }
    $ret=getUnionDetail($unionid,1);
    $ret[]=$union['union_pos'];
    return $ret;
}

function getUnionDetail($unionid,$inner)
{
	$ret=array();
	$ret[] = 1;
	if($inner==1)
	{
    	$union = sql_fetch_one("select n.*,u.name as leadername,u2.name as creator from sys_union n left join sys_user u on n.leader=u.uid left join sys_user u2 on n.creator=u2.uid where n.id='$unionid'");
	}
	else
	{
    	$union = sql_fetch_one("select n.`id`,n.`chieforder`,r.`leader`,r.`name`,r.`member`,r.`famouscity`,r.`rank`,r.`prestige`,u.name as creator,n.`intro` from sys_user u, sys_union n left join rank_union r on r.uid=n.id where n.id='$unionid' and u.uid=n.creator");
	}
    $ret[] = $union;
    if(empty($union['chieforder']))
    {
	    $leader = sql_fetch_one_cell("select leader from sys_union where id='$unionid'");
	    $ret[] = sql_fetch_one_cell("select max(b.level)*10 from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$leader'");
    }
    else $ret[]=100;
    return $ret;
}

function loadUnionMemberList($uid,$param)
{
	$unionid=sql_fetch_one_cell("select `union_id` from sys_user where `uid`='$uid'");
    if (empty($unionid)||($unionid == 0))
    {
        throw new Exception($GLOBALS['loadUnionMemberList']['you_belongTo_none_union']);
    }
    return sql_fetch_rows("select u.*,r.rank as srank,count(c.cid) as cityCount,s.lastupdate from sys_user u left join sys_city c on c.uid=u.uid left join sys_online s on s.uid=u.uid left join rank_user r on r.uid=u.uid where u.union_id='$unionid' group by u.uid");
    
}
function leaveUnion($uid,$param)
{
    $user = sql_fetch_one("select `name`, `union_id`,`union_pos` from sys_user where uid='$uid'");
    $unionid=$user['union_id'];
    $unionpos=$user['union_pos'];
    if ($unionid == 0) throw new Exception($GLOBALS['leaveUnion']['you_belongTo_none_union']);
    $union = sql_fetch_one("select * from sys_union where id='$unionid'");
    if (empty($union))
    {
        throw new Exception($GLOBALS['leaveUnion']['your_union_is_out']);
    }
    
    //如果是盟主的话，如果还有会员就不能离开联盟
    if ($union['leader'] == $uid)
    {
        $unionUserCount = sql_fetch_one_cell("select `member` from sys_union where id='$unionid'");
        if ($unionUserCount > 1)
        {
            throw new Exception($GLOBALS['leaveUnion']['chief_cant_leave']);
        }
        sql_query("update sys_user set union_id=0,union_pos=0 where uid='$uid'"); 
        sql_query("delete from sys_union where id='$unionid'");
        sql_query("delete from sys_union_relation where unionid='$uinionid' or `target`='$unionid'");
        sql_query("delete from sys_union_event where unionid='$unionid'");
        sql_query("delete from sys_union_invite where unionid='$unionid'");
        sql_query("delete from sys_union_apply where unionid='$unionid'");
        sql_query("delete from huangjin_task_log_union where unionid='$unionid'");
        sql_query("delete from sys_union_city where unionid='$unionid'");
        notifyUnionChange($uid,$unionid,0);
    }
    else if($unionpos!=0)
    {
        throw new Exception($GLOBALS['leaveUnion']['official_cant_leave']);
    }
    else
    {
    	notifyUnionChange($uid,$unionid,0);
        sql_query("update sys_user set union_id=0,union_pos=0 where uid='$uid'");
        sql_query("update sys_union set member=member-1 where id='$unionid'");
        $famouscity=sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type>0");
        if($famouscity>0)
        {
        	sql_query("update sys_union_city set `count`=GREATEST(0,`count`-$famouscity) where unionid='$unionid'");
        }
        $cities=sql_fetch_rows("select cid from sys_city where uid='$uid'");
        $fieldcids="";
		$comma="";
        if(!empty($cities))
        {
        	foreach($cities as $city)
        	{
        		$cid=$city['cid'];
		        $ownerfields=sql_fetch_rows("select wid from mem_world where ownercid='$cid'");
			    if(!empty($ownerfields))
			    {
					foreach($ownerfields as $mywid)
					{
						$fieldcids.=$comma;
						$fieldcids.=wid2cid($mywid['wid']);
						$comma=",";
					}
				}
			}
			if(!empty($fieldcids))
			{
				sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where targetcid in ($fieldcids) and state=4 and uid<>'$uid' and uid > 0");
			}
			foreach($cities as $city)
			{
				updateCityResourceAdd($city['cid']);
			}
		}
		$troops=sql_fetch_rows("select id,targetcid from sys_troops where uid='$uid' and state=4");
		foreach($troops as $troop)
		{
			$wid=cid2wid($troop['targetcid']);
			$owneruid=sql_fetch_one_cell("select c.uid from sys_city c, mem_world m where m.wid='$wid' and m.ownercid=c.cid");
			if($owneruid!=$uid)
			{
				sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where id='$troo[id]'");
				updateCityResourceAdd($troop['cid']);
			}
		}
        updateUnionRank($unionid);
    }
    
    
    $msg = sprintf($GLOBALS['leaveUnion']['addUnionEvent'],$user[name]);
    addUnionEvent($unionid,EVT_QUIT_UNION,$msg);
    return array();                         
}
function getInviteList($uid,$param)
{
    $union = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$union['union_id'];
    $unionpos=$union['union_pos'];
    if($unionid<=0||$unionpos==0)
    {
        throw new Exception($GLOBALS['getInviteList']['you_are_not_official']);
    }
     return sql_fetch_rows("select u.uid as userid,u.name,u.rank,u.prestige,i.inviter,i.`time` from sys_union_invite i left join sys_user u on u.uid=i.uid where i.unionid='$unionid';");
}
function cancelInvite($uid,$param)
{
    $targetuid = array_shift($param);
    $union = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$union['union_id'];
    $unionpos=$union['union_pos'];
    if($unionid<=0||$unionpos==0)
    {
        throw new Exception($GLOBALS['cancelInvite']['you_are_not_official']);
    }
    sql_query("delete from sys_union_invite where unionid='$unionid' and uid='$targetuid'");
    return getInviteList($uid,$param);
}
function inviteUser($uid,$param)
{
    $username = (trim(array_shift($param)));
    if (empty($username))
    {
        throw new Exception($GLOBALS['inviteUser']['enter_target_name']);
    }
    else if(mb_strlen($username,"utf-8")>8)
    {
    	throw new Exception($GLOBALS['inviteUser']['name_length_most_8']);
    }
    $username=addslashes($username);
    $myinfo = sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$myinfo['union_id'];
    $unionpos=$myinfo['union_pos'];
    $union = sql_fetch_one("select * from sys_union where id='$unionid'");
    if($unionid<=0||$unionpos==0)
    {
        throw new Exception($GLOBALS['inviteUser']['you_are_not_official']);
    }
    $user = sql_fetch_one("select * from sys_user where name='$username'");
    if (empty($user))
    {
        throw new Exception($GLOBALS['inviteUser']['named_user_not_exist']);
    }
    if ($user['uid'] == $uid)
    {
        throw new Exception($GLOBALS['inviteUser']['cant_invite_yourself']);
    }
    else if ($user['union_id'] > 0)
    {
        throw new Exception($GLOBALS['inviteUser']['taget_joined_other_union']);
    }
    
    $honglulevel = sql_fetch_one_cell("select max(b.level) from sys_building b,sys_city c where b.bid='".ID_BUILDING_HONGLU."' and b.cid=c.cid and c.uid='$union[leader]'");   
    if(empty($union['chieforder']))
    {
    	$maxmember = $honglulevel * HONGLU_LEVEL_RATE;
    }
    else $maxmember=100;
    $inviteCount = sql_fetch_one_cell("select count(*) from sys_union_invite where unionid='$unionid'");
    if ($union['member'] + $inviteCount >= $maxmember)
    {
        throw new Exception($GLOBALS['inviteUser']['your_union_is_full']);
    }
    
    sql_query("replace into sys_union_invite (unionid,inviter,uid,`time`) values ('$unionid','$myinfo[name]','$user[uid]',unix_timestamp())");
                                          
    return getInviteList($uid,$param);
                                                           
}
function kickMember($uid,$param)
{                       
    $username = (trim(array_shift($param)));
	if(empty($username))
	{
		throw new Exception($GLOBALS['kickMember']['enter_target_name']);
	}
    if(mb_strlen($username,"utf-8")>8)
    {
    	throw new Exception($GLOBALS['kickMember']['name_length_most_8']);
    }
    $username=addslashes($username);
    $user = sql_fetch_one("select `name`,`union_id`,union_pos from sys_user where uid='$uid'");
    $unionid=$user['union_id'];
    $unionpos=$user['union_pos'];
    if($unionid<=0||($unionpos==0||$unionpos>3))
    {
        throw new Exception($GLOBALS['kickMember']['not_elder']);
    }
    $userinfo = sql_fetch_one("select * from sys_user where name='$username' and union_id='$unionid'");
    if (empty($userinfo))
    {
        throw new Exception($GLOBALS['kickMember']['target_not_in_your_union']);
        //throw new Exception($GLOBALS['kickMember']['target_name_not_exist']);
    }
    else if ($userinfo['union_id'] != $unionid)
    {
        throw new Exception($GLOBALS['kickMember']['target_not_in_your_union']);
    }
    else if ($userinfo['union_pos']!=0)
    {
    	throw new Exception($GLOBALS['kickMember']['descend_target_level']);
    }
    else if ($userinfo['uid']==$uid)
    {
    	throw new Exception($GLOBALS['kickMember']['cant_kick_oneself']);
    }
    sql_query("update sys_user set union_id=0 where uid='$userinfo[uid]'");
    sql_query("update sys_union set member=member-1 where id='$unionid'");
    $famouscity=sql_fetch_one_cell("select count(*) from sys_city where uid='$uid' and type>0");
    if($famouscity>0)
    {
    	sql_query("update sys_union_city set `count`=GREATEST(0,`count`-$famouscity) where unionid='$unionid'");
    }
    $cities=sql_fetch_rows("select cid from sys_city where uid='$userinfo[uid]'");
    $fieldcids="";
	$comma="";
    if(!empty($cities))
    {
    	foreach($cities as $city)
    	{
    		$cid=$city['cid'];
	        $ownerfields=sql_fetch_rows("select wid from mem_world where ownercid='$cid'");
		    if(!empty($ownerfields))
		    {
				foreach($ownerfields as $mywid)
				{
					$fieldcids.=$comma;
					$fieldcids.=wid2cid($mywid['wid']);
					$comma=",";
				}
			}
		}
		if(!empty($fieldcids))
		{
			sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where targetcid in ($fieldcids) and state=4 and uid<>'$userinfo[uid]' and uid > 0");
		}
		foreach($cities as $city)
		{
			updateCityResourceAdd($city['cid']);
		}
	}
	$troops=sql_fetch_rows("select id,targetcid from sys_troops where uid='$uid' and state=4");
	foreach($troops as $troop)
	{
		$wid=cid2wid($troop['targetcid']);
		$owneruid=sql_fetch_one_cell("select c.uid from sys_city c, mem_world m where m.wid='$wid' and m.ownercid=c.cid");
		if($owneruid!=$uid)
		{
			sql_query("update sys_troops set `state`=1,endtime=pathtime+unix_timestamp(),starttime=unix_timestamp() where id='$troo[id]'");
			updateCityResourceAdd($troop['cid']);
		}
	}
    notifyUnionChange($userinfo['uid'],$unionid,0);
    updateUnionRank($unionid);
    
    $msg = sprintf($GLOBALS['kickMember']['addUnionEvent'],$user[name],$userinfo[name]);
    addUnionEvent($unionid,EVT_KICK_MENBER,$msg);
    $param2=array();
    $param2[]=$unionid;
    return loadUnionMemberList($uid,$param2);
}

function changeLeader($uid,$param)
{
	$username = (trim(array_shift($param)));
	if(mb_strlen($username,"utf-8")==0)
	{
		throw new Exception($GLOBALS['changeLeader']['enter_target_name']);
	}
	else if(mb_strlen($username,"utf-8")>8)
    {
    	throw new Exception($GLOBALS['changeLeader']['name_length_most_8']);
    }
    $username=addslashes($username);
    $olduser = sql_fetch_one("select `name`,`union_id` from sys_user where uid='$uid'");
    $unionid=$olduser['union_id'];
    $union = sql_fetch_one("select * from sys_union where id='$unionid'");
    if ($union['leader'] != $uid)
    {
        throw new Exception($GLOBALS['changeLeader']['you_are_not_chief']);
    }
    $user = sql_fetch_one("select * from sys_user where name='$username'");
    if (empty($user))
    {
        throw new Exception($GLOBALS['changeLeader']['target_name_not_exist']);
    }
    else if ($user['union_id'] != $unionid)
    {
        throw new Exception($GLOBALS['changeLeader']['target_not_in_your_union']);
    }
    else if ($user['union_pos']!=2)
    {
    	throw new Exception($GLOBALS['changeLeader']['upgrade_vice_chief']);
    }
    $leader=$user['uid'];
    if($leader!=$uid)
    {
	    sql_query("update sys_user set union_pos=2 where uid='$uid'");
	    sql_query("update sys_user set union_pos=1 where uid='$leader'");
	    sql_query("update sys_union set `leader`='$leader' where id='$unionid'");
	    
	    $msg = sprintf($GLOBALS['changeLeader']['addUnionEvent'],$olduser[name],$username);
        addUnionEvent($unionid,EVT_CHANGE_LEADER,$msg);
    }                                                                                          
    return array();
}

function getUnionIntro($uid,$param)
{
    $user = sql_fetch_one("select union_id,union_pos from sys_user where uid='$uid'");
    $unionid=$user['union_id'];
    $unionpos=$user['union_pos'];
    if(($unionid<=0)||($unionpos==0)||($unionpos>2))
    {
        throw new Exception($GLOBALS['getUnionIntro']['you_are_not_chief']);
    }
    return sql_fetch_rows("select * from sys_union where id='$unionid'");
}
function modifyIntro($uid,$param)
{
    $name = trim(array_shift($param));
    $intro = addslashes(array_shift($param));
    $announce = addslashes(array_shift($param));      
   
   	if(mb_strlen($name,"utf-8")==0)
   	{
   		throw new Exception($GLOBALS['modifyIntro']['union_name_notNull']);
   	}
    else if (mb_strlen($name,"utf-8") > 8)
    {
        throw new Exception($GLOBALS['modifyIntro']['union_name_tooLong']);
    }
    else if ((!(strpos($name,'\'')===false))||(!(strpos($name,'\\')===false)))
    {
    	throw new Exception($GLOBALS['modifyIntro']['invalid_char']);
    }
    else if (sql_check("select * from cfg_baned_name where instr('$name',`name`)>0"))
    {
    	throw new Exception($GLOBALS['modifyIntro']['invalid_char']);
    }
    else if(mb_strlen($intro,"utf-8")>200)
    {
    	throw new Exception($GLOBALS['modifyIntro']['union_description_tooLong']);
    }
    else if (mb_strlen($announce,"utf-8")>500)
    {
    	throw new Exception($GLOBALS['modifyIntro']['union_announce_tooLong']);
    }
    $name=addslashes($name);
    $user= sql_fetch_one("select `name`,`union_id`,`union_pos` from sys_user where uid='$uid'");
    $unionid=$user['union_id'];
    $unionpos=$user['union_pos'];
    $union = sql_fetch_one("select * from sys_union where id='$unionid'");
    if(($unionid<=0)||($unionpos==0)||($unionpos>2))
    {
        throw new Exception($GLOBALS['modifyIntro']['you_are_not_chief']);
    }
    if($union['name']!=$name)
    {
		if(sql_check("select `id` from `sys_union` where `name`='$name'"))
		{
			throw new Exception($GLOBALS['modifyIntro']['union_name_in_use']);
		}
	}
    sql_query("update sys_union set name='$name',intro='$intro',announcement='$announce' where id='$unionid'");
    if($union['name']!=$name)
    {
    	$msg = sprintf($GLOBALS['modifyIntro']['addUnionEvent'],$user[name],$name);
    	addUnionEvent($unionid,EVT_CHANGE_NAME,$msg);
    }
    return array();
}                                                             

function getUnionRelation($uid,$param)
{
	$type=array_shift($param);
	$unionid = sql_fetch_one_cell("select union_id from sys_user where uid='$uid'");
    $ret = array();
    if (empty($unionid))
    {
        $ret[] = 0;
        $ret[] = $GLOBALS['getUnionRelation']['not_belongTo_union'];
    }
    else
    {
    	$ret[]=1;
    	$ret[]=sql_fetch_rows("select ur.`target` as unionid,u.`name`,r.`leader`,u.`prestige`,u.`member`,r.`rank` from `sys_union_relation` ur left join `sys_union` u on u.`id`=ur.`target` left join `rank_union` r on r.`uid`=ur.`target` where ur.`unionid`=$unionid and ur.`type`=$type");
    }
    return $ret;
}

function addUnionRelation($uid,$param)
{
	$type=array_shift($param);
	$name=array_shift($param);
	if(mb_strlen($name,"utf-8")==0)
	{
		throw new Exception($GLOBALS['addUnionRelation']['enter_target_name']);
	}
	else if(mb_strlen($name,"utf-8")>8)
	{
		throw new Exception($GLOBALS['addUnionRelation']['union_name_tooLong']);
	}
	$name=addslashes($name);
	$myinfo = sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$uid'");
	$unionid=$myinfo['union_id'];
	$unionpos=$myinfo['union_pos'];
    $union = sql_fetch_one("select `name`,`leader` from sys_union where id='$unionid'");
    if(($unionid<=0)||($unionpos==0)||($unionpos>2))
    {
        throw new Exception($GLOBALS['addUnionRelation']['you_are_not_chief']);
    }
    else if ($union['name']==$name)
    {
    	throw new Exception($GLOBALS['addUnionRelation']['cant_contact_with_oneself']);
    }
	$target=sql_fetch_one_cell("select `id` from `sys_union` where `name`='$name'");
	if(empty($target))
	{
		throw new Exception($GLOBALS['addUnionRelation']['target_union_not_exist']);
	}
	$oldRelation=sql_fetch_one("select *,unix_timestamp() as nowtime from sys_union_relation where unionid='$unionid' and target='$target'");
	if((!empty($oldRelation))&&$oldRelation['type']==$type)
	{
		$param2=array();
		$param2[]=$type;
		$param2[]=$name;
		return getUnionRelation($uid,$param2);
	}
	if($oldRelation['time']>$oldRelation['nowtime']-900)
	{
		$remainTime=MakeTimeLeft(900+$oldRelation['time']-$oldRelation['nowtime']);
		throw new Exception(sprintf($GLOBALS['addUnionRelation']['too_frequency'],$remainTime));
	}
	sql_query("insert into `sys_union_relation` (`type`,`unionid`,`target`,time) values ('$type','$unionid','$target',unix_timestamp()) on duplicate key update `type`='$type',time=unix_timestamp()");
	
	if($type==0)
	{
		$relation=$GLOBALS['addUnionRelation']['friendly'];
	}
	else if($type==1)
	{
		$relation=$GLOBALS['addUnionRelation']['neutral'];
	}
	else if($type==2)
	{
		$relation=$GLOBALS['addUnionRelation']['hostile'];
		
		$receivers=sql_fetch_rows("select uid from sys_user where union_id='$target' and union_pos>=1 and union_pos<3");
		foreach($receivers as $receiver)
		{
			$msg = sprintf($GLOBALS['addUnionRelation']['mail_content'],$union[name],$relation);
			sendSysMail($receiver['uid'],$GLOBALS['addUnionRelation']['union_declare_war'],$msg);
		}
		
		$msg = sprintf($GLOBALS['addUnionRelation']['unionWar_declare'],$union[name],$name);
		//throw new Exception("insert into sys_inform (`type`,`inuse`,`starttime`,`endtime`,`interval`,`scrollcount`,`color`,`msg`) values (0,1,unix_timestamp(),unix_timestamp()+10,500,1,0,'$msg')");
		if(!sql_check("select id from sys_inform where scrollcount=100000+'$unionid'"))
		{
			sql_query("insert into sys_inform (`type`,`inuse`,`starttime`,`endtime`,`interval`,`scrollcount`,`color`,`msg`) values (0,1,unix_timestamp(),unix_timestamp()+600,50000,100000+'$unionid',16727871,'$msg')");
		}
	}
	
	$msgAToB = sprintf($GLOBALS['addUnionRelation']['set_A_and_B'],$myinfo[name],$name,$relation);
	addUnionEvent($unionid,EVT_RELATION_FRIEND+$type,$msgAToB);
	$msgBToA = sprintf($GLOBALS['addUnionRelation']['set_B_and_A'],$union[name],$relation);	
	addUnionEvent($target,EVT_RELATION_FRIEND+$type,$msgBToA);
	
	$param2=array();
	$param2[]=$type;
	return getUnionRelation($uid,$param2);
}

function removeUnionRelation($uid,$param)
{
	$type=array_shift($param);
	$target=array_shift($param);
	$myinfo = sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$uid'");
	$unionid=$myinfo['union_id'];
	$unionpos=$myinfo['union_pos'];
	if(($unionid<=0)||($unionpos==0)||($unionpos>2))
    {
        throw new Exception($GLOBALS['removeUnionRelation']['you_are_not_chief']);
    }
    
    $union = sql_fetch_one("select `id`,`name`,`leader` from sys_union where id='$unionid'");

    $targetname=sql_fetch_one_cell("select `name` from `sys_union` where `id`='$target'");
	sql_query("update `sys_union_relation` set type=3 where `unionid`='$unionid' and `target`=$target and `type`='$type'");
	
	if($type==0)
	{
		$relation=$GLOBALS['removeUnionRelation']['friendly'];
	}
	else if($type==1)
	{
		$relation=$GLOBALS['removeUnionRelation']['neutral'];
	}
	else if($type==2)
	{
		$relation=$GLOBALS['removeUnionRelation']['hostile'];
	}
	
	$msgAToB = sprintf($GLOBALS['removeUnionRelation']['cancel_A_and_B'],$myinfo[name],$targetname,$relation);
	addUnionEvent($unionid,EVT_RELATION_FRIEND+$type,$msgAToB);
	$msgBToA = sprintf($GLOBALS['removeUnionRelation']['cancel_B_and_A'],$union[name],$relation);
	addUnionEvent($target,EVT_RELATION_FRIEND+$type,$msgBToA);
	
	$param2=array();
	$param2[]=$type;
	return getUnionRelation($uid,$param2);
}

//获得联盟事件
function getUnionEvent($uid,$param)
{
	$page=array_shift($param);
	$unionid=sql_fetch_one_cell("select `union_id` from `sys_user` where `uid`='$uid'");
	if(empty($unionid))
	{
		throw new Exception($GLOBALS['getUnionEvent']['not_in_union']);
	}
	$evtCount=sql_fetch_one_cell("select count(*) from `sys_union_event` where `unionid`='$unionid'");
	$pageCount=ceil($evtCount/EVENT_PAGE_CPP);
	if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    	$pageCount=0;
    }
    $ret=array();
    $ret[]=$pageCount;
    $ret[]=$page;
    if($evtCount>0)
    {
    	$start=$page*EVENT_PAGE_CPP;
    	$ret[]=sql_fetch_rows("select * from `sys_union_event` where `unionid`='$unionid' order by `evttime` desc limit $start,".EVENT_PAGE_CPP);
    }
    else
    {
    	$ret[]=array();
    }
    return $ret;
}
//添加联盟事件
function addUnionEvent($unionid,$type,$content)
{
	$content=addslashes($content);
	sql_insert("insert into `sys_union_event` (`unionid`,`type`,`content`,`evttime`) values ('$unionid','$type','$content',unix_timestamp())");
	sql_insert("insert into `mem_union_event` (`unionid`,`type`,`content`,`evttime`) values ('$unionid','$type','$content',unix_timestamp())");
}

//设置某个成员权限

function setUnionProvicy($uid,$param)
{
	$target=array_shift($param);
	$position=array_shift($param);
	
	$myinfo=sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$uid'");
	$unionid=$myinfo['union_id'];
	$unionpos=$myinfo['union_pos'];
	$targetinfo=sql_fetch_one("select name,union_id,union_pos from sys_user where uid='$target'");
	$targetunionpos=$targetinfo['union_pos'];
	if(($unionid<0)||($unionid!=$targetinfo['union_id'])||($unionpos==0)||(($targetunionpos!=0)&&($unionpos>=$targetunionpos)))
	{
		throw new Exception("a");
		throw new Exception($GLOBALS['setUnionProvicy']['not_authorizied']);
	}
	else if(($position!=0)&&($position<=$unionpos))
	{
		throw new Exception("b");
		throw new Exception($GLOBALS['setUnionProvicy']['not_authorizied']);
	}
	sql_query("update sys_user set union_pos='$position' where uid='$target'");
	
	if($position==0) $targetname=$GLOBALS['setUnionProvicy']['union_memeber'];
	else if ($position==2) $targetname=$GLOBALS['setUnionProvicy']['union_vice_chief'];
	else if ($position==3) $targetname=$GLOBALS['setUnionProvicy']['union_elder'];
	else if ($position==4) $targetname=$GLOBALS['setUnionProvicy']['union_official'];
    if($position!=$targetinfo['union_pos'])
    {
	    if(($position==0)||($targetinfo['union_pos']>0&&$position>$targetinfo['union_pos']))
	    {
	    	$msg = sprintf($GLOBALS['setUnionProvicy']['descend_level'],$myinfo[name],$targetinfo[name],$targetname);
		    addUnionEvent($unionid,EVT_PROVICY,$msg);
	    }
	    else
	    {
	    	$msg = sprintf($GLOBALS['setUnionProvicy']['upgrade_level'],$myinfo[name],$targetinfo[name],$targetname);	    	
		    addUnionEvent($unionid,EVT_PROVICY,$msg);
	    }
    }
	$ret=array();
	$ret[]=$target;
	$ret[]=$position;
	return $ret;
}
//联盟官员辞职
function demissionUnion($uid,$param)
{
	$myinfo=sql_fetch_one("select name,`union_id`,union_pos from `sys_user` where `uid`='$uid'");
	$unionid=$myinfo['union_id'];
	$unionpos=$myinfo['union_pos'];
	if(empty($unionid))
	{
		throw new Exception($GLOBALS['demissionUnion']['not_in_union']);
	}
	else if($unionpos==0)
	{
		throw new Exception($GLOBALS['demissionUnion']['no_any_position']);
	}
	$union=sql_fetch_one("select leader from sys_union where `id`='$unionid'");
	if(empty($union))
	{
		throw new Exception($GLOBALS['demissionUnion']['union_dissmissed']);
	}
	if($uid==$union['leader'])
	{
		throw new Exception($GLOBALS['demissionUnion']['chief_cant_resign']);
	}
	sql_query("update sys_user set union_pos=0 where uid='$uid'");
	if($unionpos==0) $targetname = $GLOBALS['demissionUnion']['union_memeber'];
	else if ($unionpos==2) $targetname = $GLOBALS['demissionUnion']['union_vice_chief'];
	else if ($unionpos==3) $targetname = $GLOBALS['demissionUnion']['union_elder'];
	else if ($unionpos==4) $targetname = $GLOBALS['demissionUnion']['union_official'];
	
	$msg = sprintf($GLOBALS['demissionUnion']['add_union_event'],$myinfo[name],$targetname);
	addUnionEvent($unionid,EVT_DEMISSION,$msg);
	return array();
}

//获取联盟军情列表

function getUnionReport($uid,$param)
{
	$page=array_shift($param);
	$unionid=sql_fetch_one_cell("select `union_id` from `sys_user` where `uid`='$uid'");
	if(empty($unionid))
	{
		throw new Exception($GLOBALS['getUnionReport']['not_in_union']);
	}
	$reportCount=sql_fetch_one_cell("select count(*) from `sys_union_report` where `unionid`='$unionid'");
	$pageCount=ceil($reportCount/UNION_REPORT_PAGE_CPP);
	if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    	$pageCount=0;
    }
    $ret=array();
    $ret[]=$pageCount;
    $ret[]=$page;
    if($reportCount>0)
    {
    	$start=$page*UNION_REPORT_PAGE_CPP;
    	$ret[]=sql_fetch_rows("select `id`,`type`,`enemy`,`time`,`description` from `sys_union_report` where `unionid`='$unionid' order by `id` desc limit $start,".UNION_REPORT_PAGE_CPP);
    }
    else
    {
    	$ret[]=array();
    }
    return $ret;
}

function getUnionReportDetail($uid,$param)
{
	$id=array_shift($param);
	$unionid=sql_fetch_one_cell("select `union_id` from `sys_user` where `uid`='$uid'");
	if(empty($unionid))
	{
		throw new Exception($GLOBALS['getUnionReportDetail']['not_in_union']);
	}
    $ret=array();
   	$caution=sql_fetch_one("select type, origincid,origincity,happencid,happencity,time,description from sys_union_report where id='$id' and `unionid`='$unionid'");
   	if(empty($caution))
   	{
   		throw new Exception($GLOBALS['getUnionReportDetail']['report_not_found']);
   	}
   	else
   	{
   		$ret[]=$caution;
   	}
    return $ret;
}

?>