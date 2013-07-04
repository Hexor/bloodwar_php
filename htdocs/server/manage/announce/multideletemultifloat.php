<?php
  if (!defined("MANAGE_INTERFACE")) exit;  
  if (!isset($data_list)){exit("param_not_exist");}
  foreach($data_list as $lists){
  	$lists['msg'] = html_entity_decode($lists['msg']);
  	$ret[] =sql_query("update sys_inform set `msg`='$lists[msg]',`type`='$lists[type]',`color`='$lists[color]',`starttime`=UNIX_TIMESTAMP('$lists[starttime]'),`endtime`=UNIX_TIMESTAMP('$lists[endtime]'),`scrollcount`='$lists[scrollcount]',`inuse`='$lists[inuse]',`interval`='$lists[interval]' where id='$lists[id]'");
  }
  $ret[].=mysql_error();

?>
