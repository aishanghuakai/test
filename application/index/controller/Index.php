<?php
namespace app\index\controller;
use \think\View;
use \think\Db;

class Index
{
    public function index(View $view)
    {
        header("Content-type:text/html;charset=utf-8");
		if( !$this->is_mobile_request() )
		{
			exit("请使用手机访问");
		}
		$list=Db::name('picture')->order("id desc,`like` desc")->page("1,10")->select();
		if( !empty($list) )
		{
			foreach( $list as &$vv )
			{
				$arr = explode(",",$vv["image"]);
				$vv["thumb"] = $arr[0];
				$vv["list"] =$arr;
				$vv["num"] = count(explode(",",$vv["image"]));
			}
		}
		$view->list = $list;
		return $view->fetch();
    }
	
	
	 public function news(View $view)
    {
        header("Content-type:text/html;charset=utf-8");
		if( !$this->is_mobile_request() )
		{
			exit("请使用手机访问");
		}
		$list=Db::name('content')->order('id desc')->select();
		$view->list = $list;
		return $view->fetch();
    }
	
	public function addpost(View $view)
    {
        return $view->fetch();
    }
	
	public function addcomment(View $view)
    {
        return $view->fetch();
    }
	
	public function upload(){
		
		$file = request()->file('image');
		$info = $file->validate(['ext' => 'jpg,png,jpeg,gif'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'picture');
		if ($info) {
            $origin =ROOT_PATH . 'public' . DS . 'uploads' . DS . 'picture'. DS .$info->getSaveName();
			$savename = DS .'uploads' . DS . 'picture'. DS .$info->getSaveName();
			$this->image_png_size_add($origin,$origin);
			echo json_encode(["data"=>["showurl"=>$savename,"saveurl"=>$savename],"code"=>200,"message"=>"上传成功"]);
			
        } else {
            echo json_encode(["data"=>[],"code"=>500,"message"=>"上传失败"]);
        }
	}
	
	/** 

* desription 压缩图片 

* @param sting $imgsrc 图片路径 

* @param string $imgdst 压缩后保存路径 

*/

	function image_png_size_add($imgsrc,$imgdst){  

	 list($width,$height,$type)=getimagesize($imgsrc);  

	 $new_width = $width*0.5;

	 $new_height =$height*0.5;  

	 switch($type){  

	  case 1:  

	   $giftype=$this->check_gifcartoon($imgsrc);  

	   if($giftype){  

		header('Content-Type:image/gif');  

		$image_wp=imagecreatetruecolor($new_width, $new_height);  

		$image = imagecreatefromgif($imgsrc);  

		imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);  

		imagejpeg($image_wp, $imgdst,80);  

		imagedestroy($image_wp);  

	   }  

	   break;  

	  case 2:  

	   header('Content-Type:image/jpeg');  

	   $image_wp=imagecreatetruecolor($new_width, $new_height);  

	   $image = imagecreatefromjpeg($imgsrc);  

	   imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);  

	   imagejpeg($image_wp, $imgdst,80);  

	   imagedestroy($image_wp);  

	   break;  

	  case 3:  

	   header('Content-Type:image/png');  

	   $image_wp=imagecreatetruecolor($new_width, $new_height);  

	   $image = imagecreatefrompng($imgsrc);  

	   imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);  

	   imagejpeg($image_wp, $imgdst,80);  

	   imagedestroy($image_wp);  

	   break;  

	 }  

	}  

	/** 

	* desription 判断是否gif动画 

	* @param sting $image_file图片路径 

	* @return boolean t 是 f 否 

	*/

	function check_gifcartoon($image_file){  

	 $fp = fopen($image_file,'rb');  

	 $image_head = fread($fp,1024);  

	 fclose($fp);  

	 return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;  

	}  
	
	public function loadmore($page="1",View $view)
	{
		$list=Db::name('picture')->order("id desc,`like` desc")->page("{$page},10")->select();
		if( !empty($list) )
		{
			foreach( $list as &$vv )
			{
				$arr = explode(",",$vv["image"]);
				$vv["thumb"] = $arr[0];
				$vv["list"] =$arr;
				$vv["num"] = count(explode(",",$vv["image"]));
			}
		}else{
			exit("empty");
		}
		$view->list = $list;
		return $view->fetch();
	}
	
	
	function is_mobile_request(){  
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';  
    $mobile_browser = '0';  
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))  
        $mobile_browser++;  
    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))  
        $mobile_browser++;  
    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))  
        $mobile_browser++;  
    if(isset($_SERVER['HTTP_PROFILE']))  
        $mobile_browser++;  
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));  
    $mobile_agents = array(  
        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',  
        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',  
        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',  
        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',  
        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',  
        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',  
        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',  
        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',  
        'wapr','webc','winw','winw','xda','xda-' 
        );  
    if(in_array($mobile_ua, $mobile_agents))  
        $mobile_browser++;  
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)  
        $mobile_browser++;  
    // Pre-final check to reset everything if the user is on Windows  
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)  
        $mobile_browser=0;  
    // But WP7 is also Windows, with a slightly different characteristic  
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)  
        $mobile_browser++;  
    if($mobile_browser>0)  
        return true;  
    else
        return false;  
}
	
	public function like($id=""){
		if( $id )
		{
			$res = Db::name('picture')->where("id={$id}")->setInc('like');
			exit("ok");
		}
		exit("fail");
	}
	
	public function viewimage($id="")
	{
		if( $id )
		{
			$res = Db::name('picture')->find($id);
			$arr = explode(',',$res["image"]);
			
			echo json_encode( ["imageList"=>$arr,"descript"=>$res["descript"] ]  );exit;
		}
		echo json_encode([]);exit;
	}
	
	public function addPicture($image=[],$descript=""){
		if( $image && $descript )
		{
			if( !empty($image) )
			{
				$str = implode(",",$image);
				$r = Db::name('picture')->insert( ["image"=>$str,"descript"=>$descript] );
				if( $r!==false )
				{
					exit("ok");
				}
			}			
		}
		exit("fail");
	}
	
	public function postcomment($actor="",$content=""){
		if( $actor && $content )
		{						
			$r = Db::name('content')->insert( ["actor"=>$actor,"content"=>$content] );
			if( $r!==false )
			{
				exit("ok");
			}			
		}
		exit("fail");
	}
}
