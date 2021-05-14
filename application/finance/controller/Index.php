<?php
namespace app\finance\controller;
use think\Db;
use app\admin\controller\Base;
use think\Session;

use PhpOffice\Common\Font;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

use PhpOffice\PhpWord\TemplateProcessor;

use app\admin\controller\Index as E;

//财务管理
ini_set("error_reporting","E_ALL & ~E_NOTICE");
class Index extends Base
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
        'database'    => 'finance',
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
        'database'    => 'finance',
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
	
	public function _initialize()
	{
		$this->db = Db::connect($this->pconnection);
	}
	
	public function index()
    {
      $product = $this->db->name('product')->field("id,name")->where("status=1")->order( "id desc")->select();
	  $channel = $this->db->name('channel')->field("id,name")->where("type=1")->order( "id desc")->select();	  
	  $role = getuserrole();
	  $assign =array(
	     "product"=>$product,
		 "channel"=>$channel,
		 "role"=>$role
	  );
	  if( $role=="advertiser" )
	  {
		  return redirect('/finance/payment');exit;
	  }
	  return $this->fetch("index",$assign);
    }
	
	public function product_json()
	{
		
		$arr =array(
		   ["value"=>'1',"label"=>'内部',"children"=>[]],
		   ["value"=>'2',"label"=>'其他',"children"=>[]],
		   ["value"=>'3',"label"=>'合作',"children"=>[]],
		   ["value"=>'4',"label"=>'发行',"children"=>[]],
		);
		foreach( $arr as &$a)
		{
			$a["children"] = $this->db->name('product')->field("id as value,name as label")->where("status=1 and type={$a['value']}")->order( "id desc")->select();
		}
		echo json_encode($arr);exit;
	}
	
	public function getData($year="",$type="1",$page="1",$channel_id="",$product_id="")
	{
		$output =[];
		$user = getuserinfo();
		$where ="d.year='{$year}' and d.type={$type}";
		if( $channel_id!="" )
		{
			$where.=" and d.channel_id={$channel_id}";
		}
		if( $product_id!="" )
		{
			$aee = explode(",",$product_id);
			if(count($aee)==1)
			{
				$where.=" and p.type={$product_id}";
			}else{
				$where.=" and p.id={$aee[1]}";
			}		
		}
		$res  =$this->db->table('hellowd_data')->alias('d')->join('hellowd_product p','p.id=d.product_id')->field("d.id,d.channel_id,d.product_id,d.month,d.val,d.isreview,p.type")->where( $where )->select();
		if( !empty( $res ) )
		{
			foreach( $res as $vv )
			{
				$class ="checkbeforecolor";
				$isinput = false;
				$isedit =false;
				$editlog ="";
				if( $vv["isreview"]==1 )
				{
					$class ="checkaftercolor";
					$isinput = true;
					$vv["isreview"] = true;
				}else{
					$vv["isreview"] = false;
				}
				if( $user["ad_role"]=="financer" || $user["id"]=="13" )
				{
					$isinput = false;
				}else{
					$vv["isreview"] = true;
				}
				
				$editlog = $this->getlog($vv["id"]);
				if( $editlog )
				{
					$isedit = true;
				}
				
				if( !isset( $output[ $vv["channel_id"].$vv["product_id"] ] ) )
				{
					$output[ $vv["channel_id"].$vv["product_id"] ] = [ "channel_id"=>$vv["channel_id"],"product_id"=>$vv["product_id"],"type"=>$vv["type"],"month_".$vv["month"]=>["val"=>$vv["val"],"isinput"=>$isinput,"class"=>$class,"isreview"=>$vv["isreview"],"isedit"=>$isedit,"editlog"=>$editlog ] ];
				}else{
					$output[ $vv["channel_id"].$vv["product_id"] ]["month_".$vv["month"]] =["val"=>$vv["val"],"isreview"=>$vv["isreview"],"class"=>$class,"isinput"=>$isinput,"isedit"=>$isedit,"editlog"=>$editlog ];
				}
			}
		}
       $months =["01","02","03","04","05","06","07","08","09","10","11","12"];
	   $product  =$this->getProduct();
	   $channel = $this->getChannel();
	   $total_month=[];
	   $array_type =array(
	     "1"=>"内部",
		 "2"=>"其他",
		 "3"=>"合作",
		 "4"=>"发行"
	   );
       foreach( $output as &$v )
	   {
		   foreach( $months as $vv )
		   {
			   if( !isset( $v["month_".$vv] ) )
			   {
				   $v[ "month_".$vv ] =["val"=>"","isreview"=>false,"isedit"=>false,"editlog"=>"","class"=>"checkbeforecolor","isinput"=>false];				   
			   }						   
		   }
		   try {
			   $v["channel"] =$channel[$v["channel_id"]]["name"];
			   $v["product"] = $product[$v["product_id"]];			   
			   $v["typeName"] =$array_type[$v["type"]];
			   $v["currency"] = $channel[$v["channel_id"]]["currency"];
		   } catch (\Exception $e) {
			print_r($v);exit;
		  }
	   }
	   $totalnum=0;
	   $output = array_values($output);
	   if(!empty($output))
	   {
		   $page = ($page-1)*30;
		   $totalnum = count($output);
		   $output = array_slice($output,$page,30);
		    foreach( $output as &$v )
		   {
			   foreach( $months as $vv )
			   {				   
				$year_month = $year."-".$vv;
				$after = $this->getcurrencyrate($v[ "month_".$vv ]["val"],$v["currency"],$year_month);
				if( isset(  $total_month["month_".$vv] ) )
				{
					if( $v[ "month_".$vv ]["val"]!='' )
					{
						$total_month["month_".$vv]["val"]+=$after;
					}					
				}else{
					$total_month["month_".$vv]=["val"=>$after,"isreview"=>false,"isedit"=>false,"editlog"=>"","class"=>"checkbeforecolor","isinput"=>false];
				}							   
			   }
		   }
	   }
	   if( !empty($total_month) )
	   {
		   $total =array_merge( ["channel"=>"总计","product"=>"---","currency"=>"USD"],$total_month);
	      array_push($output,$total);
	   }      
	   $json = ["tableData"=>$output,"totalnum"=>$totalnum];
	   echo json_encode($json);exit;
	}
	
	public function selectReview($select=[],$year="",$type="",$month="")
	{
		
		if( !empty( $select ) )
		{
			$model = $this->db->name('data');
			foreach( $select  as $vv )
			{
				$model->where( ["channel_id"=>$vv["channel_id"],"product_id"=>$vv["product_id"],"year"=>$year,"type"=>$type,"month"=>$month ] )->update( [ "isreview"=>1 ] );
			}
			$this->updatesummary($year,$type);
		}
		exit("ok");
	}
	//hellogames   收款 【 HKIO-SYX】+年份+三位数编号 ，付款 【HKIO-PYX】+年份+三位数编号；
	//武汉：收款【 WHIO-SYD】+ 年份+三位数编号，  付款  【WHIO-PYD】+ 年份+三位数编号
	public function createContractNum($type="1",$s_type=""){
		if($type=="1")
		{
			$array = [
			  "1"=>"WHIO-SYD",
			  "3"=>"HKIO-SYX",
			];
		}else{
			$array = [
			  "1"=>"WHIO-PYD",
			  "3"=>"HKIO-PYX",
			];
		}
		$year="2021";
		$end_num =500;
		if( isset( $array[$s_type] ) && $array[$s_type] )
		{
			for( $i=1;$i<=500;$i++ )
			{
				if( strlen($i)==1 )
				{
					$str = $array[$s_type].$year."00".$i;
				}elseif( strlen($i)==2 )
				{
					$str = $array[$s_type].$year."0".$i;
				}else{
					$str = $array[$s_type].$year.$i;
				}
				$r = $this->db->name('contract')->field("id")->where("type={$type} and number='{$str}'")->find();
				if( empty($r) )
				{
					exit($str);
				}
			}
		}
		exit("");
	}
	
	//生成编号
	public function createNum($type="1",$s_type="")
	{
		if($type=="1")
		{
			$array = [
			  "1"=>"WHSYX",
			  "2"=>"HKSYX",
			  "3"=>"HGYX",
			  "4"=>"HTSYX",
			  "5"=>"NSYD"
			];
		}else{
			$array = [
			  "1"=>"WHPYX",
			  "3"=>"HGPYX"
			];
		}
		$year="2021";
		$end_num =500;
		if( isset( $array[$s_type] ) && $array[$s_type] )
		{
			for( $i=1;$i<=500;$i++ )
			{
				if( strlen($i)==1 )
				{
					$str = $array[$s_type].$year."00".$i;
				}elseif( strlen($i)==2 )
				{
					$str = $array[$s_type].$year."0".$i;
				}else{
					$str = $array[$s_type].$year.$i;
				}
				$r = $this->db->name('invoice')->field("id")->where("type={$type} and number='{$str}'")->find();
				if( empty($r) )
				{
					exit($str);
				}
			}
		}
		exit("");
	}
	
	//删除
	public function deleteProduct($id="")
	{
		if( $id )
		{
			$this->db->name('product')->where(["id"=>$id])->update( ["status"=>0] );
		}
		exit("ok");
	}
	
	public function download($type="")
	{
		switch( $type )
		{
			case "1":
			  $name="收款明细";
			break;
            case "2":
			  $name="付款明细";
			break;
            case "3":
			  $name="其他成本";
			break;			
		}
		
		$xlsCell  = array(
			array('channel','渠道名称'),			
			array('product','产品名称'),
			array('typeName','产品类型'),
			array('channel_type','收付类型'),
			array('currency','币种'),
		);
		$model = $this->db->name('data');
		 $months =["01","02","03","04","05","06","07","08","09","10","11","12"];
		 $year =["2017","2018","2019","2020"];
		 $product  =$this->getProduct();
	     $channel = $this->getChannel();
		 foreach( $year as $y )
		 {
			foreach( $months as $m )
			{
				$xlsCell[] =[ "year_".$y."_month_".$m,$y."年".$m."月" ];
			}			
		 }
		$xlsData=[];
        $res = $model->field("channel_id,product_id")->group("channel_id,product_id")->select();
		if( !empty($res) )
		{
			foreach( $res as $vv )
			{
				$r = $this->getonerow($model,$vv["channel_id"],$vv["product_id"],$type);
				$r["channel"] =$channel[$vv["channel_id"]]["name"];
				$r["channel_type"] = $channel[$vv["channel_id"]]["type"]==1?"收款":"付款";
				$r["typeName"] = $this->geProductType($vv["product_id"]);
			    $r["product"] = $product[$vv["product_id"]];
			    $r["currency"] = $channel[$vv["channel_id"]]["currency"];
				$xlsData[] =$r;
			}
		}
		$Index = new E(request());
        $Index->exportExcel($name,$xlsCell,$xlsData,$name,$name);	
	}
	
	private function geProductType($product_id)
	{
		$re = $this->db->name('product')->field("type")->where("id={$product_id}")->find();
		$type = $re["type"];
		$array_type =array(
	     "1"=>"内部",
		 "2"=>"其他",
		 "3"=>"合作",
		 "4"=>"发行"
	   );
		return $array_type[$type];
	}
	
	private function getonerow($model,$channel_id,$product_id,$type)
	{
		 $output =[];
		 $months =["01","02","03","04","05","06","07","08","09","10","11","12"];
		 $year =["2017","2018","2019","2020","2021"];
		 foreach( $year as $y )
		 {
			foreach( $months as $m )
			{
				$val ="";
				$t = $model->field("val")->where(["channel_id"=>$channel_id,"product_id"=>$product_id,"type"=>$type,"month"=>$m,"year"=>$y])->find();
				if( !empty($t) )
				{
					$val = $t["val"];
				}
				$output["year_".$y."_month_".$m] = $val;
			}			
		 }
		return $output; 
	}
	
	private function getlog($id)
	{
		$html ="";
		$result =$this->db->name('editlog')->where( [ "data_id"=>$id ] )->order("id asc")->select();
		if( !empty($result) )
		{
			foreach($result as $vv)
			{
				$time = date("m/d H:i",strtotime($vv["updatetime"]));
				//$res[] =["time"=>$time,"updateuser"=>$vv["updateuser"],"content"=>$vv["content"] ];
				$html.="<h5>".$time."</h5>";
				$html.="<strong class='red'>".$vv["updateuser"]."</strong>";
				$html.=" <span>".$vv["content"]."</span>";
			}
		}
		return $html;
	}
	
	public function saveData($inputData="",$selectData="",$year="",$type="")
	{
		if( !empty($selectData) )
		{
			$model = $this->db->name('data');
			$column = array_column($selectData,"hash");
			if( !empty( $inputData ) )
			{
				foreach( $inputData as $vv )
				{
					if( in_array( $vv["channel_id"]."#".$vv["product_id"],$column ) && !empty($vv) )
					{											
					   $this->updateSaveData($model,$vv,$year,$type);
					}
				}
			}
		}
		exit("ok");
	}
	
	public function reviewData($inputData="",$selectData="",$year="",$type="")
	{
		if( !empty($selectData) )
		{
			$model = $this->db->name('data');
			
			$column = array_column($selectData,"hash");
			if( !empty( $inputData ) )
			{
				foreach( $inputData as $vv )
				{
					if( in_array( $vv["channel_id"]."#".$vv["product_id"],$column ) && !empty($vv) )
					{											
					   $this->updateSaveData($model,$vv,$year,$type,1);
					}
				}
			}
			
			foreach( $selectData as $vv )
			{
				$model->where( ["channel_id"=>$vv["channel_id"],"product_id"=>$vv["product_id"],"year"=>$year,"type"=>$type ] )->update( [ "isreview"=>1 ] );
			}
			
			//更新汇总的
			$this->updatesummary($year,$type);
		}
	}
	
	public function reviewData1($channel_id="",$product_id="",$month="",$year="",$type="")
	{
		$this->db->name('data')->where( ["channel_id"=>$channel_id,"product_id"=>$product_id,"year"=>$year,"type"=>$type,"month"=>$month ] )->update( [ "isreview"=>1 ] );
        $this->updatesummary($year,$type);
		exit("ok");
	}
	
	//更新数据
	private function updateSaveData($model,$row,$year,$type,$review="0")
	{
		$months =["01","02","03","04","05","06","07","08","09","10","11","12"];
		foreach( $months as $m )
		{
			$month = "month_".$m;
			if( isset($row[$month]) && $row[$month]!="" )
			{
				$r = $model->where( ["channel_id"=>$row["channel_id"],"product_id"=>$row["product_id"],"type"=>$type,"year"=>$year,"month"=>$m ] )->find();
				if( empty( $r ) )
				{
					$model->insert( ["channel_id"=>$row["channel_id"],"product_id"=>$row["product_id"],"type"=>$type,"year"=>$year,"month"=>$m,"val"=>$row[$month] ] );
				}else{
					if( $r["isreview"]=="1")
					{
						$this->log($r["id"],$r["val"],$row[$month],$type);
					}					
					$model->where( ["id"=>$r["id"] ] )->update( [ "val"=>$row[$month] ] );
				}
			}
		}
		return;
	}
	
	//修改数据 新
	public function saveData1($oldval="",$newval="",$channel_id="",$product_id="",$month="",$year="",$type="",$isreview="0")
	{
		$model = $this->db->name('data');
		$r = $model->where( ["channel_id"=>$channel_id,"product_id"=>$product_id,"type"=>$type,"year"=>$year,"month"=>$month ] )->find();
		if( empty( $r ) )
		{
			$model->insert( ["channel_id"=>$channel_id,"product_id"=>$product_id,"type"=>$type,"year"=>$year,"month"=>$month,"val"=>$newval,"isreview"=>$isreview ] );
		}else{
			if( $r["isreview"]=="1")
			{
				$this->log($r["id"],$oldval,$newval,$type);
				$isreview =1;
			}					
			$model->where( ["id"=>$r["id"] ] )->update( [ "val"=>$newval,"isreview"=>$isreview ] );
		}
		exit("ok");
	}
	
	//更正记录
	private function log($data_id,$oldval,$newval,$type)
	{
		$content = $oldval."修改为".$newval;
		$this->db->name('editlog')->insert( ["data_id"=>$data_id,"updateuser"=>$this->_adminname,"type"=>$type,"content"=>$content ] );
	}
	
	private function getProduct()
	{
		$res =[];
		$result = $this->db->name('product')->field("id,name")->select();
		if( !empty($result) )
		{
			foreach( $result as $vv )
			{
				$res[$vv["id"]] = $vv["name"];
			}
		}
		return $res;
	}
	
	private function getChannel()
	{
		$res =[];
		$result = $this->db->name('channel')->field("id,name,type,currency")->select();
		if( !empty($result) )
		{
			foreach( $result as $vv )
			{
				$res[$vv["id"]] =[ "name"=>$vv["name"],"currency"=>$vv["currency"],"type"=>$vv["type"] ];
			}
		}
		return $res;
	}
	
	public function addRow($channel="",$year="",$type="",$product="")
	{
		if( $channel && $type && $product )
		{

			$r = $this->db->name('data')->where( ["channel_id"=>$channel,"product_id"=>$product,"type"=>$type,"year"=>$year ] )->find();
			if( empty( $r ) )
			{
				$this->db->name('data')->insert( ["channel_id"=>$channel,"product_id"=>$product,"type"=>$type,"year"=>$year,"month"=>"01"] );
			}
			exit("ok");
		}
		exit("fail");
	}
	
	public function payment()
	{
		 $product = $this->db->name('product')->field("id,name")->where("status=1")->order( "id desc")->select();
	     $channel = $this->db->name('channel')->field("id,name")->where("type=2")->order( "id desc")->select();	  
		  $assign =array(
			 "product"=>$product,
			 "channel"=>$channel,
			 "role"=>getuserrole()
		  );
		 return $this->fetch('payment',$assign);
	}
	
	public function other()
	{
		  $product = $this->db->name('product')->field("id,name")->where("status=1")->order( "id desc")->select();
	      $channel = $this->db->name('channel')->field("id,name")->where("type=2")->order( "id desc")->select();	  
		  $assign =array(
			 "product"=>$product,
			 "channel"=>$channel,
			 "role"=>getuserrole()
		  );
		 return $this->fetch('other',$assign);
	}
	
	public function monthsummary()
	{
		$channel = $this->db->name('channel')->field("id,name")->order( "id desc")->select();
		return $this->fetch('monthsummary',["channel"=>$channel]);
	}
	
	public function postReceipt($type="",$year="",$month="",$selectData=""){
		
		if( !empty($selectData) )
		{
			$db = $this->db->name('summary');
			foreach($selectData  as $vv )
			{
				$db->where( ["year"=>$year,"type"=>$type,"channel_id"=>$vv,"month"=>$month ])->update( ["isreceipt"=>1] );
			}
		}
		exit("ok");
	}
	
	public function getsummary($year="",$type="",$channel_id="")
	{
		$channel = $this->getChannel();
		$db = $this->db->name('summary');
		$output =[];
		$where =" year='{$year}' and type={$type}";
		if( $channel_id!="" && $channel_id!="undefined")
		{
			$where .=" and channel_id={$channel_id}";
		}
		$res = $db->field("channel_id,round(sum(val),4) as val")->where($where)->group("channel_id")->select();
		$total =[];
		if( !empty($res) )
		{
			foreach( $res as $vv )
			{
				 $months =["01","02","03","04","05","06","07","08","09","10","11","12"];
				 $arr =[];
				 $arr["channel_id"] = $vv["channel_id"];
				 $arr =array_merge($arr,$channel[$vv["channel_id"]]);
				 foreach( $months as $m )
				 {
					 $r = $db->where( ["year"=>$year,"type"=>$type,"channel_id"=>$vv["channel_id"],"month"=>$m ])->find();
					 if( !empty($r) )
					 {
						 $currentmonth =ltrim(date("m",time()),"0");
						 $month = ltrim($m,"0");
						 if( ($r["isreceipt"]=="0") && ($month+3<=$currentmonth) )
						 {
							$r["isreceipt"] =2; 
						 }
						 $year_month = $year."-".$m;
						 $aftermonry = $this->getcurrencyrate($r["val"],$arr["currency"],$year_month);

						 $arr["month_".$m] =["isreceipt"=>$r["isreceipt"],"val"=>round($r["val"],4)];
					 }else{
						 $arr["month_".$m] =["isreceipt"=>0,"val"=>""];
						 $aftermonry =0;
					 }
					 if( !isset( $total["month_".$m] ) )
					 {
						 $total["month_".$m] =["isreceipt"=>0,"val"=>$aftermonry];
					 }else{
						 $total["month_".$m]["val"]+=$aftermonry;
					 }
				 }				 
				 $output[] =$arr;
			}
			$total["name"] ="合计";
			$total["currency"] ="USD";
			array_push($output,$total);
		}
		echo json_encode( array_values($output));exit;
	}
	
	private function getcurrencyrate($val,$s,$year_month)
	{
		if( !$val )
		{
			return $val;
		}
		/* $es =array(
		    "USD"=>"1",
			"HKD"=>"0.126984126984127",
			"TWD"=>"0.032258064516129",
			"CNY"=>"0.1470588235294118"
		); */
		$rate = $this->getmonthrate($s,$year_month);
		return round( $val*$rate,4);
	}
	
	private function getmonthrate($currency,$month)
	{
		if( $currency=="USD" )
		{
			return 1;
		}
		$res = $this->db->name('rate')->where("currency='{$currency}' and month='{$month}'")->find();
		if( !empty( $res ) )
		{
			return round( 1/$res["val"],15);
		}
		$res = $this->db->name('rate')->where("currency='{$currency}' and month='0'")->find();
		return round( 1/$res["val"],15);
	}
	
	public function updatesummary($year="",$type="")
	{
		$res = $this->db->name('data')->field("channel_id,month,sum(val) as val")->where(["year"=>$year,"type"=>$type,"isreview"=>1])->group("channel_id,month")->select();
		$db = $this->db->name('summary');
		if( !empty($res) )
		{
			foreach( $res as $vv )
			{
				$r = $db->where([ "channel_id"=>$vv["channel_id"],"month"=>$vv["month"],"year"=>$year,"type"=>$type ])->find();
				if( !empty($r) )
				{
					$db->where( ["id"=>$r["id"] ] )->update( [ "val"=>$vv["val"] ] );
				}else{
					$db->insert( [ "channel_id"=>$vv["channel_id"],"month"=>$vv["month"],"year"=>$year,"type"=>$type,"val"=>$vv["val"] ] );
				}
			}
		}
		return;
	}
	
	//添加编辑产品
	public function postProduct()
	{
		$data  =input("post.");
		$id  =$data["id"];
		$data["updatetime"] = date("Y-m-d H:i:s",time() );
		$data["updateuser"] =$this->_adminname;
		if( $id>0 )
		{
			$result = $this->db->name('product')->where(["id"=>$id])->update($data);
		}else{
			unset($data["id"]);
			$result = $this->db->name('product')->insertGetId($data);
		}
		exit($result!==false?"ok":"fail");
	}
	
	//添加编辑产品
	public function postChannel()
	{
		$data  =input("post.");
		$id  =$data["id"];
		$data["updatetime"] = date("Y-m-d H:i:s",time() );
		$data["updateuser"] =$this->_adminname;
		if( $id>0 )
		{
			$result = $this->db->name('channel')->where(["id"=>$id])->update($data);
		}else{
			unset($data["id"]);
			$result = $this->db->name('channel')->insertGetId($data);
		}
		exit($result!==false?"ok":"fail");
	}
	
	public function editProduct($id="")
	{
		if( !$id )return;
		$res = $this->db->name('product')->field("id,name,type,partner,pd_class")->find($id);
		$res["type"] = (string)$res["type"];
		$res["partner"] = (string)$res["partner"];
		$res["pd_class"] = (string)$res["pd_class"];
		echo json_encode($res);exit;
	}
	
	public function editChannel($id="")
	{
		if( !$id )return;
		$res = $this->db->name('channel')->field("id,name,type,desc,currency")->find($id);
		$res["type"] = (string)$res["type"];
		echo json_encode($res);exit;
	}
	
	//渠道汇总
	public function channelsummary($year="2021",$channel_id="")
	{
		 $channel = $this->db->name('channel')->field("id,name")->order( "id desc")->select();
		 return $this->fetch('channelsummary',["userid"=>Session::get('admin_userid'),"year"=>$year,"channel_id"=>$channel_id,"channel"=>$channel]);
	}
	
	public function payment_invoice($year="2021",$channel_id="")
	{
		 $channel = $this->db->name('channel')->field("id,name")->order( "id desc")->select();
		 return $this->fetch('payment_invoice',["userid"=>Session::get('admin_userid'),"year"=>$year,"channel_id"=>$channel_id,"channel"=>$channel]);
	}
	
	//删除invice
	public function deleteinvoice($id="")
	{
		if($id)
		{
			$result = $this->db->name('invoice')->where( ["id"=>$id] )->delete();
			exit($result!==false?"ok":"fail");
		}
		exit("fail");
	}
	
	//删除合同
	public function delete_contract($id=""){
		if($id)
		{
			$result = $this->db->name('contract')->where( ["id"=>$id] )->delete();
			exit($result!==false?"ok":"fail");
		}
		exit("fail");
	}
	
	//添加invoice
	public function addinvoice()
	{
		$data =input("post.");
		$result = false;
		if( !empty($data) )
		{
			$id  =$data["id"];
			unset($data["id"]);
			if( $id>0 )
			{
				$result = $this->db->name('invoice')->where( ["id"=>$id] )->update($data);
			}else{
				$result =  $this->db->name('invoice')->insert($data);
			}
		}
		exit($result!==false?"ok":"fail");
	}
	
	//获取invoice 
	public function getInvoiceData($type="1",$year="2021",$channel_id="")
	{
		$where =["type"=>$type,"year"=>$year];
		$tag =true;
		if($year==date("Y"))
		{
			$tag =false;
		}
        	
		if($channel_id!='')
		{
			$where["channel_id"] = array('in',explode(",",$channel_id));
			$tag =false;

		}
		/* if($tag)
		{
			//缓存数据
			$mem = new \Memcache();
			$mem->connect("127.0.0.1", 11211);
			$key ="invoice_{$type}_{$year}";
			$res = $mem->get($key);
			
			if ($res) {
				echo $res;exit;
			}
		} */
		$res  =$this->db->name('invoice')->where($where)->order("id asc")->select();
		if( !empty($res) )
		{
			 $channel = $this->getChannel();
			foreach( $res as &$vv )
			{
				$vv["s_type"] = (string)$vv["s_type"];
				$vv["channel"] =$channel[$vv["channel_id"]]["name"];
                $vv["currency"] = $channel[$vv["channel_id"]]["currency"];	
			}
		}
		/* if($tag)
		{			
			$mem->set($key,json_encode($res));
		} */
		
		echo json_encode($res);exit;
	}
	
	//word 模板生成
	public function createWord($id="",$date="")
	{
		
		if( $id )
		{
			$res  =$this->db->name('invoice')->find($id);
			$templateProcessor = new TemplateProcessor('static/invoice.docx');
			if( strtotime($date)<strtotime("2019-06-01 00:00:00") )
			{
				$com = $this->getcompany( $res['s_type'] );	
			}else{
				$com = $this->getnewcompany( $res['s_type'] );
			}
			$bank = $this->getbank($res['s_type']);
			$data = $this->getendate($date);
			$da = $this->getchannelbank($res['channel_id']);
			$templateProcessor->setValue('a1',$com['a1']);
			$templateProcessor->setValue('a2',$com['a2']);
			$templateProcessor->setValue('a3',$com['a3']);
			$templateProcessor->setValue('a4',$com['a4']);
			$templateProcessor->setValue('date',$data["date"]);  //date
			$templateProcessor->setValue('number',$res["number"]);
			$templateProcessor->setValue('b1',$bank['b1']);
			$templateProcessor->setValue('b2',$bank['b2']);
			$templateProcessor->setValue('b3',$bank['b3']);
			$templateProcessor->setValue('b4',$bank['b4']);
			$templateProcessor->setValue('b5',$bank['b5']);
			$templateProcessor->setValue('c1',$da['c1']);
			$templateProcessor->setValue('c2',$da['c2']);
			$templateProcessor->setValue('c3',$da['c3']);
			$templateProcessor->setValue('c4',$da['c4']);
			$templateProcessor->setValue('cy',$da['currency']);
			$templateProcessor->setValue('money',$res["money"] );
			$templateProcessor->setValue('desc',$data["desc"] );
			$templateProcessor->setValue('footer',$this->getfooter($res["s_type"]) );
			$templateProcessor->saveAs('static/invoice/invoice.docx');
		    exit("http://".$_SERVER['SERVER_NAME']."/static/invoice/invoice.docx");
		}		
	}
	
	private function getendate($date)
	{
		$s =date('d,Y',strtotime($date));
		$m =date('M',strtotime($date));
		$y =date('Y',strtotime($date));
		
		return ["date"=>$m." ".$s,"desc"=>$m." ".$y." advertising revenue"];
	}
	
	private function getfooter($type)
	{
		$arr=array(
		    "1"=>"Hello World",
			"2"=>"Adonads",
			"3"=>"Hello Games",
			"4"=>"Hot Games",
			"5"=>"XLW"
		);
		return $arr[$type];
	}
	
	private function getcompany($type)
	{
		$arr=array(
		    "1"=>[ "a1"=>"Wuhan Hello World Network Technology Co., Ltd.","a2"=>"R2803 T1 B,  No.355 Guanshan Road,","a3"=>"Wuhan,China","a4"=>"" ],
			"2"=>[ "a1"=>"ADONADS TECHNOLOGY CO., LIMITED","a2"=>"Unit 04,7/F Bright Way Tower,","a3"=>"No.33 Mong Kok Road,","a4"=>"Kowloon, HK" ],
			"3"=>[ "a1"=>"HELLO GAMES CO., LIMITED","a2"=>"ROOM A1, 11/F WINNER BUILDING","a3"=>"36 MAN YUE STREET, HUNG HOM,","a4"=>"KOWLOON, HONG KONG" ],
			"4"=>[ "a1"=>"HOT GAMES CO., LIMITED","a2"=>"UNIT 5, 27/F RICHMOND,","a3"=>"COMM BLDG 109 ARGYLE ST,","a4"=>"MONGKOK, HONG KONG" ],
			"5"=>[ "a1"=>"liuhanshi","a2"=>"Unit 1202, David House, 8-20 Nanking Street, ","a3"=>"Jordan, Kowloon, Hong Kong","a4"=>"" ]
		);
		return $arr[$type];
	}
	
	//新
	private function getnewcompany($type)
	{
		$arr=array(
		    "1"=>[ "a1"=>"Wuhan Hello World Network Technology Co., Ltd.","a2"=>"R2803 T1 B,  No.355 Guanshan Road,","a3"=>"Wuhan,China","a4"=>"" ],
			"2"=>[ "a1"=>"ADONADS TECHNOLOGY CO., LIMITED","a2"=>"Unit 04,7/F Bright Way Tower,","a3"=>"No.33 Mong Kok Road,","a4"=>"Kowloon, HK" ],
			"3"=>[ "a1"=>"HELLO GAMES CO., LIMITED","a2"=>"ROOM A1, 11/F WINNER BUILDING","a3"=>"36 MAN YUE STREET, HUNG HOM,","a4"=>"KOWLOON, HONG KONG" ],
			"4"=>[ "a1"=>"HOT GAMES CO., LIMITED","a2"=>"ROOM A1, 11/F WINNER BUILDING,","a3"=>"36 MAN YUE STREET, HUNG HOM,","a4"=>"KOWLOON, HONG KONG" ],
			"5"=>[ "a1"=>"liuhanshi","a2"=>"Unit 1202, David House, 8-20 Nanking Street, ","a3"=>"Jordan, Kowloon, Hong Kong","a4"=>"" ]
		);
		return $arr[$type];
	}
	
	private function getbank($type)
	{
		$arr=array(
		    "1"=>[ "b1"=>"Wuhan Hello World Network Technology Co., Ltd.","b2"=>"China Merchants Bank, Head Office, Shenzhen, P.R.China","b3"=>"China Merchants Bank Tower No.7088, Shennan Boulevard, Shenzhen, China","b4"=>"127907331632301","b5"=>"CMBCCNBSXXX" ],
			"2"=>[ "b1"=>"ADONADS TECHNOLOGY CO., LIMITED","b2"=>"HANG SENG BANK LIMITED","b3"=>"83 Des Voeux Road Central Hong Kong","b4"=>"255 841 330 883","b5"=>"HASEHKHH ,Bank Code: 024" ],
			"3"=>[ "b1"=>"HELLO GAMES CO., LIMITED ","b2"=>"HONGKONG AND SHANGHAI BANKING CORPORATION LIMITED","b3"=>"1 Queen's Road Central, Hong Kong SAR, China","b4"=>"817840374838","b5"=>"HSBCHKHHHKH" ],
			"4"=>[ "b1"=>"HOT GAMES CO., LIMITED","b2"=>"HONGKONG AND SHANGHAI BANKING CORPORATION LIMITED","b3"=>"1 Queen's Road Central, Hong Kong SAR, China","b4"=>"817-233398838","b5"=>"HSBCHKHHHKH" ],
			"5"=>[ "b1"=>"Hanshi Liu(刘汉仕)","b2"=>"China Merchants Bank, Head Office, Shenzhen, P.R.China","b3"=>"China Merchants Bank Tower No.7088, Shennan Boulevard, Shenzhen, China","b4"=>"6214832704035521","b5"=>"CMBCCNBSXXX" ]			
		);
		return $arr[$type];
	}
	
	private function getchannelbank($channel_id)
	{
		$r = $this->db->field('name,currency')->name('channel')->find($channel_id);
		$name = isset($r['name'])?$r['name']:"";      
		$data =[ "c1"=>"","c2"=>"","c3"=>"","c4"=>"","currency"=>$r["currency"] ];
		$arr=array(
		    "Facebook"=>[ "c1"=>"Facebook Ireland Limited","c2"=>"4 Grand Canal Square","c3"=>"Grand Canal Harbour","c4"=>"Dublin, 2 Ireland" ],
            "Admob"=>[ "c1"=>"Google Asia Pacific Pte. Ltd.","c2"=>"8 Marina View","c3"=>"Asia Square 1 #30-01","c4"=>"Singapore 018960" ],
            "GooglePlay"=>[ "c1"=>"Google Payment Corp.","c2"=>"1600Amphitheatre Parkway","c3"=>"Mountain View,CA 94043","c4"=>"" ],
            "AppleStore"=>[ "c1"=>"Apple Inc","c2"=>"","c3"=>"","c4"=>"" ],
            "Upltv"=>["c1"=>"UPLTV Co.,Limited","c2"=>"SUITE 603，6/F LAWS COMM PLAZA 788 CHEUNG SHA WAN RD","c3"=>"KLN HONG KONG","c4"=>""],
            "Applovin"=>["c1"=>"AppLovin Corporation","c2"=>"849 High Street","c3"=>"Palo Alto CA 94301","c4"=>"United States"],
            "Ironsource"=>["c1"=>"IronSource Mobile Ltd.","c2"=>"121 Menachem Begin Road, Azrieli Sarona Tower,","c3"=>"Tel Aviv, Israel  6701318","c4"=>""],
            "Vungle"=>["c1"=>"Vungle Inc","c2"=>"185 Clara Street,","c3"=>"San Francisco, CA 94107","c4"=>""],
            "Unity"=>["c1"=>"Unity Technologies Finland Oy","c2"=>"Kaivokatu 8 B","c3"=>"FI - 00100 Helsinki","c4"=>"FINLAND VAT number: FI21849716"],
            "Tapjoy"=>["c1"=>"Tapjoy Inc","c2"=>"111 Sutter Street 12th Floo","c3"=>"San Francisco CA 94104","c4"=>"United States"],
			"Tiktok"=>["c1"=>"TikTok Pte. Ltd.","c2"=>"201 Henderson Road","c3"=>"#06-22 Apex @ Henderson Singapore","c4"=>"159545"],
			"Adcolony"=>["c1"=>"AdColony Singapore PTE. Ltd","c2"=>"8 Marina View, #14-09 Asia Square Tower 1,","c3"=>"Singapore 018960,","c4"=>"Singapore GST Reg. No. 201219906M"],
			"Mintegral"=>["c1"=>"Mintegral International Limited","c2"=>"Room 701A, 7/F.,Officeplus @prince Edward,","c3"=>"794-802 Nathan Road, ","c4"=>"Kowloon, Hong Kong"],
			"Mobvista"=>["c1"=>"Mobvista International Technology Limited","c2"=>"Room 701A, 7/F.,OfficePlus @prince Edward","c3"=>"794-802 Nathan Road,","c4"=>"Kowloon, Hong Kong"],
			"Bytedance"=>["c1"=>"Name: Bytedance Pte.Ltd","c2"=>"Registered Address: 201 Henderson Road #06-22 Apex @ Henderson Singapore 159545","c3"=>"Mailing Address: WeWork c/o Bytedance Pte. Ltd., 8 Cross Street Singapore, 048424, Singapore","c4"=>""],
            "Mycard"=>["c1"=>"Soft-world International CORP.","c2"=>"No.99-10, Sec. 2, Nangang Rd.,","c3"=>"Nangang Dist., Taipei City 115,Taiwan (R.O.C.)","c4"=>"TEL : 02-27889188 #349   Fax: 02-27866107"],
			"Mopub"=>["c1"=>"TWITTER INC.","c2"=>"1355 Market Street","c3"=>"Suite 900 San Francisco CA 94103 United States","c4"=>""],
			"Fyber"=>["c1"=>"Fyber Monetization","c2"=>"4 Hapsagot St.","c3"=>"Petach Tikva 4951447 Israel","c4"=>""],
            "Inmobi"=>["c1"=>"InMobi Monetization","c2"=>"InMobi, 7th Floor, Delta Block,","c3"=>"Embassy Tech Square, Varthur Hobli,","c4"=>"Kadubeesanahalli, Bangalore - 560103"]			
		);
		foreach( $arr as $key=>$vv )
		{
			
			if( preg_match("/{$key}/",$name) )
			{
				
				$data = $vv;
				$data["currency"] =$r["currency"];
				return $data;
			}
		}
		return $data;
	}
	
	//edit
	public function editInvoice($id="")
	{
		if( $id )
		{
			$res  =$this->db->name('invoice')->find($id);
			$res["s_type"] = (string)$res["s_type"];
		   echo json_encode($res);exit;
		}
	}
	
	//汇率设置
	public function rate()
	{
		return $this->fetch('rate');
	}
	
	public function rate_json($currency="CNY")
	{
	    $res = $this->db->name('rate')->where("currency='{$currency}' and month!=0")->order('month asc')->select();
		$r = $this->db->name('rate')->where("currency='{$currency}' and month=0")->find();
		$defaultRate = isset( $r["val"] )?$r["val"]:"0.00";
		echo  json_encode( ["list"=>$res,"defaultRate"=>$defaultRate] );exit;
	}
	
	public function partner_json()
	{
		$res = $this->db->name('partner')->order('id asc')->select();
		echo  json_encode( ["list"=>$res] );exit;
	}
	
	public function updaterate()
	{
		$data = input('post.');
		if( isset($data["id"]) && $data["id"] )
		{
			$this->db->name('rate')->where("id",$data["id"])->update( ["val"=>$data["val"]] );
		}else{
			$this->db->name('rate')->insert($data);
		}
		exit("ok");
	}
	public function updatepartner()
	{
		$data = input('post.');
		if( isset($data["id"]) && $data["id"] )
		{
			$this->db->name('partner')->where("id",$data["id"])->update( ["name"=>$data["name"]] );
		}else{
			$this->db->name('partner')->insert($data);
		}
		exit("ok");
	}	
	
	public function saveDefault($currency="",$val="")
	{
		$this->db->name('rate')->where("currency='{$currency}' and month='0'" )->update( [ "val"=>$val ] );
		exit("ok");
	}
	
	public function productlist($pd_class="all",$type="all")
	{
		$where="status=1";
		if( $pd_class!="all" )
		{
			$where.= " and pd_class={$pd_class}";
		}
		if( $type!="all" )
		{
			$where.= " and type={$type}";
		}
		$list =$this->db->name('product')
                 ->where($where)		
				 ->order ( "id desc" )
				 ->paginate(50,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "pd_class"=>$pd_class,"type"=>$type ]
								] );								
	    $this->assign('list',$list);
		$this->assign('pd_class',$pd_class);
		$partnerlist = $this->db->name('partner')->order('id asc')->select();
		$this->assign('type',$type);
		$this->assign('partnerlist',$partnerlist);
		return $this->fetch('productlist');
	}
	
	public function channellist($type="1",$id="")
	{
		$where="";
		if( $type!="all" )
		{
			$where="type={$type}";	
		}
		
		if( $id!="" && $id!='undefined')
		{
			$where="id={$id}";	
		}
		$alllist =$this->db->name('channel')->select();
		$list =$this->db->name('channel')
                 ->where($where)	
				 ->order ( "id desc" )
				 ->paginate(50,false,[
								 'type'     => 'bootstrap',
								 'var_page' => 'page',
								 'query'=>[ "type"=>$type,"id"=>$id ]
								] );								
	    $this->assign('list',$list);
		$this->assign('alllist',$alllist);
		$this->assign('type',$type);
		$this->assign('id',$id);
		return $this->fetch('channellist');
	}
	
	//合作商
	public function partnerlist()
	{
		return $this->fetch('partnerlist');
	}
	
	//新增业务合同 收款
	public function contract(){
		 $channel = $this->db->name('channel')->field("id,name")->where("type=1")->order( "id desc")->select();	  
		  $assign =array(
			 
			 "channel"=>$channel,
			 "role"=>getuserrole()
		  );
		  return $this->fetch('contract',$assign);
	}
	
	//新增业务合同 付款
	public function payment_contract(){
		 $channel = $this->db->name('channel')->field("id,name")->where("type=2")->order( "id desc")->select();	  
		  $assign =array(
			 
			 "channel"=>$channel,
			 "role"=>getuserrole()
		  );
		  return $this->fetch('payment_contract',$assign);
	}
	
	public function task_notice(){
		$emailList =array(
		    "喻久港"=>"yujiugang@hellowd.net",
			"汤文娟"=>"tangwenjuan@hellowd.net",
			"熊奥迪"=>"xiongaodi@hellowd.net",
			"万美玲"=>"wanmeiling@hellowd.net",
			"李碧莲"=>"libilian@hellowd.net",
			"郝娇娇"=>"haojiaojiao@hellowd.net",
			"刘滋"=>"liuzi@hellowd.net",
			"吴柯庆"=>"wukeqing@hellowd.net"
		);
		$where = "year=2021 and is_complete=0";
		$res = $this->db->name('contract')->field("*,IF(s_type=1,'武汉','Hellogames') as name")->where($where)->select();
		if(!empty($res))
		{
			foreach($res as $v)
			{
				$end_time = strtotime($v["end_date"]);
				$last_month_time = $end_time-30*24*3600;
				if($last_month_time<time() && time()<$end_time)
				{
					$title ="【GameBrain】合同即将到期提醒";
					$html = "您的合同编号<strong style='color:red;'>{$v['number']}</strong>,{$v['contract_content']},即将到期，请您及时关注!";
					send_mail( $emailList[$v['manager']],$emailList[$v['manager']],$title,$html,"GameBrain" );
					send_mail( "finance@hellowd.net","finance@hellowd.net",$title,$html,"GameBrain" );
				}elseif(time()>$end_time){
					$this->db->name('contract')->where(["id"=>$v['id']])->update(["is_complete"=>1]);
				}
			}
		}
		exit("ok");
	}	
	//保存更新
	public function saveContract(){
		$data = input("post.");
		$result = false;
		if( !empty($data) )
		{
			$id  =$data["id"];
			unset($data["id"],$data["file_list1"],$data["file_list2"]);
			if(isset($data["name"]))
			{
				unset($data["name"]);
			}
			if(isset($data["group_name"]))
			{
				unset($data["group_name"]);
			}
			if(isset($data["channel_name"]))
			{
				unset($data["channel_name"]);
			}
			if(isset($data["expire_show"]))
			{
				unset($data["expire_show"]);
			}
			$data["channel_id"] = implode(",",$data["channel_id"]);
			if( $id>0 )
			{
				$result = $this->db->name('contract')->where( ["id"=>$id] )->update($data);
			}else{				
				$result =  $this->db->name('contract')->insert($data);
			}
		}
		exit($result!==false?"ok":"fail");
	}
	
	//获取合同列表
	public function get_contract_data($year="2021",$channel_id="",$type="1"){
							
		$groupList =[
		  "1"=>"商业化",
		  "2"=>"用户增长部",
		  "3"=>"中台部",
		  "4"=>"研发部",
		  "5"=>"产品部",
		];
		$where = "year={$year} and type={$type}";
		if($channel_id!="")
		{
			$where.=" and channel_id={$channel_id}";
		}
		$channel = $this->getChannel();
		$res = $this->db->name('contract')->field("*,IF(s_type=1,'武汉','Hellogames') as name")->where($where)->order( "id asc")->select();
		if(!empty($res))
		{
			foreach($res as &$v)
			{
				$v["s_type"] = (string)$v["s_type"];
				$v["group"] = (string)$v["group"];
				$v["group_name"] = $groupList[$v["group"]];
				$v["channel_id"] = explode(",",$v["channel_id"]);
				$v["channel_name"]="";
				if(!empty($v["channel_id"]))
				{
					foreach($v["channel_id"] as $vv)
					{
						$v["channel_name"].=$channel[$vv]["name"].",";
					}
				}
				$expire_show = "";
				$end_time = strtotime($v["end_date"]);
				$last_month_time = $end_time-30*24*3600;
				if($last_month_time<time() && time()<$end_time)
				{
					$expire_show = "即将到期";
				}elseif(time()>$end_time){
					$expire_show = "已到期";
				}
				$v["expire_show"] = $expire_show;
				$v["channel_name"] = trim($v["channel_name"],",");
				$v["file_list1"] = $this->db->name('contract_file')->where(["contract_id"=>$v["id"],"type"=>1])->select();
				$v["file_list2"] = $this->db->name('contract_file')->where(["contract_id"=>$v["id"],"type"=>2])->select();
			}
		}
		echo  json_encode( ["list"=>$res] );exit;
	}
	
	//上传文件
	public function upload($contract_id="",$type="1")
	{
		/* if(!$contract_id)
		{
			return json_encode( ["status"=>400,"url"=>"","message"=>"参数错误" ] );
		} */
		$file = request()->file("upload");
		$filename = $file->getInfo()["name"];
		$dir ="contract";
		if($file)
		{  
			$path=ROOT_PATH . 'public' . DS . 'uploads' . DS . $dir;
		    $info = $file->validate(['size'=>1024*1024*50,'ext' => 'pdf,doc,jpg,png,jpeg'])->move($path);
			if($info)
			{
				$url="/uploads/{$dir}/".$info->getSaveName();
				if($type<3)
				{
					$data =array(
					   "contract_id"=>$contract_id,
					   "type"=>$type,
					   "filename"=>$filename,
					   "file_url"=>$url
					);
					$this->db->name('contract_file')->insert($data);
				}				
				return json_encode( ["status"=>200,"url"=>$url,"type"=>$type ] );
			}			 
		}
		return json_encode( ["status"=>400,"message"=>$file->getError() ] );
	}
}
