<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use app\admin\controller\Index as E;
class Appsflyer extends Base
{
    //推广渠道
	private $promate_media =array(
	   ["name"=>"全部媒体","value"=>"all","channel"=>"all"],
	   ["name"=>"Mintegral","value"=>"mintegral_int","channel"=>"1"],
	   ["name"=>"头条","value"=>"ocean engine_int","channel"=>"32"],
	   ["name"=>"Facebook","value"=>"Facebook Ads","channel"=>"6"],
	   ["name"=>"Unityads","value"=>"unityads_int","channel"=>"2"],
	   ["name"=>"Applovin","value"=>"applovin_int","channel"=>"3"],
	   ["name"=>"ironSource","value"=>"ironsource_int","channel"=>"7"],
       ["name"=>"Vungle","value"=>"vungle_int","channel"=>"4"],
	   ['name'=>'Adwords',"value"=>"googleadwords_int","channel"=>"5"],
	   ['name'=>'Tapjoy',"value"=>"tapjoy_int","channel"=>"9"],
	   ['name'=>'Chartboost',"value"=>"chartboosts2s_int","channel"=>"8"],
	   ['name'=>'Tiktok',"value"=>"tiktok_int","channel"=>"36"],
	   ['name'=>'Adcolony',"value"=>"Adcolony_int","channel"=>"31"],
	   ['name'=>'Snapchat',"value"=>"Snapchat Installs","channel"=>["346","347"]],
	   ['name'=>'Organic',"value"=>"Organic","channel"=>"0"],
	   
	);
	
	//广告渠道
	private $network_media =array(
	  ["name"=>"全部媒体","value"=>"all","channel"=>"all"],
	  ["name"=>"Admob","value"=>"Admob","channel"=>"5"],
	  ["name"=>"Sigmob","value"=>"Sigmob","channel"=>"34"],
	  ["name"=>"IronSource","value"=>"IronSource","channel"=>"7"],
	  ["name"=>"GDT","value"=>"GDT","channel"=>"35"],
	  ["name"=>"Vungle","value"=>"Vungle","channel"=>"4"],
	  ["name"=>"UnityAds","value"=>"UnityAds","channel"=>"2"],
	  ["name"=>"Facebook","value"=>"Facebook","channel"=>"6"],
	  ["name"=>"穿山甲","value"=>"CSJ","channel"=>"32"],
	  ["name"=>"MoPub","value"=>"MoPub","channel"=>"37"]
	);
	
	private $device =array(
	   ["name"=>"全部设备","value"=>"all"],
	   ["name"=>"iPad","value"=>"iPad"],
	   ["name"=>"iPhone","value"=>"iPhone"],
	   ["name"=>"Android","value"=>"Android"],
	   ["name"=>"iPod touch","value"=>"iPod touch"]
	);
	
	private $ads_date =array(
	   "68"=>"2020-01-15",
	   "132"=>"2020-02-12",
	   "143"=>"2020-02-20",
	   "147"=>"2020-03-19"
	);
	
	public function index($appid="",$by="adjust")
    {      	  
	    
		if( $appid=="" )
		{
		   $appid = getcache("select_app");
		}
		
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
	   $start_ads =isset($this->ads_date[$appid])?"从{$this->ads_date[$appid]}开始推广":"暂无LTV数据";
	   setcache("select_app",$appid);
	   $start = date("Y-m-d",strtotime("-4 day"));
	   $end = date("Y-m-d",strtotime("-3 day"));
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	   $this->assign("country",admincountry());
	   $this->assign("campaignList",$this->getCampaignList($appid));
	   $this->assign("promate_media",$this->promate_media);
	   $this->assign("network_media",$this->network_media);
	   $this->assign("deviceList",$this->device);
	   $this->assign("start_ads",$start_ads);
	   return $this->fetch($by);
    }
	
	public function data($appid="")
	{
	   if( $appid=="" )
		{
		   $appid = getcache("select_app");
		}
		
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
		 setcache("select_app",$appid);
	  $start = date("Y-m-d",strtotime("-4 day"));
	   $end = date("Y-m-d",strtotime("-3 day"));
	   $this->assign("country",admincountry());
	   $this->assign("promate_media",$this->promate_media);
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	  return $this->fetch("data");
	}
	
	private function getCampaignList($appid)
	{
		//$list= Db::query("select campaign_id as value,campaign_name as name from hellowd_appsflyer where gb_id={$appid} group by campaign_id");
		return [];$list;
	}
	
	private function getsepend($app_id,$start,$end,$country,$channel)
	{
		if(is_array($channel))
		{
			$platform = implode(",",$channel);
			return $this->getcontroltotal($app_id,$start,$end,$country,$platform);
		}else{
			$out_data = ["installs"=>"0","spend"=>"0.00"];
			$where="app_id={$app_id} and  date>='{$start}' and date<='{$end}'";
			if( $country!="all" )
			{
				$where.=" and country='{$country}'";
			}
			if( $channel!="all" )
			{
				$where.=" and platform_type='{$channel}'";
			}
			$row= Db::name("adspend_data")->field('sum(installs) as installs,sum(spend) as spend')->where($where)->find();
			if( !empty($row) )
			{
				$out_data = ["installs"=>(int)$row["installs"],"spend"=>round($row["spend"],2)];
			}
			if($channel=="all")
			{
				$control_data = $this->getcontroltotal($app_id,$start,$end,$country,"all");
				$out_data["installs"]+=$control_data["installs"];
				$out_data["spend"]+=$control_data["spend"];
			}
			return $out_data;
		}	
	}
	
	//获取手动添加的数据
	private function getcontroltotal($appid,$start="",$end="",$country="all",$platform="all")
	{
		$spend="0.00";
		$installs =0;
		$cpi="0.00";
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";		
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if($platform!="all")
		{
			$where.=" and platform in({$platform})";
		}
		$control_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where}";
		
		$d= Db::query($control_sql);
		if( !empty($d) )
		 {
			 $d = $d[0];
			 $spend = $d["spend"]?$d["spend"]:"0.0";
			 $installs = $d["installs"]?$d["installs"]:0;
			 $cpi = $installs<=0?"0.0":round($spend/$installs,2); 
		 }
		 return ["spend"=>$spend,"installs"=>$installs ];
	}
	
	private function getEcpm($app_id,$start,$end,$country,$channel,$adtype)
	{
		$where="sys_app_id={$app_id} and  date>='{$start}' and date<='{$end}' and adtype='{$adtype}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.=" and platform='{$channel}'";
		}
		
		$row= Db::name("adcash_data")->field('sum(impression) as impression,round(sum(revenue),2) as revenue')->where($where)->find();
		if( !empty($row) )
		{
			$ecpm = $row["impression"]>0?round($row["revenue"]/$row["impression"],4):0;
			return $ecpm;
		}
		return 0;
	}
	
	private function getchannel($arr,$value)
	{
		foreach($arr as $v)
		{
			if( $v["value"] ==$value )
			{
				return $v["channel"];
			}
		}
		return "0";
	}
	
	private function getNewDeviceUsers($gb_id,$device_category,$country,$media_source,$start,$end)
	{
		$out =[
		   "spend"=>"0.00",
		   "installs"=>0
		];
		$where ="gb_id={$gb_id} and device_category='{$device_category}' and install_date>='{$start}' and install_date<='{$end}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if( $media_source!="all" )
		{
			$where.=" and networks='{$media_source}'";
		}
		$row = Db::name("adjust_device")->field('sum(spend) as spend,sum(installs) as installs')->where($where)->find();

		if(!empty($row))
		{
			$out = ["spend"=>$row["spend"]?$row["spend"]:"0.00","installs"=>$row["installs"]?$row["installs"]:0];
		}
		return $out;
	}
	
	public function json_data_range($app_id="",$date=[],$country="all",$day="",$media_source="all",$ad_source="all",$campaign_id="",$device="all",$table="appsflyer")
	{
		$out = [];
		if(empty($date))
		{
			$start = date("Y-m-d",strtotime("-15 day"));
			$end = date("Y-m-d",strtotime("-10 day"));
		}else{
			list($start,$end) = $date;
		}
	    $out = $this->get_new_ltv_data($app_id,$start,$end,$country,$day,$media_source,$ad_source,$campaign_id,$device,$table);
		echo json_encode($out);exit;
	}
	
	public function get_byday_ltv($app_id="",$start,$end,$country="all",$day="",$spend)
	{
		$out_data =[];
		$total_revenue ="0.00";
		for ($i=0; $i<=$day; $i++) {
			$r = $this->get_one_byday_ltv($app_id,$spend,$start,$end,$i,$country,"all","all","","all","adjust");
			$total_revenue+=$r["total_revenue"];
			$r["total_roi"] = $spend["spend"]>0?round($total_revenue*100/$spend["spend"],2):0;
			$r["total_avg_revenue"] = $spend["installs"]>0?round($total_revenue/$spend["installs"],3):0;
			$out_data[] = $r;
		}
		$out =array(
		   "roi_info"=>end($out_data),
		);
		return $out;
	}
	
	private function get_one_byday_ltv($app_id,$spend,$start,$end,$num,$country="all",$media_source="all",$ad_source="all",$campaign_id="",$device="all",$table)
	{
		$dates = getDateFromRange($start, $end);
		$res =array(
		   "total_revenue"=>"0.00",
		);
		foreach( $dates as $key=>$v )
		{
			$time = date("Y-m-d",(strtotime($v)+$num*24*3600));			
			$revenue_info =$this->getRevenue($app_id,$v,$time,$time,$country,$media_source,$ad_source,$campaign_id,$device,$table);			
			$res["total_revenue"]+=$revenue_info["total_revenue"];
		}
		return $res;
	}
	
	public function get_new_ltv_data($app_id="",$start,$end,$country="all",$day="",$media_source="all",$ad_source="all",$campaign_id="",$device="all",$table,$is_group_device=false)
	{
		$media_channel =$this->getchannel($this->promate_media,$media_source);
		if($is_group_device)
		{
	       $device1=$device;
			if($device1=="iPhone")
			{
				$device1 = "phone";
			}elseif($device1=="iPod touch")
			{
				$device1 = "ipod";
			}
			$spend = $this->getNewDeviceUsers($app_id,$device1,$country,$media_source,$start,$end);
			$spend["spend"] = round($spend["spend"],2);
		}else{
			$spend = $this->getsepend($app_id,$start,$end,$country,$media_channel);
		}		
		$cpi = $spend["installs"]>0?round($spend["spend"]/$spend["installs"],2):"0.00";		
		$out_data =[];
		$Reward=0;
		$Inter=0;
		$total_revenue ="0.00";
		for ($i=0; $i<=$day; $i++) {
			$r = $this->get_one_day_ltv($app_id,$spend,$start,$end,$i,$country,$media_source,$ad_source,$campaign_id,$device,$table);
			$total_revenue+=$r["total_revenue"];
			$r["index"] ="LTV".$i;
			$r["total_total_revenue"] = $total_revenue;
			$r["total_roi"] = $spend["spend"]>0?round($total_revenue*100/$spend["spend"],2):0;
			$r["total_avg_revenue"] = $spend["installs"]>0?round($total_revenue/$spend["installs"],3):0;
			$r["rate"] = $spend["installs"]>0?round($r["num"]*100/$spend["installs"],2)."%":"0";
            $Reward+=$r["Reward"];
			$Inter+=$r["Inter"];
			$out_data[] = $r;
		}
		$impression=0;
		$num=0;
      	$out =array(
		   "tablist"=>$out_data,
		   "cpi"=>$cpi,
		   "promote_info"=>$spend,
		   "user_value"=>["num"=>$num,"impression"=>$impression],
		   "roi_info"=>end($out_data),
		);
		$avg_days =1; //$day+1;
		$out["rate"] = $out["promote_info"]["installs"]>0?round($out["user_value"]["num"]*100/$out["promote_info"]["installs"],2)."%":"0";
		$out["avg_revenue"] = $spend["installs"]>0?round($out["roi_info"]["total_total_revenue"]/$spend["installs"],3):"0";		
		$out["avgReward"] = $spend["installs"]>0?round($Reward/($spend["installs"]*$avg_days),2):"0";	
		$out["avgInter"] = $spend["installs"]>0?round($Inter/($spend["installs"]*$avg_days),2):"0";
		return $out;
	}
	
	public function json_data_dimension($app_id="",$dimension="country",$date=[],$country="all",$day="",$media_source="all",$ad_source="all",$campaign_id="",$device="all",$is_download=false,$table='appsflyer')
	{
		if(empty($date))
		{
			$start = date("Y-m-d",strtotime("-4 day"));
			$end = date("Y-m-d",strtotime("-3 day"));
		}else{
			if( is_array($date) )
			{
				list($start,$end) = $date;
			}else{
				list($start,$end) = explode(",",$date);
			}			
		}
		$out =[];
		if( $dimension=="country" )
		{
			$nameList = admincountry();
			$media_channel =$this->getchannel($this->promate_media,$media_source);
			foreach($nameList as $k=>$n)
			{
				//$spend = $this->getsepend($app_id,$start,$end,$k,$media_channel);
				if($k!="all")
				{
					$row = $this->get_new_ltv_data($app_id,$start,$end,$k,$day,$media_source,$ad_source,$campaign_id,$device,$table);
					$row["tablist"] = end($row["tablist"]);
					$row["name"] = $n;
					$out[] = $row;
				}				
			}
		}elseif($dimension =="media_source")
		{
			$nameList = $this->promate_media;
			
			foreach($nameList as $k=>$n)
			{
				//$spend = $this->getsepend($app_id,$start,$end,$country,$n["channel"]);
				if( $n["value"]!="all")
				{
					$row = $this->get_new_ltv_data($app_id,$start,$end,$country,$day,$n["value"],$ad_source,$campaign_id,$device,$table);
					$row["name"] = $n["name"];
					$row["tablist"] = end($row["tablist"]);
					$out[] = $row;
				}				
			}
		}elseif($dimension=="device_category")
		{
			$nameList = $this->device;
			
			foreach($nameList as $k=>$n)
			{
				//$spend = $this->getsepend($app_id,$start,$end,$country,$n["channel"]);
				if( $n["value"]!="all")
				{
					$row = $this->get_new_ltv_data($app_id,$start,$end,$country,$day,$media_source,$ad_source,$campaign_id,$n["value"],$table,true);
					$row["name"] = $n["name"];
					$row["tablist"] = end($row["tablist"]);
					$out[] = $row;
				}				
			}
		}elseif($dimension=="day")
		{
			$dates = getDateFromRange($start, $end);
			foreach($dates as $v)
			{
				$row = $this->get_new_ltv_data($app_id,$v,$v,$country,$day,$media_source,$ad_source,$campaign_id,$device,$table);
				$row["name"] = $v;
				$row["tablist"] = end($row["tablist"]);
				$out[] = $row;
			}
		}
		if($is_download)
		{
			return  $this->ltv_download($out);
		}
		
		echo json_encode($out);exit;
	}
	
	private function get_one_day_ltv($app_id,$spend,$start,$end,$num,$country="all",$media_source="all",$ad_source="all",$campaign_id="",$device="all",$table)
	{
		$dates = getDateFromRange($start, $end);
		$res =array(
		   "num"=>0,
		   "impression"=>0,
		   "total_revenue"=>"0.00",
		   "Reward"=>0,
		   "Inter"=>0,
		);
		foreach( $dates as $key=>$v )
		{
			$time = date("Y-m-d",(strtotime($v)+$num*24*3600));
			$r = $this->getImpression($app_id,$v,$time,$time,$country,$media_source,$ad_source,$campaign_id,$device);
			$revenue_info =$this->getRevenue($app_id,$v,$time,$time,$country,$media_source,$ad_source,$campaign_id,$device,$table);
			$Reward =$r["Reward"];
			$Inter =$r["Inter"];
			$res["total_revenue"]+=$revenue_info["total_revenue"];
			$res["Reward"]+=$Reward;
			$res["Inter"]+=$Inter;
		}
		$res["day_roi"] = $spend["spend"]>0?round($res["total_revenue"]*100/$spend["spend"],2)."%":0;
		$res["day_avg_revenue"] = $spend["installs"]>0?round($res["total_revenue"]/$spend["installs"],3):0;
		return $res;
	}
	
	private function getImpression($app_id,$date,$start,$end,$country,$media_source,$ad_source,$campaign_id,$device)
	{
		$Reward =0;
		$Inter =0;
		$where="gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if( $media_source!="all" )
		{
			$where.=" and media_source='{$media_source}'";
		}
		if( $ad_source!="all" )
		{
			$where.=" and ad_source='{$ad_source}'";
		}
		if( $campaign_id!="" )
		{
			$where.=" and campaign_id='{$campaign_id}'";
		}
		if( $device!="all" )
		{
			$where.=" and device_category='{$device}'";
		}
		$row= Db::query("SELECT adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY adtype");
		if( !empty($row) && isset($row[0]) )
		{
			foreach($row as $v)
			{
				if($v['adtype']=='Reward')
				{
					$Reward = $v['num'];
				}
				if($v['adtype']=='Inter')
				{
					$Inter = $v['num'];
				}
			}
		}
		return ["Reward"=>$Reward,"Inter"=>$Inter];
	}
	
	
	
	
	private function getRevenue($app_id,$date,$start,$end,$country,$media_source,$ad_source,$campaign_id,$device,$table)
	{
		$res = $this->byChannel($app_id,$date,$start,$end,$country,$media_source,$ad_source,$campaign_id,$device,$table);
		$total_revenue ="0.00";
		$revenueList =[];
		if( !empty($res) )
		{
			foreach( $res as $v )
			{
				if( $v["num"]>0 )
				{
					$rate = 1;
					$ad_channel = $this->getchannel($this->network_media,$v["ad_source"]);
					$adtype="";
					switch($v["adtype"])
					{
						case 'Inter':
						  $adtype ='int';
						break;
						case 'Reward':
						  $adtype ='rew';
						break;
					}
					$ecpm = $this->getEcpm($app_id,$start,$end,$country,$ad_channel,$adtype);
					if($ad_channel=='5')
					{
						$rate ="0.92";
					}
					$revenue = $v["num"]*$ecpm*$rate;
					$total_revenue+=$revenue;
					$revenueList[] = ["adname"=>$v["ad_source"],"advalue"=>$revenue];
				}				
			}
		}
		return ["total_revenue"=>round($total_revenue,2),"revenueList"=>$revenueList];
	}
	
	
	private function byChannel($app_id,$date,$start,$end,$country,$media_source,$ad_source,$campaign_id,$device,$table)
	{
		$where="gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if( $media_source!="all" )
		{
			$where.=" and media_source='{$media_source}'";
		}
		if( $ad_source!="all" )
		{
			$where.=" and ad_source='{$ad_source}'";
		}
		if( $campaign_id!="" )
		{
			//$where.=" and campaign_id='{$campaign_id}'";
		}
		if( $device!="all" )
		{
			$where.=" and device_category='{$device}'";
		}
		$row=Db::query("SELECT ad_source,adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY ad_source,adtype");
		return $row;
	}
	
	public function ltv_download($data)
	{
		if(!empty($data))
		{
			$xlsCell  = array(
				array("name",'名称'),
				array("spend",'推广花费'),
				array("installs",'推广新增'),				
				array('cpi','推广成本'),
				array('total_total_revenue','总收益'),
				array('total_avg_revenue','总人均价值'),
				array('total_roi','总ROI(%)')
			 );
			$xlsData =[];
			foreach($data as $v)
			{
				$xlsData[]=["name"=>$v["name"],"spend"=>$v["promote_info"]["spend"],"installs"=>$v["promote_info"]["installs"],"cpi"=>$v["cpi"],"total_total_revenue"=>$v["tablist"]["total_total_revenue"],"total_avg_revenue"=>$v["tablist"]["total_avg_revenue"],"total_roi"=>$v["tablist"]["total_roi"] ];
			}
			$Index = new E(request());
			$name ="LTV模型数据下载".date("YmdHis");
			echo $Index->exportExcel($name,$xlsCell,$xlsData,$name,$name);exit;
		}
	}
	
	public function ltv_json_data()
	{
		$params = input("post.");
		print_r($params);exit;
	}
	
	public function download($app_id="")
	{
		if(!$app_id)
		{
			return false;
		}
		$row = Db::name("app")->where("id={$app_id}")->find();
		if( !empty($row) )
		{
			 $type = $row["platform"]=='ios'?"idfa":"advertising_id";
			 $xlsCell  = array(
				array($type,'IDFA'),			
				array('country','国家'),
			 );
			 $xlsData = Db::name("appsflyer")->field("{$type},country")->where("gb_id={$app_id} and {$type}!=''")->group("{$type},country")->select();
			 $Index = new E(request());
			 $name =$row["app_name"]."种子用户下载".date("Ymd");
			echo $Index->exportExcel($name,$xlsCell,$xlsData,$name,$name);
		}
       	exit("下载失败!");
	}
}
