<?php


namespace app\admin\controller;


use think\Db;

class Operate extends Base
{

    /**
     * 内购
     */
    public function purchase($appid = "",$country='all',$start_date = "", $end_date = "", $index = 15){

        if ($appid == "") {
            $appid = getcache("select_app");
        } else {
            setcache("select_app", $appid);
        }

        if ($start_date == "" || $end_date == ""){
            $start_date = date("Y-m-d", strtotime("-".($index)." day"));
            $end_date = date("Y-m-d", strtotime("-1 day"));
        }

        $platform = Db::name('app')->where(['id'=>$appid])->value('platform');
        $group_person = $platform=='ios'?'idfa':'advertising_id';

        //adjust 的数据
        $all_where = ['gb_id'=>$appid];
        if ($country!='all') $all_where['country'] = $country;
        $all_info = Db::name('adjust_purchase_time_zone')
            ->field("count(`id`) as times,sum(`money`) as purchase,count(distinct(`{$group_person}`)) as person")
            ->where($all_where)
            ->find();
        //平台 数据
        $purchase_where = ['app_id'=>$appid];
        if ($country!='all') $purchase_where['country'] = $country;
        $purchase_info = Db::name('purchase_details')
            ->field("count(`id`) as times,sum(`total_usd_money`) as purchase,max(`date`) as last_date")
            ->where($purchase_where)
            ->find();
        $rate = $platform=='ios'?1:0.7;
        $all_info['purchase'] = round($purchase_info['purchase']*$rate,2);
        // adjust 预计收益
        $estimate_purchase_where = ['gb_id'=>$appid,'event_date_pdt'=>['gt',$purchase_info['last_date']]];
        if ($country!='all') $estimate_purchase_where['country'] = $country;
        $estimate_purchase_info = Db::name('adjust_purchase_time_zone')
            ->field("sum(`money`) as purchase")
            ->where($estimate_purchase_where)
            ->find();
        $all_info['estimate_purchase'] = round($estimate_purchase_info['purchase']*0.7,2);

        //今日数据
        $today_where = ['gb_id'=>$appid,'event_date_utc'=>date('Y-m-d')];
        if ($country!='all') $today_where['country'] = $country;
        $today_info = Db::name('adjust_purchase_time_zone')
            ->field("sum(`money`) as purchase,count(`id`) as times,count(distinct(`{$group_person}`)) as person")
            ->where($today_where)
            ->find();
        $today_new_where = ['gb_id'=>$appid,'install_date_utc'=>date('Y-m-d')];
        if ($country!='all') $today_new_where['country'] = $country;
        $today_new_info= Db::name('adjust_purchase_time_zone')
            ->field("sum(`money`) as purchase,count(`id`) as times,count(distinct(`{$group_person}`)) as person")
            ->where($today_new_where)
            ->find();
        $today_info['purchase'] = round($today_info['purchase']*0.7,2);
        $today_info['new_user_purchase'] = round($today_new_info['purchase']*0.7,2);
        $today_info['new_user_times'] = $today_new_info['times'];
        $today_info['new_user_person'] = $today_new_info['person'];

        //获取时间段内的 汇总数据
        $time_purchase_where = ['gb_id'=>$appid,'money'=>['gt',0],'event_date_utc'=>['between',[$start_date,$end_date]]];
        if ($country!='all') $time_purchase_where['country'] = $country;
        $time_purchase_info = Db::name('adjust_purchase_time_zone')
            ->field("count(`id`) as times,sum(`money`) as purchase,count(distinct(`{$group_person}`)) as person")
            ->where($time_purchase_where)->find();
        $time_purchase_where['install_date_utc'] = ['between',[$start_date,$end_date]];
        $time_purchase_new_user_info= Db::name('adjust_purchase_time_zone')
            ->field("count(`id`) as times,sum(`money`) as purchase,count(distinct(`{$group_person}`)) as person")
            ->where($time_purchase_where)->find();
        $time_info['purchase'] = round($time_purchase_info['purchase']*0.7,2);
        $time_info['times'] = $time_purchase_info['times'];
        $time_info['person'] = $time_purchase_info['person'];
        $time_info['all_person'] = 0;
        $time_info['all_active'] = 0;
        $time_info['all_new'] = 0;
        $time_info['new_purchase'] = round($time_purchase_new_user_info['purchase']*0.7,2);
        $time_info['new_times'] = $time_purchase_new_user_info['times'];
        $time_info['new_person'] = $time_purchase_new_user_info['person'];

        //获取时间段内内购数据情况
        $purchase_where = ['gb_id'=>$appid,'money'=>['gt',0],'event_date_utc'=>['between',[$start_date,$end_date]]];
        if ($country!='all') $purchase_where['country'] = $country;
        $purchase_data = Db::name('adjust_purchase_time_zone')
            ->field("event_date_utc as date,count(`id`) as times,sum(`money`) as purchase,count(distinct(`{$group_person}`)) as person")
            ->where($purchase_where)
            ->group('event_date_utc')
            ->order('event_date_utc asc')
            ->select();
        //获取时间段内新增用户内购数据
        $new_purchase_where = " event_date_utc = install_date_utc and money>0 ";
        $new_purchase_where .= " and gb_id = ". $appid." ";
        $new_purchase_where .= " and event_date_utc between '". $start_date."' and '".$end_date."' ";
        if ($country!='all') $new_purchase_where .= " and country='".$country."' ";
        $new_purchase_data = Db::name('adjust_purchase_time_zone')
            ->field("event_date_utc as date,count(`id`) as times,sum(`money`) as purchase,count(distinct(`{$group_person}`)) as person")
            ->where($new_purchase_where)
            ->group('event_date_utc')
            ->order('event_date_utc asc')
            ->select();
        $new_purchase_data = array_column($new_purchase_data,null,'date');
        //获取时间段内每日活跃用户
        $active_where = ['app_id'=>$appid,'country'=>$country?$country:'all','date'=>['between',[$start_date,$end_date]]];
        $active_data = Db::name('active_users')
            ->field('date,sum(val) as active_users')
            ->where($active_where)
            ->group('date')
            ->order('date asc')
            ->select();
        $active_data = array_column($active_data,'active_users','date');
        //获取时间段内每日新增用户
        $new_where = ['app_id'=>$appid,'country'=>$country?$country:'all','date'=>['between',[$start_date,$end_date]]];
        $new_data = Db::name('new_users')
            ->field('date,sum(val) as add_user')
            ->where($new_where)
            ->group('date')
            ->order('date asc')
            ->select();
        $new_data = array_column($new_data,'add_user','date');
        //获取时间段内每日收益情况
        $revenue_where = ['sys_app_id'=>$appid,'date'=>['between',[$start_date,$end_date]]];
        if ($country!='all') $revenue_where['country'] = $country;
        $revenue_data = Db::name('adcash_data')
            ->field('date,sum(revenue) as revenue')
            ->where($revenue_where)
            ->group('date')
            ->order('date asc')
            ->select();
        $revenue_data = array_column($revenue_data,'revenue','date');
        $out_data = [];
        $chats_date = [];
        $chats_purchase = [];
        $chats_purchase_times = [];
        $chats_purchase_person = [];
        foreach ($purchase_data as $data_item){
            $out_data_item = [];
            $new_purchase_item = isset($new_purchase_data[$data_item['date']])?$new_purchase_data[$data_item['date']]:[];
            $active_user = isset($active_data[$data_item['date']])?$active_data[$data_item['date']]:0;
            $time_info['all_active'] += $active_user;
            $new_user = isset($new_data[$data_item['date']])?$new_data[$data_item['date']]:0;
            $time_info['all_new'] += $new_user;
            $revenue = isset($revenue_data[$data_item['date']])?$revenue_data[$data_item['date']]:0;
            $out_data_item['date'] = $data_item['date'];
            $out_data_item['purchase'] =  round($data_item['purchase']*0.7,2);
            $out_data_item['person'] =  round($data_item['person'],2);
            $time_info['all_person'] += $out_data_item['person'];
            $out_data_item['times'] =  round($data_item['times'],2);
            $out_data_item['new_purchase'] = isset($new_purchase_item['purchase'])?round($new_purchase_item['purchase']*0.7,2):0;
            $out_data_item['new_person'] = isset($new_purchase_item['person'])?round($new_purchase_item['person'],2):0;
            $out_data_item['new_times'] = isset($new_purchase_item['times'])?round($new_purchase_item['times'],2):0;
            $out_data_item['purchase_rate'] = $active_user?round( $out_data_item['person']*100/$active_user,4):0;
            $out_data_item['new_person_purchase_rate'] = $new_user?round( $out_data_item['new_person']*100/$new_user,4):0;
            $out_data_item['new_person_proportion'] = $out_data_item['person']?round($out_data_item['new_person']*100/$out_data_item['person'],2):0;
            $revenue += $out_data_item['purchase'];
            $out_data_item['purchase_proportion'] = $revenue?round($out_data_item['purchase']*100/$revenue,4):0;
            array_push($chats_date,"'".$out_data_item['date']."'");
            array_push($out_data,$out_data_item);
            array_push($chats_purchase,$out_data_item['purchase']);
            array_push($chats_purchase_times,$out_data_item['times']);
            array_push($chats_purchase_person,$out_data_item['person']);
        }

        $this->assign("countryList", admincountry());
        return $this->fetch('purchase',[
            'country' => $country,
            'appid' => $appid,
            'index' => $index,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'all_info' => $all_info,
            'today_info' => $today_info,
            'time_info' => $time_info,
            'data' => $out_data,
            'chats' => [
                'date' => implode(',',$chats_date),
                'purchase' => implode(',',$chats_purchase),
                'purchase_times'=>implode(',',$chats_purchase_times),
                'purchase_person'=>implode(',',$chats_purchase_person)
            ],
        ]);
    }

    /**
     * 广告
     */
    public function advert($appid = "",$country='all',$start_date = "", $end_date = "", $index = 15){

        if ($appid == "") {
            $appid = getcache("select_app");
        } else {
            setcache("select_app", $appid);
        }

        if ($start_date == "" || $end_date == ""){
            $start_date = date("Y-m-d", strtotime("-".($index)." day"));
            $end_date = date("Y-m-d", strtotime("-1 day"));
        }else{
            $index = 0;
        }

        //获取 获取时间段内 广告展示数据
        $data_where = ['gb_id'=>$appid,'event_date'=>['between',[$start_date,$end_date]]];
        if ($country!='all') $data_where['country'] = $country;
        $data =  Db::name('adjust_view')
            ->field('install_date,event_date,adtype,SUM(video_num) as num,SUM(person) as person')
            ->where($data_where)
            ->group('install_date,event_date,adtype')
            ->order('event_date desc')
            ->select();
        //获取时间段内每日活跃用户
        $active_where = ['app_id'=>$appid,'country'=>$country?$country:'all','date'=>['between',[$start_date,$end_date]]];
        $active_data = Db::name('active_users')
            ->field('date,sum(val) as active_users')
            ->where($active_where)
            ->group('date')
            ->order('date asc')
            ->select();
        $active_data = array_column($active_data,'active_users','date');
        //获取时间段内每日新增用户
        $new_where = ['app_id'=>$appid,'country'=>$country?$country:'all','date'=>['between',[$start_date,$end_date]]];
        $new_data = Db::name('new_users')
            ->field('date,sum(val) as add_user')
            ->where($new_where)
            ->group('date')
            ->order('date asc')
            ->select();
        $new_data = array_column($new_data,'add_user','date');
        $out_data = [];
        foreach ($data as $data_item){
            if (!isset($out_data[$data_item['event_date']])){
                $out_data[$data_item['event_date']]=[
                    'date' => $data_item['event_date'],
                    'num' => 0,
                    'int_num' => 0,
                    'rew_num' => 0,
                    'other_num' => 0,
                    'person' => 0,
                    'int_person' => 0,
                    'rew_person' => 0,
                    'other_person' => 0,
                    'new_num' => 0,
                    'new_int_num' => 0,
                    'new_rew_num' => 0,
                    'new_other_num' => 0,
                    'new_person' => 0,
                    'new_int_person' => 0,
                    'new_rew_person' => 0,
                    'new_other_person' => 0,
                    'active' => $active_data[$data_item['event_date']],
                    'new_add' => $new_data[$data_item['event_date']],
                ];
            }
            switch ($data_item['adtype']){
                case 'Inter':
                    $key_type = 'int';
                    break;
                case 'Reward':
                    $key_type = 'rew';
                    break;
                case '':
                    $key_type = '';
                    break;
                default:
                    $key_type = 'other';
                    break;
            }
            if (!$key_type){
                $out_data[$data_item['event_date']]['num'] += $data_item['num'];
                $out_data[$data_item['event_date']]['person'] += $data_item['person'];
                if ($data_item['install_date']==$data_item['event_date']){
                    $out_data[$data_item['event_date']]['new_num'] += $data_item['num'];
                    $out_data[$data_item['event_date']]['new_person'] += $data_item['person'];
                }
            }else{
                $out_data[$data_item['event_date']][ $key_type.'_num'] += $data_item['num'];
                $out_data[$data_item['event_date']][ $key_type.'_person'] += $data_item['person'];
                if ($data_item['install_date']==$data_item['event_date']){
                    $out_data[$data_item['event_date']][ 'new_'.$key_type.'_num'] += $data_item['num'];
                    $out_data[$data_item['event_date']][ 'new_'.$key_type.'_person'] += $data_item['person'];
                }
            }
        }

        $this->assign("countryList", admincountry());
        return $this->fetch('advert',[
            'country' => $country,
            'appid' => $appid,
            'index' => $index,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $out_data,
        ]);
    }
	
	
	//ROI 模型预测 2021-02-23
	public function roi_model(){

	 return $this->fetch("roi_model");
	}
	
	public function postReten($retenData=[],$day="30")
	{
		$x =[];
		$y =[];
		if(!empty($retenData))
		{
			foreach($retenData as $v)
			{
				if($v["val"] && $v["val"]>0)
				{
					$x[] = (int)$v["day"];
					$y[] = (int)$v["val"];
				}
			}
		}
		$params =[
		   "x"=>$x,
		   "y"=>$y,
		   'day'=>intval($day)
		];
		$res = curl("http://console.gamebrain.io:5000/getReten",json_encode($params),"post");
		$result = json_decode($res,true);
		ksort($result);
		$x_out =[];
		$y_out =[];
		if(!empty($result))
		{
			foreach($result as $key =>$vv)
			{
				$x_out[] = $key+1;
				$y_out[] = round($vv,2);
			}
		}
		echo json_encode(["x_out"=>$x_out,"y_out"=>$y_out,"reten_sum"=>1+round(array_sum($result)/100,2)]);exit;
	}
}