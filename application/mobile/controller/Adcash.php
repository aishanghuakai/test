<?php
namespace app\mobile\controller;
use \think\Db;
use \app\admin\model\Adcash_m;
class Adcash
{
    public function summary()
	{
		
		return view("summary");
	}
	
	//加载数据
	public function ajaxdata($ap_code="hw_tk",$start="",$end="",$platform="")
	{
		$app_info = getappcode($ap_code);
		 
		 if( $platform=="total" )
		 {
		  
		   $android_ids = $app_info["android"];
		   
		   $ios_ids = $app_info["ios"];
		   $fireos_ids = $app_info["fireos"];
		   $app_ids = array_merge($android_ids,$ios_ids,$fireos_ids );
		 }else{
			$app_ids =  $app_info[$platform];
		 }
		 $where["date"] =[ ['>=',$start],['<=',$end] ];
		 $where["app_id"] = ['in',$app_ids ];
		 $data = Adcash_m::getplatdata($where);
		 return view("ajaxdata",["data"=>$data] );
	}
}
