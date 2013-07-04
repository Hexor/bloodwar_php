<?php
require_once("./utils.php");
require_once './TaskFunc.php';

$act_paygifts = sql_fetch_rows("select * from cfg_act a join cfg_act_paygift b on a.actid = b.actid where type = 2 and $now>=starttime and $now <=endtime");
foreach ($act_paygifts as $act_paygift) {
	$money_limit = intval($act_paygift["money_limit"]);
	$daycnt = intval($act_paygift["daycnt"]);
	$actid = $act_paygift["actid"];	
	
	/*$firstpay=false;
	//act6.9-6.12 BEGIN 活动首充
	if($actid==17 && $money>=50){
		$starttime=$act_paygift['starttime'];
		$endtime=$act_paygift['endtime'];
		$count=sql_fetch_one_cell("select count(1) from pay_log p left join sys_user u on p.passport=u.passport where uid='$uid' and (time between '$starttime' and '$endtime')");
		if($count==1) $firstpay=true;
		if($firstpay){
			giveArmor($uid,1415,1);
		}
	}
	//END
	*/
	if ($money>=$money_limit){
		$giveCount=floor($money/$money_limit);
	    if($giveCount>0)
	    {
	    	$boxdetails=sql_fetch_rows("select * from cfg_box_details where srctype=1 and srcid='$actid'");
	    	if (!$boxdetails) continue;
	    	foreach($boxdetails as $boxdetail){
		    	$sort = $boxdetail["sort"];
		    	$type = $boxdetail["type"];
		    	$todayHaveGiveCount=0;
		    	if ($sort=="2") 
		    		$todayHaveGiveCount=sql_fetch_one_cell("select sum(count) from log_goods where uid = $uid and type=6 and  gid = $type and  curdate()=date(from_unixtime(time))");
		    	else if ($sort=="5")
		    		$todayHaveGiveCount=sql_fetch_one_cell("select sum(count) from log_things where uid = $uid and type=6 and  tid = $type and  curdate()=date(from_unixtime(time))");
				else if ($sort=="6")
		    		$todayHaveGiveCount=sql_fetch_one_cell("select sum(count) from log_armor where uid = $uid and type=6 and  armorid = $type and  curdate()=date(from_unixtime(time))");
		    	if ($giveCount > ($daycnt-$todayHaveGiveCount)) $giveCount=$daycnt-$todayHaveGiveCount;
		    	if ($giveCount<=0)    $giveCount=0;
		    	else{ 		
			    	$boxdetail["count"]=$giveCount*$boxdetail["count"];
			    	giveReward($uid,$cid,$boxdetail,6,20,true);
		    	}  
	    	}
	    	if ($giveCount>0){
			    $mailcontent = $act_paygift["mailcontent"];
			    $mailtitle = $act_paygift["mailtitle"];
			    $mailcontent=str_replace("{giveCount}",$giveCount,$mailcontent); 
			    sendSysMail($uid,$mailtitle,$mailcontent);
	    	}
	    }
	}
}


?>