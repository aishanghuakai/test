<?php
namespace app\common\lib;
use \think\Db;
use \think\Request;
define('MAX_LIST_PAGE_SIZE', 50);
define('MAX_REPORT_PAGE_SIZE', 50);
  //Admob 请求类
class Admobrequest
{
    private $fileds =[
	     
		 "0"=>"date",
		 "1"=>"app_id",
		 "2"=>"country",
		 "3"=>"app_name",
		 "4"=>"app_platform",
		 "5"=>"unit_id",
		 "6"=>"request",
		 "7"=>"click",
		 "8"=>"impression",
		 "9"=>"ecpm",
		 "10"=>"revenue"
	];
	//ca-app-pub-6491984961722312  
	//坦克
	public function oldgoogelaccountdata($startDate="",$endDate="")
	{
		$client = $this->initgoogel();
		$service = new \Google_Service_AdSense($client);
		$client->refreshToken("1/A8kyM8gFVjWm3ZDykVXxVU74Hg-IRSf8LXRMIpP1Erc");
		if ($client->getAccessToken()) {
			
			 $res = $this->GenerateReport($service,"pub-6491984961722312","ca-app-pub-6491984961722312",$startDate,$endDate);
			
			 return $res;
		}
		return false;
	}
	
	public function newgoogelaccountdata($startDate="",$endDate="")
	{
		$client = $this->initgoogel();
		$service = new \Google_Service_AdSense($client);

		$client->refreshToken("1/BkpYqu7r7CdL5VbVZXcMMLLXn8opTKa381WBfQ-vgVE");
		if ($client->getAccessToken()) {
			
			 $res = $this->GenerateReport($service,"pub-9512719894815523","ca-app-pub-9512719894815523",$startDate,$endDate);
			
			 return $res;
		}
		return false;
	}
	//最新的admob 账号 2018-10-08
	public function hotgoogelaccountdata($startDate="",$endDate="")
	{
		$client = $this->initgoogel();
		$service = new \Google_Service_AdSense($client);

		$client->refreshToken("1/kdpfQZRz-PWLhi7qtVx6MnzzPfKbIHIOpMNKa7K7hY4");
		if ($client->getAccessToken()) {
			
			 $res = $this->GenerateReport($service,"pub-5470400114155059","ca-app-pub-5470400114155059",$startDate,$endDate);
			 return $res;
		}
		return false;
	}
	
	public function showoutputdata($startDate="",$endDate="")
	{
		 
		 return array_merge( $this->oldgoogelaccountdata($startDate,$endDate),
		$this->newgoogelaccountdata($startDate,$endDate) );
		
	}
	public function getdata()
	{
		
		$client = $this->initgoogel();
		
		$service = new \Google_Service_AdSense($client);

		$client->refreshToken("1/A8kyM8gFVjWm3ZDykVXxVU74Hg-IRSf8LXRMIpP1Erc");

		if ($client->getAccessToken()) {
			
		      $accounts = $this->GetAllAccounts($service, MAX_LIST_PAGE_SIZE);
  
			  if (isset($accounts) && !empty($accounts)) {
				// Get an example account ID, so we can run the following sample.
				$exampleAccountId = $accounts[0]['id'];
				
				$this->GetAccountTree($service, $exampleAccountId);
				$adClients =$this->GetAllAdClients($service, $exampleAccountId, MAX_LIST_PAGE_SIZE);
				//$result = $service->metadata_metrics->listMetadataMetrics();
				//print_r($result);exit;
				if (isset($adClients) && !empty($adClients)) {
				  // Get an ad client ID, so we can run the rest of the samples.
				  $exampleAdClient = end($adClients);
				  $exampleAdClientId = $exampleAdClient['id'];   
                  	  
				   $res = $this->GenerateReport($service, $exampleAccountId, $exampleAdClientId);	
                   print_r($res);				   
				} 
			  }
		}
	}
	
	private function initgoogel()
	{
		$client = new \Google_Client();
		$client->addScope('https://www.googleapis.com/auth/adsense.readonly');
		$client->setAccessType('offline');
        $path = $_SERVER['DOCUMENT_ROOT']."/adsense/client_secrets.json";		
		$client->setAuthConfigFile($path);
		return $client;
	}
	
	//Gets all accounts for the logged in user.
	function GetAllAccounts($service, $maxPageSize)
	{
		 $optParams['maxResults'] = $maxPageSize;
		 $pageToken = null;
		do {
		  $optParams['pageToken'] = $pageToken;
		  $result = $service->accounts->listAccounts($optParams);
		  $accounts = null;
		  if (!empty($result['items'])) {
			$accounts = $result['items'];
			
			if (isset($result['nextPageToken'])) {
			  $pageToken = $result['nextPageToken'];
			}
		  } else {
			 return false;
		  }
		} while ($pageToken);
      return $accounts;
	}
	
	//Gets a specific account for the logged in user.
	function GetAccountTree($service, $accountId)
	{
		$optParams = array('tree' => true);

        $account = $service->accounts->get($accountId, $optParams);
        $this->displayTree($account, 0);
	}
	
	 private  function displayTree($parentAccount, $level) 
	 {
	   
		if (!empty($parentAccount['subAccounts'])) {
		  foreach ($subAccounts as $subAccount) {
			$this->displayTree($subAccount, $level + 1);
		  }
		}
	  }
	function GetAllAdClients($service, $accountId, $maxPageSize)
	{
		$optParams['maxResults'] = $maxPageSize;

		$pageToken = null;
		$adClients = null;
		do {
		  $optParams['pageToken'] = $pageToken;
		  $result = $service->accounts_adclients->listAccountsAdclients($accountId,
			  $optParams);
		  if (!empty($result['items'])) {
			$adClients = $result['items'];
			
			if (isset($result['nextPageToken'])) {
			  $pageToken = $result['nextPageToken'];
			}
		  } else {
			return false;
		  }
		} while ($pageToken);
		
		return $adClients;
	}

    function GenerateReport($service, $accountId, $adClientId,$startDate="",$endDate="")
	{
		if( $startDate==""  && $endDate=="" )
		{
			$startDate = 'today-7d';
            $endDate = 'today-1d';
		}		
		$optParams = array(
		  'metric' => array(
			'AD_REQUESTS', 'CLICKS','INDIVIDUAL_AD_IMPRESSIONS','INDIVIDUAL_AD_IMPRESSIONS_RPM','EARNINGS','APP_ID','COUNTRY_CODE','APP_NAME','APP_PLATFORM','AD_UNIT_ID'),
		  'dimension' =>['DATE','APP_ID','COUNTRY_CODE','APP_NAME','APP_PLATFORM','AD_UNIT_ID'],
		  'sort' => '+DATE',
		  'currency'=>'USD',
		   'filter' => array(
			'AD_CLIENT_ID==' . $adClientId
		  ) 
		);

     
		$report = $service->accounts_reports->generate($accountId, $startDate,
			$endDate, $optParams);
		
		$data =  $report->rows;
		$out_data = [];
		foreach( $data as $k=>$v )
		{
			$out_data[$k] = [ $this->fileds[0]=>$v[0],$this->fileds[1]=>$adClientId.":".$v[1],$this->fileds[2]=>$v[2],$this->fileds[3]=>$v[3],$this->fileds[4]=>$v[4],$this->fileds[5]=>$v[5],$this->fileds[6]=>$v[6],$this->fileds[7]=>$v[7],$this->fileds[8]=>$v[8],$this->fileds[9]=>$v[9],$this->fileds[10]=>$v[10] ];
		}
		return $out_data;
	}	
}
