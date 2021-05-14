<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;

class Reten extends Base
{

    //推广渠道
    private $promate_media = array(
        ["name" => "全部媒体", "value" => "all", "channel" => "all"],
        ["name" => "Mintegral", "value" => "mintegral_int", "channel" => "1"],
        ["name" => "头条", "value" => "ocean engine_int", "channel" => "32"],
        ["name" => "Facebook", "value" => "Facebook Ads", "channel" => "6"],
        ["name" => "Unityads", "value" => "unityads_int", "channel" => "2"],
        ["name" => "Applovin", "value" => "applovin_int", "channel" => "3"],
        ["name" => "ironSource", "value" => "ironsource_int", "channel" => "7"],
        ["name" => "Vungle", "value" => "vungle_int", "channel" => "4"],
        ['name' => 'Adwords', "value" => "googleadwords_int", "channel" => "5"],
        ['name' => 'Tapjoy', "value" => "tapjoy_int", "channel" => "9"],
        ['name' => 'Chartboost', "value" => "chartboosts2s_int", "channel" => "8"],
        ['name' => 'Tiktok', "value" => "tiktok_int", "channel" => "36"],
        ['name' => 'Adcolony', "value" => "Adcolony_int", "channel" => "31"],
        ['name' => 'Snapchat', "value" => "Snapchat Installs", "channel" => "38"],
        ['name' => 'ASM', "value" => "asm", "channel" => "39"],
        ['name' => 'Organic', "value" => "Organic", "channel" => "0"],

    );

    public function index($appid = "", $start = "", $end = "", $country = "all", $retention = "retention_1", $from="GA", $promate_media="all")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);

        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-8 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        $allcountry = admincountry();
        $index = new Index(request());
        $new_appid = ($appid == 132) ? 112 : $appid;
        $result = $this->getdayreten($index, $new_appid, $start, $end, $country ,$from ,$promate_media);
        $data = $result["list"];
        $total_data = $result["total_data"];
        //$total_data = $this->getreten($index,$new_appid,$start,$end,$country);
        $viewchats = $this->getviewchats($data, $retention);
        $this->assign("viewchats", $viewchats);
        $this->assign("total_data", $total_data);
        $this->assign("country", $country);
        $this->assign("country_name", $allcountry[$country]);
        $this->assign("from_name", $from);
        $this->assign("promate_media", $this->promate_media);
        $this->assign("promate_media_value", $promate_media);
        $this->assign("promate_media_name", $this->get_promate_media_name($promate_media));
        $this->assign("retention", $retention);
        $this->assign("retention_name", $this->getreten_name($retention));
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("index", "three");
        $this->assign("data", $data);
        $this->assign("countrys", $allcountry);
        return $this->fetch();
    }

    private function getviewchats($data, $retention)
    {
        $d = "";
        $dates = array_column($data, 'date');
        foreach ($dates as $v) {
            $d .= "'{$v}',";
        }
        $retentions = array_column($data, $retention);
        return ["dates" => rtrim($d, ","), "retentions" => implode(",", $retentions)];
    }

    private function get_promate_media_name($promate_media)
    {
        $promate_media_arr = array_column($this->promate_media,"name","value");
        return isset($promate_media_arr[$promate_media])?$promate_media_arr[$promate_media]:"None";
    }

    private function getreten_name($retention)
    {
        $array = array(
            "retention_1" => "Day1",
            "retention_2" => "Day2",
            "retention_3" => "Day3",
            "retention_7" => "Day7",
            "retention_14" => "Day14",
            "retention_28" => "Day28"
        );
        return $array[$retention];
    }

    private function getdayreten($index, $app_id, $start, $end, $country = "all", $from="GA" ,$promate_media = "all")
    {
        $out_data = [];
        $dates = getDateFromRange($start, $end);
		$total_new_users1=0;
        $total_new_users = ["retention_1" =>0, "retention_2" =>0, "retention_3" =>0, "retention_7" =>0, "retention_14" =>0, "retention_28" =>0];
        $retention = ["retention_1" => [], "retention_2" => [], "retention_3" => [], "retention_7" => [], "retention_14" => [], "retention_28" => []];
        foreach ($dates as $k => $v) {

            $res = $this->getreten($index, $app_id, $v, $v, $country, $from ,$promate_media);
            $retention["retention_1"][] = $res["retention_1"] * $res["new_user"];
            $retention["retention_2"][] = $res["retention_2"] * $res["new_user"];
            $retention["retention_3"][] = $res["retention_3"] * $res["new_user"];
            $retention["retention_7"][] = $res["retention_7"] * $res["new_user"];
            $retention["retention_14"][] = $res["retention_14"] * $res["new_user"];
            $retention["retention_28"][] = $res["retention_28"] * $res["new_user"];
			if($res["retention_1"]>0)
			{
				$total_new_users["retention_1"] += $res["new_user"];
			}
			if($res["retention_2"]>0)
			{
				$total_new_users["retention_2"] += $res["new_user"];
			}
            if($res["retention_3"]>0)
			{
				$total_new_users["retention_3"] += $res["new_user"];
			}
            if($res["retention_7"]>0)
			{
				$total_new_users["retention_7"] += $res["new_user"];
			}
            if($res["retention_14"]>0)
			{
				$total_new_users["retention_14"] += $res["new_user"];
			}
            if($res["retention_28"]>0)
			{
				$total_new_users["retention_28"] += $res["new_user"];
			}
			$total_new_users1 += $res["new_user"];			
            $res["date"] = $v;
            $res["producter_showtips"] = isshowtips($app_id, $v, "producter");
            $out_data[$k] = $res;
        }
        $total_data = [];
        foreach ($retention as $key => $r) {
            $total_data[$key] = $total_new_users[$key] > 0 ? round(array_sum($r) / $total_new_users[$key], 2) : 0;
        }
        $total_data["new_user"] = $total_new_users1;
        return ["total_data" => $total_data, "list" => $out_data];
    }

    //留存
    private function getreten($index, $appid, $start = "", $end = "", $country = "all", $from="GA" ,$promate_media = "all" )
    {

        if ($from=="GA"){
            $out = [1, 2, 3, 7, 14, 28];
            $res = [];
            foreach ($out as $k => $vv) {
                $val = $this->getdayreten1($appid, $start, $end, $country, $vv );
                $res["retention_" . $vv] = $val;
            }
            $spend = $index->getspendtotal($appid, $start, $end, "all", $country);
            $new_users = $index->getnew_users($appid, $start, $end, $country);
            if ($spend["installs"] > $new_users) {
                $new_users = $spend["installs"];
            }
            $res["new_user"] = $new_users;
        }else{
            $res = $this->getdayreten2($appid, $start, $end, $country ,$promate_media);
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

    private function getdayreten2($appid, $start, $end, $country,$promate_media)
    {
//        $start = date("Y-m-d", strtotime("+1 day", strtotime($start)));
//        $end = date("Y-m-d", strtotime("+1 day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' ";
        $where .= " and country='{$country}' ";
        if ($promate_media=='Facebook Ads'){
            $promate_media_where = " in ('Instagram Installs', 'Unattributed', 'Off-Facebook Installs','Facebook Ads')";
        }else{
            $promate_media_where = " = '{$promate_media}'";
        }
        $where .= " and media_source {$promate_media_where} ";
        $reten_sql = "select * from hellowd_retention_adjust where {$where}";
        $d = Db::query($reten_sql);
        $res = [
            'retention_1' => 0,
            'retention_2' => 0,
            'retention_3' => 0,
            'retention_7' => 0,
            'retention_14' => 0,
            'retention_28' => 0,
            'new_user' => 0,
        ];
        if (!empty($d)) {
            if (count($d)==1){
                $res['retention_1'] = $d[0]["retention_1"] ? round($d[0]["retention_1"] * 100, 2) : 0;
                $res['retention_2'] = $d[0]["retention_2"] ? round($d[0]["retention_2"] * 100, 2) : 0;
                $res['retention_3'] = $d[0]["retention_3"] ? round($d[0]["retention_3"] * 100, 2) : 0;
                $res['retention_7'] = $d[0]["retention_7"] ? round($d[0]["retention_7"] * 100, 2) : 0;
                $res['retention_14'] = $d[0]["retention_14"] ? round($d[0]["retention_14"] * 100, 2) : 0;
                $res['retention_28'] = $d[0]["retention_28"] ? round($d[0]["retention_28"] * 100, 2) : 0;
                $res['new_user'] = $d[0]["add_user"] ? $d[0]["add_user"] : 0;
            }else{
                $res['new_user'] = array_sum(array_column($d,'add_user'));
                if ($res['new_user']){
                    $res['retention_1'] = round(array_sum(array_column($d,'retention_user_1'))/$res['new_user'] * 100, 2);
                    $res['retention_2'] = round(array_sum(array_column($d,'retention_user_2'))/$res['new_user'] * 100, 2);
                    $res['retention_3'] = round(array_sum(array_column($d,'retention_user_3'))/$res['new_user'] * 100, 2);
                    $res['retention_7'] = round(array_sum(array_column($d,'retention_user_7'))/$res['new_user'] * 100, 2);
                    $res['retention_14'] = round(array_sum(array_column($d,'retention_user_14'))/$res['new_user'] * 100, 2);
                    $res['retention_28'] = round(array_sum(array_column($d,'retention_user_28'))/$res['new_user'] * 100, 2);
                }
            }
        }
        return $res;
    }
	
	//新增30天留存查看
	public function overview($appid="",$start="",$end=""){
		if ($appid == "") {
            $appid = getcache("select_app");
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-8 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
		$allcountry = admincountry();
		$this->assign("start", $start);
        $this->assign("end", $end);
		$this->assign("countrys", $allcountry);
        return $this->fetch();
	}
	
	public function reten_json($date=[],$country="all"){
		$appid = getcache("select_app");
		list($start,$end) = $date;
		$out_data = [];
        $dates = getDateFromRange($start, $end);
		$index = new Index(request());
		$reten_total =[];
		$nn =0;
		foreach ($dates as $k => $v){
			 $list = $this->get_new_reten($appid, $v, $v, $country);
			 $new_users = $index->getnew_users($appid, $v, $v, $country);
			 $nn+=$new_users;
			 foreach($list as $key=>$vv)
			 {
				 
				 if($vv!="---")
				 {
					 if(isset($reten_total[$key]))
					 {
						 $val = $vv*$new_users;
						 $reten_total[$key]["val"]+=$val;
						 $reten_total[$key]["new_users"]+=$new_users;
					 }else{
						 $val = $vv*$new_users;
						 $reten_total[$key] = ["val"=>$val,"new_users"=>$new_users];
					 }
				 }
			 }
			 
			 $row = array_merge($list,["date"=>$v,"new_users"=>$new_users]);
			 $out_data[] = $row;
		 }
		 $total_data =[];
		
		 if(!empty($reten_total))
		 {
			 foreach($reten_total as $kkk=>$r)
			 {
				 $total_data[$kkk] =$r["new_users"]>0?round($r["val"]/$r["new_users"],2):0;
				 
			 }
		 }
		 $total_data["date"] ="加权平均";
		 $total_data["new_users"] =$nn;
		 array_unshift($out_data,$total_data);
		echo json_encode($out_data);exit;
	}
	
	private function get_new_reten($appid, $start, $end, $country){
		$start = date("Y-m-d", strtotime("+1 day", strtotime($start)));
        $end = date("Y-m-d", strtotime("+1 day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $reten_sql = "select reten_day,val as val from hellowd_new_reten where {$where} group by reten_day";
        $d = Db::query($reten_sql);
		$retenList =[];
		$res = $this->arrayToKey($d);
		for($i=1;$i<=30;$i++)
		{
			if(isset($res[$i]))
			{
				$retenList["day".$i] = round($res[$i]*100,2);
			}else{
				$retenList["day".$i] = "---";
			}
		}
        return $retenList;
	}
	
	private function arrayToKey($arr){
		$res =[];
		if(!empty($arr))
		{
			foreach($arr as $v)
			{
				$res[$v["reten_day"]] = $v["val"];
			}
		}
		return $res;
	}
}
