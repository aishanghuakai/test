<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
  //广告收益
  //平台类型1 Mob 2 Unity 3 applovin 4Vungle 5 admob 6 facebook
class Adgain extends Base
{
   public function index()
   {
	    $ids =getmylikedata();
		$where="1=1 and id in(".$ids.")";
		$apps= Db::name("app")->field("id,app_name,platform")->where($where)->order("FIELD(id,{$ids})")->select();		
		return $this->fetch('index',["apps"=>$apps ] );
   }

   public function getsearchdata($appids="",$type="",$start_date="",$end_date="")
	{
		$appids = rtrim($appids,",");
		$search_app=[];
		$total_spend =[["request"=>"0","impressions"=>"0","clicks"=>"0","revenue"=>"0.0"]];
		if( !empty($appids) ){
			
			if( $type=="country" )
			{
				
				return $this->getordercountry($appids,$start_date,$end_date);exit;
			}
			if( $type=="date" )
			{
				
				return $this->getdatedata($appids,$start_date,$end_date);exit;
			}
			if( $appids && $type=="adtype" )
			{
				$search_app = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
				foreach( $search_app as &$s )
				{
					$sum_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$s["id"]} and date>='{$start_date}' and date<='{$end_date}'";
					$s["sum_data"] = Db::query($sum_sql);
					$s["platform_data"] = $this->getplatformdata($s["id"],$start_date,$end_date);
				}
			}
			$total_spend = $this->totalrevenue($appids,$start_date,$end_date);
		}
		return $this->fetch('getsearchdata',[ "data"=>$search_app,"total_spend"=>$total_spend[0] ] );
	}
	
	private function getplatformdata($app_id="",$start_date, $end_date)
	{
		$sql = "select sys_app_id,platform,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$app_id} and date>='{$start_date}' and date<='{$end_date}' group by sys_app_id,platform";
		$data = Db::query($sql);
        if( !empty($data) )
		{
			foreach( $data as $kk=>&$vv )
			{
				 $cam_sql = "select adtype,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$vv["sys_app_id"]} and platform={$vv["platform"]} and date>='{$start_date}' and date<='{$end_date}' group by adtype";
				 $vv["adtypes"] = Db::query($cam_sql);
			}
		}
       return $data;		
	}
	
	public function getordercountry($appids,$start_date,$end_date)
	{
		$search_app=[];
	   if( $appids ){
			$search_app = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
			if( !empty( $search_app ))
			{
				foreach( $search_app as &$v )
				{
					$sum_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$v['id']} and date>='{$start_date}' and date<='{$end_date}'";
					$v["sum_data"] = Db::query($sum_sql);
					$v["country_data"] = $this->getcountrysdata($v["id"],$start_date,$end_date);
				}
			}
	    }
       $total_spend = $this->totalrevenue($appids,$start_date,$end_date);		
		return $this->fetch('getordercountry',[ "data"=>$search_app,"total_spend"=>isset($total_spend[0])?$total_spend[0]:[] ] );
	}
	
	private function getcountrysdata($id,$start_date, $end_date)
	{
		$country_sql = "select country,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$id} and date>='{$start_date}' and date<='{$end_date}' group by country";
		$res = Db::query($country_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vvv )
			{
				 $country_sql = "select platform,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$id} and date>='{$start_date}' and date<='{$end_date}' and country='{$vvv['country']}' group by platform";
				 $vvv["platform_data"] = Db::query($country_sql);
			}
		}
		return $res;
	}
	
	//收益汇总
	private function totalrevenue($appids,$start_date,$end_date)
	{
		$total_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}'";
		return Db::query($total_sql);
	}	
	
	public function getdatedata($appids,$start_date="",$end_date="")
	{
		$search_app=[];
		
		if( $appids ){
			$search_app = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
			if( !empty( $search_app ))
			{
				foreach( $search_app as &$v )
				{
					$sum_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$v['id']} and date>='{$start_date}' and date<='{$end_date}'";
					$v["sum_data"] = Db::query($sum_sql);
					$v["date_data"] = $this->getdateplatform($v["id"],$start_date,$end_date);
				}
			}
		}
		 $total_spend = $this->totalrevenue($appids,$start_date,$end_date);	
		return $this->fetch('getdatedata',[ "data"=>$search_app,"total_spend"=>isset($total_spend[0])?$total_spend[0]:[] ] );
	}
	
	private function getdateplatform($id,$start_date="",$end_date="")
	{
		$res=[];
		$range_date = getDateFromRange($start_date, $end_date);
		foreach( $range_date as $key=>$vvv )
		{
			 $res[$key]["date"] = $vvv;
			 $d_sql ="select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$id} and date='{$vvv}'";
			 $d = Db::query($d_sql);
			 $res[$key]["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			 $res[$key]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $res[$key]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
			 $res[$key]["request"] = isset($d[0]["request"])?$d[0]["request"]:"0";
			 $date_sql = "select platform,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$id} and date='{$vvv}' group by platform";
			 $res[$key]["platform_data"] = Db::query($date_sql);
		}
		return $res;
	}
	
	public function getcontentdata($appids="",$type="",$date="")
	{
		$appids = rtrim($appids,",");
		$x_date="";
		$revenue="";
		$impressions="";
		$clicks="";
		$ecpm="";
		$ctr="";
		$list="";
		if( !empty($appids) ){
		if( $type=="date" )
			{				
				$dates = $this->getdaterange($date);			
				foreach( $dates as $key=>&$v )
				{					
					$j_revenue="0.0";
					$j_impressions="0";
					$j_clicks="0";
					$j_request="0";
					$sum_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,ROUND(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date='{$v}'";
					$r = Db::query($sum_sql);
					if( !empty($r) )
					{
						$j_revenue = !empty($r["0"]["revenue"])?$r["0"]["revenue"]:"0.00";
						$j_impressions = !empty($r["0"]["impressions"])?$r["0"]["impressions"]:"0.00";
						$j_clicks = !empty($r["0"]["clicks"])?$r["0"]["clicks"]:"0.00";
						$j_request = !empty($r["0"]["request"])?$r["0"]["request"]:"0.00";
					}
					$x_date.=$v.",";
					$revenue.=$j_revenue.",";
					$impressions.=$j_impressions.",";
					$clicks.=$j_clicks.",";
					$pm = adcalculate($j_revenue,$j_impressions,"2");
					$ecpm.=$pm.",";
					$ct = adcalculate($j_clicks,$j_impressions);
					$ctr.=$ct.",";
					$ecpc =adcalculate($j_revenue,$j_clicks,"1"); 
					$list.=" <tr><td>{$v}</td><td>\${$j_revenue}</td><td>{$j_request}</td><td>{$j_impressions}</td><td>{$j_clicks}</td><td>{$ct}</td><td>{$ecpc}</td><td>{$pm}</td></tr>";
				}
			}
		}	
		return ["x_date"=>rtrim($x_date,","),"revenue"=>rtrim($revenue,","),"impressions"=>rtrim($impressions,","),"clicks"=>rtrim($clicks,","),"ecpm"=>rtrim($ecpm,","),"ctr"=>rtrim($ctr,","),"content_list"=>rtrim($list,",") ];	
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
	
	
	private function getviewdate($date)
	{
		$where="";
		switch($date)
		{
			case "last":
			   $time = date("Y-m-d",strtotime("-1 day"));
			   $where ="date='{$time}'";
			break;
            case "oneweek":
               $time = date("Y-m-d",strtotime("-7 day"));
			   $where ="date>='{$time}'";
            break;
            case "twoweek":
               $time = date("Y-m-d",strtotime("-14 day"));
			   $where ="date>='{$time}'";
            break; 			
		}
		return $where;
	}
	
	public function customize($appids="",$customizefield="",$start_date="",$end_date="")
	{
		$appids = rtrim($appids,",");
		$search_app=[];
		$total_spend =[["request"=>"0","impressions"=>"0","clicks"=>"0","revenue"=>"0.0"]];
		if( !empty($appids) ){
			
			if( count($customizefield)==1 )
			{
				list($a) =$customizefield;
				$function = "get".$a."data";
				$search_app = $this->$function($appids,$start_date,$end_date);
				
				$html ="customize";
			}elseif( count($customizefield)==2 )
			{
				list($a,$b) =$customizefield;
				$one_function = "get".$a."data";
				$search_app = $this->$one_function($appids,$start_date,$end_date);
				
				foreach( $search_app as &$vvv )
				{
					switch( $a )
					{
						case "app":
						   $where =" and sys_app_id='{$vvv['id']}'";
						 break;
                        case "channel":
                            $where =" and platform='{$vvv['platform']}'";
						 break;
                        case "adtype":
                            $where =" and adtype='{$vvv['adtype']}'";
						 break;
						case "country":
                            $where =" and country='{$vvv['country']}'";	
						 break;
						 case "time":
                            $where =" and date='{$vvv['date']}'";	
						 break;
				    }
					$two_function = "get".$b."data";
					$vvv["child"] = $this->$two_function($appids,$start_date,$end_date,$where);
				}
				$html ="customizetwo";
			}elseif(  count($customizefield)==3 )
			{
				list($a,$b,$c) =$customizefield;
				$one_function = "get".$a."data";
				$search_app = $this->$one_function($appids,$start_date,$end_date);
				foreach( $search_app as &$vvv )
				{
					switch( $a )
					{
						case "app":
						   $where =" and sys_app_id='{$vvv['id']}'";
						 break;
                        case "channel":
                            $where =" and platform='{$vvv['platform']}'";
						 break;
                        case "adtype":
                            $where =" and adtype='{$vvv['adtype']}'";
						break;	
						case "country":
                            $where =" and country='{$vvv['country']}'";	
						 break;							 
						case "time":
                            $where =" and date='{$vvv['date']}'";	
						 break;	
				    }
					$two_function = "get".$b."data";
					$res = $this->$two_function($appids,$start_date,$end_date,$where);
					
					foreach( $res as &$m )
					{
						switch( $b )
						{
							case "app":
							   $where_two =" and sys_app_id='{$m['id']}'";
							 break;
							case "channel":
								$where_two =" and platform='{$m['platform']}'";
							 break;
							case "adtype":
								$where_two =" and adtype='{$m['adtype']}'";
							 break;	
							case "country":
								$where_two =" and country='{$m['country']}'";	
							 break;
                            case "time":
                                $where_two =" and date='{$m['date']}'";	
						     break;							 
							
						}
						$three_function = "get".$c."data";
					    $res_two = $this->$three_function($appids,$start_date,$end_date,$where_two);
					    $m["twochild"] = $res_two;
					}
					$vvv["child"] = $res;
				}
				$html ="customizethree";
			}			
			$total_spend = $this->totalrevenue($appids,$start_date,$end_date);
		}
		return $this->fetch($html,[ "data"=>$search_app,"total_spend"=>$total_spend[0] ] );
	}
	
	//广告类型
	private function getadtypedata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		$adtype_sql = "select adtype,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where} group by adtype";
		$res =  Db::query($adtype_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				$vv["name"] = getFullADType( $vv["adtype"] );
			}
		}
		return $res;
	}
	//APP
	private function getappdata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		$search_app = Db::name("app")->field("id,app_name as name,platform")->where("id in({$appids})")->select();
		foreach( $search_app as &$s )
		{
			$app_sql = "select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$s["id"]} and date>='{$start_date}' and date<='{$end_date}' {$where}";
			$r = Db::query($app_sql);			
			$s["request"] = isset($r["0"]["request"])?$r["0"]["request"]:"0";
			$s["impressions"] = isset($r["0"]["impressions"])?$r["0"]["impressions"]:"0";
			$s["clicks"] =isset($r["0"]["clicks"])?$r["0"]["clicks"]:"0";
			$s["revenue"] = isset($r["0"]["revenue"])?$r["0"]["revenue"]:"0.0";
			if($s['platform']=='android')
			{
				$s["name"] = $s["name"].'<i title="android" style="color:#f0ad4e;" class="fa fa-android"></i>';
			}elseif( $s['platform']=='ios' )
			{
				$s["name"] = $s["name"].'<i  class="fa fa-apple"></i>';
			}
		}
		return $search_app;
	}
	
	//广告国家
	private function getcountrydata($appids,$start_date="",$end_date="",$where="")
	{
		if(!$where)
		{
			$where=" and 1=1";
		}
		$country_sql = "select country,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where} group by country";
		$res =  Db::query($country_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				$vv["name"] =getcountryname( $vv["country"] )."({$vv['country']})";
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
		$res=[];
		foreach( $range_date as $key=>$vvv )
		{
			 $res[$key]["name"] = $vvv;
			 $res[$key]["date"] = $vvv;
			 $d_sql ="select sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date='{$vvv}' {$where}";
			 $d = Db::query($d_sql);
			 $res[$key]["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			 $res[$key]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $res[$key]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
			 $res[$key]["request"] = isset($d[0]["request"])?$d[0]["request"]:"0";
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
		$channel_sql = "select platform,sum(request) as request,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}' {$where} group by platform";
		$res =  Db::query($channel_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vv )
			{
				$vv["name"] ='<img style="height:25px;width:40px;" src='.getplatformimg($vv["platform"]).' >&nbsp;'.getplatform($vv["platform"]);
			}
		}
		return $res;
	}
	
	//关联页面
	public function relateapp()
	{
		$res = Db::name("adcash_data")->field("id,app_id,app_name,app_platform,platform")->where("1=1 and sys_app_id=''")->group("app_id,platform")->select();
        $apps= Db::name("app")->field("id,app_name,platform")->select();		
		return $this->fetch('relateapp',["res"=>$res,"apps"=>$apps]);
	}
	
	public function relate_save($object="",$app_id="")
	{ 
	 
	  if( !empty($object) )
	  {
		  foreach( $object as &$v )
		  {
			 $f = Db::name("related_app")->where( ["app_id"=>$v["app_id"],"platform"=>$v["platform"],"type"=>1 ] )->find();
			 if( empty($f) )
			 {
				$v["type"]=1;
				$r = Db::name("related_app")->insert($v);
				if($r!==false)
				{
					Db::name("adcash_data")->where(["app_id"=>$v["app_id"],"platform"=>$v["platform"] ])->update( ["sys_app_id"=>$v["related_appid"] ] );
				}
			 }
		  }
		  exit("ok");
	  }
	  exit("fail");
	}
	
	public function reten($app_id="",$date="")
	{
		$apps= Db::name("app")->field("id,app_name,platform")->select();
		$r = Db::name("day_reten")->where(["app_id"=>$app_id,"date"=>$date ])->find();
		$val="";
		if(!empty($r) )
		{
			$val=$r["val"];
		}
		return $this->fetch('reten',[ "apps"=>$apps,"app_id"=>$app_id,"date"=>$date,"val"=>$val ]);
	}
	
	public function reten_save($app_id="",$date="",$val="")
	{
	   $res = Db::name("day_reten")->where( [ "app_id"=>$app_id,"date"=>$date ] )->find();
	   if( empty($res) )
	   {
		  $r = Db::name("day_reten")->insert( ["app_id"=>$app_id,"val"=>$val,"date"=>$date,"updateuser"=>$this->_adminname,"updatetime"=>date("Y-m-d H:i:s") ] );
	      
	   }else{
		  $r = Db::name("day_reten")->where( ["id"=>$res["id"] ] )->update( ["val"=>$val,"updateuser"=>$this->_adminname,"updatetime"=>date("Y-m-d H:i:s") ] );
	   }
	   if($r!==false)
	   {
		   exit("ok");
	   }
	   exit("fail");
	}
	
	
}
