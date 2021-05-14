<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;

//应用管理模块

class App extends Base
{
    
	public function index($groupID="")
    {      	   
	  $userid = Session::get('admin_userid');
	  $res = Db::name('app_group')->field("id,name")->where(["userid"=>$userid])->order("id desc")->select();
	  
	  $sql =" select id,package_name,platform,app_base_id from hellowd_app where app_base_id is not null";
	  $result =Db::query($sql);
	  if( !empty($result) )
		{
			foreach($result as &$r)
			{
				 $r["id"] = (string)$r["id"];
				 $row = Db::name("app_base")->field("name,CONCAT('http://console.gamebrain.io',icon) as imageUrl")->where("id",$r["app_base_id"])->find();
				 $row["name"] = $row["name"]."-".$r['platform'];
				 $r =array_merge($r,$row);
			}
		}
	  return $this->fetch('index',["res"=>$res,"groupID"=>$groupID,"allapps"=>$result]);
    }
	
	public function create_group($name="",$app_ids=[]){
		$result="";
		if($name && $app_ids)
		{
			$userid = Session::get('admin_userid');
			$result = Db::name('app_group')->insertGetId(["userid"=>$userid,"name"=>trim($name),"applist"=>json_encode($app_ids)]);
		}
		exit($result);
	}
	
	public function updategroup($groupID="",$app=[]){
		if($groupID)
		{
			Db::name('app_group')->where(["id"=>$groupID])->update(["applist"=>json_encode($app) ]);
		}
		echo json_encode(["code"=>200,"message"=>""]);exit;
	}
	
	public function updateStatus($id="",$status=""){
		if($id)
		{
			Db::name('app')->where(["id"=>$id])->update(["status"=>($status==1?0:1),"update_status_time"=>date("Y-m-d H:i:s") ]);
		}
		echo json_encode(["code"=>200,"message"=>""]);exit;
	}
	
	public function get_access_params($app_id=""){
		if( $app_id!="" )
		{
			$row = Db::name('access_params')->where(["app_id"=>$app_id])->find();
			echo json_encode($row);exit;
		}
		echo json_encode([]);exit;
	}
	
	public function params($appid=""){
		if( $appid!="" )
		{
			$r = Db::name("app")->find($appid);		
			if( $r["app_base_id"] )
			{
				$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
				$r["app_name"] = $row["name"].' - '.$r["platform"];
				$r["icon_url"] = $row["icon"];
			}
			return $this->fetch('params_v1',["app_id"=>$appid,"r"=>$r,"url"=>getdomainname().'/docs/params?t='.base64_encode($appid)]);
		}
		exit("不合法的参数");
	}
	
	public function params_v1($appid=""){
		if( $appid!="" )
		{
			$r = Db::name("app")->find($appid);		
			if( $r["app_base_id"] )
			{
				$row = Db::name("app_base")->where("id",$r["app_base_id"])->find();
				$r["app_name"] = $row["name"].' - '.$r["platform"];
				$r["icon_url"] = $row["icon"];
			}
			return $this->fetch('params_v1',["app_id"=>$appid,"r"=>$r,"url"=>getdomainname().'/docs/params?t='.base64_encode($appid)]);
		}
		exit("不合法的参数");
	}
	
	public function addgroup($name=""){
		if($name)
		{
			$userid = Session::get('admin_userid');
		}
		$row = Db::name('app_group')->where(["userid"=>$userid,"name"=>trim($name)])->find();
		if(empty($row))
		{
			$result = Db::name('app_group')->insertGetId(["userid"=>$userid,"name"=>trim($name)]);
		}
		echo json_encode(["code"=>200,"message"=>""]);exit;
	}
	
	public function deletegroup($id="")
	{
		if($id)
		{
			Db::name('app_group')->where(["id"=>$id])->delete();
		}
		echo json_encode(["code"=>200,"message"=>""]);exit;
	}
	
	public function add_params($app_id=""){
		$data =input('post.');
		if( !empty($data) )
		{
			$row = Db::name('access_params')->where(["app_id"=>$app_id])->find();
			unset($data["app_id"]);
			if( !empty($row) )
			{				
				Db::name('access_params')->where(["app_id"=>$app_id])->update(["content"=>json_encode($data)]);
			}else{				
				$params = ["app_id"=>$app_id,"content"=>json_encode($data)];
				Db::name('access_params')->insert($params);
			}
			$this->update_data($app_id,$data);
            admin_log("修改了产品参数配置由原来之前{$row['content']}调整为".json_encode($data));
			echo json_encode(["code"=>200,"message"=>""]);exit;
		}
		echo json_encode(["code"=>500,"message"=>"保存失败"]);exit;
	}
	
	private function update_data($app_id,$data){
		$r = Db::name('bind_attr')->where(["app_id"=>$app_id])->find();
		if(isset($data["gameId"]) && $data["gameId"])
		{			
			if( !empty($r) )
			{
				Db::name('bind_attr')->where(["app_id"=>$app_id])->update([ "ga"=>$data["gameId"] ]);
				
			}else{
				Db::name('bind_attr')->insert([ "ga"=>$data["gameId"],"app_id"=>$app_id ]);
				
			}
		}
		if(isset($data["adjust_token"]) && $data["adjust_token"])
		{			
			if( !empty($r) )
			{
				Db::name('bind_attr')->where(["app_id"=>$app_id])->update([ "adjust"=>$data["adjust_token"] ]);
				
			}else{
				Db::name('bind_attr')->insert([ "adjust"=>$data["adjust_token"],"app_id"=>$app_id ]);
				
			}
		}
		if(isset($data["package"]) && $data["package"])
		{
			Db::name('app')->where(["id"=>$app_id])->update([ "package_name"=>$data["package"] ]);
            
		}
		if(isset($data["appleID"]) && $data["appleID"])
		{
			Db::name('app')->where(["id"=>$app_id])->update([ "package_name"=>$data["appleID"] ]);
            
		}
	}
	
	public function app_json($page="1",$keyword="",$groupID="")
	{
		$where ="1=1 and status=1";
		$userid = Session::get('admin_userid');
		if($userid==95)
		{
			$where .=" and id=93";
		}
		if($keyword)
		{
		  $where.=" and name like '%{$keyword}%'";
		}
		$appList=[];
		if($groupID)
		{
			if($groupID=="ziyan")
			{
				$where.=" and type=1";
			}elseif($groupID=="faxing")
			{
				$where.=" and type=2";
			}else{
				$row = Db::name('app_group')->where(["id"=>$groupID])->find();
				$appList = json_decode($row["applist"],true);
				$wherea["id"] =["in",$appList];
				$r = Db::name('app')->field('app_base_id')->where($wherea)->group('app_base_id')->select();
				$where.=" and id in(".implode(",",array_column($r,'app_base_id')).")";
			}			
		}
		$list = Db::name('app_base')->where($where)->page($page,12)->order('updatetime desc')->select();
		if(!empty($list))
		{
			foreach($list as &$v)
			{
				$wherea["app_base_id"] = $v["id"];
				if($groupID && !in_array($groupID,["ziyan","faxing"]))
				{					
					$wherea["id"] =["in",$appList];
				}
				$v["apps"]= Db::name('app')->where($wherea)->select();
				$v["updatetime"] =date("m-d H:i",strtotime($v['updatetime']));
			}
		}
		$total = Db::name('app_base')->where($where)->count();
		echo json_encode(["list"=>$list,"total"=>$total,"appList"=>$appList]);exit;
	}
	
	public function app_revenue_json($page="1",$apapp_base_id="",$property_id="",$app_base_id="")
	{
		$where ="1=1 and app_base_id={$app_base_id}";
		if($apapp_base_id)
		{
		  $where.="  and apapp_base_id like '%{$apapp_base_id}%'";
		}
		if($property_id)
		{
			$where.="  and property_id like '%{$property_id}%'";
		}
		$list = Db::name('revenue_account')->where($where)->page($page,8)->select();
		if(!empty($list))
		{
			foreach($list as &$v)
			{
				$v["updatetime"] =date("m.d H:i",strtotime($v['updatetime']));
			}
		}
		$total = Db::name('revenue_account')->where($where)->count();
		echo json_encode(["list"=>$list,"total"=>$total]);exit;
	}
	
	public function app_advertising_json($page="1",$advertiser_id="",$channel="",$app_base_id="")
	{
		$admin_id = Session::get('admin_userid');		
		$where ="1=1 and app_base_id={$app_base_id}";
		if($advertiser_id)
		{
		  $where.="  and advertiser_id like '%{$advertiser_id}%'";
		}
		if($admin_id)
		{
			$where.=" and type=".($admin_id==70?2:1);
		}
		if($channel)
		{
			$where.="  and channel={$channel}";
		}
		$list = Db::name('advertising_account')->where($where)->page($page,8)->select();
		if(!empty($list))
		{
			foreach($list as &$v)
			{
				$v["updatetime"] =date("m.d H:i",strtotime($v['updatetime']));
				$v["rule"] = (int)$v["rule"];
				$v["channel"] = (int)$v["channel"];
			}
		}
		$total = Db::name('advertising_account')->where($where)->count();
		echo json_encode(["list"=>$list,"total"=>$total]);exit;
	}
	
	public function get_user_attr($apps=[])
	{
		$out =[];
		if( $apps )
		{
			foreach( $apps as $v )
			{
				$r = Db::name('bind_attr')->where(["app_id"=>$v["id"]])->find();
				if( !empty($r) )
				{
					$out[$v["platform"]."_ga"] = $r["ga"];
					$out[$v["platform"]."_adjust"] = $r["adjust"];
				}
			}
		}
		echo json_encode($out);exit;
	}
	
	public function setting()
	{
		return $this->fetch('setting');
	}
	
	public function edit_setting($id)
	{
		return $this->fetch('edit_setting',["id"=>$id]);
	}
	
	public function find($id="")
	{
		if($id)
		{
			$row = Db::name('app_base')->find($id);
			$apps = Db::name('app')->where("app_base_id",$id)->select();
			$platforms =[];
			if(!empty($apps))
			{
				foreach($apps as $a)
				{
					$platforms[]=$a["platform"];
					$row[$a["platform"].'_package_name'] = $a["package_name"];
					$row[$a["platform"].'_shop_url'] = $a["shop_url"];
				}
			}
			$row["type"] =(string)$row["type"];
			$row["platform"] = $platforms;
			echo json_encode($row);exit;
		}
		echo json_encode([]);exit;
	}
	
	public function deleteRow($id="",$type="")
	{
		if($id)
		{
			switch($type){
				case 1:
				  Db::name("app_base")->where("id",$id)->update(["status"=>0]);
				  exit("ok");
				break;
				case 2:
				 $table="advertising_account";
				break;
				case 3:
				 $table="revenue_account";
				break;
			}
			Db::name($table)->delete($id);
		}
		exit("ok");
	}
	
	public function add_revenue()
	{
		$data =input('post.');
		$data["updateuser"]=$this->_adminname;
		$data["updatetime"] =date("Y-m-d H:i:s",time());
		if(isset($data["id"]))
		{
			$id = $data["id"];
			unset($data["id"]);
			$result = Db::name('revenue_account')->where(["id"=>$id])->update($data);
		}else{
			$r = Db::name('revenue_account')->where(["app_base_id"=>$data["app_base_id"],"platform"=>$data["platform"],"app_id"=>$data["app_id"] ])->find();
			if(!empty($r))
			{
				$result = Db::name('revenue_account')->where(["id"=>$r["id"]])->update($data);
			}else{
				$result = Db::name('revenue_account')->insert($data);
			}
		}	
		if($result!==false)
		{			
			echo json_encode(["code"=>200,"message"=>""]);exit;
		}
		echo json_encode(["code"=>500,"message"=>"创建失败"]);exit;
	}
	
	public function add_user_attr(){
		$data =input('post.');
		$app_base_id=$data["app_base_id"];
		unset($data["app_base_id"]);
		if(!empty($data))
		{
			foreach($data as $key=>$vv)
			{
				list($platform,$field) = explode("_",$key);
				$row = Db::name('app')->field("id")->where(["app_base_id"=>$app_base_id,"platform"=>$platform ])->find();
				if(!empty($row))
				{
					$r = Db::name('bind_attr')->field("id")->where(["app_id"=>$row["id"]])->find();
					if( !empty($r) )
					{
						Db::name('bind_attr')->where(["app_id"=>$row["id"]])->update([$field=>$vv]);
					}else{
						Db::name('bind_attr')->insert([$field=>$vv,"app_id"=>$row["id"]]);
					}
				}
			}
		}
		echo json_encode(["code"=>200,"message"=>""]);exit;
	}
	
	public function add_advertising()
	{
		$data =input('post.');
		$data["updateuser"]=$this->_adminname;
		$data["updatetime"] =date("Y-m-d H:i:s",time());
		$app_name = $data["app_name"];
		$admin_id = Session::get('admin_userid');
		$arr = [
		   "19"=>5,
		   "12"=>4,
		   "74"=>9,
		   "82"=>2,
		   "79"=>10
		];
		$data["type"] = ($admin_id==70)?2:1;
		$data["ad_userid"] = isset($arr[$admin_id])?$arr[$admin_id]:"";
		unset($data["app_name"]);
		$result = true;
		if(isset($data["id"]))
		{
			$id = $data["id"];
			unset($data["id"]);
			$result = Db::name('advertising_account')->where(["id"=>$id])->update($data);
		}else{
			
			$advertiser_ids = explode("#",$data["advertiser_id"]);
			if(!empty($advertiser_ids))
			{
				$row = Db::name('app')->field("id")->where(["app_base_id"=>$data["app_base_id"],"platform"=>$data["platform"]])->find();
				$data["app_id"] = $row["id"];
				foreach($advertiser_ids as &$v)
				{
					$r = Db::name('advertising_account')->where(["app_base_id"=>$data["app_base_id"],"platform"=>$data["platform"],"channel"=>$data["channel"],"advertiser_id"=>$v ])->find();
					if(empty($r))
					{						
				        $insertData = $data;
						if($data["channel"]==2)
						{
							$res = curl("http://ad.gamebrain.io/facebook/get_account_info?advertiser_id={$v}");
							$resultData = json_decode($res,true);
							$insertData["name"] = isset($resultData["data"]["name"])?$resultData["data"]["name"]:$app_name."-".$data["platform"]."({$v})";
						}
						if($data["channel"]==3)
						{
							$insertData["name"] = $app_name."-".$data["platform"]."({$v})";
						}
						if($data["channel"]==4)
						{
							$res = curl("http://ad.gamebrain.io/tiktok/getAdvertiserName?advertiser_id={$v}");
							$resultData = json_decode($res,true);
							$insertData["name"] = isset($resultData["data"][0]["name"])?$resultData["data"][0]["name"]:$app_name."-".$data["platform"]."({$v})";
						}
						$insertData["advertiser_id"] = trim($v);
				        $result = Db::name('advertising_account')->insert($insertData);
					}
				}
			}
			
		}	
		if($result!==false)
		{			
			echo json_encode(["code"=>200,"message"=>""]);exit;
		}
		echo json_encode(["code"=>500,"message"=>"创建失败"]);exit;
	}
	
	public function add_setting()
	{
		$data =input('post.');
		$platforms = $data["platform"];
		Db::startTrans();
		$time = date("Y-m-d H:i:s",time());
		$params = [
		   "name"=>$data["name"],
		   "icon"=>$data["icon"],
		   "type"=>$data["type"],
		   "updateuser"=>$this->_adminname,
		   "updatetime"=>$time
		];
		if(isset($data["id"]))
		{
			Db::name('app_base')->where("id",$data["id"])->update($params);
			$ret = $data["id"];
		}else{
			$ret = Db::name('app_base')->insertGetId($params);
		}		
		foreach($platforms as $v)
		{
			if(!isset($data[$v.'_package_name']) || empty($data[$v.'_package_name']))
			{
				Db::rollback();
				echo json_encode(["code"=>500,"message"=>"请填写平台包名信息"]);exit;
			}
			if(!isset($data[$v.'_shop_url']) || empty($data[$v.'_shop_url']))
			{
				Db::rollback();
				echo json_encode(["code"=>500,"message"=>"请填写平台商店地址"]);exit;
			}
			$row =[
			   "package_name"=>$data[$v.'_package_name'],
			   "platform"=>$v,
			   "addtime"=>$time,
			   "updatetime"=>$time,
			   "adduser"=>$this->_adminname,
			   "shop_url"=>$data[$v.'_shop_url'],
			   "app_base_id"=>$ret
			];
			$r = Db::name('app')->where(["app_base_id"=>$ret,"platform"=>$v])->find();
			if(!empty($r))
			{
				$result = Db::name('app')->where(["app_base_id"=>$ret,"platform"=>$v])->update($row);
			}else{
				$result = Db::name('app')->insert($row);
			}
		}
		if($result!==false)
		{
			Db::commit();
			echo json_encode(["code"=>200,"message"=>""]);exit;
		}
		Db::rollback();
		echo json_encode(["code"=>500,"message"=>"创建失败"]);exit;
	}
}
