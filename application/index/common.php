<?php
use think\Db;
use app\util\Strs;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
//  api 专用公共文件函数
// 应用公共文件

function show_out($code,$message="",$data)
{
	$show_outdata = array(
	  "code"=>$code,
	  "message"=>$message,
	  "data"=>$data
	);
	return json($show_outdata);
}



