<?
// 设置一些基本的变量
$host="127.0.0.1";
$port="5038";
// 设置超时时间
set_time_limit(0);
// 创建一个Socket
$socket=socket_create(AF_INET,SOCK_STREAM,0) or die("Could not create
socket\n");
//绑定Socket到端口
$result=socket_bind($socket,$host,$port) or die("Could not bind to
socket\n");
// 开始监听链接
$result=socket_listen($socket,3) or die("Could not set up socket
listener\n");
// accept incoming connections
// 另一个Socket来处理通信
$spawn=socket_accept($socket) or die("Could not accept incoming
connection\n");
// 获得客户端的输入
$input=socket_read($spawn,1024) or die("Could not read input\n");
// 清空输入字符串
$input=trim($input);
//处理客户端输入并返回结果
$output=strrev($input) ."\n";
socket_write($spawn,$output,strlen($output)) or die("Could not write
output\n");
// 关闭sockets
socket_close($spawn);
socket_close($socket);
?>