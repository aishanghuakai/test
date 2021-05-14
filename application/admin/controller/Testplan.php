<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use \app\admin\controller\Index;
use think\Session;

class Testplan extends Base
{

    public function index($status = "all", $appid = "", $type = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        $userid = Session::get('admin_userid');
        $allstatus = array(
            "1" => 0,
            "2" => 0,
            "3" => 0
        );
        $res = Db::name("testplan")->field("status,count(*) as num")->where("app_id ={$appid}")->group("status")->select();
        if (!empty($res)) {
            foreach ($res as $vv) {
                if (isset($allstatus[$vv["status"]])) {
                    $allstatus[$vv["status"]] = $vv["num"];
                }
            }
        }
        if (!in_array($status, ["all", "1", "2", "3"])) {
            $status = "all";
        }
        $t = "";
        if ($type != "") {

            array_map(function ($v) use (&$t) {
                $t .= "'{$v}'" . ",";

            }, explode(",", $type));
        }
        $assign = array(
            "countrys" => admincountry(),
            "status" => $allstatus,
            "currentStatus" => $status,
            "type" => trim($t, ",")
        );

        $r = $this->getallData("", $status, $appid, $type);
        $assign["list"] = $r;
        if ($userid == 50) {
            $this->error('您暂无权限查看', "/admin_index/main");
            exit;
        }
        setcache("select_app", $appid);
        $assign["appid"] = $appid;
        $assign["userid"] = $userid;
        return $this->fetch('index', $assign);
    }

    private function getallData($id = "", $status = "all", $appid = "", $type = "")
    {
        $where = "1=1";
        if ($id != "") {
            $where .= " and id = {$id}";
        }
        if ($status != "all") {
            $where .= " and status = {$status}";
        }
        if ($appid != "") {
            $where .= " and app_id = {$appid}";
        }
        if ($type != "") {
            $where .= " and type in ({$type})";
        }
        $r = Db::name("testplan")->field("app_id")->where($where)->group("app_id")->order("id desc")->select();
        if (!empty($r)) {
            foreach ($r as &$vv) {

                $list = Db::name("testplan")->where("app_id=" . $vv["app_id"] . " and " . $where)->order("id asc")->select();
                $appData = Db::name("app")->field("id,icon_url,app_name,app_base_id,platform")->find($vv["app_id"]);
                if ($appData["id"] > 154) {
                    if ($appData["app_base_id"]) {
                        $row = Db::name("app_base")->where("id", $appData["app_base_id"])->find();
                        $appData["app_name"] = $row["name"] . ' - ' . $appData["platform"];
                        $appData["icon_url"] = $row["icon"];
                    }
                }
                $vv["name"] = $appData["app_name"];
                $vv["icon_url"] = $appData["icon_url"];
                if (!empty($list)) {
                    foreach ($list as &$vvv) {
                        if (time() > strtotime($vvv["start_date"]) && $vvv["status"] == "2") {
                            Db::name("testplan")->where(["id" => $vvv["id"]])->update(["status" => 1]);
                            $vvv["status"] = "1";
                        }
                        $vvv["new"] = false;
                        $last = strtotime("-1 day");
                        if ($vvv["status"] == "3" && ($last < strtotime($vvv["end_date"]))) {
                            $vvv["new"] = true;
                        }
                        $vvv["day"] = "";
                        if ($vvv["status"] == "1") {
                            $cu = date("Y-m-d", time());
                            if (time() < strtotime($vvv["end_date"] . " 00:00:00")) {
                                $vvv["day"] = count(getDateFromRange($vvv["end_date"], $cu));
                            } elseif (strtotime("-1 day") > strtotime($vvv["end_date"] . " 23:59:59")) {
                                $vvv["day"] = "已超期";
                            } else {
                                $vvv["day"] = "即将超期";
                            }
                        }
                        $nameData = Db::name("admin")->field("truename")->where(" id in ({$vvv['manager']}) ")->select();
                        $vvv["username"] = implode(",", array_column($nameData, "truename"));
                        $vvv["country"] = $this->getCountryString(admincountry(), $vvv["country"]);
                        $vvv["channel"] = $this->getChannelString($vvv["channel"]);
                        $vvv["start_date"] = date("m.d", strtotime($vvv["start_date"]));
                        $vvv["end_date"] = date("m.d", strtotime($vvv["end_date"]));
                    }
                }
                $vv["list"] = $list;
            }
        }
        return $r;
    }

    //
    private function getCountryString($data, $keys)
    {
        $string = "";
        if ($keys) {
            foreach (explode(",", $keys) as $vv) {
                if (array_key_exists($vv, $data)) {
                    $string .= $data[$vv] . ",";
                }
            }
        }
        return trim($string, ",");
    }

    private function getChannelString($keys)
    {
        $string = "";
        if ($keys) {
            foreach (explode(",", $keys) as $vv) {

                $string .= getplatform($vv) . ",";

            }
        }
        return trim($string, ",");
    }

    public function getData()
    {
        $apps = Db::name("app")->field("id as value,platform,app_base_id,concat(app_name,'-',platform) as label")->where("status=1 and platform is not null")->select();
        if (!empty($apps)) {
            foreach ($apps as &$vv) {
                if ($vv["value"] > 154) {
                    if ($vv["app_base_id"]) {
                        $row = Db::name("app_base")->where("id", $vv["app_base_id"])->find();
                        $vv["label"] = $row["name"] . ' - ' . $vv["platform"];
                    }
                }
            }
        }
        $users = Db::name("admin")->field("id as value,truename as label")->where("status=1")->select();
        echo json_encode(["apps" => $apps, "users" => $users]);
        exit;
    }

    public function report($id = "")
    {
        if (!$id) {
            return redirect("/testplan/index");
        }
        $assign = array(
            "countrys" => admincountry()
        );
        $r = $this->getallData($id);
        $view = isset($r[0]["list"][0]["type"]) && $r[0]["list"][0]["type"] == "2" ? "material" : "report";
        $assign["list"] = $r;
        $assign["id"] = $id;
        return $this->fetch($view, $assign);
    }

    public function getReportData($id = "")
    {
        $res = Db::name("testplan")->find($id);
        $r = Db::name("testreport")->where(["test_id" => $id])->select();
        if ($res["status"] == "3" && !empty($r)) {
            echo json_encode($r);
            exit;
        }
        $out_put = [];
        $index = new Index(request());
        $day = getDateFromRange($res["start_date"], $res["end_date"]);
        foreach (explode(",", $res["country"]) as $vv) {

            $s = [];
            $r = $index->getrevenuetotal($res["app_id"], $res["start_date"], $res["end_date"], "all", $vv);
            if ($vv != "CN") {
                $s = $this->getSpend($res["app_id"], $res["start_date"], $res["end_date"], $vv, $res["channel"]);
            }
            $g = $this->getuser_time($res["app_id"], $res["start_date"], $res["end_date"], $vv);
            $reten_1 = $this->getdayreten($res["app_id"], $res["start_date"], $res["end_date"], $vv, 1);
            $reten_3 = $this->getdayreten($res["app_id"], $res["start_date"], $res["end_date"], $vv, 3);
            $reten_5 = "0%";
            $reten_7 = $this->getdayreten($res["app_id"], $res["start_date"], $res["end_date"], $vv, 7);
            $reten = $reten_1 . "," . $reten_3 . "," . $reten_5 . "," . $reten_7;
            $dau = $r["total"]["active_users"];
            $num = $dau > 0 ? round($g["num"] / $dau, 2) : 0;
            $totaltime = round($g["val"] * $num / 60, 2) . "min";
            $onetime = round($g["val"] / 60, 2) . "min";
            $arpudau = $r["total"]["dauarpu"];
            $arr = ["country" => $vv, "test_id" => $id, "app_id" => $res["app_id"], "dau" => $dau, "reten" => $reten, "num" => $num, "onetime" => $onetime, "totaltime" => $totaltime, "arpudau" => $arpudau];
            $out_put[] = array_merge($arr, $s);
        }
        echo json_encode($out_put);
        exit;
    }

    //素材数据
    public function getMaterialData($id = "")
    {
        $out_put = [];
        $res = Db::name("testreport")->field("content")->where(["test_id" => $id])->find();
        if (!empty($res)) {
            if ($res["content"]) {
                $out_put = json_decode($res["content"], true);
            }
        }
        echo json_encode($out_put);
        exit;
    }

    //修改
    public function SaveReport()
    {
        $data = input("post.");
        Db::startTrans();
        $id = $data["id"];
        $result = Db::name("testreport")->where(["test_id" => $id])->delete();
        if ($result !== false) {
            Db::name("testplan")->where(["id" => $id])->update(["summary" => $data["summary"]]);
            Db::name('testreport')->insertAll($data["list"]);
            Db::commit();
            exit("ok");
        }
        Db::rollback();
        exit("fail");
    }
	
	//实时保存
	public function real_save(){
		$data = input("post.");
		$id = $data["id"];
		Db::name("testplan")->where(["id" => $id])->update(["summary" => $data["summary"]]);
	}

    public function savematerial($id = "", $ad_id = "")
    {
        if ($id && !empty($ad_id)) {

            $t = Db::name("testplan")->field("start_date,end_date")->where(["id" => $id])->find();
            $where = "date>='{$t['start_date']}' and date<='{$t['end_date']}'";
            $r = Db::name("adspend_data")->field("ad_id,ad_name,sum(video_view) as video_view,round(avg(video_percent),2) as video_percent,sum(impressions) as impressions,sum(clicks) as clicks, round(sum(spend),2) as spend")->where(["ad_id" => ["in", $ad_id], "platform_type" => 6])->where($where)->group("ad_id")->select();
            if (!empty($r)) {
                $res = Db::name("testreport")->field("content")->where(["test_id" => $id])->find();
                if (!empty($r)) {
                    foreach ($r as &$vv) {
                        $spend = $vv["spend"] ? $vv["spend"] : "0.0";
                        $impressions = $vv["impressions"] ? $vv["impressions"] : 0;
                        $clicks = $vv["clicks"] ? $vv["clicks"] : 0;
                        $vv["ctr"] = $vv["impressions"] <= 0 ? 0 : round($clicks * 100 / $impressions, 2);
                        $vv["cpm"] = $impressions <= 0 ? 0 : round($spend * 1000 / $impressions, 2);
                    }
                }
                if (!empty($res)) {
                    $t = json_decode($res["content"], true);
                    $t = array_merge($t, $r);
                    Db::name("testreport")->where(["test_id" => $id])->update(["content" => json_encode($t)]);
                } else {
                    Db::name("testreport")->insert(["test_id" => $id, "content" => json_encode($r)]);
                }
            }
        }
        exit("ok");
    }

    //素材数据
    public function getMData($id = "")
    {
        $out_put = [];
        $res = Db::name("testplan")->field("app_id")->find($id);
        if (!empty($res)) {
            $app_id = $res["app_id"];
            $out_put = Db::name("adspend_data")->field("ad_id,ad_name,campaign_name,target_id")->where(["app_id" => $app_id, "platform_type" => 6])->group("ad_id")->select();
        }

        echo json_encode($out_put);
        exit;
    }

    private function getdayreten($appid, $start, $end, $country, $day)
    {
        $start = date("Y-m-d", strtotime("+{$day} day", strtotime($start)));
        $end = date("Y-m-d", strtotime("+{$day} day", strtotime($end)));
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $reten_sql = "select avg(retention_{$day}) as val from hellowd_retention where {$where}";
        $d = Db::query($reten_sql);
        if (empty($d)) {
            return 0;
        }
        return $d[0]["val"] ? round($d[0]["val"] * 100, 2) . "%" : "0%";
    }


    private function getSpend($appid, $start = "", $end = "", $country = "all", $platform = "")
    {
        $spend = "0.00";
        $installs = 0;
        $impressions = 0;
        $clicks = 0;
        $where = "app_id={$appid} and  date>='{$start}' and date<='{$end}'";
        if ($country != "all") {
            $where .= " and country='{$country}'";
        }
        if ($platform != "all") {
            $where .= " and platform_type in({$platform})";
        }
        $spend_sql = "select sum(impressions) as impressions,sum(clicks) as clicks,sum(installs) as installs,round(sum(spend),2) as spend from hellowd_adspend_data where {$where}";
        $d = Db::query($spend_sql);
        if (!empty($d)) {
            $d = $d[0];
            $spend = $d["spend"] ? $d["spend"] : "0.0";
            $installs = $d["installs"] ? $d["installs"] : 0;
            $impressions = $d["impressions"] ? $d["impressions"] : 0;
            $clicks = $d["clicks"] ? $d["clicks"] : 0;
        }
        $cpi = $installs > 0 ? round($spend / $installs, 2) : "0";
        $ctr = $impressions > 0 ? round($clicks * 100 / $impressions, 2) . "%" : "0";
        $cvr = $clicks > 0 ? round($installs * 100 / $clicks, 2) . "%" : "0";
        return ["cpi" => $cpi, "ctr" => $ctr, "cvr" => $cvr];
    }

    //人均使用时长
    private function getuser_time($app_id, $start, $end, $country)
    {
        $where = "app_id={$app_id} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
        $user_sql = "select round(avg(val),2) as val,avg(num) as num from hellowd_user_time where {$where}";
        $d = Db::query($user_sql);

        if (empty($d)) {
            return ["val" => 0, "num" => 0];
        }
        return isset($d[0]) && !empty($d[0]) ? $d[0] : ["val" => 0, "num" => 0];
    }

    public function postForm()
    {
        $data = input("post.");
        $data["start_date"] = $data["date"][0];
        $start_date = strtotime($data["start_date"]);
        $data["end_date"] = $data["date"][1];
        $current_time = strtotime(date("Y-m-d", time()));
        $data["country"] = implode(",", $data["country"]);
        $data["channel"] = implode(",", $data["channel"]);
        $data["manager"] = implode(",", $data["manager"]);
        if ($start_date >= $current_time) {
            $data["status"] = 2;
        } else {
            $end_date = strtotime($data["end_date"]);
            if ($end_date < $current_time) {
                $data["status"] = 3;
            } else {
                $data["status"] = 1;
            }
        }
        $id = $data["id"];
        unset($data["date"], $data["id"]);
        if ($id > 0) {
            $result = Db::name("testplan")->where(["id" => $id])->update($data);

        } else {
            $start_date = date("m.d", strtotime($data["start_date"]));
            $end_date = date("m.d", strtotime($data["end_date"]));
            $nameData = Db::name("admin")->field("truename")->where(" id in ({$data['manager']}) ")->select();
            $username = implode(",", array_column($nameData, "truename"));
            $country = $this->getCountryString(admincountry(), $data["country"]);
            $channel = $this->getChannelString($data["channel"]);
            $send = $data["send"];
            $title = "【测试开启】" . getapp_name($data["app_id"]) . "-" . $data["title"];
            $body = $this->getemailhtml($data["title"], $start_date, $end_date, $username, $country, $channel, $data['budget'], $data['cpi'], $data['intent']);
            $this->sendmail(implode(",", $send), $title, $body);
            unset($data["send"]);
            $result = Db::name("testplan")->insert($data);
        }
        exit($result !== false ? "ok" : "fail");
    }


    public function getemailhtml($title, $start_date, $end_date, $username, $country, $channel, $budget, $cpi, $intent)
    {
        $assign = array(
            "title" => $title,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "username" => $username,
            "country" => $country,
            "channel" => $channel,
            "budget" => $budget,
            "cpi" => $cpi,
            "intent" => $intent
        );
        return $this->fetch("getemailhtml", $assign);
    }

    public function edit($id = "")
    {
        if (!$id) return [];
        $res = Db::name('testplan')->field("id,app_id,title,manager,start_date,end_date,country,channel,budget,cpi,type,intent")->where(["id" => $id])->find();
        $res["date"] = [$res["start_date"], $res["end_date"]];
        $res["manager"] = array_map(function ($v) {

            return (string)$v;

        }, explode(",", $res["manager"]));
        $res["country"] = explode(",", $res["country"]);
        $res["channel"] = explode(",", $res["channel"]);
        unset($res["start_date"], $res["end_date"]);
        echo json_encode($res);
        exit;

    }

    public function postReport()
    {
        $data = input("post.");
        if (isset($data["isedit"]) && $data["isedit"] == "2") {
            $result = Db::name('testplan')->where(["id" => $data["id"]])->update(["summary" => $data["content"]]);
            $r = Db::name('testplan')->where(["id" => $data["id"]])->find();
            if (!empty($data["manager"])) {
                $title = "【测试结束】" . getapp_name($r["app_id"]) . "-" . $r["title"];
                if ($r["type"] == "2") {
                    $body = $data["content"];
                } else {
                    $url = $data["url"];
                    $intent = $r["intent"];
                    $summary = $r["summary"];
                    $body = $data["content"];
                }
                $this->sendmail(implode(",", $data["manager"]), $title, $body);
            }
            exit("ok");
        }
        Db::startTrans();
        try {
            $result = Db::name('testplan')->where(["id" => $data["id"]])->update(["status" => 3, "finish_status" => $data["finish_status"], "summary" => $data["content"]]);
            if ($result !== false) {
                $r = Db::name('testplan')->where(["id" => $data["id"]])->find();
                if (!empty($data["manager"])) {
                    $title = "【测试结束】" . getapp_name($r["app_id"]) . "-" . $r["title"];
                    if ($r["type"] == "2") {
                        $body = $data["content"];
                    } else {
                        $url = $data["url"];
                        $intent = $r["intent"];
                        $summary = $r["summary"];
                        $body = $data["content"];
                    }
                    $this->sendmail(implode(",", $data["manager"]), $title, $body);
                }
                if ($r["type"] == "2") {
                    Db::name('testplan')->where(["id" => $data["id"]])->update(["summary" => $data["content"]]);
                } else {

                    Db::name('testreport')->insertAll($data["list"]);
                }
                Db::commit();
                exit("ok");
            }
            Db::rollback();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
        exit("fail");
    }

    //发送邮箱
    private function sendmail($ids, $title, $body)
    {
        if ($ids) {
            $r = getuserinfo();
            $truename = "GameBrain(" . $r["truename"] . ")";
            $res = Db::name("admin")->field("truename,email")->where(" id in ({$ids}) ")->select();
            if (!empty($res)) {
                $body = $this->emailfooter($r, $res) . $body;
                foreach ($res as $vv) {
                    if ($vv["email"]) {

                        send_mail($vv["email"], $vv["truename"], $title, $body, $truename);
                    }
                }
            }
        }
        return;
    }

    private function emailfooter($r, $res)
    {
        $date = date("Y-m-d H:i:s", time());
        $footer = "<div style='FONT-SIZE: 12px;background:#efefef;padding:8px;margin:10px 0px;'>
  <div><b>邮件来自于: </b>&nbsp;{$r['truename']}&lt;<a href='mailto:{$r["email"]}' target='_blank'>{$r["email"]}</a>&gt;;</div>
  <div><b>发送时间: </b>&nbsp;{$date}</div>
  <div>
  <b>发送对象: </b>";

        if (!empty($res)) {
            foreach ($res as $vv) {
                $footer .= "&nbsp;{$vv["truename"]};<a href='mailto:{$vv["email"]}' target='_blank'>{$vv["email"]}</a>";
            }
        }
        $footer .= "</div></div>";
        return $footer;
    }
}
