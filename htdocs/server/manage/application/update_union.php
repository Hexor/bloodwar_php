<?php

	if (!defined("MANAGE_INTERFACE")) exit;

	$ret = sql_query("update sys_union set `name`='$union_name' where `id`='$touid'");
?>