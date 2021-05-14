<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;

  //Mobvista 请求类
class Mobrequest
{
    //请求api公共地址 
	const BASIC_REQUEST_URL ="http://oauth2.mobvista.com/m/";
	
	const Skey = "ab1296d1de6f5267ef58f5dd3e041c67";
	
	const Secret="c1a331f7e7216e6cf9431be63760b81d";
	
	const Version="1.0";
	
   //生成签名
   public function generate_signature($time,$limit,$page,$start,$end)
   {
	  
	   $skey = self::Skey;
	   $Version = self::Version;
	   $Secret = self::Secret;
	   $Parameters = array(
	        "limit"=>$limit,
			"page"=>$page,
			"skey"=>$skey,
			"time"=>$time,
			"v"=>$Version,
			"start"=>$start,
		    "group_by"=>"date,app_id,country,unit_id",
		    "end"=>$end	
	   );
	   ksort($Parameters);  //进行key排序处理
	   $str="";
	   foreach( $Parameters as $kk=>$vv )
	   {
		   $str.="&{$kk}=".urlencode($vv);//进行url编码处理
	   }
	   $str =ltrim($str,"&");
	  
       $signature = md5( md5( $str ).$Secret );
       return $signature;  
   }
   
   //Offline Api Report
   public function apireport($page,$start,$end)
   {
	   $Url = self::BASIC_REQUEST_URL ."/report/offline_api_report";
	   //$Url = self::BASIC_REQUEST_URL ."data/get_country";
	   $Url.="?skey=".self::Skey;
	   $time  = time();
	   $limit = 500;
	   $page =$page;
	   $start =str_replace('-','',$start);
       $end = str_replace('-','',$end);
	   $data = array(
		  "sign"=>$this->generate_signature($time,$limit,$page,$start,$end),
		  "v"=>"1.0",
		  "time"=>$time,
		  "page"=>$page,
		  "limit"=>$limit,
		  "start"=>$start,
		  "group_by"=>"date,app_id,country,unit_id",
		  "end"=>$end	  
	   );
	   foreach ( $data as $key=>$v )
	   {
		   $Url.="&{$key}=".$v;
	   }
	  //print_r( $Url );exit;
	   return curl($Url);
   }
   
   public function get_country()
   {
	   $skey = self::Skey;
	   $Version = self::Version;
	   $Secret = self::Secret;
	   $time = time();
	   $Parameters = array(
	       
			"skey"=>$skey,
			"time"=>$time,
			"v"=>$Version
	   );
	   ksort($Parameters);  //进行key排序处理
	   $str="";
	   foreach( $Parameters as $kk=>$vv )
	   {
		   $str.="&{$kk}=".urlencode($vv);//进行url编码处理
	   }
	   $str =ltrim($str,"&");
	  
       $signature = md5( md5( $str ).$Secret );
       $Parameters["sign"] =$signature;
	   unset( $Parameters["skey"] );
	   $Url = self::BASIC_REQUEST_URL ."data/get_country";
	   $Url.="?skey=".$skey;
	   
	   
	   foreach ( $Parameters as $key=>$v )
	   {
		   $Url.="&{$key}=".$v;
	   }
	   
	   return curl($Url);
   }
   
}
