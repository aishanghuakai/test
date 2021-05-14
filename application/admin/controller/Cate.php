<?php
namespace app\admin\controller;
use think\Session;
use \think\Db;
class Cate extends \think\Controller
{
    public function test()
	{
	
		return;
		//$private_key =file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/rsa_private_key.pem');
		$private_key="cd168f272d8d61f72d59bc44fc5a4c8609fd3cb7f646a3c18b57e41cf54058c8eec3769ed59f9ecf27ffb52b39f958f4260d01f82b53f921bf5ac4ab106a32d1a8bbd66ea5871466ffcc950ffa53e6ff0db38bac032b7298f9486918bdce9415ddda26cdf9fb524831789df14d1d1d025f074f3e585238eb87e88fd3ecf94a7cf02887bbb59c31d1df83aa63ec05f5b13a638f52c7662cd88f659802452d92d08d4ef5a3ad19586199c8bc66d0943da703297cedf0359d270cc6ea6049d2c58b5dd7315ea8e6ad42e3b5dc8e276406d4851053456aaf55e0effce75382a01793646bebfd421ca36398537657962203a57c6d150974ba0d3b1407d1986093ef09,publicExponent=10001,privateExponent=66e7cee4a5a2af694b441f87ade7d145e07544a639d8b00a4954cc8d0b32425a70137bcdaefaecc09d1d5d56573c9a39c76df77302aa9bf32704f071b546d441071d593723af67b956d8dc62ac04b57f7cec7e44cd425bba0e3b1b20e2fc68a9a02bfa14983e56c70aabaef488f0f6c15dd55919bb4f66682845b5056d7e98b4b9e71481e38df771f7f4087e0ef4813014dc535e5ecedc271ac817010e768cf7bedc9ecdbdcaeb4ba3205e02cc21033d284121be334dbabf1f26c6b7a0edcccc8776ca51c2aea25a37542251664cddb0ec977ff3f34e3d72e8a90945d3a52ae93751c687bef2f4a299db75646602a0e3e8dafbad21311e76ce5864fffd66e201,primeP=ed332f380937f1c654b5dd3edba47fe9422c13d9156d157c95d75f3b96636458f35db1222d504ce238906ead51a3996bf69164b38ae1b9d215123a50c5dbae74805fdbd1d866c8952a000aaeeb43a6b875fd56ebaea9151e4f614490b4bba54b6f449b9eefa63b3883e391edba8d4cf227904118adf918b55adf678a1f3eab19,primeQ=dd57d2733b2b25cb7614941c912f9aad5c3c06e5f3833c3e9e4a8a8a4a95c718b04c46b2c9ec892863367d183cccf5d4c24ed69f2fe47437f6324285f951e15ae747cd0f6825ac5f0da50dc5e1dda49a68018b117ef93314b9fcbee39658cbba213964493a9e9c907b8e5256864bf2d7c05d9e2949cdb1aa763ea7361073d171,primeExponentP=632990c5f664a2f8ed1512a7629f84b1f96dffe63324e564ae27d9b2498ec7b6c0171d2452994d6b0d86a41e6510e5e4d2b1b9cdad73ec813d39df934104340c94567ec95d94aaaa61629f14bd5a0ceaf4b8b28bd8faeacf3f1444dbd212d8698edcb9ca5a19be8c43e6f16cd71e975a6cdbcbbb0b90259a3ba145e1e26a0a91,primeExponentQ=3a5c6da0f478ebfe39a4336954deb869fd67d669cb4b4f1733a573e202c87b368745955e8edf4f4ad6de071ddcea2de76b545e429ade21c69fa3a0e6b256649513665b2b34caba2855f0af30fddcf309c362a4c878323cc5ba446a109813d1a3c21edfd26f226325b56e51477f2187665bd253618f942cc445693fad81a9081,crtCoefficient=8ee7f84b7bfeccdf9a52f62b2871af77df9611cbe7f578bcef04ff2e5050e795458485d19818bb87ab7f311815b7b161c2fee484accdd416d9172049afb815c177793513b56e74ccccaf58e0db794db8d41b0547474c5f28be56966cf61ba50c3309a499123162d67d3bdde100bfd818d2f019eefe6e3b5de5fa7d4dbca9c08c";
		//$public_key =file_get_contents( $_SERVER['DOCUMENT_ROOT'].'/rsa_public_key.pem');
		 //$json = file_get_contents("http://console.gamebrain.io/advertise/advlist?appid=114");
		//$data ="测试呵呵哒";
		//$pi_key =  openssl_pkey_get_private($private_key); 
        //echo $pi_key;exit;
		//openssl_private_encrypt($data,$encrypted,$pi_key);
		//$encrypted = base64_encode($encrypted);//base64只是为了避免特殊字符  
        // echo $encrypted ;exit;//加密之后的结果 
		 $encrypted="MqItTtoLdT/ZIulZmTOsszpbqEbaEm6FalgZD0A9T9KLH1kBXMas9qmJDM9RVTEbeJ1GT6n8q52+Htn1RYE+UqfeLBLixFrif3VeSLqoQkQ6SAlVJBqo6GMwynFC2eUreK2kMp3iTCIoQBV6wrPKiLyvXIy1H7bwr2G+17M56RYSTAFRzVizmv7a0bFpIPkWi1qmXlIlf/CVfi6Nw72Atmo/VTdcZuuoSZi5QDfdZ9xm8SUYd4Jmp6BnV438YbQnhFNWsMn5CcQg/AsWdHgayxf/u1Kg3KKjW/tePkonEVka6IJw1QtRgULiOUVifRyuFANBj5q5lBzTIIEpBseaXQ==";
		// echo $encrypted ;exit;//加密之后的结果
		openssl_private_decrypt(base64_decode($encrypted),$decrypted,$private_key);//私钥加密的内容通过公钥可用解密出来  
		echo $decrypted,"\n";  exit;//解密之后的结果
	}
	
	public function login()
    {      
	  $username = Session::get('username');
	  if( !$username )
	  {
		  if(!empty($_COOKIE['CTRDS']))
		  {
			  $r =json_decode(unserialize(base64_decode($_COOKIE['CTRDS']) ),true);
			  if( !empty($r) )
			  {
				  Session::set('username',$r["username"] );
				  Session::set('admin_userid',$r["id"] );
				  Session::set('truename',$r["truename"] );
				  $this->loginwork($r);
				  admin_log("登录了系统");
				  return redirect('/admin_index/main');exit; 
			  }			  
		  }
	  }
	  //获取当前域名
	  $isnewhost = true;
	  if( preg_match( "/mideoshow/",$_SERVER['SERVER_NAME']) )
	  {
		  $isnewhost = false;
	  }
	  return view('login',["isnewhost"=>$isnewhost ]);
    }
	//邀请注册
	public function invite($spm="")
	{
		 $admin_id = base64_decode($spm);
		 if( !$admin_id || !preg_match("/^\d+$/",$admin_id) )
		 {
			 exit("非法操作");
		 }
		 $r =Db::name('admin')->where(["id"=>$admin_id])->find();
		 if( $r["passwd"]!="" && $r["status"]==1 )
		 {
			 return redirect('/admin_cate/login');exit;
		 }
		 return view('invite',["r"=>$r]);
	}
	
	//邀请注册添加
	public function add_invite($id="",$truename="",$passwd="")
	{
		if( !$id || !preg_match("/^\d+$/",$id) )
		 {
			 exit("error");
		 }
		 //检查是否存在该用户
		 $r = Db::name('admin')->where(["id"=>$id,"status"=>0])->find();
		 if( !empty($r) )
		 {
			$t= Db::name('admin')->where(["id"=>$id,"status"=>0])->update( [ "passwd"=>hew_md5($passwd),"truename"=>$truename,"status"=>1,"remark"=>$truename ] );
			if( $t!==false )
			{
				Session::set('username',$r["username"] );
			    Session::set('admin_userid',$id );
			    Session::set('truename',$truename );
			    $this->loginwork($r);
			    admin_log("登录了系统");
				echo "ok";exit;
			}
			echo "fail";exit;
		 }
	}
	
	
	public function logout()
	{
		admin_log("退出了系统");
		Session::clear();
		setcookie("CTRDS", NULL);
		return redirect('/admin_cate/login');exit;
	}
	public function do_login($passwd="")
	{
		if( $passwd=="695741" )
		{
			Session::set('username',"admin");	
			echo "ok";exit;
		}
		echo "fail";exit;
	}
	
	public function accountlogin($username="",$passwd="",$remember="")
	{
		$passwd = hew_md5($passwd);
		$r = Db::name('admin')->field('id,username,truename,updatelogin,passwd')->where(["username"=>$username])->find();
		if( !empty($r) && isset( $r["passwd"] ) && $r["passwd"]==$passwd )
		{
			$tr = Db::name('admin_check')->where(["userid"=>$r["id"]])->find();
			if( empty($tr) )
			{
				exit("repair");
			}			
			setcookie('token',$username,time()+3600*24,'/','.gamebrain.io');
			if( $remember==1 )
			{
				$cookies =base64_encode(serialize(json_encode($r)));
				setcookie("CTRDS",$cookies, time()+3600*24*7);
			}
			Session::set('username',$username );
			Session::set('admin_userid',$r["id"] );
			Session::set('truename',$r["truename"] );
			$this->loginwork($r);
			admin_log("登录了系统");
			//$this -> success('登录成功!','/admin_index/main');
			echo "ok";exit;
		}else{
			//$this -> error('账号密码不匹配');exit;
			echo "fail";exit;
		}
		
	}
	
	public function repair($username="",$oldpassword="",$newpassword="")
	{
		if( !$username || !$oldpassword || !$newpassword )
		{
			echo "fail";exit;
		}		
		$passwd = hew_md5($oldpassword);
		$newpassword = hew_md5($newpassword);
		$r = Db::name('admin')->where(["username"=>$username])->find();
		if( !empty($r) && isset( $r["passwd"] ) && $r["passwd"]==$passwd )
		{
			Db::name('admin')->where(["id"=>$r["id"]])->update( ["passwd"=>$newpassword] );
			Db::name('admin_check')->insert( ["userid"=>$r["id"],"type"=>1] );
			setcookie('token',$username,time()+3600*24,'/','.gamebrain.io');
			Session::set('username',$username );
			Session::set('admin_userid',$r["id"] );
			Session::set('truename',$r["truename"] );
			$this->loginwork($r);
			admin_log("修改了密码");
			echo "ok";exit;
		}else{			
			echo "fail";exit;
		}
	}
	
	//登录成功后处理
	function loginwork($r)
	{
		Db::name('admin')->where(["id"=>$r["id"] ])->update( ["lastlogin"=>$r["updatelogin"],"updatelogin"=>date("Y-m-d H:i:s") ] );
	}
}
