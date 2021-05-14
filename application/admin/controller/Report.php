<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use \app\admin\controller\Index;
use \app\admin\controller\Survey;

//报告下载

class Report extends Base
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
      $start = date("Y-m-d", strtotime("-7 day"));
      $end = date("Y-m-d", strtotime("-1 day"));
	  $this->assign("start",$start);
	  $this->assign("end",$end);
	  $this->assign("countryList", admincountry());
	  return $this->fetch('index',[]);
    }
	
	public function getData($columns=[],$date=[],$country="all"){
		
		list($start,$end) = $date;
		$out_data = [];
		$appid = getcache("select_app");
        $dates = getDateFromRange($start, $end);
		$index = new Index( request() );
		$Survey = new Survey( request() );
        foreach ($dates as $k => $v) {
            $row =[];
			$r= $index->gettotaldata($appid, $v, $v, $country);
			$rr = $this->get_other_data($r,$Survey,$appid,$v,$v,$country);
			$row["date"] = $v;			
			$out_data[$k] = array_merge($row,$rr);        
        }
		echo json_encode($out_data);exit;
	}
	
	public function get_other_data($row,$obj,$app_id,$start,$end,$country){
        $out=[];
		$day_dau = $row["revenue"]["total"]["active_users"];
		$r = $obj->getuser_time($app_id,$start,$end,$country);
		$reten = $obj->getreten($app_id,$start, $end, $country);
		$out["purchase"] = $row["purchase"];
		$out["total_revenue"] = round($row["revenue"]["total"]["revenue"],2);
		$out["ad_revenue"] = round($out["total_revenue"]-$out["purchase"],2);
		$out["avg_rew"] = round($row["revenue"]["rew"]["avgshow"],2);
		$out["avg_int"] = round($row["revenue"]["int"]["avgshow"],2);
		$out["rew_ecpm"] = $row["revenue"]["rew"]["ecpm"];
		$out["int_ecpm"] = $row["revenue"]["int"]["ecpm"];
		$out["dauarpu"] = $row["revenue"]["total"]["dauarpu"];
		$out["ltv0"] = $row["ltv0"];
		$out["roi0"] = $row["roi0"];
		//$out["ad_revenue"] = $row["ad_revenue"];
		$out["spend"] = $row["spend"]["spend"];
		$out["installs"] = $row["spend"]["installs"];
		$out["cpi"] = $row["spend"]["cpi"];
		$out["active_users"] =$day_dau;
		$out["roi"] =$row["roi"];
		$out["new_users"] = $row["new_users"];
		$out["avg_session_num"] = $day_dau>0?round($r["num"]/$day_dau,2):0;
		$out["avg_session_length"] = $r["val"]?round($r["val"],2):0;
		return array_merge($out,$reten);
	}
	
}
