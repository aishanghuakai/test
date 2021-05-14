<?php
namespace app\api\controller;
use think\Db;
use \think\Request;

set_time_limit(0);
ini_set('memory_limit', '-1');
class Appsflyer 
{
   	
	private function getallAppids()
	{
		return array(
		    //["name"=>"走遍世界","appID"=>"id1448462544","gb_id"=>"132"],
			//["name"=>"Spiral Rush Go","appID"=>"id1382192224","gb_id"=>"68"],
			//["name"=>"Fish Go IOS","appID"=>"id1485195465","gb_id"=>"143","platform"=>"ios"],
			["name"=>"物理卡车","appID"=>"id1447478335","gb_id"=>"114","platform"=>"ios"],
            //["name"=>"Fish Go android","appID"=>"com.whitedot.bfg","gb_id"=>"147","platform"=>"android"]			
		);
	}
	
	public function report($from="",$to="")
	{	
		if( $from=="" || $to=="" )
		{
			$from = date("Y-m-d",strtotime("-1 day"));
			$to = date("Y-m-d",strtotime("-1 day"));
		}		
		$ids = $this->getallAppids();
		if( !empty($ids) )
		{
			foreach( $ids as $v )
			{
				$appID = $v["appID"];
				$gb_id = $v["gb_id"];
				$platform = $v["platform"];
				$result = $this->request($appID,$from,$to);
				if( !empty($result) )
				{
					foreach( $result as &$r )
					{
						$r = $this->setField($r,$platform);
						$r["gb_id"] = $gb_id;
						$row = Db::name("appsflyer")->where(["e_hash"=>$r["e_hash"]])->find();
						if( empty($row) )
						{
							Db::name("appsflyer")->insert($r);
						}
						
					}
				}
			}
		}
		exit("ok");
	}
	
	public function impression_report($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
			$end = date("Y-m-d",strtotime("-1 day"));
		}
		$sql ="SELECT gb_id,media_source,adtype,install_date,event_date,ad_source,country,device_category,count(*) as num from hellowd_appsflyer where event_date>='{$start}' 
and event_date<='{$end}' GROUP BY ad_source,install_date,event_date,country,device_category,media_source,adtype,gb_id";
		$res = Db::query($sql);
		if(!empty($res))
		{
			foreach( $res as &$r )
			{
				$one = $r;
				unset($r["num"]);
				$row = Db::name("appsflyer_impression")->where($r)->find();
				if( empty($row) )
				{
					Db::name("appsflyer_impression")->insert($one);
				}else{
					Db::name("appsflyer_impression")->where($r)->update(["num"=>$r["num"]]);
				}			
			}
		}
		exit("ok");
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
		print_r($requestUrl);exit;
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
	
	private function setField($row,$platform)
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
	   $key = $this->field_split_guid($row["Event Value"],$platform);
	   if( $key )
	   {
		   //SigmobRewardedVideo_Reward_iPhone
		   //AdmobInterstitial_Inter_iPad
		    $toArray = explode("_",$key);
			$arr["adtype"] = isset($toArray[1])?$toArray[1]:"";
			$arr["device_category"] = isset($toArray[2])?$toArray[2]:"";
			$arr["ad_source"] =str_replace(["RewardedVideo","Interstitial"],"",isset($toArray[0])?$toArray[0]:"");
	   }
	   $arr["e_hash"] = $this->getSignMd5($arr);
	   return $arr;
	}
	
	private function field_split_guid($value,$platform)
	{
		if( $value )
		{
			$arr_fields = explode(":",$value);
			$index = ($platform=="ios")?0:1;
			if( isset($arr_fields[$index]) )
			{
				$key =str_replace(['{"','"'],"",$arr_fields[$index]);
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
 