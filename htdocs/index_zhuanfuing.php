<?php

if(isset($_POST['passport']) && isset($_POST['password'])){
	$info = "";
	$key = "bfd51210-0fd3-424b-af94-557df4877239";
	$key_2 = 'sdfh56HFGjklho';
	$url = "http://wif.12ha.com/webuserchecklogin/webgame_rxsg_userchecklogin_get.aspx?";
	$passport = $_POST['passport'];
	$password = $_POST['password'];
	
	$url .= "pusername=".$passport."&ppsw=".md5($password)."&ptype=wg&sign=".md5($passport.md5($password)."wg".$key);
	$ret = file_get_contents($url);
	
	if ($ret == "1"){
		$tnd = time();
		$sign = md5($passport.$password.'jinku_1'.$tnd.$key_2);
		$redirect_url = "http://60.191.21.182/smerge/index.php?passport=".$passport."&union=jinku_1&password=".$password."&tnd=".$tnd."&sign=".$sign;
		header("location:".$redirect_url);
		//echo "<script type=\"text/javascript\">\nwindow.location.href = \"$redirect_url\";\n</script>";
	}else{
		$info = "用户名密码错误";		
	}
}
include("index2.html");
?>