<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use think\Validate;
class Profile extends Base
{
    
	//相关设置类
	public function index()
	{
		$admin_user = getuserinfo();
		$key = "hw_tk";
				
		return $this->fetch('index',["admin_user"=>$admin_user ] );
	}
	
	public function test()
	{
		 return $this->fetch();
	}
	
	public function app_list()
	{
	  $list = Db::name("adcash_appname")->order("updatetime desc")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[]
								] ); 
	   $this->assign('list',$list);
	   return $this->fetch();
	}
	
	public function app_add()
	{
		$data = $_POST;
		$data["updateuser"] =$this->_adminname;
		$time = date("Y-m-d H:i:s",time());
		$data["updatetime"] = $time;
		$res = Db::name("adcash_appname")->where( [ "app_id"=>$data["app_id"] ] )->find();
		if( empty($res) )
		{
			Db::name('adcash_appname')->insertGetId($data);
			echo "ok";exit;
		}
		echo "fail";exit;
	}
	
	public function app_delete($id="")
	{
		if(!$id)return false;
		$r=Db::name("adcash_appname")->delete($id);
		if( $r!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	public function password()
	{
		return $this->fetch();
	}
	
	public function rule($userid="1")
	{
		
		$where="ad_role!='copartner'";
		$admin_id = Session::get('admin_userid');
		if( $admin_id==7 || $admin_id==13 )
		{
			exit("You don't have access");
		}
		$r =Db::name("admin")->field("id,ad_rules,ad_role,allow_applist")->where("id",$admin_id)->find();					
		$userinfo =Db::name("admin")->field("id,ad_rules,ad_role,allow_applist,allow_testlist")->where("id",$userid)->find();		
		if( $admin_id==3 || $admin_id==59 || $admin_id==1)
		{
			$where = "1=1";			
		}else{
			if( $userinfo["ad_role"]=="copartner" )
			{
				exit("you do not allow");
			}
		}
		
		$res = Db::name("admin")->field("id,ad_rules,truename,ad_role")->where($where)->select();	
		$data = Db::name("app")->field("id,app_name,platform,app_base_id")->where("status=1" )->order("app_name asc")->select();
		if( !empty($data) )
		{
			foreach($data as &$vv)
			{
				if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
					}
				}
			}
		}
		$testList = Db::name("test_material")->field("id,title")->order("id desc")->select();
		$this->assign("data",$data);
		$this->assign("testList",$testList);
		$this->assign("userinfo",$userinfo);
		return $this->fetch('rule',["res"=>$res,"r"=>$r ]);
	}
	
	public function edit_rule($admin_id="",$rule="",$appids="",$ad_role="",$test_ids="")
	{
		if(!$admin_id)
		{
			$admin_id = Session::get('admin_userid');
		}
		Db::name("admin")->where("id",$admin_id)->update( ["ad_role"=>$ad_role,"ad_rules"=>rtrim($rule,","),"allow_applist"=>rtrim($appids,","),"allow_testlist"=>rtrim($test_ids,",") ] );
		exit("ok");
	}
	
	//密码修改
	public function passwd_save($old_passwd="",$new_passwd="")
	{
		$admin_id = Session::get('admin_userid');
		$admin_user = getuserinfo();
		if( hew_md5($old_passwd)!=$admin_user["passwd"] )
		{
			exit("输入的旧密码不正确");
		}
		if( strlen($new_passwd)<6  )
		{
			exit("新密码长度至少为6位以上");
		}
		 $rule = [		    
			'new_passwd'  => 'alphaDash',
		 ];

		 $msg = [		   
			'new_passwd.alphaDash' => '新密码只能为数字字母',
		 ];
		$validate = new Validate($rule, $msg);
		if (!$validate->check( ["new_passwd"=>$new_passwd ] )) {
				exit($validate->getError());
		  }
        $r = Db::name("admin")->where("id",$admin_id)->update( ["passwd"=>hew_md5($new_passwd) ]);
		if( $r!==false )
		{
			admin_log("修改了密码");
			exit("ok");
		}
		exit("保存失败");
	}
	
	public function admin_update()
	{
		$admin_id = Session::get('admin_userid');
		$data = $_POST;
		$file = request()->file('upload_img');
		
		if($file)
		{  
			$path=ROOT_PATH . 'public' . DS . 'uploads' . DS . 'avatar';    
		    $info = $file->validate(['size'=>1024*1024*2,'ext' => 'jpg,png,gif,jpeg'])->move($path);
			if($info)
			{
				$data["avatar"] ='/uploads/avatar/'.$info->getSaveName();
			}
			 
		}
		$result = Db::name("admin")->where("id",$admin_id)->update($data);
		if( $result!==false )
		{
			admin_log("修改了个人信息");
			$this->success('保存成功',"/admin_profile/index");exit;
		}
		$this->error('保存失败',"/admin_profile/index");exit;
	}
	
	public function other_set()
	{
		$admin_user = getuserinfo();
		$key = "hw_tk";
				
		return $this->fetch('other_set',["admin_user"=>$admin_user ] );
		
	}
	
	public function save_app_select($sendemail="",$send_content="")
	{
		$admin_id = Session::get('admin_userid');
		$send_content = rtrim($send_content,"|");
		$r = Db::name("admin")->where("id",$admin_id)->update( ["sendemail"=>$sendemail,"send_content"=>$send_content] );
		if( $r!==false )
		{			
			exit("ok");
		}
		exit("保存失败");
	}
	
	//检查是否信息填写完整
	public function checkinfocomplete()
	{
		
		$admin_id = Session::get('admin_userid');
		$r = Db::name("admin")->where("id",$admin_id )->find();
		if( $r["email"]!="" && $r["phone"]!="" )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	//发送邀请
	public function sendinvite()
	{
		$res = Db::name("app")->field("id,app_name,platform,app_base_id")->where("status=1" )->order("id desc")->select();
		if( !empty($res) )
		{
			foreach($res as &$vv)
			{
				if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
					}
				}
			}
		}
		$this->assign("res",$res);
		
		return $this->fetch();
	}
	
	public function add_invite($email="",$truename="",$appids="",$rules="",$ad_role="")
	{
		if( !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			exit("error");
		}
		$r = Db::name("admin")->where( [ "email"=>$email ] )->find();
		if( !empty($r) )
		{
			exit("exist");
		}
		$admin_id = Session::get('admin_userid');
		$last_id = Db::name("admin")->insertGetId( ["ad_role"=>$ad_role,"truename"=>$truename,"remark"=>$truename,"ad_rules"=>4,"allow_applist"=>rtrim($appids,","),"email"=>$email,"username"=>$email,"status"=>0,"p_id"=>$admin_id ] );
		if( $last_id!==false )
		{
			Db::name("admin_check")->insertGetId( ["userid"=>$last_id,"type"=>1 ] );
			$url =getdomainname()."/admin_cate/invite?spm=".base64_encode($last_id);//getdomainname()."/admin_cate/invite?spm=".base64_encode($last_id);
			$mydata = getuserinfo();
			$title = "来自于GameBrain平台的注册邀请";
		    $html="<p>Hi,{$mydata['truename']}邀请你注册，以下是注册入口地址，请您尽快完成相关操作</p>";
		$html.="<p><a href=\"{$url}\" target=\"_blank\" style=\"display: inline-block;color: #fff;
padding: 6px 12px;
margin-bottom: 0;
font-size: 14px;
font-weight: 400;
line-height: 1.42857143;
text-align: center;
white-space: nowrap;
vertical-align: middle;
-ms-touch-action: manipulation;
touch-action: manipulation;
cursor: pointer;
-webkit-user-select: none;
-moz-user-select: none;
-ms-user-select: none;
user-select: none;
background-color: #ed4040;
border: 1px solid transparent;
		border-radius: 4px;\" >邀请注册</a></p>";
		 send_mail( $email,$email,$title,$html );
		 exit("ok");
		}
		exit("fail");		
	}
}
