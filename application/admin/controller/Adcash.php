<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use \app\admin\model\Adcash_m;
use \app\common\lib\Mobrequest;
use \app\common\lib\Applovinrequest;
use \app\common\lib\Vunglerequest;
use \app\common\lib\Unityrequest;
  //广告应用模块
  //平台类型1 Mob 2 Unity 3 applovin 4Vungle 5 admob 6 facebook
class Adcash extends Base
{
    //Mobvista report
	public function moblist( $spm="",$start="",$end="",$t="new",$app_name="",$country="" )
    {      		
       if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(1,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(1,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(1,$start,$end,$t,$app_name,$country);
    }
	
	private function getlistdata($platform,$start="",$end="",$t="new",$app_name="",$country="")
	{
		
		$ts = Db::name("adcash_data")->field("date")->where( ["platform"=>$platform] )->order("id desc")->find();		
		if( $t=="week")
		{
			$end=$ts["date"];
			$start = date('Y-m-d',strtotime($end.'-15 day'));
		}
		if( $t=="month" )
		{
			$end=$ts["date"];
			$start = date('Y-m-d',strtotime($end.'-30 day'));
		}
		if( $t=="new" )
		{
			$end=$ts["date"];
			$start = date('Y-m-d',strtotime($end.'-7 day'));
		}
		$out_time =[ "maxtime"=>$ts["date"],"s_time"=>date("m/d/Y",strtotime($start)),"e_time"=>date("m/d/Y",strtotime($end)) ];
		$where="platform={$platform} and date>='{$start}' and date<='{$end}'";
		if( $country!="" && $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		if( $app_name!="" && $app_name!="all" )
		{
			
		   $where.=" and app_id='{$app_name}'";			
		}
		 if( $platform==5 )
		 {
			 $where.=" and app_name!=''";
		 }
	    $list =Db::name('adcash_data')
				 ->where ( $where )				 
				 ->order ( "date desc" )
				 ->paginate(20,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>["start"=>$start,"end"=>$end,"t"=>$t,"app_name"=>$app_name,"country"=>$country]
								] );
	    if( $platform==2 )
		{
			$sum_data = Db::query(" SELECT SUM(request) as sum_request,SUM(started) as sum_impression,SUM(views) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}");
		}else{
			$sum_data = Db::query(" SELECT SUM(filled) as sum_filled,SUM(request) as sum_request,SUM(impression) as sum_impression,SUM(click) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}");
		}	    
		$this->assign('country',getallcountry());
        $this->assign('app',getapp($platform) );
	    $this->assign('list',$list);
	    $this->assign("t",$t);
	    $this->assign("sum_data",$sum_data[0]);
		$this->assign("out_time",$out_time);
		$this->assign("code",$country);
		$this->assign("app_name",$app_name);
		$tpl =$this->getplatformtype( $platform );
		return $this->fetch($tpl.'list');exit;
	}
	private function getplatformtype( $platform )
	{
		$str ="";
		switch( $platform )
		{
			case 1:
			  $str="mob";
			break;
            case 2:
              $str="unity";
            break;
            case 3:
              $str="app";
            break;
            case 4:
              $str="vungle";
            break;
            case 5:
              $str="admob";
            break;
            case 6:
              $str="facebook";
            break;		  
		}
		return $str;
	}
	private function gettotaldata( $platform,$start,$end,$tp,$app_names="" )
	{
		
		//取当前最新时间
		$t = Db::name("adcash_data")->field("date")->where( ["platform"=>$platform] )->order("id desc")->find();
		
		if( $tp=="week")
		{
			$end=$t["date"];
			$start = date('Y-m-d',strtotime($end.'-7 day'));
		}
		if( $tp=="month" )
		{
			$end=$t["date"];
			$start = date('Y-m-d',strtotime($end.'-15 day'));
		}
		
		if( ($start=="" && $end=="") || $tp=="new" )
		{			
			if( !empty($t) )
			{
				$start = $t["date"];
				$end = $t["date"];
			}else{
				$start = date("Y-m-d",strtotime("-1 day"));
				$end =  date("Y-m-d",strtotime("-1 day"));
			}
		}
		$out_time =[ "maxtime"=>$t["date"],"s_time"=>date("m/d/Y",strtotime($start)),"e_time"=>date("m/d/Y",strtotime($end)) ];
        $where="platform={$platform} and date>='{$start}' and date<='{$end}'";
       	if( $app_names!="" && $app_names!="all")
		 {
			$where.=" and app_id='{$app_names}'";
		 }
		 if( $platform==5 )
		 {
			 $where.=" and app_name!=''";
		 }
      		 
		$app_name ="";
		$app_income ="";
		$app_value ="";
		 if( $platform==2 )
		 {
			 $sum_data = Db::query(" SELECT SUM(started) as sum_impression,SUM(views) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}");
             $list = Db::query(" select app_id,app_name,sum(started) as total_impression,sum(views) as total_click,sum(revenue) as total_revenue  from  hellowd_adcash_data where {$where} group by app_id");			 
		 }else{
			 $sum_data = Db::query(" SELECT SUM(impression) as sum_impression,SUM(click) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}"); 
			 $list = Db::query(" select app_id,app_name,sum(impression) as total_impression,sum(click) as total_click,sum(revenue) as total_revenue  from  hellowd_adcash_data where {$where} group by app_id");
		 }	
		
		 if( !empty($list) )
		 {
			 foreach($list as $vv)
			 {
				 
				 $app_name.="'{$vv["app_name"]}',"; 

				 $app_income.="'{$vv["total_revenue"]}',";
				 $app_value.="{value:"."'{$vv["total_revenue"]}'"."},";
			 }
		 }
			
		 $this->assign('app_name',rtrim($app_name,","));
		 $this->assign('app_income',rtrim($app_income,","));
		 $this->assign('app_value',rtrim($app_value,","));
		 $this->assign('app',getapp($platform) );
	     $this->assign('country',getallcountry());
		 $this->assign('list',$list);
		 $this->assign("sum_data",$sum_data[0]);
		 $this->assign("t",$tp);
		 $this->assign("app_names",$app_names);
		 $this->assign("out_time",$out_time);
		 $tpl =$this->getplatformtype( $platform );
		 return $this->fetch($tpl.'total');exit;		 
	}
	
	//获取每天数据
	private function getdaydata($platform,$tp,$app_name="",$spm="")
	{
		 //取当前最新时间
		$t = Db::name("adcash_data")->field("date")->where( ["platform"=>$platform] )->order("id desc")->find();
		
		if( $tp=="week")
		{
			$end=$t["date"];
			$start = date('Y-m-d',strtotime($end.'-15 day'));
		}
		if( $tp=="month" )
		{
			$end=$t["date"];
			$start = date('Y-m-d',strtotime($end.'-30 day'));
		}
		if( $tp=="new" )
		{
			$end=$t["date"];
			$start = date('Y-m-d',strtotime($end.'-7 day'));
		}
		 $where="platform={$platform} and date>='{$start}' and date<='{$end}'";
		 if( $app_name!="" && $app_name!="all")
		 {
			$where.=" and app_id='{$app_name}'";
		 }
		  if( $platform==5 )
		 {
			 $where.=" and app_name!=''";
		 }
		if( $platform==2 )
		{
		  $list_sql="app_id,app_name,sum(request) as request,sum(started) as impression,sum(views) as click,sum(revenue) as revenue,date";
          $sum_sql="SELECT SUM(request) as sum_request,SUM(started) as sum_impression,SUM(views) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}";		  
		}else{
		  $list_sql="app_id,app_name,sum(filled) as filled,sum(request) as request,sum(impression) as impression,sum(click) as click,sum(revenue) as revenue,date";
          $sum_sql="SELECT SUM(filled) as sum_filled,SUM(request) as sum_request,SUM(impression) as sum_impression,SUM(click) as sum_click,SUM(revenue) as sum_revenue from  hellowd_adcash_data WHERE {$where}";	
		}		
		 $list =Db::name('adcash_data')
		         ->field($list_sql)
				 ->where ( $where )
                 ->group('app_id,date')				 
				 ->order ( "date desc" )
				 ->paginate(20,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>["spm"=>$spm]
								] );
       $sum_data = Db::query($sum_sql);	   
	   $this->assign('country',getallcountry());
       $this->assign('app',getapp($platform) );
	   $this->assign('list',$list);
	   $this->assign("t",$tp);
	   $this->assign("app_name",$app_name);
	   $this->assign("sum_data",$sum_data[0]);
	   $tpl =$this->getplatformtype( $platform );
	   return $this->fetch($tpl.'day');exit;
	}
			
	public function mobline()
	{
		  $this->assign('country',getallcountry());
          $this->assign('app',getapp(4) );
		 return $this->fetch();
	}
	//Applovin reprot
	public function apllist( $spm="",$start="",$end="",$t="new",$app_name="",$country="" )
	{
	   if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(3,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(3,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(3,$start,$end,$t,$app_name,$country);
	}
	
	
	//vungle
	public function vunglelist( $spm="",$start="",$end="",$t="new",$app_name="",$country="" )
	{
	   if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(4,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(4,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(4,$start,$end,$t,$app_name,$country);
	}
	
	//Unity
	public function unitylist($spm="",$start="",$end="",$t="new",$app_name="",$country="")
	{
	  if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(2,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(2,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(2,$start,$end,$t,$app_name,$country);
	}
	//Admob
	public function admoblist($spm="",$start="",$end="",$t="new",$app_name="",$country="")
	{
	  if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(5,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(5,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(5,$start,$end,$t,$app_name,$country);
	}
	//facebook 数据
	public function facebooklist($spm="",$start="",$end="",$t="new",$app_name="",$country="")
	{
	   if( $spm=="last" )
	   {
		   
		   return $this->gettotaldata(6,$start,$end,$t,$app_name);
		   
	   }elseif( $spm=="day" )
	   {
		    return $this->getdaydata(6,$t,$app_name,$spm);
	   }
	  return $this->getlistdata(6,$start,$end,$t,$app_name,$country);
	}
	
	//数据汇总
	public function summary($ap_code="hw_tk",$index="revenue",$start="",$end="")
	{
		 $app_info = getappcode($ap_code);
		 $app_name = $app_info["name"];
		 $android_ids = $app_info["android"];
		 $ios_ids = $app_info["ios"];
		 $fireos_ids = $app_info["fireos"];
		 if( $start=="" && $end=="" )
		{						
		    $start = date("Y-m-d",strtotime("-1 day"));
		    $end =  date("Y-m-d",strtotime("-1 day"));			
		}
		 $where["date"] =[ ['>=',$start],['<=',$end] ];
		 $where["app_id"] = ['in',array_merge($android_ids,$ios_ids,$fireos_ids ) ];	
		 $tt = getDateFromRange($start,$end);
		 $data = Adcash_m::getplatdata($where);
		
		 $result = $this->daysummary( Adcash_m::getplatdaydata($where),$index,$tt );			
		 $total = $this->getsummary($data);
		 $this->assign("time",["f_start"=>date("m/d/Y",strtotime($start)),"f_end"=>date("m/d/Y",strtotime($end)),"start"=>$start,"end"=>$end ]  );
		 $this->assign("result",$result);
		 $this->assign("index_name",$this->getindexname($index) );
		 $this->assign("tt","'".implode("','",$tt)."'");
		 return $this->fetch('summary',[ "data"=>$data,"total"=>$total,"app_name"=>$app_name,"ap_code"=>$ap_code,"index"=>$index ]);exit;
	}
	
	private function daysummary($result,$column,$tt)
	{
		$res =[ "1"=>[],"2"=>[],"3"=>[],"4"=>[],"5"=>[],"6"=>[] ];
		$r=[];
		foreach($result as &$vv)
		{
			if(isset( $vv["impression"]) && $vv["impression"]>0 )
			{ $vv["ecpm"] =  round( $vv["revenue"]*1000/$vv["impression"],2); }else{ $vv["ecpm"]="0.00";}
			$res[ $vv["platform"] ][] = $vv;
		}		
		foreach( $res as $key=>&$p_v )
		{
			$dates = array_column($p_v,"date");
			foreach( $tt as $t_vv )
			{
				if( !in_array( $t_vv,$dates ) )
				{
					$p_v[] = [ "platform"=>$key,"date"=>$t_vv,"revenue"=>0,"impression"=>0,"click"=>0,"ecpm"=>0 ];
				}
			}
		  $r[$key] = [ "column"=>implode(",",array_column($p_v,$column) ),"sum"=>array_sum( array_column($p_v,$column) ) ];
		}
		return $r;
	}
	
	private function getindexname($name)
	{
		if( $name=="revenue" )
		{
			return "AD REVENUE";
		}elseif( $name=="impression" )
		{
			return "IMPRESSIONS";
		}
		elseif( $name=="click" )
		{
			return "CLICKS";
		}
		elseif( $name=="ecpm" )
		{
			return "eCPM";
		}
	}
	
	//获取汇总数据
	private function getsummary($data)
	{
		 $revenue=0;
		 $impression =0;
		 $click =0;
		 array_walk(
         $data,
         function ($item, $key)use(&$revenue,&$impression,&$click){
			
             $revenue+=$item["revenue"];
			 $impression+=$item["impression"];
			 $click+=$item["click"];
         }
     );
	 return [ "revenue"=>$revenue,"impression"=>$impression,"click"=>$click ];
	}
	
	public function platformdata($ap_code="hw_tk",$start="",$end="",$platform="")
	{
		 $app_info = getappcode($ap_code);
		 
		 if( $platform=="total" )
		 {
		  
		   $android_ids = $app_info["android"];
		   $ios_ids = $app_info["ios"];
		   $fireos_ids = $app_info["fireos"];
		   $app_ids = array_merge($android_ids,$ios_ids,$fireos_ids );
		 }else{
			$app_ids =  $app_info[$platform];
		 }
		 $where["date"] =[ ['>=',$start],['<=',$end] ];
		 $where["app_id"] = ['in',$app_ids ];
		 $data = Adcash_m::getplatdata($where);
		 return $this->fetch('platform',[ "data"=>$data ]);
	}
	public function index()
	{
		return $this->fetch();
	}
	
	public function test()
	{
		echo hew_md5('cjx123');exit;
	}
}
