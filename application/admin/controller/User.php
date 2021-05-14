<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Session;
use \think\Db;
class User extends Base
{
    
	
	
	public function feedback(){
	   
	   $userinfo = getuserinfo();
	   $where ="1=1";
	   if($userinfo["id"]!="1" && $userinfo["id"]!="2")
	   {
		   $where ="userid={$userinfo["id"]}";
	   }
	   $list = Db::name('feedback')->where($where)->order("id desc")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );
		$this->assign("data",$list);
		return $this->fetch();
	}
	
	public function userlist()
	{
		
		 $res = Db::name('admin')->order("id asc")->select();
		 foreach($res as &$vv)
		 {
			 if( $vv["ad_role"]=="material" )
			 {
				 $vv["app_name"] ="暂无产品关联";
			 }else{
				 $apps = $this->getuserapps($vv["allow_applist"],$vv["ad_role"]);
				 $vv["app_name"] =$apps;
			 }
		 }
		 $admin_id = Session::get('admin_userid');
		 return $this->fetch('userlist',["res"=>$res,"userid"=>$admin_id ]);
	}
		
	 function getuserapps($ids,$role)
	 {
		 $html="";
		 if( $role=="super" )
		 {
			$where="1=1";
			
		 }else{
			if( !empty($ids) )
			{
				$where=" id in(".$ids.")";
			}else{
				$where = "1!=1";
			}			 
		 }
		 
		 $apps= Db::name("app")->where($where)->order("app_name asc")->select();
		 if(!empty($apps))
		 {
			 foreach( $apps as $vv )
			 {
				 if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
						$vv["icon_url"] = $row["icon"];
					}
				}
				 
				 $html.="<p style='vertical-align:middle'><img style='height:20px;width:20px;' src='{$vv['icon_url']}' />&nbsp;".$vv["app_name"]."</p>";
			 }
		 }else{
			$html="暂无产品关联"; 
		 }
		 return $html;
	 }
	
	 function getuserlike($id)
	{
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".$id;
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
       return "0";		
	}
	
	public function del_user($id=""){
		if($id)
		{
			Db::name('admin')->delete($id);
		}
		exit("ok");
	}
	
	
		
}
