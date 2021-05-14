<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
use \app\common\lib\Mobrequest;
use \app\common\lib\Unityrequest;
use \app\common\lib\Vunglerequest;
use \app\common\lib\Applovinrequest;
use \app\common\lib\Facebookrequest;
use \app\common\lib\Admobrequest;
use app\util\ShowCode;

 //第三方接口调用模块
set_time_limit(0);
ini_set('memory_limit', '-1');
class Adcash 
{
    
	//获取Mob 数据
	public function getMob($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$Mob = new Mobrequest();
		$result = $this->requestmobdata($Mob,1,$start,$end);
		
		if( $result!==false )
			exit("ok");
		exit("fail");
	}
	
	private function requestmobdata($obj,$page,$start,$end )
	{
		$list = $obj->apireport($page,$start,$end);
		$data = json_decode($list,true);
		if( isset( $data["code"] ) && $data["code"]=="ok" )
		{
			$result = $data["data"];
			$r = $result["lists"];
			$total = $result["total"];
			$num = ceil($total/500);
			if( !empty( $r ) )
			{
			   $this->insertdata($r,1);
			   if( $num>$page )
			   {
				   $page++;
                   return $this->requestmobdata($obj,$page,$start,$end );	
			   }   
			}
			return true;
		}
		return false;
	}
	
	//数据入库
	private function insertdata($data,$type)
	{
		
		array_walk( $data,function(&$v)use($type){
			switch( intval($type) )
				{
					case 1:
					   $v["platform"] =1;
					   $v["date"] =date("Y-m-d",strtotime($v["date"]));
                       $v["revenue"] = $v["est_revenue"];
					   //$v["app_name"] =//getappname($v["app_id"]);
					   $v["adtype"] = getadtype("1",$v["unit_id"] );
					   //$v["ctr"] = $v["impression"]>0?round( $v["click"]*100/$v["impression"],2):0;
					   //$v["ecpc"] = $v["click"]>0?round( $v["revenue"]/$v["click"],2):0;
					   //$v["ecpm"] =$v["impression"]>0?round($v["revenue"]*1000/$v["impression"],2):0;
                       unset($v["est_revenue"],$v["complete_view"],$v["unit_name"],$v['ad_format']);					   
					  break;
					case 2:
					   $v["platform"] =2;
					   $v["date"] =date("Y-m-d",strtotime($v["Date"]));
                       $v["app_id"] = $v["Source game id"];
					   $v["app_name"] = $v["Source game name"];
					   $v["country"] = $v["Country code"];
					   $v["request"] = $v["adrequests"];
					   $v["app_platform"] = $v["Platform"];
					   $v["unit_id"]=$v["Source zone"];
					   $v["adtype"]=getadtype("2",$v["unit_id"] );
					   $v["impression"] = $v["started"];
					   $v["click"] = $v["views"];
					   unset($v["Date"],$v["Source game id"],$v["Source zone"],$v["Source game name"],$v["Country code"],$v["adrequests"],$v["Country tier"],$v["Platform"]);				   
                      break;
                    case 3:					  
					   $v["country"] = strtoupper($v["country"]);
					   $v["app_id"] = md5( $v["package_name"]."_".$v["platform"] );
					   $v["app_platform"] = $v["platform"];
					   $v["unit_id"] = $v["zone_id"];
					   $v["app_name"] = $v["application"]."_".$v["platform"];
					   $v["adtype"] = getadtype("3",$v["ad_type"] );
					   $v["ad_hash"] = $v["ad_type"];
					   $v["impression"] = $v["impressions"];
					   $v["click"] = $v["clicks"];
					   $v["platform"] =3;
					   $v["date"] = $v["day"];
					   unset($v["package_name"],$v["impressions"],$v["zone_id"],$v["application"],$v["clicks"],$v["ad_type"],$v["day"] );					   
                      break;
                    case 4:					   
					   $v["app_id"] = $v["application id"];
					   $v["app_name"] = $v["application name"]."_".$v["platform"];
					   $v["click"] = $v["clicks"];
					   $v["app_platform"] = $v["platform"];
					   $v["platform"] =4;
					   $v["unit_id"] =isset($v["placement reference id"])?$v["placement reference id"]:"0";
					   $v["adtype"] = getadtype("4",$v["unit_id"] );
					   $v["impression"] = $v["impressions"];
					   //$v["ctr"] = $v["impression"]>0?round( $v["click"]*100/$v["impression"],2):0;
					   //$v["ecpc"] = $v["click"]>0?round( $v["revenue"]/$v["click"],2):0;
					   unset($v["impressions"],$v["clicks"],$v["application name"],$v["application id"],$v["placement id"],$v["placement name"],$v["placement reference id"] );
                      break;
                    case 5:
					   $v["platform"] =5;					   
					   $v["app_name"] =$v["app_name"]."_".$v["app_platform"];
                       $v["unit_id"] = str_replace(":","/",$v["unit_id"] );	
                       $v["adtype"] = getadtype("5",$v["unit_id"] );
                       $v["app_platform"] = strtolower( $v["app_platform"] );
                        if( in_array( $v["app_id"],[ "ca-app-pub-6491984961722312:0:","ca-app-pub-9512719894815523:0:","ca-app-pub-5470400114155059:0:" ] ) )
						{
							 $v["app_id"] =  md5( $v["app_id"].$v["app_name"] );
						}							
					   				   
                      break;
                    case 7:
					   $v["platform"] =7;
                       $v["adtype"] = getadtype("7",$v["unit_id"] );
                       $v["app_platform"] = strtolower( $v["app_platform"] );
                       $v["impression"] = $v["impressions"];
                       $v["click"] = $v["clicks"];
                       $v["country"] = strtoupper($v["countryCode"]);
                       $v["ecpm"] = $v["eCPM"];
                       
                       unset($v["appFillRate"],$v["impressions"],$v["eCPM"],$v["clicks"],$v["appFills"],$v["appRequests"],$v["countryCode"] );					   
                      break;					  
				}
			  $where = ["platform"=>$type,"app_id"=>$v["app_id"],"date"=>$v["date"],"country"=>$v["country"],"unit_id"=>$v["unit_id"] ];
			  if( intval($type)==3 || intval($type)==7 )
			  {
				  $where["ad_hash"] = $v["ad_hash"];
			  }
              $r = Db::name("adcash_data")->where(  $where )->find();
              if( empty($r) )
			  {
				$v["sys_app_id"] = getappidbycampaign($v["app_id"],$v["platform"],1);
				Db::name("adcash_data")->insert($v); 
			  }else{
				unset( $v["app_id"],$v["app_name"]);
				Db::name("adcash_data")->where( "id",$r["id"])->update( $v );  
			  }	  
		} );
        return true;		
		//return Db::name("adcash_data")->insertAll($data);
	}
	
	//获取vung 数据
	public function getVung($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$Vung = new Vunglerequest();
		$result = $Vung->request($start,$end);
		$data = json_decode($result,true);
		if( !empty($data) )
		{
			 $this->insertdata($data,4);
		}
		echo "ok";
	}
	
	//获取unity 数据
	public function getUnity($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",time() );
		}
		$Unity = new Unityrequest();
		$result =$Unity->request($start,$end);
		$data = json_decode($result,true);
		if( !empty($data) )
		{
			 $this->insertdata($data,2);
		}
		echo "ok";
	}
	
	//获取 AppLovin report api
	public function getApplovin($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$applo = new Applovinrequest();
		$this->applovindata($applo,1,$start,$end);
		
		echo "ok";
	}
	private function getappdata($start,$end)
	{
		$res= Db::query(" select CONCAT(application,'-',platform) as app_name,CONCAT(package_name,'-',platform) as package_name,sum(impressions) as impression,sum(clicks) as click,sum(revenue) as revenue,country,day as date,ad_type as unit_id,platform as app_platform from hellowd_applovin where day>='{$start}' and day<='{$end}' group by group_id");
		return $res;
	}
	public function applovindata($obj,$page,$start,$end)
	{
		$result =$obj->request($page,$start,$end);
		
		$data = json_decode($result,true);
		if( isset($data["results"]) )
		{
			$list = $data["results"];
			
			if( !empty($list) )
			{
				 $this->insertdata($list,3);
				 $page++;
				 $this->applovindata($obj,$page,$start,$end);
			}
		}
		return true;
	}
	//tankr(ios)
	public function tankriosfacebook($start="",$end="")
	{
		$apps = array(
			"Tankr(IOS)"=>[ "property_id"=>"184662625510014","app_id"=>"2000186346977172","platform"=>"ios" ],			 
			 "HexSnake-IOS"=>["property_id"=>"573697016383473","app_id"=>"2033122236908398","platform"=>"ios"],
			 "OreTycoon-iOS"=>["property_id"=>"403162960224318","app_id"=>"268883877311100","platform"=>"ios"],
			 "Tankr(Android)"=>[ "property_id"=>"131778474181158","app_id"=>"145198636252217","platform"=>"android" ],
		);
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$facebook  =new Facebookrequest();
		$result =$facebook->apprequest($apps,$start,$end);
		echo "ok";
	}
	//HexLand
	public function hexlandfacebook($start="",$end="")
	{
		$apps = array(
		  	
			"shopping mall tycoon(android)"=>["property_id"=>"1161738120663703","app_id"=>"600237440459112","platform"=>"android"],
			"Idle Fish Tycoon-ios"=>["property_id"=>"587488605084570","app_id"=>"245305093058730","platform"=>"ios"],
			"Idle Farm 农场 - iOS"=>["property_id"=>"2440704596143300","app_id"=>"2657437267628199","platform"=>"ios"],
             "fish GO(IOS)"=>["property_id"=>"151960719173384","app_id"=>"535608637261764","platform"=>"ios"],
             "fish GO(android)"=>["property_id"=>"151960719173384","app_id"=>"535608637261764","platform"=>"android"]
		);
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$facebook  =new Facebookrequest();
		$result =$facebook->apprequest($apps,$start,$end);
		echo "ok";
	}
	
	//新增upltv 变现数据拉取
	public function getfacebookv1($start="",$end="")
	{
		$apps = array(
			"Truck vs Fire(ios)"=>["property_id"=>"699862000410102","app_id"=>"995528667313289","platform"=>"ios"],
			"shopping mall tycoon(IOS)"=>["property_id"=>"1161738120663703","app_id"=>"721755674906542","platform"=>"ios"],
            "Tankr(Android)"=>[ "property_id"=>"131778474181158","app_id"=>"145198636252217","platform"=>"android" ],	
		);
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$facebook  =new Facebookrequest();
		$result =$facebook->apprequest($apps,$start,$end);
		exit("ok");
	}
	
	// 新 Facebook 异步拉取
	public function snyc_get_facebook($start="",$end=""){
		
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$list = Db::name("revenue_account")->field("app_id,platform,app_base_id,property_id")->select();
		if( !empty($list) )
		{
			foreach($list as &$v)
			{
				$row = Db::name("app_base")->field("name")->find($v["app_base_id"]);
				$v["name"] = $row["name"];
				$v["start"] = $start;
				$v["end"] = $end;
				$host = getdomainname();
				$url = $host."/adcash/getonefacebook";
				syncRequest($url,$v);
			}
		}
		$upltv_date = date("Y-m-d",strtotime("-2 day"));
		$this->getupltv($upltv_date,$upltv_date);
		exit("ok");
	}
	
	public function getonefacebook(Request $request)
	{
		$params = $request->param();
		if( !empty($params) )
		{
			$facebook  =new Facebookrequest();
			$result =$facebook->onerequest($params);
		}
		echo "ok";
	}
	
	//手动添加
	public function testonefacebook($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$facebook  =new Facebookrequest();
		//$facebook->test();
		$result =$facebook->testonerequest($start,$end);
		echo "ok";
	}
	
	//Admob 获取
	public function getadmob($start="",$end="")
	{
		$admob  =new Admobrequest();
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$res = $admob->showoutputdata($start,$end);
		if( !empty($res) )
		{
			$this->insertdata($res,5);
		}
		echo "ok";
	}
	
	public function newgetadmob($start="",$end="")
	{
		$admob  =new Admobrequest();
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$res = $admob->hotgoogelaccountdata($start,$end);
		if( !empty($res) )
		{
			$this->insertdata($res,5);
		}
		echo "ok";
	}
	
	function get_redirect_url($url){
		
	   $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// 不需要页面内容
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		// 不直接输出
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// 返回最后的Location
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_exec($ch);
		$info = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		return $info;
    }
	
	//新增fyber
	public function getfyber($start="",$end=""){
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$token_url ="https://reporting.fyber.com/auth/v1/token";
		$token_header =[];
		$token_header [] ="Content-Type:application/json";
		$params =json_encode(
		   array(
		      "grant_type"=>"client_credentials",
			  "client_id"=>"1a2efc976c5c0bc9d775fe70b2e73ed2",
			  "client_secret"=>"m6X-UgmmTCs4ceNdzuXlpJJOqH-FD_rCeWTkcILXTbb7SL0TBJ0sDuqnI5x342LYsHkBWUIok_GVcYfmUwT-kQBE4v9pacN6dKEBvxMZvJsV4jm2rJhckFxuG3DJdeHAo-3WF_eFjwn1TGB9V_pxoUPnoNCjwRCBnDf99XLVYy5vwC928IZDlfN8Ywdhl7CXkHfOQ4aDHWRFkDXJxlQxxcmEr_0fZkqFr8iexs2vmGaO3uoNw2eqv2yJIs7WU2lX2SP-512MXykSfVPH62r6fHP3Nhe2Kxi1iBUrmm5xw8UWU5gxUqgnoJgM3tg8z4poX7ZXZz92PSnIDyYAaQNeIg"
		   )
		);
		$res = json_decode($this->curl_request($token_url,$token_header,true,$params),true);
		$accessToken  =isset($res["accessToken"])?$res["accessToken"]:"";		
		if($accessToken)
		{
			$report_url ="https://reporting.fyber.com/api/v1/report?format=csv";
			$report_header =[];
			$report_header[] ="Content-Type:application/json";
			$report_header[] = 'Authorization: Bearer '. $accessToken;			
			$request_params =json_encode(
			   array(
				  "source"=>"mediation",
				  "dateRange"=>["start"=>$start,"end"=>$end],
				  "metrics"=>["Bid Requests","Impressions","Clicks","Rewarded Completions","Revenue (USD)"],
				  "splits"=>[ "App Name","Placement Type","Country"],
				  "filters"=>[]
			   )
			  );
			$data = json_decode($this->curl_request($report_url,$report_header,true,$request_params),true);
			if(isset($data["url"]))
			{
				$content =$this->get_redirect_url( $data["url"] );
				$result = $this->csvJSON($content);
				if(empty($result))
				{
					exit("fail");
				}
				foreach($result as $v)
				{
					switch($v["Placement Type"])
					{
						case 'Rewarded':
						    $adtype="rew";
						break;
						case 'Interstitial':
						    $adtype="int";
						break;
						case 'Banner':
						    $adtype="ban";
						break;
						default:
						    $adtype="no";
						break;
					}			
					$insert_data =array(						 
						 "platform"=>41,
						 "date"=>$start,
						 "app_id" =>md5($v["App Name"]),
						 "app_name"=>$v["App Name"],
						 "impression"=>$v["Impressions"],
						 "click"=>$v["Clicks"],
						 "revenue"=>$v["Revenue (USD)"],
						 "country"=>strtoupper($v["Country"]),
						 "adtype"=>$adtype
					  );
					 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_name"=>$insert_data["app_name"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
					  if( empty($rs) )
					  {
						$insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
						Db::name("adcash_data")->insert($insert_data);
					  }else{
						unset( $insert_data["app_id"],$insert_data["app_name"]);
						Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );
					  }	
				}
			}
		}
		exit("ok");
		
	}
	
	//新增inmobi 渠道 2020 11 23
	public function getinmobi($start="",$end=""){
	
      	if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$url = "https://api.inmobi.com/v1.0/generatesession/generate";
		$httpHeader = array();
		$httpHeader[] = 'userName:yujiugang@hellowd.net';
		$httpHeader[] = 'secretKey:8e8f160eb21b4b7492b47f80b576d826';
		$res = $this->curl_request($url,$httpHeader,false);
		$result = json_decode($res,true);
		if(!isset($result["respList"][0]["sessionId"]))
		{
			exit("error");
		}
		$report_url ="https://api.inmobi.com/v3.0/reporting/publisher";
		$report_header =[];
		$report_header[] ="Content-Type:application/json";
		$report_header[] ="Accept:application/json";
		$report_header[] ="accountId:772556296d6043a8a659aa37f7ecf7b8";
		$report_header[] ="secretKey:8e8f160eb21b4b7492b47f80b576d826";
		$report_header[] ="sessionId:{$result["respList"][0]["sessionId"]}";
		$params =json_encode(
		   array(
		      "reportRequest"=>array(
			     "metrics"=>["adRequests","adImpressions","clicks","earnings"],
				 "timeFrame"=>"{$start}:{$end}",
				 "groupBy"=>["country","inmobiAppId","placement","date"],
				 "offset"=>0, 
				 "length"=>3000,
				 "filterBy"=>[["filterName"=>"adImpressions","filterValue"=>"0","comparator"=>">"]]
			  )
		   )
		);
		$res = $this->curl_request($report_url,$report_header,true,$params);
		$data = json_decode($res,true);
		if(isset($data["respList"]) && !empty($data["respList"]))
		{
			foreach($data["respList"] as $v)
			{
				$adtype ="no";
                $sys_app_id="";				
				if( preg_match("/INT/",$v["placementName"] ) )
				{
					$adtype="int";
				}elseif( preg_match("/RV/",$v["placementName"] ) )
				{
					$adtype="rew";
				}elseif( preg_match("/Ban/",$v["placementName"] ) )
				{
					$adtype="ban";
				}
				//FishGo_143_InMobi_iOS_INT_M1
				$arr =explode("_",$v["placementName"]);
				$sys_app_id = isset($arr[1])?$arr[1]:"";
				$country =str_replace("'","",$v["country"]); 
				if($v["country"]=="USA")
				{
					$country = "US";
				}else{
					$row = Db::name("country")->where(" name like '%{$country}%'")->find();
					if(!empty($row))
					{
						$country = $row["code"];
					}else{
						 $country = substr($country,10);
					}
				}
				$insert_data =array(									     
						 "platform"=>40,
						 "date"=>date("Y-m-d",strtotime($v["date"])),
						 "app_id"=>$v["inmobiAppId"],
						 "app_name"=>$v["inmobiAppName"],
						 "request"=>$v["adRequests"],
						 "unit_id"=>$v["placementId"],
						 "impression"=>$v["adImpressions"],
						 "click"=>$v["clicks"],
						 "revenue"=>$v["earnings"],
						 "sys_app_id"=>$sys_app_id,
						 "country"=>strtoupper($country),
						 "adtype"=>$adtype
				   );
				 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"unit_id"=>$insert_data["unit_id"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
				  if( empty($rs) )
				  {
					//$insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
					Db::name("adcash_data")->insert($insert_data);
				  }else{
					unset( $insert_data["app_id"],$insert_data["app_name"]);
					Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
				}
			}
		}
		exit("ok");
	}
	
	//ironSource Reporting API
	public function getironSource($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
        $data=[];
        $key ="tangwenjuan@hellowd.net:1562b54338070af96f0f5cb0d509af7f";		
		$res = $this->authironSource($start,$end,$key);		
		$result = json_decode($res,true);
		if( !empty($result) )
		{
			foreach( $result as $k=>$v  )
			{
				if( !empty($v["data"]) )
				{
					foreach( $v["data"] as $key=>&$vv )
					{
						$vv["app_id"] = $v["appKey"];
						$vv["app_name"] = $v["appName"];
						$vv["app_platform"] = $v["platform"];
						$vv["date"] = $v["date"];
						$vv["ad_hash"] = $v["adUnits"];
						$vv["unit_id"] = $v["instance"];
						$data[] = $vv;
					}
				}
			}
			unset($result);
		}
		if( !empty($data) )
		{
			$this->insertdata($data,7);
		}
		echo "ok";
	}
	
	public function getnewironSource($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
        $data=[];
        $key ="904040980@qq.com:9b44db8d225eb5c28292feea4c634cba";		
		$res = $this->authironSource($start,$end,$key);		
		$result = json_decode($res,true);
		if( !empty($result) )
		{
			foreach( $result as $k=>$v  )
			{
				if( !empty($v["data"]) )
				{
					foreach( $v["data"] as $key=>&$vv )
					{
						$vv["app_id"] = $v["appKey"];
						$vv["app_name"] = $v["appName"];
						$vv["app_platform"] = $v["platform"];
						$vv["date"] = $v["date"];
						$vv["ad_hash"] = "9b44db8d225eb5c28292feea4c634cba";
						$vv["unit_id"] = $v["instance"];
						$data[] = $vv;
					}
				}
			}
			unset($result);
		}
		if( !empty($data) )
		{
			$this->insertdata($data,7);
		}
		echo "ok";
	}
	
	 
	function authironSource($start,$end,$key)
	{		
		$base64encoded = base64_encode($key);
		$httpHeader = array();
		$httpHeader[] = 'Authorization: Basic '. $base64encoded;
		$httpHeader[] = 'Accept: application/json';
		$URL="https://platform.ironsrc.com/partners/publisher/mediation/applications/v4/stats?startDate={$start}&endDate={$end}&breakdowns=date,app,country,platform,adUnits,instance&metrics=revenue,eCPM,appFillRate,appRequests,appFills,impressions,clicks";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret =  curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
	}
	
	public function gettapjoy($start="")
	{
		if( $start=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
		}
		$this->inserttapjoy($start,1);
		echo "ok";
	}
	
	private function inserttapjoy($start,$page)
	{
		$data = $this->request_tapjoy($start,$page);	
		if( !empty($data) )
		{
			if( $data["Apps"] && !empty($data["Apps"] ) )
			{
				$res = $data["Apps"];
				foreach( $res as $vv )
				{
					$app_platform ="android";
					if( $vv["Platform"] =="iphone" )
					{
						$app_platform ="ios";
					}
					
					$Placements_data = isset($vv["Placements"])?$vv["Placements"]:[];
					if( !empty( $Placements_data ) )
					{
						foreach( $Placements_data as $vvv )
						{
							
							$adtype ="no";							
							if( preg_match("/INT/",$vvv["Name"] ) )
							{
								$adtype="int";
							}elseif( preg_match("/RV/",$vvv["Name"] ) )
							{
								$adtype="rew";
							}elseif( preg_match("/Wall/",$vvv["Name"] ) )
							{
								$adtype="wall";
							}
							
							$country_data = isset($vvv["Countries"])?$vvv["Countries"]:[];
							if( !empty($country_data) )
							{
								foreach( $country_data as $vvvv )
								{
																		
									$insert_data =array(
									     
										 "platform"=>9,
										 "date"=>$data["Date"],
										 "app_id"=>$vv["AppKey"],
										 "app_name"=>$vv["Name"],
										 "unit_id"=>$vvv["Name"],
										 "impression"=>$vvvv["Impressions"],
										 "click"=>$vvvv["Clicks"],
										 "revenue"=>$vvvv["Revenue"],
										 "country"=>strtoupper($vvvv["Country"]),
										// "ecpm"=>$vvvv["ECPM"],
										 "adtype"=>$adtype,
										 "app_platform"=>$app_platform
									  );
									 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
									  if( empty($rs) )
									  {
										$insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
										Db::name("adcash_data")->insert($insert_data); 
									  }else{
										unset( $insert_data["app_id"],$insert_data["app_name"]);
										Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
									  }	
								}
							}
						}						
					}
				}
				$page = $page+1;
				return $this->inserttapjoy($start,$page);
			}
		}
		return true;
	}
	
	private function GTDcurl($url,$data=null,$method = null)
	{
	    $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST,1);
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		if($data){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
    }
	
	//快手
	public function getKuai($start="",$end=""){
		if( $start=="" || $end=="" )
		{
			$start = date("Ymd",strtotime("-1 day"));
			$end =  date("Ymd",strtotime("-1 day"));
		}
		$Ak ="5581";
		$Sk = "b5fa64828b9e5a9b683a9c66531d18d0";
		$timestamp=time();
		$sign = md5("/api/report/dailyShare?ak={$Ak}&date={$start}&sk={$Sk}&timestamp={$timestamp}");
		$url ="https://ssp.e.kuaishou.com/api/report/dailyShare?timestamp={$timestamp}&ak={$Ak}&date={$start}&sign={$sign}";
		$res = json_decode($this->googlecurl($url),true);
		if(!empty($res) && isset($res["data"]))
		{
			$result = $res["data"];
			foreach($result as $vv)
			{
				   $adtype="no";
				   if( preg_match("/int/i",$vv["position_name"] ) )
					{
						$adtype="int";
					}elseif( preg_match("/rv/i",$vv["position_name"] ) )
					{
						$adtype="rew";
					}
				 $arr = explode("_",$vv["position_name"]);	
				 $insert_data =array(									     
					 "platform"=>42,
					 "date"=>$vv["date"],
					 "sys_app_id"=>isset($arr[1])?$arr[1]:"",
					 "app_id"=>$vv["app_id"],
					 "impression"=>$vv["impression"],
					 "click"=>$vv["click"],
					 "adtype"=>$adtype,
					 "unit_id"=>$vv["position_id"],
					 "app_name"=>$vv["app_name"],
					 "revenue"=>round($vv["share"]*0.141085,2),//按固定汇率计算 7.0879,
					 "country"=>"CN"
				  );
				 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
				  if( empty($rs) )
				  {
					Db::name("adcash_data")->insert($insert_data); 
				  }else{
					 Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
				  }	
			}
		}
		exit("ok");
	}
	
	//新增广点通接口更新
	public function getGdT($start="",$end=""){
		if( $start=="" || $end=="" )
		{
			$start = date("Ymd",strtotime("-1 day"));
			$end =  date("Ymd",strtotime("-1 day"));
		}
		$memberid="302040606625";
		$secret="8qmpNfg3,Y!s{erc0T=yF,B7JI{dL7Jh";
		$time = time();
		$sign = sha1($memberid.$secret.$time);
		$token = base64_encode($memberid.','.$time .','.$sign);
		$url ="https://api.adnet.qq.com/open/v1.1/report/get?member_id={$memberid}&start_date={$start}&end_date={$end}";
		$httpHeader = array();
		$httpHeader[] = 'token: '. $token;
		$res = $this->curl_request($url,$httpHeader,false);
		$result  =json_decode($res,true);
		if( isset($result["data"]["list"])&& !empty($result["data"]["list"]) )
		{
			$data = $result["data"]["list"];
			foreach(  $data as $k=>$vv )
			{
				if($vv["is_summary"])
				{
					continue;
				}
				$placementName =explode("_",$vv["placement_name"]);
				$adtype ="no";							
				if( preg_match("/INT/i",$vv["placement_name"] ) )
				{
					$adtype="int";
				}elseif( preg_match("/RV/i",$vv["placement_name"] ) )
				{
					$adtype="rew";
				}elseif( preg_match("/ban/i",$vv["placement_name"] ) )
				{
					$adtype="ban";
				}
				$insert_data =array(				
					 "platform"=>35,
					 "date"=>$vv["date"],
					 "app_id"=>$vv["app_id"],
					 "unit_id"=>$vv["placement_id"],
					 "app_name"=>$vv["medium_name"],
					 "impression"=>$vv["pv"],
					 "click"=>$vv["click"],
					 "revenue"=>round($vv["revenue"]*0.141085,2),//按固定汇率计算 7.0879
					 "country"=>strtoupper("CN"),
					 "adtype"=>$adtype,
					 "sys_app_id"=>isset($placementName["1"]) && preg_match('/^\d/',$placementName["1"])?$placementName["1"]:"",				 
				);			
			  $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"unit_id"=>$insert_data["unit_id"] ] )->find();
			  if( empty($rs) )
			  {
				Db::name("adcash_data")->insert($insert_data); 
			  }else{
				unset( $insert_data["app_id"],$insert_data["app_name"]);
				Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
			  }										
			}
		}
		exit("ok");
	}
	
	//Sigmob 2020-01-13
	public function getSigmob($start="",$end=""){
		
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$publicKey ="0b734cd21e97392ac8ae86439c9a869b";		
		list($usec, $sec) = explode(" ", microtime());
		$time = intval(((float)$usec + (float)$sec) * 1000);
		$params =array(
		   "dimensions"=>"date,application,placement,adType,platform",
		   "startDate"=>$start,
		   "endDate"=>$end,
		   "pk"=>$publicKey,
		   "t"=>$time,
		);
		$params["sign"] = $this->getSortParams($params);
		$content = http_build_query($params);
		$url="https://report.sigmob.cn/pub/v1/apps/reports?".$content;
		$res =$this->googlecurl($url);
		$data = json_decode($res,true);
		if( !empty($data) )
		{
			foreach(  $data as $k=>$vv )
			{
				$placementName =explode("_",$vv["placementName"]);
				$adtype ="no";							
				if( preg_match("/INT/i",$vv["placementName"] ) )
				{
					$adtype="int";
				}elseif( preg_match("/RV/i",$vv["placementName"] ) )
				{
					$adtype="rew";
				}elseif( preg_match("/ban/i",$vv["placementName"] ) )
				{
					$adtype="ban";
				}
				$insert_data =array(				
					 "platform"=>34,
					 "date"=>$vv["date"],
					 "app_id"=>$vv["appId"],
					 "unit_id"=>$vv["placementId"],
					 "app_name"=>$vv["appName"],
					 "impression"=>$vv["impressions"],
					 "click"=>$vv["clicks"],
					 "revenue"=>round($vv["revenue"]*0.141085,2),//按固定汇率计算 7.0879
					 "country"=>strtoupper("CN"),
					 "adtype"=>$adtype,
					 "sys_app_id"=>isset($placementName["1"]) && preg_match('/^\d/',$placementName["1"])?$placementName["1"]:"",
					 "app_platform"=>$vv["platform"]=="1"?"iOS":"Android"					 
				);			
			  $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"unit_id"=>$insert_data["unit_id"] ] )->find();
			  if( empty($rs) )
			  {
				//$insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
				Db::name("adcash_data")->insert($insert_data); 
			  }else{
				unset( $insert_data["app_id"],$insert_data["app_name"]);
				Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
			  }										
			}
		}
       exit("ok");		
	}
	private function getSortParams($param)
	{
		$secretKey ="be76887aa7e0743a08e5d8356cd3dca7";
		ksort($param);
		$signstr = '';
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                 if ($value == '') {
                    continue;
               }
                $signstr .=$value;
            }
            $signstr.= $secretKey;
        }
        return sha1($signstr,false);
	}
	//tapjoy
	private function authtapjoy()
	{
		$base64encoded = "ZGMxOGE0NTAtYjM0YS00YmM4LWJlNzMtMjBkOTY5M2Y3ZGJjOkVKWmlhOVJuMWY4b3pSSkhqOVo3cm5hR0ExemNwSFR3V2xZOERLS3RkcGpVSUxDWjJ3cDB1VVd0a0sxeEp3akJ0K0czekkxR0p4V3ZSc3M4a0V0L1NBPT0=";
		$httpHeader = array();
		$httpHeader[] = 'Authorization: Basic '. $base64encoded;
		$httpHeader[] = 'Accept: application/json; */*';
		$URL="https://api.tapjoy.com/v1/oauth2/token";
        $r = $this->curl_request($URL,$httpHeader);
		$data = json_decode($r,true);
		if( !empty($data) && isset( $data["access_token"] )  )
		{
			return $data["access_token"];
		}
		return "";
	}
	
	public function request_tapjoy($start,$page)
	{
		$access_token = $this->authtapjoy();
		if( $access_token )
		{
			$URL="https://api.tapjoy.com/v2/publisher/reports?date={$start}&page_size=100&group_by=placements&page={$page}";			
			$httpHeader = array();
			$httpHeader[] = 'Authorization: Bearer '. $access_token;
			$httpHeader[] = 'Accept: application/json; */*';	
			$r = $this->curl_request($URL,$httpHeader,false);
			return json_decode($r,true);
		}
		return [];
	}
	
	//yomob 渠道 2019-01-05
	public function getyomob($start="",$end="")
	{
		
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$appidlist =array(
		        "p5Sk4d61Nof71M62W273"=>"66"
		);
		$base_url ="https://report.yomob.com/";
		$token = $this->getyomobtoken();
        $report_url =$base_url."/reports/revenues?startDate={$start}&endDate={$end}&groupBy=country,adformat";//adnetwork
		if( $token )
		{
			$httpHeader = array();
			$httpHeader[] = 'Authorization: Bearer '. $token;
			$res = $this->curl_request($report_url,$httpHeader,false);
			$data  =json_decode($res,true);
			if( !empty($data) )
			{
				foreach( $data as $vv )
				{
					 $rmb_revenue = $vv["revenues_rmb"]*0.1449*0.9328;
					 $adtype ="no";							
					if( preg_match("/int/",$vv["adformat"] ) )
					{
						$adtype="int";
					}elseif( preg_match("/rew/",$vv["adformat"] ) )
					{
						$adtype="rew";
					}
					 $insert_data =array(									     
										 "platform"=>33,
										 "date"=>$vv["date"],
										 "sys_app_id"=>$appidlist[$vv["appid"]],
										 "app_id"=>$vv["appid"],
										 "impression"=>$vv["impressions"],
										 "adtype"=>$adtype,
										 "unit_id"=>$vv["adformat"],
										 "revenue"=>round($rmb_revenue+$vv["revenues_usd"],2),
										 "country"=>strtoupper($vv["country"])
									  );
					 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
					  if( empty($rs) )
					  {
						Db::name("adcash_data")->insert($insert_data); 
					  }else{
						 Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
					  }	
				}
			}
		}
		$this->getfacebookv1($start,$end);
		exit("ok");
	}
	
	//获取yomob令牌
	private function getyomobtoken()
	{
		$token_url ="https://report.yomob.com/auth/access_token";
		$data =json_encode(["username"=>"tangwenjuan@hellowd.net","password"=>"uxkF342PXJ4XN4p"]);
		$res = $this->googlecurl($token_url,$data,'post');
		$result = json_decode($res,true);
		return isset($result["token"])?$result["token"]:"";
	}
	
	 //请求
	private function googlecurl($url,$data=null,$method = null)
	{
	    $header = array("Content-Type:application/json;charset=UTF-8");
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST,1);
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		if($data){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
    }
	
	
	//upltv 分渠道 2019/06/26
	public function byUpltvChannel($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$channelList = ["Admob","Vungle","AdColony","mobvista","unity","tapjoy","Facebook","Chartboost","AppLovin","Mintegral","Unity Ads","VK","ironSource","maio","GDT","OneWay","Toutiao","Sigmob","Baidu"];
		$key ="f9c4be7d7af7515e5dc639103a13561e";
		$url ="https://reporting.upltv.com/api/appList?key={$key}";
		$r = $this->curl_request($url,[],false);
	    $applist = json_decode($r,true);
		if(!empty($applist) && isset($applist["code"]) && $applist["code"]==200)
		{
			$list = $applist["data"];
			if(!empty($list))
			{
				foreach($list as $k=>$vv)
				{
					foreach( $channelList as $c )
					{
						$this->channelInsert($start,$end,$vv,$key,$c);
					}					
				}
			}
		}
		echo "ok";
	}
	
	private function channelInsert($start,$end,$vv,$key,$channel)
	{
		$request_url ="https://reporting.upltv.com/api/report?channel={$channel}&country=all&end_day={$end}&offer_type=all&pid={$vv['pid']}&start_day={$start}&key={$key}";
		$res = $this->curl_request($request_url,[],false);
		$res = json_decode($res,true);
		if( isset( $res["data"]["date_report"] ) && !empty($res["data"]["date_report"]) )
		{
			$result=$res["data"]["date_report"];
			if( !empty($result) )
			{
				foreach(  $result as $k=>$v )
				{
					if( $v["revenue"]>0 )
					{
						$app_id= getappidbycampaign($vv["pid"],30,1);					
						  $rs = Db::name("upltv_channel")->where( ["pid"=>$vv["pid"],"date"=>$k,"channel"=>$channel ] )->find();
						  $cu_data =array(
							  "app_id"=>$app_id,
							  "date"=>$k,
							  "channel"=>$channel,
							  "pid"=>$vv["pid"],
							  "revenue"=>$v["revenue"]
						  ); 
						  if( empty($rs) )
						  {					
							Db::name("upltv_channel")->insert($cu_data); 
						  }else{
							Db::name("upltv_channel")->where( "id",$rs["id"])->update( $cu_data );  
						  }	
					}														
				}
			}
		}
		return true;
	}
	
	private function curl_request1($url,$data_string="",$method="get")
	{
		
		$headers=[];
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
		curl_setopt($curl, CURLOPT_PROXY, "121.199.76.16"); //代理服务器地址
		curl_setopt($curl, CURLOPT_PROXYPORT,8091); //代理服务器端口		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
        if ($method == 'post') {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
		}
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	
	//upltv渠道 2018/10/24
	public function getupltv($start="",$end="")
	{
		
		$r = $this->curl_request("http://console.gamebrain.io/admin_ltv/updateMainData",[],false);
		$last1 = date("Y-m-d",strtotime("-1 day"));		
		$last3 = date("Y-m-d",strtotime("-3 day"));
		$r1 = $this->curl_request("http://console.gamebrain.io/admin_product/updateproduct?start={$last1}",[],false);
		sleep(3);	
		$r3 = $this->curl_request("http://console.gamebrain.io/admin_product/updateproduct?start={$last3}",[],false);
		exit("ok");
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		
		$key ="f9c4be7d7af7515e5dc639103a13561e";
		$url ="https://reporting.upltv.com/api/appList?key={$key}";
		$r = $this->curl_request($url,[],false);
	    $applist = json_decode($r,true);
		if(!empty($applist) && isset($applist["code"]) && $applist["code"]==200)
		{
			$list = $applist["data"];
			if(!empty($list))
			{
				foreach($list as $k=>$vv)
				{
					$this->upltvToutiao($start,$end,$vv,$key);
					$this->upltvGDT($start,$end,$vv,$key);
					$this->upltvSigmob($start,$end,$vv,$key);
					$this->upltvinsert($start,$end,$vv,"interstitial",$key);
					$this->upltvinsert($start,$end,$vv,"rewarded_video",$key);
					$this->upltvinsert($start,$end,$vv,"banner",$key);
				}
			}
		}
		$this->curl_request("http://console.gamebrain.io/admin_product/updateproduct?start={$start}",[],false);
		echo "ok";
	}
	
	//upltv 单独头条收益数据
	private function upltvToutiao($start,$end,$vv,$key)
	{
		$request_url ="https://reporting.upltv.com/api/report?channel=toutiao&country=all&end_day={$end}&offer_type=all&pid={$vv['pid']}&start_day={$start}&key={$key}";
		$res = $this->curl_request($request_url,[],false);
		$res = json_decode($res,true);
		if( isset( $res["data"]["date_report"] ) && !empty($res["data"]["date_report"]) )
		{
			$result=$res["data"]["date_report"];
			if( !empty($result) )
			{
				foreach(  $result as $k=>$v )
				{
					$app_id= getappidbycampaign($vv["pid"],30,1);
					
					  $rs = Db::name("upltv_toutiao")->where( ["pid"=>$vv["pid"],"date"=>$k,"type"=>1 ] )->find();
					  $cu_data =array(
						  "app_id"=>$app_id,
						  "date"=>$k,
						  "type"=>1,
						  "pid"=>$vv["pid"],
						  "original_revenue"=>$v["revenue"],
						  "cny_revenue"=>round($v["revenue"]/0.1483,4)
					  ); 
					  if( empty($rs) )
					  {					
						Db::name("upltv_toutiao")->insert($cu_data); 
					  }else{
						Db::name("upltv_toutiao")->where( "id",$rs["id"])->update( $cu_data );  
					  }										
				}
			}
		}
		return true;
	}
	//upltv GDT
	private function upltvGDT($start,$end,$vv,$key)
	{
		$request_url ="https://reporting.upltv.com/api/report?channel=GDT&country=all&end_day={$end}&offer_type=all&pid={$vv['pid']}&start_day={$start}&key={$key}";
		$res = $this->curl_request($request_url,[],false);
		$res = json_decode($res,true);
		if( isset( $res["data"]["date_report"] ) && !empty($res["data"]["date_report"]) )
		{
			$result=$res["data"]["date_report"];
			if( !empty($result) )
			{
				foreach(  $result as $k=>$v )
				{
					$app_id= getappidbycampaign($vv["pid"],30,1);
					
					  $rs = Db::name("upltv_toutiao")->where( ["pid"=>$vv["pid"],"date"=>$k,"type"=>2 ] )->find();
					  $cu_data =array(
						  "app_id"=>$app_id,
						  "date"=>$k,
						  "type"=>2,
						  "pid"=>$vv["pid"],
						  "original_revenue"=>$v["revenue"],
						  "cny_revenue"=>round($v["revenue"]/0.1483,4)
					  ); 
					  if( empty($rs) )
					  {					
						Db::name("upltv_toutiao")->insert($cu_data); 
					  }else{
						Db::name("upltv_toutiao")->where( "id",$rs["id"])->update( $cu_data );  
					  }										
				}
			}
		}
		return true;
	}
	//Sigmob 数据
	private function upltvSigmob($start,$end,$vv,$key)
	{
		$request_url ="https://reporting.upltv.com/api/report?channel=Sigmob&country=all&end_day={$end}&offer_type=all&pid={$vv['pid']}&start_day={$start}&key={$key}";
		$res = $this->curl_request($request_url,[],false);
		$res = json_decode($res,true);
		if( isset( $res["data"]["date_report"] ) && !empty($res["data"]["date_report"]) )
		{
			$result=$res["data"]["date_report"];
			if( !empty($result) )
			{
				foreach(  $result as $k=>$v )
				{
					$app_id= getappidbycampaign($vv["pid"],30,1);
					
					  $rs = Db::name("upltv_toutiao")->where( ["pid"=>$vv["pid"],"date"=>$k,"type"=>3 ] )->find();
					  $cu_data =array(
						  "app_id"=>$app_id,
						  "date"=>$k,
						  "type"=>3,
						  "pid"=>$vv["pid"],
						  "original_revenue"=>$v["revenue"],
						  "cny_revenue"=>round($v["revenue"]/0.1483,4)
					  ); 
					  if( empty($rs) )
					  {					
						Db::name("upltv_toutiao")->insert($cu_data); 
					  }else{
						Db::name("upltv_toutiao")->where( "id",$rs["id"])->update( $cu_data );  
					  }										
				}
			}
		}
		return true;
	}
	
	private function upltvinsert($start,$end,$vv,$type,$key)
	{
	    $request_url ="https://reporting.upltv.com/api/report?channel=all&country=all&end_day={$end}&offer_type={$type}&pid={$vv['pid']}&start_day={$start}&key={$key}";
		$res = $this->curl_request($request_url,[],false);
		$res = json_decode($res,true);
		if( isset( $res["data"]["country_report"] ) && !empty($res["data"]["country_report"]) )
		{
			$result=$res["data"]["country_report"];
			$ads =array(
			   "rewarded_video"=>"rew",
			   "interstitial"=>"int",
			   "banner"=>"ban"
			);
			foreach($result as $key=>$vvv)
			{
				$insert_data =array(
				
						 "platform"=>30,
						 "date"=>$start,
						 "request"=>$vvv["requests"],
						 "fill_rate"=>$vvv["fillrate"],
						 "app_id"=>$vv["pid"],
						 "app_name"=>$vv["name"],
						 "impression"=>$vvv["impressions"],
						 "click"=>$vvv["clicks"],
						 "revenue"=>$vvv["revenue"],
						 "country"=>strtoupper($key),
						 "adtype"=>$ads[$type],
						 "app_platform"=>$vv["platform"]						 
						);
						
				  $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"adtype"=>$insert_data["adtype"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
				  if( empty($rs) )
				  {
					$insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
					Db::name("adcash_data")->insert($insert_data); 
				  }else{
					unset( $insert_data["app_id"],$insert_data["app_name"]);
					Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
				  }	
			}
		}
		return true;
	}
	
	//新增穿山甲数据  2019-06-19
	public function getTouTiao($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
		}
		$nonce = rand(0,10000);
		$time = time();
		$sign = $this->signature_gen("2a82ce3552df4e5bc5f7ea930e9a17f2", $time, $nonce);
		
		$url="https://ad.oceanengine.com/union/media/open/api/report/slot?user_id=6984&sign={$sign}&nonce={$nonce}&timestamp={$time}&start_date={$start}&end_date={$end}";				
		$result  =$this->curl_request($url,[],false);
		$res = json_decode($result,true);
		if( !empty($res) && isset( $res["data"] ) && !empty( $res["data"] ) )
		{
			$data = $res["data"];
			foreach( $data as $vv )
			{
				$sys_app_id ="";
				preg_match( "/#(.*)#/",$vv["site_name"],$matches );
			    if( !empty( $matches ) &&  isset( $matches[1] ) )
				{
					$sys_app_id = $matches[1];
				}
				
				$adtype ="no";							
				if( preg_match("/INT/",$vv["code_name"] ) )
				{
					$adtype="int";
				}elseif( preg_match("/RV/",$vv["code_name"] ) )
				{
					$adtype="rew";
				}elseif( preg_match("/ban/i",$vv["code_name"] ) )
				{
					$adtype="ban";
				}
                $insert_data =array(									     
						 "platform"=>32,
						 "date"=>$vv["stat_datetime"],
						 "sys_app_id"=>$sys_app_id,
						 "app_id"=>$vv["appid"],
						 "click"=>$vv["click"],
						 "ecpm"=>$vv["ecpm"],
						 "app_name"=>$vv["site_name"],
						 "impression"=>$vv["show"],
						 "adtype"=>$adtype,
						 "unit_id"=>$vv["ad_slot_id"],
						 "revenue"=>$vv["cost"],
						 "country"=>strtoupper($vv["region"])
				    );
				 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"unit_id"=>$insert_data["unit_id"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
				  if( empty($rs) )
				  {
					Db::name("adcash_data")->insert($insert_data); 
				  }else{
					 Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
				  }					
			}
		}
		$last2 = date("Y-m-d",strtotime("-2 day"));
		$r2 = $this->curl_request("http://console.gamebrain.io/admin_product/updateproduct?start={$last2}",[],false);
		exit("ok");
	}
	
	//新增mopub 收益
	public function getMopub($start="")
	{
		if( $start=="" )
		{
			$start = date("Y-m-d",strtotime("-2 day"));
		}
		$url="https://app.mopub.com/reports/custom/api/download_report?report_key=05b884dd4d3d4d86861d3b383bc9bd3c&api_key=zpXtlWGSNQTqzFDDzqxfEHcVaY7CP1DS&date={$start}";
		$list = $this->csvJSON($url);
		$output =[];
		if( !empty($list) )
		{
            Db::name("adcash_data")->where(["platform"=>37,"date"=>$start])->delete();
			foreach($list as $vv )
			{
				if($vv["Line Item Type"]=="Marketplace")
				{
					$adtype ="no";							
					if( preg_match("/INT/",$vv["AdUnit"] ) )
					{
						$adtype="int";
					}elseif( preg_match("/RV/",$vv["AdUnit"] ) )
					{
						$adtype="rew";
					}elseif( preg_match("/ban/i",$vv["AdUnit"] ) )
					{
						$adtype="ban";
					}
                   $insert_data =array(									     
						 "platform"=>37,
						 "date"=>$vv["Day"],
						 "app_id"=>$vv["App ID"],
						 "click"=>$vv["Clicks"],
						 "app_name"=>$vv["App"],
						 "impression"=>$vv["Impressions"],
						 "adtype"=>$adtype,
						 "unit_id"=>$vv["AdUnit ID"],
						 "revenue"=>round($vv["Revenue"],4),
						 "country"=>strtoupper($vv["Country"])
				    );
				     //$rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"unit_id"=>$insert_data["unit_id"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
					  
				    $insert_data["sys_app_id"] = getappidbycampaign($insert_data["app_id"],$insert_data["platform"],1);
					Db::name("adcash_data")->insert($insert_data);
					  				
				}
			}
		}
		exit("ok");
	}
	
	function csvJSON($content) {
		
		$lines = array_map('str_getcsv',file($content) );

		$result = array();
		$headers;
		if (count($lines) > 0) {
			$headers = $lines[0];
		}
		
		for($i=1; $i<count($lines); $i++) {
			$obj = $lines[$i];
			$result[] = array_combine($headers, $obj);
		}
		return $result;
	}
	
	//新增 adcolony 2019-06-19
	public function getAdcolony($start="")
	{
		if( $start=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
		}
		header("Content-type: text/html; charset=utf-8");
		$start =date("mdY",strtotime($start));
		$url="http://clients-api.adcolony.com/api/v2/publisher_summary?user_credentials=tuCtcBVQ9l5v1qTjNEbn&date={$start}&end_date={$start}&format=json&date_group=day&group_by=country,app,zone";
		$content =$this->googlecurl($url);
		$data = json_decode($content,true);
		if( isset($data["results"]) )
		{
			$list = $data["results"];
			if( !empty($list) )
			{
				 foreach(  $list as $vv )
				 {
					if(isset($vv["app_name"])){
					$sys_app_id ="";
					//FishGo_143_AAdcolony_Android_RV_Bidding
					$arr = explode("_",$vv["zone_name"]);
					$sys_app_id = isset($arr[1])?$arr[1]:"";
					$adtype ="no";							
					if( preg_match("/INT/",$vv["zone_name"] ) )
					{
						$adtype="int";
					}elseif( preg_match("/RV/",$vv["zone_name"] ) )
					{
						$adtype="rew";
					}
					$insert_data =array(									     
							 "platform"=>31,
							 "date"=>$vv["date"],
							 "sys_app_id"=>$sys_app_id,
							 "app_id"=>$vv["app_id"],
							 "click"=>$vv["clicks"],
							 "app_name"=>$vv["app_name"],
							 "impression"=>$vv["impressions"],
							 "adtype"=>$adtype,
							 "request"=>$vv["requests"],
							 "unit_id"=>$vv["zone_id"],
							 "fill_rate"=>$vv["fill_rate"],
							 "revenue"=>$vv["earnings"],
							 "country"=>$vv["country"]?strtoupper($vv["country"]):"no"
						);
					 $rs = Db::name("adcash_data")->where( ["platform"=>$insert_data["platform"],"unit_id"=>$insert_data["unit_id"],"app_id"=>$insert_data["app_id"],"date"=>$insert_data["date"],"country"=>$insert_data["country"] ] )->find();
					  if( empty($rs) )
					  {
						Db::name("adcash_data")->insert($insert_data); 
					  }else{
						 Db::name("adcash_data")->where( "id",$rs["id"])->update( $insert_data );  
					  }
                    }					  
				 }
			}
		}
		$this->getKuai($start,$start);
		exit("ok");
	}
	
	function signature_gen($secure_key, $timestamp, $nonce)
	{     
		 $keys = array($secure_key, $timestamp, $nonce);     
		 sort($keys,2);     
		 $keyStr = implode('',$keys);     
		 return sha1($keyStr);
	}
	
	public function addWorkData($start="")
	{
		if( $start=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
		}
		
		$appid=112;
		
		//ROI 90%概率控制在70%~95%，5%概率控制在 50%~70%，5%概率控制在 95%~102%  arpdau 0.15-0.3  90%控制在0.18-0.25直接
		$roi = $this->creatRoi();
		$arpdau = $this->creatArp();
		$dau =0;
		
		
		$t = Db::name("active_users")->field("ceil(val) as val")->where( ["app_id"=>$appid,"date"=>$start,"country"=>"all"] )->find();
		if( !empty($t) )
		{
			$dau = $t["val"]?$t["val"]:0;
			$dau = ceil( $dau*0.8);
		}
		
		$v =round($dau*$arpdau,2);
		
		$spend = round( $v*100/$roi,2);
		
		//print_r( ["spend"=>$spend,"revenue"=>$v,"dau"=>$dau,"roi"=>$roi,"arpdau"=>$arpdau ] );exit;
		
		$r = Db::name("walk_data")->where( ["appid"=>$appid,"country"=>"all","date"=>$start] )->find();
		if( !empty($r) )
		{
			Db::name("walk_data")->where( ["id"=>$r["id"]] )->update( ["spend"=>$spend,"revenue"=>$v] );
		}else{
			Db::name("walk_data")->insert( ["spend"=>$spend,"revenue"=>$v,"country"=>"all","date"=>$start,"appid"=>$appid] );
		}
		
		exit("ok");
	}
	
	//生成arpdau
	private function creatArp()
	{
		$D=[];
		$i=1;
		while( $i<=90 )
		{
			$D[] =$this->randomFloat("0.18","0.25",3);
			$i++;
		}
		$i =1;			
		while( $i<=5 )
		{
			$D[] =$this->randomFloat("0.15","0.18",3);
			$i++;
		}
		$i =1;
		while( $i<=5 )
		{
			$D[] =$this->randomFloat("0.25","0.3",3);
			$i++;
		}
		shuffle($D);
		return $D[rand(0,99)];
	}
	
	//生成Roi
	private function creatRoi()
	{
	    $D=[];
		$i=1;
		while( $i<=90 )
		{
			$D[] =$this->randomFloat(70,95);
			$i++;
		}
		$i =1;			
		while( $i<=5 )
		{
			$D[] =$this->randomFloat(50,70);
			$i++;
		}
		$i =1;
		while( $i<=5 )
		{
			$D[] =$this->randomFloat(95,102);
			$i++;
		}
		shuffle($D);
		return $D[rand(0,99)];
	}
	
	function randomFloat($min = 0, $max = 1,$f="2") {
   	 $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
   	 return sprintf("%.{$f}f",$num);
	}	
	
	private function curl_request($url,$httpHeader,$ispost=true,$data=[])
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
		if( $ispost )
		{
			curl_setopt($ch, CURLOPT_POST,1);
			if(!empty($data))
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
			}
		}		 
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret =  curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
	}
	
	
	//发送邮箱
	public function sendemail($type="1")
	{
		
		$result = Db::name("admin")->field("id,truename,email,ad_role,allow_applist")->where("email!='' and ad_role in('super','advertiser','producter')")->select();
		if( !empty($result) )
		{
			foreach(  $result as $vv )
			{
				
				$html =file_get_contents("http://console.gamebrain.io/adcash/getsummary?type={$type}&user=".json_encode($vv));
				if($html && $html!='false' && in_array($vv["ad_role"],["super","advertiser","producter"]) )
				{
					$title ="【GameBrain产品".($type=="1"?'日':'周')."报】";
					send_mail( $vv["email"],$vv["email"],$title,$html,"GameBrain" );
				}
			}
		}
		echo "ok";
	}
	
	public function getsummary($user="",$type="1")
	{
		$user  =json_decode($user,true);
		//$user = ["id"=>"1","truename"=>"李雄飞","email"=>"","ad_role"=>"super","allow_applist"=>""];
		$ad_role = $user["ad_role"];
		$id = $user["id"];
		$allow_applist = $user["allow_applist"];
		$where="1=1";
		if( $type=="1" )
		{
			$date = date("Y-m-d",strtotime("-1 day"));
			$where.=" and date='{$date}'";
			$title ="昨天汇总概览";
		}elseif($type=="2"){
			$start = date("Y-m-d",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y")));
			$end = date("Y-m-d",mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y")));
			$title ="上周汇总概览";
			$date = $start." 到 ".$end;
			$where.=" and date>='{$start}' and date<='{$end}'";
		}
		if( in_array($ad_role,["super","advertiser","producter"]) )
		{
			if( $ad_role!="super" )
			{
				if( !$allow_applist )
				{
					return false;
				}
				$where.=" and app_id in({$allow_applist})";
			}
			$result = Db::query(" select app_id,sum(revenue) as revenue,sum(spend) as spend from hellowd_summary_data where {$where} group by app_id order by spend desc");
			
			if( !empty( $result ) )
			{
				$spend ="0.00";
				$revenue="0.00";
				foreach( $result as &$vv )
				{
					$r = Db::name("app")->field('id,app_name,platform,icon_url,app_base_id')->find( $vv["app_id"] );
					$r["name"] = $r["app_name"]."-".$r["platform"];
					$r["icon_url"] = getdomainname().$r["icon_url"];
					if(  $r["id"]>154 )
					{
						if( $r["app_base_id"] )
						{
							$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
							$r["name"] = $row["name"].' - '.$r["platform"];
							$r["icon_url"] = getdomainname().$row["icon"];
						}
					}
					$vv["spend"] = round($vv["spend"],2);
					$vv["revenue"] = round($vv["revenue"],2);
					$vv["roi"] = $vv["spend"]>0?round( $vv["revenue"]*100/$vv["spend"],2):"0";
					$vv = array_merge($vv,$r);
					$spend+=$vv["spend"];
					$revenue+=$vv["revenue"];					
				}
				$roi = $spend>0?round( $revenue*100/$spend,2):"0";
				$img_src = getdomainname()."/icon/{$id}.png";
				$assign = array(
				   "title"=>$title,
				   "date"=>$date,
				   "spend"=>round($spend,2),
				   "revenue"=>round($revenue,2),
				   "roi"=>$roi,
				   "img_src"=>$img_src,
				   "list"=>$result
		        );
				return view("summary",$assign);
			}
		}
       return false;		
	}
	
	public function test()
	{
		/* $key = 'b9514c52-5363-4364-b73f-a2ec93ae6b34';
		$plaintext = "wuhantest";
        $cipher = "AES-128-CBC";
		 $ciphertext =base64_encode( openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, substr($key, 0, 16)) );
		 echo $ciphertext;exit;
		 //解密
		$c = base64_decode($ciphertext);
		$original_plaintext = openssl_decrypt($c,$cipher, $key,OPENSSL_RAW_DATA, substr($key, 0, 16) );
		echo $original_plaintext;exit;  */
		//$html = file_get_contents("http://cnf.mideoshow.com/adcash/getsenddata");
		
		//echo send_mail("lixiongfei@hellowd.net","lixiongfei@hellowd.net", 'test',$html );
		//$admob  =new Admobrequest();
		//$res = $admob->getdata();
		//print_r($res);exit;
		$facebook  =new Facebookrequest();
		$facebook->test();
	}
	
	
	public function callback()
	{
		echo "ok";
	}
	
	//收益数据汇率填写提醒
	public function notify(){
		$emailList =["lixiongfei@hellowd.net","yujiugang@hellowd.net"];
		$mon =date("m");
		foreach(  $emailList as $vv )
		{
			send_mail( $vv,$vv,"【{$mon}月提醒】GB收益数据汇率填写提醒通知","<strong style='color:red;'>请及时填写GB系统收益数据{$mon}月汇率</strong>","GameBrain" );
		}
		$webhook = "https://oapi.dingtalk.com/robot/send?access_token=0faf93d5cec8ebf9e0f261d5d160f2120ab8aebc9d54ca6f12ea4345f3656025";
		$message="【{$mon}月收益提醒】：这个月的汇率还没有填写哦，请及时填写";
		$data = array ('msgtype' => 'text','text' => array ('content' => $message),"at"=>["atMobiles"=>["15027075696"],"isAtAll"=>false]);
		$data_string = json_encode($data);
		exit("ok");
	}
}
 