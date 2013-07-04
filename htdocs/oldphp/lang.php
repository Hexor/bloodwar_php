<?php


$GLOBALS['sys']['not_enough_money'] = "你没有足够的元宝，请充值后再操作。";
$GLOBALS['sys']['YuanBao'] = "元宝";
$GLOBALS['sys']['description_of_YuanBao'] = "元宝，用于购买商场道具，可通过充值获得。";
$GLOBALS['sys']['restart_mail_title']="东山再起";

$GLOBALS['activity']['wisdom_hero']="谋士";
$GLOBALS['activity']['affairs_hero']="政客";
$GLOBALS['activity']['bravery_hero']="副将";
$GLOBALS['activity']['hero_type_name1']="无名";
$GLOBALS['activity']['hero_type_name2']="清闲";
$GLOBALS['activity']['hero_type_name3']="安逸";
$GLOBALS['activity']['hero_type_name4']="得志";
$GLOBALS['activity']['hero_type_name5']="潜隐";
$GLOBALS['activity']['hero_count_limit']="你拥有的活动将领已经达到10名，需要解雇一名通过聚贤包获得的活动将领，才能继续打开聚贤包。";
$GLOBALS['activity']['get_hero_tip']="恭喜你获得“%s”一名，请去招贤馆查看。";

//building
$GLOBALS['doGetSimpleBuildingInfo']['noresource']        =    "没有相应的资源数据";
$GLOBALS['doGetSimpleBuildingInfo']['pre_building']    =    "前提建筑";
$GLOBALS['doGetSimpleBuildingInfo']['pre_technic']        =    "前提科技";
$GLOBALS['doGetSimpleBuildingInfo']['pre_thing']        =    "需要物品";
$GLOBALS['doGetSimpleBuildingInfo']['level']            =    "等级";
$GLOBALS['doGetSimpleBuildingInfo']['count']            =    "数量";
$GLOBALS['getBuildingInfo']['nobuilding']                =    "不存在该建筑";
$GLOBALS['getBuildingInfo']['upgrading']                =    "建筑正在升级中";
$GLOBALS['getBuildingInfo']['destroying']                =    "建筑正在拆除中";
$GLOBALS['getBuildingInfo']['building_error']            =    "建造建筑错误";
$GLOBALS['getBuildingInfo']['resource_not_enough']        =    "资源不足";
$GLOBALS['getBuildingInfo']['people_not_enough']        =    "人口不足，不能建造此建筑。";
$GLOBALS['getBuildingInfo']['no_pre_building']            =    "前提建筑没有建好。";
$GLOBALS['getBuildingInfo']['no_pre_technic']            =    "前提科技没有研究好。";
$GLOBALS['getBuildingInfo']['no_pre_thing']            =    "你没有相应的任务物品。";
$GLOBALS['getBuildingInfo']['government_not_enough']    =    "你的官府等级不足，不能在这块空地上建造。";
//$GLOBALS['getBuildingInfo']['upgrading_queue_full']    =    "等级%d的官府可以同时建造或拆除%d个建筑，现在建造列表已满，不能再建造新的建筑了。";
$GLOBALS['getBuildingInfo']['destroying_queue_full']    =    "等级%d的官府可以同时建造或拆除%d个建筑，现在建造列表已满，不能再拆除新的建筑了。";
$GLOBALS['getBuildingInfo']['govenment_1_destroy']    =    "1级官府不能拆除。";
$GLOBALS['getBuildingInfo']['govenment_all_destroy']    =    "官府不能彻底拆除。";

$GLOBALS['getBuildingInfo']['upgrading_queue_full']    =    "现在建造列表已满，不能再建造新的建筑了。";
$GLOBALS['getBuildingInfo']['upgrading_queue_full2']    =    "现在建造列表已满，不能再建造新的建筑了。使用“徭役令”可以增加建造队列。";


//city
$GLOBALS['getCityInfo']['not_your_city']    =    "当前城池已经被占领，不能进入，请重新登录一下。";
$GLOBALS['getCityInfo']['city_be_invaded'] = "当前城池已经被占领，不能进入。自动切换到其他城池。";
$GLOBALS['changeCityName']['name_too_long']    =    "城池名不能超过8个字符。";
$GLOBALS['changeCityName']['name_illegal']    =    "城池名字含有不被允许的字符。";
$GLOBALS['changeCityName']['today_changed']    =    "每天只能修改一次城池名。";
$GLOBALS['changeCityName']['change_name_to']   =    "成功修改城池名为“%s”。";
$GLOBALS['levyResource']['time_limit']   =    "%s后才能征收物资。";
$GLOBALS['changeCityName']['bigcity_norename'] = "历史名城不能改名。";    

$GLOBALS['levyResource']['gold'] = "黄金" ;
$GLOBALS['levyResource']['food'] = "粮食";
$GLOBALS['levyResource']['wood'] = "木材";
$GLOBALS['levyResource']['rock'] = "石料";
$GLOBALS['levyResource']['iron'] = "铁锭";
$GLOBALS['levyResource']['succ_levy'] = "成功征收%s,民心降20。";
$GLOBALS['levyResource']['not_enough_morale'] = "民心低于20，不能征收物资。";

$GLOBALS['pacifyPeople']['server_busy'] = "服务器忙，请稍后再进行操作。";
$GLOBALS['pacifyPeople']['wait_more_secs'] = "%s后才能安抚百姓。";
$GLOBALS['pacifyPeople']['no_enough_food'] = "本城的粮食不够。";
$GLOBALS['pacifyPeople']['no_enough_gold'] = "本城的黄金不够。";
$GLOBALS['pacifyPeople']['succ_pacify'] = "成功安抚百姓";

$GLOBALS['addPeople']['not_your_city'] = "城池不属于你";
$GLOBALS['addPeople']['succ'] = "成功招徕百姓%d人";
$GLOBALS['addPeople']['city_full'] = "当前人口已经超过城池人口上限，不能招徕更多流民了。";
$GLOBALS['addPeople']['no_goods'] = "你没有道具“典民令”，不能招徕流民，请先去商城购买。";

$GLOBALS['gatherFieldStart']['field_is_pingdi'] = "该地为平地，不能采集。";
$GLOBALS['gatherFieldStart']['field_is_city'] = "该地为城池，不能采集。";
$GLOBALS['gatherFieldStart']['field_in_battle'] = "此野地正在战乱中，不能进行采集。";
$GLOBALS['gatherFieldStart']['field_level_0'] = "0级野地不能采集。";
$GLOBALS['gatherFieldStart']['not_your_field'] = "该野地已经不属于你，不能进行采集。";
$GLOBALS['gatherFieldStart']['you_are_gathering'] = "你已经在采集中。";
$GLOBALS['gatherFieldStart']['no_army'] = "你没有在此野地的驻军，不能进行采集。";
$GLOBALS['gatherFieldStart']['no_hero'] = "在此野地的驻军没有将领，不能进行采集。";
///////////Test////////
//'珍珠','珊瑚','琉璃','琥珀','玛瑙','水晶','翡翠','玉石','夜明珠'
$GLOBALS['gatherFieldResult']['ZhenZhu'] = "珍珠";
$GLOBALS['gatherFieldResult']['ShanHu'] = "珊瑚";
$GLOBALS['gatherFieldResult']['LiuLi'] = "琉璃";
$GLOBALS['gatherFieldResult']['HuPo'] = "琥珀";
$GLOBALS['gatherFieldResult']['MaNao'] = "玛瑙";
$GLOBALS['gatherFieldResult']['ShuiJing'] = "水晶";
$GLOBALS['gatherFieldResult']['FeiCui'] = "翡翠";
$GLOBALS['gatherFieldResult']['YuShi'] = "玉石";
$GLOBALS['gatherFieldResult']['YeMingZhu'] = "夜明珠";
$GLOBALS['gatherFieldResult']['GuPuMuHe'] = "古朴木盒";
$GLOBALS['gatherFieldResult']['XiangSiDou'] = "相思豆";
$GLOBALS['gatherFieldResult']['XiangSiYuDi'] = "相思雨滴";

$GLOBALS['gatherFieldResult']['food'] = "粮食";
$GLOBALS['gatherFieldResult']['wood'] = "木材";
$GLOBALS['gatherFieldResult']['rock'] = "石料";
$GLOBALS['gatherFieldResult']['iron'] = "铁锭";

$GLOBALS['gatherFieldEnd']['field_in_battle'] = "该野地正在战乱中，不能进行收获。";
$GLOBALS['gatherFieldEnd']['not_your_field'] = "该野地不属于你，不能进行收获。";
$GLOBALS['gatherFieldEnd']['no_people_gather'] = "无人采集，没有获得任何东西。";
$GLOBALS['gatherFieldEnd']['field_level_0'] = "0级野地过于贫瘠，无法进行采集。";
$GLOBALS['gatherFieldEnd']['gather_time_lessThen_1'] = "采集时间小于1小时，没有任何收获。";
$GLOBALS['gatherFieldEnd']['through_gathering'] = "经过%s的采集，共收获";
$GLOBALS['gatherFieldEnd']['already_got'] = "你已经收获过了。";

$GLOBALS['discardField']['iron'] = "铁锭";
$GLOBALS['discardField']['food'] = "粮食";

$GLOBALS['gatherFieldResult']['not_your_field'] = "该野地已经不属于你。";
$GLOBALS['gatherFieldResult']['cant_dismiss_with_army'] = "该地有军队驻扎，不能放弃，请召回所有军队。";

$GLOBALS['callBackFieldTroop']['invalid_army'] = "无效的军队";

$GLOBALS['kickBackFieldTroop']['army_not_exist'] = "该驻军已经不存在。";

$GLOBALS['discardCity']['invalid_pwd'] = "密码错误，不能废弃城池。";
$GLOBALS['discardCity']['not_your_city'] = "该城池已经不属于你，不能进行操作！";
$GLOBALS['discardCity']['city_in_battle'] = "该城池正在战乱中，不能废弃城池。";
$GLOBALS['discardCity']['has_army_outside'] = "该城池有军队在外，不能废弃城池。";
$GLOBALS['discardCity']['has_union_army'] = "该城（或其附属野地）有其它盟友驻军，不能弃城。";
$GLOBALS['discardCity']['giveup_city'] = "你放弃了%s，该城已经不再属于你了。";


//command
$GLOBALS['sendCommand']['command_not_found'] = "不存在的请求";

               
//trick
$GLOBALS['useTrick']['caution_title'] = "计谋警报";
$GLOBALS['useTrick']['trick_not_exist'] = "该计谋不存在。";
$GLOBALS['useTrick']['no_enough_bag'] = "你的锦囊不足，不能使用该计谋。";
$GLOBALS['useTrick']['hero_not_exist'] = "该将领不存在。";
$GLOBALS['useTrick']['hero_not_incity'] = "不在城池内的将领不能使用计谋。";
$GLOBALS['useTrick']['hero_no_energy'] = "将领没有足够的精力来使用计谋了。";
$GLOBALS['useTrick']['target_is_mine'] = "不能对自己的城池使用该计谋。";
$GLOBALS['useTrick']['target_is_union'] = "不能对盟友的城池使用该计谋。";
$GLOBALS['useTrick']['target_is_not_my_troop'] = "不能对不是自己的军队使用该计谋。";
$GLOBALS['useTrick']['target_is_not_on_way'] = "军队不在行军途中，不能使用该计谋。";
$GLOBALS['useTrick']['target_has_no_hero'] = "该军队没有将领，不能使用“金蝉脱壳”";
$GLOBALS['useTrick']['target_is_just_run'] = "你刚刚对该军队使用过“金蝉脱壳”，%s后才能再次对该军队使用。";
$GLOBALS['useTrick']['target_not_coming_1'] = "该军队不是行军状态，不能使用“八门金锁”。";
$GLOBALS['useTrick']['target_not_coming_2'] = "该军队不是行军状态，不能使用“关门打狗”。";
$GLOBALS['useTrick']['fail_no_wisdom'] = "用计失败。一山还有一山高，对方智高一筹，轻易识破了我方的计谋。";
$GLOBALS['useTrick']['target_in_vacation'] = "对方处在休假状态，不能对其城池使用计谋。";
$GLOBALS['useTrick']['target_be_locked'] = "对方已经被锁定，不能对其城池使用计谋。";



$GLOBALS['trickCaoMuJieBin']['succ'] = "“草木皆兵”用计成功。该城池被侦察时将显示军队人数为真实的%d倍，效果将持续到%s。";
$GLOBALS['trickKongCheng']['succ'] = "“空城计”用计成功。该城池被侦察时将显示军队人数为真实的%d%%，效果将持续到%s。";
$GLOBALS['trickPaoZhuangYingYu']['succ'] = "“抛砖引玉”用计成功。该城池被侦察时将显示资源数为真实的%d倍，效果将持续到%s。";
$GLOBALS['trickJinBiQingYe']['succ'] = "“坚壁清野”用计成功。该城池被侦察时将显示资源数为真实的%d%%，效果将持续到%s。";

$GLOBALS['trickAnDuChenChang']['succ'] = "“暗渡陈仓”用计成功。你现在可以打破敌人封锁，从被敌人围困的城池内调动军队出城，效果将持续到%s。";

$GLOBALS['trickYaoYinHuoZhong']['succ'] = "“妖言惑众”用计成功。\n%s民心-%d。";
$GLOBALS['trickYaoYinHuoZhong']['succ_caution'] = "%s对%s使用“妖言惑众”！<br/>智者千虑必有一失，我方中计了！<br/>%s民心-%d。<br/>赈灾、祈福可以恢复民心。";

$GLOBALS['trickYaoYinHuoZhong']['fail'] = "“妖言惑众”用计失败。\n吃一堑长一智，对方刚刚中过同样的计策，不会重复上当了。";
$GLOBALS['trickYaoYinHuoZhong']['fail_caution'] = "%s对%s使用“妖言惑众”！<br/>雕虫小技也敢献丑，我方识破了计谋。";

$GLOBALS['trickTiaoBoLiJian']['fail'] = "“挑拨离间”用计失败。\n吃一堑长一智，对方将领刚刚中过同样的计策，都不会重复上当了。";
$GLOBALS['trickTiaoBoLiJian']['fail_caution'] = "%s对%s使用“挑拨离间”！<br/>雕虫小技也敢献丑，我方识破了计谋。<br/>";
$GLOBALS['trickTiaoBoLiJian']['fail_nohero'] = "对方城中没有将领，白费功夫。";
$GLOBALS['trickTiaoBoLiJian']['fail_caution_nohero'] = "城中没有将领，对方“挑拨离间”失败，白费功夫。";
        
$GLOBALS['trickTiaoBoLiJian']['succ'] = "“挑拨离间”用计成功！\n";                
$GLOBALS['trickTiaoBoLiJian']['succ_reduceloyalty'] = "“%s”忠诚-%d。\n";
$GLOBALS['trickTiaoBoLiJian']['succ_surrender'] = "“%s”忠诚尽失，被劝降了。\n";
$GLOBALS['trickTiaoBoLiJian']['succ_nooffice'] = "“%s”忠诚尽失，可惜我方招贤馆位置不足，无法接纳。";

$GLOBALS['trickTiaoBoLiJian']['succ_caution'] = "%s对%s使用“挑拨离间”！<br/>智者千虑必有一失，我方中计了！<br/>";
                                                                                 
$GLOBALS['trickTiaoBoLiJian']['succ_caution_reduceloyalty'] = "“%s”忠诚-%d。<br/>";
$GLOBALS['trickTiaoBoLiJian']['succ_caution_surrender'] = "“%s”忠诚尽失，被劝降了。<br/>";
$GLOBALS['trickTiaoBoLiJian']['succ_caution_nooffice'] = "“%s”忠诚尽失。<br/>";
$GLOBALS['trickTiaoBoLiJian']['succ_caution_tail'] = "赏赐将领可以提升将领忠诚。";

$GLOBALS['trickShiMianMaiFu']['succ'] = "“十面埋伏”用计成功！\n%s%s内无法出征。";
$GLOBALS['trickShiMianMaiFu']['succ_caution'] = "%s对%s使用“十面埋伏”！<br/>智者千虑必有一失，我方中计了！<br/>%s%s内无法出征。<br/>封锁结束时间：%s。<br/>用“暗度陈仓”可以打破封锁。";

$GLOBALS['trickShiMianMaiFu']['fail'] = "“十面埋伏”用计失败。\n吃一堑长一智，对方刚刚中过同样的计策，不会重复上当了。";
$GLOBALS['trickShiMianMaiFu']['fail_caution'] = "%s对%s使用“十面埋伏”！<br/>雕虫小技也敢献丑，我方识破了计谋。";  

$GLOBALS['trickBuXuanErZhan']['succ'] = "“不宣而战”用计成功！ \n你和%s进入战争状态，战争将持续%s。";


$GLOBALS['trickBuXuanErZhan']['cool_down'] = "“不宣而战”用计失败。\n吃一堑长一智，对方刚刚中过同样的计策，不会重复上当了。";
$GLOBALS['trickBuXuanErZhan']['succ_report'] = "你对%s不宣而战！<br/>你们已经进入战争状态！<br/>战争期间双方可以互相掠夺、占领对方的城池。<br/>战争持续%s后自动结束。<br/>战争开始时间：%s。<br/>战争结束时间：%s。<br/>出师无名，你的声望降低%d。";
$GLOBALS['trickBuXuanErZhan']['succ_caution'] = "%s对你不宣而战！<br/>你们已经进入战争状态！<br/>战争期间双方可以互相掠夺、占领对方的城池。<br/>战争持续%s后自动结束。<br/>战争开始时间：%s。<br/>战争结束时间：%s。";

$GLOBALS['trickJinChaoTuoQiao']['succ'] = "“金蝉脱壳”用计成功！\n我方军队正在快速返回。";
$GLOBALS['trickJinChaoTuoQiao']['cool_down'] = "一支部队一小时内只能使用一次“金蝉脱壳”。";

$GLOBALS['startWar']['succ_report'] = "你对%s宣战。<br/>宣战8小时后正式进入战争状态。<br/>战争期间双方可以互相掠夺、占领对方的城池。<br/>战争持续48小时后自动结束。<br/>战争开始时间：%s。<br/>战争结束时间：%s。<br/>";
$GLOBALS['startWar']['succ_caution'] = "%s对你宣战。<br/>宣战8小时后正式进入战争状态。<br/>战争期间双方可以互相掠夺、占领对方的城池。<br/>战争持续48小时后自动结束。<br/>战争开始时间：%s。<br/>战争结束时间：%s。<br/>";

$GLOBALS['trickBaMemJinShuo']['fail'] = "“八门金锁”用计失败。\n吃一堑长一智，对方刚刚中过同样的计策，不会重复上当了。";   
$GLOBALS['trickBaMemJinShuo']['fail_caution'] = "%s对我方军队使用“八门金锁”！<br/>雕虫小技也敢献丑，我方识破了计谋。";    
$GLOBALS['trickBaMemJinShuo']['succ'] = "“八门金锁”用计成功！\n敌方军队行军时间延长%s。"; 
$GLOBALS['trickBaMemJinShuo']['succ_caution'] = "%s对我方军队使用“八门金锁”！<br/>智者千虑必有一失，我方中计了！<br/>我方军队行军时间延长%s。";

$GLOBALS['trickGuanMemDaGou']['fail_caution'] = "%s对我方军队使用“关门打狗”！<br/>雕虫小技也敢献丑，我方识破了计谋。";

$GLOBALS['trickGuanMemDaGou']['succ_caution'] = "%s对我方军队使用“关门打狗”！<br/>智者千虑必有一失，我方中计了！<br/>军队无法召回！<br/>用“金蝉脱壳”可以快速召回军队。";
$GLOBALS['trickGuanMemDaGou']['succ'] = "“关门打狗”用计成功！\n敌方军队无法召回！";

$GLOBALS['trickQianLiBenXi']['succ'] = "“千里奔袭”用计成功！\n我方军队行军速度加快！";
$GLOBALS['trickQianLiBenXi']['wrong_state'] = "“千里奔袭”只能对行进中的队伍使用！";
$GLOBALS['trickQianLiBenXi']['cool_down'] = "一支部队一小时内只能使用一次“千里奔袭”。";


//Bufffer
$GLOBALS['getCityBuffer']['not_your_city'] = "该城池不属于你！";


//Char
$GLOBALS['sendChatMsg']['no_enough_acoustic'] = "你没有传音符，不能在世界频道聊天。";

$GLOBALS['getChatMsg']['chatFunc_shutDown'] = "该版本聊天功能暂时关闭。";


//defence
$GLOBALS['doGetDefenceInfo']['pre_building'] = "前提建筑";
$GLOBALS['doGetDefenceInfo']['level'] = "等级";
$GLOBALS['doGetDefenceInfo']['pre_technic'] = "前提科技";

$GLOBALS['getWallInfo']['no_wall'] = "该地尚未建造城墙";

$GLOBALS['startReinforceQueue']['build_zero_defence'] = "不能建造0个城防";
$GLOBALS['startReinforceQueue']['no_defence_info'] = "没有该城防信息";
$GLOBALS['startReinforceQueue']['no_enough_resource'] = "资源不足";
$GLOBALS['startReinforceQueue']['no_free_space'] = "城墙位置已满。";
$GLOBALS['startReinforceQueue']['no_pre_building'] = "前提建筑没有建好。";
$GLOBALS['startReinforceQueue']['no_pre_technic'] = "前提科技没有研究好。";
$GLOBALS['startReinforceQueue']['queue_reach_limit'] = "城墙的建造队列已经达到上限。";

$GLOBALS['stopReinforceQueue']['no_barracks_info'] = "没有该兵营信息";
$GLOBALS['stopReinforceQueue']['no_reinforcement_info'] = "没有该城防信息";

$GLOBALS['dissolveDefence']['cant_dissolve_zero'] = "不能拆除0个城防";
$GLOBALS['dissolveDefence']['no_wall_info'] = "没有该城墙信息";
$GLOBALS['dissolveDefence']['no_reinforcement_info'] = "没有该城防信息";
$GLOBALS['dissolveDefence']['cant_dissolve_exceed'] = "不能拆除比当前城防数量还要多的城防";

$GLOBALS['accDefence']['only_onece'] = "一个城防建造队列只能加速一次。";
$GLOBALS['accDefence']['no_goods'] = "你没有道具“备城门”，请先去商城购买。";


//goods
$GLOBALS['checkGoodsCount']['server_busy'] = "服务器忙，请稍后再进行操作。";

$GLOBALS['checkGoodsArray']['server_busy'] = "服务器忙，请稍后再进行操作。";

$GLOBALS['useMenzhulin']['no_MenZhuLin'] = "你没有盟主令。";
$GLOBALS['useMenzhulin']['not_join_union'] = "你还没有加入联盟，不能使用盟主令";
$GLOBALS['useMenzhulin']['union_not_exist'] = "联盟不存在。";
$GLOBALS['useMenzhulin']['already_used'] = "联盟已经使用过“盟主令”了，无须再次使用。";

$GLOBALS['useXiShuiDan']['no_hero_info'] = "无此将领信息。";
$GLOBALS['useXiShuiDan']['no_enough_XiShuiDan'] = "你没有足够的洗髓丹,不能给该将领洗点。";

$GLOBALS['useZhaoXinLin']['no_ZhaoXinLin'] = "你没有招贤榜,不能招贤。";

$GLOBALS['UseMianZhanPai']['no_MianZhanPai'] = "你没有免战牌，不能进入免战状态";

$GLOBALS['useShenNongChu']['ShenNongChu'] = "神农锄";
$GLOBALS['useShenNongChu']['advanced_ShenNongChu'] = "高级神农锄";
$GLOBALS['useShenNongChu']['no_ShenNongChu'] = "你没有%s，不能使用。";

$GLOBALS['useLuBanFu']['LuBanFu'] = "鲁班斧";
$GLOBALS['useLuBanFu']['advanced_LuBanFu'] = "高级鲁班斧";
$GLOBALS['useLuBanFu']['no_LuBanFu'] = "你没有%s，不能使用。";

$GLOBALS['useKaiShanCui']['KaiShanCui'] = "开山锤";
$GLOBALS['useKaiShanCui']['advanced_KaiShanCui'] = "高级开山锤";
$GLOBALS['useKaiShanCui']['no_KaiShanCui'] = "你没有%s，不能使用。";

$GLOBALS['useXuanTieLu']['XuanTieLu'] = "玄铁炉";
$GLOBALS['useXuanTieLu']['advanced_XuanTieLu'] = "高级玄铁炉";
$GLOBALS['useXuanTieLu']['no_XuanTieLu'] = "你没有%s，不能使用。";

$GLOBALS['useXianZhenZhaoGu']['XianZhenZhaoGu'] = "陷阵战鼓";
$GLOBALS['useXianZhenZhaoGu']['advanced_XianZhenZhaoGu'] = "高级陷阵战鼓";
$GLOBALS['useXianZhenZhaoGu']['no_XianZhenZhaoGu'] = "你没有%s，不能使用。";

$GLOBALS['useBaGuaZhenTu']['BaGuaZhenTu'] = "八卦阵图";
$GLOBALS['useBaGuaZhenTu']['advanced_BaGuaZhenTu'] = "高级八卦阵图";
$GLOBALS['useBaGuaZhenTu']['no_BaGuaZhenTu'] = "你没有%s，不能使用。";

$GLOBALS['useShuiLiBian']['ShuiLiBian'] = "税吏鞭";
$GLOBALS['useShuiLiBian']['advanced_ShuiLiBian'] = "高级税吏鞭";
$GLOBALS['useShuiLiBian']['no_ShuiLiBian'] = "你没有%s，不能使用。";

$GLOBALS['useQingNangShu']['no_QingNangShu'] = "你没有青囊书，不能使用。";
$GLOBALS['useQingCangLing']['no_QingCangLing'] = "你没有清仓令，不能使用。";

$GLOBALS['openTreasureBox']['YuanBao'] = "元宝";

$GLOBALS['useCopperBox']['no_CopperBox'] = "你没有青铜宝箱，不能使用钥匙。";
$GLOBALS['useCopperBox']['no_CopperKey'] = "你没有青铜钥匙，不能打开宝箱。";

$GLOBALS['useSilverBox']['no_SiverBox'] = "你没有白银宝箱，不能使用钥匙。";
$GLOBALS['useSilverBox']['no_SiverKey'] = "你没有白银钥匙，不能打开宝箱。";

$GLOBALS['useGoldBox']['no_GoldBox'] = "你没有黄金宝箱，不能使用钥匙。";
$GLOBALS['useGoldBox']['no_GoldKey'] = "你没有黄金钥匙，不能打开宝箱。";
$GLOBALS['useOldWoodBox']['no_OldWoodBox'] = "你没有古朴木盒，不能使用。";
$GLOBALS['useLoveBean']['no_LoveBean'] = "你没有相思豆，不能使用。";
$GLOBALS['useBoleBao']['no_BoleBao'] = "你没有伯乐包，不能使用。";

$GLOBALS['useFlagChar']['no_FlagChar'] = "你没有旌旗，不能修改旗号。";
$GLOBALS['useFlagChar']['type_flag_name'] = "请输入旗号。";
$GLOBALS['useFlagChar']['only_one_char'] = "旗号只能为一位字符。";

$GLOBALS['useMingTie']['no_goods'] = "你没有“名贴”，不能修改君主名。";
$GLOBALS['useFireBarrel']['no_goods'] = "你没有“火药筒”，不能彻底拆除建筑。";

$GLOBALS['resPackage']['gain_gold'] = "获得黄金%d。";
$GLOBALS['resPackage']['gain_food'] = "获得粮食%d。";
$GLOBALS['resPackage']['gain_wood'] = "获得木材%d。";
$GLOBALS['resPackage']['gain_rock'] = "获得石料%d。";
$GLOBALS['resPackage']['gain_iron'] = "获得铁锭%d。";

$GLOBALS['useResourcePackage']['no_ResourcePackage'] = "你没有%s，不能使用。";
$GLOBALS['useResourcePackage']['gain_resource'] = "获得黄金10000，粮食100000，木材100000，石料100000，铁锭100000。";

$GLOBALS['useLoveRain']['gain_food'] = "相思雨滴发出明亮的闪光，你获得粮食10000。";
$GLOBALS['useLoveRain']['gain_wood'] = "相思雨滴发出明亮的闪光，你获得木材10000。";
$GLOBALS['useLoveRain']['gain_rock'] = "相思雨滴发出明亮的闪光，你获得石料10000。";
$GLOBALS['useLoveRain']['gain_iron'] = "相思雨滴发出明亮的闪光，你获得铁锭10000。";
$GLOBALS['useLoveRain']['gain_gold'] = "相思雨滴发出明亮的闪光，你获得黄金10000。";

$GLOBALS['useHuodongGoods']['no_HuoDongGoods'] = "你没有%s，不能使用。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_three'] = "你的官府等级不足3级，不能打开升级礼包。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_two'] = "你的官府等级不足2级，不能打开白银礼包I。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_four'] = "你的官府等级不足4级，不能打开白银礼包II。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_five'] = "你的官府等级不足5级，不能打开黄金礼包I。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_seven'] = "你的官府等级不足7级，不能打开黄金礼包II。";
$GLOBALS['useHuodongGoods']['govenment_lessThen_nine'] = "你的官府等级不足9级，不能打开黄金礼包III。";
$GLOBALS['useHuodongGoods']['lessThen_three_for_GongXun'] = "你的官府等级不足3级，不能打开功勋礼包。";
$GLOBALS['useHuodongGoods']['YuanBao'] = "元宝";


//ground
$GLOBALS['getGroundInfo']['no_ground_built'] = "该城池尚未建造校场。";

$GLOBALS['StartTroop']['no_flag']="你没有道具“军旗”，请先去商城购买或在出征时取消“使用军旗”选项。";
$GLOBALS['StartTroop']['target_cant_be_current'] = "目标不能是当前城池。";
$GLOBALS['StartTroop']['city_in_battle'] = "城池正在遭受攻击，不能出城。";
$GLOBALS['StartTroop']['suffer_ShiMianMaiFu'] = "你中了\"十面埋伏\",%s内不能出征。使用\"暗度陈仓\"可以打破封锁。";
$GLOBALS['StartTroop']['adv_move_cooldown'] = "使用了高级迁城令的城池，在24小时内只能给自己的城池运输，你需要等待%s后才能进行其他出征活动。";
$GLOBALS['StartTroop']['invalid_target'] = "指定的目标地点无效。";
$GLOBALS['StartTroop']['insufficient_ground_level'] = "校场等级不足，不能出征。";
$GLOBALS['StartTroop']['hero_not_found'] = "城内没有找到该将领。";
$GLOBALS['StartTroop']['hero_is_busy'] = "该将领不在空闲状态，不能出征。";
$GLOBALS['StartTroop']['hero_not_enough_force'] = "%s需要消耗%d点体力。将领体力不足，无法出征。";
$GLOBALS['StartTroop']['cant_detect_friendly_union'] = "不能侦察友好联盟的城池。";
$GLOBALS['StartTroop']['not_in_battle_condition'] = "你们不处在战争状态。不能进行出征。";
$GLOBALS['StartTroop']['wait_to_battle'] = "你们已经宣战，但需要到%s才能出征。";
$GLOBALS['StartTroop']['huangjin_unfinished'] = "时机尚未成熟，必须完成黄巾史诗任务后才能攻击名城。";
$GLOBALS['StartTroop']['has_following'] = "%s麾下有%d名忠心耿耿的部下，现在攻击他的时机还不成熟。你必须先削弱他的羽翼，直到他势单力薄剩下最后一个孤城的时候才能讨伐他。";
$GLOBALS['StartTroop']['capital']="时机尚未成熟，不能攻打都城。";
$GLOBALS['StartTroop']['changan']="时机尚未成熟，不能攻打长安。";
//$GLOBALS['StartTroop']['has_great_hero'] = "出征失败！该城池有名将镇守，士兵不愿前去送死。在你成为太守之前，无法攻击该城池。";

//"运输","派遣","侦察","掠夺","占领"
$GLOBALS['StartTroop']['transport'] = "运输";
$GLOBALS['StartTroop']['send'] = "派遣";
$GLOBALS['StartTroop']['detect'] = "侦察";
$GLOBALS['StartTroop']['harry'] = "掠夺";
$GLOBALS['StartTroop']['occupy'] = "占领";
$GLOBALS['StartTroop']['some_thing_wrong'] = "目标地状态出错，请与客服联系获得帮助。";
$GLOBALS['StartTroop']['still_in_protection'] = "你还在新手保护阶段，不能对其它玩家城池进行侦察，掠夺或占领。";
$GLOBALS['StartTroop']['in_peace_condition'] = "免战状态下不能对其它玩家城池进行侦察，掠夺或占领。";
$GLOBALS['StartTroop']['target_in_protection'] = "对方处在新手保护阶段，不能进行侦察，掠夺或占领。";
$GLOBALS['StartTroop']['target_in_vacation'] = "对方处于休假状态，不能对其进行出征。";
$GLOBALS['StartTroop']['target_be_locked'] = "对方处于被锁定状态，不能对其进行出征。";
$GLOBALS['StartTroop']['target_in_peace'] = "目标城池处于免战状态，不能进行侦察，掠夺或占领。";
$GLOBALS['StartTroop']['only_transport_to_friendly'] = "只能运输到自己或同盟的城池。";
$GLOBALS['StartTroop']['transport_in_peace_or_protection'] = "新手或免战状态下，不能给盟友运输。";
$GLOBALS['StartTroop']['only_send_to_friendly'] = "只能派遣到自己或同盟的城池和野地。";
$GLOBALS['StartTroop']['not_allow_union_troop'] = "对方不允许盟友驻军，请让对方先到鸿胪寺开启允许选项。";
$GLOBALS['StartTroop']['send_in_peace_or_protection'] = "新手或免战状态下，不能向盟友派遣。";
$GLOBALS['StartTroop']['only_towards_enemy'] = "只能%s非同盟的城池或野地。";
$GLOBALS['StartTroop']['no_so_many_army'] = "城池中没有这么多军队。";
$GLOBALS['StartTroop']['no_soldier'] = "军队中没有士兵，不能出战。";
$GLOBALS['StartTroop']['army_with_spy'] = "军队有斥候才能出征侦察。";
$GLOBALS['StartTroop']['spy_cant_alone'] = "斥候无法独自出征进行掠夺、占领";
$GLOBALS['StartTroop']['no_enough_ground_level'] = "当前级别的校场不能发送超过%d的军队。";
$GLOBALS['StartTroop']['no_enough_food'] = "城内粮食不足，不能出征。";
$GLOBALS['StartTroop']['cant_carry_negative'] = "不能携带负数的资源出征。";
$GLOBALS['StartTroop']['no_enough_resource'] = "城内资源不足，不能出征。";
$GLOBALS['StartTroop']['army_carry_limit'] = "你的军队载重不足，无法带上这么多资源。";
$GLOBALS['StartTroop']['succ'] = "出征成功。\n在军队面板中可以查看军队动态。";

$GLOBALS['setAttackTactics']['succ'] = "成功修改出征战术。";

$GLOBALS['setResistTactics']['succ'] = "成功修改防守战术。";

$GLOBALS['cureWoundedSoldier']['no_enough_gold'] = "本城的黄金不足，不能治疗所有伤兵。";
$GLOBALS['cureWoundedSoldier']['no_wounded_soldier'] = "本城没有伤兵，不需要治疗。";
$GLOBALS['cureWoundedSoldier']['succ'] = "治疗成功，伤兵全部康复，返回军营。";

$GLOBALS['dismissWoundedSoldier']['succ'] = "遣散成功，伤兵全部解甲归田，成为城池人口。";


//hotel
$GLOBALS['generateRecruitHero']['no_data_of_this_level'] = "没有该级别数据";

$GLOBALS['getHotelInfo']['no_hotel_built'] = "该城池尚未建造客栈。";

$GLOBALS['recruitHero']['no_enough_gold'] = "本城池的黄金不足，不能招募该将领。";
$GLOBALS['recruitHero']['hotel_level_low'] = "招贤馆等级不够，不能容纳更多的将领。";
$GLOBALS['getRumor']['hotel_level_low']="客栈5级才能使用“市井传闻”。";
$GLOBALS['getRumor']['never_heard'] = "从来没有听说过这个事情，客官你说笑的吧！";
$GLOBALS['getRumor']['dont_know_where_he_is'] = "不过他现在在什么地方小的就不清楚了。";
$GLOBALS['getRumor']['pay_for_hero'] = "听说，%s在%s。如果你给我%d个元宝，我就告诉你更准确的情报，再给你一张他的画像，有了画像你就能发现并俘虏他。";
$GLOBALS['getRumor']['dont_know_where_it_is'] = "不过现在流落到什么地方小的就不清楚了。";
$GLOBALS['getRumor']['pay_for_staff'] = "听说，%s在%s。如果你给我%d个元宝，我就告诉你更准确的情报。";;

$GLOBALS['searchRumor']['input_name_to_seartch'] = "请输入有效的搜索名称。";
$GLOBALS['searchRumor']['no_hotel_built'] = "没有客栈不能搜索";
$GLOBALS['searchRumor']['no_enough_gold'] = "本城池没有足够的黄金。";
$GLOBALS['searchRumor']['no_useful_info'] = "客官，实在不好意思，我这里没有你要问的消息。";

$GLOBALS['moreRumor']['no_enough_gold'] = "本城池没有足够的黄金。";

$GLOBALS['askDetail']['never_heard'] = "从来没有听说过这个事情，客官你说笑的吧！";
$GLOBALS['askDetail']['no_enough_YuanBao'] = "你连这点元宝都没有，就不要浪费我的时间啦。";
$GLOBALS['askDetail']['no_info_of_hero'] = "我没有这个人的消息，不劳您费元宝啦。";
$GLOBALS['askDetail']['hero_location'] = "据可靠消息，%s在%s。行动一定要快，不要被别人抢先了。";
$GLOBALS['askDetail']['word'] = ",字";
$GLOBALS['askDetail']['no_info_of_staff'] = "我没有关于%s的消息，不劳您费元宝啦。";
$GLOBALS['askDetail']['staff_location'] = "据可靠消息%s在%s。行动一定要快，不要被别人抢先了。"; 

$GLOBALS['recordTask']['no_task_related_to_hero'] = "无此武将相关的任务";
$GLOBALS['recordTask']['no_task_related_to_staff'] = "无此物品相关的任务";
$GLOBALS['recordTask']['no_rumor_to_record'] = "没有什么传闻可记录的。";
$GLOBALS['recordTask']['task_already_recorded'] = "您已经领取了此任务。";
$GLOBALS['recordTask']['task_accomplished'] = "您已经完成此任务。";
$GLOBALS['recordTask']['task_record_succ'] = "领取任务成功。";
$GLOBALS['recordTask']['npc_hero_exist'] = "你已经拥有该将领，不能领取该任务。";
$GLOBALS['recordTask']['task_list_full'] = "你的名将任务已经达到上限，放弃或完成一个任务后才能继续领取。";



//login
$GLOBALS['doLogin']['client_version_old'] = "客户端的版本过低，请关闭浏览器，重新登录游戏。";
$GLOBALS['doLogin']['server_not_start'] = "服务器尚未开放，请稍后再登录。";
$GLOBALS['doLogin']['invalid_user_pwd'] = "错误的用户名或密码。";
$GLOBALS['doLogin']['server_full'] = "本服务器人数已满。";
$GLOBALS['doLogin']['account_temp_locked'] = "你的帐号已经被封禁，%s后才能重新登录。";
$GLOBALS['doLogin']['account_locked'] = "玩家君主已经被锁定，不能登录。";


//loginFunc
$GLOBALS['login']['login_fail'] = "登录失败，请填写正确的账号和密码。";


//mail
$GLOBALS['readInboxMail']['mail_lost'] = "信件丢失，请与客服联系！";

$GLOBALS['readOutboxMail']['mail_lost'] = "信件丢失，请与客服联系！";

$GLOBALS['readSysMail']['mail_lost'] = "信件丢失，请与客服联系！";

$GLOBALS['checkMailFull']['inbox_full'] = "收件箱已满，请删除多余信件后再发送。";
$GLOBALS['checkMailFull']['outbox_full'] = "发件箱已满，请删除多余信件后再发送。";

$GLOBALS['sendPersonMail']['untitled'] = "无标题";
$GLOBALS['sendPersonMail']['cant_find_addressee'] = "找不到收信人［%s］！";
$GLOBALS['sendPersonMail']['content_illegal'] = "信件内容包含非法字符，不能发送";
$GLOBALS['sendPersonMail']['auto_mail_content'] = "【系统自动提示信息：官方不会以任何形式在个人信件中通知用户中奖，如果您收到此类信件，请不要相信，更不要向信息发布者汇款，谨防受骗！】\n\n";

$GLOBALS['sendUnionMail']['untitled'] = "无标题";
$GLOBALS['sendUnionMail']['union'] = "［联盟］";
$GLOBALS['sendUnionMail']['not_champion'] = "你不是盟主或副盟主，不能发联盟群发信！";
$GLOBALS['sendUnionMail']['no_enough_acoustic'] = "您没有足够的传音符，请去商城购买后再发送联盟群发信。";


//market
$GLOBALS['getMarketInfo']['no_market_built'] = "该城池尚未建造市场。";

$GLOBALS['cancelSell']['cant_cancel'] = "已经达成的交易不能取消。";

$GLOBALS['accelerateSell']['no_MuNiuLiuMa'] = "你没有“木牛流马”,不能对交易进行加速。";
$GLOBALS['accelerateSell']['trade_not_exist'] = "指定的交易不存在，不能进行加速。";

$GLOBALS['buyFromMerchant']['input_amount'] = "请输入正常的购买数量。";
$GLOBALS['buyFromMerchant']['no_enough_gold'] = "本城的黄金不够。";
$GLOBALS['buyFromMerchant']['no_enough_YuanBao'] = "元宝数量不足，不能完成交易。";
$GLOBALS['buyFromMerchant']['succ'] = "交易成功！";
$GLOBALS['buyFromMerchant']['buy_limit'] = "%d级市场和商人交易的单笔交易上限为%d00000黄金。";

$GLOBALS['sellToMerchant']['input_amount'] ="请输入正常的出售数量。";
$GLOBALS['sellToMerchant']['no_enough_food'] = "本城的粮食不足，不能完成交易。";
$GLOBALS['sellToMerchant']['no_enough_wood'] = "本城的木材不足，不能完成交易。";
$GLOBALS['sellToMerchant']['no_enough_rock'] = "本城的石料不足，不能完成交易。";
$GLOBALS['sellToMerchant']['no_enough_iron'] = "本城的铁锭不足，不能完成交易。";
$GLOBALS['sellToMerchant']['no_enough_YuanBao'] = "你的元宝数量不足，不能完成交易。";
$GLOBALS['sellToMerchant']['succ'] = "交易成功！";

$GLOBALS['sellToUser']['invalid_amount'] = "出售数量不正确。";
$GLOBALS['sellToUser']['trade_time_limit'] = "交易时限不能少于1小时。";
$GLOBALS['sellToUser']['no_free_caravan'] = "城内已经没有空闲商队了。";
$GLOBALS['sellToUser']['single_trade_upperLimit'] = "%d级市场的单笔交易上限为%d00000。";
$GLOBALS['sellToUser']['no_enough_food'] = "本城的粮食不够。";
$GLOBALS['sellToUser']['price_runaway'] = "出售价格超出规定范围，不能出售。";
$GLOBALS['sellToUser']['no_enough_wood'] = "本城的木材不够。";
$GLOBALS['sellToUser']['no_enough_rock'] = "本城的石料不够。";
$GLOBALS['sellToUser']['no_enough_iron'] = "本城的铁锭不够。";

$GLOBALS['buyFromUser']['trade_not_exist'] = "该交易不存在。";
$GLOBALS['buyFromUser']['bought_by_others'] = '该资源已经被其他玩家抢先购买。';
$GLOBALS['buyFromUser']['cant_buy_from_yourself'] = "你不能购买自己其它城池的资源。";
$GLOBALS['buyFromUser']['no_enough_gold'] = "本城的黄金不足。";
$GLOBALS['buyFromUser']['no_free_caravan'] = "城内已经没有空闲商队了，请升级市场。";
$GLOBALS['buyFromUser']['distance_too_far'] = "城池之间的距离过远，交易失败。";
$GLOBALS['buyFromUser']['sell_to_union_only'] = "对方只卖给同一联盟的人，你和对方已经不在同一联盟内，不能购买。";
$GLOBALS['sellGoods']['building_level'] = "市场等级达到5级才能出售宝物。";
$GLOBALS['sellGoods']['nobility_low'] = "爵位达到“公士”才能出售宝物。";
$GLOBALS['sellGoods']['not_enough_goods'] = "你没有那么多道具，请正确填写道具数量。";


//office
$GLOBALS['getOfficeInfo']['no_office_built'] = "铁锭";

$GLOBALS['setCityChief']['set_chief_fail'] = "任命城守失败。";

$GLOBALS['dismissHero']['cant_dissmiss_this'] = "不能解雇该将领。";
$GLOBALS['dismissHero']['only_dissmiss_free_hero'] = "空闲状态的将领才能解雇。";

$GLOBALS['upgradeHero']['cant_upgrade_this'] = "不能升级该将领。";
$GLOBALS['upgradeHero']['cant_upgrade_out_hero'] = "不能升级不在城池内的将领。";
$GLOBALS['upgradeHero']['no_enough_exp'] = "将领的经验不足，不能升级。";

$GLOBALS['addHeroPoint']['cant_find_hero'] = "找不到该将领。";
$GLOBALS['addHeroPoint']['cant_add_out_hero'] = "不能给不在城池内的将领加属性点。";
$GLOBALS['addHeroPoint']['no_extra_potential'] = "没有多余的潜力。";

$GLOBALS['clearHeroPoint']['cant_find_hero'] = "找不到该将领。";
$GLOBALS['clearHeroPoint']['cant_clean_out_hero'] = "不能给不在城池里的将领洗点。";

$GLOBALS['changeHeroName']['name_too_long'] = "将领名太长，不能超过4个字。";
$GLOBALS['changeHeroName']['input_valid_name'] = "请输入有效的将领名字";
$GLOBALS['changeHeroName']['invalid_char'] = "不能采用非法的字符串作为将领名。";
$GLOBALS['changeHeroName']['cant_find_hero'] = "找不到该将领。";
$GLOBALS['changeHeroName']['cant_change_out_hero'] = "不能给不在城池内的将领改名。";
$GLOBALS['changeHeroName']['cant_change_famous_hero'] = "不能给历史将领改名。";

$GLOBALS['largessHero']['cant_find_hero'] = "找不到该将领。";
$GLOBALS['largessHero']['cant_largess_out_hero'] = "不能给不在城池内的将领赏赐。";
$GLOBALS['largessHero']['wait_duration'] = "你刚刚赏赐过该将领，%s后才能再次赏赐。";
$GLOBALS['largessHero']['no_enough_gold'] = "本城的黄金不足，不能赏赐将领。";
$GLOBALS['largessHero']['no_need_gold'] = "黄金已经无法打动该将领，你需要赏赐他更贵重的珍宝。";
$GLOBALS['largessHero']['no_this_prop'] = "你没有此道具，不能赏赐将领。";

$GLOBALS['releaseHero']['hero_not_exist'] = "此将领不存在";
$GLOBALS['releaseHero']['hero_not_captive'] = "该将领不在俘虏状态，不能释放";
$GLOBALS['releaseHero']['hero_not_coming'] = "该将领不在投奔状态，不能回绝";

$GLOBALS['getNpcIntroduce']['no_hero_info'] = "无该将领信息";
$GLOBALS['getNpcIntroduce']['not_famous_hero'] = "该将领不是历史武将，没有说明。";

$GLOBALS['trySummonHero']['no_hero_info'] = "无该将领信息";
$GLOBALS['trySummonHero']['hero_not_captive'] = "不在俘虏状态，不能招降。";
$GLOBALS['trySummonHero']['no_enough_nobility'] = "我的主公，必定是威震天下的英雄，你连\"%s\"都没有达到，我是不会跟随你的。";
$GLOBALS['trySummonHero']['hero_need'] = "若要求得良将，须得以诚相待，赏赐宝物金帛是必不可少的。收服该将领需要：";
$GLOBALS['trySummonHero']['gold'] = "黄金";
$GLOBALS['tryAcceptHero']['hero_not_coming'] = "不在投奔状态，不能接纳。";

$GLOBALS['sureSummonHero']['hero_not_exist'] = "此将领不存在";
$GLOBALS['sureSummonHero']['hero_not_captive'] = "该将领不在俘虏状态，不能招降";
$GLOBALS['sureSummonHero']['no_enough_gold'] = "本城没有足够的黄金，不能使该将领为你效命。";
$GLOBALS['sureSummonHero']['no_enough_goods'] = "你没有足够的%s，不能使%s为你效命。";


//report
$GLOBALS['report']['connection_drop'] = "你已经掉线，请重新登录。";
$GLOBALS['report']['cant_operate_others'] = "你无权对其它人的数据进行操作。";


//reportFunc
$GLOBALS['callBackTroop']['invalid_army'] = "无效的军队。";
$GLOBALS['callBackTroop']['on_back']="这支军队被对方使用了“关门打狗”计谋，无法返回，使用“金蝉脱壳”，可以快速召回军队。";
$GLOBALS['callBackTroop']['gather']="军队正在采集，不能召回。请先收获再召回。";
$GLOBALS['callBackTroop']['army_in_battle'] = "不能召回正在战斗中的军队。";
$GLOBALS['callBackTroop']['army_on_way_back'] = "该军队已经在回城途中了。";

$GLOBALS['getBattleData']['battle_end'] = "战斗已经结束，请查看报战。";
$GLOBALS['getBattleData']['battle_data_lost'] = "战斗数据丢失！";

$GLOBALS['setSoldierTactics']['cant_change_enemy_tactics'] = "你不能改变对方的战术。";


//shop
$GLOBALS['buyGoods']['invalid_amount'] = "购买数量无效。";
$GLOBALS['buyGoods']['stop_sale'] = "此商品已经停售。";
$GLOBALS['buyGoods']['sold_out'] = "此商品已经卖光了。";
$GLOBALS['buyGoods']['no_enough_YuanBao'] = "你的元宝不足，请充值。";
$GLOBALS['buyGoods']['reach_remain_amount_todayLimit'] = "购买数量无效。此商品每人每天限购%d个，你今天已经购买%d个，只能再购买%d个此商品。";
$GLOBALS['buyGoods']['reach_buy_todayLimit'] = "此商品每人每天限购%d个，你今天已经达到购买限制，请明天再来购买此商品。";
$GLOBALS['buyGoods']['reach_remain_amountLimit'] = "购买数量无效。此商品每人限购%d个，你已经购买%d个，只能再购买%d个此商品。";
$GLOBALS['buyGoods']['reach_buy_limit'] = "此商品每人限购%d个，你已经达到购买限制，不能再购买更多此商品。";
$GLOBALS['buyGoods']['nobility_limit'] = "只有爵位达到“公士”才能购买和使用“聚贤包”。";

$GLOBALS['exchangeLiquan']['code_notNull'] = "礼券码不能为空。";
$GLOBALS['exchangeLiquan']['invalid_code'] = "礼券码无效。请重新输入正确的礼券码。";
$GLOBALS['exchangeLiquan']['used_code'] = "该礼券码已被使用。";
$GLOBALS['exchangeLiquan']['code_bind'] = "该礼券码已和另外的玩家绑定，你无法使用。";
$GLOBALS['exchangeLiquan']['YuanBao'] = "元宝";
$GLOBALS['exchangeLiquan']['description_of_YuanBao'] = "元宝，用于购买商场道具，可通过充值获得。";


//soldier
$GLOBALS['doGetSoldierInfo']['pre_building'] = "前提建筑";
$GLOBALS['doGetSoldierInfo']['level'] = "等级";
$GLOBALS['doGetSoldierInfo']['pre_technic'] = "前提科技";

$GLOBALS['getArmyInfo']['no_barracks_built'] = "该地尚未建造军营。";

$GLOBALS['startDraftQueue']['cant_recruit_zero'] = "不能招募0个士兵。";
$GLOBALS['startDraftQueue']['no_barracks_info'] = "没有该兵营信息";
$GLOBALS['startDraftQueue']['no_army_branch_info'] = "没有该兵种信息。";
$GLOBALS['startDraftQueue']['no_enough_resource'] = "资源不足。";
$GLOBALS['startDraftQueue']['lack_free_people'] = "空闲人口不足，不能训练%d个士兵。";
$GLOBALS['startDraftQueue']['no_pre_building'] = "前提建筑没有建好。";
$GLOBALS['startDraftQueue']['no_pre_technic'] = "前提科技没有研究好。";
$GLOBALS['startDraftQueue']['reach_queue_limit'] = "该兵营的训练队列已经达到上限。";

$GLOBALS['stopDraftQueue']['no_barracks_info'] = "没有该兵营信息。";
$GLOBALS['stopDraftQueue']['no_army_branch_info'] = "没有该兵种信息。";

$GLOBALS['dissolveSoldier']['cant_dismiss_zero'] = "不能解散0个兵。";
$GLOBALS['dissolveSoldier']['no_barracks_info'] = "没有该兵营信息";
$GLOBALS['dissolveSoldier']['no_army_branch_info'] = "没有该兵种信息。";
$GLOBALS['dissolveSoldier']['cant_dismiss_exceed'] = "不能解散比当前士兵数量还要多的士兵。";
$GLOBALS['accSoldier']['only_once'] = "一个造兵队列只能加速一次。";
$GLOBALS['accSoldier']['no_goods'] = "你没有道具“韩信三篇”，请先去商城购买道具。";

//store
$GLOBALS['modifyStoreRate']['negative_store_rate'] = "存放比例不能为负数！";
$GLOBALS['modifyStoreRate']['resource_total_100'] = "四项资源存放比例之和不能超过100。";
$GLOBALS['modifyStoreRate']['succ_change_rate'] = "修改仓库存放比例成功！";


//task
$GLOBALS['getReward']['already_got'] = "你已经领取过该任务的奖励。";
$GLOBALS['getReward']['task_not_finished'] = "任务尚未完成,不能领取奖励";
$GLOBALS['getReward']['global_task_end'] = "该任务已经结束。";
$GLOBALS['getReward']['invalid_count'] = "请输入正确人领取数量。";
$GLOBALS['getReward']['not_enough_things'] = "任务物品不足，不能领取%d次。";
$GLOBALS['getReward']['not_enough_remain']="任务剩余次数不足，批量领取失败。";
$GLOBALS['getReward']['not_allowed_multi']="该任务不允许批量领取。";



$GLOBALS['doGetTechnicInfo']['pre_building'] = "前提建筑";
$GLOBALS['doGetTechnicInfo']['level'] = "等级";
$GLOBALS['doGetTechnicInfo']['pre_technic'] = "前提科技";

$GLOBALS['getCollegeInfo']['no_college_built'] = "该城池尚未建造学院。";

$GLOBALS['startUpgradeTechnic']['no_technic_info'] = "没有该科技信息。";
$GLOBALS['startUpgradeTechnic']['technic_full']="科技已经升达到顶级，不能继续研究了";
$GLOBALS['startUpgradeTechnic']['only_analysis_1_tech'] = "一所书院只能同时研究一项科技，已经有其它科技正在研究。";
$GLOBALS['startUpgradeTechnic']['no_enough_resource'] = "资源不足，不能升级该科技。";
$GLOBALS['startUpgradeTechnic']['no_pre_building'] = "前提建筑没有建好。";
$GLOBALS['startUpgradeTechnic']['no_pre_technic'] = "前提科技没有研究好。";

$GLOBALS['stopUpgradeTechnic']['no_upgrading_tech_info'] = "无此在建的科技的信息";


//union
$GLOBALS['getHongLuInfo']['no_HongLu_built'] = "该城池尚未建造鸿胪寺。";

$GLOBALS['kickUnionTroop']['not_your_city'] = "本城池不属于你，不能进行操作。";
$GLOBALS['kickUnionTroop']['army_not_exist'] = "该驻军已经不存在。";

$GLOBALS['createUnion']['already_joined_other_union'] = "你已经属于一个联盟，只有退出原来联盟后才能再创建新的联盟。";
$GLOBALS['createUnion']['union_name_notNull'] = "联盟名字不能为空。";
$GLOBALS['createUnion']['union_name_tooLong'] = "联盟名字过长，不能超过8个字符。";
$GLOBALS['createUnion']['has_ivalid_char'] = "联盟名字含有不被允许的字符";
$GLOBALS['createUnion']['level_lessThen_2'] = "你的鸿胪寺等级不足2级，不能创建联盟。";
$GLOBALS['createUnion']['gold_not_enough'] = "本城池的黄金不足10000,不能创建联盟。";
$GLOBALS['createUnion']['use_another_name'] = "联盟名已经被占用，请重新输入联盟名。";
$GLOBALS['createUnion']['add_union_event'] = " 创建联盟";

$GLOBALS['getUnionApplyInvite']['succ'] = "］已经通过你的申请请求，你现在已经是联盟的一员了";

$GLOBALS['applyJoin']['no_HongLu_built'] = "你尚未建造鸿胪寺，不能申请加入联盟。";
$GLOBALS['applyJoin']['already_joined_other_union'] = "你已经在一个联盟中，退出当前联盟后才能申请加入其它联盟。";
$GLOBALS['applyJoin']['reset_application'] = "你已经申请加入［%s］,去鸿胪寺撤消原申请之后才能重新申请。";
$GLOBALS['applyJoin']['send_application_succ'] = "申请发送成功，请等待盟主同意！";
$GLOBALS['applyJoin']['union_not_exist'] = "该联盟已经解散。";

$GLOBALS['getApplyList']['not_official'] = "你不是联盟官员，无权查看联盟申请列表。";

$GLOBALS['acceptApply']['taget_joined_other_union'] = "对方已经加入其他联盟，不能再加入本联盟。";
$GLOBALS['acceptApply']['target_has_no_HongLu'] = "对方尚未建造鸿胪寺，不能加入联盟。";
$GLOBALS['acceptApply']['not_official'] = "你不是联盟官员，无权处理玩家的联盟申请。";
$GLOBALS['acceptApply']['union_not_exist'] = "联盟不存在！";
$GLOBALS['acceptApply']['data_record_not_exist'] = "数据记录不存在！";
$GLOBALS['acceptApply']['no_HongLu_built'] = "你没有建造鸿胪寺，不能加新的玩家。";
$GLOBALS['acceptApply']['union_is_full'] = "你的联盟人数已满，不能再加新的玩家。";
$GLOBALS['acceptApply']['addUnionEvent'] = "%s 通过了 %s 入盟申请！";

$GLOBALS['rejectApply']['not_official'] = "你不是联盟官员，无权处理玩家的联盟申请。";

$GLOBALS['acceptInvite']['invalid_invitation'] = "该邀请已经无效。";
$GLOBALS['acceptInvite']['union_not_exist'] = "该联盟已经不存在。";
$GLOBALS['acceptInvite']['no_HongLu_built'] = "你没有建造鸿胪寺，不能加入联盟。";
$GLOBALS['acceptInvite']['already_joined_other_union'] = "你已经属于一个联盟，不能加入其它联盟。";
$GLOBALS['acceptInvite']['addUnionEvent'] = "%s 邀请 %s 加入联盟！";

$GLOBALS['rejectInvite']['invalid_invitation'] = "该邀请已经无效。";
$GLOBALS['rejectInvite']['union_not_exist'] = "该联盟已经不存在。";

$GLOBALS['loadUnionDetail']['union_dissmissed'] = "该联盟已经解散。";

$GLOBALS['loadUnionInfo']['not_belongTo_union'] = "你已经不属于任何联盟。请重新选择联盟加入或者创建联盟。";
$GLOBALS['loadUnionInfo']['your_union_is_out'] = "你属于的联盟已经不存在。请重新选择联盟加入或者创建联盟。";

$GLOBALS['loadUnionMemberList']['you_belongTo_none_union'] = "你已经不属于任何一个联盟。";

$GLOBALS['leaveUnion']['you_belongTo_none_union'] = "你当前不属于任何一个联盟。";
$GLOBALS['leaveUnion']['your_union_is_out'] = "你所属的联盟已经不存在。";
$GLOBALS['leaveUnion']['chief_cant_leave'] = "你的联盟还有成员，身为盟主不能退出联盟。";
$GLOBALS['leaveUnion']['official_cant_leave'] = "联盟官员不能退出联盟，请先辞职!";
$GLOBALS['leaveUnion']['addUnionEvent'] = "%s 退出了联盟！";

$GLOBALS['getInviteList']['you_are_not_official'] = "你不是联盟官员，无权邀请玩家加入联盟。";

$GLOBALS['cancelInvite']['you_are_not_official'] = "你不是联盟官员，无权取消邀请。";

$GLOBALS['inviteUser']['enter_target_name'] = "请输入你邀请的玩家名。";
$GLOBALS['inviteUser']['name_length_most_8'] = "玩家名字最多8个字符";
$GLOBALS['inviteUser']['you_are_not_official'] = "你不是联盟官员，无权发起邀请。";
$GLOBALS['inviteUser']['named_user_not_exist'] = "你输入的玩家不存在，请重新输入。";
$GLOBALS['inviteUser']['cant_invite_yourself'] = "不能邀请自己加入联盟";
$GLOBALS['inviteUser']['taget_joined_other_union'] = "对方已经加入某个联盟，不能接受邀请。";
$GLOBALS['inviteUser']['your_union_is_full'] = "你的联盟人数已满，不能再邀请新的玩家。";

$GLOBALS['kickMember']['enter_target_name'] = "请指定要开除的成员名字！";
$GLOBALS['kickMember']['name_length_most_8'] = "玩家名称最多8个字符！";
$GLOBALS['kickMember']['not_elder'] = "你的盟内职位不是长老以上级别，无权开除会员。";
$GLOBALS['kickMember']['target_name_not_exist'] = "你输入的玩家不存在，请重新输入。";
$GLOBALS['kickMember']['target_not_in_your_union'] = "该玩家不是本盟会员，不能开除";
$GLOBALS['kickMember']['descend_target_level'] = "不能开除担任联盟职务的成员，请先将其降级！";
$GLOBALS['kickMember']['cant_kick_oneself'] = "不能开除自己，请用“退出联盟”功能来退出。";
$GLOBALS['kickMember']['addUnionEvent'] = "%s 把 %s 开除出联盟！";

$GLOBALS['changeLeader']['enter_target_name'] = "请输入新盟主名字";
$GLOBALS['changeLeader']['name_length_most_8'] = "玩家名称最多8个字符！";
$GLOBALS['changeLeader']['you_are_not_chief'] = "你不是盟主，无权转让盟主。";
$GLOBALS['changeLeader']['target_name_not_exist'] = "你输入的玩家不存在，请重新输入。";
$GLOBALS['changeLeader']['target_not_in_your_union'] = "该玩家不是本盟会员，不能转让。";
$GLOBALS['changeLeader']['upgrade_vice_chief'] = "盟主只能转让给副盟主，请将对方提升为副盟主再进行转让！";
$GLOBALS['changeLeader']['addUnionEvent'] = "盟主 %s 把盟主转让给 %s ！";

$GLOBALS['getUnionIntro']['you_are_not_chief'] = "你不是盟主或副盟主，没有权限修改介绍文字。";

$GLOBALS['modifyIntro']['union_name_notNull'] = "联盟名字不能为空！";
$GLOBALS['modifyIntro']['union_name_tooLong'] = "联盟名字过长，不能超过8个字符。";
$GLOBALS['modifyIntro']['union_description_tooLong'] = "联盟介绍过长，不能超过200字符。";
$GLOBALS['modifyIntro']['union_announce_tooLong'] = "联盟公告过长，不能超过500字符。";
$GLOBALS['modifyIntro']['you_are_not_chief'] = "你不是盟主或副盟主，没有权限修改介绍文字。";
$GLOBALS['modifyIntro']['union_name_in_use'] = "联盟名字已被其他联盟占用，请更换其他名字！";
$GLOBALS['modifyIntro']['invalid_char'] = "联盟名字含有不被允许的字符";
$GLOBALS['modifyIntro']['addUnionEvent'] = "%s 将联盟名称改为 %s !";

$GLOBALS['getUnionRelation']['not_belongTo_union'] = "你已经不属于任何联盟。请重新选择联盟加入或者创建联盟。";

$GLOBALS['addUnionRelation']['enter_target_name'] = "请输入对方联盟名字";
$GLOBALS['addUnionRelation']['union_name_tooLong'] = "联盟名称最多8个字符！";
$GLOBALS['addUnionRelation']['you_are_not_chief'] = "你不是盟主或副盟主，没有权限处理联盟外交关系。";
$GLOBALS['addUnionRelation']['cant_contact_with_oneself'] = "不能和自己的联盟建立外交关系！";
$GLOBALS['addUnionRelation']['target_union_not_exist'] = "你输入的联盟不存在！";
$GLOBALS['addUnionRelation']['too_frequency'] = "%s后才能再次更改与该联盟的外交关系。！";
$GLOBALS['addUnionRelation']['friendly'] = "友好";
$GLOBALS['addUnionRelation']['neutral'] = "中立";
$GLOBALS['addUnionRelation']['hostile'] = "敌对";
$GLOBALS['addUnionRelation']['union_declare_war'] = "联盟宣战";
$GLOBALS['addUnionRelation']['mail_content'] = "　　%s 将对本盟的外交关系设置为 %s，本盟随时可能遭到敌人的袭击。<br/>　　将对方联盟加为敌对关系，可以与其交战。";
$GLOBALS['addUnionRelation']['unionWar_declare'] = "［%s］联盟对［%s］联盟宣战！";
$GLOBALS['addUnionRelation']['set_A_and_B'] = "%s 将本盟和 %s 的外交关系设置为 %s";
$GLOBALS['addUnionRelation']['set_B_and_A'] = "%s 将对本盟的外交关系设置为 %s";

$GLOBALS['removeUnionRelation']['you_are_not_chief'] = "你不是盟主或副盟主，没有权限处理联盟外交关系。";
$GLOBALS['removeUnionRelation']['friendly'] = "友好";
$GLOBALS['removeUnionRelation']['neutral'] = "中立";
$GLOBALS['removeUnionRelation']['hostile'] = "敌对";
$GLOBALS['removeUnionRelation']['cancel_A_and_B'] = "%s 取消了本盟和 %s 的 %s 外交关系";
$GLOBALS['removeUnionRelation']['cancel_B_and_A'] = "%s 取消了和本盟的 %s 外交关系";

$GLOBALS['getUnionEvent']['not_in_union'] = "你不在任何联盟中，没有联盟事件！";

$GLOBALS['setUnionProvicy']['not_authorizied'] = "你没有权限修改对方的联盟职位！";
$GLOBALS['setUnionProvicy']['union_memeber'] = "成员";
$GLOBALS['setUnionProvicy']['union_vice_chief'] = "副盟主";
$GLOBALS['setUnionProvicy']['union_elder'] = "长老";
$GLOBALS['setUnionProvicy']['union_official'] = "官员";
$GLOBALS['setUnionProvicy']['descend_level'] = "%s 将 %s 降级为 %s";
$GLOBALS['setUnionProvicy']['upgrade_level'] = "%s 将 %s 升级为 %s";

$GLOBALS['demissionUnion']['not_in_union'] = "你已经不在联盟中";
$GLOBALS['demissionUnion']['no_any_position'] = "你已经没有任何职务，不用辞职";
$GLOBALS['demissionUnion']['union_dissmissed'] = "联盟已经解散！";
$GLOBALS['demissionUnion']['chief_cant_resign'] = "盟主不能辞职，请先转让盟主，再辞职";
$GLOBALS['demissionUnion']['union_memeber'] = "成员";
$GLOBALS['demissionUnion']['union_vice_chief'] = "副盟主";
$GLOBALS['demissionUnion']['union_elder'] = "长老";
$GLOBALS['demissionUnion']['union_official'] = "官员";
$GLOBALS['demissionUnion']['add_union_event'] = "%s 辞去 %s 职务";

$GLOBALS['getUnionReport']['not_in_union'] = "你不在任何联盟中，没有联盟军情！";

$GLOBALS['getUnionReportDetail']['not_in_union'] = "你已经不在任何联盟中，不能查看联盟军情！";
$GLOBALS['getUnionReportDetail']['report_not_found'] = "战报不存在或者已经过期，被系统删除了！";


//user
$GLOBALS['useGoods']['no_this_good'] = "你拥有的该道具数量为0，请去商城购买后再使用。";
$GLOBALS['useGoods']['no_pack_good'] = "礼包不存存，请与客服联系。";
$GLOBALS['useGoods']['acoustic_used_in_world_channel'] = "传音符是在世界频道聊天的时候使用。";
$GLOBALS['useGoods']['ShenNongChu_valid_date'] = "神农锄有效期截止到";
$GLOBALS['useGoods']['LuBanFu_valid_date'] = "鲁班斧有效期截止到";
$GLOBALS['useGoods']['KaiShanCui_valid_date'] = "开山锤有效期截止到";
$GLOBALS['useGoods']['XuanTieLu_valid_date'] = "玄铁炉有效期截止到";
$GLOBALS['useGoods']['XianZhenZhaoGu_valid_date'] = "陷阵战鼓有效期截止到";
$GLOBALS['useGoods']['BaGuaZhenTu_valid_date'] = "八卦阵图有效期截止到";
$GLOBALS['useGoods']['MoJiaCanJuan'] = "墨家残卷";
$GLOBALS['useGoods']['MojiaTuZhi'] = "墨家图纸";
$GLOBALS['useGoods']['MoJiaDianJi'] = "墨家典籍";
$GLOBALS['useGoods']['MoJiaMiJi'] = "墨家密笈";
$GLOBALS['useGoods']['MianZhanPai'] = "免战牌";
$GLOBALS['useGoods']['JinNang'] = "锦囊";
$GLOBALS['useGoods']['use_MengZhuLing_succ'] = "“盟主令”使用成功，联盟成员人数上限提升为100。";
$GLOBALS['useGoods']['XiSuiDan_used_for_reset_hero'] = "洗髓丹是在给将领洗点的时候使用。";
$GLOBALS['useGoods']['ZhaoXianBang_used_for_hire_hero'] = "“招贤榜”在客栈的“招贤纳士”处使用。";
$GLOBALS['useGoods']['QingNangShu_valid_date'] = "“青囊书”有效期截止到";
$GLOBALS['useGoods']['YaoYiLin_valid_date'] = "“徭役令”有效期截止到";
$GLOBALS['useGoods']['QingCangLing_valid_date'] = "“清仓令”有效期截止到";
$GLOBALS['useGoods']['KaoGongJi_valid_date']="“%s”有效时间截止到";
$GLOBALS['useGoods']['ShuiLiBian_valid_date']="“税吏鞭”有效时间截止到";
$GLOBALS['useGoods']['JunQi_used_for_army'] = "“军旗”在军队出征的时候使用。";
$GLOBALS['useGoods']['AnMingGaoShi_cool_down']="“安民告示”72小时内只能使用一次，请在%s后再使用。";
$GLOBALS['useGoods']['AnMingGaoShi_succ']="“安民告示”使用成功，当前城池民心升至100，民怨降为0。";
$GLOBALS['useGoods']['HanXinSanPian_used_for_army']="“韩信三篇”是在对招兵队列加速的时候使用。";
$GLOBALS['useGoods']['BeiChengMen_used_for_army']="“备城门”在对城防建造队列加速的时候使用。";
$GLOBALS['useGoods']['armor_box_full']="你已经没有足够的空间来放置新的装备，请将多余的装备回收。";
$GLOBALS['useGoods']['func_not_in_use'] = "此功能尚未开放。";
$GLOBALS['useGoods']['invalid_data']="数据错误，请与客服联系。";
$GLOBALS['useGoods']['hero_state_wrong']="将领正在出征或者没有效忠于你。只能给在本城内效忠于你的将领使用。";
$GLOBALS['useGoods']['hero_level_full']="将领等级达到上限，不需要再增加经验。";


$GLOBALS['useMojiaGoods']['invalid_param'] = "参数错误";
$GLOBALS['useMojiaGoods']['no_need_to_use'] = "该科技不需要使用宝物。";
$GLOBALS['useMojiaGoods']['no_enough_goods'] = "你没有足够的宝物，不能使用。";

$GLOBALS['useLuBanGoods']['invalid_param'] = "参数错误";
$GLOBALS['useLuBanGoods']['no_need_to_use'] = "该建筑不需要使用宝物。";
$GLOBALS['useLuBanGoods']['no_enough_goods'] = "你没有足够的宝物，不能使用。";


$GLOBALS['doCreateCity']['province_is_full'] = "该州的城池已经太多了，请选择其它州建城。";
$GLOBALS['doCreateCity']['reType_city_name'] = "创建城池出错，请重新选择州。";

$GLOBALS['createCity']['city_name_tooLong'] = "城池名不能超过8个字符。";

$GLOBALS['createRole']['cant_duplicate_create'] = "您不能重复创建新城池。";
$GLOBALS['createRole']['city_holder_name_notNull'] = "君主名不能为空。";
$GLOBALS['createRole']['city_holder_name_tooLong'] = "君主名不能超过8个字符。";
$GLOBALS['createRole']['city_name_tooLong'] = "城池名不能超过8个字符。";
$GLOBALS['createRole']['invalid_char'] = "不能采用非法的字符串作为君主名。";
$GLOBALS['createRole']['no_illege_char'] = "君主名不能含有不被允许的字符";
$GLOBALS['createRole']['enter_flag_char'] = "请输入旗号。";
$GLOBALS['createRole']['single_char'] = "旗号只能为一位字符。";
$GLOBALS['createRole']['used_city_holder_name'] = "君主名已经被占用，请重新输入。";

$GLOBALS['changeUserState']['mianzhan'] = "免战";
$GLOBALS['changeUserState']['xiujia'] = "休闲";
$GLOBALS['changeUserState']['invalid_pwd'] = "密码错误，不能修改状态。";
$GLOBALS['changeUserState']['no_need_recovery'] = "你当前状态已经是正常了，不需要恢复。";
$GLOBALS['changeUserState']['no_need_MianZhanPai'] = "你当前状态已经是免战，不需要重新免战。";
$GLOBALS['changeUserState']['wait_to_use_MianZhanPai'] = "后才能使用免战牌。";
$GLOBALS['changeUserState']['some_city_in_war'] = "你的某个城池处于战乱中，不能";
$GLOBALS['changeUserState']['army_out'] = "你有军队在外，不能";
$GLOBALS['changeUserState']['technic_upgrading'] = "你有科技在升级，不能";
$GLOBALS['changeUserState']['building_upgrading'] = "你有建筑在升级，不能";
$GLOBALS['changeUserState']['soldier_queue'] = "你有兵营正在招募军队，不能";
$GLOBALS['changeUserState']['defence_queue'] = "你有城防正在建造，不能";
$GLOBALS['changeUserState']['union_army_in_city'] = "你的城池有盟友军队驻军，不能";
$GLOBALS['changeUserState']['vacation_limit'] = "休假至少2天，最多99天。";
$GLOBALS['changeUserState']['vacation_cant_dismiss'] = "休假至少要48小时后才能解除。在%s后才能解除休假状态。";


$GLOBALS['changeCityPosition']['has_army_outside'] = "本城有军队在外，不能迁城。";
$GLOBALS['changeCityPosition']['has_ally_force'] = "本城（或附属野地）有其它盟友驻军，不能迁城。";
$GLOBALS['changeCityPosition']['city_in_battle'] = "本城正在战乱中，不能迁城。";
$GLOBALS['changeCityPosition']['cant_move_great_city'] = "不能对名城使用迁城。";
$GLOBALS['changeCityPosition']['no_QianChengLing'] = "你没有迁城令，不能迁城。";
$GLOBALS['changeCityPosition']['no_adv_QianChengLing'] = "你没有高级迁城令，不能迁城。";
$GLOBALS['changeCityPosition']['province_is_full'] = "该州的城池已无空地，请选择其它州迁城。";
$GLOBALS['changeCityPosition']['invalid_target_city'] = "迁城只能迁往无主平地，目标不符合条件，请重新迁城。";


//util
$GLOBALS['MakeEndTime']['year'] = "年";
$GLOBALS['MakeEndTime']['month'] = "月";
$GLOBALS['MakeEndTime']['day'] = "日";

$GLOBALS['MakeTimeLeft']['hour'] = "小时";
$GLOBALS['MakeTimeLeft']['min'] = "分钟";
$GLOBALS['MakeTimeLeft']['sec'] = "秒";

$GLOBALS['checkCityExist']['no_city_info'] = "没有该城池信息。";

$GLOBALS['realLogin']['ip_blocked'] = "你的IP段已经被禁。";

$GLOBALS['doGetCityAllInfo']['no_city_info'] = "没有该城池的信息。";

$GLOBALS['doGetCityResource']['no_city_info'] = "无此城池信息";

$GLOBALS['setCityTax']['invalid_tax'] = "税率不合法。";


//world
$GLOBALS['startWar']['war_is_declared'] = "你们已经处于宣战状态。";

$GLOBALS['createCityFromLand']['only_flatlands_can_build'] = "只有平地才能筑城";
$GLOBALS['createCityFromLand']['target_flatlands_notYours'] = "目标平地不是本城池的附属平地，不能在此处筑城";
$GLOBALS['createCityFromLand']['target_flatlands_in_war'] = "目标平地正在战乱中，不能筑城。";
$GLOBALS['createCityFromLand']['no_army'] = "没有军队驻在此处不能筑城。";
$GLOBALS['createCityFromLand']['no_enough_resource'] = "你所有的驻军携带的资源不足，请带上每种资源及黄金各10000才能筑城";
$GLOBALS['createCityFromLand']['nobility_not_enough'] = "你的爵位不够，筑城失败。当你的爵位晋升为“%s”时，才能统治更多的城池。";

$GLOBALS['addFavourites']['already_in_fav'] = "该目标已被收藏。 ";
$GLOBALS['addFavourites']['fav_is_full'] = "你的收藏目标已达到上限10个，删除其他目标后才能继续收藏。";
$GLOBALS['addFavourites']['succ'] ="收藏成功！你可以在校场的出征界面查看收藏列表。";

$GLOBALS['deleteFavourites']['error_in_del_fav'] = "删除收藏目标出错。";

$GLOBALS['setFavouritesComments']['already_exist'] = "收藏目标不存在。";
$GLOBALS['setFavouritesComments']['succ'] = "修改目标备注成功。";

$GLOBALS['getMoJiaGoods']['complete_quickly']="立即完成";

$GLOBALS['equipArmor']['arm_not_exist']="该件装备不存在";
$GLOBALS['equipArmor']['no_hp_max']="装备已经没有耐久，不能使用，请先修复。";
$GLOBALS['equipArmor']['arm_in_use']="该件装备已经被其他武将使用了";
$GLOBALS['equipArmor']['level']="这件装备需要将领等级达到%d级才能使用。";
$GLOBALS['equipArmor']['hero_state_wrong']="将领不在本城或没有效忠于你。只能给本城内效忠于你的将领换装。";
$GLOBALS['repairArmor']['no_need']="这件装备没有损坏，不需要修理。";
$GLOBALS['repairArmor']['no_gold']="本城黄金不足。";
$GLOBALS['repairArmor']['no_hp_max']="装备已经没有耐久，不能修理，只能修复了。";
$GLOBALS['renovateArmor']['no_need']="这件装备没有损毁，不需要修复。";
$GLOBALS['renovateArmor']['no_money']="你没有足够的元宝，请充值后再修复。";
$GLOBALS['sellArmor']['market_level_low']="市场达到5级才能回收装备。";
$GLOBALS['sellArmor']['nobility_low']="爵位达到“公士”才能回收装备。";
?>