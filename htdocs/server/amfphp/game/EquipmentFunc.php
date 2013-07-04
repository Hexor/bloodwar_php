<?php 
require_once("./interface.php");
require_once("./utils.php");


function debug($msg)
{
	throw new Exception($msg);
}
/**
 * 升级材料
 * @param $uid
 * @param $param
 * @return unknown_type
 */
function updateMaterials($uid, $param)
{
	//客户端传过来一个合成公式，比如gid,count, gid,count
	$mid1 = array_shift($param);
	$count1 = array_shift($param);
	$mid2 = array_shift($param);
	$count2 = array_shift($param);
	$mid3 = array_shift($param);
	$count3 = array_shift($param);
	$exp = makeExp($mid1, $count1, $mid2, $count2, $mid3, $count3);
	$recipe = sql_fetch_one( "SELECT * FROM cfg_recipe WHERE recipe=$exp" );
	if( empty($recipe) ){
		throw new Exception($GLOBALS['equipment']['no_recipe']);
	}
	
	if(true == probability($recipe['probability']))
		sql_query("INSERT sys_goods(gid, `count`) VALUES($recipe[gid], 1) ON DUPLICATE KEY UPDATE `count`=`count`+1 WHERE uid=$uid");
		
	//扣除材料
	sql_query("UPDATE sys_goods set `count`=`count`-$count1 WHERE uid=$uid AND gid=$mid1");
	sql_query("UPDATE sys_goods set `count`=`count`-$count2 WHERE uid=$uid AND gid=$mid2");
	sql_query("UPDATE sys_goods set `count`=`count`-$count3 WHERE uid=$uid AND gid=$mid3");
}

/**
 * 装备镶嵌
 * @param $uid
 * @param $param
 * @return unknown_type
 */
function armorEmbed($uid, $param)
{
	$armor_id = array_shift($param);
	$pearl_id = array_shift($param);
	$hole_pos = array_shift($param);
	
	$phole = "$pearl_id,".$hole_pos.",";
	
	sql_query("UPDATE sys_user_armor SET embed_pearl=concat(embed_pearl, '$phole') ON DUPLICATE KEY UPDATE WHERE uid=$uid AND armorid=$armor_id");
} 


/**
 * 初始化数据
 * @param $uid
 * @return unknown_type
 */
function loadEquitmentInfor($uid, $param)
{
	$ret = array();
	$step = array_shift($param);
	
	$ret[] = $step;
	$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = b.gid) from cfg_goods b where b.gid in (202, 203, 204, 205)");
	if($step ==0 )
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where ((a.gid>=300 and a.gid<=379) or a.gid=205) and `group`=4 order by a.gid");
	return $ret;
}
//***********************强化****************************************//

function getEquipmentGoods($uid, $param)
{
	$gid = array_shift($param);
	$obj = sql_fetch_one("select * from sys_goods where uid=$uid and gid=$gid");
	if(empty($obj) || $obj['count']==0)
		throw new Exception($GLOBALS['equipment']['no_goods']);
	$ret=array();
	$ret[] = $obj;
	return $ret;
}

function doStrong($uid, $param)
{
	$tianGongFu_count = array_shift($param); //0, 1
	$qianKun_count = array_shift($param); //0, 1
	if($tianGongFu_count==1)
	{
		$tianGongFu_goods = sql_fetch_one("select * from sys_goods where gid=203 and uid=$uid");
		if(empty($tianGongFu_goods) || $tianGongFu_goods['count']<=0)
			throw new Exception($GLOBALS['blacksmith']['no_tiangongfu_goods']);
	}
	
	if( $qianKun_count==1 )
	{
		$qianKun_goods = sql_fetch_one("select * from sys_goods where gid=204 and uid=$uid");
		if(empty($qianKun_goods) || $qianKun_goods['count']<=0)
			throw new Exception($GLOBALS['blacksmith']['no_qiankun_goods']);
	}
	
	$strong_pearl = sql_fetch_one("select * from sys_goods where gid=205 and uid=$uid");
	if(empty($strong_pearl) || $strong_pearl['count']<=0)
	{
		throw new Exception($GLOBALS['equipment']['no_strong_pearl']);
	}
	
	$sid = array_shift($param);
	
	$ret = array();
	
	$next_level_infor = sql_fetch_one("select * from cfg_strong_probability where level=(select strong_level+1 from sys_user_armor where sid=$sid and uid=$uid)");
	
	if(empty($next_level_infor))
		throw new Exception($GLOBALS['equipment']['cannot_strong']);
	
	
	$succ_add = $tianGongFu_count * 30; //提高30%成功率	
	

	$is_succ = isSucc(1, intval($next_level_infor['suc_value']) + $succ_add );
	if( $is_succ )
	{
		sql_query("update sys_user_armor set strong_value=$next_level_infor[strong_value], strong_level=$next_level_infor[level] where uid=$uid and sid=$sid");
		$ret[] = 0;
	}
	else
	{
		$zero_value =  intval($next_level_infor['zero_value']);
		$degrade_value = intval($next_level_infor['degrade_value']);
		$intact_value = intval($next_level_infor['intact_value']);
		
		$p_value = pValue();
		
		if($qianKun_count==0)
		{//归零，降级，完整
			if( fitPValue($p_value, 1, $zero_value) ){//归零
				sql_query("update sys_user_armor set strong_value=0, strong_level=0 where uid=$uid and sid=$sid");
				$ret[]=1;
			}
			else if(fitPValue($p_value, $zero_value+1, $zero_value+$degrade_value)){	//降级
				$before_level = intval($next_level_infor['level']) - 2;
				$before_level_infor = sql_fetch_one("select cfg_strong_probability where level=$before_level");
				sql_query("update sys_user_armor set strong_value=$before_level_infor[strong_value], strong_level=$before_level_infor[level] where uid=$uid and sid=$sid");
				$ret[]=2;
			}
			else if( fitPValue($p_value, $zero_value+$degrade_value+1, $zero_value+$degrade_value+$intact_value) ){//无损失
				$ret[]=3;
			}
		}
		else{ //可以购买道具“乾坤宝珠”，在强化失败后装备不会消失
			if(fitPValue($p_value, $zero_value+1, $zero_value+$degrade_value)){	//降级
				$before_level = intval($next_level_infor['level']) - 2;
				$before_level_infor = sql_fetch_one("select cfg_strong_probability where level=$before_level");
				sql_query("update sys_user_armor set strong_value=$before_level_infor[strong_value], strong_level=$before_level_infor[level] where uid=$uid and sid=$sid");
				$ret[]=2;
			}
			else { //if( fitPValue($p_value, $zero_value+$degrade_value+1, $zero_value+$degrade_value+$intact_value) ){//无损失
				$ret[]=3;
			}
		}
	}
	
	if($tianGongFu_count==1)
	{
		sql_query("update sys_goods set `count`=`count`-1 where gid=203 and uid=$uid");
	}
	
	if( $qianKun_count==1 )
	{
		sql_query("update sys_goods set `count`=`count`-1 where gid=204 and uid=$uid");
	}
	
	sql_query("update sys_goods set `count`=`count`-1 where gid=205 and uid=$uid");
	
	
	return $ret; 
}
//***********************强化****************************************//

//**************************镶嵌*************************************************
function initHoles($uid, $param)
{
	$sid = array_shift($param);
	$armor = sql_fetch_one("select * from sys_user_armor a left join cfg_armor c on c.id=a.armorid where a.uid='$uid' and a.sid=$sid limit 1");
	$holes = "";
	$holes = "";
	$pearls = "0,0,0,0,0";
	if($armor['part']!=12){ //坐骑
		$rule = sql_fetch_one_cell("select rule from cfg_armor_hole_rule where `type`=$armor[type]");
		$holes = parseHoleRule($rule);
		//$pearls = "0,0,0,0,0"; //5个位置的珍珠
	}
	else{
		$holes = "0,0,0,0,0";
	}
	
	sql_query("update sys_user_armor set embed_holes='$holes', embed_pearls='$pearls' where sid=$sid");
	
	$ret = array();
	
	$armor['embed_holes'] = $holes;
	$armo['embed_pearls'] = $pearls;
	$ret[] = $armor;
	return $ret;
	
}

function parseHoleRule($rule)
{
	$ary = explode(",", $rule);
	$ret = "";
	for($i=0; $i<count($ary); $i++)
	{
		if($ary[$i] == "0")
			$ret = $ret."1"; //初级打孔器
		else if($ary[$i] == "N/A")
			$ret = $ret."3"; //孔不开
		else if ($ary[$i] == "-1")
			$ret = $ret."2"; //高级打孔器
		else{
			$tmp = getHole(1, intval($ary[$i]));
			$ret = $ret.$tmp;
		}
		if($i < count($ary)-1)
			$ret = $ret.",";
	}
	return $ret;	
}

function getHole($min, $max)
{
	if(isSucc($min, $max))
		return "0"; //开孔
	else 
		return "1"; //需要初级打孔器
}

function openHole($uid, $param)
{
	$sid = array_shift($param); //装备id
	$gid = array_shift($param);
	$pos = array_shift($param);
	$goods = sql_fetch_one("select * from sys_goods where gid=$gid and uid=$uid");
	if(empty($goods) || $goods['count']<=0)
		throw new Exception($GLOBALS['blacksmith']['no_open_hole_goods']);
	
	
	if($gid=201) //腐蚀剂
	{
		$armor = sql_fetch_one("select * from sys_user_armor a left join cfg_armor c on c.id=a.armorid where a.uid='$uid' and a.sid=$sid limit 1");
		$ary = explode(",", $armor['embed_pearls']);
		$ary[$pos] = 0;
		$str = assembleEmbedHoles($ary, $pos, 0);
		sql_query("update sys_user_armor set embed_pearls='$str' where uid=$uid and sid=$sid");
		sql_query("update sys_goods set `count`=`count`-1 where uid=$uid and gid=$gid");
		$armor['embed_pearls'] = $str;
		$ret[] = $armor;
		//return $ret;
	}
	else{
	//开孔
		$armor = sql_fetch_one("select * from sys_user_armor a left join cfg_armor c on c.id=a.armorid where a.uid='$uid' and a.sid=$sid limit 1");
		$ary = breakUpEmbedHoles( $armor['embed_holes'] );
		
		$ret_str = assembleEmbedHoles($ary, $pos, 0); //0表示开孔
		
		sql_query("update sys_user_armor set embed_holes='$ret_str' where uid=$uid and sid=$sid");
		
		sql_query("update sys_goods set `count`=`count`-1 where uid=$uid and gid=$gid");
		
		$armor['embed_holes'] = $ret_str;
		$ret = array();
		$ret[] = $armor;
		
		//return $ret;
	}
	$gids = $armor['embed_pearls'];
	$objs = array();
	for($i=0; $i<count($gids); $i++)
	{
		if($gids[$i] ==0)
			array_push($objs, 0);
		else{
			$record = sql_fetch_one("select * from cfg_goods where  gid=$gids[$i] ");
			array_push($objs, $record);
		}
	}
	$ret[] = $objs;
	return $ret;
} 

/**
 * 获取装备镶嵌的珍珠 描述
 * @param $uid
 * @param $param
 * @return unknown_type
 */
function loadEmbedPearlByArmor($uid, $param)
{
	$gidstr = array_shift($param);
	$ret = array();
	$objs = array();
	$gids = explode(",", $gidstr);
	for($i=0; $i<count($gids); $i++)
	{
		if($gids[$i] ==0)
			array_push($objs, 0);
		else{
			$record = sql_fetch_one("select * from cfg_goods where  gid=$gids[$i] ");
			array_push($objs, $record);
		}
	}
	$ret[] = $objs;
	return $ret;
}

/**
 * 获取用户所有的镶嵌珠宝
 * @param $uid
 * @return unknown_type
 */
function loadEmbedPearl($uid)
{
	$ret = array();
	$ret[] = sql_fetch_rows("select * from sys_goods g left join cfg_goods f on f.gid=g.gid where g.uid='$uid' and g.`count` > 0 and g.gid>=300 and g.gid<=379 and f.group=4 order by f.`group`,f.position");
	return $ret;
}

/**
 * 获取打孔道具的数量
 * @param $uid
 * @param $param
 * @return unknown_type
 */
function onLoadDaKong($uid, $param)
{
	$val = array_shift($param);
	$gid = 0;

	if($val == 1)
		$gid = 206; //初级打孔器
	if($val ==2)
		$gid = 207; //高级打孔器
	
	$ret = array();
	$ret[] = sql_fetch_one_cell("select `count` from sys_goods where gid=$gid and uid=$uid");
	return $ret;
}

/**
 * 镶嵌
 */
function doEmbed($uid, $param)
{
	$sid = array_shift($param);
	$pearls = array_shift($param);
	
	$old_pearls = sql_fetch_one_cell("select embed_pearls from sys_user_armor where sid=$sid and uid=$uid");
	if($pearls != $old_pearls)
	{ 
		$ary = explode(",", $pearls);
		foreach ($ary as $gid)
		{
			if($gid!=0){ 
				$goods = sql_fetch_one("select * from sys_goods where uid=$uid and gid=$gid");
				if(empty($goods) || $goods['count']<=0)
				{
					throw new Exception($GLOBALS['equipment']['no_embed_pearl']);
				}
			}
		}
		sql_query("update sys_user_armor set embed_pearls='$pearls' where sid=$sid and uid=$uid");
		
		$old_ary = explode(",", $old_pearls);
		for($i=0; $i<count($ary); $i++)
		{
			if($ary[$i]!=$old_ary[$i]){ 
				$gid = $ary[$i];
				sql_query("update sys_goods set `count`=`count`-1 where uid=$uid and gid=$gid");
			}
		}
	}
	$ret = array();
	$ret[] = 0;
	return $ret;
}
//**************************镶嵌*************************************************//

//****************************合成材料****************************************************
//材料合成
function synthStuff($uid, $param)
{
	$targe_gid = array_shift($param);
	$count = array_shift($param);
	$dj = array_shift($param); //是否使用道具0, 1, gid=202
	$synth_count = $count; //最终合成个数
	
	if( $targe_gid==205 || ($targe_gid%10==0) ) //从基础材料到镶嵌宝石或者强化宝石，成功率为100%
	{
	}
	else
	{
		$prop =  rand(80, 100); //得到80%概率值
		if($dj == 1){ //使用道具
			$level = $targe_gid%10 + 1; //目标等级 
			$limit = pow(3, level-1);
			if($count <= $limit)
				$prop = 100;
			else{
				$synth_count = $limit + intval(($count-$limit)*$prop/100);
			}
		}
		else{
			$synth_count = intval($count*$prop/100);
		}
	}
	
	$recipe = sql_fetch_one_cell("select recipe from cfg_recipe where gid=$targe_gid");
		$tmp_ary = explode(",", $recipe);
		$need_gid = "";
		for($i=0; $i<count($tmp_ary); $i+=2)
		{
			if($i+1 == count($tmp_ary)-1)
				$need_gid = $need_gid."$tmp_ary[$i]";
			else
				$need_gid = $need_gid."$tmp_ary[$i], ";
		}
		
		$dict = parseStuffRecipe($recipe);
		$rows = sql_fetch_rows("select * from sys_goods where uid=$uid and gid in ($need_gid)");
		
		if(empty($rows) || count($rows)!=count($dict) ){
			throw new Exception($GLOBALS['equipment']['no_stuff']);
		}
		foreach ($rows as $stuff)
		{
			if($stuff['count'] < intval($dict["$stuff[gid]"])*$count )
			{
				throw new Exception($GLOBALS['equipment']['no_stuff']);
			}
		}
		
		//合成材料
		sql_query("insert into sys_goods(`uid`, `gid`, `count`) values($uid, $targe_gid, $synth_count) on duplicate key update `count`=`count`+$synth_count");
		foreach ($rows as $stuff)
		{
			$need = intval($dict["$stuff[gid]"])*$count;
			sql_query("update sys_goods set `count`=`count`-$need where uid=$uid and gid=$stuff[gid]");
		}
		
		$ret = array();
		$ret[] = $synth_count;
		return $ret;
	
}
		
function loadStuff($uid, $param)
{
	$gid = array_shift($param);
	$ret = array();
	$ret[] = $gid;
	if ($gid == 205 || ($gid%10==0)) //强化宝珠, 初级镶嵌宝珠
	{
		$recipe = sql_fetch_one_cell("select recipe from cfg_recipe where gid=$gid");
		
		$tmp_ary = explode(",", $recipe);
		$need_gid = "";
		
		for($i=0; $i<count($tmp_ary); $i+=2)
		{
			if($i+1 == count($tmp_ary)-1)
				$need_gid = $need_gid."$tmp_ary[$i]";
			else
				$need_gid = $need_gid."$tmp_ary[$i], ";
		}
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in ($need_gid) order by a.gid");
		//$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (209, 210, 211) order by a.gid");
		$ret[] = $recipe;
	}
	/*
	else if($gid==300) //1统率,  32 | 琉璃,  34 | 玛瑙  ,   38 | 夜明珠 
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (32, 34, 38) order by a.gid");
	}
	else if($gid==310) //1级内政宝珠  30 | 珍珠	35 |水晶		36|翡翠
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (30, 35, 36) order by a.gid");
	}
	else if($gid==320) //1级勇武镶嵌宝石 	 31 | 珊瑚 	33|琥珀		37|玉石
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (31, 33, 37) order by a.gid");
	}
	else if($gid==330) //1级智谋镶嵌宝石   32 | 琉璃	34| 玛瑙	  38 |夜明珠
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (32, 34, 38) order by a.gid");
	}
	else if($gid==340) //1级体力镶嵌宝石  珊瑚	1	水晶	1	翡翠
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (31, 35, 36) order by a.gid");
	}
	else if($gid==350)//1级精力镶嵌宝石 珍珠	1	琥珀	1	玉石
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (30, 33, 37) order by a.gid");
	}
	else if($gid==360) //1级攻击镶嵌宝石 珊瑚	1	玛瑙	1	玉石	1
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (31, 34, 37)  order by a.gid");
	}
	else if($gid==370) // 琥珀	1	玛瑙	1	水晶
	{
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in (33, 34, 35) order by a.gid");
	}*/
	else
	{
		$tmp = $gid - 1;
		$ret[] = sql_fetch_rows("select *,(select `count`  from sys_goods where uid=$uid and gid = a.gid) as `count`  from cfg_goods  a where a.gid in ($tmp) and `group`=4 order by a.gid");
		$ret[] = "$tmp,3"; //配方
	}
	return $ret; 
}

//*****************************合成材料*************************************************//

//------------------------------------基础函数
function isSucc($min, $max)
{
	return probability($min, $max);
}
/**
 * 
 * @param $value 100为基数
 * @return unknown_type
 */
function probability($min, $max)
{
	$ret = rand(1, 100);
	return ($min<=$ret && $ret>=$max);
}

function fitPValue($pvalue, $min, $max)
{
	return ($min<=$pvaluet && $pvalue>=$max);
}

function pValue()
{
	return rand(1, 100);
}

function makeExp()
{
	$numargs = func_num_args();
	$arg_list = func_get_args();
	$ret = "";
	for ($i = 0; $i < $numargs; $i++) {
		if ($i!=$numargs-1)
        	$ret = $ret."$arg_list[$i],";
        else
        	$ret = $ret."$arg_list[$i]";
    }
    return $ret;
	
}

/**
 * 分解打孔
 * @param $holes
 * @return unknown_type
 */
function breakUpEmbedHoles($holes)
{
	$ary = explode(",", $holes);
	return $ary;
}
/**
 * 组装打孔
 * @param $ary
 * @param $rep_pos
 * @param $value
 * @return unknown_type
 */
function assembleEmbedHoles($ary, $rep_pos, $value)
{
	$ary[$rep_pos] = $value;
	$ret = "";
	for($i=0;$i<count($ary); $i++)
	{
		if($i==count($ary)-1)
			$ret = $ret."$ary[$i]";
		else
			$ret = $ret."$ary[$i],";
	}
	return $ret;
}

function parseStuffRecipe($recipe)
{
	$ary = explode(",", $recipe);
	$dict = array();
	for($i=0; $i<count($ary); $i+=2)
	{
		$dict["$ary[$i]"] = $ary[$i+1];
	}
	return $dict;
}
?>