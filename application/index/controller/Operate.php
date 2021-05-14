<?php
namespace app\index\controller;
use \think\Db;
class Operate
{
    //提现申请
	public function withdrawal_apply($userid="",$expend="")
    {
       
	   return view('apply',[ "userid"=>$userid,"expend"=>$expend ] );
    }
    
	//申请提现
	public function apply( $userid="",$expend="" )
	{
		return view('apply',[ "userid"=>$userid,"expend"=>$expend ] );
	}
	
	//申请成功
	public function apply_success()
	{
		return view('apply_success');
	}
	
	public function addapply()
	{
		  $data = $_POST;
		 // var_dump($data);exit;
			
		  $userid = $data["userid"];
		  $account = $data["remark"];
		  $expend = $account*180;
			
		  $user_acount =  Db::name('live_girl_info')->where(["userid"=>$userid ])->find();
		  if( empty( $user_acount ) )
		  {
			  return json(["code"=>404]);
		  }
		  $income = $user_acount["available_gold_coin"];
		  
		  if( bccomp($income,$expend,2)<0 )
		  {
			   return json(["code"=>405]);
		  }
		 Db::startTrans(); 
		 $res =  Db::execute("update chat_live_girl_info set available_gold_coin=available_gold_coin-'{$expend}',withdrawal_gold_coin=withdrawal_gold_coin+'{$expend}' where userid={$userid}");
		 if( $res!==false )
		 {
			 
			 $time = time();
			
			 //更新提现记录
			 $tr =  Db::execute("insert into chat_user_flow_account(userid,type,account,addtime,remark)values( {$userid},3,'{$expend}','{$time}','{$account}' )");
			 $lastid= Db::name('user_flow_account')->getLastInsID();
			 if($tr!==false)
			 {
				 $data["resource_id"] = $lastid;
				 $data["apply_time"]  =time();
				 Db::name('advance_account')->insert($data);
				 Db::commit();
				 return json(["code"=>200]);
			 }
			 Db::rollback();	 
            return json(["code"=>500]);
		 }else
		 {
			return json(["code"=>500]);
		 }
	}
}
