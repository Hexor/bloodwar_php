<?php
require_once("interface.php");
require_once("utils.php");
                      
require_once("DefenceFunc.php");
require_once("SoldierFunc.php");
require_once("TechnicFunc.php");
require_once("MarketFunc.php");
require_once("MailFunc.php");
require_once("WorldFunc.php");
require_once("GroundFunc.php");
require_once("BuildingFunc.php");  
require_once("UserFunc.php");
require_once("CityFunc.php");
require_once("ReportFunc.php");
require_once("TaskFunc.php");
require_once("UnionFunc.php");
require_once("ShopFunc.php");
require_once("RankFunc.php");
require_once("TrickFunc.php");
require_once("BufferFunc.php");
require_once("HeroFunc.php");
require_once("TroopFunc.php");
require_once("ArmorFunc.php");
    
class Command
{
	function sendCommand($param)
	{
        $ret = array(0=>1);
        $uid = array_shift($param);
        $sid = array_shift($param);  
        $type = array_shift($param);
        $ret[] = $type; 
        $cid = 0;    
		try
		{              
            
            
            checkUserAuth($uid,$sid);     
                                                                                          
			if ($type == 1)	//dialog type has receiver
			{
                $cid = array_shift($param);
				$receiverID = array_shift($param);
				$receiverParam = array_shift($param);
                $ttype = array_shift($param);
				$ret[] = $cid;
				$ret[] = $receiverID;
				$ret[] = $receiverParam;
                $ret[] = $ttype;
                checkCityOwner($cid,$uid);
                $commandFunc = array_shift($param);
                if (function_exists($commandFunc))
                {
                    $ret[] = $commandFunc($uid,$cid,$param);
                }
                else
                {
                    throw new Exception($commandFunc.$GLOBALS['sendCommand']['command_not_found']);
                }
			}
/*			else if ($type == 0) //city type
			{
                $cid = array_shift($param);
				$ret[] = $cid;
				$ret_type = array_shift($param);
				$ret[] = $ret_type;
			}
*/
            else if ($type == 2)    //global
            {
                $ret[] = array_shift($param);
                $commandFunc = array_shift($param);
                $ret[] = $commandFunc;
                    
                if (function_exists($commandFunc))
                {
                    $ret[] = $commandFunc($uid,$param);
                }
                else
                {
                    throw new Exception($commandFunc.$GLOBALS['sendCommand']['command_not_found']);
                }
            }
//			sql_query("insert into dbg_command (`uid`,`count`) values ('$uid',1) on duplicate key update `count`=`count`+1");
			return $ret;
		}
		catch(Exception $e)
		{
			$ret = array(0=>0);
			$ret[] = $e->getMessage();
            unlockUser($uid);
			return $ret;
		}
	}
}          
/*                                          
$test = new Command();
$param = array();
$param[] = 181;	       
$param[] = 30304;           
$param[] = 2;           
$param[] = "user";      
$param[] = "useGoods";
$param[] = "16";                              
$test->sendCommand($param);

printf("forget to delete Command.php's test code");
      */
           
       
 
?>