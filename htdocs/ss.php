<?
// ����һЩ�����ı���
$host="127.0.0.1";
$port="5038";
// ���ó�ʱʱ��
set_time_limit(0);
// ����һ��Socket
$socket=socket_create(AF_INET,SOCK_STREAM,0) or die("Could not create
socket\n");
//��Socket���˿�
$result=socket_bind($socket,$host,$port) or die("Could not bind to
socket\n");
// ��ʼ��������
$result=socket_listen($socket,3) or die("Could not set up socket
listener\n");
// accept incoming connections
// ��һ��Socket������ͨ��
$spawn=socket_accept($socket) or die("Could not accept incoming
connection\n");
// ��ÿͻ��˵�����
$input=socket_read($spawn,1024) or die("Could not read input\n");
// ��������ַ���
$input=trim($input);
//����ͻ������벢���ؽ��
$output=strrev($input) ."\n";
socket_write($spawn,$output,strlen($output)) or die("Could not write
output\n");
// �ر�sockets
socket_close($spawn);
socket_close($socket);
?>