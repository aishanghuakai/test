<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use app\api\controller\Adcash;
  //广告收益
  //平台类型1 Mob 2 Unity 3 applovin 4Vungle 5 admob 6 facebook
class Adgain extends Base
{
   public function index($appid="",$type="day",$start_date="",$end_date="",$index="three")
   {
	    if( $appid=="" )
		{
			$appid = getcache("select_app");
			
		}else{
			 setcache("select_app",$appid);
		}
		
		if( $start_date=="" || $end_date=="" ){
			switch($index)
			{			
				case "oneweek":
				   $start_date = date("Y-m-d",strtotime("-8 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day")); 				  
				break;
				case "twoweek":
				   $start_date = date("Y-m-d",strtotime("-15 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day"));
				   break;
				case "three":
				   $start_date = date("Y-m-d",strtotime("-4 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day"));			    
				break; 			
			 }
           }
		
		$oneapp= Db::name("app")->field("id,app_name")->find($appid);
        $data = $this->getdata($appid,$type,$start_date,$end_date,$oneapp["app_name"]);	
		$chats =$this->viewchats($appid,$start_date,$end_date);
		
		return $this->fetch('index',["appid"=>$appid,"index"=>$index,"type"=>$type,"start_date"=>$start_date,"end_date"=>$end_date,"data"=>$data,"chats"=>$chats,"sumdata"=>$this->sumary($appid,$start_date,$end_date) ] );
   }
   
   public function upltv($appid="",$start_date="",$end_date="")
   {
	    if( $appid=="" )
		{
			$appid = getcache("select_app");
		}
		 setcache("select_app",$appid);
		$country = admincountry();
		
		$result =[];
		$oneapp= Db::name("app")->field("id,app_name,platform")->find($appid);
		$country = admincountry();
		if( $start_date=="" )
			{
				$start_date = date("Y-m-d");
			}
		foreach( $country as $kk=>$vvv )
		{
			$where ="";
			if($kk!="all")
			{
				$where = " and country='{$kk}'";
			}
			
            $revenue_sql ="select sum(revenue) as revenue from hellowd_upltv_data where app_id={$appid}  and date='{$start_date}' and country='{$kk}'";			
            $revenue_data =Db::query($revenue_sql);			
			
            $result[$kk]["revenue"] =isset($revenue_data["0"]["revenue"])?$revenue_data["0"]["revenue"]:"0";	
         
            $result[$kk]["date"] = $start_date;
            $result[$kk]["name"] = $vvv;
            $result[$kk]["country"] =$kk;			
		}
		return $this->fetch('upltv',[ "data"=>$result,"start_date"=>$start_date,"end_date"=>$end_date,"oneapp"=>$oneapp]);
   }
   
   public function day_save($app_id="",$date="",$val="",$country="")
	{
	   $res = Db::name("upltv_data")->where( [ "app_id"=>$app_id,"date"=>$date,"country"=>$country ] )->find();
	   if( empty($res) )
	   {
		  $r = Db::name("upltv_data")->insert( ["app_id"=>$app_id,"revenue"=>$val,"country"=>$country,"date"=>$date ] );
	      
	   }else{
		  $r = Db::name("upltv_data")->where( ["id"=>$res["id"] ] )->update( ["revenue"=>$val ] );
	   }
	   if($r!==false)
	   {
		   exit("ok");
	   }
	   exit("fail");
	}
   
   public function refresh_data()
   {
	   $all = getplatformimg("all");
	   $this->assign("all",$all);
	   return $this->fetch();
   }
   
   public function updatedata($start_date="",$end_date="",$type="")
   {
	   if( $start_date=="" || $end_date=="" )
		{
			$start_date = date("Y-m-d");
			$end_date =  date("Y-m-d");
		}
	   $tom = date("Y-m-d",strtotime('+1 day',strtotime($start_date)));
	   $adcash = new Adcash();
	   switch($type)
	   {
		    case "1":
		     $adcash->getMob($start_date,$end_date);exit;
		     break;
			case "2":
		     $adcash->getUnity($start_date,$tom);exit;
		     break;
			 case "3":
		     $adcash->getApplovin($start_date,$end_date);exit;
		     break;
			 case "4":
		     $adcash->getVung($start_date,$end_date);exit;
		     break;
			 case "5":
		     $adcash->getMob($start_date,$end_date);exit;
		     break;
			 case "6":
		      $adcash->getfacebook($start_date,$end_date);
			  $adcash->tankriosfacebook($start_date,$end_date);
	          $adcash->hexlandfacebook($start_date,$end_date);
	          $adcash->rushballsfacebook($start_date,$end_date);
	          $adcash->getonefacebook($start_date,$end_date);exit;
		     break;
			 case "7":
		     $adcash->getironSource($start_date,$end_date);exit;
		     break;
	   }
	   
	   echo "ok";
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
    
	private function sumary($appid,$start_date,$end_date)
	{
		 $r=[];
		 $sum_sql = "select sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$appid} and date>='{$start_date}' and date<='{$end_date}'";
		 $d= Db::query($sum_sql);
		 $r["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
		 $r["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
		 $r["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
		 $upltv_adtype_data = getupltvfacebook($appid,$start_date,$end_date,"all");
		 $r["revenue"] =$r["revenue"]-$upltv_adtype_data["revenue"];
		 $r["impressions"] =$r["impressions"]-$upltv_adtype_data["impression"];
		 $r["clicks"] =$r["clicks"]-$upltv_adtype_data["click"];
		 $r["ecpm"] = $r["impressions"]<=0?0:number_format($r["revenue"]*1000/$r["impressions"],2);
		 return $r;
	}
    
	private function getdata($appid="",$type="",$start_date="",$end_date="",$name="")
	{
		$where ="";
		switch($type)
		{
			case "channel":
			  $where ="platform";			  
			  break;
			case "adtype":
			  $where ="adtype";			  
			  break;
            case "day":
			   return $this->getdatedata($appid,$start_date,$end_date,$name);
              break;			
		}
		
	    $sql = "select {$where} as type, sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' group by {$where}";
		$res= Db::query($sql);
		if( !empty($res) )
		{
			foreach( $res as $kk=>&$vvv )
			{
				if( $type=="channel" ){
					$vvv["name"] ='<img style="height:14px;width:14px;" src='.getplatformimg($vvv["type"]).' >&nbsp;'.getplatform($vvv["type"]);
				}elseif( $type=="adtype" )
				{
					$vvv["name"] = getFullADType( $vvv["type"] );
				}
				if( $type=="adtype" )
				{
					$upltv_adtype_data = getupltvfacebook($appid,$start_date,$end_date,"all",$vvv["type"]);
					$vvv["revenue"] =($vvv["revenue"]-$upltv_adtype_data["revenue"])<0?0:$vvv["revenue"]-$upltv_adtype_data["revenue"];
				    $vvv["impressions"] =$vvv["impressions"]-$upltv_adtype_data["impression"]<0?0:$vvv["impressions"]-$upltv_adtype_data["impression"];
					$vvv["clicks"] =$vvv["clicks"]-$upltv_adtype_data["click"]<0?0:$vvv["clicks"]-$upltv_adtype_data["click"];
				}
				if( $vvv["type"]=="30" )
				{
					$upltv_adtype_data = getupltvfacebook($appid,$start_date,$end_date,"all");
					$vvv["revenue"] =($vvv["revenue"]-$upltv_adtype_data["revenue"])<0?0:$vvv["revenue"]-$upltv_adtype_data["revenue"];
				    $vvv["impressions"] =$vvv["impressions"]-$upltv_adtype_data["impression"]<0?0:$vvv["impressions"]-$upltv_adtype_data["impression"];
					$vvv["clicks"] =$vvv["clicks"]-$upltv_adtype_data["click"]<0?0:$vvv["clicks"]-$upltv_adtype_data["click"];
				}
				
				$vvv["date"] = "----";
				$vvv["ecpm"] = $vvv["impressions"]<=0?0:number_format($vvv["revenue"]*1000/$vvv["impressions"],2);
			}
		}
		return $res;
	}
	
	private function getdatedata($appid="",$start_date="",$end_date="",$name)
	{
		$range_date = getDateFromRange($start_date,$end_date);
		$res=[];
		foreach( $range_date as $key=>$vvv )
		{
			 $res[$key]["name"] =$name;
			 $res[$key]["date"] = $vvv;
			 $d_sql ="select sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and date='{$vvv}'";
			 $d = Db::query($d_sql);
			 $res[$key]["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			 $res[$key]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $res[$key]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
			 $upltv_data = getupltvfacebook($appid,$vvv,$vvv,"all");
		     $res[$key]["impressions"] = ($res[$key]["impressions"]-$upltv_data["impression"])<0?0:$res[$key]["impressions"]-$upltv_data["impression"];
		     $res[$key]["revenue"] = ($res[$key]["revenue"]-$upltv_data["revenue"])<0?0:$res[$key]["revenue"]-$upltv_data["revenue"];
             $res[$key]["clicks"] = ($res[$key]["clicks"]-$upltv_data["click"])<0?0:$res[$key]["clicks"]-$upltv_data["click"];			 
			 $res[$key]["ecpm"] = $res[$key]["impressions"]<=0?0:number_format($res[$key]["revenue"]*1000/$res[$key]["impressions"],2);
		}
		return $res;
	}
	
	private function viewchats($appid="",$start_date="",$end_date="",$platform="")
	{
		$range_date = getDateFromRange($start_date,$end_date);
		$where="";
		if( $platform!="" && $platform!="all" )
		{
			$platform =" and platform={$platform}";
		}
		$date ="";
		$revenue="";
		$impressions="";
		$clicks="";
		$ecpm="";
		foreach( $range_date as $key=>$vvv )
		{
			
			
			 $d_sql ="select sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and date='{$vvv}' {$where}";
			 $d = Db::query($d_sql);
			 $c_revenue = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			 $c_impressions = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
			 $c_clicks = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
			 $c_ecpm = $c_impressions<=0?0:number_format($c_revenue*1000/$c_impressions,2);
			 $date.="'{$vvv}',";
			 $revenue.="{$c_revenue},";
			 $impressions.="{$c_impressions},";
			 $clicks.="{$c_clicks},";
			 $ecpm.= "{$c_ecpm},";
		}
		return ["date"=>rtrim($date,','),"revenue"=>rtrim($revenue,','),"impressions"=>rtrim($impressions,','),"clicks"=>rtrim($clicks,','),"ecpm"=>rtrim($ecpm,',') ];
	}
	
	//关联页面
	public function relateapp()
	{
		$res = Db::name("adcash_data")->field("id,app_id,app_name,app_platform,platform")->where("1=1 and sys_app_id=''")->group("app_id,platform")->select();
        $apps= Db::name("app")->field("id,app_name,platform,app_base_id")->order("app_name asc")->select();
		if( !empty($apps) )
		{
			foreach($apps as &$vv)
			{
				if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
					}
				}
			}
		}
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
	
	//类型分析
	public function analysis($appid="",$tag="impressions",$start_date="",$end_date="",$index="three")
	{
		if( $appid=="" )
		{
			$appid = getcache("select_app");
			
		}else{
			 setcache("select_app",$appid);
		}
		
		$oneapp= Db::name("app")->field("id,app_name")->find($appid);
		$res = $this->getchanneladtype($appid,$start_date,$end_date,"1,2,3,4,5,6,7",$index,$tag,"channel");
		$resadtype = $this->getchanneladtype($appid,$start_date,$end_date,"'int','rew','nat','ban','no'",$index,$tag,"adtype");
		
		$this->assign("appid",$appid);
		$this->assign("index",$index);
		$this->assign("res",$res);
		$this->assign("resadtype",$resadtype);
		$start_date = date("Y-m-d",strtotime("-4 day"));
		$end_date = date("Y-m-d",strtotime("-2 day"));
		$this->assign("tag",$tag);
		$this->assign("start_date",$start_date);
		$this->assign("end_date",$end_date);
		return $this->fetch();
	}
	public function ajaxdataplatform($appid="",$tag="",$start_date="",$end_date="",$index="",$platform="")
	{
		echo json_encode( $this->getchanneladtype($appid,$start_date,$end_date,$platform,$index,$tag,"channel") );exit;
	}
	
	public function ajaxdataadtype($appid="",$tag="",$start_date="",$end_date="",$index="",$adtype="")
	{
		echo json_encode( $this->getchanneladtype($appid,$start_date,$end_date,$adtype,$index,$tag,"adtype") );exit;
	}
	
	//渠道细分
	private function getchanneladtype($appid,$start_date,$end_date,$type_index,$index,$tag="",$type="")
	{
		if( $index ){
			switch($index)
			{			
				case "oneweek":
				   $start_date = date("Y-m-d",strtotime("-8 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day")); 				  
				break;
				case "twoweek":
				   $start_date = date("Y-m-d",strtotime("-15 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day"));
				   break;
				case "three":
				   $start_date = date("Y-m-d",strtotime("-4 day"));
				   $end_date = date("Y-m-d",strtotime("-2 day"));			    
				break; 			
			 }
           }
		if( $type=="channel" )
		{
			$output = $this->platformchats($appid,$start_date,$end_date,$type_index);
		}elseif($type=="adtype")
		{
			$output = $this->adtypechats($appid,$start_date,$end_date,$type_index);
		}	
		$re =[];
		foreach( $output as $k=>$t )
		{
			if( $k!="date" )
			{
				$re[$k] = $t[$tag];
			}else{
				$re[$k] = $t;
			}
		}
		return $re;
	}
	
	private function platformchats($appid="",$start_date="",$end_date="",$platform="")
	{
		$range_date = getDateFromRange($start_date,$end_date);
		$where="";
		
		$platform = rtrim($platform,",");
		if( $platform=="" )
		{
			$platform="0";
		}
		$where =" and platform in({$platform})";
		
		$output = [ "int"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"rew"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"nat"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"ban"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"no"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""] ];
		$date="";
		foreach( $range_date as $key=>$vvv )
		{
						
			 $d_sql ="select adtype,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and date='{$vvv}' {$where} group by adtype";
			 $d = Db::query($d_sql);
			 if(!empty($d) ){
				 foreach( $d as $kkk=>$vv )
				 {
					 $c_revenue = isset($vv["revenue"])?$vv["revenue"]:"0";
			         $c_impressions = isset($vv["impressions"])?$vv["impressions"]:"0";
			         $c_clicks = isset($vv["clicks"])?$vv["clicks"]:"0";
			         $c_ecpm = $c_impressions<=0?0:number_format($c_revenue*1000/$c_impressions,2);
					 $output[$vv["adtype"]]["revenue"].="{$c_revenue},";
			         $output[$vv["adtype"]]["impressions"].="{$c_impressions},";
			         $output[$vv["adtype"]]["clicks"].="{$c_clicks},";
			         $output[$vv["adtype"]]["ecpm"].= "{$c_ecpm},";
				 }
				 
			 }			 			
			 $date.="{$vvv},";			
		}
		foreach( $output as &$v )
		 {
			 foreach( $v as &$r )
			 {
				 $r = rtrim($r,',');
			 }
		 }
		$date = rtrim($date,',');
		$output["date"] = $date;
		return $output;
	}
	
	//类型细分
	private function adtypechats($appid="",$start_date="",$end_date="",$adtype="")
	{
		$range_date = getDateFromRange($start_date,$end_date);
		$where="";
		
		$adtype = rtrim($adtype,",");
		if( $adtype=="" )
		{
			$adtype="0";
		}
		$where =" and adtype in({$adtype})";
		
		$output = [ "1"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"2"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"3"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"4"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"5"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"6"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"7"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"9"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""],
					"30"=>["revenue"=>"","impressions"=>"","clicks"=>"","ecpm"=>""]
				  ];
		$date="";
		foreach( $range_date as $key=>$vvv )
		{
						
			 $d_sql ="select platform,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and date='{$vvv}' {$where} group by platform";
			 $d = Db::query($d_sql);
			 if(!empty($d) ){
				 foreach( $d as $kkk=>$vv )
				 {
					 $c_revenue = isset($vv["revenue"])?$vv["revenue"]:"0";
			         $c_impressions = isset($vv["impressions"])?$vv["impressions"]:"0";
			         $c_clicks = isset($vv["clicks"])?$vv["clicks"]:"0";
			         $c_ecpm = $c_impressions<=0?0:number_format($c_revenue*1000/$c_impressions,2);
					 $output[$vv["platform"]]["revenue"].="{$c_revenue},";
			         $output[$vv["platform"]]["impressions"].="{$c_impressions},";
			         $output[$vv["platform"]]["clicks"].="{$c_clicks},";
			         $output[$vv["platform"]]["ecpm"].= "{$c_ecpm},";
				 }
				 
			 }			 			
			 $date.="{$vvv},";			
		}
		foreach( $output as &$v )
		 {
			 foreach( $v as &$r )
			 {
				 $r = rtrim($r,',');
			 }
		 }
		$date = rtrim($date,',');
		$output["date"] = $date;
		return $output;
	}
	
	//国家分布
	public function countryanalysis($appid="")
	{
		if( $appid=="" )
		{
			$appid = getcache("select_app");
			
		}else{
			 setcache("select_app",$appid);
		}
		
		$oneapp= Db::name("app")->field("id,app_name")->find($appid);
		$start_date = date("Y-m-d",strtotime("-4 day"));
		$end_date = date("Y-m-d",strtotime("-2 day"));
		$data = $this->getcountrydata($appid,$start_date,$end_date,"'int','ban','rew','nat','no'","1,2,3,4,5,6,7,9","'all'");
		$this->assign("start_date",$start_date);
		$this->assign("end_date",$end_date);
		$this->assign("data",$data);
		
		$this->assign("appid",$appid);
		$this->assign("country",admincountry());
		return $this->fetch();
	}
	
	public function countrydata($appid="",$start_date="",$end_date="",$adtype="",$platform="",$country="")
	{
		$data = $this->getcountrydata($appid,$start_date,$end_date,$adtype,$platform,$country);
		
		$this->assign("data",$data);
		return $this->fetch();
	}
	
	//
	private function getcountrydata($appid="",$start_date="",$end_date="",$adtype="",$platform="",$country="")
	{
		$country = rtrim($country,',');
		$platform = rtrim($platform,',');
		$adtype = rtrim($adtype,',');
		$appid = rtrim($appid,',');
		$result = [];
		$where ="";
		if( $country!="" )
		{
			 if(!preg_match("/all/",$country))
			 {
				$where.=" and country in({$country})"; 
			 }			 
			 
		}
		if( $adtype=="" )
		{
			$adtype="-1";
		}
		$where.=" and adtype in({$adtype})";
		if( $platform=="" )
		{
			$platform="-1";
		}	 
		
		$where.=" and platform in({$platform})";
			 
		
		if(preg_match("/all/",$country) || $country=="" )
		 {
			$d_sql ="select sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and  date>='{$start_date}' and date<='{$end_date}' {$where}";
            $d = Db::query($d_sql);
			if( !empty($d) )
			{
				$d[0]["country"] = "全部";
				$d[0]["impressions"] = isset($d[0]["impressions"])?$d[0]["impressions"]:"0";
				$d[0]["clicks"] = isset($d[0]["clicks"])?$d[0]["clicks"]:"0";
				$d[0]["revenue"] = isset($d[0]["revenue"])?$d[0]["revenue"]:"0";
			}
		 }else{
			 $d_sql ="select country,sum(impression) as impressions,sum(click) as clicks,round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id in({$appid}) and  date>='{$start_date}' and date<='{$end_date}' {$where} group by country";
             $d = Db::query($d_sql);
		 }
		
		return $d;
	}
}
