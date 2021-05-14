<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Tenjin extends Base
{
    
	public function index($appid="")
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
	   $assign =array(
	     "countrys"=>admincountry(),
		 "start"=>date("Y-m-d",strtotime("-8 day")),
		 "end"=>date("Y-m-d",strtotime("-2 day"))
	  );
	  return $this->fetch('index',$assign);
    }
	
	public function getchannel($type="1")
	{
		$appid = getcache("select_app");
		$r = $this->getAdNetWork($appid,$type);
		echo json_encode($r);exit;
	}
	
	
	//获取渠道
	private function getAdNetWork($app_id,$type="1")
	{
		$table = "tenjin_report";
		if( $type=="2" )
		{
			$table = "tenjin_adrevenue";
		}
		$t = Db::name($table)->field("ad_network_id")->where("app_id={$app_id}")->group("ad_network_id")->select();
		$ids = array_column($t,"ad_network_id");
		
		 $key = array_search(19223, $ids);
         if ($key !== false)
            array_splice($ids, $key, 1);
        $data = Db::name("tenjin_adnetwork")->field("adnetwork_id,name")->where(["adnetwork_id"=>["in",$ids] ])->select();
		
		array_unshift($data,["adnetwork_id"=>"all","name"=>"全部渠道"]);	
		return $data;
	}
	
	public function getReportData($start="",$end="",$country="all",$adnetwork_id="all",$field="day",$type="overview")
	{
		$appid = getcache("select_app");
		$out_data=[];
		$arr = array(
				   "overview"=>"getDayReportData",
				   "reten"=>"getretentab",
				   "revenue"=>"getrevenue"
				);
		$func =$arr[$type]; 		
		if( $field=="day" )
		{
			$dates =getDateFromRange($start, $end);			
			foreach( $dates as $k=>$v )
			{			
								
				$t = $this->$func($appid,$v,$v,$country,$adnetwork_id);
				$t["name"] = $v;
				$out_data[$k] = $t;
			}
		}elseif( $field=="country" )
		{
			$countrys =admincountry();				
			foreach( $countrys as $k=>$v )
			{			
				
				
				if( $country!="all" && $country==$k )
				{
					$t = $this->$func($appid,$start,$end,$k,$adnetwork_id);
				    $t["name"] = $v;
				    echo json_encode(["0"=>$t]);exit;
					break;
				}else{
					$t = $this->$func($appid,$start,$end,$k,$adnetwork_id);
					$t["name"] = $v;
					$out_data[] = $t;
				}
			}
			
		}elseif($field=="channel")
		{
			$tage = ($type =="revenue")?"2":"1";
			$platforms = $this->getAdNetWork($appid,$tage);
			foreach( $platforms as $kk=>$vv )
			{
				if( $adnetwork_id!="all" && $adnetwork_id==$vv["adnetwork_id"] )
				{
					$t = $this->$func($appid,$start,$end,$country,$vv["adnetwork_id"]);
				    $t["name"] = $vv["name"];
				    echo json_encode(["0"=>$t]);exit;
					break;
				}else{
					$t = $this->$func($appid,$start,$end,$country,$vv["adnetwork_id"]);
					$t["name"] = $vv["name"];
					$out_data[] = $t;
				}
			}
		}
		
		echo json_encode($out_data);exit;
	}
	
	//
	private function getDayReportData($appid,$start,$end,$country,$ad_network_id)
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $ad_network_id!="all" && $ad_network_id!="" )
		{
			if( $ad_network_id==19520 )
			{
				$ad_network_id ="19223,19520";
			}
			$where.=" and ad_network_id in ({$ad_network_id})";
		}
		$r  =Db::name("tenjin_report")->field("sum(tracked_installs) as tracked_installs,sum(spend) as spend,sum(reported_installs) as reported_installs,sum(daily_active_users) as daily_active_users,sum(ad_revenue) as ad_revenue")->where($where)->find();
	    $tracked_installs = isset($r["tracked_installs"])?$r["tracked_installs"]:0;
		$spend = isset($r["spend"])?round($r["spend"],2):"0.00";
		$dau =isset($r["daily_active_users"])?$r["daily_active_users"]:0;
		$ad_revenue = isset($r["ad_revenue"])?round($r["ad_revenue"],2):"0.00";
		$day_Data1 = $this->getRetenData($appid,$start,$end,$country,$ad_network_id,1);
		$Reven_Day_Data1 = $this->getRevenueData($appid,$start,$end,$country,$ad_network_id,1);
		$Reven_Day_Data7 = $this->getRevenueData($appid,$start,$end,$country,$ad_network_id,7);
		$Reven_Day_Data30 = $this->getRevenueData($appid,$start,$end,$country,$ad_network_id,30);
		return array(
		     "tracked_installs"=>(int)$tracked_installs,
			 "reported_installs"=>isset($r["reported_installs"])?(int)$r["reported_installs"]:0,
			 "daily_active_users"=>(int)$dau,
			 "spend"=>(float)$spend,
			 "ltv_1"=>round($Reven_Day_Data1["ad_revenue"],2),
			 "roas_1"=>$spend>0?round(($Reven_Day_Data1["ad_revenue"]+$Reven_Day_Data1["iap_revenue"])*100/$spend,2)."%":"0%",
			 "roas_7"=>$spend>0?round(($Reven_Day_Data7["ad_revenue"]+$Reven_Day_Data7["iap_revenue"])*100/$spend,2)."%":"0%",
			 "roas_30"=>$spend>0?round(($Reven_Day_Data30["ad_revenue"]+$Reven_Day_Data30["iap_revenue"])*100/$spend,2)."%":"0%",
			 "reten_1"=>$tracked_installs>0?round($day_Data1["reten_users"]*100/$tracked_installs,2)."%":"0%",
			 "tcpi"=>$tracked_installs>0?round($spend/$tracked_installs,2):"0.00",
			 "arpdau"=>$dau>0?round($ad_revenue/$dau,2):"0.00"
		);
	}
	
	private function getrevenue($appid,$start,$end,$country,$ad_network_id)
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $ad_network_id!="all" && $ad_network_id!="" )
		{
			$where.=" and ad_network_id in ({$ad_network_id})";
		}
		$r  =Db::name("tenjin_adrevenue")->field("sum(revenue) as revenue,sum(impressions) as impressions,sum(clicks) as clicks")->where($where)->find();
	    $impressions = isset($r["impressions"])?$r["impressions"]:0;
		$revenue = isset($r["revenue"])?round($r["revenue"],2):"0.00";
		$clicks =isset($r["clicks"])?$r["clicks"]:0;
		$ecpm = $impressions>0?round( $revenue*1000/$impressions,2):"0.00";
		$ecpc = $clicks>0?round( $revenue/$clicks,2):"0.00";
		return ["impressions"=>$impressions,"revenue"=>$revenue,"clicks"=>$clicks,"ecpm"=>$ecpm,"ecpc"=>$ecpc];
	}
	
	private function getRetenData($appid,$start,$end,$country,$ad_network_id,$day)
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}' and days_since_install={$day}";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $ad_network_id!="all" && $ad_network_id!="" )
		{
			if( $ad_network_id==19520 )
			{
				$ad_network_id ="19223,19520";
			}
			$where.=" and ad_network_id in ({$ad_network_id})";
		}
		$r = Db::name("tenjin_reten")->field("sum(daily_active_users) as reten_users,sum(iap_revenue) as iap_revenue,sum(ad_revenue) as ad_revenue")->where($where)->find();
		$ad_revenue = isset($r["ad_revenue"]) && $r["ad_revenue"]?$r["ad_revenue"]:"0.00";
		$iap_revenue = isset($r["iap_revenue"]) && $r["iap_revenue"]?$r["iap_revenue"]:"0.00";
		$reten_users = isset($r["reten_users"]) && $r["reten_users"]?$r["reten_users"]:0;
		return ["reten_users"=>$reten_users,"iap_revenue"=>$iap_revenue,"ad_revenue"=>$ad_revenue];
	}
	
	//总收益
	private function getRevenueData($appid,$start,$end,$country,$ad_network_id,$day)
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}' and days_since_install<={$day} and days_since_install>=0";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $ad_network_id!="all" && $ad_network_id!="" )
		{
			$where.=" and ad_network_id in({$ad_network_id})";
		}
		$r = Db::name("tenjin_reten")->field("sum(daily_active_users) as reten_users,sum(iap_revenue) as iap_revenue,sum(ad_revenue) as ad_revenue")->where($where)->find();
		$ad_revenue = isset($r["ad_revenue"]) && $r["ad_revenue"]?$r["ad_revenue"]:"0.00";
		$iap_revenue = isset($r["iap_revenue"]) && $r["iap_revenue"]?$r["iap_revenue"]:"0.00";
		$reten_users = isset($r["reten_users"]) && $r["reten_users"]?$r["reten_users"]:0;
		return ["reten_users"=>$reten_users,"iap_revenue"=>$iap_revenue,"ad_revenue"=>$ad_revenue];
	}
	
	//留存
	private function getretentab($appid,$start,$end,$country,$ad_network_id)
	{
		$allowds = [1,2,3,4,5,6,7,14,30,60,90];
		$day0 = $this->getRetenData($appid,$start,$end,$country,$ad_network_id,0);
		$current_new_users = $day0["reten_users"];
		//$data= $this->getDayReportData($appid,$start,$end,$country,$ad_network_id);
		//$installs =$data["tracked_installs"]; 
		$res =[];
		foreach( $allowds as $vv )
		{
			$e = $this->getRetenData($appid,$start,$end,$country,$ad_network_id,$vv);
			$reten = $current_new_users>0?round($e["reten_users"]*100/$current_new_users,2)."%":"0%";
			$res["reten_".$vv] =$reten;
			$res["installs"] = (int)$current_new_users;
		}
		return $res;
	}
}
