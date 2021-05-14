<?php
namespace app\api\controller;

use think\Db;
use think\Session;

class Index 
{
   	//内购收益
	private function getpurchase($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" )";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		$purchase_sql = "select round(sum(revenue),2) as revenue from hellowd_purchase_data where {$where}";
		$d= Db::query($purchase_sql);
		if( !empty($d) )
		{
			return $d[0]["revenue"]?$d[0]["revenue"]:"0.00";
		}
		return "0.00";
	}
	
	//广点通收益扣税 和 Sigmob
	private function getGdtTax($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }elseif( $field=="country" )
		 {
			 $field=1;
			 $value=1;
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}' and type in(2,3))";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel" || $vv["pvalue"]=="country" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" )";
		 }
		$gdt = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as original_revenue")->where($where)->find();
		if( !empty($gdt) && isset($gdt["original_revenue"])  && $gdt["original_revenue"]>0 )
		{
			return round($gdt["original_revenue"]*0.0634,2);
		}
		return 0;
	}
	
	public function gettotaldata($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		$revenue = $this->getrevenuetotal($start,$end,$field,$value,$filterLists,$filterType);
		
		$spend = $this->getspendtotal($start,$end,$field,$value,$filterLists,$filterType);
		//新增
		$new_users = $this->getnew_users($start,$end,$field,$value,$filterLists,$filterType);
		if( $spend["installs"]>$new_users )
		{
			$new_users = $spend["installs"];
		}
		$spend["cpi"] = $new_users<=0?"0.00":number_format($spend["spend"]/$new_users,2);
		//内购收益
		$purchase = $this->getpurchase($start,$end,$field,$value,$filterLists,$filterType);
		
		$revenue["total"]["revenue"] = $revenue["total"]["revenue"]+$purchase;
		
		//留存
		$reten_1 = $this->getreten($start,$end,$field,$value,$filterLists,$filterType);
		$reten_2 = $this->getdayreten($start,$end,$field,$value,$filterLists,$filterType,2);
		$reten_3 = $this->getdayreten($start,$end,$field,$value,$filterLists,$filterType,3);
		$reten_7 = $this->getdayreten($start,$end,$field,$value,$filterLists,$filterType,7);
		$reten_14 = $this->getdayreten($start,$end,$field,$value,$filterLists,$filterType,14);
		$reten_28 = $this->getdayreten($start,$end,$field,$value,$filterLists,$filterType,28);
		//roi计算
		$roi = $spend["spend"]<=0?"0":round($revenue["total"]["revenue"]*100/$spend["spend"],2);
        //自然量
        $nature_num = ($new_users-$spend["installs"])<0?0:$new_users-$spend["installs"];
        //自然占比
        $nature_rat = $new_users<=0?"0":number_format($nature_num*100/$new_users,0);
		$dau = $revenue["total"]["active_users"];
		$session = $this->getuser_time($start,$end,$field,$value,$filterLists,$filterType);
		$avg_session_length =$session["val"]?$session["val"]:0;
   		$avg_session_num =$dau>0?round($session["num"]/$dau,2):0;
		return $out_data =array(
		    "purchase"=>$purchase,
            "roi"=>$roi,
			"date"=>$start."-".$end,
            "reten_1"=>$reten_1,
            "reten_2"=>$reten_2,
            "reten_3"=>$reten_3,
			"reten_7"=>$reten_7,
			"reten_14"=>$reten_14,
            "reten_28"=>$reten_28,
            "new_users"=>$new_users,
            "revenue"=>round($revenue["total"]["revenue"],2),
			"nature"=>$nature_num,
			"nature_rat"=>$nature_rat,
			"avgint"=>$revenue["int"]["avgshow"],
			"avgrew"=>$revenue["rew"]["avgshow"],
			"arpdau"=>$revenue["total"]["dauarpu"],
			"dau"=>$dau,
			"session_num"=>$avg_session_num,
			"session_length"=>round($avg_session_length,2),
			"ecpm"=>$revenue["total"]["ecpm"],
			"spend"=>$spend["spend"],
			"cpi"=>$spend["cpi"]           			
		);
	}
	
	//人均使用时长
	private function getuser_time($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=")";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		$user_sql = "select sum(val) as val,sum(num) as num from hellowd_user_time where {$where}";
		$d= Db::query($user_sql);
		
		if( empty($d) )
		{
			return ["val"=>0,"num"=>0];
		}
		return isset($d[0]) && !empty($d[0])?$d[0]:["val"=>0,"num"=>0];
	}
	
	public function getrevenuetotal($start="",$end="",$field,$value,$filterLists,$filterType)
	{   
		 $newfield = $field;
		 if( $field=="app" )
		 {
			 $field ="sys_app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "sys_app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "platform";
				 }elseif( $vv["pvalue"]=="country" ){
					if( $vv["value"]=="all" )
					{
						 $vv["pvalue"] = "1";
					     $vv['value'] ="1";
					}						
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			$where.=" )";
		 }
		 $active_users = $this->getactive_users($start,$end,$newfield,$value,$filterLists,$filterType); 
		
		 $r=Array
			(
				"int" => Array
					(
						"impressions" =>0,
						"revenue" => "0.0",
						"ecpm" => "0.0",
						"avgshow" => 0
					),
				"rew" => Array
					(
						"impressions" =>0,
						"revenue" => "0.0",
						"ecpm" => "0.0",
						"avgshow" => 0
					),
				"ban" => Array
					(
						"impressions" =>0,
						"revenue" => "0.0",
						"ecpm" => "0.0",
						"avgshow" => 0
					),
                "nat" => Array
					(
						"impressions" =>0,
						"revenue" => "0.0",
						"ecpm" => "0.0",
						"avgshow" => 0
					),					
				"total" => Array
					(
						"impressions" =>0,
						"revenue" => "0.0",
						"ecpm" => "0.0",
						"avgshow" => 0,
						"dauarpu" => "0.0",
						"active_users" => 0
					)

			);
		 $impressions=0;
		 $revenue="0.00";
		 $sum_sql = "select if(adtype!='',adtype,'no') as adtype,sum(impression) as impressions,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} group by adtype";
		 $d= Db::query($sum_sql);
		 if( !empty($d) )
		 {
			 foreach( $d as &$v )
			 {
				 				
				 $v["ecpm"] = $v["impressions"]<=0?0:number_format($v["revenue"]*1000/$v["impressions"],2);
				 $v["avgshow"] = $active_users<=0?0:number_format($v["impressions"]/$active_users,2);
                 $r[ $v["adtype"] ] = ["impressions"=>$v["impressions"],"revenue"=>$v["revenue"],"ecpm"=>$v["ecpm"],"avgshow"=>$v["avgshow"] ];
                 $impressions+=	$v["impressions"];
                 $revenue+=$v["revenue"];				 
			 }
		 }
			 
		 $ecpm= $impressions<=0?0:number_format($revenue*1000/$impressions,2);
		 $avgshow = $active_users<=0?0:number_format($impressions/$active_users,2);
		 $purchase = $this->getpurchase($start,$end,$newfield,$value,$filterLists,$filterType);
		 //DAUARPU		
		 $daynum = count( getDateFromRange($start, $end));		 
		// $revenue = $revenue-($this->getGdtTax($start,$end,$newfield,$value,$filterLists,$filterType));
		  $dauarpu = $active_users<=0?"0.00":number_format(($revenue+$purchase)/($active_users*$daynum),3);
		 $r["total"] = ["impressions"=>$impressions,"purchase"=>$purchase,"revenue"=>round($revenue,2),"ecpm"=>$ecpm,"avgshow"=>$avgshow,"dauarpu"=>$dauarpu,"active_users"=>$active_users ];	 
		 return $r;
	}
	
	public function getspendtotal($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "platform_type";
				 }elseif( $vv["pvalue"]=="country" ){
					if( $vv["value"]=="all" )
					{
						$vv["pvalue"] = "1";
					    $vv['value'] ="1";
					}						
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" ) ";
		 }
		
		$sum_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
		$d= Db::query($sum_sql);
		$con_data = $this->getcontroltotal($start,$end,$field,$value,$filterLists,$filterType);
		
		if( !empty($d) )
		 {
			 $d = $d[0];
			 $spend = $d["spend"]?$d["spend"]:"0.00";
			 $installs = $d["installs"]?$d["installs"]:0;			
			 $con_data["spend"] = $con_data["spend"]+$spend;
			 $con_data["installs"] = $con_data["installs"]+$installs;			
		 }
		 $con_data["cpi"] = $con_data["installs"]<=0?"0.0":number_format($con_data["spend"]/$con_data["installs"],2); 
		 return $con_data;
	}
	//获取手动添加的数据
	private function getcontroltotal($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		$spend="0.00";
		$installs =0;
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" ) ";
		 }
		$control_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where}";
		
		$d= Db::query($control_sql);
		if( !empty($d) )
		 {
			 $d = $d[0];
			 $spend = $d["spend"]?$d["spend"]:"0.0";
			 $installs = $d["installs"]?$d["installs"]:0;
		 }
		 return ["spend"=>$spend,"installs"=>$installs ];
	}
		
	//日活
	private function getactive_users($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.=" {$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" ) ";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		
		$active_sql = "select ceil(sum(val)) as val from hellowd_active_users where {$where}";
		$d= Db::query($active_sql);		
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	//新增
	public function getnew_users($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" ) ";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		$new_sql = "select sum(val) as val from hellowd_new_users where {$where}";
		$d= Db::query($new_sql);
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	//留存
	public function getreten($start="",$end="",$field,$value,$filterLists,$filterType)
	{
		
		
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }elseif( $field=="date" )
		 {
			$start = $value;
			$end = $value;
			$field=1;
			$value=1;
		 }
		 $start=date("Y-m-d",strtotime('+1 day',strtotime($start)));
		 $end=date("Y-m-d",strtotime('+1 day',strtotime($end)));
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			$where.=" ) ";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		$reten_sql = "select avg(retention_1) as val from hellowd_retention where {$where}";

		$d= Db::query($reten_sql);
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?round($d[0]["val"]*100,2):0;
	}
	
	private function getdayreten( $start="",$end="",$field,$value,$filterLists,$filterType,$day)
	{
		
		if( $field=="app" )
		 {
			 $field ="app_id";
		 }elseif( $field=="date" )
		 {
			$start = $value;
			$end = $value;
			$field=1;
			$value=1;
		 }
		 $start=date("Y-m-d",strtotime("+{$day} day",strtotime($start)));
		 $end=date("Y-m-d",strtotime("+{$day} day",strtotime($end)));
		 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}')";		 
		 if( !empty($filterLists) )
		 {
			 $where.=" and ( (1=1) ";
			 foreach( $filterLists as $vv )
			 {
				 $seg ="and";
				 if( $filterType=="2" )
				 {
					$seg ="or"; 
				 }
				 if( $vv["pvalue"]=="app" )
				 {
					 $vv["pvalue"] = "app_id";
				 }elseif( $vv["pvalue"]=="channel"  )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 if( $vv["value"]=="custom" )
				 {
					 $vv["pvalue"] = "1";
					 $vv['value'] ="1";
				 }
				 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
			 }
			 $where.=" ) ";
		 }
		 if( !preg_match('/country/',$where) )
		 {
			 $where.=" and country='all'";
		 }
		$reten_sql = "select avg(retention_{$day}) as val from hellowd_retention where {$where}";
		$d= Db::query($reten_sql);
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?round($d[0]["val"]*100,2):0;
	}
		
	 function getupltvids()
 {
	$unit_ids =array(
	    "77"=>"'2000186346977172_2163629353966203','2000186346977172_2163631157299356','2000186346977172_2163630907299381','2000186346977172_2163631017299370','2000186346977172_2163631083966030','2000186346977172_2161214377541034','2000186346977172_2179312555731216','2000186346977172_2179312435731228','2000186346977172_2174066506255821','2000186346977172_2000201066975700','2000186346977172_2186745954987876','2000186346977172_2193585484303923'",
		"66"=>"'159499268084463_260996104601445','159499268084463_261001291267593','159499268084463_261001521267570','159499268084463_261001464600909','159499268084463_267662947268094','159499268084463_284050938962628','159499268084463_284053835629005'",
		"31"=>"'421636544919960_644165049333774','421636544919960_644167192666893','421636544919960_645764275840518','421636544919960_645764055840540','421636544919960_645764115840534','421636544919960_645764195840526','421636544919960_645760189174260'",
		"68"=>"'567407280311928_716666568719331','567407280311928_716666482052673','567407280311928_716666918719296','567407280311928_716667082052613','567407280311928_716667212052600','567407280311928_716738348712153'",
		"91"=>"'2033122236908398_2062911953929426','2033122236908398_2062911867262768','2033122236908398_2062912820596006','2033122236908398_2062912880596000','2033122236908398_2062912947262660','2033122236908398_2075801135973841','2033122236908398_2075801049307183','2033122236908398_2075801435973811','2033122236908398_2068102096743745'",
		"93"=>"'2033122236908398_2072495836304371','2033122236908398_2107945469426074','2033122236908398_2072495926304362','2033122236908398_2072496016304353','2033122236908398_2072496086304346','2033122236908398_2057155771171711','2033122236908398_2072495386304416','2033122236908398_2072496486304306','2033122236908398_2072495456304409','2033122236908398_2072495559637732','2033122236908398_2072495592971062','2033122236908398_2072496202971001'",
		"94"=>"'247052485982890_257063704981768','247052485982890_257063671648438','247052485982890_257063618315110','247052485982890_257063528315119','247052485982890_257061558315316','247052485982890_257060871648718','247052485982890_257061104982028'",
		"85"=>"'572769599809326_606240836462202','572769599809326_606240943128858','572769599809326_606241159795503','572769599809326_606241099795509','572769599809326_606241033128849','572769599809326_606241833128769','572769599809326_606241759795443'",
		"52"=>"'145198636252217_357542768351135','145198636252217_357542515017827','145198636252217_357543095017769','145198636252217_357542595017819','145198636252217_357542665017812','2000186346977172_2171610266501445','2000186346977172_2172054866456985','145198636252217_400338097404935','145198636252217_400339204071491','145198636252217_431970717575006','145198636252217_431380517634026','145198636252217_431380594300685','145198636252217_431380667634011','145198636252217_430338284404916'",
	    "114"=>"'995528667313289_1027081290824693','995528667313289_1018771101655712','995528667313289_1047490962117059','995528667313289_1030568363809319'",
		"109"=>"'363644127735416_410884053011423','363644127735416_410883709678124'",
		"127"=>"'721755674906542_723603851388391','721755674906542_723603718055071','721755674906542_723602838055159','721755674906542_723602078055235','721755674906542_721756491573127'",
		"112"=>"'589597201479764_632290357210448','589597201479764_632290227210461','589597201479764_632290120543805','589597201479764_632290460543771','589597201479764_617897358649748','589597201479764_621263628313121'"
	);
    return $unit_ids;	
 }
 //获取upltv 在facebook的数据
 function getupltvfacebook($start,$end,$field,$value,$filterLists,$filterType,$adtype="")
 {
	$unit_ids = $this->getupltvids();
	if( $field=="app" )
	 {
		 $field ="sys_app_id";
	 }
	 $allids = implode(",",$unit_ids);
	 $where="(date>='{$start}' and date<='{$end}' and {$field}='{$value}' and platform=6 and unit_id in({$allids}))";
	 if( !empty($filterLists) )
	 {
		 $where.=" and ( (1=1) ";
		 foreach( $filterLists as $vv )
		 {
			 $seg ="and";
			 if( $filterType=="2" )
			 {
				$seg ="or"; 
			 }
			 if( $vv["pvalue"]=="app" )
			 {
				 $vv["pvalue"] = "sys_app_id";
				 if(isset($unit_ids[$vv["value"]] ))
				 {
					$where.="{$seg} ( unit_id in'{$unit_ids[$vv["value"]]}')";
				 }
			 }elseif( $vv["pvalue"]=="channel"  )
			 {
				 $vv["pvalue"] = "platform";
			 }elseif( $vv["pvalue"]=="country" ){
				if( $vv["value"]=="all" )
				{
					$vv["pvalue"] = "1";
					 $vv['value'] ="1";
				}						
			 }
			 $where.="{$seg} (".$vv["pvalue"]."='{$vv['value']}')";
		 }
		 $where.=" ) ";
	 }
	if( $adtype!="" )
	{
		$where.=" and adtype='{$adtype}'";
	}
	$res =Db::name("adcash_data")->field("sum(impression) as impression,sum(click) as click,sum(revenue) as revenue")->where($where)->find();
	$impression = isset($res["impression"]) && !empty($res["impression"])?$res["impression"]:0;
	$click = isset($res["click"]) && !empty($res["click"])?$res["click"]:0;
	$revenue = isset($res["revenue"]) && !empty($res["revenue"])?$res["revenue"]:0;
	return ["impression"=>$impression,"click"=>$click,"revenue"=>$revenue];
 }
}
