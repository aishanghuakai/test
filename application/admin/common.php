<?php
use think\Db;
use app\util\Strs;
use think\Session;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
//  admin 专用公共文件函数
// 应用公共文件

 function getheaderimg($avatar)
 {
	if( !$avatar )
	{
		return '/static/images/noheader.png';
	}
    return 	$avatar;
 }
 
 function getuserinfo()
 {
	 $id = Session::get('admin_userid');
	 if( $id!==false )
	 {
		 return Db::name("admin")->find($id);
	 }
	 return [];
 }
 
 //头部显示红点数
 function mymessage()
 {
	$userid = Session::get('admin_userid');
	$r =Db::name("message_read")->field("count(*) as num")->where( ["isread"=>1,"type"=>2,"userid"=>$userid] )->find();
    return isset($r["num"])?$r["num"]:0;	
 }
 
 //获取消息是否读取
 function isread($message_id)
 {
	 $userid = Session::get('admin_userid');
	 $r =Db::name("message_read")->field("isread")->where( ["message_id"=>$message_id,"type"=>2,"userid"=>$userid] )->find();
	 return isset($r["isread"])?$r["isread"]:0;
 }
 
 function getrandcolor($id)
 {
	 $colors = array(
	     "0"=>"#44b549",
		 "1"=>"#689cfc",
		 "2"=>"#f85959",
		 "3"=>"#fac249",
		 "4"=>"#3a3f51"
	 );
	 $r = array_rand($colors,1);
	 return isset( $colors[$id] )?$colors[$id]:$colors[$r]; 
 }
 
 function purchaseShow($date,$appid){
	  $current = date("Y-m-d", strtotime("-2 day"));
	  $h =date("H");
	  if($h>=16  && $current==$date)
	  {
		  $appList =["166","135"];
		  $res = Db::name('purchase_details')->where(['date'=>$date,'app_id'=>['in',$appList]])->find();
		  if(empty($res) && in_array($appid,$appList))
		  {
			  return "<span style='font-size:8px;color:red;text-decoration:line-through'>异常</span>";
		  }
	  }
	  return "";
 }
 
 function ctrSepend($start,$end,$appid,$country="all"){
	 $where="";
	 if($country!="all")
	 {
		 $where.=" and country='{$country}'";
	 }
	 $sql="SELECT IFNULL(SUM(spend),0) as spend from  hellowd_adspend_data WHERE target_id IN( 
SELECT advertiser_id  from  hellowd_advertising_account WHERE channel=2 and type=2 and app_id={$appid}) and date>='{$start}' and date<='{$end}' {$where}";
     $row = Db::query($sql);	 
	 return isset($row[0]["spend"])?$row[0]["spend"]:0;
 }
 
 function spendShow($appid)
 {
	 $last = date("Y-m-d", strtotime("-1 day"));
	 $plast = date("Y-m-d", strtotime("-2 day"));
	  $h =date("H");
	  if($h>=17)
	  {
		  $channelList =getplatform("");
		  $str ="";
		  foreach($channelList as $k=>$v)
		  {
			  if(!in_array($k,["all","30","33","34","35","37","40","41"]))
			  {
				  $row1 = Db::name('adspend_data')->where(['date'=>$last,'app_id'=>$appid,"platform_type"=>$k ])->find();
				  $row2 = Db::name('adspend_data')->where(['date'=>$plast,'app_id'=>$appid,"platform_type"=>$k ])->find();
				  if(!empty($row2) && empty($row1))
				  {
					  $str.=$v.",";
				  }
			  }
		  }
		  return $str;		 
	  }
	  return "";
 }
 
 function ChangeTime($date){
	    $time = strtotime($date);
        $time = time() - $time;
        if(is_numeric($time)){  
            $value = array(  
                  "years" => 0, "days" => 0, "hours" => 0,  
                  "minutes" => 0, "seconds" => 0,  
            );  
            if($time >= 31556926){  
                  $value["years"] = floor($time/31556926);  
                  $time = ($time%31556926);
                  $t = $value["years"].'年前';  
            }  
            elseif(31556926 >$time && $time >= 86400){  
                 $value["days"] = floor($time/86400);  
                 $time = ($time%86400);
				 $t = $value["days"].'天前';
            }  
            elseif(86400 > $time && $time >= 3600){  
                 $value["hours"] = floor($time/3600);  
                  $time = ($time%3600);
                  $t = $value["hours"].'小时前';  
            }  
            elseif(3600 > $time && $time >= 60){  
                  $value["minutes"] = floor($time/60);  
                  $time = ($time%60);
                  $t = $value["minutes"].'分钟前';  
            }else{
				if($time<1)
				{
					$t = "刚刚";
				}else{
					$t = $time.'秒前';
				}              
            }   
            return $t;    
        }else{  
            return $date;  
        }  
    }
 
 function getmyallowapps()
 {
   // $userinfo = getuserinfo();
	// $all_where = "1=1";
	// if (!in_array($userinfo["ad_role"], ["super", "publisher", "advertiser"])) {
		// if (!$userinfo['allow_applist']) {
			// exit("You do not have permission to access");
		// }
		// $all_where = "id in({$userinfo['allow_applist']})";
	// }	 
	 $ids =getmylikedata();	
	 $where=" id in(".$ids.")";
	 $apps= Db::name("app")->field("id,app_name,platform,unique_hash,app_base_id,icon_url")->where($where)->order("FIELD(id,{$ids})")->select();
	 if( !empty($apps) )
		{
			foreach($apps as &$vv)
			{
				if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
						$vv["icon_url"] = $row["icon"];
					}
				}
			}
		}
	 return $apps;
 }
 
 function getappid($hash)
 {
	 $r = Db::name("app")->field("id")->where(["unique_hash"=>$hash])->find();
	 if( !empty($r) )
	 {
		 return $r["id"];
	 }
	 return false;
 }
 
 function getdayreten($id,$date)
 {
	$res = Db::name("day_reten")->where(["app_id"=>$id,"date"=>$date ])->find();
	if( empty($res) )
	{
		return "no";
	}
	return $res["val"]."%";
 }
 
 //权限管理
 function permission($rule_id="")
 {
	$admin_userinfo = getuserinfo();
	if( in_array($admin_userinfo["ad_role"],["super"] ) )
	{
		return true;
	}
	$allow_rules = explode(",",$admin_userinfo["ad_rules"] );
    if(!in_array($rule_id,$allow_rules))return false;
    return true;	
 }
 
 //获取用户名
 function getusername($id)
 {
	if($id==0)return "系统管理员";
	$r=Db::name("admin")->field("truename")->find($id);
	return isset($r["truename"])?$r["truename"]:"unknown";
 }
 
 //获取用户角色
 function getuserrole()
 {
	$admin_userinfo = getuserinfo();
    return $admin_userinfo["ad_role"];	
 }
 
 //获取角色名字
 function getrolename($role="")
 {
	 if( $role=="" )
	 {
		$role = getuserrole(); 
	 }
	 $array = [	      
		  "super"=>"超级组",
		  "publisher"=>"变现组",
		  "advertiser"=>"推广组",
		  "producter"=>"产品组",
		  "copartner"=>"合作组",
		  "material"=>"素材组",
		  "financer"=>"财务组"
	 ];
	 return $array[$role];
 }
 //获取列名称
 function getcolumname($appid="")
 {
	 $res = Db::name("platform")->field("id,platform_id,platform_name")->where("type=2 and status=1 and app_id={$appid}")->order("id asc")->select();
	 return $res;
 }
 //获取产品图片
 function getappimg()
 {
	 $appid=  getcache("select_app");
	if( !$appid )exit("不合法操作");
	$r = Db::name("app")->field("icon_url")->find($appid);
	return $r["icon_url"];
 }
 
 function get_app_info()
 {
	  $appid=  getcache("select_app");
	if( !$appid )exit("不合法操作");
	$r = Db::name("app")->find($appid);
	if(  $r["id"]>154 )
	{
		if( $r["app_base_id"] )
		{
			$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
			$r["app_name"] = $row["name"].' - '.$r["platform"];
			$r["icon_url"] = $row["icon"];
		}
	}
	return $r;
 }
 
 function getplatformimg($tag)
 {
	//平台类型1 Mob 2 Unity 3 applovin 4Vungle 5 admob 6 facebook 7 ironSource 8 Chartboost
	$imgs= array(
	    "1"=>"/static/img/app/Mobvista.png", 
		"2"=>"/static/img/app/unity.png",
		"3"=>"/static/img/app/applovin.png",
		"4"=>"/static/img/app/vungle.png",
		"5"=>"/static/img/app/admob.png",
		"6"=>"/static/img/app/facebook.png",
		"7"=>"/static/img/app/ironSource.png",
		"8"=>"/static/img/app/chartboost.png",
		"9"=>"/static/img/app/tapjoy.png",
		"30"=>"/static/img/app/upltv.png",
		"31"=>"/static/img/app/adcolony.png",
		"32"=>"/static/img/app/toutiao.png",
		"33"=>"/static/img/app/yomob.png",
		"34"=>"/static/img/app/sigmob.png",
		"35"=>"/static/img/app/gdt.png",
		"36"=>"/static/img/app/tiktok.png",
		"37"=>"/static/img/app/mopub.png",
		"38"=>"Snapchat",
		"39"=>"ASM",
		"40"=>"Inmobi",
		"41"=>"Fyber",
		"42"=> "KuaiShou"
	 );
	 if($tag=="all")
	 {
		 return $imgs;
	 }
	 return $imgs[$tag];
 }
 
 //判断早上，中午，下午
 function daytime()
 {
	 $h = date("G");
	 if ($h<11) return '早上好';
	else if ($h<13) return'中午好';
	else if ($h<18) return'下午好';
	else return '晚上好';
 }
 //获取日期对应的星期
 function getweekday($date)
 {
	 $weekarray=array("日","一","二","三","四","五","六");
     return $weekarray[date("w",strtotime($date))];
 } 
 
 function get_country_json(){
	 $path = dirname(__FILE__)."/country.json";
	$json = file_get_contents($path);
	$data = json_decode($json,true);
	$result =[];
	if(!empty($data))
	{
		foreach($data as $v)
		{
			$result[$v["short"]] = $v["name"];
		}
	}
	return $result;
 }
 function admincountry()
 {
	return get_country_json();
	return array(
	   "all"=>"全部",
	   "US"=>"美国",
	   "TW"=>"台湾",
	   "HK"=>"香港",
	   "JP"=>"日本",
	   "KR"=>"韩国",
	   "DE"=>"德国",
	   "FR"=>"法国",
	   "CN"=>"中国",
	   "RU"=>"俄罗斯",
	   "CA"=>"加拿大",
	   "GB"=>"英国",
	   "TH"=>"泰国",
	   "BR"=>"巴西",
	   "TR"=>"土耳其",
	   "VN"=>"越南",
	   "IN"=>"印度",
	   "MY"=>"马来西亚",
	   "ID"=>"印度尼西亚",
	   "IT"=>"意大利",
	   "ES"=>"西班牙",
	   "SE"=>"瑞典",
	   "CH"=>"瑞士",
	   "MO"=>"澳门",
	   "AU"=>"澳大利亚",
	   "NO"=>"挪威",
	   "DK"=>"丹麦",
	   "FI"=>"芬兰",
	   "NL"=>"荷兰",
	   "PH"=>"菲律宾",
	   "NG"=>"尼日利亚",
	   "PK"=>"巴基斯坦",
	   "MX"=>"墨西哥",
	   "BD"=>"孟加拉",
	   "SG"=>"新加坡",
	   "PT"=>"葡萄牙",
	   "ZA"=>"南非",
	   "IE"=>"爱尔兰",
	   "AE"=>"阿拉伯联合酋长国",
	   "AF"=>"阿富汗",
	   "EG"=>"埃及",
	   "IL"=>"以色列",
	   "JO"=>"约旦",
	   "KP"=>"朝鲜",
	   "AR"=>"阿根廷",
	   "CL"=>"智利",
	   "BT"=>"不丹",
	   "GR"=>"希腊",
	   "IS"=>"冰岛",
	   "MM"=>"缅甸",
	   "BL"=>"巴勒斯坦",
	   "UG"=>"乌干达",
	   "IQ"=>"伊拉克",
	   "IQ"=>"伊拉克",
	   "RO"=>"罗马尼亚",
	   "BE"=>"比利时",
	   "KH"=>"柬埔寨",
	   "PL"=>"波兰",
	   "HU"=>"匈牙利",
	   "UA"=>"乌克兰",
	   "NZ"=>"新西兰",
	   "SA"=>"沙特阿拉伯",
	   "AT"=>"奥地利",
	   "BY"=>"白俄罗斯",
	   "LK"=>"斯里兰卡",
	   "LT"=>"立陶宛",
	   "KZ"=>"哈萨克斯坦",
	   "BG"=>"保加利亚",
	   "BD"=>"孟加拉国",
	   "NE"=>"尼日尔",
	   "LA"=>"老挝"
	);
 }
 //是否有备注信息
 function isshowtips($appid,$date,$tag)
 {
     $id = Session::get('admin_userid');
	 
	 if( $id=="36" )
	 {
		 return 0;
	 }
	
	$r = Db::name("app_remark")->where( ["app_id"=>$appid,"date"=>$date,"tag"=>$tag ] )->find();
	
	if(!empty($r) )
	{
		//return "<p>{$r['title']}<p><p>".str_replace(array("\r\n", "\r", "\n"), "",$r["content"] )."</p>";
		//$content = str_replace(array("\r\n", "\r", "\n"), "",$r["content"] );
		return "<p>{$r['title']}<p>".htmlspecialchars($r["content"]);
	}
	return 0;
 }
 
 //留存数据
 function retenClass($date,$day)
 {
	 $yesday =strtotime("-1 day");
	 $c = strtotime($date)+(86400*$day);
	 if( $c<=$yesday )
	 {
		 return "reten".$day;
	 }
	 return "";
 }
 
 function retenClass1($val)
 {
	 if( $val>90 && $val<=100  )
	 {
		 return 1;
	 }elseif( $val>80 && $val<=90 )
	 {
		 return 2;
	 }
	 elseif( $val>70 && $val<=80 )
	 {
		 return 3;
	 }
	 elseif( $val>60 && $val<=70 )
	 {
		 return 4;
	 }
	 elseif( $val>50 && $val<=60 )
	 {
		 return 5;
	 }
	 elseif( $val>40 && $val<=50 )
	 {
		 return 6;
	 }
	 elseif( $val>30 && $val<=40 )
	 {
		 return 7;
	 }
	 elseif( $val>20 && $val<=30 )
	 {
		 return 8;
	 }
	 elseif( $val>10 && $val<=20 )
	 {
		 return 9;
	 }
	 elseif( $val>0 && $val<=10 )
	 {
		 return 10;
	 }else{
		 return 0;
	 }
 }
 
 function getadmincountryname($tag)
 {
	 $res = admincountry();
	 return isset( $res[$tag] )?$res[$tag]:$tag;
 }
 //应用管理时间
 function getadvertime( $time )
 {
	return date("m/d H:i",strtotime($time)); 
 }
 
 //获取接口URL
 function getapiurl($id)
 {
	$url = getdomainname()."/advertise/advlist?appid={$id}";
    return $url;	
 }
 
 //密码加密
 function hew_md5($str)
 {
	$pre = "*^!@fdasDSADASd";
	return md5(md5($str).$pre);
 }
 //获取国家名称
 function getcountryname($key)
 {
	 $allcountrys = getallcountry();
	 if( !empty($allcountrys) )
	 {
		 foreach( $allcountrys as $vv )
		 {
			 if( $vv["code"] == $key )
			 {
				 return $vv["name"];
			 }
		 }
	 }
	 return "";
 }
 //获取国家
 function getallcountry()
 {
	 $options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	$key = "country";
	$res = cache($key);
	 if( empty($res) )
	 {
		 $res =  Db::name("country")->select();
		 cache($key,$res);
	 }
	return $res;
 }
 
 //获取缓存
 function getcache($key)
 {
	 $options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = $key.Session::get('admin_userid');
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
		return "";
 }
 function getupltvids()
 {
	$unit_ids =array(
	    "77"=>"'2000186346977172_2163629353966203','2000186346977172_2163631157299356','2000186346977172_2163630907299381','2000186346977172_2163631017299370','2000186346977172_2163631083966030','2000186346977172_2161214377541034','2000186346977172_2179312555731216','2000186346977172_2179312435731228','2000186346977172_2174066506255821','2000186346977172_2000201066975700','2000186346977172_2186745954987876','2000186346977172_2193585484303923'",
		"66"=>"'159499268084463_260996104601445','159499268084463_261001291267593','159499268084463_261001521267570','159499268084463_261001464600909','159499268084463_267662947268094','159499268084463_284050938962628','159499268084463_284053835629005'",
		"31"=>"'421636544919960_644165049333774','421636544919960_644167192666893','421636544919960_645764275840518','421636544919960_645764055840540','421636544919960_645764115840534','421636544919960_645764195840526','421636544919960_645760189174260'",
		"68"=>"'567407280311928_716666568719331','567407280311928_716666482052673','567407280311928_716666918719296','567407280311928_716667082052613','567407280311928_716667212052600','567407280311928_716738348712153'",
		"91"=>"'2033122236908398_2062911953929426','2033122236908398_2062911867262768','2033122236908398_2062912820596006','2033122236908398_2062912880596000','2033122236908398_2062912947262660','2033122236908398_2075801135973841','2033122236908398_2075801049307183','2033122236908398_2075801435973811','2033122236908398_2068102096743745'",
		"93"=>"'2033122236908398_2072495836304371','2033122236908398_2107945469426074','2033122236908398_2072495926304362','2033122236908398_2072496016304353','2033122236908398_2072496086304346','2033122236908398_2057155771171711','2033122236908398_2072495386304416','2033122236908398_2072496486304306','2033122236908398_2072495456304409','2033122236908398_2072495559637732','2033122236908398_2072495592971062','2033122236908398_2072496202971001'",
		"94"=>"'247052485982890_257063704981768','247052485982890_257063671648438','247052485982890_257063618315110','247052485982890_257063528315119','247052485982890_257061558315316','247052485982890_257060871648718','247052485982890_257061104982028'",
		"85"=>"'572769599809326_606240836462202','572769599809326_606240943128858','572769599809326_606241159795503','572769599809326_606241099795509','572769599809326_606241033128849','572769599809326_606241833128769','572769599809326_606241759795443'",
		"52"=>"'145198636252217_357542768351135','145198636252217_357542515017827','145198636252217_357543095017769','145198636252217_357542595017819','145198636252217_357542665017812','2000186346977172_2171610266501445','2000186346977172_2172054866456985','145198636252217_400338097404935','145198636252217_400339204071491','145198636252217_431970717575006','145198636252217_431380517634026','145198636252217_431380594300685','145198636252217_431380667634011','145198636252217_430338284404916'",
	    "114"=>"'995528667313289_1027081290824693','995528667313289_1018771101655712','995528667313289_1047490962117059','995528667313289_1030568363809319'",
		"109"=>"'363644127735416_410884053011423','363644127735416_410883709678124'",
		"117"=>"'245305093058730_274123846843521','245305093058730_274125633510009'",
		"127"=>"'721755674906542_723603851388391','721755674906542_723603718055071','721755674906542_723602838055159','721755674906542_723602078055235','721755674906542_721756491573127'",
		"112"=>"'589597201479764_632290357210448','589597201479764_632290227210461','589597201479764_632290120543805','589597201479764_632290460543771','589597201479764_617897358649748','589597201479764_621263628313121'"
	);
    return $unit_ids;	
 }
 //获取upltv 在facebook的数据
 function getupltvfacebook($appid,$start,$end,$country="all",$adtype="")
 {
	$unit_ids = getupltvids();
	if( !isset($unit_ids[$appid]) )
	{
		return ["impression"=>0,"click"=>0,"revenue"=>"0.00"];
	}
	$where= "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}' and platform=6 and unit_id in({$unit_ids[$appid]})";
	if( $country!="" && $country!="all" )
	{
		$where.=" and country='{$country}'";
	}
	if( $adtype!="" )
	{
		$where.=" and adtype='{$adtype}'";
	}
	$res =Db::name("adcash_data")->field("sum(impression) as impression,sum(click) as click,sum(revenue) as revenue")->where($where)->find();
	$impression = isset($res["impression"]) && !empty($res["impression"])?$res["impression"]:0;
	$click = isset($res["click"]) && !empty($res["click"])?$res["click"]:0;
	$revenue = isset($res["revenue"]) && !empty($res["revenue"])?$res["revenue"]:0;
	return ["impression"=>$impression,"click"=>$click,"revenue"=>$revenue];
 }
 
 function getwhereupltv($appid,$start,$end,$where)
 {
	$unit_ids = getupltvids();
	if( !isset($unit_ids[$appid]) )
	{
		return ["impression"=>0,"click"=>0,"revenue"=>"0.00"];
	}
    $where= "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}' and platform=6 and unit_id in({$unit_ids[$appid]}) {$where}";
	$res =Db::name("adcash_data")->field("sum(impression) as impression,sum(click) as click,sum(revenue) as revenue")->where($where)->find();
	$impression = isset($res["impression"]) && !empty($res["impression"])?$res["impression"]:0;
	$click = isset($res["click"]) && !empty($res["click"])?$res["click"]:0;
	$revenue = isset($res["revenue"]) && !empty($res["revenue"])?$res["revenue"]:0;
	return ["impression"=>$impression,"click"=>$click,"revenue"=>$revenue];
 }
 
 //设置缓存
 function setcache($key,$data)
 {
	  $options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	cache( $key.Session::get('admin_userid'),$data);
	return true;
 }
 
  function getmylikedata()
	{
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".Session::get('admin_userid');
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
       return "0";		
	}
	
	//设置
  function setmylikedata($data)
  {
	  $options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".Session::get('admin_userid');
		cache( $key,$data);
		return true;
  }
 
 function getDateFromRange($startdate, $enddate){

    $stimestamp = strtotime($startdate);
    $etimestamp = strtotime($enddate);

    // 计算日期段内有多少天
    $days = ($etimestamp-$stimestamp)/86400+1;

    // 保存每天日期
    $date = array();

    for($i=0; $i<$days; $i++){
        $date[] = date('Y-m-d', $stimestamp+(86400*$i));
    }

    return $date;
}

 function adcalculate($header,$body,$type="")
 {
	if( !isset($body) || $body=="" || $body<=0 )
	{
		return "0";
	}
	if( !isset($header) || $header=="" || $header<=0 )
	{
		return "0";
	}
	$num = $header/$body;
	if($type=="1")
	{
		return "$".round($num,2);
	}
	if( $type=="2" )
	{
		return "$".number_format($num*1000,2);
	}
	if($type=="3")
	{
		return number_format($num,2);
	}
	return round($num,2)*100 ."%";
 }
 
 function getapp_name($appid="")
 {
	if( $appid=="" )
	{
	  $appid=  getcache("select_app");	
	}
	if( !$appid )exit("不合法操作");
	$r = Db::name("app")->field("app_name,app_base_id,id,platform")->find($appid);
	if(  $r["id"]>154 )
	{
		if( $r["app_base_id"] )
		{
			$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
			$r["app_name"] = $row["name"].' - '.$r["platform"];
		}
	}
	return $r["app_name"];
 }
 
 //获取合作方收益分成比例
 function getrevenue_rate($appid)
 {
	$res = Db::name("revenue_rate")->field("revenue_rate")->where( ["app_id"=>$appid] )->find();
	if( !empty($res) )
	{
		return $res["revenue_rate"];
	}
    return 0;	
 }
 
 //获取应用名称
 function getapp($platform)
 {
	$options = [
		'type'   => 'File',//memcache File
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	$key = "app_name_{$platform}";
	//cache($key, NULL);
	$res = cache($key);
	if( empty($res) )
	{
		if( $platform==2 || $platform==3 || $platform==6 || $platform==5 )
		   {
			 $res =  Db::query( " select app_id,app_name from hellowd_adcash_data where  app_name!='' and platform={$platform} group by app_id ");
		   }else{
			    $platform=4;
				$res =  Db::name("adcash_appname")->where("status=1 and platform={$platform}")->select();
		   }			
		 cache($key,$res);
	}
    return $res;
 }
 //获取用户名
 function getadminusername()
 {
	 return Session::get('username');
 }
  function getFullADType($t) {
        $r = array('int' => "插屏广告", 'rew' => "激励视频", 'nat' => "原生广告","native"=>"原生广告","ban"=>"banner广告");
		return isset($r[$t])?$r[$t]:"未知类型";
    }

    function getFullADName($t) {
        return array(
            'fb1' => "Facebook_1",
            'fb2' => "Facebook_2",
            'fb3' => "Facebook_3",
            'am1' => "Admob_1",
            'am2' => "Admob_2",
            'am3' => "Admob_3",
            'al' => "AppLovin",
            'un' => "Unity ads",
            'vu' => "Vungle",
            'mv' => "MobVista")[$t];
    }
 function admin_array_sort($arr,$keys,$orderby='asc'){
	  $keysvalue = $new_array = array();
	  foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	  }
	  if($orderby== 'asc'){
		asort($keysvalue);
	  }else{
		arsort($keysvalue);
	  }
	  reset($keysvalue);
	  foreach ($keysvalue as $k=>$v){
		$new_array[] = $arr[$k];
	  }
	  return $new_array;
   }
