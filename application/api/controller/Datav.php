<?php
namespace app\api\controller;
use think\Db;
use \think\Request;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods:GET,POST,OPTIONS,DELETE,PUT");
set_time_limit(0);
class Datav
{
    
	private $appid =array(
	
	         "55409"=>[ "appid"=>"68","groupid"=>"68","icon"=>"http://console.gamebrain.io/uploads/userproduct/spiralrush.png","groupname"=>"Spiral Rush Go","name"=>"Spiral Rush Go" ],
			 "56540"=>[ "appid"=>"77","groupid"=>"77","icon"=>"http://console.gamebrain.io/uploads/userproduct/tankr.png","groupname"=>"Tankr","name"=>"Tankr.io Realtime Battle" ],
			 "53679"=>[ "appid"=>"81","groupid"=>"81","icon"=>"http://console.gamebrain.io/uploads/userproduct/dailypinball.png","groupname"=>"Daily Pinball","name"=>"Daily Pinball" ],
			 "57470"=>[ "appid"=>"66","groupid"=>"66","icon"=>"http://console.gamebrain.io/uploads/userproduct/hexland.io.png","groupname"=>"Hexland","name"=>"Hexland-ios" ],
			 "57970"=>["appid"=>"73","groupid"=>"73","icon"=>"http://console.gamebrain.io/uploads/userproduct/street.png","groupname"=>"Idle Capital Street","name"=>"Idle Capital Street-ios"],
			 "57754"=>[ "appid"=>"52","groupid"=>"77","icon"=>"http://console.gamebrain.io/uploads/userproduct/tankr.png","groupname"=>"Tankr","name"=>"Tankr.io-Android" ],
			 "59269"=>[ "appid"=>"91","groupid"=>"66","icon"=>"http://console.gamebrain.io/uploads/userproduct/hexland.io.png","groupname"=>"Hexland","name"=>"HexSnake.io-Android" ],
			 "58928"=>[ "appid"=>"90","groupid"=>"81","icon"=>"http://console.gamebrain.io/uploads/userproduct/dailypinball.png","groupname"=>"Daily Pinball","name"=>"Daily Pinball - Android" ],
			 "59127"=>[ "appid"=>"73","groupid"=>"73","icon"=>"http://console.gamebrain.io/uploads/userproduct/street.png","groupname"=>"Idle Capital Street","name"=>"Idle Capital Street-Android" ]
	);
	
	//实时获取今天的活跃
	private function realactive_user()
	{
		$total_active=0;
		$product_list =[];
		$start = date("Y-m-d",time() );
		$statisticName ="active_users";
		$end = date("Y-m-d",strtotime('+1 day',strtotime($start)));
		
		$res = $this->login();
		if( !empty($res) )
		{
			foreach( $res as $k=>$v )
			{
				$result = $this->curl_active_users($start,$v["token"],$v["gameId"]);
			    $data =end($result);
				$active_users =0;
                if( isset($data["total"]["user_unique"]) )
				{
					$active_users =ceil($data["total"]["user_unique"]);
				}
                $total_active+=$active_users;
				if( isset($product_list[$v["groupid"]]) )
				{
					 $product_list[$v["groupid"]]["val"]+=$active_users;			 
				}else{
					$product_list[$v["groupid"]] = ["groupname"=>$v["groupname"],"icon"=>$v["icon"],"val"=>$active_users];
				}
               			
			}
		}
		$output = ["total_active"=>$total_active,"list"=>$product_list];
		return $output;
	}
	
	private function country_active_user()
	{
		
		$country=[];
		$total_active_users=0;
		$start = date("Y-m-d",time() );
		$statisticName ="active_users";
		$end = date("Y-m-d",strtotime('+1 day',strtotime($start)));
		
		$res = $this->login();
		if( !empty($res) )
		{
			foreach( $res as $k=>$v )
			{
				$result = $this->curl_country_active_users($start,$v["token"],$v["gameId"]);
			    $data =end($result);
				$r  =isset($data["result"])?$data["result"]:[];
				$active_users=0;
				if( !empty($r) )
				{
					foreach( $r as $c )
					{
						$active_users+=$c["user_unique"];
						if( isset( $country[$c["country_code"]]  ) )
						{
							$country[$c["country_code"]]["val"]+=$c["user_unique"];
						}elseif( $c["country_code"] ){
							$country[$c["country_code"]]=["country_code"=>$c["country_code"],"val"=>$c["user_unique"] ];
						}
					}
				}
				$total_active_users+=$active_users;
				         			
			}
		}
		$output_data = ["total_active_users"=>$total_active_users,"list"=>$country];
		return $output_data;
	}
	
	private function new_users()
	{
		
		$start = date("Y-m-d",time());
		$total=0;
		$res =$this->login();
		
		if( !empty($res) )
		{
			foreach( $res as $v )
			{
				$result = $this->curl_new_users($start,$v["token"],$v["gameId"]);
			  				
				if( !empty($result))
				{
				   foreach( $result as $r )
				   {
					   $t_toal = $r["total"]["new_users"];
					   $total+=$t_toal;
				   }				   										
				}
			}
		}
		return $total;
	}
	
	//新增
	private function curl_new_users($start,$access_token,$gameId)
	{
		
		$end = date("Y-m-d",strtotime('+1 day',strtotime($start)));
		$url ="https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/new_users/timeseries";
		$httpHeader[] = 'Authorization:'. $access_token;
		$httpHeader[] = 'application/json, text/plain, */*';
		$httpHeader[] ='Content-Type: application/json;charset=utf-8';
        $data = json_encode(
		  [
		   "granularity"=>"day", 
		   "interval"=>"{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
	      ]
		);		
		$r = $this->curl_request($url,$httpHeader,$data,false);
		return json_decode($r,true);	
	}
	
	public function login()
	{
		$game_list=[];
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		$ga_token = $mem->get('dataga_token');
		if( $ga_token )
		{
			
			$game_list = json_decode($ga_token,true);
			return $game_list;
		}
		$loginurl= "https://summary-api.gameanalytics.com/summary_api/login?email=lixiongfei@hellowd.net&password=a547534827";
		$res = json_decode( $this->googlecurl($loginurl,null,"post"),true);
		if( count($res["errors"] )>0 )
		{
			return false;
		}
		$result = $res["results"];
		foreach( $result as $k=>$v )
		{
			
            $games = $v['games'];
			foreach( $games as  $vv )
			{
				if( isset($this->appid[$vv["id"]]) )
				{
					$game_list[] = [ "gameId"=>$vv["id"],"appid"=>$this->appid[$vv["id"]]["appid"],"icon"=>$this->appid[$vv["id"]]["icon"],"groupid"=>$this->appid[$vv["id"]]["groupid"],"appid"=>$this->appid[$vv["id"]]["appid"],"groupname"=>$this->appid[$vv["id"]]["groupname"],"token"=>$vv["token"] ];
				}
			}               
		}
		$o_list = $this->other_login();
		if( !empty($o_list) )
		{
			$game_list= array_merge($game_list,$o_list);
		}
		$mem->set('dataga_token',json_encode($game_list),0,72000);
		return $game_list;
	}
	
	private function other_login()
	{
		$game_list=[];
		//$loginurl= "https://summary-api.gameanalytics.com/summary_api/login?email=zhangfangyu@hellowd.net&password=karasuSmoon1109";
		$loginurl= "https://summary-api.gameanalytics.com/summary_api/login?email=wangziheng@hellowd.net&password=wzh0316wzx0609";
		$res = json_decode( $this->googlecurl($loginurl,null,"post"),true);
		if( count($res["errors"] )>0 )
		{
			return false;
		}
		$result = $res["results"];
		foreach( $result as $k=>$v )
		{
			
            $games = $v['games'];
			foreach( $games as  $vv )
			{
				if( isset($this->appid[$vv["id"]]) )
				{
					$game_list[] = [ "gameId"=>$vv["id"],"appid"=>$this->appid[$vv["id"]]["appid"],"icon"=>$this->appid[$vv["id"]]["icon"],"groupid"=>$this->appid[$vv["id"]]["groupid"],"appid"=>$this->appid[$vv["id"]]["appid"],"groupname"=>$this->appid[$vv["id"]]["groupname"],"token"=>$vv["token"] ];
				}
			}               
		}
		return $game_list;		
	}
	private function curl_active_users($start,$access_token,$gameId)
	{
		
		$end = date("Y-m-d",strtotime('+1 day',strtotime($start)));
		$url ="https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/user_unique/timeseries";
		$httpHeader[] = 'Authorization:'. $access_token;
		$httpHeader[] = 'application/json, text/plain, */*';
		$httpHeader[] ='Content-Type: application/json;charset=utf-8';
        $data = json_encode(
		  [
		   "granularity"=>"hour",		   
		   "interval"=>"{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
	      ]
		);		
		$r = $this->curl_request($url,$httpHeader,$data,false);
		return json_decode($r,true);	
	}
	
	private function curl_country_active_users($start,$access_token,$gameId)
	{
		
		$end = date("Y-m-d",strtotime('+1 day',strtotime($start)));
		$url ="https://facelessvoid.gameanalytics.com/v1/games/{$gameId}/datasources/dashboards/metrics/user_unique/topN";
		$httpHeader[] = 'Authorization:'. $access_token;
		$httpHeader[] = 'application/json, text/plain, */*';
		$httpHeader[] ='Content-Type: application/json;charset=utf-8';
        $data = json_encode(
		  [
		   "dimension"=>"country_code",
		   "threshold"=>30,
		   "granularity"=>"hour",
		   "interval"=>"{$start}T00:00:00.000Z/{$end}T00:00:00.000Z"
	      ]
		);		
		$r = $this->curl_request($url,$httpHeader,$data,false);
		return json_decode($r,true);	
	}
	
	private function curl_request($url,$httpHeader,$data=[],$ispost=true)
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
		if( $ispost )
		{
			curl_setopt($ch, CURLOPT_POST,1);
		}
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数		
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret =  curl_errno($ch);
        }
        curl_close($ch);
        return $ret;
	}
	
	 //请求
	private function googlecurl($url,$data=null,$method = null)
	{
	   $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST,1);
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		if($data){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
    }
	//日活用户
	private function getdayusers()
	{
	   $yx_num=0;
	   $date = date("Y-m-d",strtotime("-1 day"));
	   $r = Db::name("active_users")->field("sum(val) as val")->where( ["date"=>$date,"country"=>"all"] )->find();
	   if( empty($r) )
	   {
		   $date = date("Y-m-d",strtotime("-2 day"));
	       $rz = Db::name("active_users")->field("sum(val) as val")->where( ["date"=>$date,"country"=>"all"] )->find();
		   $yx_num = $rz["val"];
	   }else{
		  $yx_num = $r["val"];
	   }
	   $wm_data = file_get_contents("https://www.wangmeng.online/api/Analytics/getDailyActivity");
	   $w_udata = json_decode($wm_data,true);
	   return $yx_num+$w_udata["cumulant"];
	}
	public function dau($f="")
	{
		if( $f=="d" )
		{
			try {
			    $total =$this->getdayusers();
			    $this->setcache("active_users",$total);
			} catch (Exception $e) {
				$total = $this->getcache("active_users");
            }
		}
		 $total = $this->getcache("active_users");
		$onlinedata = [ ["name"=>"日活用户数","value"=>$total,"suffix"=>""] ];
		echo json_encode($onlinedata);exit;
	}
	//获取实时总在线用户数
	private function gettotalonlineusers()
	{
		$yx_data =$this->realactive_user();
		
		$wm_data = file_get_contents("https://www.wangmeng.online/getActiveUsers");
		$w_udata = json_decode($wm_data,true);
		return $yx_data["total_active"]+$w_udata["data"];
	}
	//获取实时产品用户数
	public function gettotalproductusers()
	{
		$yx_data =$this->realactive_user();
        $wm_productdata = file_get_contents("https://www.wangmeng.online/getProductData");
		$wm_productdata = json_decode($wm_productdata,true);
		$totalusers = $yx_data["total_active"]+$wm_productdata["total"];
        $wm_list =$wm_productdata["data"];
		$yx_list = $yx_data["list"];
		$output_data =[];
		if( !empty($wm_list) )
		{
			foreach( $wm_list as $v )
			{
				$rate =$totalusers<=0?0:ceil($v["active_users"]*100/$totalusers);
				$output_data[] = ["value"=>$v["active_users"],"content"=>"<img height='20' width='20' src='{$v['logo']}' />&nbsp;{$v['profile_name']} &nbsp;&nbsp;{$rate}%" ];
			}
		}
		if( !empty($yx_list) )
		{
			foreach( $yx_list as $vv )
			{
				$rate =$totalusers<=0?0:ceil($vv["val"]*100/$totalusers);
				$output_data[] = ["value"=>$vv["val"],"content"=>"<img height='20' width='20' src='{$vv['icon']}' />&nbsp;{$vv['groupname']} &nbsp;&nbsp;{$rate}%" ];
			}
		}
		$output_data =array_slice($this->admin_array_sort($output_data,"value","desc"),0,10);
		return $output_data;
	}
	
	//获取实时国家用户数
	public function gettotalcountryusers()
	{
		$yx_data =$this->country_active_user();
		$wm_productdata = file_get_contents("https://www.wangmeng.online/getCountryData");
		$wm_productdata = json_decode($wm_productdata,true);
		$totalusers = $yx_data["total_active_users"]+$wm_productdata["total"];
        $wm_list =$wm_productdata["data"];
		$yx_list = $yx_data["list"];
		foreach( $wm_list as $vv )
		{
			if( isset( $yx_list[$vv["countryIsoCode"]] ) )
			{
				$yx_list[$vv["countryIsoCode"]]["val"]+=$vv["activeUsers"];
			}elseif( $vv["countryIsoCode"] ){
				$yx_list[$vv["countryIsoCode"]] = ["country_code"=>$vv["countryIsoCode"],"val"=>$vv["activeUsers"] ];
			}
		}
		$output_data =array_slice($this->admin_array_sort($yx_list,"val","desc"),0,10);
		$output =[];
		foreach( $output_data as $o )
		{
			$rate =$totalusers<=0?0:ceil($o["val"]*100/$totalusers);
			$icon = "http://console.gamebrain.io/uploads/country/{$o['country_code']}.png";
		   $output[] = ["value"=>$o["val"],"content"=>"<img height='20' width='20' src='{$icon}' />&nbsp;{$o['country_code']} &nbsp;&nbsp;{$rate}%" ];
		}
		return $output;
	}
	function admin_array_sort($arr,$keys,$orderby='asc'){
	  $keysvalue = $new_array = array();
	  foreach ($arr as $k=>$v){
		$keysvalue[$k] = $v[$keys];
	  }
	  if($orderby== 'asc'){
		asort($keysvalue);
	  }else{
		arsort($keysvalue);
	  }
	  reset($keysvalue);
	  foreach ($keysvalue as $k=>$v){
		$new_array[] = $arr[$k];
	  }
	  return $new_array;
   }
   
   //获取累计用户
   private function gethistoryusers()
   {
	  $r = Db::name("new_users")->field("sum(val) as val")->where("country='all'")->find();
	  $yx_today = $this->new_users();
	  $befornum =3550862;
	  $yx_data = $r["val"]+$yx_today+$befornum;
	  $wm_totaldata = file_get_contents("https://www.wangmeng.online/getUserCumulant");
	  $wm_totaldata = json_decode($wm_totaldata,true);
	  $wm_data =$wm_totaldata["cumulant"];
	  $total = $yx_data+$wm_data;
	  return $total;
   }
	//当前实时用户
	public function onlineuser($f="")
	{				
		if( $f=="d" )
		{
			try {
			    $total =$this->gettotalonlineusers();
			    $this->setcache("online_users",$total);
			} catch (Exception $e) {
				$total = $this->getcache("online_users");
            }
		}
        $total = $this->getcache("online_users");
		
		$onlinedata = [ ["name"=>"实时用户数","value"=>$total,"suffix"=>""] ];
		echo json_encode($onlinedata);exit;
	}

    //当前累计用户
	public function historyuser($f="")
	{
		
		if( $f=="d" )
		{
			try {
			  $total =$this->gethistoryusers();
			  $this->setcache("history_users",$total);
			} catch (Exception $e) {
			  $total = $this->getcache("history_users");
            }
		}
        $total = $this->getcache("history_users");
		$onlinedata = [ ["name"=>"累计用户数","value"=>$total,"suffix"=>""] ];
		echo json_encode($onlinedata);exit;
	}
    //当前国家分布
   public function country($f="")
   {		  
	  if( $f=="d" )
		{
			try{
				$res =$this->gettotalcountryusers();
				$this->setcache("country_users",$res);
			}catch (Exception $e) {
			  $res = $this->getcache("country_users");
		   }			
		}			
		$res = $this->getcache("country_users");
		echo json_encode($res);exit;
   }
   
   //当前产品分布
   public function product($f="")
   {	   
		if( $f=="d" )
		{
			try{
			  $res =$this->gettotalproductusers();
			  $this->setcache("product_users",$res);
			}catch (Exception $e) {
			  $res = $this->getcache("product_users");
		   }  
		}
		$res = $this->getcache("product_users");
	   echo json_encode($res);exit;
   }
     function getcache($key)
	 {
		 $options = [
			'type'   => 'File',
			'expire' => 0,
			'host'       => '127.0.0.1',
			];
			cache($options);
			$res =cache($key);
			//cache($key, NULL);
			if( !empty($res) )
			{
				return $res;
			}
			return "";
	 }
   
   //设置缓存
 function setcache($key,$data)
 {
	  $options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	cache( $key,$data);
	return true;
 }   
   
}
