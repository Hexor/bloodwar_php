<?php
	
define("db_host","127.0.0.1");
define("db_user","root");
define("db_password","feijie");

mysql_connect(db_host, db_user,db_password) or die("Could not connect: " . mysql_error());

function sql_selectdb($dbname)
{
	mysql_select_db($dbname);
	mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
}

function sql_query($sql)
{
	//global $outsql;
	//$outsql=$outsql.$sql."\n";
	mysql_query($sql);
}
function sql_fetch_one($sql)
{
	$r = mysql_query($sql);
	if ((!empty($r))&&($row = mysql_fetch_array($r,MYSQL_ASSOC))) {
		return $row;
	}
	return 0;
}

function sql_fetch_one_cell($sql)
{
	$r = mysql_query($sql);
	if ((!empty($r))&&($row = mysql_fetch_array($r,MYSQL_NUM))) {
		return $row[0];
	}
	return 0;
}
function sql_fetch_rows($sql)
{
	$r = mysql_query($sql);
	$ret = array();
	if (!empty($r))
	{
		while($row = mysql_fetch_array($r,MYSQL_ASSOC)) {
			$ret[] = $row;
		}
	}
	return $ret;
}

?>