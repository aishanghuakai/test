<?php

namespace app\admin\controller;

use app\admin\controller\Base;
use \think\Db;
use think\Session;
use lib\Diff;


class Apkinfo extends Base
{
    protected $db=null;
	 protected $pconnection = [
        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',
        // 服务器地址
        'hostname'    => '127.0.0.1',
        // 数据库名
        'database'    => 'apk_info',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'root',
        // 数据库连接端口
        'hostport'    => '',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8mb4',
        // 数据库表前缀
        'prefix'      => 'appinfo_',
    ]; 
	
	 protected $sconnection = [
        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',
        // 服务器地址
        'hostname'    => '127.0.0.1',
        // 数据库名
        'database'    => 'apk_info',
        // 数据库用户名
        'username'    => 'thehotgames',
        // 数据库密码
        'password'    => 'week2e13&hellowd',
        // 数据库连接端口
        'hostport'    => '',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8mb4',
        // 数据库表前缀
        'prefix'      => 'appinfo_',
    ];
	
	public function _initialize()
	{
		$this->db = Db::connect($this->sconnection);
	}
	
	
	public function view($appid = "")
    {
        if ($appid == "") {
            $appid = getcache("select_app");
        }
        if (!$appid || !preg_match("/^\d+$/", $appid)) {
            return redirect('/admin_index/select_app');
            exit;
        }
        setcache("select_app", $appid);
        $start_date = date("Y-m-d", strtotime("-2 day"));
        $end_date = date("Y-m-d", strtotime("-2 day"));
        $this->assign("appid", $appid);
        $this->assign("start_date", $start_date);
        $this->assign("end_date", $end_date);
        return $this->fetch();
    }
	
	public function get_apk_info($filepath){
		$params =[
		   //"apkurl"=>"/var/www/gamebrain_data/public/app/20210324/bd19efdcfc89e261fffc843d07e22561.apk",
		   "apkurl"=>"/var/www/gamebrain_data/public/app/{$filepath}",
		   //"apkurl"=>"C:\work\app\gitapp\public\app\{$filepath}"
		];
		$filepath =$params["apkurl"];
		$res = curl("http://console.gamebrain.io:5000/getApkInfo",json_encode($params),"post");
		$result = json_decode($res,true);
		if(isset($result["package"]))
		{
			$row = $this->db->name('base')->where(["package"=>$result["package"],"versioncode"=>$result["versioncode"] ])->find();
			if(empty($row))
			{
				Db::startTrans();
				try {
					$base_data =array(
					   "package"=>$result["package"],
					   "versioncode"=>$result["versioncode"],
					   "filepath"=>$filepath,
					   "appname"=>$result["appname"],
					   "iconpath"=>$result["iconpath"],
					   "versionname"=>$result["versionname"],
					   "targetsdkversion"=>$result["targetsdkversion"],
					   "minsdkversion"=>$result["minsdkversion"],
					   "manifest"=>$result["manifest"]
					);
					$id = $this->db->name('base')->insertGetId($base_data);
					$activitie_data =array(
					  "appid"=>$id,
					  "activity"=>json_encode($result["activities"])
					);
					$this->db->name('activities')->insert($activitie_data);
					// 提交事务
					Db::commit();
				} catch (\Exception $e) {
					// 回滚事务
					print_r($e->getMessage());
					Db::rollback();
				}
			}
			return $result["package"];
		}
		return "";
	}
	
	private $channel =array(
	    "Admob"=>"com.google.android.gms.ads.AdActivity",
		"Applovin"=>"com.applovin.adview.AppLovinFullscreenActivity",
		"Ironsource"=>"com.ironsource.sdk.controller.ControllerActivity",
		"Mintegral"=>"com.mintegral.msdk.reward.player.MTGRewardVideoActivity",
		"Unity"=>"com.unity3d.services.ads.adunit.AdUnitActivity",
		"Vungle"=>"com.vungle.warren.ui.VungleActivity",
		"Csj"=>"com.bytedance.sdk.openadsdk.activity.baseTTRewardExpressVideoActivity",
		"Adcolony"=>"com.adcolony.sdk.AdColonyInterstitalActivity",
		"Inmobi"=>"com.inmobi.ads.rendering.InMobiAdActivity",
		"Fyber"=>"com.fyber.inneractive.sdk.activities.InneractiveFullscreenAdActivity",
		"Facebook"=>"com.facebook.ads.AudienceNetworkActivity"
	);
	
	public function aa(){
		echo phpinfo();
	}
	
	public function uploads(){
		
		$file = request()->file('upload_apk');
		if($file)
		{  
			$path=ROOT_PATH . 'public' . DS . 'app';    
		    $info = $file->validate(['size'=>1024*1024*200,'ext' => 'apk'])->move($path);
			if($info)
			{
				$filepath = $info->getSaveName();
				$package = $this->get_apk_info($filepath);
				echo json_encode(["code"=>200,"data"=>$package]);exit;
			}
			 
		}
		echo json_encode(["code"=>500,"message"=>$file->getError()]);
	}
	
	public function version_compare($packages=""){
		
        if(!$packages)return;
		$sql=" SELECT ab.*,aa.activity,concat('http://console.gamebrain.io/uploads/apkicon/',ab.iconpath) as iconpath  from  appinfo_base ab join appinfo_activities aa on ab.id=aa.appid WHERE ab.package='{$packages}' order by ab.versioncode desc limit 2";
		$res = $this->db->query($sql);
		$campare =[[],[]];
		if(!empty($res))
		{
			foreach($res as &$v)
			{
				$v["engine"] = preg_match("/cocos2dx/i",$v["activity"])?"cocos2dx":"unity3d";
				$v["activity"] = $this->get_channel_name($v["activity"]);
				//$v["manifest"] =json_decode($v["manifest"]);
			}
			$activities = array_column($res,'activity');
			$manifest = array_column($res,'manifest');
			if(count($activities)>1)
			{
				$result = Diff::compare(implode("\n",$activities[0]),implode("\n",$activities[1]));
			}
			
			if(!empty($result))
			{
				foreach($result as $c)
				{
					if($c[1]>0)
					{
						$campare[$c[1]-1][] = $c[0];
					}
				}
			}
		}
		echo json_encode(["res"=>$res,"campare"=>$campare]);exit;
	}
	
	function mb_str_cmp($str1,$str2,$encode='utf-8'){
          $num=mb_strlen($str2,$encode);
          if(strcmp($str1,$str2)==0)return array($str2,'');
          for($i=0;$i<$num;$i++){
               $word1=mb_substr($str1,$i,1,$encode);
               $word2=mb_substr($str2,$i,1,$encode);
               if($word1!=$word2)break;
          }
        return array(mb_substr($str2,0,$i,$encode),mb_substr($str2,$i,$num,$encode));
    }
	
	private function get_channel_name($activity)
	{
		$out =[];
		foreach($this->channel as $k=>$c)
		{
			
			if(preg_match("/{$c}/i",$activity))
				{
					$out[] = $k;
				}
		}
		return $out;
	}
}
