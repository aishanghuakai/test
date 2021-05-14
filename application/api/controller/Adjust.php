<?php

namespace app\api\controller;

use think\Db;
use \think\Request;

set_time_limit(0);
ini_set('memory_limit', '-1');

class Adjust
{
    private $apps = array(
        ["app_id" => 154, "key" => "xevly0673uv4"],
        ["app_id" => 153, "key" => "xzephw6694ow"],
        ["app_id" => 143, "key" => "8s8nbdme5m9s"],
        ["app_id" => 127, "key" => "3ed8qzs19ygw"]
    );
    //推广渠道
    private $promate_media = array(
        "Mintegral_int" => "mintegral_int",
        "头条" => "bytedance_int",
        "Facebook Installs" => "Facebook Ads",
        "Unity_int" => "unityads_int",
        "Applovin_int" => "applovin_int",
        "ironsource_int" => "ironsource_int",
        "Vungle_int" => "vungle_int",
        'Adwords UAC Installs' => "googleadwords_int",
        'Google Ads UAC' => "googleadwords_int",
        'Adwords Installs' => 'googleadwords_int',
        'Tapjoy_int' => "tapjoy_int",
        'Chartboost_int' => "chartboosts2s_int",
        'Tiktok_int' => "tiktok_int",
    );

    public function report()
    {
        $input = input('get.');
        /* $file_path =dirname(__FILE__)."/log1.txt";//字体
         if(!empty($input))
        {
             file_put_contents($file_path,http_build_query($input)."\n\r",FILE_APPEND);
        } */
        if (!empty($input)) {
            $input["country"] = strtoupper($input["country"]);
            $input["install_date"] = date("Y-m-d", $input["install_time"]);
            $input["event_date"] = date("Y-m-d", $input["event_time"]);
            if (isset($input["HwAds"])) {
                $input["event_value"] = $input["HwAds"];
                list($a, $b, $c) = explode("_", $input["HwAds"]);
                $input["adtype"] = $b;
                $input["device_category"] = $c;
                $input["ad_source"] = $a;
                $arr = explode("_", $input["app_name"]);
                if (isset($arr["1"])) {
                    $input["gb_id"] = $arr["1"];
                }
                unset($input["HwAds"], $input["tracker"]);
                $input["e_hash"] = $this->getSignMd5($input);
                Db::name("adjust")->insert($input);
            }

        }
        echo json_encode(["code" => 200, "message" => "success"]);
    }

    public function impression_report($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $sql = "SELECT gb_id,media_source,adtype,install_date,event_date,ad_source,country,device_category,count(*) as num from hellowd_adjust where  event_date>='{$start}' 
and event_date<='{$end}' GROUP BY ad_source,install_date,event_date,country,device_category,media_source,adtype,gb_id";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$r) {
                if (isset($this->promate_media[$r['media_source']])) {
                    $r['media_source'] = $this->promate_media[$r['media_source']];
                }
                if (trim($r["ad_source"]) == "GooglePlayServices") {
                    $r['ad_source'] = "Admob";
                }
                if (in_array($r["ad_source"], ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
                    $one['ad_source'] = "MoPub";
                }
                if (in_array($r["ad_source"], ['Unity', 'Unity Ads', 'Unityads'])) {
                    $one['ad_source'] = "UnityAds";
                }
                if (trim($r["ad_source"]) == "MINTEGRAL") {
                    $one['ad_source'] = "Mintegral";
                }
                if (trim($r["ad_source"]) == "FACEBOOK") {
                    $one['ad_source'] = "Facebook";
                }
                if (trim($r["ad_source"]) == "Google AdMob") {
                    $one['ad_source'] = "Admob";
                }
                Db::name("adjust_impression")->insert($r);
            }
        }
        exit("ok");
    }

    public function ad_analysis_report($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/ad_analysis_report?start={$start}&type=1";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                foreach ($res as $r) {
                    $arr = explode("#", $r["event_value"]);
                    $row = array(
                        "gb_id" => $r["gb_id"],
                        "country" => $r["country"],
                        "event_value" => $r["event_value"],
                        "num" => $r["num"],
                        "event_date" => $start,
                        "action" => isset($arr[0]) ? $arr[0] : "",
                        "adtype" => isset($arr[1]) ? $arr[1] : "",
                        "adsource" => isset($arr[2]) ? $arr[2] : "",
                        "unit_id" => isset($arr[3]) ? $arr[3] : "",
                        "scenes" => isset($arr[4]) ? $arr[4] : ""
                    );
                    Db::name("adjust_adanalysis")->insert($row);
                }
            }
        }
        exit("ok");
    }

    public function ad_report($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/ad_report?start={$start}&end={$end}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name("adjust_ad")->where(['event_date'=>$end])->delete();
                foreach ($res as $r) {
                    $one = $r;
                    $one['media_source'] = "Facebook Ads";
                    if (trim($r["ad_source"]) == "GooglePlayServices") {
                        $one['ad_source'] = "Admob";
                    }
                    if ($r["ad_source"] == "Minteral") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (preg_match("/MoPubRewardedPlayabl/", $r["ad_source"])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['Unity', 'Unity Ads', 'Unityads'])) {
                        $one['ad_source'] = "UnityAds";
                    }
                    if (trim($r["ad_source"]) == "MINTEGRAL") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (trim($r["ad_source"]) == "FACEBOOK") {
                        $one['ad_source'] = "Facebook";
                    }
                    if (trim($r["ad_source"]) == "Google AdMob") {
                        $one['ad_source'] = "Admob";
                    }
                    $arr = explode("(", $r["ad_name"]);
                    $one["ad_id"] = rtrim(end($arr), ")");
                    unset($one["ad_name"]);
                    Db::name("adjust_ad")->insert($one);
                }
            }
        }
        exit("ok");
    }

    public function adset_report($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/adset_report?start={$start}&end={$end}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name("adjust_adgroup")->where(['event_date'=>$end])->delete();
                foreach ($res as $r) {
                    $one = $r;
                    if (isset($this->promate_media[$r['media_source']])) {
                        $one['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (trim($r["ad_source"]) == "GooglePlayServices") {
                        $one['ad_source'] = "Admob";
                    }
                    if ($r["ad_source"] == "Minteral") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (preg_match("/MoPubRewardedPlayabl/", $r["ad_source"])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['Unity', 'Unity Ads', 'Unityads'])) {
                        $one['ad_source'] = "UnityAds";
                    }
                    if (trim($r["ad_source"]) == "MINTEGRAL") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (trim($r["ad_source"]) == "FACEBOOK") {
                        $one['ad_source'] = "Facebook";
                    }
                    if (trim($r["ad_source"]) == "Google AdMob") {
                        $one['ad_source'] = "Admob";
                    }
                    $one["adset_id"] = $one["adset_name"];
                    if ($r["media_source"] == "Vungle_int") {
                        $arr = explode("_", $r["adset_name"]);
                        $one["adset_id"] = end($arr);
                    }
                    Db::name("adjust_adgroup")->insert($one);
                }
            }
        }
        exit("ok");
    }

    public function campaign_report($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/campaign_report?start={$start}&end={$end}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name("adjust_campaign")->where(['event_date'=>$end])->delete();
                foreach ($res as $r) {
                    $one = $r;
                    if (in_array($r["media_source"], ['Facebook Installs', 'Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $one["media_source"] = "Facebook Ads";
                    }
                    if ($r["media_source"] == 'Adwords UAC Installs') {
                        $one["media_source"] = 'googleadwords_int';
                    }
                    if ($r["media_source"] == 'Google Ads UAC') {
                        $one["media_source"] = 'googleadwords_int';
                    }
                    if (trim($r["ad_source"]) == "GooglePlayServices") {
                        $one['ad_source'] = "Admob";
                    }
                    if ($r["ad_source"] == "Minteral") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (preg_match("/MoPubRewardedPlayabl/", $r["ad_source"])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
                        $one['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['Unity', 'Unity Ads','Unityads'])) {
                        $one['ad_source'] = "UnityAds";
                    }
                    if (trim($r["ad_source"]) == "MINTEGRAL") {
                        $one['ad_source'] = "Mintegral";
                    }
                    if (trim($r["ad_source"]) == "FACEBOOK") {
                        $one['ad_source'] = "Facebook";
                    }
                    if (trim($r["ad_source"]) == "Google AdMob") {
                        $one['ad_source'] = "Admob";
                    }
//                    $pattern = "/(?:\()(.*)(?:\))/i";
//                    preg_match($pattern, $r['campaign_name'], $arr);
//                    if (!empty($arr) && isset($arr[1])) {
//                        $one["campaign_id"] = $arr[1];
//                        Db::name("adjust_campaign")->insert($one);
//                    }
                    $campaign_name = $r['campaign_name'];
                    $campaign_name = strrchr($campaign_name,'(');
                    $campaign_name = str_replace('(','',$campaign_name);
                    $campaign_id = str_replace(')','',$campaign_name);
                    $one["campaign_id"] = $campaign_id;
                    Db::name("adjust_campaign")->insert($one);
                }
            }
        }
        exit("ok");
    }

    public function users_report($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/users_report?start={$start}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                foreach ($res as &$r) {
                    if (isset($this->promate_media[$r['media_source']])) {
                        $r['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (in_array($r["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $r['media_source'] = "Facebook Ads";
                    }
                    Db::name("adjust_ltv_users")->insert($r);
                }
            }
        }
        exit("ok");
    }
	
	
	public function maxReport($date="")
	{
		if ($date == "") {
            $date = date("Y-m-d", strtotime("-2 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/max_report?date={$date}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                //Db::name("applovin_max")->where(['event_date'=>$end])->delete();
				$dd = Db::name("applovin_unit")->select();
				$units =[];
				foreach($dd as $v)
				{
					$units[$v["unit_id"]] = $v["adtype"];
				}
                foreach ($res as &$r) {
                    if (isset($this->promate_media[$r['media_source']])) {
                        $r['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (in_array($r["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $r['media_source'] = "Facebook Ads";
                    }
					$insertData =array(
					   "media_source"=>$r["media_source"],
					   "gb_id"=>$r["gb_id"],
					   "country"=>$r["country"],
					   "install_date"=>$r["install_date"],
					   "event_date"=>$r["event_date"],
					   "device_category"=>$r["device_category"],
					   "revenue"=>$r["revenue"],
					);
                    $r["adtype"] = isset($units[$r["ad_revenue_unit"]])?$units[$r["ad_revenue_unit"]]:"";
                    if($r["adtype"]=="rew")
					{
						$insertData["rew_show"] = $r["num"];
					}
					if($r["adtype"]=="int")
					{
						$insertData["int_show"] = $r["num"];
					}					
                    Db::name("applovin_max")->insert($insertData);
                }
            }
            exit("ok");
        }
        exit("fail");
	}
	
	
	 public function maxCampaignReport($date=""){
        
        if (!$date){
            $date = date("Y-m-d",strtotime("-2 day"));
        }
        $table_name = "campaign_max";
        $url = "https://analytics.gamebrain.io/adjust/max_campaign_report?date={$date}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                $dd = Db::name("applovin_unit")->select();
				$units =[];
				foreach($dd as $v)
				{
					$units[$v["unit_id"]] = $v["adtype"];
				}
				
                foreach ($res as &$r) {
                    if (isset($this->promate_media[$r['media_source']])) {
                        $r['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (in_array($r["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $r['media_source'] = "Facebook Ads";
                    }
					$insertData =array(
					   "media_source"=>$r["media_source"],
					   "gb_id"=>$r["gb_id"],
					   "country"=>$r["country"],
					   "install_date"=>$r["install_date"],
					   "event_date"=>$r["event_date"],
					   "campaign_name"=>$r["campaign_name"],
					   "revenue"=>$r["revenue"],
					);
                    $r["adtype"] = isset($units[$r["ad_revenue_unit"]])?$units[$r["ad_revenue_unit"]]:"";
                    if($r["adtype"]=="rew")
					{
						$insertData["rew_show"] = $r["num"];
					}
					if($r["adtype"]=="int")
					{
						$insertData["int_show"] = $r["num"];
					}
					
                    $campaign_name = $r['campaign_name'];
                    $campaign_name = strrchr($campaign_name,'(');
                    $campaign_name = str_replace('(','',$campaign_name);
                    $campaign_id = str_replace(')','',$campaign_name);
                    $insertData["campaign_id"] = $campaign_id;
                    Db::name($table_name)->insert($insertData);
                }
                
            }
        }
        exit("we get max campaign impression on".$date);
    }
	
	//更新Campaign 模型
	public function update_campaign_ad_model($date=""){
		if (!$date){
            $date = date("Y-m-d",strtotime("-2 day"));
        }
		$where ="event_date='{$date}' and campaign_name REGEXP 'gp|yd|aeo|Gameplay|诱导|youdao'";
		$res = Db::name("adjust_campaign_ltv_pdt")->field('media_source,country,install_date,event_date,revenue,gb_id,campaign_id,campaign_name')->where($where)->select();
		if(!empty($res))
		{
			Db::name("campaign_ltv_model")->where("event_date='{$date}'")->delete();
			foreach($res as &$v)
			{
				$v["day"] = count(getDateFromRange($v["install_date"],$v["event_date"]))-1;
				if(preg_match('/AEO/i',$v["campaign_name"]))
				{
					$v["tag"] = "AEO";
				}elseif( preg_match('/GP|Gameplay/i',$v["campaign_name"]) )
				{
					$v["tag"] = "GP";
				}elseif( preg_match('/YD|诱导|youdao/i',$v["campaign_name"]) )
				{
					$v["tag"] = "YD";
				}
				$row_where =array(
				   "campaign_id"=>$v["campaign_id"],
				   "country"=>$v["country"],
				   "date"=>$v["install_date"]
				);
				$row = Db::name("adspend_data")->field('sum(spend) as spend,sum(installs) as installs')->where($row_where)->find();
				$v["spend"] = $row["spend"]?$row["spend"]:0;
				$v["cpi"] = $row["installs"]>0?round($row["spend"]/$row["installs"],2):0;
				Db::name("campaign_ltv_model")->insert($v);
			}
		}
		exit("ok");
	}
	
	//更新Campaign 内购模型
	public function update_campaign_purchase_model($date=""){
		if (!$date){
            $date = date("Y-m-d",strtotime("-2 day"));
        }
		$where ="event_date_utc='{$date}' and campaign_name is not null and campaign_name REGEXP 'gp|yd|aeo'";
		$res = Db::name("adjust_purchase_time_zone")->field('media_source,country,install_date_utc as install_date,event_date_utc as event_date,money as revenue,gb_id,campaign_id,campaign_name')->where($where)->select();

		if(!empty($res))
		{
			Db::name("campaign_ltv_model")->where("event_date='{$date}' and type='purchase'")->delete();
			foreach($res as &$v)
			{
				$v["day"] = count(getDateFromRange($v["install_date"],$v["event_date"]))-1;
				if(preg_match('/AEO/i',$v["campaign_name"]))
				{
					$v["tag"] = "AEO";
				}elseif( preg_match('/GP/i',$v["campaign_name"]) )
				{
					$v["tag"] = "GP";
				}elseif( preg_match('/YD/i',$v["campaign_name"]) )
				{
					$v["tag"] = "YD";
				}
				$v["revenue"] = $v["revenue"] * 0.7;
				$v["type"] ="purchase";
				Db::name("campaign_ltv_model")->insert($v);
			}
		}
		exit("ok");
	}
	
	public function maxAdReport($date=""){
        
        if (!$date){
            $date = date("Y-m-d",strtotime("-2 day"));
        }
        $table_name = "ad_max";
        $url = "https://analytics.gamebrain.io/adjust/max_ad_report?date={$date}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                $dd = Db::name("applovin_unit")->select();
				$units =[];
				foreach($dd as $v)
				{
					$units[$v["unit_id"]] = $v["adtype"];
				}
				
                foreach ($res as &$r) {
                    if (isset($this->promate_media[$r['media_source']])) {
                        $r['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (in_array($r["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $r['media_source'] = "Facebook Ads";
                    }
					$insertData =array(
					   "media_source"=>$r["media_source"],
					   "gb_id"=>$r["gb_id"],
					   "country"=>$r["country"],
					   "install_date"=>$r["install_date"],
					   "event_date"=>$r["event_date"],
					   "ad_name"=>$r["ad_name"],
					   "revenue"=>$r["revenue"],
					);
                    $r["adtype"] = isset($units[$r["ad_revenue_unit"]])?$units[$r["ad_revenue_unit"]]:"";
                    if($r["adtype"]=="rew")
					{
						$insertData["rew_show"] = $r["num"];
					}
					if($r["adtype"]=="int")
					{
						$insertData["int_show"] = $r["num"];
					}
					
                    $ad_name = $r['ad_name'];
                    $ad_name = strrchr($ad_name,'(');
                    $ad_name = str_replace('(','',$ad_name);
                    $ad_id = str_replace(')','',$ad_name);
                    $insertData["ad_id"] = $ad_id;
                    Db::name($table_name)->insert($insertData);
                }
                
            }
        }
        exit("we get max ad impression on".$date);
    }

    public function syncReport($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/adjust/impression_report?start={$start}&end={$end}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name("adjust_impression")->where(['event_date'=>$end])->delete();
                foreach ($res as &$r) {
                    if (isset($this->promate_media[$r['media_source']])) {
                        $r['media_source'] = $this->promate_media[$r['media_source']];
                    }
                    if (in_array($r["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                        $r['media_source'] = "Facebook Ads";
                    }
                    if (trim($r["ad_source"]) == "GooglePlayServices") {
                        $r['ad_source'] = "Admob";
                    }
                    if (in_array($r["ad_source"], ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
                        $r['ad_source'] = "MoPub";
                    }
                    if ($r["ad_source"] == "Minteral") {
                        $r['ad_source'] = "Mintegral";
                    }
                    if (preg_match("/MoPubRewardedPlayabl/", $r["ad_source"])) {
                        $r['ad_source'] = "MoPub";
                    }
                    if (in_array($r["ad_source"], ['Unity', 'Unity Ads', 'Unityads'])) {
                        $r['ad_source'] = "UnityAds";
                    }
                    if (trim($r["ad_source"]) == "MINTEGRAL") {
                        $r['ad_source'] = "Mintegral";
                    }
                    if (trim($r["ad_source"]) == "FACEBOOK") {
                        $r['ad_source'] = "Facebook";
                    }
                    if (trim($r["ad_source"]) == "Google AdMob") {
                        $r['ad_source'] = "Admob";
                    }
                    Db::name("adjust_impression")->insert($r);
                }
            }
            exit("ok");
        }
        exit("fail");
    }

    public function device_reoprt($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-2 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        $apps = $this->apps;
        $apps[] = ["app_id" => 93, "key" => "q4cie8ourif4"];
        $apps[] = ["app_id" => 77, "key" => "j8h5sltmiry8"];
        foreach ($apps as $a) {
            $this->get_device_reoprt($a['app_id'], $a['key'], $start, $end);
        }
        exit("ok");
    }

    private function get_device_reoprt($app_id, $key, $start, $end)
    {

        $url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=08:00&kpis=installs,cost&attribution_type=click&grouping=networks,countries,device_types&user_token=zDxyxVAafpjFX-yYMbvK";
        $result = json_decode(curl($url), true);
        if (isset($result["result_set"]['networks']) && !empty($result["result_set"]['networks'])) {
            $res = $result["result_set"]['networks'];
            if (!empty($res)) {
                foreach ($res as $r) {
                    foreach ($r["countries"] as $c) {
                        if (!empty($c['device_types'])) {
                            foreach ($c['device_types'] as $d) {
                                if (!empty($d['kpi_values'])) {
                                    $params = array(
                                        "gb_id" => $app_id,
                                        "device_category" => $d['device_type'] == 'tablet' ? 'ipad' : $d['device_type'],
                                        "installs" => $d['kpi_values'][0],
                                        "install_date" => $start,
                                        "networks" => $r["name"],
                                        "spend" => round($d['kpi_values'][1], 2),
                                        "country" => strtoupper($c["country"])
                                    );
                                    $row = Db::name("adjust_device")
                                        ->where(["gb_id" => $params["gb_id"], "device_category" => $params["device_category"], "install_date" => $params['install_date'], "networks" => $params['networks'], "country" => $params['country']])
                                        ->find();
                                    if (empty($row)) {
                                        Db::name("adjust_device")->insert($params);
                                    } else {
                                        Db::name("adjust_device")->where("id", $row["id"])->update($params);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function reten_day_report($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        foreach ($this->apps as $a) {
            $this->get_reten_day_report($a['app_id'], $a['key'], $start, $end);
        }
        exit("ok");
    }

    private function get_reten_day_report($app_id, $key, $start, $end)
    {

        $url = "https://api.adjust.com/kpis/v1/{$key}/cohorts.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=retention_rate&attribution_type=click&grouping=day&period=day&cohort_period_filter=1-30&user_token=zDxyxVAafpjFX-yYMbvK";
        $result = json_decode(curl($url), true);
        if (isset($result["result_set"]['dates']) && !empty($result["result_set"]['dates'])) {
            $data = $result["result_set"]['dates'][0]["periods"];
            if (!empty($data)) {
                foreach ($data as $v) {
                    $params = array(
                        "date" => $result["result_set"]['dates'][0]["date"],
                        "app_id" => $app_id,
                        "country" => "all",
                    );
                    $params["retention_{$v['period']}"] = $v["kpi_values"][0];
                    $row = Db::name("retention")->where(["date" => $params["date"], "app_id" => $params["app_id"], "country" => $params["country"]])->find();
                    if (empty($row)) {
                        Db::name("retention")->insert($params);
                    } else {
                        Db::name("retention")->where("id", $row["id"])->update($params);
                    }
                }
            }
        }
    }

    public function sync_data($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $host = getdomainname();
        $list = Db::name("bind_attr")->field("app_id,adjust")->where("adjust!=''")->select();
        if (!empty($list)) {
            foreach ($list as $g) {
                $url_total = $host . "/adjust/sync_day_report";
                $url_country = $host . "/adjust/sync_country_report";
                $g["start"] = $start;
                syncRequest($url_total, $g);
                sleep(5);
                syncRequest($url_country, $g);
            }
        }
        sleep(2);
        $device_reoprt_url = $host . "/adjust/device_reoprt";
        syncRequest($device_reoprt_url, []);
        exit("ok");
    }

    public function sync_day_report(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $this->get_day_report($params['app_id'], $params['adjust'], $params["start"], $params["start"]);
        }
        exit("ok");
    }

    public function sync_country_report(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $this->get_country_report($params['app_id'], $params['adjust'], $params["start"], $params["start"]);
        }
        exit("ok");
    }

    public function purchase_report(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $data = array(
                "idfa" => isset($params["idfa"]) ? $params["idfa"] : '',
                "install_date" => $params["install_date"],
                "event_date" => $params["event_date"],
                "media_source" => isset($params["media_source"]) ? $params["media_source"] : '',
                "country" => $params["country"],
                "device_category" => isset($params["device_category"]) ? $params["device_category"] : '',
                "advertising_id" => isset($params["advertising_id"]) ? $params["advertising_id"] : '',
                "gb_id" => $params["gb_id"],
                "money" => isset($params["event_value"]) ? $params["event_value"] : 0
            );
            if (isset($params["campaign_name"]) && $params["campaign_name"]) {
                $campaign_name = $params['campaign_name'];
                $campaign_name = strrchr($campaign_name,'(');
                $campaign_name = str_replace('(','',$campaign_name);
                $campaign_id = str_replace(')','',$campaign_name);
                $data["campaign_id"] = $campaign_id;
//                $pattern = "/(?:\()(.*)(?:\))/i";
//                preg_match($pattern, $params['campaign_name'], $arr);
//                if (!empty($arr) && isset($arr[1])) {
//                    $data["campaign_id"] = $arr[1];
//                }
            }
            if (isset($this->promate_media[$data['media_source']])) {
                $data['media_source'] = $this->promate_media[$data['media_source']];
            }
            if (in_array($data["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                $data['media_source'] = "Facebook Ads";
            }
            Db::name("adjust_purchase")->insert($data);
        }
        exit("ok");
    }

    /**
     * ADJUST 内购 时区版本
     * @param Request $request
     */
    public function purchase_report_time_zone(Request $request){
        $params = $request->param();
        if (!empty($params)) {
            $install_time = $params["install_time"];
            $event_time = $params["event_time"];
            $data = array(
                "idfa" => isset($params["idfa"]) ? $params["idfa"] : '',
                "media_source" => isset($params["media_source"]) ? $params["media_source"] : '',
                "country" => $params["country"],
                "device_category" => isset($params["device_category"]) ? $params["device_category"] : '',
                "advertising_id" => isset($params["advertising_id"]) ? $params["advertising_id"] : '',
                "gb_id" => $params["gb_id"],
                "money" => isset($params["event_value"]) ? $params["event_value"] : 0,
                'real_event_time' => date('Y-m-d H:i:s',$event_time),
                'real_install_time' => date('Y-m-d H:i:s',$install_time)
            );
            if (isset($this->promate_media[$data['media_source']])) {
                $data['media_source'] = $this->promate_media[$data['media_source']];
            }
            if (in_array($data["media_source"], ['Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                $data['media_source'] = "Facebook Ads";
            }
            $where = $data;
            $where['real_event_time'] = ['between',[date('Y-m-d H:i:s',$event_time-2),date('Y-m-d H:i:s',$event_time+2)]];
            if (!Db::name("adjust_purchase_time_zone")->where($where)->find()){
                // 时间戳转化为 时区时间
                $data['install_date'] = date('Y-m-d',$install_time);
                $data['event_date'] = date('Y-m-d',$event_time);
                $data['install_date_utc'] = date('Y-m-d',$install_time-8*3600);
                $data['event_date_utc'] = date('Y-m-d',$event_time-8*3600);
                $data['install_date_pdt'] = date('Y-m-d',$install_time-15*3600);
                $data['event_date_pdt'] = date('Y-m-d',$event_time-15*3600);
                $data['install_date_pst'] = date('Y-m-d',$install_time-16*3600);
                $data['event_date_pst'] = date('Y-m-d',$event_time-16*3600);
                if (isset($params["campaign_name"]) && $params["campaign_name"]) {
                    $campaign_name = $params['campaign_name'];
                    $data["campaign_name"] = $campaign_name;
                    $campaign_name = strrchr($campaign_name,'(');
                    $campaign_name = str_replace('(','',$campaign_name);
                    $campaign_id = str_replace(')','',$campaign_name);
                    $data["campaign_id"] = $campaign_id;
                }
                if (isset($params["adset_name"]) && $params["adset_name"]) {
                    $adset_name = $params['adset_name'];
                    $data["adset_name"] = $adset_name;
                    $adset_name = strrchr($adset_name,'(');
                    $adset_name = str_replace('(','',$adset_name);
                    $adset_id = str_replace(')','',$adset_name);
                    $data["adset_id"] = $adset_id;
                }
                if (isset($params["ad_name"]) && $params["ad_name"]) {
                    $ad_name = $params['ad_name'];
                    $data["ad_name"] = $ad_name;
                    $ad_name = strrchr($ad_name,'(');
                    $ad_name = str_replace('(','',$ad_name);
                    $ad_name = str_replace(')','',$ad_name);
                    $data["ad_id"] = $ad_name;
                }
				if(strtolower($data["media_source"])=="tiktok_int")
				{
					if( isset($params["adset_name"]) && $params["adset_name"] )
					{
						$ad_name = strrchr($params["adset_name"],'&');
						$ad_name = str_replace('&','',$ad_name);
						$arr = explode("-",$ad_name);
						$data["adset_id"] = $arr[0];
					}
					if( isset($params["campaign_name"]) && $params["campaign_name"] )
					{
						$campaign_name = $params['campaign_name'];
						$campaign_name = strrchr($campaign_name,'&');
                        $campaign_id = str_replace('&','',$campaign_name);
						$data["campaign_id"] = $campaign_id;
					}
				}
                Db::name("adjust_purchase_time_zone")->insert($data);
            }
        }
        exit("ok");
    }

    private function get_day_report($app_id, $key, $start, $end)
    {
        $url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=installs,daus&attribution_type=click&grouping=day&user_token=zDxyxVAafpjFX-yYMbvK";
        $result = json_decode(curl($url), true);
        if (isset($result["result_set"]['dates']) && !empty($result["result_set"]['dates'])) {
            $data = $result["result_set"]['dates'];
            if (!empty($data)) {
                foreach ($data as $v) {
                    $params = array(
                        "date" => $v["date"],
                        "app_id" => $app_id,
                        "country" => "all",
                    );
                    $params["val"] = $v["kpi_values"][1];
                     $row = Db::name("active_users")->where(["date" => $params["date"], "app_id" => $params["app_id"], "country" => $params["country"]])->find();
                    if (empty($row)) {
                        Db::name("active_users")->insert($params);
                    } else {
                        Db::name("active_users")->where("id", $row["id"])->update($params);
                    }

                    $params["val"] = $v["kpi_values"][0];
                    $row = Db::name("new_users")->where(["date" => $params["date"], "app_id" => $params["app_id"], "country" => $params["country"]])->find();
                    if (empty($row)) {
                        Db::name("new_users")->insert($params);
                    } else {
                        Db::name("new_users")->where("id", $row["id"])->update($params);
                    }
                }
            }
        }
    }

    private function get_country_report($app_id, $key, $start, $end)
    {

        $url = "https://api.adjust.com/kpis/v1/{$key}.json?&start_date={$start}&end_date={$end}&utc_offset=00:00&kpis=installs,daus&attribution_type=click&grouping=countries&user_token=zDxyxVAafpjFX-yYMbvK";
        $result = json_decode(curl($url), true);
        if (isset($result["result_set"]['countries']) && !empty($result["result_set"]['countries'])) {
            $data = $result["result_set"]['countries'];
            if (!empty($data)) {
                foreach ($data as $v) {
                    $params = array(
                        "date" => $start,
                        "app_id" => $app_id,
                        "country" => strtoupper($v["country"]),
                    );
                    $params["val"] = $v["kpi_values"][1];
                    $row = Db::name("active_users")->where(["date" => $params["date"], "app_id" => $params["app_id"], "country" => $params["country"]])->find();
                    if (empty($row)) {
                        Db::name("active_users")->insert($params);
                    } else {
                        Db::name("active_users")->where("id", $row["id"])->update($params);
                    }

                    $params["val"] = $v["kpi_values"][0];
                    $row = Db::name("new_users")->where(["date" => $params["date"], "app_id" => $params["app_id"], "country" => $params["country"]])->find();
                    if (empty($row)) {
                        Db::name("new_users")->insert($params);
                    } else {
                        Db::name("new_users")->where("id", $row["id"])->update($params);
                    }
                }
            }
        }
    }

    private function request($appID, $from, $to)
    {
        $apiToken = "5331274b-ab11-493a-9830-1e05367f045a";
        $reportType = 'in_app_events_report';
        $query = http_build_query([
            'api_token' => $apiToken,
            'from' => $from,
            'to' => $to,
            'fields' => 'country_code,install_time,event_time,event_name,event_value,media_source,af_channel,campaign,af_c_id,af_adset,af_adset_id,af_ad,af_ad_id,app_id,advertising_id,idfa,device_type,appsflyer_id',
            'event_name' => 'AFHwAds',
        ]);
        $requestUrl = 'https://hq.appsflyer.com/export/' . $appID . '/' . $reportType . '/v5?' . $query;
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
                $signstr .= $value;
            }
        }
        return md5($signstr);
    }

    private function setField($row)
    {
        $arr = array(
            "country" => ($row["Country Code"] == "UK") ? "GB" : $row["Country Code"],
            "install_time" => $row["Install Time"],
            "install_date" => date("Y-m-d", strtotime($row["Install Time"])),
            "event_date" => date("Y-m-d", strtotime($row["Event Time"])),
            "event_time" => $row["Event Time"],
            "event_value" => $row["Event Value"],
            "media_source" => $row["Media Source"],
            "campaign_name" => $row["Campaign"],
            "campaign_id" => $row["Campaign ID"],
            "adset_name" => $row["Adset"],
            "adset_id" => $row["Adset ID"],
            "ad_id" => $row["Ad ID"],
            "ad_name" => $row["Ad"],
            "app_id" => $row["App ID"],
            "idfa" => $row["IDFA"],
            "advertising_id" => $row["Advertising ID"],
            "device_type" => $row["Device Type"],
            "af_id" => $row["AppsFlyer ID"],
        );
        $key = $this->field_split_guid($row["Event Value"]);
        if ($key) {
            //SigmobRewardedVideo_Reward_iPhone
            //AdmobInterstitial_Inter_iPad
            list($a, $b, $c) = explode("_", $key);
            $arr["adtype"] = $b;
            $arr["device_category"] = $c;
            $arr["ad_source"] = str_replace(["RewardedVideo", "Interstitial"], "", $a);
        }
        $arr["e_hash"] = $this->getSignMd5($arr);
        return $arr;
    }

    private function field_split_guid($value)
    {
        if ($value) {
            $arr_fields = explode(":", $value);
            if (isset($arr_fields[0])) {
                $key = str_replace(['{"', '"'], "", $arr_fields[0]);
                return $key;
            }
        }
        return "";
    }
	
	
	//新增 LTV 30,60,90,120,150,180,210汇总 
	public function ltv_summary($start=""){
		$current_date = date("Y-m-d", strtotime("-2 day"));
		if ($start == "") {
            $start = $current_date;
        }
		$time =[30,60,90,120,150,180,210];
		foreach($time as $t)
		{
			$t_date = date("Y-m-d", (strtotime($start) - $t * 24 * 3600));
			$sql =" select gb_id,media_source,install_date,country,round(SUM(revenue),4) as revenue,round(SUM(int_show),2) as int_show,round(SUM(rew_show),2) as rew_show  
from hellowd_adjust_ltv_pdt WHERE install_date='{$t_date}' and event_date<='{$start}' GROUP BY media_source,country,gb_id";
			$res = Db::query($sql);
			$res = array_map(function($v)use(&$t){
				$v["day"] = $t;
				return $v;
			},$res);
			if(!empty($res))
			{
				Db::name("adjust_ltv_summary")->where(["install_date"=>$t_date,"day"=>$t])->delete();
				Db::name("adjust_ltv_summary")->insertAll($res);
			}
		}
		exit("ok");
	}

    function csvJSON($content)
    {

        $lines = array_map('str_getcsv', file($content));

        $result = array();
        $headers;
        if (count($lines) > 0) {
            $headers = $lines[0];
        }

        for ($i = 1; $i < count($lines); $i++) {
            $obj = $lines[$i];
            $result[] = array_combine($headers, $obj);
        }
        return $result;
    }

    function get_redirect_url($url)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 不需要页面内容
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        // 不直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 返回最后的Location
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        $info = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $info;
    }

    public function test()
    {
        $apiToken = "5331274b-ab11-493a-9830-1e05367f045a";
        $appID = 'id1460648111';
        $reportType = 'geo_report';
        $from = date("Y-m-d", strtotime("-2 day"));
        $to = date("Y-m-d", strtotime("-2 day"));
        $query = http_build_query([
            'api_token' => $apiToken,
            'from' => $from,
            'to' => $to,
            'fields' => 'impressions,clicks,installs,cr,sessions,loyal_users,loyal_users_rate,cost,revenue,roi,arpu_ltv'
        ]);
        $requestUrl = 'https://hq.appsflyer.com/export/' . $appID . '/' . $reportType . '/v5?' . $query;
        echo $requestUrl;
        exit;
    }


    //对外数据接口
    public function day_ltv($gb_id = "", $date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-2 day"));
        }
        if (!$gb_id) {
            return show_out(500, "参数错误", []);
        }
        $res = Db::name("adjust_ltv")->where(["gb_id" => $gb_id, "event_date" => $date])->select();
        return show_out(200, "success", $res);
    }

    public function aaa()
    {
        $file_path = dirname(__FILE__) . "/log1.txt";//字体
        $content = "这是测试水水水水";
        file_put_contents($file_path, $content, FILE_APPEND);
        exit("ok");
    }

}
 