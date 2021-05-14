<?php
namespace app\common\income;
use \think\Db;
  //主播收益抽象类 @lxf
abstract class Revenue
{
    //每笔收益
    protected $income;
    
    //每笔收益类型
    protected $type; //收益类型 1 直播打赏 2 私聊打赏 3提现 4 私聊收益
	
	protected $userid;
	
	protected $resource_id;

	public function doincrease()
	{
		
		// 启动事务
		Db::startTrans();
		try{
			$field = $this->getfield();
			
			Db::execute("update chat_live_girl_info set available_gold_coin=available_gold_coin+'{$this->income}',total_gold_coin=total_gold_coin+'{$this->income}' {$field} where userid={$this->userid}");
		
			$time = time();
			Db::execute("insert into chat_user_flow_account(userid,type,account,addtime,resource_id)values( {$this->userid},{$this->type},'{$this->income}','{$time}','{$this->resource_id}' )");
			
			addredmessage($this->userid,$this->resource_id,5);
			// 提交事务
			Db::commit();
            return true;			
		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
			return false;
		}
	}
	abstract public  function action();
	
	//获取需要设置的字段值
	public function getfield()
	{
		if( $this->type==2 )
		{
			return ",chatpresent_gold_coin=chatpresent_gold_coin+'{$this->income}'";
		}
		return "";
	}
}
