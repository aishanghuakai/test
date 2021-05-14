<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
use think\Session;
header('Access-Control-Allow-Origin:http://analytics.gamebrain.io');
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
class Analytics
{
   	private $config=[
	    // 数据库类型
		'type'        => 'mysql',
		// 数据库连接DSN配置
		'dsn'         => '',
		// 服务器地址
		'hostname'    => '124.156.109.75',
		// 数据库名
		'database'    => 'analytics',
		// 数据库用户名
		'username'    => 'root',
		// 数据库密码
		'password'    => 'VXCxvff*&DS@#$#CVXse',
		// 数据库连接端口
		'hostport'    => '3306',
		// 数据库连接参数
		'params'      => [],
		// 数据库编码默认采用utf8
		'charset'     => 'utf8mb4',
		// 数据库表前缀
		'prefix'      => 'hellowd_',
		// 数据库调试模式
		'debug'       => false,
	];
	
	//获取所有国家
	public function getallcountry($key="")
	{
		$out_put =[];
		$res = array(
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
		   "TR"=>"土尔其",
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
		   "SG"=>"新加坡",
		   "PT"=>"葡萄牙"
	   );
	   if( $key!="" )
	   {
		   return isset($res[$key])?$res[$key]:"";
	   }
	   foreach($res as $key=>$vv)
	   {
		   $out_put[] = ["value"=>$key,"label"=>$vv];
	   }
	   return $out_put;
	}
	
	public function getallapps($userid="")
	{
		 if( !$userid )
		 {
			 return [];
		 }
		 $ids =$this->getmylikedata($userid);		
		 $where=" id in(".$ids.")";
		 $apps= Db::name("app")->where($where)->order("FIELD(id,{$ids})")->select();
		 if( !empty($apps) )
		 {
			 foreach( $apps as &$vv )
			 {
				 $vv["icon_url"] ="http://cnf.mideoshow.com".$vv["icon_url"];
			 }
		 }
		 return $apps;			
	}
	
	//获取设备属性
	public function getAppProperty($appid="",$platform="")
	{
		if( !$appid )
		 {
			 return [];
		 }
		$model = Db::connect($this->config);
		$appVersion =$model->query("select id as value,app_version as label from hellowd_ana_app_version where app_id={$appid}");
		$language = $model->query("select id as value,language as label from hellowd_ana_language");
		$deviceModel = $model->query("select id as value,device_model as label from hellowd_ana_device_model where device_type='{$platform}'");
	    $platformVersion = $model->query("select id as value,platform_version as label from hellowd_ana_platform_version where device_type='{$platform}'");
		return ["appVersion"=>$appVersion,"language"=>$language,"deviceModel"=>$deviceModel,"platformVersion"=>$platformVersion ];
	}
	
	//检查登录
	public function getlogin()
	{
		
		//pjI5yxzN7F9I
		 $userid = Session::get('admin_userid');
		 $username = Session::get('truename');
		 if( $userid )
		 {
			return ["username"=>substr_text($username,0,1),"apps"=>$this->getallapps($userid) ];
		 }
		 return [];
	}
	
	public function main($appid="",$start="",$end="",$country="",$channel="",$property="")
	{
		if( !$appid )
		{
			return [];
		}
		$day = count($this->getDateFromRange($start, $end));
		$before_start = $this->getStartTime($start,$day);
		$before_end = $this->getStartTime($end,$day);
		$propertywhere = $this->getPropertyWhere($property);
		$NewUser = $this->getNewUser($appid,$start,$end,$country,$channel,$propertywhere);
		$beforeNewUser = $this->getNewUser($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$Origin = $this->getNewUser($appid,$start,$end,$country,"organic",$propertywhere);
		$BeforeOrigin = $this->getNewUser($appid,$before_start,$before_end,$country,"organic",$propertywhere);
		$Origin_rate = $NewUser>0?round($Origin*100/$NewUser,2):0;
		$Dau =$this->getDau($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeDau = $this->getDau($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$Session =$this->getSession($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeSession =$this->getSession($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$One_session_length = $Session["Session_num"]>0?round($Session["Session_length"]/$Session["Session_num"],2):0;
		$Session["Session_num"] = $Dau>0?round($Session["Session_num"]/$Dau,2):0;
		$Session["Session_length"] = $Dau>0?round($Session["Session_length"]/$Dau,2):0;
		$BeforeOne_session_length = $BeforeSession["Session_num"]>0?round($BeforeSession["Session_length"]/$BeforeSession["Session_num"],2):0;
		$BeforeSession["Session_num"] = $BeforeDau>0?round($BeforeSession["Session_num"]/$BeforeDau,2):0;
		$BeforeSession["Session_length"] = $BeforeDau>0?round($BeforeSession["Session_length"]/$BeforeDau,2):0;
		$DayDau = $this->getDayActiveUsers($appid,$start,$end,$country,$channel,$propertywhere);
		$DayNewUsers =$this->getDayNewUsers($appid,$start,$end,$country,$channel,$propertywhere);
		$retenData = $this->reten($appid,$start,$end,$country,$channel,$property);
		$BeforeretenData = $this->reten($appid,$before_start,$before_end,$country,$channel,$property);
		$avgsession =$this->getAvgSession($appid,$start,$end,$country,$channel,$propertywhere);
		$array = [ 
		      "NewUser"=>number_format($NewUser),
			  "NewUserText"=>$this->getRateText($NewUser,$beforeNewUser,$day),
			  "Dau"=>number_format($Dau),
			  "DauText"=>$this->getRateText($Dau,$BeforeDau,$day),
			  "DayDau"=>$DayDau,
			  "DayNewUsers"=>$DayNewUsers["data"],
			  "avgsession"=>$avgsession,
			  "Origin"=>$Origin,
			  "OriginText"=>$this->getRateText($Origin,$BeforeOrigin,$day),
			  "Reten"=>$retenData["avgReten_1"],
			  "RetenText"=>$this->getRateText($retenData["avgReten_1"],$BeforeretenData["avgReten_1"],$day),
			  "One_session_length"=>$One_session_length,
			  "One_session_length_Text"=>$this->getRateText($One_session_length,$BeforeOne_session_length,$day),
			  "Session_num_Text"=>$this->getRateText($Session["Session_num"],$BeforeSession["Session_num"],$day),
			  "Session_length_Text"=>$this->getRateText($Session["Session_length"],$BeforeSession["Session_length"],$day),
			  "Origin_rate"=>$Origin_rate,
			  "retenData"=>$retenData["data"] ];
		return array_merge($array,$Session);
	}
	
	private function getPropertyWhere($property)
	{
		if( empty($property) )
		{
			return "";
		}
		$where="";
		foreach( $property as $k=>$v )
		{
			if( !empty($v) )
			{
				$str = implode(",",$v);
				$where.=" and {$k} in({$str})";
			}
		}
		return $where;
	}
		
	private function getRateText($current,$before,$day)
	{
		$t = $current-$before;
		$rate = $before<=0?$t*100:round($t*100/$before,2);
		$class = $t<0?"down_active":"up_active";
		$type = $t<0?"md-arrow-dropdown":"md-arrow-dropup";
		return ["class"=>$class,"type"=>$type,"day"=>$day,"rate"=>$rate];
	}
	
	private function getStartTime($date,$day)
	{
		$current_timestamp = strtotime($date);
		$rtime =$current_timestamp-($day*86400);
		return date("Y-m-d",$rtime);
	}
	//报告
	public function report($appid="",$start="",$end="",$country="",$channel="",$property="")
	{
		if( !$appid )
		{
			return [];
		}
		$dates = $this->getDateFromRange($start, $end);
		$data=[];
		$standResult =[];
		$model = Db::connect($this->config);
		$propertywhere = $this->getPropertyWhere($property);
		foreach($dates as $k=>$vv)
		{
			$res = $this->getdayreten($model,$vv,$appid,$country,$channel,$propertywhere);
			$data[$k]["new_users"] = $this->getNewUser($appid,$vv,$vv,$country,$channel,$propertywhere);
			$dau = $this->getDau($appid,$vv,$vv,$country,$channel);
			$data[$k]["Origin"] = $this->getNewUser($appid,$vv,$vv,$country,"organic",$propertywhere);
			$Session =$this->getSession($appid,$vv,$vv,$country,$channel,$propertywhere);
			$data[$k]["date"] = $vv;
			$standResult[$k]=array_merge(["date"=>$vv],$this->standardEvent($appid,$vv,$vv,$country,$channel,"dau","all",$propertywhere) );
			$data[$k]["dau"] = $dau;
			$data[$k]["length"] =$Session["Session_num"]>0?round($Session["Session_length"]/$Session["Session_num"],2):0; 
			$data[$k]["num"] = $dau>0?round($Session["Session_num"]/$dau,2):0;
			$data[$k]["reten_1"] = $res["reten_1"];
		}
		return ["data"=>$data,"standResult"=>$standResult ];
	}
	//标准事件报告
	public function standReport($appid="",$start="",$end="",$country="",$channel="",$fiter="dau",$adtype="all",$property="")
	{
		if( !$appid )
		{
			return [];
		}
		$standResult =[];
		$dates = $this->getDateFromRange($start, $end);
		$propertywhere = $this->getPropertyWhere($property);
		foreach($dates as $k=>$vv)
		{
			 $standResult[$k]=array_merge(["date"=>$vv],$this->standardEvent($appid,$vv,$vv,$country,$channel,$fiter,$adtype,$propertywhere) );
		}
		return $standResult;
	}
	
	private function standardEvent($appid="",$start="",$end="",$country="",$channel="",$fiter="dau",$adtype="all",$propertywhere)
	{
		$model = Db::connect($this->config);
		$dau = $this->getDau($appid,$start,$end,$country,$channel,$propertywhere);
		//各地区人均局数  field =PlayCountTotal
		$PlayCountTotal = $this->getStandEventNum($model,$appid,$start,$end,$country,$channel,"PlayCountTotal",$propertywhere);
		//单个视频点的人均观看次数 field =VideoWatchtime
		$VideoWatchtime = $this->getStandEventNum($model,$appid,$start,$end,$country,$channel,"VideoWatchtime",$propertywhere);
		if( $fiter=="real" )
		{
			$AvgPlayCount = $PlayCountTotal["UserNum"]<=0?0:round($PlayCountTotal["evenNum"]/$PlayCountTotal["UserNum"],2);
			$AvgVideoWatchtime = $VideoWatchtime["UserNum"]<=0?0:round($VideoWatchtime["evenNum"]/$VideoWatchtime["UserNum"],2);
		}else{
			$AvgPlayCount = $dau<=0?0:round($PlayCountTotal["evenNum"]/$dau,2);			
			$AvgVideoWatchtime = $dau<=0?0:round($VideoWatchtime["evenNum"]/$dau,2);
		}		
		//视频渗透率 field=VideoWatchtime,InterWatchtime
	    if( $adtype=="all" )
		{
			$adtype = ["VideoWatchtime","InterWatchtime"];
		}
		$VideoPenetrate=$this->getStandEventNum($model,$appid,$start,$end,$country,$channel,$adtype,$propertywhere);
		$VideoPenetrate = $dau<=0?0:round($VideoPenetrate["UserNum"]*100/$dau,2);
		//视频损失率
		$VideoLossRate =round(100-$VideoPenetrate,2);
		//重要变现点击率 field=ReviveVideo
		$ReviveVideo = $this->getStandEventNum($model,$appid,$start,$end,$country,$channel,"ReviveVideo",$propertywhere);
		$ReviveVideoCtr =$dau<=0?0:round($ReviveVideo["UserNum"]*100/$dau,2); 
		//内购点击率 field=Noads
		$Noads = $this->getStandEventNum($model,$appid,$start,$end,$country,$channel,"Noads",$propertywhere);
		$NoadsCtr =$dau<=0?0:round($Noads["UserNum"]*100/$dau,2); 
		
		return ["AvgPlayCount"=>$AvgPlayCount,"ReviveVideoCtr"=>$ReviveVideoCtr,"NoadsCtr"=>$NoadsCtr,"AvgVideoWatchtime"=>$AvgVideoWatchtime,"VideoPenetrate"=>$VideoPenetrate,"VideoLossRate"=>$VideoLossRate];
	}
	
	private function getStandEventNum($model,$appid="",$start="",$end="",$country="",$channel="",$category="",$propertywhere="")
	{
		$where="app_id={$appid} and date>='{$start}' and date<='{$end}'";
		if( is_array($category) )
		{
			$str="";
			array_map(function($a)use(&$str){
				return $str.="'{$a}',";
			},$category);
			$str = rtrim($str,",");
			$where.=" and category in ({$str})";
		}else{
			$where.=" and category='{$category}'";
		}		
		$evenNum=0;
		$UserNum=0;
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$r = $model->query("select count(*) as num from hellowd_ana_eventdata where {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			$evenNum = (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:"0";
		}
		$s =$model->query("select count(*) as num  from ( select dv_id from hellowd_ana_eventdata where {$where} GROUP BY dv_id ) c");
		if( isset($s[0]) && !empty($s[0]) )
		{
			$UserNum = (isset($s[0]["num"]) && $s[0]["num"])?$s[0]["num"]:"0";
		}
		return ["evenNum"=>$evenNum,"UserNum"=>$UserNum];
	}
	
	//自然量
	public function origin($appid="",$start="",$end="",$country="",$channel="",$property=""){
		if( !$appid )
		{
			return [];
		}
		$day = count($this->getDateFromRange($start, $end));
		$before_start = $this->getStartTime($start,$day);
		$before_end = $this->getStartTime($end,$day);
		$propertywhere = $this->getPropertyWhere($property);
		$NewUser = $this->getNewUser($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeNewUser = $this->getNewUser($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$Origin = $this->getNewUser($appid,$start,$end,$country,"organic",$propertywhere);
		$BeforeOrigin = $this->getNewUser($appid,$before_start,$before_end,$country,"organic",$propertywhere);
		$Origin_rate = $NewUser>0?round($Origin*100/$NewUser,2):0;
		$BeforeOrigin_rate = $BeforeNewUser>0?round($BeforeOrigin*100/$BeforeNewUser,2):0;
		$dates = $this->getDateFromRange($start, $end);
		$data=[];
		foreach($dates as $k=>$vv)
		{
			 $data[$k]["date"] =$vv;
			 $data[$k]["NewUser"] = $this->getNewUser($appid,$vv,$vv,$country,$channel,$propertywhere);
		     $data[$k]["Origin"]=$this->getNewUser($appid,$vv,$vv,$country,"organic",$propertywhere);
		     $data[$k]["Origin_rate"] =$data[$k]["NewUser"]>0?round($data[$k]["Origin"]*100/$data[$k]["NewUser"],2)."%":"0%";			 
		}
		return array_merge(array(
		    "NewUserText"=>$this->getRateText($NewUser,$BeforeNewUser,$day),
			"OriginText"=>$this->getRateText($Origin,$BeforeOrigin,$day),
			"Origin_rate_Text"=>$this->getRateText($Origin_rate,$BeforeOrigin_rate,$day),
		),["NewUser"=>$NewUser,"Origin"=>$Origin,"Origin_rate"=>$Origin_rate,"list"=>$data]);
	}
	
	function getDateFromRange($startdate, $enddate)
	{

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
	//获取新增
	private function getNewUser($appid,$start,$end,$country,$channel,$propertywhere="")
	{
		$where="";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$r =Db::connect($this->config)->query(" select count(*) as num from hellowd_ana_appnewuser where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			return (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:0;
		}
		return 0;
	}
	
	//获取日活
	private function getDau($appid,$start,$end,$country,$channel,$propertywhere="")
	{
		$where="";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$r = Db::connect($this->config)->query("select count(*) as num from hellowd_ana_applanuch where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			return (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:"0";
		}
		return "0";
	}
	
	public function active_users($appid="",$start="",$end="",$country="",$channel="",$groupField="",$property="")
	{
		if(!$appid)
		{
			return [];
		}
		$day = count($this->getDateFromRange($start, $end));
		$before_start = $this->getStartTime($start,$day);
		$before_end = $this->getStartTime($end,$day);
		$propertywhere = $this->getPropertyWhere($property);
		$DauNum =$this->getDau($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeDauNum = $this->getDau($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$NewUser = $this->getNewUser($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeNewUser = $this->getNewUser($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$avgDauNum = $NewUser>0?round($DauNum/$NewUser,2):"0";
		$BeforeavgDauNum = $BeforeNewUser>0?round($BeforeDauNum/$BeforeNewUser,2):"0";
		$DayDau = $this->groupActiveUsers($appid,$start,$end,$country,$channel,$groupField,$propertywhere);
		$Session =$this->getSession($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeSession =$this->getSession($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$Session_length = $DauNum>0?round($Session["Session_length"]/$DauNum,2):0;
		$BeforeSession_length = $BeforeDauNum>0?round($BeforeSession["Session_length"]/$BeforeDauNum,2):0;
		return array_merge(array(
		    "NewUserText"=>$this->getRateText($NewUser,$BeforeNewUser,$day),
            "DauText"=>$this->getRateText($DauNum,$BeforeDauNum,$day),
			"avgDauText"=>$this->getRateText($avgDauNum,$BeforeavgDauNum,$day),
            "Session_length_Text"=>$this->getRateText($Session_length,$BeforeSession_length,$day),			
		),["dauNum"=>number_format($DauNum),"Session_length"=>$Session_length,"Independent"=>number_format($NewUser),"avgDauNum"=>$avgDauNum,"DayDau"=>$DayDau]);
	}
	
	private function groupActiveUsers($appid,$start,$end,$country,$channel,$groupField,$propertywhere)
	{
		$data=[];
        $columns=["日期"];
		$table_data =[];
		$dates = $this->getDateFromRange($start, $end);
		foreach($dates as $k=>$vv)
		{
			$res = $this->geteventData($appid,$vv,$vv,$country,$channel,"","","","active_users",$groupField,$propertywhere);
			$result = $this->getGroupByData($res,"active_users",$groupField);
			$columns = array_merge($columns,array_keys($result) );
			$result["日期"] =$vv;
			$data[$k] = $result;
			$table_data[$k] =["date"=>$vv,"val"=>$this->getDau($appid,$vv,$vv,$country,$channel,$propertywhere) ]; 
		}
		if( !in_array($groupField,["groupCountry","groupVersion","groupChannel"]) )
		{
			
			$columns=array("日期","活跃用户");
		}
		return ["data"=>$data,"table_data"=>$table_data,"columns"=>array_values(array_unique($columns))];
	}
	
	//新增用户
	public function new_users($appid="",$start="",$end="",$country="",$channel="",$groupField="",$property="")
	{
		if(!$appid)
		{
			return [];
		}
		$day = count($this->getDateFromRange($start, $end));
		$before_start = $this->getStartTime($start,$day);
		$before_end = $this->getStartTime($end,$day);
		$propertywhere = $this->getPropertyWhere($property);
		$NewUser = $this->getNewUser($appid,$start,$end,$country,$channel,$propertywhere);
		$BeforeNewUser = $this->getNewUser($appid,$before_start,$before_end,$country,$channel,$propertywhere);
		$res =$this->getDayNewUsers($appid,$start,$end,$country,$channel,$propertywhere);
		$data = $this->groupNewUsers($appid,$start,$end,$country,$channel,$groupField,$propertywhere);
		$DayAvgNewUser = ceil($NewUser/$res["day"]);
		$beforeDayAvgNewUser = ceil($BeforeNewUser/$day);
		$DayCountry =$this->countryNewUsers($appid,$start,$end,$country,$channel,$propertywhere);
		return array_merge(array(
		     "NewUserText"=>$this->getRateText($NewUser,$BeforeNewUser,$day),
             "AvgNewUserText"=>$this->getRateText($DayAvgNewUser,$beforeDayAvgNewUser,$day),
		),["NewUsers"=>$NewUser,"data"=>$data,"DayAvgNewUser"=>$DayAvgNewUser,"DayNewUser"=>$res["data"],"DayCountry"=>$DayCountry]);
	}
	
	private function groupNewUsers($appid,$start,$end,$country,$channel,$groupField,$propertywhere)
	{
		$data=[];
        $columns=["日期"];
		$table_data =[];
		$dates = $this->getDateFromRange($start, $end);
		foreach($dates as $k=>$vv)
		{
			$res = $this->geteventData($appid,$vv,$vv,$country,$channel,"","","","new_users",$groupField,$propertywhere);
			$result = $this->getGroupByData($res,"new_users",$groupField);
			$columns = array_merge($columns,array_keys($result) );
			$result["日期"] =$vv;
			$data[$k] = $result;
			$table_data[$k] =["date"=>$vv,"val"=>$this->getDau($appid,$vv,$vv,$country,$channel,$propertywhere) ]; 
		}
		if( !in_array($groupField,["groupCountry","groupVersion","groupChannel"]) )
		{
			
			$columns=array("日期","新增用户");
		}
		return ["rows"=>$data,"table_data"=>$table_data,"columns"=>array_values(array_unique($columns))];
	}
	
	//渠道占比
	public function getChannelData($appid="",$start="",$end="",$country="",$channel=""){
		if( !$appid )
		{
			return [];
		}
		$where="";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}		
		$r = Db::connect($this->config)->query("select channel,count(*) as val from hellowd_ana_appnewuser where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} group by channel");
		if( !empty($r) )
		{
			$out_put=[];
			foreach($r as $k=>$v)
			{
				if( $v["channel"] && preg_match("/\d+$/",$v["channel"]) )
				{
					$channel_data = Db::connect($this->config)->query("SELECT channel from hellowd_ana_channel WHERE id={$v["channel"]}");
					$v["channel"]  =isset($channel_data[0]["channel"])?$channel_data[0]["channel"]:"";
					$out_put[] = ["name"=>$v["channel"]=="NO"?"未知":$v["channel"],"val"=>$v["val"] ];
				}
			}
			return $out_put;
		}
		return [];
	}
	
	//留存
	public function reten($appid="",$start="",$end="",$country="",$channel="",$property="")
	{
		if(!$appid)
		{
			return [];
		}
		$dates = $this->getDateFromRange($start, $end);
		$daynum = count($dates);
		$data=[];//echats
		$model = Db::connect($this->config);
		$propertywhere = $this->getPropertyWhere($property);
		foreach($dates as $k=>$vv)
		{
			$res = $this->getdayreten($model,$vv,$appid,$country,$channel,$propertywhere);
			$res["date"] = $vv;
			$res["new_users"] =$this->getNewUser($appid,$vv,$vv,$country,$channel,$propertywhere);
			$data[$k] =$res;
		}
		//平均次留
		$avgReten_1 = round(array_sum(array_column($data,"reten_1"))/$daynum,2);
		$columns =$this->getRetencolumn(); 
		$Retencolumns =$columns["Retencolumns"]; 
		return ["data"=>$data,"Retencolumns"=>$Retencolumns,"avgReten_1"=>$avgReten_1];
	}
	
	//事件列表
	public function eventlist($appid="")
	{
		if(!$appid)
		{
			return [];
		}
		$r = Db::connect($this->config)->query(" SELECT event as category,FORMAT(count(*),0) as num from hellowd_ana_event WHERE app_id={$appid} and level=1 and event not IN('applanuch','usertime') GROUP BY category");
		return $r;
	}
	
	//事件数
	public function eventdata($appid="",$start="",$end="",$country="",$channel="",$category="",$action="",$label="",$metric="",$groupField="",$property="")
	{
		if(!$appid || !$category)
		{
			return [];
		}
		$day = count($this->getDateFromRange($start, $end));
		$before_start = $this->getStartTime($start,$day);
		$before_end = $this->getStartTime($end,$day);
        $propertywhere = $this->getPropertyWhere($property);		
		$eventDayData = $this->getDayEventData($appid,$start,$end,$country,$channel,$category,$action,$label,$metric,$groupField,$propertywhere);
		$BeforeeventDayData = $this->getDayEventData($appid,$before_start,$before_end,$country,$channel,$category,$action,$label,$metric,$groupField,$propertywhere);
		
		$arr  = array_merge(array(
		  "eventNumText"=>$this->getRateText($eventDayData["EventTotalData"]["eventNum"],$BeforeeventDayData["EventTotalData"]["eventNum"],$day),
		  "userNumText"=>$this->getRateText($eventDayData["EventTotalData"]["userNum"],$BeforeeventDayData["EventTotalData"]["userNum"],$day),
		  "avgNumText"=>$this->getRateText($eventDayData["EventTotalData"]["avgNum"],$BeforeeventDayData["EventTotalData"]["avgNum"],$day),
		),$eventDayData["EventTotalData"]);
		return ["eventDayData"=>$eventDayData["data"],"columns"=>$eventDayData["columns"],"tableData"=>$eventDayData["tableData"],"EventTotalData"=>$arr ];
	}
	//事件列表
	public function evenListDe($appid="",$category="")
	{
		if(!$appid)
		{
			return [];
		}
		$result =[];
		$r = Db::connect($this->config)->query(" SELECT id from hellowd_ana_event WHERE app_id={$appid} and event='{$category}'");
		if( isset($r[0]["id"]) && $r[0]["id"] )
		{
			$category_id = $r[0]["id"];
			$action = Db::connect($this->config)->query(" SELECT event from hellowd_ana_event WHERE app_id={$appid} and level=2 and parent_id={$category_id}");
			if( !empty($action) )
			{
				foreach( $action as $kk=>$vv )
				{
					$result[$kk]=["value"=>$vv["event"],"label"=>$vv["event"],"children"=>[] ]; 
				}
                $a =[];				
				$label = Db::connect($this->config)->query(" SELECT event from hellowd_ana_event WHERE app_id={$appid} and level=3 and p_parent_id={$category_id}");
				if( !empty($label) )
				{
					foreach( $label as $kkk=>$vvv )
					{
						$a[$kk]=["value"=>$vvv["event"],"label"=>$vvv["event"],"children"=>[] ]; 
					}
				}
				$result[$kk]["children"] = $a;
			}
		}		
		return $result;
	}
	
	//获取每天的事件
	private function getDayEventData($appid="",$start="",$end="",$country="",$channel="",$category="",$action="",$label="",$metric,$groupField,$propertywhere="")
	{
		$dates = $this->getDateFromRange($start, $end);
		$data=[];
        $columns=["日期"];
		$tableData=[];
		$model = Db::connect($this->config);
		foreach($dates as $k=>$vv)
		{
			$res = $this->geteventData($appid,$vv,$vv,$country,$channel,$category,$action,$label,$metric,$groupField,$propertywhere);
			$result = $this->getGroupByData($res,$metric,$groupField);
			$columns = array_merge($columns,array_keys($result) );
			$result["日期"] =$vv;
			$data[$k] = $result;			
			$tableres = $this->geteventTableData($model,$appid,$vv,$vv,$country,$channel,$category,$action,$label,$propertywhere);
			$tableData[$k] =["date"=>$vv,"evenNum"=>$tableres["evenNum"],"UserNum"=>$tableres["UserNum"] ]; 			
		}
		$eventNum = array_sum(array_column($tableData,"evenNum"));	
		$userNum = array_sum(array_column($tableData,"UserNum"));
		$avgNum=$userNum>0?round( $eventNum/$userNum,2):0;		
		if( !in_array($groupField,["groupCountry","groupVersion","groupChannel"]) )
		{
			$name = $metric=="event"?"事件数":"独立用户";
			$columns=array("日期",$name);
		}
		return ["data"=>$data,"columns"=>array_values(array_unique($columns)),"tableData"=>$tableData,"EventTotalData"=>["eventNum"=>$eventNum,"userNum"=>$userNum,"avgNum"=>$avgNum] ];
	}
	
	//事件分组查询
	private function getGroupByData($r,$metric,$groupField)
	{
		$out_put=[];
		if( !empty($r) )
		{
			$m = Db::connect($this->config);
			foreach( $r as $v )
			{
				if( $groupField=="groupCountry" )
				{				
					$out_put[$v["country"]] = $v["num"];
				}elseif( $groupField=="groupVersion" )
				{
					$t = $m->query("SELECT app_version from hellowd_ana_app_version where id={$v["app_version"]}");
					$v["app_version"] = isset($t[0]["app_version"])?$t[0]["app_version"]:"no";
					$out_put[$v["app_version"]] = $v["num"];
				}elseif( $groupField=="groupChannel" )
				{
					$t = $m->query("SELECT channel from hellowd_ana_channel where id={$v["channel"]}");
					$v["channel"] = isset($t[0]["channel"])?$t[0]["channel"]:"no";
					$out_put[$v["channel"]] = $v["num"];
				}else{
					 $srt = ["event"=>"事件数","users"=>"独立用户","active_users"=>"活跃用户","new_users"=>"新增用户"];
					$name = isset($srt[$metric])?$srt[$metric]:"";
					$out_put[$name]=$v["num"];
				}
			}
		}
		return $out_put;
	}
	
	//事件数
	private function geteventData($appid,$start,$end,$country,$channel,$category,$action,$label,$metric,$groupField,$propertywhere="")
	{
		$where="";
		if( $country && $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel && $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		if( $category!="" )
		{
			$where.= " and category='{$category}'";
		}
		if( $action!="" )
		{
			$where.= " and action='{$action}'";
		}
		if( $label!="" )
		{
			$where.= " and label='{$label}'";
		}
		$where.=$propertywhere;
		$group="";
		$field="";
		switch( $groupField )
		{
			case 'groupCountry':
			  $group = "group by country";
			  $field =",country";
			break;
			case 'groupVersion':
			  $group = "group by app_version";
			  $field =",app_version";
			break;
			case 'groupChannel':
			  $group = "group by channel";
			  $field =",channel";
			break;
			case 'groupDefault':
			  $group = "";
			  $field ="";
			break;
		}		
		if( $metric=="event" )
		{						
		    $r = Db::connect($this->config)->query("select count(*) as num {$field} from hellowd_ana_eventdata where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} {$group} order by num desc limit 10");			
		}elseif($metric=="active_users"){
			
			$r = Db::connect($this->config)->query("select count(*) as num {$field} from hellowd_ana_applanuch where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} {$group} order by num desc limit 10");
		}elseif($metric=="new_users"){			
			$r = Db::connect($this->config)->query("select count(*) as num {$field} from hellowd_ana_appnewuser where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} {$group} order by num desc limit 10");			
		}else{
		   $r = Db::connect($this->config)->query("select count(*) as num {$field}  from ( select dv_id {$field} from hellowd_ana_eventdata where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} GROUP BY dv_id ) c  {$group} order by num desc limit 10");			
		}
      return $r;
	}
	
	//事件数
	private function geteventTableData($model,$appid,$start,$end,$country,$channel,$category,$action,$label,$propertywhere)
	{
		$where="app_id={$appid} and date>='{$start}' and date<='{$end}'";
		if( $country && $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel && $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		if( $category!="" )
		{
			$where.= " and category='{$category}'";
		}
		if( $action!="" )
		{
			$where.= " and action='{$action}'";
		}
		if( $label!="" )
		{
			$where.= " and label='{$label}'";
		}
		$where.=$propertywhere;
		$evenNum=0;
		$UserNum=0;
		$r = $model->query("select count(*) as num from hellowd_ana_eventdata where  {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			$evenNum = (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:"0";
		}
		$s =$model->query("select count(*) as num  from ( select dv_id from hellowd_ana_eventdata where  {$where} GROUP BY dv_id ) c");
		if( isset($s[0]) && !empty($s[0]) )
		{
			$UserNum = (isset($s[0]["num"]) && $s[0]["num"])?$s[0]["num"]:"0";
		}
		return ["evenNum"=>$evenNum,"UserNum"=>$UserNum];
	}
	
	
	//获取每天的新增
	private function getDayNewUsers($appid="",$start="",$end="",$country="",$channel="",$propertywhere="")
	{
		$dates = $this->getDateFromRange($start, $end);
		$data=[];
		foreach($dates as $k=>$vv)
		{
			$data[$k] =["date"=>$vv,"val"=>$this->getNewUser($appid,$vv,$vv,$country,$channel,$propertywhere) ]; 
		}
		return ["data"=>$data,"day"=>count($dates)];
	}
	
	//获取每天的日活
	private function getDayActiveUsers($appid="",$start="",$end="",$country="",$channel="",$propertywhere="")
	{
		$dates = $this->getDateFromRange($start, $end);
		$data=[];
		foreach($dates as $k=>$vv)
		{
			$data[$k] =["date"=>$vv,"val"=>$this->getDau($appid,$vv,$vv,$country,$channel,$propertywhere) ]; 
		}
		return $data;
	}
	
	//活跃国家分布
	private function countryActiveUsers($appid="",$start="",$end="",$country="",$channel="")
	{
		$where="app_id={$appid} and date>='{$start}' and date<='{$end}'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$r = Db::connect($this->config)->query("select country,count(*) as val from hellowd_ana_applanuch where  {$where} group by country order by val desc limit 15");
		if( !empty($r) )
		{
			$out_put=[];
			foreach($r as $k=>$v)
			{
				$country = $this->getallcountry($v["country"]?$v["country"]:1);
				if( $country )
				{
					$out_put[] = ["date"=>$country,"val"=>$v["val"] ];
				}
			}
			return $out_put;
		}
		return [];
	}
	
	//新增国家分布
	private function countryNewUsers($appid="",$start="",$end="",$country="",$channel="",$propertywhere="")
	{
		$where="app_id={$appid} and date>='{$start}' and date<='{$end}'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$r = Db::connect($this->config)->query("select country,count(*) as val from hellowd_ana_appnewuser where  {$where} group by country order by val desc limit 15");
		if( !empty($r) )
		{
			$out_put=[];
			foreach($r as $k=>$v)
			{
				$country = $this->getallcountry($v["country"]?$v["country"]:1);
				if( $country )
				{
					$out_put[] = ["date"=>$country,"val"=>$v["val"] ];
				}
			}
			return $out_put;
		}
		return [];
	}
	
	//获取每天的会话总次数，会话总时长
	private function getSession($appid,$start,$end,$country,$channel,$propertywhere="")
	{
		$where="app_id={$appid} and date>='{$start}' and date<='{$end}'";
		$num=0;
		$time_length="0";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$r =Db::connect($this->config)->query(" select sum(num) as num,sum(time_length) as time_length from hellowd_ana_applanuch where  {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			$num = (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:0;
			$time_length = (isset($r[0]["time_length"]) && $r[0]["time_length"])?$r[0]["time_length"]:0;
		}
		return ["Session_num"=>$num,"Session_length"=>ceil($time_length/60)];
	}
	
	//人均会话
	private function getAvgSession($appid,$start,$end,$country,$channel,$propertywhere)
	{
		$dates = $this->getDateFromRange($start, $end);
		
		$data=[];
		foreach($dates as $k=>$vv)
		{
			$Dau = $this->getDau($appid,$vv,$vv,$country,$channel,$propertywhere);
			$Session = $this->getSession($appid,$vv,$vv,$country,$channel,$propertywhere);
			$data[$k] =["date"=>$vv,"Session_num"=>$Dau<=0?"0":round($Session["Session_num"]/$Dau,2),"Session_length"=>$Dau<=0?"0":round($Session["Session_length"]/$Dau,2) ]; 
		}
		return $data;
	}
	
	//多少天留存获取
	private function getdayreten($model,$date,$appid,$country,$channel,$propertywhere)
	{
		$where="app_id={$appid} and date='{$date}'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		$num =0;
		
		$t = $model->query("select count(*) as num from hellowd_ana_appnewuser where {$where}");		
		if( isset($t[0]["num"]) && $t[0]["num"] )
		{
			$num =$t[0]["num"];			
		}
	
		$allowd_day_reten =[1,2,3,4,5,6,7,8];
		$out_put=[];
		$className =[];
		$lasttimes = strtotime(date("Y-m-d",strtotime("-1 day")));
		foreach( $allowd_day_reten as $v )
		{
			 $val = $this->getreten($model,$num,$date,$v,$appid,$country,$channel,$propertywhere);
			 $out_put["reten_".$v] =$val;
			if( $val=="0" )
			{
				$current_timestamp = strtotime($date);
				$rtime =$current_timestamp+($v*86400);				
				if( $rtime>$lasttimes )
				{
					$className["reten_".$v] ="notime";
					$out_put["reten_".$v] ="0";
				}else{
					$className["reten_".$v] ="demo-table-column".$v;
				}
			}else{
				$className["reten_".$v] ="demo-table-column".$v;
			}
		}
		$out_put["cellClassName"] = $className;
		return $out_put;
	}
	
	private function getRetencolumn()
	{
		$Retencolumns = [
		               ["title"=>'日期',"key"=>'date',"className"=>'table-date','align'=>'left',"sortable"=>true],
					   ["title"=>'新增',"key"=>'new_users',"className"=>'table-date','align'=>'left',"sortable"=>true],
					   ["title"=>'次日',"key"=>'reten_1','align'=>'center'],
                       ["title"=>'2日',"key"=>'reten_2','align'=>'left'],
					   ["title"=>'3日',"key"=>'reten_3','align'=>'left'],
					   ["title"=>'4日',"key"=>'reten_4'],
					   ["title"=>'5日',"key"=>'reten_5'],
					   ["title"=>'6日',"key"=>'reten_6'],
					   ["title"=>'7日',"key"=>'reten_7']					  
                ];
		return 	["Retencolumns"=>$Retencolumns];	
	}
	
	//留存计算
	private function getreten($model,$num,$date,$day,$appid,$country,$channel,$propertywhere)
	{
		$where="app_id={$appid} and date='{$date}'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$where.=$propertywhere;
		if( $num>0 )
		{
			$current_timestamp = strtotime($date);
			$rtime =$current_timestamp+($day*86400);
			$current_date = date("Y-m-d",$rtime);
			$lasttimes = strtotime(date("Y-m-d",strtotime("-1 day")));
			if( $rtime>$lasttimes )
			{
				return "0";
			}
			$current_num =0;
		   $r =$model->query("SELECT count(*) as num from ( SELECT dv_id from hellowd_ana_appnewuser WHERE {$where} ) c WHERE  EXISTS ( SELECT id from hellowd_ana_applanuch as b WHERE c.dv_id=b.dv_id and b.app_id={$appid} and b.date='{$current_date}')");
			if( isset($r[0]["num"]) && $r[0]["num"] )
			{
				$current_num =$r[0]["num"];				
			}
			return $num<=0?"0":round($current_num*100/$num,2);
		}
		return "0";
	}
	
	
	function getmylikedata($userid)
	{
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".$userid;
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
       return "0";		
	}
}
