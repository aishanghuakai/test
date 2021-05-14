<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
class Delivery extends Base
{
    public function index($advertiser_id="",$campaign_id="")
	{					
		$users =  getuserinfo();
		$where="";
		$disabled = true;
		if( $users["ad_role"]=="super" || $users["id"]==19 )
		{
			$where="1=1";
			$disabled = false;
		}else{
			$where = "user_ids regexp '{$users['id']}'";
		}
		$accounts = Db::query("select advertiser_id,type,app_id from hellowd_advertiser_id_account where {$where}");
		$all=[];
        if( !empty( $accounts ) )
		{
			foreach( $accounts as $vv )
			{
				$all[$vv["advertiser_id"]] =[ "app_id"=>$vv["app_id"],"type"=>$vv['type'] ];
			}
		}
		//$all = $this->gettouTiaoAccount('all');
		$this->assign('all',$all);//110659871557
		$this->assign('disabled',$disabled);
		$this->assign('advertiser_id',$advertiser_id);
		$this->assign('campaign_id',$campaign_id);
		return $this->fetch();
	}
	
	public function account()
	{		
		$users = Db::name("admin")->field('id,truename')->where("ad_role='advertiser'")->select();
		$apps = Db::name("app")->field('id,app_name')->order('id desc')->select();
		$this->assign('users',$users);
		$this->assign('apps',$apps);
		return $this->fetch();
	}
	
	public function account_list($advertiser_id="",$page="")
	{
		$page = $page>0?$page:1;
		$where="1=1";
		if( $advertiser_id!="" )
		{
			$where = "advertiser_id={$advertiser_id}";
		}
		$list = Db::name('advertiser_id_account')->alias('a')->field('a.id,a.advertiser_id,a.type,a.user_ids,a.app_id,b.app_name')->join('hellowd_app b','a.app_id= b.id')->page("{$page},10")->where($where)->order('id desc')->select();
		foreach($list as &$vv)
		{
			$users = Db::name("admin")->field("truename")->where(" id in({$vv['user_ids']}) ")->select();
			$user_name ="";
			if( !empty($users) )
			{
				$user_name = implode(",",array_column($users,"truename"));
			}
			$vv["user_names"] = $user_name;
			$vv["app_id"] = (string)$vv["app_id"];
			$vv["user_ids"] = explode(",",$vv["user_ids"]);
		}
		$num = Db::name('advertiser_id_account')->where($where)->count();
		echo json_encode( ["list"=>$list,"total_number"=>$num,"page_size"=>10] );
	}
	
	public function CreateAccount()
	{
		$data = input('post.');
		$data["user_ids"] = implode(",",$data["user_ids"]);
		if( isset($data["id"]) && $data["id"]!="" )
		{
			$id = $data["id"];
			unset($data["id"],$data["app_name"],$data["user_names"]);
			$result = Db::name("advertiser_id_account")->where('id',$id)->update($data);
		}else{
			$result = Db::name("advertiser_id_account")->insert($data);
		}		
		if( $result!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	public function test()
	{
		echo $this->gettouTiaotoken();
	}
	
	public function ad_batch($advertiser_id="",$campaign_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$arr["ids"] =[$campaign_id];
		$where = json_encode($arr);
		$result = $this->campaign($access_token,$advertiser_id,$where,1);
		$industrylist = $this->getindustry($access_token);
		$custom_audience = $this->getstom_audience($access_token,$advertiser_id);
		$this->assign('custom_audience',$custom_audience);
		$this->assign('industrylist',$industrylist);
		$this->assign('result',$result);
		$this->assign('advertiser_id',$advertiser_id);
		$this->assign('campaign_id',$campaign_id);		
		return $this->fetch();
	}
	
	public function edit_ad($advertiser_id="",$campaign_id="",$ad_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$arr["ids"] =[$campaign_id];
		$where = json_encode($arr);
		$result = $this->campaign($access_token,$advertiser_id,$where,1);
		$industrylist = $this->getindustry($access_token);
		$this->assign('industrylist',$industrylist);
		$this->assign('result',$result);
		$this->assign('advertiser_id',$advertiser_id);
		$this->assign('campaign_id',$campaign_id);
		$this->assign('ad_id',$ad_id);
		$users =  getuserinfo();
		$whereA="";		
		if( $users["ad_role"]=="super" || $users["id"]==19 )
		{
			$whereA="1=1";
		}else{
			$whereA = "user_ids regexp '{$users['id']}'";
		}
		$accounts = Db::query("select advertiser_id,type,app_id from hellowd_advertiser_id_account where {$whereA}");
		$all=[];
        if( !empty( $accounts ) )
		{
			foreach( $accounts as $vv )
			{
				$all[$vv["advertiser_id"]] =[ "app_id"=>$vv["app_id"],"type"=>$vv['type'] ];
			}
		}
		$this->assign('all',$all);
		return $this->fetch();
	}
	
	public function getad_info($advertiser_id="",$ad_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$arr["ids"] =[$ad_id];
		$where = json_encode($arr);
		$fields =["adjust_cpa","advertiser_id","app_type","bid","budget","budget_mode","campaign_id","convert_id","cpa_bid","delivery_range","download_type","download_url","external_url",
		 "flow_control_mode","hide_if_converted","hide_if_exists","name","package","pricing","schedule_time","schedule_type",
		 "smart_bid_type","start_time","end_time","union_video_type","smart_inventory","scene_inventory","audience"
		];
		$url ='https://ad.oceanengine.com/open_api/2/ad/get/?advertiser_id='.$advertiser_id."&filtering=".$where."&fields=".json_encode($fields);	
		$res  = json_decode($this->getTouTiaoData($access_token,$url),true);
		$data = isset($res["data"]["list"])?$res["data"]["list"][0]:[];
		$smart_inventory="0";
		$inventory_value=[];
		$creativeslist =[];
		if( !empty($data) )
		{
			$r = $data["audience"];
			if( empty($r["ad_tag"]) )
			{
				$r["ad_tag"] ="";
			}elseif( $r["ad_tag"][0]=="0" )
			{
				$r["ad_tag"] ="0";
			}
			
			if( !empty($data) )
			{
				foreach( $data as $k=>$vv )
				{
					if( is_null($vv) )
					{
						unset($data[$k]);
					}
				}
			}
			if( !empty($r) )
			{
				foreach( $r  as $key=>$v )
				{
					if( is_null($v) )
					{
						unset($r[$key]);
					}
				}
			}		
			if( $data["download_type"]=="EXTERNAL_URL" )
			{
				$data["download_url"] = $data["external_url"];
				unset($data["external_url"]);
			}		
			unset($data["audience"],$data["ad_create_time"],$data["ad_modify_time"]);
			$createform =[ "creative_mode"=>"custom" ];
			$create = $this->getadcreate_info($access_token,$advertiser_id,$ad_id);
            if( !empty($create) )
			{
				if( isset( $create["creative_material_mode"] ) && $create["creative_material_mode"]=='STATIC_ASSEMBLE' ) //程序化创意
				{
					$createform["creative_mode"]="STATIC_ASSEMBLE";
					$createform["title_list"] = implode("#",array_column($create["title_list"],'title'));
					$createform["image_list"] = $create["image_list"];
					$creativeslist = $create["image_list"];
				}else{
					$creativeslist = $create["creatives"];
					$createform["creatives"] = $create["creatives"];
				}
				if( isset($create["playable_url"]) )
				{
					$createform["playable_url"]=$create["playable_url"];
				}
				if( isset($create["source"]) )
				{
					$createform["source"]=$create["source"];
				}
				if( isset($create["smart_inventory"]) && $create["smart_inventory"]=="1" )
				{
					$smart_inventory="1";
				}elseif( isset($create["inventory_type"]) && !empty($create["inventory_type"]) )
				{
					$smart_inventory="inventory_type";
					$inventory_value = $create["inventory_type"];
				}elseif( isset($create["scene_inventory"]) && $create["scene_inventory"]!="" )
				{
					$smart_inventory="scene_inventory";
					$inventory_value =[$create["scene_inventory"]];
				}
				$creativeslist = $this->getallimages($access_token,$advertiser_id,$creativeslist);
				if( isset($create["app_name"]) )
				{
					$createform["app_name"] = $create["app_name"];
				}				
				$createform["third_industry_id"] =$create["third_industry_id"];
				if( isset($create["advanced_creative_title"]) )
				{
					$createform["advanced_creative_title"] =$create["advanced_creative_title"];
				}
				$createform["ad_keywords"] = implode("#",$create["ad_keywords"]);
				$createform["creative_display_mode"] = $create["creative_display_mode"];
				$createform["advanced_creative_type"] = $create["advanced_creative_type"];
			}			
			$data = array_merge($data,$r);
			$custom_audience = $this->getstom_audience($access_token,$advertiser_id);
		}
		echo json_encode(["form"=>$data,"custom_audience"=>$custom_audience,"createform"=>$createform,"other"=>["smart_inventory"=>$smart_inventory,"creativeslist"=>$creativeslist,"inventory_value"=>$inventory_value]]);exit;
	}
	
	private function getallimages($access_token,$advertiser_id,$creativeslist)
	{
		if( !empty($creativeslist) )
		{
			
			foreach( $creativeslist as &$vv )
			{
				$vv["imageurl"] ="";
				$vv["images"] = [];
				if( in_array($vv["image_mode"],["CREATIVE_IMAGE_MODE_VIDEO_VERTICAL","CREATIVE_IMAGE_MODE_VIDEO"]) )
				{
					$res = $this->getimageinfo($access_token,$advertiser_id,[ $vv["image_id"] ]);
					if( !empty($res) )
					{
						$vv["imageurl"] =$res[0]["url"];
					    $vv["images"] = [ $res[0]["url"] ];
					}
				}else{
					$res = $this->getimageinfo($access_token,$advertiser_id,$vv["image_ids"]);
					if( !empty($res) )
					{
						$vv["imageurl"] =$res[0]["url"];
					    $vv["images"] = array_column($res,"url");
					}					
				}
			}
		}
		return $creativeslist;
	}
	
	//查询图片信息
	private function getimageinfo($access_token,$advertiser_id,$image_ids)
	{
		$json  =json_encode( $image_ids );
	    $url ='https://ad.oceanengine.com/open_api/2/file/image/ad/get/?advertiser_id='.$advertiser_id.'&image_ids='.$json;		
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$result = isset($res["data"]["list"])?$res["data"]["list"]:[];
			return $result;
		}
		return [];
	}
	
	private function getadcreate_info($access_token,$advertiser_id,$ad_id)
	{
		$url ='https://ad.oceanengine.com/open_api/2/creative/read_v2/?advertiser_id='.$advertiser_id."&ad_id=".$ad_id;	
		$res  = json_decode($this->getTouTiaoData($access_token,$url),true);
		$data = isset($res["data"])?$res["data"]:[];
		return $data;
	}
	
	public function CreateCampagin()
	{
		$data =input('post.');
		$advertiser_id = $data["advertiser_id"];			
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url ='https://ad.oceanengine.com/open_api/2/campaign/create/';	
		echo $this->getTouTiaoData($access_token,$url,json_encode($data),'post');exit;
	}
	
	public function deleteCampagin($advertiser_id="",$campaign_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url ='https://ad.oceanengine.com/open_api/2/campaign/update/status/';
		$data =array(
		   "advertiser_id"=>$advertiser_id,
		   "campaign_ids"=>[$campaign_id],
		   "opt_status"=>"delete"
		);
		echo $this->getTouTiaoData($access_token,$url,json_encode($data),'post');exit;
	}
	
	public function createad()
	{
		
		try {
			
			$params =input('post.');
			$data = $params["ad"];
			$create = $params["create"];
			$advertiser_id = $data["advertiser_id"];			
			if( $this->getcompanytype($advertiser_id)==1 )
			{
				$access_token =$this->gettouTiaotoken();
			}else{
				$access_token =$this->getNewTouTiaotoken();
			}			
			if($data["ad_tag"]=="0")
			{
				$data["ad_tag"] =[0];
			}else{
				$data["ad_tag"] =[];
			}
			if(isset($data["device_type"]) && $data["device_type"]!="" && $data["device_type"]!="0")
			{
				$data["device_type"] =[$data["device_type"]];
			}else{
				unset( $data["device_type"] );
			}
			$num = $data["num"];
			$name = $data["name"];
			unset($data["num"]);
			if( $data["download_type"]=="EXTERNAL_URL" )
			{
				$data["external_url"] = $data["download_url"];
				unset($data["download_url"]);
			}
			if( $data["delivery_range"]=='DEFAULT' )
			{
				if( $create["smart_inventory"]=='inventory_type' )
				{
					$create["inventory_type"] = $create["inventory_value"];
					unset($create["smart_inventory"],$create["inventory_value"]);
				}elseif( $create["smart_inventory"]=='scene_inventory' )
				{
					$create["scene_inventory"] = isset($create["inventory_value"][0])?$create["inventory_value"][0]:[];
					unset($create["smart_inventory"],$create["inventory_value"]);
				}else{
					$create["smart_inventory"] =$create["smart_inventory"];
					unset($create["inventory_value"]);
				}			
			}else{
				$create["inventory_type"] = ['INVENTORY_UNION_SLOT'];
				unset($create["inventory_value"],$create["smart_inventory"]);
			}
			if( isset($create["playable_url"]) )
			{
				if( $data["delivery_range"]=='DEFAULT' || !$create["playable_url"] )
				{
					unset($create["playable_url"]);
				}
			}
			if( isset( $data["platform"][0] ) )
			{
				if( $data["platform"][0]=="IOS" )
				{
					$data["ios_osv"] = $data["version"];
				}else{
					$data["android_osv"] = $data["version"];
				}
			}
            if( isset($data["version"]) )
			{
				unset($data["version"]);	
			}
			if( $create["creative_mode"]=="STATIC_ASSEMBLE" )
			{
				$create["creative_material_mode"] = 'STATIC_ASSEMBLE';
				$title_list = explode("#",$create["title_list"]);
				if( !empty($title_list) )
				{
					$r_list = [];
					foreach( $title_list as $vv )
					{
						$r_list[] = [ "title"=>$vv,"creative_word_ids"=>[] ];
					}
					$create["title_list"] = $r_list;
				}
				$create["creatives"] = [];
			}else{
				$create["title_list"] =[];
				$create["image_list"] =[];
			}
			unset($create["creative_mode"]);
			$create["advertiser_id"] = $data["advertiser_id"];		
			$create["ad_keywords"] =explode("#",$create["ad_keywords"]);
			$url ='https://ad.oceanengine.com/open_api/2/ad/create/';
			$create_url ="https://ad.oceanengine.com/open_api/2/creative/create_v2/";
			for($i=1;$i<=$num;$i++)
			{
				$title = $name."_pl".$i;
				$data["name"] = $title;
				$res = $this->getTouTiaoData($access_token,$url,json_encode($data),'post');
				$result = json_decode($res,true);
				if( $result["code"]!=0 )
				{
					echo json_encode( ["code"=>500,"message"=>$result["message"] ] );exit;
				}else{
					$ad_id = $result["data"]["ad_id"];				
					$create["ad_id"] = $ad_id;
					$tr = $this->getTouTiaoData($access_token,$create_url,json_encode($create),'post');
					$tr = json_decode($tr,true);
					if( $tr["code"]!=0 )
					{
						$this->updateAdStatus($access_token,$data["advertiser_id"],$ad_id); //删除广告计划
						echo json_encode( ["code"=>500,"message"=>$tr["message"] ] );exit;
					}
				}
			} 
		}catch (\Exception $e) {
		  echo json_encode( ["code"=>500,"message"=>$e->getMessage() ] );exit;
       }	
		echo json_encode( ["code"=>200,"message"=>"success" ] );exit;
	}
	
	private function updateAdStatus($access_token,$advertiser_id="",$ad_ids){
		$url ='https://ad.oceanengine.com/open_api/2/ad/update/status/';
		$data = array(
		   'advertiser_id'=>$advertiser_id,
		   'ad_ids'=>[$ad_ids],
		   'opt_status'=>'delete'
		);
		$res = $this->getTouTiaoData($access_token,$url,json_encode($data),'post');
	}
	
	public function getversion($type="ios")
	{
		$iosversion = array( ["value"=>"0.0","label"=>'不限'],["value"=>"4.0","label"=>'iOS 4.0'],["value"=>"4.1","label"=>'iOS 4.1'],
		 ["value"=>"4.2","label"=>'iOS 4.2'],["value"=>"4.3","label"=>'iOS 4.3'],["value"=>"5.0","label"=>'iOS 5.0'],
		 ["value"=>"5.1","label"=>'iOS 5.1'],["value"=>"6.0","label"=>'iOS 6.0'],["value"=>"7.0","label"=>'iOS 7.0'],
		 ["value"=>"7.1","label"=>'iOS 7.1'],["value"=>"8.0","label"=>'iOS 8.0'],["value"=>"8.1","label"=>'iOS 8.1'],
		 ["value"=>"8.2","label"=>'iOS 8.2'],["value"=>"9.0","label"=>'iOS 9.0'],["value"=>"9.1","label"=>'iOS 9.1']
		);
		$androidversion = array( ["value"=>"0.0","label"=>'不限'],["value"=>"2.0","label"=>'Android 2.0'],["value"=>"2.1","label"=>'Android 2.1'],
		 ["value"=>"2.2","label"=>'Android 2.2'],["value"=>"2.3","label"=>'Android 2.3'],
		 ["value"=>"3.0","label"=>'Android 3.0'],["value"=>"3.1","label"=>'Android 3.1'],["value"=>"3.2","label"=>'Android 3.2'],
		 ["value"=>"4.0","label"=>'Android 4.0'],["value"=>"4.1","label"=>'Android 4.1'],
		 ["value"=>"4.2","label"=>'Android 4.2'],["value"=>"4.3","label"=>'Android 4.3'],
		 ["value"=>"4.4","label"=>'Android 4.4'],["value"=>"4.5","label"=>'Android 4.5'],
		 ["value"=>"5.0","label"=>'Android 5.0'],["value"=>"5.1","label"=>'Android 5.1'],["value"=>"6.0","label"=>'Android 6.0'],["value"=>"7.0","label"=>'Android 7.0'],
		 ["value"=>"7.1","label"=>'Android 7.1'],["value"=>"8.0","label"=>'Android 8.0'],["value"=>"8.1","label"=>'Android 8.1'],["value"=>"9.0","label"=>'Android 9.0']
		);
		if( $type=="ios" )
		{
			echo json_encode($iosversion);exit;
		}else{
			echo json_encode($androidversion);exit;
		}
	}
	
	//人群包
	private function getstom_audience($access_token,$advertiser_id)
	{
		$url ="https://ad.oceanengine.com/open_api/2/dmp/custom_audience/select/?advertiser_id={$advertiser_id}&select_type=0&limit=100";
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$list = isset($res["data"]["custom_audience_list"])?$res["data"]["custom_audience_list"]:[];
			return $list;
		}
		return [];
	}
	
	
	public function getpackage($advertiser_id="",$convert_id){
		
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url ='https://ad.oceanengine.com/open_api/2/tools/ad_convert/read/?advertiser_id='.$advertiser_id."&convert_id=".$convert_id;
		$res = $this->getTouTiaoData($access_token,$url);
		echo $res;exit;
	}
	
	public function getcampaign($advertiser_id="",$campaign_id=""){		
		$form = $this->getdefaultad($advertiser_id,$campaign_id);
		echo json_encode($form);exit;
	}
	
	public function upload($advertiser_id="",$type=""){
		
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		if( $type=="video" )
		{
			$url ='https://ad.oceanengine.com/open_api/2/file/video/ad/';
		    $path = $_FILES["video_file"]["tmp_name"];
		    echo $this->formVideoRequest($access_token,$advertiser_id,$url,$path);exit;
		}elseif( $type=="image")
		{
			$url ='https://ad.oceanengine.com/open_api/2/file/image/ad/';
		    $path = $_FILES["image_file"]["tmp_name"];
		    echo $this->formImageRequest($access_token,$advertiser_id,$url,$path);exit;
		}		
	}
	
	public function video_cover($advertiser_id="",$video_id=""){
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url = "https://ad.oceanengine.com/open_api/2/tools/video_cover/suggest/?advertiser_id=".$advertiser_id."&video_id=".$video_id;
		$res = $this->getTouTiaoData($access_token,$url);
		echo $res;exit;
	}
	
	//转化ID列表
	public function adv_convert($advertiser_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url = "https://ad.oceanengine.com/open_api/2/tools/adv_convert/select/?advertiser_id=".$advertiser_id;
		$res = $this->getTouTiaoData($access_token,$url);
		echo $res;exit;
	}
	
	//试玩列表
	public function playable_list($advertiser_id="")
	{
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$url = "https://ad.oceanengine.com/open_api/2/tools/playable_list/get/?advertiser_id=".$advertiser_id;
		$res = $this->getTouTiaoData($access_token,$url);
		echo $res;exit;
	}
	
	public function json_data($advertiser_id="",$type="campaign",$keyword="",$campaign_id="",$page=1){
		
		if( $this->getcompanytype($advertiser_id)==1 )
		{
			$access_token =$this->gettouTiaotoken();
		}else{
			$access_token =$this->getNewTouTiaotoken();
		}
		$where="";
		if( $type=="campaign" )
		{
			$arr=[];
			if( $keyword!="" )
			{
				$arr["campaign_name"]=trim($keyword);
			}
			if( $campaign_id!="" )
			{
				$arr["ids"] =[$campaign_id];
			}
			if( !empty($arr) )
			{
				$where = json_encode($arr);
			}
		}elseif( $type=="ad" )
		{
			$arr =[];
			if( $keyword!="" )
			{
				$arr["ad_name"] = trim($keyword);
			}
			if( $campaign_id!="" )
			{
				$arr["campaign_id"] =$campaign_id;
			}
			if( !empty($arr) )
			{
				$where = json_encode($arr);
			}			
		}
		$data = $this->$type($access_token,$advertiser_id,$where,$page);
		echo json_encode($data);exit;
	}
	
	private function campaign($access_token,$advertiser_id,$where,$page)
	{	    
		$url ='https://ad.oceanengine.com/open_api/2/campaign/get/?advertiser_id='.$advertiser_id."&page={$page}";		
		if( $where )
		{
			$url.="&filtering=".$where;
		}
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$list = isset($res["data"])?$res["data"]:[];
			return $list;
		}
		return [];
	}
	
	
	
	public function getindustry($access_token){
		
		$out =[];
		$url ='https://ad.oceanengine.com/open_api/2/tools/industry/get/';
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		$data = isset($res["data"]["list"])?$res["data"]["list"]:[];
		if( !empty($data) )
		{
			foreach($data as $vv)
			{
				if( $vv["first_industry_id"]==1913 && $vv["level"]==3)
				{
					$out[] = [ "value"=>$vv["industry_id"],"label"=>$vv["industry_name"] ];
				}
			}
		}
		return $out;
	}
	
	
	private function creative($access_token,$advertiser_id,$where,$page)
	{	    
		$url ='https://ad.oceanengine.com/open_api/2/creative/get/?advertiser_id='.$advertiser_id."&page={$page}";		
		if( $where )
		{
			$url.="&filtering=".$where;
		}
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$list = isset($res["data"])?$res["data"]:[];
			if( isset( $list["list"] ) )
			{
				foreach( $list["list"] as &$vv )
				{
					$vv["status"] = $this->getcreateStatus($vv["status"]);
				}
			}
			return $list;
		}
		return [];
	}
	
	private function getdefaultad($advertiser_id,$campaign_id){
		return $form =[
		   "advertiser_id"=>(int)$advertiser_id,
		   "campaign_id"=>$campaign_id,
		   "delivery_range"=>'DEFAULT',
		   "union_video_type"=>'REWARDED_VIDEO',
		   "download_type"=>"download_url",
		   "convert_id"=>"",//1623867672285188
		   "download_url"=>"",//https://itunes.apple.com/cn/app/ore-tycoon-idle-mining-game/id1446683276
		   "smart_bid_type"=>'SMART_BID_CUSTOM',
		   "budget_mode"=>'BUDGET_MODE_DAY',
		   "schedule_type"=>'SCHEDULE_FROM_NOW',
		   "pricing"=>"PRICING_OCPM",
		   "start_time"=>"",
		   "end_time"=>"",
		   "ac"=>[],
		   "ad_tag"=>"",
		   "age"=>[],
           "bid"=>0,
		   "flow_control_mode"=>'FLOW_CONTROL_MODE_FAST',
		   "schedule_time"=>"111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
		];
	}
	
	private function getcreateStatus($status)
	{
		$all_status = array(
		    "CREATIVE_STATUS_DELIVERY_OK"=>'投放中',
			"CREATIVE_STATUS_NOT_START"=>'未到达投放时间',
			"CREATIVE_STATUS_NO_SCHEDULE"=>'不在投放时段',
			"CREATIVE_STATUS_DISABLE"=>'创意暂停',
			"CREATIVE_STATUS_CAMPAIGN_DISABLE"=>'已被广告组暂停',
			"CREATIVE_STATUS_CAMPAIGN_EXCEED"=>'广告组超出预算',
			"CREATIVE_STATUS_AUDIT"=>'新建审核中',
			"CREATIVE_STATUS_REAUDIT"=>'修改审核中',
			"CREATIVE_STATUS_DELETE"=>'已删除',
			"CREATIVE_STATUS_DONE"=>'已完成（投放达到结束时间）',
			"CREATIVE_STATUS_AD_DISABLE"=>'广告计划暂停',
			"CREATIVE_STATUS_AUDIT_DENY"=>'审核不通过',
			"CREATIVE_STATUS_BALANCE_EXCEED"=>'账户余额不足',
			"CREATIVE_STATUS_BUDGET_EXCEED"=>'超出预算',
			"CREATIVE_STATUS_DATA_ERROR"=>'数据错误（数据错误时返回，极少出现）',
			"CREATIVE_STATUS_PRE_ONLINE"=>'预上线',
			"CREATIVE_STATUS_AD_AUDIT"=>'广告计划新建审核中',
			"CREATIVE_STATUS_AD_REAUDIT"=>'广告计划修改审核中',
			"CREATIVE_STATUS_AD_AUDIT_DENY"=>'广告计划审核不通过',
			"CREATIVE_STATUS_ALL"=>'所有包含已删除',
			"CREATIVE_STATUS_NOT_DELETE"=>'所有不包含已删除（状态过滤默认值）',
			"CREATIVE_STATUS_ADVERTISER_BUDGET_EXCEED"=>'超出账户日预算'
		);
		return isset( $all_status[$status])?$all_status[$status]:"";
	}
	
	private function getadStatus($status)
	{
		$all_status = array(
		    "AD_STATUS_DELIVERY_OK" =>"投放中",
			"AD_STATUS_DATA_ERROR"=>'数据错误',
			"AD_STATUS_DISABLE"=>'计划暂停',
			"AD_STATUS_AUDIT"=>'新建审核中',
			"AD_STATUS_REAUDIT"=>'修改审核中',
			"AD_STATUS_DONE"=>'已完成（投放达到结束时间）',
			"AD_STATUS_CREATE"=>'计划新建',
			"AD_STATUS_AUDIT_DENY"=>'审核不通过',
			"AD_STATUS_BALANCE_EXCEED"=>'账户余额不足',
			"AD_STATUS_BUDGET_EXCEED"=>'超出预算',
			"AD_STATUS_NOT_START"=>'未到达投放时间',
			"AD_STATUS_NO_SCHEDULE"=>'不在投放时段',
			"AD_STATUS_CAMPAIGN_DISABLE"=>'已被广告组暂停',
			"AD_STATUS_CAMPAIGN_EXCEED"=>'广告组超出预算',
			"AD_STATUS_DELETE"=>'已删除',
			"AD_STATUS_FROZEN"=>'已冻结',
			"AD_STATUS_ALL"=>'所有包含已删除',
			"AD_STATUS_NOT_DELETE"=>'所有不包含已删除（状态过滤默认值）',
			"AD_STATUS_ADVERTISER_BUDGET_EXCEED"=>'超出账户日预算',
		);
		return isset( $all_status[$status])?$all_status[$status]:"";
	}
	
	private function ad($access_token,$advertiser_id,$where,$page)
	{	    
		$url ='https://ad.oceanengine.com/open_api/2/ad/get/?advertiser_id='.$advertiser_id."&page={$page}";
        if( $where )
		{
			$url.="&filtering=".$where;
		}			
		$res = $this->getTouTiaoData($access_token,$url);
		$res = json_decode($res,true);
		if( isset($res["code"]) && $res["code"]=="0")
		{
			$list = isset($res["data"])?$res["data"]:[];
			if( isset( $list["list"] ) )
			{
				foreach( $list["list"] as &$vv )
				{
					$vv["status"] = $this->getadStatus($vv["status"]);
				}
			}
			return $list;
		}
		return [];
	}
	
	
	
	private function getTouTiaoData($access_token,$url,$data_string="",$method="get")
	{
		
		$headers=[];
		$headers[] ='Access-Token: '.$access_token;
		$headers[] ='Content-Type: application/json';
		$curl = curl_init();
		//设置抓取的url
		curl_setopt($curl, CURLOPT_URL, $url);
		//curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
		//curl_setopt($curl, CURLOPT_PROXY, "42.159.91.248"); //代理服务器地址
		//curl_setopt($curl, CURLOPT_PROXYPORT,8080); //代理服务器端口		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
        if ($method == 'post') {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
		}
		curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}
	
	private function formVideoRequest($access_token,$advertiser_id,$url,$path)
	{
		$headers=[];
		$headers[] ='Access-Token: '.$access_token;
		$data = array("video_file"=>new \CURLFile(realpath($path)),
		"advertiser_id"=>$advertiser_id,
		"video_signature"=>md5_file($path) );
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$return_data = curl_exec($ch);
		curl_close($ch);
		return $return_data;
	}
	private function formImageRequest($access_token,$advertiser_id,$url,$path)
	{
		$headers=[];
		$headers[] ='Access-Token: '.$access_token;
		$data = array("image_file"=>new \CURLFile(realpath($path)),
		"advertiser_id"=>$advertiser_id,
		"upload_type"=>'UPLOAD_BY_FILE',
		"image_signature"=>md5_file($path) );
		$ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$return_data = curl_exec($ch);
		curl_close($ch);
		return $return_data;
	}
	//刷新token
	public function gettouTiaotoken()
	{
	    //return "6478e6d3ac0c2357deb84592103bd668115bb66e";
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		$access_token = $mem->get('access_token');
		if( $access_token )
		{
			return $access_token;
		} 
		$refresh_token =$mem->get('refresh_token');
		//$refresh_token="5f55ed92b85886cf4c9dbbbd7c946363419d654c";
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
	
	public function getNewTouTiaotoken()
	{
		//return '7d139d22b29e8a1ac210139fd8eb6a08b5398748';
		 $mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		 $access_token = $mem->get('new_toutiao_access_token');
		if( $access_token )
		{
			return $access_token;
		} 
		$refresh_token =$mem->get('new_toutiao_refresh_token');
		//$refresh_token="6310a6632e91711f37bf9bbdfafcbd0e1b3c9a46";
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
			 $mem->set('new_toutiao_access_token',$result["data"]["access_token"],0,72000);
	         $mem->set('new_toutiao_refresh_token',$result["data"]["refresh_token"]);
			 return $result["data"]["access_token"];
		}
		return "";
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
	
	private function gettouTiaoAccount($advertiser_id)
	{
		$res = array(
		    "108230033563"=>["app_id"=>"93","type"=>1],
			"106713028212"=>["app_id"=>"77","type"=>1],
			"106677044761"=>["app_id"=>"77","type"=>1],
			"106713064697"=>["app_id"=>"68","type"=>1],
			"108699407172"=>["app_id"=>"68","type"=>1],
			"108699422358"=>["app_id"=>"93","type"=>1],
			"111580090967"=>["app_id"=>"93","type"=>1],
			"110655073381"=>["app_id"=>"107","type"=>1],
			"110655056738"=>["app_id"=>"107","type"=>1],
			"110655041439"=>["app_id"=>"107","type"=>1],
			"110659871557"=>["app_id"=>"107","type"=>1],
			"110659866957"=>["app_id"=>"107","type"=>1],
			"110659886424"=>["app_id"=>"107","type"=>1],
			"111603199845"=>["app_id"=>"77","type"=>1],
			"3188212662276990"=>["app_id"=>"114","type"=>1],
			"461423825914632"=>["app_id"=>"114","type"=>1],
			"3223392436101134"=>["app_id"=>"107","type"=>1],
			"3205800250321131"=>["app_id"=>"77","type"=>1],
			"108230005386"=>["app_id"=>"68","type"=>1],			
			"1633137763673096"=>["app_id"=>"77","type"=>1],
			"1633137411769351"=>["app_id"=>"77","type"=>1],
			"1631851380627468"=>["app_id"=>"77","type"=>1],
			"1631851586421772"=>["app_id"=>"107","type"=>1],			
			"1634854264204300"=>["app_id"=>"127","type"=>1],
			"1634854617897998"=>["app_id"=>"127","type"=>1],
			"1634854796319812"=>["app_id"=>"127","type"=>1],			
			"1636917837114380"=>["app_id"=>"93","type"=>1],
			"1636918030534663"=>["app_id"=>"129","type"=>1],
			"1636918151490563"=>["app_id"=>"122","type"=>1],			
			"1636918268913677"=>["app_id"=>"127","type"=>1],
			"1636918391801867"=>["app_id"=>"127","type"=>1],
			"1636918512519181"=>["app_id"=>"117","type"=>1],
            "1639115541053453"=>["app_id"=>"117","type"=>1],			
			"1631851177659404"=>["app_id"=>"107","type"=>1],
			"1638565951868941"=>["app_id"=>"127","type"=>1],
			"1638566171071565"=>["app_id"=>"127","type"=>1],
			"1639114890601484"=>["app_id"=>"127","type"=>1],
			"1639114795287566"=>["app_id"=>"127","type"=>1],
			"1638566330742797"=>["app_id"=>"127","type"=>1],
			"1639117032686603"=>["app_id"=>"127","type"=>1],
			"1638565801105412"=>["app_id"=>"127","type"=>1],
			"1641914707674190"=>["app_id"=>"127","type"=>2],
			"1641914209584140"=>["app_id"=>"127","type"=>2],
			"1645070080411651"=>["app_id"=>"","type"=>2],
			"1645072152454152"=>["app_id"=>"","type"=>2],
			"1645072493441032"=>["app_id"=>"","type"=>2],
			"1641194098001931"=>["app_id"=>"127","type"=>2]
			
		);
		if( $advertiser_id=="all" )
		{
			return $res;
		}
		return isset($res[$advertiser_id])?$res[$advertiser_id]:[];
	}
	
	
	//获取主体类型
	private function getcompanytype($advertiser_id)
	{
		$res = $this->gettouTiaoAccount($advertiser_id);
		return $res["type"];
	}
	
	
}
