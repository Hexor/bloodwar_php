<?php
	//ÿ��ע������ͳ��
	//�����б�
	//day_start:��ʼ����
	//day_end:��������
	//����
	//array[0]:array{day,count}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($day_start)){exit("param_not_exist");}
	if (!isset($day_end)){exit("param_not_exist");}
	
	$ret = sql_fetch_rows("select from_unixtime((regtime-(regtime+8*3600)%86400),'%Y-%m-%d') as day,count(*) as count from sys_user where uid > 1000 and regtime>=UNIX_TIMESTAMP('$day_start') and regtime<UNIX_TIMESTAMP('$day_end') group by (regtime-(regtime+8*3600)%86400)"); 
?>