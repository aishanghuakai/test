<?php
namespace app\common\income;
use \think\Db;
use app\common\income\Revenue;

  //主播收益抽象类 @lxf
class Privaterevenue extends Revenue
{
   
    public $type=4;
	
	public $income=20;

	public function __construct($userid,$resource_id)
	{
		$this->userid = $userid;
		$this->resource_id = $resource_id;
	}
	//执行业务
	public function action()
	{
		if( isgirl( $this->userid ) )
		{
			return $this->doincrease();
		}
		return false;
	}
	
	//设置收益
	public function setincome($income="")
	{
		$this->income = $income;
	}
}
