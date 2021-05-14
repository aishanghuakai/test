<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use app\admin\controller\Index;
class Anaevent extends Base
{
   
	
	private function getapp_user_str($appid)
	{
		$app_user =[];
		$res = Db::name("behavior_name")->where("app_id={$appid}")->order("short desc")->select();
		if( !empty($res) )
		{
			foreach( $res as $vv )
			{
				$app_user[$vv["short"]] =$vv["name"]; 
			}
		}
		return $app_user;
	}
	
	//数据概览
	public function index($appid="")
	{		
		if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
		setcache("select_app",$appid);
		$start = date("Y-m-d",strtotime("-6 day"));
		$end = date("Y-m-d",strtotime("-6 day"));
		$this->assign('countrys',admincountry());
		return $this->fetch('index',["start"=>$start,"end"=>$end]);
	}
	
	public function getretendata($appid,$start,$end,$filter,$day,$where)
	{
		$output=[];
		$where1="";
		if( !empty($filter) )
		{
			$b = implode("|",$filter);
			$where1 =" AND parameters regexp '{$b}'";
		}
		
		for( $i=0;$i<=$day;$i++ )
		{
			
			if( $i==0 )
			{
				$device_ids = $this->getcurentparam($appid,$start,$end,$where1,$where);
				$newdevice_ids = $this->currentnum($appid,$start,$end,$where);	
                $output[$i]["day"] ="新用户/参与用户";
			    $output[$i]["users"] = count($newdevice_ids)."/".count($device_ids);					
			}else{
				$device_ids = $this->getpassuser($start,$end,$appid,$i,$where1,$where);
				if( $i==1 )
				{
					 $output[$i]["users"] = count($device_ids);
				}else{
					$lastdevice_ids = $this->getpassuser($start,$end,$appid,$i-1,$where1,$where);
					$output[$i]["users"] =count($device_ids)-count($lastdevice_ids);
				}
				$output[$i]["day"] ="第".$i."天";
				$output[$i]["i"] = $i;
			   	
			}					
		}
		return $output;
	}
	
	private function getdiffdeviceid($array1, $array2)
	{
		$out=[];
		$arr = array_column($array2, 'device_id');
		if( !empty($array1) )
		{
			foreach( $array1 as $vv )
			{
				if( !in_array( $vv["device_id"],$arr ) )
				{
					$out[]=$vv;
				}
			}
		}		
		return $out;
	}
	
	public function download( $start,$end,$filter,$i,$country="all" )
	{
		$appid = getcache("select_app");		
		$where=" and 1=1";
		if( $country!="all" )
		{
			$where = " and country='{$country}'";
		}
		$filter = explode(",",$filter);
		
		if( !empty($filter) )
		{
			$b = implode("|",$filter);
			if( $b )
			{
				$where =" AND parameters regexp '{$b}'";
			}			
		}
		if( $i==1 )
		{
			$device_ids = $this->getpassuser($start,$end,$appid,$i,"",$where);
		}else{
			$device_ids_t = $this->getpassuser($start,$end,$appid,$i,"",$where);
			$lastdevice_ids = $this->getpassuser($start,$end,$appid,($i-1),"",$where);		
			$device_ids = $this->getdiffdeviceid($device_ids_t,$lastdevice_ids);
		}
		if( !empty($device_ids) )
		{
			$time =time();
			header('Content-Type: application/vnd.ms-excel;charset=gbk');
			header('Content-Disposition: attachment;filename="用户路径导出.csv"');
			header('Cache-Control: max-age=0');
			$fp = fopen('php://output', 'a');
			$head = array(
				0 => '时间',
				1 => '用户ID',
				2=>'登录天数',
				3=>'用户行为'
			);
			foreach ($head as $i => $v){
				$head[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($fp, $head);
			 foreach( $device_ids as $vv ){
					$res = Db::name("user_behavior")->field('add_time,device_id,day_count,parameters')->where( "app_id={$appid} and date>='{$start}' and device_id='{$vv['device_id']}'" )->order("add_time asc")->select();
					if( !empty($res) )
					{
						foreach( $res as $row )
						{
							fputcsv($fp, $row);
						}
					}
			}	 
		}
	}
	
	private function getcurentparam($appid,$start,$end,$where1,$where)
	{
		
		$sql="SELECT device_id from 
( SELECT DISTINCT device_id from hellowd_user_behavior WHERE app_id={$appid} and date>='{$start}' and date<='{$end}' {$where1} {$where} GROUP BY device_id  ) c WHERE
not EXISTS ( SELECT id from hellowd_user_behavior WHERE date<'{$start}'  and device_id=c.device_id )";
 return Db::query($sql);
	}
	
	private function getpassuser($start,$end,$appid,$i,$where1,$where)
	{
		$stimestamp = strtotime($end);
		$date = date('Y-m-d', $stimestamp+(86400*$i));
		$sql="SELECT device_id from ( 
SELECT device_id from 
( SELECT DISTINCT device_id from hellowd_user_behavior WHERE app_id={$appid} and date>='{$start}' and date<='{$end}' {$where1} {$where} GROUP BY device_id  ) c WHERE
not EXISTS ( SELECT id from hellowd_user_behavior WHERE date<'{$start}'  and device_id=c.device_id ) ) d WHERE not EXISTS 
( SELECT id from hellowd_user_behavior WHERE date>='{$date}'  and device_id=d.device_id )";
      return Db::query($sql);
	}
	
	public function json_data($date=[],$keyword="",$filter=[],$day="3",$country="all",$selecttype="2")
	{
		$appid = getcache("select_app");
		if(  empty($date) )
		{
			$start = date("Y-m-d",strtotime("-6 day"));
		    $end = date("Y-m-d",strtotime("-6 day"));
		}else{
			$start = $date[0];
		    $end = $date[1];
		}		
		$field="";
		$sumfield ="";
		$where="";
		if( $country!="all" )
		{
			$where = " and country='{$country}'";
		}
		$schema =[ ["label"=>"天数","value"=>"day"],["label"=>"用户","value"=>"users"] ];
		if( $selecttype=="1" )
		{
			$schema =[ ["label"=>"天数","value"=>"day"],["label"=>"流失用户","value"=>"users"],["label"=>"操作","value"=>"category"] ];
			$output = $this->getretendata($appid,$start,$end,$filter,$day,$where);
			echo json_encode( ["schema"=>$schema,"output"=>$output] );exit;
		}
		if( !empty($filter) )
		{			
			 foreach( $filter as $vv )
			 {
				$length = strlen($vv);
				$sumfield.=$vv."+";
				$field.="sum((LENGTH(parameters)-length(replace(parameters,'{$vv}','')))/{$length}) as {$vv},";
			 }
			 $field = rtrim($field,",");
             $sumfield = rtrim($sumfield,"+");
            $schema =[["label"=>"天数","value"=>"day"],["label"=>"用户","value"=>"users"],["label"=>"计数","value"=>"num"],["label"=>"人均计数","value"=>"avgnum"] ]; 			 
		}
		$stimestamp = strtotime($end);
		$output=[];
		for( $i=0;$i<$day;$i++ )
		{
			$date = date('Y-m-d', $stimestamp+(86400*$i));
			if( $i==0 )
			{
				$device_ids = $this->currentnum($appid,$start,$end,$where);				
			}else{
				$device_ids = $this->getdeviceid($appid,$start,$end,$date,$where);
			}
			$output[$i]["day"] ="第".($i+1)."天";
			$output[$i]["users"] = count($device_ids);
			if( $field!="" )
			{
				$output[$i]["num"] = $this->getSumamry($device_ids,$date,$field,$sumfield);
				$output[$i]["avgnum"] = $output[$i]["users"]>0?round( $output[$i]["num"]/$output[$i]["users"],2):"0";
			}
		}
        echo json_encode( ["schema"=>$schema,"output"=>$output] );exit;		
	}
	
	private function getSumamry($device_ids,$date,$field,$sumfield)
	{

		$sum="0";
		if( !empty($device_ids) && $field!="" )
		{
			$sumfield = "sum({$sumfield}) as num";
			foreach( $device_ids as $vv )
			{
			   $sql="select {$sumfield} from (SELECT {$field} from hellowd_user_behavior WHERE device_id='{$vv['device_id']}' and date='{$date}') as c";

			   $result = Db::query($sql);
			   $num = isset($result[0]["num"])?$result[0]["num"]:"0";
			   $sum+= $num;
			}
		}
		return $sum;
	}
	
	private function getdeviceid($appid,$start,$end,$date,$where)
	{
		$sql="SELECT device_id from (
		SELECT device_id from ( SELECT DISTINCT device_id from hellowd_user_behavior WHERE app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} GROUP BY device_id  ) c WHERE
not EXISTS ( SELECT id from hellowd_user_behavior WHERE date<'{$start}'  and device_id=c.device_id ) ) b WHERE EXISTS 
(SELECT id from hellowd_user_behavior WHERE date='{$date}'  and device_id=b.device_id)";
      return Db::query($sql);
	}
	private function currentnum($appid,$start,$end,$where)
	{
		$sql="SELECT device_id from ( SELECT DISTINCT device_id from hellowd_user_behavior WHERE app_id={$appid} and date>='{$start}' and date<='{$end}' {$where} GROUP BY device_id  ) c WHERE
not EXISTS ( SELECT id from hellowd_user_behavior WHERE date<'{$start}'  and device_id=c.device_id )";
        return Db::query($sql);
	}
	
	//用户路径
	public function user_behavior($appid="",$start="",$end="",$country="")
	{
		if( $appid=="" )
		{
		   $appid = getcache("select_app");	
		}
		
		if( !$appid || !preg_match("/^\d+$/",$appid) )
		{
			 return redirect('/admin_index/select_app');exit;
		}
		$where = "app_id={$appid}";
		if( $start!="" && $end!="" )
		{
			
			$where.= " and date>='{$start}' and date<='{$end}'"; 
		}else{
			$start = date("Y-m-d",strtotime("-30 day"));
			$end = date("Y-m-d",time());
		}
		if( $country!="all" && $country )
		{
			$where .= " and country='{$country}'";
		}
	    setcache("select_app",$appid);
		$singe_num = Db::name('user_behavior')->where($where)->group('device_id')->count();       
		$list =Db::name('user_behavior')
                 ->where($where)	
				 ->order ( "id desc" )		 
				 ->paginate(30,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'path'=>'javascript:AjaxPage([PAGE],1);',
								 'query'=>[  ]
								] );
        $data = $list->toarray();
        $result = $data["data"];
		$res = $this->getapp_user_str($appid);
        foreach($result as &$v)
        {
			
			$d  =explode(",",$v["parameters"]);
			foreach($d as &$vvv)
			{
				$vvv=rtrim($vvv,"_");
			}
			$v["parameters"] = implode("_(截断)_",$d);
			$v["parameters"] = $this->behavior_replace($res,$v["parameters"]);
		}		
	    $this->assign('list',$list);
		$this->assign('res',$res);
		$this->assign('result',$result);
		$this->assign('countrys',admincountry());
		$this->assign('country',$country);
		$this->assign("start",$start);
		$this->assign("singe_num",$singe_num);
		$this->assign("end",$end);
		$switch =false;
		$tr = Db::name("adconfig")->field("id")->where("appid={$appid} and name='Flow_Switch_Self' and val='1'")->find();
		if(!empty($tr))
		{
			$switch =true;
		}
		$this->assign("switch",$switch);
		$this->assign("appid",$appid);
		return $this->fetch('user_behavior');
	}
	
	public function setSwitch($appid,$val="")
	{
		Db::name("adconfig")->where("appid={$appid} and name='Flow_Switch_Self'")->update( ["val"=>$val] );
		exit("ok");
	}
	
	 public function ajaxListAction($page="1",$join_num="0",$type="1",$appid="",$start="",$end="",$open_day="all",$open_count="all",$exit_num="0",$user_path=[])
	 {
		$table="hellowd_user_behavior";
		$where = "app_id={$appid}";
		$o_where = $where;
		if( $start!="" && $end!="" )
		{			
			$where.= " and date>='{$start}' and date<='{$end}'"; 
			$o_where = $where;
		}else{
			$start="2019-01-01";
			$end = date("Y-m-d",time());
		}
		if( $open_day!="" )
		{
			if( !preg_match("/all/",$open_day))
			{
				$day_sql ="SELECT bb.device_id,count(*) as num from ( SELECT device_id,date from hellowd_user_behavior WHERE app_id={$appid} GROUP BY date,device_id ) as bb GROUP BY bb.device_id HAVING num=$open_day";
				$dev = Db::query($day_sql);
				if( !empty($dev) )
				{
					
					$w_dev = "";
					foreach( $dev as $vv )
					{
					   $w_dev.="'{$vv['device_id']}',";
					}
					$w_dev.="0";
					
					$where.= " and device_id in($w_dev)";
				}else{
					$where.= " and day_count=-1";
				}
			}
		}
		if( $open_count!="" )
		{
			if( !preg_match("/all/",$open_count))
			{
				$where.= " and open_count in($open_count)";
			}
		}				
		if( $exit_num>0 )
		{
			$num = $exit_num+1;
			$field="id,device_id,app_id,add_time,day_count,open_count,date,substring_index(parameters,'_',-{$num}) as parameters";
			$subQuery = Db::name('user_behavior')
							->field($field)
							->where($where)
							->buildSql();
			$table=$subQuery." a";	
            $where = $o_where;			
		}
		if( !empty($user_path) )
		{
			$sd ="";
			foreach( $user_path as $uu )
			{
				$sd.=$uu."\\\|.*_";
			}
			$where.= " and parameters REGEXP '{$sd}'";
		}
		if( $join_num>0 )
		{
			$where.=" and (LENGTH(parameters)-LENGTH(REPLACE(parameters,'|','')))<={$join_num}";
		}

		$singe_num = Db::table($table)->where($where)->group('device_id')->count();
		$result=[];
		$list=[];
		$res = $this->getapp_user_str($appid);
		$total_num = Db::table($table)->where($where)->count();
		if( $type=="2" ){
			if( !empty($res) )
			{
				foreach( $res as $r_k=>$r_v )
				{
					$csd =$r_k."|";				
					$r_where = $where." and  parameters LIKE '%{$csd}%'";
					$ttt_sub = Db::table($table)->field("device_id")->where($r_where)->group("device_id")->buildSql();
					$c_num =Db::table($ttt_sub." b")->count();
					$c_total_num = Db::table($table)->where($r_where)->count();
					$result[] = ["name"=>$r_v,"num"=>$c_num,"totalnum"=>$c_total_num];
				}
			}
		}else{
             $list =Db::table($table)
		         ->field("*")
                 ->where($where)	
				 ->order ( "id desc" )			 
				 ->paginate(30,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'path'=>"javascript:AjaxPage([PAGE],{$type});",
								 'query'=>[  ]
								] );			
			$data = $list->toarray();
			$result = $data["data"];
			foreach($result as &$v)
			{
				
				$d  =explode(",",$v["parameters"]);
				foreach($d as &$vvv)
				{
					$vvv=rtrim($vvv,"_");
				}
				$v["parameters"] = implode("_(截断)_",$d);
				$v["parameters"] = $this->behavior_replace($res,$v["parameters"],$user_path);
			}
		}	
        $this->assign('list',$list);
		$this->assign('total_num',$total_num);
		$this->assign('result',$result);
		$this->assign("start",$start);
		$this->assign("end",$end);
		$this->assign("singe_num",$singe_num);
		$this->assign("appid",$appid);
		if( $type=="2" )
		{
			return $this->fetch('user_behavior_event_ajax');
		}
		return $this->fetch('user_behavior_ajax');
	 }
	 
	 public function new_download($appid="",$start="",$end="",$country="all")
	 {
		 ini_set('memory_limit','1024M');
			set_time_limit(0);
			 
			$mysqli = mysqli_connect("127.0.0.1","thehotgames","week2e13&hellowd");
             mysqli_set_charset($mysqli,"utf8mb4");
             mysqli_select_db($mysqli,"promote_data");			 
			if (mysqli_connect_errno()){
				printf("Connect failed: %s\n", mysqli_connect_error());
				exit();
			}
			$where = "app_id={$appid} and date>='{$start}' and date<='{$end}'";
			if( $country!="all" && $country )
			{
				$where .= " and country='{$country}'";
			}			
	        $sql="select add_time,device_id,day_count,parameters from hellowd_user_behavior where {$where} order by id desc limit 1000000";
			 
			$res=$mysqli->query($sql);
			$name = str_replace(" ","",getapp_name($appid));
			header('Content-Type: application/vnd.ms-excel;charset=gbk');
			header('Content-Disposition: attachment;filename="用户路径导出.csv"');
			header('Cache-Control: max-age=0');
			$fp = fopen('php://output', 'a');
			$head = array(
				0 => '时间',
				1 => '用户ID',
				2=>'登录天数',
				3=>'用户行为'
			);
			foreach ($head as $i => $v){
				$head[$i] = iconv('utf-8', 'gbk', $v);
			}
			fputcsv($fp, $head);
			if($res){
				while($row=mysqli_fetch_array($res,MYSQLI_ASSOC)){
					fputcsv($fp, $row);
				}
			}else{
				die("fetch data failed!");
			}			 
			//释放游标内存
			mysqli_free_result($res); 
			//关闭数据库连接
			mysqli_close($mysqli);
	 }
	
	public function user_behavior_download($appid="",$start="",$end="")
	{
		set_time_limit(0);
		$where = "app_id={$appid} and date>='{$start}' and date<='{$end}'";
		$list =Db::name('user_behavior')
                 ->where($where)	
				 ->order ( "id asc" )
                 ->limit(10000)				 
				 ->select();
		 $xlsName  ="用户行为数据";
		 $tit = getapp_name($appid)."用户行为数据";
		 $xlsCell  = array(
			array('date','时间'),
			array('deviceid','用户ID'),
			array('day','登录天数'),
			array('eventValue','用户行为')
		);
        $xlsData =[];
		$res = $this->getapp_user_str($appid);
		if( !empty($list) )
		{
			foreach($list as $key=>&$vv)
			{
				
				$d  =explode(",",$vv["parameters"]);
				foreach($d as &$vvv)
				{
					$vvv=rtrim($vvv,"_");
				}
			    $vv["parameters"] = implode("_",$d);
				$arr =  [ 
				          "date"=>$vv["add_time"],
						  "deviceid"=>"{$vv['device_id']}",
						  "day"=>"{$vv['day_count']}",
						  "eventValue"=>$this->behavior_replace($res,$vv["parameters"],[])
						];
				$xlsData[$key] = $arr;		
			}
		}
		$Index = new Index(request());
        $Index->exportExcel($xlsName,$xlsCell,$xlsData,$xlsName,$tit);	
	}
	
	//用户行为路径替换
	private function behavior_replace($res,&$str,$user_path=[])
	{		
		if( empty($res) )
		{
			return $str;
		}
		if( !empty($user_path) )
		{
			foreach( $user_path as $uv )
			{
				if( preg_match("/{$uv}\|/",$str) )
				{
					$str = str_replace($uv."|","<span style='color:red;'>{$uv}|</span>",$str);
				}
			}
		}
		foreach( $res as $k=>$vv )
		{
			if( preg_match("/{$k}\|/",$str) )
				{
				  $str = str_replace($k."|",$vv."|",$str);
				}
				$str = str_replace("_","--->",$str);
				
		}
		
		return $str;
	}
	
	function getTree($array, $pid =0, $level = 0){ 
	    $list = []; 
		foreach ($array as $key => $value){ 
		
			if ($value['parent_id'] == $pid){  
				$value['level'] = $level;
				$value["child"] = $this->getTree($array, $value['id'], $level+1);		
				$list[] = $value; 
				unset($array[$key]);
		 } } 
		return $list; 
	}
	
	//过滤列表
	public function fiterlist()
	{
		return $this->fetch('fiterlist');
	}
	
	//过滤事件
	public function fiter()
	{
		return $this->fetch('fiter');
	}
}
