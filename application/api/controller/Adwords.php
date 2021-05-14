<?php

namespace app\api\controller;

use think\Db;
use \think\Request;


require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/google-ads-php-8.0.0/vendor/autoload.php';
use GetOpt\GetOpt;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentNames;
use Google\Ads\GoogleAds\Examples\Utils\ArgumentParser;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsException;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V6\GoogleAdsServerStreamDecorator;
use Google\Ads\GoogleAds\V6\Errors\GoogleAdsError;
use Google\Ads\GoogleAds\V6\Services\GoogleAdsRow;
use Google\ApiCore\ApiException;


// Init PHP Sessions
session_start();

//require_once dirname($_SERVER['DOCUMENT_ROOT']). '/vendor/autoload.php';

//第三方接口调用模块
set_time_limit(0);
ini_set('memory_limit', '512M');

class Adwords
{

    private const CUSTOMER_ID = '260-084-2362';
	
	public function getCampaignReport(){
		$path = dirname($_SERVER['DOCUMENT_ROOT']) . "/public/adsapi/ads_auto/adsapi_php" . self::CUSTOMER_ID . ".ini";

        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile($path)->build();

        // Construct a Google Ads client configured from a properties file and the
        // OAuth2 credentials above.
        $googleAdsClient = (new GoogleAdsClientBuilder())
            ->fromFile($path)
            ->withOAuth2Credential($oAuth2Credential)
            ->build();

        try {
            self::runExample(
                $googleAdsClient,
				'2600842362'
            );
        } catch (GoogleAdsException $googleAdsException) {
            printf(
                "Request with ID '%s' has failed.%sGoogle Ads failure details:%s",
                $googleAdsException->getRequestId(),
                PHP_EOL,
                PHP_EOL
            );
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                /** @var GoogleAdsError $error */
                printf(
                    "\t%s: %s%s",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage(),
                    PHP_EOL
                );
            }
            exit(1);
        } catch (ApiException $apiException) {
            printf(
                "ApiException was thrown with message '%s'.%s",
                $apiException->getMessage(),
                PHP_EOL
            );
            exit(1);
        }
	}
	
	 public static function runExample(GoogleAdsClient $googleAdsClient, int $customerId)
    {
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();
        // Creates a query that retrieves all campaigns.
        $query =
            "SELECT campaign.id, "
                . "campaign.name, "
				. "customer.id, "
                . "segments.date, "
                . "metrics.impressions, "
                . "metrics.clicks, "
				. "location_view.resource_name, "
                . "metrics.cost_micros "
            . "FROM location_view "
            . "WHERE segments.date BETWEEN '2021-05-07' AND '2021-05-07' "
                . "AND campaign.status IN ('ENABLED','PAUSED') "
            . "ORDER BY segments.date DESC";
        // Issues a search stream request.
        /** @var GoogleAdsServerStreamDecorator $stream */
        $response =
            $googleAdsServiceClient->searchStream($customerId, $query);

        // Iterates over all rows in all messages and prints the requested field values for
        // the campaign in each row.
		$csvRows = [];
        foreach ($response->iterateAllElements() as $googleAdsRow) {
            /** @var GoogleAdsRow $googleAdsRow */
            $csvRows[] = [
                'campaign.id' => $googleAdsRow->getCampaign()->getId(),
                'campaign.name' => $googleAdsRow->getCampaign()->getName(),
				'customer.id' => $googleAdsRow->getCustomer()->getId(),
                'segments.date' => $googleAdsRow->getSegments()->getDate(),
                'metrics.impressions' => $googleAdsRow->getMetrics()->getImpressions(),
                'metrics.clicks' => $googleAdsRow->getMetrics()->getClicks(),
                'metrics.cost_micros' => $googleAdsRow->getMetrics()->getCostMicros()
            ];
        }
        print_r($csvRows);exit;
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

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    function moneyInDollars($money)
    {
        return round(($money / 1000000.00), 2);
    }
}
 