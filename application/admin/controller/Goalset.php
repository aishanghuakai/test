<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;

class Goalset extends Base
{
    
	public function index()
    {      
	  $r = Db::name("product_goal")->field("month")->group("month")->order("month desc")->select();
	  if( !empty($r) )
	  {
		  foreach( $r as &$vv )
		  {
			 
			  $list = Db::name("product_goal")->where( ["month"=>$vv["month"] ] )->order("id asc")->select();
			   $vv["month"] = str_replace("-",".",$vv["month"]);
			  if( !empty($list) )
			  {
				  foreach( $list as &$vvv )
				  {
					  $nameData =Db::name("admin")->field("truename")->find($vvv["manager"] ); 
					  $vvv["username"] = $nameData["truename"];
					  $appData =Db::name("app")->field("app_name")->find($vvv["app_id"] ); 
					  $vvv["app_name"] = $appData["app_name"];
				  }				  
			  }
			  $vv["list"] = $list;
		  }
	  }
	  return $this->fetch('index',["r"=>$r]);
    }
	
	public function getData()
	{
		$apps =Db::name("app")->field("id as value,platform,app_base_id,concat(app_name,'-',platform) as label")->where("status=1 and platform is not null")->select();
		if( !empty($apps) )
		{
			foreach($apps as &$vv)
			{
				if(  $vv["value"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["label"] = $row["name"].' - '.$vv["platform"];
					}
				}
			}
		}
		$users = Db::name("admin")->field("id as value,truename as label")->where("status=1")->select();
		echo json_encode(["apps"=>$apps,"users"=>$users]);exit;
	}
	
	public function edit($id="")
	{
		if(!$id)
		{
			exit(json_encode([]));
		}
		$app = Db::name("product_goal")->field("*")->find($id);
        $app["manager"] =(string)($app["manager"]);
        $app["status"] =(string)($app["status"]);		
		exit(json_encode(["app"=>$app]));
	}
	
	public function postForm()
	{
		$data = input("post.");
		$id = $data["id"];
		unset($data["id"]);
		if( $id==0 )
		{			
			$data["create_time"] = date("Y-m-d H:i:s",time());
		    Db::name("product_goal")->insert($data);
		}else{
			Db::name("product_goal")->where(["id"=>$id])->update($data);
		}		
		exit("ok");
	}
	
}
