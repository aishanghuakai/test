<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
class Adid extends Base
{
    
	public function index($appid="",$platform="all",$adtype="all",$id="",$country="all")
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
		 
	   $where ="app_id={$appid}";
	   if( $platform!="all" )
	   {
		   $where.=" and platform={$platform}";
	   }
	   if( $adtype!="all" )
	   {
		   $where.=" and adtype='{$adtype}'";
	   }
	   if( $id!="" )
	   {
		   $where.=" and unit_id like '%{$id}%'";
	   }
	   $page =10;
	   if( $country!="all" && $adtype!="all" )
		  {
			 $page =1000; 
		  }
	   $res = Db::name('ads_id')->where($where)->order("ordernum asc,id desc")->paginate(10,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "appid"=>$appid,"platform"=>$platform,"adtype"=>$adtype,"country"=>$country ]
								] );	   
	  $data = $res->toarray();
	  $r= $data["data"];
	  if( !empty($r) )
	  {
		  if( $country!="all" )
		  {
			 foreach( $r as &$vv )
			  {
				 $one = Db::name('ads_id')->where( [ "p_id"=>$vv["id"],"country"=>$country ] )->field("ordernum,ecpm")->find();
				 if( !empty($one) )
				 {
					 $vv["ordernum"] = $one["ordernum"];
					 $vv["ecpm"] = $one["ecpm"];
				 }
			  }
              if( $adtype!="all" )
			  {
				  $r = admin_array_sort($r,"ordernum","asc");
			  }				  
		  }
		  
	  }
	  $countrys = admincountry();
      $this->assign("platform",$platform);
	  $this->assign("allplatforms",getplatform(""));
      $this->assign("adtype",$adtype);
      $this->assign("id",$id);
	  $this->assign("country",$country);
	  $this->assign("country_name",$countrys[$country]);
      $this->assign("countrys",$countrys);	  
	  return $this->fetch('index',["res"=>$r,"list"=>$res ]);
    }
	
	public function add($appid="")
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
		 $this->assign("appid",$appid);
		 $this->assign("allplatforms",getplatform(""));
		 return $this->fetch();
	}
	
	public function add_data($appid="",$unit_id="",$platform="",$adtype="",$remark="",$ordernum="",$ecpm="")
	{
		if($unit_id)
		{
			$unit_id = trim($unit_id);
		}
		$r = Db::name('ads_id')->where( ["unit_id"=>$unit_id,"platform"=>$platform,"app_id"=>$appid ] )->find();
		if( !empty($r) )
		{
			exit("exist");
		}
		$result = Db::name('ads_id')->insert( ["unit_id"=>$unit_id,"ecpm"=>$ecpm,"ordernum"=>$ordernum,"platform"=>$platform,"app_id"=>$appid,"remark"=>$remark,"adtype"=>$adtype,"update_time"=>date("Y-m-d H:i:s",time() ) ] );
		if( $result!==false )
		{
			Db::name('adcash_data')->where( ["unit_id"=>$unit_id,"platform"=>$platform,"sys_app_id"=>$appid ] )->update( ["adtype"=>$adtype] );
			exit("ok");
		}
		exit("fail");
	}
	
	public function edit_data($id="",$unit_id="",$adtype="",$remark="")
	{
		$last = false;
		if($unit_id)
		{
			$unit_id = trim($unit_id);
		}
		$r = Db::name('ads_id')->where( ["id"=>$id ] )->find();
		if( !empty($r) )
		{
		  $last = Db::name('ads_id')->where( ["id"=>$id ] )->update( ["unit_id"=>$unit_id,"adtype"=>$adtype,"remark"=>$remark ] );
		}
		if( $last!==false )
		{
			exit("ok");
		}
		exit("fail");
	}
	
	public function input_data($fieldindex="",$id="",$country="",$update_v="")
	{
		if( $country=="all" )
		{
		   $r = Db::name('ads_id')->where( ["id"=>$id ] )->update( [ $fieldindex=>$update_v ] );
		}else{
		  $t = Db::name('ads_id')->where( ["p_id"=>$id,"country"=>$country ] )->find();
		  if( !empty($t) )
		  {
			$r = Db::name('ads_id')->where( ["p_id"=>$id,"country"=>$country ] )->update( [ $fieldindex=>$update_v ] );
		  }else{
			$o = Db::name('ads_id')->field("ecpm,ordernum")->where( ["id"=>$id ] )->find();
			$o[$fieldindex] = $update_v;
			$o["p_id"] = $id;
			$o["country"] = $country;
			$r =Db::name('ads_id')->insert($o);
		  }
		}
		if($r!==false)
		{
			exit("ok");
		}
        exit("fail");
	}
	
	public function delete($id="")
	{
		if( !$id )return false;
		$ret = Db::name('ads_id')->delete($id);
        if($ret!==false)
		{
			exit("ok");
		}
        exit("fail");	
	}
	
}
