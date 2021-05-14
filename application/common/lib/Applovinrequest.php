<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;

  //Applovin 请求类
class Applovinrequest
{
    //请求api公共地址 
	const BASIC_REQUEST_URL ="https://r.applovin.com/report";
	
	const apikey = "xMbVp7OZaTI_RJs4ZKN0Vxp2IZkM-BsvXSPKZov9KC9mV_r4KD4r8utQRanPndJI5idfUXppkzZEyOIHcN6lF-";
	
    //广告成效数据
	public function request($page,$start,$end)
	{
		$start = $start;
		$end = $end;
		$limit =2500;
		$offset =($page-1)*$limit;
		$having = urlencode("impressions > 0");
		$url =self::BASIC_REQUEST_URL."?api_key=".self::apikey."&start={$start}&end={$end}&columns=day,impressions,clicks,ctr,revenue,ecpm,country,ad_type,zone_id,platform,application,package_name&format=json&report_type=publisher&limit={$limit}&offset={$offset}&having={$having}";
		$list = $this->curl($url);
		return $list;
	}
   //广告推广数据
    public function ads_promote($page,$start,$end)
	{
		//adgroup,app_id_external,
		$start = $start;
		$end = $end;
		$limit =10600;
		$offset =($page-1)*$limit;
		$having = urlencode("cost > 0");
		$url =self::BASIC_REQUEST_URL."?api_key=".self::apikey."&start={$start}&end={$end}&columns=adgroup,app_id_external,day,impressions,ad,ad_id,clicks,ctr,conversions,average_cpa,country,campaign,cost,campaign_package_name,platform,campaign_id&format=json&report_type=advertiser&limit={$limit}&offset={$offset}&having={$having}";
		$list = $this->curl($url);
		return $list;
	}
   private function curl($action,$headers=[]) {
        $httpHeader = $headers;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params) );
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
	
}
