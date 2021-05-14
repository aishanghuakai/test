<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
  //广告应用模块

class Advertisement extends Base
{
    public function lists( $type="1",$keyword="",$status="1" )
    {      		
	    $userinfo = getuserinfo();
		
		$where="1=1 and app_class in(1,2) and status={$status}";
		if( $type=="2" )
		{
			$where ="app_class in(1,2)";
		}
		if($keyword!="")
		{
			$where .=" and app_name like '%{$keyword}%'";
		}
		if( !in_array( $userinfo["ad_role"],["super","publisher","advertiser"] ) )
		{
			if( !$userinfo['allow_applist'] )
			{
				exit("您没有权限访问,请联系系统管理员");
			}
			$where .=" and id in({$userinfo['allow_applist']})";
		}
		
		$list =Db::name('app')
				 ->where ( $where )				 
				 ->order ( "addtime desc" )
				 ->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ 'type'=>$type,"keyword"=>$keyword,"status"=>$status ]
								] );								
	   $this->assign('list',$list);
	   if (request()->isPost())
	   {
		   return $this->fetch('searchapp');exit;
	   }
       $this->assign('type',$type);	
	   $this->assign('status',$status);
       $this->assign('keyword',$keyword);	   
	   return $this->fetch('list');
    }
	
	public function addindex()
	{
		return $this->fetch();
	}
	
	//获取国家对应upltv开关
	public function getupltv_switch($appid="",$country="")
	{
		if( !$appid )return;
		$r = Db::name("adconfig")->field("id,val")->where("appid={$appid} and name='upltv_switch_on'")->find();
		if( empty($r) )
		{
			exit("未设置");
		}else{
			if( $country && $country!="all" )
			{
				$tr = Db::name("adprop")->where("cfid={$r["id"]} and prop_value_one='{$country}'")->find();
				if( !empty($tr) )
				{
					$r["val"] = $tr["prop_value_two"];
				}
			}
			exit( $r["val"]==1?"ltv":"mopub");
			
		}
	}
	
	//内推展示
	public function adshow($appid="")
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
		$res = Db::name("app_spread")->where("status=1 and appid={$appid}")->order("ordernum asc,id desc")->select();
		$this->assign("appid",$appid);
		$this->assign("res",$res);
		return $this->fetch();
	}
	
	//内推修改
	public function adshow_edit($id="")
	{
		if(!$id)
		{
			return;
		}
		$res = Db::name("app_spread")->find($id);
		$this->assign("res",$res);
		return $this->fetch();
	}
	
	//内推添加
	public function adshow_add($appid="")
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
		$res = Db::name("app")->where( ["status"=>1 ] )->order("id desc")->select();
		$this->assign("appid",$appid);
		$this->assign("res",$res);
		return $this->fetch();
	}
	public function adshow_post()
	{
		$data = $_POST;
		$appid = $_POST["appid"];
		if( empty( $_POST["shop_url"] ) )
		{
			$this->error("请填写完整信息");exit;
		}
		$Video = request()->file('video_url');
        if ($Video != false) {						
            $info = $Video->validate(['ext' => 'mp4'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'video');
			if ($info) {
				$url=getdomainname()."/uploads/video/".$info->getSaveName();
			} else {
				// 上传失败获取错误信息
				$this->error($Video->getError());
			}
				$data['video_url'] = $url;
        }
		if( isset( $data["id"] ) && $data["id"] )
		{
			$id = $data["id"];
			unset($data["id"]);
			$r=Db::name("app_spread")->where(["id"=>$id])->update($data);
		}else{
			$r=Db::name("app_spread")->insert($data);
		}		
		if( $r!==false )
		{
			$this->success("操作成功","/admin_advertisement/adshow?appid={$appid}");exit;
		}
		$this->error("操作失败，请重试!");exit;
	}
	
	public function adshow_delete($id="")
	{
		if( !$id )return false;
		$ret = Db::name('app_spread')->delete($id);
        if($ret!==false)
		{
			exit("ok");
		}
        exit("fail");	
	}
	
	//产品设置
	public function setting($appid="")
	{
		
		if( !preg_match("/\d+$/",$appid) )
		{
			exit("No Access");
		}
		$res = Db::name("app")->field("app_name,icon_url,unique_hash")->where( ["id"=>$appid ] )->find();
		if( empty($res) )
		{
			exit("No Access");
		}
		 $this->assign('res',$res);
		return $this->fetch();
	}
	
	public function add_ads_data()
	{
		$data = $_POST;
		if( isset($data["id"]) && $data["id"]!="" )
		{
			$id = $data["id"];
			unset($data["id"]);
			$data["adduser"] =$this->_adminname;
			$data["updateuser"] =$this->_adminname;
			$time = date("Y-m-d H:i:s",time());
			$data["updatetime"] = $time;
			$ret = Db::name("app")->where( ["id"=>$id ] )->update($data);
		}else{
			$data["adduser"] =$this->_adminname;
			$data["updateuser"] =$this->_adminname;
			$time = date("Y-m-d H:i:s",time());
			$data["updatetime"] = $time;
			$data["addtime"] = $time;
			
			$ret = Db::name('app')->insertGetId($data);
			if($ret!==false)
			{
				$this->defaultParams($ret);
				$userinfo = getuserinfo();
				$allow_applist = $userinfo["allow_applist"];
				if( $allow_applist )
				{
					$allow_applist=$allow_applist.",".$ret;
				}else{
					$allow_applist=$ret;
				}
				Db::name("app")->where( ["id"=>$ret ] )->update(["unique_hash"=>mdd5($ret)]);
				Db::name('admin')->where(["id"=>$userinfo["id"]])->update(["allow_applist"=>$allow_applist]);
			}
		}
		
		if( $ret!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	 //广告参数默认添加
   	private function defaultParams($appid)
	{
		$adconfig = array(
		      ["name"=>"ae","val"=>"0","desc"=>"插屏转视频的次数，默认是0，不转；具体数值就代表转换几次","app_class"=>2],
			  ["name"=>"af","val"=>"3","desc"=>"插屏转视频，间隔视频的时间，默认是3分钟","app_class"=>2],
			  ["name"=>"ag","val"=>"0ac59b0996d947309c33f59d6676399f","desc"=>"mopub banner id","app_class"=>2],
			  ["name"=>"ah","val"=>"4f117153f5c24fa6a3a92b818a5eb630","desc"=>"mopub插屏id","app_class"=>2],
			  ["name"=>"ab","val"=>"35","desc"=>"新用户前多少秒没有插屏广告,插屏间隔时间，默认值35秒","app_class"=>2],
			  ["name"=>"aa","val"=>"60","desc"=>"新用户无广告时间，单位秒","app_class"=>2],
			  ["name"=>"ad","val"=>"20","desc"=>"小于多少秒无插屏广告，默认值20秒","app_class"=>2],
			  ["name"=>"ac","val"=>"3","desc"=>"插屏局数控制，几局出插屏广告，默认是3","app_class"=>2],
			  ["name"=>"upltv_switch_on","val"=>"0","desc"=>"upltv国家开关.1打开,给upltv流量,0关闭,切回mopub","app_class"=>2],
			  ["name"=>"ai","val"=>"8f000bd5e00246de9c789eed39ff6096","desc"=>"mopub reward id","app_class"=>2],
			  ["name"=>"aj","val"=>"1234567890","desc"=>"AppsFlyer APP ID；是纯数字，不带id两个字母，Android没有这个值","app_class"=>2],
			  ["name"=>"ak","val"=>"0ac59b0996d947309c33f59d6676399f","desc"=>"新的 mopub banner id ","app_class"=>2]
		);
		
		foreach( $adconfig as &$y )
		{
			$r = Db::name("adconfig")->where( ["appid"=>$appid,"name"=>$y["name"] ] )->find();
			if( empty($r) )
			{
				$y["appid"] =$appid;
				$y["adduser"] = "lxf";
				$y["updateuser"] = "lxf";
				$y["isnew"] =1;
				$y["addtime"] = date("Y-m-d H:i:s",time() );
				$y["updatetime"] = date("Y-m-d H:i:s",time() );
				Db::name("adconfig")->insert($y);
			}
		}		
		return true;
	}
	
	public function ads_edit($id="")
	{
		if( !$id ){
			exit("非法操作");
		}
		$res = Db::name('app')->find($id);
		return $this->fetch('ads_edit',[ "res"=>$res ]);
	}
	
	public function uploads($type)
	{
		$file = request()->file("upload_img");		
		if($file)
		{  
			$path=ROOT_PATH . 'public' . DS . 'uploads' . DS . $type;    
		    $info = $file->validate(['size'=>1024*1024*2,'ext' => 'jpg,png,gif,jpeg'])->move($path);
			if($info)
			{
				$url="/uploads/{$type}/".$info->getSaveName();
				return json_encode( ["status"=>200,"url"=>$url ] );
			}			 
		}
		return json_encode( ["status"=>400,"url"=>"" ] );
	}
	
	//广告具体列表
	public function adlist( $appid="",$type="",$adtype="all",$isnew="0" )
	{
		if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
		if( $appid )
		{
			 $userinfo = getuserinfo();
			 if( !in_array( $userinfo["ad_role"],["super","publisher","advertiser"] ) )
			{
				if( !in_array($appid,explode(",",$userinfo['allow_applist'] ) ) )
				{
					exit("您没有权限访问,请联系系统管理员");
				}				
			}
           setcache("select_app",$appid);			
			$r = Db::name('app')->field("app_name,app_class")->where("id={$appid}")->find();
			$where=" and isnew={$isnew}";
			if($type==1 )
			{
				$where.="  and app_class=1";
				if( $adtype!="" && $adtype!="all" )
				{
					$where.=" and adtype='{$adtype}' and app_class=1 ";
				}
			}else{
				$where.=" and app_class=2";
				
			}
			$list =Db::name('adconfig')
				 ->where ( "appid={$appid} {$where}" )				 
				 ->order ( "adtype,adsort" )
				 ->paginate(20,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>["id"=>$appid,"adtype"=>$adtype,"type"=>$type ]
								] ); 
	        $this->assign('list',$list);
			$this->assign("appid",$appid);
			$this->assign("type",$type);
			$this->assign("isnew",$isnew);
			$this->assign("ad_role",$userinfo["id"]);
			$this->assign("adtype",$adtype);
			$this->assign("app_name",$r["app_name"] );
			if( $type==2 )
			{
				return $this->fetch('adlist_tan');
			}else{
				return $this->fetch();
			}			
		}
		exit("非法操作");	   
	}
	//广告参数国家配置
	public function adparamcountry($id="",$code="")
	{
		$list =Db::name('adconfig')
			      ->alias ('uus')
				 ->field("uus.*,cc.prop_value_one,cc.prop_value_two,cc.id as pid")
				 ->join("hellowd_adprop cc", 'uus.id=cc.cfid')	
				 ->where ( "cc.cfid={$id}" )				 
				 ->order ( "cc.id" )
				 ->paginate(20,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>["id"=>$id,"code"=>$code ]
								] ); 
		$r =Db::name('adconfig')->field("name")->find($id); 
        $userid = Session::get('admin_userid');		
		return $this->fetch('adparams',["list"=>$list,"userid"=>$userid,"id"=>$id,"name"=>$r["name"],"admincountry"=>admincountry() ]);
	}
	public function adparams_add($prop_value_one="",$prop_value_two="",$cfid="")
	{
		if( $prop_value_one!=""  && $prop_value_two!="" && $cfid!="" )
		{
			Db::name('adprop')->insert( ["prop_value_one"=>$prop_value_one,"prop_value_two"=>$prop_value_two,"cfid"=>$cfid ] );
			exit("ok");
		}
		exit("fail");
	}
	
	public function adparams_edit($id="",$val="")
	{
		
        $ret = Db::name('adprop')->where("id={$id}")->update(["prop_value_two"=>$val]);
		if( $ret!==false  )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	public function getcountrylist($id="",$type="",$adtype="all",$code="")
	{
		if( $id )
		{
			$where=" and 1=1";
			$r = Db::name('app')->field("app_name,app_class")->where("id={$id}")->find();
            if( $adtype!="" && $adtype!="all" )
				{
					$where.=" and adtype='{$adtype}' ";
				}
		  if( $code!="" && $code!="all" )
			{
				$where.=" and prop_value_one='{$code}' ";
			}		
			$list =Db::name('adconfig')
			      ->alias ('uus')
				 ->field("uus.*,cc.prop_value_one,cc.prop_value_two,cc.id as pid,cc.remark")
				 ->join("hellowd_adprop cc", 'uus.id=cc.cfid')	
				 ->where ( "appid={$id} and app_class=1 {$where}" )				 
				 ->order ( "uus.adtype,CAST(cc.remark as SIGNED)" )
				 ->paginate(20,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>["id"=>$id,"adtype"=>$adtype,"type"=>$type,"code"=>$code ]
								] ); 
		}
		
		$this->assign("appid",$id);
		$this->assign("type",$type);
		$this->assign("adtype",$adtype);
		$this->assign('list',$list);
		$this->assign("code",$code);
		$this->assign("admincountry",admincountry());
        $this->assign("app_name",$r["app_name"] );		
		return $this->fetch();
	}
	
	public function sync($id="")
	{
		if(!$id)
		{
			exit("fail");
		}
		$live_app = Db::name('app')->find($id);
		$live_app_list = Db::name('adconfig')->where("appid",$id)->select();
		$live_app_prop = Db::name('adprop')->select();
		$db1 = Db::connect('mysql://thehotgames:week2e13&hellowd@127.0.0.1:3306/promote_data_test#utf8mb4');
		$db1->name('hellowd_app')->where("id={$id}")->delete();
		$db1->name('hellowd_adprop')->delete();
		$db1->name('hellowd_adconfig')->where("appid={$id}")->delete();
		$db1->name('hellowd_app')->insert($live_app);
		$db1->name('hellowd_adconfig')->insertAll($live_app_list);
		$db1->name('hellowd_adprop')->insertAll($live_app_prop);
		exit("ok");
	}
	
	public function country_edit()
	{
		$data =$_POST;
		$id = $data["id"];
		$cfid = $data["cfid"];
        $r = Db::name('adprop')->where("id={$id}")->update(["prop_value_one"=>$data["prop_value_one"],"prop_value_two"=>$data["prop_value_two"],"remark"=>$data["remark"] ]);
		$ret = Db::name('adconfig')->where("id={$cfid}")->update(["updatetime"=>date("Y-m-d H:i:s",time()),"updateuser"=>$this->_adminname ]);
		if( $ret!==false && $r!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	public function country_delete($id="")
	{
		if( !$id )return false;
		$ret = Db::name('adprop')->delete($id);
        if($ret!==false)
		{
			exit("ok");
		}
        exit("fail");	
	}
	
	//应用设置编辑
	public function edit()
	{
        $data =$_POST;
		$id = $data["id"];
		$data["updatetime"] = date("Y-m-d H:i:s",time());
		$data["updateuser"] = $this->_adminname;
		unset( $data["id"] );
		$ret = Db::name('app')->where("id={$id}")->update($data);
		if( $ret!==false )
		{
			admin_log("修改了应用");
			exit("ok");
		}
		exit("fail");
        
	}
	
	//广告设置编辑
	public function ad_edit()
	{
		$data =$_POST;
		$id = $data["id"];
		$data["updatetime"] = date("Y-m-d H:i:s",time());
		$data["updateuser"] = $this->_adminname;
		$before = Db::name('adconfig')->where("id={$id}")->value('val');
		unset( $data["id"] );
		$ret = Db::name('adconfig')->where("id={$id}")->update($data);
		if( $ret!==false )
		{
			admin_log("修改了广告应用ID:".$id."-名称:".$data["name"]."--值:由原来{$before}调整为".$data["val"]);
			exit("ok");
		}
		exit("fail");
	}
	
	//广告属性设置
	public function attr($id="")
	{
		if(!$id)return false;
		$res =Db::name('adprop')->where("cfid={$id}")->select();
		$this->assign("res",$res);
		$this->assign("cfid",$id);
		return $this->fetch();
	}
	public function attr_save()
	{
		$data = $_POST;
	    if( !isset($data["id"] ) )
		{
			exit("fail");
		}
		$cfid = $data["cfid"];
		
		Db::startTrans();
		Db::name('adprop')->where("cfid={$cfid}")->delete();
		$tag=true;
		foreach( $data["id"] as $key=>$vvv )
		{
			
				if( $data["prop_value_one"][$key]!="" && $data["prop_value_one"][$key]!="country" )
				{
					$r=Db::name('adprop')->insert( ["prop_value_one"=>$data["prop_value_one"][$key],"prop_value_two"=>$data["prop_value_two"][$key],"remark"=>$data["remark"][$key],"cfid"=>$cfid ] );
					if($r!==false)
					{
						$tag=true;
					}else{
						$tag=false;
					}
				}				
		}
		if( $tag )
		{
			Db::commit();
			exit("ok");
		}
        Db::rollback();
        exit("fail");		
	}
	//添加数据
	public function add()
	{
		$data = $_POST;
		$tag = $data["tag"];
		$data["adduser"] =$this->_adminname;
		$data["updateuser"] =$this->_adminname;
		$time = date("Y-m-d H:i:s",time());
		$data["updatetime"] = $time;
		$data["addtime"] = $time;
		unset($data["tag"]);
		Db::startTrans();
		$ret = Db::name('app')->insertGetId($data);
        if( "2"==$tag && $ret )
		{
			try{
				//预先生成广告类型配置
				$res = $this->getadvtype();
				 array_walk($res,function (&$v) use ($ret,$time) {
					 
					 $v["appid"] = $ret;
					 $v["adduser"] = $this->_adminname;
					 $v["updateuser"] = $this->_adminname;
					 $v["addtime"] =$time;
					 $v["updatetime"] = $time;
					 $v["app_class"] = 1;				 
				 });
				Db::name('adconfig')->insertAll($res); 
				// 提交事务
				admin_log("添加了广告ID");
				Db::commit();    
			} catch (\Exception $e) {
				// 回滚事务
				Db::rollback();
			}
			
		}else{
			Db::commit();
		}
        		
		echo "ok";exit;
	}
	
	public function delete($id="")
	{
		if( !$id )return false;
		Db::startTrans();
		$r = Db::name('app')->delete($id);
		if( $r!==false )
		{
			Db::name('adconfig')->where("appid={$id}")->delete(); 
			Db::commit();
			admin_log("删除了应用ID".$id);
			exit("ok");
		}
		Db::rollback();
		exit("fail");
	}
	
	public function ad_add()
	{
		$data = $_POST;
		$data["adduser"] =$this->_adminname;
		$data["updateuser"] =$this->_adminname;
		$time = date("Y-m-d H:i:s",time());
		$data["updatetime"] = $time;
		$data["addtime"] = $time;
	   
		$ret = Db::name('adconfig')->insertGetId($data);
		if($ret!==false)
		{
			admin_log("添加了广告配置--名称:".$data["name"]."值:".$data["val"] );
			exit("ok");
		}
        exit("fail");
	}
	
	public function ad_delete($id="")
	{
		if( !$id )return false;
		$ret = Db::name('adconfig')->delete($id);
        if($ret!==false)
		{
			admin_log("删除了一条广告配置".$id);
			exit("ok");
		}
        exit("fail");		
	}
	
	//获取广告   ad1、fb1、ad2、fb2、al、fb3、ad3
	private function getadvtype()
	{
		$configParams = array(
		    array(
				"adtype" => 'int',
				"name" => 'fb1',
				"adsort" => '2',
				"desc" => "Facebook_1"
			),
			array(
				"adtype" => 'int',
				"name" => 'am1',
				"adsort" => '1',
				"desc" => "Admob_1"
			),
			array(
				"adtype" => 'int',
				"name" => 'fb2',
				"adsort" => '4',
				"desc" => "Facebook_2"
			),
			array(
				"adtype" => 'int',
				"name" => 'am2',
				"adsort" => '3',
				"desc" => "Admob_2"
			),
			 
			array(
				"adtype" => 'int',
				"name" => 'al',
				"adsort" => '5',
				"desc" => "AppLovin"
			), 
			array(
				"adtype" => 'int',
				"name" => 'fb3',
				"adsort" => '6',
				"desc" => "Facebook_3"
			),			
			array(
				"adtype" => 'int',
				"name" => 'am3',
				"adsort" => '7',
				"desc" => "Admob_3"
			),
            array(
				"adtype" => 'int',
				"name" => 'un',
				"adsort" => '8',
				"desc" => "Unity"
			),
            array(
				"adtype" => 'int',
				"name" => 'ta',
				"adsort" => '9',
				"desc" => "Tapjoy"
			),
            array(
				"adtype" => 'int',
				"name" => 'vu',
				"adsort" => '10',
				"desc" => "Vungle"
			),			
			array(
				"adtype" => 'rew',
				"name" => 'fb1',
				"adsort" => '1',
				"desc" => "Facebook_1"
			),			
			array(
				"adtype" => 'rew',
				"name" => 'al',
				"adsort" => '2',
				"desc" => "AppLovin"
			),			
			array(
				"adtype" => 'rew',
				"name" => 'am1',
				"adsort" => '3',
				"desc" => "Admob_1"
			),			
			array(
				"adtype" => 'rew',
				"name" => 'un',
				"adsort" => '4',
				"desc" => "Unity"
			),			
			array(
				"adtype" => 'rew',
				"name" => 'vu',
				"adsort" => '5',
				"desc" => "Vungle"
			),			 
			array(
				"adtype" => 'nat',
				"name" => 'fb1',
				"adsort" => '1',
				"desc" => "Facebook_1"
			),
			array(
				"adtype" => 'nat',
				"name" => 'mv',
				"adsort" => '2',
				"desc" => "MobVista"
			)
        );
		return $configParams;
	}
	
	//用户信息更新
	public function edit_update($id="")
	{
		$data  = $_POST;
		
		$prev = $_SERVER['HTTP_REFERER'];
        $this->error('更新失败', $prev);
	}
}
