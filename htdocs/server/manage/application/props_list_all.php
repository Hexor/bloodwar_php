<?php

	if (!defined("MANAGE_INTERFACE")) exit;

	$ret = sql_fetch_rows("select * from cfg_goods order by gid asc");
?>