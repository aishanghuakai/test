<?php
namespace app\index\controller;
use think\Db;
class Document
{
    
	public function improve()
    {
       
	   echo "test";
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
