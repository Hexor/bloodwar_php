<?php 
/**
 * 随机产生圆盘的物品
 * @param $uid
 * @return unknown_type
 */
function getGoods($uid, $param)
{
	$first = array_shift($param);
	$infor = sql_fetch_one("select * from mem_lottery_goods where uid=$uid and `time`=current_date()");
	if(empty($infor)||$first!=0)
	{
		$total = 0;
		//1~8等
		$records = getAllLevelGoods();
		$record_str = "";
		$rcount = 1;
		for($i=0; $i<8; $i++){
			for($j=0; $j<count($records[$i]); $j++){
				$ids = logIDs($records[$i][$j]);
				$record_str = $record_str."".$ids;
				
				if($rcount!=8)
					$record_str = $record_str.",";
					
				$rcount++;
			}
		}
		sql_query("insert into mem_lottery_goods(uid, records, `time`) values($uid, '$record_str',  current_date()) on duplicate key update records='$record_str'");
	}
	else{
		$records = retrieveGoods($infor['records']);
	}
	
	
	$ret = array();
	
	$tmp = randWin($records);
	
	$win_type = $tmp[0];  $win_id = $tmp[1];
	$ret[] = $records;
	$ret[] = $win_type;
	$ret[] = $win_id;
	
	$tcount = getTodayCount($uid);
	$ret[] = $tcount;
	return $ret;
	
}

function getTodayCount($uid)
{
	$count = sql_fetch_one_cell("select count(*) from log_lottery where uid=$uid and date(`time`)=current_date()");
	if(empty($count))
		$count = 0;
	return $count;
}

/**
 * 生产中将id
 * @param $records1
 * @param $records2
 * @param $records3
 * @param $records4
 * @return unknown_type
 */
function randWin($records)
{
	//中奖概率, 7-8等, 5-6等, 2-4等, 1等
	$prob = array(0, 4000, 2500, 1500, 1000, 500, 300, 199, 1);
	$win_rand = mt_rand(1, 10000);
	$win_id = -1;
	$win_type = 0; //cfg_goods
	
	$win_index = 7;
	for($i=1; $i<=8; $i++)
	{
		if($win_rand[$i]< $win_rand && $win_rand<=$prob[$i]){
			$win_index = $i-1;
			break;
		}
	}
	
	for($i=$win_index; $i<8; $i++)
	{
		$win_record = $records[$i];
		if(count($win_record) == 0) continue;
		if($win_record[0]['gid']!=null)
			$win_id = $win_record[0]['gid'];
		else{
			$win_id = $win_record[0]['id'];
			$win_type=1; //装备
		}
	}
	
	$ret = array();
	$ret[] = $win_type;
	$ret[] = $win_id;
	return $ret;
}

function logIDs($rs)
{
	$ret = "";
	
	$count = $rs['count'];
	if($rs['gid']!=null){
		$gid = $rs['gid'];
		$ret = $ret."0,$gid,$count";
	}
	else{
		$id = $rs['id'];
		$ret = $ret."1,$id,$count";
	}
	
	return $ret;
}

function retrieveGoods($ids)
{
	$ret = array(8); //数组的数组
	for($i=0; $i<8; $i++)
		$ret[$i] = array();
	$id_ary = explode(",", $ids);
	for($i=0; $i<count($id_ary); $i+=3)
	{
		$type = $id_ary[$i];
		$id = $id_ary[$i+1];
		$count = $id_ary[$i+2];
		if($type==1){ //装备{
			$goods = sql_fetch_one("select * from cfg_armor where id=$id");
		}
		else if($type==0){
			$goods = sql_fetch_one("select * from cfg_goods where gid=$id");
		}
		$goods['count'] = $count;
		$ret[intval($goods['level'])-1][] = $goods;
	}
	return $ret;
}

function  getAllLevelGoods() // 1~8级别
{
	$ret = array();
	$total = 0;
	//1等
	$count = mt_rand(0,1);
	$records1 = getGoodsByType($count, 1, $total);
	$total += count($records1);

	//2等
	$count = mt_rand(0,1);
	$records2 = getGoodsByType($count, 2, $total);
	$total += count($records2);
	
	//3等
	$count = mt_rand(0,1);
	if($total==0)
		$count = 1;
	$records3 = getGoodsByType($count, 3, $total);
	$total += count($records3);
	
	//7等
	$count = mt_rand(1,2);
	$records7 = getGoodsByType($count, 7, $total);
	$total += count($records7);
	
	//8等
	$count = mt_rand(1,2);
	$records8 = getGoodsByType($count, 8, $total);
	$total += count($records8);
	
	//4等
	$count = mt_rand(0,2);
	$records4 = getGoodsByType($count, 4, $total);
	$total += count($records4);
	
	//5等
	$count = mt_rand(0,2);
	$records5 = getGoodsByType($count, 5, $total);
	$total += count($records5);
	
	//6等
	$count = mt_rand(0,2);
	if($total+$count < 8)
		$count = 8-$total; //保证8个
	$records6 = getGoodsByType($count, 6, $total, 1);
	$total += count($records6);
	
	$ret[] = $records1; $ret[] = $records2; $ret[] = $records3;$ret[] = $records4;
	$ret[] = $records5;$ret[] = $records6; $ret[] = $records7;$ret[] = $records8;
	return $ret;
}

function getGoodsByType($count, $level, $total, $is_last=0)
{
	$ret = array();
	if($total+$count > 8)
		$count = max(0, 8-$total);
	
	for($i=0; $i<$count; $i++)
	{
		$type = randType();
		$record = "";
		switch($type){
			case 0:
				$record = sql_fetch_one("select * from cfg_goods where gid<10000 and `group` in (4, 5) and `level`=$level order by rand() limit 1");
				if($record==false) break;
				$record['count'] = intval( (mt_rand(0, 10)/10) * (intval($record['level'])-1) + 1 );
				break;
			case 1:
				$record = sql_fetch_one("select * from cfg_goods where gid<10000 and `group` in (0,1,2,3) and `level`=$level order by rand() limit 1");
				if($record==false) break;
				$record['count'] = 1;
				break;
			case 2:
				$record = sql_fetch_one("select * from cfg_armor where `level`=$level order by rand() limit 1");
				if($record==false) break;
				$record['count'] = 1;
				break;
			case 3:
				$record = sql_fetch_one("select * from cfg_goods where gid=0");
				$record['count'] = intval(200/pow($level, 2));
				break;
		}
		if($record!=false)
			$ret[] = $record;
	}
	if($is_last){
		if($total + count($ret) < 8)
		{
			$left = 8 - $total - count($ret);
			for($i=0; $i<$left; $i++){
				$record = sql_fetch_one("select * from cfg_goods where gid<10000 and `group` in (0,1,2,3) and `level`=6 order by rand() limit 1");
				$record['count'] = 1;
				$ret[] = $record;
			}
		}
	}
	return $ret;
}

function randType()
{
	$rand = mt_rand(1, 100);
	if($rand<= 50)
		return 0; //材料
	else if($rand>50 && $rand<=50+15)
		return 1; //道具
	else if($rand>50+15 && $rand<50+15+30)
		return 2; //装备
	else 
		return 3; //礼金
}

function getWin($uid, $param)
{
	if(getTodayCount($uid)>=50){
		throw new Exception($GLOBALS['lottery']['full_playcount']);
	}
	$type = array_shift($param);
	$id = array_shift($param);
	$count = array_shift($param);
	if($type == 1){
		$arminfo = sql_fetch_one("select * from cfg_armor where id='$id'");
		if(empty($arminfo))
			throw new Exception($GLOBALS['lottery']['no_such_armor']);
		$hp = $arminfo['ori_hp_max'];
		sql_query("insert into sys_user_armor (uid,armorid,hp, hp_max, hid) values ('$uid','$id',$hp*10, '$hp', 0)");
	}
	else if($type == 0){
		$goods = sql_fetch_one("select * from cfg_goods where gid='$id'");
		if(empty($goods))
			throw new Exception($GLOBALS['lottery']['no_such_goods']);
			
		sql_query("insert into sys_goods(uid, gid, `count`) values($uid, $id, $count) on duplicate key update `count`=`count`+$count");
	}
	else{
		throw new Exception($GLOBALS['lottery']['no_such_goods']);
	}
	//throw new Exception($GLOBALS['lottery']['get_win']);
	$ret = array();
	$ret[] = 1;
	return $ret;
	
	
}

function restart($uid, $param)
{
	$records = array_shift($param);
	return randWin($records);
}

//增加一次开奖记录
function addCount($uid, $param)
{
	$type = array_shift($param);
	$gid = array_shift($param);
	sql_query("insert into log_lottery(uid, `time`, gid, `type`) values($uid, NOW(), $gid, $type)");
}

function useMoney($uid)
{
	$use_money = 10; //消耗10个元宝
	$money = sql_fetch_one_cell("select money from sys_user where uid=$uid");
	if(empty($money) || $money<10){
		throw new Exception($GLOBALS['lottery']['no_money']);
	} 
	sql_query("update sys_user set money=money-10 where uid=$uid");
	$ret = array();
	$ret[] = 1;
	return $ret;
}

?>