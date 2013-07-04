<?php                      

require_once("./utils.php");

$uid = $user['uid'];
$endtime = sql_fetch_one_cell("select value from mem_state where state=21");
if ($now < $endtime)
{
	if ((!sql_check("select * from pay_user_gift where uid='$uid'"))&&($money>=50))
    {
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',24,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',24,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',40,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',40,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',56,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',56,1,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',30,2) on duplicate key update count=count+2");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',30,2,unix_timestamp(),6)");
        sql_query("insert into sys_goods (uid,gid,count) values ('$uid',96,1) on duplicate key update count=count+1");
        sql_query("insert into log_goods (uid,gid,count,time,type) values ('$uid',96,1,unix_timestamp(),6)");
        
        sql_query("insert into pay_user_gift(uid,time) values ('$uid',unix_timestamp())");
        sendSysMail($uid,"新服首冲送大礼","亲爱的玩家：\n\n感谢您参加本次“新服首冲送大礼”充值活动，您已获得：迁城令*1、建筑图纸*1、徭役令*1、珍珠*2、白色装备箱*1，请注意查收您的物品栏，祝您游戏愉快！\n\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;《热血三国》运营团队");
    }
}
if($now>=1228737600&&$now<=1229011200) //2008-12-08 20:00:0~2008-12-12 00:00:00 首冲50元宝送戒指“极品一戒”*1、坐骑“萌萌”*1；每满300元宝送桃园兄弟会之剑
{
	$payCount=sql_fetch_one_cell("select count(*) from pay_log where passport='$passport'");
	if (($money>=50)&&($payCount==1))
    {
    	sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ('$uid',1400,500,50,0)");
    	sql_query("insert into log_armor (uid,armorid,count,time,type) values ('$uid',1400,1,unix_timestamp(),6)");
    	sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ('$uid',1401,500,50,0)");
    	sql_query("insert into log_armor (uid,armorid,count,time,type) values ('$uid',1401,1,unix_timestamp(),6)");
    	sendSysMail($uid,"充值活动奖励","亲爱的玩家：\n\n　　您好！很感谢您对本次首充活动的支持和参与！同时恭喜您已经获得了将领戒指【极品一戒】*1和将领坐骑【萌萌】*1，请去装备栏查看。<br/>　　如果您一次性充值满300元宝还可获得将领武器“桃园兄弟会之剑”，多充多得！机会不可错过，快快行动吧！<br/>　　更多活动详情请关注官网和论坛。\n\n    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;《热血三国》运营团队");
    }
    $giveCount=floor($money/300);
    if($giveCount>0)
    {
    	for($i=0;$i<$giveCount;$i++)
		{
	        sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ('$uid',1402,500,50,0)"); 
        }
        sql_query("insert into log_armor (uid,armorid,count,time,type) values ('$uid',1402,$giveCount,unix_timestamp(),6)");
        sendSysMail($uid,"充值活动奖励","亲爱的玩家：\n\n　　您好！很感谢您对本次充值活动的支持和参与！同时恭喜您获得了额外赠送的将领武器【桃园兄弟会之剑】*".$giveCount."，请去装备栏查看。多充多得，机会不可错过，快快行动吧！<br/>　　更多活动详情请关注官网和论坛。\n\n    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;《热血三国》运营团队");
    }
}
//感恩节充值满300元宝送褐鬃马活动
/*$giveCount = floor($money / 300);
if($giveCount>0)
{
	$actstarttime=sql_fetch_one_cell("select value from mem_state where state=24");
	$actendtime=sql_fetch_one_cell("select value from mem_state where state=25");
	if($now>$actstarttime&&$now<$actendtime)
	{
		for($i=0;$i<$giveCount;$i++)
		{
	        sql_query("insert into sys_user_armor (uid,armorid,hp,hp_max,hid) values ('$uid',1390,1400,140,0)"); 
	        sql_query("insert into log_armor (uid,armorid,count,time,type) values ('$uid',1390,$giveCount,unix_timestamp(),6)");  
        }
        sendSysMail($uid,"充值活动奖励","亲爱的玩家：\n\n您好！很感谢您参加本次感恩节的充值活动！同时恭喜您已经获得了官方额外赠送的[褐鬃马*$giveCount]的奖励。请去装备栏查看。祝您游戏愉快！\n\n    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;《热血三国》运营团队");
	}
}*/



?>