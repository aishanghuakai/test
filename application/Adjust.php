<?php
namespace app\api\controller;
use think\Db;
use \think\Request;

set_time_limit(0);
ini_set('memory_limit', '-1');
class Adjust 
{
   	private $apps = array(
	      ["app_id"=>154,"key"=>"xevly0673uv4"],
		  ["app_id"=>153,"key"=>"xzephw6694ow"],
		  ["app_id"=>143,"key"=>"8s8nbdme5m9s"],
		  ["app_id"=>127,"key"=>"3ed8qzs19ygw"]
	);
	 //推广渠道
	private $promate_media =array(
	    "Mintegral_int"=>"mintegral_int",
		"头条"=>"bytedance_int",
	   "Facebook Installs"=>"Facebook Ads",
	   "Unity_int"=>"unityads_int",
	   "Applovin_int"=>"applovin_int",
	   "ironsource_int"=>"ironsource_int",
       "Vungle_int"=>"vungle_int",
	   'Adwords UAC Installs'=>"googleadwords_int",
	   'Tapjoy_int'=>"tapjoy_int",
	   'Chartboost_int'=>"chartboosts2s_int",
	   'Tiktok_int'=>"tiktok_int",	   
	);
	
	public function report()
	{	
	  $input = input('get.');
	  /* $file_path =dirname(__FILE__)."/log1.txt";//字体
	   if(!empty($input))
	  {
		   file_put_contents($file_path,http_build_query($input)."\n\r",FILE_APPEND);
	  } */
	  if( !empty($input) )
	  {
		  $input["country"] = strtoupper($input["country"]);
		  $input["install_date"] = date("Y-m-d",$input["install_time"]);
		  $input["event_date"] = date("Y-m-d",$input["event_time"]);		  
		  if( isset($input["HwAds"]) )
		  {
			  $input["event_value"] = $input["HwAds"];
			  list($a,$b,$c) = explode("_",$input["HwAds"]);
			  $input["adtype"] = $b;
			  $input["device_category"] = $c;
			  $input["ad_source"] =$a;
			  $arr = explode("_",$input["app_name"]);
			  if(isset($arr["1"]))
			  {
				  $input["gb_id"] = $arr["1"];
			  }
			  unset($input["HwAds"],$input["tracker"]);
			  $input["e_hash"] = $this->getSignMd5($input);
			  Db::name("adjust")->insert($input);
		  }
		   
	  }		  
	  echo json_encode(["code"=>200,"message"=>"success"]);
	}
	
	public function impression_report($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end = date("Y-m-d",strtotime("-1 day"));
		}
		$sql ="SELECT gb_id,media_source,adtype,install_date,event_date,ad_source,country,device_category,count(*) as num from hellowd_adjust where  event_date>='{$start}' 
and event_date<='{$end}' GROUP BY ad_source,install_date,event_date,country,device_category,media_source,adtype,gb_id";
		$res = Db::query($sql);
		if(!empty($res))
		{
			foreach( $res as &$r )
			{
				if( isset($this->promate_media[$r['media_source']]) )
				{
					$r['media_source'] = $this->promate_media[$r['media_source']];
				}
				if(trim($r["ad_source"])=="GooglePlayServices")
				{
					$r['ad_source'] = "Admob";
				}
				if( in_array($r["ad_source"],['VastVideo','Mraid','MoPubRewardedPlayabl']) )
				{
					$one['ad_source'] = "MoPub";
				}
				if( in_array($r["ad_source"],['Unity','Unity Ads']) )
				{
					$one['ad_source'] = "UnityAds";
				}
				if(trim($r["ad_source"])=="MINTEGRAL")
				{
					$one['ad_source'] = "Mintegral";
				}
				if(trim($r["ad_source"])=="FACEBOOK")
				{
					$one['ad_source'] = "Facebook";
				}
				if(trim($r["ad_source"])=="Google AdMob")
				{
					$one['ad_source'] = "Admob";
				}				
				Db::name("adjust_impression")->insert($r);						
			}
		}
		exit("ok");
	}
	
	public function campaign_report($start="",$end=""){
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end = date("Y-m-d",strtotime("-1 day"));
		}
		$url = "https://analytics.gamebrain.io/adjust/campaign_report?start={$start}&end={$end}";
		$result = json_decode(curl($url),true);
		if( isset($result["data"]) && !empty($result["data"]))
		{
			$res = $result["data"];
			if(!empty($res))
			{
				foreach( $res as $r )
				{
					$one = $r;
					if( in_array($r["media_source"],['Facebook Installs','Instagram Installs','Off-Facebook Installs']) )
					{
						$one["media_source"] = "Facebook Ads";
					}
					if( $r["media_source"]=='Adwords UAC Installs' )
					{
						$one["media_source"] ='googleadwords_int';
					}
					if(trim($r["ad_source"])=="GooglePlayServices")
					{
						$one['ad_source'] = "Admob";
					}
					if( in_array($r["ad_source"],['VastVideo','Mraid','MoPubRewardedPlayabl']) )
					{
						$one['ad_source'] = "MoPub";
					}
					$pattern = "/(?:\()(.*)(?:\))/i";
					preg_match($pattern,$r['campaign_name'],$arr);
					if( !empty($arr) && isset($arr[1]) )
					{
						$one["campaign_id"] = $arr[1];
						Db::name("adjust_campaign")->insert($one);
					}					
				}
			}	
		}
		exit("ok");
	}
	
	public function syncReport($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end = date("Y-m-d",strtotime("-1 day"));
		}
		$url = "https://analytics.gamebrain.io/adjust/impression_report?start={$start}&end={$end}";
		$result = json_decode(curl($url),true);
		if( isset($result["data"]) && !empty($result["data"]))
		{
			$res = $result["data"];
			if(!empty($res))
			{
				foreach( $res as &$r )
				{
					if( isset($this->promate_media[$r['media_source']]) )
					{
						$r['media_source'] = $this->promate_media[$r['media_source']];
					}
					if( in_array($r["media_source"],['Instagram Installs','Unattributed','Off-Facebook Installs']) )
					{
						$r['media_source'] = "Facebook Ads";
					}
					if(trim($r["ad_source"])=="GooglePlayServices")
					{
						$r['ad_source'] = "Admob";
					}
					if( in_array($r["ad_source"],['VastVideo','Mraid','MoPubRewardedPlayabl']) )
					{
						$r['ad_source'] = "MoPub";
					}
					if( in_array($r["ad_source"],['Unity','Unity Ads']) )
					{
						$r['ad_source'] = "UnityAds";
					}
					if(trim($r["ad_source"])=="MINTEGRAL")
					{
						$r['ad_source'] = "Mintegral";
					}
					if(trim($r["ad_source"])=="FACEBOOK")
					{
						$r['ad_source'] = "Facebook";
					}
					if(trim($r["ad_source"])=="Google AdMob")
					{
						$r['ad_source'] = "Admob";
					}				
					Db::name("adjust_impression")->insert($r);		
				}
			}
			exit("ok");
		}
		exit("fail");
	}
	
	public function device_reoprt($start="",$end=""){
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-2 day"));
			$end = date("Y-m-d",strtotime("-2 day"));
		}
		$apps = $this->apps;
		$apps[] =["app_id"=>93,"key"=>"q4cie8ourif4"];
		$apps[] =["app_id"=>77,"key"=>"j8h5sltmiry8"];
		foreach($apps as $a)
		{
			$this->get_device_reoprt($a['app_id'],$a['key'],$start,$end);
		}
		exit("ok");
	}
		
	private function get_device_reoprt($app_id,$key,$start,$end)
	{
		
		$url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=08:00&kpis=installs,cost&attribution_type=click&grouping=networks,countries,device_types&user_token=zDxyxVAafpjFX-yYMbvK";
		$result = json_decode(curl($url),true);
		if( isset($result["result_set"]['networks']) && !empty($result["result_set"]['networks']))
		{
			$res = $result["result_set"]['networks'];
			if(!empty($res))
			{
				foreach( $res as $r )
				{					
					foreach($r["countries"] as $c)
					{
						if(!empty($c['device_types']))
						{
							foreach($c['device_types'] as $d)
							{
								if(!empty($d['kpi_values']))
								{
									$params =array(
									   "gb_id"=>$app_id,
									   "device_category"=>$d['device_type']=='tablet'?'ipad':$d['device_type'],
									   "installs"=>$d['kpi_values'][0],
									   "install_date"=>$start,
									   "networks"=>$r["name"],
									   "spend"=>round($d['kpi_values'][1],2),
									   "country"=>strtoupper($c["country"])
									  );
									$row = Db::name("adjust_device")
									       ->where(["gb_id"=>$params["gb_id"],"device_category"=>$params["device_category"],"install_date"=>$params['install_date'],"networks"=>$params['networks'],"country"=>$params['country'] ])
										   ->find();
									if( empty($row) )
									{
										Db::name("adjust_device")->insert($params);
									}else{
										Db::name("adjust_device")->where("id",$row["id"])->update($params);
									}
								}								
							}
						}
					}					
				}
			}
		}
	}
	
	
	public function reten_day_report($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end = date("Y-m-d",strtotime("-1 day"));
		}
		foreach($this->apps as $a)
		{
			$this->get_reten_day_report($a['app_id'],$a['key'],$start,$end);
		}
		exit("ok");
	}
	
	private function get_reten_day_report($app_id,$key,$start,$end)
	{
		
		$url = "https://api.adjust.com/kpis/v1/{$key}/cohorts.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=retention_rate&attribution_type=click&grouping=day&period=day&cohort_period_filter=1-30&user_token=zDxyxVAafpjFX-yYMbvK";
		$result = json_decode(curl($url),true);
		if( isset($result["result_set"]['dates']) && !empty($result["result_set"]['dates']))
		{
			$data = $result["result_set"]['dates'][0]["periods"];
			if(!empty($data))
			{
				foreach($data as $v)
				{
					$params =array(
					   "date"=>$result["result_set"]['dates'][0]["date"],
					   "app_id"=>$app_id,
					   "country"=>"all",
					);
					$params["retention_{$v['period']}"] = $v["kpi_values"][0];
					$row = Db::name("retention")->where(["date"=>$params["date"],"app_id"=>$params["app_id"],"country"=>$params["country"] ])->find();							      
					if( empty($row) )
					{
						Db::name("retention")->insert($params);
					}else{
						Db::name("retention")->where("id",$row["id"])->update($params);
					}					
				}
			}
		}
	}
	
	public function sync_data($start=""){
		if( $start=="")
		{
			$start = date("Y-m-d",strtotime("-1 day"));
		}
		$host = getdomainname();
		$list = Db::name("bind_attr")->field("app_id,adjust")->where("adjust!=''")->select();
		if( !empty($list) )
		{
			 foreach($list as $g)
			 {				 
				 $url_total = $host."/adjust/sync_day_report";
				 $url_country = $host."/adjust/sync_country_report";
				 $g["start"] = $start;
				 syncRequest($url_total,$g);
				 syncRequest($url_country,$g); 
			 }			 
		}
		$device_reoprt_url =$host."/adjust/device_reoprt";
		syncRequest($device_reoprt_url,[]);
		exit("ok");
	}
	
	public function sync_day_report(Request $request){
		$params = $request->param();
		if( !empty($params) )
		{
			$this->get_day_report($params['app_id'],$params['adjust'],$params["start"],$params["start"]);
		}
		exit("ok");
	}
	
	public function sync_country_report(Request $request){
		$params = $request->param();
		if( !empty($params) )
		{
			$this->get_country_report($params['app_id'],$params['adjust'],$params["start"],$params["start"]);
		}
		exit("ok");
	}
	
	
	private function get_day_report($app_id,$key,$start,$end)
	{		
		$url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=installs,daus&attribution_type=click&grouping=day&user_token=zDxyxVAafpjFX-yYMbvK";
		$result = json_decode(curl($url),true);
		if( isset($result["result_set"]['dates']) && !empty($result["result_set"]['dates']))
		{
			$data = $result["result_set"]['dates'];
			if(!empty($data))
			{
				foreach($data as $v)
				{
					$params =array(
					   "date"=>$v["date"],
					   "app_id"=>$app_id,
					   "country"=>"all",
					);
					$params["val"] = $v["kpi_values"][1];
					$row = Db::name("active_users")->where(["date"=>$params["date"],"app_id"=>$params["app_id"],"country"=>$params["country"] ])->find();							      
					if( empty($row) )
					{
						Db::name("active_users")->insert($params);
					}else{
						Db::name("active_users")->where("id",$row["id"])->update($params);
					}
					
					$params["val"] = $v["kpi_values"][0];
					$row = Db::name("new_users")->where(["date"=>$params["date"],"app_id"=>$params["app_id"],"country"=>$params["country"] ])->find();							      
					if( empty($row) )
					{
						Db::name("new_users")->insert($params);
					}else{
						Db::name("new_users")->where("id",$row["id"])->update($params);
					}
				}
			}
		}
	}
	
	private function get_country_report($app_id,$key,$start,$end)
	{
		
		$url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=installs,daus&attribution_type=click&grouping=countries&user_token=zDxyxVAafpjFX-yYMbvK";
		$result = json_decode(curl($url),true);
		if( isset($result["result_set"]['countries']) && !empty($result["result_set"]['countries']))
		{
			$data = $result["result_set"]['countries'];
			if(!empty($data))
			{
				foreach($data as $v)
				{
					$params =array(
					   "date"=>$start,
					   "app_id"=>$app_id,
					   "country"=>strtoupper($v["country"]),
					);
					$params["val"] = $v["kpi_values"][1];
					$row = Db::name("active_users")->where(["date"=>$params["date"],"app_id"=>$params["app_id"],"country"=>$params["country"] ])->find();							      
					if( empty($row) )
					{
						Db::name("active_users")->insert($params);
					}else{
						Db::name("active_users")->where("id",$row["id"])->update($params);
					}
					
					$params["val"] = $v["kpi_values"][0];
					$row = Db::name("new_users")->where(["date"=>$params["date"],"app_id"=>$params["app_id"],"country"=>$params["country"] ])->find();							      
					if( empty($row) )
					{
						Db::name("new_users")->insert($params);
					}else{
						Db::name("new_users")->where("id",$row["id"])->update($params);
					}
				}
			}
		}
	}
	
	private function request($appID,$from,$to)
	{
		$apiToken ="5331274b-ab11-493a-9830-1e05367f045a";
		$reportType = 'in_app_events_report';
		$query = http_build_query([
		'api_token' => $apiToken,
		'from' => $from,
		'to' => $to,
		'fields'=>'country_code,install_time,event_time,event_name,event_value,media_source,af_channel,campaign,af_c_id,af_adset,af_adset_id,af_ad,af_ad_id,app_id,advertising_id,idfa,device_type,appsflyer_id',
		'event_name'=>'AFHwAds',
		]);
		$requestUrl = 'https://hq.appsflyer.com/export/' . $appID . '/' . $reportType . '/v5?'.$query;
		$list = $this->csvJSON($requestUrl);
		//$content =file_get_contents($this->get_redirect_url($requestUrl));
		//$file =$_SERVER['DOCUMENT_ROOT']."/appsflyer/report_".time().".csv";
		//$r = file_put_contents($file,$content, FILE_APPEND);
		/* if($r!==false)
		{
			$list = $this->csvJSON($file);
			if( $list!='' )
			{
				unlink($file);
	            return $list;
			}
		} */
		return $list;
	}
	
	
	private function getSignMd5($param)
	{
		$signstr = '';
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                 if ($value == '') {
                    continue;
               }
                $signstr .=$value;
            }
        }
		return md5($signstr);
	}
	
	private function setField($row)
	{
       $arr =array(
	      "country"=>($row["Country Code"]=="UK")?"GB":$row["Country Code"],
		  "install_time"=>$row["Install Time"],
		  "install_date"=>date("Y-m-d",strtotime($row["Install Time"])),
		  "event_date"=>date("Y-m-d",strtotime($row["Event Time"])),
		  "event_time"=>$row["Event Time"],
		  "event_value"=>$row["Event Value"],
		  "media_source"=>$row["Media Source"],
		  "campaign_name"=>$row["Campaign"],
		  "campaign_id"=>$row["Campaign ID"],
		  "adset_name"=>$row["Adset"],
		  "adset_id"=>$row["Adset ID"],
		  "ad_id"=>$row["Ad ID"],
		  "ad_name"=>$row["Ad"], 
		  "app_id"=>$row["App ID"],
          "idfa"=>$row["IDFA"],
		  "advertising_id"=>$row["Advertising ID"],
          "device_type"=>$row["Device Type"],
          "af_id"=>$row["AppsFlyer ID"],
	   );
	   $key = $this->field_split_guid($row["Event Value"]);
	   if( $key )
	   {
		   //SigmobRewardedVideo_Reward_iPhone
		   //AdmobInterstitial_Inter_iPad
			list($a,$b,$c) = explode("_",$key);
			$arr["adtype"] = $b;
			$arr["device_category"] = $c;
			$arr["ad_source"] =str_replace(["RewardedVideo","Interstitial"],"",$a);
	   }
	   $arr["e_hash"] = $this->getSignMd5($arr);
	   return $arr;
	}
	
	private function field_split_guid($value)
	{
		if( $value )
		{
			$arr_fields = explode(":",$value);
			if( isset($arr_fields[0]) )
			{
				$key =str_replace(['{"','"'],"",$arr_fields[0]);
				return $key;
			}
		}
		return "";
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
	
	public function test()
	{
		$apiToken ="5331274b-ab11-493a-9830-1e05367f045a";
		$appID = 'id1460648111';
		$reportType = 'geo_report';
		$from = date("Y-m-d",strtotime("-2 day"));
		$to = date("Y-m-d",strtotime("-2 day"));
		$query = http_build_query([
		'api_token' => $apiToken,
		'from' => $from,
		'to' => $to,
		'fields'=>'impressions,clicks,installs,cr,sessions,loyal_users,loyal_users_rate,cost,revenue,roi,arpu_ltv'
		]);
		$requestUrl = 'https://hq.appsflyer.com/export/' . $appID . '/' . $reportType . '/v5?'.$query;
		echo $requestUrl;exit;
	}
}
 