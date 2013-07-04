<?php                      
require_once("./interface.php");
require_once("./utils.php");

define("RANK_PAGE_CPP",10);

function getRankTableName($type)
{
	if($type=="user")
	{
		$tablename="rank_user";
	}
	else if($type=="union")
	{
		$tablename="rank_union";
	}
	else if($type=="hero_level")
	{
		$tablename="rank_hero";
	}
	else if($type=="hero_affairs")
	{
		$tablename="rank_hero_affairs";
	}
	else if($type=="hero_bravery")
	{
		$tablename="rank_hero_bravery";
	}
	else if($type=="hero_wisdom")
	{
		$tablename="rank_hero_wisdom";
	}
	else if ($type=="city_people")
	{
		$tablename="rank_city";
	}
	else if($type=="city_type")
	{
		$tablename="rank_city_type";
	}
	else if ($type=="jungong")
	{
		$tablename="rank_jungong";
	}
	else if ($type=="juanxian")
	{
		$tablename="rank_juanxian";
	}
	else if ($type=="qinwang")
	{
		$tablename="rank_qinwang";
	}
	else if ($type=="gongpin")
	{
		$tablename="rank_gongpin";
	}
	else if ($type=="jungong_union")
	{
		$tablename="rank_jungong_union";
	}
	else if ($type=="juanxian_union")
	{
		$tablename="rank_juanxian_union";
	}
	else if ($type=="qinwang_union")
	{
		$tablename="rank_qinwang_union";
	}
	else if ($type=="gongpin_union")
	{
		$tablename="rank_gongpin_union";
	}
	return $tablename;
}

function getRank($start,$type)
{
	$mem_state=10;
	$tablename=getRankTableName($type);
	
	$updateTime=sql_fetch_one_cell("select `value` from `mem_state` where `state`='$mem_state'");
	$rowCount=sql_fetch_one_cell("select count(*) from `$tablename`");
	$pageCount=ceil($rowCount/RANK_PAGE_CPP);
	$page=floor(($start+RANK_PAGE_CPP-1)/RANK_PAGE_CPP);
	if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($rowCount<=0)
    {
    	$page=0;
    	$pageCount=0;
    }
    $ret = array();
    $ret[]=$updateTime;
    if($rowCount>0&&$start<$rowCount)
    {
	    $ret[]=$pageCount;
	    $ret[]=$page;
		$ret[]=$type;
    	$ret[]=sql_fetch_rows("select * from `$tablename` where `rank`>'$start' limit ".RANK_PAGE_CPP);
    }
    else
    {
    	$ret[]=0;
    	$ret[]=0;
		$ret[]=$type;
    	$ret[]=array();
    }
	return $ret;
}

function getPageRank($uid,$param)
{
	$page=array_shift($param);
	$type=array_shift($param);
	return getRank($page*RANK_PAGE_CPP,$type);
}

function getRankRank($uid,$param)
{
	$rank=array_shift($param)-1;
	$rank=floor(($rank/RANK_PAGE_CPP))*RANK_PAGE_CPP;
	$type=array_shift($param);
	return getRank($rank,$type);
}

function getNameRank($uid,$param)
{
	$name=array_shift($param);
	$name=addslashes($name);
	$type=array_shift($param);
	$mem_state=10;
	$tablename=getRankTableName($type);
	$rankArray=sql_fetch_rows("select * from `$tablename` where `name` like '%$name%' order by rand() limit ".RANK_PAGE_CPP);
	$updateTime=sql_fetch_one_cell("select `value` from `mem_state` where `state`='$mem_state'");
	$ret=array();
	$ret[]=$updateTime;
	$ret[]=1;
	$ret[]=0;
	$ret[]=$type;
	$ret[]=$rankArray;
	return $ret;
}

function getMyRank($uid,$param)
{
	$name=array_shift($param);
	$name=addslashes($name);
	$type=array_shift($param);
	$tablename=getRankTableName($type);
	$rank=sql_fetch_one_cell("select `rank` from `$tablename` where `name`='$name'");
	if($rank<=0)
	{
		$rank=1;
	}
	$rank=floor((($rank-1)/RANK_PAGE_CPP))*RANK_PAGE_CPP;
	return getRank($rank,$type);
}

function getHuangJinProgress($uid,$param)
{
	$type=intval(array_shift($param));
	
	if($type==0) //¾ü¹¦²á
	{
		return sql_fetch_rows("select tid,name,maxvalue,curvalue,maxvalue-curvalue as remainvalue from huangjin_progress where `group`=11000 order by tid");
	}
	else if($type==1) //¾èÏ×±¡
	{
		return sql_fetch_rows("select tid,name,maxvalue,curvalue,maxvalue-curvalue as remainvalue from huangjin_progress where `group`=12000 order by tid");
	}
	else if($type==2) //ÇÚÍõÚ¯
	{
		return sql_fetch_rows("select tid,name,maxvalue,curvalue,maxvalue-curvalue as remainvalue from huangjin_progress where `group`=13000 order by tid");
	}
	else if($type==3) //¹±Æ·Â¼
	{
		return sql_fetch_rows("select tid,name,maxvalue,curvalue,maxvalue-curvalue as remainvalue from huangjin_progress where `group`=14000 order by tid");
	}
}

?>