<?php


namespace app\api\controller;

use think\Db;

set_time_limit(0);
ini_set('memory_limit', '-1');

class AdjustLtvData
{

    private $AdSource = array(
        ["name" => "Admob", "value" => "Admob", "channel" => "5"],
        ["name" => "Sigmob", "value" => "Sigmob", "channel" => "34"],
        ["name" => "IronSource", "value" => "IronSource", "channel" => "7"],
        ["name" => "Ironsource", "value" => "Ironsource", "channel" => "7"],
        ["name" => "GDT", "value" => "GDT", "channel" => "35"],
        ["name" => "Vungle", "value" => "Vungle", "channel" => "4"],
        ["name" => "UnityAds", "value" => "UnityAds", "channel" => "2"],
        ["name" => "Facebook", "value" => "Facebook", "channel" => "6"],
        ["name" => "穿山甲", "value" => "CSJ", "channel" => "32"],
        ["name" => "Applovin", "value" => "Applovin", "channel" => "3"],
        ["name" => "AppLovin", "value" => "AppLovin", "channel" => "3"],
        ["name" => "Mintegral", "value" => "Mintegral", "channel" => "1"],
        ["name" => "MoPub", "value" => "MoPub", "channel" => "37"],
        ["name" => "Mopub", "value" => "Mopub", "channel" => "37"],
        ["name" => "Other", "value" => "Other", "channel" => "37"],
        ["name" => "InMobi", "value" => "InMobi", "channel" => "40"],
        ["name" => "AdColony", "value" => "AdColony", "channel" => "31"]
    );

    /**
     * 获取真实的广告渠道
     * @param $platform
     */
    private function _get_real_real_ad_source($platform, $status = true)
    {
        if ($status) {
            $ad_source_by_platform = array_column($this->AdSource, "value", "channel");
        } else {
            $ad_source_by_platform = array_column($this->AdSource, "channel", "value");
        }
        return isset($ad_source_by_platform[$platform]) ? $ad_source_by_platform[$platform] : "NONE";
    }

    /**
     * 获取真实广告类型
     * @param $adtype
     */
    private function _get_real_adType($adtype, $status = true)
    {
        if ($adtype == 'rew' || $adtype == 'Reward') {
            return $status ? "Reward" : "rew";
        }
        if ($adtype == 'int' || $adtype == 'Inter') {
            return $status ? "Inter" : "int";
        }
        return $status ? "NONE" : '';
    }

    /**
     * 初始化 LTV 数据
     * @param string $date
     * @param string $time_zone
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function initAdjustLtv($date = "", $time_zone = "pdt", $gb_id = 0,$day="1")
    {
        // 只处理 PST/PDT 时区
        if (!in_array($time_zone, ["pdt", "pst"])) {
            exit("this API must use on PDT/PST");
        }
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-{$day} day"));
        }
        //展示 数据表名
        $impression_table_name = "adjust_impression";
        $impression_table_name .= "_" . $time_zone;
        // 查询当天 展示数据 是否
        $impression_data = Db::name($impression_table_name)->where(["event_date_real" => $date])->find();
        if (empty($impression_data)) {
            exit("wait impression update");
        }
        // 获取当天收益数据
        $cash_data_field = "sys_app_id,country,platform,adtype,date";
        $cash_data_field .= ",SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date,
            'sys_app_id' => $gb_id ? $gb_id : ['gt', 0],
            'revenue' => ['gt', 0],
            'adtype' => ['in', ['int', 'rew']]
        ];
        $cash_data_group = "sys_app_id,country,platform,adtype";
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $install_ltv_data = [];
        foreach ($cash_data as $cash_data_item) {
            //查询 符合条件 打点数据集合
            $impression_data_field = "gb_id,country,adtype,device_category,media_source,install_date_real as install_date";
            $impression_data_field .= ",event_date_real as event_date,ad_source,sum(num) as num";
            $impression_data_where = [
                'gb_id' => $cash_data_item["sys_app_id"],
                'country' => $cash_data_item["country"],
                'event_date_real' => $cash_data_item['date'],
                'ad_source' => $this->_get_real_real_ad_source($cash_data_item["platform"]),
                'adtype' => $this->_get_real_adType($cash_data_item['adtype'])
            ];
            $impression_data_group = "gb_id,country,adtype,device_category,media_source,install_date_real";
            $impression_data = Db::name($impression_table_name)
                ->field($impression_data_field)
                ->where($impression_data_where)
                ->group($impression_data_group)->select();
            if (!empty($impression_data)) {
                // 计算 LTV 值 借助矫正数据计算
                $adcash_num = $cash_data_item["impression"];
                $adcash_revenue = $cash_data_item["revenue"];
                $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;
                $adjust_num = array_sum(array_column($impression_data, 'num'));
                $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
                foreach ($impression_data as &$impression_data_item) {
                    $impression_data_item["num"] = $impression_data_item["num"] * $adjust_ratio;
                    $impression_data_item["revenue"] = $impression_data_item["num"] * $adcash_cpm;
                    $field = $impression_data_item["gb_id"];
                    $field .= "#" . $impression_data_item["country"];
                    $field .= "#" . $impression_data_item["media_source"];
                    $field .= "#" . $impression_data_item["install_date"];
                    $field .= "#" . $impression_data_item["event_date"];
                    $field .= "#" . $impression_data_item["device_category"];
                    $field = md5($field);
                    if (!isset($install_ltv_data[$field])) {
                        $install_ltv_data[$field] = [
                            "gb_id" => $impression_data_item["gb_id"],
                            "country" => $impression_data_item["country"],
                            "media_source" => $impression_data_item["media_source"],
                            "install_date" => $impression_data_item["install_date"],
                            "event_date" => $impression_data_item["event_date"],
                            "device_category" => $impression_data_item["device_category"],
                            "revenue" => 0,
                            "int_show" => 0,
                            "rew_show" => 0,
                        ];
                    }
                    $install_ltv_data[$field]["revenue"] += $impression_data_item["revenue"];
                    if ($impression_data_item["adtype"] == 'Inter') {
                        $install_ltv_data[$field]["int_show"] += $impression_data_item["num"];
                    } else {
                        $install_ltv_data[$field]["rew_show"] += $impression_data_item["num"];
                    }
                }
            }
        }
        //LTV 数据更新
        $adjust_ltv_table_name = "adjust_ltv";
        $adjust_ltv_table_name .= "_" . $time_zone;
        // 删除异常数据
        $delete_where = ["event_date" => $date];
        if ($gb_id) $delete_where['gb_id'] = $gb_id;
        Db::name($adjust_ltv_table_name)->where($delete_where)->delete();
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }

        exit("adjust LTV update success!");
    }
	
	
	public function initAdjustFbLtv($date = "", $time_zone = "pdt", $gb_id = 0)
    {
        // 只处理 PST/PDT 时区
        if (!in_array($time_zone, ["pdt", "pst"])) {
            exit("this API must use on PDT/PST");
        }
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        //展示 数据表名
        $impression_table_name = "adjust_impression";
        $impression_table_name .= "_" . $time_zone;
        // 查询当天 展示数据 是否
        $impression_data = Db::name($impression_table_name)->where(["event_date_real" => $date])->find();
        if (empty($impression_data)) {
            exit("wait impression update");
        }
        // 获取当天收益数据
        $cash_data_field = "sys_app_id,country,platform,adtype,date";
        $cash_data_field .= ",SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date,
			'platform'=>6,
            'sys_app_id' => $gb_id ? $gb_id : ['gt', 0],
            'revenue' => ['gt', 0],
            'adtype' => ['in', ['int', 'rew']]
        ];
        $cash_data_group = "sys_app_id,country,platform,adtype";
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $install_ltv_data = [];
        foreach ($cash_data as $cash_data_item) {
            //查询 符合条件 打点数据集合
            $impression_data_field = "gb_id,country,adtype,device_category,media_source,install_date_real as install_date";
            $impression_data_field .= ",event_date_real as event_date,ad_source,sum(num) as num";
            $impression_data_where = [
                'gb_id' => $cash_data_item["sys_app_id"],
                'country' => $cash_data_item["country"],
                'event_date_real' => $cash_data_item['date'],
                'ad_source' =>'Facebook',
                'adtype' => $this->_get_real_adType($cash_data_item['adtype'])
            ];
            $impression_data_group = "gb_id,country,adtype,device_category,media_source,install_date_real";
            $impression_data = Db::name($impression_table_name)
                ->field($impression_data_field)
                ->where($impression_data_where)
                ->group($impression_data_group)->select();
            if (!empty($impression_data)) {
                // 计算 LTV 值 借助矫正数据计算
                $adcash_num = $cash_data_item["impression"];
                $adcash_revenue = $cash_data_item["revenue"];
                $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;
                $adjust_num = array_sum(array_column($impression_data, 'num'));
                $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
                foreach ($impression_data as &$impression_data_item) {
                    $impression_data_item["num"] = $impression_data_item["num"] * $adjust_ratio;
                    $impression_data_item["revenue"] = $impression_data_item["num"] * $adcash_cpm;
                    $field = $impression_data_item["gb_id"];
                    $field .= "#" . $impression_data_item["country"];
                    $field .= "#" . $impression_data_item["media_source"];
                    $field .= "#" . $impression_data_item["install_date"];
                    $field .= "#" . $impression_data_item["event_date"];
                    $field .= "#" . $impression_data_item["device_category"];
                    $field = md5($field);
                    if (!isset($install_ltv_data[$field])) {
                        $install_ltv_data[$field] = [
                            "gb_id" => $impression_data_item["gb_id"],
                            "country" => $impression_data_item["country"],
                            "media_source" => $impression_data_item["media_source"],
                            "install_date" => $impression_data_item["install_date"],
                            "event_date" => $impression_data_item["event_date"],
                            "device_category" => $impression_data_item["device_category"],
                            "revenue" => 0,
                            "int_show" => 0,
                            "rew_show" => 0,
                        ];
                    }
                    $install_ltv_data[$field]["revenue"] += $impression_data_item["revenue"];
                    if ($impression_data_item["adtype"] == 'Inter') {
                        $install_ltv_data[$field]["int_show"] += $impression_data_item["num"];
                    } else {
                        $install_ltv_data[$field]["rew_show"] += $impression_data_item["num"];
                    }
                }
            }
        }
        //LTV 数据更新
        $adjust_ltv_table_name = "applovin_max";
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }

        exit("adjust LTV update success!");
    }

    public function initAdjustCampaignLtvCheck($date = '', $gb_id = 0)
    {
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        //展示 数据表名
        $campaign_table_name = "adjust_campaign";
        // 查询当天 展示数据
        $campaign_data_field = "gb_id,campaign_id,campaign_name,country,adtype,media_source,ad_source,install_date";
        $campaign_data_field .= ",event_date,sum(num) as num";
        $campaign_data_group = "gb_id,campaign_name,country,adtype,ad_source,install_date";
        $campaign_where = ["event_date" => $date];
        if ($gb_id) $campaign_where['gb_id'] = $gb_id;
        $campaign_data = Db::name($campaign_table_name)
            ->field($campaign_data_field)
            ->where($campaign_where)
            ->group($campaign_data_group)
            ->select();
        if (empty($campaign_data)) {
            exit("wait impression update");
        }
        // 查询当天 收益数据
        $cash_data_field = "SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date
        ];
        if ($gb_id) $cash_data_where['sys_app_id'] = $gb_id;
        $cash_data_group = "country,sys_app_id,adtype,platform";
        $cash_data_field .= "," . $cash_data_group;
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $cash_data_out = [];
        foreach ($cash_data as $cash_item) {
            $sign = '#';
            $sign .= '#' . $cash_item['country'];
            $sign .= '#' . $cash_item['sys_app_id'];
            $sign .= '#' . $cash_item['adtype'];
            $sign .= '#' . $cash_item['platform'];
            $sign = md5($sign);
            $cash_data_out[$sign] = [
                'impression' => $cash_item['impression'],
                'revenue' => $cash_item['revenue'],
            ];
        }
        // 查询当天 展示数据
        $impression_field = "SUM(num) as num";
        $impression_group = "country,gb_id,adtype,ad_source";
        $impression_field .= "," . $impression_group;
        $impression_where = [
            'event_date' => $date,
        ];
        if ($gb_id) $impression_where['gb_id'] = $gb_id;
        $impression_data = Db::name("adjust_impression")
            ->field($impression_field)
            ->where($impression_where)
            ->group($impression_group)
            ->select();
        $impression_data_out = [];
        foreach ($impression_data as $impression_item) {
            $sign = '#';
            $sign .= '#' . $impression_item['country'];
            $sign .= '#' . $impression_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($impression_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($impression_item['ad_source'], false);
            $sign = md5($sign);
            $impression_data_out[$sign] = [
                'num' => $impression_item['num'],
            ];
        }
        $install_ltv_data = [];
        foreach ($campaign_data as $campaign_item) {
            $sign = '#';
            $sign .= '#' . $campaign_item['country'];
            $sign .= '#' . $campaign_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($campaign_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($campaign_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($cash_data_out[$sign]) || !isset($impression_data_out[$sign])) continue;
            $cash_data_re = $cash_data_out[$sign];
            $impression_data_re = $impression_data_out[$sign];
            $adcash_num = $cash_data_re["impression"];
            $adcash_revenue = $cash_data_re["revenue"];
            $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;
            $adjust_num = $impression_data_re['num'];
            $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
            $campaign_num_real = $campaign_item["num"] * $adjust_ratio;

            $field = $campaign_item["campaign_id"];
            $field .= "#" . $campaign_item["country"];
            $field .= "#" . $campaign_item["gb_id"];
            $field .= "#" . $campaign_item["media_source"];
            $field .= "#" . $campaign_item["install_date"];
            $field .= "#" . $campaign_item["event_date"];
            $field = md5($field);
            if (!isset($install_ltv_data[$field])) {
                $install_ltv_data[$field] = [
                    "gb_id" => $campaign_item["gb_id"],
                    "country" => $campaign_item["country"],
                    "media_source" => $campaign_item["media_source"],
                    "install_date" => $campaign_item["install_date"],
                    "event_date" => $campaign_item["event_date"],
                    "revenue" => 0,
                    "int_show" => 0,
                    "rew_show" => 0,
                    "campaign_id" => $campaign_item["campaign_id"],
                    "campaign_name" => $campaign_item["campaign_name"],
                ];
            }
            $install_ltv_data[$field]["revenue"] += $campaign_num_real * $adcash_cpm;
            if ($campaign_item["adtype"] == 'Inter') {
                $install_ltv_data[$field]["int_show"] += $campaign_num_real;
            } else {
                $install_ltv_data[$field]["rew_show"] += $campaign_num_real;
            }

        }
        //LTV 数据更新
        $adjust_ltv_table_name = "adjust_campaign_ltv";
        $adjust_ltv_table_name .= "_check";
        // 删除异常数据
        $delete_where = ["event_date" => $date];
        if ($gb_id) $delete_where['gb_id'] = $gb_id;
        Db::name($adjust_ltv_table_name)->where($delete_where)->delete();
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }
        exit("adjust campaign LTV update success!");
    }

    //新增子渠道LTV数据

    public function initAdjustGroupLtvCheck($date = '', $gb_id = 0)
    {
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        //展示 数据表名
        $group_table_name = "adjust_adgroup_pdt";
        // 查询当天 展示数据
        $group_data_field = "gb_id,adset_id,adset_name,country,adtype,media_source,ad_source,install_date";
        $group_data_field .= ",event_date_real as event_date,sum(num) as num";
        $group_data_group = "gb_id,adset_id,country,adtype,ad_source,install_date_real";
        $group_data_where = ["event_date_real" => $date];
        if ($gb_id) $group_data_where['gb_id'] = $gb_id;
        $group_data = Db::name($group_table_name)
            ->field($group_data_field)
            ->where($group_data_where)
            ->group($group_data_group)
            ->select();
        if (empty($group_data)) {
            exit("wait impression update");
        }
        // 查询当天 收益数据
        $cash_data_field = "SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date
        ];
        if ($gb_id) $cash_data_where['sys_app_id'] = $gb_id;
        $cash_data_group = "country,sys_app_id,adtype,platform";
        $cash_data_field .= "," . $cash_data_group;
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $cash_data_out = [];
        foreach ($cash_data as $cash_item) {
            $sign = '#';
            $sign .= '#' . $cash_item['country'];
            $sign .= '#' . $cash_item['sys_app_id'];
            $sign .= '#' . $cash_item['adtype'];
            $sign .= '#' . $cash_item['platform'];
            $sign = md5($sign);
            $cash_data_out[$sign] = [
                'impression' => $cash_item['impression'],
                'revenue' => $cash_item['revenue'],
            ];
        }
        // 查询当天 展示数据
        $impression_field = "SUM(num) as num";
        $impression_group = "country,gb_id,adtype,ad_source";
        $impression_field .= "," . $impression_group;
        $impression_where = [
            'event_date' => $date,
        ];
        if ($gb_id) $impression_where['gb_id'] = $gb_id;
        $impression_data = Db::name("adjust_impression_pdt")
            ->field($impression_field)
            ->where($impression_where)
            ->group($impression_group)
            ->select();
        $impression_data_out = [];
        foreach ($impression_data as $impression_item) {
            $sign = '#';
            $sign .= '#' . $impression_item['country'];
            $sign .= '#' . $impression_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($impression_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($impression_item['ad_source'], false);
            $sign = md5($sign);
            if (isset($impression_data_out[$sign])) {
                $impression_data_out[$sign]["num"] += $impression_item['num'];
            } else {
                $impression_data_out[$sign] = [
                    'num' => $impression_item['num'],
                ];
            }
        }
        $install_ltv_data = [];
        foreach ($group_data as $campaign_item) {
            $sign = '#';
            $sign .= '#' . $campaign_item['country'];
            $sign .= '#' . $campaign_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($campaign_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($campaign_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($cash_data_out[$sign]) || !isset($impression_data_out[$sign])) continue;
            $cash_data_re = $cash_data_out[$sign];
            $impression_data_re = $impression_data_out[$sign];
            $adcash_num = $cash_data_re["impression"];
            $adcash_revenue = $cash_data_re["revenue"];
            $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;

            $adjust_num = $impression_data_re['num'];
            $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
            $campaign_num_real = $campaign_item["num"] * $adjust_ratio;

            $field = $campaign_item["adset_id"];
            $field .= "#" . $campaign_item["country"];
            $field .= "#" . $campaign_item["gb_id"];
            $field .= "#" . $campaign_item["media_source"];
            $field .= "#" . $campaign_item["install_date"];
            $field .= "#" . $campaign_item["event_date"];
            $field = md5($field);
            if (!isset($install_ltv_data[$field])) {
                $install_ltv_data[$field] = [
                    "gb_id" => $campaign_item["gb_id"],
                    "country" => $campaign_item["country"],
                    "media_source" => $campaign_item["media_source"],
                    "install_date" => $campaign_item["install_date"],
                    "event_date" => $campaign_item["event_date"],
                    "revenue" => 0,
                    "int_show" => 0,
                    "rew_show" => 0,
                    "adset_id" => $campaign_item["adset_id"],
                    "adset_name" => $campaign_item["adset_name"],
                ];
            }
            $install_ltv_data[$field]["revenue"] += $campaign_num_real * $adcash_cpm;
            if ($campaign_item["adtype"] == 'Inter') {
                $install_ltv_data[$field]["int_show"] += $campaign_num_real;
            } else {
                $install_ltv_data[$field]["rew_show"] += $campaign_num_real;

            }

        }

        //LTV 数据更新
        $adjust_ltv_table_name = "adjust_adgroup_ltv";
        $adjust_ltv_table_name .= "_pdt";
        // 删除异常数据
        $delete_where = ["event_date" => $date];
        if ($gb_id) $delete_where['gb_id'] = $gb_id;
        Db::name($adjust_ltv_table_name)->where($delete_where)->delete();
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }
        exit("adjust adgroup LTV update success!");
    }

    /**
     * 初始化 Campaign 数据
     * @param string $date
     * @param string $time_zone
     */
    public function initAdjustCampaignLtv($date = "", $time_zone = "pdt", $gb_id = 0)
    {
        // 只处理 PST/PDT 时区
        if (!in_array($time_zone, ["pdt", "pst"])) {
            exit("this API must use on PDT/PST");
        }
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-1 day"));
        }
        //展示 数据表名
        $campaign_table_name = "adjust_campaign";
        $campaign_table_name .= "_" . $time_zone;
        // 查询当天 展示数据
        $campaign_data_field = "gb_id,campaign_id,campaign_name,country,adtype,media_source,ad_source,install_date_real as install_date";
        $campaign_data_field .= ",event_date_real as event_date,sum(num) as num";
        $campaign_data_group = "gb_id,campaign_name,country,adtype,ad_source,install_date_real";
        $campaign_data_where = ["event_date_real" => $date,];
        if ($gb_id) $campaign_data_where['gb_id'] = $gb_id;
        $campaign_data = Db::name($campaign_table_name)
            ->field($campaign_data_field)
            ->where($campaign_data_where)
            ->group($campaign_data_group)
            ->select();
        if (empty($campaign_data)) {
            exit("wait impression update");
        }
        // 查询当天 收益数据
        $cash_data_field = "SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date
        ];
        if ($gb_id) $cash_data_where['sys_app_id'] = $gb_id;
        $cash_data_group = "country,sys_app_id,adtype,platform";
        $cash_data_field .= "," . $cash_data_group;
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $cash_data_out = [];
        foreach ($cash_data as $cash_item) {
            $sign = '#';
            $sign .= '#' . $cash_item['country'];
            $sign .= '#' . $cash_item['sys_app_id'];
            $sign .= '#' . $cash_item['adtype'];
            $sign .= '#' . $cash_item['platform'];
            $sign = md5($sign);
            $cash_data_out[$sign] = [
                'impression' => $cash_item['impression'],
                'revenue' => $cash_item['revenue'],
            ];
        }
        // 查询当天 展示数据
        $impression_field = "SUM(num) as num";
        $impression_group = "country,gb_id,adtype,ad_source";
        $impression_field .= "," . $impression_group;
        $impression_where = [
            'event_date_real' => $date,
        ];
        if ($gb_id) $impression_where['gb_id'] = $gb_id;
        $impression_data = Db::name("adjust_impression" . "_" . $time_zone)
            ->field($impression_field)
            ->where($impression_where)
            ->group($impression_group)
            ->select();
        $impression_data_out = [];
        foreach ($impression_data as $impression_item) {
            $sign = '#';
            $sign .= '#' . $impression_item['country'];
            $sign .= '#' . $impression_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($impression_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($impression_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($impression_data_out[$sign])) {
                $impression_data_out[$sign] = [
                    'num' => 0,
                ];
            }
            $impression_data_out[$sign]['num'] += $impression_item['num'];
        }
        $install_ltv_data = [];
        foreach ($campaign_data as &$campaign_item) {
            $sign = '#';
            $sign .= '#' . $campaign_item['country'];
            $sign .= '#' . $campaign_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($campaign_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($campaign_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($cash_data_out[$sign]) || !isset($impression_data_out[$sign])) continue;
            // 计算 LTV 值 借助矫正数据计算
//            $cash_data_field = "SUM(impression) as impression,SUM(revenue) as revenue";
//            $cash_data_where = [
//                'date' => $date,
//                'country' => $campaign_item['country'],
//                'sys_app_id' => $campaign_item['gb_id'],
//                'adtype' => $this->_get_real_adType($campaign_item['adtype'],false),
//                'platform' => $this->_get_real_real_ad_source($campaign_item['ad_source'],false)
//            ];
//            $cash_data = Db::name("adcash_data")
//                ->field($cash_data_field)
//                ->where($cash_data_where)
//                ->find();
//            $impression_field = "SUM(num) as num";
//            $impression_where = [
//                'event_date_real' => $date,
//                'country' => $campaign_item['country'],
//                'gb_id' => $campaign_item['gb_id'],
//                'adtype' => $campaign_item['adtype'],
//                'ad_source' => $campaign_item['ad_source']
//            ];
//            $impression_data = Db::name("adjust_impression"."_".$time_zone)
//                ->field($impression_field)
//                ->where($impression_where)
//                ->find();
//            $adcash_num = $cash_data["impression"];
//            $adcash_revenue = $cash_data["revenue"];
//            $adcash_cpm = $adcash_num?bcdiv($adcash_revenue,$adcash_num,4):0;
//            $adjust_num = $impression_data['num'];
//            $adjust_ratio = $adjust_num?bcdiv($adcash_num,$adjust_num,4):0;
//            $campaign_num_real = $campaign_item["num"] * $adjust_ratio;
            $cash_data_re = $cash_data_out[$sign];
            $impression_data_re = $impression_data_out[$sign];
            $adcash_num = $cash_data_re["impression"];
            $adcash_revenue = $cash_data_re["revenue"];
            $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;
            $adjust_num = $impression_data_re['num'];
            $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
            $campaign_num_real = $campaign_item["num"] * $adjust_ratio;

            $field = $campaign_item["campaign_id"];
            $field .= "#" . $campaign_item["country"];
            $field .= "#" . $campaign_item["gb_id"];
            $field .= "#" . $campaign_item["media_source"];
            $field .= "#" . $campaign_item["install_date"];
            $field .= "#" . $campaign_item["event_date"];
            $field = md5($field);
            if (!isset($install_ltv_data[$field])) {
                $install_ltv_data[$field] = [
                    "gb_id" => $campaign_item["gb_id"],
                    "country" => $campaign_item["country"],
                    "media_source" => $campaign_item["media_source"],
                    "install_date" => $campaign_item["install_date"],
                    "event_date" => $campaign_item["event_date"],
                    "revenue" => 0,
                    "int_show" => 0,
                    "rew_show" => 0,
                    "campaign_id" => $campaign_item["campaign_id"],
                    "campaign_name" => $campaign_item["campaign_name"],
                ];
            }
            $install_ltv_data[$field]["revenue"] += $campaign_num_real * $adcash_cpm;
            if ($campaign_item["adtype"] == 'Inter') {
                $install_ltv_data[$field]["int_show"] += $campaign_num_real;
            } else {
                $install_ltv_data[$field]["rew_show"] += $campaign_num_real;
            }

        }
        //LTV 数据更新
        $adjust_ltv_table_name = "adjust_campaign_ltv";
        $adjust_ltv_table_name .= "_" . $time_zone;
        // 删除异常数据
        $delete_where = ["event_date" => $date];
        if ($gb_id) $delete_where['gb_id'] = $gb_id;
        Db::name($adjust_ltv_table_name)->where($delete_where)->delete();
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }
        exit("adjust campaign LTV update success!");
    }
	
	
	
	public function initAdjustCampaignFBLtv($date = "", $time_zone = "pdt", $gb_id = 0)
    {
       
        // 默认前一天
        if (!$date) {
            $date = date("Y-m-d", strtotime("-2 day"));
        }
        $campaign_table_name = "adjust_campaign";
        $campaign_table_name .= "_" . $time_zone;
        // 查询当天 展示数据
        $campaign_data_field = "gb_id,campaign_id,campaign_name,country,adtype,media_source,ad_source,install_date_real as install_date";
        $campaign_data_field .= ",event_date_real as event_date,sum(num) as num";
        $campaign_data_group = "gb_id,campaign_name,country,adtype,ad_source,install_date_real";
        $campaign_data_where = ["event_date_real" => $date,];
        if ($gb_id) $campaign_data_where['gb_id'] = $gb_id;
        $campaign_data = Db::name($campaign_table_name)
            ->field($campaign_data_field)
            ->where($campaign_data_where)
            ->group($campaign_data_group)
            ->select();
        if (empty($campaign_data)) {
            exit("wait impression update");
        }
        // 查询当天 收益数据
        $cash_data_field = "SUM(impression) as impression,SUM(revenue) as revenue";
        $cash_data_where = [
            'date' => $date,
			'platform'=>6
        ];
        if ($gb_id) $cash_data_where['sys_app_id'] = $gb_id;
        $cash_data_group = "country,sys_app_id,adtype,platform";
        $cash_data_field .= "," . $cash_data_group;
        $cash_data = Db::name("adcash_data")
            ->field($cash_data_field)
            ->where($cash_data_where)
            ->group($cash_data_group)
            ->select();
        $cash_data_out = [];
        foreach ($cash_data as $cash_item) {
            $sign = '#';
            $sign .= '#' . $cash_item['country'];
            $sign .= '#' . $cash_item['sys_app_id'];
            $sign .= '#' . $cash_item['adtype'];
            $sign .= '#' . $cash_item['platform'];
            $sign = md5($sign);
            $cash_data_out[$sign] = [
                'impression' => $cash_item['impression'],
                'revenue' => $cash_item['revenue'],
            ];
        }
        // 查询当天 展示数据
        $impression_field = "SUM(num) as num";
        $impression_group = "country,gb_id,adtype,ad_source";
        $impression_field .= "," . $impression_group;
        $impression_where = [
            'event_date_real' => $date,
			'ad_source' =>'Facebook',
        ];
        if ($gb_id) $impression_where['gb_id'] = $gb_id;
        $impression_data = Db::name("adjust_impression" . "_" . $time_zone)
            ->field($impression_field)
            ->where($impression_where)
            ->group($impression_group)
            ->select();
        $impression_data_out = [];
        foreach ($impression_data as $impression_item) {
            $sign = '#';
            $sign .= '#' . $impression_item['country'];
            $sign .= '#' . $impression_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($impression_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($impression_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($impression_data_out[$sign])) {
                $impression_data_out[$sign] = [
                    'num' => 0,
                ];
            }
            $impression_data_out[$sign]['num'] += $impression_item['num'];
        }
        $install_ltv_data = [];
        foreach ($campaign_data as &$campaign_item) {
            $sign = '#';
            $sign .= '#' . $campaign_item['country'];
            $sign .= '#' . $campaign_item['gb_id'];
            $sign .= '#' . $this->_get_real_adType($campaign_item['adtype'], false);
            $sign .= '#' . $this->_get_real_real_ad_source($campaign_item['ad_source'], false);
            $sign = md5($sign);
            if (!isset($cash_data_out[$sign]) || !isset($impression_data_out[$sign])) continue;
            $cash_data_re = $cash_data_out[$sign];
            $impression_data_re = $impression_data_out[$sign];
            $adcash_num = $cash_data_re["impression"];
            $adcash_revenue = $cash_data_re["revenue"];
            $adcash_cpm = $adcash_num ? bcdiv($adcash_revenue, $adcash_num, 4) : 0;
            $adjust_num = $impression_data_re['num'];
            $adjust_ratio = $adjust_num ? bcdiv($adcash_num, $adjust_num, 4) : 0;
            $campaign_num_real = $campaign_item["num"] * $adjust_ratio;

            $field = $campaign_item["campaign_id"];
            $field .= "#" . $campaign_item["country"];
            $field .= "#" . $campaign_item["gb_id"];
            $field .= "#" . $campaign_item["media_source"];
            $field .= "#" . $campaign_item["install_date"];
            $field .= "#" . $campaign_item["event_date"];
            $field = md5($field);
            if (!isset($install_ltv_data[$field])) {
                $install_ltv_data[$field] = [
                    "gb_id" => $campaign_item["gb_id"],
                    "country" => $campaign_item["country"],
                    "media_source" => $campaign_item["media_source"],
                    "install_date" => $campaign_item["install_date"],
                    "event_date" => $campaign_item["event_date"],
                    "revenue" => 0,
                    "int_show" => 0,
                    "rew_show" => 0,
                    "campaign_id" => $campaign_item["campaign_id"],
                    "campaign_name" => $campaign_item["campaign_name"],
                ];
            }
            $install_ltv_data[$field]["revenue"] += $campaign_num_real * $adcash_cpm;
            if ($campaign_item["adtype"] == 'Inter') {
                $install_ltv_data[$field]["int_show"] += $campaign_num_real;
            } else {
                $install_ltv_data[$field]["rew_show"] += $campaign_num_real;
            }

        }
        //LTV 数据更新
        $adjust_ltv_table_name = "campaign_max";
        // 新增新数据
        $times = ceil(count($install_ltv_data) / 10000);
        while ($times) {
            $now_data = array_slice($install_ltv_data, 0, 10000);
            $install_ltv_data = array_slice($install_ltv_data, 10000);
            Db::name($adjust_ltv_table_name)->insertAll($now_data);
            $times--;
        }
        exit("adjust campaign LTV update success!");
    }

}