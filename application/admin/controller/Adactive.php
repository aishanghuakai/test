<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;

class Adactive extends Base
{
    public function action($appid="")
	{
		 
		if( $appid=="" )
		{
			$appid = getcache("select_app");
		}
		 setcache("select_app",$appid);
					
		
		$country = admincountry();
		
		return $this->fetch('action',["country"=>$country,"appid"=>$appid ] );
	}
	
	public function action_detail($appid="",$start_date="",$end_date="")
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
		if( $start_date==$end_date )
			{
				$date = $start_date;
			}else{
				$date = "---";
			}
		foreach( $country as $kk=>$vvv )
		{
			$where ="";
			if($kk!="all")
			{
				$where = " and country='{$kk}'";
			}
			$app_sql = "select round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' {$where}";
			$r = Db::query($app_sql);
            $ac_sql ="select sum(val) as active_num from hellowd_active_users where app_id={$appid}  and date>='{$start_date}' and date<='{$end_date}' and country='{$kk}'";			
            $user_active =Db::query($ac_sql);			
			$result[$kk]["revenue"] = isset($r["0"]["revenue"])?$r["0"]["revenue"]:"0.0";
            $result[$kk]["active_num"] =isset($user_active["0"]["active_num"])?$user_active["0"]["active_num"]:"0";	
            $result[$kk]["avg_rev"] =($result[$kk]["active_num"]=="0")?"0":round($result[$kk]["revenue"]/$result[$kk]["active_num"],2);
            $result[$kk]["date"] = $date;
            $result[$kk]["name"] = $vvv;
            $result[$kk]["country"] =$kk;			
		}
		return $this->fetch('action_detail',[ "data"=>$result,"start_date"=>$start_date,"end_date"=>$end_date,"date"=>$date,"oneapp"=>$oneapp ] );
	}
	
	
	public function user_active_body($appids="",$start_date="",$end_date="",$isgroupday="1")
	{
		$appids = rtrim($appids,",");
		if( !$appids )
		{
			exit("no");
		}
		$search_app = Db::name("app")->field("id,app_name as name,platform")->where("id in({$appids})")->select();
		if( $isgroupday=="1" ){//汇总
		    if( $start_date==$end_date )
			{
				$date = $start_date;
			}else{
				$date = "---";
			}
			foreach( $search_app as &$s )
			{
				$app_sql = "select round(sum(revenue),2) as revenue from hellowd_adcash_data where sys_app_id={$s["id"]} and date>='{$start_date}' and date<='{$end_date}'";
				$r = Db::query($app_sql);
				$user_active = Db::query("select sum(val) as active_num from hellowd_active_users where app_id={$s["id"]} and country='all' and date>='{$start_date}' and date<='{$end_date}' ");			
				$s["revenue"] = isset($r["0"]["revenue"])?$r["0"]["revenue"]:"0.0";
                $s["active_num"] =isset($user_active["0"]["active_num"])?$user_active["0"]["active_num"]:"0";	
                $s["avg_rev"] =$s["active_num"]=="0"?"0":round($s["revenue"]/$s["active_num"],2);
                $s["date"] = $date;				
			}
		}
		return $this->fetch('user_active_body',["data"=>$search_app]);
	}
}
