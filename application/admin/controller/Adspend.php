<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use think\Session;
use app\admin\controller\Consu;

//广告推广数据
class Adspend extends Base
{
    public function advertising($appid = "", $type = "day", $start_date = "", $end_date = "", $index = "three")
    {
        if ($appid == "") {
            $appid = getcache("select_app");

        } else {
            setcache("select_app", $appid);
        }

        $ids = getmylikedata();

        if ($start_date == "" || $end_date == "") {
            switch ($index) {
                case "oneweek":
                    $start_date = date("Y-m-d", strtotime("-8 day"));
                    $end_date = date("Y-m-d", strtotime("-2 day"));
                    break;
                case "twoweek":
                    $start_date = date("Y-m-d", strtotime("-15 day"));
                    $end_date = date("Y-m-d", strtotime("-2 day"));
                    break;
                case "three":
                    $start_date = date("Y-m-d", strtotime("-4 day"));
                    $end_date = date("Y-m-d", strtotime("-2 day"));
                    break;
            }
        }

        $oneapp = Db::name("app")->field("id,app_name")->find($appid);
        $function = "get" . $type . "data";
        $data = $this->$function($appid, $start_date, $end_date, $oneapp["app_name"]);
        $chats = $this->viewchats($appid, $start_date, $end_date);
        return $this->fetch('advertising', ["appid" => $appid, "index" => $index, "type" => $type, "start_date" => $start_date, "end_date" => $end_date, "data" => $data, "chats" => $chats, "sumdata" => $this->totalspend($appid, $start_date, $end_date)]);
    }

    private function viewchats($appid = "", $start_date = "", $end_date = "")
    {
        $range_date = getDateFromRange($start_date, $end_date);

        $date = "";
        $spend = "";
        $installs = "";
        $cpi = "";
        foreach ($range_date as $key => $vvv) {

            $d_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where app_id in({$appid}) and date='{$vvv}'";
            $d = Db::query($d_sql);
            $c_spend = isset($d[0]["spend"]) ? $d[0]["spend"] : "0";
            $c_installs = isset($d[0]["installs"]) ? $d[0]["installs"] : "0";

            $c_cpi = $c_installs <= 0 ? 0 : number_format($c_spend / $c_installs, 2);
            $date .= "'{$vvv}',";
            $spend .= "{$c_spend},";
            $installs .= "{$c_installs},";
            $cpi .= "{$c_cpi},";
        }
        return ["date" => rtrim($date, ','), "spend" => rtrim($spend, ','), "installs" => rtrim($installs, ','), "cpi" => rtrim($cpi, ',')];
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


    //关联页面
    public function relateapp()
    {
        $res = Db::name("adspend_data")->field("id,campaign_id,campaign_name,platform_type,platform")->where("1=1 and app_id=''")->group("campaign_id,platform_type")->select();
        $apps = Db::name("app")->field("id,app_name,platform,app_base_id")->select();
        if (!empty($apps)) {
            foreach ($apps as &$vv) {
                if ($vv["id"] > 154) {
                    if ($vv["app_base_id"]) {
                        $row = Db::name("app_base")->where("id", $vv["app_base_id"])->find();
                        $vv["app_name"] = $row["name"] . ' - ' . $vv["platform"];
                    }
                }
            }
        }
        return $this->fetch('relateapp', ["res" => $res, "apps" => $apps]);
    }

    //关联保存
    public function relate_save($object = "", $app_id = "")
    {
		if (!empty($object)) {
            foreach ($object as &$v) {
                $f = Db::name("related_app")->where(["app_id" => $v["app_id"], "platform" => $v["platform"], "type" => 2])->find();
                if (empty($f)) {
                    $v["type"] = 2;
                    $r = Db::name("related_app")->insert($v);
                    if ($r !== false) {
                        Db::name("adspend_data")->where(["campaign_id" => $v["app_id"], "platform_type" => $v["platform"]])->update(["app_id" => $v["related_appid"]]);
                    }
                }else{
					Db::name("adspend_data")->where(["campaign_id" => $v["app_id"], "platform_type" => $v["platform"]])->update(["app_id" => $v["related_appid"]]);
				}
            }
            exit("ok");
        }
        exit("fail");
    }

}
