<?php
use think\Db;
use app\util\Strs;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
//  api 专用公共文件函数
// 应用公共文件

function show_out($code,$message="",$data)
{
	$show_outdata = array(
	  "code"=>$code,
	  "message"=>$message,
	  "data"=>$data
	);
	return json($show_outdata);
}
function setfieldtype( $data,$fields)
{
	foreach( $data as $key=>&$v)
	{
		if( isset( $fields[$key] ) )
		{
			$type = $fields[$key];
             switch( $type )
			 {
				 case "int":
				    $v = (int)$v;
				    break;
				 case "string":
				    $v = (string)$v;
				    break;
                 case "object":
				    $v = (object)$v;
				    break;
                 case "array":
				    $v = (array)$v;
				    break;					
			 }
		}
	}
    return $data;
}

	 function genernumber()
	{
	   $orderid = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	   return $orderid;
	}
    //新浪IP API
	function getsinaip( $ip )
	{
		 header("Content-type: text/html; charset=utf-8");
		 $t =@file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip);
		 $r = json_decode($t,true );
		 if( isset($r["country"]) && isset( $r["ret"] ) && $r["ret"]==1 )
		 {
			// return trim($r["country"]);
			return $r;
		 }
		 return "-1";
	}
	
	//淘宝ip
	function gettaobaoip($ip)
	{ 
		 header("Content-type: text/html; charset=utf-8");
		 $url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;		 
		 $json = json_decode(file_get_contents($url),true);
		if( isset($json["data"]) && isset( $json["code"] ) && $json["code"]==0 )
		 {
			 return trim($json["data"]["country_id"]);
			// return $json["data"];
		 }
		 return "tao";
	}
	//http://ip-api.com/
    function getapiip($ip)
	{
		header("Content-type: text/html; charset=utf-8");
		$url ="http://ip-api.com/json/{$ip}";
		$json = json_decode(file_get_contents($url),true);
		if( isset( $json["countryCode"] ) && $json["countryCode"]!='' )
		{
			return  $json["countryCode"];
		}
		return "-1";
	}
    //ip api
   function getgeoip($ip){
	   header("Content-type: text/html; charset=utf-8");
		$url ="https://api.ip.sb/geoip/{$ip}";
		$json = json_decode(file_get_contents($url),true);
		if( isset( $json["country_code"] ) && $json["country_code"]!='' )
		{
			return  $json["country_code"];
		}
		return "-1";
   }	

    // 获取IP地址
    function get_client_ip($type = 0) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
     
	 
	function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){  
        if(is_array($arrays)){  
            foreach ($arrays as $array){  
                if(is_array($array)){  
                    $key_arrays[] = $array[$sort_key];  
                }else{  
                    return false;  
                }  
            }  
        }else{  
            return false;  
        } 
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);  
        return $arrays;  
    }  
 
  function getDateFromRange($startdate, $enddate){

    $stimestamp = strtotime($startdate);
    $etimestamp = strtotime($enddate);

    // 计算日期段内有多少天
    $days = ($etimestamp-$stimestamp)/86400+1;

    // 保存每天日期
    $date = array();

    for($i=0; $i<$days; $i++){
        $date[] = date('Y-m-d', $stimestamp+(86400*$i));
    }

    return $date;
}

 /**
     * 使用fsocketopen()方式发送异步请求,put方式
     */
	function syncRequest($url, $param=array(),$bodyData="",$timeout =30)
    {
        $urlParmas = parse_url($url);
        $host = $urlParmas['host'];
        $path = $urlParmas['path'];
        $scheme = $urlParmas['scheme'];
        $port = isset($urlParmas['port'])? $urlParmas['port'] :80;
        $errno = 0;
        $errstr = '';
        if($scheme == 'https') {
            $host = 'ssl://'.$host;
        }
        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
        stream_set_blocking($fp,true);//开启了手册上说的非阻塞模式
        $query = isset($param)? http_build_query($param) : '';
        //如果传递参数在body中,则使用
        if(!empty($postData)) $query = $postData;
        $out = "PUT ".$path." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($query)."\r\n";
        //传递参数为url=?p1=1&p2=2的方式,使用application/x-www-form-urlencoded方式
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        //传递参数为json字符串的方式,并且在请求体的body中,使用application/json
        //$out .= "content-type:application/json\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $query;
        fputs($fp, $out);
        //usleep(1000); // 这一句也是关键，如果没有这延时，可能在nginx服务器上就无法执行成功
        $result = "";
        /*
        //获取返回结果, 如果不循环接收返回值,请求发出后直接关闭连接, 则为异步请求
        while(!feof($fp)) {
            $result .= fgets($fp, 1024);
        }*/
        //print_r($result);
        fclose($fp);
    }
 
  