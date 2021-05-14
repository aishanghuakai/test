<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;

  //facebook 请求类
class Facebookrequest
{
   
	//永久token 
	//const access_token = "EAAFZCeeh7ZBZAgBAPX1psbmpDLt9EZB2rZAvsowd4Fvh8fSXBy0NyjrOZC7e4g3wiZBiTNnjtOIHBJrDcJhQRkPxjaHuxYFPuTncZBToe67GoAZBqIK7FnDEcTZBiS4Mp5wk9MPy3ade0WZBIcZBEBp6qtdS9mPRrFoitbnrysZBhZA4AcVqfx4sByI3ndRyJMSSaqMNYZD";   
	
	//const access_token ="EAAQB3ql2XpIBAJ5p9UrUonZBgUPYedffHA3Yrl1aHdncQdLCBr9BZB1XeXAZCEqEENLRD3MXJXAbaoGzr3lmpW852wVZAqqp2w40CTEQm5ZCn7C8sDxWj5uZBB0kDPAYEHbNoFLrpO4JbwZCrA6Lx9Mj1E1UX4QOuZBZBZCwxeluvJRYidEGdhZCqHt";
	
	const access_token ="EAAI1gxRce9QBALygc1RmF5ZArsHfkTmbgEDSoXs8WaizhBYBsQAmHIguBu5fZAw4sG5NWHZCXVhvU0zPJwqMrsUQtkAcs6YAL3m6yhHTOJenGqw2OZCfvihIAUmLG2xF8zQ2sUh0yga4EyAqnaFahlvReUpvLeGWYLFGGTPVD2M3ZCZAZAyhgH2";
	
	
	//查询字段
	private $fields =["fb_ad_network_revenue","fb_ad_network_request","fb_ad_network_cpm","fb_ad_network_click","fb_ad_network_imp","fb_ad_network_filled_request","fb_ad_network_fill_rate"];
	
	//映射字段
	private $db_field =array(
	           
			   "fb_ad_network_revenue"=>"revenue",
			   "fb_ad_network_request"=>"request",
			   "fb_ad_network_cpm"=>"ecpm",
			   "fb_ad_network_click"=>"click",
			   "fb_ad_network_imp"=>"impression",
			   "fb_ad_network_filled_request"=>"filled",
			   "fb_ad_network_fill_rate"=>"fill_rate"
			   
	);
	private function getapp()
	{
		$applist =array(
		     
			 "Tankr(Android)"=>[ "property_id"=>"131778474181158","app_id"=>"145198636252217","platform"=>"android" ]
		);
		return $applist;
	}
	//调试
	public function test()
	{
		$key="995528667313289";
		$url ="https://graph.facebook.com/v2.11/{$key}/adnetworkanalytics/";
		
		//$url.="&applicationId={$appid}";
		 $paras['access_token'] = self::access_token;
	     $paras['metrics'] ="['fb_ad_network_revenue']";
		
		 $paras['since'] ="2019-04-01";
		 $paras["until"] ="2019-04-02";
		 //$paras['filters']="[{'field':'platform', 'operator':'in', 'values':['{$filter}']}]";
		
		 $content = http_build_query($paras);
         $url = $url."?".$content;
		 $data = $this->curl($url);
		 print_r($data);exit;
	}
	//收益
	public function request($start,$end)
	{
		$apps = $this->getapp();
		foreach( $apps as $kk=>$vv )
		{
			foreach( $this->fields as $vvv )
			{
				$r = $this->adrequest($kk,$vv["property_id"],$vv["app_id"],$vvv,$vv["platform"],$start,$end);
				sleep(5);continue;
			}
		}
        return true;		
	}
	
	//单独某个产品的收益
	public function onerequest($app)
	{		
		foreach( $this->fields as $vvv )
		{
			$r = $this->adrequest($app["name"],$app["property_id"],$app["app_id"],$vvv,$app["platform"],$app["start"],$app["end"]);
		}
        return true;
	}
	//手动添加
	public function testonerequest($start,$end)
	{
		$apps = array(		  			 			
			"Idle Fish Tycoon-ios"=>["property_id"=>"587488605084570","app_id"=>"245305093058730","platform"=>"ios"],
			"Tank Shooting - Survival Battle-ios"=>["property_id"=>"391852528071950","app_id"=>"2317528021852797","platform"=>"ios"],
		);
		foreach( $apps as $kk=>$vv )
		{
			foreach( $this->fields as $vvv )
			{
				$r = $this->adrequest($kk,$vv["property_id"],$vv["app_id"],$vvv,$vv["platform"],$start,$end);
			}
		}
        return true;
	}
	
	//
	public function apprequest($apps,$start,$end)
	{
		if(empty($apps))return;
		foreach( $apps as $kk=>$vv )
		{
			foreach( $this->fields as $vvv )
			{
				$r = $this->adrequest($kk,$vv["property_id"],$vv["app_id"],$vvv,$vv["platform"],$start,$end);
			}
		}
        return true;
	}
	
	//获取推广token
	private function getaccess_token()
	{
		return "EAADQrlS8Xb0BAOGa7ZBrwCmdt8awlAZBjddK8hFShw8DF7ihidh6SHheIQjfEY9RwpJCN4cC9Vgsb5tZAuxFeJIoQ4duywQbUjLIMFaVXS1X2QnsZAyBqcYU3M0SVMZCn8CWq2RMimLKb3y4e8ZCsH25t5NLCYw90LLy8gZAvrW1xZAguw3mEcYxUXjIj2ddjrMZD";
	    //$access_token ="EAADQrlS8Xb0BAFzNaSKW5aZBrZB0VI198teq8bRAWe7ZAlFPysNUgABZCeSs45nEZC7DHqsvskD9YgfVk29cVULeCveuStOq4X3QZBy9ya4iR7pZCIyv7BUV8jXcswOnqp6MZCJyyvXggQemGaJ6gcUZAnq2aVzEFbbWbrnGPUoDP13Bk759tVeAtO6WsQsOkZB062PJTJxu3VTwZDZD";
		//return $access_token;
	}
	
	//获取推广账号
	private function getaccount()
	{
		   $accounts =[
		            "822457261283345",
		            "1259551744177301",
					"1946121135400380",
					"1253402368126583",
					"211463422998856",
					"393751594444117",
					"1049046465277178",
					"990865171086809",
					"895209157336560",
				    "1041951502653341",
				    "1041951505986674",
					"1818312378276513",
                    "237712117013468",
					"2132383423675293",
                    "2012596678804283",
					"1000509606797531",
                    "1000509610130864",
					"2067484693518507",
                    "232752934165234",
					"686334028413592",
					"230936887569450",					
					"225643964677228",
					"2131792750428133",
					"1966170656829266",
					"233957027447560",
					"227249291320267",					                    
                    "2067484693518507",
					"263106164233532",
                    "965287146964194",
					"253280598786649",
                    "260990564701917",					
                    "218818365420468",
					"485038798617210"							
					];
		return $accounts;
	}
	
	public function ads_promote($start,$end)
	{
		$accounts =["1063124077448514"]; //$this->getaccount();
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,campaign_id,campaign_name,impressions,clicks,spend,ctr,actions,attribution_setting,estimated_ad_recallers";
		$params["level"] ="campaign";
		//$params['breakdowns']='country';
		$params['use_unified_attribution_setting'] = true;
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);
		foreach($accounts as $vv )
		{
			 $url = "https://graph.facebook.com/v9.0/act_{$vv}/insights/";
			 $url = $url."?".$content;
			 echo $url;exit;
			 $this->ads_inset_data($url);
		}
      return true; 		
	}
	
	//分推广版位拉取
	public function platform_report($accounts,$start,$end,$app_id=""){
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,unique_actions";
		$params["level"] ="ad";
		$params['breakdowns']='publisher_platform,platform_position';
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);
		foreach($accounts as $vv )
		{
			 $url = "https://graph.facebook.com/v9.0/act_{$vv}/insights/";
			 $url = $url."?".$content;
			 $this->platform_ads_inset_data($url);
		}
	}
	//新加
	private function platform_ads_inset_data($url,$app_id="")
	{
	    
		 $result = $this->curl($url);
		 if( isset($result["data"]) && !empty($result["data"]) )
		 {
			 $res = $result["data"];
			 foreach($res as &$vvv)
			 {
				 $vvv["installs"] = isset( $vvv["unique_actions"] )?$this->getapp_install($vvv["unique_actions"]):0;				
				 $vvv["date"] = $vvv["date_start"];
				 $vvv["advertiser_id"] = $vvv["account_id"];
				 unset( $vvv["date_start"],$vvv["unique_actions"],$vvv["date_stop"],$vvv["account_id"] );
				 $r = Db::name("platform_report")->where( ["publisher_platform"=>$vvv["publisher_platform"],"platform_position"=>$vvv["platform_position"],"date"=>$vvv["date"],"ad_id"=>$vvv["ad_id"],"advertiser_id"=>$vvv["advertiser_id"] ] )->find();
				  if( empty($r) )
				  {
					$vvv["app_id"] =$app_id?$app_id:getappidbycampaign($vvv["campaign_id"],6);
					Db::name("platform_report")->insert($vvv); 
				  }else{				
					Db::name("platform_report")->where( "id",$r["id"])->update( $vvv );  
				  }
			 }
			  if( isset( $result["paging"] ) && isset( $result["paging"]['next']  ) && $result["paging"]['next']!='' )
			 {
				return $this->platform_ads_inset_data($result["paging"]['next']);
			 } 
		 }
		 return true;
	}
	
	
	
	//AAA视频 数据
	public function aaa_report($accounts,$start,$end,$app_id="")
	{
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,unique_actions";
		$params["level"] ="ad";
		$params['breakdowns']='video_asset';
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);
		foreach($accounts as $vv )
		{
			 $url = "https://graph.facebook.com/v9.0/act_{$vv}/insights/";
			 $url = $url."?".$content;
			 $this->aaa_ads_inset_data($url,$app_id);
		}
	}
	
	private function aaa_ads_inset_data($url,$app_id="")
	{
	    
		 $result = $this->curl($url);
		 if( isset($result["data"]) && !empty($result["data"]) )
		 {
			 $res = $result["data"];
			 foreach($res as &$vvv)
			 {
				 $vvv["installs"] = isset( $vvv["unique_actions"] )?$this->getapp_install($vvv["unique_actions"]):0;				
				 $vvv["date"] = $vvv["date_start"];
				 $vvv["advertiser_id"] = $vvv["account_id"];
				 $video_asset = $vvv["video_asset"];
				 $vvv["video_id"] = $video_asset["video_id"];
				 $vvv["fb_id"] = $video_asset["id"];
				 $vvv["video_name"] = $video_asset["video_name"];
				 $vvv["video_url"] = $video_asset["url"];
				 unset( $vvv["date_start"],$vvv["unique_actions"],$vvv["date_stop"],$vvv["account_id"],$vvv["video_asset"] );
				 $r = Db::name("aaa_report")->where( ["video_id"=>$video_asset["video_id"],"fb_id"=>$video_asset["id"],"date"=>$vvv["date"],"ad_id"=>$vvv["ad_id"],"advertiser_id"=>$vvv["advertiser_id"] ] )->find();
				  if( empty($r) )
				  {
					$vvv["app_id"] =$app_id?$app_id:getappidbycampaign($vvv["campaign_id"],6);
					Db::name("aaa_report")->insert($vvv); 
				  }else{				
					Db::name("aaa_report")->where( "id",$r["id"])->update( $vvv );  
				  }
			 }
			  if( isset( $result["paging"] ) && isset( $result["paging"]['next']  ) && $result["paging"]['next']!='' )
			 {
				return $this->aaa_ads_inset_data($result["paging"]['next']);
			 } 
		 }
		 return true;
	}
	public function new_ads_promote($accounts,$start,$end,$app_id="")
	{
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,ctr,unique_actions";
		$params["level"] ="ad";
		$params['breakdowns']='country';
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);
		foreach($accounts as $vv )
		{
			 $url = "https://graph.facebook.com/v9.0/act_{$vv}/insights/";
			 $url = $url."?".$content;
			 $this->ads_inset_data($url,$app_id); 
		}
      return true; 
	}
	
	public function get_hour_spend($accounts,$start,$end)
	{
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,spend";
		$params["level"] ="account";
		$params['breakdowns']='hourly_stats_aggregated_by_advertiser_time_zone';
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);		
		 $url = "https://graph.facebook.com/v9.0/act_{$accounts}/insights/";
		 $url = $url."?".$content;
		 $result = $this->curl($url);
		 return $result;

      return true; 
	}
	
	public function platform_promote($accounts,$start,$end)
	{
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,impressions,clicks,spend,ctr,unique_actions";
		$params["level"] ="account";
		$params['breakdowns']='country';
		$params['action_report_time']='impression';
		$params['time_range']=["since"=>$start,"until"=>$end ];
		$content = http_build_query($params);
		foreach($accounts as $vv )
		{
			 $url = "https://graph.facebook.com/v9.0/act_{$vv['id']}/insights/";
			 $url = $url."?".$content;
			 $result = $this->curl($url);
			 if( isset($result["data"]) && !empty($result["data"]) )
			 {
				 $res = $result["data"];
				 foreach($res as &$vvv)
				 {
					 $row =[];
					 $row["installs"] = isset( $vvv["unique_actions"] )?$this->getapp_install($vvv["unique_actions"]):0;
					 $row["date"] = $vvv["date_start"];
					 $row["platform"] =$vv["platform"];
					 $row["app_id"] =$vv["app_id"];
					 $row["country"] = $vvv["country"];
					 $row["spend"] = $vvv["spend"];
					 $r = Db::name("control_data")->where($row)->find();
					  if( empty($r) )
					  {
						Db::name("control_data")->insert($row); 
					  }else{				
						Db::name("control_data")->where( "id",$r["id"])->update( $row );  
					  }
				 }
			 }
		}
      return true; 
	}
	
	
	private function ads_inset_data($url,$app_id="")
	{
	    
		 $result = $this->curl($url);
		 if( isset($result["data"]) && !empty($result["data"]) )
		 {
			 $res = $result["data"];
			 foreach($res as &$vvv)
			 {
				 $vvv["installs"] = isset( $vvv["unique_actions"] )?$this->getapp_install($vvv["unique_actions"]):0;
				 $vvv["video_view"] = isset( $vvv["unique_actions"] )?$this->getvideo_view($vvv["unique_actions"]):0;
				 //$vvv["video_percent"] = isset( $vvv["video_avg_percent_watched_unique_actions"] )?$this->getvideo_view($vvv["video_avg_percent_watched_unique_actions"]):0;
				 $vvv["date"] = $vvv["date_start"];
				 $vvv["platform_type"] =6;
				 $vvv["target_id"] = $vvv["account_id"];
				 unset( $vvv["date_start"],$vvv["unique_actions"],$vvv["date_stop"],$vvv["account_id"],$vvv["video_avg_percent_watched_unique_actions"] );
				 $r = Db::name("adspend_data")->where( ["platform_type"=>6,"date"=>$vvv["date"],"country"=>$vvv["country"],"ad_id"=>$vvv["ad_id"],"target_id"=>$vvv["target_id"] ] )->find();
				  if( empty($r) )
				  {
					$vvv["app_id"] =$app_id?$app_id:getappidbycampaign($vvv["campaign_id"],$vvv["platform_type"]);
					Db::name("adspend_data")->insert($vvv); 
				  }else{				
					Db::name("adspend_data")->where( "id",$r["id"])->update( $vvv );  
				  }
			 }
			  if( isset( $result["paging"] ) && isset( $result["paging"]['next']  ) && $result["paging"]['next']!='' )
			 {
				return $this->ads_inset_data($result["paging"]['next'],$app_id);
			 } 
		 }
		 return true;
	}
	
	private function getapp_install($array)
	{
		if( !empty($array) )
		{
			foreach( $array as $a_v )
			{
				if( isset($a_v["action_type"] ) && ($a_v["action_type"]=="mobile_app_install") )
				{
					return $a_v["value"];
				}
			}
		}
		return 0;
	}
	//获取视频观看数
	private function getvideo_view($array)
	{
		if( !empty($array) )
		{
			foreach( $array as $a_v )
			{
				if( isset($a_v["action_type"] ) && ($a_v["action_type"]=="video_view") )
				{
					return $a_v["value"];
				}
			}
		}
		return 0;
	}
	
	//推广
	public function ads_test()
	{
		//822457261283345
        //1259551744177301
		//1946121135400380
		//1253402368126583 empty
		//211463422998856
		//393751594444117
		
		//1049046465277178
        //990865171086809
        //895209157336560
       //1041951502653341
       //1041951505986674


		$url = "https://graph.facebook.com/v3.1/act_2164736540466902/insights/";
		$params=[];
		$params["access_token"]=$this->getaccess_token();
		$params["fields"]="account_id,ad_id,ad_name,adset_id,adset_name,campaign_id,campaign_name,impressions,clicks,spend,cpc,cpm,ctr";
		$params["level"] ="ad";
		$params['breakdowns']='description_asset';
		$params['action_report_time']='impression';
		//$params['action_breakdowns']='action_link_click_destination';
		//$params['summary'] = 'spend,impressions';
		$params['time_range']=["since"=>"2018-10-31","until"=>"2018-10-31"];
		$content = http_build_query($params);
        $url = $url."?".$content;
		$data = $this->curl($url);
		print_r($data);
	}
	protected function adrequest($name,$key,$facebook_id,$field,$filter,$start,$end)
	{
		$url ="https://graph.facebook.com/v3.3/{$key}/adnetworkanalytics/";
		
		//$url.="&applicationId={$appid}";
		 $paras['access_token'] = self::access_token;
	     $paras['metrics'] ="['{$field}']";
		 $paras['breakdowns']=['country','placement_name'];
		 $paras['since'] = $start;
		 $paras["until"] = $end;
		 $paras['filters']="[{'field':'platform', 'operator':'in', 'values':['{$filter}']}]";
		
		 $content = http_build_query($paras);
         $url = $url."?".$content;
		 $data = $this->curl($url);
		 if( isset($data["data"] ) && !empty( $data["data"] ) )
		 {
			 $r =$data["data"][0]["results"];
			 if( !empty($r) )
			 {
				foreach( $r as $v )
				 {
					 $app_id = md5( $facebook_id.$filter );
					 $time = date("Y-m-d",strtotime( $v["time"] ) );
					 $metric =$this->db_field[$v["metric"]];
					 $country = $v["breakdowns"][0]["value"];
					 $unit_id = $facebook_id."_".$v["breakdowns"][1]["value"];
					 $ad_hash = $v["breakdowns"][2]["value"];
					 $adtype ="no";
					 if( preg_match("/INT/",$ad_hash ) )
					{
						$adtype="int";
					}elseif( preg_match("/RV/",$ad_hash ) )
					{
						$adtype="rew";
					}elseif( preg_match("/ban/i",$ad_hash ) )
					{
						$adtype="ban";
					}
					$value = $v["value"];
					$app_name =$name; 
					$data = [  $metric=>$value ];
					$this->insertfacebook( $time,$country,$app_id,$app_name,$data,$unit_id,$ad_hash,$adtype,$filter );
				 } 
			 }
		 }
	}
	
	//插入数据库
	private function insertfacebook( $time,$country,$app_id,$app_name,$data,$unit_id,$ad_hash,$adtype,$filter )
	{
		$t = Db::name("adcash_data")->where( ["date"=>$time,"country"=>$country,"app_id"=>$app_id,"platform"=>6,"unit_id"=>$unit_id ] )->find();
		if( !empty($t) )
		{
			$data["adtype"] = $adtype;
			$data["ad_hash"] = $ad_hash;
			Db::name("adcash_data")->where( ["id"=>$t["id"] ] )->update($data);
			
		}else{
			$data["date"] = $time;
			$data["country"] = $country;
			$data["app_id"] = $app_id;
            $data["app_name"] = $app_name;
            $data["platform"]=6;
			$data["unit_id"] =$unit_id;
            $data["app_platform"] =$filter; 
			$data["adtype"] = $adtype;
			$data["ad_hash"] = $ad_hash;
            //$data["adtype"] = //getadtype("6",$data["unit_id"] );
            $data["sys_app_id"] = getappidbycampaign($app_id,$data["platform"],1);			
			Db::name("adcash_data")->insert( $data );
		}
	}
	
   private function curl($action,$headers=[]) {
	   header("Content-type: text/html; charset=utf-8");
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
        return json_decode($ret,true);
    }
	
	
	function request_post($url = '', $post_data = array()) {
		
        if (empty($url) || empty($post_data)) {
            return false;
        }        
        $post_data = http_build_query($post_data);

        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        
        return json_decode($data,true);
    }
}
