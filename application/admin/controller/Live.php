<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Live extends Base
{
    
	public function list( $uuid="" )
    {      
	   $where = "1=1 and pp.room_active=1 ";
	   
	   if( $uuid!="" )
	   {
		   $where.=" and uus.uuid={$uuid}";
	   }
	   $list =Db::name('live_room')
	             ->alias ('pp')
				 ->field("uus.username,uus.country,pp.userid,uus.uuid,pp.*")
				 ->join('chat_user uus','uus.id=pp.userid')
				 ->where ( $where )				 
				 ->order ( "pp.room_id desc" )
				 ->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
							]);	   
	   $this->assign('list',$list);

	   return $this->fetch();
    }
		
}
