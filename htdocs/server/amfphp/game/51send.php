<?php

require_once("51SDK/appinclude.php");
require_once("./interface.php");
$money= $_GET['amount'];
if(empty($money)){
	exit("1");
}

if($money<=0){
	exit("1");
}
$uid= sql_fetch_one_cell("select  uid from sys_user where passport='$user51'");
sql_query("insert into log_51_charge (uid,passport,money,state,time) values('$uid','$user51','$money','0',unix_timestamp() ) ");
$orderid=sql_fetch_one_cell("select last_insert_id()");
if($orderid<=0){
	exit("1");
}
require_once("../config/db.php");
$orderid="0".SERVER_ID.$orderid;

$msg="充值 ".$money." 元";
//echo $msg;
$order = array(	
	'order_id' => $orderid,
	'order_price' => $money*100,
	'order_msg' => $msg,
	'order_callback_url' => PAY51_REDIRENT_URL,
	'order_check_url' => PAY51_NOTICE_URL
);
$OpenApp_51->api_client->set_encoding("GBK");
$post = $OpenApp_51->api_client->create_post_string('', $order);
?>
<html>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<body onLoad="document.forms[0].submit();">

	<form action="http://apps.51.com/paybank.php?sandbox=1" method="post" >
	    <input type="hidden" name="51_pay" value="<?php echo $post; ?>" />
	</form>
</body>
</html>