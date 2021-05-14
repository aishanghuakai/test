<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use \app\admin\controller\Index;
use \app\api\controller\Gastatic;

class Consu  extends Base
{
    public function index($app_id="52",$id="")
    {      
	   
		$html="";
		
		if( $id!="")
		{
			$res = Db::name("roi_model")->find($id);
			$data = json_decode($res["roi_content"],true);
			 foreach( $data as $v )
			 {
				 $html.="<tr><td>".$v['date']."</td><td><input type=\"text\" class=\"form-control lc\" value=\"{$v['lc']}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">{$v['revenu']}</div></td><td><div class=\"roi_rate\"><span class=\"roi\">{$v['roi_rate']}</span>%</div></td></tr>";
			 }
		}else{
			for($i=0;$i<41;$i++ )
			{
				if($i==0){
					$html.="<tr><td>".$i."</td><td><input type=\"text\" class=\"form-control lc\" value=\"100\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">0.09</div></td><td><div class=\"roi_rate\"><span class=\"roi\">32</span>%</div></td></tr>";
				}else{
					$html.="<tr><td>".$i."</td><td><input type=\"text\" class=\"form-control lc\" value=\"0\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
				}
			}
		}
	    $app_s = Db::name("app")->field("id,app_name")->select();
		$saves = Db::name("roi_model")->where("appid={$app_id}")->order("id desc")->select();
		$r = Db::name("app")->field("id,app_name")->find($app_id);
		$app_name = $r["app_name"]."_".date("Y-m-d");
	    return view('index',["html"=>$html,"app_s"=>$app_s,"app_id"=>$app_id,"saves"=>$saves,"id"=>$id,"app_name"=>$app_name ]);
    }
	
	
	public function history($app_id="",$id="")
	{
		
		 if( empty($res) )
		 {
			 
			 return redirect('/admin_consu/index');exit;
		 }
		 $data = json_decode($res["roi_content"],true);
		 $app_s = Db::name("app")->field("id,app_name")->select();
		 $html="";
		 foreach( $data as $v )
		 {
			 $html.="<tr><td>".$v['date']."</td><td><input type=\"text\" class=\"form-control lc\" value=\"{$v['lc']}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">{$v['revenu']}</div></td><td><div class=\"roi_rate\"><span class=\"roi\">{$v['roi_rate']}</span>%</div></td></tr>";
		 }
		 return view('history',["html"=>$html,"app_s"=>$app_s,"res"=>$res ]);
	}
	public function history1($appid="",$id="")
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
	   $html="";
	   $res = Db::name("roi_model")->find($id);
		$data = json_decode($res["roi_content"],true);
		 foreach( $data as $v )
		 {
			 $html.="<tr><td>".$v['date']."</td><td><input type=\"text\" class=\"form-control lc\" value=\"{$v['lc']}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">{$v['revenu']}</div></td><td><div class=\"roi_rate\"><span class=\"roi\">{$v['roi_rate']}</span>%</div></td></tr>";
		 }
		 if( count($data)<=60 )
		 {
			  
			  for($i=41;$i<61;$i++ )
			{
				
					$html.="<tr><td>".$i."</td><td><input type=\"text\" class=\"form-control lc\" value=\"0\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
				
			}
		 }
		 $r = Db::name("app")->field("id,app_name")->find($appid);
		 return view('history1',["html"=>$html,"res"=>$res,"app_id"=>$appid,"id"=>$id,"app_name"=>$r["app_name"] ]);
	}
	
	public function ltvhistory($appid="",$id="")
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
	   $html="";
	   $res = Db::name("roi_model")->find($id);
	  
		$data = json_decode($res["roi_content"],true);
		 foreach( $data as $v )
		 {
			 $current_date=0;
			 if( $res["start_date"] )
			 {
				  $stimestamp = strtotime($res["start_date"]);
				  $current_date =date('Y-m-d',$stimestamp+(86400*$v['date']));
			 }
			 
			 
			 $users = isset($v["users"])?$v["users"]:0;
			 $time = isset($v["time"])?$v["time"]:$current_date;
			 $ecpm = isset($v["ecpm"])?$v["ecpm"]:0;
			 $html.="<tr><td>".$v['date']."</td><td>".$time."</td><td><input type=\"text\" value=\"{$v['arpdau']}\" class=\"form-control arpdau\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"></td><td><input type=\"text\" class=\"form-control lc\" value=\"{$v['lc']}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"users\">{$users}</div></td><td><div class=\"ecpm\">{$ecpm}</div></td><td><div class=\"todayrevenu\">{$v['todayrevenu']}</div></td><td><div class=\"revenu\">{$v['revenu']}</div></td><td><div class=\"roi_rate\"><span class=\"roi\">{$v['roi_rate']}</span>%</div></td></tr>";
		 }
		 
		 $r = Db::name("app")->field("id,app_name")->find($appid);
		 return view('ltvhistory',["html"=>$html,"res"=>$res,"app_id"=>$appid,"id"=>$id,"app_name"=>$r["app_name"] ]);
	}
	
	public function save($arpdau="",$cpi="",$model="",$data="",$save_name="",$appid="",$id="",$type="1",$country="")
	{
		
		$res["arpdau"] = $arpdau;
		$res["cpi"] = $cpi;
		$res["type"] = $type;
		$res["appid"] = $appid;
		$res["roi_content"] =json_encode($data);
		$res["save_name"] = $save_name;
		$res["save_user"] = $this->_adminname;
		$res["country"] = $country;
		if( $id!="" && $model=='edit' )
		{
			Db::name("roi_model")->where("id",$id)->update( ["arpdau"=>$arpdau,"cpi"=>$cpi,"roi_content"=>$res["roi_content"],"save_user"=>$res["save_user"],"save_date"=>date("Y-m-d H:i:s") ] );
		}else{
			$res["start_date"] =date("Y-m-d H:i:s");
			Db::name("roi_model")->insert($res);
		}
		
		exit("ok");
	}
	
	public function save_name($id="",$name="")
	{
		Db::name("roi_model")->where("id",$id)->update( ["save_name"=>$name ] );
		exit("ok");
	}
	
	public function applist($app_name="")
	{
		$ids  = $this->getmylikedata();
		$where="1=1 and id in(".$ids.")";
		if( $app_name!="" )
		{
			$where.=" and app_name like '%{$app_name}%'";
		}
		$app_s = Db::name("app")->field("id,app_name,package_name,updateuser,updatetime")->where($where)->order("FIELD(id,{$ids})")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "app_name"=>$app_name ]
								] );
		$data = $app_s->toarray();	
       		
		return view("list",["app_s"=>$app_s,"app_name"=>$app_name,"data"=>$data["data"] ]);
	}
	
	public function s_list()
	{
		$ids  = $this->getmylikedata();
		$where="1=1 and id in(".$ids.")";
		$r =Db::name("app")->field("id,app_name,package_name,platform,updateuser,updatetime,app_base_id")->where($where)->order("FIELD(id,{$ids})")->select();
		if( !empty($r) )
		{
			foreach($r as &$vvv)
			{
				if(  $vvv["id"]>154 )
				{
					if( $vvv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vvv["app_base_id"])->find();
						$vvv["app_name"] = $row["name"].' - '.$vvv["platform"];
					}
				}
			}
		}
		$userinfo = getuserinfo();
		$all_where="1=1";
		if($userinfo["id"]==95 || $userinfo["id"]==97)
		{
			exit("您没有权限访问,请联系系统管理员");
		}
		if( !in_array( $userinfo["ad_role"],["super","advertiser","material","financer"] ) )
		{
			if( !$userinfo['allow_applist'] )
			{
				exit("您没有权限访问,请联系系统管理员");
			}
			$all_where=" id in({$userinfo['allow_applist']})";
		}
		$all_where.=" and id not in(".$ids.")";
		$app_s = Db::name("app")->field("id,app_name,package_name,platform,updateuser,updatetime,app_base_id")->where($all_where)->order("id desc")->select();
		if( !empty($app_s) )
		{
			foreach($app_s as &$vv)
			{
				if(  $vv["id"]>154 )
				{
					if( $vv["app_base_id"] )
					{
						$row = Db::name("app_base")->where("id",$vv["app_base_id"])->find();
						$vv["app_name"] = $row["name"].' - '.$vv["platform"];
					}
				}
			}
		}
		return view("s_list",["app_s"=>$app_s,"r"=>$r ]);
	}
	public function mylike($data)  
	{
		
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".Session::get('admin_userid');
		
		cache($key,implode(",",$data));
		admin_log("设置了应用喜爱");
		exit("ok");
	}
	
	function getmylikedata()
	{
		$options = [
		'type'   => 'File',
		'expire' => 0,
		'host'       => '127.0.0.1',
	    ];
		cache($options);
		$key = "mylike1".Session::get('admin_userid');
		$res =cache($key);
		//cache($key, NULL);
        if( !empty($res) )
		{
			return $res;
		}
       return "0";		
	}
	
	public function historylist($appid="")
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
		$userid = Session::get('admin_userid');
		if( $userid==50 )
	   {
		   $this->error('您暂无权限查看',"/admin_index/main");exit;
	   }
		$saves = Db::name("roi_model")->where("appid={$appid}")->order("id desc")->select();
		return view('historylist',["userid"=>$userid,"app_id"=>$appid,"saves"=>$saves]);
	}
	public function index1($appid="52",$id="")
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
		$html="";
		
		if( $id!="")
		{
			$res = Db::name("roi_model")->find($id);
			$data = json_decode($res["roi_content"],true);
			 foreach( $data as $v )
			 {
				 $html.="<tr><td>".$v['date']."</td><td><input type=\"text\" class=\"form-control lc\" value=\"{$v['lc']}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">{$v['revenu']}</div></td><td><div class=\"roi_rate\"><span class=\"roi\">{$v['roi_rate']}</span>%</div></td></tr>";
			 }
		}else{
			for($i=0;$i<61;$i++ )
			{
				if($i==0){
					$html.="<tr><td>".$i."</td><td><input type=\"text\" class=\"form-control lc\" value=\"100\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">0.09</div></td><td><div class=\"roi_rate\"><span class=\"roi\">32</span>%</div></td></tr>";
				}else{
					$html.="<tr><td>".$i."</td><td><input type=\"text\" class=\"form-control lc\" value=\"0\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
				}
			}
		}	   
		$saves = Db::name("roi_model")->where("appid={$appid}")->order("id desc")->select();
		$r = Db::name("app")->field("id,app_name")->find($appid);
		$app_name = $r["app_name"]."_".date("Y-m-d");
	    return view('index1',["html"=>$html,"app_id"=>$appid,"saves"=>$saves,"id"=>$id,"app_name"=>$app_name ]);
    }
	
	public function add_reten($date="",$appid="")
	{
		$n = new Gastatic();
		if(  $appid=="" )
		{
			exit("fail");
		}

		$out_data =[];
		$res = $n->getaccess_token($appid);
		$start="2019-06-01";
		$end="2019-06-15";
		$dates = getDateFromRange($start, $end);
		foreach( $dates as $vv )
		{
			for($i=1;$i<16;$i++ )
			{
				$reten = $n->getdayretention($res,$vv,$i);
				$r = Db::name("all_reten")->where(["app_id"=>$appid,"date"=>$vv,"reten_day"=>$i,"country"=>"all"])->find();
				if( empty($r) )
				{
					$out_data= [ "date"=>$vv,"reten_day"=>$i,"country"=>"all","val"=>$reten,"app_id"=>$appid ];
					Db::name("all_reten")->insert($out_data);
				}else{
					Db::name("all_reten")->where(["id"=>$r["id"]])->update( ["val"=>$reten] );
				}
			}
		}		
		echo "ok";
	}
	
	//获取某天留存
	private function getdaynumreten($appid,$date,$num,$country)
	{
		if( $country=="all" )
		{
			$r = Db::name("all_reten")->where(["app_id"=>$appid,"date"=>$date,"reten_day"=>$num,"country"=>$country])->find();
			if( empty($r) )
			{
				return 0;
			}
			return $r["val"]?$r["val"]:0;
		}else{
			
			$current_time= strtotime("-2 day");
			$stimestamp = strtotime($date);
			$reten_timestamp =$stimestamp+(86400*$num);
			if( $reten_timestamp>=$current_time )
			{
				return 0;
			}
			
			$r = Db::name("retention")->where(["app_id"=>$appid,"date"=>$date,"country"=>$country])->find();
			if( isset($r["retention_".$num]) && $r["retention_".$num] )
			{
				return $r["retention_".$num]*100;
			}else{
				
				
				$retention_7 = $r["retention_7"];
				$reten_rate =array(
				  "4"=>"0.3",
				  "5"=>"0.5",
				  "6"=>"0.7"
				);
                if( $num>3 && $num<7 )
				{
					$retention_3 = $r["retention_3"];
					
					return ($retention_3-(($retention_3-$retention_7)*$reten_rate[$num]))*100;
				}else if( $num>7 && $num<29 )
				{
					$retention_28 = $r["retention_28"];
					
					$reten_push = round(($retention_7-$retention_28)/21,4);
					return 	($retention_7-(($num-7)*$reten_push))*100;			
				}
                return 0;				
			}
		}
	}
	
	public function ltv($appid="",$id="",$type="",$start_date="",$end_date="",$country="all")
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
		$html="";
		if( $start_date=="" || $end_date=="" )
		{
			 $start_date= date("Y-m-d",strtotime("-7 day"));
			 $end_date= date("Y-m-d",strtotime("-2 day"));
		}      
		$end = date("Y-m-d",strtotime("-2 day"));
		$index = new Index( request() );
		$arpdau_data = $this->getarpdau($index,$appid,$start_date,$end,$country);
		$stimestamp = strtotime($start_date);
		//新增
        $new_users  =$index->getnew_users($appid,$start_date,$end_date,$country);
		//$n = new Gastatic();
		//$res = $n->getaccess_token($appid);
		for($i=0;$i<91;$i++ )
		{
			$arpdau = isset($arpdau_data[$i]["arpdau"])?$arpdau_data[$i]["arpdau"]:"0.00";
			$ecpm = isset($arpdau_data[$i]["ecpm"])?$arpdau_data[$i]["ecpm"]:"0.00";
			$current_date =date('Y-m-d',$stimestamp+(86400*$i));
		    $reten =$this->getdaynumreten($appid,$start_date,$i,$country);//$index->getreten($appid,$current_date,$current_date,$country);//$n->getdayretention($res,$date,$i);
			if($i==0){
				$html.="<tr><td>".$i."</td><td>".$current_date."</td><td><input type=\"text\" value=\"{$arpdau}\" class=\"form-control arpdau\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"></td><td><input type=\"text\" class=\"form-control lc\" value=\"100\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"users\">0</div></td><td><div class=\"ecpm\">{$ecpm}</div></td><td><div class=\"todayrevenu\">0.00</div></td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
			}else{
				$html.="<tr><td>".$i."</td><td>".$current_date."</td><td><input type=\"text\" value=\"{$arpdau}\" class=\"form-control arpdau\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"></td><td><input type=\"text\" class=\"form-control lc\" value=\"{$reten}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"users\">0</div></td><td><div class=\"ecpm\">{$ecpm}</div></td><td><div class=\"todayrevenu\">0.00</div></td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
			}
			
		}       
        $spend_data  =$index->getspendtotal($appid,$start_date,$end_date,"all",$country);
        $spend =$spend_data["spend"];
		$ad_cpi =$spend_data["cpi"]; 
       	$nature_num = ($new_users-$spend_data["installs"])<0?0:$new_users-$spend_data["installs"];
        //自然占比
        $nature_rat = $new_users<=0?"0":number_format($nature_num*100/$new_users,0);
        $avgcpi = $new_users<=0?"0.00":number_format($spend/$new_users,2);		
		$saves = Db::name("roi_model")->where("appid={$appid}")->order("id desc")->select();
		$r = Db::name("app")->field("id,app_name")->find($appid);
		$app_name = $r["app_name"]."_".date("Y-m-d");		
		$countrys = admincountry();
		$this->assign("country_name",$countrys[$country]);
	    return view('ltv',["html"=>$html,"ad_cpi"=>$ad_cpi,"start_date"=>$start_date,"end_date"=>$end_date,"avgcpi"=>$avgcpi,"nature_num"=>$nature_num,"nature_rat"=>$nature_rat,"country"=>$country,"app_id"=>$appid,"saves"=>$saves,"id"=>$id,"spend"=>$spend,"new_users"=>$new_users,"app_name"=>$app_name,"countrys"=>$countrys ]);
    }
	
	public function tenjin($appid="",$start_date="",$end_date="",$country="all",$channel_id="all")
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
		$html="";
		if( $start_date=="" || $end_date=="" )
		{
			 $start_date= date("Y-m-d",strtotime("-7 day"));
			 $end_date= date("Y-m-d",strtotime("-7 day"));
		}
        $channels = $this->getAdNetWork($appid);
		$stimestamp = strtotime($start_date);
		$dayoneData = $this->getTenjinReven($appid,$start_date,$country,$channel_id,0);
		$new_users =$dayoneData["reten_users"];
		$platform = $this->rleaChannel($channel_id);
		if( $platform!="" )
		{
			$spend = $this->getGbSpend($appid,$start_date,$end_date,$platform,$country);
		}else{
			$spendData = $this->getTenjinSpend($appid,$start_date,$country,$channel_id);
			$spend = $spendData["spend"];
		}		
		for($i=0;$i<91;$i++ )
		{
			$DayData = $this->getTenjinReven($appid,$start_date,$country,$channel_id,$i);
			$arpdau =$DayData["ARPPU"];
			$ecpm ="0";
			$reten_users = $DayData["reten_users"];
			$current_date =date('Y-m-d',$stimestamp+(86400*$i));
			$reten = $new_users<=0?0:round($reten_users*100/$new_users,2);	
			if($i==0){
				$html.="<tr><td>".$i."</td><td>".$current_date."</td><td><input type=\"text\" value=\"{$arpdau}\" class=\"form-control arpdau\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"></td><td><input type=\"text\" class=\"form-control lc\" value=\"100\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"users\">{$reten_users}</div></td><td><div class=\"ecpm\">{$ecpm}</div></td><td><div class=\"todayrevenu\">0.00</div></td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
			}else{
				$html.="<tr><td>".$i."</td><td>".$current_date."</td><td><input type=\"text\" value=\"{$arpdau}\" class=\"form-control arpdau\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"></td><td><input type=\"text\" class=\"form-control lc\" value=\"{$reten}\" style=\"width:60px;height:24px;display:inherit;text-align:center;\"> %</td><td><div class=\"users\">{$reten_users}</div></td><td><div class=\"ecpm\">{$ecpm}</div></td><td><div class=\"todayrevenu\">0.00</div></td><td><div class=\"revenu\">0.00</div></td><td><div class=\"roi_rate\"><span class=\"roi\">0</span>%</div></td></tr>";
			}			
		}
		$nature = $this->getTenjinReven($appid,$start_date,$country,"0",0);
        $avgcpi = $new_users<=0?"0.00":number_format($spend/$new_users,2);       
    	$nature_num=$nature["reten_users"];
		$nature_rat=$new_users<=0?"0":number_format($nature_num*100/$new_users,0);
		$installs =($new_users-$nature_num)<0?0:$new_users-$nature_num;
		if( $channel_id!="all" )
		{
			$installs = $new_users;
		}
		$ad_cpi=$installs<=0?"0.00":number_format($spend/$installs,2);
		$saves = Db::name("roi_model")->where("appid={$appid}")->order("id desc")->select();
		$r = Db::name("app")->field("id,app_name")->find($appid);
		$app_name = $r["app_name"]."_".date("Y-m-d");		
		$countrys = admincountry();
		
		if($channel_id=="all")
		{
			$channel_name="全部渠道";
		}else{
			$c = Db::name("tenjin_adnetwork")->field("name")->where(["adnetwork_id"=>$channel_id ])->find();
			$channel_name = $c["name"];
		}
		$this->assign("country_name",$country=="all"?"全部国家":$countrys[$country]);
		$this->assign("channel_name",$channel_name);
	    return view('tenjin',["html"=>$html,"channels"=>$channels,"channel_id"=>$channel_id,"ad_cpi"=>$ad_cpi,"start_date"=>$start_date,"end_date"=>$end_date,"avgcpi"=>$avgcpi,"nature_num"=>$nature_num,"nature_rat"=>$nature_rat,"country"=>$country,"app_id"=>$appid,"saves"=>$saves,"spend"=>$spend,"new_users"=>$new_users,"app_name"=>$app_name,"countrys"=>$countrys ]);
    }
	
	private function rleaChannel($channel_id)
	{
		$channels =array(
		    "all"=>"all",
			"1"=>"9",
			"2"=>"31",
			"5"=>"5",
			"6"=>"4",
			"7"=>"8",
			"3"=>"6",
			"11"=>"3",
			"18"=>"2",
			"29"=>"7",
			"216"=>"Volo",
			"235"=>"Snapchat",
			"19223"=>"32",
			"19275"=>"TikTok",
			"19449"=>"1",
			"19593"=>"Taptica"
		);
		return isset($channels[$channel_id])?$channels[$channel_id]:"";
	}
	
	//自己后台花费数据
	private function getGbSpend($appid,$start_date,$end_date,$platform,$country)
	{
		$where = "app_id={$appid} and  date>='{$start_date}' and date<='{$end_date}'";
		if( $country!="all" )
		{
			$where.=" and country='{$country}'";
		}	
		if( preg_match("/^\d+$/",$platform) )
		{
			$where.=" and platform_type={$platform}";
			$d= Db::name("adspend_data")->field("sum(spend) as spend")->where($where)->find();
			
		}elseif($platform=="all"){
			$index = new Index( request() );
			$spend = $index->getspendtotal($appid,$start_date,$end_date,"all",$country);
			return $spend["spend"];
		}else{
			//手动添加的数据
			$ids="";
			$res = Db::name("platform")->field("platform_id")->where(["platform_name"=>$platform])->select();
			if( !empty($res) )
			{
				$ids =" and platform in (".implode(",",array_column($res,"platform_id")).")";
			}
			$where.=$ids;
			$d= Db::name("control_data")->field("sum(spend) as spend")->where($where)->find();
		}
		return isset($d["spend"]) && $d["spend"]>0?$d["spend"]:"0.00";		
	}
	
	//tenjin当天花费 新增 数据
	private function getTenjinSpend($app_id,$start_date,$country,$channel_id)
	{
		$where ="app_id={$app_id} and date='{$start_date}'";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $channel_id!="all" && $channel_id!="" )
		{
			$where.=" and ad_network_id='{$channel_id}'";
		}
		$r = Db::name("tenjin_report")->field("sum(tracked_installs) as installs,sum(spend) as spend")->where($where)->find();
		$spend = isset($r["spend"]) && $r["spend"]?round($r["spend"],4):"0.00";
		$installs = isset($r["installs"]) && $r["installs"]?$r["installs"]:0;
		return ["spend"=>$spend,"installs"=>$installs];
	}
	
	//tenjin 第几天的 回收数据
	private function getTenjinReven($app_id,$start_date,$country,$channel_id,$day)
	{
		$where ="app_id={$app_id} and date='{$start_date}' and days_since_install={$day}";
		if( $country!="all" && $country!="" )
		{
			$where.=" and country='{$country}'";
		}
		if( $channel_id!="all" && $channel_id!="" )
		{
			$where.=" and ad_network_id='{$channel_id}'";
		}
		$r = Db::name("tenjin_reten")->field("sum(daily_active_users) as reten_users,sum(iap_revenue+ad_revenue) as revenu")->where($where)->find();
		$revenu = isset($r["revenu"]) && $r["revenu"]?$r["revenu"]:"0.00";
		$reten_users = isset($r["reten_users"]) && $r["reten_users"]?$r["reten_users"]:0;
		$ARPPU = $reten_users<=0?"0.00":round($revenu/$reten_users,4);	
		return ["revenu"=>$revenu,"reten_users"=>$reten_users,"ARPPU"=>$ARPPU];
	}
	
	//获取渠道
	private function getAdNetWork($app_id)
	{
		//$t = Db::name("tenjin_report")->field("ad_network_id")->where("app_id={$app_id}")->group("ad_network_id")->select();
		//$ids = array_column($t,"ad_network_id");
		//$data = Db::name("tenjin_adnetwork")->field("adnetwork_id,name")->where(["adnetwork_id"=>["in",$ids] ])->select();
		//array_unshift($data,["adnetwork_id"=>"all","name"=>"全部渠道"]);
		$data = array(
		    ["adnetwork_id"=>"all","name"=>"全部渠道"],
			["adnetwork_id"=>"0","name"=>"Organic"],
			["adnetwork_id"=>"1","name"=>"Tapjoy"],
			["adnetwork_id"=>"2","name"=>"AdColony"],
			["adnetwork_id"=>"5","name"=>"Google Ads"],
			["adnetwork_id"=>"6","name"=>"Vungle"],
			["adnetwork_id"=>"7","name"=>"Chartboost"],
			["adnetwork_id"=>"3","name"=>"Facebook"],
			["adnetwork_id"=>"11","name"=>"Applovin"],
			["adnetwork_id"=>"18","name"=>"Unity Ads"],
			["adnetwork_id"=>"29","name"=>"ironSource"],
			["adnetwork_id"=>"83","name"=>"Apple Search Ads"],
			["adnetwork_id"=>"216","name"=>"Volo"],
			["adnetwork_id"=>"235","name"=>"Snapchat"],
			["adnetwork_id"=>"19223","name"=>"Toutiao"],
			["adnetwork_id"=>"19275","name"=>"TikTok"],
			["adnetwork_id"=>"19449","name"=>"Mintegral"],
			["adnetwork_id"=>"19593","name"=>"taptica自定义回调"]
		);
		return $data;
	}
	
	
	//下载
	public function download($appid="",$date="",$data="")
	{
		if( empty($data) )
		{
			return;
		}
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		$s = $mem->set('download_excel_data',$data);
		if($s )
		{
			exit("ok");
		}			
	}
	
	public function excel($appid="",$date="")
	{
		 $xlsName  ="UPLTV表格";
	     $tit ="[".$date."]".getapp_name($appid);
		 $xlsCell  = array(
			array('day','天数'),
			array('date','日期'),
			array('arpdau','ARPDAU'),
			array('reten','留存'),
			array('reten_user','留存用户'),
			array('ecpm','eCPM'),
			array('current_revenue','当天收益'),
			array('total_revenue','总收益'),
			array('roi','ROI')
		);
		$xlsData =[];
		$mem = new \Memcache();
        $mem->connect("127.0.0.1", 11211);
		$xlsData = $mem->get('download_excel_data');
         if( empty($xlsData) )
		 {
			 exit("no data");
		 }
		$Index = new Index(request());
        echo  $Index->exportExcel($xlsName,$xlsCell,$xlsData,$xlsName,$tit);	
	}
	
	private function getarpdau($index,$appid,$start,$end,$country)
	{
		$out_data=[];
		$dates = getDateFromRange($start, $end);
		foreach( $dates as $k=>$v )
		{
			$data = $index->getrevenuetotal($appid,$v,$v,"all",$country);
			$out_data[] =["arpdau"=>$data["total"]["dauarpu"],"ecpm"=>$data["total"]["ecpm"]]; 
		}
		return $out_data;
	}
		
	public function del($id="")
	{
		if($id)
		{
			Db::name("roi_model")->where("id={$id}")->delete();
		}
		exit("ok");
	}
}
