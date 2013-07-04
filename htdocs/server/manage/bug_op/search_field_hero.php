<?php
//查询在野名将不存在，用于解决市井传闻查到武将在某个野地，占领该野地却发现武将不存在的问题
//参数列表：武将名
//返回
//array[]:

if (!defined("MANAGE_INTERFACE")) exit;
if (!isset($heroname)) {exit("param_not_exist");}
$ret = sql_fetch_one("select hid,uid from sys_city_hero where name='$heroname'");


?>