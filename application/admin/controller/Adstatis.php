<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use app\admin\controller\Index as E;
  //广告收益
  //平台类型1 Mob 2 Unity 3 applovin 4Vungle 5 admob 6 facebook
class Adstatis extends Base
{
   
	public function show($appid="")
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
		$start_date = date("Y-m-d",strtotime("-2 day"));
		$end_date =  date("Y-m-d",strtotime("-2 day") );		
	    $this->assign("country",admincountry());		
		$this->assign("start_date",$start_date);
		$this->assign("end_date",$end_date);
		$this->assign("appid",$appid);
		return $this->fetch();
	}
	
	public function adanalysis($appid="")
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
		$start_date = date("Y-m-d",strtotime("-2 day"));
		$end_date =  date("Y-m-d",strtotime("-2 day") );		
	    $this->assign("country",admincountry());		
		$this->assign("start_date",$start_date);
		$this->assign("end_date",$end_date);
		$this->assign("appid",$appid);
		return $this->fetch();
	}
	
	public function adanalysis_json_data($appid="",$adsource="all",$country="all",$date=[]){
		
		if(empty($date))
		{
			$start = date("Y-m-d",strtotime("-2 day"));
			$end = date("Y-m-d",strtotime("-2 day"));
		}else{
			if( is_array($date) )
			{
				list($start,$end) = $date;
			}else{
				list($start,$end) = explode(",",$date);
			}
		}
		$list = $this->get_unit_id($appid,$start,$end,$adsource,$country);
		$result = $this->get_one_row($appid,$start,$end,$adsource,$country);
		if(!empty($list))
		{
			foreach($list as &$v)
			{
				$v["request"] = $this->get_action_num($result,$appid,$v['adsource'],$v['unit_id'],'request');
				$requestSuccess = $this->get_action_num($result,$appid,$v['adsource'],$v['unit_id'],'requestSuccess');
				$v['filled_rate'] =$v["request"]>0?round($requestSuccess*100/$v["request"],2):0;
				$v['impressions'] = $this->get_action_num($result,$appid,$v['adsource'],$v['unit_id'],'show');
				$v["impressions_rate"] = $requestSuccess>0?round($v['impressions']*100/$requestSuccess,2):0;
				$clicks = $this->get_action_num($result,$appid,$v['adsource'],$v['unit_id'],'click');
				$v["ctr"] = $v['impressions']>0?round($clicks*100/$v['impressions'],2):0;				
				$acsh = $this->get_ecpm($appid,$country,$start,$end,$v["unit_id"]);
				$v["revenue"] = $acsh["revenue"]<=0?"0.00":round($acsh["revenue"],2);
				$v["ecpm"] = $acsh["impressions"]<=0?0:round($acsh["revenue"]*1000/$acsh["impressions"],2);
			}
		}
		$purchase = $this->get_purchase($appid,$start,$end,$country);
		echo  json_encode(["list"=>$list,"purchase"=>$purchase]);exit;
	}
	
	private function get_purchase($appid,$start,$end,$country){
		
		$where = "gb_id={$appid} and event_date>='{$start}' and event_date<='{$end}' and idfa!=''";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$sql="SELECT d.*,round(money/num,2) as `avg`  from (
SELECT sum(c.money) as money,count(*) as num  from (  
SELECT idfa,SUM(money) as money  from  hellowd_adjust_purchase WHERE {$where} GROUP BY idfa ) c ) d";
        $res =  Db::query($sql);
		return (isset($res[0]) &&!empty($res[0]))?$res[0]:["num"=>0,"money"=>"0.00","avg"=>0];
	}
	
	private function get_action_num($result,$appid,$adsource,$unit_id,$action){
		if(!empty($result))
		{
			foreach($result as $v)
			{
				if( $appid==$v["gb_id"] && $adsource==$v["adsource"] && $action==$v["action"] && $unit_id==$v["unit_id"] )
				{
					return intval($v["num"]);
				}
			}
		}
		return 0;
	}
	
	private function get_ecpm($appid,$country,$start,$end,$unit_id){
		
		$where = "sys_app_id={$appid} and date>='{$start}' and date<='{$end}' and unit_id='{$unit_id}'";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$sql = "select sum(impression) as impressions,round(sum(revenue),2) as revenue 
from hellowd_adcash_data  where {$where}";
		$res =  Db::query($sql);
		return (isset($res[0]) &&!empty($res[0]))?$res[0]:["impressions"=>0,"revenue"=>"0.00"];
	}
	
	private function get_unit_id($appid,$start,$end,$adsource="",$country){
		$where = "gb_id={$appid} and unit_id!='' and event_date>='{$start}' and event_date<='{$end}' and action not in('gamebrainInit','rewardRequestToFaile','rewardRequestToSucce','gamebrainInitToSucce','isRewardLoaded','mopubInit','mopubInitToFinish')";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		if($adsource!="all")
		{
			$where.=" and adsource='{$adsource}'";
		}
		$sql = "SELECT cc.*,aa.remark  from (
SELECT adsource,unit_id from  hellowd_adjust_adanalysis WHERE {$where} GROUP BY adsource,unit_id ) 
cc LEFT join hellowd_ads_id aa ON cc.unit_id=aa.unit_id";
		$res =  Db::query($sql);
		return $res;
	}
	
	private function get_one_row($appid,$start,$end,$adsource="",$country){
		
		//and action not in('gamebrainInit','rewardRequestToFaile','rewardRequestToSucce','gamebrainInitToSucce','isRewardLoaded','mopubInit','mopubInitToFinish')
		$where = "gb_id={$appid} and event_date>='{$start}' and event_date<='{$end}' and action not in('gamebrainInit','rewardRequestToFaile','rewardRequestToSucce','gamebrainInitToSucce','isRewardLoaded','mopubInit','mopubInitToFinish')";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		if($adsource!="all")
		{
			$where.=" and adsource='{$adsource}'";
		}
		$sql = "SELECT gb_id,adsource,unit_id,action,SUM(num) as num from  hellowd_adjust_adanalysis WHERE  {$where} GROUP BY adsource,unit_id,action,gb_id";
		$res =  Db::query($sql);
		return $res;
	}
	
	public function adtype_json_data($appid="",$adtype="",$country="all",$date=[],$is_download=false)
	{
		
		if(empty($date))
		{
			$start = date("Y-m-d",strtotime("-2 day"));
			$end = date("Y-m-d",strtotime("-2 day"));
		}else{
			if( is_array($date) )
			{
				list($start,$end) = $date;
			}else{
				list($start,$end) = explode(",",$date);
			}
		}
		$where = "sys_app_id={$appid} and date>='{$start}' and date<='{$end}' and unit_id!=''";
		if($adtype!='all')
		{
			$where.=" and adtype='{$adtype}'";
		}
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$sql = "SELECT c.*,(SELECT remark from hellowd_ads_id a WHERE  c.unit_id = a.unit_id and c.platform = a.platform and a.app_id = {$appid} and a.p_id=0) as remark FROM 
( select date,unit_id,platform,sum(filled) as filled,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue 
from hellowd_adcash_data  where {$where} group by unit_id,date) c ";
		$res =  Db::query($sql);
		if(!empty($res))
		{
			foreach($res as &$vv)
			{
				if(empty($vv["remark"]))
				{
					$vv["remark"] = $vv["unit_id"];
				}
				$vv["request"] = $vv["request"]<=0?0:round($vv["request"]);
				$vv["filled"] = $vv["filled"]<=0?0:round($vv["filled"]);
				$vv["revenue"] = $vv["revenue"]<=0?"0.00":round($vv["revenue"],2);
				$vv["ctr"] = $vv["impressions"]<=0?0:round($vv["clicks"]*100/$vv["impressions"],2);
				//$vv["impressions_rate"] = $vv["total_impressions"]<=0?0:number_format($vv["impressions"]*100/$vv["total_impressions"],2);
				$vv["ecpm"] = $vv["impressions"]<=0?0:round($vv["revenue"]*1000/$vv["impressions"],2);
				$vv["filled_rate"] = $vv["request"]<=0?0:round($vv["filled"]*100/$vv["request"],2);
				$vv["impressions_rate"] = $vv["filled"]<=0?0:round($vv["impressions"]*100/$vv["filled"],2);
			}
		}
		if($is_download)
		{
			return  $this->download($res);
		}
		echo  json_encode($res);exit;
	}
	
	public function download($data)
	{
		if(!empty($data))
		{
			$xlsCell  = array(
				array("date",'日期'),
				array("remark",'名称'),
				array("request",'请求'),				
				array('filled','广告填充请求'),
				array('filled_rate','填充率(%)'),
				array('impressions','展示次数'),
				array('impressions_rate','展示率(%)'),
				array('clicks','点击量'),
				array('ctr','点击率(%)'),
				array('ecpm','千次展示平均费用'),
				array('revenue','千次展示平均费用')
			 );
			$xlsData =[];
			foreach($data as $v)
			{
				$xlsData[]=$v;
			}
			$Index = new E(request());
			$name ="广告数据下载".date("YmdHis");
			echo $Index->exportExcel($name,$xlsCell,$xlsData,$name,$name);exit;
		}
	}
	
	private function getall_unit_id($adtypeid,$country)
	{
		$sql ="select id,remark,unit_id,ordernum,adtype,ecpm as decpm from hellowd_ads_id where id in({$adtypeid})";
		$r =  Db::query($sql);
		
		if( $country!="all" )
		  {
			 foreach( $r as &$vv )
			  {
				 $one = Db::name('ads_id')->where( [ "p_id"=>$vv["id"],"country"=>$country ] )->field("ordernum,ecpm")->find();
				 if( !empty($one) )
				 {
					 $vv["ordernum"] = $one["ordernum"];
					 $vv["decpm"] = $one["ecpm"];
				 }
			  }			  
		  }
		 
		  return $r;
	}
	
	public function getadtype_json($appid,$adtype="")
	{
		$adtype_sql = "select id,platform,remark,unit_id,ordernum,adtype from hellowd_ads_id where app_id={$appid} and adtype='{$adtype}' order by ordernum asc";		
		$res =  Db::query($adtype_sql);
        $this->assign("adlist",$res);		
		return $this->fetch();
	}
	
	private function getad_imdata($appid,$start_date,$end_date,$v,$country)
	{
	$where = "sys_app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' and unit_id='{$v}'";
    if( !preg_match("/all/",$country) )
		{
			if( $country )
			{
				$where.= " and country='{$country}'";
			}			
		}	
	$sql = " select adtype,sum(filled) as filled,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data  where {$where}";
		$res =  Db::query($sql);		
		if( isset($res[0]) && !empty($res[0]) )
		{
			return $res[0];
		}
		return ["adtype"=>"","filled"=>0,"request"=>0,"impressions"=>0,"clicks"=>0,"revenue"=>"0.00"];
	}
	
	private function getadiddata($appid,$start_date,$end_date,$adtypeid,$country)
	{
		$country = rtrim($country,",");
		
		
        if($adtypeid=="")
		{
			return [];
		}			
		
		$res = $this->getall_unit_id($adtypeid,$country);
		
		
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				$tr = $this->getad_imdata($appid,$start_date,$end_date,$vv["unit_id"],$country);
				
				$vv = array_merge($vv,$tr);
				$vv["active_users"] =  $this->getactive_users($appid,$start_date,$end_date,$country);
				$data = $this->getcurrenincome($appid,$start_date,$end_date,$country,$vv["adtype"]);
				$vv["total_revenue"] = $data["revenue"];
				$vv["total_impressions"] = $data["impressions"];
				$vv["impressions"] =$vv["impressions"]?$vv["impressions"]:0;
                $vv["clicks"] = $vv["clicks"]?$vv["clicks"]:0;
                $vv["revenue"] = $vv["revenue"]?$vv["revenue"]:"0.00";				
				$vv["ctr"] = $vv["impressions"]<=0?0:number_format($vv["clicks"]*100/$vv["impressions"],2);
				$vv["impressions_rate"] = $vv["total_impressions"]<=0?0:number_format($vv["impressions"]*100/$vv["total_impressions"],2);
				$vv["revenue_rate"] = $vv["total_revenue"]<=0?0:number_format($vv["revenue"]*100/$vv["total_revenue"],2);
				$vv["ecpm"] = $vv["impressions"]<=0?0:number_format($vv["revenue"]*1000/$vv["impressions"],2);
				$vv["avg_impressions"] = $vv["active_users"]<=0?0:number_format($vv["impressions"]/$vv["active_users"],2);
				$vv["filled_rate"] = $vv["request"]<=0?0:number_format($vv["filled"]*100/$vv["request"],2);
				$isneed = "0";
				if( $vv["decpm"]<$vv["ecpm"] )
				{
					$isneed = "1";
				}elseif( $vv["decpm"]>$vv["ecpm"] )
				{
					$fd = $vv["decpm"]-$vv["ecpm"];
					$fd_rate = $vv["ecpm"]<=0?0:$fd/$vv["ecpm"];
					if( $fd_rate>="0.1" )
					{
						$isneed = "2";
					}
				}
				$vv["isneed"] = $isneed;
			}
			$res = admin_array_sort($res,"ordernum","asc");
		}
		return $res;
	}
	
	//当月收益 按时间筛选
	private function getcurrenincome($appid,$start,$end,$country,$adtype)
	{
		$where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}' and adtype='{$adtype}'";
		$country = rtrim($country,",");
		if( !preg_match("/all/",$country) )
		{
			if( $country )
			{
				$where.= " and country='{$country}'";
			}			
		}	
		$sum_sql = "select round(sum(revenue),2) as revenue,sum(impression) as impressions from hellowd_adcash_data where {$where}";
		$d= Db::query($sum_sql);
		$revenue ="0.00";
		$impressions =0;
		if( isset($d[0]) && !empty($d[0]) )
		{
			$revenue = $d[0]["revenue"]?$d[0]["revenue"]:"0.00";
			$impressions = $d[0]["impressions"]?$d[0]["impressions"]:0;
		}
		return ["revenue"=>$revenue,"impressions"=>$impressions]; 
	}
	
	private function getactive_users($appid,$start="",$end="",$country)
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		$country = rtrim($country,",");
		if( !preg_match("/all/",$country) )
		{
			if( $country )
			{
				$where.= " and country='{$country}'";
			}			
		}	
		$active_sql = "select sum(val) as val from hellowd_active_users where {$where}";
		$d= Db::query($active_sql);
		
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	public function adtype_json($appid,$start_date,$end_date,$adtypeid,$country)
	{
		$adtypeid =rtrim($adtypeid,",");
		if( $adtypeid=="" )
		{
			$res = [];
		}else{
			$res = $this->getadiddata($appid,$start_date,$end_date,$adtypeid,$country);
		}			
		$this->assign("list",$res);
		return $this->fetch();
	}
	
	private function getdaterange($date)
	{
		switch($date)
		{
			case "last":
			   $time = date("Y-m-d",strtotime("-1 day"));
			   return getDateFromRange($time,$time);
			break;
            case "oneweek":
               $start_time = date("Y-m-d",strtotime("-7 day"));
			   $end_time = date("Y-m-d",strtotime("-1 day")); 
			   return getDateFromRange($start_time,$end_time);
            break;
            case "twoweek":
               $start_time = date("Y-m-d",strtotime("-14 day"));
			   $end_time = date("Y-m-d",strtotime("-1 day"));
			   return getDateFromRange($start_time,$end_time);
			case "three":
               $start_time = date("Y-m-d",strtotime("-3 day"));
			   $end_time = date("Y-m-d",strtotime("-1 day"));
			   return getDateFromRange($start_time,$end_time);   
            break; 			
		}
	}
	
	//广告类型
	private function getadtypedata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		if( preg_match("/country/",$where) )
		{
			$all_country = admincountry();
			foreach( $all_country as $key=>$c )
			{
				if( preg_match("/{$key}/",$where) )
					{
						$country=$key;
						break;
					}
			}
		}else{
			$country="all";
		}
		$day_where="";
		$dates = preg_match("/date='(\d{4}\-\d{2}\-\d{2})'/",$where, $matches);
		if( !empty($dates) )
		{
			$day_where = " and date='{$matches[1]}'";
		}
		
	    $active_sql = "(SELECT SUM(val) from hellowd_active_users WHERE app_id in({$appids}) and country='{$country}' and date>='{$start_date}' and date<='{$end_date}' {$day_where} ) as activenum";
		$adtype_sql = "select adtype as type,{$active_sql},sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where} group by adtype";
		
		$res =  Db::query($adtype_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
						
				$vv["name"] = getFullADType( $vv["type"] );
				$vv["activenum"] = !empty($vv["activenum"])?$vv["activenum"]:"0";
			}
		}
		return $res;
	}
	
	//广告渠道
	private function getchanneldata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		if( preg_match("/country/",$where) )
		{
			$all_country = admincountry();
			foreach( $all_country as $key=>$c )
			{
				if( preg_match("/{$key}/",$where) )
					{
						$country=$key;
						break;
					}
			}
		}else{
			$country="all";
		}
        
        $day_where="";
		$dates = preg_match("/date='(\d{4}\-\d{2}\-\d{2})'/",$where, $matches);
		if( !empty($dates) )
		{
			$day_where = " and date='{$matches[1]}'";
		}		
		 
		$channel_sql = "select platform as type,(SELECT SUM(val) from hellowd_active_users WHERE app_id in({$appids}) and country='{$country}' and date>='{$start_date}' and date<='{$end_date}' {$day_where} ) as activenum,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where} group by platform";
		$res =  Db::query($channel_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				$vv["activenum"] = !empty($vv["activenum"])?$vv["activenum"]:"0";
				$vv["name"] ='<img style="height:14px;width:14px;" src='.getplatformimg($vv["type"]).' >&nbsp;'.getplatform($vv["type"]);
			}
		}
		return $res;
	}
	
	//时间筛选
	private function gettimedata($appids,$start_date="",$end_date="",$where="")
	{
		
		$range_date = getDateFromRange($start_date,$end_date);
		if(!$where)
		{
			$where=" and 1=1";
		}
      if( preg_match("/country/",$where) )
		{
			$all_country = admincountry();
			foreach( $all_country as $key=>$c )
			{
				if( preg_match("/{$key}/",$where) )
					{
						$country=$key;
						break;
					}
			}
		}else{
			$country="all";
		}			
		$res=[];
		
		foreach( $range_date as $key=>$vvv )
		{
			 $res[$key]["name"] = $vvv;
			 $res[$key]["type"] = $vvv;
			 $d_sql ="select (SELECT SUM(val) from hellowd_active_users WHERE app_id in({$appids}) and country='{$country}' and date='{$vvv}' ) as activenum,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date='{$vvv}' {$where}";
			 $d = Db::query($d_sql);
			 $res[$key]["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			 $res[$key]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $res[$key]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";	
			 $res[$key]["activenum"] = isset($d[0]["activenum"])?$d[0]["activenum"]:"0";
			 $res[$key]["publisher_showtips"] = isshowtips($appids,$vvv,"publisher");
             $res[$key]["producter_showtips"] = isshowtips($appids,$vvv,"producter");
		}
		return $res;
	}
	
	//人均展示统计
	public function average($appid="")
	{
		if( $appid=="" )
		{
			$appid = getcache("select_app");
		}
		 setcache("select_app",$appid);
		$appid = getcache("select_app");
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}		
		$country = admincountry();
		
		return $this->fetch('average',["country"=>$country,"appid"=>$appid ] );
	}
	
	private function getappdata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		$search_app = Db::name("app")->field("id,app_name as name,platform")->where("id in({$appids})")->select();
		foreach( $search_app as &$s )
		{
			$app_sql = "select (SELECT SUM(val) from hellowd_active_users WHERE app_id ={$s["id"]} and country='all' and date>='{$start_date}' and date<='{$end_date}' ) as activenum,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$s["id"]} and date>='{$start_date}' and date<='{$end_date}' {$where}";
			$r = Db::query($app_sql);			
			$s["activenum"] = isset($r["0"]["activenum"])?$r["0"]["activenum"]:"0";
			$s["impressions"] = isset($r["0"]["impressions"])?$r["0"]["impressions"]:"0";
			$s["clicks"] =isset($r["0"]["clicks"])?$r["0"]["clicks"]:"0";
			$s["revenue"] = isset($r["0"]["revenue"])?$r["0"]["revenue"]:"0.0";				
			$upltv_adtype_data = getwhereupltv($appids,$start_date,$end_date,$where);
			$s["revenue"] =($s["revenue"]-$upltv_adtype_data["revenue"])<0?0:$s["revenue"]-$upltv_adtype_data["revenue"];
			$s["impressions"] =($s["impressions"]-$upltv_adtype_data["impression"])<0?0:$s["impressions"]-$upltv_adtype_data["impression"];
			$s["clicks"] =($s["clicks"]-$upltv_adtype_data["click"])<0?0:$s["clicks"]-$upltv_adtype_data["click"];				
            $s["type"] = $s["id"];
		}
		return $search_app;
	}
	
	//广告国家
	private function getscountrydata($appids,$start_date="",$end_date="",$where="")
	{
		$field="";
		if(!$where){
			$where=" and 1=1";
		}
		if( preg_match("/country/",$where) )
		{
			$country_sql = "select country as type,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids})  and date>='{$start_date}' and date<='{$end_date}' {$where} group by country";
		}else{
			$country_sql = "select  sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where}";
		}
		
		$day_where="";
		$dates = preg_match("/date='(\d{4}\-\d{2}\-\d{2})'/",$where, $matches);
		if( !empty($dates) )
		{
			$day_where = " and date='{$matches[1]}'";
		}	
		
		$countrys = admincountry();		
		$res =  Db::query($country_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				if( !isset($vv["type"] ) )
				{
					$vv["name"]="全部";
					$vv["type"]="all";
				}else{
					
					$vv["name"] =$countrys[$vv["type"]];
				}
								
			   $active_sql ="SELECT SUM(val) as activenum  from hellowd_active_users WHERE app_id in({$appids}) and country='{$vv["type"]}' and date>='{$start_date}' and date<='{$end_date}' {$day_where}";	
				$r =  Db::query($active_sql);
				$vv["activenum"] = isset($r["0"]["activenum"])?$r["0"]["activenum"]:"0";
			}
		}
		return $res;
	}
	
	public function levle($appids,$all,$start_date,$end_date,$levle="",$tag="",$index="",$prev_index="",$prev_tag="",$p_prev_index="",$p_prev_tag="",$isgroupday)
	{
		$appids = rtrim($appids,",");
		if($levle=="one")
		{
			return $this->levle_one($appids,$all,$start_date,$end_date);
		}elseif( $levle=="two" )
		{
			return $this->levle_two($appids,$all,$start_date,$end_date,$tag,$index);
			
		}elseif( $levle=="three" )
		{
			
			return $this->levle_three($appids,$all,$start_date,$end_date,$tag,$index,$prev_index,$prev_tag,$isgroupday);
		}
        elseif( $levle=="four" )
		{
			
			if( $isgroupday==2 )return false;
			return $this->levle_four($appids,$all,$start_date,$end_date,$tag,$index,$prev_index,$prev_tag,$p_prev_index,$p_prev_tag);
		}		
	}
	
	private function levle_one($appids,$all,$start_date,$end_date)
	{
		$one_where=" and adtype!='no' ";
		$one_v = rtrim($all[0]["value"],",");
		$up_te = $one_where;
		switch( $all[0]["type"] )
		{
			case "adtype":
			  if( !preg_match("/all/",$one_v) )
			  {
				  $one_where.=" and adtype in({$one_v})";
			  }
			 
			  $res = $this->getadtypedata($appids,$start_date,$end_date,$one_where );
			  break;
			case "platform":
			  if( !preg_match("/all/",$one_v) )
			  {
				  $one_where.=" and platform in({$one_v})";
			  }
			  $res = $this->getchanneldata($appids,$start_date,$end_date,$one_where);
			  break;
            case "country":
			  if( !preg_match("/all/",$one_v) )
			  {
				  $one_where.=" and country in({$one_v})";
			  }
			  $res = $this->getscountrydata($appids,$start_date,$end_date,$one_where);
			  break;
            case "date":
			  $res = $this->gettimedata($appids,$start_date,$end_date,$one_where);
			  break;
            case "sys_app_id":
			   if( !preg_match("/all/",$one_v) )
			  {
				  $one_where.=" and sys_app_id in({$one_v})";
			  }
			  $res = $this->getappdata($appids,$start_date,$end_date,$one_where);
			  break;			  
		}
		if( !empty($res) )
		{
			
			foreach( $res as $key=>$vvv )
			{
			  $up_where=$up_te;
			  if( $all[0]["type"]=="platform" )
			  {
				  if( $vvv["type"]!="30" )
				  {
					  continue;
				  }
					  
			  }else{
				  if( !preg_match("/all/",$vvv['type']) )
				   {
					 $up_where.= " and ".$all[0]["type"]."='{$vvv['type']}'";
				   }else{
					   $up_where=$up_te;
				   }  
			  }
 			  
				$upltv_adtype_data = getwhereupltv($appids,$start_date,$end_date,$up_where);
				$res[$key]["revenue"] =($res[$key]["revenue"]-$upltv_adtype_data["revenue"])<0?0:$res[$key]["revenue"]-$upltv_adtype_data["revenue"];
				$res[$key]["impressions"] =($res[$key]["impressions"]-$upltv_adtype_data["impression"])<0?0:$res[$key]["impressions"]-$upltv_adtype_data["impression"];
				$res[$key]["clicks"] =($res[$key]["clicks"]-$upltv_adtype_data["click"])<0?0:$res[$key]["clicks"]-$upltv_adtype_data["click"];
			}
		}
		return $this->fetch("levle_one",[ "data"=>$res,"tag"=>$all[0]["type"] ] );
	}
	
	private function levle_two($appids,$all,$start_date,$end_date,$tag,$index)
	{
		$two_v = rtrim($all[1]["value"],",");
		$one_where=" and adtype!='no' ";
		if( $tag=="country" && $index=="all" )
		{
			$one_where=" and adtype!='no' ";
		}else{
			$one_where.=" and {$tag}='{$index}'";
		}
		$up_te = $one_where;		
		switch( $all[1]["type"] )
		{
			case "adtype":
			  if( !preg_match("/all/",$two_v) )
			  {
				  $one_where.=" and adtype in({$two_v})";
			  }
			 
			  $res = $this->getadtypedata($appids,$start_date,$end_date,$one_where );
			  break;
			case "platform":
			  if( !preg_match("/all/",$two_v) )
			  {
				  $one_where.=" and platform in({$two_v})";
			  }
			  $res = $this->getchanneldata($appids,$start_date,$end_date,$one_where);
			  break;
            case "country":
			  if( !preg_match("/all/",$two_v) )
			  {
				  $one_where.=" and country in({$two_v})";
			  }
			  $res = $this->getscountrydata($appids,$start_date,$end_date,$one_where);
			  break;
            case "date":
			  $res = $this->gettimedata($appids,$start_date,$end_date,$one_where);
			  break;
            case "sys_app_id":
			   if( !preg_match("/all/",$two_v) )
			  {
				  $one_where.=" and sys_app_id in({$two_v})";
			  }
			  $res = $this->getappdata($appids,$start_date,$end_date,$one_where);
            break;			  
		}
		if( !empty($res) )
		{
			
			foreach( $res as $key=>$vvv )
			{
				$up_where=$up_te;
			  if( $all[1]["type"]=="platform" )
			  {
				  if( $vvv["type"]!="30" )
				  {
					  continue;
				  }
					  
			  }else{
				  if( !preg_match("/all/",$vvv['type']) )
				   {
					 $up_where.= " and ".$all[1]["type"]."='{$vvv['type']}'";
				   }else{
					    if( preg_match("/platform='30'/",$up_te) )
					   {
						   $up_where =str_replace("platform='30'","platform='6'",$up_te);
					   }elseif(preg_match("/platform='6'/",$up_te))
					   {
						   $up_where=" and 1!=1";
					   }else{
						   $up_where=$up_where;
					   }
				   }  
			  }
				$upltv_adtype_data = getwhereupltv($appids,$start_date,$end_date,$up_where);
				$res[$key]["revenue"] =($res[$key]["revenue"]-$upltv_adtype_data["revenue"])<0?0:$res[$key]["revenue"]-$upltv_adtype_data["revenue"];
				$res[$key]["impressions"] =($res[$key]["impressions"]-$upltv_adtype_data["impression"])<0?0:$res[$key]["impressions"]-$upltv_adtype_data["impression"];
				$res[$key]["clicks"] =($res[$key]["clicks"]-$upltv_adtype_data["click"])<0?0:$res[$key]["clicks"]-$upltv_adtype_data["click"];
			}
		}
       return $this->fetch("levle_two",[ "data"=>$res,"tag"=>$all[1]["type"],"prev_index"=>$index,"prev_tag"=>$tag ] );
	}
	
	private function levle_three($appids,$all,$start_date,$end_date,$tag,$index,$prev_index,$prev_tag,$isgroupday)
	{
		
		$three_v = rtrim($all[2]["value"],",");
		$one_where=" and adtype!='no' ";
		if( ($tag=="country" && $index=="all")   )
		{
			
			if( ($prev_tag=="country" && $prev_index=="all") )
			{
				$one_where.="";
			}else{
				$one_where.=" and {$prev_tag}='{$prev_index}'";
			}
		}else{
			$one_where.=" and {$tag}='{$index}'";
		}
		
		if( ($prev_tag=="country" && $prev_index=="all")   )
		{
			
			if( ($tag=="country" && $index=="all") )
			{
				$one_where.=" ";
			}else{
				$one_where.=" and {$tag}='{$index}'";
			}
		}else{
			$one_where.=" and {$prev_tag}='{$prev_index}'";
		}
        $up_te = $one_where;
		switch( $all[2]["type"] )
		{
			case "adtype":
			  if( !preg_match("/all/",$three_v) )
			  {
				  $one_where.=" and adtype in({$three_v})";
			  }
			 
			  $res = $this->getadtypedata($appids,$start_date,$end_date,$one_where );
			  break;
			case "platform":
			  if( !preg_match("/all/",$three_v) )
			  {
				  $one_where.=" and platform in({$three_v})";
			  }
			  $res = $this->getchanneldata($appids,$start_date,$end_date,$one_where);
			  break;
            case "country":
			  if( !preg_match("/all/",$three_v) )
			  {
				  $one_where.=" and country in({$three_v})";
			  }
			  $res = $this->getscountrydata($appids,$start_date,$end_date,$one_where);
			  break;
             case "date":
			  $res = $this->gettimedata($appids,$start_date,$end_date,$one_where);
			  break;
             case "sys_app_id":
			   if( !preg_match("/all/",$three_v) )
			  {
				  $one_where.=" and sys_app_id in({$three_v})";
			  }
			  $res = $this->getappdata($appids,$start_date,$end_date,$one_where);
            break;			  
		}
		if( !empty($res) )
		{
			
			foreach( $res as $key=>$vvv )
			{
				$up_where=$up_te;
			  if( $all[2]["type"]=="platform" )
			  {
				  if( $vvv["type"]!="30" )
				  {
					  continue;
				  }
					  
			  }else{
				  if( !preg_match("/all/",$vvv['type']) )
				   {
					 $up_where.= " and ".$all[2]["type"]."='{$vvv['type']}'";
				   }else{
					   if( preg_match("/platform='30'/",$up_te) )
					   {
						   $up_where =str_replace("platform='30'","platform='6'",$up_te);
					   }elseif(preg_match("/platform='6'/",$up_te))
					   {
						   $up_where=" and 1!=1";
					   }else{
						   $up_where=$up_te;
					   }
					  
				   }  
			  }
				$upltv_adtype_data = getwhereupltv($appids,$start_date,$end_date,$up_where);
				$res[$key]["revenue"] =($res[$key]["revenue"]-$upltv_adtype_data["revenue"])<0?0:$res[$key]["revenue"]-$upltv_adtype_data["revenue"];
				$res[$key]["impressions"] =($res[$key]["impressions"]-$upltv_adtype_data["impression"])<0?0:$res[$key]["impressions"]-$upltv_adtype_data["impression"];
				$res[$key]["clicks"] =($res[$key]["clicks"]-$upltv_adtype_data["click"])<0?0:$res[$key]["clicks"]-$upltv_adtype_data["click"];
			}
		}
       return $this->fetch("levle_three",[ "data"=>$res,"tag"=>$all[2]["type"],"prev_index"=>$index,"prev_tag"=>$tag,"p_prev_index"=>$prev_index,"p_prev_tag"=>$prev_tag,"isgroupday"=>$isgroupday ] );
	}
	private function levle_four($appids,$all,$start_date,$end_date,$tag,$index,$prev_index,$prev_tag,$p_prev_index,$p_prev_tag)
	{
		
		
		$one_where=" and adtype!='no' ";
		if( $tag=="country" && $index=="all"  )
		{
			$one_where=" and adtype!='no' ";
		}else{
			$one_where.=" and {$tag}='{$index}'";
		}
		
		if( $prev_tag=="country" && $prev_index=="all" )
		{
			$one_where.="";
		}else{
			$one_where.=" and {$prev_tag}='{$prev_index}'";
		}
		
		if( $p_prev_tag=="country" && $p_prev_index=="all" )
		{
			$one_where.="";
		}else{
			$one_where.=" and {$p_prev_tag}='{$p_prev_index}'";
		}
		
		$res = $this->gettimedata($appids,$start_date,$end_date,$one_where);
            
       return $this->fetch("levle_four",[ "data"=>$res,"tag"=>$all[2]["type"],"prev_index"=>$index,"prev_tag"=>$tag,"p_prev_index"=>$prev_index,"p_prev_tag"=>$prev_tag ] );
	}
	//日活设置
	public function dayactive()
	{
		$apps= Db::name("app")->field("id,app_name,platform")->select();
		
		$all_country = admincountry();
		return $this->fetch('dayactive',[ "apps"=>$apps,"all_country"=>$all_country ]);
	}
	public function daynewuser()
	{
		$apps= Db::name("app")->field("id,app_name,platform")->select();
		
		$all_country = admincountry();
		return $this->fetch('daynewuser',[ "apps"=>$apps,"all_country"=>$all_country ]);
	}
	public function day_save($app_id="",$date="",$val="",$country="")
	{
	   $res = Db::name("active_users")->where( [ "app_id"=>$app_id,"date"=>$date,"country"=>$country ] )->find();
	   if( empty($res) )
	   {
		  $r = Db::name("active_users")->insert( ["app_id"=>$app_id,"val"=>$val,"country"=>$country,"date"=>$date,"updateuser"=>$this->_adminname,"updatetime"=>date("Y-m-d H:i:s") ] );
	      
	   }else{
		  $r = Db::name("active_users")->where( ["id"=>$res["id"] ] )->update( ["val"=>$val,"updateuser"=>$this->_adminname,"updatetime"=>date("Y-m-d H:i:s") ] );
	   }
	   if($r!==false)
	   {
		   exit("ok");
	   }
	   exit("fail");
	}
	
	public function ad_download($start_date="",$end_date="",$type="day"){
		$appid = getcache("select_app");
		$xlsData =[];
		$data =[];
		if($start_date && $end_date)
		{
			$dates = getDateFromRange($start_date,$end_date);
			foreach($dates as $v)
			{
				if($type=="day")
				{
					$row = $this->getadtypedata($appid,$v,$v);
					$row = array_map(function(&$a)use($v){
					$a["date"] = $v;
					$a["avg_show"] = adcalculate($a['impressions'],$a['activenum'],"3");
					$a["ecpm"] = adcalculate($a['revenue'],$a['impressions'],"2");
					$a["avg_click"] = adcalculate($a['clicks'],$a['activenum'],"3");
					return $a;
				},$row);
				
				}else{
					$arr = $this->getWeekRange($v,0);
					$key = implode("-",$arr);

					if(!isset($data[$key]))
					{
						$row = $this->getadtypedata($appid,$arr[0],$arr[1]);
						$row = array_map(function(&$a)use($key){
						$a["date"] = $key;
						$a["avg_show"] = adcalculate($a['impressions'],$a['activenum'],"3");
						$a["ecpm"] = adcalculate($a['revenue'],$a['impressions'],"2");
						$a["avg_click"] = adcalculate($a['clicks'],$a['activenum'],"3");
						return $a;
					 },$row);
					}else{
						continue;
					}
				}
                $xlsData=array_merge($xlsData,$row);				
			}
			$xlsCell  = array(
				array("date",'日期'),
				array("name",'名称'),
				array("impressions",'展示'),				
				array('clicks','点击'),
				array('activenum','日活'),
				array('avg_show','人均展示'),
				array('avg_click','人均点击'),
				array('ecpm','eCPM'),
				array('revenue','收益')
			 );
			$Index = new E(request());
			$name ="广告{$type}数据下载".date("YmdHis");
			echo $Index->exportExcel($name,$xlsCell,$xlsData,$name,$name);exit;
		}
	}
	
	function getWeekRange($date, $start=0){

    // 将日期转时间戳
    $dt = new \DateTime($date);
    $timestamp = $dt->format('U');

    // 获取日期是周几
    $day = (new \DateTime('@'.$timestamp))->format('w');

    // 计算开始日期
    if($day>=$start){
        $startdate_timestamp = mktime(0,0,0,date('m',$timestamp),date('d',$timestamp)-($day-$start),date('Y',$timestamp));
    }elseif($day<$start){
        $startdate_timestamp = mktime(0,0,0,date('m',$timestamp),date('d',$timestamp)-7+$start-$day,date('Y',$timestamp));
    }

    // 结束日期=开始日期+6
    $enddate_timestamp = mktime(0,0,0,date('m',$startdate_timestamp),date('d',$startdate_timestamp)+6,date('Y',$startdate_timestamp));

    $startdate = (new \DateTime('@'.$startdate_timestamp))->format('Y-m-d');
    $enddate = (new \DateTime('@'.$enddate_timestamp))->format('Y-m-d');

    return array($startdate, $enddate);
  }
}
