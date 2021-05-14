<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;
use app\util\ShowCode;
use lib\Push;

  //公共基类
class Base
{
     
	 //推送
	 public function push_send($userid,$touserid,$type,$content)
	 {
		
		$users=[];
		$res = Db::name('user')->field("id,uuid,username,language,avatar,token,if(login_source=1,'android','ios') as login_source")->where("id in({$userid},{$touserid})")->select();
		if( !empty($res) )
		{
			$users = array_column ( $res ,null,"id");
			if( !isset($users[ $userid ] ) )
			{
				$users[ $userid ] =[];
			}
			$result = $this->getpushmessage($type,$users[$userid],$content);
			return Push::getInstance()->send( $users[$touserid]["token"],$result["message"],$users[$touserid]["login_source"],$result["title"],$result["body"] );
		}
		return false;
		
	 }
	 
	 //获取推送消息内容
	 private function getpushmessage($type,$result,$content)
	 {
		 $title = "SweetLive Message Notification";
		 $data=[];
		 switch( $type )
		 {
			 case "system":
			    $data['type'] = "system";
                $data['value'] = $content;
				$body = "notification_system_msg";
                break;
			  case 'privatevideochat':
                $data['id'] = $result['id'];
                $data['type'] = "privatevideochat";
                $data['username'] = $result['username'];
				$data['channelid'] = $content;
				$data['uuid'] = $result['uuid'];
				$body = $data['username']." want to have private chat with you";
                break;
              case 'anchorwomanpush':
                $data["id"] = $result['id'];
                $data["type"]="anchorwomanpush";
                $data['username'] = $result['username'];
                $data['uuid'] = $result['uuid'];
                $data["avatar"] = $result["avatar"];
                $title = "Official Message Notification";
                $body = $content;
				$data["content"] = $body;
				break;
			   case 'response':
                  $data["type"]="response";
				  $title = "Official Message Notification";
                  $body ="response";
                  break;				  
              case 'livepush':
                $data["id"] = $result['id'];
                $data["type"]="livepush";
                $data['username'] = $result['username'];
                $data['uuid'] = $result['uuid'];
                $data["avatar"] = $result["avatar"];
                $title =$this->getlivetitle( $result["language"] );
                $body =$this->getlivepush( $result["language"],$result['username'] );
				$data["content"] = $body;
                break;
               case 'privatevideoleave':
                $data['type'] = "privatevideoleave";
                $body ="leave";				
                break;
               case 'verifypush':  //认证女主播成功后推送
                $data['type'] = "verifypush";
				$title = "SweetLive notice";
                $body ="Congratulations on your verification of anchor. Now start to live and receive video chat to get coins.";
                $data["content"] = $body;				
                break;				
		 }
		 $data["title"] = $title;
		 $data["body"] = $body;
		
		 
		 return ["message"=>$data,"title"=>$title,"body"=>$body ];
	 }
	 
	 //获取推送消息内容
	 private function getlivepush( $language,$name )
	 {
		 $content="The host you are following, {$name} is living, come and have a look.";
		 $array = array(
		    "zh"=>"你关注的主播{$name}正在直播，快去看看吧",
			"en"=>"{$name} is living, come and have a look.",
			"pt"=>"A apresentadora {$name}, que você está seguindo, está ao vivo. Assista agora!",
			"es"=>"{$name} está en vivo, ven y echa un vistazo.",
			"de"=>"{$name} macht living, komm und sieh es dir an.",
			"fr"=>"{$name} est en direct, venez la voir.",
			"it"=>"{$name} è in linea, vieni a dare un'occhiata",
			"tr"=>"{$name} canlı yayında, gel ve göz at",
			"ar"=>"يقوم المذيع### الذي قمت بمتابعته باجراء بث مباشر اذهب {$name}عة لمشاهدته"
		);
		if( isset( $array[$language] ) )
		{
			$content = $array[$language];
		}
		return $content;
	 }
	 
	 //获取直播推送标题
	 private function getlivetitle($language)
	 {
		 $title = "Live Stream Notcie";
         if( $language=="zh" )
		 {
			$title ="直播通知";
		 }
		 return $title;
	 }
}
