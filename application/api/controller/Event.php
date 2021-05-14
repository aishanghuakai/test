<?php
namespace app\api\controller;
use think\Db;
use \think\Request;
use think\Validate;
use app\util\ShowCode;
header("Access-Control-Allow-Origin: *");
class Event extends Validate
{
    //自定义事件
	private $hellowd_ana_eventdata =["action","country","app_version","label","app_id","utc_time","cn_time","cn_date","category","date"];
	//用户属性
	private $hellowd_ana_device = ["country","device_type","device_size","device_model","platform_version","language","device_id","date"];
	//用户启动APP
	private $hellowd_ana_applanuch =["country","app_version","app_id","date","dv_id"];
	
	private $hellowd_ana_appnewuser =["country","app_version","app_id","date","dv_id"];
	//事件
	public function data()
	{
		$data = input("post.");
		$out["code"] ="Authorization failure";
		$out["result"] =[];
        //$this->test($data);
		if(  !empty($data) )
		{
			
			try{
		  		
				if( $this->auth($data["app_id"],$data["key"]) && $data["device_id"] )
				{
					unset($data["key"]);
					
					$time = time();
					$data["cn_time"] =$time;
					$data["date"] =date("Y-m-d",$time); 
					$data["cn_date"] =date("Y-m-d H:i:s",$time);
					$data["utc_time"] = $time-(8 * 24 * 60 * 60);
					Db::startTrans();
					$dv_id =$this->device_data($data);
					
					if( $dv_id!==false )
					{
						$event_data =$this->getuserattr($data,$this->hellowd_ana_eventdata);
						
						$event_data["dv_id"] =$dv_id; 
						$r =Db::name("ana_eventdata")->insert($event_data);
						if( trim($data["category"])=="applanuch" )
						{
							$data["dv_id"] = $dv_id;
							$this->applanuch($data);
						}
						if( trim($data["category"])=="usertime" )
						{
							$data["dv_id"] = $dv_id;
							$this->updateusertime($data);
						}
						$out["code"] = "success";
						Db::commit();
					}else{
						Db::rollback();
					}					
				}
				
 			} catch (\Exception $e) {
			  Db::rollback();
		      echo json_encode($out);exit;
			  //echo $e->getmessage();
		   }
		}
		echo json_encode($out);exit;
	}
	
	private function getuserattr($data,$attr)
	{
		$result =[];
		if( count($data) >0)
		{
			foreach( $data as $k=>$vv )
			{
				if( in_array($k,$attr))
				{
					$result[$k] =$vv;
				}
			}
		}
		return $result;
	}
	
	//验证授权
	private function auth($appid,$key)
	{
		$k = mdd5($appid);
		if( $k===$key )
		{
			return true;
		}
		return false;
	}
	
	//当天启动APP用户汇总
	private function applanuch($res)
	{
		$data = $this->getuserattr($res,$this->hellowd_ana_applanuch);
		
		$r = Db::name("ana_applanuch")->field("id")->where( ["date"=>$data["date"],"app_id"=>$data["app_id"],"dv_id"=>$data["dv_id"] ])->find();
		if( empty($r) )
		{			
			Db::name("ana_applanuch")->insert($data);
		}
        $this->appnewusers($res);	
	}
	
	//当天新增用户汇总
	private function appnewusers($res)
	{
		$data = $this->getuserattr($res,$this->hellowd_ana_appnewuser);
		$r = Db::name("ana_appnewuser")->field("id")->where( ["app_id"=>$data["app_id"],"dv_id"=>$data["dv_id"] ])->find();
		if( empty($r) )
		{			
			Db::name("ana_appnewuser")->insert($data);
		}
	}
	//更新用户时长
	private function updateusertime($res)
	{
		Db::name("ana_applanuch")->where( ["app_id"=>$res["app_id"],"date"=>$res["date"],"dv_id"=>$res["dv_id"] ])->setInc('time_length',$res["label"]);

		Db::name("ana_applanuch")->where( ["app_id"=>$res["app_id"],"date"=>$res["date"],"dv_id"=>$res["dv_id"] ])->setInc('num');
	}
	
	//用户属性是存在
	private function device_data($res)
	{
		
		$data = $this->getuserattr($res,$this->hellowd_ana_device);
		
		$r = Db::name("ana_device")->field("id")->where( ["device_id"=>$data["device_id"] ])->find();
		if( empty($r) )
		{			
			$id = Db::name("ana_device")->insertGetId($data);
		}else{
			$id = $r["id"];
		}
		return $id;
	}
	
	//用户行为分析
	public function user_behavior()
	{
		$data = input("post.");
		$out["code"] ="Authorization failure";
		$out["result"] =[];
		if(  !empty($data) )
		{			
			
			try{				
				if($this->checkSwitch($data["app_id"]))
				{
					$out["code"]="Switch off";
					echo json_encode($out);exit;
				}
				$time = time();
				$data["date"] =date("Y-m-d",$time);
				if( isset($data["device_id_"]) )
				{
					$data["device_id"] = $data["device_id_"];
				    unset($data["device_id_"]);
				}
                if( !$data["device_id"] )
				{
					return;
				}					
				//$this->test($data);
				Db::name("user_behavior")->insert($data);
				$out["code"]="success";			
 			} catch (\Exception $e) {
			 
		      echo json_encode($out);exit;
		   }
		}
		echo json_encode($out);exit;
	}
	
	//检查开关是否关闭
	private function checkSwitch($app_id)
	{
		if(!$app_id)
		{
			return true;
		}
		$tr = Db::name("adconfig")->field("id")->where("appid={$app_id} and name='Flow_Switch_Self' and val='0'")->find();
		if(empty($tr))
		{
			return false;
		}
		return true;
	}
	
	public function test($data)
	{
	  $basedir = dirname(__FILE__);
	  $file = $basedir.'/log1.txt';
	  
	  $content ="";
	  foreach($data as $key=>$vv)
	  {
		  $content.=$key."=".$vv;
	  }
	  file_put_contents($file, $content, FILE_APPEND);
	}
}
