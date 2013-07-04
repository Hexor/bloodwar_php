<?php
	require_once("db.php");
	$visit_ip_list = array ('0'=>'122.224.130.117','1'=>'122.225.30.91','2'=>'122.225.28.27');
	$ret = '';
	try{

		if (!in_array($_SERVER["REMOTE_ADDR"],$visit_ip_list))
		{
			throw new Exception('ip_error,ip='.$_SERVER["REMOTE_ADDR"]);
		}
		
		@array_walk($_GET,'addslashes');
		@array_walk($_POST,'addslashes');

		if (empty($_GET['action']))
		{
			throw new Exception('action_error');
		}
		
		
		$signstr = $_GET['timeid'];
		if ((!isset($_GET['sign'])) || (md5($signstr.'bioasdim32lkasmb') != $_GET['sign']))
		{
			throw new Exception('sign_error');
		}
		
	
		$action_path = "platform/".$_GET['action'].".php";
		if (file_exists($action_path))
		{
			@extract($_GET);
			@extract($_POST);
			include($action_path);
		}
		else
		{
			throw new Exception('action_not_exist');
		}
	}
	catch(Exception $e)
	{
		echo $e->getMessage();
		exit;
	}
	echo "00$".implode("|",$ret);
?>