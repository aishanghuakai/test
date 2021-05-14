<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

//Route::alias('user','api/User');
#Route::domain('169.48.112.138','api');
return [
    '__alias__' =>  [
        'advertise'  => 'api/Advertise',
		'payment'=>'api/Payments',
		'adspend'=>'api/Adspend',
		'task'=>'api/Task',
		'adcash'=>'api/Adcash',
		'DataV'=>'api/Datav',
		'aglup'=>'api/Glup',
		'purchase'=>'api/Purchase',
		'event'=>'api/Event',
		'analytics'=>'api/Analytics',
		'adgastatic' =>'api/Gastatic',
		'material' =>'api/Adcreative',
		'appsflyer'=>'api/Appsflyer',
		'adjust'=>'api/Adjust',
		'chart'=>'admin/Chart',
		'tenjin'=>'api/Tenjin',
		'adwords'=>'api/Adwords',
		'backhistory'=>'Index/index/index',
		'say'=>'Index/index/news',
		'upload'=>'Index/index/upload',
	    'addPicture'=>'Index/index/addPicture',
		'postcomment'=>'Index/index/postcomment',
	    'viewimage'=>'Index/index/viewimage',
		'addpost'=>'Index/index/addpost',
		'addcomment'=>'Index/index/addcomment',
	    'like'=>'Index/index/like',
	    'loadmore'=>'Index/index/loadmore',
		'videoshow'=>'api/Datashow',
        'adjust_data'=>'api/adjust_data',
        'adjust_ltv_data'=>'api/adjust_ltv_data',
    ],
	'[api]'=>[
	  
	   '__miss__'  => 'api/Miss/index'
	],
	//'__miss__'  => 'api/Miss/index'
];
