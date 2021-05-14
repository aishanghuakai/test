<?php

namespace app\api\controller;

use think\Db;
use \think\Request;
use \app\common\lib\Unityrequest;
use \app\common\lib\Applovinrequest;
use \app\common\lib\Vunglerequest;
use \app\common\lib\Facebookrequest;
use \app\api\GetRefreshToken;

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;


use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201809\cm\ReportDefinitionReportType;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\DateRange;
use Google\AdsApi\AdWords\v201809\cm\LocationCriterionService;
use Google\AdsApi\Common\OAuth2TokenBuilder;

// Init PHP Sessions
session_start();

//require_once dirname($_SERVER['DOCUMENT_ROOT']). '/vendor/autoload.php';

//第三方接口调用模块
set_time_limit(0);
ini_set('memory_limit', '512M');

class Adspend
{

    //const APP_ID="231114537668121";

    //const APP_SECRET ="25ccd55edb5e4d8791fb7e0a0a85ce31";

    const APP_ID = "229447164517821";

    const APP_SECRET = "e7904273283dabf0c1ae4f5b3b0cbea9";

    const REDIRECT_URI = "https://cnf.mideoshow.com/adspend/bb";

    public function auth()
    {
        $fb = new Facebook([
            'app_id' => self::APP_ID,
            'app_secret' => self::APP_SECRET
        ]);
        $helper = $fb->getRedirectLoginHelper();

        if (!isset($_SESSION['facebook_access_token'])) {
            $_SESSION['facebook_access_token'] = null;
        }

        if (!$_SESSION['facebook_access_token']) {
            $helper = $fb->getRedirectLoginHelper();
            try {
                $_SESSION['facebook_access_token'] = (string)$helper->getAccessToken();
            } catch (FacebookResponseException $e) {
                // When Graph returns an error
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch (FacebookSDKException $e) {
                // When validation fails or other local issues
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }
        }

        if ($_SESSION['facebook_access_token']) {
            echo "You are logged in!" . $_SESSION['facebook_access_token'];
        } else {
            $permissions = ['ads_read', 'read_insights', 'ads_management'];
            $loginUrl = $helper->getLoginUrl(self::REDIRECT_URI, $permissions);
            echo '<a href="' . $loginUrl . '">Log in with Facebook1</a>';

        }
    }

    public function bb()
    {

        echo "ok";
    }
	
	public function callback()
    {

        echo "ok";
    }

    public function touTiaoCallback($state="",$auth_code="")
    {
        if($state=="adc_smartad_pro")
		{
			return redirect("https://adc.smartad.pro/api/v1/set_toutiao_promote_account?auth_code={$auth_code}");
		}
		echo "ok";
    }

    public function tikTokCallback($state="",$auth_code="")
    {
        if($state=="adc_smartad_pro")
		{
			return redirect("https://adc.smartad.pro/api/v1/set_tiktok_promote_account?auth_code={$auth_code}");
			//return header("Location: https://adc.smartad.pro/api/v1/set_tiktok_promote_account?auth_code={$auth_code}"); 
		}
		echo "ok";
    }

    public function getaccess_token()
    {
        //EAADSMovrjhkBABZCtqBXqJh1F9nLT7c9EUBzlIsh01THfCCOMy5dJBjhlITeCUYMT1fc9JqDygjzKbdXmn1ASZC5oCVWkWXVTLvgs9EC7oIazHmzZCZAnDKx6jm4dZAxo0ZAPbUPuEWNPpUWAuRCeSfH9sMKkmz5FOGSGp6LBXKH8XqsXZAolHE
        //更新时间 2019-07-31
        $url = "https://graph.facebook.com/v3.2/oauth/access_token";
        $paras["client_id"] = self::APP_ID;
        $paras["client_secret"] = self::APP_SECRET;
        $paras["redirect_uri"] = self::REDIRECT_URI;
        $paras["code"] = "AQA4zNxWSXxHSUx8qW5C_8iKUaYa9PcNtX4OXve_8mu9Ry3TUA_-J4R1Ts9EG62iweZVQptjNIvfOXXpa4jn-HcXd-pviqlQ2kdjMrm887pwtexlDbRZa3IntWMan5XOojGiL4UWV3qq8kaAqZxGEW8ZIfqN0InRehV09gXfbqCo3fKIfjtx1A7sGFdVKtwxbi1hmQ-_V25N0ZSRx16ove69pJ-pV_Lk0ozh0fcLXPvDAmkzUJGPWjtDxayhuCMPb1e3Sk2rWGaZMIFdoU0kIo4Wg-f-9_wQ53zXVLDHhCRgK2_2PrZ-rm6lQ3Smlr_rJWOOTic7SHRbTTUwRMUREOTK";
        $content = http_build_query($paras);
        $url = $url . "?" . $content;
        $data = $this->curl($url);
        print_r($data);
        exit;
    }

    public function getlong_token()
    {
        //更新时间 2019-02-16
        $url = "https://graph.facebook.com/v2.10/oauth/access_token";
        $paras["client_id"] = self::APP_ID;
        $paras["client_secret"] = self::APP_SECRET;
        $paras["redirect_uri"] = self::REDIRECT_URI;
        $paras["grant_type"] = "fb_exchange_token";
        $paras["fb_exchange_token"] = "EAADSMovrjhkBABZCtqBXqJh1F9nLT7c9EUBzlIsh01THfCCOMy5dJBjhlITeCUYMT1fc9JqDygjzKbdXmn1ASZC5oCVWkWXVTLvgs9EC7oIazHmzZCZAnDKx6jm4dZAxo0ZAPbUPuEWNPpUWAuRCeSfH9sMKkmz5FOGSGp6LBXKH8XqsXZAolHE";
        $content = http_build_query($paras);
        $url = $url . "?" . $content;
        $data = $this->curl($url);
        print_r($data);
        exit;
    }

    public function getadset()
    {
        $url = "https://graph.facebook.com/v3.0/23842866493550071";
        $params["access_token"] = "EAADSMovrjhkBAMwlTfIlNREc25mC8vLaKnsuBFDXD2FecrKljlHZAXph5a5aNnpyuhRxY9bTGcLEzVRujtUWnzizKEfKhNQ1HWZChHaCQkuqbJjbdiVJAJiq8ZCCUocTWfzpbHskXizCowxyJzzU8k1VnccjpDcnr6WZCjBaDjcYshWSNgkN";
        $params["fields"] = "impressions,reach";
        $params['time_range'] = ["since" => "2018-06-18", "until" => "2018-06-18"];
        $content = http_build_query($params);
        $url = $url . "?" . $content;

        $data = $this->curl($url);
        print_r($data);
        exit;
    }

    public function getfacebook($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $facebook = new Facebookrequest();
        $facebook->ads_promote($start, $end);
        echo "ok";
    }

    public function getnewfacebook($start = "", $end = "")
    {
       if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }       
        $this->newselfmobvista(10, $start, $end);
        exit("ok");
    }

    public function testv4($start = "", $end = "")
    {
        $accounts = [
            "321362695676859",
			"796493217492078",
			"567083683931251",
            "244324226779402",
            "694935894596221",
            "283303746022285",
            "2508078919442915",
            "679062189496629"
        ];
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $facebook = new Facebookrequest();
        $facebook->new_ads_promote($accounts, $start, $end);
        echo "ok";
    }

    public function get_fb_data(Request $request)
    {
        $params = $request->param();
        $accounts = [
            $params["advertiser_id"]
        ];
        $facebook = new Facebookrequest();
        $facebook->new_ads_promote($accounts, $params["start"], $params["end"],$params["app_id"]);
		sleep(3);
		$facebook->platform_report($accounts,$params["start"], $params["end"]);
		sleep(3);
		$facebook->aaa_report($accounts,$params["start"], $params["end"],$params["app_id"]);
    }

    /**
     * 更新FB 数据 通过 账户ID
     */
    public function refresh_facebook_spend_by_account($start = "", $end = "", $account=""){
        if (!$start||!$end||!$account||$start>$end){
            exit("error, 不正确");
        }
        if (date('Y-m-d',strtotime($start))!=$start){
            exit("error2, 不正确");
        }
        if (date('Y-m-d',strtotime($end))!=$end){
            exit("error2, 不正确");
        }
        while ($start<=$end){
            $where = [
                "platform_type" => 6,
                "date" => $start,
                "target_id" => $account
            ];
            // 删除该账户当天的数据
            Db::name("adspend_data")->where($where)->delete();
            // 更新账户的当天数据
            $facebook = new Facebookrequest();
            $facebook->new_ads_promote([$account], $start, $start);
            $start++;
        }
        exit("OK");
    }

    public function pullFB($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $host = getdomainname();
        $url = $host . "/adspend/get_fb_data";
        $list = Db::name("advertising_account")->field('distinct(advertiser_id),app_id')->where(["channel" => 2])->select();
        if (!empty($list)) {
            foreach ($list as &$vv) {
                $vv["start"] = $start;
                $vv["end"] = $end;
                syncRequest($url, $vv);
            }
        }
        exit("ok");
    }

    public function aaa_data($start = "", $end = ""){
		if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
		
		$facebook = new Facebookrequest();
        $facebook->aaa_report(["796493217492078"],"2021-02-19","2021-02-19");
	}
	
	public function checkspend(){
		
		$sql =" SELECT aa.advertiser_id,app.update_status_time,aa.channel,aa.app_id  from  hellowd_advertising_account aa 
JOIN hellowd_app app on aa.app_id=app.id WHERE app.status=0 and aa.type=1 and aa.channel in(2,3,4)";
        $res = Db::query($sql);
		$out =[];
		 if(!empty($res))
		{
			foreach($res as $vv)
			{
				$row =[];
				switch($vv["channel"]){
					case 2:
					  $row = $this->get_fb_hour_spend($vv["advertiser_id"],$vv["update_status_time"]);
					  break;
					case 3:
					  $row = $this->get_gg_hour_spend($vv["advertiser_id"]);
					  break;
					case 4:
					  $row = $this->get_tk_hour_spend($vv["advertiser_id"],$vv["update_status_time"]);
					  break;
				}
				if(!empty($row) && $row["spend"]>0)
				{
					$row["app_name"] = $this->get_app_info($vv['app_id'])["app_name"];
					$row["app_id"] = $vv['app_id'];
					$out[] = $row;
				}
			}			
		}
        $appList = Db::name("app")->field('id,update_status_time')->where(["status"=>0])->select();
		if(!empty($appList))
		{
			foreach($appList as $a)
			{
				$date = date("Y-m-d", strtotime($a["update_status_time"]));
				$sql =" SELECT platform_type,SUM(spend) as spend FROM hellowd_adspend_data 
WHERE platform_type not in(5,6,36) and date>='{$date}' and app_id={$a['id']} GROUP BY platform_type";
                $result = Db::query($sql);
				$appname = $this->get_app_info($a['id'])["app_name"];
				$appId = $a['id'];
				if(!empty($result))
				{
					foreach($result as &$v)
					{
						if($v['spend']>0)
						{
							$arr =array(
							  "account_id"=>"",
							  "channel"=>getplatform($v['platform_type']),
							  "spend"=>$v["spend"],
							  "app_name"=>$appname,
							  "app_id" =>$appId
							);							
							$out[] = $arr;
						}	
					}					
				}					
			}
		}
	   
		Db::name("spend_check")->where("id>0")->delete();
		if(!empty($out))
		{
			
			Db::name("spend_check")->insertAll($out);
			$this->send_mail($out);
		}
		exit("ok");
	}
	
	function get_app_info($id){
	 $apps= Db::name("app")->field("id,app_name,platform,unique_hash,app_base_id,icon_url")->find($id);
	 if( !empty($apps) )
		{			
			if(  $apps["id"]>154 )
			{
				if( $apps["app_base_id"] )
				{
					$row = Db::name("app_base")->where("id",$apps["app_base_id"])->find();
					$apps["app_name"] = $row["name"].' - '.$apps["platform"];
					$apps["icon_url"] = $row["icon"];
				}
			}
		}
	  return $apps;
	}
	
	private function get_fb_hour_spend($account_id,$stop_time){
		$facebook = new Facebookrequest();
		//$stop_time = "2021-03-23 05:00:00";
		$date = date("Y-m-d", strtotime($stop_time));
		$fb_result = $facebook->get_hour_spend($account_id,date("Y-m-d"),date("Y-m-d"));
		$total_spend ="0.00";
		if(isset($fb_result["data"]) && !empty($fb_result["data"]))
		{
			foreach($fb_result["data"] as $v)
			{
				list($a,$b) = explode("-",$v["hourly_stats_aggregated_by_advertiser_time_zone"]);
				 if(strtotime($date." ".$a) > strtotime($stop_time) && $v["spend"]>0)
				 {
					 $total_spend+=$v["spend"];
				 }
			}
		}
		return ["account_id"=>$account_id,"channel"=>"Facebook","spend"=>$total_spend];
	}
	
	private function get_tk_hour_spend($account_id,$stop_time){
		
		//$stop_time = "2021-03-25 00:00:00";
		$date = date("Y-m-d", strtotime($stop_time));
		$result = $this->get_tiktok_hour($account_id,date("Y-m-d"),date("Y-m-d"));
		$total_spend ="0.00";
		if(isset($result["data"]["list"]) && !empty($result["data"]["list"]))
		{
			foreach($result["data"]["list"] as $v)
			{
				
				 if(strtotime($v["dimensions"]["stat_time_hour"]) > strtotime($stop_time) && $v['metrics']["spend"]>0)
				 {
					 $total_spend+=$v['metrics']["spend"];
				 }
			}
		}
		return ["account_id"=>$account_id,"channel"=>"Tittok","spend"=>$total_spend];
	}
	
	
	private function get_tiktok_hour($advertiser_id,$start,$end){
		$access_token = $this->getTikToktoken();
		$c_fields = json_encode(['stat_cost']);
		$dimension =json_encode(["advertiser_id","stat_time_hour"]);
		$ad_url = 'https://ads.tiktok.com/open_api/v1.1/reports/integrated/get/?advertiser_id=' . $advertiser_id . '&start_date=' . $start . '&end_date=' . $end . '&page_size=1000&page=1&dimensions='.$dimension.'&report_type=BASIC&data_level=AUCTION_ADVERTISER&metrics=' . $c_fields;
        $ad_res = $this->getcreative($access_token,$ad_url);
		return json_decode($ad_res,true);
	}
	
	//google 
	private function get_gg_hour_spend($account_id){
		$path = $_SERVER['DOCUMENT_ROOT'] . "/adsapi/ads_auto/adsapi_php{$account_id}.ini";
         $r = $this->main(date("Y-m-d"),date("Y-m-d"), $path);
		 $total_spend ="0.00";
          if (!empty($r)) {
                foreach ($r as $v) {
                    $spend = $this->moneyInDollars($v["cost"]);
					$total_spend+=$spend;
				}
		  }
		return ["account_id"=>$account_id,"channel"=>"Google","spend"=>$total_spend];
	}
	
	public function testv3($start = "", $end = "")
    {
        $accounts = [
            "2486906054740586",
            "2190086497963343",
            "2582496595408503",
            "993284984380349",
            "2475922335953428",
            "586369838590862",
            "490332321870615",
            "801696293675599",
            "769067600262915",
            "2309534829338303",
            "481502825871707",
            "181397706261210",
            "230221674658774",
            "224783238555262",
            "801696293675599",
            "864043897370137",
            "502880640419945",
            "796493217492078",
            "560045658198689",
            "878007669346496",
            "532311814372998",
            "600956980753812",
            "641846496580761",
            "3333847369963354",
            "491440768201824",
            "675044469931936",
            "721564668737421",
            "781753269015568",
            "537463563821006",
            "195740928510375",
            "663178494500749",
            "502348623795039",
            "221095449083144",
            "905760133213807",
            "293661824934530",
            "608646573067968",
            "679062189496629",
            "2508078919442915",
            "518266608855552",
            "802495896907619",
            "197321654825080"
        ];
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $facebook = new Facebookrequest();
        $facebook->new_ads_promote($accounts, $start, $end);
        echo "ok";
    }

    public function getfacebookv2($start = "", $end = "")
    {
        $accounts = [
            "383632982561027",
            "521865351960263",
            "506680040119110",
            "509890409558071",
            "1516782215130584",
            "578650979338769",
            "482458419268089",
            "489323774954782",
            "2419166218373385",
            "2421751414540031",
            "404779517082296",
            "1088395268028384",
            "470305920253709",
            "539243956811430",
            "978706819145875",
            "487442872053876",
            "1229190500573173",
            "715124712309189",
            "563902191058308",
            "1371468699696843",
            "2614596898768268",
            "2407185659403414",
            "1690927407842484",
            "3788531361172370",
            "2556367034469672",
            "531565470866988"
        ];
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $facebook = new Facebookrequest();
        $facebook->new_ads_promote($accounts, $start, $end);
        echo "ok";
    }

    public function getfacebookv1($start = "", $end = "")
    {
        $accounts = [
            "340521613388381",
            "408489216359247",
            "501576807031774",
            "819742358379629",
            "225245508427238",
            "325852618058442",
            "245072486400824",
            "415006782604911",
            "359181301588558",
            "1962393993808745",
            "258224258461437",
            "423194118416140",
            "809366699431489",
            "306803973341704",
            "454159035137475",
            "2132383423675293",
            "401090147333721",
            "427203724773331",
            "604289153422490",
            "862502154090318",
            "330644877842825",
            "458888881333270",
            "2444708755597641",
            "601938593654760",
            "338121913736230",
            "359932471365783",
            "2357286797864055",
            "888639331535094",
            "1400264893475949",
            "1229190500573173",
            "346141872959892"
        ];
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $facebook = new Facebookrequest();
        $facebook->new_ads_promote($accounts, $start, $end);
        echo "ok";
    }

    public function ff_test()
    {
        $facebook = new Facebookrequest();
        $facebook->ads_test();
        echo "ok";
    }

    private function curl($action, $headers = [])
    {
        $httpHeader = $headers;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $action);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params) );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return json_decode($ret, true);
    }

    public function control_facebook($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $accounts = array(
            ["id" => "679062189496629", "platform" => 344, "app_id" => 143],
            ["id" => "2508078919442915", "platform" => 342, "app_id" => 143],
        );
        $facebook = new Facebookrequest();
        $facebook->platform_promote($accounts, $start, $end);
        echo "ok";
    }

    //数据入库
    private function insertdata($data, $type)
    {

        array_walk($data, function (&$v) use ($type) {
            switch (intval($type)) {
                case 1:
                    break;
                case 3:
                    $v["platform_type"] = 3;
                    $v["date"] = $v["day"];
                    $v["installs"] = $v["conversions"];
                    $v["cpi"] = $v["average_cpa"];
                    $v["campaign_name"] = $v["campaign"];
                    $v["spend"] = $v["cost"];
                    $v["country"] = strtoupper($v["country"]);
                    $v["store_id"] = $v["campaign_package_name"];
                    unset($v["day"], $v["conversions"], $v["average_cpa"], $v["campaign"], $v["cost"], $v["campaign_package_name"]);
                    break;
                case 4:
                    $v["platform_type"] = 4;
                    $v["country"] = strtoupper($v["country"]);
                    $v["platform"] = strtolower($v["platform"]);
                    $v["campaign_id"] = $v["campaign id"];
                    $v["campaign_name"] = $v["campaign name"];
                    $v["adset_id"] = $v["site id"];
                    $v["adset_name"] = $v["site name"];
                    unset($v["campaign name"], $v["campaign id"], $v["site id"], $v["site name"]);
                    break;
                case 5:
                    break;
                case 7:
                    $v["platform_type"] = 7;
					$v["adset_id"] = $v["application_id"];
                    $v["date"] = date("Y-m-d", strtotime($v["date"]));
					unset($v["application_id"]);
                    break;
                case 31:
                    $v["platform_type"] = 31;
                    $v["country"] = strtoupper($v["country"]);
                    $v["clicks"] = $v["total_clicks"];
                    unset($v["total_clicks"], $v["cvvs"], $v["total_campaign_spend_limit"], $v["ecpi"]);
                    break;
            }
            $where = ["platform_type" => $type, "date" => $v["date"], "country" => $v["country"], "campaign_id" => $v["campaign_id"]];
            if (intval($type) == 4) {
                $where["adset_id"] = $v["adset_id"];
            }
			if(intval($type)== 7)
			{
				$where["adset_id"] = $v["adset_id"];
			}
            $r = Db::name("adspend_data")->where($where)->find();
            if (empty($r)) {
                if ($v["spend"] > 0) {
                    $v["app_id"] = getappidbycampaign($v["campaign_id"], $v["platform_type"]);
                    Db::name("adspend_data")->insert($v);
                }
            } else {
                if ($v["spend"] > 0) {
                    Db::name("adspend_data")->where("id", $r["id"])->update($v);
                }
            }
        });
        return true;
    }

    //获取unity 数据
    public function getUnity($start = "", $end = "")
    {

        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", time());
        }
        //$start = "2020-08-17T00:00:00.000Z";
        //$end ="2020-08-18T00:00:00.000Z";
        $Unity = new Unityrequest();
        $result = $Unity->ads_promote($start, $end);
        $data = json_decode($result, true);
        if (!empty($data)) {
            foreach ($data as $v) {
                $row = array(
                    "platform_type" => 2,
                    "date" =>$start, //date("Y-m-d", strtotime($v["timestamp"])),
                    "target_id" => $v["target id"],
                    "app_name" => $v["target name"],
                    "impressions" => $v["starts"],
                    "platform" => $v["platform"],
                    "campaign_id" => $v["campaign id"],
                    "campaign_name" => $v["campaign name"],
                    "store_id" => $v["target store id"],
                    "country" => $v["country"],
                    "clicks" => $v["clicks"],
                    "installs" => $v["installs"],
                    "spend" => $v["spend"],
                    "adset_id" => isset($v["﻿source app id"]) ? $v["﻿source app id"] : "",
                );
                $where = ["platform_type" => 2, "date" => $row["date"], "country" => $row["country"], "campaign_id" => $row["campaign_id"]];
                $where["adset_id"] = $row["adset_id"];
                $r = Db::name("adspend_data")->where($where)->find();
                if (empty($r)) {
                    if ($row["spend"] > 0) {
                        $row["app_id"] = getappidbycampaign($row["campaign_id"], 2);
                        Db::name("adspend_data")->insert($row);
                    }
                } else {
                    Db::name("adspend_data")->where("id", $r["id"])->update($row);
                }
            }
        }
        echo "ok";
    }

    //获取 AppLovin report api
    public function getApplovin($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $this->pageapplovin(1, $start, $end);
        /* $applo = new Applovinrequest();
		$result = $applo->ads_promote(1,$start,$end);
		$data = json_decode($result,true);
		print_r($data);exit;
		if( isset($data["results"]) )
		{
			$list = $data["results"];
			if( !empty($list) )
			{
				 $this->insertdata($list,3);
			}
		} */
        echo "ok";
    }

    private function pageapplovin($page, $start, $end)
    {
        $applo = new Applovinrequest();
        $result = $applo->ads_promote(1, $start, $end);
        $data = json_decode($result, true);
        if (isset($data["results"])) {
            $list = $data["results"];
            $num = $data["count"];
            if (!empty($list)) {
                foreach ($list as &$v) {
                    $v["platform_type"] = 3;
                    $v["date"] = $v["day"];
                    $v["installs"] = $v["conversions"];
                    $v["cpi"] = $v["average_cpa"];
                    $v["campaign_name"] = $v["campaign"];
                    $v["spend"] = $v["cost"];
                    $v["ad_name"] = $v["ad"];
                    $v["adset_id"] = $v["app_id_external"];
                    $v["adset_name"] = $v["adgroup"];
                    $v["country"] = strtoupper($v["country"]);
                    $v["store_id"] = $v["campaign_package_name"];
                    unset($v["day"], $v["ad"], $v["app_id_external"], $v["adgroup"], $v["conversions"], $v["average_cpa"], $v["campaign"], $v["cost"], $v["campaign_package_name"]);
                    $r = Db::name("adspend_data")->where(["platform_type" => 3, "date" => $v["date"], "country" => $v["country"], "ad_id" => $v["ad_id"], "adset_id" => $v["adset_id"]])->find();
                    if (empty($r)) {
                        $v["app_id"] = getappidbycampaign($v["campaign_id"], $v["platform_type"]);
                        Db::name("adspend_data")->insert($v);
                    } else {
                        Db::name("adspend_data")->where("id", $r["id"])->update($v);
                    }
                }
                if ($num >= 10600) {
                    sleep(5);
                    ++$page;
                    $this->pageapplovin($page, $start, $end);
                }
            }
        }
    }

    //Snapchat 数据
    public function getSnapchat($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        header("Content-type: text/html; charset=utf-8");
        $accounts = ["24889164-09a6-4e9b-a464-fa2b3ec7d343","870ba339-9827-4758-9993-dc05a8a4b75b"];
        foreach($accounts as $a)
		{
			$this->get_snapchat_data($a,$start,$end);
		}
        exit("ok");
    }
	
	private function get_snapchat_data($ad_account_id,$start,$end){
		$report_url = "https://adsapi.snapchat.com/v1/adaccounts/{$ad_account_id}/stats?start_time={$start}T00:00:00-07:00&end_time={$end}T00:00:00-07:00&breakdown=campaign&fields=impressions,swipes,spend,total_installs&pivot=country&granularity=TOTAL&report_dimension=country";
        $campaign_url = "https://adsapi.snapchat.com/v1/adaccounts/{$ad_account_id}/campaigns";
        $result = $this->request_snapchat($campaign_url);
        $campaignLists = $this->fieldToKey(isset($result["campaigns"]) ? $result["campaigns"] : [], 'id');
        $report = $this->request_snapchat($report_url);
        $report = isset($report["total_stats"][0]["total_stat"]["breakdown_stats"]["campaign"]) ? $report["total_stats"][0]["total_stat"]["breakdown_stats"]["campaign"] : [];
        if (!empty($report)) {
            foreach ($report as $v) {
                if (!empty($v["dimension_stats"])) {
                    foreach ($v["dimension_stats"] as $vv) {
                        $row = [
                            "country" => strtoupper($vv["country"]),
                            "platform_type" => 38,
                            "campaign_id" => $v["id"],
                            "date" => $start,
                            "campaign_name" => $campaignLists[$v["id"]]['campaign']['name'],
                            "impressions" => $vv["impressions"],
                            "clicks" => isset($vv["swipes"]) ? $vv["swipes"] : 0,
                            "spend" => round($vv["spend"] / 1000000, 2),
                            "installs" => $vv["total_installs"]
                        ];
                        $r = Db::name("adspend_data")->where(["platform_type" => $row["platform_type"], "date" => $row["date"], "country" => $row["country"], "campaign_id" => $row["campaign_id"]])->find();
                        if (empty($r)) {
                            $row["app_id"] = getappidbycampaign($row["campaign_id"], $row["platform_type"]);
                            Db::name("adspend_data")->insert($row);
                        } else {
                            Db::name("adspend_data")->where("id", $r["id"])->update($row);
                        }
                    }
                }
            }
        }
	}

    function fieldToKey($report, $field)
    {
        $data = [];
        if (!empty($report)) {
            foreach ($report as $vv) {
                $data[$vv["campaign"][$field]] = $vv;
            }
        }
        return $data;
    }

    public function get_snapchat_code()
    {
        $url = "https://accounts.snapchat.com/login/oauth2/authorize";
        $params = array(
            "client_id" => "fae2e990-78da-4e93-8568-1f777b3a1692",
            "redirect_uri" => "https://console.gamebrain.io/adspend/bb",
            "response_type" => "code",
            "scope" => "snapchat-marketing-api",
            "state" => "Optional"
        );
        $url = $url."?".http_build_query($params);
		//print_r($url);exit;
        $result = $this->googlecurl($url);
		
    }

    private function request_snapchat($URL)
    {
        $access_token = $this->get_accsess_token();
        if ($access_token) {
            $httpHeader = array();
            $httpHeader[] = 'Authorization: Bearer ' . $access_token;
            $r = $this->curl_request($URL, $httpHeader, false);
            return json_decode($r, true);
        }
        return [];
    }

    private function get_accsess_token()
    {
        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $access_token = $mem->get('snapchat_access_token');
        if ($access_token) {
            return $access_token;
        }
        $refresh_token = $mem->get('snapchat_refresh_token');
        $result = $this->refresh_access_token($refresh_token);
        if (isset($result["access_token"]) && $result["refresh_token"] != '') {
            $mem->set('snapchat_access_token', $result["access_token"], 0, 1600);
            $mem->set('snapchat_refresh_token', $result["refresh_token"]);
            return $result["access_token"];
        }
        return "";
    }

    private function refresh_access_token($refresh_token)
    {

        $url = "https://accounts.snapchat.com/login/oauth2/access_token";
        $params = array(
            "client_id" => "fae2e990-78da-4e93-8568-1f777b3a1692",
            "refresh_token" => $refresh_token,
            "client_secret" => "4e0a37661a049ad5db27",
            "grant_type" => "refresh_token"
        );
        $result = $this->googlecurl($url, http_build_query($params), 'post');
        return json_decode($result, true);
    }

    public function getSnapchatToken()
    {
        $url = "https://accounts.snapchat.com/login/oauth2/access_token";
        $params = array(
            "client_id" => "fae2e990-78da-4e93-8568-1f777b3a1692",
            "redirect_uri" => "https://console.gamebrain.io/adspend/bb",
            "code" => "M8DNb_rnK8yO3IYAhnDPpUzJhm92gBDwjsBOTeDzi8I",
            "client_secret" => "4e0a37661a049ad5db27",
            "grant_type" => "authorization_code"
        );
       // $refresh_token = "eyJraWQiOiJyZWZyZXNoLXRva2VuLWExMjhnY20uMCIsInR5cCI6IkpXVCIsImVuYyI6IkExMjhHQ00iLCJhbGciOiJkaXIifQ..-0rXXkgW8SFoE36v.8x0dhgnsBmD-8QS1FvIRC9v7410bYWBWRM8urUx1rhQjK5ceyItFMPefq-PivZdwPiFju6Tm2hlsIFOaib3tigK8VU2L79C5bSVpxOvgYkBGV1-fw7LgnWdkKSeBeMEDWoqa_Bn3W15gd_R5H5pvNvJ_-zq1nMxE62SChEJUZElsrvJrQK5K86t8NHAktdC__l9Z_27e7hwVmrFIB-xKvaBcjWFH6F-ZAVxs0y5yh4hiqjz4Q22cCASY27dR1qLRTFZeSGj-x_UnBq4.s2JLwIhSM5NqfG1xR4uPWw";
        $result = $this->googlecurl($url, http_build_query($params), 'post');
        print_r($result);
        exit;
    }

    //获取Vungle
    public function getVungle($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $Vungle = new Vunglerequest();
        header("Content-type: text/html; charset=utf-8");
        $res = $Vungle->ads_request($start, $end);
        $result = json_decode($res, true);
        if (!empty($result)) {
            $this->insertdata($result, 4);
        }
        echo "ok";
    }

    //AdColony
    public function getAdColony($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        header("Content-type: text/html; charset=utf-8");
        $start = date("mdY", strtotime($start));
        $url = "http://clients-api.adcolony.com/api/v2/advertiser_summary?user_credentials=ezMw9UUnYostOFr4Rwli&date={$start}&date_group=day&group_by=country&group_by=campaign";
        $content = $this->googlecurl($url);
        $data = json_decode($content, true);
        if (isset($data["results"])) {
            $list = $data["results"];
            if (!empty($list)) {
                $this->insertdata($list, 31);
            }
        }
        echo "ok";
    }

    //tapjoy
    public function gettapjoy($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://api.tapjoy.com/reporting_data.json?email=Tangwenjuan@hellowd.net&api_key=f85409596c0848cebecedc944f893dcb&date={$start}&page_size=500&timezone=0";
        $content = $this->googlecurl($url);
        $data = json_decode($content, true);
        if (isset($data["Apps"]) && !empty($data["Apps"])) {
            $list = $data["Apps"];
            foreach ($list as $vvv) {
                if (isset($vvv["Spend"]) && $vvv["Spend"] < 0) {
                    $this->tapjoy_insert($vvv, $start);
                }
            }
        }
        $this->campaignReport($start, $start);
        exit("ok");
    }

    private function tapjoy_insert($r, $start)
    {
        $appkey = $r["AppKey"];
        $stimestamp = strtotime($start);
        $start_s = $start . "T00:00:00-00:00";
        $end = date('Y-m-d', ($stimestamp + 86400));
        $end = $end . "T00:00:00-00:00";
        $res = $this->request_tapjoy($appkey, $start_s, $end);
        if (!empty($res)) {
            if (isset($res["data"][$appkey]["insights"]) && !empty($res["data"][$appkey]["insights"])) {
                $data = $res["data"][$appkey]["insights"];
                if (!empty($data)) {
                    foreach ($data as $k => $vv) {
                        if (isset($vv["installs_spend"][0][1]) && $vv["installs_spend"][0][1] < 0) {
                            $insert_data = [];
                            $insert_data["platform_type"] = 9;
                            $insert_data["date"] = $start;
                            $insert_data["installs"] = $vv["global_conversions"][0][1];
                            $insert_data["clicks"] = $vv["paid_clicks"][0][1];
                            if (!isset($r["AppName"])) {
                                $insert_data["campaign_name"] = $r["campaign_name"];
                                $insert_data["store_id"] = $r["store_id"];
                            } else {
                                $insert_data["campaign_name"] = $r["AppName"] . "-" . $r["Name"];
                                $insert_data["store_id"] = $r["AppStoreID"];
                            }

                            $insert_data["campaign_id"] = $appkey;
                            $insert_data["spend"] = ltrim($vv["installs_spend"][0][1], "-");
                            $insert_data["country"] = strtoupper($k);

                            $r = Db::name("adspend_data")->where(["platform_type" => 9, "date" => $insert_data["date"], "country" => $insert_data["country"], "campaign_id" => $insert_data["campaign_id"]])->find();
                            if (empty($r)) {
                                $insert_data["app_id"] = getappidbycampaign($insert_data["campaign_id"], 9);
                                Db::name("adspend_data")->insert($insert_data);
                            } else {
                                Db::name("adspend_data")->where("id", $r["id"])->update($insert_data);
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    private function authtapjoy()
    {
        $base64encoded = "ODEzYzI1MGItMDI2OS00YTAyLWIxZTEtNzFlMWFmODZlZTVlOng4VTkzbUhJTmhDMUdmUGZIdHpDbmQxYmc0em5MSDJUT3JxYk1tS0N1bkwyVlFWTXlLNEpBSm1OQlBpa0JmWTY2cUNXSVVCSVVpanQ4ZzMydzZxWnlnPT0=";
        $httpHeader = array();
        $httpHeader[] = 'Authorization: Basic ' . $base64encoded;
        $httpHeader[] = 'Accept: application/json; */*';
        $URL = "https://api.tapjoy.com/v1/oauth2/token";
        $r = $this->curl_request($URL, $httpHeader);
        $data = json_decode($r, true);
        if (!empty($data) && isset($data["access_token"])) {
            return $data["access_token"];
        }
        return "";
    }

    private function curl_request($url, $httpHeader, $ispost = true)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
    }

    public function request_tapjoy($appkey, $start, $end)
    {
        $access_token = $this->authtapjoy();
        if ($access_token) {
            $URL = "https://api.tapjoy.com/v1/ad_groups/{$appkey}/insights?start_time={$start}&end_time={$end}&time_increment=daily&breakdowns=country_code";
            $httpHeader = array();
            $httpHeader[] = 'Authorization: Bearer ' . $access_token;
            $httpHeader[] = 'Accept: application/json; */*';
            $r = $this->curl_request($URL, $httpHeader, false);
            return json_decode($r, true);
        }
        return [];
    }

    //2018-11-22
    public function getironSource($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $data = [];
        $res = $this->authironSource($start, $end);
        $result = json_decode($res, true);
        if (!empty($result)) {
            $data = $result["data"];
        }
        if (!empty($data)) {
            $this->insertdata($data, 7);
        }
        echo "ok";
    }

    function authironSource($start, $end)
    {
        $base64encoded = base64_encode("Tangwenjuan@hellowd.net:e052664368a7feeae4afceb24180b957");
        $httpHeader = array();
        $httpHeader[] = 'Authorization: Basic ' . $base64encoded;
        $httpHeader[] = 'Accept: application/json';
        $URL = "https://api.ironsrc.com/advertisers/v1/reports?startDate={$start}&endDate={$end}&breakdowns=day,campaign,application,country&metrics=impressions,clicks,spend,installs&format=json&count=10000";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
    }

    public function getchartboost($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://analytics.chartboost.com/v3/metrics/appcountry?dateMin={$start}&dateMax={$end}&role=advertiser&userId=5b5fc304818b590be73db190&userSignature=777b3632743a6d8b30608ef5a7c77c758dbb0059aa6b62ec8d04d18074e12bf0&timezone=pst";
        $content = $this->googlecurl($url);
        if (!empty($content)) {

            $params = json_decode($content, JSON_PRETTY_PRINT);

            if (!empty($params)) {
                foreach ($params as &$vv) {
                    if ($vv["platform"] == "Google Play") {
                        $vv["platform"] = "android";
                    } elseif ($vv["platform"] == "iOS") {
                        $vv["platform"] = "ios";
                    }
                    $vv["target_id"] = md5($vv["campaignType"] . $vv["adType"]);

                    $r = Db::name("adspend_data")->where(["platform_type" => 8, "date" => $vv["dt"], "country" => $vv["countryCode"], "platform" => $vv["platform"], "target_id" => $vv["target_id"], "campaign_id" => $vv["appId"]])->find();
                    if (empty($r)) {

                        $data = array(
                            "campaign_id" => $vv["appId"],
                            "impressions" => $vv["impressionsReceived"],
                            "clicks" => $vv["clicksReceived"],
                            "installs" => $vv["installsReceived"],
                            "spend" => round($vv["moneySpent"], 2),
                            "country" => $vv["countryCode"],
                            "date" => $vv["dt"],
                            "platform" => $vv["platform"],
                            "platform_type" => 8,
                            "app_name" => $vv["app"],
                            "campaign_name" => $vv["app"],
                            "target_id" => $vv["target_id"],
                            "app_id" => getappidbycampaign($vv["appId"], 8)
                        );
                        Db::name("adspend_data")->insert($data);
                    } else {
                        $data = array(
                            "impressions" => $vv["impressionsReceived"],
                            "clicks" => $vv["clicksReceived"],
                            "installs" => $vv["installsReceived"],
                            "spend" => round($vv["moneySpent"], 2),
                        );
                        Db::name("adspend_data")->where("id", $r["id"])->update($data);
                    }

                }
            }
        }
		$this->checkspend();
        exit("ok");
    }

    public function aax()
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . "/unity/chartboost_report_1533523768.csv";
        $list = $this->csvJSON($file);
        print_r($list);
        exit;
    }

    //头条广告账户对应ID
    private function touTiaoAccount($advertiser_id)
    {
        $res = array(
            "108230033563" => ["app_id" => "93"],
            "106713028212" => ["app_id" => "77"],
            "106677044761" => ["app_id" => "77"],
            "106713064697" => ["app_id" => "68"],
            "108699407172" => ["app_id" => "68"],
            "108699422358" => ["app_id" => "93"],
            "111603199845" => ["app_id" => "77"],
            "108230005386" => ["app_id" => "68"]
        );
        if ($advertiser_id == "all") {
            return $res;
        }
        return isset($res[$advertiser_id]) ? $res[$advertiser_id] : [];
    }

    //新增修改
    private function gettouTiaoAccount($advertiser_id)
    {
        $res = array(
            "108230033563" => ["app_id" => "93", "type" => 1],
            "106713028212" => ["app_id" => "77", "type" => 1],
            "106677044761" => ["app_id" => "77", "type" => 1],
            "106713064697" => ["app_id" => "68", "type" => 1],
            "108699407172" => ["app_id" => "68", "type" => 1],
            "108699422358" => ["app_id" => "93", "type" => 1],
            "111580090967" => ["app_id" => "93", "type" => 1],
            "110655073381" => ["app_id" => "107", "type" => 1],
            "110655056738" => ["app_id" => "107", "type" => 1],
            "110655041439" => ["app_id" => "107", "type" => 1],
            "110659871557" => ["app_id" => "107", "type" => 1],
            "110659866957" => ["app_id" => "107", "type" => 1],
            "110659886424" => ["app_id" => "107", "type" => 1],
            "111603199845" => ["app_id" => "77", "type" => 1],
            "3188212662276990" => ["app_id" => "114", "type" => 1],
            "461423825914632" => ["app_id" => "114", "type" => 1],
            "3223392436101134" => ["app_id" => "107", "type" => 1],
            "3205800250321131" => ["app_id" => "77", "type" => 1],
            "108230005386" => ["app_id" => "68", "type" => 1],
            "1633137763673096" => ["app_id" => "77", "type" => 1],
            "1633137411769351" => ["app_id" => "77", "type" => 1],
            "1631851380627468" => ["app_id" => "77", "type" => 1],
            "1631851586421772" => ["app_id" => "107", "type" => 1],
            "1634854264204300" => ["app_id" => "127", "type" => 1],
            "1634854617897998" => ["app_id" => "127", "type" => 1],
            "1634854796319812" => ["app_id" => "127", "type" => 1],
            "1636917837114380" => ["app_id" => "93", "type" => 1],
            "1636918030534663" => ["app_id" => "129", "type" => 1],
            "1636918151490563" => ["app_id" => "93", "type" => 1],
            "1636918268913677" => ["app_id" => "127", "type" => 1],
            "1636918391801867" => ["app_id" => "127", "type" => 1],
            "1636918512519181" => ["app_id" => "117", "type" => 1],
            "1639115541053453" => ["app_id" => "117", "type" => 1],
            "1631851177659404" => ["app_id" => "107", "type" => 1],
            "1638565951868941" => ["app_id" => "127", "type" => 1],
            "1638566171071565" => ["app_id" => "127", "type" => 1],
            "1639114890601484" => ["app_id" => "127", "type" => 1],
            "1639114795287566" => ["app_id" => "127", "type" => 1],
            "1638566330742797" => ["app_id" => "127", "type" => 1],
            "1639117032686603" => ["app_id" => "127", "type" => 1],
            "1638565801105412" => ["app_id" => "127", "type" => 1],
            "1641914707674190" => ["app_id" => "127", "type" => 2],
            "1641914209584140" => ["app_id" => "127", "type" => 2],
            "1641194098001931" => ["app_id" => "127", "type" => 2],
            "1645072493441032" => ["app_id" => "132", "type" => 2],
            "1645072152454152" => ["app_id" => "127", "type" => 2],
            "1646974296844302" => ["app_id" => "107", "type" => 2],
            "1645071690447884" => ["app_id" => "93", "type" => 2],
            "1645070080411651" => ["app_id" => "77", "type" => 2],
            "1646981667993608" => ["app_id" => "77", "type" => 2],
            "1653776830602251" => ["app_id" => "68", "type" => 2],
            "1646981953298439" => ["app_id" => "142", "type" => 2],
            "1646982160954380" => ["app_id" => "127", "type" => 2],
            "1654973550608396" => ["app_id" => "114", "type" => 2],
            "1654973829366796" => ["app_id" => "154", "type" => 2],
            "1653776588042244" => ["app_id" => "153", "type" => 2],
            "1678440901991431" => ["app_id" => "153", "type" => 2],
            "1678440902845454" => ["app_id" => "153", "type" => 2],
			"1678440903261191"=> ["app_id" => "153", "type" => 2],
			"1678440903640072"=> ["app_id" => "153", "type" => 2],
			"1681145391636487"=> ["app_id" => "181", "type" => 2],
			"1681145392160776"=> ["app_id" => "154", "type" => 2],
			"1681145391170568"=> ["app_id" => "154", "type" => 2],
			"1678440902453261"=> ["app_id" => "153", "type" => 2]

        );
        if ($advertiser_id == "all") {
            return $res;
        }
        return isset($res[$advertiser_id]) ? $res[$advertiser_id] : [];
    }

    //获取主体类型
    private function getcompanytype($advertiser_id)
    {
        $res = $this->gettouTiaoAccount($advertiser_id);
        return $res["type"];
    }

    //新增账号
    public function getnewtouTiao($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
            "110655073381" => ["app_id" => "107"],
            "110655056738" => ["app_id" => "107"],
            "110655041439" => ["app_id" => "107"],
            "110659871557" => ["app_id" => "107"],
            "110659866957" => ["app_id" => "107"],
            "111580090967" => ["app_id" => "93"],
            "1633137763673096" => ["app_id" => "77"],
            "110659886424" => ["app_id" => "107"]
        );
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }

    public function getnewtouTiaov2($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
            "1634854264204300" => ["app_id" => "127"],
            "1634854617897998" => ["app_id" => "127"],
            "1636917837114380" => ["app_id" => "93"],
            "1636918030534663" => ["app_id" => "129"],
            "1636918151490563" => ["app_id" => "93"],
            "1636918268913677" => ["app_id" => "127"],
            "1653776830602251" => ["app_id" => "68"],
            "1636918512519181" => ["app_id" => "117"]
        );
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }

    public function getnewtouTiaov3($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
            "1638565951868941" => ["app_id" => "127"],
            "1638566171071565" => ["app_id" => "127"],
            "1639114795287566" => ["app_id" => "127"],
            "1639115541053453" => ["app_id" => "117"],
            "1639114890601484" => ["app_id" => "127"],
            "1638566330742797" => ["app_id" => "127"],
            "1639117032686603" => ["app_id" => "127"],
            "1638565801105412" => ["app_id" => "127"]
        );
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }

    public function getnewtouTiaov1($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
            "3223392436101134" => ["app_id" => "107"],
            "3188212662276990" => ["app_id" => "114"],
            "461423825914632" => ["app_id" => "114"],
            "3205800250321131" => ["app_id" => "77"],
            "1631851380627468" => ["app_id" => "77"],
            "1631851586421772" => ["app_id" => "107"],
            "1633137411769351" => ["app_id" => "77"],
            "1631851177659404" => ["app_id" => "107"]
        );
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }


    public function getnewtouTiaov4($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(

            "1641194098001931" => ["app_id" => "127", "type" => 2],
            "1645072493441032" => ["app_id" => "132", "type" => 2],
            "1645070080411651" => ["app_id" => "77", "type" => 2],
            "1646974296844302" => ["app_id" => "107", "type" => 2],
            "1646981667993608" => ["app_id" => "77", "type" => 2],
            "1646982160954380" => ["app_id" => "127", "type" => 2],
            "1646981953298439" => ["app_id" => "107", "type" => 2],
            "1645071690447884" => ["app_id" => "93", "type" => 2],
            "1654973550608396" => ["app_id" => "114", "type" => 2]
        );
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }

    //头条数据拉取 2018-12-17
    public function gettouTiao($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = $this->touTiaoAccount("all");
        foreach ($advertiser_ids as $k => $vds) {
            $this->gettouTiaoRequest($k, $start, $end);
            sleep(5);
        }
        exit("ok");
    }

    //

    //头条汇总请求
    private function gettouTiaoRequest($advertiser_id, $start, $end)
    {
        header("Content-type: text/html; charset=utf-8");
        if ($this->getcompanytype($advertiser_id) == 1) {
            $access_token = $this->gettouTiaotoken();
        } else {
            $access_token = $this->getNewTouTiaotoken();
        }

        $out_put = [];
        $out_put = $this->getcreativedata($out_put, $access_token, $advertiser_id, $start, $end, 1);
        if (!empty($out_put)) {
            $str = "[" . implode(",", $out_put) . "]";
            $res = $this->readcreative($advertiser_id, $str);
            $this->updateTouTiaoadsData($res, $start);
            $ad_str = "[" . implode(",", array_column($res, "ad_id")) . "]";
            $data = $this->gettouads($advertiser_id, $ad_str);
            $this->updateTouTiaocamData($data, $start);
        }
        return true;
    }

    //广告组报告
    public function campaignReport($start = "", $end = "")
    {
        return $this->gettouTiaoAdReport($start,$end);exit;
		header("Content-type: text/html; charset=utf-8");
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
		$access_token = $this->getNewTouTiaotoken();
		$advertiser_ids = Db::name("advertising_account")->where(["channel" =>1])->select();
        foreach ($advertiser_ids as $k => $vds) {
            $url = 'https://ad.oceanengine.com/open_api/2/report/campaign/get/?advertiser_id=' . $vds['advertiser_id'] . '&start_date=' . $start . '&end_date=' . $end . '&page_size=300&page=1&group_by=["STAT_GROUP_BY_FIELD_ID"]';
            $res = $this->getcreative($access_token, $url);
            $result = json_decode($res, true);
            if (!empty($result) && isset($result["data"]["list"])) {
                $list = $result["data"]["list"] ? $result["data"]["list"] : [];
                if (!empty($list)) {
                    $app_id = $vds["app_id"];
                    foreach ($list as $vv) {
                        if ($vv["cost"] > 0 || $vv["convert"] > 0) {
                            $spend = round($vv["cost"] * 0.145773, 4); //当前汇率是6.86 2019-01-07
                            $r = ["impressions" => $vv["show"], "clicks" => $vv["click"], "installs" => $vv["convert"], "spend" => $spend, "country" => "CN", "date" => $start, "target_id" => $vds['advertiser_id'], "campaign_name" => $vv["campaign_name"], "campaign_id" => $vv["campaign_id"], "app_id" => $app_id];
                            $this->insertTouTiaoCampaignData($r, 32);
                        }
                    }
                }
            }
        }
        exit("ok");
    }

    
	//头条广告计划 新 2021-02-01
	public function gettouTiaoAdReport($start = "", $end = ""){
		
		header("Content-type: text/html; charset=utf-8");
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
		 $access_token = $this->getNewTouTiaotoken();
		$advertiser_ids = Db::name("advertising_account")->where(["channel" =>1])->select();
        foreach ($advertiser_ids as $k => $vds) {
            
            $url = 'https://ad.oceanengine.com/open_api/2/report/ad/get/?advertiser_id=' . $vds['advertiser_id'] . '&start_date=' . $start . '&end_date=' . $end . '&page_size=300&page=1&group_by=["STAT_GROUP_BY_FIELD_ID"]';
            $res = $this->getcreative($access_token, $url);
            $result = json_decode($res, true);
            if (!empty($result) && isset($result["data"]["list"])) {
                $list = $result["data"]["list"] ? $result["data"]["list"] : [];
                if (!empty($list)) {
                    $app_id = $vds["app_id"];
                    foreach ($list as $vv) {
                        if ($vv["cost"] > 0 || $vv["convert"] > 0) {
                            $spend = round($vv["cost"] * 0.145773, 4); //当前汇率是6.86 2019-01-07
                            $r = ["impressions" => $vv["show"], "clicks" => $vv["click"], "installs" => $vv["convert"], "spend" => $spend, "country" => "CN", "date" => $start, "target_id" => $vds['advertiser_id'], "campaign_name" => $vv["campaign_name"], "campaign_id" => $vv["campaign_id"],"adset_id"=>$vv["ad_id"],"adset_name"=>$vv["ad_name"],"app_id" => $app_id];
                            $this->insertTouTiaoAdData($r,32);
                        }
                    }
                }
            }
        }
        exit("ok");
	}
	
	//获取广告创意数据
    private function getcreativedata($out_put, $access_token, $advertiser_id = "", $start_date, $end_date, $page)
    {

        $url = 'https://ad.oceanengine.com/open_api/2/report/creative/get/?advertiser_id=' . $advertiser_id . '&start_date=' . $start_date . '&end_date=' . $end_date . '&page_size=300&page=' . $page . '&group_by=["STAT_GROUP_BY_FIELD_ID"]';

        $res = $this->getcreative($access_token, $url);
        $res = json_decode($res, true);
        if (isset($res["code"]) && $res["code"] == "0") {
            $total_page = $res["data"]["page_info"]["total_page"];
            if ($page <= $total_page) {
                $list = $res["data"]["list"];
                if (!empty($list)) {
                    $app_id = "";
                    $app_data = $this->gettouTiaoAccount($advertiser_id);
                    if (!empty($app_data)) {
                        $app_id = $app_data["app_id"];
                    }
                    foreach ($list as $vv) {
                        if ($vv["cost"] > 0 || $vv["convert"] > 0) {

                            $spend = round($vv["cost"] * 0.145773, 4); //当前汇率是6.86 2019-01-07
                            $r = ["impressions" => $vv["show"], "clicks" => $vv["click"], "installs" => $vv["convert"], "spend" => $spend, "country" => "CN", "date" => $start_date, "target_id" => $advertiser_id, "ad_id" => $vv["id"], "app_id" => $app_id];
                            $this->insertTouTiaoCreativeData($r);
                            $out_put[] = $vv["id"];
                        }
                    }
                    if ($page < $total_page) {
                        ++$page;
                        return $this->getcreativedata($out_put, $access_token, $advertiser_id, $start_date, $end_date, $page);
                    }
                }

            }
        }
        return $out_put;
    }

    //更新计划数据
    private function updateTouTiaoadsData($res, $date)
    {
        if (!empty($res)) {

            foreach ($res as $vvv) {
                $a = Db::name("adspend_data")->field("id,adset_id,ad_name")->where(["platform_type" => 32, "date" => $date, "ad_id" => $vvv["id"]])->find();
                if (!empty($a)) {
                    if (empty($a["adset_id"]) || empty($a["ad_name"])) {
                        Db::name("adspend_data")->where(["id" => $a["id"]])->update(["ad_name" => $vvv["title"], "adset_id" => $vvv["ad_id"]]);
                    }
                }
            }
        }
        return true;
    }

    //更新计划和Campagin数据
    private function updateTouTiaocamData($res, $date)
    {
        if (!empty($res)) {

            foreach ($res as $vvvv) {

                Db::name("adspend_data")->where(["adset_id" => $vvvv["id"], "date" => $date])->update(["store_id" => $vvvv["download_url"], "adset_name" => $vvvv["name"], "campaign_id" => $vvvv["campaign_id"]]);

            }
        }
        return true;
    }

    //更新创意数据
    private function insertTouTiaoCreativeData($r)
    {
        if (!empty($r)) {
            $o = Db::name("adspend_data")->where(["platform_type" => 32, "date" => $r["date"], "target_id" => $r["target_id"], "ad_id" => $r["ad_id"]])->find();
            if (!empty($o)) {
                Db::name("adspend_data")->where(["id" => $o["id"]])->update($r);
            } else {
                $r["platform_type"] = 32;
                Db::name("adspend_data")->insert($r);
            }
        }
        return true;
    }

    //campaign
    private function insertTouTiaoCampaignData($r, $platform)
    {
        if (!empty($r)) {
            $o = Db::name("adspend_data")->where(["platform_type" => $platform, "date" => $r["date"], "target_id" => $r["target_id"], "campaign_id" => $r["campaign_id"], "country" => $r["country"]])->find();
            if (!empty($o)) {
                Db::name("adspend_data")->where(["id" => $o["id"]])->update($r);
            } else {
                $r["platform_type"] = $platform;
                Db::name("adspend_data")->insert($r);
            }
        }
        return true;
    }
	
	 private function insertTouTiaoAdData($r, $platform)
    {
        if (!empty($r)) {
            $o = Db::name("adspend_data")->where(["platform_type" => $platform, "date" => $r["date"], "target_id" => $r["target_id"], "adset_id" => $r["adset_id"], "country" => $r["country"]])->find();
            if (!empty($o)) {
                Db::name("adspend_data")->where(["id" => $o["id"]])->update($r);
            } else {
                $r["platform_type"] = $platform;
                Db::name("adspend_data")->insert($r);
            }
        }
        return true;
    }

    //创意素材信息
    public function readcreative($advertiser_id, $creative_ids)
    {
        //header("Content-type: text/html; charset=utf-8");
        if ($this->getcompanytype($advertiser_id) == 1) {
            $access_token = $this->gettouTiaotoken();
        } else {
            $access_token = $this->getNewTouTiaotoken();
        }
        $url = 'https://ad.oceanengine.com/open_api/2/creative/material/read/?advertiser_id=' . $advertiser_id . '&fields=["id","ad_id","title"]&creative_ids=' . $creative_ids;
        $res = $this->getcreative($access_token, $url);
        $res = json_decode($res, true);

        if (isset($res["code"]) && $res["code"] == "0") {
            return $res["data"];
        }
        return [];
    }

    //获取广告计划信息
    public function gettouads($advertiser_id, $ad_ids)
    {
        if ($this->getcompanytype($advertiser_id) == 1) {
            $access_token = $this->gettouTiaotoken();
        } else {
            $access_token = $this->getNewTouTiaotoken();
        }
        $url = 'https://ad.oceanengine.com/open_api/2/ad/get/?advertiser_id=' . $advertiser_id . '&fields=["id","name","download_url","campaign_id"]&filtering={"ids":' . $ad_ids . '}';
        $res = $this->getcreative($access_token, $url);
        $res = json_decode($res, true);
        if (isset($res["code"]) && $res["code"] == "0") {
            return $res["data"]["list"];
        }
        return [];
    }

    //头条广告数据拉取
    private function getcreative($access_token, $url)
    {

        $headers = array('Access-Token: ' . $access_token);
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候

        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    //预览视频专用
    public function previewvideo($advertiser_id, $ad_id)
    {

        if ($this->getcompanytype($advertiser_id) == 1) {
            $access_token = $this->gettouTiaotoken();
        } else {
            $access_token = $this->getNewTouTiaotoken();
        }
        $video = "https://cc.toutiao.com/v/video_player?code=";
        $url = 'https://ad.oceanengine.com/open_api/2/creative/get/?advertiser_id=' . $advertiser_id . '&filtering={"creative_ids":[' . $ad_id . ']}';
        $res = $this->getcreative($access_token, $url);
        $data = json_decode($res, true);

        if (isset($data["code"]) && $data["code"] == 0) {
            $v_data = $data["data"]["list"][0];
            //视频
            $video_id = $v_data["video_id"];
            if ($v_data["image_mode"] == "CREATIVE_IMAGE_MODE_VIDEO_VERTICAL") {

                return redirect($video . $video_id);
                exit;
            } elseif ($v_data["image_mode"] == "CREATIVE_IMAGE_MODE_LARGE") {
                $img_u = $v_data["image_ids"][0];
                return redirect("https://sf6-ttcdn-tos.pstatp.com/obj/" . $img_u);
                exit;
            } else {
                return redirect($video . $video_id);
                exit;
            }

        }
    }

    public function gettikTokReportOther($start = "", $end = "")
    {
        header("Content-type: text/html; charset=utf-8");
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
            "6811014488456691717" => ["app_id" => "143"],
            "6828387903798771718" => ["app_id" => "127"],
            "6828388159647121413" => ["app_id" => "127"],
        );
        $access_token = $this->getTikToktoken();
        foreach ($advertiser_ids as $k => $vds) {
            $out_put = [];
            $c_fields = json_encode(['show_cnt', 'stat_cost', 'convert_cnt', 'click_cnt', 'campaign_name']);
            $campaign_url = 'https://ads.tiktok.com/open_api/2/reports/campaign/get/?advertiser_id=' . $k . '&start_date=' . $start . '&end_date=' . $end . '&page_size=500&page=1&group_by=["STAT_GROUP_BY_FIELD_ID"]&fields=' . $c_fields;;
            $campaign_res = $this->getcreative($access_token, $campaign_url);
            $campaign_result = json_decode($campaign_res, true);
            if (isset($campaign_result["data"]["list"]) && !empty($campaign_result["data"]["list"])) {
                $campaign_list = $campaign_result["data"]["list"];
                foreach ($campaign_list as $c) {
                    $campaigns = json_encode([$c['campaign_id']]);
                    $dimensions = json_encode(['COUNTRY']);
                    $fields = json_encode(['show_cnt', 'stat_cost', 'convert_cnt', 'click_cnt']);
                    $url = "https://ads.tiktok.com/open_api/2/audience/campaign/get/?advertiser_id={$k}&start_date={$start}&end_date={$end}&page_size=300&page=1&dimensions={$dimensions}&campaign_ids={$campaigns}&fields={$fields}";
                    $res = $this->getcreative($access_token, $url);
                    $result = json_decode($res, true);
                    if (!empty($result) && isset($result["data"]["list"])) {
                        $list = $result["data"]["list"] ? $result["data"]["list"] : [];
                        if (!empty($list)) {
                            foreach ($list as $vv) {
                                if ($vv["metrics"]["stat_cost"] > 0 || $vv["metrics"]["convert_cnt"] > 0) {
                                    $r = ["impressions" => $vv["metrics"]["show_cnt"], "clicks" => $vv["metrics"]["click_cnt"], "installs" => $vv["metrics"]["convert_cnt"], "spend" => $vv["metrics"]["stat_cost"], "country" => $vv["dimensions"]["country_id"], "date" => $start, "target_id" => $k, "campaign_name" => $c["campaign_name"], "campaign_id" => $c["campaign_id"]];
                                    $this->insertTouTiaoCampaignData($r, 36);
                                }
                            }
                        }
                    }
                }
            }

        }
        exit("ok");
    }

    public function getTikTokReport($start = "", $end = "")
    {
        return $this->getTiktokAdReport($start, $end);
		header("Content-type: text/html; charset=utf-8");
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $advertiser_ids = array(
           //"6841857125644255238" => ["app_id" => "160"],
        );
		$advertiser_ids = Db::name("advertising_account")->where(["channel" =>4])->select();
        $access_token = $this->getTikToktoken();
		
        foreach ($advertiser_ids as $k => $vds) {

            $c_fields = json_encode(['show_cnt', 'stat_cost', 'convert_cnt', 'click_cnt', 'campaign_name']);
            $campaign_url = 'https://ads.tiktok.com/open_api/2/reports/campaign/get/?advertiser_id=' . $vds['advertiser_id'] . '&start_date=' . $start . '&end_date=' . $end . '&page_size=500&page=1&group_by=["STAT_GROUP_BY_FIELD_ID"]&fields=' . $c_fields;
            $campaign_res = $this->getcreative($access_token, $campaign_url);
            $campaign_result = json_decode($campaign_res, true);
            if (isset($campaign_result["data"]["list"]) && !empty($campaign_result["data"]["list"])) {
                $campaign_list = $campaign_result["data"]["list"];
                foreach ($campaign_list as $c) {
                    $campaigns = json_encode([$c['campaign_id']]);
                    $dimensions = json_encode(['COUNTRY']);
                    $fields = json_encode(['show_cnt', 'stat_cost', 'convert_cnt', 'click_cnt']);
                    $url = "https://ads.tiktok.com/open_api/2/audience/campaign/get/?advertiser_id={$vds['advertiser_id']}&start_date={$start}&end_date={$end}&page_size=300&page=1&dimensions={$dimensions}&campaign_ids={$campaigns}&fields={$fields}";
                    $res = $this->getcreative($access_token, $url);
                    $result = json_decode($res, true);
                    if (!empty($result) && isset($result["data"]["list"])) {
                        $list = $result["data"]["list"] ? $result["data"]["list"] : [];
                        if (!empty($list)) {
                            $app_id = "";
                            if (!empty($vds)) {
                                $app_id = $vds["app_id"];
                            }

                            foreach ($list as $vv) {
                                if ($vv["metrics"]["stat_cost"] > 0 || $vv["metrics"]["convert_cnt"] > 0) {
                                    $r = ["impressions" => $vv["metrics"]["show_cnt"], "clicks" => $vv["metrics"]["click_cnt"], "installs" => $vv["metrics"]["convert_cnt"], "spend" => $vv["metrics"]["stat_cost"], "country" => $vv["dimensions"]["country_id"], "date" => $start, "target_id" => $vds['advertiser_id'], "campaign_name" => $c["campaign_name"], "campaign_id" => $c["campaign_id"], "app_id" => $app_id];
                                    $this->insertTouTiaoCampaignData($r, 36);
                                }
                            }
                        }
                    }
                }
            }
        }
        exit("ok");
    }
	
	public function getTiktokAdReport($start = "", $end = ""){
		
		header("Content-type: text/html; charset=utf-8");
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        
		$advertiser_ids = Db::name("advertising_account")->where(["channel" =>4])->select();
		foreach ($advertiser_ids as $k => $vds) {
			 $row = $this->get_tiktok_page($vds['advertiser_id'],$start,$end);
			 if(isset($row["data"]["list"]) && !empty($row["data"]["list"]))
				{
					$list = $row["data"]["list"];
					foreach ($list as $vv) 
					{
						$data =array(
							//"ad_id"=>$vv["dimensions"]["ad_id"],
							//"ad_name"=>$vv["metrics"]["ad_name"],
							"adset_id"=>$vv["dimensions"]["adgroup_id"],
							"adset_name"=>$vv["metrics"]["adgroup_name"],
							"campaign_id"=>$vv["metrics"]["campaign_id"],
							"campaign_name"=>$vv["metrics"]["campaign_name"],
							"impressions"=>$vv["metrics"]["impressions"],
							"clicks"=>$vv["metrics"]["clicks"],
							"installs"=>$vv["metrics"]["result"],
							"spend"=>$vv["metrics"]["spend"],
							"country"=>$vv["dimensions"]["country_code"],
							"date"=>$start,
							"platform_type"=>36,
							"target_id"=>$vds['advertiser_id'],
							"app_id"=>$vds["app_id"]
						);
						$r = Db::name("adspend_data")->where(["platform_type" =>36, "date" => $data["date"], "country" => $data["country"],"target_id" => $data["target_id"], "adset_id" => $data["adset_id"]])->find();
						if (empty($r)) {
							Db::name("adspend_data")->insert($data);
						} else {                      
								Db::name("adspend_data")->where("id", $r["id"])->update($data);
							}
					}
				}
		}
		if(!empty($advertiser_ids))
		{
			foreach($advertiser_ids as $ad){
				$this->get_tiktok_page_skan($ad['advertiser_id'],$ad["app_id"],$start,$end);
			}
		}
		exit("ok");
	}
	
	private function get_tiktok_page($advertiser_id,$start,$end){
		$access_token = $this->getTikToktoken();
		//$advertiser_id="6933399571338887169";
		$c_fields = json_encode(['show_cnt', 'stat_cost', 'time_attr_effect_cnt','click_cnt','campaign_id','campaign_name','adgroup_name']);
		$ad_url = 'https://ads.tiktok.com/open_api/v1.2/reports/integrated/get/?advertiser_id=' . $advertiser_id . '&start_date=' . $start . '&end_date=' . $end . '&page_size=1000&page=1&dimensions=["adgroup_id","country_code"]&report_type=AUDIENCE&data_level=AUCTION_ADGROUP&metrics=' . $c_fields;
        $ad_res = $this->getcreative($access_token,$ad_url);
		return json_decode($ad_res,true);
	}
	
	public function ttss(){
		$advertiser_id="6933399571338887169";
		$start = date("Y-m-d", strtotime("-1 day"));
        $end = date("Y-m-d", strtotime("-1 day"));
		$res = $this->get_tiktok_page_skan($advertiser_id,$start,$end);
		print_r($res);exit;
	}
	
	private function get_tiktok_page_skan($advertiser_id,$app_id,$start,$end){
		$access_token = $this->getTikToktoken();		
		$c_fields = json_encode(['show_cnt', 'stat_cost', 'time_attr_effect_cnt','skan_result','click_cnt','campaign_id','campaign_name','adgroup_name']);
		$ad_url = 'https://ads.tiktok.com/open_api/v1.2/reports/integrated/get/?advertiser_id=' . $advertiser_id . '&start_date=' . $start . '&end_date=' . $end . '&page_size=1000&page=1&dimensions=["adgroup_id"]&report_type=BASIC&data_level=AUCTION_ADGROUP&metrics=' . $c_fields;
        $ad_res = $this->getcreative($access_token,$ad_url);
		$result = json_decode($ad_res,true);
		if(isset($result["data"]["list"]) && !empty($result["data"]["list"]))
		{
			$list = $result["data"]["list"];
			foreach ($list as $vv) 
			{
			   if($vv["metrics"]["skan_result"]>0)
			   {
				   
				  $data =array(							
						"adset_id"=>$vv["dimensions"]["adgroup_id"],
						"adset_name"=>$vv["metrics"]["adgroup_name"],
						"campaign_id"=>$vv["metrics"]["campaign_id"],
						"campaign_name"=>$vv["metrics"]["campaign_name"],
						"impressions"=>$vv["metrics"]["impressions"],
						"clicks"=>$vv["metrics"]["clicks"],
						"installs"=>$vv["metrics"]["skan_result"],
						"spend"=>$vv["metrics"]["spend"],
						"date"=>$start,
						"target_id"=>$advertiser_id,
						"platform_type"=>36
					);
					$r = Db::name("adspend_data")->where(["platform_type" =>36,"date" => $data["date"],"target_id" => $data["target_id"], "adset_id" => $data["adset_id"]])->find();
					if (empty($r)) {
						$country = Db::name("adspend_data")->where(["platform_type" =>36,"target_id" => $data["target_id"], "adset_id" => $data["adset_id"]])->value('country');
						$data["country"] = $country;
						$data["app_id"] = $app_id;
						Db::name("adspend_data")->insert($data);
					} else {                      
					  Db::name("adspend_data")->where("id", $r["id"])->update(["installs"=>$vv["metrics"]["skan_result"]]);
					}
			   }				   
			}
		}
	}
	
	//新增 腾讯广告拉取
	public function getGtdReport($start = "", $end = ""){
		if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
		$account_id ="19364692";
		$res = $this->get_gtd_request($account_id,$start,$end);
        if(isset($res["data"]["list"]) && !empty($res["data"]["list"]))
		{
			$list =  $res["data"]["list"];
			foreach($list as $vv)
			{
				$data =array(
				      "campaign_id"=>$vv["campaign_id"],
					  "campaign_name"=>$vv["campaign_name"],
					  "impressions"=>$vv["view_count"],
					  "clicks"=>$vv["valid_click_count"],
					  "installs"=>$vv["activated_count"],
					  "spend"=>round(($vv["cost"]/100) * 0.145773, 4), //当前汇率是6.86 2019-01-07,
					  "country"=>"CN",
					  "date"=>$vv["date"],
					  "platform_type"=>35,
					  "target_id"=>$vv["account_id"],
					  "app_id"=>""
				);
				$r = Db::name("adspend_data")->where(["platform_type" =>35, "date" => $data["date"], "country" => $data["country"],"target_id" => $data["target_id"], "campaign_id" => $data["campaign_id"]])->find();
				if (empty($r)) {
					$data["app_id"] = getappidbycampaign($data["campaign_id"],35);
					Db::name("adspend_data")->insert($data);
					} else {
					Db::name("adspend_data")->where("id", $r["id"])->update($data);
				 }
			}
		}
		exit("ok");
	}
	
	private function get_gtd_request($account_id,$start,$end){
		$url = 'https://api.e.qq.com/v1.1/daily_reports/get';
		$access_token ="6b7558b9c8fd0c4a1dadb204c66d0d9a";
		$refresh_token ="304c6914e07f748b1753d901d22796eb";
		$common_parameters = array (
        'access_token' => $access_token,
        'timestamp' => time(),
		'fields'=>['account_id','campaign_id','campaign_name','date','view_count','valid_click_count','cost','download_count','activated_count'],
        'nonce' => md5(uniqid('', true))
		);   
		$parameters = array (
		  'account_id' => '19364692',
		  'level' => 'REPORT_LEVEL_CAMPAIGN',
		  'date_range' => 
		  array (
			'start_date' =>$start,
			'end_date' =>$end,
		  ),
          'group_by'=>['date','campaign_id'],		  
		  'page' => 1,
		  'page_size' => 1000,      
		);
		$parameters = array_merge($common_parameters, $parameters);
		foreach ($parameters as $key => $value) {
			if (!is_string($value)) {
				$parameters[$key] = json_encode($value);
			}
		}
		$request_url = $url . '?' . http_build_query($parameters);
		 $res = $this->googlecurl($request_url,[], 'get');
        return json_decode($res, true);
		
		//https://developers.e.qq.com/oauth/authorize?client_id=1111613144&redirect_uri=http://console.gamebrain.io/adspend/callback
		//authorization_code=9badb435c911b69a2f339d5c39fbc2aa&state=
		/* $data = array(
            "client_id" => "1111613144",
            "client_secret" => "plDXsg40gfJJuwiw",
            "grant_type" => "authorization_code",
            "authorization_code" =>"9badb435c911b69a2f339d5c39fbc2aa",
			"redirect_uri"=>"http://console.gamebrain.io/adspend/callback"
        );
        $url = "https://api.e.qq.com/oauth/token"; */
	}
	
	public function getKuaishouReport($start = "", $end = ""){
		if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
		$access_token = $this->getKuaishouToken();
		$url ="https://ad.e.kuaishou.com/rest/openapi/v1/report/campaign_report";
		$params =array(
		    "advertiser_id"=>"8051616",
			"start_date"=>$start,
			"end_date"=>$end,
			"page_size"=>2000
		);
		$res = $this->googlecurl_json($url,$access_token,json_encode($params), 'post');
		$res = json_decode($res,true);
        if(isset($res["data"]["details"]) && !empty($res["data"]["details"]))
		{
			$result = $res["data"]["details"];
			foreach($result as $vv)
			{
				$data =array(
				      "campaign_id"=>$vv["campaign_id"],
					  "campaign_name"=>$vv["campaign_name"],
					  "impressions"=>$vv["aclick"],
					  "clicks"=>$vv["bclick"],
					  "installs"=>$vv["activation"],
					  "spend"=>round($vv["charge"] * 0.145773, 4), //当前汇率是6.86 2019-01-07,
					  "country"=>"CN",
					  "date"=>$start,
					  "platform_type"=>42,
					  "target_id"=>"8051616",
					  "app_id"=>""
				);
				$r = Db::name("adspend_data")->where(["platform_type" =>42, "date" => $data["date"], "country" => $data["country"],"target_id" => $data["target_id"], "campaign_id" => $data["campaign_id"]])->find();
				if (empty($r)) {
					$data["app_id"] = getappidbycampaign($data["campaign_id"],42);
					Db::name("adspend_data")->insert($data);
					} else {
					Db::name("adspend_data")->where("id", $r["id"])->update($data);
				 }
			}
		}
		exit("ok");
	}

    //刷新token
    public function gettouTiaotoken()
    {

        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $access_token = $mem->get('access_token');
        if ($access_token) {
            return $access_token;
        }
        $refresh_token = $mem->get('refresh_token');

        //刷新token
        $data = array(
            "app_id" => "1620074816149511",
            "secret" => "28f20dd56475ec7590f67e2c49c51e3e5f99910a",
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh_token
        );
        $url = "https://ad.oceanengine.com/open_api/oauth2/refresh_token/";
        $res = $this->googlecurl($url, http_build_query($data), 'post');
        $result = json_decode($res, true);
        if (isset($result["code"]) && $result["code"] == 0) {
            $mem->set('access_token', $result["data"]["access_token"], 0, 72000);
            $mem->set('refresh_token', $result["data"]["refresh_token"]);
            return $result["data"]["access_token"];
        }
        return "";
    }

    //新的TikToktoken
    public function getTikToktoken()
    {

        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $access_token = $mem->get('tiktok_access_token');
        if ($access_token) {
            return $access_token;
        }
        $refresh_token = $mem->get('tiktok_refresh_token');

        //刷新token
        $data = array(
            "app_id" => "1661926852885510",
            "secret" => "64538f1104382015f14e90f3efd461ed1c150210",
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh_token
        );
        $url = "https://ads.tiktok.com/open_api/oauth2/refresh_token/";
        $res = $this->googlecurl($url, http_build_query($data), 'post');
        $result = json_decode($res, true);
        if (isset($result["code"]) && $result["code"] == 0) {
            $mem->set('tiktok_access_token', $result["data"]["access_token"], 0, 72000);
            $mem->set('tiktok_refresh_token', $result["data"]["refresh_token"]);
            return $result["data"]["access_token"];
        }
        return "";
    }

    //新的头条token
    public function getNewTouTiaotoken()
    {

        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $access_token = $mem->get('new_toutiao_access_token');
        if ($access_token) {
            return $access_token;
        }
        $refresh_token = $mem->get('new_toutiao_refresh_token');

        //刷新token
        $data = array(
            "app_id" => "1620074816149511",
            "secret" => "28f20dd56475ec7590f67e2c49c51e3e5f99910a",
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh_token
        );
        $url = "https://ad.oceanengine.com/open_api/oauth2/refresh_token/";
        $res = $this->googlecurl($url, http_build_query($data), 'post');
        $result = json_decode($res, true);
        if (isset($result["code"]) && $result["code"] == 0) {
            $mem->set('new_toutiao_access_token', $result["data"]["access_token"], 0, 72000);
            $mem->set('new_toutiao_refresh_token', $result["data"]["refresh_token"]);
            return $result["data"]["access_token"];
        }
        return "";
    }

    public function tokenmem()
    {
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
       $refresh_token ="eyJraWQiOiJyZWZyZXNoLXRva2VuLWExMjhnY20uMCIsInR5cCI6IkpXVCIsImVuYyI6IkExMjhHQ00iLCJhbGciOiJkaXIifQ..-0rXXkgW8SFoE36v.8x0dhgnsBmD-8QS1FvIRC9v7410bYWBWRM8urUx1rhQjK5ceyItFMPefq-PivZdwPiFju6Tm2hlsIFOaib3tigK8VU2L79C5bSVpxOvgYkBGV1-fw7LgnWdkKSeBeMEDWoqa_Bn3W15gd_R5H5pvNvJ_-zq1nMxE62SChEJUZElsrvJrQK5K86t8NHAktdC__l9Z_27e7hwVmrFIB-xKvaBcjWFH6F-ZAVxs0y5yh4hiqjz4Q22cCASY27dR1qLRTFZeSGj-x_UnBq4.s2JLwIhSM5NqfG1xR4uPWw";
        $mem->set('snapchat_refresh_token',$refresh_token);
		$mem->set('snapchat_access_token', "eyJpc3MiOiJodHRwczpcL1wvYWNjb3VudHMuc25hcGNoYXQuY29tXC9hY2NvdW50c1wvb2F1dGgyXC90b2tlbiIsInR5cCI6IkpXVCIsImVuYyI6IkExMjhDQkMtSFMyNTYiLCJhbGciOiJkaXIiLCJraWQiOiJhY2Nlc3MtdG9rZW4tYTEyOGNiYy1oczI1Ni4wIn0..bvMc5-ZBiUGt8kWVyVq3Rw.Q22Go4UNURZrX8oSRcsLfr-cUG3Et1oQafHXGMlQPhm6SsbwSukMaHwwRl8tIPfLpjXkwROxOzVFIunQxqxYPgiY37Rc0wifkuoI4TRtF5W2Z_ryTtm0_8F4rxETUBRNKNA7MXG93jyKUiPSqx2ykS1Qfh-Z_cQSjVz4Boazi5DHl3SpPc9noeRpby2Ztgpaf9YPP8vXGvXRnPjdViZgusEhw634L6wdkvzis0ObOW12rIPrIOPiHVbsmp9Sa-TVLN0W8cp0dpA4wg2PltB6pOZlbQlFs73efwrAO0QlnsTAXsnn_z-Es9PN2j7pSZVqig9WEOiHejRtKsl54CxC61Y7MD5gASXvnqILZKUUYEGLFqlWRahIP94fQjPXbUUtfCwF57FUdRRDtiqt1_r16Q2AWeHSqEwnGyI3ibHy567WCkzmDEjmVnyriNzsaq9hw18qBwcqIKhifJP48H3Q-Vu01tvA1av9GXS1baIiSe_AG3cIUdaAjVkiqTGbwIRi7l9hpv61rmOY6wJJ5mWwA43w85F4mgcc1Qm6L3SjeTAyoyt88_Wxk4fzimXf-2G8iIjK91esSgPzMDUZ7tzZwQwHfSQjG3KqE0wPt5x6-I2L2aTXB0e1VT1Dfw7S74-QdXTA0gkpU4MHHikCoPYTm5LY7UOnONb83zCqZCPMxyi5Nc70Fy1Goe4OMrsKvM8j1CplmRiMlP20875rEwugO_7hKEgUMVYS_kisLVpe0cE.XAki36lDXbkkaO4h1UR2ZQ", 0, 72000);
        //$mem->set('tiktok_access_token', "48567f7fa0ad1d38faf4b8aab2bdc1789e18efe3", 0, 72000);
        //$mem->set('tiktok_refresh_token', "30bb36e007f7815c080d0dd43be60b7cc205ac68");
        //$mem->set('access_token',"5a5dc191c28aa74964e227ac241a36f026ed62c0",0,72000);
        //$mem->set('refresh_token',"159b15a3a0e0d599c5a3fd16e170a9b579a5b216");//一个月重新授权一次 2018-12-19
        //$val = $mem->get('access_token');
        //$mem->set('new_toutiao_access_token',"ad1b705906d68917fa56785ca48bcf69f180f40b",0,72000);
        //$mem->set('new_toutiao_refresh_token',"2e1b74ab0272180105d67e7e26bb039a9a284dcf");//
		
		//$mem->set('kuaishou_access_token',"c9a6c944e64a8086b2ac9d68a75ff9d8", 0, 72000);
        //$mem->set('kuaishou_refresh_token',"e2a94660926d8de44344f72834944ec1");
        echo "ok";
    }

    //获取头条Refresh_token
    public function gettouTiaoRefresh_token()
    {
        //https://ad.oceanengine.com/openapi/audit/oauth.html?app_id=1620074816149511&state=your_custom_params&scope=[1,2,3,4,5]&redirect_uri=http%3A%2F%2Fconsole.gamebrain.io%2Fadspend%2FtouTiaoCallback
        $auth_code = "29d70540a57ae11d5d1643046537d1dc6e89903d";
        $data = array(
            "app_id" => "1620074816149511",
            "secret" => "28f20dd56475ec7590f67e2c49c51e3e5f99910a",
            "grant_type" => "auth_code",
            "auth_code" => $auth_code
        );
        $url = "https://ad.oceanengine.com/open_api/oauth2/access_token/";
        $res = $this->googlecurl($url, http_build_query($data), 'post');
        print_r(json_decode($res, true));
    }
	
	public function getKuaishouToken(){
		
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $access_token = $mem->get('kuaishou_access_token');
        if ($access_token) {
            return $access_token;
        }
        $refresh_token = $mem->get('kuaishou_refresh_token');
		$advertiser_id ="8051616";
		//return $access_token;
		$app_id ="165893337";
		$secret ="dZ&4Yo=ycw+m55A=";
		/* $scope =urlencode('["ad_query","ad_manage","report_service","account_service"]');
		$callback =urlencode("http://console.gamebrain.io/adspend/callback");
		$url ="https://ad.e.kuaishou.com/openapi/oauth?app_id=165893337&scope={$scope}&redirect_uri={$callback}";
		echo $url;exit; */
		$refresh_url ="https://ad.e.kuaishou.com/rest/openapi/oauth2/authorize/refresh_token";
		$auth_code="4d270606c39a02e9cdca0f768e877ad6";
		$data = array(
            "app_id" => "165893337",
            "secret" => "dZ&4Yo=ycw+m55A=",
			"refresh_token"=>$refresh_token
            //"auth_code" => $auth_code
        );
        $url = "https://ad.e.kuaishou.com/rest/openapi/oauth2/authorize/access_token";
        $res = $this->googlecurl_json($refresh_url,"",json_encode($data), 'post');
		$result = json_decode($res, true);
		if (isset($result["code"]) && $result["code"] == 0) {
           
			$mem->set('kuaishou_access_token', $result["data"]["access_token"], 0, 72000);
            $mem->set('kuaishou_refresh_token', $result["data"]["refresh_token"]);
            return $result["data"]["access_token"];
        }
        return "";
		
	}
	
	private function googlecurl_json($url,$access_token,$data = null, $method = null)
    {
		$header = array();
        $header[] = 'Content-Type: application/json';
		if($access_token)
		{
			$header[] = 'Access-Token: ' . $access_token;
		}
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    public function gettikTokRefresh_token()
    {
        //https://ads.tiktok.com/marketing_api/auth?app_id=1661926852885510&state=your_custom_params&scope=[1,2,3,4,5]&redirect_uri=http%3A%2F%2Fconsole.gamebrain.io%2Fadspend%2FtikTokCallback
        $auth_code = "6885bf78d656efb4cf677865ae9a6a0d029a0f24";
        $data = array(
            "app_id" => "1661926852885510",
            "secret" => "64538f1104382015f14e90f3efd461ed1c150210",
            "grant_type" => "auth_code",
            "auth_code" => $auth_code
        );
        $url = "https://ads.tiktok.com/open_api/oauth2/access_token/";
        $res = $this->googlecurl($url, http_build_query($data), 'post');
        print_r(json_decode($res, true));
    }

    //Mobvista  start=2018-12-01 end=2018-12-02
    public function getmobvista($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        //$this->selfmobvista(1, $start, $end);
		$this->newselfmobvista(1, $start, $end);
        //$this->newselfmobvista(10, $start, $end);
        exit("ok");
    }

    private function newselfmobvista($page, $start_time, $end_time)
    {
        $api_key = 'a75581135b18fa3a2f2ac2e6f50aea78';
        $timestamp = time();
        $token = md5($api_key . md5($timestamp));
        $username = 'HelloWorld_MTG';
        //$url="http://ss-api.mintegral.com/api/v1/reports/data";
        $url = 'http://data.mintegral.com/v4.php?m=advertiser';
        $start_times = strtotime($start_time . " 00:00:00");
        $end_times = strtotime($end_time . " 23:59:59");
        $query = http_build_query([
            'username' => $username,
            'timestamp' => $timestamp,
            'token' => $token,
            'start_time' => $start_times,
            'end_time' => $end_times,
            'per_page' => 5000,
            'page' => $page,
            'utc' => '+8',
            'dimension' => 'location,sub_id',]);
        $url .= '&' . $query;
        $data = $this->curl($url);
        if (!empty($data) && isset($data["page"])) {
            $current_page = $data["page"];
            $page_count = $data["page_count"];
            if ($current_page <= $page_count) {
                $res = $data["data"];
                if (!empty($res)) {
                    foreach ($res as $v) {

                        if ($v["install"] > 0 && $v["location"] != "") {
                            $v["location"] = $v["location"] ? strtoupper($v["location"]) : "other";
                            $v["location"] = $v["location"] == "UK" ? "GB" : $v["location"];
                            $v["uuid"] = $username . $v["uuid"];
                            $r = Db::name("adspend_data")->where(["platform_type" => 1, "adset_id" => $v["sub_id"], "date" => $v["date"], "country" => $v["location"], "campaign_id" => $v["offer_id"]])->find();
                            $insert_data = [
                                "country" => $v["location"],
                                "platform_type" => 1,
                                "campaign_id" => $v["offer_id"],
                                "date" => $v["date"],
                                "campaign_name" => $v["uuid"],
                                "impressions" => $v["impression"],
                                "clicks" => $v["click"],
                                "spend" => $v["spend"],
                                "installs" => $v["install"],
                                "store_id" => $v["package_name"],
                                "adset_id" => $v["sub_id"],
                                "target_id" => $username,
                                "platform" => $v["platform"]
                            ];
                             if (empty($r)) {
                                $insert_data["app_id"] = getappidbycampaign($v["offer_id"], 1);
                                Db::name("adspend_data")->insert($insert_data);
                            } else {
                                Db::name("adspend_data")->where("id", $r["id"])->update($insert_data);
                            }
                        }
                    }
                }
                if ($current_page < $page_count) {
                    ++$page;
                    return $this->newselfmobvista($page, $start_time, $end_time);
                }
            }
        }
        return true;
    }

    private function selfmobvista($page, $start_time, $end_time)
    {
        $api_key = '8db9dc53806769a81a6470e0fb43c463';
        $timestamp = time();
        $token = md5($api_key . md5($timestamp));
        $username = 'HelloGames_MTG';
        $url = 'http://data.mintegral.com/v4.php?m=advertiser';
        $start_times = strtotime($start_time);
        $end_times = strtotime($end_time) + 24 * 60 * 60;
        $query = http_build_query([
            'username' => $username,
            'timestamp' => $timestamp,
            'token' => $token,
            'start_time' => $start_times,
            'end_time' => $end_times,
            'per_page' => 1000,
            'page' => $page,
            'utc' => '+8',
            'dimension' => 'location',]);
        $url .= '&' . $query;
        $data = $this->curl($url);

        if (!empty($data) && isset($data["page"])) {
            $current_page = $data["page"];
            $page_count = $data["page_count"];
            if ($current_page <= $page_count) {
                $res = $data["data"];
                if (!empty($res)) {
                    foreach ($res as $v) {

                        if ($v["install"] > 0 && $v["location"] != "") {
                            $v["location"] = $v["location"] ? strtoupper($v["location"]) : "other";
                            $v["location"] = $v["location"] == "UK" ? "GB" : $v["location"];
                            $r = Db::name("adspend_data")->where(["platform_type" => 1, "adset_id" => $v["sub_id"], "date" => $v["date"], "country" => $v["location"], "campaign_id" => $v["uuid"]])->find();
                            $insert_data = [
                                "country" => $v["location"],
                                "platform_type" => 1,
                                "campaign_id" => $v["offer_id"],
                                "date" => $v["date"],
                                "campaign_name" => $v["uuid"],
                                "impressions" => $v["impression"],
                                "clicks" => $v["click"],
                                "spend" => $v["spend"],
                                "installs" => $v["install"],
                                "adset_id" => $v["sub_id"],
                                "store_id" => $v["package_name"],
                                "target_id" => $username,
                                "platform" => $v["platform"]
                            ];
                            if (empty($r)) {
                                $insert_data["app_id"] = getappidbycampaign($v["offer_id"], 1);
                                Db::name("adspend_data")->insert($insert_data);
                            } else {
                                Db::name("adspend_data")->where("id", $r["id"])->update($insert_data);
                            }
                        }
                    }
                }
                if ($current_page < $page_count) {
                    ++$page;
                    return $this->selfmobvista($page, $start_time, $end_time);
                }
            }
        }
        return true;
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

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    public function adwordscallback()
    {
        echo "ok";
    }

    public function all_data($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $params["start"] = $start;
        $params["end"] = $end;
        $host = getdomainname();
        $url2 = $host . "/adspend/getSnapchat";
        $url3 = $host . "/adspend/getTikTokReportOther";
        $url4 = $host . "/adspend/getTikTokReport";
        $fb_url = $host . "/adspend/pullFB";
        $asm_url = $host . "/adspend/get_apple_ads";
		$kuaishou_url =$host."/adspend/getKuaishouReport";
		$gdt_url =$host."/adspend/getGtdReport";
        syncRequest($url2, $params);
        syncRequest($url3, $params);
        syncRequest($url4, $params);
        syncRequest($fb_url, $params);
        syncRequest($asm_url, $params);
		syncRequest($kuaishou_url,$params);
		syncRequest($gdt_url,$params);
        exit("ok");
    }

    //adwords report api
    public function adwords($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $params["start"] = $start;
        $params["end"] = $end;
        $host = getdomainname();
        $url3 = $host . "/adspend/newadwordsv3";
        $url4 = $host . "/adspend/newadwordsv4";
        syncRequest($url3, $params);
        sleep(3);
        syncRequest($url4, $params);
        exit("ok");
    }

    public function get_adwords_ads_auto($start = "", $end = "")
    {

        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $host = getdomainname();
        $url = $host . "/adspend/get_aw_data";
        $list = Db::name("advertising_account")->where(["channel" => 3])->select();
        if (!empty($list)) {
            foreach ($list as &$vv) {
                $vv["start"] = $start;
                $vv["end"] = $end;
				sleep(30);
                syncRequest($url,$vv);
            }
        }
        exit("ok");
    }

    public function get_aw_data(Request $request)
    {
        $params = $request->param();
        $advertiser_id = trim($params["advertiser_id"]);
        $path = $_SERVER['DOCUMENT_ROOT'] . "/adsapi/ads_auto/adsapi_php{$advertiser_id}.ini";
        if (!file_exists($path)) {
            $base_path = $_SERVER['DOCUMENT_ROOT'] . "/adsapi/ads_auto/adsapi_php.ini";
            file_put_contents($path, str_replace('#{clientCustomerId}#', $advertiser_id, file_get_contents($base_path)));
        }
        $r = $this->main($params["start"], $params["end"], $path);
        if (!empty($r)) {
            foreach ($r as $v) {
                $spend = $this->moneyInDollars($v["cost"]);
                $country = getgooglecountry($v["location"]);
                $rows = Db::name("adspend_data")->where(["platform_type" => 5, "date" => $v["day"], "campaign_id" => $v["campaignID"], "target_id" => $advertiser_id, "country" => $country])->find();
                if (empty($rows)) {
                    $app_id = getappidbycampaign($v["campaignID"], 5);
                    $arr = array(
                        "campaign_name" => $v["campaign"],
                        "impressions" => $v["impressions"],
                        "spend" => $spend,
                        "clicks" => $v["clicks"],
                        "installs" => intval($v["conversions"]),
                        "date" => $v["day"],
                        "campaign_id" => $v["campaignID"],
                        "platform_type" => 5,
                        "target_id" => $advertiser_id,
                        "app_id" => $app_id,
                        "country" => $country
                    );
                    Db::name("adspend_data")->insert($arr);
                } else {
                    Db::name("adspend_data")->where("id", $rows["id"])->update(["spend" => $spend, "country" => $country, "impressions" => $v["impressions"], "clicks" => $v["clicks"], "installs" => intval($v["conversions"])]);
                }
            }
        }
        exit("ok");
    }

    public function newadwordsv3($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $accounts = [
            //"560-554-6660",
            //"557-425-1539",
            "747-717-6847",
            "188-172-5156",
            "832-000-1213",
            "465-629-8842"
        ];
        $this->getadwordsdata($accounts, $start, $end);
        echo "ok";
    }

    public function newadwordsv4($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $accounts = [
			"260-084-2362"
            //"565-275-7901"
        ];
        $this->getadwordsdata($accounts, $start, $end);
        echo "ok";
    }

    private function getadwordsdata($accounts, $start, $end)
    {
        $dir = $_SERVER['DOCUMENT_ROOT'] . "/adsapi/ads_auto/";
        foreach ($accounts as $vvv) {
            $path = $dir . "adsapi_php" . $vvv . ".ini";
            $r = $this->main($start, $end, $path);
            if (!empty($r)) {
                foreach ($r as $v) {
                    $spend = $this->moneyInDollars($v["cost"]);
                    $country = getgooglecountry($v["location"]);
					//特殊处理
					if($country=='GB')
					{
						$spend = $spend*1.02;
					}
					if($country=='TR' || $country=='AT')
					{
						$spend = $spend*1.05;
					}
                    $rows = Db::name("adspend_data")->where(["platform_type" => 5, "date" => $v["day"], "campaign_id" => $v["campaignID"], "target_id" => $vvv, "country" => $country])->find();
                    if (empty($rows)) {
                        $app_id = getappidbycampaign($v["campaignID"], 5);
                        $arr = array(
                            "campaign_name" => $v["campaign"],
                            "impressions" => $v["impressions"],
                            "spend" => $spend,
                            "clicks" => $v["clicks"],
                            "installs" => intval($v["conversions"]),
                            "date" => $v["day"],
                            "campaign_id" => $v["campaignID"],
                            "platform_type" => 5,
                            "target_id" => $vvv,
                            "app_id" => $app_id,
                            "country" => $country
                        );
                        Db::name("adspend_data")->insert($arr);
                    } else {
                        Db::name("adspend_data")->where("id", $rows["id"])->update(["spend" => $spend, "country" => $country, "impressions" => $v["impressions"], "clicks" => $v["clicks"], "installs" => intval($v["conversions"])]);
                    }
                }
            }
            sleep(2);
        }
        return true;
    }

    public function runExample(AdWordsSession $session, $filePath, $start, $end)
    {
        // Create selector.
        $selector = new Selector();
        $selector->setFields(
            [
                'CampaignId',
                'CampaignName',
                'Impressions',
                'Cost',
                'Clicks',
                'Conversions',
                'Ctr',
                'Date',
                'Id'
            ]
        );
        // Use a predicate to filter out paused criteria (this is optional).
        $selector->setPredicates(
            [
                new Predicate('CampaignStatus', PredicateOperator::IN, ['PAUSED', 'ENABLED'])
            ]
        );

        $selector->setDateRange(new DateRange($start, $end));
        // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'Criteria performance report #' . uniqid()
        );
        $reportDefinition->setDateRangeType(
            ReportDefinitionDateRangeType::CUSTOM_DATE
        );
        //CAMPAIGN_PERFORMANCE_REPORT
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CAMPAIGN_LOCATION_TARGET_REPORT
        );
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);
        // Download report.
        $reportDownloader = new ReportDownloader($session);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings override here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())->includeZeroImpressions(false)->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
            $reportDefinition,
            $reportSettingsOverride
        );
        $reportDownloadResult->saveToFile($filePath);
        /* printf(
            "Report with name '%s' was downloaded to '%s'.\n",
            $reportDefinition->getReportName(),
            $filePath
        ); */
        return true;
    }

    public function main($start, $end, $path = null)
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile($path)->build();
        // See: AdWordsSessionBuilder for setting a client customer ID that is
        // different from that specified in your adsapi_php.ini file.
        // Construct an API session configured from a properties file and the
        // OAuth2 credentials above.
        $session = (new AdWordsSessionBuilder())->fromFile($path)->withOAuth2Credential($oAuth2Credential)->build();

        /* $filePath = sprintf(
            '%s.csv',
            tempnam(sys_get_temp_dir(), 'criteria-report-')
        ); */
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/adwords/report_" . time() . ".xml";
        $this->runExample($session, $filePath, $start, $end);
        header("Content-type: text/html; charset=utf-8");
        $xml = simplexml_load_string(file_get_contents($filePath));
        $data = json_decode(json_encode($xml), TRUE);
        unlink($filePath);
        if (!isset($data["table"]["row"])) {
            return [];
        }

        $res = $this->adowrdsarray($data["table"]["row"]);

        return $res;

    }

    function adowrdsarray($data)
    {

        $out_put = [];
        if (!empty($data)) {
            if (count($data) == 1) {
                $out_put[0] = $data["@attributes"];

            } else {

                foreach ($data as $key => $vvv) {

                    $out_put[$key] = $vvv["@attributes"];
                }
            }
        }
        return $out_put;

    }

    function moneyInDollars($money)
    {
        return round(($money / 1000000.00), 2);
    }

    //请求
    private function googlecurl($url, $data = null, $method = null)
    {
        $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    function curl_post_ssl($url, $orgId, $vars, $second = 30)
    {
        $ch = curl_init();
        //curl_setopt($ch,CURLOPT_VERBOSE,'1');
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, "/var/www/gamebrain_data/public/Certificates/{$orgId}.pem");
        //curl_setopt($ch,CURLOPT_SSLCERTPASSWD,'1234');
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, "/var/www/gamebrain_data/public/Certificates/{$orgId}.key");
        $httpHeader[] = 'Authorization:  orgId=' . $orgId;
        $httpHeader[] = 'Content-Type: application/json';
        $httpHeader[] = 'Content-Length: ' . strlen($vars);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data)
            return json_decode($data, true);
        else
            return false;
    }

    // Apple Search Ads
    public function get_apple_ads($start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        $url = "https://api.searchads.apple.com/api/v3/reports/campaigns";
        $params = array(
            "startTime" => $start,
            "endTime" => $end,
            "selector" => [
                "orderBy" => [["field" => "localSpend", "sortOrder" => "DESCENDING"]],
                "pagination" => ["offset" => 0, "limit" =>800]
            ],
            "timeZone" => "UTC",
            "granularity" => "DAILY",
            "returnRecordsWithNoMetrics" => true,
            "returnRowTotals" => true,
            "returnGrandTotals" => true
        );
		$orgIds =[
		   "1958830","2288840","2311690"
		];
		foreach( $orgIds as $o )
		{
			$this->asm_request($url,$o,$params);
		}
        exit("ok");
    }
	
	private function asm_request($url,$orgId,$params){
		
		$result = $this->curl_post_ssl($url,$orgId, json_encode($params));
        $data = isset($result["data"]["reportingDataResponse"]["row"]) ? $result["data"]["reportingDataResponse"]["row"] : [];
        if (!empty($data)) {
            foreach ($data as $v) {
                $row = [
                    "country" => strtoupper($v["metadata"]["countriesOrRegions"][0]),
                    "platform_type" => 39,
                    "campaign_id" => $v["metadata"]["campaignId"],
                    "date" => $params['startTime'],
                    "campaign_name" => $v["metadata"]["campaignName"],
                    "impressions" => $v["total"]["impressions"],
                    "clicks" => $v["total"]["taps"],
                    "spend" => $v["total"]["localSpend"]["amount"],
                    "installs" => $v["total"]["installs"]
                ];
                $r = Db::name("adspend_data")->where(["platform_type" => $row["platform_type"], "date" => $row["date"], "country" => $row["country"], "campaign_id" => $row["campaign_id"]])->find();
                if (empty($r)) {
                    $row["app_id"] = getappidbycampaign($row["campaign_id"], $row["platform_type"]);
                    Db::name("adspend_data")->insert($row);
                } else {
                    Db::name("adspend_data")->where("id", $r["id"])->update($row);
                }
            }
        }
		return;
	}


    function request_by_curl($remote_server, $post_string)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_server);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
	
	private function send_mail($out){
		$emailList =["tangwenjuan@hellowd.net","guilijun@hellowd.net","xiongaodi@hellowd.net"];
		$title ="【GB提醒】推广花费存在超出";
		$body ="<p style='color:red;'>以下产品渠道可能存在推广数据未暂停，请负责人去相关渠道后台进行查看，确认是否推广已暂停</p><table style='margin-bottom:0px;'><thead><tr>	<th style='color:#333;'>产品名称</th><th style='color:#333;'>渠道</th><th style='color:#333;'>账户</th>	<th style='color:#333;'>花费</th></tr></thead><tbody>";
        foreach($out as $nv)
		{
			$body .="<tr><td>{$nv['app_name']}</td><td>{$nv['channel']}</td><td>{$nv['account_id']}</td><td>{$nv['spend']}</td></tr>";
		}		
		$body .="</tbody></table>";
		foreach(  $emailList as $vv )
		{
			send_mail( $vv,$vv,$title,$body,"GameBrain" );
		}
	}

    function testaaa()
    {
        $webhook = "https://oapi.dingtalk.com/robot/send?access_token=0faf93d5cec8ebf9e0f261d5d160f2120ab8aebc9d54ca6f12ea4345f3656025";
        $message = "【收益提醒】：这个月的汇率还没有填写哦，请及时填写";
        $data = array('msgtype' => 'text', 'text' => array('content' => $message), "at" => ["atMobiles" => ["15027075696"], "isAtAll" => false]);
        $data_string = json_encode($data);
        //$result = $this->request_by_curl($webhook, $data_string);
        //echo $result;
    }


}
 