<?php
	//���߷�ֵ����ͳ��
	//�����б�
	//startday:��ʼ����
	//endday:��������
	//����
	//array[0]:array{day,online}
	if (!defined("MANAGE_INTERFACE")) exit;

	if (!isset($startday)){exit("param_not_exist");}
	if (!isset($endday)){exit("param_not_exist");}
	
	$rows = sql_fetch_rows("select from_unixtime(time-(time+8*3600)%86400,'%Y-%m-%d') as day,max(online) as online from log_online where time >= unix_timestamp($startday) and time < unix_timestamp($endday)+86400 group by (time-(time+8*3600)%86400)","bloodwarlog");
	$ret[] = $rows;
?>