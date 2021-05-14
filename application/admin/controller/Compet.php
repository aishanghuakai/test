<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;
use \app\admin\controller\Index;


class Compet extends Base
{
   
	public function doGet($url)
    {
        //初始化
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);
        
        return $output;
    }
	
	public function test($keyword="")
	{
		 header("Content-type: text/html; charset=utf-8");
		 $keyword = urlencode($keyword);
		 $url="https://play.google.com/store/search?q={$keyword}&c=apps";
	     $result = $this->doGet($url);
		 if( !preg_match("/id-card-list/",$result) )
		 {
			 echo '<p style="text-align:center;">对不起，没有搜到您想要的内容哦</p>';exit;
		 }
         echo $result;exit;
	}
	
	public function contact()
	{
		$list = Db::name('contact_us')->order("id desc")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );
		$this->assign("data",$list);
		return $this->fetch();
	}
	
	public function apple($keyword="")
	{
		header("Content-type: text/html; charset=utf-8");
		$keyword = urlencode($keyword);
		$url="https://theappstore.org/search.php?search={$keyword}&platform=software";
		$out_data = [];
		import('phpQuery.phpQuery', EXTEND_PATH);
		$detail = \phpQuery::newDocumentFile($url);
		$card = pq($detail)->find('#bodypanel')->find('.appmain');
		 foreach($card as $k=>$vv)  
		{  
		   $out_data[$k]["title"] = pq($vv)->find('.apptitle')->text();
		   $out_data[$k]["subtitle"] = pq($vv)->find('.mobseller')->text();
		   $out_data[$k]["packages"] = pq($vv)->attr("trackid");
		   $img = pq($vv)->find("img")->eq(0)->attr("src");
		   $img = str_replace("60x60","170x170",$img);
		   $out_data[$k]["img"] =$img;
		   $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=".$out_data[$k]["packages"];
		}
		$this->assign("res",$out_data);
		return $this->fetch();
		
	}
	
	public function likesave()
	{
		$data = $_POST;
		$data["developer"] =trim($data["developer"]);
		$admin_id = Session::get('admin_userid');
		
		$result = getdeveloperapp($data["developer_url"]);
		$list =$result["out_data"]; 
		$data["app_num"]  =count($list);
		$rank_list =[];
		$date =date("Y-m-d");
		foreach($list as $vv)
		{
			$r =Db::name("product_grap")->field("country,topnum")->where(["app_package"=>$vv["packages"],"date"=>$date])->order("topnum asc")->find();
			if( !empty($r) )
			{
				$rank_list[] =["img"=>$vv["img"],"packages"=>$vv["packages"],"topnum"=>$r["topnum"],"country"=>$r["country"]];
			}
		}
		
		Db::startTrans();
		try{
			$res = Db::name('developer')->where(["developer_url"=>$data["developer_url"]])->find();
			if( empty($res) )
			{
				$data["rank_list"] = json_encode($rank_list);
				$last_id = Db::name('developer')->insertGetId($data);
			}else{
				$last_id = $res["id"];
			}			
			if( $last_id!==false )
			{
				Db::name('developer_like')->insert(["userid"=>$admin_id,"developer_id"=>$last_id]);
			}			
			// 提交事务
			Db::commit();
            exit("ok");			
		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
			exit("fail");
		}		
	}
	
	//更新关注列表
	public function updatemylikelist()
	{
	  $res = Db::name('developer')->select();
	 
	  if( !empty($res) )
	  {
		  foreach( $res as $vvv )
		  {			  
				$result = getdeveloperapp($vvv["developer_url"]);				
				$list =$result["out_data"]; 
				$data["app_num"]=count($list);
				$rank_list =[];
				$date =date("Y-m-d");
				foreach($list as $vv)
				{
					$r =Db::name("product_grap")->field("country,topnum")->where(["app_package"=>$vv["packages"],"date"=>$date])->order("topnum asc")->find();
					if( !empty($r) )
					{
						$rank_list[] =["img"=>$vv["img"],"packages"=>$vv["packages"],"topnum"=>$r["topnum"],"country"=>$r["country"]];
					}
				}
			$data["rank_list"] = json_encode($rank_list);
			Db::name('developer')->where(["id"=>$vvv["id"]])->update($data);	
		  }
	  }
	  exit("ok");
	}
	
	public function googledetail($packages="")
	{
		 header("Content-type: text/html; charset=utf-8");
	 	 $url = "https://play.google.com/store/apps/details?id=".$packages;
	     $result = $this->doGet($url);
		 if( !preg_match("/T4LgNb/",$result) )
		 {
			 echo '<p style="text-align:center;">对不起，暂无数据可查</p>';exit;
		 }
         echo $result;exit;
	}
	
	//查看开发者应用
	public function allapp($id="",$to="")
	{
		
		header("Content-type: text/html; charset=utf-8");
		if( $id && $to=="" )
		{
			$res = Db::name("developer")->find($id);
			if( !empty($res) )
			{
				 $data = getdeveloperapp($res["developer_url"]);
				 $list = $data["out_data"];
				 $date =date("Y-m-d");
				 foreach($list as &$vv)
				 {
					$r =Db::name("product_grap")->field("country,topnum")->where(["app_package"=>$vv["packages"],"date"=>$date])->order("topnum asc")->find();
					if( !empty($r) )
					{
						$vv["rank"] =$r["country"]." ".$r["topnum"];
					}else{
						$vv["rank"] ="100名之外";
					}
				 }
				 $data["out_data"]=$list;
				 $this->assign("res",$res);
                 $this->assign("data",$data);
				 return $this->fetch();
			}			
		}elseif($id=="" && $to)
		{
			 $data = getdeveloperapp($to);
			 $list = $data["out_data"];
			 $res["developer"] = $list[0]["subtitle"];
			 $date =date("Y-m-d");
			 foreach($list as &$vv)
			 {
				$r =Db::name("product_grap")->field("country,topnum")->where(["app_package"=>$vv["packages"],"date"=>$date])->order("topnum asc")->find();
				if( !empty($r) )
				{
					$vv["rank"] =$r["country"]." ".$r["topnum"];
				}else{
					$vv["rank"] ="100名之外";
				}
			 }
			 $data["out_data"]=$list;
			 $this->assign("res",$res);
			 $this->assign("data",$data);
			 return $this->fetch();
		}
		exit("this is not allowd");
	}
	
	private function getappdetail($kw="")
	{
		if( preg_match("/^\d.+?/",$kw) )
		 {
			 $url = "https://itunes.apple.com/app/id".$kw;
			 import('phpQuery.phpQuery', EXTEND_PATH);
			 //实例化PHPExcel
			$detail = \phpQuery::newDocumentFile($url);
			$title = pq($detail)->find("h1.product-header__title")->html();
			preg_match('/(?:.|\n)*?<span /i', $title, $match);
			$title =trim(strip_tags($match[0]));
			if(!$title)
			 {
				 $this->error("暂无搜索到您想要的{$kw},请检查是否输入正确","/admin_compet/search");exit;
			 }
			$comment_num =pq($detail)->find("div.we-customer-ratings__count")->text();
			$comment_num = str_replace(" Ratings","",$comment_num);
			$comment_score =pq($detail)->find("span.we-customer-ratings__averages__display")->text();
			$update_time = pq($detail)->find("time")->eq(0)->text();
			$game_type=pq($detail)->find("div.information-list__item")->eq(2)->find("a.link")->text();
		 }else{
			 $url = "https://play.google.com/store/apps/details?id=".$kw;
			 $result = $this->doGet($url);
			 preg_match("/<h1 class=\"AHFaub\" itemprop=\"name\">(.*)<\/h1>/i",$result,$match);
			 if(empty($match))
			 {
				 $this->error("暂无搜索到您想要的{$kw},请检查是否输入正确","/admin_compet/search");exit;
			 }
			 $title = strip_tags($match[1]);
			 preg_match("/<a itemprop=\"genre\" .* class=\"hrTbp R8zArc\">(.*?)<\/a>/i",$result,$match1);
			 $game_type =$match1[1];
			 preg_match("/aria-label=\"Rated (.*) stars out of five stars\">/i",$result,$match2);
			 $comment_score =$match2[1];
			 preg_match("/<span class=\"\" aria-label=\"(.*) ratings\">/i",$result,$match3);
			 $comment_num=0;
			 if( !empty($match3) )
			 {
				$comment_num =$match3[1]; 
			 }        
			 preg_match("/<div class=\"hAyfc\"><div class=\"BgcNfc\">Updated<\/div>(.*?)<\/div>/i",$result,$match4);
			 $update_time =strip_tags($match4[1]);
		 }
		 return ["title"=>$title,"game_type"=>$game_type,"comment_score"=>$comment_score,"comment_num"=>$comment_num,"update_time"=>$update_time];
	}
	
	public function results($kw="")
	{
		
		header("Content-type: text/html; charset=utf-8");
		 if( preg_match("/^\d.+?/",$kw) )
		 {
			$developer_url=$this->getios($kw); 
		 }else{
			 $developer_url=$this->getandroid($kw);
		 }
         $admin_id = Session::get('admin_userid');
		 $islike =false;
         $r = Db::query("select a.* from hellowd_developer a join hellowd_developer_like b on a.id=b.developer_id where b.userid={$admin_id} and a.developer_url='{$developer_url}'");		 
		 if( !empty($r) )
		 {
			 $islike =true;
		 }
		 $this->assign("islike",$islike);
		 return $this->fetch();
	}
	public function delete($id="")
	{
		if( !$id )return false;
	    $admin_id = Session::get('admin_userid');
		$r = Db::name('developer_like')->where(["userid"=>$admin_id,"developer_id"=>$id])->delete();
		if( $r!==false )
		{	
			exit("ok");
		}
		exit("fail");
	}
	public function sendmessage($email="",$title="",$content="",$platform="",$developer_url="",$developer="")
	{
		if( $email && $title && $content )
		{
			$r = send_mail($email,$email,$title,$content );
			if( $r!==false )
			{
				$this->aftersend($email,$title,$content,$platform,$developer_url,$developer);
				exit("ok");
			}
		}
		exit("fail");
	}
	
	//发送邮箱后保存数据并关联我的关注
	private function aftersend($email,$title,$content,$platform,$developer_url,$developer)
	{
		$truename = Session::get('truename');
		$result = getdeveloperapp($developer_url);
		$list =$result["out_data"]; 
		$data["app_num"]  =count($list);
		$rank_list =[];
		$date =date("Y-m-d");
		foreach($list as $vv)
		{
			$r =Db::name("product_grap")->field("country,topnum")->where(["app_package"=>$vv["packages"],"date"=>$date])->order("topnum asc")->find();
			if( !empty($r) )
			{
				$rank_list[] =["img"=>$vv["img"],"packages"=>$vv["packages"],"topnum"=>$r["topnum"],"country"=>$r["country"]];
			}
		}		
		Db::startTrans();
		try{
			$res = Db::name('developer')->where(["developer_url"=>$developer_url])->find();
			if( empty($res) )
			{
				$data["rank_list"] = json_encode($rank_list);
				$data["platform"] =$platform;
                $data["developer_url"] =$developer_url;
                $data["developer"] =$developer;
                $data["from"] =2;				
				$last_id = Db::name('developer')->insertGetId($data);
			}else{
				$last_id = $res["id"];
				Db::name('developer')->where(["id"=>$res["id"]])->update(["from"=>2]);
			}
            Db::name('send_email')->insert(["developer_id"=>$last_id,"title"=>$title,"content"=>$content,"send_email"=>$email,"send_user"=>$truename,"developer"=>$developer]);			
			// 提交事务
			Db::commit();	
		} catch (\Exception $e) {
			// 回滚事务
			Db::rollback();
		}		
	}
	
	public function send()
	{
		 $list = Db::name('send_email')->order("id desc")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );
		$this->assign("list",$list);
		return $this->fetch();
	}
	
	public function send_detail($id="")
	{
		if($id)
		{
			$res = Db::name('send_email')->find($id);
			$this->assign("res",$res);
		    return $this->fetch();
		}		
	}
	
	//收件箱
	public function receive()
	{
		$list = Db::name('receive_email')->order("id desc")->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[  ]
								] );
		$this->assign("data",$list);
		return $this->fetch();
	}
	
	public function receive_detail($id="")
	{
		if($id)
		{
			$res = Db::name('receive_email')->find($id);
			$this->assign("res",$res);
		    return $this->fetch();
		}		
	}
	
	private function getios($kw)
	{
		 $url = "https://itunes.apple.com/app/id".$kw;
		 import('phpQuery.phpQuery', EXTEND_PATH);
		 //实例化PHPExcel
		$detail = \phpQuery::newDocumentFile($url);
		$title = pq($detail)->find("h1.product-header__title")->html();
		preg_match('/(?:.|\n)*?<span /i', $title, $match);
		$title =trim(strip_tags($match[0]));
		if(!$title)
		 {
			 $this->error("暂无搜索到您想要的{$kw},请检查是否输入正确","/admin_compet/search");exit;
		 }
		$subtitle=pq($detail)->find("h2.product-header__subtitle")->text();
		$email=pq($detail)->find("h2.product-header__identity")->text();
		$developer_url=pq($detail)->find("h2.product-header__identity a.link")->attr("href");
		$image = pq($detail)->find("img.we-artwork__image")->attr("src");
		$comment_num =pq($detail)->find("div.we-customer-ratings__count")->text();
        $comment_num = str_replace(" Ratings","",$comment_num);
        $comment_score =pq($detail)->find("span.we-customer-ratings__averages__display")->text();
        $update_time = pq($detail)->find("time")->eq(0)->text();
        $game_type=pq($detail)->find("div.information-list__item")->eq(2)->find("a.link")->text();
		$installs ="---";
		$developer_url = $developer_url."#see-all/i-phonei-pad-apps";
		$this->assign("shop_url","https://itunes.apple.com/app/id".$kw);
		$this->assign("developer_url",$developer_url);
		$developer_apps = \phpQuery::newDocumentFile($developer_url);
		$out_data=[];
		$card = pq($developer_apps)->find("div.l-row")->find('a');
		//echo count($card);exit;
		foreach($card as $k=>$vv)
		{
			$out_data[$k]["title"] = pq($vv)->find("div.we-lockup__title ")->text();			
			$out_data[$k]["subtitle"] =$email;//pq($vv)->find(".page-header__title")->text();						
			$out_data[$k]["img"] =pq($vv)->find("img")->attr("src");
			$shop_url = pq($vv)->attr("href");
			$rt = explode("/",$shop_url);
			$s_url =explode("id",end($rt));
			$s_url = explode("?",end($s_url));
			$out_data[$k]["packages"] =$s_url[0];
			$out_data[$k]["shop_url"] ="http://console.gamebrain.io/admin_compet/results?kw=".$out_data[$k]["packages"];
		}
		$this->assign("out_data",$out_data);		
         $this->assign("title",str_replace(PHP_EOL, '',$title));
        $this->assign("game_type",$game_type);
         $this->assign("comment_num",$comment_num);
         $this->assign("comment_score",$comment_score);	
         $this->assign("update_time",$update_time);
         $this->assign("installs",$installs);
         $this->assign("subtitle",$subtitle);
		 $this->assign("email",str_replace(PHP_EOL, '',$email) );
		 $this->assign("package",$kw);
		 $this->assign("platform","ios");
         $this->assign("image",$image);
		 return $developer_url;
	}
	
	private function getandroid($kw)
	{
		 $url = "https://play.google.com/store/apps/details?id=".$kw;
	     $result = $this->doGet($url);
		 preg_match("/<h1 class=\"AHFaub\" itemprop=\"name\">(.*)<\/h1>/i",$result,$match);
		 if(empty($match))
		 {
			 $this->error("暂无搜索到您想要的{$kw},请检查是否输入正确","/admin_compet/search");exit;
		 }
		 $title = strip_tags($match[1]);
		 preg_match("/<a itemprop=\"genre\" .* class=\"hrTbp R8zArc\">(.*?)<\/a>/i",$result,$match1);
		 $game_type =$match1[1];
		 preg_match("/aria-label=\"Rated (.*) stars out of five stars\">/i",$result,$match2);		
		 $comment_score =isset($match2[1])?$match2[1]:"未知";
         preg_match("/<span class=\"\" aria-label=\"(.*) ratings\">/i",$result,$match3);
		 $comment_num=0;
		 if( !empty($match3) )
		 {
			$comment_num =$match3[1]; 
		 }        
		 preg_match("/<div class=\"hAyfc\"><div class=\"BgcNfc\">Updated<\/div>(.*?)<\/div>/i",$result,$match4);
		 $update_time =strip_tags($match4[1]);
         preg_match("/<div class=\"hAyfc\"><div class=\"BgcNfc\">Installs<\/div>(.*?)<\/div>/i",$result,$match5);
         $installs =strip_tags($match5[1]);
         preg_match("/<div class=\"hAyfc\"><div class=\"BgcNfc\">Developer<\/div>(.*)<\/div>/i",$result,$match6);
         $emails =$match6[1];
		 preg_match("/\"mailto:(.*?)\"/i",$emails,$em);
		 $email =$em[1]; 
		 preg_match("/<img src=\"(.*)\" class=\"T75of ujDFqe\" .* itemprop=\"image\">/i",$result,$match7);
         $image = "http://cnf.mideoshow.com/adgastatic/getgoogleimg?url=".str_replace("https:","",$match7[1]);
		 preg_match("/<span class=\"T32cc UAO9ie\">(.*?)<\/span>/i",$result,$match8);
         $subtitle = strip_tags($match8[1]);
		 preg_match("/<a  href=\"(.*)\"  class=\"hrTbp R8zArc\">/i",$match8[1],$match9);
         $developer_url =$match9[1];
		 
		 import('phpQuery.phpQuery', EXTEND_PATH);
		 //实例化PHPExcel
		 $detail1 = \phpQuery::newDocumentFile($developer_url."&num=100&start=0");
        $out_data=[];
       if(  preg_match("/developer/i",$developer_url) )
	   {
			 $card = pq($detail1)->find('.IFTL7')->find('.Vpfmgd');
			   foreach($card as $k=>$vv)  
				{  
				   $out_data[$k]["title"] = pq($vv)->find('div.nnK0zc')->text();
				   $out_data[$k]["subtitle"] = pq($vv)->find('a.mnKHRc')->text();
				   $d = pq($vv)->find("a.JC71ub")->attr("href");
				   $arr = explode("id=",$d);
				   $out_data[$k]["packages"] =$arr[1];
				   $img = pq($vv)->find("img")->eq(0)->attr("data-src");
				   $out_data[$k]["img"] = "http://cnf.mideoshow.com/adgastatic/getgoogleimg?url=".str_replace("https:","",$img);
				   $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=".$out_data[$k]["packages"];
				}  
	   }else{
		   $r = pq("div.xBRiJc")->find("a")->attr('href');
		   $detail = \phpQuery::newDocumentFile($r);
		   $card = pq($detail)->find('.id-card-list')->find('.card');
			 foreach($card as $k=>$vv)  
			{  
			   $out_data[$k]["title"] = pq($vv)->find('a.title')->text();
			   $out_data[$k]["subtitle"] = pq($vv)->find('a.subtitle')->text();
			   $out_data[$k]["packages"] = pq($vv)->attr("data-docid");
			   $out_data[$k]["img"] = "http://cnf.mideoshow.com/adgastatic/getgoogleimg?url=".pq($vv)->find(".cover-image")->attr("src");
			   $out_data[$k]["shop_url"] = "http://console.gamebrain.io/admin_compet/results?kw=".$out_data[$k]["packages"];
			} 
	   }		   
       
         $this->assign("out_data",$out_data);		
         $this->assign("title",str_replace(PHP_EOL, '',$title));
         $this->assign("game_type",$game_type);
         $this->assign("comment_num",$comment_num);
         $this->assign("comment_score",$comment_score);	
         $this->assign("update_time",$update_time);
         $this->assign("installs",$installs);
         $this->assign("subtitle",$subtitle);
		 $this->assign("email",$email);
		 $this->assign("platform","android");
		 $this->assign("package",$kw);
		 $this->assign("shop_url","https://play.google.com/store/apps/details?id=".$kw);
		 $this->assign("developer_url",$developer_url);
         $this->assign("image",$image);
		 return $developer_url;
	}
	
	public function search()
	{
		 
		 return $this->fetch();
	}
	
	public function mylikelist()
	{
		$admin_id = Session::get('admin_userid');
		$developer = Db::query("select a.* from hellowd_developer a join hellowd_developer_like b on a.id=b.developer_id where b.userid={$admin_id} order by b.id desc");
		$res = Db::query("select * from hellowd_developer  where `from`=2 order by id desc");
		$developer = $this->unique_multidim_array(array_merge($developer,$res),"id");
		if( !empty($developer) )
		{
			foreach($developer as &$vv)
			{
				$vv["rank"] = json_decode($vv["rank_list"],true);				
			}
		}
		$this->assign("developer",$developer);
		return $this->fetch();
	}
	
	function unique_multidim_array($array, $key) 
	{ 
    $temp_array = array(); 
    $i = 0; 
    $key_array = array(); 
     
    foreach($array as $val) { 
        if (!in_array($val[$key], $key_array)) { 
            $key_array[$i] = $val[$key]; 
            $temp_array[$i] = $val; 
        } 
        $i++; 
    } 
    return $temp_array; 
   } 
	
	public function index($country="US",$type="today",$platform="android")
    {      	    
	  
	   $date = date("Y-m-d",time());
	   $where["country"] = $country;
	   $where["date"] = $date;
	   $where["platform"] = $platform;
	   if( $type=="today" )
	   {
		   $list =Db::name('product_grap')
				 ->where ( $where )
				 ->order("topnum asc")
				 ->paginate(15,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "country"=>$country,"platform"=>$platform ]
								] );
         $data = $list->toarray();
         $data = isset($data["data"])?$data["data"]:[];
			if( !empty($data) )
			{
				$data = $this->getdata($data);
			}
       $this->assign('list',$list);			
	   }elseif( $type=="up" ){
		    $data =Db::name('product_grap')->where ( $where )->select();
			$data = array_slice(admin_array_sort($this->getdata($data,"up"),"num",'desc'),0,10);
           		
	   }
	   elseif( $type=="down" ){
		    $data =Db::name('product_grap')->where ( $where )->select();
			$data = array_slice(admin_array_sort($this->getdata($data,"down"),"num",'desc'),0,10);			
	   }
	    $this->assign('platform',$platform);
	    $this->assign('data',$data);
		$this->assign('type',$type);
	    $this->assign('country',$country);
	    return $this->fetch();
    }
	
	public function index_chats($country="",$app_package="")
	{
		
		$end_date = date("Y-m-d");
		$start_date = date("Y-m-d",strtotime("-2 day"));
		$range_date = getDateFromRange($start_date,$end_date);
		$date ="";		
		$num="";
		foreach( $range_date as $key=>$vvv )
		{
						
			 $d_sql ="select topnum from hellowd_product_grap where app_package='{$app_package}' and country='{$country}' and date='{$vvv}'";
			 $d = Db::query($d_sql);
			 $topnum = isset($d[0]["topnum"])?$d[0]["topnum"]:100;
			 $date.="'{$vvv}',";			
			 $num.="{$topnum},";			
		}
		$this->assign("dates",rtrim($date,','));
		$this->assign("nums",rtrim($num,','));
		return $this->fetch();
	}
	
	private function getdata($data,$filter="")
	{
		$out_data =[];
		foreach( $data as $key=>&$vv )
			{
				$yesnum = $this->getyesdayrangnum($vv["app_package"],$vv["country"] );
				if( $vv["topnum"]>=$yesnum )
				{
					$vv["isplus"] ="down";
					$vv["num"] = $vv["topnum"]-$yesnum;
					if( $filter=="down" )
					{
						$out_data[$key] = $vv;
					}
				}else{
					$vv["isplus"] ="up";
					$vv["num"] = $yesnum-$vv["topnum"];
					if( $filter=="up" )
					{
						$out_data[$key] = $vv;
					}
				}
			}
		return !empty($out_data)?$out_data:$data;	
	}
	
	private function getyesdayrangnum($app_package,$country)
	{
		$yesday = date("Y-m-d",strtotime("-1 day"));
		$r = Db::name('product_grap')->field("topnum")->where( ["app_package"=>$app_package,"country"=>$country,"date"=>$yesday] )->find();
		if( !empty($r) )
		{
			return $r["topnum"]?$r["topnum"]:100;
		}
		return 100;
	}
	
}
