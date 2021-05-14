<?php
namespace app\index\controller;
use think\Db;
class Document
{
    
	public function improve()
    {
       
	   echo "test";
    }
	
	public function params($t="")
	{
		if(!$t || !base64_decode($t))
		{
			exit("error params");
		}
		$app_id = base64_decode($t);
		$r = Db::name("app")->find($app_id);		
		if( $r["app_base_id"] )
		{
			$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
			$r["app_name"] = $row["name"].' - '.$r["platform"];
			$r["icon_url"] = $row["icon"];
		}
		$res = Db::name('access_params')->where(["app_id"=>$app_id])->find();
		return view('params',["r"=>$r,"res"=>$res,"content"=>json_decode($res["content"],true)]);
	}
	
	
	//接口文档说明
	public function remark()
	{
		return view('remark');
	}
	//
	public function apply()
	{
		echo "ok";
	}
}
