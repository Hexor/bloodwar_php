<?php
	//ÿ��ע������ͳ��
	//�����б�
	//����
	//[0]:��¼ʱ��
	//[1]:��ʱ��������
	//[2]:10������������
	//[3]:30������������
	//[4]:������������
	if (!defined("MANAGE_INTERFACE")) exit;
     $ret[] = sql_fetch_one_cell("select now()");
	 $ret[] =  sql_fetch_one_cell("select count(0) AS `count(*)` from `sys_online` where ((unix_timestamp() - `lastupdate`) < 60) "); 
     $ret[] =  sql_fetch_one_cell("select count(0) AS `count(*)` from `sys_online` where ((unix_timestamp() - `lastupdate`) < 600) ");  
     $ret[] =  sql_fetch_one_cell("select count(0) AS `count(*)` from `sys_online` where ((unix_timestamp() - `lastupdate`) < 1800) ");  
     
	$unix_time = sql_fetch_one_cell("select unix_timestamp()");  
	$time_between = ($unix_time+8*3600)%86400; 
	$ret[] =  sql_fetch_one_cell("select count(0) AS `count(*)` from `sys_online` where ((unix_timestamp() - `lastupdate`) < $time_between) ");
?>