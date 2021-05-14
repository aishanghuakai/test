<?php


namespace app\api\controller;


use think\Db;

set_time_limit(0);
ini_set('memory_limit', '-1');
class AdjustData
{

    //推广渠道
    private $promate_media = array(
        "Mintegral_int" => "mintegral_int",
        "头条" => "bytedance_int",
        "Facebook Installs" => "Facebook Ads",
        "Unity_int" => "unityads_int",
        "Applovin_int" => "applovin_int",
        "ironsource_int" => "ironsource_int",
        "Vungle_int" => "vungle_int",
        'Adwords UAC Installs' => "googleadwords_int",
        'Google Ads UAC' => "googleadwords_int",
		'Google Ads ACI'=> "googleadwords_int",
		'Google Ads ACE'=> "googleadwords_int",
        'Adwords Installs' => 'googleadwords_int',
        'Tapjoy_int' => "tapjoy_int",
        'Chartboost_int' => "chartboosts2s_int",
        'Tiktok_int' => "tiktok_int",
    );

    /**
     * 获取真实的推广渠道
     */
    private function _get_real_media_source($media_source,$all=false){
        if (isset($this->promate_media[$media_source])) {
            return $this->promate_media[$media_source];
        }
        if ($all){
            if (in_array($media_source, ['Facebook Installs','Instagram Installs', 'Unattributed', 'Off-Facebook Installs'])) {
                return "Facebook Ads";
            }
        }
        return $media_source;
    }

    /**
     * 获取真实的广告渠道
     * @param $ad_source
     */
    private function _get_real_ad_source($ad_source){
        $real_ad_source = $ad_source;
        if (trim($ad_source) == "GooglePlayServices") {
            $real_ad_source = "Admob";
        }
        if (in_array($ad_source, ['VastVideo', 'Mraid', 'MoPubRewardedPlayabl'])) {
            $real_ad_source = "MoPub";
        }
        if ($ad_source == "Minteral") {
            $real_ad_source = "Mintegral";
        }
        if (preg_match("/MoPubRewardedPlayabl/", $ad_source)) {
            $real_ad_source = "MoPub";
        }
        if (in_array($ad_source, ['Unity', 'Unity Ads', 'Unityads'])) {
            $real_ad_source = "UnityAds";
        }
        if (trim($ad_source) == "MINTEGRAL") {
            $real_ad_source = "Mintegral";
        }
        if (trim($ad_source) == "FACEBOOK") {
            $real_ad_source = "Facebook";
        }
        if (trim($ad_source) == "Google AdMob") {
            $real_ad_source = "Admob";
        }
        return $real_ad_source;
    }

    /**
     * 初始化拉取 ADJUST 展示
     * @param $date
     * @param string $time_zone
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function initAdjustImpression($date="",$time_zone="pdt"){
        if (!in_array($time_zone,["pst","pdt"])){
            exit("we only get PST/PDT");
        }
        if (!$date){
            $date = date("Y-m-d",strtotime("-1 day"));
        }
        $table_name = "adjust_impression";
        $table_name .= "_".$time_zone;
        $url = "https://analytics.gamebrain.io/gb_get_adjust/getAdjustImpression?date={$date}&time_zone={$time_zone}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name($table_name)->where(["event_date_real"=>$date])->delete();
                $insert_data = [];
                foreach ($res as &$r) {
                    $r["media_source"] = $this->_get_real_media_source($r["media_source"],true);
                    $r["ad_source"] = $this->_get_real_ad_source($r["ad_source"]);
                    $r["event_date_real"] = $date;
                    if (count($insert_data)<10000){
                        array_push($insert_data,$r);
                    }else{
                        Db::name($table_name)->insertAll($insert_data);
                        $insert_data = [];
                    }
                }
                if ($insert_data){
                    Db::name($table_name)->insertAll($insert_data);
                }
            }
        }
        exit("we get adjust impression on".$date);
    }
	
	private function get_cam_row($campaign_name,$type){
		$row = Db::name("adspend_data")->field('campaign_id')->where(["campaign_name"=>$campaign_name,"platform_type"=>$type ])->order('id desc')->find();
		return isset($row['campaign_id'])?$row['campaign_id']:"";
	}
	
	public function update_campaign(){
		$sql =" SELECT bb.campaign_name,aa.campaign_id  from 
  ( SELECT campaign_name  from  hellowd_adjust_campaign_pdt WHERE media_source='unityads_int' and campaign_id='' GROUP BY campaign_name ) bb
 LEFT JOIN ( SELECT campaign_id,campaign_name from hellowd_adspend_data 
WHERE platform_type=2 and date>='2021-03-19' GROUP BY campaign_id) aa ON bb.campaign_name=aa.campaign_name";
        $res = Db::query($sql);
		
		if(!empty($res))
		{
			foreach($res as $v)
			{
				if($v['campaign_id']!="")
				{
					Db::name('adjust_campaign_pdt')->where(['media_source'=>'unityads_int','campaign_name'=>$v['campaign_name']])->update(["campaign_id"=>$v['campaign_id']]);
				}				
			}
		}
		exit("ok");
	}

    /**
     * 初始化拉取 ADJUST Campaign
     * @param string $date
     * @param string $time_zone
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function initAdjustCampaign($date="",$time_zone="pdt"){
        if (!in_array($time_zone,["pst","pdt"])){
            exit("we only get PST/PDT");
        }
        if (!$date){
            $date = date("Y-m-d",strtotime("-1 day"));
        }
        $table_name = "adjust_campaign";
        $table_name .= "_".$time_zone;
        $url = "https://analytics.gamebrain.io/gb_get_adjust/getAdjustCampaign?date={$date}&time_zone={$time_zone}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name($table_name)->where(["event_date_real"=>$date])->delete();
                $insert_data = [];
                foreach ($res as &$r) {
                    $r["media_source"] = $this->_get_real_media_source($r["media_source"],true);
                    $r["ad_source"] = $this->_get_real_ad_source($r["ad_source"]);
                    $r["event_date_real"] = $date;
                    $campaign_name = $r['campaign_name'];
					if(strtolower($r["media_source"])=="tiktok_int")
					{
						$campaign_name = strrchr($campaign_name,'&');
                        $campaign_id = str_replace('&','',$campaign_name);
					}elseif(in_array($r['media_source'],['Unity_int','unityads_int'])){
						//$campaign_id = $this->get_cam_row($campaign_name,2);
						$campaign_id="";
					}elseif($r["media_source"]=='Mintegral_int'){
						$campaign_id = $this->get_cam_row("HelloWorld_MTG".$campaign_name,1);
					}elseif($r["media_source"]=='Vungle_int'){
						$campaign_name = strrchr($campaign_name,'_');
						$campaign_name = str_replace('_','',$campaign_name);
                        $campaign_id = str_replace('_','',$campaign_name);
					}else{
						$campaign_name = strrchr($campaign_name,'(');
                        $campaign_name = str_replace('(','',$campaign_name);
						$campaign_id = str_replace(')','',$campaign_name);
					}
                    				
                    $r["campaign_id"] = $campaign_id;
                    if (count($insert_data)<10000){
                        array_push($insert_data,$r);
                    }else{
                        Db::name($table_name)->insertAll($insert_data);
                        $insert_data = [];
                    }
                }
                if ($insert_data){
                    Db::name($table_name)->insertAll($insert_data);
                }
            }
        }
		$this->update_campaign();
        exit("we get adjust impression on".$date);
    }
	
	public function initAdjustGroup($date="",$time_zone="pdt"){
        if (!in_array($time_zone,["pst","pdt"])){
            exit("we only get PST/PDT");
        }
        if (!$date){
            $date = date("Y-m-d",strtotime("-1 day"));
        }
        $table_name = "adjust_adgroup";
        $table_name .= "_".$time_zone;
        $url = "https://analytics.gamebrain.io/gb_get_adjust/getAdjustGroup?date={$date}&time_zone={$time_zone}";
        $result = json_decode(curl($url), true);
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                Db::name($table_name)->where(["event_date_real"=>$date])->delete();
                $insert_data = [];
                foreach ($res as &$r) {
                    $r["media_source"] = $this->_get_real_media_source($r["media_source"],true);
                    $r["ad_source"] = $this->_get_real_ad_source($r["ad_source"]);
                    $r["event_date_real"] = $date;
					$r["adset_id"] = $r["adset_name"];
					if(strtolower($r["media_source"])=="tiktok_int")
					{
						$name = strrchr($r["adset_name"],'&');
                        $name = str_replace('&','',$name);
						$arr = explode("-",$name);		
						$r["adset_id"] = $arr[0];
					}
                    if (count($insert_data)<10000){
                        array_push($insert_data,$r);
                    }else{
                        Db::name($table_name)->insertAll($insert_data);
                        $insert_data = [];
                    }
                }
                if ($insert_data){
                    Db::name($table_name)->insertAll($insert_data);
                }
            }
        }
        exit("we get adjust impression on".$date);
    }


    /**
     * 拉取 adjust 广告展示数据
     * @param string $date
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function initAdjustView($date="",$is_all=false){
        if (!$date){
            $date = date("Y-m-d",strtotime("-1 day"));
        }
        $url = "https://analytics.gamebrain.io/gb_get_adjust/getAdjustVideo?date={$date}&is_all={$is_all}";
        $result = json_decode(curl($url), true);
        $table_name = "adjust_view";
        if (isset($result["data"]) && !empty($result["data"])) {
            $res = $result["data"];
            if (!empty($res)) {
                $where = ["event_date"=>$date];
                if ($is_all){
                    $where['adtype'] = '';
                }else{
                    $where['adtype'] = ['neq',''];
                }
                Db::name($table_name)->where($where)->delete();
                $insert_data = [];
                foreach ($res as &$r) {
                    if (count($insert_data)<10000){
                        array_push($insert_data,$r);
                    }else{
                        Db::name($table_name)->insertAll($insert_data);
                        $insert_data = [];
                    }
                }
                if ($insert_data){
                    Db::name($table_name)->insertAll($insert_data);
                }
            }
        }
        exit("we get adjust view on".$date);
    }

    /**
     * 拉取Adjust 留存报告
     * @param int $app_id
     * @param string $date
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function initRetentionAdjust($app_id=0,$date=''){

        if (!$date){
            $date = date("Y-m-d", strtotime("-1 day"));
        }

        $where = ['adjust'=>['neq','']];
        if ($app_id) $where = ['app_id' => $app_id];
        $field = 'app_id,adjust';
        $adjust_token_list = Db::name("bind_attr")->field($field)->where($where)->select();

        foreach ( $adjust_token_list as $adjust_token_info ){
            $this->_get_retention_report($date,$adjust_token_info["app_id"],$adjust_token_info["adjust"]);
            $this->_get_retention_report_by_country($date,$adjust_token_info["app_id"],$adjust_token_info["adjust"]);
            $this->_get_retention_report_by_network($date,$adjust_token_info["app_id"],$adjust_token_info["adjust"]);
            $this->_get_retention_report_all($date,$adjust_token_info["app_id"],$adjust_token_info["adjust"]);
        }

        exit("we get adjust retention on".$date);

    }

    /**
     * 获取单个应用的数据 并插入数据库(分国家、分渠道)
     * @param $date
     * @param $app_id
     * @param $adjust_token
     * @return bool
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    private function _get_retention_report($date,$app_id,$adjust_token){

        $need_retention_ext = [1, 2, 3, 7, 14, 28];
        $need_retention_dates= [];
        foreach ($need_retention_ext as $need_retention){
            $need_retention_date = date("Y-m-d", strtotime($date)-$need_retention*86400);
            array_push($need_retention_dates,$need_retention_date);
            $insert_item_data_re["retention_".$need_retention] = 0;
            $insert_item_data_re["retention_user_".$need_retention] = 0;
        }
        $api_url = "https://api.adjust.com/kpis/v1/";
        $api_url_ext = "/cohorts.json?";
        $api_url_ext .= "start_date=".date("Y-m-d", strtotime($date)-28*86400);
        $api_url_ext .= "&end_date=".$date;
        $api_url_ext .= "&utc_offset=00:00";
        $api_url_ext .= "&kpis=retention_rate,retained_users";
        $api_url_ext .= "&attribution_type=click";
        $api_url_ext .= "&grouping=day,countries,networks";
        $api_url_ext .= "&period=day";
        $api_url_ext .= "&cohort_period_filter=".implode(',',$need_retention_ext);
        $api_url_ext .= "&user_token=zDxyxVAafpjFX-yYMbvK";

        $real_api_url = $api_url.$adjust_token.$api_url_ext;
        $result = json_decode(curl($real_api_url), true);

        if ( !isset($result["result_set"]['dates']) ) return false;

        $retention_data_dates = $result["result_set"]['dates'];

        if ( empty($retention_data_dates) ) return false;

        foreach ($retention_data_dates as $retention_data_date){
            $retention_date = $retention_data_date["date"];
            $insert_item_data_where = [
                "app_id" => $app_id,
                "date" => $retention_date,
            ];
            if (in_array($retention_date,$need_retention_dates)){
                $insert_data = [];
                $retention_data_countries = $retention_data_date["countries"];
                foreach ($retention_data_countries as $retention_data_country){
                    $country = strtoupper($retention_data_country["country"]);
                    $retention_data_networks = $retention_data_country["networks"];
                    foreach ($retention_data_networks as $retention_data_network){
                        $media_source = $this->_get_real_media_source($retention_data_network["name"]);
                        $periods = $retention_data_network["periods"];
                        $insert_item_data_where["country"] = $country;
                        $insert_item_data_where["media_source"] = $media_source;
                        $insert_item_data = array_merge($insert_item_data_where,$insert_item_data_re);
                        $add_user = [];
                        foreach ($periods as $period){
                            if (strtotime($retention_date)+86400*$period['period']>strtotime($date)) continue;
                            $insert_item_data["retention_".$period['period']] = $period["kpi_values"][0];
                            $insert_item_data["retention_user_".$period['period']] = $period["kpi_values"][1];
                            if ($period["kpi_values"][0]) array_push($add_user,$period["kpi_values"][1]/$period["kpi_values"][0]);
                        }
                        $insert_item_data['add_user'] = count($add_user)?intval(array_sum($add_user)/count($add_user)):0;
                        if (Db::name("retention_adjust")->where($insert_item_data_where)->find()){
                            Db::name("retention_adjust")->where($insert_item_data_where)->update($insert_item_data);
                        }else{
                            array_push($insert_data,$insert_item_data);
                        }
                    }
                }
                if ($insert_data){
                    Db::name("retention_adjust")->insertAll($insert_data);
                }
            }
        }

        echo "we get APP_ID => ".$app_id." adjust retention on".$date."_c_n</br>";

        return true;
    }

    /**
     * 获取单个应用的数据 并插入数据库(分国家)
     * @param $date
     * @param $app_id
     * @param $adjust_token
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function _get_retention_report_by_country($date,$app_id,$adjust_token){

        $need_retention_ext = [1, 2, 3, 7, 14, 28];
        $need_retention_dates= [];
        foreach ($need_retention_ext as $need_retention){
            $need_retention_date = date("Y-m-d", strtotime($date)-($need_retention)*86400);
            array_push($need_retention_dates,$need_retention_date);
            $insert_item_data_re["retention_".$need_retention] = 0;
            $insert_item_data_re["retention_user_".$need_retention] = 0;
        }
        $api_url = "https://api.adjust.com/kpis/v1/";
        $api_url_ext = "/cohorts.json?";
        $api_url_ext .= "start_date=".date("Y-m-d", strtotime($date)-28*86400);
        $api_url_ext .= "&end_date=".$date;
        $api_url_ext .= "&utc_offset=00:00";
        $api_url_ext .= "&kpis=retention_rate,retained_users";
        $api_url_ext .= "&attribution_type=click";
        $api_url_ext .= "&grouping=day,countries";
        $api_url_ext .= "&period=day";
        $api_url_ext .= "&cohort_period_filter=".implode(',',$need_retention_ext);
        $api_url_ext .= "&user_token=zDxyxVAafpjFX-yYMbvK";

        $real_api_url = $api_url.$adjust_token.$api_url_ext;
        $result = json_decode(curl($real_api_url), true);

        if ( !isset($result["result_set"]['dates']) ) return false;

        $retention_data_dates = $result["result_set"]['dates'];

        if ( empty($retention_data_dates) ) return false;

        foreach ($retention_data_dates as $retention_data_date){
            $retention_date = $retention_data_date["date"];
            $insert_item_data_where = [
                "app_id" => $app_id,
                "date" => $retention_date,
            ];
            if (in_array($retention_date,$need_retention_dates)) {
                $insert_data = [];
                $retention_data_countries = $retention_data_date["countries"];
                foreach ($retention_data_countries as $retention_data_country) {
                    $country = strtoupper($retention_data_country["country"]);
                    $media_source = "all";
                    $periods = $retention_data_country["periods"];
                    $insert_item_data_where["country"] = $country;
                    $insert_item_data_where["media_source"] = $media_source;
                    $insert_item_data = array_merge($insert_item_data_where, $insert_item_data_re);
                    $add_user = [];
                    foreach ($periods as $period) {
                        if (strtotime($retention_date)+86400*$period['period']>strtotime($date)) continue;
                        $insert_item_data["retention_" . $period['period']] = $period["kpi_values"][0];
                        $insert_item_data["retention_user_" . $period['period']] = $period["kpi_values"][1];
                        if ($period["kpi_values"][0]) array_push($add_user, $period["kpi_values"][1] / $period["kpi_values"][0]);
                    }
                    $insert_item_data['add_user'] = count($add_user) ? intval(array_sum($add_user) / count($add_user)) : 0;
                    if (Db::name("retention_adjust")->where($insert_item_data_where)->find()) {
                        Db::name("retention_adjust")->where($insert_item_data_where)->update($insert_item_data);
                    } else {
                        array_push($insert_data, $insert_item_data);
                    }
                }
                if ($insert_data) {
                    Db::name("retention_adjust")->insertAll($insert_data);
                }
            }
        }

        echo "we get APP_ID => ".$app_id." adjust retention on".$date."_c</br>";

        return true;
    }

    /**
     * 获取单个应用的数据 并插入数据库(分渠道)
     * @param $date
     * @param $app_id
     * @param $adjust_token
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function _get_retention_report_by_network($date,$app_id,$adjust_token){

        $need_retention_ext = [1, 2, 3, 7, 14, 28];
        $need_retention_dates= [];
        foreach ($need_retention_ext as $need_retention){
            $need_retention_date = date("Y-m-d", strtotime($date)-($need_retention)*86400);
            array_push($need_retention_dates,$need_retention_date);
            $insert_item_data_re["retention_".$need_retention] = 0;
            $insert_item_data_re["retention_user_".$need_retention] = 0;
        }
        $api_url = "https://api.adjust.com/kpis/v1/";
        $api_url_ext = "/cohorts.json?";
        $api_url_ext .= "start_date=".date("Y-m-d", strtotime($date)-28*86400);
        $api_url_ext .= "&end_date=".$date;
        $api_url_ext .= "&utc_offset=00:00";
        $api_url_ext .= "&kpis=retention_rate,retained_users";
        $api_url_ext .= "&attribution_type=click";
        $api_url_ext .= "&grouping=day,networks";
        $api_url_ext .= "&period=day";
        $api_url_ext .= "&cohort_period_filter=".implode(',',$need_retention_ext);
        $api_url_ext .= "&user_token=zDxyxVAafpjFX-yYMbvK";

        $real_api_url = $api_url.$adjust_token.$api_url_ext;
        $result = json_decode(curl($real_api_url), true);

        if ( !isset($result["result_set"]['dates']) ) return false;

        $retention_data_dates = $result["result_set"]['dates'];

        if ( empty($retention_data_dates) ) return false;

        foreach ($retention_data_dates as $retention_data_date){
            $retention_date = $retention_data_date["date"];
            $insert_item_data_where = [
                "app_id" => $app_id,
                "date" => $retention_date,
            ];
            if (in_array($retention_date,$need_retention_dates)) {
                $insert_data = [];
                $retention_data_networks = $retention_data_date["networks"];
                foreach ($retention_data_networks as $retention_data_network) {
                    $country = "all";
                    $media_source = $this->_get_real_media_source($retention_data_network["name"]);
                    $periods = $retention_data_network["periods"];
                    $insert_item_data_where["country"] = $country;
                    $insert_item_data_where["media_source"] = $media_source;
                    $insert_item_data = array_merge($insert_item_data_where, $insert_item_data_re);
                    $add_user = [];
                    foreach ($periods as $period) {
                        if (strtotime($retention_date)+86400*$period['period']>strtotime($date)) continue;
                        $insert_item_data["retention_" . $period['period']] = $period["kpi_values"][0];
                        $insert_item_data["retention_user_" . $period['period']] = $period["kpi_values"][1];
                        if ($period["kpi_values"][0]) array_push($add_user, $period["kpi_values"][1] / $period["kpi_values"][0]);
                    }
                    $insert_item_data['add_user'] = count($add_user) ? intval(array_sum($add_user) / count($add_user)) : 0;
                    if (Db::name("retention_adjust")->where($insert_item_data_where)->find()) {
                        Db::name("retention_adjust")->where($insert_item_data_where)->update($insert_item_data);
                    } else {
                        array_push($insert_data, $insert_item_data);
                    }
                }
                if ($insert_data) {
                    Db::name("retention_adjust")->insertAll($insert_data);
                }
            }
        }

        echo "we get APP_ID => ".$app_id." adjust retention on".$date."_n</br>";

        return true;
    }

    /**
     * 获取单个应用的数据 并插入数据库
     * @param $date
     * @param $app_id
     * @param $adjust_token
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function _get_retention_report_all($date,$app_id,$adjust_token){

        $need_retention_ext = [1, 2, 3, 7, 14, 28];
        $need_retention_dates= [];
        foreach ($need_retention_ext as $need_retention){
            $need_retention_date = date("Y-m-d", strtotime($date)-($need_retention)*86400);
            array_push($need_retention_dates,$need_retention_date);
            $insert_item_data_re["retention_".$need_retention] = 0;
            $insert_item_data_re["retention_user_".$need_retention] = 0;
        }
        $api_url = "https://api.adjust.com/kpis/v1/";
        $api_url_ext = "/cohorts.json?";
        $api_url_ext .= "start_date=".date("Y-m-d", strtotime($date)-28*86400);
        $api_url_ext .= "&end_date=".$date;
        $api_url_ext .= "&utc_offset=00:00";
        $api_url_ext .= "&kpis=retention_rate,retained_users";
        $api_url_ext .= "&attribution_type=click";
        $api_url_ext .= "&grouping=day";
        $api_url_ext .= "&period=day";
        $api_url_ext .= "&cohort_period_filter=".implode(',',$need_retention_ext);
        $api_url_ext .= "&user_token=zDxyxVAafpjFX-yYMbvK";

        $real_api_url = $api_url.$adjust_token.$api_url_ext;
        $result = json_decode(curl($real_api_url), true);

        if ( !isset($result["result_set"]['dates']) ) return false;

        $retention_data_dates = $result["result_set"]['dates'];

        if ( empty($retention_data_dates) ) return false;

        $insert_data = [];
        foreach ($retention_data_dates as $retention_data_date){
            $retention_date = $retention_data_date["date"];
            $insert_item_data_where = [
                "app_id" => $app_id,
                "date" => $retention_date,
            ];
            if (in_array($retention_date,$need_retention_dates)) {
                $country = "all";
                $media_source = "all";
                $periods = $retention_data_date["periods"];
                $insert_item_data_where["country"] = $country;
                $insert_item_data_where["media_source"] = $media_source;
                $insert_item_data = array_merge($insert_item_data_where, $insert_item_data_re);
                $add_user = [];
                foreach ($periods as $period) {
                    if (strtotime($retention_date)+86400*$period['period']>strtotime($date)) continue;
                    $insert_item_data["retention_" . $period['period']] = $period["kpi_values"][0];
                    $insert_item_data["retention_user_" . $period['period']] = $period["kpi_values"][1];
                    if ($period["kpi_values"][0]) array_push($add_user, $period["kpi_values"][1] / $period["kpi_values"][0]);
                }
                $insert_item_data['add_user'] = count($add_user) ? intval(array_sum($add_user) / count($add_user)) : 0;
                if (Db::name("retention_adjust")->where($insert_item_data_where)->find()) {
                    Db::name("retention_adjust")->where($insert_item_data_where)->update($insert_item_data);
                } else {
                    array_push($insert_data, $insert_item_data);
                }
            }
        }
        if ($insert_data){
            Db::name("retention_adjust")->insertAll($insert_data);
        }

        echo "we get APP_ID => ".$app_id." adjust retention on".$date."_all</br>";

        return true;
    }

}