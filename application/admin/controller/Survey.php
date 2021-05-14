<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Survey extends Base
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
	   $start = date("Y-m-d",strtotime("-9 day"));
	   $end = date("Y-m-d",strtotime("-2 day"));
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	   $this->assign("country",admincountry());
	   return $this->fetch('index');
    }
	
	public function model($appid=""){
		if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
		$start = date("Y-m-d",strtotime("-7 day"));
	   $end = date("Y-m-d",strtotime("-2 day"));
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	    setcache("select_app",$appid);
		$this->assign("country",admincountry());
		return $this->fetch('model');
	}
	
	//人均使用时长
	public function getuser_time($app_id,$start,$end,$country)
	{
		$where="app_id={$app_id} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
		$user_sql = "select sum(val) as val,sum(num) as num from hellowd_user_time where {$where}";
		$d= Db::query($user_sql);
		
		if( empty($d) )
		{
			return ["val"=>0,"num"=>0];
		}
		return isset($d[0]) && !empty($d[0])?$d[0]:["val"=>0,"num"=>0];
	}
	
	public function getreten($appid, $start = "", $end = "", $country = "all")
    {

        $out = [1, 2, 3, 7, 14, 28];
        $res = [];
        foreach ($out as $k => $vv) {
			$val = $this->getdayreten1($appid, $start, $end, $country, $vv );
			$res["retention_" . $vv] = $val;
        }
        return $res;
    }
	
	private function getdayreten1($appid, $start, $end, $country, $day)
    {
        $start = date("Y-m-d", strtotime("+1 day", strtotime($start)));
        $end = date("Y-m-d", strtotime("+1 day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}' and retention_{$day}>0";
        $reten_sql = "select avg(retention_{$day}) as val from hellowd_retention where {$where}";
        $d = Db::query($reten_sql);
        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? round($d[0]["val"] * 100, 2) : 0;
    }
	
	//新增
    public function getnew_users($appid, $start = "", $end = "", $country = "all")
    {

        if ($appid == 132) {
            $appid = 112;
        }
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $new_sql = "select sum(val) as val from hellowd_new_users where {$where}";
        $d = Db::query($new_sql);
        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] : 0;
    }
	
	private function get_spend_channel($app_id,$start,$end,$country){
		
		$where="app_id={$app_id} and  date>='{$start}' and date<='{$end}' and spend>0";
		
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		$sql=" SELECT platform_type as name,round(SUM(spend),2) as spend,SUM(impressions) as impressions,SUM(installs) as installs,SUM(clicks) as clicks from  
hellowd_adspend_data WHERE {$where} GROUP BY platform_type";
        $res = Db::query($sql);
		if(!empty($res))
		{
			foreach($res as &$vv)
			{
				$vv["name"] = getplatform($vv["name"]);
				$spend = $vv["spend"] ? $vv["spend"] : "0.0";
                $installs = $vv["installs"] ? $vv["installs"] : 0;
                $impressions = $vv["impressions"] ? $vv["impressions"] : 0;
                $clicks = $vv["clicks"] ? $vv["clicks"] : 0;
                $vv["ctr"] = $vv["impressions"] <= 0 ? 0 : round($clicks * 100 / $impressions, 2);
                $vv["cpm"] = $vv["impressions"] <= 0 ? 0 : round($spend * 1000 / $impressions, 2);
                $vv["cvr"] = $vv["clicks"] <= 0 ? 0 : round($installs * 100 / $clicks, 2);
                $vv["cpi"] = $installs <= 0 ? 0 : round($spend / $installs, 2);
			}
		}
		return $res;
	}
	
	
	public function json_model_data($app_id="",$date=[],$country="all"){
		if( !$app_id || empty($date) || !$country )
		{
			exit( json_encode([]));
		}
		$producter=[];
		$monetizater =[];
		list($start,$end) = $date;
		$dates = getDateFromRange($start, $end);
		$total_new_users = 0;
		$total_new_users_reten=[
		   "retention_1"=>0,
		   "retention_2"=>0,
		   "retention_3"=>0,
		   "retention_7"=>0,
		   "retention_14"=>0,
		   "retention_28"=>0
		];
		$index = new Index(request());
        $retention = ["retention_1" => [], "retention_2" => [], "retention_3" => [], "retention_7" => [], "retention_14" => [], "retention_28" => []];
		foreach( $dates as $k=>$v )
		{			
			
			$new_users = $this->getnew_users($app_id,$v,$v,$country);			
            $res = $this->getreten($app_id,$v,$v,$country);
            $retention["retention_1"][] = $res["retention_1"]*$new_users;
            $retention["retention_2"][] = $res["retention_2"]*$new_users;
            $retention["retention_3"][] = $res["retention_3"]*$new_users;
            $retention["retention_7"][] = $res["retention_7"]*$new_users;
            $retention["retention_14"][] = $res["retention_14"]*$new_users;
            $retention["retention_28"][] = $res["retention_28"]*$new_users;
			if($res["retention_1"]>0)
			{
				$total_new_users_reten["retention_1"]+=$new_users;
			}
			if($res["retention_2"]>0)
			{
				$total_new_users_reten["retention_2"]+=$new_users;
			}
			if($res["retention_3"]>0)
			{
				$total_new_users_reten["retention_3"]+=$new_users;
			}
			if($res["retention_7"]>0)
			{
				$total_new_users_reten["retention_7"]+=$new_users;
			}
			if($res["retention_14"]>0)
			{
				$total_new_users_reten["retention_14"]+=$new_users;
			}
			if($res["retention_28"]>0)
			{
				$total_new_users_reten["retention_28"]+=$new_users;
			}
			$session = $this->getuser_time($app_id,$v,$v,$country);
			$impressions =$index->getrevenuetotal($app_id,$v, $v,"all",$country);
			$day_dau = $impressions["total"]["active_users"];
			$res["new_users"] = $new_users;
			$res["id"] = $k;
			$res["name"] = "(".getweekday($v).")".$v;
			$res["producter_showtips"] = isshowtips($app_id,$v,"producter");
		    $res["avg_session_num"] = $day_dau>0?round($session["num"]/$day_dau,2):0;
		    $res["avg_session_length"] =$session["val"]?round($session["val"],2):0;
			$producter[] =$res;
            $total_new_users += $new_users;
			$monetizater[$k]['id'] = $k;
			$monetizater[$k]['name'] = $res["name"];
			$monetizater[$k]["publisher_showtips"] = isshowtips($app_id,$v,"publisher");
			$monetizater[$k]["avg_adv_show"] =$impressions["total"]["avgshow"];
			$monetizater[$k]["ecpm"] =$impressions["total"]["ecpm"];
		}		
		$total_data =[];
		$total_data_rev =[];
        $advertiser = $this->get_spend_channel($app_id,$start,$end,$country);
		
		$impressions =$index->getrevenuetotal($app_id,$start, $end,"all",$country);
		$dau = $impressions["total"]["active_users"];
		$session = $this->getuser_time($app_id,$start,$end,$country);
		$total_data["name"]="总计";
		$total_data["id"]="-1";
		$total_data_rev["id"]="-1";
		$total_data["producter_showtips"] =0;
		$total_data["new_users"] = $total_new_users; 
		$total_data_rev["name"]="总计";
		$total_data_rev["publisher_showtips"]=0;
		$total_data["avg_session_num"] =$dau>0?round($session["num"]/$dau,2):0;
		$total_data["avg_session_num"] = round($total_data["avg_session_num"]/count($dates),2);
		$total_data["avg_session_length"] =$session["val"]?round($session["val"],2):0;
		$total_data["avg_session_length"] = round($total_data["avg_session_length"]/count($dates),2);
		$total_data_rev["avg_adv_show"] =$impressions["total"]["avgshow"];
		$total_data_rev["ecpm"] =$impressions["total"]["ecpm"];
        foreach ($retention as $key => $r) {
            $total_data[$key] = $total_new_users_reten[$key] > 0 ? round(array_sum($r) /$total_new_users_reten[$key], 2)."%" : 0;
        }
		
		$total_data["children"] = $producter;
		$total_data_rev["children"] = $monetizater;
		//array_unshift($monetizater,$total_data_rev);
		echo json_encode(["total_data"=>[$total_data],"advertiser"=>$advertiser,"producter"=>[$total_data],"monetizater"=>[$total_data_rev] ]);exit;
	}
	
	//获取每天人均
	public function json_data($app_id="",$date=[],$country="all",$groupField="groupdate")
	{
		if( !$app_id || empty($date) || !$country )
		{
			exit( json_encode([]));
		}
		if( $app_id==132 )
		{
			$app_id = 112;
		}
		if( $groupField=="groupcountry" )
		{
			$this->countryData($app_id,$date,$country);
		}
		$start = $date[0];
		$end = $date[1];
		$out_data=[];
		$dates = getDateFromRange($start, $end);
		$intavgshow=0;
		$totalgaint=0;
		$totalgarew=0;
		$rewavgshow=0;
		$spavgshow=0;
		$total_day =0;
		$avg_session_length="0.00";
		$avg_session_num="0.00";
		$avg_session_daylength="0.00";
		foreach( $dates as $k=>$v )
		{
			$out_data[$k]["name"] ="(".getweekday($v).")".$v;
			$impressions = $this->getimpressions($app_id,$v,$v,$country);
			$avg_adv_int = $impressions["int"]["avgshow"];
			$avg_adv_rew = $impressions["rew"]["avgshow"];
			$avg_adv_sp = $impressions["sp"]["avgshow"];
            $dau = 	$impressions["active_users"];		
			$session = $this->getuser_time($app_id,$v,$v,$country);
			$out_data[$k]["avg_session_length"] =$session["val"]?$session["val"]:0;
   			$out_data[$k]["avg_session_num"] =$dau>0?round($session["num"]/$dau,2):0;
			$out_data[$k]["avg_session_daylength"] = round( $out_data[$k]["avg_session_length"]*$out_data[$k]["avg_session_num"]/60,2);
			$out_data[$k]["dau"] =$dau;
			$out_data[$k]["avg_adv_int"] =$avg_adv_int;
			$out_data[$k]["avg_adv_rew"] =$avg_adv_rew;
			$out_data[$k]["avg_adv_sp"] =$avg_adv_sp;
			if($dau>0)
			{
				$total_day+=$dau;
			}			
			$intavgshow+=($avg_adv_int*$dau);
			$rewavgshow+=($avg_adv_rew*$dau);
			$spavgshow+=($avg_adv_sp*$dau);
			$avg_session_length+=($out_data[$k]["avg_session_length"]*$dau);
			$avg_session_num+=($out_data[$k]["avg_session_num"]*$dau);
			$avg_session_daylength+=($out_data[$k]["avg_session_daylength"]*$dau);
            $out_data[$k]["producter_showtips"] = isshowtips($app_id,$v,"producter");		
		}
		$day = count($dates);
		$total_day = $total_day==0?1:$total_day;
		$total =array(
		    "name"=>"总计(加权平均)",
			"avg_adv_int"=>round($intavgshow/$total_day,2),
			"avg_adv_rew"=>round($rewavgshow/$total_day,2),
			"avg_adv_sp"=>round($spavgshow/$total_day,2),
			"producter_showtips"=>0,
            "avg_session_num"=>round($avg_session_num/$total_day,2),
			"avg_session_daylength"=>round($avg_session_daylength/$total_day,2),
			"avg_session_length"=>round($avg_session_length/$total_day,2),
			"dau"=>ceil(array_sum(array_column($out_data,"dau"))/$day)
		);
		array_push($out_data,$total);
		echo json_encode($out_data );exit;
	}
	
	//按国家
	private function countryData($app_id="",$date=[],$country="all")
	{
		
		$start = $date[0];
		$end = $date[1];
		$out_data=[];
		$countrys = admincountry();
		if( $country!="all" )
		{
			$countrys = [ $country=>$countrys[$country] ];
		}
		
		foreach( $countrys as $k=>$v )
		{
			
			$impressions = $this->getimpressions($app_id,$start,$end,$k);
			$avg_adv_int = $impressions["int"]["avgshow"];
			$avg_adv_rew = $impressions["rew"]["avgshow"];
			$avg_adv_sp = $impressions["sp"]["avgshow"];
            $dau = 	$impressions["active_users"];		
			$session = $this->getuser_time($app_id,$start,$end,$k);
			$avg_session_length=$session["val"]?$session["val"]:0;
   			$avg_session_num =$dau>0?round($session["num"]/$dau,2):0;
			$avg_session_daylength = round( $avg_session_length*$avg_session_num/60,2);
			$ga_int = $this->geteventval($app_id,"InterShow",$start,$end,$k);
			$ga_rew = $this->geteventval($app_id,"RewardShow",$start,$end,$k);
			$avg_ga_int = $dau>0?round($ga_int/$dau,2):0;
			$avg_ga_rew = $dau>0?round($ga_rew/$dau,2):0;
			$avg_adv_int =$avg_adv_int;
			$avg_adv_rew =$avg_adv_rew;
            $out_data[]=["producter_showtips"=>0,"avg_session_daylength"=>$avg_session_daylength,"avg_ga_int"=>$avg_ga_int,"avg_ga_rew"=>$avg_ga_rew,"name"=>$v,"dau"=>$dau,"avg_session_length"=>round($avg_session_length,2),"avg_session_num"=>$avg_session_num,"avg_adv_int"=>$avg_adv_int,"avg_adv_rew"=>$avg_adv_rew,"avg_adv_sp"=>$avg_adv_sp];			
		}
		echo json_encode($out_data );exit;
	}
		
	//获取备注
	private function getremark($appid)
	{
		return Db::name("app_remark")->where( ["app_id"=>$appid] )->order("date desc")->select();
	}
		
	private function getimpressions($appid,$start="",$end="",$country="all")
	{
		$where="sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
	   $active_users = $this->getactive_users($appid,$start,$end,$country);
		
	   $r=Array
		(
		"int" => Array
			(
				"impressions" =>0,				
				"avgshow" => 0
			),
		"sp" => Array
			(
				"impressions" =>0,				
				"avgshow" => 0
			),
		"rew" => Array
			(
				"impressions" =>0,
				"avgshow" => 0
			)
		);
		 $sum_sql = "select adtype,sum(impression) as impressions from hellowd_adcash_data where {$where} group by adtype";
		 $d= Db::query($sum_sql);
		 if( !empty($d) )
		 {
			 foreach( $d as &$v )
			 {
								  
				$upltv_adtype_data=getupltvfacebook($appid,$start,$end,$country,$v["adtype"]);				  
				$v["impressions"] =($v["impressions"]-$upltv_adtype_data["impression"])<0?0:$v["impressions"]-$upltv_adtype_data["impression"];
				
				 $v["avgshow"] = $active_users<=0?0:number_format($v["impressions"]/$active_users,2);
                 $r[ $v["adtype"] ] = ["impressions"=>$v["impressions"],"avgshow"=>$v["avgshow"] ];				 
			 }
		 }
		 $r["active_users"] =$active_users; 
		 return $r;
	}
    
    //日活
	private function getactive_users($appid,$start="",$end="",$country="all")
	{
		
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
		$active_sql = "select sum(val) as val from hellowd_active_users where {$where}";
		$d= Db::query($active_sql);
		if( !$d[0]["val"] )
		{			
			if($country=="all")
			{
				$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";
				$active_sql = "select sum(val) as val from hellowd_active_users where {$where}";
				$d= Db::query($active_sql);
				if(!empty($d))
				{
					return $d[0]["val"]?$d[0]["val"]:0;
				}
			}
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	//tenjin活跃
	private function tenjindau($appid,$start="",$end="",$country="all")
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
		$active_sql = "select sum(daily_active_users) as val from hellowd_tenjin_report where {$where}";
		$d= Db::query($active_sql);
		
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	//获取事件
	private function geteventval($appid,$event_name,$start="",$end="",$country="all")
	{
		$where="app_id={$appid} and event_name='{$event_name}' and  date>='{$start}' and date<='{$end}' and country='{$country}'";
		$event_sql = "select sum(event_v) as val from hellowd_event_data where {$where}";
		$d= Db::query($event_sql);
		
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}
	
	public function chats($appid="",$spm="active_users",$start="",$end="",$country="all")
	{
		if( $appid=="" )
		{
			$appid = getcache("select_app");
		}
		 setcache("select_app",$appid);
		
		$r = $this->viewchats($appid,$spm,$start,$end,$country);
		
		$this->assign("appid",$appid);
		$this->assign("start",$start);
		$this->assign("end",$end);
		$this->assign("r",$r);
		$this->assign("spm",$spm);
		$this->assign("countrys",admincountry());
		$this->assign("country",$country);
		return $this->fetch('chats');
	}
	
	private function viewchats($appid="",$spm="",$start="",$end="",$country="")
	{
		$out_data=[];
		$text="";		
		$dates = getDateFromRange($start, $end);
		foreach( $dates as $k=>$v )
		{
			$week = getweekday($v);
			$date= date("m月d日",strtotime($v));
		    $out_data[$k]["date"] ="({$week})".$date;
			if( $spm=="avg_session_daylength" )
			{				
				$text ="日均时长(min) ";				
			}elseif( $spm=="avg_session_num" )
			{
				$text ="人均会话 ";
			}elseif( $spm=="avg_session_length" )
			{
				$text ="人均时长(s) ";
			}
			elseif( $spm=="avg_adv_int" )
			{
				$text ="人均插屏 ";
			}elseif( $spm=="avg_adv_rew" )
			{
				$text ="人均激励 ";
			}elseif( $spm=="dau" )
			{
				$text ="活跃 ";
			}
			$out_data[$k]["val"] = $this->property($appid,$spm,$v,$v,$country);
			$out_data[$k]["desc"] = str_replace(array("\r\n", "\r", "\n"), "",(isshowtips($appid,$v,"producter")));
		}
		return ["data"=>json_encode($out_data),"text"=>$text];exit;
	}
	
	
	private function property($appid,$spm,$start,$end,$country)
	{
		$impressions = $this->getimpressions($appid,$start,$end,$country);
		$avg_adv_int = $impressions["int"]["avgshow"];
		$avg_adv_rew = $impressions["rew"]["avgshow"];
		$dau =$this->getactive_users($appid,$start,$end,$country);
		$session = $this->getuser_time($appid,$start,$end,$country);
		$avg_session_length =$session["val"]?$session["val"]:0;
		$avg_session_num =$dau>0?round($session["num"]/$dau,2):0;
		$avg_session_daylength = round( $avg_session_length*$avg_session_num/60,2);
		$array =  ["dau"=>$dau,"avg_adv_int"=>$avg_adv_int,"avg_adv_rew"=>$avg_adv_rew,"avg_session_length"=>$avg_session_length,"avg_session_num"=>$avg_session_num,"avg_session_daylength"=>$avg_session_daylength];
	    return $array[$spm];
	}
}
