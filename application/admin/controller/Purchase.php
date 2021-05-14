<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use app\admin\controller\Index as E;
use \think\Db;

class Purchase extends Base
{
    public function index($appid = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $start = date("Y-m-d", strtotime("-9 day"));
        $end = date("Y-m-d", strtotime("-2 day"));
        $this->assign("appid", $appid);
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign('list', []);
        $this->assign("countryList", admincountry());
        $this->assign("chartData", $this->getchartdata($appid, $start, $end));
        return $this->fetch('list');
    }

    public function edit($appid = ""){
		if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
		$this->assign("appid", $appid);
		return $this->fetch('edit');
	}
    private function getchartdata($appid, $start, $end)
    {
        $dates = getDateFromRange($start, $end);
        $date = "";
        $val = "";
        foreach ($dates as $k => $v) {
            $date .= "'{$v}',";
            $where = "app_id={$appid} and  date>='{$v}' and date<='{$v}'";
            $money = '0.00';
            $row = Db::name("purchase_details")->field('sum(total_usd_money) as total_usd_money')->where($where)->find();
            if (!empty($row)) {
                $money = $row["total_usd_money"];
            }
            $val .= $money . ",";
        }
        $date = rtrim($date, ",");
        $val = rtrim($val, ",");
        return ["date" => $date, "val" => $val];
    }
	
	public function price_json($appid=""){
		
		if ($appid) {
			$res = Db::name("purchase_details")->field('product_id,product_name')->where(["app_id"=>$appid,"total_usd_money"=>0])->group('product_id')->select();
            echo json_encode($res);
            exit;
        }
        echo json_encode([]);
        exit;
	}
	
	public function add_price($app_id="",$product_id="",$product_name="",$price=""){
		if($app_id && $product_id && $price)
		{
			$total_usd_money = $price*0.7;
			Db::execute("update hellowd_purchase_details set total_usd_money=units*{$total_usd_money} where product_id='{$product_id}' and app_id={$app_id} and total_usd_money=0");			
			$row = Db::name("purchase_product")->where(["app_id"=>$app_id,"product_id"=>$product_id])->find();
            if(empty($row))
			{
				Db::name("purchase_product")->insert(
				   ["app_id"=>$app_id,"product_id"=>$product_id,"product_name"=>$product_name,"price"=>$price]
				);
			}				
		}
		exit("ok");
	}
	
	public function add_rate($id="0",$channel="",$month="",$val="",$app_id="",$is_default="0")
	{
		if($channel && $month && $app_id)
		{
			$where =["channel"=>$channel,"month"=>$month,"app_id"=>$app_id];
			if($id>0)
			{
				Db::name("rate")->where(["id"=>$id])->update(["val"=>$val,"is_default"=>$is_default]);
			}else{
				$where['val']=$val;
				$where['is_default']=$is_default;
				Db::name("rate")->insert($where);
			}		
		}
		exit("ok");
	}

    public function json_data($date = [], $appid = "", $country = "all")
    {
        if ($appid) {
            list($start, $end) = $date;
            $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
            if ($country != "all") {
                $where .= " and country='{$country}'";
            }

            $sql = "select product_id,product_name,sum(units) as units,sum(total_usd_money) as total_usd_money from hellowd_purchase_details where {$where} group by product_id";
            $res = Db::query($sql);
            echo json_encode($res);
            exit;
        }
        echo json_encode([]);
        exit;
    }

    /**
     * 获取内购次数统计 页面
     */
    public function times($appid = ""){
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $start = date("Y-m-d", strtotime("-9 day"));
        $end = date("Y-m-d", strtotime("-2 day"));
        $this->assign("appid", $appid);
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign('list', []);
        $this->assign("countryList", admincountry());
        return $this->fetch('times');
    }

    /**
     * 获取内购次数统计 数据
     */
    public function times_json_data($date = [], $appid = "", $time_zone = "", $country="all",$is_download=false,$is_times=1){
        if ($appid) {
            if (is_array($date)) {
                list($start, $end) = $date;
            } else {
                list($start, $end) = explode(",", $date);
            }
            $time_zone_ext = "";
            if (in_array($time_zone,["pst","pdt","utc"])){
                $time_zone_ext = "_".$time_zone;
            }
            $field = " install_date".$time_zone_ext.",event_date".$time_zone_ext." ";
            $field .= " ,count(id) as count,sum(money) as money ";
            $where = " gb_id=".$appid;
            if ($country!="all") $where .= " and country = '".$country."' ";
            $where .= " and install_date".$time_zone_ext.">='".$start."' ";
            $where .= " and install_date".$time_zone_ext."<='".$end."' ";
            $group_by = " install_date".$time_zone_ext.",event_date".$time_zone_ext." ";
            $order_by = " install_date".$time_zone_ext.",event_date".$time_zone_ext." ";
            $sql = "SELECT ".$field." FROM hellowd_adjust_purchase_time_zone ";
            $sql .= " WHERE ".$where;
            $sql .= " GROUP BY ".$group_by;
            $sql .= " ORDER BY ".$order_by;
            $res = Db::query($sql);
            $out_data = [];
            $max_day = 0;
            foreach ($res as $res_item){
                $item_day = (strtotime($res_item['event_date'.$time_zone_ext])-strtotime($res_item['install_date'.$time_zone_ext]))/86400;
                $out_data[$res_item['install_date'.$time_zone_ext]]['date'] = $res_item['install_date'.$time_zone_ext];
                $out_data[$res_item['install_date'.$time_zone_ext]]['money_'.$item_day] = $res_item['money']*0.7;
                $out_data[$res_item['install_date'.$time_zone_ext]]['count_'.$item_day] = $res_item['count'];
                $max_day = $item_day>$max_day?$item_day:$max_day;
            }
            if ($is_download) {
                return $this->download($max_day,$out_data,$is_times);
            }
            echo json_encode(['time_zone'=>$time_zone,'max_day'=>$max_day,'out_data'=>$out_data]);
            exit;
        }
        echo json_encode([]);
        exit;
    }

    private function download($max_day,$data,$is_times=1)
    {
        if (!empty($data)) {
            $xlsCell = [
                ["date", '安装日期'],
            ];
            $now_x = 0;
            while ($now_x<$max_day){
                array_push($xlsCell,["count_".$now_x,"安装".$now_x."天"]);
                $now_x++;
            }
            $xlsData = [];
            foreach ($data as $key => $v) {
                $item = [];
                $now_x = 0;
                $item['date'] = $key;
                while ($now_x<$max_day){
                    if ($is_times){
                        $item["count_".$now_x] = isset($v["count_".$now_x])?$v["count_".$now_x]:0;
                    }else{
                        $item["count_".$now_x] = isset($v["money_".$now_x])?$v["money_".$now_x]:0;
                    }
                    $now_x++;
                }
                $xlsData[] = $item;
            }
            $Index = new E(request());
            $name = "内购".($is_times?"次数":"金额")."数据下载" . date("YmdHis");
            echo $Index->exportExcel($name, $xlsCell, $xlsData, $name, $name);
            exit;
        }
    }
}
