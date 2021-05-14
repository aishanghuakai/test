<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use app\api\controller\Live;
class Apply extends Base
{
    //申请认证为女主播
	public function list($uuid="")
    {      
	   $where = "1=1 and pp.status=0";
	   
	   if( $uuid!="" )
	   {
		   $where.=" and uus.uuid={$uuid}";
	   }
	   $list =Db::name('female_apply')
	             ->alias ('pp')
				 ->field("uus.username,uus.country,uus.avatar,pp.apply_img,pp.apply_time,pp.status,pp.userid,uus.uuid,pp.id")
				 ->join('chat_user uus','uus.id=pp.userid')
				 ->where ( $where )				 
				 ->order ( "pp.id desc" )
				 ->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "uuid"=>$uuid ]
							]);	   
	   $this->assign('list',$list);

       $this->assign('uuid',$uuid);
	   return $this->fetch();
    }
	public function apply_confirm($id="",$status="")
	{
		if( $id  && $status )
		{
			if( $status==2 )
			{
				$r = Db::name("female_apply")->where("id",$id)->update( ["status"=>2,"update_time"=>time()] );
			}else{
				$res = Db::name("female_apply")->where("id",$id)->find();
				if(!empty($res) && $res["status"]==0)
				{
					$live = new Live();
					$live->set_status( $res["userid"] );
				}
				$r = Db::name("female_apply")->where("id",$id)->update( ["status"=>1,"update_time"=>time()] );
			}
			if( $r!==false )
			{
				exit("ok");
			}
		}
		exit("fail");
	}
	//女主播上传图像审核
	public function femalelist()
	{
		return $this->fetch();
	}
}
