<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;

  //Vungle请求类
class Vunglerequest
{
    //请求api公共地址 
	//const BASIC_REQUEST_URL ="https://stats.unityads.unity3d.com/organizations/58bf862279f9ef001e6be6af/reports/acquisitions";
	
	const apikey = "cdf06d3b297cd39ef95f4ca3bc699b13";   
	
	
	public function request($start="",$end="")
	{
		$start =$start;
		$end =$end;
		$url ="https://report.api.vungle.com/ext/pub/reports/performance?dimensions=date,country,application,platform,placement&aggregates=impressions,clicks,ecpm,completes,views,revenue&start={$start}&end={$end}";
		//$url.="&applicationId={$appid}";
		$headers = array(
            'Authorization:Bearer '.self::apikey,
            'Accept: application/json',
			'Vungle-Version:1'
           );
		$list = $this->curl($url,$headers);
		
		return $list;
	}
	
	//花费数据
	public function ads_request($start="",$end="")
	{
		$start =$start;
		$end =$end;
		$url ="https://report.api.vungle.com/ext/adv/reports/spend?dimensions=date,country,platform,campaign,site&aggregates=installs,impressions,clicks,spend&start={$start}&end={$end}";

		//$url.="&applicationId={$appid}";
		$headers = array(
            'Authorization:Bearer 07b192599e72179c9973442dafe71d3f',
            'Accept: application/json',
			'Vungle-Version:1'
           );
		$list = $this->curl($url,$headers);
		
		return $list;
	}
	
	public function getapp()
	{
		$headers = array(           
            'Content-Type: application/json'
           );
		$key = self::apikey;  
		$url ="https://ssl.vungle.com/api/applications?key={$key}&geo=all";
		$list = $this->curl($url,$headers);
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
