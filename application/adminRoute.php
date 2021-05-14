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
        'admin_index'  => 'admin/Index',
		'admin_advertisement'  => 'admin/Advertisement',
		'admin_adcash'  => 'admin/Adcash',
		'admin_adgain'  => 'admin/Adgain',
		'admin_adstatis'  => 'admin/Adstatis',
		'admin_purchase'  => 'admin/Purchase',
		'admin_cate'=>'admin/Cate',
		'document'=>'admin/Document',
		'admin_consu'=>'admin/Consu',
		'm_adcash'=>'mobile/Adcash',
		'admin_profile'=>'admin/Profile',
		'admin_finance'=>'admin/Finance',
		'admin_remark'=>'admin/Remark',
		'admin_product'=>'admin/Product',
		'admin_compet'=>'admin/Compet',
		'admin_reten'=>'admin/Reten',
		'admin_campagin'=>'admin/Campagin',
		'admin_log'=>'admin/Adlog',
		'admin_report'=>'admin/Report',
		'admin_user'=>'admin/User',
		'admin_chart'=>'admin/Chart',
		'survey'=>'admin/Survey',
		'ad_event'=>'admin/Anaevent',
		'message'=>'admin/Message',
		'glup'=>'admin/Glup',
		'admin_adsid'=>'admin/Adid',
		'admin_adset'=>'admin/Adset',
		'admin_rate'=>'admin/Rate',
		'admin_control'=>'admin/Control',
		'admin_active'=>'admin/Adactive',
		'admin_newuser'=>'admin/Adnewuser',
		'admin_adspend'=>'admin/Adspend',
		'admin_tenjin'=>'admin/Tenjin',
		'admin_ltv'=>'admin/Appsflyer',
		'admin_max'=>'admin/Applovin',
		'admin_appsflyer'=>'admin/Appsflyer',
		'goalset'=>'admin/Goalset',
		'notice'=>'admin/Notice',
		'testplan'=>'admin/Testplan',
		'testmaterial'=>'admin/Testmaterial',
		'admaterial'=>'admin/Admaterial',
		'delivery'=>'admin/Delivery',
		'finance'=>'finance/Index',
		'app'=>'admin/App',
		'docs'=>'index/Document',
        'admin_operate'=>'admin/Operate',
        'admin_adneed'=>'admin/Adneed',
		'admin_apkinfo'=>'admin/Apkinfo',
    ],
	'[admin]'=>[
	  
	   '__miss__'  => 'api/Miss/index'
	],
	//'__miss__'  => 'api/Miss/index'
];
