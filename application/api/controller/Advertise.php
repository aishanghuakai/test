<?php

namespace app\api\controller;

use think\Db;
use \think\Request;
use think\Validate;
use app\util\ShowCode;

header("Access-Control-Allow-Origin: *");

class Advertise extends Validate
{

    //获取广告列表 老
    public function advlist($appid = "", $country = "")
    {
        if (!$appid || !preg_match('/^\d+$/', $appid)) {
            return show_out(1001, "INVALID", new \StdClass());
        }
        $r = Db::name('app')->where("id={$appid}")->find();
        if (empty($r)) {
            return show_out(1002, "DB_DATA_EMPTY", new \StdClass());
        }
        if ($country != "" && $country == "-1") {
            $result = $this->getcountry();
            $country = $result["country_code"];
        }
        $issort = false;
        $out_data = array(
            "int" => array(),
            "rew" => array(),
            "nat" => array(),
            "ban" => array(),
            "conf" => array()
        );
        $data = Db::name('adconfig')->where("appid={$appid} and val!='' and app_class=1 and isnew=0 and status=1")->field('id,adtype,name,val,adsort')->order('adtype,adsort')->select();
        if (!empty($data)) {
            if ($country != "") {

                $sql = "select uus.*,cc.prop_value_one,cc.prop_value_two,cc.id as pid,cc.remark from hellowd_adprop cc join hellowd_adconfig uus on uus.id=cc.cfid where uus.appid={$appid} and cc.prop_value_one='{$country}' and uus.app_class=1";
                $res = Db::query($sql);
                //print_r($res);exit;
                if (!empty($res)) {
                    foreach ($res as $r_v) {
                        $out_data[$r_v["adtype"]][] = ["name" => $r_v["name"], "val" => $r_v["prop_value_two"], "adsort" => $r_v["remark"]];
                    }
                } else {

                    foreach ($data as $kk => &$vv) {

                        $out_data[$vv["adtype"]][] = ["name" => $vv["name"], "val" => $vv["val"], "adsort" => $vv["adsort"]];
                    }
                }

            } else {

                foreach ($data as $kk => &$vv) {

                    $out_data[$vv["adtype"]][] = ["name" => $vv["name"], "val" => $vv["val"], "adsort" => $vv["adsort"]];
                }
            }

            foreach ($out_data as &$r_v) {
                if (!empty($r_v)) {
                    if ($issort) {
                        $r_v = my_sort($r_v, "adsort");
                    }
                    foreach ($r_v as &$z_v) {
                        unset($z_v["adsort"]);
                    }
                }
            }

        }

        $condata = Db::name('adconfig')->where(['appid' => $appid, "app_class" => 2, "isnew" => 0])->field('name,val,id')->select();
        if (!empty($condata)) {
            foreach ($condata as &$vvv) {

                if ($country != "") {
                    $resa = Db::name('adprop')->where("cfid={$vvv['id']} and prop_value_one='{$country}'")->find();

                    if (!empty($resa)) {
                        $vvv["val"] = $resa["prop_value_two"];

                    }
                }

                $out_data["conf"][$vvv["name"]] = $vvv["val"];
            }
        }
        $out_data["country"] = $country;
        return $out_data;
    }

    //获取国家
    public function getcountry()
    {
        $ip = get_client_ip();
        try {
            $country_code = getgeoip($ip);
            //$country_code = getapiip($ip);
        } catch (\Exception $e) {
            //$country_code = getapiip($ip);
            $country_code = gettaobaoip($ip);
        }

        return ["country_code" => $country_code];
    }

    //应用内推送
    public function pushlist($appid = "")
    {
        if (!$appid || !preg_match('/^\d+$/', $appid)) {
            return show_out(1001, "INVALID", new \StdClass());
        }
        $r = Db::name('app')->where("id={$appid}")->find();
        if (empty($r)) {
            return show_out(1002, "DB_DATA_EMPTY", new \StdClass());
        }
        $out_data = [];
        $out_data = Db::name("app_spread")->field("id,name,desc,shop_url,package_name,video_url,version")->where(["appid" => $appid])->order("ordernum asc,id desc")->select();
        return $out_data;
    }


    //获取广告列表 新
    public function tab_json($appid = "", $country = "")
    {
        if (!$appid || !preg_match('/^\d+$/', $appid)) {
            return show_out(1001, "INVALID", new \StdClass());
        }
        $r = Db::name('app')->where("id={$appid}")->find();
        if (empty($r)) {
            return show_out(1002, "DB_DATA_EMPTY", new \StdClass());
        }
        if ($country != "" && $country == "-1") {
            $result = $this->getcountry();
            $country = $result["country_code"];
        }
        $issort = false;
        $out_data = array(
            "int" => array(),
            "rew" => array(),
            "nat" => array(),
            "ban" => array(),
            "conf" => array()
        );
        $data = Db::name('adconfig')->where("appid={$appid} and val!='' and app_class=1 and isnew=1 and status=1")->field('id,adtype,name,val,adsort')->order('adtype,adsort')->select();
        if (!empty($data)) {
            if ($country != "") {

                $sql = "select uus.*,cc.prop_value_one,cc.prop_value_two,cc.id as pid,cc.remark from hellowd_adprop cc join hellowd_adconfig uus on uus.id=cc.cfid where uus.appid={$appid} and cc.prop_value_one='{$country}' and uus.app_class=1";
                $res = Db::query($sql);
                //print_r($res);exit;
                if (!empty($res)) {
                    foreach ($res as $r_v) {
                        $out_data[$r_v["adtype"]][] = ["name" => $r_v["name"], "val" => $r_v["prop_value_two"], "adsort" => $r_v["remark"]];
                    }
                } else {

                    foreach ($data as $kk => &$vv) {

                        $out_data[$vv["adtype"]][] = ["name" => $vv["name"], "val" => $vv["val"], "adsort" => $vv["adsort"]];
                    }
                }

            } else {

                foreach ($data as $kk => &$vv) {

                    $out_data[$vv["adtype"]][] = ["name" => $vv["name"], "val" => $vv["val"], "adsort" => $vv["adsort"]];
                }
            }

            foreach ($out_data as &$r_v) {
                if (!empty($r_v)) {
                    if ($issort) {
                        $r_v = my_sort($r_v, "adsort");
                    }
                    foreach ($r_v as &$z_v) {
                        unset($z_v["adsort"]);
                    }
                }
            }

        }

        $condata = Db::name('adconfig')->where(['appid' => $appid, "app_class" => 2, "isnew" => 1])->field('name,val,id')->select();
        if (!empty($condata)) {
            foreach ($condata as &$vvv) {

                if ($country != "") {
                    $resa = Db::name('adprop')->where("cfid={$vvv['id']} and prop_value_one='{$country}'")->find();

                    if (!empty($resa)) {
                        $vvv["val"] = $resa["prop_value_two"];

                    }
                }

                $out_data["conf"][$vvv["name"]] = $vvv["val"];
            }
        }
        $out_data["country"] = $country;
		$row = Db::name('access_params')->where(["app_id" => $appid])->find();
		if (!empty($row)) {
			$params_data = json_decode($row["content"], true);
			$out_data["adjust_token"] = isset($params_data["adjust_token"])?$params_data["adjust_token"]:"";
			$out_data["hwImportantToken"] = isset($params_data["hwImportantToken"])?$params_data["hwImportantToken"]:"";
			$out_data["hwGuideFinishToken"] = isset($params_data["hwGuideFinishToken"])?$params_data["hwGuideFinishToken"]:"";
			$out_data["hwLevelToken"] = isset($params_data["hwLevelToken"])?$params_data["hwLevelToken"]:"";
			$out_data["hwMonetizationToken"] = isset($params_data["hwMonetizationToken"])?$params_data["hwMonetizationToken"]:"";
			$out_data["hwPurchaseToken"] = isset($params_data["hwPurchaseToken"])?$params_data["hwPurchaseToken"]:"";
			$out_data["hwVideohighToken"] =isset($params_data["hwVideohighToken"])?$params_data["hwVideohighToken"]:"";
			$out_data["hwVideolowToken"] = isset($params_data["hwVideolowToken"])?$params_data["hwVideolowToken"]:"";
			$out_data["hwUACToken"] = isset($params_data["hwUACToken"])?$params_data["hwUACToken"]:"";
			$out_data["hwShowCloseToken"] = isset($params_data["hwShowCloseToken"])?$params_data["hwShowCloseToken"]:"";
			$out_data["hwATTToken"] = isset($params_data["hwATTToken"])?$params_data["hwATTToken"]:"";
			$out_data["hwATTAgreeToken"] = isset($params_data["hwATTAgreeToken"])?$params_data["hwATTAgreeToken"]:"";
			$out_data["hwATTCancelToken"] = isset($params_data["hwATTCancelToken"])?$params_data["hwATTCancelToken"]:"";
			$out_data["hwSecondRetentionToken"] = isset($params_data["hwSecondRetentionToken"])?$params_data["hwSecondRetentionToken"]:"";
			$out_data["hwATTCustomAgreeToken"] = isset($params_data["hwATTCustomAgreeToken"])?$params_data["hwATTCustomAgreeToken"]:"";
			$out_data["hwATTCustomCancelToken"] = isset($params_data["hwATTCustomCancelToken"])?$params_data["hwATTCustomCancelToken"]:"";
		}
        return $out_data;
    }
}
