<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use app\admin\controller\Index as E;
use think\Session;
use app\admin\controller\Normdist;

set_time_limit(0);
ini_set('memory_limit', '-1');

class Appsflyer extends Base
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
        ['name' => 'ASM', "value" => "Apple Search Ads", "channel" => "39"],
        ['name' => 'Organic', "value" => "Organic", "channel" => "0"],
		['name' => 'Kuaishou', "value" => "kuaishou_int", "channel" => "42"],

    );

    //广告渠道
    private $network_media = array(
        ["name" => "全部媒体", "value" => "all", "channel" => "all"],
        ["name" => "Admob", "value" => "Admob", "channel" => "5"],
        ["name" => "Sigmob", "value" => "Sigmob", "channel" => "34"],
        ["name" => "IronSource", "value" => "IronSource", "channel" => "7"],
        ["name" => "GDT", "value" => "GDT", "channel" => "35"],
        ["name" => "Vungle", "value" => "Vungle", "channel" => "4"],
        ["name" => "UnityAds", "value" => "UnityAds", "channel" => "2"],
        ["name" => "Facebook", "value" => "Facebook", "channel" => "6"],
        ["name" => "穿山甲", "value" => "CSJ", "channel" => "32"],
        ["name" => "Applovin", "value" => "Applovin", "channel" => "3"],
        ["name" => "Mintegral", "value" => "Mintegral", "channel" => "1"],
        ["name" => "MoPub", "value" => "MoPub", "channel" => "37"],
        ["name" => "Other", "value" => "Other", "channel" => "37"],
		["name" => "InMobi", "value" => "InMobi", "channel" => "40"],
		["name" => "AdColony", "value" => "AdColony", "channel" => "31"]
    );

    private $device = array(
        ["name" => "全部设备", "value" => "all"],
        ["name" => "iPad", "value" => "ipad"],
        ["name" => "iPhone", "value" => "phone"],
        ["name" => "iPod touch", "value" => "ipod"]
    );

    private $ads_date = array(
        "68" => "2020-01-15",
        "132" => "2020-02-12",
        "143" => "2020-02-20",
        "147" => "2020-03-19"
    );

    private $default_ltv = array(
        "143" => ["day" => 30, "day_num" => 7],
        "147" => ["day" => 30, "day_num" => 7]
    );

    private $predict_cpi = array(
        "0" => "0.3",
        "3" => "0.55",
        "7" => "0.7",
        "15" => "0.9"
    );

    public function index($appid = "", $by = "adjust")
    {

        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        $start_ads = isset($this->ads_date[$appid]) ? "从{$this->ads_date[$appid]}开始推广" : "暂无LTV数据";

        $start = date("Y-m-d", strtotime("-4 day"));
        $end = date("Y-m-d", strtotime("-3 day"));
        $this->assign("app_id", $appid);
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("country", admincountry());
        $this->assign("campaignList", $this->getCampaignList($appid));
        $this->assign("promate_media", $this->promate_media);
        $this->assign("network_media", $this->network_media);
        $this->assign("deviceList", $this->device);
        $this->assign("start_ads", $start_ads);
        $userinfo = getuserinfo();
        $role = $userinfo["ad_role"];
        if ($role == "copartner") {
            if ($userinfo["ad_role"] == "copartner") {
                $app_list = explode(",", $userinfo['allow_applist']);
                if (!in_array($appid, $app_list)) {
                    if (!empty($app_list)) {
                        $appid = $app_list[0];
                    } else {
                        exit("You do not have permission to access, please contact the system administrator");
                    }
                }
            }
            $by = "main";
        };
        setcache("select_app", $appid);
        return $this->fetch($by);
    }

    public function data($appid = "", $by = "data")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $start = date("Y-m-d", strtotime("-3 day"));
        $end = date("Y-m-d", strtotime("-2 day"));
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("country", admincountry());
        $this->assign("promate_media", $this->promate_media);
        $this->assign("app_id", $appid);
		$this->assign("ischeckpurchase",in_array($appid,$this->allow_app_list));
        $ltv_default_show = isset($this->default_ltv[$appid]) ? $this->default_ltv[$appid] : ["day" => 0, "day_num" => 3];
        $this->assign("userid", Session::get('admin_userid'));
        $this->assign("ltv_default_show", $ltv_default_show);
		 $this->assign("by", $by);
        return $this->fetch($by);
    }

    private function getCampaignList($appid)
    {
        //$list= Db::query("select campaign_id as value,campaign_name as name from hellowd_appsflyer where gb_id={$appid} group by campaign_id");
        return [];
        $list;
    }

    private function getsepend($app_id, $start, $end, $country, $channel)
    {
        if (is_array($channel)) {
            $platform = implode(",", $channel);
            return $this->getcontroltotal($app_id, $start, $end, $country, $platform);
        } else {
            $out_data = ["installs" => "0", "spend" => "0.00"];
            $where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}'";
            if ($country != "all") {
                $where .= " and country='{$country}'";
            }
            if ($channel != "all") {
                $where .= " and platform_type='{$channel}'";
            }
            $row = Db::name("adspend_data")->field('sum(installs) as installs,sum(spend) as spend')->where($where)->find();
            if (!empty($row)) {
                $out_data = ["installs" => (int)$row["installs"], "spend" => round($row["spend"], 2)];
            }
            if ($channel == "all") {
                $control_data = $this->getcontroltotal($app_id, $start, $end, $country, "all");
                $out_data["installs"] += $control_data["installs"];
                $out_data["spend"] += $control_data["spend"];
            }
            return $out_data;
        }
    }

    //获取手动添加的数据
    private function getcontroltotal($appid, $start = "", $end = "", $country = "all", $platform = "all")
    {
        $spend = "0.00";
        $installs = 0;
        $cpi = "0.00";
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($platform != "all") {
            $where .= " and platform in({$platform})";
        }
        $control_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where}";

        $d = Db::query($control_sql);
        if (!empty($d)) {
            $d = $d[0];
            $spend = $d["spend"] ? $d["spend"] : "0.0";
            $installs = $d["installs"] ? $d["installs"] : 0;
            $cpi = $installs <= 0 ? "0.0" : round($spend / $installs, 2);
        }
        return ["spend" => $spend, "installs" => $installs];
    }

    private function getEcpm($app_id, $start, $end, $country, $channel, $adtype)
    {
        $where = "sys_app_id={$app_id} and  date>='{$start}' and date<='{$end}' and adtype='{$adtype}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($channel != "all") {
            $where .= " and platform='{$channel}'";
        }

        $row = Db::name("adcash_data")->field('sum(impression) as impression,round(sum(revenue),2) as revenue')->where($where)->find();
        if (!empty($row)) {
            $ecpm = $row["impression"] > 0 ? round($row["revenue"] / $row["impression"], 4) : 0;
            return $ecpm;
        }
        return 0;
    }

    private function getchannel($arr, $value)
    {
        foreach ($arr as $v) {
            if ($v["value"] == $value) {
                return $v["channel"];
            }
        }
        return "0";
    }

    private function getNewDeviceUsers($gb_id, $device_category, $country, $media_source, $start, $end)
    {
        $out = [
            "spend" => "0.00",
            "installs" => 0
        ];
        $where = "gb_id={$gb_id} and device_category='{$device_category}' and install_date>='{$start}' and install_date<='{$end}'";
        if ($country != "all" && !empty($country)) {
            if (is_array($country)) {
                $where .= " and country='{$country[0]}'";
            } else {
                $where .= " and country='{$country}'";
            }
        }
        if ($media_source != "all" && !empty($media_source)) {
            if (is_array($media_source)) {
                $where .= " and networks='{$media_source[0]}'";
            } else {
                $where .= " and networks='{$media_source}'";
            }

        }
        if ($device_category == 'iPhone') {
            print_r($where);
            exit;
        }
        $row = Db::name("adjust_device")->field('sum(spend) as spend,sum(installs) as installs')->where($where)->find();

        if (!empty($row)) {
            $out = ["spend" => $row["spend"] ? round($row["spend"], 2) : "0.00", "installs" => $row["installs"] ? $row["installs"] : 0];
        }
        return $out;
    }

    public function json_data_range($app_id = "", $date = [], $country = "all", $day = "", $media_source = "all", $ad_source = "all", $campaign_id = "", $device = "all", $table = "appsflyer")
    {
        $out = [];
        if (empty($date)) {
            $start = date("Y-m-d", strtotime("-15 day"));
            $end = date("Y-m-d", strtotime("-10 day"));
        } else {
            list($start, $end) = $date;
        }
        $out = $this->get_new_ltv_data($app_id, $start, $end, $country, $day, $media_source, $ad_source, $campaign_id, $device, $table);
        echo json_encode($out);
        exit;
    }

    public function get_byday_ltv($app_id = "", $start, $end, $country = "all", $day = "", $spend)
    {
        $out_data = [];
        $total_revenue = "0.00";
        for ($i = 0; $i <= $day; $i++) {
            $r = $this->get_one_byday_ltv($app_id, $spend, $start, $end, $i, $country, "all", "all", "", "all", "adjust");
            $total_revenue += $r["total_revenue"];
            $r["total_roi"] = $spend["spend"] > 0 ? round($total_revenue * 100 / $spend["spend"], 2) : 0;
            $r["total_avg_revenue"] = $spend["installs"] > 0 ? round($total_revenue / $spend["installs"], 3) : 0;
            $out_data[] = $r;
        }
        $out = array(
            "roi_info" => end($out_data),
        );
        return $out;
    }

    private function get_one_byday_ltv($app_id, $spend, $start, $end, $num, $country = "all", $media_source = "all", $ad_source = "all", $campaign_id = "", $device = "all", $table)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "total_revenue" => "0.00",
        );
        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $revenue_info = $this->getRevenue($app_id, $v, $time, $time, $country, $media_source, $ad_source, $campaign_id, $device, $table);
            $res["total_revenue"] += $revenue_info["total_revenue"];
        }
        return $res;
    }

    public function get_new_ltv_data($app_id = "", $start, $end, $country = "all", $day = "", $media_source = "all", $ad_source = "all", $campaign_id = "", $device = "all", $table, $is_group_device = false)
    {
        $media_channel = $this->getchannel($this->promate_media, $media_source);
        if ($is_group_device) {
            $device1 = $device;
            if ($device1 == "iPhone") {
                $device1 = "phone";
            } elseif ($device1 == "iPod touch") {
                $device1 = "ipod";
            }
            $spend = $this->getNewDeviceUsers($app_id, $device1, $country, $media_source, $start, $end);
            $spend["spend"] = round($spend["spend"], 2);
        } else {
            $spend = $this->getsepend($app_id, $start, $end, $country, $media_channel);
        }
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $Reward = 0;
        $Inter = 0;
        $total_revenue = "0.00";
        for ($i = 0; $i <= $day; $i++) {
            $r = $this->get_one_day_ltv($app_id, $spend, $start, $end, $i, $country, $media_source, $ad_source, $campaign_id, $device, $table);
            $total_revenue += $r["total_revenue"];
            $r["index"] = "LTV" . $i;
            $r["total_total_revenue"] = $total_revenue;
            $r["total_roi"] = $spend["spend"] > 0 ? round($total_revenue * 100 / $spend["spend"], 2) : 0;
            $r["total_avg_revenue"] = $spend["installs"] > 0 ? round($total_revenue / $spend["installs"], 3) : 0;
            $r["rate"] = $spend["installs"] > 0 ? round($r["num"] * 100 / $spend["installs"], 2) . "%" : "0";
            $Reward += $r["Reward"];
            $Inter += $r["Inter"];
            $out_data[] = $r;
        }
        $impression = 0;
        $num = 0;
        $out = array(
            "tablist" => $out_data,
            "cpi" => $cpi,
            "promote_info" => $spend,
            "user_value" => ["num" => $num, "impression" => $impression],
            "roi_info" => end($out_data),
        );
        $avg_days = 1; //$day+1;
        $out["rate"] = $out["promote_info"]["installs"] > 0 ? round($out["user_value"]["num"] * 100 / $out["promote_info"]["installs"], 2) . "%" : "0";
        $out["avg_revenue"] = $spend["installs"] > 0 ? round($out["roi_info"]["total_total_revenue"] / $spend["installs"], 3) : "0";
        $out["avgReward"] = $spend["installs"] > 0 ? round($Reward / ($spend["installs"] * $avg_days), 2) : "0";
        $out["avgInter"] = $spend["installs"] > 0 ? round($Inter / ($spend["installs"] * $avg_days), 2) : "0";
        return $out;
    }

    public function json_data_dimension($app_id = "", $dimension = "country", $date = [], $country = "all", $day = "", $media_source = "all", $ad_source = "all", $campaign_id = "", $device = "all", $is_download = false, $table = 'appsflyer')
    {
        if (empty($date)) {
            $start = date("Y-m-d", strtotime("-4 day"));
            $end = date("Y-m-d", strtotime("-3 day"));
        } else {
            if (is_array($date)) {
                list($start, $end) = $date;
            } else {
                list($start, $end) = explode(",", $date);
            }
        }
        $out = [];
        if ($dimension == "country") {
            $nameList = admincountry();
            $media_channel = $this->getchannel($this->promate_media, $media_source);
            foreach ($nameList as $k => $n) {
                //$spend = $this->getsepend($app_id,$start,$end,$k,$media_channel);
                if ($k != "all") {
                    $row = $this->get_new_ltv_data($app_id, $start, $end, $k, $day, $media_source, $ad_source, $campaign_id, $device, $table);
                    $row["tablist"] = end($row["tablist"]);
                    $row["name"] = $n;
                    $out[] = $row;
                }
            }
        } elseif ($dimension == "media_source") {
            $nameList = $this->promate_media;

            foreach ($nameList as $k => $n) {
                //$spend = $this->getsepend($app_id,$start,$end,$country,$n["channel"]);
                if ($n["value"] != "all") {
                    $row = $this->get_new_ltv_data($app_id, $start, $end, $country, $day, $n["value"], $ad_source, $campaign_id, $device, $table);
                    $row["name"] = $n["name"];
                    $row["tablist"] = end($row["tablist"]);
                    $out[] = $row;
                }
            }
        } elseif ($dimension == "device_category") {
            $nameList = $this->device;

            foreach ($nameList as $k => $n) {
                //$spend = $this->getsepend($app_id,$start,$end,$country,$n["channel"]);
                if ($n["value"] != "all") {
                    $row = $this->get_new_ltv_data($app_id, $start, $end, $country, $day, $media_source, $ad_source, $campaign_id, $n["value"], $table, true);
                    $row["name"] = $n["name"];
                    $row["tablist"] = end($row["tablist"]);
                    $out[] = $row;
                }
            }
        } elseif ($dimension == "day") {
            $dates = getDateFromRange($start, $end);
            foreach ($dates as $v) {
                $row = $this->get_new_ltv_data($app_id, $v, $v, $country, $day, $media_source, $ad_source, $campaign_id, $device, $table);
                $row["name"] = $v;
                $row["tablist"] = end($row["tablist"]);
                $out[] = $row;
            }
        }
        if ($is_download) {
            return $this->ltv_download($out);
        }

        echo json_encode($out);
        exit;
    }

    private function get_one_day_ltv($app_id, $spend, $start, $end, $num, $country = "all", $media_source = "all", $ad_source = "all", $campaign_id = "", $device = "all", $table)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "num" => 0,
            "impression" => 0,
            "total_revenue" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
        );
        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $r = $this->getImpression($app_id, $v, $time, $time, $country, $media_source, $ad_source, $campaign_id, $device);
            $revenue_info = $this->getRevenue($app_id, $v, $time, $time, $country, $media_source, $ad_source, $campaign_id, $device, $table);
            $Reward = $r["Reward"];
            $Inter = $r["Inter"];
            $res["total_revenue"] += $revenue_info["total_revenue"];
            $res["Reward"] += $Reward;
            $res["Inter"] += $Inter;
        }
        $res["day_roi"] = $spend["spend"] > 0 ? round($res["total_revenue"] * 100 / $spend["spend"], 2) . "%" : 0;
        $res["day_avg_revenue"] = $spend["installs"] > 0 ? round($res["total_revenue"] / $spend["installs"], 3) : 0;
        return $res;
    }

    private function getImpression($app_id, $date, $start, $end, $country, $media_source, $ad_source, $campaign_id, $device)
    {
        $Reward = 0;
        $Inter = 0;
        $where = "gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($media_source != "all") {
            $where .= " and media_source='{$media_source}'";
        }
        if ($ad_source != "all") {
            $where .= " and ad_source='{$ad_source}'";
        }
        if ($campaign_id != "") {
            $where .= " and campaign_id='{$campaign_id}'";
        }
        if ($device != "all") {
            $where .= " and device_category='{$device}'";
        }
        $row = Db::query("SELECT adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY adtype");
        if (!empty($row) && isset($row[0])) {
            foreach ($row as $v) {
                if ($v['adtype'] == 'Reward') {
                    $Reward = $v['num'];
                }
                if ($v['adtype'] == 'Inter') {
                    $Inter = $v['num'];
                }
            }
        }
        return ["Reward" => $Reward, "Inter" => $Inter];
    }


    private function getRevenue($app_id, $date, $start, $end, $country, $media_source, $ad_source, $campaign_id, $device, $table)
    {
        $res = $this->byChannel($app_id, $date, $start, $end, $country, $media_source, $ad_source, $campaign_id, $device, $table);
        $total_revenue = "0.00";
        $revenueList = [];
        if (!empty($res)) {
            foreach ($res as $v) {
                if ($v["num"] > 0) {
                    $rate = 1;
                    $ad_channel = $this->getchannel($this->network_media, $v["ad_source"]);
                    $adtype = "";
                    switch ($v["adtype"]) {
                        case 'Inter':
                            $adtype = 'int';
                            break;
                        case 'Reward':
                            $adtype = 'rew';
                            break;
                    }
                    $ecpm = $this->getEcpm($app_id, $start, $end, $country, $ad_channel, $adtype);
                    if ($ad_channel == '5') {
                        $rate = "0.92";
                    }
                    $revenue = $v["num"] * $ecpm * $rate;
                    $total_revenue += $revenue;
                    $revenueList[] = ["adname" => $v["ad_source"], "advalue" => $revenue];
                }
            }
        }
        return ["total_revenue" => round($total_revenue, 2), "revenueList" => $revenueList];
    }


    private function byChannel($app_id, $date, $start, $end, $country, $media_source, $ad_source, $campaign_id, $device, $table)
    {
        $where = "gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($media_source != "all") {
            $where .= " and media_source='{$media_source}'";
        }
        if ($ad_source != "all") {
            $where .= " and ad_source='{$ad_source}'";
        }
        if ($campaign_id != "") {
            //$where.=" and campaign_id='{$campaign_id}'";
        }
        if ($device != "all") {
            $where .= " and device_category='{$device}'";
        }
        $row = Db::query("SELECT ad_source,adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY ad_source,adtype");
        return $row;
    }

    public function ltv_download($data)
    {
        if (!empty($data)) {
            $xlsCell = array(
                array("name", '名称'),
                array("spend", '推广花费'),
                array("installs", '推广新增'),
                array('cpi', '推广成本'),
                array('total_total_revenue', '总收益'),
                array('total_avg_revenue', '总人均价值'),
                array('total_roi', '总ROI(%)')
            );
            $xlsData = [];
            foreach ($data as $v) {
                $xlsData[] = ["name" => $v["name"], "spend" => $v["promote_info"]["spend"], "installs" => $v["promote_info"]["installs"], "cpi" => $v["cpi"], "total_total_revenue" => $v["tablist"]["total_total_revenue"], "total_avg_revenue" => $v["tablist"]["total_avg_revenue"], "total_roi" => $v["tablist"]["total_roi"]];
            }
            $Index = new E(request());
            $name = "LTV模型数据下载" . date("YmdHis");
            echo $Index->exportExcel($name, $xlsCell, $xlsData, $name, $name);
            exit;
        }
    }


    private function get_v1_Ecpm($app_id, $start, $end, $country, $channel, $adtype)
    {
        $where = "sys_app_id={$app_id} and  date>='{$start}' and date<='{$end}' and adtype='{$adtype}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if ($channel != "all") {
            $where .= " and platform='{$channel}'";
        }
        $row = Db::name("adcash_data")->field('sum(impression) as impression,round(sum(revenue),2) as revenue')->where($where)->find();
        if (!empty($row)) {
            $ecpm = $row["impression"] > 0 ? round($row["revenue"] / $row["impression"], 4) : 0;
            return $ecpm;
        }
        return 0;
    }

    private function by_v1_Channel($app_id, $date, $start, $end, $country, $media_source, $device_category)
    {
        $where = "gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_source = implode(",", $media_source);
            $where .= " and media_source='{$media_source}'";
        }
        if ($device_category && $device_category != 'all') {
            if ($device_category == 'phone') {
                $device_category = "iPhone";
            } elseif ($device_category == 'ipod') {
                $device_category = 'iPod touch';
            }
            $where .= " and device_category='{$device_category}'";
        }

        $row = Db::query("SELECT ad_source,adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY ad_source,adtype");
        return $row;
    }


    private function get_v1_Revenue($app_id, $date, $start, $end, $country, $media_source, $device_category)
    {
        $res = $this->by_v1_Channel($app_id, $date, $start, $end, $country, $media_source, $device_category);
        $total_revenue = "0.00";
        $revenueList = [];
        if (!empty($res)) {
            foreach ($res as $v) {
                if ($v["num"] > 0) {
                    $rate = 1;
                    $ad_channel = $this->getchannel($this->network_media, $v["ad_source"]);
                    $adtype = "";
                    switch ($v["adtype"]) {
                        case 'Inter':
                            $adtype = 'int';
                            break;
                        case 'Reward':
                            $adtype = 'rew';
                            break;
                    }
                    $ecpm = $this->get_v1_Ecpm($app_id, $start, $end, $country, $ad_channel, $adtype);
                    if ($ad_channel == '5') {
                        $rate = "0.92";
                    }
                    $revenue = $v["num"] * $ecpm * $rate;
                    $total_revenue += $revenue;
                    $revenueList[] = ["adname" => $v["ad_source"], "advalue" => $revenue];
                }
            }
        }
        return ["total_revenue" => round($total_revenue, 2), "revenueList" => $revenueList];
    }


    private function get_v1_Impression($app_id, $date, $start, $end, $country, $media_source, $device_category)
    {
        $Reward = 0;
        $Inter = 0;
        $where = "gb_id={$app_id} and install_date='{$date}' and event_date>='{$start}' and event_date<='{$end}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_source = implode(",", $media_source);
            $where .= " and media_source='{$media_source}'";
        }
        if ($device_category && $device_category != 'all') {
            if ($device_category == 'phone') {
                $device_category = "iPhone";
            } elseif ($device_category == 'ipod') {
                $device_category = 'iPod touch';
            }
            $where .= " and device_category='{$device_category}'";
        }
        $row = Db::query("SELECT adtype,sum(num) as num from hellowd_adjust_impression where {$where} GROUP BY adtype");
        if (!empty($row) && isset($row[0])) {
            foreach ($row as $v) {
                if ($v['adtype'] == 'Reward') {
                    $Reward = $v['num'];
                }
                if ($v['adtype'] == 'Inter') {
                    $Inter = $v['num'];
                }
            }
        }
        return ["Reward" => $Reward, "Inter" => $Inter];
    }

    private function get_v1_one_day_ltv($app_id, $spend, $start, $end, $num, $country, $media_source, $device_category)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "total_revenue" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
        );

        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $r = $this->get_v1_Impression($app_id, $v, $time, $time, $country, $media_source, $device_category);
            $revenue_info = $this->get_v1_Revenue($app_id, $v, $time, $time, $country, $media_source, $device_category);
            $Reward = $r["Reward"];
            $Inter = $r["Inter"];
            $res["total_revenue"] += $revenue_info["total_revenue"];
            $res["Reward"] += $Reward;
            $res["Inter"] += $Inter;
        }
        $res["day_roi"] = $spend["spend"] > 0 ? round($res["total_revenue"] * 100 / $spend["spend"], 2) . "%" : 0;
        $res["day_avg_revenue"] = $spend["installs"] > 0 ? round($res["total_revenue"] / $spend["installs"], 3) : 0;
        return $res;
    }


    private function get_v1_controltotal($appid, $start = "", $end = "", $country, $platform = "all")
    {
        $spend = "0.00";
        $installs = 0;
        $cpi = "0.00";
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if ($platform != "all") {
            $where .= " and platform in({$platform})";
        }
        $control_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where}";

        $d = Db::query($control_sql);
        if (!empty($d)) {
            $d = $d[0];
            $spend = $d["spend"] ? $d["spend"] : "0.0";
            $installs = $d["installs"] ? $d["installs"] : 0;
            $cpi = $installs <= 0 ? "0.0" : round($spend / $installs, 2);
        }
        return ["spend" => $spend, "installs" => $installs];
    }


    private function get_v1_sepend($app_id, $start, $end, $country, $channel)
    {
        if (is_array($channel)) {
            $platform = implode(",", $channel);
            return $this->get_v1_controltotal($app_id, $start, $end, $country, $platform);
        } else {
            $out_data = ["installs" => "0", "spend" => "0.00"];
            $where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}'";
            if (!empty($country)) {
                $countrys = array_map(function ($v) {
                    return "'{$v}'";
                }, $country);
                $countrys = implode(",", $countrys);
                $where .= " and country in({$countrys})";
            }
            if ($channel != "all") {
                $where .= " and platform_type='{$channel}'";
            }
            $row = Db::name("adspend_data")->field('sum(installs) as installs,sum(spend) as spend')->where($where)->find();
            if (!empty($row)) {
                $out_data = ["installs" => (int)$row["installs"], "spend" => round($row["spend"], 2)];
            }
            if ($channel == "all") {
                $control_data = $this->get_v1_controltotal($app_id, $start, $end, $country, "all");
                $out_data["installs"] += $control_data["installs"];
                $out_data["spend"] += $control_data["spend"];
            }
            if ($channel == "5") {
                $control_data = $this->get_v1_controltotal($app_id, $start, $end, $country, "356");
                $out_data["installs"] += $control_data["installs"];
                $out_data["spend"] += $control_data["spend"];
            }
            return $out_data;
        }
    }


    private function get_v1_total_spend($app_id, $start, $end, $country, $media_channel)
    {
        if (!empty($media_channel)) {
            $out = ["installs" => 0, "spend" => "0.00"];
            foreach ($media_channel as $c) {
                $result = $this->get_v1_sepend($app_id, $start, $end, $country, $c);
                $out["installs"] += $result["installs"];
                $out["spend"] += $result["spend"];
            }
            return $out;
        }
        return $this->get_v1_sepend($app_id, $start, $end, $country, "all");
    }

    private function get_v1_channel($arr, $value)
    {
        $out = [];
        foreach ($arr as $v) {
            if (in_array($v["value"], $value)) {
                $out[] = $v["channel"];
            }
        }
        return $out;
    }

    private $test_device = [
        'A4D46894-3619-4435-9067-3175CD2D222A',
        'C827FFD4-AD23-4061-A835-50BAB42B47DB',
        '7041D62D-E66E-488B-996B-68F29BE40D8A',
        '3FB943ED-D804-4842-BB97-396144502F9F',
        '58CE0224-E913-4D2D-AFB1-1D6740674BEB',
        'C3D6A909-3F92-4FFA-AB61-0D7611D0218C',
        '7767920B-EEC6-4B3D-BD17-4A4212B99919',
		'702F8377-CA8B-409A-8DA0-45D239531B9A',
		'C8C4109A-C43D-404D-ACAA-80C7ED2F2169',
		'885A520D-43BB-450B-8B05-B22E3B8DB1B0',
		'CCF08DFC-397A-4184-A38C-DB9FF00ABF58',
		"C99530AC-4320-4D17-A830-2706F2274281",
        'D701047A-D273-4767-82D8-075E2B69F6F6'
    ];

    //新增内购收益
    private function getpurchasev1($app_id = "", $install_date, $event_start_date, $event_end_date, $campaign_id = "",$adset_id="",$country = [], $media_source = [])
    {
        $where = "gb_id={$app_id} and install_date_utc='{$install_date}' and event_date_utc>='{$event_start_date}' and event_date_utc<='{$event_end_date}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_sources = array_map(function ($v) {
                return "'{$v}'";
            }, $media_source);
            $media_sources = implode(",", $media_sources);
            $where .= " and media_source in({$media_sources})";
        }
        $deviceList = $this->test_device;
        if (!empty($deviceList)) {
            $deviceLists = array_map(function ($v) {
                return "'{$v}'";
            }, $deviceList);
            $deviceLists = implode(",", $deviceLists);
            $where .= " and idfa not in({$deviceLists})";
        }
        if ($campaign_id) {
            $awh = " and campaign_id='{$campaign_id}'";
			if(!empty($media_source) )
			{
				if($media_source[0]=="unityads_int")
				{
					$awh = " and campaign_name='{$campaign_id}'";
				}
			}
			$where .= $awh;
        }
		if ($adset_id) {
            
			if($media_source[0]=='tiktok_int')
			{
				$where .= " and adset_id='{$adset_id}'";
			}else{
				$where .= " and adset_name='{$adset_id}'";
			}
        }
        $d = Db::name("adjust_purchase_time_zone")->field("sum(money) as revenue")->where($where)->find();
        if (!empty($d)) {
            $rate ="0.7";
			if(strtotime($event_start_date)>=strtotime("2021-01-01"))
			{
				$rate ="0.85";
			}
			return $d["revenue"] ? $d["revenue"] * $rate : "0.00";
        }
        return "0.00";
    }


    public function get_v1_ltv_data($app_id = "", $start, $end, $country, $day = "", $day_num = "", $media_source, $is_device = false, $device_category = "")
    {
        $media_channel = $this->get_v1_channel($this->promate_media, $media_source);
        if ($is_device) {
            $spend = $this->getNewDeviceUsers($app_id, $device_category, $country, $media_source, $start, $end);
        } else {
            $spend = $this->get_v1_total_spend($app_id, $start, $end, $country, $media_channel);
        }
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $r = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end, $day, $day_num, $country, $media_source, $device_category);
        $out["total_revenue"] = round($r["total_revenue"], 2);
        $out["ad_revenue"] = round($r["ad_revenue"], 2);
        $out["purchase"] = round($r["purchase"], 2);
        $out["cpi"] = $cpi;
        $out["end_day"] = $r["end_day"];
        $out["reten"] = $r["reten"];
        $out["spend"] = round($spend["spend"],2);
        $out["installs"] = $spend["installs"];
        $out["avg_purchase"] = $spend["installs"] > 0 ? round($r["purchase"] / $spend["installs"], 3) : 0;
        $out["avg_revenue"] = $spend["installs"] > 0 ? round($r["total_revenue"] / $spend["installs"], 3) : 0;
        $out["avg_ad_revenue"] = $spend["installs"] > 0 ? round($r["ad_revenue"] / $spend["installs"], 3) : 0;
        $out["predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["avg_revenue"] / $this->predict_cpi[$day], 3) : $out["avg_revenue"];
        $out["avgReward"] = $spend["installs"] > 0 ? round($r["Reward"] / $spend["installs"], 2) : "0";
        $out["avgInter"] = $spend["installs"] > 0 ? round($r["Inter"] / $spend["installs"], 2) : "0";
        $out["total_roi"] = $spend["spend"] > 0 ? round($r["total_revenue"] * 100 / $spend["spend"], 2) : 0;
        $out["new_total_revenue"] = round($r["new_total_revenue"], 2);
        $out["new_ad_revenue"] = round($r["new_ad_revenue"], 2);
        $out["new_avg_revenue"] = $spend["installs"] > 0 ? round($r["new_total_revenue"] / $spend["installs"], 3) : 0;
        $out["new_avg_ad_revenue"] = $spend["installs"] > 0 ? round($r["new_ad_revenue"] / $spend["installs"], 3) : 0;
        $out["new_predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["new_avg_revenue"] / $this->predict_cpi[$day], 3) : $out["new_avg_revenue"];
        $out["new_avgReward"] = $spend["installs"] > 0 ? round($r["new_Reward"] / $spend["installs"], 2) : "0";
        $out["new_avgInter"] = $spend["installs"] > 0 ? round($r["new_Inter"] / $spend["installs"], 2) : "0";
        $out["new_total_roi"] = $spend["spend"] > 0 ? round($r["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
		$out["cus_total_roi1"] = $out["new_total_roi"];
		$out["cus_total_roi2"] = $out["new_total_roi"];
		if($day==0)
		{
			if(!$this->isd($start,3))
			{
				$r3 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end, 3, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi1"] = $r3["roi"];
			}else{
				$out["cus_total_roi1"]="--";
			}
			
			if(!$this->isd($start,7))
			{
				$r7 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end, 7, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi2"] = $r7["roi"];
			}else{
				$out["cus_total_roi2"]="--";
			}			
		}
		if($day==3)
		{
			if(!$this->isd($start,7))
			{
				$r7 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,7, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi1"] = $r7["roi"];
			}else{
				$out["cus_total_roi1"]="--";
			}			
			if(!$this->isd($start,10))
			{
				$r10 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,10, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi2"] = $r10["roi"];
			}else{
				$out["cus_total_roi2"]="--";
			}
		}
		if($day==7)
		{
			if(!$this->isd($start,10))
			{
				$r10 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,10, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi1"] = $r10["roi"];
			}else{
				$out["cus_total_roi1"]="--";
			}
			
			if(!$this->isd($start,14))
			{
				$r14 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,14, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi2"] = $r14["roi"];
			}else{
				$out["cus_total_roi2"]="--";
			}
		}
		if($day==15)
		{
			if(!$this->isd($start,30))
			{
				$r30 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,30, $day_num, $country, $media_source, $device_category);
				$out["cus_total_roi2"] = $r30["roi"];
			}else{
				$out["cus_total_roi2"]="--";
			}
			
		}
		
		
        return $out;
    }
	
	private function isd($end,$c){
		$current_date = date("Y-m-d", strtotime("-2 day"));
		$tr = (strtotime($end) + $c * 24 * 3600);
		 if($tr>strtotime($current_date))
		 {
			return true;
		 }
		 return false;
	}

    public function get_ltv_total_data()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] != '' && $params["day_num"] != 'custom' && $params["day"] == 'n') {
            $params["day_num"] = $params["day_num"] + 1;
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2;
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] == '' && $params["day"] == 'n') {
            $date = date("Y-m-d", strtotime("-2 day"));
            $result = $this->get_week_ltv($params['app_id'], $date, $params['country'], $params['media_source']);
            $start = $result['start'];
            $end = $result['end'];
            $reten = $result["reten"];
        } else {
            list($start, $end) = $params["date"];
        }
        $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, $params['country'], $params['day'], $params["day_num"], $params['media_source']);
        if ($params["day_num"] == '' && $params["day"] == 'n') {
            $row["reten"] = $reten;
        }
        echo json_encode([$row]);
        exit;
    }
	
	public function get_trapezoid_total_data()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] != '' && $params["day_num"] != 'custom' && $params["day"] == 'n') {
            $params["day_num"] = $params["day_num"] + 1;
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2;
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] == '' && $params["day"] == 'n') {
            $date = date("Y-m-d", strtotime("-2 day"));
            $result = $this->get_week_ltv($params['app_id'], $date, $params['country'], $params['media_source']);
            $start = $result['start'];
            $end = $result['end'];
            $reten = $result["reten"];
        } else {
            list($start, $end) = $params["date"];
        }
        $row = $this->get_v2_ltv_data($params['app_id'], $start, $end, $params['country'], $params['day'], $params["day_num"], $params['media_source']);
        
        echo json_encode([$row]);
        exit;
    }
	
	
	public function get_v2_ltv_data($app_id = "", $start, $end, $country, $day = "", $day_num = "", $media_source, $is_device = false, $device_category = "")
    {
        $media_channel = $this->get_v1_channel($this->promate_media, $media_source);
        if ($is_device) {
            $spend = $this->getNewDeviceUsers($app_id, $device_category, $country, $media_source, $start, $end);
        } else {
            $spend = $this->get_v1_total_spend($app_id, $start, $end, $country, $media_channel);
        }
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $r = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end, $day, $day_num, $country, $media_source, $device_category);
        $out["total_revenue"] = round($r["total_revenue"], 2);
        $out["ad_revenue"] = round($r["ad_revenue"], 2);
        $out["purchase"] = round($r["purchase"], 2);
        $out["cpi"] = $cpi;
        $out["spend"] = $spend["spend"];
        $out["installs"] = $spend["installs"];
        $out["avg_purchase"] = $spend["installs"] > 0 ? round($r["purchase"] / $spend["installs"], 3) : 0;
        $out["new_total_revenue"] = round($r["new_total_revenue"], 2);
        $out["new_ad_revenue"] = round($r["new_ad_revenue"], 2);
        $out["ltv0"] = $spend["installs"] > 0 ? round($r["new_total_revenue"] / $spend["installs"], 3) : 0;
        $out["new_avg_ad_revenue"] = $spend["installs"] > 0 ? round($r["new_ad_revenue"] / $spend["installs"], 3) : 0;
        $out["new_predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["ltv0"] / $this->predict_cpi[$day], 3) : $out["ltv0"];
        $out["new_avgReward"] = $spend["installs"] > 0 ? round($r["new_Reward"] / $spend["installs"], 2) : "0";
        $out["new_avgInter"] = $spend["installs"] > 0 ? round($r["new_Inter"] / $spend["installs"], 2) : "0";
        $out["roi0"] = $spend["spend"] > 0 ? round($r["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
		if(!$this->isd($end,1))
		{
			$r1 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,1, $day_num, $country, $media_source, $device_category);
			$out["roi1"] = $spend["spend"] > 0 ? round($r1["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv1"] = $spend["installs"] > 0 ? round($r1["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi1"]="--";
			$out["ltv1"]="--";
		}
       if(!$this->isd($end,2))
		{
			$r2 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,2, $day_num, $country, $media_source, $device_category);
			$out["roi2"] = $spend["spend"] > 0 ? round($r2["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv2"] = $spend["installs"] > 0 ? round($r2["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi2"]="--";
			$out["ltv2"]="--";
		}
        if(!$this->isd($end,3))
		{
			$r3 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,3, $day_num, $country, $media_source, $device_category);
			$out["roi3"] = $spend["spend"] > 0 ? round($r3["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv3"] = $spend["installs"] > 0 ? round($r3["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi3"]="--";
			$out["ltv3"]="--";
		}
		if(!$this->isd($end,4))
		{
			$r4 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,4, $day_num, $country, $media_source, $device_category);
			$out["roi4"] = $spend["spend"] > 0 ? round($r4["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv4"] = $spend["installs"] > 0 ? round($r4["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi4"]="--";
			$out["ltv4"]="--";
		}
		if(!$this->isd($end,5))
		{
			$r5 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,5, $day_num, $country, $media_source, $device_category);
			$out["roi5"] = $spend["spend"] > 0 ? round($r5["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv5"] = $spend["installs"] > 0 ? round($r5["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi5"]="--";
			$out["ltv5"]="--";
		}
		if(!$this->isd($end,6))
		{
			$r6 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,6, $day_num, $country, $media_source, $device_category);
			$out["roi6"] = $spend["spend"] > 0 ? round($r6["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv6"] = $spend["installs"] > 0 ? round($r6["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi6"]="--";
			$out["ltv6"]="--";
		}
		if(!$this->isd($end,7))
		{
			$r7 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,7, $day_num, $country, $media_source, $device_category);
			$out["roi7"] = $spend["spend"] > 0 ? round($r7["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv7"] = $spend["installs"] > 0 ? round($r7["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi7"]="--";
			$out["ltv7"]="--";
		}
        if(!$this->isd($end,14))
		{
			$r14 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,14, $day_num, $country, $media_source, $device_category);
			$out["roi14"] = $spend["spend"] > 0 ? round($r14["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv14"] = $spend["installs"] > 0 ? round($r14["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi14"]="--";
			$out["ltv14"]="--";
		}
        if(!$this->isd($end,30))
		{
			//$r30 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,30, $day_num, $country, $media_source, $device_category);
			$revenue_total = $this->get_new_ltv_summary($app_id,$start,30,$country,$media_source);
			$out["roi30"] = $spend["spend"] > 0 ? round($revenue_total * 100 / $spend["spend"], 2) : 0;
			$out["ltv30"] = $spend["installs"] > 0 ? round($revenue_total / $spend["installs"], 3) : 0;
		}else{
			$out["roi30"]="--";
			$out["ltv30"]="--";
		}

       if(!$this->isd($end,45))
		{
			$r45 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,45, $day_num, $country, $media_source, $device_category);
			$out["roi45"] = $spend["spend"] > 0 ? round($r45["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
			$out["ltv45"] = $spend["installs"] > 0 ? round($r45["new_total_revenue"] / $spend["installs"], 3) : 0;
		}else{
			$out["roi45"]="--";
			$out["ltv45"]="--";
		}
        
        if(!$this->isd($end,60))
		{
			//$r60 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,60, $day_num, $country, $media_source, $device_category);
			$revenue_total60 = $this->get_new_ltv_summary($app_id,$start,60,$country,$media_source);
			$out["roi60"] = $spend["spend"] > 0 ? round($revenue_total60 * 100 / $spend["spend"], 2) : 0;
			$out["ltv60"] = $spend["installs"] > 0 ? round($revenue_total60 / $spend["installs"], 3) : 0;
		}else{
			$out["roi60"]="--";
			$out["ltv60"]="--";
		}

        if(!$this->isd($end,90))
		{
			//$r90 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,90, $day_num, $country, $media_source, $device_category);
			$revenue_total90 = $this->get_new_ltv_summary($app_id,$start,90,$country,$media_source);
			$out["roi90"] = $spend["spend"] > 0 ? round($revenue_total90 * 100 / $spend["spend"], 2) : 0;
			$out["ltv90"] = $spend["installs"] > 0 ? round($revenue_total90 / $spend["installs"], 3) : 0;
		}else{
			$out["roi90"]="--";
			$out["ltv90"]="--";
		}
        if(!$this->isd($end,120))
		{
			//$r90 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,90, $day_num, $country, $media_source, $device_category);
			$revenue_total120 = $this->get_new_ltv_summary($app_id,$start,120,$country,$media_source);
			$out["roi120"] = $spend["spend"] > 0 ? round($revenue_total120 * 100 / $spend["spend"], 2) : 0;
			$out["ltv120"] = $spend["installs"] > 0 ? round($revenue_total120 / $spend["installs"], 3) : 0;
		}else{
			$out["roi120"]="--";
			$out["ltv120"]="--";
		}

        if(!$this->isd($end,180))
		{
			//$r90 = $this->get_v2_one_day_ltv($app_id, $spend, $start, $end,90, $day_num, $country, $media_source, $device_category);
			$revenue_total180 = $this->get_new_ltv_summary($app_id,$start,180,$country,$media_source);
			$out["roi180"] = $spend["spend"] > 0 ? round($revenue_total180 * 100 / $spend["spend"], 2) : 0;
			$out["ltv180"] = $spend["installs"] > 0 ? round($revenue_total180 / $spend["installs"], 3) : 0;
		}else{
			$out["roi180"]="--";
			$out["ltv180"]="--";
		}		
		
        return $out;
    }
	
	private function get_new_ltv_summary($app_id,$start,$day_num,$country,$media_source){
		$where = "gb_id={$app_id} and install_date='{$start}' and day={$day_num}";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_sources = array_map(function ($v) {
                return "'{$v}'";
            }, $media_source);
            $media_sources = implode(",", $media_sources);
            $where .= " and media_source in({$media_sources})";
        }
		$res = Db::query("select SUM(revenue) as revenue from hellowd_adjust_ltv_summary where {$where}");
		$revenue = isset($res[0]["revenue"])?$res[0]["revenue"]:"0.00";
		$purchase = '0.00';
		if (in_array($app_id,$this->allow_app_list)) {
			$event_end_date = date("Y-m-d", (strtotime($start) + $day_num * 24 * 3600));
			$purchase = $this->getpurchasev1($app_id, $start, $start, $event_end_date, "", "",$country, $media_source);			
		}
		return $revenue+$purchase;
	}
	
	public function trapezoid(){
		$params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] != '' && $params["day_num"] != 'custom' && $params["day"] == 'n') {
            $params["day_num"] = $params["day_num"] + 1;
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2;
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        }else {
            list($start, $end) = $params["date"];
        }
		$dates = getDateFromRange($start, $end);

		foreach ($dates as $v) {
			$row = $this->get_v2_ltv_data($params['app_id'], $v, $v, $params['country'], $params['day'], $params["day_num"], $params['media_source']);
			$row["name"] = $v . "(" . getweekday($v) . ")";
			$row["hasChildren"] = false;
			$row["id"] = mt_rand() . $v;
			$row["attr"] = "date";
			$row["parent_key"] = "";
			$row["child_key"] = "";
			$row["level"] = 1;
			$row["key"] = $v;
			$row["tablist"] = $row;
			$out[] = $row;
		}
		echo json_encode(["data"=>$out,"date"=>[$start,$end]]);
        exit;
	}

    public function ltv_json_data()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] != '' && $params["day_num"] != 'custom' && $params["day"] == 'n') {
            $params["day_num"] = $params["day_num"] + 1;
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2;
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] == '' && $params["day"] == 'n') {
            $date = date("Y-m-d", strtotime("-2 day"));
            $result = $this->get_week_ltv($params['app_id'], $date, $params['country'], $params['media_source']);
            $start = $result['start'];
            $end = $result['end'];
            $reten = $result["reten"];
        } else {
            list($start, $end) = $params["date"];
        }
        if ($params["dimension_one"] == "date") {
            $dates = getDateFromRange($start, $end);
            foreach ($dates as $v) {
                $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, $params['country'], $params['day'], $params["day_num"], $params['media_source']);
                $row["name"] = $v . "(" . getweekday($v) . ")";
                $row["hasChildren"] = true;
                $row["id"] = mt_rand() . $v;
                $row["attr"] = "date";
                $row["parent_key"] = "";
                $row["child_key"] = "";
                $row["level"] = 1;
                $row["key"] = $v;
                $row["tablist"] = $row;
                $out[] = $row;


            }
        } elseif ($params["dimension_one"] == "country") {
            $country = $params["country"];
            $allCountry = admincountry();
            if (empty($country)) {
                $country = array_keys($allCountry);
            }
            foreach ($country as $c) {
                if ($c != 'all') {
                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], $params['media_source']);
                    $row["name"] = $allCountry[$c];
                    $row["hasChildren"] = true;
                    $row["id"] = uniqid() . $c;
                    $row["attr"] = "country";
                    $row["key"] = $c;
                    $row["level"] = 1;
                    $row["child_key"] = "";
                    $row["parent_key"] = "";
                    $row["tablist"] = $row;
                    $out[] = $row;
                }
            }
        } elseif ($params["dimension_one"] == "channel") {
            $media_source = $params["media_source"];
            if (empty($media_source)) {
                $media_source = array_column($this->promate_media, 'value');
            }
            foreach ($media_source as $c) {
                if ($c != 'all') {
                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, $params['country'], $params['day'], $params["day_num"], [$c]);
                    $row["name"] = $this->get_media_source_name($c);
                    $row["id"] = uniqid() . $c;
                    $row["hasChildren"] = true;
                    $row["attr"] = "channel";
                    $row["key"] = $c;
                    $row["level"] = 1;
                    $row["parent_key"] = "";
                    $row["child_key"] = "";
                    $row["tablist"] = $row;
                    $out[] = $row;
                }
            }
        }
        echo json_encode(["data"=>$out,"date"=>[$start,$end]]);
        exit;
    }

    private function get_media_source_name($value)
    {
        foreach ($this->promate_media as $v) {
            if ($v['value'] == $value) {
                return $v["name"];
            }
        }
    }

    public function children_ltv_json_data($mediashow = "yes")
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] != '' && $params["day_num"] != 'custom' && $params["day"] == 'n') {
            $params["day_num"] = $params["day_num"] + 1;
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2;
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } elseif ($params["day_num"] == '' && $params["day"] == 'n') {
            $date = date("Y-m-d", strtotime("-2 day"));
            $result = $this->get_week_ltv($params['app_id'], $date, $params['country'], $params['media_source']);
            $start = $result['start'];
            $end = $result['end'];
            $reten = $result["reten"];
        } else {
            list($start, $end) = $params["date"];
        }
        $level = ($params["level"] == 1) ? 2 : 3;
        if ($level == 2) {
            if ($params["dimension_two"] == 'country') {
                $country = $params["country"];
                $allCountry = admincountry();
                if (empty($country)) {
                    $country = array_keys($allCountry);
                }
                foreach ($country as $c) {
                    if ($c != 'all') {
                        switch ($params["dimension_one"]) {
                            case "country":
                                $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "date":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], [$c], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "channel":
                                $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], [$params['key']]);
                                break;
                        }
                        $row["hasChildren"] = true;
                        $row["name"] = $allCountry[$c];
                        $row["id"] = uniqid() . $c;
                        $row["attr"] = "country";
                        $row["key"] = $c;
                        $row["level"] = $level;
                        $row["child_key"] = "";
                        $row["parent_key"] = $params["key"];
                        $row["tablist"] = $row;
                        $out[] = $row;
                    }
                }
            } elseif ($params["dimension_two"] == "channel") {
                $media_source = $params["media_source"];
                if (empty($media_source)) {
                    $media_source = array_column($this->promate_media, 'value');
                }
                foreach ($media_source as $c) {
                    if ($c != 'all') {
                        switch ($params["dimension_one"]) {
                            case "country":
                                $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$params['key']], $params['day'], $params["day_num"], [$c]);
                                break;
                            case "date":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], $params['country'], $params['day'], $params["day_num"], [$c]);
                                break;
                            case "channel":
                                $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, $params["country"], $params['day'], $params["day_num"], [$c]);
                                break;
                        }
                        $row["name"] = $this->get_media_source_name($c);
                        $row["id"] = uniqid() . $params['parent_key'] . $params['key'] . $c;
                        $row["hasChildren"] = true;
                        $row["attr"] = "channel";
                        $row["key"] = $c;
                        $row["level"] = 2;
                        $row["parent_key"] = $params['key'];
                        $row["child_key"] = $params['key'];
                        $row["tablist"] = $row;
                        $out[] = $row;
                    }
                }
            } elseif ($params["dimension_two"] == "date") {
                $dates = getDateFromRange($start, $end);
                foreach ($dates as $v) {
                    switch ($params["dimension_one"]) {
                        case "country":
                            $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, [$params['key']], $params['day'], $params["day_num"], $params['media_source']);
                            break;
                        case "date":
                            $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], $params['country'], $params['day'], $params["day_num"], $params['media_source']);
                            break;
                        case "channel":
                            $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, $params["country"], $params['day'], $params["day_num"], [$params['key']]);
                            break;
                    }
                    $row["name"] = $v;
                    $row["hasChildren"] = true;
                    $row["id"] = mt_rand() . $v;
                    $row["attr"] = "date";
                    $row["parent_key"] = $params['key'];
                    $row["child_key"] = "";
                    $row["level"] = 2;
                    $row["key"] = $v;
                    $row["tablist"] = $row;
                    $out[] = $row;
                }
            }
        } else {
            if ($params["dimension_three"] == 'country') {
                $country = $params["country"];
                $allCountry = admincountry();
                if (empty($country)) {
                    $country = array_keys($allCountry);
                }
                foreach ($country as $c) {
                    if ($c != 'all') {
                        if ($params["dimension_one"] == "country") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], $params['media_source']);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], [$c], $params['day'], $params["day_num"], $params['media_source']);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], [$params['key']]);
                                    break;
                            }
                        } elseif ($params["dimension_one"] == "channel") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$params['key']], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], [$c], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$c], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                            }

                        } elseif ($params["dimension_one"] == "date") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], [$params['key']], $params['day'], $params["day_num"], $params['media_source']);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], [$c], $params['day'], $params["day_num"], $params['media_source']);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], [$c], $params['day'], $params["day_num"], [$params['key']]);
                                    break;
                            }

                        }
                        $row["hasChildren"] = false;
                        $row["name"] = $allCountry[$c];
                        $row["id"] = uniqid() . $params['parent_key'] . $params['key'] . $c;
                        $row["attr"] = "country";
                        $row["key"] = $c;
                        $row["level"] = $level;
                        $row["child_key"] = "";
                        $row["parent_key"] = "";
                        $row["tablist"] = $row;
                        $out[] = $row;
                    }
                }
            } elseif ($params["dimension_three"] == "channel") {
                $media_source = $params["media_source"];
                if (empty($media_source)) {
                    $media_source = array_column($this->promate_media, 'value');
                }
                foreach ($media_source as $c) {
                    if ($c != 'all') {
                        if ($params["dimension_one"] == "country") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$params["key"]], $params['day'], $params["day_num"], [$c]);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], [$params["parent_key"]], $params['day'], $params["day_num"], [$c]);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$params["parent_key"]], $params['day'], $params["day_num"], [$c]);
                                    break;
                            }
                        } elseif ($params["dimension_one"] == "channel") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, [$params['key']], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], $params["country"], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, $params["country"], $params['day'], $params["day_num"], [$params['parent_key']]);
                                    break;
                            }

                        } elseif ($params["dimension_one"] == "date") {
                            switch ($params["dimension_two"]) {
                                case "country":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], [$params['key']], $params['day'], $params["day_num"], [$c]);
                                    break;
                                case "date":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], $params["country"], $params['day'], $params["day_num"], [$c]);
                                    break;
                                case "channel":
                                    $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], $params["country"], $params['day'], $params["day_num"], [$params['key']]);
                                    break;
                            }
                        }
                        $row["name"] = $this->get_media_source_name($c);
                        $row["id"] = uniqid() . $params['parent_key'] . $params['key'] . $c;
                        $row["hasChildren"] = false;
                        $row["attr"] = "channel";
                        $row["key"] = $c;
                        $row["level"] = 2;
                        $row["parent_key"] = "";
                        $row["child_key"] = "";
                        $row["tablist"] = $row;
                        $out[] = $row;
                    }
                }
            } elseif ($params["dimension_three"] == "date") {
                $dates = getDateFromRange($start, $end);
                foreach ($dates as $v) {
                    if ($params["dimension_one"] == "country") {
                        switch ($params["dimension_two"]) {
                            case "country":
                                $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, [$params["parent_key"]], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "date":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], [$params["parent_key"]], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "channel":
                                $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, [$params["parent_key"]], $params['day'], $params["day_num"], [$params["key"]]);
                                break;
                        }
                    } elseif ($params["dimension_one"] == "channel") {
                        switch ($params["dimension_two"]) {
                            case "country":
                                $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, [$params['key']], $params['day'], $params["day_num"], [$params['parent_key']]);
                                break;
                            case "date":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["key"], $params["key"], $params["country"], $params['day'], $params["day_num"], [$params['parent_key']]);
                                break;
                            case "channel":
                                $row = $this->get_v1_ltv_data($params['app_id'], $v, $v, $params["country"], $params['day'], $params["day_num"], [$params['parent_key']]);
                                break;
                        }

                    } elseif ($params["dimension_one"] == "date") {
                        switch ($params["dimension_two"]) {
                            case "country":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], [$params['key']], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "date":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], $params["country"], $params['day'], $params["day_num"], $params['media_source']);
                                break;
                            case "channel":
                                $row = $this->get_v1_ltv_data($params['app_id'], $params["parent_key"], $params["parent_key"], $params["country"], $params['day'], $params["day_num"], [$params['key']]);
                                break;
                        }
                    }
                    $row["name"] = $v;
                    $row["hasChildren"] = false;
                    $row["id"] = mt_rand() . $v;
                    $row["attr"] = "date";
                    $row["parent_key"] = $params['key'];
                    $row["child_key"] = "";
                    $row["level"] = 2;
                    $row["key"] = $v;
                    $row["tablist"] = $row;
                    $out[] = $row;
                }
            }
        }
        echo json_encode($out);
        exit;
    }

    public function predict()
    {
        $params = input("post.");
        $date = $params["date"];
        $end = date("Y-m-d", strtotime("-2 day"));
        $res = $this->get_week_ltv($params['app_id'], $end, $params['country'] == 'all' ? [] : [$params['country']], $params['media_source'] == 'all' ? [] : [$params['media_source']]);
        $result = $this->get_day_reten($res, $params["app_id"], $date[0], $date[1], $params["media_source"], $params["country"], 90);
        echo json_encode($result);
        exit;
    }

    private function get_week_ltv($app_id, $event_date, $country, $media_source)
    {

        $where = "gb_id={$app_id} and event_date='{$event_date}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_sources = array_map(function ($v) {
                return "'{$v}'";
            }, $media_source);
            $media_sources = implode(",", $media_sources);
            $where .= " and media_source in({$media_sources})";
        }
        $media_channel = $this->get_v1_channel($this->promate_media, $media_source);
        $list = Db::query("SELECT install_date,SUM(num) as current_num 
from  hellowd_adjust_ltv_users  WHERE {$where} GROUP BY install_date order by install_date desc");
        $rangeList = $list;
        $last_arr_dates = end($rangeList);
        $last_date = $last_arr_dates["install_date"];
        $dates = getDateFromRange($last_date, $event_date);
        $ltv_ranges = [];
        $result = [];
        if (!empty($list)) {
            foreach ($list as $r) {
                $ltv_ranges[$r["install_date"]] = $r["current_num"];
            }
        }
        foreach ($dates as $d) {
            $current_num = 0;
            if (isset($ltv_ranges[$d])) {
                $current_num = $ltv_ranges[$d];
            }
            $result[] = ["install_date" => $d, "current_num" => $current_num];
        }
        $result = admin_array_sort($result, "install_date", "desc");
        $result = array_chunk($result, 7);
        $out = [];
        if (!empty($result)) {
            foreach ($result as $v) {
                $arr_sum = array_column($v, "current_num");
                $active_num = array_sum($arr_sum);
                $first_arr = $v[0];
                $last_arr = end($v);
                $row = ["active_num" => $active_num, "end" => $first_arr["install_date"], "start" => $last_arr["install_date"]];
                $spend = $this->get_v1_total_spend($app_id, $row["start"], $row["end"], $country, $media_channel);
                $row["installs"] = $spend["installs"];
                $row["reten"] = $spend["installs"] > 0 ? round($active_num / $spend["installs"], 3) * 100 : 0;
                if ($row["reten"] <= 1) {
                    return $row;
                }
                $out[] = $row;
            }
            if (!empty($out)) {
                return end($out);
            }
        }
        $start = date("Y-m-d", strtotime("-3 day"));
        $end = date("Y-m-d", strtotime("-2 day"));
        return ["start" => $start, "end" => $end, "reten" => 0, "active_num" => 0, "installs" => 0];
    }

    //计算ltv n时的当前日期
    private function get_n_num($app_id, $start, $end, $country, $media_source)
    {
        $current_date = date("Y-m-d", strtotime("-2 day"));
        $where = "gb_id={$app_id} and install_date>='{$start}' and install_date<='{$end}' and event_date='{$current_date}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_sources = array_map(function ($v) {
                return "'{$v}'";
            }, $media_source);
            $media_sources = implode(",", $media_sources);
            $where .= " and media_source in({$media_sources})";
        }
        $res = Db::name("adjust_ltv_users")->field('sum(num) as num')->where($where)->find();
        return isset($res["num"]) ? $res["num"] : 0;

    }

    private function get_v2_one_day_ltv($app_id, $spend, $start, $end, $num, $day_num, $country, $media_source, $device_category)
    {
        $current_date = date("Y-m-d", strtotime("-2 day"));
        $current_num = count(getDateFromRange($end, $current_date));
        $before_num = count(getDateFromRange($start, $current_date));
        $dates = getDateFromRange($start, $end);
		$media_channel = $this->get_v1_channel($this->promate_media, $media_source);
        $res = array(
            "total_revenue" => "0.00",
            "ad_revenue" => "0.00",
            "purchase" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
            "new_total_revenue" => "0.00",
            "new_ad_revenue" => "0.00",
            "new_Reward" => 0,
            "new_Inter" => 0,
			"spend"=>"0.00",
			"installs"=>0,
			"roi"=>"0.00",
			"avg_revenue"=>"0.00"
        );
        foreach ($dates as $key => $v) {
            if ($num == 'n') {
                if ($day_num == '') {
                    $time = $current_date;
                } else {
                    $time = date("Y-m-d", (strtotime($v) + $current_num * 24 * 3600));
                }
            } else {
                $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
				$nt = strtotime("-2 day");
				$tn = strtotime($v) + ($num * 24 * 3600);
				if($tn>$nt)
				{					
					continue;
				}
            }
            $row = $this->get_v2_revenue($app_id, $v, $v, $time, $country, $media_source, $device_category);
			$spend = $this->get_v1_total_spend($app_id, $v, $v, $country, $media_channel);
            $res["total_revenue"] += $row["revenue"];
            $res["Reward"] += $row["rew_show"];
            $res["Inter"] += $row["int_show"];
            $res["ad_revenue"] += $row["ad_revenue"];
            $res["new_total_revenue"] += $row["new_revenue"];
            $res["new_ad_revenue"] += $row["new_ad_revenue"];
            $res["new_Reward"] += $row["new_rew_show"];
            $res["new_Inter"] += $row["new_int_show"];
            $res["purchase"] += $row["purchase"];
			$res["spend"] += $spend["spend"];
			$res["installs"] += $spend["installs"];
        }
        $end_day = $current_num;
        //$current_active = $this->get_n_num($app_id, $start, $end, $country, $media_source);
        $installs = $spend["installs"];
        $reten =0; //$installs > 0 ? round($current_active * 100 / $installs, 3) : 0;
        if ($num == 'n') {
            if ($day_num == '') {
                $end_day = ceil(($before_num + $current_num) / 2);
            }
        }
        $res["end_day"] = $end_day;
        $res["reten"] = $reten;
		$res["roi"] = $res["spend"] > 0 ? round($res["new_total_revenue"] * 100 / $res["spend"], 2) : 0;
		$res["avg_revenue"]=$res["installs"]> 0 ? round($res["new_total_revenue"] / $res["installs"], 3):0;
        return $res;
    }
	
	//核对没问题后加入的应用ID
	private $allow_app_list =[
	    "135", "160", "158", "166", "169", "167"
	];

    private function get_v2_revenue($app_id, $install_date, $event_start_date, $event_end_date, $country, $media_source, $device_category)
    {


        $where = "gb_id={$app_id} and install_date='{$install_date}' and event_date>='{$event_start_date}' and event_date<='{$event_end_date}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        if (!empty($media_source)) {
            $media_sources = array_map(function ($v) {
                return "'{$v}'";
            }, $media_source);
            $media_sources = implode(",", $media_sources);
            $where .= " and media_source in({$media_sources})";
        }
        if ($device_category && $device_category != 'all') {
             if (in_array($app_id, [153])) {
                if ($device_category == 'ipad') {
                    $device_category = 'iPad';
                }
				if ($device_category == 'phone') {
                    $device_category = 'iPhone';
                }
            } else {
                if ($device_category == 'phone') {
                    $device_category = "iPhone";
                } elseif ($device_category == 'ipod') {
                    $device_category = 'iPod touch';
                }
            }
            $where .= " and device_category='{$device_category}'";
        }
	
		if ($event_start_date<'2020-09-01'){
			$row = Db::name("adjust_ltv")->field('sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
			$new_row = $row;
		}else{
			$new_row = Db::name("adjust_ltv_pdt")->field('sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
			$row = $new_row;
		}	
        
        if (!empty($row)) {
            $row['ad_revenue'] = $row["revenue"];
            $row['new_revenue'] = $new_row["revenue"];
            $row['new_ad_revenue'] = $new_row["revenue"];
            $row['new_int_show'] = $new_row["int_show"];
            $row['new_rew_show'] = $new_row["rew_show"];
            $purchase = '0.00';
            if (in_array($app_id,$this->allow_app_list)) {
                $purchase = $this->getpurchasev1($app_id, $install_date, $event_start_date, $event_end_date, "", "",$country, $media_source);
                $row["revenue"] += $purchase;
                $row['new_revenue'] += $purchase;
            }
            $row['purchase'] = $purchase;
            return $row;
        }
        return ["revenue" => '0.00', "purchase" => '0.00', "ad_revenue" => '0.00', "int_show" => 0, 'rew_show' => 0];
    }

    public function ltv_test($date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        $out = [];
        $sql = " SELECT ad_source,adtype,sum(num) as num  from  hellowd_adjust_impression WHERE event_date='{$date}' 
 and install_date='{$date}' and country='US' and gb_id=143 GROUP BY adtype,ad_source";
        $list = Db::query($sql);

        if (!empty($list)) {
            foreach ($list as $vv) {
                $total_revenue = "0.00";
                $int_show = 0;
                $rew_show = 0;
                $rate = 1;
                $ad_channel = $this->getchannel($this->network_media, $vv["ad_source"]);
                $adtype = "";
                switch ($vv["adtype"]) {
                    case 'Inter':
                        $adtype = 'int';
                        $int_show += $vv["num"];
                        break;
                    case 'Reward':
                        $adtype = 'rew';
                        $rew_show += $vv["num"];
                        break;
                }
                $ecpm = $this->getEcpm(143, $date, $date, 'US', $ad_channel, $adtype);

                if ($ad_channel == '5') {
                    $rate = "0.92";
                }
                $revenue = $vv["num"] * $ecpm * $rate;
                $total_revenue += $revenue;
                $row = $vv;
                $row["revenue"] = $total_revenue;
                $out[] = $row;
            }

        }
        print_r($out);
        exit;

    }

    // ltv 数据汇总
    public function update_ltv($date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        $sql = " SELECT media_source,install_date,event_date,country,device_category,gb_id  from  hellowd_adjust_impression WHERE event_date='{$date}' 
GROUP BY media_source,install_date,event_date,country,device_category,gb_id";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$v) {
                if ($v["gb_id"]) {
                    $list = Db::name("adjust_impression")->field('ad_source,adtype,sum(num) as num')->where($v)->group('adtype,ad_source')->select();
                    $total_revenue = "0.00";
                    $int_show = 0;
                    $rew_show = 0;
                    if (!empty($list)) {
                        foreach ($list as $vv) {
                            $rate = 1;
                            $ad_channel = $this->getchannel($this->network_media, $vv["ad_source"]);
                            $adtype = "";
                            switch ($vv["adtype"]) {
                                case 'Inter':
                                    $adtype = 'int';
                                    $int_show += $vv["num"];
                                    break;
                                case 'Reward':
                                    $adtype = 'rew';
                                    $rew_show += $vv["num"];
                                    break;
                            }
                            $ecpm = $this->getEcpm($v["gb_id"], $v["event_date"], $v["event_date"], $v["country"], $ad_channel, $adtype);

                            if ($ad_channel == '5') {
                                $rate = "0.92";
                            }
                            $revenue = $vv["num"] * $ecpm * $rate;
                            $total_revenue += $revenue;
                        }

                    }
                    $row = Db::name("adjust_ltv")->where($v)->find();
                    if (!empty($row)) {
                        Db::name("adjust_ltv")->where("id", $row["id"])->update(["revenue" => $total_revenue, "int_show" => $int_show, "rew_show" => $rew_show]);
                    } else {
                        $data = $v;
                        $data = array_merge($data, ["revenue" => $total_revenue, "int_show" => $int_show, "rew_show" => $rew_show]);
                        Db::name("adjust_ltv")->insert($data);
                    }
                }
            }
        }
        exit("ok");
    }


    //广告系列数据更新
    public function update_campaign_ltv($date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        $sql = " SELECT media_source,install_date,event_date,campaign_id,gb_id,campaign_name,country  from hellowd_adjust_campaign WHERE event_date='{$date}'
GROUP BY media_source,install_date,event_date,campaign_id,gb_id,country";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$v) {
                if (!$v["gb_id"]) continue;
                $where = $v;
                unset($where["campaign_name"]);
                $list = Db::name("adjust_campaign")->field('ad_source,adtype,sum(num) as num')->where($where)->group('adtype,ad_source')->select();
                $total_revenue = "0.00";
                $int_show = 0;
                $rew_show = 0;
                if (!empty($list)) {
                    foreach ($list as $vv) {
                        $rate = 1;
                        $ad_channel = $this->getchannel($this->network_media, $vv["ad_source"]);
                        $adtype = "";
                        switch ($vv["adtype"]) {
                            case 'Inter':
                                $adtype = 'int';
                                $int_show += $vv["num"];
                                break;
                            case 'Reward':
                                $adtype = 'rew';
                                $rew_show += $vv["num"];
                                break;
                        }
                        $ecpm = $this->getEcpm($v["gb_id"], $v["event_date"], $v["event_date"], $v["country"], $ad_channel, $adtype);

                        if ($ad_channel == '5') {
                            $rate = "0.92";
                        }
                        $revenue = $vv["num"] * $ecpm * $rate;
                        $total_revenue += $revenue;
                    }
                }
                $data = $v;
                $data = array_merge($data, ["revenue" => $total_revenue, "int_show" => $int_show, "rew_show" => $rew_show]);
                Db::name("adjust_campaign_ltv")->insert($data);
                /* $row = Db::name("adjust_campaign_ltv")->where($v)->find();
				if( !empty($row) )
				{
					Db::name("adjust_campaign_ltv")->where("id",$row["id"])->update(["revenue"=>$total_revenue,"int_show"=>$int_show,"rew_show"=>$rew_show]);
				}else{
					$data = $v;
					$data = array_merge($data,["revenue"=>$total_revenue,"int_show"=>$int_show,"rew_show"=>$rew_show]);
					Db::name("adjust_campaign_ltv")->insert($data);
				} */
            }
        }
        exit("ok");
    }

    //*****************************************
    //更新子渠道数据
    public function update_adset_ltv($date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        $sql = " SELECT media_source,install_date,event_date,adset_id,gb_id,country  from hellowd_adjust_adgroup WHERE event_date='{$date}'
GROUP BY media_source,install_date,event_date,adset_id,gb_id,country";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$v) {
                if (!$v["gb_id"]) continue;
                $where = $v;
                $list = Db::name("adjust_adgroup")->field('ad_source,adtype,sum(num) as num')->where($where)->group('adtype,ad_source')->select();
                $total_revenue = "0.00";
                $int_show = 0;
                $rew_show = 0;
                if (!empty($list)) {
                    foreach ($list as $vv) {
                        $rate = 1;
                        $ad_channel = $this->getchannel($this->network_media, $vv["ad_source"]);
                        $adtype = "";
                        switch ($vv["adtype"]) {
                            case 'Inter':
                                $adtype = 'int';
                                $int_show += $vv["num"];
                                break;
                            case 'Reward':
                                $adtype = 'rew';
                                $rew_show += $vv["num"];
                                break;
                        }
                        $ecpm = $this->getEcpm($v["gb_id"], $v["event_date"], $v["event_date"], $v["country"], $ad_channel, $adtype);

                        if ($ad_channel == '5') {
                            $rate = "0.92";
                        }
                        $revenue = $vv["num"] * $ecpm * $rate;
                        $total_revenue += $revenue;
                    }
                }
                $data = $v;
                $data = array_merge($data, ["revenue" => $total_revenue, "int_show" => $int_show, "rew_show" => $rew_show]);
                Db::name("adjust_adgroup_ltv")->insert($data);
            }
        }
        exit("ok");
    }



    //*****************************************
    //更新素材数据
    public function update_ad_ltv($date = "")
    {
        if ($date == "") {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        $sql = " SELECT install_date,event_date,ad_id,gb_id,country  from hellowd_adjust_ad WHERE event_date='{$date}'
GROUP BY install_date,event_date,ad_id,gb_id,country";
        $res = Db::query($sql);
        if (!empty($res)) {
            foreach ($res as &$v) {
                $where = $v;
                $list = Db::name("adjust_ad")->field('ad_source,adtype,sum(num) as num')->where($where)->group('adtype,ad_source')->select();
                $total_revenue = "0.00";
                $int_show = 0;
                $rew_show = 0;
                if (!empty($list)) {
                    foreach ($list as $vv) {
                        $rate = 1;
                        $ad_channel = $this->getchannel($this->network_media, $vv["ad_source"]);
                        $adtype = "";
                        switch ($vv["adtype"]) {
                            case 'Inter':
                                $adtype = 'int';
                                $int_show += $vv["num"];
                                break;
                            case 'Reward':
                                $adtype = 'rew';
                                $rew_show += $vv["num"];
                                break;
                        }
                        $ecpm = $this->getEcpm($v["gb_id"], $v["event_date"], $v["event_date"], $v["country"], $ad_channel, $adtype);

                        if ($ad_channel == '5') {
                            $rate = "0.92";
                        }
                        $revenue = $vv["num"] * $ecpm * $rate;
                        $total_revenue += $revenue;
                    }
                }
                $data = $v;
                $data["media_source"] = "Facebook Ads";
                $data = array_merge($data, ["revenue" => $total_revenue, "int_show" => $int_show, "rew_show" => $rew_show]);
                Db::name("adjust_ad_ltv")->insert($data);
            }
        }
        exit("ok");
    }
    //*****************************************
    // 广告系列ltv数据
    private function get_campaign_sepend($app_id, $campaign_id, $start, $end, $country, $channel)
    {
        $out_data = ["installs" => "0", "spend" => "0.00"];
        if (preg_match('/\(/', $campaign_id)) {
            $arr = explode("(", $campaign_id);
            $campaign_id = end($arr);
        }
        $where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}' and campaign_id='{$campaign_id}'";
		if($channel==32)
		{
			$where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}' and adset_id='{$campaign_id}'";
		}
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }
        if ($channel != "all") {
            $where .= " and platform_type='{$channel}'";
        }

        $row = Db::name("adspend_data")->field('sum(installs) as installs,sum(spend) as spend')->where($where)->find();
        if (!empty($row)) {
            $out_data = ["installs" => (int)$row["installs"], "spend" => round($row["spend"], 2)];
        }
        return $out_data;
    }
	
	private function get_adset_sepend($app_id, $adset_id, $start, $end, $country, $channel)
    {
        $out_data = ["installs" => "0", "spend" => "0.00"];
        
        $where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}' and adset_id='{$adset_id}'";
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }
        if ($channel != "all") {
            $where .= " and platform_type='{$channel}'";
        }

        $row = Db::name("adspend_data")->field('sum(installs) as installs,sum(spend) as spend')->where($where)->find();
        if (!empty($row)) {
            $out_data = ["installs" => (int)$row["installs"], "spend" => round($row["spend"], 2)];
        }
        return $out_data;
    }

    private function get_campaign_revenue($app_id, $campaign_id, $install_date, $event_start_date, $event_end_date, $country, $media_source)
    {


        $where = "gb_id={$app_id} and campaign_id='{$campaign_id}' and install_date='{$install_date}' and event_date>='{$event_start_date}' and event_date<='{$event_end_date}'";
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }
        if ($media_source) {
            $where .= " and media_source='{$media_source}'";
        }
        //$row = Db::name("adjust_campaign_ltv")->field('sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
        $new_row = Db::name("adjust_campaign_ltv_pdt")->field('campaign_name,sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
		$row = $new_row;

        if (!empty($row)) {
            $row['ad_revenue'] = $row["revenue"];
            $row['new_revenue'] = $new_row["revenue"];
            $row['new_ad_revenue'] = $new_row["revenue"];
            $row['new_int_show'] = $new_row["int_show"];
            $row['new_rew_show'] = $new_row["rew_show"];
            $purchase = '0.00';
            if (in_array($app_id,$this->allow_app_list)) {
                if($media_source=="unityads_int")
				{
					$campaign_id = $new_row["campaign_name"];
				}
				$purchase = $this->getpurchasev1($app_id, $install_date, $event_start_date, $event_end_date, $campaign_id,"",[], [$media_source]);
                $row["revenue"] += $purchase;
                $row["new_revenue"] += $purchase;
            }
            $row['purchase'] = $purchase;
            return $row;
        }
        return ["revenue" => '0.00', "purchase" => '0.00', "ad_revenue" => '0.00', "int_show" => 0, 'rew_show' => 0];
    }

    private function get_adset_revenue($app_id, $adset_id, $install_date, $event_start_date, $event_end_date, $country, $media_source)
    {


        $where = "gb_id={$app_id} and install_date='{$install_date}' and event_date>='{$event_start_date}' and event_date<='{$event_end_date}'";
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }
		if ($adset_id && $adset_id!='') {
            $where .= " and adset_id='{$adset_id}'";			
        }
        if ($media_source) {
            $where .= " and media_source='{$media_source}'";
        }

        $row = Db::name("adjust_adgroup_ltv_pdt")->field('sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
        if (!empty($row)) {
            $row['ad_revenue'] = $row["revenue"];
			$purchase ="0.00";
            if (in_array($app_id,$this->allow_app_list)) {
                $purchase = $this->getpurchasev1($app_id, $install_date, $event_start_date, $event_end_date,"",$adset_id,[], [$media_source]);
                $row["revenue"] += $purchase;
            }
			$row['purchase'] = $purchase;
            return $row;
        }
        return ["revenue" => '0.00', "purchase" => '0.00', "ad_revenue" => '0.00', "int_show" => 0, 'rew_show' => 0];
    }

    private function get_ad_revenue($app_id, $ad_id, $install_date, $event_start_date, $event_end_date, $country, $media_source)
    {


        $where = "gb_id={$app_id} and ad_id='{$ad_id}' and install_date='{$install_date}' and event_date>='{$event_start_date}' and event_date<='{$event_end_date}'";
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }

        $row = Db::name("adjust_ad_ltv")->field('sum(revenue) as revenue,sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
        if (!empty($row)) {
            $row['ad_revenue'] = $row["revenue"];
            $row['purchase'] = '0.00';
            return $row;
        }
        return ["revenue" => '0.00', "purchase" => '0.00', "ad_revenue" => '0.00', "int_show" => 0, 'rew_show' => 0];
    }

    private function get_campaign_ltv($app_id, $campaign_id, $spend, $start, $end, $num, $country, $media_source)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "total_revenue" => "0.00",
            "ad_revenue" => "0.00",
            "purchase" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
            "new_total_revenue" => "0.00",
            "new_ad_revenue" => "0.00",
            "new_Reward" => 0,
            "new_Inter" => 0,
        );
        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $row = $this->get_campaign_revenue($app_id, $campaign_id, $v, $v, $time, $country, $media_source);
            $res["total_revenue"] += $row["revenue"];
            $res["ad_revenue"] += $row["ad_revenue"];
            $res["purchase"] += $row["purchase"];
            $res["Reward"] += $row["rew_show"];
            $res["Inter"] += $row["int_show"];
            $res["new_total_revenue"] += $row["new_revenue"];
            $res["new_ad_revenue"] += $row["new_ad_revenue"];
            $res["new_Reward"] += $row["new_rew_show"];
            $res["new_Inter"] += $row["new_int_show"];
        }
        return $res;
    }

    private function get_adset_ltv($app_id, $adset_id, $spend, $start, $end, $num, $country, $media_source)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "total_revenue" => "0.00",
            "ad_revenue" => "0.00",
            "purchase" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
        );
        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $row = $this->get_adset_revenue($app_id, $adset_id, $v, $v, $time, $country, $media_source);
            $res["total_revenue"] += $row["revenue"];
            $res["ad_revenue"] += $row["ad_revenue"];
            $res["purchase"] += $row["purchase"];
            $res["Reward"] += $row["rew_show"];
            $res["Inter"] += $row["int_show"];
        }
        return $res;
    }

    private function get_ad_ltv($app_id, $ad_id, $spend, $start, $end, $num, $country, $media_source)
    {
        $dates = getDateFromRange($start, $end);
        $res = array(
            "total_revenue" => "0.00",
            "ad_revenue" => "0.00",
            "purchase" => "0.00",
            "Reward" => 0,
            "Inter" => 0,
        );
        foreach ($dates as $key => $v) {
            $time = date("Y-m-d", (strtotime($v) + $num * 24 * 3600));
            $row = $this->get_ad_revenue($app_id, $ad_id, $v, $v, $time, $country, $media_source);
            $res["total_revenue"] += $row["revenue"];
            $res["ad_revenue"] += $row["ad_revenue"];
            $res["purchase"] += $row["purchase"];
            $res["Reward"] += $row["rew_show"];
            $res["Inter"] += $row["int_show"];
        }
        return $res;
    }

    public function campaign_ltv_json()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
		$admin_id = Session::get('admin_userid');
        $listRes = $this->get_campaign_list($admin_id,$params['own'],$params['app_id'], $start, $end, $params['media_source'],$params["page"],$params["status"],$params["keyword"]);
		$list = $listRes["res"];
		$total = $listRes["total"];
		$page_size = $listRes["page_size"];
        $out = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                
				$row = $this->get_campaign_ltv_data($params['app_id'], $v["campaign_id"], $start, $end, "", $params['day'], $params['media_source']);
                $row["name"] = $v["campaign_name"];
				$row["hasChildren"] = true;
				$row["id"] = uniqid();
				$row["campaign_status"] = isset($v["campaign_status"])?$v["campaign_status"]:"";
				$row["campaign_id"] = $v["campaign_id"];
                $row["tablist"] = $row;
                $out[] = $row;
            }
        }
        echo json_encode(["data"=>$out,"total"=>$total,"page_size"=>$page_size,"date"=>[$start,$end]] );
        exit;
    }
	
	
	public function campaign_ltv_child_json()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
        $dates = getDateFromRange($start,$end);
        $out = [];
		foreach ($dates as $v) {
			$row = $this->get_campaign_ltv_data($params['app_id'], $params["campaign_id"], $v, $v, "", $params['day'], $params['media_source']);
			$row["name"] = $v . "(" . getweekday($v) . ")";
			$row["hasChildren"] = false;
			$row["id"] ="";
			$row["tablist"] = $row;
			$out[] = $row;
		}
        echo json_encode($out);
        exit;
    }
	public function adset_ltv_child_json()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
        $dates = getDateFromRange($start,$end);
        $out = [];
		foreach ($dates as $v) {
			$media_channel = $this->getchannel($this->promate_media,$params['media_source']);
			$spend = $this->get_adset_sepend($params['app_id'],$params["adset_id"], $v, $v,$params['country'], $media_channel);
			$row = $this->get_adset_ltv_data($params['app_id'],$spend,$params["adset_id"], $v, $v, $params['country'], $params['day'], $params['media_source']);
			$row["name"] = $v . "(" . getweekday($v) . ")";
			$row["hasChildren"] = false;
			$row["id"] ="";
			$row["tablist"] = $row;
			$out[] = $row;
		}
        echo json_encode($out);
        exit;
    }
	
	
	public function get_adset_ltv_total_data()
	{
		$out = [];
		$params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
		$row =[];		
		$lists = $this->get_adset_list($params['app_id'],$params["keyword"],$start, $end,$params['country'],$params['media_source']);
		$spend = ["spend"=>array_sum(array_column($lists,"spend")),"installs"=>array_sum(array_column($lists,"installs")) ];
		$row = $this->get_adset_ltv_data($params['app_id'],$spend,$params["keyword"],$start,$end,$params['country'],$params['day'], $params['media_source']);			
		echo json_encode($row);
        exit;
	}

    public function adset_ltv_json()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
		$list =[];
		$list = $this->get_adset_list($params['app_id'],$params["keyword"],$start, $end,$params['country'],$params['media_source']); 
        $out = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $row = $this->get_adset_ltv_data($params['app_id'], $v, $v["adset_id"], $start, $end,$params['country'], $params['day'], $params['media_source']);
                $row["name"] = $v["adset_name"].$v["adset_id"];
				$row["hasChildren"] = true;
				$row["id"] = uniqid();
				$row["adset_id"] = $v["adset_id"];
                $row["tablist"] = $row;
                $out[] = $row;
            }
        }
        echo json_encode(["data"=>$out,"date"=>[$start,$end]]);
        exit;
    }

    public function ad_ltv_json()
    {
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
        $list = $this->get_ad_list($params['app_id'], $start, $end, $params['media_source']);
        $out = [];
        if (!empty($list)) {
            foreach ($list as $v) {
                $row = $this->get_ad_ltv_data($params['app_id'], $v, $v["ad_id"], $start, $end, "", $params['day'], $params['media_source']);
                $row["name"] = $v["ad_name"];
				$row["ad_id"] = $v["ad_id"];
                $row["adset_name"] = $v["adset_name"];
                $row["campaign_name"] = $v["campaign_name"];
                $row["tablist"] = $row;
                $out[] = $row;
            }
        }
        echo json_encode(["data"=>$out,"date"=>[$start,$end]]);
        exit;
    }

    private function get_campaign_list($userid,$own,$app_id, $start, $end, $media_source,$page,$status,$keyword)
    {
        $where = "install_date>='{$start}' and campaign_id!='' and install_date<='{$end}' and gb_id={$app_id} and media_source='{$media_source}'";
		$page_size =6;
		if($status!="all" || $keyword!="")
		{
			if(preg_match('/\+/',$keyword))
			{
				$arr = explode("+",$keyword);
				if($keyword!="" && !empty($arr))
				{
					foreach($arr as $a)
					{
						$where.=" and campaign_name REGEXP '{$a}'";
					}
				}
			}elseif(preg_match('/\-/',$keyword)){
				//
				$arr = explode("-",$keyword);
				$first = array_shift($arr);
				$where.=" and campaign_name REGEXP '{$first}'";
				if($keyword!="" && !empty($arr))
				{
					foreach($arr as $a)
					{
						$where.=" and campaign_name not REGEXP '{$a}'";
					}
				}
			}elseif($keyword!=''){
				$where.=" and campaign_name REGEXP '{$keyword}'";
			}
			
			$result = Db::name("adjust_campaign_ltv_pdt")->field("campaign_id,campaign_name")->where($where)->group('campaign_id')->select();
			
			$res =[];
			if(!empty($result))
			{
				$db = Db::connect('mysql://thehotgames:week2e13&hellowd@127.0.0.1:3306/ads_service#utf8mb4');
				foreach($result as &$v)
				{
					$cam = $db->table("ads_online_info_campaign")->field('status,advertiser_id')->where(["campaign_id"=>$v["campaign_id"]])->find();
					if($status!="all" && $cam["status"]!=$status)
					{
						continue;
					}
					if($own=="my")
					{
						$num = Db::name("advertising_account")->where(["advertiser_id"=>$cam['advertiser_id'],"ad_userid"=>$userid])->count();
						
						if($num<1)
						{
							continue;
						}
					}
					$v["campaign_status"] = $cam["status"];
					$res[] =$v;
				}
			}
			$total = count($res);
			$page_size  =$total;
		}else{
			$res =[];
			$result = Db::name("adjust_campaign_ltv_pdt")->field("campaign_id,campaign_name")->where($where)->group('campaign_id')->page("{$page},6")->select();
			if(!empty($result))
			{
				$db = Db::connect('mysql://thehotgames:week2e13&hellowd@127.0.0.1:3306/ads_service#utf8mb4');
				foreach($result as &$v)
				{
					$cam = $db->table("ads_online_info_campaign")->field('status,advertiser_id')->where(["campaign_id"=>$v["campaign_id"]])->find();
					if($status!="all" && $cam["status"]!=$status)
					{
						continue;
					}
					
					if($own=="my")
					{
						$num = Db::name("advertising_account")->where(["advertiser_id"=>$cam['advertiser_id'],"ad_userid"=>$userid])->count();
						
						if($num<1)
						{
							continue;
						}
					}
					$v["campaign_status"] = $cam["status"];
					$res[] =$v;
				}
			}
			$total = count($res);
			//$page_size  =$total;
		    $total = Db::name("adjust_campaign_ltv_pdt")->field("*")->where($where)->group('campaign_id')->count();
		}
        
        return ["res"=>$res,"total"=>$total,"page_size"=>$page_size];
    }

    private function get_adset_list($app_id,$keyword,$start, $end, $country,$media_source)
    {
        $media_channel = $this->getchannel($this->promate_media, $media_source);	
        $where = "date>='{$start}' and date<='{$end}' and app_id={$app_id} and platform_type='{$media_channel}'";
		if($country && $country!='all')
		{
			$where.=" and country='{$country}'";
		}
		if($keyword!="")
		{
			$where.=" and adset_id like '%{$keyword}%'";
		}
		$where.=" and installs>0";
        $res = Db::name("adspend_data")->field("adset_id,adset_name,sum(spend) as spend,ceil(sum(installs)) as installs")->where($where)->group('adset_id')->select();
        return $res;
    }

    private function get_ad_list($app_id, $start, $end, $media_source)
    {

        $where = "date>='{$start}' and date<='{$end}' and app_id={$app_id} and platform_type='6'";
        $res = Db::name("adspend_data")->field("ad_id,ad_name,adset_name,campaign_name,sum(spend) as spend,ceil(sum(installs)) as installs")->where($where)->group('ad_id')->select();
        return $res;
    }

    private function get_campaign_ltv_data($app_id = "", $campaign_id, $start, $end, $country, $day = "", $media_source)
    {
        $media_channel = $this->getchannel($this->promate_media, $media_source);
        $spend =$this->get_campaign_sepend($app_id, $campaign_id, $start, $end, $country, $media_channel);
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $r = $this->get_campaign_ltv($app_id, $campaign_id, $spend, $start, $end, $day, $country, $media_source);
        $out["total_revenue"] = round($r["total_revenue"], 2);
        $out["ad_revenue"] = round($r["ad_revenue"], 2);
        $out["purchase"] = round($r["purchase"], 2);
        $out["cpi"] = $cpi;
        $out["spend"] = round($spend["spend"], 2);
        $out["installs"] = $spend["installs"];
        $out["avg_purchase"] = $spend["installs"] > 0 ? round($r["purchase"] / $spend["installs"], 3) : 0;
        $out["avg_revenue"] = $spend["installs"] > 0 ? round($r["total_revenue"] / $spend["installs"], 3) : 0;
        $out["avg_ad_revenue"] = $spend["installs"] > 0 ? round($r["ad_revenue"] / $spend["installs"], 3) : 0;
        $out["predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["avg_revenue"] / $this->predict_cpi[$day], 3) : $out["avg_revenue"];
        $out["avgReward"] = $spend["installs"] > 0 ? round($r["Reward"] / $spend["installs"], 2) : "0";
        $out["avgInter"] = $spend["installs"] > 0 ? round($r["Inter"] / $spend["installs"], 2) : "0";
        $out["total_roi"] = $spend["spend"] > 0 ? round($r["total_revenue"] * 100 / $spend["spend"], 2) : 0;
        $out["new_total_revenue"] = round($r["new_total_revenue"], 2);
        $out["new_ad_revenue"] = round($r["new_ad_revenue"], 2);
        $out["new_avg_revenue"] = $spend["installs"] > 0 ? round($r["new_total_revenue"] / $spend["installs"], 3) : 0;
        $out["new_avg_ad_revenue"] = $spend["installs"] > 0 ? round($r["new_ad_revenue"] / $spend["installs"], 3) : 0;
        $out["new_predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["new_avg_revenue"] / $this->predict_cpi[$day], 3) : $out["new_avg_revenue"];
        $out["new_avgReward"] = $spend["installs"] > 0 ? round($r["new_Reward"] / $spend["installs"], 2) : "0";
        $out["new_avgInter"] = $spend["installs"] > 0 ? round($r["new_Inter"] / $spend["installs"], 2) : "0";
        $out["new_total_roi"] = $spend["spend"] > 0 ? round($r["new_total_revenue"] * 100 / $spend["spend"], 2) : 0;
        return $out;
    }

    private function get_adset_ltv_data($app_id = "", $spend, $adset_id, $start, $end, $country, $day = "", $media_source)
    {
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $r = $this->get_adset_ltv($app_id, $adset_id, $spend, $start, $end, $day, $country, $media_source);
        $out["total_revenue"] = round($r["total_revenue"],2);
        $out["ad_revenue"] = round($r["ad_revenue"], 2);
        $out["purchase"] = round($r["purchase"], 2);
        $out["cpi"] = $cpi;
        $out["spend"] = round($spend["spend"], 2);
        $out["installs"] = $spend["installs"];		
        $out["avg_revenue"] = $spend["installs"] > 0 ? round($r["total_revenue"] / $spend["installs"], 3) : 0;
        $out["avgReward"] = $spend["installs"] > 0 ? round($r["Reward"] / $spend["installs"], 2) : "0";
        $out["avgInter"] = $spend["installs"] > 0 ? round($r["Inter"] / $spend["installs"], 2) : "0";
        $out["total_roi"] = $spend["spend"] > 0 ? round($r["total_revenue"] * 100 / $spend["spend"], 2) : 0;
		$out["predict_cpi"] = isset($this->predict_cpi[$day]) ? round($out["avg_revenue"] / $this->predict_cpi[$day], 3) : $out["avg_revenue"];
        return $out;
    }

    private function get_ad_ltv_data($app_id = "", $spend, $ad_id, $start, $end, $country, $day = "", $media_source)
    {
        $cpi = $spend["installs"] > 0 ? round($spend["spend"] / $spend["installs"], 2) : "0.00";
        $out_data = [];
        $r = $this->get_ad_ltv($app_id, $ad_id, $spend, $start, $end, $day, $country, $media_source);
        $out["total_revenue"] = round($r["total_revenue"], 2);
        $out["ad_revenue"] = round($r["ad_revenue"], 2);
        $out["purchase"] = round($r["purchase"], 2);
        $out["cpi"] = $cpi;
        $out["spend"] = round($spend["spend"], 2);
        $out["installs"] = $spend["installs"];
        $out["avg_revenue"] = $spend["installs"] > 0 ? round($r["total_revenue"] / $spend["installs"], 3) : 0;
        $out["avgReward"] = $spend["installs"] > 0 ? round($r["Reward"] / $spend["installs"], 2) : "0";
        $out["avgInter"] = $spend["installs"] > 0 ? round($r["Inter"] / $spend["installs"], 2) : "0";
        $out["total_roi"] = $spend["spend"] > 0 ? round($r["total_revenue"] * 100 / $spend["spend"], 2) : 0;
        return $out;
    }

    //**********************分设备***************
    public function get_device_ltv_data()
    {
        $devices = $this->device;
        $out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        } else {
            list($start, $end) = $params["date"];
        }
        foreach ($devices as $c) {
            if ($c["value"] != 'all') {
                $row = $this->get_v1_ltv_data($params['app_id'], $start, $end, $params['country'], $params['day'], $params["day_num"], $params['media_source'], true, $c["value"]);
                $row["name"] = $c["name"];
                $row["id"] = uniqid() . $c["value"];
                $row["attr"] = "device";
                $row["key"] = $c["value"];
                $row["parent_key"] = "";
                $row["child_key"] = "";
                $row["tablist"] = $row;
                $out[] = $row;
            }
        }
        echo json_encode($out);
        exit;
    }

    //对外的数据接口
    public function data_json($gb_id = "", $type = "", $start = "", $end = "", $country = [], $media_source = [], $day = 1)
    {
        $data = [];
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-2 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        $media_channel = $this->get_v1_channel($this->promate_media, $media_source);
        if (in_array($type, ["ecpm", "spend", "reten"])) {
            $list = getDateFromRange($start, $end);
            foreach ($list as $v) {
                switch ($type) {

                    case "spend":
                        $result = $this->get_v1_total_spend($gb_id, $v, $v, $country, $media_channel);
                        $result["cpi"] = $result["installs"] <= 0 ? "0.0" : round($result["spend"] / $result["installs"], 2);
                        $result["date"] = $v;
                        $data[] = $result;
                        break;
                    case "ecpm":
                        $result = $this->getnewrevenuetotal($gb_id, $v, $v, "all", $country);
                        $result["date"] = $v;
                        $data[] = $result;
                        break;
                    case "reten":
                        $result = $this->getdayreten($gb_id, $v, $v, $country, $day);
                        $data[] = ["date" => $v, "countryList" => $result];
                        break;
                }

            }
        }
        echo json_encode($data);
        exit;
    }

    private function getdayreten($appid, $start, $end, $country, $day)
    {

        $start = date("Y-m-d", strtotime("+{$day} day", strtotime($start)));
        $end = date("Y-m-d", strtotime("+{$day} day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        $reten_sql = "select country,sum(retention_{$day}) as val from hellowd_retention where {$where} group by country";
        $d = Db::query($reten_sql);
        if (empty($d)) {
            return [];
        }
        return $d;
    }

    private function getnewrevenuetotal($appid, $start = "", $end = "", $platform = "all", $country = [])
    {
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($platform != "all") {
            $where .= " and platform={$platform}";
        }
        if (!empty($country)) {
            $countrys = array_map(function ($v) {
                return "'{$v}'";
            }, $country);
            $countrys = implode(",", $countrys);
            $where .= " and country in({$countrys})";
        }
        $r = array
        (
            "int" => array
            (
                "impressions" => 0,
                "revenue" => "0.0",
                "ecpm" => "0.0",
                "avgshow" => 0
            ),
            "rew" => array
            (
                "impressions" => 0,
                "revenue" => "0.0",
                "ecpm" => "0.0",
                "avgshow" => 0
            ),
            "ban" => array
            (
                "impressions" => 0,
                "revenue" => "0.0",
                "ecpm" => "0.0",
                "avgshow" => 0
            ),
            "nat" => array
            (
                "impressions" => 0,
                "revenue" => "0.0",
                "ecpm" => "0.0",
                "avgshow" => 0
            ),
            "total" => array
            (
                "impressions" => 0,
                "revenue" => "0.0",
                "ecpm" => "0.0",
                "avgshow" => 0
            )

        );
        $impressions = 0;
        $revenue = "0.00";
        $sum_sql = "select platform,if(adtype!='',adtype,'no') as adtype,sum(impression) as impressions,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} group by adtype,platform";
        $res = Db::query($sum_sql);
        $month_start = date("Y-m", strtotime($start));
        $month_end = date("Y-m", strtotime($end));
        if (!empty($res)) {
            foreach ($res as &$v) {
                if (isset($r[$v["adtype"]]["impressions"])) {
                    $r[$v["adtype"]]["impressions"] += $v["impressions"];
                }
                $impressions += $v["impressions"];
                if ($month_start == $month_end) {
                    $rate = $this->getNewMonthRate($appid, $v["platform"], $month_start);
                    $v["revenue"] = round($v["revenue"] * $rate, 2);
                }
                if (isset($r[$v["adtype"]]["revenue"])) {
                    $r[$v["adtype"]]["revenue"] += $v["revenue"];
                }
                $revenue += $v["revenue"];
            }
            if (!empty($r)) {
                foreach ($r as $key => &$vv) {
                    if ($key != "total") {
                        $vv["ecpm"] = $vv["impressions"] <= 0 ? 0 : number_format($vv["revenue"] * 1000 / $vv["impressions"], 2);
                    }
                }
            }
        }
        $ecpm = $impressions <= 0 ? 0 : number_format($revenue * 1000 / $impressions, 2);
        $r["total"] = ["impressions" => $impressions, "revenue" => round($revenue, 2), "ecpm" => $ecpm, "avgshow" => 0];
        return $r;
    }

    private function getNewMonthRate($appid, $channel, $month)
    {
        if ($channel == "34" || $channel == "35") {
            $channel = "cny";//人民币渠道
        }
        $row = Db::name("rate")->field('val')->where(["app_id" => $appid, "channel" => $channel, "month" => $month])->find();
        if (empty($row)) {
            return $channel == 5 ? "0.92" : "1";
        }
        if ($channel == "cny") {
            return round($row["val"] / 0.141085, 5);//按固定汇率计算 7.0879
        }
        return $row["val"];
    }


    public function download($app_id = "")
    {
        if (!$app_id) {
            return false;
        }
        $row = Db::name("app")->where("id={$app_id}")->find();
        if (!empty($row)) {
            $type = $row["platform"] == 'ios' ? "idfa" : "advertising_id";
            $xlsCell = array(
                array($type, 'IDFA'),
                array('country', '国家'),
            );
            $xlsData = Db::name("appsflyer")->field("{$type},country")->where("gb_id={$app_id} and {$type}!=''")->group("{$type},country")->select();
            $Index = new E(request());
            $name = $row["app_name"] . "种子用户下载" . date("Ymd");
            echo $Index->exportExcel($name, $xlsCell, $xlsData, $name, $name);
        }
        exit("下载失败!");
    }


    // LTV 模型 预估
    private function get_day_reten($res, $gb_id, $start, $end, $media_source, $country, $day)
    {
        $row = $this->get_v1_ltv_data($gb_id, $start, $end, $country == "all" ? [] : [$country], "0", 'custom', $media_source == "all" ? [] : [$media_source], false);
        $media_channel = $this->get_v1_channel($this->promate_media, $media_source == "all" ? [] : [$media_source]);
        $spend = $this->get_v1_total_spend($gb_id, $start, $end, $country == "all" ? [] : [$country], $media_channel);
        $spend["cpi"] = $spend["installs"] <= 0 ? "0.0" : round($spend["spend"] / $spend["installs"], 2);
        $ltv0 = $row["total_revenue"];
        $out = [];
        $total_revenue = $ltv0;
        if ($res["active_num"] > 0 && $res["installs"] > 0) {
            $start = $res["start"];
            $end = $res["end"];
        }
        for ($i = 1; $i <= 60; $i++) {
            $reten = $this->get_a_reten($gb_id, $start, $end, $media_source, $country, $i, isset($out[$i - 1]["reten"]) ? $out[$i - 1]["reten"] : 1);
            if ($reten <= 0) {
                $reten = $out[$i - 1]["reten"] / 100;
            }
            $revenue = $ltv0 * $reten;
            $total_revenue += $revenue;
            $avg_revenue = $spend["installs"] > 0 ? round($total_revenue / $spend["installs"], 3) : 0;
            $roi = $spend["spend"] > 0 ? round($total_revenue * 100 / $spend["spend"], 2) : 0;
            $estimate_cpi_110 = round((100 * $avg_revenue / 110), 2);
            $estimate_cpi_115 = round((100 * $avg_revenue / 115), 2);
            $estimate_cpi_120 = round((100 * $avg_revenue / 120), 2);
            $out[$i] = ["num" => $i, "estimate_cpi_110" => $estimate_cpi_110, "estimate_cpi_115" => $estimate_cpi_115, "estimate_cpi_120" => $estimate_cpi_120, "avg_revenue" => $avg_revenue, "revenue" => $revenue, "reten" => $reten * 100, "total_revenue" => round($total_revenue, 2), "roi" => $roi];
        }
        array_unshift($out, ["num" => 0, "revenue" => $ltv0, "reten" => 100, "avg_revenue" => $spend["installs"] > 0 ? round($ltv0 / $spend["installs"], 3) : 0, "total_revenue" => $ltv0, "roi" => $spend["spend"] > 0 ? round($ltv0 * 100 / $spend["spend"], 2) : 0]);
        return ["spend" => $spend, "list" => array_values($out)];
    }

    private function get_a_reten($gb_id, $start, $end, $media_source, $country, $day, $prev_reten)
    {
        $current_date = date("Y-m-d", strtotime("-2 day"));
        $day_num = getDateFromRange($end, $current_date);
        if ($day_num < 2) {
            return 0;
        }
        $real_reten = $this->get_range_date_reten($gb_id, $start, $end, $media_source, $country, $day);
        if ($real_reten <= 0) {
            $num = $day - 1;
            if ($num >= 1) {
                $r = Db::name("adjust_model")->where(["num" => $day])->find();
                $b = round((($prev_reten / 100) - ($r["reten_change"] / 100)), 4);
                if (($b * 100) > $r["reten"]) {
                    return round($r["reten"] / 100, 4);
                }
                return $b;
            }
        }
        return $real_reten;
    }

    private function get_range_date_reten($gb_id, $start, $end, $media_source, $country, $day)
    {

        $dates = getDateFromRange($start, $end);
        $num = 0;
        $reten_list = [];
        foreach ($dates as $d) {
            $reten_num = $this->get_one_day_reten($gb_id, $d, $media_source, $country, 0);
            $event_date = strtotime($d) + $day * 24 * 3600;
            $current_date = strtotime(date("Y-m-d", strtotime("-2 day")));
            if ($reten_num > 0 && $current_date >= $event_date) {
                $num = $num + 1;
                $current_reten_num = $this->get_one_day_reten($gb_id, $d, $media_source, $country, $day);
                $reten_list[] = round($current_reten_num / $reten_num, 4);
            }
        }
        return $num <= 0 ? 0 : round(array_sum($reten_list) / $num, 4);
    }

    private function get_one_day_reten($gb_id, $date, $media_source, $country, $day)
    {
        $where = "gb_id={$gb_id} and install_date='{$date}'";
        if ($media_source && $media_source != 'all') {
            $where .= " and media_source='{$media_source}'";
        }
        if ($country && $country != 'all') {
            $where .= " and country='{$country}'";
        }
        $event_date = date("Y-m-d", (strtotime($date) + $day * 24 * 3600));
        $where .= " and event_date='{$event_date}'";
        $row = Db::query("SELECT SUM(num) as num from  hellowd_adjust_ltv_users  WHERE {$where}");

        return isset($row[0]["num"]) && $row[0]["num"] ? $row[0]["num"] : 0;
    }
	
	
	public function get_ltv_roi($date="",$country="",$appid=""){
		return $this->get_ltv_roi1($date,$country,$appid);
		$where ="event_date='{$date}' and install_date='{$date}' and gb_id={$appid}";
		$cc=[];
		if($country && $country!="all")
		{
			$where.=" and country='{$country}'";
			$cc = [$country];
		}
		$ad_data = Db::name("adjust_ltv_pdt")->field("SUM(revenue) as revenue")->where($where)->find();
		$ad_revenue = isset($ad_data["revenue"])?$ad_data["revenue"]:"0.00";
		$purchase ="0.00";
		if (in_array($appid,$this->allow_app_list)) {
			$purchase = $this->getpurchasev1($appid, $date, $date, $date, "","",$cc,[]);			
        }
		return ["ad_revenue"=>$ad_revenue,"purchase"=>$purchase];
	}
	
	public function get_ltv_roi1($date="",$country="",$appid=""){
		$where ="date='{$date}' and app_id={$appid}";
		$cc=[];
		if($country && $country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$ad_data = Db::name("main_roi")->field("SUM(ad_revenue) as ad_revenue,sum(purchase) as purchase")->where($where)->find();
		$ad_revenue = isset($ad_data["ad_revenue"])?$ad_data["ad_revenue"]:"0.00";
		$purchase = isset($ad_data["purchase"])?$ad_data["purchase"]:"0.00";
		
		return ["ad_revenue"=>$ad_revenue,"purchase"=>$purchase];
	}
	
	private function getactive_users($appid, $start = "", $end = "", $country = "all")
    {       
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $active_sql = "select ceil(avg(val)) as val from hellowd_active_users where {$where}";
        $d = Db::query($active_sql);

        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] : 0;
    }
	
	//获取用户数
	private function get_ltv_users($appid, $event_date = "", $install_date = "", $country = "all")
	{
		$where ="event_date='{$event_date}' and install_date='{$install_date}' and gb_id={$appid}";
		if($country && $country!="all")
		{
			$where.=" and country='{$country}'";
		}
		return Db::name("adjust_ltv_users")->where($where)->sum('num');
	}
	
	public function revenue_rate(){
		$out = [];
        $params = input("post.");
        if ($params["day_num"] != 'custom' && $params["day"] != 'n') {
            $params["day_num"] = $params["day_num"] + 1 + $params["day"];
            $start = date("Y-m-d", strtotime("-{$params["day_num"]} day"));
            $last_day = 2 + $params["day"];
            $end = date("Y-m-d", strtotime("-{$last_day} day"));
        }else {
            list($start, $end) = $params["date"];
        }
		 $date = getDateFromRange($start, $end);
		 $out =[];
		 foreach($date as $v)
		 {
			 $res = $this->get_day_revenue_rate($params['app_id'],$v,$params['country']);
			 $out[] = $res;
		 }
		 $data["rate0"] = array_column($out,"rate0");
		 $data["rate30"] = array_column($out,"rate30");
		 $data["rate60"] = array_column($out,"rate60");
		 $data["rate90"] = array_column($out,"rate90");
		 $data["ratem"] = array_column($out,"ratem");
		 echo json_encode(["data"=>$data,"xdate"=>$date,"date"=>[$start,$end]]);
	}
	
	private function get_day_revenue_rate($appid,$date,$country){
		
		$where ="event_date='{$date}' and gb_id={$appid}";
		$cc=[];
		if($country && $country!="all")
		{
			$where.=" and country='{$country}'";
			$cc=[$country];
		}
		$sql ="SELECT install_date,SUM(revenue) as revenue,round(sum(int_show+rew_show),2) as total_show,round(SUM(int_show),2) as int_show,round(SUM(rew_show),2) as rew_show  from hellowd_adjust_ltv_pdt WHERE {$where} GROUP BY install_date ORDER BY install_date desc";
		$data = Db::query($sql);
		$total_revenue ="0.00";
		$res =[];
		$revenue0="0.00";
		$revenue30="0.00";
		$revenue60="0.00";
		$revenue90="0.00";
		$revenuem="0.00";
		if(!empty($data))
		{
			foreach($data as $vv)
			{
			   $purchase="0.00";
				if (in_array($appid,$this->allow_app_list)) {
					$purchase = $this->getpurchasev1($appid,$vv["install_date"],$date,$date,"","",$cc,[]);
				}
				$vv["total"] = round($vv["revenue"]+$purchase,2);
				$total_revenue+=$vv["total"];
				 if($vv["install_date"]==$date)
				 {
					$revenue0+=$vv["total"];
				 }elseif( strtotime($vv["install_date"])>=(strtotime($date)-30* 24 * 3600) )
				 {
					$revenue30+=$vv["total"];
				 }elseif( strtotime($vv["install_date"])>=(strtotime($date)-60* 24 * 3600) && strtotime($vv["install_date"])<(strtotime($date)-30* 24 * 3600) )
				 {
					$revenue60+=$vv["total"];
				 }
				 elseif( strtotime($vv["install_date"])>=(strtotime($date)-90* 24 * 3600) && strtotime($vv["install_date"])<(strtotime($date)-60* 24 * 3600) )
				 {
					$revenue90+=$vv["total"];
				 }else{
					 $revenuem+=$vv["total"];
				 }
			}
		}
		$res["rate0"] = $total_revenue>0?round($revenue0*100/$total_revenue,2):0;
		$res["rate30"] = $total_revenue>0?round($revenue30*100/$total_revenue,2):0; 
		$res["rate60"] = $total_revenue>0?round($revenue60*100/$total_revenue,2):0; 
		$res["rate90"] = $total_revenue>0?round($revenue90*100/$total_revenue,2):0; 
		$res["ratem"] = $total_revenue>0?round($revenuem*100/$total_revenue,2):0;
		return $res;
		
	}
	
	//新增计算模型
	public function model_json(){
		$params = input("post.");
		$campaign_name = $params["name"];
		$campaign_id = $params["campaign_id"];
		$roi_day = $params["roi_day"];
		$roi_num = $params["roi_num"];
		$date = $params["roi_date"];
		if(empty($date))
		{
			$start = date("Y-m-d", strtotime("-2 day"));
			$end = date("Y-m-d", strtotime("-2 day"));
		}else{
			list($start,$end) = $date;
		}
		$tag="";
        if(preg_match('/AEO/i',$campaign_name))
		{
			$tag = "AEO";
		}elseif( preg_match('/GP/i',$campaign_name) )
		{
			$tag = "GP";
		}elseif( preg_match('/YD/i',$campaign_name) )
		{
			$tag = "YD";
		}
		$appid = getcache("select_app");
		$current_date = date("Y-m-d", strtotime("-2 day"));
		
		if($tag!="")
		{			
			$exist_data = Db::query("SELECT c.day,avg(revenue) as revenue  from (
SELECT day,install_date,SUM(revenue) as revenue from hellowd_campaign_ltv_model WHERE gb_id={$appid} and day<={$roi_day} and install_date<='{$end}' 
and install_date>='{$start}' and campaign_id='{$campaign_id}' GROUP BY DAY,install_date ) c GROUP BY c.day");
            $country = Db::name("campaign_ltv_model")->where("campaign_id='{$campaign_id}' and spend>10")->value('country');          
			if(!empty($exist_data))
			{
			    $roi_end_time = (strtotime($current_date) - $roi_day * 24 * 3600);
				$roi_end_date = date("Y-m-d",$roi_end_time);
				if($roi_end_time<strtotime("2020-12-15"))
				{
					echo json_encode(["code"=>700]);exit;
				}
				$roi_start_time = $roi_end_time-30*24*3600;
				$roi_start_date = date("Y-m-d",$roi_start_time);
				if($roi_start_time<strtotime("2020-12-15"))
				{
					$roi_start_date ="2020-12-15";
				}
				$history_data = $this->get_new_roi_data($appid,$roi_start_date,$roi_end_date,$country,$tag,$roi_day,$roi_num);				
				if(empty($history_data["res"]))
				{
					echo json_encode(["code"=>700]);exit;
				}
				$result =[];
				$yb_spend = "0.00";
				foreach( $history_data["res"] as $h )
				{
					$i = 0;
					for($i=0;$i<=$roi_day;$i++)
					{
						$ad_row = $this->get_roi_day_data($h["campaign_id"],$h["install_date"],"ad",$i);
						$ad_revenue ="0.00";
						if(!empty($ad_row))
						{
							$ad_revenue = $ad_row["revenue"];
						}
						
						$purchase_row = $this->get_roi_day_data($h["campaign_id"],$h["install_date"],"purchase",$i);
						$purchase_revenue ="0.00";
						if(!empty($purchase_row))
						{
							$purchase_revenue = $purchase_row["revenue"];
						}
						if(isset($result[$i]["ad_revenue"]))
						{
							$result[$i]["ad_revenue"]+=$ad_revenue;
						}else{
							$result[$i]["ad_revenue"] = $ad_revenue;
						}
						if(isset($result[$i]["purchase_revenue"]))
						{
							$result[$i]["purchase_revenue"]+=$purchase_revenue;
						}else{
							$result[$i]["purchase_revenue"] = $purchase_revenue;
						}
						$total_revenue = $ad_revenue+$purchase_revenue;
						if(isset($result[$i]["total_revenue"]))
						{
							$result[$i]["total_revenue"]+=$total_revenue;
						}else{
							$result[$i]["total_revenue"] = $total_revenue;
						}
						
					}
					$yb_spend+=$h["spend"];
				}
				
				$yb_total_revenue_list = array_column($result,"total_revenue");
				$yb_ad_revenue_list = array_column($result,"ad_revenue");
				$yb_purchase_revenue_list = array_column($result,"purchase_revenue");
				$revenue_roi =[];
				$i=0;
				while($i<=$roi_day)
				{
					if(isset($exist_data[$i]))
					{
						$revenue_roi[$i] =$exist_data[$i]["revenue"];
					}else{
						$last_revenue = $revenue_roi[$i-1];
						$rate = $yb_total_revenue_list[$i-1]>0?$yb_total_revenue_list[$i]/$yb_total_revenue_list[$i-1]:0;
						$revenue_roi[$i] =round($last_revenue*$rate,2);
					}
					++$i;
				}
				$trs = Db::query("SELECT avg(c.spend) as spend,avg(c.cpi) as cpi from (
SELECT install_date,avg(spend) as spend,avg(cpi) as cpi from hellowd_campaign_ltv_model WHERE gb_id={$appid} and  day<={$roi_day} and install_date<='{$end}' 
and install_date>='{$start}' and campaign_id='{$campaign_id}' GROUP BY install_date ) as c");
				$spend = isset($trs[0]["spend"])?$trs[0]["spend"]:0;
				$cpi = isset($trs[0]["cpi"])?round($trs[0]["cpi"],2):0;
				$revenue = array_sum($revenue_roi);
				$roi = $spend>0?round($revenue*100/$spend,2):'0.00';
				$pltv= round($roi*$cpi/100,2);
				$left_color ="#fff";
				$right_color ="#fff";
				$width="";
				$awidth="40";
				$str ="";
				if($roi_num<$roi)
				{
					$left_color = "#ef4f4f";
					$budget="增加";
					$width = 40+($roi-$roi_num);
					if($width>95)
					{
						$width =95;
					}
				}else{
					$right_color = "#ef4f4f";
					$budget="降低";
					$width = 100-($roi_num-$roi);
					if($width<5)
					{
						$width =5;
					}
					$awidth=100-$width;
				}
				$yb_rev= array_sum( $yb_total_revenue_list);
				if($roi_day<=3)
				{
					$revenue0 = array_sum( array_splice($yb_total_revenue_list,0,1));
					$roi0 = $yb_spend>0?round($revenue0*100/$yb_spend,2):'0.00';
					$str.="ROI0 : ".$roi0."%(样本数{$history_data["num"]}) ,";
				}elseif($roi_day<=7 && $roi_day>3)
				{
					$revenue3 = array_sum( array_splice($yb_total_revenue_list,0,3));
					$roi3 = $yb_spend>0?round($revenue3*100/$yb_spend,2):'0.00';
					$str.="ROI3 : ".$roi3."%(样本数{$history_data["num"]}) ,";
				}elseif($roi_day<=14 && $roi_day>7)
				{
					$revenue7 = array_sum( array_splice($yb_total_revenue_list,0,7));
					$roi7 = $yb_spend>0?round($revenue7*100/$yb_spend,2):'0.00';
					$str.="ROI7 : ".$roi7."%(样本数{$history_data["num"]}) ,";
				}elseif($roi_day<=30 && $roi_day>14)
				{
					$revenue14= array_sum( array_splice($yb_total_revenue_list,0,14));
					$roi14 = $yb_spend>0?round($revenue14*100/$yb_spend,2):'0.00';
					$str.="ROI14 : ".$roi14."%(样本数{$history_data["num"]}) ,";
				}				
				$yb_roi = $yb_spend>0?round($yb_rev*100/$yb_spend,2):'0.00';
				$str.="ROI{$roi_day} : ".$yb_roi."%(样本数{$history_data["num"]})";
				
				$iap_new0 = isset($yb_purchase_revenue_list[0])?$yb_purchase_revenue_list[0]:0;
				$iap_data1 =[];
				
				for($j=0;$j<=$roi_day;$j++)
				{
					$val =0;
					if(isset($yb_purchase_revenue_list[$j]))
					{
						$val = $yb_purchase_revenue_list[$j];
					}
					$iap_data1[$j] = $iap_new0>0? round($val/$iap_new0,2):0;
				}
				$ad_new0 = isset($yb_ad_revenue_list[0])?$yb_ad_revenue_list[0]:0;
				$new_array = array_map(function($v)use($ad_new0){
					return round($v/$ad_new0,2);
				},$yb_ad_revenue_list);
				$iap_revenue = array_sum($yb_purchase_revenue_list);
				$iaa_revenue = round(array_sum($yb_ad_revenue_list),2);
				$ap_rate =($iap_revenue>0?"1 : ".round($iaa_revenue/$iap_revenue,2):"0 : ".$iaa_revenue);
				echo json_encode(["code"=>200,"awidth"=>$awidth,"pltv"=>$pltv,"width"=>$width,"str_roi"=>$str,"ap_rate"=>$ap_rate,"country"=>$country,"tag"=>$tag,"p_rate_list"=>implode(" : ",$iap_data1),"cpi"=>$cpi,"rate_list"=>implode(" : ",$new_array),"budget"=>$budget,"left_color"=>$left_color,"right_color"=>$right_color,"roi"=>$roi,"num"=>$history_data["num"]]);exit;
			}else{
				echo json_encode(["code"=>500]);exit;
			}
		}	
		echo json_encode(["code"=>600]);exit;
	}
	
	private function get_roi_day_data($campaign_id,$install_date,$type,$day){
		
		return Db::name("campaign_ltv_model")->field('sum(revenue) as revenue')->where("campaign_id='{$campaign_id}' and type='{$type}' and install_date='{$install_date}' and day={$day}")->find();
	}
	
		
	private function get_model_roi_data($appid,$start,$end,$country,$type,$tag,$day,$roi){
		
	  $exchange = 2;
	  $child_sql="SELECT c.campaign_id from ( 
 SELECT campaign_id,install_date,MAX(spend) as spend,SUM(revenue) as revenue,ROUND(SUM(revenue)*100/max(spend),2) as roi,MAX(cpi) as cpi
from  hellowd_campaign_ltv_model WHERE gb_id={$appid} and tag='{$tag}' and day<={$day} and country='{$country}' and install_date<='{$end}'

and install_date>='{$start}' GROUP BY campaign_id,install_date ) c 

WHERE c.roi>={$roi}-{$exchange} and c.roi<={$roi}+{$exchange}  and c.spend>20";
       $sql ="SELECT day,SUM(revenue) as revenue  from  hellowd_campaign_ltv_model WHERE gb_id={$appid} and tag='{$tag}' and day<={$day} and country='{$country}' and install_date<='{$end}'
and install_date>='{$start}' and type='{$type}' and campaign_id IN({$child_sql})GROUP BY day";
       //print_r($sql);exit;
       $res = Db::query($sql);
	   $num = count(Db::query($child_sql));
	   return ["res"=>$res,"num"=>$num];
	}
	
	private function get_new_roi_data($appid,$start,$end,$country,$tag,$day,$roi){
		$exchange = 2;
		$sql ="SELECT c.campaign_id,c.install_date,c.revenue,c.roi,c.spend
from ( SELECT campaign_id,install_date,MAX(spend) as spend,SUM(revenue) as revenue,ROUND(SUM(revenue)*100/max(spend),2) as roi,MAX(cpi) as cpi 
from hellowd_campaign_ltv_model WHERE gb_id={$appid} and tag='{$tag}' and day<={$day} and country='{$country}' and install_date<='{$end}' and install_date>='{$start}' 
GROUP BY campaign_id,install_date ) c WHERE c.roi>={$roi}-2 and c.roi<={$roi}+2 and c.spend>20";
      $res = Db::query($sql);
	  return ["res"=>$res,"num"=>sizeof($res)];
	}
	
	private function get_history_roi_data($appid,$start,$end,$country,$tag,$day){

		$where="gb_id={$appid} and tag='{$tag}' and day<={$day} and install_date<='{$end}' and install_date>='{$start}' ";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$sql ="SELECT c.campaign_id,bb.num,c.install_date,c.revenue,c.roi,c.spend
from ( SELECT campaign_id,install_date,MAX(spend) as spend,SUM(revenue) as revenue,ROUND(SUM(revenue)*100/max(spend),2) as roi,MAX(cpi) as cpi 
from hellowd_campaign_ltv_model WHERE {$where}
GROUP BY campaign_id,install_date ) c 

JOIN (
  SELECT aa.campaign_id,count(*) as num  from (
SELECT campaign_id,install_date
from hellowd_campaign_ltv_model WHERE {$where} 
GROUP BY campaign_id,install_date ) aa GROUP BY aa.campaign_id
) bb on c.campaign_id = bb.campaign_id

WHERE  c.spend>10 and bb.num>=3";
      //print_r($sql);exit;
      $res = Db::query($sql);
	  return ["res"=>$res,"num"=>sizeof($res)];
	}
	
	private function get_history_lefttime_roi_data($appid,$start,$end,$country,$tag,$ssroi){

		$where="gb_id={$appid} and tag='{$tag}' and install_date<='{$end}' and install_date>='{$start}' ";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		$sql ="SELECT c.campaign_id,bb.num,c.install_date,c.revenue,c.roi,c.spend
from ( SELECT campaign_id,install_date,MAX(spend) as spend,SUM(revenue) as revenue,ROUND(SUM(revenue)*100/max(spend),2) as roi,MAX(cpi) as cpi 
from hellowd_campaign_ltv_model WHERE {$where}
GROUP BY campaign_id,install_date ) c 

JOIN (
  SELECT aa.campaign_id,count(*) as num  from (
SELECT campaign_id,install_date
from hellowd_campaign_ltv_model WHERE {$where} 
GROUP BY campaign_id,install_date ) aa GROUP BY aa.campaign_id
) bb on c.campaign_id = bb.campaign_id

WHERE  c.spend>10 and bb.num>=2 and c.roi>={$ssroi}-5 and c.roi<={$ssroi}+5";
      //print_r($sql);exit;
      $res = Db::query($sql);
	  $result =[];
	  $dayData =[];
	  $dayList =[];
	  if(!empty($res))
	  {
		  foreach($res as $v)
		  {
			  $row = $this->find_last_reten($v["campaign_id"],$v["install_date"]);
			  if($row["code"])
			  {
				  $v["day"] = $row["day"];
				  if(isset($dayData[$v["day"]]))
				  {
					  $dayData[$v["day"]]+=1;
				  }else{
					  $dayData[$v["day"]] =1;
				  }
				  $result[] =$v;
			  }
		  }
	  }
	  if(!empty($dayData))
	  {
		  foreach($dayData as $k=>$d)
		  {
		  $dayList[] =[$k,$d];
		  }
	  }
	  return ["res"=>$result,"num"=>sizeof($result),"dayData"=>$dayList];
	}
	
	private function find_last_reten($campaign_id,$install_date){
		$sql="SELECT `day`,SUM(revenue) as revenue  from  hellowd_campaign_ltv_model WHERE campaign_id='{$campaign_id}' and install_date='{$install_date}' GROUP BY `day`";
		 $res = Db::query($sql);
		 if(!empty($res))
		 {
			 $first = $res[0]["revenue"];
			 $arr = end($res);
			 $end = $arr["revenue"];
			 $rate = $first>0?round($end/$first,4):0;
			 if($rate<=0.05)
			 {
				 return ["code"=>true,"day"=>$arr['day']];
			 }
		 }
		 return ["code"=>false,"day"=>0];
	}
	
	//新增历史数据LTV模型展示
	public function history_json(){
		$params = input("post.");
		$row = $params;
		$appid = getcache("select_app");
		$roi = $params["roi"];
		$sroi = str_replace("%","",$params["sroi"]);
		$dayData =[];
		if($params["day"]=="lefttime")
		{
			$start_day = 2+$params["date"];
			$end = date("Y-m-d", strtotime("-2 day"));
			$start = date("Y-m-d", strtotime("-{$start_day} day"));
			$roi = $sroi;
			$params["day"] =30;
			$data = $this->get_history_lefttime_roi_data($appid,$start,$end,$params["country"],$params['type'],$sroi);
			$dayData = $data["dayData"];
		}else{
			$end_day = $params['day']+2;			
			$start_day = $end_day+$params["date"];
			$end = date("Y-m-d", strtotime("-{$end_day} day"));
			$start = date("Y-m-d", strtotime("-{$start_day} day"));
			$data = $this->get_history_roi_data($appid,$start,$end,$params["country"],$params['type'],$params['day']);
		}
        $roi_day = $params['day']; 		
		$history_data = $data["res"];
		$rangeList = $this->get_range_roi($roi);
		if($rangeList)
		{
			foreach($rangeList as &$vv)
			{
				$vv["roiList"] =[];
				if(!empty($history_data))
				{
					foreach($history_data as $v)
					{
						if( $v["roi"]>=$vv["min"] && $v["roi"]<=$vv["max"] )
						{
							$vv["roiList"][] = $v["roi"]; 
						}
					}
				}
				$vv["num"] = count($vv["roiList"]);
			}
		}
		$res = $this->history_set($history_data,$params['day']);
		$result = $res["result"];
		$yb_spend = $res["yb_spend"];
		$yb_total_revenue_list = array_column($result,"total_revenue");
		$yb_total_revenue_list1 = $yb_total_revenue_list;
		$yb_ad_revenue_list = array_column($result,"ad_revenue");
		$yb_purchase_revenue_list = array_column($result,"purchase_revenue");
		$yb_rev= array_sum( $yb_total_revenue_list);
		$str="";
		if($roi_day<=3 || $row["day"]=="lefttime")
		{
			$revenue0 = array_sum( array_splice($yb_total_revenue_list,0,1));
			$roi0 = $yb_spend>0?round($revenue0*100/$yb_spend,2):'0.00';
			$str.="ROI0 : ".$roi0."%(样本数{$data["num"]}) ,";
		}
		if( ($roi_day<=7 && $roi_day>3) || $row["day"]=="lefttime" )
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue3 = array_sum( array_splice($yb_total_revenue_list,0,3));
			$roi3 = $yb_spend>0?round($revenue3*100/$yb_spend,2):'0.00';
			$str.="ROI3 : ".$roi3."%(样本数{$data["num"]}) ,";
		}
		if(($roi_day<=14 && $roi_day>7) || $row["day"]=="lefttime")
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue7 = array_sum( array_splice($yb_total_revenue_list,0,7));
			$roi7 = $yb_spend>0?round($revenue7*100/$yb_spend,2):'0.00';
			$str.="ROI7 : ".$roi7."%(样本数{$data["num"]}) ,";
		}
		if(($roi_day<=30 && $roi_day>14) || $row["day"]=="lefttime")
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue14= array_sum( array_splice($yb_total_revenue_list,0,14));
			$roi14 = $yb_spend>0?round($revenue14*100/$yb_spend,2):'0.00';
			$str.="ROI14 : ".$roi14."%(样本数{$data["num"]}) ,";
		}				
		$yb_roi = $yb_spend>0?round($yb_rev*100/$yb_spend,2):'0.00';
		$str.="ROI{$roi_day} : ".$yb_roi."%(样本数{$data["num"]})";
		
		$iap_new0 = isset($yb_purchase_revenue_list[0])?$yb_purchase_revenue_list[0]:0;
		$iap_data1 =[];
		
		for($j=0;$j<=$roi_day;$j++)
		{
			$val =0;
			if(isset($yb_purchase_revenue_list[$j]))
			{
				$val = $yb_purchase_revenue_list[$j];
			}
			$iap_data1[$j] = $iap_new0>0? round($val/$iap_new0,2):0;
		}
		$ad_new0 = isset($yb_ad_revenue_list[0])?$yb_ad_revenue_list[0]:0;
		$new_array = array_map(function($v)use($ad_new0){
			return round($v/$ad_new0,2);
		},$yb_ad_revenue_list);
		$iap_revenue = array_sum($yb_purchase_revenue_list);
		$iaa_revenue = round(array_sum($yb_ad_revenue_list),2);
		$ap_rate =($iap_revenue>0?"1 : ".round($iaa_revenue/$iap_revenue,2):"0 : ".$iaa_revenue);
		$xdata = array_column($rangeList,"range");
		$ydata = array_column($rangeList,"num");
		echo json_encode(["xdata"=>$xdata,"dayData"=>$dayData,"proi"=>$str,"iap"=>implode(" : ",$iap_data1),"iaa"=>implode(" : ",$new_array),"prate"=>$ap_rate,"ydata"=>$ydata,"num"=>$data["num"]]);exit;
	}
	
	
	//新增历史数据ROI
	public function history_json_v1(){
		$params = input("post.");
		$row = $params;
		$appid = getcache("select_app");
		$roi = $params["roi"];
		$roi_day = $params['day'];
		$end_day = $params['day']+2;	
		$start_day = $end_day+$params["date"];
		$end = date("Y-m-d", strtotime("-{$end_day} day"));
		$start = date("Y-m-d", strtotime("-{$start_day} day"));
		if($params['group']=='day')
		{
			$data = $this->get_history_roi_data_v1($appid,$start,$end,$params["country"],$params['day']);
		}else{
			$data = $this->get_history_roi_data_campaign($appid,$start,$end,$params["country"],$params['day']);
		}		
		$history_data = $data["out"];
		$rangeList = $this->get_range_roi($roi);
		if($rangeList)
		{
			foreach($rangeList as &$vv)
			{
				$vv["roiList"] =[];
				if(!empty($history_data))
				{
					foreach($history_data as $v)
					{
						if( $v["roi"]>=$vv["min"] && $v["roi"]<=$vv["max"] )
						{
							$vv["roiList"][] = $v["roi"]; 
						}
					}
				}
				$vv["num"] = count($vv["roiList"]);
			}
		}
		$result = $data["result"];
		$yb_spend = $data["spend"];
		$yb_total_revenue_list = array_column($result,"total_revenue");
		$yb_total_revenue_list1 = $yb_total_revenue_list;
		$yb_ad_revenue_list = array_column($result,"ad_revenue");
		$yb_purchase_revenue_list = array_column($result,"purchase_revenue");
		$yb_rev= array_sum( $yb_total_revenue_list);
		$str="";
		if($roi_day<=3)
		{
			$revenue0 = array_sum( array_splice($yb_total_revenue_list,0,1));
			$roi0 = $yb_spend>0?round($revenue0*100/$yb_spend,2):'0.00';
			$str.="ROI0 : ".$roi0."%(样本数{$data["num"]}) ,";
		}
		if( ($roi_day<=7 && $roi_day>3) )
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue3 = array_sum( array_splice($yb_total_revenue_list,0,3));
			$roi3 = $yb_spend>0?round($revenue3*100/$yb_spend,2):'0.00';
			$str.="ROI3 : ".$roi3."%(样本数{$data["num"]}) ,";
		}
		if(($roi_day<=14 && $roi_day>7))
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue7 = array_sum( array_splice($yb_total_revenue_list,0,7));
			$roi7 = $yb_spend>0?round($revenue7*100/$yb_spend,2):'0.00';
			$str.="ROI7 : ".$roi7."%(样本数{$data["num"]}) ,";
		}
		if(($roi_day<=30 && $roi_day>14))
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue14= array_sum( array_splice($yb_total_revenue_list,0,14));
			$roi14 = $yb_spend>0?round($revenue14*100/$yb_spend,2):'0.00';
			$str.="ROI14 : ".$roi14."%(样本数{$data["num"]}) ,";
		}				
		$yb_roi = $yb_spend>0?round($yb_rev*100/$yb_spend,2):'0.00';
		$str.="ROI{$roi_day} : ".$yb_roi."%(样本数{$data["num"]})";
		$iap_new0 = isset($yb_purchase_revenue_list[0])?$yb_purchase_revenue_list[0]:0;
		$iap_data1 =[];
		
		for($j=0;$j<=$roi_day;$j++)
		{
			$val =0;
			if(isset($yb_purchase_revenue_list[$j]))
			{
				$val = $yb_purchase_revenue_list[$j];
			}
			$iap_data1[$j] = $iap_new0>0? round($val/$iap_new0,2):0;
		}
		$ad_new0 = isset($yb_ad_revenue_list[0])?$yb_ad_revenue_list[0]:0;
		$new_array = array_map(function($v)use($ad_new0){
			return round($v/$ad_new0,2);
		},$yb_ad_revenue_list);
		$iap_revenue = array_sum($yb_purchase_revenue_list);
		$iaa_revenue = round(array_sum($yb_ad_revenue_list),2);
		$ap_rate =($iap_revenue>0?"1 : ".round($iaa_revenue/$iap_revenue,2):"0 : ".$iaa_revenue);
		$xdata = array_column($rangeList,"range");
		$ydata = array_column($rangeList,"num");
		echo json_encode(["xdata"=>$xdata,"dayData"=>[],"proi"=>$str,"iap"=>implode(" : ",$iap_data1),"iaa"=>implode(" : ",$new_array),"prate"=>$ap_rate,"ydata"=>$ydata,"num"=>$data["num"]]);exit;
	}
	
	public function history_json_campaign(){
		$params = input("post.");
		$row = $params;
		$appid = getcache("select_app");
		$sroi = str_replace("%","",$params["sroi"]);
		$roi = $sroi;
		$end_day = $params['day']+2;			
		$start_day = $end_day+$params["date"];
		$end = date("Y-m-d", strtotime("-{$end_day} day"));
		$start = date("Y-m-d", strtotime("-{$start_day} day"));
		$where="gb_id={$appid} and install_date<='{$end}' and install_date>='{$start}' ";
		$country = $params["country"];
		$wherecountry =[];
		if($country!="all")
		{
			$where.=" and country='{$country}'";
			$wherecountry =[$country];
		}
		$sql ="SELECT campaign_name,campaign_id,media_source,datediff(event_date,install_date) as `day`,SUM(revenue) as ad_revenue  from  hellowd_adjust_campaign_ltv_pdt WHERE  {$where} and campaign_id!='' GROUP BY campaign_id HAVING day<={$params['day']}";
		$res = Db::query($sql);
		$out =[];
		if(!empty($res))
		{
			foreach($res as $v)
			{
				 $spend =$this->get_campaign_sepend($appid, $v["campaign_id"], $start, $end, $country,"all");				
				$v["roi"] = $spend["spend"]>0?round($v["ad_revenue"]*100/$spend["spend"]):0;
				if($v["roi"]>=$sroi)
				{ 
					$v["roi"].="%";
					$out[] =$v; 
				}
			}
		}
		echo json_encode($out);exit;
	}
	
	public function history_json_revise(){
		
		$params = input("post.");
		$row = $params;
		$appid = getcache("select_app");
		$sroi = str_replace("%","",$params["sroi"]);
		$roi = $sroi;
		if($params["day"]=="lefttime")
		{
			$start_day = 2+$params["date"];
			$end = date("Y-m-d", strtotime("-2 day"));
			$start = date("Y-m-d", strtotime("-{$start_day} day"));			
			$params["day"] =30;
		}else{
			$end_day = $params['day']+2;			
			$start_day = $end_day+$params["date"];
			$end = date("Y-m-d", strtotime("-{$end_day} day"));
			$start = date("Y-m-d", strtotime("-{$start_day} day"));
		}
		$data= $this->get_history_roi_data_v1($appid,$start,$end,$params["country"],$params["day"]);
        $roi_day = $params['day'];
		$result = $data["result"];
		$yb_spend = $data["spend"];
		$yb_total_revenue_list = array_column($result,"total_revenue");
		$yb_total_revenue_list1 = $yb_total_revenue_list;
		$yb_ad_revenue_list = array_column($result,"ad_revenue");
		$yb_purchase_revenue_list = array_column($result,"purchase_revenue");
		$yb_rev= array_sum( $yb_total_revenue_list);
		$str="";		
		$revenue0 = array_sum( array_splice($yb_total_revenue_list,0,1));
		$roi0 = $yb_spend>0?round($revenue0*100/$yb_spend,2):'0.00';
		$str.="ROI0 : ".$roi0."%,";
	
	    if(($roi_day>3))
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue3 = array_sum( array_splice($yb_total_revenue_list,0,3));
			$roi3 = $yb_spend>0?round($revenue3*100/$yb_spend,2):'0.00';
			$str.="ROI3 : ".$roi3."%,";
		}
	
	    if(($roi_day>7))
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue7 = array_sum( array_splice($yb_total_revenue_list,0,7));
			$roi7 = $yb_spend>0?round($revenue7*100/$yb_spend,2):'0.00';
			$str.="ROI7 : ".$roi7."%,";
		}
		
		if(($roi_day>14))
		{
			$yb_total_revenue_list = $yb_total_revenue_list1;
			$revenue14= array_sum( array_splice($yb_total_revenue_list,0,14));
			$roi14 = $yb_spend>0?round($revenue14*100/$yb_spend,2):'0.00';
			$str.="ROI14 : ".$roi14."%,";
		}
        if($roi_day>30)
		{
			for($r=30;$r<$roi_day;$r++)
			{
				if( $r%15==0 )
				{
					$yb_total_revenue_list = $yb_total_revenue_list1;
					$revenue_r= array_sum( array_splice($yb_total_revenue_list,0,$r));
					$roi_r = $yb_spend>0?round($revenue_r*100/$yb_spend,2):'0.00';
					$str.="ROI{$r} : ".$roi_r."%,";
				}		
			}
		}			
		$yb_roi = $yb_spend>0?round($yb_rev*100/$yb_spend,2):'0.00';
		$str.="ROI{$roi_day} : ".$yb_roi."%";
		$iap_new0 = isset($yb_purchase_revenue_list[0])?$yb_purchase_revenue_list[0]:0;
		$ad_new0 = isset($yb_ad_revenue_list[0])?$yb_ad_revenue_list[0]:0;
		$iap_data1 =[];
		$new_array =[];
		for($j=0;$j<=$roi_day;$j++)
		{
			if((($j>=15) && ($j%15==0)) || ($j<15) )
			{
				$val=0;
				if(isset($yb_purchase_revenue_list[$j]))
				{
					$val = $yb_purchase_revenue_list[$j];
				}
				$iap_data1[$j] = $iap_new0>0? round($val/$iap_new0,2):0;
				
				$new_array[$j] = $ad_new0>0? round($yb_ad_revenue_list[$j]/$ad_new0,2):0;
			}			
		}		
		$iap_revenue = array_sum($yb_purchase_revenue_list);
		$iaa_revenue = round(array_sum($yb_ad_revenue_list),2);
		$ap_rate =($iap_revenue>0?"1 : ".round($iaa_revenue/$iap_revenue,2):"0 : ".$iaa_revenue);
		$checkroi = $yb_roi>0?round(($sroi/$yb_roi)*$roi0,2):0;
		$object_roi = Db::name("object_roi")->where(["app_id"=>$appid,"country"=>$params["country"] ])->value('roi0');
		echo json_encode(["proi"=>$str,"object_roi"=>$object_roi,"start"=>$start,"end"=>$end,"checkroi"=>$checkroi,"iap"=>implode(" : ",$iap_data1),"iaa"=>implode(" : ",$new_array),"prate"=>$ap_rate,"num"=>$data["num"]]);exit;
	}
	
	public function get_history_roi_data_campaign($appid,$start,$end,$country,$day){
		$list =[];
		$dates = getDateFromRange($start, $end);
		for($i=0;$i<=$day;$i++)
		{
			foreach($dates as $v)
			{
				$list[] = ["date"=>$v,"day"=>$i];
			}
		}
		$where="gb_id={$appid} and install_date<='{$end}' and install_date>='{$start}' ";
		$wherecountry =[];
		if($country!="all")
		{
			$where.=" and country='{$country}'";
			$wherecountry =[$country];
		}
		$sql ="SELECT campaign_id,install_date,event_date,datediff(event_date,install_date) as `day`,SUM(revenue) as ad_revenue  from  hellowd_adjust_campaign_ltv_pdt WHERE  {$where} GROUP BY install_date,event_date,campaign_id HAVING day<={$day}";
		$res = Db::query($sql);
		
		$res = $this->asn($res,true);
		if(!empty($list))
		{
			foreach($list as &$vv)
			{
				$vv["ad_revenue"] ="0.00";
				if(isset($res[$vv["date"]][0][$vv["day"]]))
				{
					$vv["ad_revenue"] = $res[$vv["date"]][0][$vv["day"]];
					
				}
				$vv["campaign_id"] = $res[$vv["date"]][1];
				$event_date =  date("Y-m-d", (strtotime($vv["date"]) + $vv["day"] * 24 * 3600));
				$purchase="0";
				if (in_array($appid,$this->allow_app_list)) {
					//$purchase = $this->getpurchasev1($appid, $vv["date"], $event_date,$event_date,"","",$wherecountry,[]);
				}
				//$purchase =$this->getpurchasev1($appid, $vv["date"], $event_date,$event_date,"","",$wherecountry,[]);
				$vv["purchase"] = $purchase;
			}
		}
		$result = $this->history_set_v1($list,$day,$appid,$wherecountry,true);
		return $result;
	}
	
	private function get_history_roi_data_v1($appid,$start,$end,$country,$day){
		$list =[];
		$dates = getDateFromRange($start, $end);
		for($i=0;$i<=$day;$i++)
		{
			foreach($dates as $v)
			{
				$list[] = ["date"=>$v,"day"=>$i];
			}
		}
		$where="gb_id={$appid} and install_date<='{$end}' and install_date>='{$start}' ";
		$wherecountry =[];
		if($country!="all")
		{
			$where.=" and country='{$country}'";
			$wherecountry =[$country];
		}
		$sql ="SELECT install_date,event_date,datediff(event_date,install_date) as `day`,SUM(revenue) as ad_revenue  from  hellowd_adjust_ltv_pdt WHERE  {$where} GROUP BY install_date,event_date HAVING day<={$day}";
		$res = Db::query($sql);
		$res = $this->asn($res);
		if(!empty($list))
		{
			foreach($list as &$vv)
			{
				$vv["ad_revenue"] ="0.00";
				if(isset($res[$vv["date"]][$vv["day"]]))
				{
					$vv["ad_revenue"] = $res[$vv["date"]][$vv["day"]];
				}
				$event_date =  date("Y-m-d", (strtotime($vv["date"]) + $vv["day"] * 24 * 3600));
				$purchase="0";
				if (in_array($appid,$this->allow_app_list)) {
					$purchase = $this->getpurchasev1($appid, $vv["date"], $event_date,$event_date,"","",$wherecountry,[]);
				}
				//$purchase =$this->getpurchasev1($appid, $vv["date"], $event_date,$event_date,"","",$wherecountry,[]);
				$vv["purchase"] = $purchase;
			}
		}
		$result = $this->history_set_v1($list,$day,$appid,$wherecountry);
		return $result;
	}
	
	private function asn($res,$group=false){
		$out =[];
		if(!empty($res))
		{
			foreach($res as $vv)
			{
				if($group)
				{
					$out[$vv["install_date"]][0][$vv["day"]] = $vv["ad_revenue"];
					$out[$vv["install_date"]][1] = $vv["campaign_id"];
				}else{
					$out[$vv["install_date"]][$vv["day"]] = $vv["ad_revenue"];
				}
			}
		}
		return $out;
	}
	
	
	private function history_set_v2($res,$sroi,$day){
		$out =[];
        $result =[];
        $spend ="0.00";		
		foreach($res as $vv)
		{  
		   if($vv["roi"]>=$sroi)
		   {
			   $out[] = $vv;
		   }		    
		}
		if(!empty($out))
		{
			foreach($out as $vvv)
			{
				for($i=0;$i<=$day;$i++)
				{
					if(isset($result[$i]["ad_revenue"]))
					{
						$result[$i]["ad_revenue"]+=$vvv["ad_revenue"][$i];
					}else{
						$result[$i]["ad_revenue"] = $vvv["ad_revenue"][$i];
					}
					if(isset($result[$i]["purchase_revenue"]))
					{
						$result[$i]["purchase_revenue"]+=$vvv["purchase_revenue"][$i];
					}else{
						$result[$i]["purchase_revenue"] = $vvv["purchase_revenue"][$i];
					}
					$total_revenue = $vvv["ad_revenue"][$i]+$vvv["purchase_revenue"][$i];
					if(isset($result[$i]["total_revenue"]))
					{
						$result[$i]["total_revenue"]+=$total_revenue;
					}else{
						$result[$i]["total_revenue"] = $total_revenue;
					}			
				}
				$spend+=$vvv["spend"]["spend"];
			}
		}
       return ["out"=>$out,"result"=>$result,"spend"=>$spend,"num"=>count($out)];
	}
	
	
	private function history_set_v1($res,$day,$appid,$wherecountry,$group=false){
		$out =[];
        $result =[];
        $spend ="0.00";		
		foreach($res as $vv)
		{  
		   
		   $i = $vv["day"];
		   $out[$vv["date"]]["ad_revenue"][] = $vv["ad_revenue"];
		   $out[$vv["date"]]["purchase_revenue"][] = $vv["purchase"];
		   $out[$vv["date"]]["total_revenue"][] = $vv["ad_revenue"]+$vv["purchase"];
		   if($group)
		   {
			   $out[$vv["date"]]["campaign_id"] = $vv["campaign_id"];
		   }		   
		   if(isset($result[$i]["ad_revenue"]))
			{
				$result[$i]["ad_revenue"]+=$vv["ad_revenue"];
			}else{
				$result[$i]["ad_revenue"] = $vv["ad_revenue"];
			}
			if(isset($result[$i]["purchase_revenue"]))
			{
				$result[$i]["purchase_revenue"]+=$vv["purchase"];
			}else{
				$result[$i]["purchase_revenue"] = $vv["purchase"];
			}
			$total_revenue = $vv["ad_revenue"]+$vv["purchase"];
			if(isset($result[$i]["total_revenue"]))
			{
				$result[$i]["total_revenue"]+=$total_revenue;
			}else{
				$result[$i]["total_revenue"] = $total_revenue;
			}					   
		}
         if(!empty($out))
		 {
			 foreach($out as $k=>&$o)
			 {
				 if($group)
				 {
					 $o["spend"] = $this->get_campaign_sepend($appid, $o["campaign_id"], $k, $k,$wherecountry,"all");	
				 }else{					 
					 $o["spend"] = $this->get_v1_sepend($appid,$k,$k,$wherecountry,"all");					 
				 }				 
				 $o["roi"] = $o["spend"]["spend"]>0?round(array_sum($o["total_revenue"])*100/$o["spend"]["spend"]):0;
				 $spend+=$o["spend"]["spend"];
			 }
		 }
		return ["out"=>$out,"result"=>$result,"spend"=>$spend,"num"=>count($out)];
	}
	
	private function  history_set($res,$day){
		   $result =[];
			$yb_spend = "0.00";
			
			foreach( $res as $h )
			{
				$i = 0;
				for($i=0;$i<=$day;$i++)
				{
					$ad_row = $this->get_roi_day_data($h["campaign_id"],$h["install_date"],"ad",$i);
					$ad_revenue ="0.00";
					if(!empty($ad_row))
					{
						$ad_revenue = $ad_row["revenue"];
					}
					
					$purchase_row = $this->get_roi_day_data($h["campaign_id"],$h["install_date"],"purchase",$i);
					$purchase_revenue ="0.00";
					if(!empty($purchase_row))
					{
						$purchase_revenue = $purchase_row["revenue"];
					}
					if(isset($result[$i]["ad_revenue"]))
					{
						$result[$i]["ad_revenue"]+=$ad_revenue;
					}else{
						$result[$i]["ad_revenue"] = $ad_revenue;
					}
					if(isset($result[$i]["purchase_revenue"]))
					{
						$result[$i]["purchase_revenue"]+=$purchase_revenue;
					}else{
						$result[$i]["purchase_revenue"] = $purchase_revenue;
					}
					$total_revenue = $ad_revenue+$purchase_revenue;
					if(isset($result[$i]["total_revenue"]))
					{
						$result[$i]["total_revenue"]+=$total_revenue;
					}else{
						$result[$i]["total_revenue"] = $total_revenue;
					}
					
				}
				$yb_spend+=$h["spend"];
			}
	  return ["result"=>$result,"yb_spend"=>$yb_spend];
	}
	
	private function get_range_roi($roi)
	{
		$start_list =[];
		$change =2;
		$end_list =[];
		for($i=5;$i>0;$i--)
		{
			$min = ($roi-2)-(5*$i);
			$max = ($roi-3)-(5*($i-1));
			$start_list[] = ["range"=>"{$min}%~{$max}%","min"=>$min,"max"=>$max];
		}
		$first = [];
		if(!empty($start_list))
		{
			$max = $start_list[0]["min"]-1;
			$first[] = ["range"=>"0%~{$max}%","min"=>0,"max"=>$max];
		}
		for($j=1;$j<=5;$j++)
		{
			$max = ($roi+2)+(5*$j);
			$min = ($roi+3)+(5*($j-1));
			$end_list[] = ["range"=>"{$min}%~{$max}%","min"=>$min,"max"=>$max];
		}
		$last = [];
		if(!empty($end_list))
		{
			$end = end($end_list);
			$last[] = ["range"=>($end["max"]+1)."%~","min"=>$end["max"]+1,"max"=>2000];
		}
		return array_merge($first,$start_list,[["range"=>($roi-2)."%~".($roi+2)."%","min"=>$roi-2,"max"=>$roi+2]],$end_list,$last);
	}
	
	public function setROI(){
		$data = input("post.");
		if(!empty($data))
		{
			$appid = getcache("select_app");
			$row = Db::name("object_roi")->where(["app_id"=>$appid,"country"=>$data["country"] ])->find();
			if(empty($row))
			{
				$data["app_id"] = $appid; 
				Db::name("object_roi")->insert($data);
			}else{
				Db::name("object_roi")->where(["app_id"=>$appid,"country"=>$data["country"] ])->update(["roi0"=>$data["roi0"] ]);
			}
		}
		exit("ok");
	}
	
	public function get_obj_roi($country="all")
	{
		$appid = getcache("select_app");
		$roi0 = Db::name("object_roi")->where(["app_id"=>$appid,"country"=>$country ])->value('roi0');
		echo "目标值:".$roi0."%";exit;
	}
	
	public function get_avg_show($appid,$start,$end,$country)
	{
		$where ="gb_id={$appid} and install_date>='{$start}' and install_date<='{$end}' and install_date=event_date";
		if($country!="all")
		{
			$where.=" and country='{$country}'";
		}
		return Db::name("adjust_ltv_pdt")->field('sum(int_show) as int_show,sum(rew_show) as rew_show')->where($where)->find();
	}
	
	public function revenue_total_ltv_json($date=[],$country=""){
		list($start,$end) = $date;
		$dates = getDateFromRange($start, $end);
		$appid = getcache("select_app");
		$sum0=0;
		$sum1=0;
		$sum2=0;
		$sum3=0;
		$sum4=0;
		$sum5=0;
		$sum6=0;
		$sum7=0;
		$sum14=0;
		$sum30=0;
		$n0=0;$n1=0;$n2=0;$n3=0;$n4=0;$n5=0;$n6=0;$n7=0;$n14=0;$n30=0;
		foreach ($dates as $v) {
			$row = $this->get_v2_ltv_data($appid, $v, $v,[],"0","custom",[]);	
			if($row["roi0"]!='--')
			{
			  $sum0+=$row["roi0"]*$row["installs"];
			  $n0+=$row["installs"];
			}
			if($row["roi1"]!='--')
			{
			  $sum1+=$row["roi1"]*$row["installs"];
			  $n1+=$row["installs"];
			}
			if($row["roi2"]!='--')
			{
			  $sum2+=$row["roi2"]*$row["installs"];
			  $n2+=$row["installs"];
			}
			if($row["roi3"]!='--')
			{
			  $sum3+=$row["roi3"]*$row["installs"];
			  $n3+=$row["installs"];
			}
			if($row["roi4"]!='--')
			{
			  $sum4+=$row["roi4"]*$row["installs"];
			  $n4+=$row["installs"];
			}
			if($row["roi5"]!='--')
			{
			  $sum5+=$row["roi5"]*$row["installs"];
			  $n5+=$row["installs"];
			}
			if($row["roi6"]!='--')
			{
			  $sum6+=$row["roi6"]*$row["installs"];
			  $n6+=$row["installs"];
			}
			if($row["roi7"]!='--')
			{
			  $sum7+=$row["roi7"]*$row["installs"];
			  $n7+=$row["installs"];
			}
			if($row["roi14"]!='--')
			{
			  $sum14+=$row["roi14"]*$row["installs"];
			  $n14+=$row["installs"];
			}
			if($row["roi30"]!='--')
			{
			  $sum30+=$row["roi30"]*$row["installs"];
			  $n30+=$row["installs"];
			}
		}
		$result["roi0"]=$sum0!=0?($n0>0?round($sum0/$n0,2):0):'--';
		$result["roi1"]=$sum1!=0?($n0>0?round($sum1/$n1,2):0):'--';
		$result["roi2"]=$sum2!=0?($n0>0?round($sum2/$n2,2):0):'--';
		$result["roi3"]=$sum3!=0?($n3>0?round($sum3/$n3,2):0):'--';
		$result["roi4"]=$sum4!=0?($n4>0?round($sum4/$n4,2):0):'--';
		$result["roi5"]=$sum5!=0?($n5>0?round($sum5/$n5,2):0):'--';
		$result["roi6"]=$sum6!=0?($n6>0?round($sum6/$n6,2):0):'--';
		$result["roi7"]=$sum7!=0?($n7>0?round($sum7/$n7,2):0):'--';
		$result["roi14"]=$sum14!=0?($n14>0?round($sum14/$n14,2):0):'--';
		$result["roi30"]=$sum30!=0?($n30>0?round($sum30/$n30,2):0):'--';
		$columns =["roi0","roi1","roi2","roi3","roi4","roi5","roi6","roi7","roi14","roi30"];
		$this->assign("columns", $columns);
		$this->assign("group","roi"); 
		$this->assign("data", $result);
		return $this->fetch("revenue_ltv_json");		
	}
	
	//新增 主页分析收益细分来源 2020-11-16
	public function revenue_ltv_json($date="",$group="",$country="")
	{
		$appid = getcache("select_app");
		$where ="event_date='{$date}' and gb_id={$appid}";	
		$cc=[];
		if($country && $country!="all")
		{
			$where.=" and country='{$country}'";
			$cc=[$country];
		}
		switch($group)
		{
			case 'day':
			 $columns =["留存天数/人数<span style='font-size:10px;color:red;'>(单指看了广告的人数)</span>","安装日期","收益=(广告+内购)","人均=(激励+插屏)","收益占比"];
			 $sql ="SELECT install_date as name,SUM(revenue) as revenue,round(sum(int_show+rew_show),2) as total_show,round(SUM(int_show),2) as int_show,round(SUM(rew_show),2) as rew_show  from hellowd_adjust_ltv_pdt WHERE {$where} GROUP BY install_date ORDER BY install_date desc";
			break;
			case 'channel':
			 $columns =["渠道","广告收益","收益占比"];
			 $sql ="SELECT media_source as name,SUM(revenue) as revenue  from hellowd_adjust_ltv_pdt WHERE {$where} GROUP BY media_source order by revenue desc";
			break;
			case 'device':
			 $columns =["设备类型","广告收益","收益占比"];
			 $sql =" SELECT device_category as name,SUM(revenue) as revenue  from hellowd_adjust_ltv_pdt WHERE {$where} GROUP BY device_category";
			break;
			case 'roi':
			 $columns =["roi0","roi1","roi2","roi3","roi4","roi5","roi6","roi7","roi14","roi30"];
			 $current_date = date("Y-m-d", strtotime("-2 day"));
			 $result=[];
			 $spend = $this->get_v1_sepend($appid,$date,$date,$cc,"all");
			 foreach(["0","1","2","3","4","5","6","7","14","30"] as $c)
			 {				 
				 $tr = (strtotime($date) + $c * 24 * 3600);
				 if($tr>strtotime($current_date))
				 {
					$result["roi".$c] ="--";
				 }else{
					$time = date("Y-m-d", $tr);
                   $row = $this->get_v2_revenue($appid,$date,$date,$time,$cc,[],[]);
				   $revenue = $row["new_revenue"]>0?$row["new_revenue"]:"0";
				   $result["roi".$c] = $revenue>0?round($revenue*100/$spend["spend"],2)."%":0; 
				 }				 
			 }
			 
			 $this->assign("columns", $columns);
		     $this->assign("group", $group);      
		     $this->assign("data", $result);
		     return $this->fetch("revenue_ltv_json");
		}
		$active_users = $this->getactive_users($appid, $date, $date,$country);
		$data = Db::query($sql);
        $all_revenue = 0;
		$total_show = 0;
		$int_show =0;
		$rew_show =0;
		if(!empty($data))
		{
			$res = Db::name("adjust_ltv_pdt")->field("SUM(revenue) as revenue")->where($where)->find();
			$total_revenue = $res["revenue"]?$res["revenue"]:"0.00";
			foreach($data as &$vv)
			{
				$vv["rate"] = $total_revenue>0?round($vv["revenue"]*100/$total_revenue,2):0;
				$vv["rate"] = $vv["rate"]."%";
				$vv["revenue"] = round($vv["revenue"],2);
				if($group=="day")
				{
					$purchase="0.00";
					if (in_array($appid,$this->allow_app_list)) {
						$purchase = $this->getpurchasev1($appid,$vv["name"], $date, $date,"","",$cc,[]);
					}
					$num = $this->get_ltv_users($appid, $date, $vv["name"], $country);
					$stimestamp = strtotime($vv["name"]);
					$etimestamp = strtotime($date);
					$days = ($etimestamp-$stimestamp)/86400;
					$vv["day"] = $days."/".$num;
					$vv["total"] = round($vv["revenue"]+$purchase,2);
                    $all_revenue += $vv["total"];
					$vv["revenue"] = $vv["total"]."=(".$vv["revenue"]."+".$purchase.")";
					$total_show+=$vv["total_show"];
					$int_show+=$vv["int_show"];
					$rew_show+=$vv["rew_show"];
					$vv["rate"] = $total_revenue>0?round($vv["total"]*100/$total_revenue,2):0;
					$vv["total_show"] = $num>0?round($vv["total_show"]/$num,2):0;
					$vv["int_show"] = $num>0?round($vv["int_show"]/$num,2):0;
					$vv["rew_show"] = $num>0?round($vv["rew_show"]/$num,2):0;					
				}else{
                    $all_revenue += $vv["revenue"];
                }
			}
		}
		$this->assign("columns", $columns);
		$this->assign("group", $group);
        $this->assign("all_revenue", $all_revenue);
		$this->assign("total_show", $active_users>0?round($total_show/$active_users,2):0);
		$this->assign("int_show",$active_users>0?round($int_show/$active_users,2):0);
		$this->assign("rew_show", $active_users>0?round($rew_show/$active_users,2):0);
		$this->assign("data", $data);
		return $this->fetch("revenue_ltv_json");
	}
	
	
	//新增 收益数据 和花费数据更新
	public function updateMainData($start="",$end=""){
		
		if($start=="" || $end=="")
		{
			$start = date("Y-m-d", strtotime("-5 day"));
			$end = date("Y-m-d", strtotime("-1 day"));
		}
		$revenue_sql ="SELECT country,platform as channel,sys_app_id as app_id,date,SUM(revenue) as ad_revenue  from  hellowd_adcash_data 
WHERE date>='{$start}' and date<='{$end}' and revenue>0 GROUP BY country,platform,sys_app_id,date";
        $revenueList = Db::query($revenue_sql);
		 if(!empty($revenueList))
		{
			Db::name("main_data")->where("date>='{$start}' and date<='{$end}'")->delete();
			Db::name("main_data")->insertAll($revenueList);
		}
		$this->updateMainRoi();
		exit("ok");
	}
	
	public function updateMainRoi($start="",$end=""){
		
		if($start=="" || $end=="")
		{
			$start = date("Y-m-d", strtotime("-3 day"));
			$end = date("Y-m-d", strtotime("-1 day"));
		}
		$roi_sql =" SELECT install_date as date,gb_id as app_id,country,SUM(revenue) as ad_revenue  from  hellowd_adjust_ltv_pdt 
WHERE event_date=install_date and install_date>='{$start}' and install_date<='{$end}' GROUP BY gb_id,country,install_date";
		 $revenueList = Db::query($roi_sql);
		 if(!empty($revenueList))
		{
			Db::name("main_roi")->where("date>='{$start}' and date<='{$end}'")->delete();
			foreach($revenueList as &$r)
			{
				$purchase ="0.00";
				if (in_array($r["app_id"],$this->allow_app_list)) {
						$purchase = $this->getpurchasev1($r["app_id"], $r["date"],$r["date"],$r["date"], "","",[$r["country"]],[]);			
				}
				$r["purchase"] = $purchase;
				Db::name("main_roi")->insert($r);
			}
		} 
		exit("ok");		
	}
}
