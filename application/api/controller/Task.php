<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
set_time_limit(0);

class Task 
{
   	//定义要更新的请求连接
	private $cron_url =array(
	    "/adspend/getSnapchat",
		"/adspend/pullFB",
		"/adspend/get_apple_ads",
		"/adspend/adwords",
		"/adspend/get_adwords_ads_auto",
		"/adcash/getSigmob",
		"/adcash/snyc_get_facebook",
		"/adcash/hexlandfacebook"		
	);
	
	private $product_url =array(
	   "/admin_product/updateproduct"
	);
	
	private function get_host_url(){
		return getdomainname();
	}
	
	//每周更新一次
	public function get_last_week_data(){
		
		$start = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y")));
		$end = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7-8,date("Y")));
		$dateList = getDateFromRange($start,$end);
		$host = $this->get_host_url();
		foreach( $dateList as $v )
		{
			$params = [
			  "start"=>$v,
			  "end"=>$v
			];
			foreach( $this->cron_url as $u )
			{
				$request_url  =$host.$u;
				syncRequest($request_url,$params);
			}
			sleep(3);
		}
		exit("ok");
	}
	
	//每半个月更新一次
	public function get_last_month_data(){
		
		$start = date("Y-m-d",strtotime("-15 day"));
		$end = date("Y-m-d",strtotime("-3 day"));
		$dateList = getDateFromRange($start,$end);
		$host = $this->get_host_url();
		foreach( $dateList as $v )
		{
			$params = [
			  "start"=>$v,
			  "end"=>$v
			];
			foreach( $this->cron_url as $u )
			{
				$request_url  =$host.$u;
				syncRequest($request_url,$params);
			}
			sleep(3);
		}
		exit("ok");
	}
	
	//产品汇总更新
	public function get_product(){
		$start = date("Y-m-d",strtotime("-15 day"));
		$end = date("Y-m-d",strtotime("-2 day"));
		$dateList = getDateFromRange($start,$end);
		$host = $this->get_host_url();
		foreach( $dateList as $v )
		{
			$params = [
			  "start"=>$v,
			];
			foreach( $this->product_url as $u )
			{
				$request_url  =$host.$u;
				syncRequest($request_url,$params);
			}
			sleep(3);
		}
		exit("ok");
	}
	
	//系统监控检测
	public function monitor(){
		
        $cpu = $this->getCpu();
		$emailList =["lixiongfei@hellowd.net","durongjian@hellowd.net","jinyijie@hellowd.net"];
		$time =date("Ymd-Hi");
		$fp = popen('df -lh | grep -E "^(/)"',"r");
		$rs = fread($fp,1024);
		pclose($fp);
		$hd = explode(" ",$rs);
		$hd_usage_sys = trim($hd[15],'%'); //系统盘挂载点
		$hd_usage_data = trim($hd[29],'%'); //系统盘挂载点
		$content ="";
		if($hd_usage_sys>80)
		{
			$content.="<p>当前系统盘挂载：<strong style='color:red;'>{$hd_usage_sys}%</strong></p>";
		}
		if($cpu>80)
		{
			$content.="<p>当前系统CPU：<strong style='color:red;'>{$cpu}%</strong></p>";
		}
		if($hd_usage_data>80)
		{
			$content.="<p>当前数据盘挂载：<strong style='color:red;'>{$hd_usage_data}%</strong></p>";
		}
		exec('ps -ef | grep -v grep | grep apache | wc -l',$num);
		if($num[0]==0 || $num[0]>250)
		{
			
			$content.="<p>当前apache进程数：<strong style='color:red;'>{$num[0]}</strong></p>";
		}
		if($content!="")
		{
			foreach(  $emailList as $vv )
			{
				send_mail( $vv,$vv,"【服务器异常{$time}】",$content,"GameBrain");
			}
		}
        $this->check_ios_data();
		exit("ok");
	}
	
	private function getCpu(){
		 $replace = ' ';
		exec('top -b -n 1 -d 3',$out);
		$task = explode($replace, preg_replace('/\s+/',$replace,$out[1]));
		$task_all = $task[1];
		$task_run = $task[3];
		$cpu = explode($replace, preg_replace('/\s+/',$replace,$out[2]));
		$cpu_free = $cpu[7];
		$cpu_use = 100 - $cpu_free;
		return $cpu_use;
	}
	
	//检测 苹果数据是否有
	public function check_ios_data()
	{
		$date = date("Y-m-d",strtotime("-2 day"));
		$h=date("H");
		if($h>15)
		{
			$appList =["166","135"];
			$res = Db::name('purchase_details')
							->where(['date'=>$date,'app_id'=>['in',$appList]])
							->find();
			$emailList =["lixiongfei@hellowd.net","durongjian@hellowd.net","jinyijie@hellowd.net"];
			if(empty($res))
			{
				foreach(  $emailList as $vv )
				{
					send_mail( $vv,$vv,"【内购数据未拉取到{$date}】","请及时处理","GameBrain");
				}
			}
		}		
		exit("ok");
	}
	
}
 