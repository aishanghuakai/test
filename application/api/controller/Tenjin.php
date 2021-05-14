<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
set_time_limit(0);
class Tenjin
{
    	
	private $config=[
	    // 数据库类型
		'type'        => 'Pgsql',
		// 数据库连接DSN配置
		'dsn'         => '',
		// 服务器地址
		'hostname'    => 'hellowd.dv.tenjin.com',
		// 数据库名
		'database'    => '720eb0fdccbdb97e9d52377b0fa0908c',
		// 数据库用户名
		'username'    => 'd9b4d15b816ddbfd69d82cfc0cd9486f',
		// 数据库密码
		'password'    => '9dd5b8039ecc3e0dc74876a10d31bA31',
		// 数据库连接端口
		'hostport'    => '5439',
		// 数据库连接参数
		'params'      => [],
		// 数据库编码默认采用utf8
		'charset'     => '',
		// 数据库表前缀
		'prefix'      => '',
		// 数据库调试模式
		'debug'       => false,
	];
	
	//重要产品拉取
	
	private $apps=array(
	
	    "e347e3cf-cbf5-4f37-ab7a-d8f9fc15b47a"=>["app_id"=>"107","name"=>"Ore tycoon-idle Mining game ios"],
		"44bd4b79-c410-4d24-b00f-3bde71942213"=>["app_id"=>"77","name"=>"Tankr.io-Tank Realtime Battle ios"],
		"b7270ea9-c2f5-4918-ac16-c30587152541"=>["app_id"=>"114","name"=>"Truck vs Fire: Brain Challenge ios"],
		"cc27b2da-840c-4c70-9663-ac88a9bd07da"=>["app_id"=>"93","name"=>"Hexsnake io-Challenge friends"],
		"22f4a8da-daaf-48bf-b64f-20b12f955478"=>["app_id"=>"117","name"=>"Idle Fish Tycoon"],
		"71f61fd1-5819-4f55-bf49-a46157036619"=>["app_id"=>"130","name"=>"Shopping Mall Tycoon"],
		"f6a6224d-c518-4d0d-80a7-5209a9e1e254"=>["app_id"=>"122","name"=>"Tank Shooting-Survival Battle"],
		"92799d25-7016-41e4-aa5e-fac69ed51cbb"=>["app_id"=>"127","name"=>"Shopping Mall Tycoon"]
	);
	
	public function test()
	{
		$model = Db::connect($this->config);
		$sql="select 
distinct events.advertising_id
,events.source_uuid
,events.country
,ad_engagements.remote_click_id
from tenjin.events
join campaigns on events.source_campaign_id = campaigns.id
left join ad_engagements on ad_engagements.uuid=events.source_uuid
left join ad_networks  on ad_networks.id = campaigns.ad_network_id
where events.created_at >= '2019-04-01' and events.created_at < '2019-05-01' and events.country!='' and events.country!='CN'
and ad_networks.id = '19593' and events.event_type='event' and events.event='open'
and events.app_id= '44bd4b79-c410-4d24-b00f-3bde71942213'
";
		$data =$model->query($sql);
	    print_r($data);exit;
		return $this->inserdata($data,"19593",77);
	}
	
	public function test1()
	{
		$model = Db::connect($this->config);
		$sql="select 
*
from ad_engagements where uuid='0e9b5d43-30d6-4379-a21d-9359bfa84781'
";
		$data =$model->query($sql);
	    print_r($data);exit;
		return $this->inserdata($data,"19593",77);
	}
	
	//每天收益
	public function dayrevenue($date="")
	{
		if( $date=="" )
		{
			$date = date("Y-m-d",strtotime("-7 day"));			
		}
		foreach( $this->apps  as $k=>$vv)
		{
			$this->revenue($date,$k);
		}
		exit("ok");
	}
	
	//每天报告
	public function dayreport($date="")
	{
		if( $date=="" )
		{
			$date = date("Y-m-d",strtotime("-7 day"));			
		}
		foreach( $this->apps  as $k=>$vv)
		{
			$this->report($date,$k);
		}
		exit("ok");
	}
	
	//每天留存
	public function dayreten($date="")
	{
		if( $date=="" )
		{
			$date = date("Y-m-d",strtotime("-7 day"));			
		}
		foreach( $this->apps  as $k=>$vv)
		{
			$this->retenreport($date,$k);
		}
		exit("ok");
	}
	
	public function report($date="",$id="")
	{
		
		$model = Db::connect($this->config);
		$sql="select 
date
,platform
,campaign_id
,ad_network_id
,app_id
,country
,sum(daily_active_users) as daily_active_users
,sum(sessions) as sessions
,round(sum(reported_spend)/100,4)  as spend
,sum(reported_installs) as reported_installs
,sum(tracked_installs) as tracked_installs
,sum(tracked_clicks) as tracked_clicks
,sum(reported_clicks) as reported_clicks
,round(sum(publisher_ad_revenue)/100,4)  as ad_revenue
,round(sum(iap_revenue)/100,4) as iap_revenue
from reporting_metrics  where  app_id='{$id}' and date='{$date}'
group by date,app_id,country,ad_network_id,platform,campaign_id
";
		$data =$model->query($sql);
		return $this->reportinserdata($data);
	}
	
	public function retenreport($date,$id)
	{
		
		$model = Db::connect($this->config);
		$sql="SELECT install_date AS DATE,
       ad_networks.id as ad_network_id,
       country,reporting_cohort_metrics.campaign_id as campaign_id,reporting_cohort_metrics.app_id,platform,days_since_install
       ,sum(daily_active_users) as daily_active_users,sum(sessions) as sessions,round(sum(iap_revenue)/100,4) as iap_revenue,round(sum(publisher_ad_revenue)/100,4) as ad_revenue
FROM reporting_cohort_metrics
  LEFT JOIN bucket_campaign_info ON bucket_campaign_info.id = reporting_cohort_metrics.campaign_id
  LEFT JOIN ad_networks ON ad_networks.id = bucket_campaign_info.ad_network_id
WHERE install_date = '{$date}'  and reporting_cohort_metrics.app_id='{$id}'
GROUP BY 1,
         2,
         3,
		 4,
		 5,
		 6,
		 7
";
		$data =$model->query($sql);
		return $this->reteninserdata($data);
	}
	
	//收益
	private function revenue($date,$id)
	{
		$model = Db::connect($this->config);
		$sql="SELECT
date,
app_id,
publisher_apps.ad_network_id,
country,
sum(revenue) as revenue,
sum(conversions) as conversions,
sum(clicks) as clicks,
sum(impressions) as impressions
FROM daily_ad_revenue
  LEFT JOIN publisher_apps ON publisher_apps.id = daily_ad_revenue.publisher_app_id
WHERE date = '{$date}'  and publisher_apps.app_id='{$id}'
GROUP BY 1,
         2,
         3,
		 4
";
		$data =$model->query($sql);
		return $this->revenueinserdata($data);
	}
	
	public function adnetwork()
	{
		$model = Db::connect($this->config);
		$sql="select id as adnetwork_id,name from ad_networks";
		$data =$model->query($sql);	   
		Db::name("tenjin_adnetwork")->insertAll($data);
		exit("ok");
	}
	
	public function apps()
	{
		$model = Db::connect($this->config);
		$sql="select * from apps";
		$data =$model->query($sql);
	     print_r($data);exit;
	}
	

	//报告入库
	private function reportinserdata($data)
	{
		
		if( !empty($data) )
		{			
		    foreach( $data as &$vv )
			{
				if( isset($this->apps[$vv["app_id"] ] ) && !empty($this->apps[$vv["app_id"] ]) )
				{
					$t = $this->apps[$vv["app_id"]];
					$vv["app_id"] = $t["app_id"];
					$where=array( 
					   "ad_network_id"=>$vv["ad_network_id"],
					   "app_id"=>$vv["app_id"],
					   "country"=>$vv["country"],
					   "date"=>$vv["date"],
					   "campaign_id"=>$vv["campaign_id"],
					   "platform"=>$vv["platform"]
					);
					$r = Db::name("tenjin_report")->field("id")->where($where)->find();
					if( empty($r) )
					{
						Db::name("tenjin_report")->insert($vv);
					}else{
						Db::name("tenjin_report")->where( ["id"=>$r["id"] ] )->update($vv);
					}
				}				
			}							
		}
		return true;
	}
	//留存
	private function reteninserdata($data)
	{
		
		if( !empty($data) )
		{			
		    foreach( $data as &$vv )
			{
				if( isset($this->apps[$vv["app_id"] ] ) && !empty($this->apps[$vv["app_id"] ]) )
				{
					$t = $this->apps[$vv["app_id"]];
					$vv["app_id"] = $t["app_id"];
					$where=array( 
					   "ad_network_id"=>$vv["ad_network_id"],
					   "app_id"=>$vv["app_id"],
					   "country"=>$vv["country"],
					   "date"=>$vv["date"],
					   "days_since_install"=>$vv["days_since_install"],
					   "campaign_id"=>$vv["campaign_id"],
					   "platform"=>$vv["platform"]
					);
					$r = Db::name("tenjin_reten")->field("id")->where($where)->find();
					if( empty($r) )
					{
						Db::name("tenjin_reten")->insert($vv);
					}else{
						Db::name("tenjin_reten")->where( ["id"=>$r["id"] ] )->update($vv);
					}
				}				
			}							
		}
		return true;
	}
	
	//收益
	private function revenueinserdata($data)
	{
		if( !empty($data) )
		{			
		    foreach( $data as &$vv )
			{
				if( isset($this->apps[$vv["app_id"] ] ) && !empty($this->apps[$vv["app_id"] ]) )
				{
					$t = $this->apps[$vv["app_id"]];
					$vv["app_id"] = $t["app_id"];
					$where=array( 
					   "ad_network_id"=>$vv["ad_network_id"],
					   "app_id"=>$vv["app_id"],
					   "country"=>$vv["country"],
					   "date"=>$vv["date"]
					);
					$vv["revenue"] = round($vv["revenue"]/100,2);
					$r = Db::name("tenjin_adrevenue")->field("id")->where($where)->find();
					if( empty($r) )
					{
						Db::name("tenjin_adrevenue")->insert($vv);
					}else{
						Db::name("tenjin_adrevenue")->where( ["id"=>$r["id"] ] )->update($vv);
					}
				}				
			}							
		}
		return true;
	}
}
