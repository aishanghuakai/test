<?php

namespace app\api\controller;

use think\Db;
use \think\Request;

set_time_limit(0);

class Gastatic
{

    private $appid = array(

        "55409" => ["appid" => "68", "name" => "Spiral Rush Go"],
        "56540" => ["appid" => "77", "name" => "Tankr.io Realtime Battle"],
        "53679" => ["appid" => "81", "name" => "Daily Pinball"],
        "57470" => ["appid" => "66", "name" => "Hexland-ios"],
        "73210" => ["appid" => "127", "name" => "Shopping Mall Tycoon - IOS"],
        "67053" => ["appid" => "117", "name" => "Idle Fish Tycoon - IOS"],
        "62154" => ["appid" => "94", "name" => "Bricks Pinball-IOS"],
        "57970" => ["appid" => "73", "name" => "Idle Capital Street-ios"],
        "57754" => ["appid" => "52", "name" => "Tankr.io-Android"],
        "59269" => ["appid" => "91", "name" => "HexSnake.io-Android"],
        "58928" => ["appid" => "90", "name" => "Daily Pinball - Android"],
        "77067" => ["appid" => "130", "name" => "Shopping Mall Tycoon - Andriod"],
        "61955" => ["appid" => "93", "name" => "HexSnake-IOS"],
        "64438" => ["appid" => "107", "name" => "Ore Tycoon-iOS"],
        "74384" => ["appid" => "122", "name" => " Tank Shooting-ios"],
        "64601" => ["appid" => "109", "name" => "Way to die - iOS"],
        "70839" => ["appid" => "126", "name" => "Truck vs Fire - Android"],
        "64679" => ["appid" => "114", "name" => "Truck vs Fire-IOS"],
        "70519" => ["appid" => "113", "name" => "Ore Tycoon - Android"],
        "93145" => ["appid" => "144", "name" => "找茬 Find Difference - iOS"],
        "91154" => ["appid" => "141", "name" => "Brain Puzzle:Trickiest Test - ios"],
        "87168" => ["appid" => "135", "name" => "Idle Farm"],
        "94204" => ["appid" => "146", "name" => "Zombie Clash"],
        "95473" => ["appid" => "147", "name" => "Fish Go安卓"],
        "92510" => ["appid" => "143", "name" => "Fish Go - ios 发行"],
        "96960" => ["appid" => "148", "name" => "Tankr.io 2 - Tank Clash- ios"]
    );

    private $metrics = [
        "active_users",
        "session_length",
        "retention_1"
    ];
    private $retenmetrics = [
        //"retention_1",
        "retention_2",
        "retention_3",
        "retention_7",
        "retention_14",
        "retention_28"
    ];

    public function countryQuery($start = "")
    {
        /* foreach( $this->metrics as $v )
		{
			$this->ByCountryRequest($v,$start);
		} */
        echo "ok";
    }

    public function TotalQuery($start = "")
    {
        /* foreach( $this->metrics as $v )
		{
			$this->ByTotalRequest($v,$start);
		} */
        echo "ok";
    }

    public function RetenCountryQuery($start = "")
    {
        foreach ($this->retenmetrics as $v) {
            $this->ByCountryRequest($v, $start);
        }
        echo "ok";
    }

    public function RetenTotalQuery($start = "")
    {
        foreach ($this->retenmetrics as $v) {
            $this->ByTotalRequest($v, $start);
        }
        echo "ok";
    }


    public function ByTotalRequest($statisticName = "", $start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $params = array(
            "intervalStart" => $start,
            "intervalEnd" => $end,
            "statisticName" => $statisticName
        );
        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->query(array_merge($v, $params));

                if (isset($result["timestamp"])) {
                    $result["timestamp"] = date("Y-m-d", $result["timestamp"]);
                }
                if (!empty($result)) {
                    $val = $result["total"];
                    $this->inserdata($start, $v["appid"], $statisticName, "all", $val);
                }
            }
        }
        return true;
    }

    private function ByCountryRequest($statisticName = "", $start = "")
    {

        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $params = array(
            "intervalStart" => $start,
            "intervalEnd" => $end,
            "splitBy" => "country_code",
            "splitByThreshold" => 20,
            "statisticName" => $statisticName
        );
        $res = $this->login();
        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->query(array_merge($v, $params));


                if (isset($result["timestamp"])) {
                    $result["timestamp"] = date("Y-m-d", $result["timestamp"]);
                }

                if (!empty($result)) {

                    $data = $result["result"];
                    if (!empty($data)) {
                        foreach ($data as $d_v) {
                            if ($statisticName == "session_length") {
                                $t_toal = isset($d_v["mean"]) ? $d_v["mean"] : 0;
                            } else {
                                $t_toal = isset($d_v["total"]) ? $d_v["total"] : 0;
                            }
                            $this->inserdata($start, $v["appid"], $statisticName, $d_v["country_code"], $t_toal);
                        }
                    }
                }
            }
        }
        return true;
    }

    private function inserdata($date, $app_id, $statisticName, $country, $val)
    {
        if (in_array($statisticName, ["active_users", "new_users"])) {
            if ($app_id == 127) {
                return;
            }
            $r = Db::name($statisticName)->where(["app_id" => $app_id, "date" => $date, "country" => $country])->find();
            if (empty($r)) {

                Db::name($statisticName)->insert(["app_id" => $app_id, "date" => $date, "country" => $country, "val" => ceil($val)]);
            } else {
                Db::name($statisticName)->where("id", $r["id"])->update(["val" => ceil($val)]);
            }
        } elseif (preg_match("/retention/", $statisticName)) {
            $r = Db::name("retention")->where(["app_id" => $app_id, "date" => $date, "country" => $country])->find();
            if (empty($r)) {
                Db::name("retention")->insert(["app_id" => $app_id, "date" => $date, "country" => $country, $statisticName => round($val, 4)]);
            } else {
                Db::name("retention")->where("id", $r["id"])->update([$statisticName => round($val, 4)]);
            }
        } elseif (preg_match("/session_length/", $statisticName)) {
            $r = Db::name("user_time")->where(["app_id" => $app_id, "date" => $date, "country" => $country])->find();
            if (empty($r)) {
                Db::name("user_time")->insert(["app_id" => $app_id, "date" => $date, "country" => $country, "val" => round($val, 2)]);
            } else {
                Db::name("user_time")->where("id", $r["id"])->update(["val" => round($val, 2)]);
            }
        } elseif (preg_match("/session_unique/", $statisticName)) {
            $r = Db::name("user_time")->where(["app_id" => $app_id, "date" => $date, "country" => $country])->find();
            if (empty($r)) {
                Db::name("user_time")->insert(["app_id" => $app_id, "date" => $date, "country" => $country, "num" => $val]);
            } else {
                Db::name("user_time")->where("id", $r["id"])->update(["num" => $val]);
            }
        }
    }

    private function query($params = [])
    {
        $url = "https://summary-api.gameanalytics.com/summary_api/query?";
        $url = $url . http_build_query($params);
        $res = $this->googlecurl($url, null, "post");
        $result = json_decode($res, true);
        if ("success" == $result["msg"]) {
            return isset($result["data"][0]) ? $result["data"][0] : [];
        }
        return [];
    }

    public function login()
    {
        header("Content-Type: text/html;charset=utf-8");
		$game_list = [];
         $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
         $ga_token = $mem->get('ga_token');
         if ($ga_token) {

            $game_list = json_decode($ga_token, true);
            return $game_list;
        }
        $loginurl = "https://go.gameanalytics.com/api/v1/public/login/basic";
        $res = json_decode($this->googlecurl($loginurl,json_encode(["email"=>"lixiongfei@hellowd.net","password"=>"a547534827","remember"=>false]), "post"), true);      
		if(!isset($res["results"][0]["token"]))
		{
			return false;
		}
		$result = $this->new_curl_v2($res["results"][0]["token"]);
		$data = isset($result['results'][0]['studiosGames'][0]['games'])?$result['results'][0]['studiosGames'][0]['games']:[];
        if(empty($data))
		{
			return false;
		}
		foreach ($data as $k => $vv) {
            if (isset($this->appid[$vv["id"]])) {
                    $game_list[] = ["gameId" => $vv["id"], "appid" => $this->appid[$vv["id"]]["appid"], "token" => $vv['dataApiToken']["token"]];
                }
        }
        $mem->set('ga_token', json_encode($game_list), 0, 2000);
        return $game_list;
    }
	
	private function new_curl_v2($access_token)
    {        
        
		$url = "https://go.gameanalytics.com/api/v1/user/data";     
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $httpHeader[] = 'X-Authorization:'.$access_token;
        $r = $this->curl_request($url,$httpHeader,[],false);
        return json_decode($r, true);
    }

    private function other_login($email, $password)
    {
        $game_list = [];
        //$loginurl= "https://summary-api.gameanalytics.com/summary_api/login?email=zhangfangyu@hellowd.net&password=karasuSmoon1109";
        $loginurl = "https://summary-api.gameanalytics.com/summary_api/login?email={$email}&password={$password}";
        $res = json_decode($this->googlecurl($loginurl, null, "post"), true);
        if (count($res["errors"]) > 0) {
            return false;
        }
        $result = $res["results"];
        foreach ($result as $k => $v) {

            $games = $v['games'];
            foreach ($games as $vv) {
                if (isset($this->appid[$vv["id"]])) {
                    $game_list[] = ["gameId" => $vv["id"], "appid" => $this->appid[$vv["id"]]["appid"], "token" => $vv["token"]];
                }
            }
        }
        return $game_list;
    }

    public function EventQuery($start = "", $event_name = "RewardShow", $country = "all")
    {

        $res = $this->login();
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        if (!empty($res)) {
            foreach ($res as $v) {
                $r = $this->GaVideoShow($v["token"], $v["gameId"], $start, $event_name, $country);

                $this->insert_event_data($r, $start, $v["appid"], $event_name, $country);
            }
        }
        exit("ok");
    }

    private function insert_event_data($data, $date, $app_id, $event_name, $country)
    {
        if (!empty($data)) {
            $result = isset($data[0]) ? $data[0] : [];
            if ("all" == $country) {
                $res = $result["total"];
                $res[0] = $res;
            } else {
                $res = $result["result"];
            }
            if (!empty($res)) {
                foreach ($res as $vv) {
                    if (isset($vv["event_count"]) && $vv["event_count"] > 0) {
                        $c = isset($vv["country_code"]) ? $vv["country_code"] : "all";
                        $c_res = Db::name("event_data")->where(["app_id" => $app_id, "event_name" => $event_name, "date" => $date, "country" => $c])->find();
                        if (empty($c_res)) {
                            Db::name("event_data")->insert(["app_id" => $app_id, "event_name" => $event_name, "date" => $date, "country" => $c, "event_v" => $vv["event_count"]]);
                        } else {
                            Db::name("event_data")->where("id", $c_res["id"])->update(["event_v" => $vv["event_count"]]);
                        }
                    }
                }
            }
        }
        return true;
    }


    public function new_users($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_new_users($start, "new_users", $v["token"], $v["gameId"]);

                if (!empty($result)) {
                    foreach ($result as $r) {
                        $t_toal = $r["total"]["new_users"];
                        $this->inserdata($start, $v["appid"], "new_users", "all", $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }

    //会话次数
    public function session_num($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_new_users($start, "session_unique", $v["token"], $v["gameId"]);
                if (!empty($result)) {
                    foreach ($result as $r) {
                        $t_toal = $r["total"]["session_unique"];
                        $this->inserdata($start, $v["appid"], "session_unique", "all", $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }

    public function country_session_num($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_country_new_users($start, "session_unique", $v["token"], $v["gameId"]);
                if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                    $data = $result[0]["result"];
                    foreach ($data as $r) {
                        $t_toal = $r["session_unique"];
                        $this->inserdata($start, $v["appid"], "session_unique", $r["country_code"], $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }

    public function country_new_users($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_country_new_users($start, "new_users", $v["token"], $v["gameId"]);
                if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                    $data = $result[0]["result"];
                    foreach ($data as $r) {
                        $t_toal = $r["new_users"];
                        $this->inserdata($start, $v["appid"], "new_users", $r["country_code"], $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }

    private function curl_new_users($start, $type, $access_token, $gameId)
    {

        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/{$type}/timeseries";
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $data = json_encode(
            [
                "granularity" => "day",
                "interval" => "{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
            ]
        );
        $r = $this->curl_request($url, $httpHeader, $data, false);
        return json_decode($r, true);
    }


    private function curl_country_new_users($start, $type, $access_token, $gameId)
    {

        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/{$type}/topN";
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $data = json_encode(
            [
                "dimension" => "country_code",
                "threshold" => 50,
                "granularity" => "day",
                "interval" => "{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
            ]
        );
        $r = $this->curl_request($url, $httpHeader, $data, false);
        return json_decode($r, true);
    }

    public function getaccess_token($appid)
    {
        $res = $this->login();
        foreach ($res as $vv) {
            if (isset($vv["appid"]) && $vv["appid"] == $appid) {
                return $vv;
            }
        }
        return [];
    }

    //获取某一天留存
    public function getdayretention($data, $date = "", $day = "")
    {
        if (!empty($data)) {
            $result = $this->curl_reten($date, $data["token"], $data["gameId"], $day);
            if (!empty($result) && isset($result[0]["total"]["retention"])) {
                return round($result[0]["total"]["retention"] * 100, 2);
            }
        }
        return 0;
    }

    public function add_reten($date = "")
    {

        $this->all_reten();
        exit;

    }

    private function all_reten()
    {
        $current_date = strtotime(date("Y-m-d", strtotime("-1 day")));
        $d = Db::name("bind_app")->where(["update_switch" => 1])->find();
        if (!empty($d)) {
            $res = $this->getaccess_token($d["app_id"]);
            for ($i = 30; $i > 0; $i--) {
                $c_time = $current_date - (86400 * $i);
                $date = date('Y-m-d', $c_time);
                $reten = $this->getdayretention($res, $date, $i);
                $r = Db::name("all_reten")->where(["app_id" => $d["app_id"], "date" => $date, "reten_day" => $i, "country" => "all"])->find();
                if (empty($r)) {
                    $out_data = ["date" => $date, "reten_day" => $i, "country" => "all", "val" => $reten, "app_id" => $d["app_id"]];
                    Db::name("all_reten")->insert($out_data);
                } else {
                    Db::name("all_reten")->where(["id" => $r["id"]])->update(["val" => $reten]);
                }
            }
            Db::name("bind_app")->where(["id" => $d["id"]])->update(["update_switch" => 2, "update_time" => date("Y-m-d", strtotime("-1 day"))]);
        } else {
            $x = Db::name("bind_app")->where(["update_switch" => 2])->find();
            $stimestamp = strtotime($x["update_time"]);
            if ($current_date >= $stimestamp) {
                Db::name("bind_app")->where(["update_switch" => 2])->update(["update_switch" => 1]);
                return $this->add_reten();
            }
        }
        exit("ok");
    }


    //新时长获取 总
    public function new_session_total($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $res = $this->login();
        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_new_users($start, "session_length", $v["token"], $v["gameId"]);
                if (!empty($result)) {
                    foreach ($result as $r) {
                        $t_toal = $r["total"]["mean"];
                        $this->inserdata($start, $v["appid"], "session_length", "all", $t_toal);
                    }
                }
            }
        }
        exit("ok");
    }

    //新时长 国家
    public function new_session_country($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_country_new_users($start, "session_length", $v["token"], $v["gameId"]);
                if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                    $data = $result[0]["result"];
                    foreach ($data as $r) {
                        $t_toal = $r["mean"];
                        $this->inserdata($start, $v["appid"], "session_length", $r["country_code"], $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }

    //新活跃获取 国家
    public function new_active_country($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }

        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_country_new_users($start, "user_unique", $v["token"], $v["gameId"]);
                if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                    $data = $result[0]["result"];
                    foreach ($data as $r) {
                        $t_toal = $r["user_unique"];
                        $this->inserdata($start, $v["appid"], "active_users", $r["country_code"], $t_toal);
                    }
                }
            }
        }
        echo "ok";
    }


    //新活跃获取 总
    public function new_active_total($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $res = $this->login();

        if (!empty($res)) {
            foreach ($res as $v) {
                $result = $this->curl_new_users($start, "user_unique", $v["token"], $v["gameId"]);				
                if (!empty($result)) {
                    foreach ($result as $r) {
                        $t_toal = $r["total"]["user_unique"];
                        $this->inserdata($start, $v["appid"], "active_users", "all", $t_toal);
                    }
                }
            }
        }
        exit("ok");
    }

    /**
     * 获取单个产品数据留存
     * @param string $start
     * @param int $app_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_reten_data_one($start = "",$app_id=0){
        if (!$app_id){
            exit("error APP ID");
        }
        $game_list = [];
        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $ga_token = $mem->get('ga_new_token');
        if ($ga_token) {
            $game_list = json_decode($ga_token, true);
        }
        if (!empty($game_list)) {
            foreach ($game_list as &$g) {
                if ($g['appid']==$app_id){
                    $host = getdomainname();
                    $url_total = $host . "/adgastatic/asyn_reten_total";
                    $url_country = $host . "/adgastatic/asyn_reten_country";
                    $session_url = $host . "/adgastatic/asyn_session_total";
                    $session_country_url = $host . "/adgastatic/asyn_session_country";
                    $g["start"] = $start;
                    var_dump($url_country,$g);
                    syncRequest($url_total, $g);
                    sleep(3);
                    syncRequest($url_country, $g);
                    sleep(3);
                    syncRequest($session_url, $g);
                    sleep(3);
                    syncRequest($session_country_url, $g);
                }
            }
        }
		$this->get_ga_sd();
        exit("ok");
    }
	
	public function get_ga_sd($start=""){
		if($start=="")
		{
			$start = date("Y-m-d", strtotime("-1 day"));
		}		
		$app_ids =[184,186,187,188,190];
		foreach($app_ids as $a)
		{
			$this->get_one_product_data($start,$a);
		}
		exit("ok");
	}
	
	//特殊产品拉取
	public function get_one_product_data($start = "",$app_id=0){
		$game_list = [];
        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $ga_token = $mem->get('ga_new_token');
        if ($ga_token) {
            $game_list = json_decode($ga_token,true);
        }
        if (!empty($game_list)) {
            foreach ($game_list as &$g) {
                if ($g['appid']==$app_id){
                    $host = getdomainname();
                    $url_total = $host . "/adgastatic/asyn_get_active_total";
                    $url_country = $host . "/adgastatic/asyn_get_active_country";
					$a_url_total = $host . "/adgastatic/asyn_get_new_users_total";
                    $a_url_country = $host . "/adgastatic/asyn_get_new_users_country";
                    $g["start"] = $start;
                    syncRequest($url_total, $g);
                    sleep(3);
                    syncRequest($url_country, $g);
					sleep(3);
                    syncRequest($a_url_total, $g);
					sleep(3);
                    syncRequest($a_url_country, $g);
                }
            }
        }
        
	}
	
	//新增用户
	public function asyn_get_new_users_total(Request $request){
		$params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_new_users($start, "new_users", $token, $gameId);
            if (!empty($result)) {
                foreach ($result as $r) {
                    $t_toal = $r["total"]["new_users"];
                    $this->inserdata($start, $appid, "new_users", "all", $t_toal);
                }
            }
        }
        exit("ok");
	}
	
	public function asyn_get_new_users_country(Request $request){
		$params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_country_new_users($start, "new_users", $token, $gameId);
            if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                $data = $result[0]["result"];
                foreach ($data as $r) {
                    $t_toal = $r["new_users"];
                    $this->inserdata($start, $appid, "new_users", $r["country_code"], $t_toal);
                }
            }
        }
        exit("ok");
	}
	
	//活跃用户拉取
	public function asyn_get_active_total(Request $request){
		
		$params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_new_users($start, "user_unique", $token, $gameId);
            if (!empty($result)) {
                foreach ($result as $r) {
                    $t_toal = $r["total"]["user_unique"];
                    $this->inserdata($start, $appid, "active_users", "all", $t_toal);
                }
            }
        }
        exit("ok");
	}
	
	public function asyn_get_active_country(Request $request){
		
		$params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_country_new_users($start, "user_unique", $token, $gameId);
            if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                $data = $result[0]["result"];
                foreach ($data as $r) {
                    $t_toal = $r["user_unique"];
                    $this->inserdata($start, $appid, "active_users", $r["country_code"], $t_toal);
                }
            }
        }
        exit("ok");
	}
	
    public function get_reten_data($start = "")
    {
        $list = Db::name("bind_attr")->field("app_id,ga")->where("ga!=''")->select();

        if (!empty($list)) {
            if ($start == "") {
                $start = date("Y-m-d", strtotime("-1 day"));
            }
            $res = [];
            foreach ($list as $v) {
                $res[$v['ga']] = $v['app_id'];
            }
            $game_list = [];
            $mem = new \Memcache();
            $mem->connect("127.0.0.1", 11211);
            $ga_token = $mem->get('ga_new_token');
            if ($ga_token) {
                $game_list = json_decode($ga_token, true);
            }
            if (empty($game_list)) {
                $game_list = $this->update_login_token($res);
            }
            if (!empty($game_list)) {
                foreach ($game_list as &$g) {
                    $host = getdomainname();
                    $url_total = $host . "/adgastatic/asyn_reten_total";
                    $url_country = $host . "/adgastatic/asyn_reten_country";
                    $session_url = $host . "/adgastatic/asyn_session_total";
                    $session_country_url = $host . "/adgastatic/asyn_session_country";
                    $g["start"] = $start;
                    syncRequest($url_total, $g);
                    sleep(3);
                    syncRequest($url_country, $g);
                    sleep(3);
                    syncRequest($session_url, $g);
                    sleep(3);
                    syncRequest($session_country_url, $g);
                }
            }
        }
		$this->get_ga_sd();
        exit("ok");
    }

    public function asyn_reten_country(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $reten = ["1", "2", "3", "7", "14", "28"];
            foreach ($reten as $vv) {
                $start_now = date('Y-m-d',strtotime($start)-(intval($vv)-1)*86400);
                $result = $this->country_reten_v1($start_now, $token, $gameId, $vv);
                if (!empty($result)) {
                    foreach ($result as $d_v) {
                        if (isset($d_v["total"]) && !empty($d_v["total"])) {
                            $this->inserdata($start_now, $appid, "retention_" . $vv, $d_v["total"]["country_code"], $d_v["total"]["retention"]);
                        }
                    }
                }
            }
        }
        exit("ok");
    }
	
	
	 public function get_new_reten_data($start = "")
    {
        $list = Db::name("bind_attr")->field("app_id,ga")->where("ga!=''")->select();

        if (!empty($list)) {
            if ($start == "") {
                $start = date("Y-m-d", strtotime("-1 day"));
            }
            $res = [];
            foreach ($list as $v) {
                $res[$v['ga']] = $v['app_id'];
            }
            $game_list = [];
            $mem = new \Memcache();
            $mem->connect("127.0.0.1", 11211);
            $ga_token = $mem->get('ga_new_token');
            if ($ga_token) {
                $game_list = json_decode($ga_token, true);
            }

            if (empty($game_list)) {
                $game_list = $this->update_login_token($res);
            }
            if (!empty($game_list)) {
                foreach ($game_list as &$g) {
                    $host = getdomainname();
                    $url_total = $host . "/adgastatic/asyn_new_reten_total";
                    $url_country = $host . "/adgastatic/asyn_new_reten_country";					
                    $g["start"] = $start;
                    syncRequest($url_total,$g);
					sleep(3);
                    syncRequest($url_country,$g);				
                }
            }
        }
        exit("ok");
    }
	
	private function insert_reten_data($start,$appid,$reten_day,$country,$val){
		 $r = Db::name("new_reten")->where(["app_id" => $appid,"reten_day"=>$reten_day,"date" => $start, "country" => $country])->find();
		if (empty($r)) {
			Db::name("new_reten")->insert(["app_id" => $appid, "date" => $start,"reten_day"=>$reten_day,"country" => $country, "val" => $val]);
		} else {
			Db::name("new_reten")->where("id", $r["id"])->update(["val" => $val]);
		}
	}
	
	//新增30天留存 2021-03-04
	public function asyn_new_reten_total(Request $request)
	{
		$params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            for($i=1;$i<=30;$i++)
			{
				$start_now = date('Y-m-d',strtotime($start)-(intval($i)-1)*86400);
                sleep(3);
                $result = $this->curl_reten($start_now, $token, $gameId, $i);              
                $val = isset($result[0]["total"]["retention"]) ? $result[0]["total"]["retention"] : "";
                if ($val) {
                   
                    $this->insert_reten_data($start_now, $appid, $i, "all", $val);
                }
			}
        }
	}
	
	public function asyn_new_reten_country(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            for($i=1;$i<=30;$i++)
			{
				$start_now = date('Y-m-d',strtotime($start)-(intval($i)-1)*86400);
                $result = $this->country_reten_v1($start_now, $token, $gameId, $i);
                if (!empty($result)) {
                    foreach ($result as $d_v) {
                        if (isset($d_v["total"]) && !empty($d_v["total"])) {
							$this->insert_reten_data($start_now, $appid, $i,$d_v["total"]["country_code"],$d_v["total"]["retention"]);
                        }
                    }
                }
			}
        }
        exit("ok");
    }

    public function asyn_reten_total(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $reten = ["1", "2", "3", "7", "14", "28"];
            foreach ($reten as $vv) {
                $start_now = date('Y-m-d',strtotime($start)-(intval($vv)-1)*86400);
                sleep(3);
                $result = $this->curl_reten($start_now, $token, $gameId, $vv);
                echo json_encode($result);
                $val = isset($result[0]["total"]["retention"]) ? $result[0]["total"]["retention"] : "";
                if ($val) {
                    $statisticName = "retention_" . $vv;
                    $this->inserdata($start_now, $appid, $statisticName, "all", $val);
                }
            }
        }
        //syncRequest
        exit("ok");
    }


    public function asyn_session_num_total(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_new_users($start, "session_unique", $token, $gameId);
            if (!empty($result)) {
                foreach ($result as $r) {
                    $t_toal = $r["total"]["session_unique"];
                    $this->inserdata($start, $appid, "session_unique", "all", $t_toal);
                }
            }
        }
        exit("ok");
    }

    public function asyn_session_total(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_new_users($start, "session_length", $token, $gameId);
            if (!empty($result)) {
                foreach ($result as $r) {
                    $t_toal = $r["total"]["mean"];
                    $this->inserdata($start, $appid, "session_length", "all", $t_toal);
                }
            }
        }
        $host = getdomainname();
        $session_num_total_url = $host . "/adgastatic/asyn_session_num_total";
        syncRequest($session_num_total_url, $params);
        exit("ok");
    }

    public function asyn_session_num_country(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_country_new_users($start, "session_unique", $token, $gameId);
            if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                $data = $result[0]["result"];
                foreach ($data as $r) {
                    $t_toal = $r["session_unique"];
                    $this->inserdata($start, $appid, "session_unique", $r["country_code"], $t_toal);
                }
            }
        }
        exit("ok");
    }

    public function asyn_session_country(Request $request)
    {
        $params = $request->param();
        if (!empty($params)) {
            $start = $params["start"];
            $token = $params["token"];
            $gameId = $params["gameId"];
            $appid = $params["appid"];
            $result = $this->curl_country_new_users($start, "session_length", $token, $gameId);
            if (isset($result[0]["result"]) && !empty($result[0]["result"])) {
                $data = $result[0]["result"];
                foreach ($data as $r) {
                    $t_toal = $r["mean"];
                    $this->inserdata($start, $appid, "session_length", $r["country_code"], $t_toal);
                }
            }
        }
        $host = getdomainname();
        $session_num_country_url = $host . "/adgastatic/asyn_session_num_country";
        syncRequest($session_num_country_url, $params);
        exit("ok");
    }

    private function update_login_token($data)
    {
        $game_list = [];
        $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
        $loginurl = "https://go.gameanalytics.com/api/v1/public/login/basic";
		$res = json_decode($this->googlecurl($loginurl,json_encode(["email"=>"lixiongfei@hellowd.net","password"=>"a547534827","remember"=>false]), "post"), true);
		if(!isset($res["results"][0]["token"]))
		{
			return false;
		}
		$result = $this->new_curl_v2($res["results"][0]["token"]);
		$list = isset($result['results'][0]['studiosGames'][0]['games'])?$result['results'][0]['studiosGames'][0]['games']:[];
        if(empty($list))
		{
			return false;
		}
		foreach ($list as $k => $vv) {
            if (isset($data[$vv["id"]])) {
                    $game_list[] = ["gameId" => $vv["id"], "appid" => $data[$vv["id"]], "token" => $vv["dataApiToken"]["token"]];
                }
        }
        $mem->set('ga_new_token', json_encode($game_list), 0,2000);
        return $game_list;
    }


    //新留存获取
    public function new_reten_total($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $res = $this->login();
        if (!empty($res)) {
            foreach ($res as $v) {
                $reten = ["1", "2", "3", "7", "14", "28"];
				
                foreach ($reten as $vv) {
                    $result = $this->curl_reten($start, $v["token"], $v["gameId"], $vv);
					
                    $val = isset($result[0]["total"]["retention"]) ? $result[0]["total"]["retention"] : "";
                    if ($val) {
                        $statisticName = "retention_" . $vv;
                        $this->inserdata($start, $v["appid"], $statisticName, "all", $val);
                    }
                }
            }
        }
        exit("ok");
    }

    //国家留存
    public function new_reten_country($start = "")
    {
        if ($start == "") {
            $start = date("Y-m-d", strtotime("-1 day"));
        }
        $res = $this->login();
        if (!empty($res)) {
            foreach ($res as $v) {
				$reten = ["1", "2", "3", "7", "14", "28"];
                $allcountry = ["US", "TW", "HK", "JP", "KR", "DE", "FR", "CN", "RU", "CA", "GB", "TH", "BR", "TR", "VN", "MY", "ID", "IT", "ES", "SE", "CH", "MO", "AU", "PH", "NG", "PK", "MX", "SG", "PT", "ZA", "IE", "NZ", "DK", "NL"];
                //$allcountry =["DK","NL"];
                foreach ($allcountry as $c) {
                    $result = $this->country_reten($start, $v["token"], $v["gameId"], 1, $c);
                    $val = isset($result[0]["total"]["retention"]) ? $result[0]["total"]["retention"] : "";
                    if ($val) {
                        $statisticName = "retention_1";
                        $this->inserdata($start, $v["appid"], $statisticName, $c, $val);
                    }
                }

            }
        }
        exit("ok");
    }

    public function ttt()
    {

        $token = "priv-eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0b2tlbiI6ImV5SmhiR2NpT2lKSVV6STFOaUlzSW5SNWNDSTZJa3BYVkNKOS5leUpsZUhBaU9qRTFPVGcxTURVd09EQjkuWjUtOHRsTjdoTlhuTGlOc3ktUXZuMEY5TlcyUGZGNGxvNG1mMlUwZnA5cyJ9.TS21bvf6ZBXQCitfUhn7kdgalcTUXFO-IjGSmA5YtZ0";
        $result = $this->country_reten_v1("2020-08-24", $token, "101349", 1);
    }

    //留存
    private function curl_reten($start, $access_token, $gameId, $day)
    {

        $start = date("Y-m-d", strtotime('-1 day', strtotime($start)));
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/retention/timeseries";
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $data = json_encode(
            [
                "granularity" => "day",
                "days_ahead" => $day,
                "interval" => "{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
            ]
        );
        $r = $this->curl_request($url, $httpHeader, $data, false);
        return json_decode($r, true);
    }

    private function country_reten($start, $access_token, $gameId, $day, $country)
    {

        $start = date("Y-m-d", strtotime('-1 day', strtotime($start)));
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/retention/timeseries";
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $data = json_encode(
            [
                "dimension" => ["country_code"],
                "threshold" => 10,
                "filter" => ["type" => "and", "fields" => [["type" => "in", "dimension" => "country_code", "values" => ["{$country}"]]]],
                "days_ahead" => $day,
                "granularity" => "day",
                "interval" => "{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
            ]
        );
        $r = $this->curl_request($url, $httpHeader, $data, false);
        return json_decode($r, true);
    }

    //修改留存
    private function country_reten_v1($start, $access_token, $gameId, $day)
    {
        $start = date("Y-m-d", strtotime('-1 day', strtotime($start)));
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/retention/groupBy";
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $countryList = ["US", "TW", "HK", "JP", "KR", "DE", "FR", "CN", "RU", "CA", "GB", "TH", "BR", "TR", "VN", "IN", "MY", "ID", "IT", "ES", "SE", "CH", "MO", "AU", "NO", "DK", "FI", "NL", "PH", "NG", "PK", "MX", "BD", "SG", "PT", "ZA", "IE", "AE", "AF", "EG", "IL", "JO", "KP", "AR", "CL", "BT", "GR", "IS", "MM", "BL", "UG", "IQ", "IQ", "RO", "BE", "KH", "PL", "HU", "UA", "NZ", "SA", "AT", "BY", "LK", "LT", "KZ", "BG", "BD", "NE"];
        $data = json_encode(
            [
                "dimensions" => ["country_code"],
                "threshold" => 1000,
                "filter" => ["type" => "and", "fields" => [["type" => "in", "dimension" => "country_code", "values" => $countryList]]],
                "days_ahead" => $day,
                "granularity" => "day",
                "interval" => "{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
            ]
        );
        $r = $this->curl_request($url, $httpHeader, $data, true);
        return json_decode($r, true);
    }

    public function GaVideoShow($access_token, $gameId, $start, $event_name, $country = "all")
    {

        $start = date("Y-m-d", strtotime($start));
        $end = date("Y-m-d", strtotime('+1 day', strtotime($start)));
        if ("all" == $country) {
            $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/value_sum/timeseries";
            $data = '{"filter":{"type":"and","fields":[{"type":"or","fields":[{"type":"and","fields":[{"type":"in","dimension":"event_id_02","values":["' . $event_name . '"]},{"type":"in","dimension":"event_id_01","values":["HwAds"]}]}]},{"type":"selector","dimension":"category","value":"design"}]},"granularity":"day","currency":"USD","interval":"' . $start . 'T00:00:00.000Z/' . $end . 'T00:00:00.000Z"}';
        } else {
            $url = "https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/value_sum/topN";
            $data = '{"filter":{"type":"and","fields":[{"type":"or","fields":[{"type":"and","fields":[{"type":"in","dimension":"event_id_02","values":["' . $event_name . '"]},{"type":"in","dimension":"event_id_01","values":["HwAds"]}]}]},{"type":"selector","dimension":"category","value":"design"}]},"granularity":"day","currency":"USD","interval":"' . $start . 'T00:00:00.000Z/' . $end . 'T00:00:00.000Z","dimension":"country_code","threshold":500}';
        }
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'application/json, text/plain, */*';
        $httpHeader[] = 'Content-Type: application/json;charset=utf-8';
        $r = $this->curl_request($url, $httpHeader, $data, false);
        return json_decode($r, true);
    }


    //事件请求
    private function event_query($gameid, $token, $date = "")
    {
        $URL = "https://query-3.gameanalytics.com/v1/batch/{$gameid}";
        $access_token = $token;
        $country = "US,TW,HK,JP,KR,DE,FR,CN,RU,CA,GB,TH,BR,ID";
        $httpHeader = array();
        $data = json_encode(
            [
                "/v1/games/{$gameid}/design/EndlessTime?start={$date}&end={$date}&country={$country}&aggregation=event_count", "/v1/games/{$gameid}/design/VideoWatchtime%3AReviveVideoWatch?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/ReviveScreen%3AShow?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/TimeLimitTim?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/Interloaded%3ADeathInterloaded%3A.*?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/Interloaded%3AReviveInterloaded%3A.*?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/Interloaded%3ATimeLimitPassInterloaded%3A.*?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/Videoloaded%3AReviveVideoloaded%3A.*?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/VideoWatchtime?start={$date}&end={$date}&country={$country}&aggregation=event_count",
                "/v1/games/{$gameid}/design/InterWatchtime?start={$date}&end={$date}&country={$country}&aggregation=event_count"
            ]
        );
        $httpHeader[] = 'Authorization:' . $access_token;
        $httpHeader[] = 'Accept: application/json; */*';
        $r = $this->curl_request($URL, $httpHeader, $data, false);
        return json_decode($r, true);
    }

    private function curl_request($url, $httpHeader, $data = [], $ispost = true)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
        }		
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret = curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
    }

    //请求
    private function googlecurl($url, $data = null, $method = null)
    {
        $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    //广告ID添加
    public function aaa()
    {
        $adconfig = array(
            ["name" => "interChangeToVideoCount", "val" => "0", "desc" => "插屏转视频的次数，默认是0，不转；具体数值就代表转换几次", "app_class" => 2],
            ["name" => "interChangeToVideoIntervalTime", "val" => "3", "desc" => "插屏转视频，间隔视频的时间，默认是3分钟", "app_class" => 2],
            ["name" => "AuditAds", "val" => "0", "desc" => "审核期间，广告总开关，默认是0，默认总开关关；1是开", "app_class" => 2],
            ["name" => "NetWorkDetectionCanClose", "val" => "0", "desc" => "网络检测期间，默认是0，显示'稍后再说'文案，点击可以关闭；若设置成1，显示“刷新”文案，必须设置才可关闭", "app_class" => 2]
        );

        $res = Db::name("app")->select();
        foreach ($res as $vv) {
            foreach ($adconfig as &$y) {
                $r = Db::name("adconfig")->where(["appid" => $vv["id"], "name" => $y["name"]])->find();
                if (empty($r)) {
                    $y["appid"] = $vv["id"];
                    $y["adduser"] = "lxf";
                    $y["updateuser"] = "lxf";
                    $y["addtime"] = date("Y-m-d H:i:s", time());
                    Db::name("adconfig")->insert($y);
                }
            }
        }
        echo "ok";
    }

    public function getRequestUrl()
    {
        $base_url = "https://www.facebook.com/analytics/";
        $common_param = "?section=overview&range_type=DATE_RANGE";
        $output = [];
        $RequestURL = $this->countryparams();
        $start = date("Y-m-d", strtotime("-2 day"));
        date_default_timezone_set('US/Arizona');
        $start_date = $start;
        $end_date = $start;
        $start = strtotime($start_date) . "000";
        $end = strtotime($end_date) . "000";
        $app = Db::name("count_update")->where(["isupdate" => 1])->order("update_uninx_time asc")->find();
        if (!empty($app)) {

            foreach ($RequestURL as $k => $vv) {
                $url = $base_url . $app["static_id"] . "/" . $common_param . "&since=" . $start . "&until=" . $end . $vv["param"];
                $output[$k] = ["url" => $url, "date" => $start_date, "country" => $vv["country"], "appid" => $app["app_id"], "facebookid" => $app["static_id"]];
            }
        } else {
            $res = Db::name("count_update")->where(["isupdate" => 2])->order("update_uninx_time asc")->find();
            if (!empty($res)) {
                $nowtime = time();
                $c_time = $nowtime - $res["update_uninx_time"];
                if ($c_time >= 180) {
                    Db::name("count_update")->where(["isupdate" => 2])->update(["isupdate" => 1]);
                    return $this->getRequestUrl();
                }
            }
        }
        return $output;
    }

    public function update_data($data = "")
    {
        if (!empty($data)) {
            $appid = "";
            $json_data = json_decode($data, true);
            foreach ($json_data as $kk => &$vv) {
                if ($vv["new_users"] != "--" || $vv["active_users"] != "--") {
                    if (preg_match("/&nbsp;万/", $vv["new_users"])) {
                        $vv["new_users"] = str_replace("&nbsp;万", "", $vv["new_users"]) * 10000;
                    }
                    if (preg_match("/&nbsp;万/", $vv["active_users"])) {
                        $vv["active_users"] = str_replace("&nbsp;万", "", $vv["active_users"]) * 10000;
                    }

                    $vv["new_users"] = str_replace(",", "", $vv["new_users"]);
                    $vv["active_users"] = str_replace(",", "", $vv["active_users"]);
                    $this->inserdata($vv["date"], $vv["appid"], "new_users", $vv["country"], $vv["new_users"]);
                    $this->inserdata($vv["date"], $vv["appid"], "active_users", $vv["country"], $vv["active_users"]);
                }
                $appid = $vv["appid"];
            }
            Db::name("count_update")->where(["app_id" => $appid])->update(["isupdate" => 2, "update_uninx_time" => time(), "update_date" => date("Y-m-d H:i:s", time())]);
        }
        echo "ok";
    }

    //更新产品数据 谷歌
    public function update_product($data = "")
    {
        $data = json_decode($data, true);
        if (!empty($data) && isset($data["app_package"])) {
            $date = date("Y-m-d");

            $data["comment_users"] = str_replace(",", "", $data["comment_users"]);
            $data["date"] = $date;
            $r = Db::name("product_grap")->field("id")->where(["app_package" => $data["app_package"], "platform" => "android", "country" => $data["country"], "date" => $date])->find();
            if (!empty($r)) {
                unset($data["app_icon"]);
                unset($data["title"]);
                Db::name("product_grap")->where(["id" => $r["id"]])->update($data);
            } else {
                // 11.  命中和击倒
                $arr = explode(".  ", $data["title"]);
                $data["title"] = $arr[1];
                $data["topnum"] = $arr[0];
                $data["app_icon"] = $this->download($data["app_icon"]);
                Db::name("product_grap")->insert($data);
            }
            Db::name("grap_url")->where(["country" => $data["country"], "platform" => "android"])->update(["update" => $date]);
        }
        echo "ok";
    }

    //更新产品数据 苹果
    public function update_iosproduct($data = "")
    {
        #header("Content-Type: text/html;charset=utf-8");
        #$data = $_POST?$_POST:$_REQUEST;
        #print_r($data);exit;
        $data = json_decode($data, true);
        if (!empty($data) && isset($data["app_package"])) {
            $date = date("Y-m-d");
            $url_data = parse_url($data["app_package"]);
            $url_data = explode("/", $url_data["path"]);
            $data["app_package"] = array_pop($url_data);
            $data["topnum"] = str_replace(".", "", $data["topnum"]);
            $data["app_icon"] = "https://www.apple.com" . $data["app_icon"];
            $data["date"] = $date;
            $r = Db::name("product_grap")->field("id")->where(["app_package" => $data["app_package"], "platform" => "ios", "country" => $data["country"], "date" => $date])->find();
            if (!empty($r)) {
                unset($data["app_icon"]);
                unset($data["title"]);
                Db::name("product_grap")->where(["id" => $r["id"]])->update($data);
            } else {

                Db::name("product_grap")->insert($data);
            }
            Db::name("grap_url")->where(["country" => $data["country"], "platform" => "ios"])->update(["update" => $date]);
        }
        echo "ok";
    }

    public function getgoogleimg($url = "")
    {
        Header("Content-type: image/png");
        if ($url != "") {
            $url = "https:" . $url;

            echo file_get_contents($url);
        }
    }

    function base64EncodeImage($image_file)
    {
        $base64_image = '';
        $image_data = file_get_contents($image_file);
        $base64_image = 'data:png;base64,' . chunk_split(base64_encode($image_data));
        return "<img src=" . $base64_image . "/>";
    }

    public function previews($ad_id = "")
    {
        if ($ad_id != "") {
            $row = Db::name("aaa_report")->where(["video_id"=>$ad_id])->find();
			if(!empty($row))
			{
				$config = 'mysql://thehotgames:week2e13&hellowd@localhost:3306/ads_service#utf8mb4';
				$r = Db::connect($config)->query('SELECT vv.path  from ads_video vv JOIN ads_channel_video cv on vv.id=cv.video_id WHERE cv.channel=2 and cv.channel_video_id=:video_id',['video_id'=>$ad_id]);
				$path = isset($r[0]['path'])?"http://gbsc.7766.org:3030/uploads".$r[0]['path']:"";
				echo "<video controls='controls' autoplay='autoplay' width='450' height='400' src='{$path}'></video>";exit;
			}
			$ad_format = "MOBILE_FEED_STANDARD"; //DESKTOP_FEED_STANDARD
            $access_token = "EAADQrlS8Xb0BAOGa7ZBrwCmdt8awlAZBjddK8hFShw8DF7ihidh6SHheIQjfEY9RwpJCN4cC9Vgsb5tZAuxFeJIoQ4duywQbUjLIMFaVXS1X2QnsZAyBqcYU3M0SVMZCn8CWq2RMimLKb3y4e8ZCsH25t5NLCYw90LLy8gZAvrW1xZAguw3mEcYxUXjIj2ddjrMZD";
            $width = "800";
            $height = "520";
            $url = "https://graph.facebook.com/v9.0/{$ad_id}/previews?ad_format=" . $ad_format . "&access_token=" . $access_token . "&width=" . $width . "&height=" . $height;
            $content = $this->googlecurl($url, [], 'GET');
            $data = json_decode($content, true);
            if (isset($data["data"][0]["body"])) {
                echo $data["data"][0]["body"];
                exit;
            }

        }
        echo "fail";
    }


    function hexToStr($hex)
    {
        $str = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2)
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        return $str;
    }

    //获取抓取的链接
    public function grap_url($type)
    {
        $date = date("Y-m-d", time());
        #$res = Db::name("grap_url")->where( ["platform"=>$type] )->find();
        $res = Db::query("select * from hellowd_grap_url where platform='{$type}' and `update`!='{$date}' limit 1");
        return $res;
    }

    public function fans()
    {
        $url = "https://data.wxb.com/searchResult?kw=https://mp.weixin.qq.com/s?__biz=MzU0Mjc1Mjc4Nw==&page=1";
        $headers = array(
            'Host: data.wxb.com',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64; rv:63.0) Gecko/20100101 Firefox/63.0',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        );

        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //禁止 cURL 验证对等证书
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //是否检测服务器的域名与证书上的是否一

        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);


        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        print_r($data);
        exit;
    }

    public function download($url)
    {
        $url = "https:" . $url;
        $file = file_get_contents($url);

        $filename = pathinfo($url, PATHINFO_BASENAME);
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'product/';
        $last_path = $path . $filename . ".png";
        file_put_contents($last_path, $file);
        return '/uploads/product/' . $filename . ".png";
    }

    private function countryparams()
    {
        $RequestURL = [
            [
                "country" => "all",
                "param" => ""
            ],
            [
                "country" => "US",
                "param" => "&segment=eyJpZCI6IjIwOTE0MzI2ODExODU4NzEiLCJuYW1lIjoi576O5Zu9IiwiZXZlbnRfcnVsZXMiOltdLCJldmVudF9pbmZvX3J1bGVzIjpbeyJwYXJhbWV0ZXJfbmFtZSI6ImNvdW50cnkiLCJvcGVyYXRvciI6ImlfaXNfYW55IiwidmFsdWVzIjpbIlVTIl0sInJ1bGVUeXBlIjoiREVNT0dSQVBISUMifV0sInVzZXJfcHJvcGVydHlfcnVsZXMiOltdLCJ3ZWJfcGFyYW1fcnVsZXMiOltdfQ%3D%3D"
            ],
            [
                "country" => "TW",
                "param" => "&segment=eyJpZCI6IjIxMDM5MjEyOTMyNzAzNDMiLCJuYW1lIjoi5Y%2Bw5rm%2BIiwiZXZlbnRfcnVsZXMiOltdLCJldmVudF9pbmZvX3J1bGVzIjpbeyJwYXJhbWV0ZXJfbmFtZSI6ImNvdW50cnkiLCJvcGVyYXRvciI6ImlfaXNfYW55IiwidmFsdWVzIjpbIlRXIl0sInJ1bGVUeXBlIjoiREVNT0dSQVBISUMifV0sInVzZXJfcHJvcGVydHlfcnVsZXMiOltdLCJ3ZWJfcGFyYW1fcnVsZXMiOltdfQ%3D%3D"
            ],
            [
                "country" => "HK",
                "param" => "&segment=eyJpZCI6IjIxMDM5MjE5NjY2MDM2MDkiLCJuYW1lIjoi6aaZ5rivIiwiZXZlbnRfcnVsZXMiOltdLCJldmVudF9pbmZvX3J1bGVzIjpbeyJwYXJhbWV0ZXJfbmFtZSI6ImNvdW50cnkiLCJvcGVyYXRvciI6ImlfaXNfYW55IiwidmFsdWVzIjpbIkhLIl0sInJ1bGVUeXBlIjoiREVNT0dSQVBISUMifV0sInVzZXJfcHJvcGVydHlfcnVsZXMiOltdLCJ3ZWJfcGFyYW1fcnVsZXMiOltdfQ%3D%3D"
            ],
            [
                "country" => 'CN',
                "param" => "&segment=eyJpZCI6IjIxNDYwMzU5OTU3MjU1MzkiLCJuYW1lIjoi5Lit5Zu9IiwiZXZlbnRfcnVsZXMiOltdLCJldmVudF9pbmZvX3J1bGVzIjpbeyJwYXJhbWV0ZXJfbmFtZSI6ImNvdW50cnkiLCJvcGVyYXRvciI6ImlfaXNfYW55IiwidmFsdWVzIjpbIkNOIl0sInJ1bGVUeXBlIjoiREVNT0dSQVBISUMifV0sInVzZXJfcHJvcGVydHlfcnVsZXMiOltdLCJ3ZWJfcGFyYW1fcnVsZXMiOltdfQ%3D%3D"
            ]
        ];
        return $RequestURL;
    }

    //留存
    public function getRequestRet()
    {

        $base_url = "https://www.facebook.com/analytics/";
        $common_param = "?section=retention&range_type=DATE_RANGE";
        $output = [];
        date_default_timezone_set('US/Arizona');
        $start_date = "2018-09-25";
        $end_date = "2018-09-28";
        $start = strtotime($start_date) . "000";
        $end = strtotime($end_date) . "000";
        $app = Db::name("count_update")->where(["isretupdate" => 1])->order("update_uninx_time asc")->find();
        $RequestURL = $this->countryparams();
        if (!empty($app)) {
            foreach ($RequestURL as $k => $vv) {
                $url = $base_url . $app["static_id"] . "/" . $common_param . "&since=" . $start . "&until=" . $end . $vv["param"];
                $output[$k] = ["url" => $url, "date" => "2018-09-26", "country" => $vv["country"], "appid" => $app["app_id"], "facebookid" => $app["static_id"]];
            }
        } else {
            $res = Db::name("count_update")->where(["isretupdate" => 2])->order("ret_update_uninx_time asc")->find();
            if (!empty($res)) {
                $nowtime = time();
                $c_time = $nowtime - $res["ret_update_uninx_time"];
                if ($c_time >= 180) {
                    Db::name("count_update")->where(["isretupdate" => 2])->update(["isretupdate" => 1]);
                    return $this->getRequestRet();
                }
            }
        }
        return $output;
    }


    //更新留存
    public function update_reten($data = "")
    {
        header("Content-Type: text/html;charset=utf-8");
        //$data ='[{"appid":"81","country":"all","data":[{"day1":"<div class=\"_2je8\">31.6%</div>","day2":"<div class=\"_2je8\">22.5%</div>","day3":"<div class=\"_2je8\">22.6%</div>","date":"<span>权重平均值<span class=\"_lx9 _3-99 _3b62\"><i alt=\"\" class=\"img sp_v3rDQP3fUMa sx_d3bd8b\"></i></span></span>"},{"day1":"37.4%","day2":"26.2%","day3":"22.6%","date":"9 月 25 日"},{"day1":"29.2%","day2":"20.3%","date":"9 月 26 日"},{"day1":"30.6%","date":"9 月 27 日"},{"date":"9 月 28 日"}]}]';
        if (!empty($data)) {
            $appid = "";
            $res = json_decode($data, true);

            if (!empty($res)) {
                foreach ($res as $vv) {
                    $list = $vv["data"];
                    array_shift($list);
                    foreach ($list as &$vvv) {
                        $date = date("Y") . "-" . str_replace(" 日", "", str_replace(" 月 ", "-", $vvv["date"]));
                        $date_arr = explode("-", $date);
                        $date_arr = array_map(function ($str) {
                            if (strlen($str) == 1) {
                                $str = "0" . $str;
                            }
                            return $str;
                        }, $date_arr);
                        $date = implode("-", $date_arr);
                        if (isset($vvv["retention_1"])) {
                            $vvv["retention_1"] = str_replace("%", "", $vvv["retention_1"]) / 100;
                        }
                        if (isset($vvv["retention_2"])) {
                            $vvv["retention_2"] = str_replace("%", "", $vvv["retention_2"]) / 100;
                        }
                        if (isset($vvv["retention_3"])) {
                            $vvv["retention_3"] = str_replace("%", "", $vvv["retention_3"]) / 100;
                        }

                        $r = Db::name("retention")->where(["app_id" => $vv["appid"], "date" => $date, "country" => $vv["country"]])->find();
                        if (empty($r)) {
                            $vvv["app_id"] = $vv["appid"];
                            $vvv["country"] = $vv["country"];
                            Db::name("retention")->insert($vvv);
                        } else {
                            unset($vvv["date"]);
                            Db::name("retention")->where("id", $r["id"])->update($vvv);
                        }
                    }
                    $appid = $vv["appid"];
                }
            }
            Db::name("count_update")->where(["app_id" => $appid])->update(["isretupdate" => 2, "ret_update_uninx_time" => time(), "ret_update_date" => date("Y-m-d H:i:s", time())]);
        }
        echo "ok";
    }

}
 