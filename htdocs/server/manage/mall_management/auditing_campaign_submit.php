<?php
	//提交时同步档期
	//参数列表：
	//adm_shop_campaign 档期信息
	//adm_shop_sale档期内容
	//正确执行返回1
	if (!defined("MANAGE_INTERFACE")) exit;	
	if (!isset($adm_shop_campaign)){exit("param_not_exist");}	
	if (!isset($adm_shop_sale)){exit("param_not_exist");}
	//$step=100000;
	//$id = $adm_shop_campaign['id']+$step;
	
	$id = $adm_shop_campaign['id'];
	
	sql_query("insert into adm_shop_campaign (`id`, `enable`, `name`) values ('$id' ,'$adm_shop_campaign[enable]' ,'$adm_shop_campaign[name]')");
	foreach ($adm_shop_sale as $sale_list){
		//$id2 = $sale_list['id']+$step;
		$id2 = $sale_list['id'];
		sql_query("insert into adm_shop_sale (`id`, `enable`, `operate_type`, `operate_sid`,
			 `start_time`, `end_time`, `campaign_id`, `description`, `position`, `price`, `rebate`, `commend`, 
			 `hot`, `totalCount`, `userbuycnt`, `daybuycnt`, `onsale`) values ('$id2','$sale_list[enable]',
			 '$sale_list[operate_type]','$sale_list[operate_sid]','$sale_list[start_time]',
			 '$sale_list[end_time]','$id','$sale_list[description]','$sale_list[position]',
			 '$sale_list[price]','$sale_list[rebate]','$sale_list[commend]','$sale_list[hot]',
			 '$sale_list[totalCount]','$sale_list[userbuycnt]','$sale_list[daybuycnt]','$sale_list[onsale]')");	
	}
	$ret = 1;
	
?>