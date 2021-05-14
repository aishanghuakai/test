<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Adlog extends Base
{
    
	public function index()
    {      
	   
	   $res = Db::name('admin_log')->order("operate_time desc")->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );	   
	 $data = $res->toarray();
	  $r= $data["data"];
	   if( !empty($r) )
	   {
		   foreach($r as $kk=>&$vv)
		   {			  
			   $vv["time"] = date( 'm月d号 H:i',strtotime($vv["operate_time"]) );
               $vv["operate_content"] = mb_substr($vv["operate_content"],0,15,'utf-8')."...";	   		   
		   }
		  
	   }
	   $spend = Db::name('system_assessment')->field('round(avg(val),2) as val')->where("type='spend'")->find();
	   $revenue = Db::name('system_assessment')->field('round(avg(val),2) as val')->where("type='revenue'")->find();
       $servce = $this->get_used_status();
	   return $this->fetch('index',["res"=>$r,"list"=>$res,"servce"=>$servce,"spend"=>$spend["val"],"revenue"=>$revenue["val"] ]);
    }
	
	public function qq(){
		$month =["01","02","03","04","05","06","07","08","09","10","11","12"];
		foreach($month as $m)
		{
			Db::name('system_assessment')->insert(["month"=>date("Y").".".$m,"type"=>"revenue"]);
		}
		exit("ok");
	}
	
	public function update_system_assessment_data($val="",$month,$type=""){
		if($type && $month)
		{
			Db::name('system_assessment')->where(["month"=>$month,"type"=>$type])->update(["val"=>$val]);
		}
		exit("ok");
	}
	
	public function get_system_assessment_data()
	{
		$spend = Db::name('system_assessment')->field("month,IFNULL(val,'') as val")->where("type='spend'")->select();
		$revenue = Db::name('system_assessment')->field("month,IFNULL(val,'') as val")->where("type='revenue'")->select();
		echo json_encode(["spend"=>$spend,"revenue"=>$revenue]);
	}
	
	public function test()
	{
		 echo $this->get_os();exit;
	}
	
	public function userlist()
	{
		
		 $res = Db::name('admin')->order("id asc")->select();
		 foreach($res as &$vv)
		 {
			 if( $vv["ad_role"]=="material" )
			 {
				 $vv["app_name"] ="暂无产品关联";
			 }else{
				 $apps = $this->getuserapps($vv["id"]);
				 $vv["app_name"] = implode(",",array_column($apps,"app_name") );
			 }
		 }
		 return $this->fetch('userlist',["res"=>$res ]);
	}
		
	 function getuserapps($id)
 {
	 $ids =$this->getuserlike($id);		
	 $where=" id in(".$ids.")";
	 $apps= Db::name("app")->field("app_name")->where($where)->select();
	 return $apps;
 }
	
	 function getuserlike($id)
	{
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".$id;
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
       return "0";		
	}
	
	function getsinaip( $ip )
	{
		 header("Content-type: text/html; charset=utf-8");
		 $t =@file_get_contents("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip);
		
		 $r = json_decode($t,true );
		 if( isset($r["country"]) && isset( $r["ret"] ) && $r["ret"]==1 )
		 {
			
			return $r["city"]?$r["city"]:$r["country"];
		 }
		 return "未知";
	}
	//获取服务器性能CPU、内存、硬盘等使用率
	function get_used_status(){
		
		  $fp = popen("top -bn 1 -i -c | grep Cpu | awk -F ',' '{print $1}'","r");//获取某一时刻系统cpu和内存使用情况
		  $rs = "";
		  while(!feof($fp)){
		   $rs .= fread($fp,1024);
		  }
		  pclose($fp);
		  $sys_info = explode(";",$rs);
         
		  $cpu_usage = str_replace(' us','',trim($sys_info[0],'%Cpu(s): '));  //百分比
		  
		  
		  /*硬盘使用率 begin*/
		  $fp = popen('df -lh | grep -E "^(/)"',"r");
		  $rs = fread($fp,1024);
		  pclose($fp);
		  $hd1 = explode(" ",$rs);
		  $rs = preg_replace("/\s{2,}/",' ',$rs);  //把多个空格换成 “_”
		  $hd = explode(" ",$rs);
		  $hd_avail = trim(isset($hd[3])?$hd[3]:0,'G'); //磁盘可用空间大小 单位G
		  $hd_usage = trim(isset($hd[4])?$hd[4]:0,'%'); //挂载点 百分比
		  $hd_usage_sys_avail = trim(isset($hd1[14])?$hd1[14]:0,'G'); //系统盘
		  $hd_usage_data_avail = trim(isset($hd1[28])?$hd1[28]:0,'G'); //数据盘
		  $hd_usage_sys = trim(isset($hd1[16])?$hd1[16]:0,'%'); //系统盘挂载点
		  $hd_usage_data = trim(isset($hd1[30])?$hd1[30]:0,'%'); //系统盘挂载点
		  /*硬盘使用率 end*/  
		  
		  //检测时间
		  //$fp = popen("date +\"%Y-%m-%d %H:%M\"","r");
		  //$rs = fread($fp,1024);
		  $fp = popen("ps -ef | grep apache | grep -v grep | wc -l","r");
		   $rs = fread($fp,1024);
		   pclose($fp);
		  $apache_work_num = trim($rs);
          return array('cpu_usage'=>trim($cpu_usage),"hd_usage_sys_avail"=>$hd_usage_sys_avail,"hd_usage_data_avail"=>$hd_usage_data_avail,'hd_avail'=>$hd_avail,'hd_usage'=>$hd_usage,"hd_usage_data"=>$hd_usage_data,'apache_work_num'=>$apache_work_num);
       }
		
}
