<?php
namespace app\admin\controller;
use think\Session;
use \think\Db;

//手动加数据
class Control extends Base
{
   //添加名称
   public function add_name($platform_name="",$id="0",$appid="")
   {
	   if( $platform_name && $id=="0" )
	   {
		   $r = Db::name("platform")->where( ["status"=>0,"type"=>2] )->find();
		   if( !empty($r) )
		   {
			   $t =Db::name("platform")->where( ["id"=>$r["id"] ] )->update( ["status"=>1,"app_id"=>$appid,"platform_name"=>$platform_name] );
			   
		   }else{
			  $t = Db::name("platform")->insert( ["status"=>1,"type"=>2,"app_id"=>$appid,"platform_name"=>$platform_name] );
		   }
	   }elseif( $platform_name && $id!="0" ){
		   
		    $t =Db::name("platform")->where( ["id"=>$id ] )->update( ["platform_name"=>$platform_name] );
	   }
	  if( $t!==false )
	  {
		  exit("ok");
	  }
	  exit("fail");
   }
   
   public function add_tag($appid="",$tag_value="")
   {
	  
	   $userid = Session::get('admin_userid');
	   if( $tag_value!="" )
	   {		  
		  $allcountry = admincountry();
		 
		  $country="";
		  foreach( $allcountry as $key=>$vv )
		  {
			  if( preg_match("/{$tag_value}/",$vv))
			  {
				  $country = $key;
				  break;
			  }
		  }

		  Db::name("tag")->insert( ["app_id"=>$appid,"userid"=>$userid,"tag_value"=>$tag_value,"country_code"=>$country] );		  
	   }
	  
	   exit("ok");
   }
   
   public function delete_tag($id="")
   {
	   Db::name("tag")->delete($id);
	   exit("ok");
   }
   
   //添加数据
   public function add_data($appid="",$date="",$country="",$field="",$value="",$platform="")
   {
	  $r = Db::name("control_data")->where( ["app_id"=>$appid,"date"=>$date,"country"=>$country,"platform"=>$platform ] )->find();
	   if( !empty($r) )
	   {
		   $t =Db::name("control_data")->where( ["id"=>$r["id"] ] )->update( [ $field=>$value ] );
		   
	   }else{
		  $t = Db::name("control_data")->insert( ["app_id"=>$appid,"date"=>$date,"country"=>$country,"platform"=>$platform,$field=>$value ] );
	   }
	  if( $t!==false )
	  {
		  exit("ok");
	  }
	  exit("fail");
   }
   //添加收益比例分成
   public function add_revenue_rate($appid="",$revenue_rate="")
   {
	   if( preg_match("/^\d+$/",$appid ) && preg_match("/^\d+$/",$revenue_rate ) )
	   {
		   $r = Db::name("revenue_rate")->where( ["app_id"=>$appid ] )->find();
		   if( !empty($r) )
		   {
			   $t =Db::name("revenue_rate")->where( ["id"=>$r["id"] ] )->update( [ "revenue_rate"=>$revenue_rate ] );
			   
		   }else{
			  $t = Db::name("revenue_rate")->insert( ["app_id"=>$appid,"revenue_rate"=>$revenue_rate,"update_name"=>$this->_adminname ] );
		   }
		  if( $t!==false )
		  {
			  exit("ok");
		  }
	   }
	   exit("fail");
   }
   
   //添加内购收益
   public function add_purchase($appid="",$date="",$v="",$country="")
   {
	   if( $appid )
	   {
		   $r = Db::name("purchase_data")->where( ["app_id"=>$appid,"date"=>$date,"country"=>$country ] )->find();
		   if( !empty($r) )
		   {
			   $t =Db::name("purchase_data")->where( ["id"=>$r["id"] ] )->update( [ "revenue"=>$v ] );
			   
		   }else{
			  $t = Db::name("purchase_data")->insert( ["app_id"=>$appid,"revenue"=>$v,"date"=>$date,"country"=>$country ] );
		   }
		   if( $t!==false )
		  {
			  exit("ok");
		  }
	   }
	    exit("fail");
   }
   
   //添加反馈
   public function feedback(){
	   $params = input("post.");
	   $filename="";
	   if (preg_match('/^(data:\s*image\/(\w+);base64,)/',$params["url"], $result)){
          $type = $result[2];
		  $filename = "/uploads/feedback/".time().uniqid().".{$type}";
		  $new_file = $_SERVER['DOCUMENT_ROOT'] .$filename; 
		  file_put_contents($new_file, base64_decode(str_replace($result[1], '',$params["url"] )));
	   }
	   $params["url"] = $filename;
	   $params["userid"] = Session::get('admin_userid');
	   $params["app_id"] = getcache("select_app");
	   Db::name("feedback")->insert( $params );
	   exit("ok");
   }
}
