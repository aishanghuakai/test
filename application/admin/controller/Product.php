<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use \app\admin\controller\Index;

class Product extends Base
{
    
	
	//体育馆收益处理
	private function get_gym_revenue($appid,$start,$end){
		
		$where = "date>='{$start}' and date<='{$end}' and app_id={$appid}";
		$res = Db::name("summary_data")->field("revenue,purchase,date")->where($where." and ( spend>0 or revenue>0)")->select();
		$total_purchase ="0.00";
        $total_revenue ="0.00";
		foreach($res as &$vv)
			{				
			   $purchase = $vv["purchase"];
			   
			   if(strtotime($vv["date"]) >= strtotime("2021-02-01") && strtotime($vv["date"]) <= strtotime("2021-04-15") )
			   {
				   //$vv["revenue"] =0;
				   //$vv["purchase"] =0;
			   }					   
				$total_revenue+=$vv["revenue"];
                $total_purchase+=$vv["purchase"];		
			}
	   return ["revenue"=>$total_revenue,"purchase"=>$total_purchase];
	}

	public function index($appid="",$start="",$end="",$groupby="product",$select="all",$app_id="",$groupid="all")
    {      	    
	  //echo 333;exit;
	  $t1 = microtime(true);
	  $total_spend ="0.00";
      $total_revenue ="0.00";
	  $rebate_revenue ="0.00";
      $total_active_users =0;
      $total_new_users=0;
      $total_nature_num=0;
      $total_purchase="0.00";
	  $out_data =[];
	  $where="status=1";
	  $applist =[];
	  $admin_id = Session::get('admin_userid');
	  $groupList = Db::name('app_group')->where(["userid"=>$admin_id])->select();
      if($groupid!="all")
	  {
		  if($groupid=="ziyan")
		  {
			  $res = Db::query("SELECT a.id from  hellowd_app a  join hellowd_app_base b  on a.app_base_id=b.id WHERE b.type=1");
			  $app_id = implode(",",array_column($res,"id"));
		  }elseif($groupid=="faxing"){
			  $res = Db::query("SELECT a.id from  hellowd_app a  join hellowd_app_base b  on a.app_base_id=b.id WHERE b.type=2");
			  $app_id = implode(",",array_column($res,"id"));
		  }else{
			  $rrr = Db::name('app_group')->find($groupid);
			  if($rrr["applist"])
			  {
				  $app_id = implode(",",json_decode($rrr["applist"],true));
			  }	
		  }		  	 
	  }elseif($groupid=="all"){		 		
		 if(!$app_id)
		 {
			 $app_id ="";
		 }else{
			$groupid=""; 
		 }		  
	  }  
	  $wherea=" ";
	  if($app_id)
	  {
		  $wherea=" and app_id in({$app_id})";
	  }
	  if($admin_id==95 || $admin_id==97)
	  {
		  $wherea=" and app_id in(93)";
	  }
	  if( $groupby=="product" ){
        if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-2 day"));
			$end = date("Y-m-d",strtotime("-2 day"));
		}		  
		  $r = Db::name("summary_data")->field('app_id,sum(spend) as spend,sum(rebate) as rebate_revenue,sum(revenue) as revenue,sum(new_users) as new_users,sum(nature_num) as nature_num,avg(active_users) as avg_active_users,sum(active_users) as active_users,sum(purchase) as purchase,round(avg(reten),2) as reten')->where(" date>='{$start}' and date<='{$end}' and (spend>0 or revenue>0 ) {$wherea}")->group('app_id')->select();
		  if( !empty($r) )
		  {
			  $index = new Index( request() );
			  foreach( $r as $key=>&$v )
			  {
				    
					
					$rr = Db::name("app")->find($v["app_id"]);
					if($rr["app_base_id"])
					{
						if($admin_id==32 || $admin_id==59 || $admin_id==90)
						{
							$type = $admin_id==59?2:1;						
							$base_app = Db::name("app_base")->where("id",$rr["app_base_id"])->find();
							if($base_app["type"]!=$type)
							{
								continue;
							}						
						}
					}else{
					  continue;
					}
					$v["app_name"] = $rr["app_name"].' - '.$rr["platform"];
					$v["icon_url"] = $rr["icon_url"];
					$v["adjust_new_users"] = $index->getnew_users($v["app_id"], $start, $end,"all");
					$v["spend"] = round($v["spend"],2);
					$v["roi"] = $v["spend"]?round($v["revenue"]*100/$v["spend"],2):0;
					$v["nature_rat"] = $v["new_users"]>0?round($v["nature_num"]*100/$v["new_users"],2):0;
					$v["cpi"] = $v["new_users"]>0?round($v["spend"]/$v["new_users"],2):"0.00";
					$v["dauarpu"] = $v["active_users"]>0?round($v["revenue"]/$v["active_users"],3):"0.00";
					if(  $v["app_id"]>154 )
					{												
						if( $rr["app_base_id"] )
						{
							$row = Db::name("app_base")->where("id",$rr["app_base_id"])->find();
							$v["app_name"] = $row["name"].' - '.$rr["platform"];
							$v["icon_url"] = $row["icon"];
						}						
					}
				   $applist[] = $v["app_id"];
				   $out_data[$key]["name"] = $v["app_name"];
				   $file_list = explode(".",$v["icon_url"]);
				   $out_data[$key]["icon_url"] = $file_list[0]."_40_40.".$file_list[1];
				   $total_active_users+=$v["avg_active_users"];
				   
				   $total_nature_num+=$v["nature_num"];
				   $total_new_users+=$v["new_users"];
				   
				   /* if(in_array($v["app_id"],[166,169]))
				   {
					 $tr = $this->get_gym_revenue($v["app_id"],$start,$end);	   	   
					 $v["revenue"] =$tr["revenue"];
					 $v["purchase"] =$tr["purchase"];
				   } */
				   $out_data[$key]["result"] =$v;
				   $total_purchase+=$v["purchase"];
				   $total_revenue+=$v["revenue"];
				   $rebate_revenue+=$v["rebate_revenue"];
				   $total_spend+=$v["spend"];
			  }
		  } 
	  }else{
		  if( $start=="" || $end=="" )
		  {
			 $start = date("Y-m-d",strtotime("-9 day"));
			 $end = date("Y-m-d",strtotime("-2 day"));
		  }		  
		   $data = $this->getdaydata($start, $end);
		   $out_data = $data["list"];
		   $total_revenue = $data["total_revenue"];
		   $total_spend = $data["total_spend"];
		   $total_purchase =$data["total_purchase"]; 
		   $rebate_revenue =$data["rebate_revenue"];
	  }

      $roi = $total_spend<=0?"0":number_format($total_revenue*100/$total_spend,2);
      $chats_str = $this->getproductchats($out_data,$groupby);
      
      $this->assign("chats_str",$chats_str); 
	  $this->assign("list",$out_data);
	  $this->assign("total_spend",$total_spend);
	  $this->assign("total_purchase",$total_purchase);
	  $this->assign("total_nature_num",$total_nature_num);
	  $this->assign("total_new_users",$total_new_users);
	  $this->assign("total_active_users",$total_active_users);
	  $this->assign("select",$select);
	  //$total_new_data = Db::name("summary_data")->where("date>='{$start}' and date<='{$end}'")->find();
	  $cooperate_revenue = $this->cooperate($app_id,$start,$end);	  
	  //$rebate_revenue = $this->getRebate($app_id,$start,$end);
	  $t2 = microtime(true);
      $hs_time = '耗时'.round($t2-$t1,3).'秒';
	  $this->assign("hs_time",$hs_time);
	  $this->assign("total_revenue",$total_revenue);
	  $this->assign("rebate_revenue",$rebate_revenue);
	  $this->assign("cooperate_revenue",$cooperate_revenue);
	  $m_revenue = $total_revenue-$total_spend-$cooperate_revenue+$rebate_revenue;
	  $this->assign("real_revenue",round($total_revenue-$cooperate_revenue,2));
	  $this->assign("m_revenue",round($m_revenue,2));
	  $this->assign("start",$start);
	  $this->assign("roi",$roi);
	  $this->assign("groupby",$groupby);
	  $this->assign("end",$end);
	  $this->assign("app_id",$app_id);
	  $this->assign("groupid",$groupid);
	  $this->assign("groupList",$groupList);    	  
	  return $this->fetch('index');
    }
	
	private function getproductchats($data,$groupby)
	{
		$a=1;			
		$names = "";
		$date="";
		$roi="";
		$values="";
		
		foreach($data as $key=>$vv)
		{
			if( $groupby=="product" )
			{ 
		        //$str.="{ 'value':'{$vv['result']['roi']}','name':'{$vv['name']}'},";
				if($a<18 && !preg_match("/,/",$vv["result"]["roi"]) )
				{
					if( $vv["result"]["spend"]>0 || $vv["result"]["revenue"]>0 )
					{
						$names.="'{$vv['name']}',";
						$values.="{$vv['result']['roi']},";
						++$a;
					}
				}						
		    }else{
				$date.="'{$vv['date']}',";
				$roi.="{$vv['roi']},";
			}			
		}
		return ["names"=>rtrim( $names,","),"values"=>rtrim( $values,","),"date"=>rtrim($date,","),"roi"=>rtrim( $roi,",") ];
	}
	
	private function newgetproductday($start,$end){
		$where = "date>='{$start}' and date<='{$end}'";
		$res = Db::name("summary_data")->field("revenue,spend,app_id,rebate,purchase,date")->where($where." and ( spend>0 or revenue>0)")->select();
		$total_spend ="0.00";
        $total_revenue ="0.00";
		$rebate_revenue ="0.00";
		$roi="0.00";
		if(!empty($res))
		{
			$admin_id = Session::get('admin_userid');
			foreach($res as &$vv)
			{
				$rr = Db::name("app")->find($vv["app_id"]);
				if($rr["app_base_id"])
				{
					if($admin_id==32 || $admin_id==59 || $admin_id==90)
					{
						$type = $admin_id==59?2:1;						
						$base_app = Db::name("app_base")->where("id",$rr["app_base_id"])->find();
						if($base_app["type"]!=$type)
						{
							continue;
						}						
					}
				}else{
				  continue;
				}				
				$total_revenue+=$vv["revenue"];
				$total_spend+=$vv["spend"];	
                $rebate_revenue+=$vv["rebate"];				
			}
			$roi = $total_spend<=0?"0":round( $total_revenue*100/$total_spend,2);
		}
		return ["roi"=>$roi,"revenue"=>$total_revenue,"spend"=>$total_spend,"rebate_revenue"=>$rebate_revenue];
	}
		
	private function getdaydata($start, $end)
	{
		$total_spend ="0.00";
        $total_revenue ="0.00";
		$total_purchase="0.00";
		$rebate_revenue ="0.00";
		$out_data=[];
		$dates = getDateFromRange($start, $end);
		foreach( $dates as $k=>$v )
		{
			$out_data[$k]["date"] = $v;			 
			$tds= $this->newgetproductday($v,$v);
			$out_data[$k]["revenue"] =$tds["revenue"];
			$out_data[$k]["spend"] = $tds["spend"];
			$out_data[$k]["purchase"] = $this->getpurchase($v,$v,"all");
			$out_data[$k]["roi"] = $out_data[$k]["spend"]<=0?"0":number_format( $out_data[$k]["revenue"]*100/$out_data[$k]["spend"],2);
			$total_revenue+=$out_data[$k]["revenue"];
			$total_spend+=$out_data[$k]["spend"];
			$total_purchase+=$out_data[$k]["purchase"];
			$rebate_revenue+=$tds["rebate_revenue"];
		}
		return ["list"=>$out_data,"rebate_revenue"=>$rebate_revenue,"total_revenue"=>$total_revenue,"total_spend"=>$total_spend,"total_purchase"=>$total_purchase];
	}
	
	//收益 按时间筛选
	private function getcurrenincome($appid,$start,$end)
	{
		$ad_revenue ="0.00";
		$where = "sys_app_id in({$appid}) and  date>='{$start}' and date<='{$end}'";
		$sum_sql = "select round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where}";
		$d= Db::query($sum_sql);
		if( isset($d[0]) && !empty($d[0]) )
		{
			$ad_revenue=$d[0]["revenue"]?$d[0]["revenue"]:"0.00";
		}
		$purchase_revenue = $this->getpurchase($appid,$start,$end,"all");
		
		$ids_data = implode(",",getupltvids() );
		$upltv_where= " date>='{$start}' and date<='{$end}' and platform=6 and unit_id in({$ids_data})";	
	    $res =Db::name("adcash_data")->field("sum(revenue) as revenue")->where($upltv_where)->find();	
	    $revenue = isset($res["revenue"]) && !empty($res["revenue"])?$res["revenue"]:"0.00";		
		return $ad_revenue+$purchase_revenue-$revenue;
	}
	//内购收益
	private function getpurchase($start="",$end,$country="all")
	{
		$where="date>='{$start}' and date<='{$end}' and country='{$country}'";
		$purchase_sql = "select round(sum(revenue),2) as revenue from hellowd_purchase_data where {$where}";
		$d= Db::query($purchase_sql);
		if( !empty($d) )
		{
			return $d[0]["revenue"]?$d[0]["revenue"]:"0.00";
		}
		return "0.00";
	}
	
	
	
	//花费 按时间筛选
	private function getcurrenspend($appid,$start,$end)
	{
		$where = "app_id in ( {$appid} ) and  date>='{$start}' and date<='{$end}'";
		$sum_sql = "select round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
		$d= Db::query($sum_sql);
		$spend =$this->getcontroltotal($start,$end);
		if( isset($d[0]) && !empty($d[0]) )
		{
			$d_spend = $d[0]["spend"]?$d[0]["spend"]:"0.00";
			$spend+=$d_spend;
		}
		return $spend;
	}
	
	//获取手动添加的数据
	private function getcontroltotal($start="",$end="")
	{
		$spend="0.00";
		$installs =0;
		$where=" date>='{$start}' and date<='{$end}'";
		$control_sql = "select round(sum(spend),2) as spend from hellowd_control_data where {$where}";		
		$d= Db::query($control_sql);
		
		if( !empty($d) )
		 {
			 $d = $d[0];
			 $spend = $d["spend"]?$d["spend"]:"0.00";	 
			 return $spend;
		 }
		 return "0.00";
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
			  "2019-04"=>"0.1486",
			  "2019-05"=>"0.144944",
			  "2019-06"=>"0.14546",
			  "2019-07"=>"0.145262",
			  "2019-08"=>"0.141085",
			  "2019-09"=>"0.141385"
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
	
	private function getproductday($start,$end)
	{
		
		$where = "date>='{$start}' and date<='{$end}'";
		$res = Db::name("summary_data")->field("round(sum(revenue),2) as revenue,round(sum(purchase),2) as purchase,ceil(avg(active_users)) as active_users,sum(new_users) as new_users,round(avg(reten),2) as reten,sum(nature_num) as nature_num,round(avg(nature_rat),2) as nature_rat,round(avg(roi),2) as roi,round(sum(spend),2) as spend,round(avg(cpi),2) as cpi,round(avg(dauarpu),2) as dauarpu")->where($where." and ( spend>0 or revenue>0)")->find();
		
		if( !empty($res) )
		{
			$upltv = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as original_revenue,round(sum(cny_revenue),2) as cny_revenue,DATE_FORMAT(date,'%Y-%m') as months")->where($where)->group('months')->select();
			if( !empty($upltv) )
			   {
				  foreach( $upltv as $vv )
				  {
					$rate = $this->getmonthexchange($vv["months"]);
					$original_revenue = $vv["original_revenue"]?$vv["original_revenue"]:0;
				    $cny_revenue=$vv["cny_revenue"]?$vv["cny_revenue"]:0;
				    $truemoney = $cny_revenue*$rate;
                    $res["revenue"] = round($res["revenue"]-$original_revenue+$truemoney,2);					
				  }				  
			   }
			$res["roi"] = $res["spend"]<=0?"0":round( $res["revenue"]*100/$res["spend"],2);
		}
		return $res;
	}
	
	//更新每天单个产品汇总数据
	public function updateproduct($start="")
	{
		if( $start=="" )
		  {
			 $start = date("Y-m-d",strtotime("-2 day"));
		  }
        $r = Db::name("app")->field("id")->where("status=1")->select();
        $index = new Index( request() );
        foreach( $r as $key=>$v )
		{
		   
		   $data = $index->gettotaldata($v["id"],$start,$start,"all",2);
		   $rebate = $this->getRebate($v["id"],$start,$start);
		   $data["app_id"] = $v["id"];
		   $data["rebate"] = $rebate;
		   $this->insert_sum_data($data,$start);
		}
       //exit("ok");	
	}
	
	public function aaaa(){
		$start ="2021-03-15";
		$end ="2021-03-31";
		$dateList = getDateFromRange($start,$end);
		
		foreach( $dateList as $v )
		{
			$this->updateproduct($v);
		}
		exit("ok");
	}
	
	//变成和数据库字段一样
	private function setfield($data)
	{
		$out_data =[];
		$out_data["app_id"] = $data["app_id"];
		$out_data["revenue"] =$data["revenue"]["total"]["revenue"];
		$out_data["purchase"] =$data["purchase"];
		$out_data["active_users"] =$data["revenue"]["total"]["active_users"];
		$out_data["new_users"] =$data["new_users"];
		$out_data["reten"] =$data["reten"];
		$out_data["nature_num"] =$data["nature_num"];
		$out_data["nature_rat"]=$data["nature_rat"];
		$out_data["roi"]=$data["roi"];
		$out_data["spend"] =$data["spend"]["spend"];
		$out_data["cpi"] = $data["spend"]["cpi"];
		$out_data["dauarpu"] =$data["revenue"]["total"]["dauarpu"];
		$out_data["rebate"] = $data["rebate"];
		return $out_data;
	}
	
	
	
	private function insert_sum_data($data,$start)
	{
	  $data = $this->setfield($data);
	  if( $data["app_id"]==112 )
	  {
		$data["revenue"] =$data["revenue"]-$data["purchase"]; 
		$data["purchase"] ="0.00"; 
	  }
	  if( in_array($data["app_id"],["138","139"]) )
	  {
		  return true;
	  }
	  $d = Db::name("summary_data")->field("id")->where(["app_id"=>$data["app_id"],"date"=>$start])->find();
	  
	  if( !empty($d) )
	  {
		  Db::name("summary_data")->where(["id"=>$d["id"]])->update($data);
	  }else{
		  $data["date"] =$start; 
		  Db::name("summary_data")->insert($data);
	  }
      return true;	  
	}
	//Mobvista：返点 2%
	//Avazu：海外4.5%，大陆/香港 11%
    //Gatherone：海外4%，大陆/香港 3%
	//返点收益计算
	private function getRebate($app_id,$start,$end){
		
		$where=" and 1=1";
		$swhere =" and 1=1";
		if($app_id)
		{
			$where =" and app_id in({$app_id})";
			$swhere =" and sps.app_id in({$app_id})";
		}
		//Facebook，各家8%。比较统一。头条 智趣，3%
		$adwords = ["1"=>["CN"=>"0","HK"=>"0","HU"=>"0","FR"=>"0","other"=>"0.04"],//Mobvista
		           "2"=>["other"=>"0.04"],//Gatherone 所有地区4%，从12月1号开始。12月之前3%。
		           "3"=>["CN"=>"0.10","HK"=>"0.10","other"=>"0.055"],//Avazu
		           "4"=>["CN"=>"0.09","HK"=>"0.09","other"=>"0.045","HU"=>"0","FR"=>"0"]];//钛动
		if(strtotime($start)>=strtotime("2021-01-01"))
		{
			$adwords = ["1"=>["other"=>"0.02"],//Mobvista
		           "2"=>["CN"=>"0.03","HK"=>"0.03","other"=>"0.04"],//Gatherone 所有地区4%，从12月1号开始。12月之前3%。
		           "3"=>["CN"=>"0.11","HK"=>"0.11","other"=>"0.045"],//Avazu
		            //"4"=>["CN"=>"0.09","HK"=>"0.09","other"=>"0.045","HU"=>"0","FR"=>"0"]//钛动
				   ];
		}
		
		$adwords_sql ="SELECT sps.country,aa.`subject`,SUM(spend) as spend  from  hellowd_adspend_data sps 
join hellowd_advertising_account aa ON sps.target_id=aa.advertiser_id 
WHERE sps.platform_type=5 and sps.date>='{$start}' and sps.date<='{$end}' {$swhere} GROUP BY aa.`subject`,sps.country";
        $adwords_data = Db::query($adwords_sql);
		$revenue="0.00";
		if(!empty($adwords_data))
		{
			foreach($adwords_data as $v)
			{
				if(isset($adwords[$v["subject"]]))
				{
					
					if(isset( $adwords[$v["subject"]][$v["country"]] ))
					{
						$c_rev = round($v["spend"]*$adwords[$v["subject"]][$v["country"]],2);
					}else{
						$c_rev = round($v["spend"]*$adwords[$v["subject"]]["other"],2);
					}
					$revenue+=$c_rev;
				}
				
			}
		}
		$fb_data = Db::name("adspend_data")->field("sum(spend) as spend")->where("platform_type=6 and date>='{$start}' and date<='{$end}' {$where}")->find();
        $revenue+=($fb_data["spend"]>0?round($fb_data["spend"]*0.08,2):"0.00");		
		$toutiao_data = Db::name("adspend_data")->field("sum(spend) as spend")->where("platform_type=32 and date>='{$start}' and date<='{$end}' {$where}")->find();
		$tittok = [
		           "1"=>["US"=>"0.03","JP"=>"0.05","TW"=>"0.1","KR"=>"0.1","other"=>"0"],//Mobvista
		           "3"=>["other"=>"0.075"],//Avazu
				   ];
		$tittok_sql ="SELECT sps.country,aa.`subject`,SUM(spend) as spend  from  hellowd_adspend_data sps 
join hellowd_advertising_account aa ON sps.target_id=aa.advertiser_id 
WHERE sps.platform_type=36 and sps.date>='{$start}' and sps.date<='{$end}' {$swhere} GROUP BY aa.`subject`,sps.country";
        $tittok_data = Db::query($tittok_sql);
		$tittok_revenue="0.00";
		if(!empty($tittok_data))
		{
			foreach($tittok_data as $v)
			{
				if(isset($tittok[$v["subject"]]))
				{
					
					 if(isset( $tittok[$v["subject"]][$v["country"]] ))
					{
						$c1_rev = round($v["spend"]*$tittok[$v["subject"]][$v["country"]],2);
					}else{
						$c1_rev = round($v["spend"]*$tittok[$v["subject"]]["other"],2);
					}
					$tittok_revenue+=$c1_rev;
				}
				
			}
		}
		$revenue+=$tittok_revenue;
		$revenue+=($toutiao_data["spend"]>0?round($toutiao_data["spend"]*0.03,2):"0.00");
		return $revenue;
	}
	
	
	//分成
	private function cooperate($app_id,$start,$end){
		$where="77,143,147,153";
		if($app_id)
		{
			$where = $app_id;
		}
		$res = Db::name("revenue_rate")->field("app_id,revenue_rate")->where( "app_id in({$where}) and app_id is not null" )->select();
		$revenue="0.00";
		if(!empty($res))
		{
			foreach($res as &$v)
			{
				if($v['app_id']==153 && strtotime($start)< strtotime("2021-04-01") )
				{
					continue;
				}
				$s = Db::name("summary_data")->field('sum(spend) as spend,sum(revenue) as revenue')->where("app_id={$v['app_id']} and date<='{$end}'")->find();
				$d = Db::name("summary_data")->field('sum(spend) as spend,sum(revenue) as revenue')->where("app_id={$v['app_id']} and date>='{$start}' and date<='{$end}'")->find();
				$rt = $d["revenue"]-$d["spend"];
				$ed = $s["revenue"]-$s["spend"];
				if($ed>0 && $rt>0)
				{
					$cur =$rt*$v["revenue_rate"]/100;
					$revenue+=round($cur,2);
				}
			}
		}
		return $revenue;
	}
	
	// 产品地区汇总设置
	public function update_country_summary($start="",$end=""){
		
		if( $start=="" || $end=="" )
		{
			$start ="2020-01-01"; //date("Y-m-d",strtotime("-2 day"));
			$end = "2020-12-31";//date("Y-m-d",strtotime("-2 day"));
		}
		$date ="2020-12-31";
		$index = new Index( request() );
		$data=[];
		$res = Db::name("adspend_data")->field('app_id,country,sum(spend) as spend')->where(" spend>1 and date>='{$start}' and date<='{$end}'")->group('country,app_id')->select();
		if(!empty($res))
		{
		  Db::name("country_summary")->where(["date"=>$date])->delete();
		  foreach ($res as $v) {
			if($v["country"]!="" && preg_match("/^[a-zA-Z]/i",$v["country"]) && $v["app_id"]!="")
			{
				$row = $index->getmaintotal($v["app_id"], $start, $end,$v["country"]);
				$insert_data = array(
				      "app_id"=>$v["app_id"],
					  "revenue"=>$row["revenue"]["total"]["revenue"],
					  "active_users"=>$row["revenue"]["total"]["active_users"],
					  "new_users"=>$row["new_users"],
					  "reten"=>$row["reten"],
					  "spend"=>$row["spend"]["spend"],
					  "date"=>$date,
					  "country"=>$v["country"]
				);
				Db::name("country_summary")->insert($insert_data);
			}
		  }
		  $appIDS =array_unique(array_column($res,"app_id"));
		  if(!empty($appIDS))
		  {
			  foreach($appIDS as $d)
			  {
				  if($d)
				  {
					  $row = $index->getmaintotal($d, $start, $end,"all");
					  $insert_data = array(
				      "app_id"=>$d,
					  "revenue"=>$row["revenue"]["total"]["revenue"],
					  "active_users"=>$row["revenue"]["total"]["active_users"],
					  "new_users"=>$row["new_users"],
					  "reten"=>$row["reten"],
					  "spend"=>$row["spend"]["spend"],
					  "date"=>$date,
					  "country"=>"all"
				     );
				     Db::name("country_summary")->insert($insert_data);
				  }
			  }
		  }
		}
		exit("ok");
	}
}
