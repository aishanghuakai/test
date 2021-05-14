<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use think\Session;

class Adset extends Base
{

    public function index($appid = "")
    {
        $role = getuserrole();

        if ($appid == "") {
            $appid = getcache("select_app");
            if ($role == "material") {
                if (!$appid) {
                    $appid = 77;
                }
            }
        }
        setcache("select_app", $appid);
        $start = date("Y-m-d", strtotime("-1 day"));
        $end = date("Y-m-d", strtotime("-1 day"));
        $res = $this->getdata($appid, $start, $end, "all", "one");
        $this->assign("res", admin_array_sort($res['data'], "ctr", 'desc'));
        $this->assign("total_data", $res['total_data']);
        $this->assign("start", $start);
        $this->assign("end", $end);
        return $this->fetch('index');
    }

    private function getdata($appid = "", $start = "", $end = "", $platform_type, $app = "")
    {
        $where = "date>='{$start}' and date<='{$end}' and ad_name!=''";
        if ($platform_type == "all") {
            $where .= " and platform_type in(3,6)";
        } else {
            $where .= " and platform_type={$platform_type}";
        }

        if ($app == "one") {
            $where .= " and app_id={$appid}";
        }

        $sum_sql = "select ad_id,ad_name,adset_name,target_id,platform_type,campaign_name,sum(installs) as installs,sum(impressions) as impressions,sum(clicks) as clicks, round(sum(spend),2) as spend from hellowd_adspend_data where {$where} and campaign_name not like '%AAA%' group by ad_id UNION 

select video_id as ad_id,video_name as ad_name,adset_name,advertiser_id as target_id,platform_type,campaign_name,sum(installs) as installs,
sum(impressions) as impressions,sum(clicks) as clicks, round(sum(spend),2) 
as spend from hellowd_aaa_report where {$where} group by video_id";
        $d = Db::query($sum_sql);
        $total_data = [
            'spend' => 0,
            'installs' => 0,
            'impressions' => 0,
            'clicks' => 0,
            'ctr' => 0,
            'cvr' => 0,
            'cpi' => 0,
            'ecpm' => 0,
        ];
        if (!empty($d)) {
            foreach ($d as &$vv) {
                $spend = $vv["spend"] ? $vv["spend"] : "0.0";
                $installs = $vv["installs"] ? $vv["installs"] : 0;
                $impressions = $vv["impressions"] ? $vv["impressions"] : 0;
                $clicks = $vv["clicks"] ? $vv["clicks"] : 0;
                $vv["ctr"] = $vv["impressions"] <= 0 ? 0 : number_format($clicks * 100 / $impressions, 2);
                $vv["ecpm"] = $vv["impressions"] <= 0 ? 0 : number_format($spend * 1000 / $impressions, 2);
                $vv["cvr"] = $vv["clicks"] <= 0 ? 0 : number_format($installs * 100 / $clicks, 2);
                $vv["cpi"] = $installs <= 0 ? 0 : number_format($spend / $installs, 2);
                $total_data['spend'] += $spend;
                $total_data['installs'] += $installs;
                $total_data['impressions'] += $impressions;
                $total_data['clicks'] += $clicks;
            }
            if ($total_data['impressions'] > 0) {
                $total_data['ctr'] = number_format($total_data['clicks'] * 100 / $total_data['impressions'], 2);
                $total_data['ecpm'] = number_format($total_data['spend'] * 1000 / $total_data['impressions'], 2);
            }
            if ($total_data['clicks'] > 0) {
                $total_data['cvr'] = number_format($total_data['installs'] * 100 / $total_data['clicks'], 2);
            }
            if ($total_data['installs'] > 0) {
                $total_data['cpi'] = number_format($total_data['spend'] / $total_data['installs'], 2);
            }
        }
        return ['data' => $d, 'total_data' => $total_data];
    }

    public function data_json($start = "", $end = "", $platform_type, $app)
    {
        $appid = getcache("select_app");
        $res = $this->getdata($appid, $start, $end, $platform_type, $app);
        $this->assign("res", admin_array_sort($res['data'], "ctr", 'desc'));
        $this->assign("total_data", $res['total_data']);
        return $this->fetch('data_json');
    }
	
	
	public function plat_json($start = "", $end = "", $ad_id="")
    {
        $where = "date>='{$start}' and date<='{$end}' and ad_id='{$ad_id}'";               
        $sum_sql = "select publisher_platform,platform_position,sum(installs) as installs,sum(impressions) as impressions,sum(clicks) as clicks, round(sum(spend),2) as spend from hellowd_platform_report where {$where} group by publisher_platform,platform_position";
        $d = Db::query($sum_sql);        
        if (!empty($d)) {
            foreach ($d as &$vv) {
                $spend = $vv["spend"] ? $vv["spend"] : "0.0";
                $installs = $vv["installs"] ? $vv["installs"] : 0;
                $impressions = $vv["impressions"] ? $vv["impressions"] : 0;
                $clicks = $vv["clicks"] ? $vv["clicks"] : 0;
                $vv["ctr"] = $vv["impressions"] <= 0 ? 0 : number_format($clicks * 100 / $impressions, 2);
                $vv["ecpm"] = $vv["impressions"] <= 0 ? 0 : number_format($spend * 1000 / $impressions, 2);
                $vv["cvr"] = $vv["clicks"] <= 0 ? 0 : number_format($installs * 100 / $clicks, 2);
                $vv["cpi"] = $installs <= 0 ? 0 : number_format($spend / $installs, 2);
            }
        }
        $this->assign("res",$d);
        return $this->fetch('plat_json');
    }

    /**
     * 个人产品数据
     */
    public function personal_product($start='',$end=''){
        $admin_id = Session::get('admin_userid');
        $app = Db::name('admin')->where(['id'=>$admin_id])->value('allow_applist');
        $app = explode(',',$app);
        $start = $start?$start:date("Y-m-d", strtotime("-30 day"));
        $end = $end?$end:date("Y-m-d", strtotime("-1 day"));
        $role = getuserrole();
        $where = [
            'date' => ['between',[$start,$end]],
        ];
        if($role!=='super'){
            $where['app_id'] = ['in',$app];
        }
        $app_spend = Db::name('adspend_data')
            ->field('app_id,sum(spend) as spend')
            ->where($where)
            ->group('app_id')
            ->order('app_name desc')
            ->select();
        $product_spend_out = [];
        foreach ($app_spend as $app_spend_item){
            $product_info = Db::name('app')->field('app_base_id,platform')->where(['id'=>$app_spend_item['app_id']])->find();
            $product_name = Db::name('app_base')->where(['id'=>$product_info['app_base_id']])->value('name');
            if (!isset($product_spend_out[$product_info['app_base_id']])){
                $product_spend_out[$product_info['app_base_id']] = [
                    'spend'=>0,
                    'app_name'=>$product_name,
                    'app_base_id'=>$product_info['app_base_id'],
                    'ios'=>0,
                    'android'=>0,
                ];
            }
            $product_spend_out[$product_info['app_base_id']]['spend'] += $app_spend_item['spend'];
            $product_spend_out[$product_info['app_base_id']][$product_info['platform']] = $app_spend_item['spend'];
        }
        $this->assign("start",$start);
        $this->assign("end",$end);
        $this->assign("list",$product_spend_out);
        return $this->fetch('personal_product');

    }
	private $userList =array(
	    "LS"=>"刘爽",
		"LQ"=>"刘桥",
		"ZXW"=>"张星伟",
		"HYC"=>"黄轶涔",
		"XJS"=>"谢京松",
		"WST"=>"王姝潼",
		"XSY"=>"肖诗羽"
	);
	//制作人消耗
	public function producer_product(){
		$start = date("Y-m-d", strtotime("-4 day"));
        $end = date("Y-m-d", strtotime("-2 day"));
		$this->assign("start",$start);
        $this->assign("end",$end);
		$product = $this->getallplatforms();
		$this->assign("product",$product);
		return $this->fetch('producer_product');
	}
	
	private function getallplatforms()
	{
		$sql =" select id,package_name,platform,app_base_id from hellowd_app where app_base_id is not null";
		$result = Db::query($sql);
		if( !empty($result) )
		{
			foreach($result as &$r)
			{
				 $r["id"] = (string)$r["id"];
				 $row = Db::table("hellowd_app_base")->field("name,CONCAT('http://console.gamebrain.io',icon) as imageUrl")->where("id",$r["app_base_id"])->find();
				 $row["name"] = $row["name"]."-".$r['platform'];
				 $r =array_merge($r,$row);
			}
		}
		return $result;
	}
	
	public function producer_json($product_id="",$date=[]){
		$users = $this->userList;
		list($start,$end) = $date;
		$out =[];
		foreach($users as $k=>$v)
		{
			$row = $this->get_one_data($k,$start,$end,$product_id);
			$row["name"] = $v;
			$row["id"] = $k;
			$row["hasChildren"] = true;
			$row["children"] =[];
			$out[] = $row;
		}
		echo json_encode($out);exit;
	}
	
	public function children_producer_json($name="",$date=[],$product_id=""){
		list($start,$end) = $date;
		$where =" ";
		if($product_id!="all")
		{
			$where =" and app_id={$product_id}";
		}
		$sql="SELECT DISTINCT  ad_name,app_id,SUM(spend) as spend,SUM(impressions) as impressions,SUM(clicks) as clicks,SUM(installs) as installs
  from hellowd_adspend_data WHERE platform_type=6 and date>='{$start}' and date<='{$end}' and ad_name like '%{$name}%' {$where} GROUP BY app_id,ad_name";
        $res = Db::query($sql);
		$result =[];
		$out =[];
		if(!empty($res))
		{
			foreach($res as $v)
			{
				$result[$v["app_id"]][] = $v;
			}
		}

		if(!empty($result))
		{
			foreach($result as $key=>$vv)
			{
				$num = 0;
				$spend=0;
				$impressions=0;
				$clicks=0;
				$installs=0;
				$ctr=0;
				$cvr=0;
				if(!empty($vv))
				{
					$num =count($vv);
					$spend= round(array_sum(array_column($vv,"spend")),2);
					$impressions=array_sum(array_column($vv,"impressions"));
					$clicks=array_sum(array_column($vv,"clicks"));
					$installs=array_sum(array_column($vv,"installs"));
					$ctr = $impressions <= 0 ? 0 : number_format($clicks * 100 / $impressions, 2);
					$cvr = $clicks <= 0 ? 0 : number_format($installs * 100 / $clicks, 2);
				}
				$row = ["num"=>$num,"spend"=>$spend,"impressions"=>$impressions,"clicks"=>$clicks,"installs"=>$installs,"ctr"=>$ctr,"cvr"=>$cvr];
				$row["name"] = getapp_name($key);
				$row["id"] = $key;
				$out[] =$row;
			}
		}
		echo json_encode($out);exit;
	}
	
	private function get_one_data($name,$start,$end,$product_id){
		
		$where =" ";
		if($product_id!="all")
		{
			$where =" and app_id={$product_id}";
		}
		$sql="SELECT DISTINCT  ad_name,count(*),SUM(spend) as spend,SUM(impressions) as impressions,SUM(clicks) as clicks,SUM(installs) as installs
  from hellowd_adspend_data WHERE platform_type=6 and date>='{$start}' and date<='{$end}' and ad_name like '%{$name}%' {$where} GROUP BY ad_name";
        $res = Db::query($sql);
		$num = 0;
		$spend=0;
		$impressions=0;
		$clicks=0;
		$installs=0;
		$ctr=0;
		$cvr=0;
		if(!empty($res))
		{
			$num =count($res);
			$spend= round(array_sum(array_column($res,"spend")),2);
			$impressions=array_sum(array_column($res,"impressions"));
			$clicks=array_sum(array_column($res,"clicks"));
			$installs=array_sum(array_column($res,"installs"));
			$ctr = $impressions <= 0 ? 0 : number_format($clicks * 100 / $impressions, 2);
			$cvr = $clicks <= 0 ? 0 : number_format($installs * 100 / $clicks, 2);
		}
		return ["num"=>$num,"spend"=>$spend,"impressions"=>$impressions,"clicks"=>$clicks,"installs"=>$installs,"ctr"=>$ctr,"cvr"=>$cvr];
	}
}
