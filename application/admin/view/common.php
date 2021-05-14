<?php
use think\Db;
use app\util\Strs;
use think\Session;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
//  admin 专用公共文件函数
// 应用公共文件

 function getheaderimg($avatar)
 {
	if( !$avatar )
	{
		return '/static/images/noheader.png';
	}
    return 	$avatar;
 }
 	
 function admincountry()
 {
	return array(
	   "all"=>"全部",
	   "US"=>"美国",
	   "TW"=>"台湾",
	   "HK"=>"香港",
	   "JP"=>"日本",
	   "KR"=>"韩国"
	);
 }
 //应用管理时间
 function getadvertime( $time )
 {
	return date("m/d H:i",strtotime($time)); 
 }
 
 //获取接口URL
 function getapiurl($id)
 {
	$url = getdomainname()."/advertise/advlist?appid={$id}";
    return $url;	
 }
 
 //密码加密
 function hew_md5($str)
 {
	$pre = "*^!@fdasDSADASd";
	return md5(md5($str).$pre);
 }
 //获取国家
 function getallcountry()
 {
	 $options = [
		'type'   => 'memcache',
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	$key = "country";
	$res = cache($key);
	 if( empty($res) )
	 {
		 $res =  Db::name("country")->select();
		 cache($key,$res);
	 }
	return $res;
 }
 
 function getplatform($tag)
 {
	
	$platforms =array(
	   "1"=>"Mobvista",
	   "2"=>"Unity",
	   "3"=>"Applovin",
	   "4"=>"Vungle",
	   "5"=>"AdMob",
	   "6"=>"Facebook"
	);
	return isset($platforms[$tag])?$platforms[$tag]:"no";
 }
 //获取应用名称
 function getapp($platform)
 {
	$options = [
		'type'   => 'memcache',
		'expire' => 0,
		'host'       => '127.0.0.1',
	];
	cache($options);
	$key = "app_name_{$platform}";
	$res = cache($key);
	if( empty($res) )
	{
		if( $platform==2 || $platform==3 || $platform==6 )
		   {
			 $res =  Db::query( " select app_id,app_name from hellowd_adcash_data where platform={$platform} group by app_id ");
		   }
		   if($platform!=1)
		   {
			   $platform=4;
		   }
			$res =  Db::name("adcash_appname")->where("status=1 and platform={$platform}")->select();
		 cache($key,$res);
	}
    return $res;
 }
 //获取用户名
 function getadminusername()
 {
	 return Session::get('username');
 }
  function getFullADType($t) {
        return array('int' => "插屏广告", 'rew' => "激励视频", 'nat' => "原生广告","native"=>"原生广告")[$t];
    }

    function getFullADName($t) {
        return array(
            'fb1' => "Facebook_1",
            'fb2' => "Facebook_2",
            'fb3' => "Facebook_3",
            'am1' => "Admob_1",
            'am2' => "Admob_2",
            'am3' => "Admob_3",
            'al' => "AppLovin",
            'un' => "Unity ads",
            'vu' => "Vungle",
            'mv' => "MobVista")[$t];
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
