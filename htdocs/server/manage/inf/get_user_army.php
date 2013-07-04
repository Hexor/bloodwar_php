<?php
	//获得所有城市军队信息
	//参数列表：
	//uid:用户id
	//返回军队信息
	if (!defined("MANAGE_INTERFACE")) exit;
	if (!isset($uid))exit("param_not_exist");
	$city = sql_fetch_rows("select cid,name from sys_city where uid =$uid ");
	if (!empty($city)){
		foreach ($city as &$city_list){
			$result = sql_fetch_rows("select c.name as `name`,s.count as `count`,s.sid as `sid` 
			from sys_city_soldier as s,cfg_soldier as c where s.cid='$city_list[cid]' and s.sid=c.sid order by c.sid asc");			
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
			$city_list['army']=$army;
		}
		$ret=$city;
	}
	else {
		$ret="no data";
	}

	
?>