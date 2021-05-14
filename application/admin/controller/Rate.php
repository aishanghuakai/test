<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Rate extends Base
{
    
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
	  $this->assign("appid",$appid);
	  $this->assign("channelList",[]);	  
	  return $this->fetch('index');
    }
  	
	public function add_rate($id="0",$channel="",$month="",$val="",$app_id="",$is_default="0")
	{
		if($channel && $month && $app_id)
		{
			$where =["channel"=>$channel,"month"=>$month,"app_id"=>$app_id];
			if($id>0)
			{
				Db::name("rate")->where(["id"=>$id])->update(["val"=>$val,"is_default"=>$is_default]);
			}else{
				$where['val']=$val;
				$where['is_default']=$is_default;
				Db::name("rate")->insert($where);
			}		
		}
		exit("ok");
	}
	
	public function rate_json($appid="",$channel="")
	{
		$out =[];
		if($channel && $appid)
		{
		  $out = Db::name("rate")->where("(app_id={$appid} and is_default=0 and channel='{$channel}') or (is_default=1 and channel='{$channel}')")->order('month desc')->select();
		  if(!empty($out))
		  {
			  foreach($out as &$v)
			  {
				  $v["is_default"] = (int)$v["is_default"];
			  }
		  }
		}
		echo json_encode($out);exit;
	}
}
