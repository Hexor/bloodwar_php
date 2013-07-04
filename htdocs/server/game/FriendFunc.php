<?php
require_once("./interface.php");
require_once("./utils.php");

//添加好友或者仇人
function addUserRelation($uid,$param){
	//添加的名字
	$tname=array_shift($param);
	//0 好友 1 仇
	$type=intval(array_shift($param));
	if($type>=2) throw new Exception($GLOBALS['friend']['op_not_exist']);

	//目标用户是否存在
	$tuid = sql_fetch_one_cell("select uid from sys_user where name='$tname'");
	if(empty($tuid)) throw new Exception($GLOBALS['friend']['user_not_exist']);

	$count= sql_fetch_one_cell("select count(*) from sys_user_relation where uid='$uid'");
	//好友或者仇人大于等于20个
	if($count>=20) throw new Exception($GLOBALS['friend']['too_many_relations']);
	//添加到好友列表
	sql_insert("insert into sys_user_relation (uid,tuid,tname,type,time) values ('$uid','$tuid','$tname','$type',unix_timestamp())");

	$ret = array();
	$ret[] = $tuid;
	$ret[] = $tname;
	$ret[] = $type;
	return $ret;
}

function removeRelation($uid,$param){
	//删除的名字
	$tname=array_shift($param);
		//0 好友 1 仇
	$type=intval(array_shift($param));
	sql_query("delete from sys_user_relation  where tname='$tname' and type='$type'");
	$ret = array();
	$ret[] = $tname;
	$ret[] = $type;
	return $ret;
}

//取得好友和仇人列表
function getUserRelation($uid,$param){	
	$relations= sql_fetch_rows("select u.`tuid`,s.`name` as tname,u.type,n.name as unionName ,s.nobility from `sys_user_relation` u left join `sys_user` s on u.tuid=s.uid left join sys_union n on s.union_id=n.id where u.uid='$uid'");
	$ret= array();
	if(empty($relations)){
		return $ret;
	}
	
	$index=0;
	foreach($relations as $relation){
		$real=getBufferNobility($relation['tuid'],$relation['nobility']);
		$relation['nobility']=$real;
		$ret[0][$index]=$relation;
		$index++;
	}
	
	return  $ret;
}
//51好友
function get51User($uid,$param){	
	require_once("51utils.php");
    return get51FriendsEvent($uid);   
}
function ignoreFiendJoin($uid,$param){
	$id=intval(array_shift($param));
	require_once("51utils.php");
	remove51FriendsEvent($id);
}

function addFriend($uid,$param){
	$id=intval(array_shift($param));
	$tuid=intval(array_shift($param));	
	require_once("51utils.php");
	add51Friend($uid,$tuid,$id);
}
function invite51Friend($uid,$param){
	require_once("51utils.php");
	return invite51Friend51($uid);
}
?>