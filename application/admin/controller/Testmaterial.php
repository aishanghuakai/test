<?php
namespace app\admin\controller;
use app\admin\controller\Base;
use \think\Db;
use think\Session;

class Testmaterial extends Base
{
    
	 protected $db=null;
	 protected $pconnection = [
        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',
        // 服务器地址
        'hostname'    => '127.0.0.1',
        // 数据库名
        'database'    => 'ads_service',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'root',
        // 数据库连接端口
        'hostport'    => '',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8mb4',
        // 数据库表前缀
        'prefix'      => 'hellowd_',
    ]; 
	
	 protected $sconnection = [
        // 数据库类型
        'type'        => 'mysql',
        // 数据库连接DSN配置
        'dsn'         => '',
        // 服务器地址
        'hostname'    => '127.0.0.1',
        // 数据库名
        'database'    => 'ads_service',
        // 数据库用户名
        'username'    => 'thehotgames',
        // 数据库密码
        'password'    => 'week2e13&hellowd',
        // 数据库连接端口
        'hostport'    => '',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8mb4',
        // 数据库表前缀
        'prefix'      => 'hellowd_',
    ];
	
	
	
	//游戏类型
	private $gameTypeList = array(
	       ["label"=>"动作类","value"=>"1"],
		   ["label"=>"休闲","value"=>"2"],
		   ["label"=>"模拟","value"=>"3"],
		   ["label"=>"策略","value"=>"4"],
		   ["label"=>"放置","value"=>"5"],
		   ["label"=>"益智","value"=>"6"],
		   ["label"=>"体育","value"=>"7"]
	);
	
	//文案
	private $officialList = array(
	   "The best game I've played👉",
       "😱Help sort things out!😰",
	   "Play for FREE ✅",
	   "Free in anytime-either anywhere",
	   "Harder than you think🧐"
	);
	
	private function get_label($arr){
		$string ="";
		if(!empty($arr))
		{
			foreach($this->gameTypeList as $g)
			{
				if(in_array($g["value"],$arr))
				{
					$string.= $g["label"].",";
				}
			}
		}
		return $string;
	}
	
	public function index(){
		$admin_user = getuserinfo();
		if($admin_user["ad_role"]=="copartner")
		{
			exit("You don't have access");
		}
		return $this->fetch();
	}
	
	public function lists(){
		return $this->fetch();
	}
	
	public function delete($id="",$title="")
	{
		if($id)
		{
			Db::name("test_material")->where("id={$id}")->update(["status"=>2]);
			$this->googlecurl("http://ad.gamebrain.io/testmaterial/updateStatus?id={$id}&title={$title}");
		}
		echo json_encode( ["code"=>200,"message"=>"操作成功"] );exit;
	}
	
	public function json_data($date=[]){
		$this->db = Db::connect($this->sconnection);
		$admin_user = getuserinfo();
		$where ="1=1";
		if($admin_user["ad_role"]=="copartner")
		{
			$where ="status=2 and id in({$admin_user['allow_testlist']})";
		}		
		if($date && !is_null($date))
		{
			$where.=" and create_time>='{$date[0]} 00:00:00' and create_time<='{$date[1]} 23:59:59'";
		}
		$total_spend ="0.00";
		$res = Db::name("test_material")->where($where)->order('id desc')->select();
		if(!empty($res))
		{
			foreach($res as &$v)
			{
				
				
				$videoList = Db::name("test_video")->where("test_id={$v["id"]}")->select();
				$total_data =[
								'installs' => 0,
								'impressions' => 0,
								'spend'=>'0.00',
								'clicks' => 0,
								'ctr' => 0,
								'cpm'=>0,
								'cvr' => 0];
				if(!empty($videoList))
				{
					foreach( $videoList as &$vv )
					{
						$video_data = [
								'installs' => 0,
								'spend'=>'0.00',
								'impressions' => 0,
								'clicks' => 0,
								'ctr' => 0,
								'cpm'=>0,
								'cvr' => 0
							];
						$campaginList = $this->db->table("ads_video_report")->field('campaign_id')->where("type=2 and video_id={$vv["id"]}")->group('campaign_id')->select();
						if(!empty($campaginList))
						{
							$where = [
								'campaign_id' => ['in',array_column($campaginList,'campaign_id')]
							];
							/* if($date && !is_null($date))
							{
								$where["date"] = ['between',[$date[0],$date[1]]];
							} */
							
							$row = $this->db->table('ads_report')->field("sum(spend) as spend,sum(installs) as installs,sum(impressions) as impressions,sum(clicks) as clicks")->where($where)->find();
							$row["installs"] =$row["installs"]?$row["installs"]:0;
							$row["spend"] =$row["spend"]?round($row["spend"],2):'0.00';
							$row["impressions"] =$row["impressions"]?$row["impressions"]:0;
							$row["clicks"] =$row["clicks"]?$row["clicks"]:0;
							$video_data = $row;
							$video_data["ctr"] = $video_data["impressions"] <= 0 ? 0 : number_format($video_data["clicks"] * 100 /$video_data["impressions"], 2);
							$video_data["cvr"] = $video_data["clicks"] <= 0 ? 0 : number_format($video_data["installs"] * 100 /$video_data["clicks"], 2);
							$video_data["cpm"] = $video_data["impressions"] <= 0 ? 0 : number_format($video_data["spend"] * 1000 /$video_data["impressions"], 2);
						}
						$total_data['installs'] += $video_data["installs"];
						$total_data['impressions'] += $video_data["impressions"];
						$total_data['clicks'] += $video_data["clicks"];
						$total_data['spend'] += $video_data["spend"];
						$vv["report"] = $video_data;
					}
					$v["videoList"] = $videoList;
				}
				$total_data['spend'] = round($total_data['spend'],2);
				$total_spend += $total_data['spend'];
				$total_data["ctr"] = $total_data["impressions"] <= 0 ? 0 : number_format($total_data["clicks"] * 100 /$total_data["impressions"], 2);
				$total_data["cvr"] = $total_data["clicks"] <= 0 ? 0 : number_format($total_data["installs"] * 100 /$total_data["clicks"], 2);
				$v["report"] = $total_data;
				$total_data["cpm"] = $total_data["impressions"] <= 0 ? 0 : number_format($total_data["spend"] * 1000 /$total_data["impressions"], 2);
				$v["report"] = $total_data;
			}
		}
		echo json_encode( ["res"=>$res,"total_spend"=>round($total_spend,2)] );exit;
	}
	
	public function add()
	{							
		$this->assign("gameTypeList", $this->gameTypeList);
		$this->assign("officialList", $this->officialList);
        $this->assign("countryList",admincountry());
		return $this->fetch();
	}
	
	public function get_adv_account(){
		$list = Db::name('app_base')->field("name as label,id as value,id")->select();
		if(!empty($list))
		{
			foreach($list as &$v)
			{
				$v["children"] =Db::name('advertising_account')->field("name as label,advertiser_id as value")->where(["app_base_id"=>$v["id"],"channel"=>2,"type"=>2])->select();
			}
		}
		echo json_encode( $list );exit;
	}
	
	public function create(){
		$data =input("post.");
		if(!empty($data))
		{
			if(isset($data["game_type"]) && !empty($data["game_type"]))
			{
				$data["game_type"] = implode(",",$data["game_type"]);
			}
			$create_time = $data["create_time"];
			unset($data["create_time"]);
			if(isset($data["custom_time"]) && $data["custom_time"])
			{
				$data["status"] =3;
				$data["create_time"] = $create_time;
			}else{
				$data["create_time"] = date("Y-m-d H:i:s");
			}
			$videolist =$data["videoList"];
			unset($data["videoList"],$data["custom_time"]);
			if(!empty($videolist))
			{
				foreach($videolist as &$v)
				{
					$filename = $v["filename"];
					$arr = explode("-",$filename);
					$title = isset($arr["0"])?trim($arr["0"]):"";
					if($title)
					{
						$row = Db::name("test_material")->where(["title"=>$title])->find();
						$test_id = $row["id"];
						if(empty($row))
						{
							$data["title"] = $title;
							$test_id = Db::name("test_material")->insertGetId($data);
						}
						if($test_id)
						{
							$v["test_id"] = $test_id;
							Db::name("test_video")->insert($v);
						}
					}
				}
				echo json_encode( ["code"=>200,"message"=>"创建成功"] );exit;
			}			
		}
		echo json_encode( ["code"=>500,"message"=>"创建失败"] );exit;
	}
	
	//请求
	private function googlecurl($url,$data=null,$method = null)
	{
	    $header = array("Content-Type:application/x-www-form-urlencoded;charset=UTF-8");
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($method == 'post') {
			curl_setopt($ch, CURLOPT_POST,1);
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书 https访问的时候
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书 https访问的时候
		if($data){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//传递参数
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
    }
	
	
}
