<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use think\Session;
use \think\Db;
class Message extends Base
{
    
	public function index($type="1")
    {      
	   $admin_id = Session::get('admin_userid');
	   if( $type=="1" )
	   {
		   $where ="to_userid={$admin_id}";
	   }else{
		  $where ="from_userid={$admin_id}"; 
	   }
	   $res = Db::name('send_message')->where($where)->order("id desc")->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );	
	   return $this->fetch('index',["list"=>$res,"type"=>$type]);
    }
	
	public function add_message($title="",$content="",$touserid="")
	{
		if( $title!="" && $content!="" && $touserid!="" )
		{
			$admin_id = Session::get('admin_userid');
			$lastid = Db::name("send_message")->insertGetId(["from_userid"=>$admin_id,"type"=>1,"to_userid"=>$touserid,"send_title"=>$title,"send_content"=>$content ]);
			if( $lastid!==false )
			{
				$data = ["message_id"=>$lastid,"type"=>2,"userid"=>$touserid];
				Db::name("message_read")->insert($data);
				exit("ok");
			}
		}
		exit("fail");
	}
	
	public function add_all($title="",$content="")
	{
		$res = Db::name("admin")->field("id")->where("ad_role!='copartner'")->select();
		foreach( $res as $vv )
		{
			$lastid =Db::name("send_message")->insertGetId(["from_userid"=>0,"type"=>1,"to_userid"=>$vv["id"],"send_title"=>$title,"send_content"=>$content ]);
			if( $lastid!==false )
			{
				$data = ["message_id"=>$lastid,"type"=>2,"userid"=>$vv["id"]];
				Db::name("message_read")->insert($data);
				
			}
		}
		exit("ok");
	}
	
	public function add()
	{
		return $this->fetch('add_message');
	}
	
	public function detail($id="")
	{
		if(!$id)return;
		$res =Db::name("send_message")->find($id);
		$admin_id = Session::get('admin_userid');
		Db::name("message_read")->where(["message_id"=>$id,"userid"=>$admin_id])->update(["isread"=>2]);
		return $this->fetch('detail',["res"=>$res]);
	}
	
	public function showmessage()
	{
		$arr=[];
		$r = Db::name("admin_log")->order("id desc")->find();
		$admin_id = Session::get('admin_userid');
		$result =Db::name("message_read")->where(["message_id"=>$r["id"],"userid"=>$admin_id,"type"=>1])->find();
		if( empty($result) && $r["userid"]>0 )
		{
			Db::name("message_read")->insert(["message_id"=>$r["id"],"userid"=>$admin_id,"type"=>1,"isread"=>2]);
			$data = Db::name("admin")->field("avatar,ad_role")->find($r["userid"]);
			$aadata = Db::name("admin")->field("ad_role")->find($admin_id);
			if($aadata["ad_role"]=="copartner")
			{
			  echo json_encode($arr);exit;
			}
			if( empty($data["avatar"]))
			{
				$data["avatar"]="http://console.gamebrain.io/static/images/noheader.png";
			}else{
				$data["avatar"]="http://console.gamebrain.io".$data["avatar"];
			}
			if( $admin_id==$r["userid"] )
			{
				$r["operate_name"]="您";
			}
			$arr = array(
			'info'   =>$r["operate_name"].mb_substr($r["operate_content"],0,15,'utf-8')."...   ".ChangeTime($r["operate_time"]),
			'img'    => $data["avatar"],
			'href'   => 'http://console.gamebrain.io/admin_log/index',
			);
		}
		
		echo json_encode($arr);exit;
	}
}
