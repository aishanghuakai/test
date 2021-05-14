<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Female extends Base
{
    public function list()
    {      
	   $where = "1=1 and uus.user_type=2 ";
	   $param_name ="";
	   $param_value="";
	   if( $this->request->has('id','get'))
	   {
		   $param_value = $this->request->get('id');
		   $param_name = "id";
		   $where ="uus.id=".$param_value;
	   }
	   if( $this->request->has('username','get'))
	   {
		   $param_value = $this->request->get('username');
		   $param_name = "username";
		   $where ="uus.username like '%{$param_value}%' ";
	   }
	   if( $this->request->has('email','get'))
	   {
		   $param_value = $this->request->get('email');
		   $param_name = "email";
		   $where ="email like '%{$param_value}%' ";
	   }
	   if( $this->request->has('gender','get'))
	   {
		   $param_value = $this->request->get('gender');
		   $param_name = "gender";
		   $where ="gender={$param_value}";
	   }
	   if( $this->request->has('registered_source','get'))
	   {
		   $param_value = $this->request->get('registered_source');
		   $param_name = "registered_source";
		   $where ="registered_source={$param_value}";
	   }
	   $list =Db::name('user')
	             ->alias ('uus')
				 ->field("uus.id,uus.username,uus.avatar,uus.email,uus.country,uus.version,uus.uuid,uus.fans_num,available_gold_coin,livepresent_gold_coin,chatpresent_gold_coin,withdrawal_gold_coin")
				 ->join('chat_live_girl_info pp','uus.id=pp.userid')
				 ->where ( $where )				 
				 ->order ( "uus.id desc" )
				 ->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ $param_name=>$param_value ]
							]);
       $this->assign('param_name',$param_name);
       $this->assign('param_value',$param_value);		   
	   $this->assign('list',$list);					
	   return $this->fetch();
    }
	
}
