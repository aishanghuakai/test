<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Glup extends Base
{
    
	public function index($appid="")
    {      	   
	  if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
	  setcache("select_app",$appid);
	  $r = Db::name("app")->find($appid);
	  if( empty($r) )
	  {
		  return redirect('/admin_index/select_app');exit;
	  }
	  $this->assign("product",$r);
	  $this->assign("country",admincountry());
	  return $this->fetch('index');
    }
	
	public function load_html($app_id="",$country="")
	{
	    $country = strtolower($country);
		$url ="https://www.chandashi.com/apps/view/appId/{$app_id}/country/{$country}.html";
	    $res = $this->get_content($url);
	    echo $res;exit;
	}

	public function aso_load_html($appId="",$country="")
	{
		$country = strtolower($country);
		$url ="https://www.chandashi.com/apps/keywordcover/appId/{$appId}/country/{$country}.html";
	    $res = $this->get_content($url);
		
		preg_match("/keywordData = (.*);/i",$res,$match);
		if(isset($match[1]))
		{
		    $res =json_decode($match[1],true);
			
			if( !empty($res) )
			{
				foreach($res as &$vv)
				{
					$vv[1] = explode("|",$vv[1]);
				}
			}
			$this->assign("res",$res);
			return $this->fetch('aso_load_html');
		}
	    
	}
	
	function get_content($url)
	{
		
		$cookie_file="cds_session_id=29on0pce7f43au6sor9jdcbgj4; __guid=23217980.3569131971083750000.1540729077555.657; monitor_count=1; Hm_lvt_0cb325d7c4fd9303b6185c4f6cf36e36=1540729079,1541166745; Hm_lpvt_0cb325d7c4fd9303b6185c4f6cf36e36=1541166745";
		$headers = array(           
            'Cookie:'.$cookie_file			
		);    
        //初始化
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //禁止 cURL 验证对等证书 
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //是否检测服务器的域名与证书上的是否一
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		//若给定url自动跳转到新的url,有了下面参数可自动获取新url内容：302跳转
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	  
		//执行命令
		$data = curl_exec($curl);
		//关闭URL请求
		curl_close($curl);
		return $data;
	}
}
