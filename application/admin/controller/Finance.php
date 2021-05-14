<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;

//财务管理
class Finance extends Base
{
    
	public function index()
	{
		$res = Db::name('apply')->order("status asc,add_time desc")->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );
		$start_today =date("Y-m-d",time())." 00:00:00";
        $end = date("Y-m-d H:i:s",time());		
		$start_mon_date = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
		$this->assign("totday",$this->gettotalmoney($start_today,$end));
		$this->assign("mon",$this->gettotalmoney($start_mon_date,$end));
		$this->assign("total",$this->gettotalmoney());
		$this->assign("res",$res);
        $this->assign("data",$res->toarray()["data"] );		
		return $this->fetch('index');
	}
	
	private function gettotalmoney($start="",$end="")
	{
		$where ="status=1";
		if( $start!="" && $end!="" )
		{
			$where.=" and update_time>='{$start}' and update_time<='{$end}'";
		}
	    $r = Db::query(" select sum(money) as money from hellowd_apply where {$where} ");
		if( !empty($r) && isset($r[0]) )
		{
			return $r[0]["money"]?$r[0]["money"]:"0.00";
		}
		return "0.00";
	}
	
	public function edit($id="",$status="")
	{
		if( $id && $status )
		{
			Db::name("apply")->where("id={$id}")->update( [ "status"=>$status,"update_time"=>date("Y-m-d H:i:s",time()) ] );
			exit("ok");
		}
		exit("fail");
	}
	
	//申请提现
	public function add($appid="",$money="",$remark="")
	{
		if( $appid && $money && $remark )
		{
			$revenue_rate = getrevenue_rate($appid);
			$apply_revenue = "0.00";
			$apply_data = Db::query(" select sum(money) as money from hellowd_apply where status!=2 ");
			if( !empty($apply_data) && isset($apply_data[0]) )
			{
				$apply_revenue =  $apply_data[0]["money"]?$apply_data[0]["money"]:"0.00";
			}
			$end = date("Y-m-d",strtotime("-2 day"));
			$total_revenue ="";
			$sum_revenue_sql = "select round(sum(revenue),2) as revenue from hellowd_adcash_data where date>='2018-09-01' and date<='{$end}' and sys_app_id={$appid} ";
			$revenue= Db::query($sum_revenue_sql);
			if( !empty($revenue) && isset($revenue[0]) )
			{
				$total_revenue =  $revenue[0]["revenue"]?$revenue[0]["revenue"]:"0.00";
			}
			$total_spend = $this->getspend($appid,"2018-09-01",$end);
			$copartner_data = $this->getcopartnerdata($appid);
			$copartner_other_data = $this->getcopartnerdata($appid,2);
		    $total_revenue =$total_revenue+$copartner_data["revenue"];
		    $total_spend = $total_spend+$copartner_data["spend"]+$copartner_other_data["spend"];
			$copartner_revenue = $total_revenue-$total_spend;
			if( $copartner_revenue>0 )
			{
				$copartner_revenue = $copartner_revenue*$revenue_rate*0.01;			
			}else{
				$copartner_revenue="0.00";
			}
			$can_total_revenue = number_format($copartner_revenue,2);
			
			$can_left_money = $can_total_revenue-$apply_revenue;
			if( ($can_left_money-$money)<=0 )
			{
				exit("notenough");
			}
			$userinfo = getuserinfo();
			$r = Db::name("apply")->insert( ["userid"=>$userinfo["id"],"name"=>$userinfo["truename"],"remark"=>$remark,"money"=>$money,"app_id"=>$appid ] );
			if( $r!==false )
			{
			   exit("ok");
			}
		}
		exit("fail");
	}
	
	private function getcopartnerdata($appid,$type="1")
	{
		$res =Db::name("copartner_data")->field("spend,revenue,active_users,new_users,reten,roi")->where( ["app_id"=>$appid,"type"=>1 ] )->find();
		if( !empty($res) )
		{
			return $res;
		}
		return [ "spend"=>"0.00","revenue"=>"0.00","active_users"=>0,"new_users"=>0,"reten"=>0,"roi"=>0 ];
	}
	
	private function getspend($appid,$start,$end)
	{
		$where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		$sum_sql = "select round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
		$d= Db::query($sum_sql);
		if( isset($d[0]) && !empty($d[0]) )
		{
			return $d[0]["spend"]?$d[0]["spend"]:"0.00";
		}
		return "0.00"; 
	}
	
	//添加数据
	public function add_data($appid="",$date="",$field="",$value="",$type="1")
	{
		if( $appid && $field  )
		{
			$r = Db::name("copartner_data")->where( ["app_id"=>$appid,"type"=>$type ] )->find();
			if( !empty($r) )
			{
				Db::name("copartner_data")->where( ["app_id"=>$appid,"type"=>$type ] )->update( [$field=>$value] );
				
			}else{
				Db::name("copartner_data")->insert( [$field=>$value,"app_id"=>$appid,"type"=>$type,"date"=>$date ] );
			}
			exit("ok");
		}
		exit("fail");
	}
	
	//收益账单
	public function bill($appid="",$select="one")
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
		$current_month_start = date("Y-m-d",mktime(0, 0 , 0,date("m"),1,date("Y")));

		$current_month_end=date("Y-m-d",strtotime("-2 day"));
		
		$where = " date>='{$current_month_start}'";
		$where_history =" and date<'{$current_month_start}'";
		$touwhere="1=1";
		if( $select=="one" )
		{
			$where.=" and  app_id={$appid}";
			$where_history.=" and  app_id={$appid}";
			$touwhere.=" and  app_id={$appid}";
		}
		$res = Db::name("summary_data")->field(" ROUND(sum(revenue),2) as revenue,ROUND(sum(spend),2) as spend ")->where($where)->find();
		
		$data =Db::query(" SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y.%m') as y,ROUND(SUM(spend),2) as spend,ROUND(SUM(revenue),2) as revenue from hellowd_summary_data WHERE date>='2018-10-01'  {$where_history}  GROUP BY y");
		
		
		if( !empty($data) )
		{
			foreach( $data as &$vv )
			{
			   $toutiao =Db::query(" SELECT FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y.%m') as y,ROUND(SUM(original_revenue),2) as original_revenue,ROUND(SUM(cny_revenue),2) as cny_revenue from hellowd_upltv_toutiao WHERE {$touwhere} group by y HAVING y='{$vv['y']}'");
			  
			   if( isset($toutiao[0]) && !empty($toutiao[0]) )
			   {
				  $original_revenue = $toutiao[0]["original_revenue"]?$toutiao[0]["original_revenue"]:0;
				  $cny_revenue=$toutiao[0]["cny_revenue"]?$toutiao[0]["cny_revenue"]:0;
				  $truemoney = $cny_revenue*0.1449;
				  $vv["revenue"] = round($vv["revenue"]-$original_revenue+$truemoney,2);
			   }
			   $vv["roi"] = $vv["spend"]<=0?"0":number_format( $vv["revenue"]*100/$vv["spend"],2);
			}
		}
		
		$this->assign("data",$data);
        $this->assign("res",$res);
		$this->assign("select",$select);
		return $this->fetch('bill');
	}
	
	
	//渠道
	public function channel($ad_type="adv")
	{
		$allcountrys = admincountry();
		$this->assign("allcountrys",$allcountrys);
		
		$start = date("Y-m-d",strtotime("-30 day"));
		$end = date("Y-m-d",time());
        
		$this->assign("end",$end);
		$this->assign("start",$start);
		
		if( "pub"==$ad_type )
		{
			$data = $this->getpubchanneldata("6",$start,$end,"all","month");
			$this->assign("data",$data);
			return $this->fetch('publishchannel');
		}else{
			
			$data = $this->getchanneldata("6",$start,$end,"all","product");
		}
		$pp =Db::name("platform")->field("platform_name")->where("status=1 and platform_name!=''")->group("platform_name")->select();
		$this->assign("pp",$pp);
		$this->assign("data",$data);
		return $this->fetch();
	}
	
	//推广渠道 优化师
	public function group_channel(){
		$allcountrys = admincountry();
		$this->assign("allcountrys",$allcountrys);		
		$start = date("Y-m-d",strtotime("-30 day"));
		$end = date("Y-m-d",time());
        
		$this->assign("end",$end);
		$this->assign("start",$start);
		$data = $this->getchanneldata("6",$start,$end,"all","product");
		$pp =Db::name("platform")->field("platform_name")->where("status=1 and platform_name!=''")->group("platform_name")->select();
		$this->assign("pp",$pp);
		$this->assign("data",$data);
		return $this->fetch();
	}
	
	//json 
	public function channel_data_json($platform="",$start="",$end="",$country="all",$group="month")
	{
		if( preg_match("/^\d+$/",$platform) )
		{
			$data = $this->getchanneldata($platform,$start,$end,$country,$group);
		}else{
			//手动添加的数据
			$data =$this->getSpendCton($platform,$start,$end,$country,$group);
		}		
		$this->assign("data",$data);
		return $this->fetch();
	}
	
	//变现
	public function pub_channel_data_json($platform="",$start="",$end="",$country="all",$group="month")
	{
		if( $platform=="all" )
		{
			$data = $this->getpubsummary($start,$end);
		}else{
			$data = $this->getpubchanneldata($platform,$start,$end,$country,$group);
		}	
		$this->assign("data",$data);
		return $this->fetch();
	}
	function gettime($month)
	{
		 $month_start =date('Y-m-01', strtotime($month));
         $end_time = date("Y-m-t",strtotime($month));
		 return ["start"=>$month_start,"end"=>$end_time];
	}
	
	private function getpubsummary($start="",$end="")
	{
		$where="date>='{$start}' and date<='{$end}'";	
		$summary_sql = "select platform,sum(impression) as impression,sum(click) as click,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} group by platform";
		$res= Db::query($summary_sql);
		if( !empty($res) )
			{
				foreach( $res as &$vv )
				{
					
					$vv["revenue"]= $vv["revenue"]?$vv["revenue"]:"0.0";
					if($vv["platform"]=="30")
					{
						$vv["revenue"] =$this->getRateUpltv($vv["revenue"],$start,$end);
					}
			        $vv["impression"] = $vv["impression"]?$vv["impression"]:0;
			        $vv["click"] = $vv["click"]?$vv["click"]:0;
					$vv["appcation"] = getplatform($vv["platform"]);
                    $vv["icon_url"] = getplatformimg($vv["platform"]);				
				}
			}
		return $res;
	}
	
	private function getpubchanneldata($platform="",$start="",$end="",$country="all",$group="month")
	{
		$where="platform={$platform} and  date>='{$start}' and date<='{$end}'";		
		if(!preg_match('/all/',$country))
		{
			$where.=" and country in({$country})";
		}
		$out_data =[];
		if( "month"==$group )
		{			
			$dates = $this->getrangemonth($start, $end);
			foreach( $dates as $k=>$v )
			{
				$spend_sql = "select sum(impression) as impression,sum(click) as click,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} and FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m')='{$v}'";
				$d= Db::query($spend_sql);
				if( !empty($d) )
				 {
					 $d = $d[0];
					 $revenue = $d["revenue"]?$d["revenue"]:"0.00";
					/*  if( $platform=="30" )
					 {
						$r_date = $this->gettime($v);
						$revenue =$this->getRateUpltv($revenue,$r_date["start"],$r_date["end"]);
						
					 } */
					 $impression = $d["impression"]?$d["impression"]:0;
					 $click = $d["click"]?$d["click"]:0;
					 $out_data[$k] = ["revenue"=>$revenue,"impression"=>$impression,"click"=>$click,"appcation"=>$v ];
                     				 
				 }
			}
			 			 	  
		}else
		{
			
			if( $platform=="30" )
			 {
				$spend_sql = "select round(sum(revenue),2) as revenue,channel from hellowd_upltv_channel where  date>='{$start}' and date<='{$end}' group by channel";
			    $res= Db::query($spend_sql);
				if( !empty($res) )
				{
					foreach( $res as $vv )
					{
						 $out_data[] = ["revenue"=>$vv["revenue"],"impression"=>0,"click"=>0,"appcation"=>$vv["channel"] ];						
					}
				}
			 }			
		}
		return $out_data;
	}
	
	//获取每个月汇率
	private function getmonthexchange($date)
	{
		$key = date("Y-m",strtotime($date));
		$exchange_rate = array(
		      "2018-11"=>"0.1449",
			  "2018-12"=>"0.1453",
			  "2019-02"=>"0.1495",
			  "2019-03"=>"0.1485",
			  "2019-04"=>"0.1486"
		);
		return isset($exchange_rate[$key])?$exchange_rate[$key]:"0.1449";
	}
	
	//广点通扣税
	private function getGDT($date)
	{
		$key = date("Y-m",strtotime($date));
		$exchange_rate = array(
		      "2019-02"=>"0.0317"
		);
		return isset($exchange_rate[$key])?$exchange_rate[$key]:"0.0634";
	}
	
	
	//upltv重复收益
	private function getRepeatfacebook($start,$end)
	{
		$str_ids="";
		$unit_ids = array_values(getupltvids());
		if( !empty($unit_ids) )
		{
			$str_ids = rtrim(implode(",",$unit_ids),",");			
		}
		$where= "date>='{$start}' and date<='{$end}' and platform=6 and unit_id in({$str_ids})";
		$res =Db::name("adcash_data")->field("sum(impression) as impression,sum(click) as click,sum(revenue) as revenue")->where($where)->find();
		$impression = isset($res["impression"]) && !empty($res["impression"])?$res["impression"]:0;
		$click = isset($res["click"]) && !empty($res["click"])?$res["click"]:0;
		$revenue = isset($res["revenue"]) && !empty($res["revenue"])?$res["revenue"]:0;
		return ["impression"=>$impression,"click"=>$click,"revenue"=>$revenue];
	}
	//汇率 扣税后收益
	private function getRateUpltv($total,$start,$end){
		
		$where = "date>='{$start}' and date<='{$end}'";
		$f_revenue = $this->getRepeatfacebook($start,$end);
		
		$rate = $this->getmonthexchange($start);
		$upltv = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as original_revenue,round(sum(cny_revenue),2) as cny_revenue")->where($where)->find();
		$gdt = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as original_revenue,round(sum(cny_revenue),2) as cny_revenue")->where($where." and type=2")->find();
		if( isset($upltv) && !empty($upltv) )
		   {
			  $original_revenue = $upltv["original_revenue"]?$upltv["original_revenue"]:0;
			  $gdt_rate = $this->getGDT($start);
			  $gdt_cny_revenue =$gdt["cny_revenue"]?$gdt["cny_revenue"]:0;
			  $gdt_cny_exchange_revenue = $gdt_cny_revenue*(1-$gdt_rate);
			  $cny_revenue=$upltv["cny_revenue"]?$upltv["cny_revenue"]:0;
			  $truemoney = $cny_revenue*$rate;
			  $total = round($total-$f_revenue["revenue"]-$original_revenue+$truemoney,2);
		   }
		return $total;   
	}
	
	//花费手动添加的数据
	private function getSpendCton($platform="",$start="",$end="",$country="all",$group="month")
	{
		$ids="";
		$res = Db::name("platform")->field("platform_id")->where(["platform_name"=>$platform])->select();
		if( !empty($res) )
		{
			$ids =" and platform in (".implode(",",array_column($res,"platform_id")).")";
		}
		$where="date>='{$start}' and date<='{$end}' ".$ids;
		if(!preg_match('/all/',$country))
		{
			$where.=" and country in({$country})";
		}
		$out_data =[];
		if( "month"==$group )
		{			
			$dates = $this->getrangemonth($start, $end);
			foreach( $dates as $k=>$v )
			{
				$spend_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where} and FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m')='{$v}'";
				$d= Db::query($spend_sql);
				if( !empty($d) )
				 {
					 $d = $d[0];
					 $spend = $d["spend"]?$d["spend"]:"0.00";
					 $installs = $d["installs"]?$d["installs"]:0;
					 $cpi = $installs<=0?"0.00":number_format($spend/$installs,2);
					 $out_data[$k] = ["installs"=>$installs,"spend"=>$spend,"cpi"=>$cpi,"appcation"=>$v ];
                     				 
				 }
			}
			 			 	  
		}else
		{
			$spend_sql = "select sum(installs) as installs,round(sum(spend),2) as spend,app_id from hellowd_control_data where {$where} and app_id>0 group by app_id";
			$res= Db::query($spend_sql);
			
			if( !empty($res) )
			{
				foreach( $res as &$vv )
				{
					
					$vv["spend"]= $vv["spend"]?$vv["spend"]:"0.0";
			        $vv["installs"] = $vv["installs"]?$vv["installs"]:0;
			        $vv["cpi"] = $vv["installs"]<=0?"0.0":number_format($vv["spend"]/$vv["installs"],2);
					$r = Db::name("app")->field("app_name as appcation,icon_url")->find($vv["app_id"]);	
                    if(!empty($r))
					{
						$out_data[] = array_merge($vv,$r);
					}											
				}
			}
		}
		return $out_data;
	}
	
	//花费渠道数据
	private function getchanneldata($platform="",$start="",$end="",$country="all",$group="month")
	{
		$where="platform_type={$platform} and  date>='{$start}' and date<='{$end}'";		
		if(!preg_match('/all/',$country))
		{
			$where.=" and country in({$country})";
		}
		$out_data =[];
		if( "month"==$group )
		{			
			$dates = $this->getrangemonth($start, $end);
			foreach( $dates as $k=>$v )
			{
				$spend_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where} and FROM_UNIXTIME(UNIX_TIMESTAMP(date),'%Y-%m')='{$v}'";
				$d= Db::query($spend_sql);
				if( !empty($d) )
				 {
					 $d = $d[0];
					 $spend = $d["spend"]?$d["spend"]:"0.00";
					 $installs = $d["installs"]?$d["installs"]:0;
					 $cpi = $installs<=0?"0.00":number_format($spend/$installs,2);
					 $out_data[$k] = ["installs"=>$installs,"spend"=>$spend,"cpi"=>$cpi,"appcation"=>$v ];
                     				 
				 }
			}
			 			 	  
		}else
		{
			$spend_sql = "select sum(installs) as installs,round(sum(spend),2) as spend,app_id from hellowd_adspend_data where {$where} and app_id>0 group by app_id";
			$res= Db::query($spend_sql);
			
			if( !empty($res) )
			{
				foreach( $res as &$vv )
				{
					
					$vv["spend"]= $vv["spend"]?$vv["spend"]:"0.0";
			        $vv["installs"] = $vv["installs"]?$vv["installs"]:0;
			        $vv["cpi"] = $vv["installs"]<=0?"0.0":number_format($vv["spend"]/$vv["installs"],2);
					$r = Db::name("app")->field("app_name as appcation,icon_url,app_base_id,platform")->find($vv["app_id"]);
					if(  $vv["app_id"]>154 )
					{
						if( $r["app_base_id"] )
						{
							$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
							$r["appcation"] = $row["name"].' - '.$r["platform"];
							$r["icon_url"] = $row["icon"];
						}
					}
                    if(!empty($r))
					{
						$out_data[] = array_merge($vv,$r);
					}											
				}
			}
		}
		return $out_data;
	}
	
	//获取日期之间的月份
	private function getrangemonth($start,$end)
	{
		$stimestamp = strtotime($start);
		$etimestamp = strtotime($end);

		// 计算日期段内有多少天
		$days = ($etimestamp-$stimestamp)/86400+1;

		// 保存每天日期
		$date = array();

		for($i=0; $i<$days; $i++){
			$key = date('Y-m', $stimestamp+(86400*$i));
			if( !in_array($key,$date) )
			{
				$date[]=$key;
			}
		}

		return $date;
	}
}
