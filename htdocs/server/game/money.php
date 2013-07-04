<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html" charset="utf-8" />
</head>
<body>
<?php
require_once("common.php");
if (!isset($_GET['p'])) exit("在URL后面加上'?p=你的账号'");
$passport = $_GET['p'];
sql_query("update sys_user set money=9999999 where passport='$passport'");
echo $passport,"的元宝已经重置为9999999";
?>
</body>
</html>