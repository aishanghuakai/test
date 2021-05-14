<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Income extends Base
{
    public function list($type="",$uuid="")
    {      
	   $where = "1=1 and pp.type in(1,4)";
	   if( $type!="" )
	   {
		   $where.=" and pp.type={$type}";
	   }
	   if( $uuid!="" )
	   {
		   $where.=" and uus.uuid={$uuid}";
	   }
	   $list =Db::name('user_flow_account')
	             ->alias ('pp')
				 ->field("uus.username,uus.country,uus.avatar,pp.account,pp.type,pp.addtime,pp.userid,uus.uuid")
				 ->join('chat_user uus','uus.id=pp.userid')
				 ->where ( $where )				 
				 ->order ( "pp.id desc" )
				 ->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "type"=>$type,"uuid"=>$uuid ]
							]);	   
	   $this->assign('list',$list);
       $this->assign('type',$type);
       $this->assign('uuid',$uuid);
	   return $this->fetch();
    }
	
	//按天统计
	public function daylist()
	{
		$sql = "SELECT FROM_UNIXTIME(addtime, '%Y-%m-%d' ) as time,SUM(account) as totalmoney from chat_user_flow_account where type in(1,4) GROUP BY time"; 
	    $list =Db::query($sql);
	    $result = admin_array_sort($list,'time','desc');
		
		$this->assign('list',$result);
		return $this->fetch();
	}
}
