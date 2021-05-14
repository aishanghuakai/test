<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Notice extends Base
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
	   $start = date("Y-m-d",strtotime("-9 day"));
	   $end = date("Y-m-d",strtotime("-2 day"));
	   $this->assign("app_id",$appid);
	   $this->assign("start",$start);
	   $this->assign("end",$end);
	   $this->assign("country",admincountry());
	   return $this->fetch('index');
    }
	
	
}
