<?php
require_once("./interface.php");
require_once("./utils.php");

function getGuidesByGroup($uid,$param){
	$group=array_shift($param);
	$ret=array();
	$ret[]=sql_fetch_rows("select b.* from  cfg_guide b where `group`='$group'  order by b.gid");
	return $ret;
}

?>