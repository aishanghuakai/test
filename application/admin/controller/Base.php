<?php
namespace app\admin\controller;
use think\Controller;
use think\Request;
use think\Session;
class Base extends Controller
{
    
	 /**
     * @var \think\Request Request实例
     */
    protected $request;
	
	//定义允许的方法
	protected $allow_func =array(
	   "updateproduct",
	   "get_ltv_total_data",
	   'data_json',
	   "ltv_json_data",
        "update_ltv",
        "update_campaign_ltv",
        "update_ad_ltv",
		"task_notice",
		"updatemaindata",
        "update_adset_ltv"
	);
	public $_adminname ="";
	
	public function __construct(Request $request)
    {
		
		parent::__construct();
		$this->request = $request;
		$this->_adminname = Session::get('username');
		if( !$this->_adminname )
		{
			if ($request->isAjax())
			{
				  echo "nologin";exit;
			}else{
				if( !in_array($this->request->action(),$this->allow_func) )
				{
				   $this->redirect('/admin_cate/login',302);exit;
				}
				
			}
			
		}
    }

}
