<?php
namespace app\api\controller;
use app\util\ShowCode;
 
 // 接口访问异常统一抛出
 
class Miss
{
    public function index()
    {      
	  return show_out(ShowCode::NOT_EXISTS,"the api request error",new \StdClass());
    }
	
}
