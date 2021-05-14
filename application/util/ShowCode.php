<?php
namespace app\util;
  
  /**
   *  输出状态码  @2018-1-15
   *  客户端在调用接口返回统一状态码入口
   */

class ShowCode
{
    const SUCCESS = 200;
	const NOT_EXISTS =4004;  //接口返回不存在
    const INVALID = 1001;  //参数错误
    const DB_SAVE_ERROR =3001; //数据保存失败
	const DB_DATA_EMPTY =3002; //记录未找到
	const USER_DENIED_LOGIN = 4001; //用户已禁止登陆
	const UPLOADS_ERROR = 3003;//上传失败
	const ON_LIVING = 3004;//正在直播中
	const HAS_FOCUS = 3005;//已经关注了
	const COIN_NOT_ENOUGH =6001;//余额不足
	const USER_OFFLINE=6002;//女主播离线
	const USER_BUSY=6003;//女主播忙碌
	const USER_EXIT=6004;//用户退出
    const DB_READ_ERROR = -3;
    const CACHE_SAVE_ERROR = -4;
    const CACHE_READ_ERROR = -5;
    const FILE_SAVE_ERROR = -6;
    const LOGIN_ERROR = -7;
    const JSON_PARSE_FAIL = -9;
    const TYPE_ERROR = -10;
    const NUMBER_MATCH_ERROR = -11;
    const EMPTY_PARAMS = -12;
    const DATA_EXISTS = -13;
    const AUTH_ERROR = -14;

    const OTHER_LOGIN = -16;
    const VERSION_INVALID = -17;

    const CURL_ERROR = -18;

    const RECORD_NOT_FOUND = -19; // 记录未找到
    const DELETE_FAILED = -20; // 删除失败
    const ADD_FAILED = -21; // 添加记录失败
    const UPDATE_FAILED = -22; // 添加记录失败

    const PARAM_INVALID = -995; // 参数无效
    const ACCESS_TOKEN_TIMEOUT = -996;
    const SESSION_TIMEOUT = -997;
    const UNKNOWN = -998;
    const EXCEPTION = -999;

    static public function getConstants() {
        $oClass = new \ReflectionClass(__CLASS__);
        return $oClass->getConstants();
    }
}
