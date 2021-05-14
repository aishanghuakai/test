<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
class Glup
{
    	
	//获取开发者下的所有应用
	public function getdeveloper($url="")
	{
		echo 123;
	}
	//获取苹果排名游戏前100
	public function freetopgames()
	{
		header("Content-Type: text/html;charset=utf-8");
		$date =date("Y-m-d",time());
		$r =Db::query("select * from hellowd_grap_url where platform='ios' and `update`!='{$date}' limit 1");
		if(!isset($r[0]) && empty($r[0]))
		{
			exit("ok");
		}
		$r = $r[0];
		$url =$r["url"];
		$res = $this->googlecurl($url);
		$res = json_decode($res,true);
		if( isset($res["feed"]["results"]) && !empty( $res["feed"]["results"] ) )
		{
			$result = $res["feed"]["results"];
			$country =$r["country"];
			$platform="ios";
			foreach( $result as $key=>$vv )
			{
				
				$data["app_package"] =$vv["id"];
				$data["topnum"] =$key+1;
				$data["title"] =$vv["name"];
				$data["updated_time"] =$vv["releaseDate"];
				$data["developer"] =$vv["artistName"];
				$data["app_icon"] =$vv["artworkUrl100"]; 			
				$data["date"] = $date;
				$data["country"] = $country;
				$data["platform"] =$platform; 
				$rt = Db::name("product_grap")->field("id")->where([ "app_package"=>$data["app_package"],"platform"=>$platform,"country"=>$country,"date"=>$date ])->find();
				if( !empty($rt) )
				{
					unset($data["app_icon"]); 
					unset($data["title"]);
					Db::name("product_grap")->where([ "id"=>$rt["id"] ])->update($data);
				}else{
					
					Db::name("product_grap")->insert($data);
				}			
			}
			Db::name("grap_url")->where( ["country"=>$r["country"],"platform"=>"ios" ] )->update( ["update"=>$date] );
		}
		echo "ok";
	}
	
	//收件箱获取
	public function getmail()
	{
		$data = receive_mail();
		if( !empty($data) )
		{
			
			foreach( $data as &$vv )
			{
				if( isset($vv["id"]) )
				{
					
					$vv["id"] = trim($vv["id"]);
					$rt = Db::name("receive_email")->field("id")->where([ "receive_id"=>$vv["id"] ])->find();
					if( empty($rt) )
					{
						$vv["receive_id"] =$vv["id"];
						unset($vv["toOther"],$vv["toOtherName"]);
						Db::name("receive_email")->insert($vv);
					}
				}				
			}
			
		}
		echo "ok";
	}
	
	//请求
	private function googlecurl($url,$data=null,$method = null)
	{
	   $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST,1);
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		if($data){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
    }
	
	//收益花费 更新
	public function weekupdate()
	{
		$start = date("Y-m-d",strtotime("-3 day"));
		$date = date("Y-m-d");
		$res = Db::name("count_update")->where("isupdate=1 and update_date!='{$date}' and type='week'")->order("id asc")->find();
		if( !empty($res) )
		{
			
			$url = $res["url"]."?start={$start}&end={$start}";
			Db::name("count_update")->where("id={$res['id']}")->update(["isupdate"=>2,"update_date"=>$date,"update_time"=>date("Y-m-d H:i:s",time() ) ]);
			$this->googlecurl($url);
		}else{
			$r = Db::name("count_update")->find();
			if( $r["update_date"]==$date )
			{
				exit("ok");
			}
			Db::name("count_update")->where("isupdate=2 and type='week'")->update(["isupdate"=>1]);
			$this->weekupdate();
		}
		exit("ok");
	}
	
	//day 收益 花费 更新
	public function dayupdate()
	{
		$start = date("Y-m-d",strtotime("-1 day"));
		$date = date("Y-m-d");
		$res = Db::name("count_update")->where("isupdate=1  and type='day'")->order("id asc")->find();
		
		if( !empty($res) )
		{
			if( $res["day"]>1 )
			{
				$num = $res["day"];
				$start = date("Y-m-d",strtotime("-{$num} day"));
			}
			$url = $res["url"]."?start={$start}&end={$start}";
			Db::name("count_update")->where("id={$res['id']}")->update(["isupdate"=>2,"update_date"=>$date,"update_time"=>date("Y-m-d H:i:s",time() ) ]);
			$this->googlecurl($url);
		}else{
			
			Db::name("count_update")->where("isupdate=2 and type='day'")->update(["isupdate"=>1]);
			$this->daydate();
		}
		exit("ok");
	}
	
	//谷歌排行榜
	public function googleGameTop()
	{
		$date =date("Y-m-d",time() );
		$res =Db::query("select * from hellowd_grap_url where platform='android' and `update`!='{$date}' limit 1");
		if( !empty($res) && count($res)>0 )
		{
			 $url = $res[0]["url"];
			 import('phpQuery.phpQuery', EXTEND_PATH);
			 //实例化PHPExcel
			 header("Content-Type: text/html;charset=utf-8");
			 $detail = \phpQuery::newDocumentFile($url);
			 $list = pq($detail)->find(".id-card-list .card");
		  foreach($list as $k=>$vv)
		   {
				 $data =[];
				 $data["app_package"] = pq($vv)->attr("data-docid");					
				 $data["title"] = pq($vv)->find(".title")->text();								
				 $data["date"] = $date;
				 $data["app_icon"] =pq($vv)->find(".cover-image")->attr("src");
				 $data["developer"] =pq($vv)->find(".subtitle")->text(); 
				 $detailurl = "https://play.google.com/store/apps/details?id=".$data["app_package"];
				/*  $childdetail = \phpQuery::newDocumentFile($detailurl);
				 $data["comment_score"] =pq($childdetail)->find(".BHMmbe")->text();
				 $data["comment_users"] = pq($childdetail)->find(".EymY4b")->find("span")->eq(1)->text();
				 $data["updated_time"] = pq($childdetail)->find(".hAyfc")->eq(0)->find(".htlgb .htlgb")->text();
				 $data["installs"] = pq($childdetail)->find(".hAyfc")->eq(2)->find(".htlgb .htlgb")->text();				
				 $data["app_type"] = pq($childdetail)->find(".ZVWMWc .R8zArc")->eq(1)->text();	 */				 
				 $data["country"]=$res[0]["country"];
				 $data["platform"]=$res[0]["platform"];
				 $r = Db::name("product_grap")->field("id")->where([ "app_package"=>$data["app_package"],"platform"=>"android","country"=>$data["country"],"date"=>$date ])->find();
				 if( !empty($r) )
					{
						unset($data["app_icon"]);
						unset($data["title"]);
						Db::name("product_grap")->where([ "id"=>$r["id"] ])->update($data);
					}else{
						$arr = explode(".  ",$data["title"]);
						$data["title"] = $arr[1];
						$data["topnum"] = $arr[0];		
						$data["app_icon"] = $this->download($data["app_icon"]);
						Db::name("product_grap")->insert($data);
					}
					Db::name("grap_url")->where( ["country"=>$data["country"],"platform"=>"android" ] )->update( ["update"=>$date] );
		   }
		 exit("ok");
		}
	}
	
	private function download($url)
	{
	  $url ="https:".$url;
	  $file = file_get_contents($url);
	 
	  $filename = pathinfo($url, PATHINFO_BASENAME);
	  $path=ROOT_PATH . 'public' . DS . 'uploads' . DS . 'product/';
	  $last_path = $path.$filename.".png";
	  file_put_contents($last_path,$file);
	  return '/uploads/product/'.$filename.".png";
	}
	
	//合作信息提交
	public function contact()
	{
		$data  = input('post.');
			
		if( empty($data) )
		{
			exit("fail");
		}
		$r = Db::name("contact_us")->insert($data);
		if( $r!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	public function shopp_data($start="")
	{
		if( $start=="" )
		{
			$start = date("Y-m-d",strtotime("-1 day"));
		}
		$data =json_encode(array(
		   "fromDate"=>$start,
		   "toDate"=>$start,
		   "timeUnit"=>"day",
		   "page"=>1,
		   "pageSize"=>30,
		   "view"=>"index_details",
		   "relatedId"=>"5d25535b0cafb2362e001103"
		));
		$httpHeader[] = 'application/json, text/plain, */*';
		$httpHeader[] ='Content-Type: application/json;charset=utf-8';		
		$res = $this->curl_request($httpHeader,$data);
		if(  isset($res["data"]["items"][1]) && !empty( $res["data"]["items"][1] ) )
		{
			$list = $res["data"]["items"][1];
			$date = $list[0];
			$new_users = $list[1];
			$dau =$list[2];
			$r = Db::name("active_users")->where( ["app_id"=>129,"date"=>$date,"country"=>'all' ] )->find();
			if( empty($r) )
			  {									 
				 Db::name("active_users")->insert( ["app_id"=>129,"date"=>$date,"country"=>'all',"val"=>$dau ] ); 
			  }else{												 
				 Db::name("active_users")->where( "id",$r["id"])->update( ["val"=>$dau ] );  
			  }
            $r = Db::name("new_users")->where( ["app_id"=>129,"date"=>$date,"country"=>'all' ] )->find();
			if( empty($r) )
			  {									 
				 Db::name("new_users")->insert( ["app_id"=>129,"date"=>$date,"country"=>'all',"val"=>$new_users ] ); 
			  }else{												 
				 Db::name("new_users")->where( "id",$r["id"])->update( ["val"=>$new_users ] );  
			  }			  
		}
		exit("ok");
	}
	
	
	
	private function curl_request($httpHeader,$data=[])
	{
		header("Content-type:text/html;Charset=utf8");
		$ch = curl_init();
		$cookies ="UM_distinctid=16a919d67e06-020209820f08118-11656d4a-100200-16a919d67e1181; cna=curUEN+4cSgCAW+s7MWH2EeK; isg=BO_vtUmL7lRKAep8FghvqBcpfQM5PEDjHZTGyQF8s95lUA5SCWUBB54C1oBLMxsu; uc_session_id=27c6a0dd-a98e-4c99-9687-e40d0433a1ab; hng=CN%7Czh-CN%7CCNY%7C156; um_lang=zh; CNZZDATA1258498910=1310384264-1562823286-%7C1562823286; l=Anx8j-PVEocZ1VyfOy7l8R2rTBAu-CCf; cn_1276392090_dplus=1%5B%7B%7D%2C0%2C1562832063%2C0%2C1562832063%2Cnull%2C%2216a919d67e06-020209820f08118-11656d4a-100200-16a919d67e1181%22%2C%221562821996%22%2C%22https%3A%2F%2Fweb.umeng.com%2Fmain.php%3Fc%3Dsite%26a%3Dshow%26from%3Dtaobao%26tbpm%3D20190711%22%2C%22web.umeng.com%22%5D; CNZZDATA1259864772=79429300-1562826781-https%253A%252F%252Fwww.umeng.com%252F%7C1563589829; umplus_uc_loginid=%E7%88%B1%E4%B8%8A%E8%8A%B1%E5%BC%80; umplus_uc_token=1SYNLJ8I7gyUaJFOELijMpQ_968f0d6331874cfa9777e4691c372746; cn_1258498910_dplus=1%5B%7B%22userid%22%3A%22%22%7D%2C0%2C1563592471%2C0%2C1563592471%2C%22%24direct%22%2C%2216a919d67e06-020209820f08118-11656d4a-100200-16a919d67e1181%22%2C%221557219145%22%2C%22https%3A%2F%2Fwww.baidu.com%2Flink%3Furl%3D3si_VtGUjG5p4srsB8nSux_j1MKqiCgpFafZPth2gsK%26wd%3D%26eqid%3Df1f5dcb800011f1b000000055cd14f24%22%2C%22www.baidu.com%22%5D; ummo_ss=BAh7CUkiGXdhcmRlbi51c2VyLnVzZXIua2V5BjoGRVRbCEkiCVVzZXIGOwBGWwZvOhNCU09OOjpPYmplY3RJZAY6CkBkYXRhWxFpYmkraQHpaUFpEWkBr2kBsmkRaRdpAGkHaTtJIhl6YkhaQTFURGtHRFJCczkyWXlCeQY7AFRJIhR1bXBsdXNfdWNfdG9rZW4GOwBGIj0xU1lOTEo4STdneVVhSkZPRUxpak1wUV85NjhmMGQ2MzMxODc0Y2ZhOTc3N2U0NjkxYzM3Mjc0NkkiEF9jc3JmX3Rva2VuBjsARkkiMWlTc1FuTHViY1IxK3paRlpJWnRRVGE2Z0ZKdnR5N1JXVllNNDZ4bHJSOXc9BjsARkkiD3Nlc3Npb25faWQGOwBUSSIlZWIyM2Q2N2QyZTNmN2NmNTY0Mjc0NTZjODM1OGRiMGQGOwBG--11416118944eaeb4289e74c9a0c170beb8a53d74; frame=; JSESSIONID=709F357189E2F2CABB6C6B6AECED3AEA; cn_1259864772_dplus=1%5B%7B%22%E6%98%AF%E5%90%A6%E7%99%BB%E5%BD%95%22%3Atrue%2C%22UserID%22%3A%22%E7%88%B1%E4%B8%8A%E8%8A%B1%E5%BC%80%22%7D%2C0%2C1563592504%2C0%2C1563592504%2C%22%24direct%22%2C%2216a919d67e06-020209820f08118-11656d4a-100200-16a919d67e1181%22%2C%221562826781%22%2C%22https%3A%2F%2Fwww.umeng.com%2Fanalytics_games%22%2C%22www.umeng.com%22%5D; cn_1273967994_dplus=1%5B%7B%7D%2Cnull%2Cnull%2Cnull%2Cnull%2C%22%24direct%22%2C%2216a919d67e06-020209820f08118-11656d4a-100200-16a919d67e1181%22%2C%221562826781%22%2C%22https%3A%2F%2Fmobile.umeng.com%2Fplatform%2Fapps%2Flist%22%2C%22mobile.umeng.com%22%5D";
        curl_setopt($ch, CURLOPT_URL,"https://mobile.umeng.com/ht/api/v3/app/whole/detail?relatedId=5d25535b0cafb2362e001103");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$httpHeader);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
        curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch,CURLOPT_COOKIE,$cookies);
		
		curl_setopt($ch, CURLOPT_POST,1);
		
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数		
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $ret = curl_exec($ch);
        if (false === $ret) {
            $ret =  curl_errno($ch);
        }
        curl_close($ch);
        return json_decode($ret,true);
	}
}
