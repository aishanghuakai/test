<?php
namespace app\admin\controller;
use think\Db;
use \think\Request;
use app\api\controller\Index;
use think\Session;
class Chart
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
	
	// 模型列表
	public function modellist()
	{
		$userid =Session::get('admin_userid');
		$list  =Db::name("chart_model")->where( "userid={$userid}")->select();
		$this->out_data(20000,$list);
	}
	
	//添加修改模型
	public function updatemodel($id="",$name="",$desc="")
	{
		if( $id>0 )
		{
			Db::name("chart_model")->where(["id"=>$id])->update(["name"=>$name,"desc"=>$desc]);
		}elseif($id==0 && $name!=""){
			$userid =Session::get('admin_userid');
			Db::name("chart_model")->insert(["name"=>$name,"userid"=>$userid,"desc"=>$desc]);
		}
	   $this->out_data(20000,[]);
	}
	
	public function deletemodel($id="")
	{
		if( $id>0 )
		{
			Db::name("chart_model")->delete($id);
		}
		$this->out_data(20000,[]);
	}
	
	//创建图表
	public function addchart($id="",$name="",$desc="",$content=[])
	{
		
		if( $name && $desc && $content )
		{
			if( $id>0 )
			{
				Db::name("chart_list")->where( ["id"=>$id] )->update( ["name"=>$name,"desc"=>$desc,"content"=>json_encode($content)] );
			}else{
				$userid =Session::get('admin_userid');
				$id  = Db::name("chart_list")->insertGetId( ["name"=>$name,"userid"=>$userid,"desc"=>$desc,"content"=>json_encode($content)] );
			}			
		}
		$this->out_data(20000,$id);
	}
	
	public function addmodelchart($chart_id="",$model_id="")
	{
		if( $chart_id && $model_id )
		{
			$userid =Session::get('admin_userid');
			$r = Db::name("chart_my")->where( ["chart_id"=>$chart_id,"userid"=>$userid,"model_id"=>$model_id ] )->find();
			if( empty($r) )
			{
				Db::name("chart_my")->insertGetId( ["chart_id"=>$chart_id,"userid"=>$userid,"model_id"=>$model_id ] );
			}
		}
		$this->out_data(20000,[]);
	}
	
	//模型列表
	public function modelchartlist($id="")
	{
		$layout =[];
		$caculCols=[];
		$dimensions=[];
		$chartType=[];
		$content = [];
		$filterType=[];
		$filterLists=[];
		$chartLoading=[];
		$results =[];
		if( $id && $id!="undefined" )
		{
		  $userid =Session::get('admin_userid');
		  $res  =Db::name("chart_my")->alias('m')->field('m.chart_id,a.name,a.desc,a.content')->join('chart_list a','a.id=m.chart_id')->where( "model_id={$id} and m.userid in({$userid},0)")->select();
		  $num = count($res);
		  if( !empty($res) )
		  {
			  foreach( $res as $k=>$vv )
			  {
				  $data = json_decode($vv["content"],true);
				  $w =ceil(24/$num);
				  $layout[$k] = ["x"=>$k*$w,"y"=>rand(0,$num),"w"=>$w,"h"=>9,"i"=>$k,"index"=>$k];
				  $caculCols[$k]=$data["caculCols"];
				  $dimensions[$k] =$data["dimensions"];
				  $chartType[$k] =$data["chartType"];
				  $content[$k] = ["id"=>$vv["chart_id"],"name"=>$vv["name"],"desc"=>$vv["desc"] ];
				  $chartLoading[$k] = true;
				  $filterType[$k] = $data["filterType"];
		          $filterLists[$k] = $data["filterLists"];
				  $results[$k] =[];
			  }
		  }
		}
		$obj = [ "results"=>$results,"filterType"=>$filterType,"filterLists"=>$filterLists,"layout"=>$layout,"caculCols"=>$caculCols,"dimensions"=>$dimensions,"chartType"=>$chartType,"content"=>$content,"chartLoading"=>$chartLoading ];
		$this->out_data(20000,$obj);
	}
	
	public function deletemodellist($chart_id="",$model_id="")
	{
		if( $chart_id && $model_id )
		{
			Db::name("chart_my")->where( ["chart_id"=>$chart_id,"model_id"=>$model_id ] )->delete();			
		}
		$this->out_data(20000,[]);
	}
	
	public function one($id="")
	{
		if( $id )
		{
			$res  = Db::name("chart_list")->find($id);
			$res["content"] = json_decode($res["content"],true);
		}
		$this->out_data(20000,$res);
	}
	public function deletechart($id="")
	{
		if( $id )
		{
			Db::name("chart_list")->delete($id);
		}
		$this->out_data(20000,[]);
	}
	public function lists()
	{
		$userid =Session::get('admin_userid');
		$list = Db::name("chart_list")->where("userid in({$userid},0)")->select();
		$this->out_data(20000,$list);
	}
	//图表数据
	public function data($dimensions=[],$caculCols=[],$chartType="",$filterLists=[],$filterType="")
	{
		$r = $this->getdate($filterLists);
		$index = new Index();
		$out_data=[];
		if( !empty($dimensions) )
		{
			$g = $dimensions[0]["value"];
			if( $g=="date" )
			{				
				$list = $this->getDateFromRange($r[0],$r[1]);
			}else{
				$func ="get".$g;
				$list = $this->$func($filterLists);
			}
			if( !empty($list) )
			{
				foreach( $list as $vv )
				{
					
						$res =  $index->gettotaldata($r[0],$r[1],$g,$vv["value"],$filterLists,$filterType);
						$res[$g] = $vv["label"];
						$out_data[] = $res;
					
				}
			}
		}else{
			$res =  $index->gettotaldata($r[0],$r[1],1,1,$filterLists,$filterType);
			$res["date"]=$r[0]."-".$r[1];
			$out_data[] = $res;
		}
		
		$this->out_data(20000,$out_data);
	}
	
	private function getcountry($filterLists){
		
		if( !empty($filterLists) )
		{
			foreach( $filterLists as $vv )
			{
				if( $vv["pvalue"]=="country" && $vv["value"]!="all" )
				{
					return [ [ "label"=>$vv["label"],"value"=>$vv["value"]] ];
				}
			}
		}
		return $this->country();
	}
	
	private function getapp($filterLists){
		
		if( !empty($filterLists) )
		{
			foreach( $filterLists as $vv )
			{
				if( $vv["pvalue"]=="app" && $vv["value"]!="all" )
				{
					return [ [ "label"=>$vv["label"],"value"=>$vv["value"]] ];
				}
			}
		}
		return $this->app();
	}
	
	//检查是否设置了时间
	private function getdate($filterLists)
	{
		if( !empty($filterLists) )
		{
			foreach( $filterLists as $vv )
			{
				if( $vv["pvalue"]=="date" && $vv["value"]=="custom" )
				{
					return $vv["cvalue"];
				}elseif( $vv["pvalue"]=="date" && $vv["value"]!="custom" ){
					$arr  =explode("_",$vv["value"]);
					$start = date("Y-m-d",strtotime("-{$arr[1]} day"));
			        $end = date("Y-m-d",strtotime("-2 day"));
					return [$start,$end];
				}
			}
		}
		return [ date("Y-m-d",strtotime("-9 day")),date("Y-m-d",strtotime("-2 day")) ];
	}

	function getDateFromRange($startdate, $enddate){

		$stimestamp = strtotime($startdate);
		$etimestamp = strtotime($enddate);

		// 计算日期段内有多少天
		$days = ($etimestamp-$stimestamp)/86400+1;

		// 保存每天日期
		$date = array();

		for($i=0; $i<$days; $i++){
			$date[] =[ "value"=>date('Y-m-d', $stimestamp+(86400*$i)),"label"=>date('Y-m-d', $stimestamp+(86400*$i)) ];
		}

		return $date;
   }
	
	public function getAttrList($type="")
	{
		if( request()->isOptions() )
		{
			$this->out_data(20000,[]);
		}
		if( $type )
		{
			$data = $this->$type();
		}
		$this->out_data(20000,$data);
	}
	
	//APP
	private function app()
	{
		$list = Db::name("app")->field("id as value,app_name as label,platform")->select();
		foreach($list as &$vv)
		{
			$vv["label"] = $vv["label"].$vv["platform"];
			unset($vv["platform"]);
		}
		return $list;
	}
	
	//渠道
	private function channel()
	{
		$out_data =[];
		$list = [
		    "1"=>"Mobvista", 
			"2"=>"Unity",
			"3"=>"Applovin",
			"4"=>"Vungle",
			"5"=>"Admob",
			"6"=>"Facebook",
			"7"=>"IronSource",
			"8"=>"Chartboost",
			"9"=>"Tapjoy",
			"30"=>"Upltv",
			"31"=>"Adcolony",
			"32"=>"Toutiao",
			"33"=>"Yomob"
		];
		foreach( $list as $k=>$vv )
		{
			$out_data[] = ["label"=>$vv,"value"=>$k];
		}
		return $out_data;
	}
	
	//国家
	private function country()
	{
		$out_data=[];
		$list =  array(
		   "all"=>"全部",
		   "US"=>"美国",
		   "TW"=>"台湾",
		   "HK"=>"香港",
		   "JP"=>"日本",
		   "KR"=>"韩国",
		   "DE"=>"德国",
		   "FR"=>"法国",
		   "CN"=>"中国",
		   "RU"=>"俄罗斯",
		   "CA"=>"加拿大",
		   "GB"=>"英国",
		   "TH"=>"泰国",
		   "BR"=>"巴西",
		   "TR"=>"土耳其",
		   "VN"=>"越南",
		   "IN"=>"印度",
		   "MY"=>"马来西亚",
		   "ID"=>"印度尼西亚",
		   "IT"=>"意大利",
		   "ES"=>"西班牙",
		   "SE"=>"瑞典",
		   "CH"=>"瑞士",
		   "MO"=>"澳门",
		   "AU"=>"澳大利亚",
		   "PH"=>"菲律宾",
		   "NG"=>"尼日利亚",
		   "PK"=>"巴基斯坦",
		   "MX"=>"墨西哥",
		   "BD"=>"孟加拉",
		   "SG"=>"新加坡",
		   "PT"=>"葡萄牙",
		   "ZA"=>"南非",
		   "IE"=>"爱尔兰",
		   "NZ"=>"新西兰"
		);
		foreach( $list as $k=>$vv )
		{
			$out_data[] = ["label"=>$vv,"value"=>$k];
		}
		return $out_data;
	}	
}
