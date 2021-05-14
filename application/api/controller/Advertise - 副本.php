<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
use think\Validate;
use app\util\ShowCode;
class Advertise extends Validate
{
    
	//获取广告列表
	public function advlist($appid="",$country="")
	{
		if( !$appid || !preg_match('/^\d+$/',$appid) )
		{
			return show_out(1001,"INVALID",new \StdClass());
		}
		$r = Db::name('app')->where("id={$appid}")->find();
		if( empty($r) )
		{
			return show_out(1002,"DB_DATA_EMPTY",new \StdClass());
		}
        if( $country!="" && $country=="-1" )
		{
			$result = $this->getcountry();
			$country = $result["country_code"];
		}
         $issort = false;		
		  $out_data=array(
		       "int"=>array(),
			   "rew"=>array(),
			   "nat"=>array(),
			   "ban"=>array(),
			   "conf"=>array()
		   );
		  $data = Db::name('adconfig')->where("appid={$appid} and val!='' and app_class=1 and status=1")->field('id,adtype,name,val,adsort')->order('adtype,adsort') -> select();
		   if( !empty($data) )
		   {
			   foreach( $data as $kk=>&$vv )
			   {
				  if($country!="")
				  {
					   $res = Db::name('adprop')->where("cfid={$vv['id']} and prop_value_one='{$country}'")->find();
					  
					   if( !empty($res) )
					   {
						   $vv["val"] = $res["prop_value_two"];
						   if( $res["remark"]!="" && $res["remark"]>0 )
						   {
							   $issort = true;
							   $vv["adsort"] = $res["remark"];
						   }
					   }
				  }
				 
				  $out_data[ $vv["adtype"] ][] = [ "name"=>$vv["name"],"val"=>$vv["val"],"adsort"=>$vv["adsort"] ];
			   }
			  foreach( $out_data as &$r_v )
			  {
				  if( !empty( $r_v )  )
				  {
					  if( $issort ){
						   $r_v = my_sort($r_v,"adsort");
					  }					 
					  foreach( $r_v as &$z_v )
					  {
						  unset( $z_v["adsort"] );
					  }
				  }
			  }
			  
		   }
		   
         $condata = Db::name('adconfig')->where(['appid'=>$appid,"app_class"=>2])->field('name,val,id') ->select();
		 if( !empty($condata) )
		 {
			 foreach( $condata as &$vvv )
			 {
				
				if($country!="")
				  {
					   $resa = Db::name('adprop')->where("cfid={$vvv['id']} and prop_value_one='{$country}'")->find();
					  
					   if( !empty($resa) )
					   {
						   $vvv["val"] = $resa["prop_value_two"];
						  
					   }
				  }
				
				$out_data["conf"][ $vvv["name"] ] = $vvv["val"]; 
			 }
		 }
        $out_data["country"] = $country;		 
        return $out_data;		
	}
	//获取国家
	public function getcountry()
	{
		$ip = get_client_ip();
		$country_code = gettaobaoip($ip);
		if( $country_code=="-1" )
		{
			$country_code = getapiip($ip);
		}
       return ["country_code"=>$country_code];
	}
	
}
