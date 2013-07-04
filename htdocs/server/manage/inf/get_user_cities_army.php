<?php
	//获得一个城市军队信息
	//参数列表：
	//cid:城市id
	//返回军队信息
	if (!defined("MANAGE_INTERFACE")) exit;	
	if (!isset($cid))exit("param_not_exist");
	$result = sql_fetch_rows("select c.name as `name`,s.count as `count`,s.sid as `sid` 
	from sys_city_soldier as s,cfg_soldier as c where s.cid='$cid' and s.sid=c.sid order by c.sid asc");
	if(empty($result)){
		$ret = 'no data';
	}
	else {
		$army=array();
		$armyno=array();
		foreach ($result as $list){
			$key=$list['sid'];
			$armyno[]=$list['sid'];
			$army[$key]=$list;
		}
		for ($i=1;$i<13;$i++){
			if (!in_array($i,$armyno)){
				$army[$i]=array('count'=>0,'sid'=>$i);
			}
		}
		$ret=$army;
	}
?>