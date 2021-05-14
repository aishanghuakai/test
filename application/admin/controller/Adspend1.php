<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use app\admin\controller\Consu;
   
   //广告推广数据
class Adspend extends Base
{
    public function advertising()
	{			
		$ids =getmylikedata();
		$where="1=1 and id in(".$ids.")";
		$apps= Db::name("app")->field("id,app_name,platform")->where($where)->order("FIELD(id,{$ids})")->select();		
		return $this->fetch('advertising',["apps"=>$apps ] );
	}
	public function getsearchdata($appids="",$type="",$start_date="",$end_date="")
	{
		$appids = rtrim($appids,",");
		$search_app=[];
		if( $type=="country" )
		{
			
			return $this->getordercountry($appids,$start_date,$end_date);exit;
		}
		if( $type=="date" )
		{
			
			return $this->getdatedata($appids,$start_date,$end_date);exit;
		}
		if( $appids && in_array($type,["app","campaign"]) )
		{
			$search_app = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
			foreach( $search_app as &$s )
			{
				$sum_sql = "select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$s["id"]} and date>='{$start_date}' and date<='{$end_date}'";
				$s["sum_data"] = Db::query($sum_sql);
				$s["platform_data"] = $this->getplatformdata($s["id"],$start_date,$end_date);
			}
		}
		$total_spend = $this->totalspend($appids,$start_date,$end_date);
		return $this->fetch('getsearchdata',[ "data"=>$search_app,"total_spend"=>isset($total_spend[0])?$total_spend[0]:[] ] );
	}
	
	//汇总花费
	private function totalspend($appids,$start_date,$end_date)
	{
		$total_sql = "select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id in({$appids}) and date>='{$start_date}' and date<='{$end_date}'";
		return Db::query($total_sql);
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
					$sum_sql = "select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$v['id']} and date>='{$start_date}' and date<='{$end_date}'";
					$v["sum_data"] = Db::query($sum_sql);
					$v["country_data"] = $this->getcountrydata($v["id"],$start_date,$end_date);
				}
			}
	    }
        $total_spend = $this->totalspend($appids,$start_date,$end_date);		
		return $this->fetch('getordercountry',[ "data"=>$search_app,"total_spend"=>isset($total_spend[0])?$total_spend[0]:[] ] );
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
					$sum_sql = "select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$v['id']} and date>='{$start_date}' and date<='{$end_date}'";
					$v["sum_data"] = Db::query($sum_sql);
					$v["date_data"] = $this->getdateplatform($v["id"],$start_date,$end_date);
				}
			}
		}
        $total_spend = $this->totalspend($appids,$start_date,$end_date);		
		return $this->fetch('getdatedata',[ "data"=>$search_app,"total_spend"=>isset($total_spend[0])?$total_spend[0]:[] ] );
	}
	private function getdateplatform($id,$start_date="",$end_date="")
	{
		$res=[];
		$range_date = getDateFromRange($start_date, $end_date);
		foreach( $range_date as $key=>$vvv )
		{
			 $res[$key]["date"] = $vvv;
			 $d_sql ="select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$id} and date='{$vvv}'";
			 $d = Db::query($d_sql);
			 $res[$key]["spend"] = isset($d[0]["spend"])?$d[0]["spend"]:"0";
			 $res[$key]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $res[$key]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
			 $res[$key]["installs"] = isset($d[0]["installs"])?$d[0]["installs"]:"0";
			 $date_sql = "select platform_type,sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$id} and date='{$vvv}' group by platform_type";
			 $res[$key]["platform_data"] = Db::query($date_sql);
		}
		return $res;
	}
	
	private function getcountrydata($id,$start_date, $end_date)
	{
		$country_sql = "select country,sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$id} and date>='{$start_date}' and date<='{$end_date}' group by country";
		$res = Db::query($country_sql);
		if( !empty($res) )
		{
			foreach( $res as &$vvv )
			{
				 $country_sql = "select platform_type,sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,sum(spend) as spend from hellowd_adspend_data where app_id={$id} and date>='{$start_date}' and date<='{$end_date}' and country='{$vvv['country']}' group by platform_type";
				 $vvv["platform_data"] = Db::query($country_sql);
			}
		}
		return $res;
	}
	
	private function getplatformdata($app_id="",$start_date, $end_date)
	{
		$sql = "select app_id,platform_type,sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$app_id} and date>='{$start_date}' and date<='{$end_date}' group by app_id,platform_type";
		$data = Db::query($sql);
        if( !empty($data) )
		{
			foreach( $data as $kk=>&$vv )
			{
				 $cam_sql = "select campaign_id,campaign_name,sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,FORMAT(sum(spend),2) as spend from hellowd_adspend_data where app_id={$vv["app_id"]} and platform_type={$vv["platform_type"]} and date>='{$start_date}' and date<='{$end_date}' group by campaign_id";
				 $vv["campaign"] = Db::query($cam_sql);
			}
		}
       return $data;		
	}
	
	//关联页面
	public function relateapp()
	{
		$res = Db::name("adspend_data")->field("id,campaign_id,campaign_name,platform_type,platform")->where("1=1 and app_id=''")->group("campaign_id,platform_type")->select();
        $apps= Db::name("app")->field("id,app_name,platform")->select();		
		return $this->fetch('relateapp',["res"=>$res,"apps"=>$apps]);
	}
	
	//关联保存
	public function relate_save($object="",$app_id="")
	{ 
	 
	  if( !empty($object) )
	  {
		  foreach( $object as &$v )
		  {
			 $f = Db::name("related_app")->where( ["app_id"=>$v["app_id"],"platform"=>$v["platform"],"type"=>2 ] )->find();
			 if( empty($f) )
			 {
				$v["type"]=2;
				$r = Db::name("related_app")->insert($v);
				if($r!==false)
				{
					Db::name("adspend_data")->where(["campaign_id"=>$v["app_id"],"platform_type"=>$v["platform"] ])->update( ["app_id"=>$v["related_appid"] ] );
				}
			 }
		  }
		  exit("ok");
	  }
	  exit("fail");
	}
	
	//图报数据
	public function getcontentdata($appids="",$type="",$date="")
	{
		$echats_name ="";
		$echats_value ="";
		$list="";
		if( $appids )
		{
			$appids = rtrim($appids,",");
			$where = $this->getviewdate($date);
			if( $type=="app" )
			{
				$app_list = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
				if( !empty( $app_list ))
				{
					$spend_data = Db::query( "select sum(spend) as spend from hellowd_adspend_data where app_id in ($appids) and {$where}" );
					
					if( !empty($spend_data) )
					{
						$total_spend = $spend_data[0]["spend"];
					}else{
						$total_spend="0";
					}
					foreach( $app_list as $v )
					{
						$v["spend_rate"] ="0";
						$sum_sql = "select sum(spend) as spend from hellowd_adspend_data where app_id={$v['id']} and {$where}";
						$r = Db::query($sum_sql);
						if( !empty($r) && $total_spend>0  )
						{
							$v["spend_rate"] = round( $r[0]["spend"]/$total_spend,2 )*100;
						}
						$echats_name.=$v["app_name"].",";
						$echats_value.=$v["spend_rate"].",";
						$list.=" <tr><td>{$v["app_name"]}</td><td>{$v["spend_rate"]}%</td></tr>";
					}
				}
			}elseif( $type=="country" ){
				$app_list = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
				if( !empty( $app_list ))
				{
					$spend_data = Db::query( "select sum(spend) as spend from hellowd_adspend_data where app_id in ($appids) and {$where}" );
					
					if( !empty($spend_data) )
					{
						$total_spend = $spend_data[0]["spend"];
					}else{
						$total_spend="0";
					}
					$country_sql = "select country,sum(spend) as spend from hellowd_adspend_data where app_id in ($appids) and {$where} group by country";
					$res = Db::query($country_sql);
					if( !empty($res) )
					{
						foreach( $res as $v )
						{
							$v["spend_rate"] ="0";
						
							if(  $total_spend>0  )
							{
								$v["spend_rate"] = round( $v["spend"]/$total_spend,2 )*100;
							}
							$echats_name.=$v["country"].",";
							$echats_value.=$v["spend_rate"].",";
							$c_country = getcountryname($v["country"]);
						$list.=" <tr><td>{$v["country"]}<i style='font-size:10px;'>({$c_country})</i></td><td>{$v["spend_rate"]}%</td></tr>";
						}
					}
				}
			}elseif($type=="channel")
			{
				$app_list = Db::name("app")->field("id,app_name,platform")->where("id in({$appids})")->select();
				if( !empty( $app_list ))
				{
					$spend_data = Db::query( "select sum(spend) as spend from hellowd_adspend_data where app_id in ($appids) and {$where}" );
					
					if( !empty($spend_data) )
					{
						$total_spend = $spend_data[0]["spend"];
					}else{
						$total_spend="0";
					}
					$res = [ "1","2","3","4","5","6"];
					if( !empty($res) )
					{
						$echats_value = count($res);
						$out_name=[];
						foreach( $res as $key=>&$v )
						{
							$name = getplatform($v);
							$channel_sql = "select sum(spend) as spend from hellowd_adspend_data where app_id in ($appids) and platform_type={$v} and {$where}";
					        $r = Db::query($channel_sql);
							$spend_rate ="0.00";
						
							if( !empty($r) && $total_spend>0  )
							{
								$spend_rate =$r[0]["spend"]>0?$r[0]["spend"]:"0.00"; //round( $r[0]["spend"]/$total_spend,2 )*100;
							}							
							$out_name[$key]=[ "name"=>$name,"value"=>$spend_rate ];							
						    $list.=" <tr><td>{$name}</td><td>\${$spend_rate}</td></tr>";
						}
					}
				}
				 return ["echats_name"=>$out_name,"echats_value"=>rtrim($echats_value,","),"content_list"=>rtrim($list,",") ];		
			}
			
		}
        return ["echats_name"=>rtrim($echats_name,","),"echats_value"=>rtrim($echats_value,","),"content_list"=>rtrim($list,",") ];		
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
}
