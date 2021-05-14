<?php

namespace app\api\controller;

use think\Db;
use \think\Request;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods:GET,POST,OPTIONS,DELETE,PUT");

class Purchase
{

    private $app_config = array(
        "idlefarm" => 135,
        "clashzombie" => 158,
        "relicequest" => 161,
        "fantidabing" => 160,
        "idlegymsports" => 166,
        "zombieinc" => 170,
        "BlockMaster" => 172,
		"tankrio"=>185,
		"oretycoon"=>107,
		"com.linglong.jouney"=>112,
		"HelixFall"=>68
    );

    public function index($appid = "", $income = "", $orderid = "", $country = "")
    {
        if (!$appid || !$income || !$orderid || !$country) {
            echo json_encode(["code" => 200]);
            exit;
        }
        try {
            Db::name('purchase')->insert(["appid" => $appid, "income" => $income, "orderid" => $orderid, "country" => $country]);
        } catch (\Exception $e) {
            echo json_encode(["code" => 200]);
            exit;
        }
        echo json_encode(["code" => 200]);
        exit;
    }

    function csvJSON($content)
    {

        $lines = array_map('str_getcsv', file($content));

        $result = array();
        $headers;
        if (count($lines) > 0) {
            $headers = $lines[0];
        }

        for ($i = 1; $i < count($lines); $i++) {
            $obj = $lines[$i];
            $result[] = array_combine($headers, $obj);
        }
        return $result;
    }

    public function bb()
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . "/Reporter/price1.csv";
        $content = $this->csvJSON($file);
        $currency = "USD";
        if (!empty($content)) {
            foreach ($content as $v) {
                $r = Db::name('purchase_price')->where(["currency" => $currency, "money" => $v[$currency]])->find();
                if (empty($r)) {
                    Db::name('purchase_price')->insert(["currency" => $currency, "money" => $v[$currency], "usd_money" => $v["USD"]]);
                }
            }
        }
        exit("ok");
    }

    /**
     * 更新内购汇率
     */
    public function exchange_rate(){
        $url = "https://open.exchangerate-api.com/v6/latest";
        $result = json_decode(curl($url), true);
        if ($result['result']=='success'){
            $update_time = $result['time_last_update_unix'];
            $rates = $result['rates'];
            $data = [];
            foreach ($rates as $key => $val){
                $data[] = [
                    'currency' => $key,
                    'exchange_rate' => $val,
                    'update_time' => $update_time,
                ];
            }
            if ($data){
                Db::name('purchase_exchange_rate')->where(['update_time'=>$update_time])->delete();
                Db::name('purchase_exchange_rate')->insertAll($data);
                exit("update exchange_rate ". $update_time . " OK !");
            }
        }
        exit("update exchange_rate ERROR !!! ");
    }

    /**
     * 插入google内购数据
     * @param int $app_id
     * @param int $time
     * @param int $units
     * @param int $money
     * @param int $price
     * @param string $country
     * @param string $currency
     * @param string $product_name
     * @param string $product_id
     * @param string $package
     * @param int $total_usd_money
     */
    public function insertGooglePlay($app_id=0,$time=0,$units=1,$money=0,$price=0,$country='',$currency='',$product_name='',$product_id='',$package='',$total_usd_money=0)
    {
        if (!$app_id||!$product_id||!$package){
            exit("error");
        }
        $insert_data = [
            "date" => date("Y-m-d", $time-15*3600),//PDT
            "product_name" => $product_name,
            "app_id" => $app_id,
            "units" => $units,
            "money" => $money,
            "price" => $price,
            "country" => $country,
            "currency" => $currency,
            "package" => $package,
            "product_id" => $product_id,
            "total_usd_money" => $total_usd_money*$units
        ];
        Db::name('purchase_details')->insert($insert_data);
        exit("ok");
    }


    public function report($vendor='87962023',$date = "")
    {

        if ($date == "") {
            $date = date("Ymd", strtotime("-2 day"));
        }
        $file = $_SERVER['DOCUMENT_ROOT'] . "/Reporter/S_D_{$vendor}_{$date}.txt";
        $fp = fopen($file, "r");
        $i = 0;
        while (!feof($fp)) {
            $row = fgets($fp);
            $arr = explode("	", $row);
            if ($i > 0 && !empty($arr) && isset($arr[8]) && $arr[8] > 0) {
                $row = array(
                    "date" => date("Y-m-d", strtotime($arr[9])),
                    "product_name" => $arr[2] . "-" . $arr[4],
                    "app_id" => isset($this->app_config[$arr[17]]) ? $this->app_config[$arr[17]] : "",
                    "units" => $arr[7],
                    "money" => $arr[8],
                    "price" => $arr[15],
                    "country" => $arr[12],
                    "currency" => $arr[13],
                    "package" => $arr[17],
                    "product_id" => $arr[14]
                );
                $usd_money = $row["money"];
                if ($row["currency"] != "USD") {

                    //获取 汇率值
                    //$start_time = strtotime($row['date']);
                    $start_time = strtotime($row['date']);
                    $end_time = $start_time+86400;
                    $ratio = Db::name('purchase_exchange_rate')
                        ->where(['currency'=>$row["currency"],'update_time'=>['lt',$end_time]])
                        ->order('update_time desc')
                        ->value('exchange_rate');
                    $usd_money = $ratio>0?round($usd_money/$ratio,4):0;

                    //先从产品映射表去找
                    //$product = Db::name('purchase_product')->where(["app_id" => $row["app_id"], "product_id" => $row["product_id"]])->find();
                    //if(!empty($product))
                    //{
                    //$usd_money = $product["price"]*0.7;
                    //}else{
                    //从价格匹配表去找
                    //$usd_money =0;
                    //$res = Db::name('purchase_price')->where(["currency" => $row["currency"], "money" => $row["money"]])->find();
                    //if (!empty($res)) {
                    //$usd_money = $res["usd_money"];
                    //}
                    //}
                }
                $row["total_usd_money"] = $row["units"] * $usd_money;
                Db::name('purchase_details')->insert($row);
            }
            $i++;
        }
        fclose($fp);
        exit("ok");
    }
	
	//  base64_encode(base64_encode("hw166"))  YUhjeE5qWT0=
	
	private $allow =[
	      "hw166"=>"YUhjeE5qWT0="
	];
	
	// cp  苹果销售报告  S_D_87962023_20210201  10分钟内有效
	public function salesReports($bundleId="",$date="",$time=""){
		
		$token = request()->header('token');
		if(!$token)
		{
			echo "token error";exit;
		}
		$hw_key = base64_decode(base64_decode($token));
		
		if(!isset($this->allow[$hw_key]))
		{
			echo "token error";exit;
		}
		if($this->allow[$hw_key]!=$token)
		{
			echo "token error";exit;
		}
		//验证时间有效期
		$current = time();
		if(!preg_match('/^\d{10}/i',$time))
		{
			exit("time error");
		}
		if($time<($current-10*60))
		{
			exit("time expire");
		}
		$h = date("H");
		if(!in_array($bundleId,["idlegymsports"]))
		{
			echo "data empty";exit;
		}
		if(!$date)
		{
			if($h>17)
			{
				$date = date("Ymd", strtotime("-2 day"));
			}else{
				$date = date("Ymd", strtotime("-3 day"));
			}
		}else{
			$date = date("Ymd", strtotime($date));
		}
		$vendor ="87962023";
		$file = $_SERVER['DOCUMENT_ROOT'] . "/Reporter/S_D_{$vendor}_{$date}.txt";
		$cp_purchase_file = $_SERVER['DOCUMENT_ROOT'] . "/Reporter/S_D_{$vendor}_{$date}_cp.txt";
        if(file_exists($cp_purchase_file))
		{
			echo file_get_contents($cp_purchase_file);exit;
		}			
		if(file_exists($file))
		{
				$fp = fopen($file, "r");
				$out =[];
				$i = 0;
				$str ="";
				touch($cp_purchase_file);
				while (!feof($fp)) {
					$row = fgets($fp);
					$arr = explode("	", $row);
					if($i==0)
					{
						$str.= $row;
					}
					if( $i > 0 && !empty($arr) && isset($arr[8]) && $arr[8] > 0 && $arr[17]==$bundleId)
					{
						$str.=$row;
					}
					$i++;
				}
				fclose($fp);
				file_put_contents($cp_purchase_file,$str);
				echo file_get_contents($cp_purchase_file);exit;
		}
		echo "data empty";exit;
	}
	
}
