<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;
  //Unity 请求类
class Unityrequest
{
    //请求api公共地址 
	const BASIC_REQUEST_URL ="https://gameads-admin.applifier.com/stats/monetization-api";
	
	const apikey = "b3b56425f96106cc84d0b1c12506ea30dd6863a9d2566c746dc03b0d92102aff";
	
	
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
		
	public function request($start,$end)
	{
		$url =self::BASIC_REQUEST_URL."?apikey=".self::apikey;
		$url.="&splitBy=date,source,country,zone&fields=adrequests,available,views,revenue,platform,started";
        $url.="&start={$start}&end={$end}&scale=day";
		
		$content =file_get_contents($this->get_redirect_url($url));
		$file =$_SERVER['DOCUMENT_ROOT']."/unity/report_".time().".csv"; 		
		$r = file_put_contents($file,$content, FILE_APPEND);
		if($r!==false)
		{
			$list = $this->csvJSON($file);
			if( $list!='' )
			{
				unlink($file);
               // print_r( json_decode($list) );exit;				
	            return $list;
			}
		}
		return [];
	}

    //推广数据
    public function ads_promote($start,$end)
	{
		$apikey ="542fa73c8e421d28b74df4ed5442d646b1a16776dab4dbbcaabc38c5785dedb9";
		$organizationId ="58bf862279f9ef001e6be6af";
		$url ="https://stats.unityads.unity3d.com/organizations/{$organizationId}/reports/acquisitions?apikey=".$apikey;
		$url.="&splitBy=campaign,target,country,platform,sourceAppId&fields=sourceAppId,timestamp,campaign,country,platform,starts,clicks,installs,spend,target";
        $url.="&start={$start}&end={$end}&scale=day";
		$content = file_get_contents($url);
		//print_r($content);exit;
		$file =$_SERVER['DOCUMENT_ROOT']."/unity/ads_report_".time().".csv"; 
		$r = file_put_contents($file,$content, FILE_APPEND);		
		if($r!==false)
		{
			$list = $this->csvJSON($file);
			if( $list!='' )
			{
				unlink($file);			
	            return $list;
			}
		}		
		return [];
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
		return json_encode($result, JSON_PRETTY_PRINT);
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
