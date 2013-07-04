<?php

	if (!defined("MANAGE_INTERFACE")) exit;

	$ret['armors'] = sql_fetch_rows("select `name` from cfg_armor");
    $ret['things'] = sql_fetch_rows("select `name` from cfg_things");
    $ret['goods'] = sql_fetch_rows("select `name` from cfg_goods"); 
?>