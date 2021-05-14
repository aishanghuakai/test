<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use think\Session;

//Campagin
class Campagin extends Base
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
        $start_date = date("Y-m-d", strtotime("-2 day"));
        $end_date = date("Y-m-d", strtotime("-2 day"));
        $list = $this->getCampagin($appid, $start_date, $end_date, "6");
        $total = $this->getplatformtotal($appid, $start_date, $end_date, "6");
        $this->assign("list", $list);
        $this->assign("total", $total);
        $this->assign("appid", $appid);
        $this->assign("start_date", $start_date);
        $this->assign("end_date", $end_date);
        return $this->fetch();
    }

    //按平台汇总
    private function getplatformtotal($appid, $start_date, $end_date, $platform = "6", $keyword = "")
    {
        $out_data = ["cpi" => "0.00", "ctr" => 0, "cvr" => 0, "installs" => 0, "impression" => 0, "clicks" => 0, "spend" => "0.00"];
        $where = "";
        if ($keyword != "") {
            $where = " and campaign_name like '%{$keyword}%'";
        }
        $sql = "select sum(installs) as installs,sum(impressions) as impression,sum(clicks) as clicks,round(sum(spend),2) as spend from hellowd_adspend_data where app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' and platform_type={$platform} {$where}";
        $res = Db::query($sql);
        if (!empty($res) && isset($res[0]) && !empty($res[0])) {
            foreach ($res as &$v) {
                $v["campaign_name"] = "总计";
                $v["installs"] = $v["installs"] ? (int)$v["installs"] : 0;
                $v["impression"] = $v["impression"] ? (int)$v["impression"] : 0;
                $v["clicks"] = $v["clicks"] ? (int)$v["clicks"] : 0;
                $v["spend"] = $v["spend"] ? (float)$v["spend"] : 0;
                $v["cpi"] = $v["installs"] <= 0 ? "0" : number_format($v["spend"] / $v["installs"], 2);
                $v["ctr"] = $v["impression"] <= 0 ? "0" : number_format($v["clicks"] * 100 / $v["impression"], 2);
                $v["cvr"] = $v["clicks"] <= 0 ? "0" : number_format($v["installs"] * 100 / $v["clicks"], 2);
                $out_data = $v;
            }
        }
        return $out_data;
    }

    private function getCampagin($appid, $start_date, $end_date, $platform = "6", $keyword = "")
    {
        $where = "";
        if ($keyword != "") {
            $where = " and campaign_name like '%{$keyword}%'";
        }
        if ($platform == "32") {
            $sql = "select concat_ws('--',adset_name,target_id) as campaign_name,sum(installs) as installs,sum(impressions) as impression,sum(clicks) as clicks,round(sum(spend),2) as spend from hellowd_adspend_data where app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' and platform_type={$platform} {$where} group by adset_id";
        } else {
            $sql = "select campaign_name,sum(installs) as installs,sum(impressions) as impression,sum(clicks) as clicks,round(sum(spend),2) as spend from hellowd_adspend_data where app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' and platform_type={$platform} {$where} group by campaign_id";
        }
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$v) {
                $v["installs"] = $v["installs"] ? (int)$v["installs"] : 0;
                $v["impression"] = $v["impression"] ? (int)$v["impression"] : 0;
                $v["clicks"] = $v["clicks"] ? (int)$v["clicks"] : 0;
                $v["spend"] = $v["spend"] ? (float)$v["spend"] : 0;
                $v["cpi"] = $v["installs"] <= 0 ? "0" : number_format($v["spend"] / $v["installs"], 2);
                $v["ctr"] = $v["impression"] <= 0 ? "0" : number_format($v["clicks"] * 100 / $v["impression"], 2);
                $v["cvr"] = $v["clicks"] <= 0 ? "0" : number_format($v["installs"] * 100 / $v["clicks"], 2);
            }
        }
        return $res;
    }

    public function campaign_json($app_id, $date, $platform = "6", $keyword = "")
    {

        $start = $date[0];
        $end = $date[1];
        $list = $this->getCampagin($app_id, $start, $end, $platform, $keyword);
        $total = $this->getplatformtotal($app_id, $start, $end, $platform, $keyword);
        array_unshift($list, $total);
        echo json_encode($list);
        exit;
    }


    //汇总花费
    private function totalspend($appid, $start_date, $end_date)
    {
        $r = [];
        $total_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where app_id in({$appid}) and date>='{$start_date}' and date<='{$end_date}'";
        $d = Db::query($total_sql);
        $r["spend"] = isset($d[0]["spend"]) ? $d[0]["spend"] : "0";
        $r["installs"] = isset($d[0]["installs"]) ? $d[0]["installs"] : "0";
        $r["cpi"] = $r["installs"] <= 0 ? 0 : number_format($r["spend"] / $r["installs"], 2);
        return $r;
    }

    //渠道
    private function getchanneldata($appid = "", $start_date = "", $end_date = "", $name = "")
    {
        $sql = "select platform_type as type, sum(installs) as installs,sum(impressions) as impression,sum(clicks) as clicks,round(sum(spend),2) as spend from hellowd_adspend_data where app_id={$appid} and date>='{$start_date}' and date<='{$end_date}' group by platform_type";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as $kk => &$vvv) {

                $vvv["name"] = '<img style="height:14px;width:14px;" src=' . getplatformimg($vvv["type"]) . ' >&nbsp;' . getplatform($vvv["type"]);
                $vvv["date"] = "----";
                $vvv["cpi"] = $vvv["installs"] <= 0 ? 0 : number_format($vvv["spend"] / $vvv["installs"], 2);
            }
        }
        return $res;
    }

    private function getdaydata($appid = "", $start_date = "", $end_date = "", $name = "")
    {
        $range_date = getDateFromRange($start_date, $end_date);
        $res = [];
        foreach ($range_date as $key => $vvv) {
            $res[$key]["name"] = $name;
            $res[$key]["date"] = $vvv;
            $d_sql = "select sum(installs) as installs,sum(impressions) as impression,sum(clicks) as clicks,round(sum(spend),2) as spend from hellowd_adspend_data where app_id in({$appid}) and date='{$vvv}'";
            $d = Db::query($d_sql);
            $res[$key]["spend"] = isset($d[0]["spend"]) ? $d[0]["spend"] : "0";
            $res[$key]["impression"] = isset($d[0]["impression"]) ? $d[0]["impression"] : "0";
            $res[$key]["clicks"] = isset($d[0]["clicks"]) ? $d[0]["clicks"] : "0";
            $res[$key]["installs"] = isset($d[0]["installs"]) ? $d[0]["installs"] : "0";
            $res[$key]["cpi"] = $res[$key]["installs"] <= 0 ? 0 : number_format($res[$key]["spend"] / $res[$key]["installs"], 2);
        }
        return $res;
    }

    /**
     * 获取内购LTV 分 campaign数据
     * @param string $appid
     * @return mixed|\think\response\Redirect
     */
    public function purchase_adjust($appid = ""){
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $start_date = date("Y-m-d", time());
        $end_date = date("Y-m-d", time());
        $this->assign("appid", $appid);
        $this->assign("countryList", admincountry());
        $this->assign("start_date", $start_date);
        $this->assign("end_date", $end_date);
        return $this->fetch();
    }

    /**
     *
     */
    public function purchase_adjust_json($app_id, $date, $material_level="campaign", $now_install=true, $country = "all", $time_zone = "", $time_zone_number = 8){
        if ($app_id) {
            list($start, $end) = $date;

            $where = " gb_id=".$app_id;

            if ($country!="all") $where .= " and country='".$country."' ";

            if ($time_zone==="other"){
                $real_add_hours = $time_zone_number-8;
                //转化为中国时间
                $china_start_time = date('Y-m-d H:i:s',strtotime($start.' 00:00:00')-3600*$real_add_hours);
                $china_end_time = date('Y-m-d H:i:s',strtotime($start.' 23:59:59')-3600*$real_add_hours);
                if ($now_install){
                    $where .= " and real_install_time>='".$china_start_time."' ";
                    $where .= " and real_install_time<='".$china_end_time."' ";
                }
                $where .= " and real_event_time>='".$china_start_time."' ";
                $where .= " and real_event_time<='".$china_end_time."' ";
            }else{
                $time_zone_ext = "";
                if (in_array($time_zone,["pst","pdt","utc"])){
                    $time_zone_ext = "_".$time_zone;
                }
                if ($now_install){
                    $where .= " and install_date".$time_zone_ext.">='".$start."' ";
                    $where .= " and install_date".$time_zone_ext."<='".$start."' ";
                }
                $where .= " and event_date".$time_zone_ext.">='".$start."' ";
                $where .= " and event_date".$time_zone_ext."<='".$end."' ";
            }

            if (!in_array($material_level,["adset","ad"])){
                $material_level = "campaign";
            }
            $field = $material_level."_name,".$material_level."_id";
            $group_by = " ".$material_level."_id ";
            $order_by = " ".$material_level."_id ";
            $field .= ",sum(money) as money";

            $sql = "SELECT ".$field." FROM hellowd_adjust_purchase_time_zone ";
            $sql .= " WHERE ".$where;
            $sql .= " GROUP BY ".$group_by;
            $sql .= " ORDER BY ".$order_by;
            $res = Db::query($sql);
            $total_money = array_sum(array_column($res, 'money'));;
            echo json_encode(['time_zone'=>$time_zone,'out_data'=>$res ,'total_money'=>$total_money]);
            exit;
        }

        echo json_encode([]);
        exit;
    }


}
