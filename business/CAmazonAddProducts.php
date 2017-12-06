<?php

namespace bamboo\amazon\business;

use bamboo\amazon\business\builders\AAmazonFeedBuilder;
use bamboo\amazon\business\builders\CAmazonImageFeedBuilder;
use bamboo\amazon\business\builders\CAmazonInventoryFeedBuilder;
use bamboo\amazon\business\builders\CAmazonPricingFeedBuilder;
use bamboo\amazon\business\builders\CAmazonProductFeedBuilder;
use bamboo\amazon\business\builders\CAmazonRelationshipFeedBuilder;
use bamboo\core\application\AApplication;
use bamboo\domain\entities\CMarketplaceAccountHasProduct;

/**
 * Class CAmazonImageFeedBuilder
 * @package bamboo\amazon\business
 *
 * @author Bambooshoot Team <emanuele@bambooshoot.agency>
 *
 * @copyright (c) Bambooshoot snc - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @date 02/08/2016
 * @since 1.0
 */
class CAmazonAddProducts
{
	/**
	 * @var AApplication
	 */
	protected $app;

	protected $marketplaceWebServiceClient;

	public function __construct(AApplication $app)
	{
		$this->app = $app;
		$this->app->vendorLibraries->load('amazonMWS');
	}

	/**
	 * @return string
	 */
	public function sendProducts()
	{
		$sql = "SELECT 	marketplaceAccountId as id,
						marketplaceId
				FROM 	MarketplaceAccountHasProduct mahp, 
						Marketplace m 
				WHERE 	m.id = mahp.marketplaceId 
					AND m.name = 'Amazon' and mahp.isToWork = 1 
					GROUP BY marketplaceId,
					marketplaceAccountId";

		$marketplaceAccounts = \Monkey::app()->repoFactory->create('MarketplaceAccount')->em()->findBySql($sql, []);

		foreach ($marketplaceAccounts as $marketplaceAccount) {
			try {
				$sql = "SELECT 	productId, 
						productVariantId, 
						marketplaceId,
						marketplaceAccountId 
				FROM 	MarketplaceAccountHasProduct mahp, 
						Marketplace m 
				WHERE 	m.id = mahp.marketplaceId 
					AND m.name = 'Amazon' and mahp.isToWork = 1 and mahp.marketplaceAccountId = ?";
				$res = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProduct')->em()->findBySql($sql, [$marketplaceAccount->id]);

				foreach ($res as $re) {
					$this->prepareSkus($re);
				}

				$product = new CAmazonProductFeedBuilder($this->app);
				$this->prepareAndSend($marketplaceAccount,$product,$res);

				$inventary = new CAmazonInventoryFeedBuilder($this->app);
				$this->prepareAndSend($marketplaceAccount,$inventary,$res);

				$pricing = new CAmazonPricingFeedBuilder($this->app);
				$this->prepareAndSend($marketplaceAccount,$pricing,$res);

				$relationship = new CAmazonRelationshipFeedBuilder($this->app);
				$this->prepareAndSend($marketplaceAccount,$relationship,$res);

                $image = new CAmazonImageFeedBuilder($this->app);
                $this->prepareAndSend($marketplaceAccount,$image,$res);

            } catch (\Throwable $e) {
				die(var_dump($e));
			}
		}
	}

	protected function prepareAndSend($marketplaceAccount, AAmazonFeedBuilder $builder,$products) {

		$service = new \MarketplaceWebService_Client(
			$marketplaceAccount->config['awsAccessKeyId'],
			$marketplaceAccount->config['awsSecretAccessKey'],
			["ServiceURL"=>$marketplaceAccount->config['serviceUrl'],],
			"BlueSeal",
			"1.01");

		$content = $builder->prepare($products,false)->getRawBody();

		$x = new \XMLWriter();
		$x->openMemory();
		$x->setIndent(false);
		$x->startDocument();
		$x->startElement('AmazonEnvelope');
		$x->writeAttribute("xsi:noNamespaceSchemaLocation","amzn-envelope.xsd");
		$x->writeAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
		$x->startElement('Header');
		$x->writeElement('DocumentVersion','1.01');
		$x->writeElement('MerchantIdentifier',$marketplaceAccount->config['merchantIdentifier']);
		$x->endElement();
		$x->writeRaw($content);
		$x->endElement();
		$x->endDocument();
		$content = $x->outputMemory();
		echo $content;
		$feedHandle = @fopen('php://temp', 'rw+');
		fwrite($feedHandle, $content);
		rewind($feedHandle);
		$parameters = array (
			'Merchant' => $marketplaceAccount->config['merchantIdentifier'],
			'MarketplaceIdList' => ["Id" => $marketplaceAccount->config['marketplaceIdList']],
			'FeedType' => $builder->getFeedTypeName(),
			'FeedContent' => $feedHandle,
			'PurgeAndReplace' => false,
			'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true))
		);
		if(isset($marketplaceAccount->config['MWSAuthToken']) && !empty($marketplaceAccount->config['MWSAuthToken'])){
			$parameters['MWSAuthToken'] = $marketplaceAccount->config['MWSAuthToken']; // Optional]
		}
		rewind($feedHandle);
		$request = new \MarketplaceWebService_Model_SubmitFeedRequest($parameters);

		try {
			$response = $service->submitFeed($request);

			echo ("Service Response\n");
			echo ("=============================================================================\n");

			echo("        SubmitFeedResponse\n");
			if ($response->isSetSubmitFeedResult()) {
				echo("            SubmitFeedResult\n");
				$submitFeedResult = $response->getSubmitFeedResult();
				if ($submitFeedResult->isSetFeedSubmissionInfo()) {
					echo("                FeedSubmissionInfo\n");
					$feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
					if ($feedSubmissionInfo->isSetFeedSubmissionId())
					{
						echo("                    FeedSubmissionId\n");
						echo("                        " . $feedSubmissionInfo->getFeedSubmissionId() . "\n");
					}
					if ($feedSubmissionInfo->isSetFeedType())
					{
						echo("                    FeedType\n");
						echo("                        " . $feedSubmissionInfo->getFeedType() . "\n");
					}
					if ($feedSubmissionInfo->isSetSubmittedDate())
					{
						echo("                    SubmittedDate\n");
						echo("                        " . $feedSubmissionInfo->getSubmittedDate()->format(DATE_FORMAT) . "\n");
					}
					if ($feedSubmissionInfo->isSetFeedProcessingStatus())
					{
						echo("                    FeedProcessingStatus\n");
						echo("                        " . $feedSubmissionInfo->getFeedProcessingStatus() . "\n");
					}
					if ($feedSubmissionInfo->isSetStartedProcessingDate())
					{
						echo("                    StartedProcessingDate\n");
						echo("                        " . $feedSubmissionInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\n");
					}
					if ($feedSubmissionInfo->isSetCompletedProcessingDate())
					{
						echo("                    CompletedProcessingDate\n");
						echo("                        " . $feedSubmissionInfo->getCompletedProcessingDate()->format(DATE_FORMAT) . "\n");
					}
				}
			}
			if ($response->isSetResponseMetadata()) {
				echo("            ResponseMetadata\n");
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId())
				{
					echo("                RequestId\n");
					echo("                    " . $responseMetadata->getRequestId() . "\n");
				}
			}

			echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\n");
		} catch (\MarketplaceWebService_Exception $ex) {
			echo("Caught Exception: " . $ex->getMessage() . "\n");
			echo("Response Status Code: " . $ex->getStatusCode() . "\n");
			echo("Error Code: " . $ex->getErrorCode() . "\n");
			echo("Error Type: " . $ex->getErrorType() . "\n");
			echo("Request ID: " . $ex->getRequestId() . "\n");
			echo("XML: " . $ex->getXML() . "\n");
			echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
			fclose($feedHandle);
			return false;
		}
		fclose($feedHandle);
		return true;
	}

	public function prepareSkus(CMarketplaceAccountHasProduct $marketplaceAccountHasProduct)
	{
		$sizesDone = [];
		foreach ($marketplaceAccountHasProduct->product->productSku as $sku) {
			if(empty($sku->ean)) {
				\Monkey::app()->repoFactory->create('ProductSku')->assignNewEan($sku);
			}
			$marketSku = \Monkey::app()->repoFactory->create('MarketplaceAccountHasProductSku')->getEmptyEntity();
			$marketSku->productSizeId = $sku->productSizeId;
			$marketSku->productId = $sku->productId;
			$marketSku->productVariantId = $sku->productVariantId;
			$marketSku->marketplaceId = $marketplaceAccountHasProduct->marketplaceId;
			$marketSku->marketplaceAccountId = $marketplaceAccountHasProduct->marketplaceAccountId;
			$existingMarket = $marketSku->em()->findOneBy($marketSku->getIds());
			if (is_null($existingMarket)) {
				$sizesDone[$sku->productSizeId] = $sku->stockQty;
				$marketSku->insert();
			} else {
				//$sizesDone[$sku->productSizeId] += $sku->stockQty;
				//$existingMarket->update();
			}
		}
	}
}