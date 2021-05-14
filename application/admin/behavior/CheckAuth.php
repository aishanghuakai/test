<?php
namespace app\admin\behavior;
use \think\Db;
class CheckAuth 
{
    	
	public function appInit(&$params) {
       if(request()->has("appid"))
	   {
		    $appid = request()->param("appid");
		    request()->route(["appid"=>hotgame_decrypt($appid)] );		    	
	   }
    }
	
}
