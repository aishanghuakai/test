<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
set_time_limit(0);

class Adcreative 
{
   	
	private function gettouTiaoAccount($advertiser_id)
	{
		$res = array(
		    "108230033563"=>["app_id"=>"93"],
			"106713028212"=>["app_id"=>"77"],
			"106677044761"=>["app_id"=>"77"],
			"106713064697"=>["app_id"=>"68"],
			"108699407172"=>["app_id"=>"68"],
			"108699422358"=>["app_id"=>"93"],
			"111580090967"=>["app_id"=>"93"],
			"110655073381"=>["app_id"=>"107"],
			"110655056738"=>["app_id"=>"107"],
			"110655041439"=>["app_id"=>"107"],
			"110659871557"=>["app_id"=>"107"],
			"110659866957"=>["app_id"=>"107"],
			"110659886424"=>["app_id"=>"107"],
			"111603199845"=>["app_id"=>"77"],
			"3188212662276990"=>["app_id"=>"114"],
			"461423825914632"=>["app_id"=>"114"],
			"3223392436101134"=>["app_id"=>"107"],
			"3205800250321131"=>["app_id"=>"77"],
			"108230005386"=>["app_id"=>"68"],			
			"1633137763673096"=>["app_id"=>"77"],
			"1633137411769351"=>["app_id"=>"77"],
			"1631851380627468"=>["app_id"=>"77"],
			"1631851586421772"=>["app_id"=>"107"],		
			"1634854264204300"=>["app_id"=>"127"],
			"1634854617897998"=>["app_id"=>"127"],
			"1634854796319812"=>["app_id"=>"127"],			
			"1636917837114380"=>["app_id"=>"93"],
			"1636918030534663"=>["app_id"=>"129"],
			"1636918151490563"=>["app_id"=>"122"],			
			"1636918268913677"=>["app_id"=>"127"],
			"1636918391801867"=>["app_id"=>"127"],
			"1636918512519181"=>["app_id"=>"117"],
            "1639115541053453"=>["app_id"=>"117"],			
			"1631851177659404"=>["app_id"=>"107"], 
			"1638565951868941"=>["app_id"=>"127"],
			"1638566171071565"=>["app_id"=>"127"], 
			"1639114890601484"=>["app_id"=>"127"],
			"1639114795287566"=>["app_id"=>"127"],
			"1638566330742797"=>["app_id"=>"127"],
			"1638565801105412"=>["app_id"=>"127"]
			
		);
		if( $advertiser_id=="all" )
		{
			return $res;
		}
		return isset($res[$advertiser_id])?$res[$advertiser_id]:[];
	}
	
	public function updateApp()
	{
		$account = $this->gettouTiaoAccount("all");
		foreach( $account as $key=>$vv )
		{
			Db::name("material_detail")->where(["advertiser_id"=>$key ])->update(["app_id"=>$vv["app_id"] ]);
		}
		exit("ok");
	}
	
	public function gettouTiaoRequest($start="",$end="")
	{
		if( $start=="" || $end=="" )
		{
			$start = date("Y-m-d",strtotime("-2 day"));
			$end =  date("Y-m-d",strtotime("-2 day"));
		}
		header("Content-type: text/html; charset=utf-8");
		$access_token =$this->gettouTiaotoken();
		$account = $this->gettouTiaoAccount("all");
		foreach( $account as $key=>$vv )
		{
			$this->getcreativedata($access_token,$key,$start,$end,1);
		}
		exit("ok");
	}
	
	//查询图片信息
	private function getimageinfo($access_token,$advertiser_id,$image_ids)
	{
		$json  =json_encode( $image_ids );
	    $url ='https://ad.oceanengine.com/open_api/2/file/image/ad/get/?advertiser_id='.$advertiser_id.'&image_ids='.$json;		
		$res = $this->getcreative($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$url = isset($res["data"]["list"][0]['url'])?$res["data"]["list"][0]['url']:"";
			return $url;
		}
		return "";
	}
	
	//视频预览
	public function getVideoUrl($advertiser_id="",$video_id="")
	{
		$json =["code"=>20000,"message"=>"empty","result"=>[] ];
		if( !$advertiser_id )
		{
			$json["code"] = 10010;
			exit(json_encode($json));
		}
		$access_token =$this->gettouTiaotoken();
		$res = $this->getvideoinfo($access_token,$advertiser_id,[$video_id]);	
		$json["result"] =$res;
		exit(json_encode($json));
	}
    	
	private function getvideoinfo($access_token,$advertiser_id,$video_ids)
	{
		$json  =json_encode( $video_ids );
	    $url ='https://ad.oceanengine.com/open_api/2/file/video/ad/get/?advertiser_id='.$advertiser_id.'&video_ids='.$json;		
		$res = $this->getcreative($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$list = isset($res["data"]["list"][0])?$res["data"]["list"][0]:[];
			return $list;
		}
		return [];
	}
	
	public function updateimage()
	{
		$access_token =$this->gettouTiaotoken();
		$result = Db::query(" select image_id,advertiser_id from hellowd_material_detail where image_id!='' and thumb='' group by image_id,advertiser_id");
		if( !empty($result) )
		{
			foreach( $result as $vv )
			{
				$url = $this->getimageinfo($access_token,$vv["advertiser_id"],[$vv["image_id"]] );
				Db::name("material_detail")->where(["advertiser_id"=>$vv["advertiser_id"],"image_id"=>$vv["image_id"] ])->update(["thumb"=>$url]);
			}
		}
		exit("ok");
	}
	
	public function detailRequest()
	{
		
		header("Content-type: text/html; charset=utf-8");
		$access_token =$this->gettouTiaotoken();
		$account = $this->gettouTiaoAccount("all");
		foreach( $account as $key=>$vv )
		{
			$this->getallcreativelist($access_token,$key,$vv["app_id"],1);
		}
		exit("ok");
	}
	
	//获取广告创意数据
	private function getcreativedata($access_token,$advertiser_id="",$start_date,$end_date,$page)
	{
		
		$url ='https://ad.oceanengine.com/open_api/2/report/creative/get/?advertiser_id='.$advertiser_id.'&start_date='.$start_date.'&end_date='.$end_date.'&page_size=300&page='.$page.'&group_by=["STAT_GROUP_BY_FIELD_ID"]';
		
		$res = $this->getcreative($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$total_page = $res["data"]["page_info"]["total_page"];
			if( $page<=$total_page )
			{
				$list = $res["data"]["list"];			
				if( !empty($list) )
				{
				   $this->reportData($list,$start_date,$advertiser_id);
                   if( $page<$total_page )
					{
						++$page;
						return $this->getcreativedata($access_token,$advertiser_id,$start_date,$end_date,$page);
					}									   
				}				
			}
		}
		return true;
	}
	
	//报告数据插入
	private function reportData($result,$date,$advertiser_id)
	{
		if( !empty($result) )
		{
			foreach( $result as $vv )
			{
			   if( $vv["cost"]>0 || $vv["convert"]>0  )
			   {
				   $row =array(
				   "creative_id"=>$vv["creative_id"],
				   "cost"=>$vv["cost"],
				   "show"=>$vv["show"],
				   "convert"=>$vv["convert"],
				   "click"=>$vv["click"],
				   "total_play"=>$vv["total_play"],
				   "valid_play"=>$vv["valid_play"],
				   "advertiser_id"=>$advertiser_id,
				   "date"=>$date,
				   "ad_name"=>$vv["ad_name"],
				   "campaign_name"=>$vv["campaign_name"]
				);
					$o = Db::name("material_report")->where( ["creative_id"=>$row["creative_id"],"date"=>$row["date"],"advertiser_id"=>$row["advertiser_id"]] )->find();
					if( !empty($o) )
					{
						Db::name("material_report")->where(["id"=>$o["id"]])->update($row);
					}else{
						Db::name("material_report")->insert($row);
					}
			   }
			}
		}
		return true;
	}
	
	//创意素材详细信息
	private function detailData($result,$app_id)
	{
		if( !empty($result) )
		{
			foreach( $result as $vv )
			{			   
				   $row =array(
				   "creative_id"=>$vv["creative_id"],
				   "advertiser_id"=>$vv["advertiser_id"],
				   "title"=>$vv["title"],
				   "video_id"=>$vv["video_id"],
				   "image_id"=>$vv["image_id"],
				   "image_mode"=>$vv["image_mode"],
				   "app_id"=>$app_id,
				   "creative_create_time"=>$vv["creative_create_time"]
				);
					$o = Db::name("material_detail")->where( ["creative_id"=>$row["creative_id"],"video_id"=>$row["video_id"],"advertiser_id"=>$row["advertiser_id"]] )->find();
					if( !empty($o) )
					{
						Db::name("material_detail")->where(["id"=>$o["id"]])->update($row);
					}else{
						Db::name("material_detail")->insert($row);
					}
			}
		}
		return true;
	}
		
	
	//头条广告数据拉取
	private function getcreative($access_token,$url)
	{
		
		$headers = array('Access-Token: '.$access_token);
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_URL, $url);
		//设置头文件的信息作为数据流输出
		curl_setopt($curl, CURLOPT_HEADER, 0);
		//设置获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	
	//获取创意列表
	public function getcreativelist($access_token,$advertiser_id,$creative_ids,$page)
	{
		$filter_str = json_encode( ["creative_ids"=>$creative_ids ] );
		$url ='https://ad.oceanengine.com/open_api/2/creative/get/?advertiser_id='.$advertiser_id."&filtering={$filter_str}&page_size=10&page=".$page;
		$res = $this->getcreative($access_token,$url);
		$data = json_decode($res,true);
		return $data;
	}
	
	private function getallcreativelist($access_token,$advertiser_id,$app_id,$page)
	{
		$url ='https://ad.oceanengine.com/open_api/2/creative/get/?advertiser_id='.$advertiser_id."&page_size=500&page=".$page;
		$res = $this->getcreative($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$total_page = $res["data"]["page_info"]["total_page"];
			if( $page<=$total_page )
			{
				$list = $res["data"]["list"];
				
				if( !empty($list) )
				{
				   $this->detailData($list,$app_id);
                   if( $page<$total_page )
					{
						++$page;
						return $this->getallcreativelist($access_token,$advertiser_id,$app_id,$page);
					}									   
				}				
			}
		}
		return true;
	}
	
	//刷新token
	public function gettouTiaotoken()
	{
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		$access_token = $mem->get('access_token');
		if( $access_token )
		{
			return $access_token;
		}
		$refresh_token =$mem->get('refresh_token');
		
		//刷新token
		$data =array(
		   "app_id"=>"1620074816149511",
		   "secret"=>"28f20dd56475ec7590f67e2c49c51e3e5f99910a",
		   "grant_type"=>"refresh_token",
		   "refresh_token"=>$refresh_token
		);
		$url ="https://ad.oceanengine.com/open_api/oauth2/refresh_token/";
		$res = $this->googlecurl($url,http_build_query($data),'post');
		$result = json_decode( $res,true);
		if( isset($result["code"]) && $result["code"]==0 )
		{
			 $mem->set('access_token',$result["data"]["access_token"],0,72000);
	         $mem->set('refresh_token',$result["data"]["refresh_token"]);
			 return $result["data"]["access_token"];
		}
		return "";
	}
		
	//获取头条Refresh_token
	public function gettouTiaoRefresh_token()
	{
		//https://ad.oceanengine.com/openapi/audit/oauth.html?app_id=1620074816149511&state=your_custom_params&scope=%5B%22ad_service%22%2C%22report_service%22%5D&redirect_uri=http%3A%2F%2Fconsole.gamebrain.io%2Fadspend%2FtouTiaoCallback
		$auth_code ="d3080a3bccf1e13d01c7d9b6408c6baa0a91b04d";
		$data =array(
		   "app_id"=>"1620074816149511",
		   "secret"=>"28f20dd56475ec7590f67e2c49c51e3e5f99910a",
		   "grant_type"=>"auth_code",
		   "auth_code"=>$auth_code
		);
		$url ="https://ad.oceanengine.com/open_api/oauth2/access_token/";
		$res = $this->googlecurl($url,http_build_query($data),'post');
		print_r(json_decode( $res,true));
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
	
}
 