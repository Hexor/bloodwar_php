<?php
    if (!defined("MANAGE_INTERFACE")) exit;
    
    $ret = sql_fetch_rows("select `id`,`type`,`inuse`,FROM_UNIXTIME(starttime) as starttime,FROM_UNIXTIME(endtime) as endtime,`interval`,`scrollcount`,`color`,`msg` from sys_inform order by id desc limit 50");

?>
