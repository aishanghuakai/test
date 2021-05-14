<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \app\admin\model\Adcash_m;
use think\Db;
use think\Session;
use \app\admin\controller\Adcash;
use \app\admin\controller\Appsflyer;
ini_set("error_reporting","E_ALL & ~E_NOTICE");
/**
 * 后台管理主页面
 */
class Index extends Base
{

    public function main($appid = "", $from = "", $start = "", $end = "", $platform = "all", $country = "all")
    {
        //$role = getuserrole();
        $userinfo = getuserinfo();
        $role = $userinfo["ad_role"];
        $admin_id = Session::get('admin_userid');
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }


        if ($role == "publisher" && $admin_id != "6") {
            $app_list = explode(",", $userinfo['allow_applist']);
            if (!in_array($appid, $app_list)) {
                if (!empty($app_list)) {
                    $appid = $app_list[0];
                }
                //exit("You do not have permission to access, please contact the system administrator");
            }
			//return $this->revenue($appid, $start, $end, $platform, $country);
            //exit;
        }
        if ($role == "advertiser") {
            $app_list = explode(",", $userinfo['allow_applist']);

            if (empty($userinfo['allow_applist']) || empty($app_list)) {
                return $this->spend($appid, $start, $end);
                exit;
            }
            if (!in_array($appid, $app_list)) {
                return $this->spend($appid, $start, $end);
                exit;
            }
        }
        if ($role == "producter") {
            $app_list = explode(",", $userinfo['allow_applist']);
            if (!in_array($appid, $app_list)) {
                if (!empty($app_list)) {
                    $appid = $app_list[0];
                }
                //exit("You do not have permission to access, please contact the system administrator");
            }
        }
        if ($role == "copartner") {
            return $this->copartner($appid, $start, $end);
            exit;
        }
        if ($role == "material") {
            return redirect('/admin_adset/index');
            exit;
        }

        if ($role == "financer") {
            //return $this->copartner($appid,$start,$end);exit;
        }

        if ($from == "1") {
            $ids = getmylikedata();
            setmylikedata($ids . "," . $appid);
        }

        setcache("select_app", $appid);

        $ids = getmylikedata();

        $where = "1=1 and id in(" . $ids . ")";
        $apps = Db::name("app")->field("id,app_name,platform")->where($where)->order("FIELD(id,{$ids})")->select();
        $this->assign("apps", $apps);
        $this->assign("role", $role);
        $this->assign("appid", $appid);
        $this->assign("userid", $userinfo["id"]);
        return $this->summary($start, $end, $country);
    }

    public function caption($id = "", $type = "")
    {
        if (!$id) {
            $id = Session::get('admin_userid');
        }
        $root = $_SERVER['DOCUMENT_ROOT'] . "/icon/{$id}.png";
        $name = getusername($id);
        $text = "{$name}--GameBrain";
        $size = 8;//文字大小
        $font = dirname(__FILE__) . "/msyh.TTF";//字体
        $slimg = imagecreatetruecolor(100, 150);//建立一个画板，尺寸可以自行修改
        $bg = imagecolorallocatealpha($slimg, 0, 0, 0, 127);
        $color = imagecolorallocate($slimg, 0, 0, 0); //字体拾色
        imagealphablending($slimg, false);//关闭混合模式，以便透明颜色能覆盖原画板
        imagefill($slimg, 0, 0, $bg);//填充
        imagefttext($slimg, $size, 20, 10, 150, $color, $font, $text);
        imagesavealpha($slimg, true);
        header("content-type:image/png");

        if ($type == "yes") {
            imagepng($slimg, $root);
        } else {
            imagepng($slimg);
        }
        imagedestroy($slimg);
    }

    public function copartner($appid = "", $start = "", $end = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }

        $userinfo = getuserinfo();
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
        setcache("select_app", $appid);
        if ($start == "" || $end == "") {
            if ($userinfo["id"] == "58") {
                $start = "2020-04-20";
            } else {
                $start = date("Y-m-d", strtotime("-10 day"));
            }
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        $now = date("Y-m-d", time());
        $month = date("Y-m", time()) . "-01";
        $current_monthrate = $this->getEveryRate($month);
        if ($appid == 112) {
            $w_data = $this->getCopartnerWork($appid, $month, $now);
            $revenue = $w_data["revenue"];
            $spend = $w_data["spend"];
        } else {
            $revenue = $this->dealworkData($appid, $month, $now, $current_monthrate);
            if ($appid == 77) {
                $spend = $this->getnewtarspend($appid, $month, $now);
            } else {
                $spend_data = $this->getspendtotal($appid, $month, $now, "all", "all");
                $spend = $spend_data["spend"];
            }
        }
        $revenue_rate = getrevenue_rate($appid);
        $month_revenue = ($revenue - $spend) * $revenue_rate / 100;
        $this->assign("revenue_rate", $revenue_rate);
        $total_money = "0.00";
        $r = Db::name("copartner_month")->field("sum(money) as money")->where(["app_id" => $appid])->find();
        if (!empty($r)) {
            $total_money = $r["money"];
        }
        $this->assign("appid", $appid);
        $this->assign("month_revenue", round($month_revenue, 3));
        $this->assign("total_money", round($total_money, 3));
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("userinfo", $userinfo);
        return $this->fetch('copartner');
    }

    private function getcopartnerdata($appid, $type = "1")
    {
        $res = Db::name("copartner_data")->field("spend,revenue,active_users,new_users,reten,roi")->where(["app_id" => $appid, "type" => $type])->find();
        if (!empty($res)) {
            return $res;
        }
        return ["spend" => "0.00", "revenue" => "0.00", "active_users" => 0, "new_users" => 0, "reten" => 0, "roi" => 0];
    }

    //国家
    public function countrygroup($appid = "", $start = "", $end = "")
    {
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-3 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $allcountrys = admincountry();
        $out_data = [];
        $echats_data = [];
        $countryname = "";
        $rois = "";
        $common_roi = "";
		/* $country = Db::name("adspend_data")->field('country,sum(spend) as spend')->where(" app_id={$appid} and spend>1 and date>='{$start}' and date<='{$end}'")->group('country')->having('spend>20')->select();
		array_unshift($country,["country"=>"all"]);
        foreach ($country as $k => $v) {
			if($v!="")
			{
				$out_data[]["name"] = $allcountrys[$v["country"]]?$allcountrys[$v["country"]]:$v["country"];
				$data = $this->getmaintotal($appid, $start, $end,$v["country"]);
				if($v["country"]=="all")
				{
					 $list = $this->getrangetotalday($appid, $start, $end,"all");
					 $revenue = $this->get_total_revenue($list);
					 $data["revenue"]["total"]["revenue"] = $revenue;
					 $data["roi"] = $data["spend"]["spend"]>0?round($revenue*100/$data["spend"]["spend"]):0;
				}
				if ($v["country"]!= "all" && $data["roi"] < 10000) {
					$echats_data[$v["country"]] = ["name" =>$allcountrys[$v["country"]], "roi" => $data["roi"], "spend" => $data["spend"]["spend"], "revenue" => $data["revenue"]["total"]["revenue"]];
				}

				$out_data[$k]["results"] = $data;
				$out_data[$k]["kk"] = $v["country"];
			}
			
        } */
		$res = Db::name("country_summary")->field('country,avg(active_users) as active_users,sum(revenue) as revenue,sum(new_users) as new_users,avg(reten) as reten,sum(spend) as spend')->where(" app_id={$appid} and date>='{$start}' and date<='{$end}'")->group('country')->select();
		if(!empty($res))
		{
			foreach($res as $v)
			{
				$data=$v;
				$out_data[]["name"] = $allcountrys[$v["country"]]?$allcountrys[$v["country"]]:$v["country"];
				$data["revenue"]["total"]["revenue"] = $v["revenue"];
				$data["spend"]["spend"] = $v["spend"];
				$data["spend"]["cpi"] = $v["new_users"]>0?round($v["spend"]/$v["new_users"],2):0;
				$data["revenue"]["total"]["dauarpu"] = $v["active_users"]>0?round($v["revenue"]/$v["active_users"],3):0;
				$data["roi"] = $data["spend"]["spend"]>0?round($v["revenue"]*100/$data["spend"]["spend"]):0;
				//$data["reten"] = $data["reten"]*100;
				$out_data[$k]["results"] = $data;
				$out_data[$k]["kk"] = $v["country"];
			}
		}
        $roinum = 0;
        $echats_data = admin_array_sort($echats_data, "spend", 'desc');
        foreach ($echats_data as $kk => $vv) {
            $countryname .= "'{$vv['name']}',";
            $rois .= "{$vv['roi']},";
            $common_roi .= "100,";
            if ($vv["roi"] > 100) {
                $roinum++;
            }
        }
        $this->assign("countryname", rtrim($countryname, ","));
        $this->assign("rois", rtrim($rois, ","));
        $this->assign("out_data", $out_data);
        $this->assign("common_roi", rtrim($common_roi, ","));
        $this->assign("start", $start);
        $this->assign("roinum", $roinum);
        $this->assign("end", $end);
        return $this->fetch('countrygroup');
    }

    //国家分产品
    public function productgroup($appid = "", $country = "all")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        $where_country = "";
        if ($country != "all") {
            $where_country = " and country='{$country}'";
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $rev = Db::name("adcash_data")->field("round(sum(revenue),2) as revenue")->where("sys_app_id={$appid} {$where_country}")->find();
        $upltvs = getupltvids();
        $revenue = $rev["revenue"] ? $rev["revenue"] : "0.00";
        if (isset($upltvs[$appid]) && !empty($upltvs[$appid])) {
            $where = "sys_app_id={$appid}  and platform=6 and unit_id in({$upltvs[$appid]}) {$where_country}";
            $up_data = Db::name("adcash_data")->field("round(sum(revenue),2) as revenue")->where($where)->find();
            $up_revenue = $up_data["revenue"] ? $up_data["revenue"] : "0.00";
            $revenue = $revenue - $up_revenue < 0 ? "0.00" : $revenue - $up_revenue;
        }
        $spe = Db::name("adspend_data")->field("round(sum(spend),2) as spend")->where("app_id={$appid} {$where_country}")->find();
        $spend = $spe["spend"] ? $spe["spend"] : "0.00";
        $roi = $spend <= 0 ? "0" : round($revenue * 100 / $spend, 2);
        $allcountrys = admincountry();
        $this->assign("country", $allcountrys[$country]);
        $this->assign("spend", $spend);
        $this->assign("revenue", $revenue);
        $this->assign("roi", $roi);
        return $this->fetch('productgroup');
    }

    public function chats($appid = "", $spm = "active_users", $start = "", $end = "", $country = "all")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        setcache("select_app", $appid);

        $r = $this->viewchats($appid, $spm, $start, $end, $country);

        $this->assign("appid", $appid);
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("r", $r);
        $this->assign("spm", $spm);
        $this->assign("countrys", admincountry());
        $this->assign("country", $country);
        return $this->fetch('chats');
    }

    private function viewchats($appid = "", $spm = "", $start = "", $end = "", $country = "")
    {
        $out_data = [];
        $text = "";
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $week = getweekday($v);
            $date = date("m月d日", strtotime($v));
            $out_data[$k]["date"] = "({$week})" . $date;
            if ($spm == "active_users") {
                $text = "活跃";
                $out_data[$k]["val"] = $this->getactive_users($appid, $v, $v, $country);
            } elseif ($spm == "new_users") {
                $text = "新增";
                $out_data[$k]["val"] = $this->getnew_users($appid, $v, $v, $country);
            } elseif (preg_match('/retention/', $spm)) {

                $d = explode("_", $spm);
                $day = $d[1];
                $text = $day == 1 ? "次留" : "{$day}留";
                $out_data[$k]["val"] = $this->getdayreten($appid, $v, $v, $country, $day);
            }
            $out_data[$k]["desc"] = str_replace(array("\r\n", "\r", "\n"), "", (isshowtips($appid, $v, "producter")));
        }
        return ["data" => json_encode($out_data), "text" => $text];
        exit;
    }

    private function getRoiModel($data)
    {
        $date = "";
        $val = "[";
        $o_val = "";
        if (!empty($data)) {
            foreach ($data as $key => $vv) {
                $date .= $vv["date"] . ",";
                $val .= $vv["result"]["roi"] . ",";
                $o_val .= $vv["result"]["roi"] . ",";
            }
            $val = rtrim($val, ",");
            $o_val = rtrim($o_val, ",");
        }
        $val .= "]";
        return ["date" => rtrim($date, ","), "val" => $val, "o_val" => $o_val];
    }

    private function getSpendRevenue($data)
    {
        $date = "";
        $spendval = "";
        $revenueval = "";
        $rolval = "";
        if (!empty($data)) {
            foreach ($data as $key => $vv) {
                $vv["date"] = date("m月d日", strtotime($vv["date"]));
                $date .= "'{$vv["date"]}',";
                $spendval .= $vv["result"]["spend"]['spend'] . ",";
                $revenueval .= $vv["result"]["revenue"]['total']['revenue'] . ",";
                $rolval .= $vv["result"]["roi"] . ",";
            }
            $spendval = rtrim($spendval, ",");
            $revenueval = rtrim($revenueval, ",");
            $rolval = rtrim($rolval, ",");
        }
        return ["date" => rtrim($date, ","), "spendval" => $spendval, "revenueval" => $revenueval, "rolval" => $rolval];
    }

    //总表
    public function summary($start = "", $end = "", $country = "all")
    {
        $appid = getcache("select_app");
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        $isnew = false;

        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-8 day"));
            if (date("H") >= "17") {
                $end = date("Y-m-d", strtotime("-1 day"));
                $isnew = true;
            } else {
                $end = date("Y-m-d", strtotime("-2 day"));
            }
        }
        $tg_data = $this->getrangetotalday($appid, $start, $end, $country);
        //$tg_data =$this->cahcadata($appid,$start,$end,$country);
        $result = $this->summaryrevenuerate($appid);
        $list = admin_array_sort($tg_data, "date", "desc");
        $chats_data = $this->getSpendRevenue($tg_data);
        $total_data = $this->getmaintotal($appid, $start, $end, $country);
        $this->assign("list", $list);
        $this->assign("chats_data", $chats_data);
        $this->assign("total_data", $total_data);
        $this->assign("tag", $this->getmyliketag($appid));
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("result", $result);
        $this->assign("isnew", $isnew);
        $this->assign("country", admincountry());
		$product_num = Db::name("purchase_details")->field('product_id,product_name')->where(["app_id"=>$appid,"total_usd_money"=>0])->count();
        $this->assign("product_num", $product_num);
		$this->assign("testnum", $this->checktestplan($appid));
        return $this->fetch('main');
    }

    //测试计划
    private function checktestplan($appid)
    {
        $date = date("Y-m-d", strtotime("-1 day"));
        $where = "app_id={$appid} and end_date>='{$date}' and status=3";
        $num = Db::name("testplan")->where($where)->count();
        return $num;
    }

    //收益增长
    private function summaryrevenuerate($appid)
    {
        $yesterday = $this->getdaterange("yesterday");
        $yesterday_revenue = $this->getsummaryrevenue($appid, $yesterday["start"], $yesterday["end"]);

        $beforeyesterday = $this->getdaterange("beforeyesterday");
        $beforeyesterday_revenue = $this->getsummaryrevenue($appid, $beforeyesterday["start"], $beforeyesterday["end"]);

        $lastweek = $this->getdaterange("lastweek");
        $lastweek_revenue = $this->getsummaryrevenue($appid, $lastweek["start"], $lastweek["end"]);

        $lastlastweek = $this->getdaterange("lastlastweek");
        $lastlastweek_revenue = $this->getsummaryrevenue($appid, $lastlastweek["start"], $lastlastweek["end"]);

        $thisweekrank = $this->roirank($appid, $lastweek["start"], $lastweek["end"]);
        $lastweekrank = $this->roirank($appid, $lastlastweek["start"], $lastlastweek["end"]);

        $z = $thisweekrank - $lastweekrank;
        $action = "down";
        if ($z < 0) {
            $z = $lastweekrank - $thisweekrank;
            $action = "up";
        } elseif ($z == 0) {
            $action = "=";
        }
        $lastmonth = $this->getdaterange("lastmonth");
        $lastmonth_revenue = $this->getsummaryrevenue($appid, $lastmonth["start"], $lastmonth["end"]);

        $thismonth = $this->getdaterange("thismonth");
        $thismonth_revenue = $this->getsummaryrevenue($appid, $thismonth["start"], $thismonth["end"]);
        return array(
            "yesterday" => array(
                "revenue" => round($yesterday_revenue, 2),
                "rate" => $this->getcomparerate($yesterday_revenue, $beforeyesterday_revenue)
            ),
            "thisweek" => array(
                "revenue" => round($lastweek_revenue, 2),
                "week" => $lastweek,
                "rate" => $this->getcomparerate($lastweek_revenue, $lastlastweek_revenue)
            ),
            "thismonth" => array(
                "revenue" => round($thismonth_revenue, 2),
                "rate" => $this->getcomparerate($thismonth_revenue, $lastmonth_revenue)
            ),
            "rank" => ["num" => $thisweekrank, "rate" => $z, "action" => $action]
        );
    }

    private function roirank($appid, $start, $end)
    {
        $sql = "SELECT t.*, @rownum := @rownum + 1 AS rownum
FROM (SELECT @rownum := 0) r, (SELECT app_id,avg(roi) as roi,avg(revenue) as revenue
FROM hellowd_summary_data WHERE date>='{$start}' and date<='{$end}' GROUP BY app_id ORDER BY (revenue*0.2/roi*0.8) desc) AS t;";
        $result = Db::query($sql);
        foreach ($result as $vv) {
            if ($vv["app_id"] == $appid) {
                return $vv["rownum"];
            }
        }
        return 0;
    }

    private function getcomparerate($a, $b)
    {
        $rate = "up";
        $c = $a - $b;
        if ($c < 0) {
            $rate = "down";
            $c = $b - $a;
        } elseif ($c > 0) {
            $rate = "up";
        } else {
            $rate = "=";
        }
        $percentage = $b > 0 ? round($c * 100 / $b, 2) : 0;
        return ["action" => $rate, "rate" => $percentage];
    }

    private function getdaterange($day)
    {
        $start = "";
        $end = "";
        switch ($day) {
            case 'yesterday':
                $start = date("Y-m-d", strtotime("-2 day"));
                $end = date("Y-m-d", strtotime("-2 day"));
                break;
            case 'beforeyesterday':
                $start = date("Y-m-d", strtotime("-3 day"));
                $end = date("Y-m-d", strtotime("-3 day"));
                break;
            case 'lastweek':
                $start = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 7, date("Y")));
                $end = date("Y-m-d", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 7, date("Y")));
                break;
            case 'lastlastweek':
                $start = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 - 14, date("Y")));
                $end = date("Y-m-d", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7 - 14, date("Y")));
                break;
            case 'lastmonth':
                $start = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 1, date("Y")));
                $end = date("Y-m-d", mktime(23, 59, 59, date("m"), 0, date("Y")));
                break;
            case 'thismonth':
                $start = date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y")));
                $end = date("Y-m-d", mktime(23, 59, 59, date("m"), date("t"), date("Y")));
                break;
        }
        return ["start" => $start, "end" => $end];
    }

    private function getsummaryrevenue($appid, $start, $end)
    {
        $where = "app_id={$appid} and date>='{$start}' and date<='{$end}'";
        $data = Db::name("summary_data")->field("sum(revenue) as revenue")->where($where)->find();
        return (isset($data["revenue"]) && $data["revenue"]) ? $data["revenue"] : "0.00";
    }

    //首页入口进来缓存
    private function cahcadata($appid, $start, $end, $country)
    {
        $hash = md5($appid . $start . $end . $country);
        $key = "APP_TOTAL_DATA" . $hash;
        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $val = $mem->get($key);
        if (!$val) {
            $tg_data = $this->getrangetotalday($appid, $start, $end, $country);
            $mem->set($key, $tg_data, 0, 24 * 60 * 60);
            return $tg_data;
        }
        return $val;
    }

    private function getmyliketag($appid)
    {
        $userid = Session::get('admin_userid');
        $res = Db::name("tag")->field("id,tag_value,country_code")->where(["app_id" => $appid, "userid" => $userid])->select();
        return $res;
    }

    public function roi_json($start = "", $end = "")
    {
        $appid = getcache("select_app");
        $list = $this->getrangetotalday($appid, $start, $end, "all");
        $roi_chats = $this->getRoiModel($list);
        return $this->fetch('roi', ["roi_chats" => $roi_chats]);
    }

    //当月收益 按时间筛选
    private function getcurrenincome($appid, $start, $end)
    {
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        $sum_sql = "select round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where}";
        $d = Db::query($sum_sql);
        if (isset($d[0]) && !empty($d[0])) {
            return $d[0]["revenue"] ? $d[0]["revenue"] : "0.00";
        }
        return "0.00";
    }

    //当月花费 按时间筛选
    private function getcurrenspend($appid, $start, $end)
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        $sum_sql = "select round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
        $d = Db::query($sum_sql);
        if (isset($d[0]) && !empty($d[0])) {
            return $d[0]["spend"] ? $d[0]["spend"] : "0.00";
        }
        return "0.00";
    }

    //对所选日期汇总
    public function getmaintotal($appid, $start, $end, $country)
    {
        $total_data = $this->gettotaldata($appid, $start, $end, $country);
        $daynum = count(getDateFromRange($start, $end));
        $total_data["reten"] = number_format($total_data["reten"] / $daynum, 2);
        return $total_data;
    }

    //新
    private function get_total_revenue($list)
    {
        $revenue = "0.00";
        if (!empty($list)) {
            foreach ($list as $vv) {
                $revenue += $vv["result"]["revenue"]["total"]["revenue"];
            }
        }
        return round($revenue, 2);
    }
	
	private function get_total_purchase($list)
    {
        $purchase = "0.00";
        if (!empty($list)) {
            foreach ($list as $vv) {
                $purchase += $vv["result"]["purchase"];
            }
        }
        return round($purchase,2);
    }
	
	private function get_total_ltv($list)
    {
        $ltv = "0.00";
		$ltv_ad_revenue ="0.00";
		$ltv_purchase ="0.00";
		$ctr_spend="0.00";
        if (!empty($list)) {
            foreach ($list as $vv) {
                $ltv += $vv["result"]["ltv_revenue"];
				$ltv_ad_revenue += $vv["result"]["ltv_ad_revenue"];
				$ltv_purchase += $vv["result"]["ltv_purchase"];
				$ctr_spend += $vv["result"]["ctr_spend"];
            }
        }
        return ["ltv"=>round($ltv,2),"ctr_spend"=>$ctr_spend,"ltv_ad_revenue"=>round($ltv_ad_revenue,2),"ltv_purchase"=>round($ltv_purchase,2)];
    }
	
	private function get_total_reten($list){
		$total_news =0;
		$total_reten_news =0;
		if (!empty($list)) {
            foreach ($list as $vv) {
                $c_users = $vv["result"]["new_users"];
				$c_reten = $vv["result"]["reten"];
				if($c_reten>0)
				{
					$total_news+=$c_users;
					$total_reten_news+=($c_users*$c_reten/100);
				}
            }
        }
		return $total_news>0?round($total_reten_news*100/$total_news,2):0;
	}
	
	private function get_total_roi0($list){
		$sum0=0;
		$num0=0;
		$last_date =  date("Y-m-d", strtotime("-1 day"));
		if (!empty($list)) {
            foreach ($list as $vv) {
                if($vv["date"]==$last_date)
				{
					continue;
				}
				$installs = $vv["result"]["spend"]["installs"];
				$sum0+= $installs*$vv["result"]["roi0"];
				$num0+=$installs;
            }
        }		
		return $num0>0?round($sum0/$num0,2):0;
	}

    public function summary_json($start = "", $end = "", $country = "all")
    {
        $appid = getcache("select_app");
        $list = $this->getrangetotalday($appid, $start, $end, $country);
        $total_data = $this->getmaintotal($appid, $start, $end, $country);
        $revenue = $this->get_total_revenue($list);
		$ltv_total_revenue = $this->get_total_ltv($list);
		$ltv_revenue = $ltv_total_revenue["ltv"];
		
		$total_data["ltv0"] = $total_data["spend"]["installs"]>0?round($ltv_revenue/$total_data["spend"]["installs"],2):"0.00";
		$total_data["ltv_ad0"] = $total_data["spend"]["installs"]>0?round($ltv_total_revenue["ltv_ad_revenue"]/$total_data["spend"]["installs"],2):"0.00";
		$total_data["ltv_pr0"] = $total_data["spend"]["installs"]>0?round($ltv_total_revenue["ltv_purchase"]/$total_data["spend"]["installs"],2):"0.00";
        $total_data["roi0"] = $this->get_total_roi0($list)."%";
		$total_data["purchase"] = $this->get_total_purchase($list);
        $total_data["revenue"]["total"]["revenue"] = $revenue;
		$total_data["reten"] = $this->get_total_reten($list);
		$total_data["ctr_spend"] =$ltv_total_revenue['ctr_spend'];
        $this->assign("total_data", $total_data);
        $role = getuserrole();
        $this->assign("role", $role);
        $this->assign("isnew", false);
        $this->assign("appid", $appid);
        $this->assign("list", admin_array_sort($list, "date", "desc"));
        return $this->fetch('summary_json');
    }

    private function getimpressions($appid, $start, $end, $active_users)
    {
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        $r = array
        (
            "int" => array
            (
                "impressions" => 0,
                "avgshow" => 0,
                "revenue" => "0.00"
            ),
            "rew" => array
            (
                "impressions" => 0,
                "avgshow" => 0,
                "revenue" => "0.00"
            ),
            "ban" => array
            (
                "impressions" => 0,
                "avgshow" => 0,
                "revenue" => "0.00"
            )
        );
        $sum_sql = "select adtype,sum(impression) as impressions,sum(revenue) as revenue  from hellowd_adcash_data where {$where} group by adtype";
        $d = Db::query($sum_sql);
        if (!empty($d)) {
            foreach ($d as &$v) {

                $upltv_adtype_data = getupltvfacebook($appid, $start, $end, "all", $v["adtype"]);
                $v["impressions"] = ($v["impressions"] - $upltv_adtype_data["impression"]) < 0 ? 0 : $v["impressions"] - $upltv_adtype_data["impression"];
                $v["revenue"] = $v["revenue"] > 0 ? round($v["revenue"], 2) : "0.00";
                $v["avgshow"] = $active_users <= 0 ? 0 : number_format($v["impressions"] / $active_users, 2);
                $r[$v["adtype"]] = ["impressions" => $v["impressions"], "avgshow" => $v["avgshow"], "revenue" => $v["revenue"]];
            }
        }
        $r["active_users"] = $active_users;
        return $r;
    }

    public function copartner_new_json($start = "", $end = "")
    {
        $appid = getcache("select_app");
        $out_data = [];
        $total = ["date" => "总计", "int_show" => 0, "rew_show" => 0, "ban_show" => 0,"sp_show"=>0,"active_users" => 0, "new_users" => 0, "reten_1" => "0", "spend" => "0.00", "revenue" => "0.00", "roi" => "0", "retention_7" => "0", "retention_28" => "0", "int_revenue" => "0.00", "rew_revenue" => "0.00", "ban_revenue" => "0.00","sp_revenue"=>"0.00"];
        $dates = getDateFromRange($start, $end);
        $num = count($dates);
        foreach ($dates as $k => $v) {
            $revenue = $this->getnewrevenuetotal($appid, $v, $v, "all", "all");
            $active_users = $this->getactive_users($appid, $v, $v, "all");
            $new_users = $this->getnew_users($appid, $v, $v, "all");
            $spend = $this->getspendtotal($appid, $v, $v, "all", "all");
            if ($new_users < $spend["installs"]) {
                $new_users = $spend["installs"];
            }
            $revenue["total"]["revenue"] = $revenue["total"]["revenue"] + $revenue["total"]["purchase"];
			if($appid==77)
			{
				if (strtotime($v) >= strtotime("2020-12-22")) {
                      $spend["spend"] = round($spend["spend"]*1.15,2);
				 }
			}
            $int_show = $revenue['int']['avgshow'];
            $rew_show = $revenue['rew']['avgshow'];
            $ban_show = $revenue['ban']['avgshow'];
			$sp_show = $revenue['sp']['avgshow'];
            $int_revenue = round($revenue['int']['revenue'],2);
            $rew_revenue = round($revenue['rew']['revenue'],2);
            $ban_revenue = round($revenue['ban']['revenue'],2);
			$sp_revenue = round($revenue['sp']['revenue'],2);
            $reten = $this->getreten($appid, $v, $v, "all");
            $roi = $spend["spend"] > 0 ? round($revenue['total']['revenue'] * 100 / $spend["spend"], 2) : "0";
            $vv = ["date" => $v, "int_show" => $int_show, "rew_show" => $rew_show, "ban_show" => $ban_show,"sp_show"=>$sp_show,"active_users" => $active_users, "new_users" => $new_users, "reten_1" => round($reten, 2), "spend" => round($spend["spend"], 2), "revenue" => round($revenue['total']['revenue'], 2), "roi" => $roi, "ban_revenue" => $ban_revenue,"sp_revenue"=>$sp_revenue,"int_revenue" => $int_revenue, "rew_revenue" => $rew_revenue];
            $reten_data = $this->getallreten($appid, $v, $v, "all");
            $s = array_merge($vv, $reten_data);
            $total["active_users"] += $s["active_users"];
            $total["new_users"] += $s["new_users"];
            $total["int_show"] += $s["int_show"];
            $total["rew_show"] += $s["rew_show"];
            $total["ban_show"] += $s["ban_show"];
			$total["sp_show"] += $s["sp_show"];
            $total["ban_revenue"] += $s["ban_revenue"];
            $total["int_revenue"] += $s["int_revenue"];
            $total["rew_revenue"] += $s["rew_revenue"];
            $total["reten_1"] += $s["reten_1"];
            $total["spend"] += $s["spend"];
            $total["revenue"] += $s["revenue"];
            $total["retention_7"] += $s["retention_7"];
            $total["retention_28"] += $s["retention_28"];
            $out_data[] = $s;
        }
        $total["roi"] = $total["spend"] > 0 ? round($total["revenue"] * 100 / $total["spend"], 2) : "0";
        $total["active_users"] = ceil($total["active_users"] / $num);
        $total["reten_1"] = round($total["reten_1"] / $num, 2);
        $total["retention_7"] = round($total["retention_7"] / $num, 2);
        $total["retention_28"] = round($total["retention_28"] / $num, 2);
        $total["int_show"] = round($total["int_show"] / $num, 2);
        $total["rew_show"] = round($total["rew_show"] / $num, 2);
        $total["ban_show"] = round($total["ban_show"] / $num, 2);
		$total["sp_show"] = round($total["sp_show"] / $num, 2);
		$total["ban_revenue"] = round($total["ban_revenue"],2);
        $total["int_revenue"] = round($total["int_revenue"],2);
        $total["rew_revenue"]= round($total["rew_revenue"],2);
		$total["sp_revenue"]= round($total["sp_revenue"],2);
		$total["spend"] = round($total["spend"],2);
		$total["revenue"] = round($total["revenue"],2);
        array_unshift($out_data, $total);
        echo json_encode($out_data);
        exit;
    }

    public function copartner_json($start = "", $end = "")
    {
        if (strtotime($start) >= strtotime("2020-01-01")) {
            return $this->copartner_new_json($start, $end);
        }
        $appid = getcache("select_app");
        $out_data = [];
        $total = ["date" => "总计", "int_show" => 0, "rew_show" => 0, "ban_show" => 0, "active_users" => 0, "new_users" => 0, "reten_1" => "0", "spend" => "0.00", "revenue" => "0.00", "roi" => "0", "retention_7" => "0", "retention_28" => "0", "int_revenue" => "0.00", "rew_revenue" => "0.00", "ban_revenue" => "0.00"];
        $dates = getDateFromRange($start, $end);
        $num = count($dates);
        foreach ($dates as $k => $v) {
            $revenue = $this->dealworkData($appid, $v, $v);
            $active_users = $this->getactive_users($appid, $v, $v, "all");
            $new_users = $this->getnew_users($appid, $v, $v, "all");
            $spend = $this->getspendtotal($appid, $v, $v, "all", "all");
            if ($appid == 77) {
                $spend["spend"] = $this->getnewtarspend($appid, $v, $v);
            }
            $impression = $this->getimpressions($appid, $v, $v, $active_users);
            $int_show = $impression['int']['avgshow'];
            $rew_show = $impression['rew']['avgshow'];
            $ban_show = $impression['ban']['avgshow'];
            $int_revenue = round($impression['int']['revenue'],2);
            $rew_revenue = round($impression['rew']['revenue'],2);
            $ban_revenue = round($impression['ban']['revenue'],2);
            $reten = $this->getreten($appid, $v, $v, "all");
            $roi = $spend["spend"] > 0 ? round($revenue * 100 / $spend["spend"], 2) : "0";
            $vv = ["date" => $v, "int_show" => $int_show, "rew_show" => $rew_show, "ban_show" => $ban_show, "active_users" => $active_users, "new_users" => $new_users, "reten_1" => round($reten, 2), "spend" => round($spend["spend"], 2), "revenue" => $revenue, "roi" => $roi, "ban_revenue" => $ban_revenue, "int_revenue" => $int_revenue, "rew_revenue" => $rew_revenue];
            $reten_data = $this->getallreten($appid, $v, $v, "all");
            $s = array_merge($vv, $reten_data);
            $total["active_users"] += $s["active_users"];
            $total["new_users"] += $s["new_users"];
            $total["int_show"] += $s["int_show"];
            $total["rew_show"] += $s["rew_show"];
            $total["ban_show"] += $s["ban_show"];
            $total["ban_revenue"] += $s["ban_revenue"];
            $total["int_revenue"] += $s["int_revenue"];
            $total["rew_revenue"] += $s["rew_revenue"];
            $total["reten_1"] += $s["reten_1"];
            $total["spend"] += $s["spend"];
            $total["revenue"] += $s["revenue"];
            $total["retention_7"] += $s["retention_7"];
            $total["retention_28"] += $s["retention_28"];
            $out_data[] = $s;
        }
        $total["roi"] = $total["spend"] > 0 ? round($total["revenue"] * 100 / $total["spend"], 2) : "0";
        $total["active_users"] = ceil($total["active_users"] / $num);
        $total["reten_1"] = round($total["reten_1"] / $num, 2);
        $total["retention_7"] = round($total["retention_7"] / $num, 2);
        $total["retention_28"] = round($total["retention_28"] / $num, 2);
        $total["int_show"] = round($total["int_show"] / $num, 2);
        $total["rew_show"] = round($total["rew_show"] / $num, 2);
        $total["ban_show"] = round($total["ban_show"] / $num, 2);
        array_unshift($out_data, $total);
        echo json_encode($out_data);
        exit;
    }

    private function getCopartnerWork($appid, $start, $end)
    {

        $w_data = Db::name("walk_data")->field("sum(revenue) as revenue,sum(spend) as spend")->where("appid={$appid} and country='all' and date>='{$start}' and date<='{$end}'")->find();
        if (!empty($w_data)) {
            $revenue = round($w_data["revenue"], 2);
            $spend = round($w_data["spend"], 2);
            return ["revenue" => $revenue, "spend" => $spend];
        }
        return [];
    }

    public function addtarSpend($date = "", $spend = "")
    {
        $appid = getcache("select_app");
        $r = Db::name("walk_data")->where(["appid" => $appid, "country" => "all", "date" => $date])->find();
        if (!empty($r)) {
            Db::name("walk_data")->where(["id" => $r["id"]])->update(["spend" => $spend]);
        } else {
            Db::name("walk_data")->insert(["spend" => $spend, "country" => "all", "date" => $date, "appid" => $appid]);
        }
        exit("ok");
    }

    public function addMoney($id = "", $money = "", $desc = "", $may_revenue = "")
    {
        if ($id) {
            Db::name("copartner_month")->where(["id" => $id])->update(["money" => $money, "desc" => $desc, "status" => 2, "save_revenue" => $may_revenue]);
            exit("ok");
        }
        exit("fail");
    }

    public function addRate($date = "", $server_spend = "", $other_spend = "", $spend_desc1 = "", $other_spend1 = "", $rate = "")
    {
        if ($date) {
            $appid = getcache("select_app");
            $r = Db::name("copartner_month")->field("id")->where("app_id={$appid} and date='{$date}'")->find();
            if (!empty($r)) {
                Db::name("copartner_month")->where("app_id={$appid} and date='{$date}'")->update(["server_spend" => $server_spend, "spend_desc1" => $spend_desc1, "other_spend1" => $other_spend1, "other_spend" => $other_spend]);
            } else {
                $revenue = "0.00";
                $ads_spend = "0.00";
                if ($date == "2019-03-01") {
                    $data = $this->getCopartnerbefore();
                    $revenue = $data["revenue"];
                    $ads_spend = $data["spend"];
                }
                Db::name("copartner_month")->insert(["revenue" => $revenue, "ads_spend" => $ads_spend, "server_spend" => $server_spend, "other_spend" => $other_spend, "spend_desc1" => $spend_desc1, "other_spend1" => $other_spend1, "app_id" => $appid, "date" => $date]);
            }
            $this->addnewRate($date, $rate);
        }
        exit("ok");
    }

    private function addnewRate($date, $rate)
    {
        $res = Db::name("revenue_rate")->field("id")->where("date='{$date}'")->find();
        if (!empty($res)) {
            Db::name("revenue_rate")->where("date='{$date}'")->update(["revenue_rate" => $rate]);
        } else {

            Db::name("revenue_rate")->insert(["revenue_rate" => $rate, "date" => $date]);
        }
    }

    private function getnewtarspend($appid, $start, $end)
    {
        $total = "0.00";
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $spend = $this->getspendtotal($appid, $v, $v, "all", "all");
            if ($appid == 77) {
                $w_data = $this->getCopartnerWork($appid, $v, $v);
                if (!empty($w_data)) {
                    if ($w_data["spend"] > 0) {
                        $spend["spend"] = $w_data["spend"];
                    }
                }
				if (strtotime($v) >= strtotime("2020-12-22")) {
                      $spend["spend"] = round($spend["spend"]*1.15,2);
				}
            }
            $total += $spend["spend"];
        }
        return $total;
    }

    public function getSetInfo($date = "")
    {
        $data = ["date" => $date, "server_spend" => "0.00", "other_spend" => "0.00", "other_spend1" => "0.00", "rate" => ""];
        if ($date) {
            $appid = getcache("select_app");
            $res = Db::name("copartner_month")->field("date,server_spend,other_spend,other_spend1,spend_desc1")->where("app_id={$appid} and date='{$date}'")->find();
            if (!empty($res)) {
                $data = $res;
            }
            $r = Db::name("revenue_rate")->field("revenue_rate")->where("date='{$date}'")->find();
            if (!empty($r)) {
                $data["rate"] = $r["revenue_rate"];
            }
        }
        echo json_encode($data);
        exit;
    }

    //合作组账单报表
    public function copartner_report()
    {
        $appid = getcache("select_app");
        $current_month = date("Y-m", time());
        $current_date = $current_month . "-01";
        $copartner_rate = getrevenue_rate($appid) / 100;
        $total_data = ["name" => "总计", "revenue" => "0.00", "spend" => "0.00", "roi" => "0", "may_revenue" => "0.00", "status" => "-1", "desc" => "", "money" => "0.00"];
        $result = Db::name("copartner_month")->where("app_id={$appid} and date<='{$current_date}'")->order('date asc')->select();
		
        $out_data = [];
        if (!empty($result)) {
            foreach ($result as $k => $vv) {
                $name = date("Y.m", strtotime($vv["date"]));
                $revenue = "0.00";
                $ads_spend = "0.00";
                if ($vv["date"] == "2019-03-01") {
                    $name = "截止到2019.03";
                    $revenue = $vv["revenue"];
                    $ads_spend = $vv["ads_spend"];
                } else {
                    $lastday = date('Y-m-d', strtotime("{$vv['date']} +1 month -1 day"));
                    if (strtotime($vv["date"]) < strtotime("2020-01-01")) {
                        $revenue = $this->dealworkData($appid, $vv["date"], $lastday, $this->getEveryRate($vv["date"]));
                    } else {
                        if ($vv["date"] == '2020-01-01') {
                            $lastday = '2020-01-31';
                        }
                        $rev = $this->getnewrevenuetotal($appid, $vv["date"], $lastday, "all", "all");
                        $revenue = $rev["total"]["revenue"] + $rev["total"]["purchase"];
                    }
                    if ($appid == 77) {
                        $ads_spend = $this->getnewtarspend($appid, $vv["date"], $lastday);
                    } else {
                        $s_data = $this->getspendtotal($appid, $vv["date"], $lastday, "all", "all");
                        $ads_spend = $s_data["spend"];
                    }
                    if ($appid == 112) {
                        $w_data = $this->getCopartnerWork($appid, $vv["date"], $lastday);
                        if (!empty($w_data)) {
                            $revenue = $w_data["revenue"];
                            $ads_spend = $w_data["spend"];
                        }
                    }
                    if ($appid == 127 && $vv["date"] == "2019-07-01") {
                        $admobs = $this->dealadmobrevenue($appid, "2019-07-29", "2019-07-31", "all", "");
                        $revenue = ($revenue - $admobs["revenue"]) > 0 ? $revenue - $admobs["revenue"] : 0;
                    }
                }
				$revenue =round($revenue,2);
				
                $spend =$ads_spend + $vv["server_spend"] + $vv["other_spend"] + $vv["other_spend1"];
				
                $roi = $spend > 0 ? round($revenue * 100 / $spend, 2) : "0";
				
                $may_revenue = "0.00";
                if ($vv["status"] == 2) {
                    $may_revenue = $vv["save_revenue"];
                } else {
                    $may_revenue = round(($revenue - $spend) * $copartner_rate, 2);
                }

                $total_data["revenue"] += $revenue;
                $total_data["spend"] += $spend;
                $total_data["may_revenue"] += $may_revenue;
                $total_data["money"] += $vv["money"];
                $out_data[$k] = ["id" => $vv["id"], "name" => $name, "may_revenue" => $may_revenue, "revenue" => $revenue, "ads_spend" => round($ads_spend, 2), "server_spend" => $vv["server_spend"], "other_spend1" => $vv["other_spend1"], "spend_desc1" => $vv["spend_desc1"], "other_spend" => $vv["other_spend"], "roi" => $roi, "spend" => round($spend, 2), "desc" => $vv["desc"], "money" => $vv["money"], "status" => $vv["status"]];
            }
        }
        $total_data["roi"] = $total_data["spend"] > 0 ? round($total_data["revenue"] * 100 / $total_data["spend"], 2) : "0";
        $total_data["may_revenue"] = round($total_data["may_revenue"], 2);
        $total_data["revenue"] = round($total_data["revenue"], 2);
        $total_data["spend"] = round($total_data["spend"], 2);
        array_push($out_data, $total_data);
        echo json_encode($out_data);
        exit;
    }

    public function getCopartnerbefore()
    {
        $appid = getcache("select_app");
        $date = "2019-05-01";
        $revenue = "0.00";
        $spend = "0.00";
        $co_total_data = $this->getcopartnerdata($appid, 1);
        $spend += $co_total_data["spend"];
        $revenue += $co_total_data["revenue"];
        $s_data = $this->getspendtotal($appid, "2018-09-01", "2019-03-31", "all", "all");
        $spend += $s_data["spend"];
        $revenue += $this->dealworkData($appid, "2018-09-01", "2019-03-31", "0.144944");
        return ["revenue" => $revenue, "spend" => $spend];
    }

    //合作方 结算数据处理
    private function dealworkData($appid, $start, $end, $month_rate = "")
    {
        $revenue = "0.00";
        $admob_revenue = "0.00";
        $upltv_revenue = "0.00";
        $exchange_revenue = "0.00";
        $repeat_revenue = "0.00";
        $rate_revenue = "0.00";
        $real_exchange_revenue = "0.00";

        if ($month_rate == "") {
            $month_rate = $this->getCoMonthRate($appid, $start, $end);
        }
        $data5 = Db::name('adcash_data')->field("sum(revenue) as revenue")->where(" sys_app_id={$appid} and date>='{$start}' and date<='{$end}' and platform not in(5,30)")->find();
        if (!empty($data5)) {
            $revenue = $data5["revenue"] > 0 ? round($data5["revenue"], 2) : "0.00";
        }
        //admob 收益
        $data1 = Db::name('adcash_data')->field("sum(revenue) as revenue")->where(" sys_app_id={$appid} and date>='{$start}' and date<='{$end}' and platform=5")->find();
        if (!empty($data1)) {
            $admob_revenue = $data1["revenue"] > 0 ? round($data1["revenue"] * 0.99, 2) : "0.00";
            if (in_array($appid, [127, 130]) && strtotime("2019-07-29 00:00:00") <= strtotime($start)) {
                if (strtotime($start) >= strtotime("2019-08-01") && strtotime($start) >= strtotime("2019-08-31")) {
                    $admob_revenue = round($admob_revenue * 0.81, 2);
                } elseif (strtotime($start) >= strtotime("2019-09-01") && strtotime($start) >= strtotime("2019-09-30")) {
                    $admob_revenue = round($admob_revenue * 0.92, 2);
                } elseif (strtotime($start) >= strtotime("2019-10-01")) {
                    $admob_revenue = round($admob_revenue * 0.92, 2);
                } else {
                    $admob_revenue = round($admob_revenue * 0.7, 2);
                }
            } elseif (strtotime($start) >= strtotime("2020-02-01")) {
                $admob_revenue = round($admob_revenue * 0.92, 2);
            }
        }
        $data2 = Db::name('adcash_data')->field("sum(revenue) as revenue")->where(" sys_app_id={$appid} and date>='{$start}' and date<='{$end}' and platform=30")->find();
        if (!empty($data2)) {
            $upltv_revenue = $data2["revenue"] > 0 ? round($data2["revenue"], 2) : "0.00";
        }

        $data3 = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as revenue")->where("app_id={$appid} and  date>='{$start}' and date<='{$end}' and type in(1,2,3)")->find();
        if (!empty($data3)) {
            $exchange_revenue = $data3["revenue"] > 0 ? round($data3["revenue"], 2) : "0.00";
            $real_exchange_revenue = round(($exchange_revenue / 0.1483) * $month_rate, 2);
        }
        $data4 = getupltvfacebook($appid, $start, $end, "all");
        if (!empty($data4)) {
            $repeat_revenue = $data4["revenue"] > 0 ? round($data4["revenue"], 2) : "0.00";
        }
        $rate_revenue = $this->getGdtTax($appid, $start, $end);
        $rate_revenue = round(($rate_revenue / 0.1483) * $month_rate, 2);
        $purchase_revenue = $this->getpurchase($appid, $start, $end, "all");
        $purchase_revenue = round($purchase_revenue * 0.98, 2);
        $a = $upltv_revenue - $exchange_revenue - $repeat_revenue - $rate_revenue + $real_exchange_revenue;
        $real_upltv_revenue = $a < 0 ? "0.00" : round($a, 2);
        $revenue = $revenue + $purchase_revenue + $real_upltv_revenue + $admob_revenue;
        return $revenue > 0 ? round($revenue, 2) : "0.00";
    }

    //获取每个月汇率
    private function getCoMonthRate($appid, $start, $end)
    {

        if ($start == $end) {
            if (strtotime($start) <= strtotime("2019-05-31 23:59:59")) {
                return "0.144944";
            } else {
                $rate = "0.00";
                $month = date("Y-m", strtotime($start)) . "-01";
                $data = Db::name("revenue_rate")->field("revenue_rate")->where(["date" => $month])->find();
                if (!empty($data) && isset($data["revenue_rate"]) && $data["revenue_rate"] > 0) {
                    $rate = $data["revenue_rate"];
                } else {
                    $rate = "0.144944";
                }
                return $rate;
            }
        } else {
            return "0.144944";
        }
    }

    private function getEveryRate($month)
    {
        $rate = "0.144944";
        $data = Db::name("revenue_rate")->field("revenue_rate")->where(["date" => $month])->find();
        if (!empty($data) && isset($data["revenue_rate"]) && $data["revenue_rate"] > 0) {
            $rate = $data["revenue_rate"];
        }
        return $rate;
    }

    private function getrangetotalday($appid, $start = "", $end = "", $country)
    {
        $out_data = [];
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $out_data[$k]["date"] = $v;
            $out_data[$k]["result"] = $this->gettotaldata($appid, $v, $v, $country);
            $out_data[$k]["advertiser_showtips"] = isshowtips($appid, $v, "advertiser");
            $out_data[$k]["publisher_showtips"] = isshowtips($appid, $v, "publisher");
            $out_data[$k]["producter_showtips"] = isshowtips($appid, $v, "producter");
        }
        return $out_data;
    }

    //新增内购收益
    private function getpurchasev1($appid = "", $start = "", $end, $country = "all")
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $purchase_sql = "select round(sum(total_usd_money),2) as revenue from hellowd_purchase_details where {$where}";
        $d = Db::query($purchase_sql);
        if (!empty($d)) {
            $platform = Db::name("app")->where(['id'=>$appid])->value('platform');
            $ratio = $platform=='android'?0.7:1;
            return $d[0]["revenue"] ? $d[0]["revenue"]*$ratio : false;
        }
        return false;
    }

    //内购收益
    private function getpurchase($appid = "", $start = "", $end, $country = "all")
    {
        if (strtotime($start) >= strtotime("2020-06-01")) {
            $purchase = $this->getpurchasev1($appid, $start, $end, $country);
            if ($purchase) {
                return $purchase;
            }
        }
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $purchase_sql = "select round(sum(revenue),2) as revenue from hellowd_purchase_data where {$where}";
        $d = Db::query($purchase_sql);
        if (!empty($d)) {
            return $d[0]["revenue"] ? $d[0]["revenue"] : "0.00";
        }
        return "0.00";
    }

    //upltv收益
    private function getupltve($appid = "", $start = "", $end, $country = "all")
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $ltv_sql = "select round(sum(revenue),2) as revenue from hellowd_upltv_data where {$where}";
        $d = Db::query($ltv_sql);
        if (!empty($d)) {
            return $d[0]["revenue"] ? $d[0]["revenue"] : "0.00";
        }
        return "0.00";
    }

    //广点通收益扣税 和 Sigmob
    private function getGdtTax($appid, $start, $end)
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and type in(2,3)";
        $gdt = Db::name("upltv_toutiao")->field("round(sum(original_revenue),2) as original_revenue")->where($where)->find();
        if (!empty($gdt) && isset($gdt["original_revenue"]) && $gdt["original_revenue"] > 0) {
            return round($gdt["original_revenue"] * 0.0634, 2);
        }
        return 0;
    }

    private function count_days($date)
    {
        $now = strtotime("-1 day");
        $date = strtotime($date);
        return round(($now - $date) / 3600 / 24);
    }

    public function gettotaldata($appid = "", $start = "", $end, $country = "", $from = "1")
    {
        $revenue =$this->getnewrevenuetotalv1($appid, $start, $end,$country);//$this->getrevenuetotal($appid, $start, $end, "all", $country);
        $spend = $this->getspendtotal($appid, $start, $end, "all", $country);
        //新增
        $new_users = $this->getnew_users($appid, $start, $end, $country);
		$adjust_new_users = $new_users;
        if ($spend["installs"] > $new_users) {
            $new_users = $spend["installs"];
        }
        $spend["cpi"] = $new_users <= 0 ? "0.00" : number_format($spend["spend"] / $new_users, 2);
		$spend["ad_cpi"] = $spend["installs"] <= 0 ? "0.00" : number_format($spend["spend"] /$spend["installs"], 2);
        //内购收益
        $purchase = $this->getpurchase($appid, $start, $end, $country);

        $revenue["total"]["revenue"] = $revenue["total"]["revenue"] + $purchase;

        $Appsflyer = new Appsflyer(request());
      
        $ltv_total_revenue = $Appsflyer->get_ltv_roi($start,$country,$appid);
		$ltv_revenue = $ltv_total_revenue["ad_revenue"]+$ltv_total_revenue["purchase"];
        $ltv0 = $spend["installs"]>0?round($ltv_revenue/$spend["installs"],3):'0.00';
		$ltv_ad0 = $spend["installs"]>0?round($ltv_total_revenue["ad_revenue"]/$spend["installs"],3):'0.00';
		$ltv_pr0 = $spend["installs"]>0?round($ltv_total_revenue["purchase"]/$spend["installs"],3):'0.00';
        $roi0 = $spend["spend"]>0?round($ltv_revenue*100/$spend["spend"],2):'0';
        //留存
        $reten = $this->getreten($appid, $start, $end, $country);
        //roi计算
        $roi = $spend["spend"] <= 0 ? "0" : round($revenue["total"]["revenue"] * 100 / $spend["spend"], 2);
        //自然量
        $nature_num = ($new_users - $spend["installs"]) < 0 ? 0 : $new_users - $spend["installs"];
        //自然占比
        $nature_rat = $new_users <= 0 ? "0" : number_format($nature_num * 100 / $new_users, 0);
        $ctr_spend = ctrSepend($start,$end,$appid,$country);
        return ["purchase" => $purchase,"ctr_spend"=>$ctr_spend,"adjust_new_users"=>$adjust_new_users,"roi" => $roi, "reten" => $reten, "new_users" => $new_users, "revenue" => $revenue, "spend" => $spend, "nature_num" => $nature_num, "nature_rat" => $nature_rat,"ltv_ad_revenue"=>$ltv_total_revenue["ad_revenue"],"ltv_purchase"=>$ltv_total_revenue["purchase"],"ltv_ad0"=>$ltv_ad0,"ltv_pr0"=>$ltv_pr0,"ltv0" => $ltv0,"ltv_revenue"=>$ltv_revenue,"roi0" => $roi0];
    }

    //work you dream 数据添加
    public function addProductData($date = "", $appid = "", $v = "", $type = "")
    {
        if ($v != "") {
            if ($type == "revenue") {
                //ROI 90%概率控制在80%~100%，5%概率控制在 75%~80%，5%概率控制在 100%~105%
                $roi = $this->creatRoi();
                if ($v > 0) {
                    $spend = round($v * 100 / $roi, 2);
                } else {
                    $spend = "0";
                }
                $r = Db::name("walk_data")->where(["appid" => $appid, "country" => "all", "date" => $date])->find();
                if (!empty($r)) {
                    Db::name("walk_data")->where(["id" => $r["id"]])->update(["spend" => $spend, "revenue" => $v]);
                } else {
                    Db::name("walk_data")->insert(["spend" => $spend, "revenue" => $v, "country" => "all", "date" => $date, "appid" => $appid]);
                }
            } elseif ($type == "spend") {
                $r = Db::name("walk_data")->where(["appid" => $appid, "country" => "all", "date" => $date])->find();
                if (!empty($r)) {
                    Db::name("walk_data")->where(["id" => $r["id"]])->update(["spend" => $v]);
                } else {
                    Db::name("walk_data")->insert(["spend" => $v, "revenue" => "0.00", "country" => "all", "date" => $date, "appid" => $appid]);
                }
            }
        }
        exit("ok");
    }

    //生成Roi
    private function creatRoi()
    {
        $D = [];
        $i = 1;
        while ($i <= 90) {
            $D[] = $this->randomFloat(80, 100);
            $i++;
        }
        $i = 1;
        while ($i <= 5) {
            $D[] = $this->randomFloat(75, 80);
            $i++;
        }
        $i = 1;
        while ($i <= 5) {
            $D[] = $this->randomFloat(100, 105);
            $i++;
        }
        shuffle($D);
        return $D[rand(0, 99)];
    }

    function randomFloat($min = 0, $max = 1)
    {
        $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return sprintf("%.2f", $num);
    }

    //收益
    public function revenue($appid = "", $start = "", $end = "", $platform = "all", $country = "all")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        $userinfo = getuserinfo();
        $allow_list = ["producter", "super", "publisher", "advertiser", "financer"];
        if (!in_array($userinfo["ad_role"], $allow_list)) {
            exit("You do not have permission to access, please contact the system administrator");
        }

        if ($userinfo["ad_role"] == "producter") {
            $app_list = explode(",", $userinfo['allow_applist']);
            if (!in_array($appid, $app_list)) {
                exit("You do not have permission to access, please contact the system administrator");
            }
        }
        setcache("select_app", $appid);
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-10 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }
        $lastend = date("Y-m-d", (strtotime($end) - 3600 * 24));

        $data = $this->getrevenuetotal($appid, $end, $end, $platform, $country);

        $lastdata = $this->getrevenuetotal($appid, $lastend, $lastend, $platform, $country);

        $list = $this->getrangerevenueday($appid, $start, $end, $platform, $country);
        $chats_data = $this->getEcpmArpdau($list);
        $total_data = $this->getrevenuetotal($appid, $start, $end, $platform, $country);
		$Appsflyer = new  Appsflyer(request());
		$new_users = $this->getnew_users($appid,$start, $end, $country);
		$row = $Appsflyer->get_avg_show($appid,$start,$end,$country);
		$total_data["new_users_avg_rew_show"] = $new_users>0?round($row["rew_show"]/$new_users,2):0;
		$total_data["new_users_avg_int_show"] = $new_users>0?round($row["int_show"]/$new_users,2):0;
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("list", $list);
        $this->assign("chats_data", $chats_data);
        $this->assign("total_data", $total_data);
        $this->assign("appid", $appid);
        $this->assign("data", $data);
        $this->assign("lastdata", $lastdata);
        $all_country = admincountry();
        $this->assign("current_country", isset($all_country[$country]) ? $all_country[$country] : "全部国家");
        $this->assign("country", $all_country);
        return $this->fetch('revenue');
    }

    private function getEcpmArpdau($data)
    {
        $date = "";
        $avg_int = "";
        $avg_rew = "";
        if (!empty($data)) {
            foreach ($data as $key => $vv) {
                $vv["date"] = date("m月d日", strtotime($vv["date"]));
                $date .= "'{$vv["date"]}',";
                $avg_int .= $vv["result"]["int"]['avgshow'] . ",";
                $avg_rew .= $vv["result"]["rew"]['avgshow'] . ",";
            }
            $avg_int = rtrim($avg_int, ",");
            $avg_rew = rtrim($avg_rew, ",");
        }
        return ["date" => rtrim($date, ","), "avg_int" => $avg_int, "avg_rew" => $avg_rew];
    }

    public function revenue_json($start = "", $end = "", $platform = "all", $country = "all")
    {
        $appid = getcache("select_app");
        $list = $this->getrangerevenueday($appid, $start, $end, $platform, $country);
        $total_data = $this->getrevenuetotal($appid, $start, $end, $platform, $country);
		$Appsflyer = new  Appsflyer(request());
		$new_users = $this->getnew_users($appid,$start,$end,$country);
		$row = $Appsflyer->get_avg_show($appid,$start,$end,$country);
		$total_data["new_users_avg_rew_show"] = $new_users>0?round($row["rew_show"]/$new_users,2):0;
		$total_data["new_users_avg_int_show"] = $new_users>0?round($row["int_show"]/$new_users,2):0;
        $this->assign("total_data", $total_data);
        $this->assign("list", $list);
        return $this->fetch('revenue_json');
    }

    public function spend_json($start = "", $end = "", $platform = "all", $country = "all")
    {
        $appid = getcache("select_app");
        $list = $this->getRangeSpendByplatform($appid, $start, $end, $country);
        $this->assign("list", $list);
        $names = getcolumname($appid);
        $total_data = $this->getrangtotalspend($list, $appid);
        $this->assign("total_data", $total_data);
        $this->assign("names", $names);
        return $this->fetch('spend_json');
    }

    private function getrangerevenueday($appid, $start = "", $end = "", $platform = "all", $country = "all")
    {
        $out_data = [];
		$Appsflyer = new  Appsflyer(request());
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $out_data[$k]["date"] = $v;
            $out_data[$k]["result"] = $this->getrevenuetotal($appid, $v, $v, $platform, $country);
			$new_users = $this->getnew_users($appid, $v, $v, $country);
			$row = $Appsflyer->get_avg_show($appid,$v,$v,$country);
			$out_data[$k]["new_users_avg_rew_show"] = $new_users>0?round($row["rew_show"]/$new_users,2):0;
			$out_data[$k]["new_users_avg_int_show"] = $new_users>0?round($row["int_show"]/$new_users,2):0;
            $out_data[$k]["new_users"] = $new_users;
            $out_data[$k]["publisher_showtips"] = isshowtips($appid, $v, "publisher");
        }
        return $out_data;
    }

    private function getrangespendday($appid, $start = "", $end = "", $platform = "all", $country)
    {
        $out_data = [];
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $out_data[$k]["date"] = $v;
            $out_data[$k]["result"] = $this->getspendtotal($appid, $v, $v, $platform, $country);
        }
        return $out_data;
    }

    private function getRangeSpendByplatform($appid, $start = "", $end = "", $country)
    {
        $out_data = [];
        $dates = getDateFromRange($start, $end);
        foreach ($dates as $k => $v) {
            $out_data[$k]["date"] = $v;
            $out_data[$k]["result"] = $this->getSpendByplatform($appid, $v, $v, $country);
            $out_data[$k]["advertiser_showtips"] = isshowtips($appid, $v, "advertiser");
        }

        return $out_data;
    }

    //花费平台全部筛选
    private function getSpendByplatform($appid, $start = "", $end = "", $country)
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $out_data = [
            "6" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "5" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "3" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "2" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "4" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "8" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "31" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "1" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "9" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "7" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "32" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "36" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "38" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "39" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
			"42" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"]
        ];
        $sum_sql = "select platform_type,sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where} group by platform_type";
        $d = Db::query($sum_sql);
        if (!empty($d)) {
            foreach ($d as $vv) {
                $spend = $vv["spend"] ? $vv["spend"] : "0.0";
                $installs = $vv["installs"] ? $vv["installs"] : 0;
                $cpi = $vv["installs"] <= 0 ? "0.0" : number_format($vv["spend"] / $vv["installs"], 2);
                $out_data[$vv["platform_type"]] = ["installs" => $installs, "spend" => $spend, "cpi" => $cpi];
            }
        }
        $out_data = $this->getControlPlatform($out_data, $appid, $start, $end, $country);

        $out_data["total"] = $this->getspendtotal($appid, $start, $end, "all", $country);
        return $out_data;
    }

    private function getControlPlatform(&$out_data, $appid, $start = "", $end = "", $country)
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $names = getcolumname($appid);
        if (!empty($names)) {
            foreach ($names as $vv) {
                $where1 = " and platform=" . $vv["platform_id"];
                $con_sql = "select platform,sum(installs) as installs,round(sum(spend),2) as spend from hellowd_control_data where {$where} {$where1}";

                $d = Db::query($con_sql);
                if (!empty($d)) {
                    $d = $d[0];
                    $spend = $d["spend"] ? $d["spend"] : "0.00";
                    $installs = $d["installs"] ? $d["installs"] : 0;
                    $cpi = $installs <= 0 ? "0.00" : number_format($spend / $installs, 2);
                    $out_data[$vv["platform_id"]] = ["installs" => $installs, "spend" => $spend, "cpi" => $cpi];

                }
            }
        }
        return $out_data;
    }
	
	public function testa(){
		$r = Db::name("app")->field("id")->where("status=1")->select();
		$total="0.00";
		{
			foreach( $r as $key=>$v )
			{
			   
			   $data = $this->getnewrevenuetotal($v["id"],"2021-01-01","2021-01-31","7","all");
			   $total+=$data["total"]["revenue"];
			}
		}
		echo $total;exit;
	}
	
	//新增收益汇总
	private function getnewrevenuetotalv1($appid, $start = "", $end = "",$country = "all"){
		 $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		 if ($country != "all") {
            $country =trim($country);
			$where .= " and country='{$country}'";
		  }
		 $active_users = $this->getactive_users($appid, $start, $end, $country);
		 $revenue ="0.00";
		 $month_start = date("Y-m", strtotime($start));
		 $month_end = date("Y-m", strtotime($end));
		 $res = Db::name("main_data")->field('channel,sum(ad_revenue) as revenue')->where($where)->group("channel")->select();
		
		 if(!empty($res))
		 {
			 foreach($res as $v)
			 {
				if ($month_start == $month_end) {
                    $rate = $this->getNewMonthRate($appid, $v["channel"], $month_start);
                    $v["revenue"] = round($v["revenue"] * $rate, 2);
                }               
                $revenue += $v["revenue"];
			 }
		 }
		 $purchase = $this->getpurchase($appid, $start, $end, $country);
		 $daynum = count(getDateFromRange($start, $end));
		 $dauarpu = $active_users <= 0 ? "0.00" : number_format(($revenue + $purchase) / ($active_users * $daynum), 3);
		 $r["total"] = ["revenue" => round($revenue, 2),"dauarpu" => $dauarpu,"active_users" => $active_users];
        return $r;
	}

    private function getnewrevenuetotal($appid, $start = "", $end = "", $platform = "all", $country = "all")
    {
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($platform != "all") {
            $where .= " and platform={$platform}";
        }
        if ($country != "all") {
            $country =trim($country);
			$where .= " and country='{$country}'";
        }
        $active_users = $this->getactive_users($appid, $start, $end, $country);

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
			"sp" => array
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
                "avgshow" => 0,
                "dauarpu" => "0.0",
                "active_users" => 0
            )

        );
        $impressions = 0;
        $revenue = "0.00";
        $sum_sql = "select platform,if(adtype!='',adtype,'no') as adtype,sum(impression) as impressions,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} group by adtype,platform";
        $res = Db::query($sum_sql);
        $month_start = date("Y-m", strtotime($start));
        $month_end = date("Y-m", strtotime($end));
        $daynum = count(getDateFromRange($start, $end));
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
                        $vv["avgshow"] = $active_users <= 0 ? 0 : number_format($vv["impressions"] / ($active_users * $daynum), 2);
                    }
                }
            }
        }
        $ecpm = $impressions <= 0 ? 0 : number_format($revenue * 1000 / $impressions, 2);
        $avgshow = $active_users <= 0 ? 0 : number_format($impressions / ($active_users * $daynum), 2);
        $purchase = $this->getpurchase($appid, $start, $end, $country);
        if (strtotime($start) >= strtotime("2020-05-01") && in_array($country, ["all", "CN"]) && in_array($platform, ["all", "30"])) {
            $revenue = $revenue - ($this->getGdtTax($appid, $start, $end));
        }
        $dauarpu = $active_users <= 0 ? "0.00" : number_format(($revenue + $purchase) / ($active_users * $daynum), 3);
        $ad_arpu = $active_users <= 0 ? "0.00" : number_format($revenue / ($active_users * $daynum), 3);
        $pr_arpu = $active_users <= 0 ? "0.00" : number_format($purchase / ($active_users * $daynum), 3);
        $r["total"] = ["impressions" => $impressions, "purchase" => $purchase, "revenue" => round($revenue, 2), "ecpm" => $ecpm, "avgshow" => $avgshow, "dauarpu" => $dauarpu, "ad_arpu" => $ad_arpu, "pr_arpu" => $pr_arpu, "active_users" => $active_users];
        return $r;
    }

    private function getNewMonthRate($appid, $channel, $month)
    {
        if ($channel == "34" || $channel == "35") {
            $channel = "cny";//人民币渠道
        }
        $row = Db::name("rate")->field('val')->where(["app_id" => $appid, "channel" => $channel, "is_default" => 0, "month" => $month])->find();
        if (empty($row)) {
            $row = Db::name("rate")->field('val')->where(["channel" => $channel, "is_default" => 1, "month" => $month])->find();
            if (empty($row)) {
                if ($channel == "cny") {
                    return round((0.147059 / 0.141085) * 0.9366, 5);
                }
                return $channel == 5 ? "0.92" : "1";
            }
        }
        if ($channel == "cny") {
            return round(($row["val"] / 0.141085) * 0.9366, 5);//按固定汇率计算 6.8  GDT，sigmob的税差是0.0634
        }
		
        return $row["val"];
    }

    public function getrevenuetotal($appid, $start = "", $end = "", $platform = "all", $country = "all")
    {
        return $this->getnewrevenuetotal($appid, $start, $end, $platform, $country);
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($platform != "all") {
            $where .= " and platform={$platform}";
        }
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $active_users = $this->getactive_users($appid, $start, $end, $country);

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
                "avgshow" => 0,
                "dauarpu" => "0.0",
                "active_users" => 0
            )

        );
        $impressions = 0;
        $revenue = "0.00";
        $sum_sql = "select if(adtype!='',adtype,'no') as adtype,sum(impression) as impressions,round(sum(revenue),2) as revenue from hellowd_adcash_data where {$where} group by adtype";
        $d = Db::query($sum_sql);
        if (!empty($d)) {
            foreach ($d as &$v) {
                if ($platform == "all" || $platform == "30") {
                    $upltv_adtype_data = getupltvfacebook($appid, $start, $end, $country, $v["adtype"]);
                    $v["revenue"] = ($v["revenue"] - $upltv_adtype_data["revenue"]) < 0 ? "0.00" : $v["revenue"] - $upltv_adtype_data["revenue"];
                    $v["impressions"] = ($v["impressions"] - $upltv_adtype_data["impression"]) < 0 ? 0 : $v["impressions"] - $upltv_adtype_data["impression"];
                }
                if ($platform == "all" || $platform == "5") {
                    if ((in_array($appid, [127, 130])) && strtotime($start) >= strtotime("2019-07-29")) {
                        $admob = $this->dealadmobrevenue($appid, $start, $end, $country, $v["adtype"]);
                        $v["revenue"] = ($v["revenue"] - $admob["revenue"]) < 0 ? "0.00" : $v["revenue"] - $admob["revenue"];
                        $v["impressions"] = ($v["impressions"] - $admob["impression"]) < 0 ? 0 : $v["impressions"] - $admob["impression"];
                    } elseif (strtotime($start) >= strtotime("2020-02-01")) {
                        $admob = $this->dealadmobrevenue($appid, $start, $end, $country, $v["adtype"]);
                        $v["revenue"] = ($v["revenue"] - $admob["revenue"]) < 0 ? "0.00" : $v["revenue"] - $admob["revenue"];
                        $v["impressions"] = ($v["impressions"] - $admob["impression"]) < 0 ? 0 : $v["impressions"] - $admob["impression"];
                    }
                }
                $v["ecpm"] = $v["impressions"] <= 0 ? 0 : number_format($v["revenue"] * 1000 / $v["impressions"], 2);
                $v["avgshow"] = $active_users <= 0 ? 0 : number_format($v["impressions"] / $active_users, 2);
                $r[$v["adtype"]] = ["impressions" => $v["impressions"], "revenue" => $v["revenue"], "ecpm" => $v["ecpm"], "avgshow" => $v["avgshow"]];
                $impressions += $v["impressions"];
                $revenue += $v["revenue"];
            }
        }

        $ecpm = $impressions <= 0 ? 0 : number_format($revenue * 1000 / $impressions, 2);
        $daynum = count(getDateFromRange($start, $end));
        $avgshow = $active_users <= 0 ? 0 : number_format($impressions / ($active_users * $daynum), 2);
        $purchase = $this->getpurchase($appid, $start, $end, $country);
        //DAUARPU
        if (strtotime($start) >= strtotime("2019-03-01") && in_array($country, ["all", "CN"]) && in_array($platform, ["all", "30"])) {
            $revenue = $revenue - ($this->getGdtTax($appid, $start, $end));
        }
        $dauarpu = $active_users <= 0 ? "0.00" : number_format(($revenue + $purchase) / ($active_users * $daynum), 3);
        $r["total"] = ["impressions" => $impressions, "purchase" => $purchase, "revenue" => round($revenue, 2), "ecpm" => $ecpm, "avgshow" => $avgshow, "dauarpu" => $dauarpu, "active_users" => $active_users];
        return $r;
    }

    private function dealadmobrevenue($appid, $start = "", $end = "", $country = "all", $adtype = "")
    {
        $where = "sys_app_id={$appid} and  date>='{$start}' and date<='{$end}' and platform=5";

        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($adtype != "") {
            $where .= " and adtype='{$adtype}'";
        }
        $res = Db::name("adcash_data")->field("round(sum(revenue),2) as revenue,sum(impression) as impressions")->where($where)->find();
        $impression = isset($res["impression"]) && !empty($res["impression"]) ? $res["impression"] : 0;
        $revenue = isset($res["revenue"]) && !empty($res["revenue"]) ? $res["revenue"] : 0;
        if (strtotime($start) >= strtotime("2019-07-29")) {
            if (strtotime($start) >= strtotime("2019-08-01") && strtotime($start) <= strtotime("2019-08-31")) {
                return ["impression" => round($impression * 0.19, 2), "revenue" => round($revenue * 0.19, 2)];
            } elseif (strtotime($start) >= strtotime("2019-09-01") && strtotime($start) <= strtotime("2019-09-30")) {
                return ["impression" => round($impression * 0.08, 2), "revenue" => round($revenue * 0.08, 2)];
            } elseif (strtotime($start) >= strtotime("2019-10-01")) {
                return ["impression" => round($impression * 0.08, 2), "revenue" => round($revenue * 0.08, 2)];
            }
        }
    }

    public function getspendtotal($appid, $start = "", $end = "", $platform = "all", $country = "all")
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($platform != "all") {
            $where .= " and platform_type={$platform}";
        }
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        //$r=["installs"=>0,"spend"=>"0.00","cpi"=>"0.00"];
        $sum_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
        $d = Db::query($sum_sql);
        $con_data = $this->getcontroltotal($appid, $start, $end, $country);

        if (!empty($d)) {
            $d = $d[0];
            $spend = $d["spend"] ? $d["spend"] : "0.00";
            $installs = $d["installs"] ? $d["installs"] : 0;
            $con_data["spend"] = $con_data["spend"] + $spend;
            $con_data["installs"] = $con_data["installs"] + $installs;
        }
        $con_data["cpi"] = $con_data["installs"] <= 0 ? "0.0" : round($con_data["spend"] / $con_data["installs"], 2);
        return $con_data;
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
            $where .= " and platform='{$platform}'";
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

    private function get_by_country_spend($appid, $start = "", $end = "", $country = "all", $platform = "all")
    {
        $r = ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"];
        if ($platform == "all") {
            return $this->getspendtotal($appid, $start, $end, "all", $country);
        } else {
            if ($this->ischeistN($platform, $appid)) {
                return $this->getcontroltotal($appid, $start, $end, $country, $platform);
            }
            $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
            if ($platform != "all") {
                $where .= " and platform_type={$platform}";
            }
            if ($country != "all") {
                $where .= " and country='{$country}'";
            }
            $sum_sql = "select sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
            $d = Db::query($sum_sql);
            if (!empty($d)) {
                $d = $d[0];
                $r["spend"] = $d["spend"] ? round($d["spend"], 2) : "0.00";
                $r["installs"] = $d["installs"] ? round($d["installs"]) : 0;
            }
            $r["cpi"] = $r["installs"] <= 0 ? "0.0" : round($r["spend"] / $r["installs"], 2);
            return $r;
        }
    }

    private function ischeistN($platform, $appid)
    {
        $names = getcolumname($appid);
        if (!empty($names)) {
            foreach ($names as $n) {
                if ($platform == $n["platform_id"]) {
                    return true;
                }
            }
        }
        return false;
    }

    //日活
    private function getactive_users($appid, $start = "", $end = "", $country = "all")
    {

        if ($appid == 132) {
            $appid = 112;
        }
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $active_sql = "select ceil(avg(val)) as val from hellowd_active_users where {$where}";
        $d = Db::query($active_sql);

        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] : 0;
    }

    //tenjin活跃
    private function tenjindau($appid, $start = "", $end = "", $country = "all")
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $active_sql = "select sum(daily_active_users) as val from hellowd_tenjin_report where {$where}";
        $d = Db::query($active_sql);

        if (empty($d)) {
            return 0;
        }
        $dates = getDateFromRange($start, $end);
        $num = count($dates);
        return $d[0]["val"] ? ceil($d[0]["val"] / $num) : 0;
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

    //tenjin新增
    private function tenjininstalls($appid, $start = "", $end = "", $country = "all")
    {
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        $active_sql = "select sum(tracked_installs) as val from hellowd_tenjin_report where {$where}";
        $d = Db::query($active_sql);

        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] : 0;
    }

    //留存
    public function getreten($appid, $start = "", $end = "", $country = "all")
    {
        if ($appid == 132) {
            $appid = 112;
        }
        
        $start = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $end = date("Y-m-d", strtotime('+1 day', strtotime($end)));

        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $reten_sql = "select sum(retention_1) as val from hellowd_retention where {$where}";
        $d = Db::query($reten_sql);
        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] * 100 : 0;
    }

    private function getdayreten($appid, $start, $end, $country, $day)
    {
        if ($appid == 132) {
            $appid = 112;
        }
        $start = date("Y-m-d", strtotime("+1 day", strtotime($start)));
        $end = date("Y-m-d", strtotime("+1 day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $reten_sql = "select sum(retention_{$day}) as val from hellowd_retention where {$where}";
        $d = Db::query($reten_sql);
        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? $d[0]["val"] * 100 : 0;
    }

    //留存
    private function getallreten($appid, $start = "", $end = "", $country = "all")
    {
        $retention_7 = $this->getdayreten($appid, $start, $end, $country, 7);
        $retention_28 = $this->getdayreten($appid, $start, $end, $country, 28);
        return ["retention_7" => round($retention_7, 2), "retention_28" => round($retention_28, 2)];
    }

    public function by_country_spend($appid = "", $start = "", $end = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
            $end = date("Y-m-d", strtotime("-1 day"));
        }
        setcache("select_app", $appid);
        $platform = [
            ["id" => "0", "platform_id" => "all", "platform_name" => "全部"],
            ["id" => "0", "platform_id" => "6", "platform_name" => "Facebook"],
            ["id" => "0", "platform_id" => "5", "platform_name" => "Adwords"],
            ["id" => "0", "platform_id" => "3", "platform_name" => "Applovin"],
            ["id" => "0", "platform_id" => "2", "platform_name" => "UnityAds"],
            ["id" => "0", "platform_id" => "4", "platform_name" => "Vungle"],
            ["id" => "0", "platform_id" => "8", "platform_name" => "Chartboost"],
            ["id" => "0", "platform_id" => "31", "platform_name" => "Adcolony"],
            ["id" => "0", "platform_id" => "1", "platform_name" => "Mintegral"],
            ["id" => "0", "platform_id" => "9", "platform_name" => "Tapjoy"],
            ["id" => "0", "platform_id" => "7", "platform_name" => "ironSource"],
            ["id" => "0", "platform_id" => "32", "platform_name" => "Toutiao-Hellowd"],
            ["id" => "0", "platform_id" => "36", "platform_name" => "TikTok"],
            ["id" => "0", "platform_id" => "38", "platform_name" => "Snapchat"],
            ["id" => "0", "platform_id" => "39", "platform_name" => "ASM"],
			["id" => "0", "platform_id" => "42", "platform_name" => "Kuaishou"]
        ];
        $channelList = array_merge($platform, getcolumname($appid));
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("appid", $appid);
        $this->assign("channelList", $channelList);
        return $this->fetch('by_country_spend');
    }

    public function by_country_spend_json($appid = "", $date = [], $platform = "all")
    {
        $countrys = admincountry();
        $out_data = [];
        if (!empty($date)) {
            list($start, $end) = $date;
            foreach ($countrys as $k => $c) {
                $row = $this->get_by_country_spend($appid, $start, $end, $k, $platform);
                $row["name"] = $c;
                $out_data[] = $row;
            }
        }
        echo json_encode($out_data);
        exit;
    }

    //花费
    public function spend($appid = "", $start = "", $end = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }

        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        $userinfo = getuserinfo();
        $allow_list = ["producter", "super", "advertiser", "publisher"];
        if (!in_array($userinfo["ad_role"], $allow_list)) {
            exit("You do not have permission to access, please contact the system administrator");
        }

        if ($userinfo["ad_role"] == "producter") {
            $app_list = explode(",", $userinfo['allow_applist']);
            if (!in_array($appid, $app_list)) {
                exit("You do not have permission to access, please contact the system administrator");
            }
        }
        setcache("select_app", $appid);
        if ($start == "" || $end == "") {
            $start = date("Y-m-d", strtotime("-10 day"));
            $end = date("Y-m-d", strtotime("-2 day"));
        }

        $list = $this->getRangeSpendByplatform($appid, $start, $end, "all");
        $total_data = $this->getrangtotalspend($list, $appid);
        $chats_data = $this->getSpendInstalls($list);
        $names = getcolumname($appid);
        $this->assign("start", $start);
        $this->assign("end", $end);
        $this->assign("chats_data", $chats_data);
        $this->assign("total_data", $total_data);
        $this->assign("names", $names);
        $this->assign("appid", $appid);
        $this->assign("list", $list);
		$this->assign("notice",Db::name("spend_check")->where("app_id",$appid)->select());
        $this->assign("country", admincountry());
        return $this->fetch('spend', []);
    }

    private function getSpendInstalls($data)
    {
        $date = "";
        $spend = "";
        $installs = "";
        if (!empty($data)) {
            foreach ($data as $key => $vv) {
                $vv["date"] = date("m月d日", strtotime($vv["date"]));
                $date .= "'{$vv["date"]}',";
                $spend .= $vv["result"]["total"]['spend'] . ",";
                $installs .= $vv["result"]["total"]['installs'] . ",";
            }
            $spend = rtrim($spend, ",");
            $installs = rtrim($installs, ",");
        }
        return ["date" => rtrim($date, ","), "spend" => $spend, "installs" => $installs];
    }

    private function getrangtotalspend($list, $appid)
    {
        $out_data = [
            "6" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "5" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "3" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "2" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "4" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "8" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "31" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "1" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "9" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "7" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "32" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "36" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "38" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "39" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
			"42" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"],
            "total" => ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"]
        ];
        $names = getcolumname($appid);
        foreach ($names as $v) {
            $out_data[$v["platform_id"]] = ["installs" => 0, "spend" => "0.00", "cpi" => "0.00"];
        }

        foreach ($list as $vvv) {
            $child_data = $vvv["result"];
            foreach ($child_data as $k => $c_v) {
                $out_data[$k]["installs"] += $c_v["installs"];
                $out_data[$k]["spend"] += $c_v["spend"];
            }
        }
        foreach ($out_data as &$u) {
            $u["cpi"] = $u["installs"] <= 0 ? "0.0" : number_format($u["spend"] / $u["installs"], 2);
        }
        return $out_data;
    }

    private function arrtostring($arr, $t = "")
    {
        $str = "";
        foreach ($arr as $v) {
            if ($t == 1) {
                $str .= "{$v}" . ",";
            } else {
                $str .= "'{$v}'" . ",";
            }
        }
        return trim($str, ",");
    }

    public function select_app()
    {
        $userinfo = getuserinfo();
        $all_where = "1=1";
        if (!in_array($userinfo["ad_role"], ["super", "publisher", "advertiser"])) {
            if (!$userinfo['allow_applist']) {
                exit("You do not have permission to access");
            }
            $all_where = "id in({$userinfo['allow_applist']})";
        }
        $res = Db::name('app')->where($all_where)->limit(5)->order('id desc')->select();
        if (!empty($res)) {
            foreach ($res as &$vv) {
                if ($vv["id"] > 154) {
                    if ($vv["app_base_id"]) {
                        $row = Db::name("app_base")->where("id", $vv["app_base_id"])->find();
                        $vv["app_name"] = $row["name"] . ' - ' . $vv["platform"];
                        $vv["icon_url"] = $row["icon"];
                    }
                }
            }
        }
        $this->assign("res", $res);
        return $this->fetch();
    }


    private function getlog()
    {
        return Db::name('admin_log')->order("operate_time desc")->limit(5)->select();
    }

    //Excel导出
    public function export($start = "", $end = "", $country = "all")
    {

        $xlsName = "数据汇总";
        $tit = getapp_name() . "数据汇总";
        $xlsCell = array(
            array('date', '日期'),
            array('active_users', '活跃'),
            array('new_users', '新增'),
            array('reten', '次留'),
            array('nature_num', '自然量'),
            array('nature_rat', '自然占比'),
			array('purchase', '内购'),
            array('roi', 'ROI'),
            array('spend', '花费'),
            array('cpi', '平均CPI'),
            array('revenue', '收益'),
            array('dauarpu', 'ARPDAU')
        );
        $appid = getcache("select_app");
        $xlsData = [];
        $res = $this->getrangetotalday($appid, $start, $end, $country);
        if (!empty($res)) {
            foreach ($res as $key => $vv) {
                $arr = [
                    "date" => $vv["date"],
                    "active_users" => $vv["result"]["revenue"]["total"]["active_users"],
                    "new_users" => $vv["result"]["new_users"],
                    "reten" => $vv["result"]["reten"] . "%",
                    "nature_num" => $vv["result"]["nature_num"],
                    "nature_rat" => $vv["result"]["nature_rat"] . "%",
					"purchase" => $vv["result"]["purchase"],
                    "roi" => $vv["result"]["roi"] . "%",
                    "spend" => $vv["result"]["spend"]["spend"],
                    "cpi" => $vv["result"]["spend"]["cpi"],
                    "revenue" => $vv["result"]["revenue"]["total"]["revenue"],
                    "dauarpu" => $vv["result"]["revenue"]["total"]["dauarpu"],
                ];
                $xlsData[$key] = $arr;
            }
        }

        $this->exportExcel($xlsName, $xlsCell, $xlsData, $xlsName, $tit);

    }

    public function exportExcel($expTitle, $expCellName, $expTableData, $fileName, $tit)
    {


        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $fileName . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);

        import('phpexcel.PHPExcel', EXTEND_PATH);
        //实例化PHPExcel
        $objPHPExcel = new \PHPExcel();

        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH');

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(15);


        $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1:I1')->getFont()->setSize(20);
        $objPHPExcel->getActiveSheet()->getStyle('A2:AM2')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A2:AM2')->getFont()->setSize(15);


        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$objPHPExcel->getActiveSheet()->getStyle('A3:AM2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:K2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $objPHPExcel->getActiveSheet()->getStyle('A1:K1')->getFont()->getColor()->setARGB(\PHPExcel_Style_Color::COLOR_RED);
        // $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $tit.'  导出时间:'.date('Y-m-d H:i:s'));
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        ob_end_clean();//清除缓冲区,避免乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        vendor('PHPExcel.PHPExcel.IOFactory');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        $objWriter->save('php://output');
        exit;
    }

}
