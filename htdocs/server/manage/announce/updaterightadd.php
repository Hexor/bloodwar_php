<?php
    if (!defined("MANAGE_INTERFACE")) exit;

    if (!isset($data_list)){exit("param_not_exist");}

    foreach($data_list as &$lists)
    {
       sql_query("update sys_activity set `inuse`='$lists[inuse]',`content`='$lists[content]',`link`='$lists[link]',`interval`='$lists[interval]' where id='$lists[id]'");
    }
    
?>
