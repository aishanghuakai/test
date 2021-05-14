<?php
namespace app\admin\controller;
use think\Db;
use \think\Request;
use think\Session;
header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Credentials:true");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE');
class Admaterial
{
    
	public function index()
	{
		return view('index');
	}
	
	private function out_data($code,$data)
	{
		$json =["code"=>$code,"message"=>"empty","result"=>$data ];
		exit(json_encode($json));
	}
	
	public function reportData($p="1",$date=[],$keyword="",$sort="cost",$video_id="")
	{
		if( empty($date) )
		{
			$start = date("Y-m-d",strtotime("-6 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
			$date = [$start,$end];
		}
		$where="date>='{$date[0]}' and date<='{$date[1]}'";
		$where1="1=1";
		if( $keyword!="" )
		{
			$keyword = trim($keyword);
			if( preg_match('/^v0.+$/i',$keyword) )
			{
				$where1.=" and d.video_id='{$keyword}'";
			}else{
				$where1.=" and d.title like '%{$keyword}%'";
			}		
		}		
		$p = $p<=0?1:(int)$p;
	    $pagenum = ($p-1)*16;
	    $sql="select r.*,d.advertiser_id,d.thumb,d.video_id,d.title from ( select creative_id,ad_name,sum(cost) as cost,sum(`show`) as `show`,sum(`convert`) as `convert`,sum(click) as click,sum(total_play) as total_play,sum(valid_play) as valid_play  from hellowd_material_report where {$where} group by creative_id  limit {$pagenum},16 ) r left join hellowd_material_detail d on r.creative_id=d.creative_id where {$where1} order by CAST(r.{$sort} AS SIGNED) desc";
		$list  =Db::query($sql);
		if( !empty( $list ) )
		{
			foreach( $list as &$vv )
			{
				$vv["cost"] = round($vv["cost"],2);
				$vv["ctr"] = $vv["show"]<=0?"0.00":round($vv["click"]*100/$vv["show"],2);
				$vv["cvr"] = $vv["convert"];
				$vv["convert"] = $vv["click"]<=0?"0.00":round($vv["convert"]*100/$vv["click"],2);
			}
		}
		$fiter_sql = "select count(*) as num from ( select creative_id,sum(cost) as cost,sum(`show`) as `show`,sum(`convert`) as `convert`,sum(click) as click,sum(total_play) as total_play,sum(valid_play) as valid_play  from hellowd_material_report where {$where} group by creative_id ) r left join hellowd_material_detail d on r.creative_id=d.creative_id where {$where1}";
		$total = Db::query($fiter_sql);
		$num =intval($total[0]["num"]);
		$this->out_data(20000,["list"=>$list,"pagesize"=>16,"total"=>$num,"date"=>$date]);
	}
	
	public function productList(){
		$sql ="select d.*,a.app_name,a.icon_url from (SELECT app_id,count(*) as num from  hellowd_material_detail GROUP BY app_id) d join hellowd_app a on a.id=d.app_id";
		$total=0;
		$list = Db::query($sql);
		if( !empty($list) )
		{
			foreach( $list as &$vv )
			{
				$vv["icon_url"] = getdomainname().$vv["icon_url"];
				$total+=$vv["num"];
			}
		}
		$this->out_data(20000,["list"=>$list,"total"=>$total]);
	}
	
	public function createList($p="1",$date=[],$keyword="")
	{
		if( empty($date) )
		{
			$start ="2019-01-01"; //date("Y-m-d",strtotime("-6 day"));
			$end =  date("Y-m-d",strtotime("-1 day"));
			$date = [$start,$end];
		}
		$where="creative_create_time>='{$date[0]}' and creative_create_time<='{$date[1]}'";
		if( $keyword!="" )
		{
			$keyword = trim($keyword);
			$where.=" and title like '%{$keyword}%'";
		}
		$p = $p<=0?1:(int)$p;
	    $pagenum = ($p-1)*20;
		$sql="select * from hellowd_material_detail  where {$where} group by image_id order by creative_create_time desc limit {$pagenum},20";

		$list  =Db::query($sql);
		$fiter_sql = "select image_id from hellowd_material_detail where {$where} group by image_id";
		$total = Db::query($fiter_sql);
		$num =count($total);
		$this->out_data(20000,["list"=>$list,"pagesize"=>20,"num"=>$num,"date"=>$date]);
	}
	
	public function getProductCreateList($app_id="",$p="1",$type="video")
	{
		if( !$app_id )
		{
			$this->out_data(10010,[]);
		}
		$product  =Db::name("app")->find($app_id);
		if( !empty($product) )
		{
			$product["icon_url"] = getdomainname().$product["icon_url"];
		}
		$p = $p<=0?1:(int)$p;
	    $pagenum = ($p-1)*20;
		if( $type=="video" )
		{
			$sql="select * from hellowd_material_detail  where app_id={$app_id} order by creative_create_time desc limit {$pagenum},20";
		    $fiter_sql = "select image_id from hellowd_material_detail where app_id={$app_id}";
		}else{
			$sql="select title from hellowd_material_detail  where app_id={$app_id} group by title order by creative_create_time desc limit {$pagenum},20";
		    $fiter_sql = "select id from hellowd_material_detail where app_id={$app_id} group by title";
		}		
		$list  =Db::query($sql);
		$total = Db::query($fiter_sql);
		$num =count($total);
		$this->out_data(20000,["list"=>$list,"pagesize"=>20,"num"=>$num,"product"=>$product]);
	}
	
}
