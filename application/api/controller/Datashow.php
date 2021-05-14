<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
class Datashow
{
    	
	public function index()
	{
		return view('index');
	}
}
