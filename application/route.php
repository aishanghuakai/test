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
#Route::domain('admin.thinkphp.cn','index');
#Route::rule('new','api/Index/index');
#Route::rule('register','api/User/register');
 /* return [
   /*  '__pattern__' => [
        'name' => '\w+',
    ], 
	'__rest__'=>[
        // 指向index模块的blog控制器
        'blog'=>'index/index',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],
   'new'   => 'index/index',
];
 */