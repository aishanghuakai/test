<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Survey extends Base
{
    	private $config=[
	    // 数据库类型
		'type'        => 'mysql',
		// 数据库连接DSN配置
		'dsn'         => '',
		// 服务器地址
		'hostname'    => '124.156.109.75',
		// 数据库名
		'database'    => 'analytics',
		// 数据库用户名
		'username'    => 'root',
		// 数据库密码
		'password'    => 'VXCxvff*&DS@#$#CVXse',
		// 数据库连接端口
		'hostport'    => '3306',
		// 数据库连接参数
		'params'      => [],
		// 数据库编码默认采用utf8
		'charset'     => 'utf8mb4',
		// 数据库表前缀
		'prefix'      => 'hellowd_',
		// 数据库调试模式
		'debug'       => false,
	];
	
	public function index($appid="")
    {      	  
	    
		if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
	    setcache("select_app",$appid);
	   $start = date("Y-m-d",strtotime("-9 day"));
	   $end = date("Y-m-d",strtotime("-2 day"));	   
	   $event_data =$this->getdayeventdata($appid,$start,$end,"all","all");
	   $this->assign("event_data",$event_data);
	   $avg_data = $this->getdayavgdata($appid,$start,$end,"all");
	   $this->assign("avg_data",$avg_data["out_data"]);
       $this->assign("total",$avg_data["total"]); 	   
	   $current_data =$this->getimpressions($appid,$end,$end,"all");
       $this->assign("current_data",$current_data); 
	   $this->assign("remarks",$this->getremark($appid) );
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	   $this->assign("country",admincountry());
	   $this->assign("bind_id",$this->getbind_id($appid) );
	   return $this->fetch('index');
    }
	
	public function json_data($app_id,$start,$end,$country="all",$type,$channel,$lable="NewUser")
	{
		if( $type=="event" )
		{
			$res = $this->getdayeventdata($app_id,$start,$end,$country,$channel,$lable);
		}else{
			$res = $this->getdayavgdata($app_id,$start,$end,$country);
		}
		$this->assign("res",$res);
	    $this->assign("type",$type);
		return $this->fetch('json_data');
	}
	
	//人均使用时长
	private function getuser_time($app_id,$start,$end,$country)
	{
		$where="app_id={$app_id} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
		$user_sql = "select sum(val) as val,sum(num) as num from hellowd_user_time where {$where}";
		$d= Db::query($user_sql);
		
		if( empty($d) )
		{
			return ["val"=>0,"num"=>0];
		}
		return isset($d[0]) && !empty($d[0])?$d[0]:["val"=>0,"num"=>0];
	}
	
	//获取每天事件
	private function getdayeventdata($app_id,$start,$end,$country="all",$channel="all",$lable="NewUser")
	{
		$out_data=[];
		$dates = getDateFromRange($start, $end);
		foreach( $dates as $k=>$v )
		{
			$out_data[$k]["date"] = $v;
			if( $lable=="NewUser" )
			{
				$out_data[$k]["num"] =0; //$this->getNewUser($app_id,$v,$v,$country,$channel);
				$videos =[]; //$this->getNewwatch_video($app_id,$v,$v,$country,$channel);
			}else{
				$out_data[$k]["num"] =0; //$this->getDau($app_id,$v,$v,$country,$channel);
			    $videos =[];//  $this->getwatch_video($app_id,$v,$v,$country,$channel);
			}				
			$out_data[$k]["int"] = isset($videos["int"])?$videos["int"]:0;
			$out_data[$k]["rew"] = isset($videos["rew"])?$videos["rew"]:0;
			$out_data[$k]["total"] = $out_data[$k]["int"]+$out_data[$k]["rew"];
			
			$out_data[$k]["int"] = $out_data[$k]["num"]>0?round($out_data[$k]["int"]/$out_data[$k]["num"],2):0;
			$out_data[$k]["rew"] = $out_data[$k]["num"]>0?round($out_data[$k]["rew"]/$out_data[$k]["num"],2):0;
			$out_data[$k]["total"] = $out_data[$k]["num"]>0?round($out_data[$k]["total"]/$out_data[$k]["num"],2):0;
			
			//$reten =$this->getdayreten($v,$app_id,$country,$channel);
	        $out_data[$k]["reten_1"] =0; //$reten["reten_1"];
		}
		return $out_data;
	}
	
	//获取每天人均
	private function getdayavgdata($app_id,$start,$end,$country)
	{
		$out_data=[];
		$dates = getDateFromRange($start, $end);
		$intavgshow=0;
		$rewavgshow=0;
		foreach( $dates as $k=>$v )
		{
			$out_data[$k]["date"] = $v;
			$impressions = $this->getimpressions($app_id,$v,$v,$country);
			$intavgshow+=$impressions["int"]["avgshow"];
			$rewavgshow+=$impressions["rew"]["avgshow"];
			$out_data[$k]["impressions"] =$impressions; 			
			$session = $this->getuser_time($app_id,$v,$v,$country);
			$dau = $this->getactive_users($app_id,$v,$v,$country);
			$out_data[$k]["usertime"] =$session["val"];
   			$out_data[$k]["num"] =$dau>0?round($session["num"]/$dau,2):0;
			$out_data[$k]["dau"] =$dau;
            $out_data[$k]["producter_showtips"] = isshowtips($app_id,$v,"producter");			
		}
		$day = count($dates);
		$total =array(
		    "intavgshow"=>round($intavgshow/$day,2),
			"rewavgshow"=>round($rewavgshow/$day,2),
            "num"=>round(array_sum(array_column($out_data,"num"))/$day,2),	
			"usertime"=>round(array_sum(array_column($out_data,"usertime"))/$day,2),
			"dau"=>ceil(array_sum(array_column($out_data,"dau"))/$day)
		);
		return ["out_data"=>$out_data,"total"=>$total];
	}
	
	//获取新增
	private function getNewUser($appid,$start,$end,$country,$channel)
	{
		$where="";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$r =Db::connect($this->config)->query(" select count(*) as num from hellowd_ana_appnewuser where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			return (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:0;
		}
		return 0;
	}
	
	//获取日活
	private function getDau($appid,$start,$end,$country,$channel)
	{
		$where="";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$r = Db::connect($this->config)->query("select count(*) as num from hellowd_ana_applanuch where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where}");
		if( isset($r[0]) && !empty($r[0]) )
		{
			return (isset($r[0]["num"]) && $r[0]["num"])?$r[0]["num"]:0;
		}
		return 0;
	}
	
	//获取观看视频 活跃
	private function getwatch_video($appid,$start,$end,$country,$channel)
	{
		$where=" and category='watch_video'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$r =Db::connect($this->config)->query(" select action,count(*) as num from hellowd_ana_eventdata where app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} group by action");
		if( !empty($r) )
		{
			$res =[];
			foreach($r as $v )
			{
				$res[$v["action"]] = $v["num"];
			}
			return $res;
		}
		return ["int"=>0,"rew"=>0];
	}
	
	//获取观看视频 新增
	private function getNewwatch_video($appid,$start,$end,$country,$channel)
	{
		$where=" and category='watch_video'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		
			$sql  ="SELECT * from ( SELECT dv_id,action,count(*) as num from hellowd_ana_eventdata where  app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} GROUP BY dv_id,action ) c WHERE EXISTS ( SELECT * from hellowd_ana_appnewuser as b 
WHERE c.dv_id=b.dv_id and b.app_id={$appid} and b.date>='{$start}' and b.date<='{$end}' )";
				$r =Db::connect($this->config)->query($sql);
				if( !empty($r) )
				{
					$ss =["int"=>0,"rew"=>0];
					foreach($r as $v )
					{
						$ss[$v["action"]]+=$v["num"];
					}
					return $ss;
				}
			
		return ["int"=>0,"rew"=>0];
	}
	
	//多少天留存获取
	private function getdayreten($date,$appid,$country,$channel)
	{
		$where="app_id={$appid} and date='{$date}'";
		if( $country!="all" )
		{
			$where.= " and country='{$country}'";
		}
		if( $channel!="all" )
		{
			$where.= " and channel='{$channel}'";
		}
		$res =Db::connect($this->config)->query("select dv_id from hellowd_ana_appnewuser where {$where}"); //Db::name("ana_appnewuser")->field("dv_id")->where($where)->select();
		
		$dv_ids="";
		if( !empty($res) )
		{
			$dv_ids = array_column($res,"dv_id");
		}
		$allowd_day_reten =[1,2];
		$out_put=[];
		foreach( $allowd_day_reten as $v )
		{
			$out_put["reten_".$v] = $this->getreten($dv_ids,$date,$v,$appid);
		}
		return $out_put;
	}
	
	//留存计算
	private function getreten($dv_ids,$date,$day,$appid)
	{
		//当天新增人数
		$totalnum = count($dv_ids);
		if( !empty($dv_ids) )
		{
			$current_timestamp = strtotime($date);
			$rtime =$current_timestamp+($day*86400);
			$current_date = date("Y-m-d",$rtime);
			
			$lasttimes = strtotime(date("Y-m-d",strtotime("-1 day")));
			if( $rtime>$lasttimes )
			{
				return 0;
			}
			$current_num =0;
			$ids = implode(",",$dv_ids);
			
			$r =Db::connect($this->config)->query(" select count(*) as num from hellowd_ana_applanuch where app_id={$appid} and date='{$current_date}' and dv_id in({$ids})");
			if( !empty($r[0]) )
			{
				$current_num =$r[0]["num"]; 
				
			}
			return $totalnum<=0?0:round($current_num*100/$totalnum,2);
		}
		return 0;
	}
	
	
	//获取备注
	private function getremark($appid)
	{
		return Db::name("app_remark")->where( ["app_id"=>$appid] )->order("date desc")->select();
	}
	
	private function getbind_id($app_id)
	{
		$r = Db::name("bind_app")->where( ["app_id"=>$app_id,"type"=>1] )->find();
		if( !empty($r) )
		{
			return $r["bind_id"];
		}
		return "";
	}
	
	public function bind_data($app_id="",$bind_id="")
	{
		$r = Db::name("bind_app")->where( ["app_id"=>$app_id,"type"=>1] )->find();
		if( !empty($r) )
		{
			$result = Db::name("bind_app")->where( ["app_id"=>$app_id,"type"=>1] )->update( ["bind_id"=>$bind_id,"bind_user"=>$this->_adminname] );
		}else{
			$result = Db::name("bind_app")->insert( ["bind_id"=>$bind_id,"bind_user"=>$this->_adminname,"app_id"=>$app_id,"type"=>1 ] );
		}
		if( $result!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	private function getimpressions($appid,$start="",$end="",$country="all")
	{
		$where="sys_app_id={$appid} and  date>='{$start}' and date<='{$end}'";
		
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}
	   $active_users = $this->getactive_users($appid,$start,$end,$country);
		
	   $r=Array
		(
		"int" => Array
			(
				"impressions" =>0,				
				"avgshow" => 0
			),
		"rew" => Array
			(
				"impressions" =>0,
				"avgshow" => 0
			)
		);
		 $sum_sql = "select adtype,sum(impression) as impressions from hellowd_adcash_data where {$where} group by adtype";
		 $d= Db::query($sum_sql);
		 if( !empty($d) )
		 {
			 foreach( $d as &$v )
			 {
								  
				$upltv_adtype_data=getupltvfacebook($appid,$start,$end,$country,$v["adtype"]);				  
				$v["impressions"] =($v["impressions"]-$upltv_adtype_data["impression"])<0?0:$v["impressions"]-$upltv_adtype_data["impression"];
				
				 $v["avgshow"] = $active_users<=0?0:number_format($v["impressions"]/$active_users,2);
                 $r[ $v["adtype"] ] = ["impressions"=>$v["impressions"],"avgshow"=>$v["avgshow"] ];				 
			 }
		 }
		 $r["active_users"] =$active_users; 
		 return $r;
	}
    
    //日活
	private function getactive_users($appid,$start="",$end="",$country="all")
	{
		$where="app_id={$appid} and  date>='{$start}' and date<='{$end}' and country='{$country}'";
		$active_sql = "select sum(val) as val from hellowd_active_users where {$where}";
		$d= Db::query($active_sql);
		
		if( empty($d) )
		{
			return 0;
		}
		return $d[0]["val"]?$d[0]["val"]:0;
	}	
}
