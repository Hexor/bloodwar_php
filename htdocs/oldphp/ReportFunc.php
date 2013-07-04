<?php                      
require_once("./interface.php");
require_once("./utils.php");
define("REPORT_PAGE_CPP",10);

function getUnreadReport($uid,$param)
{
	$type=array_shift($param);
    $page = array_shift($param);
    sql_query("update sys_alarm set report=0 where uid='$uid'");
    $ret = array();
    $reportCount = sql_fetch_one_cell("select count(*) from sys_report where uid='$uid' and `read`=0");
    $pageCount=ceil($reportCount/REPORT_PAGE_CPP);
    if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    }
    $ret[]=$pageCount;
    $ret[]=$page;
    if($reportCount>0)
    {
    	$pagestart = $page * REPORT_PAGE_CPP;
    	$ret[] = sql_fetch_rows("select id,origincid,origincity,happencid,happencity,title,type,time,`read`,battleid from sys_report where uid='$uid' and `read`=0 order by `id` desc limit $pagestart,".REPORT_PAGE_CPP);
    }
    else 
    {
    	$ret[]=array();
    }
    return $ret;
}

function getReport($uid,$param)
{
	if($param[0]<0) return getUnreadReport($uid,$param);
	$type=array_shift($param);
    $page = array_shift($param);
    $ret = array();
    $reportCount = sql_fetch_one_cell("select count(*) from sys_report where uid='$uid' and type='$type'");
    $pageCount=ceil($reportCount/REPORT_PAGE_CPP);
    if($page>=$pageCount)
    {
    	$page=$pageCount-1;
    }
    if($page<0)
    {
    	$page=0;
    }
    $ret[]=$pageCount;
    $ret[]=$page;
    if($reportCount>0)
    {
    	$pagestart = $page * REPORT_PAGE_CPP;
    	$ret[] = sql_fetch_rows("select id,origincid,origincity,happencid,happencity,title,type,time,`read`,battleid from sys_report where uid='$uid' and type='$type' order by `id` desc limit $pagestart,".REPORT_PAGE_CPP);
    }
    else 
    {
    	$ret[]=array();
    }
    return $ret;
}

function delReport($uid,$param)
{
    $ids = array_shift($param);
    if (count($ids) > 0)
    {
        $idsarray = implode(",",$ids);
        sql_query("delete from sys_report where id in ($idsarray) and uid='$uid'");
    }
    return getReport($uid,$param);
}         
function getReportDetail($uid,$param)
{
    $battleid = array_shift($param);
    $ret = array();
    if ($battleid>0)
    {
        $ret[] = sql_fetch_rows("select * from sys_battle_report where battleid=".$battleid." order by round");
    }
    return $ret;
}
?>