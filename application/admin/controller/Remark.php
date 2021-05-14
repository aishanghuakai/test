<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;

class Remark extends Base
{
    public function index()
    {      					
	   $where="1=1";
	   $list =Db::name('app_remark')
				 ->where ( $where )				 
				 ->order ( "id desc" )
				 ->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );								
	   $this->assign('list',$list);
	   return $this->fetch();
    }
	
	public function add($appid="")
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
		$this->assign("appid",$appid);
		return $this->fetch();
	}
	
	public function edit($id)
	{
		if(!$id)
		{
			return false;
		}
		$res = Db::name('app_remark')->find($id);
		$this->assign("res",$res);
		return $this->fetch();
	}
	
	
	public function add_data($title="",$content="",$appid="",$id="",$date="")
	{
		if( $title && $content  )
		{
			if( $id!="" )
			{
				$r = Db::name("app_remark")->where("id={$id}")->update(["title"=>$title,"content"=>$content ]);				
			}else{
				if( $date=="" )
				{
					$date = date("Y-m-d");
				}
				$userinfo = getuserinfo();
				if( $userinfo["id"]==3 )
				{
					$userinfo["ad_role"] = "advertiser";
				}
				if( $userinfo["id"]==6 )
				{
					$userinfo["ad_role"] = "publisher";
				}
				if( $userinfo["id"]==32 )
				{
					$userinfo["ad_role"] = "producter";
				}
				$r = Db::name("app_remark")->insert(["app_id"=>$appid,"userid"=>$userinfo["id"],"title"=>$title,"content"=>$content,"tag"=>$userinfo["ad_role"],"date"=>$date ]);				
			}
           if( $r!==false )
			{
				exit("ok");
			}			
		}
		exit("fail");
	}
	
	public function delete($id="")
	{
		if( !$id )return false;
		$ret = Db::name('app_remark')->delete($id);
        if($ret!==false)
		{
			exit("ok");
		}
        exit("fail");	
	}
}
