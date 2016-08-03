<?php

namespace bamboo\amazon\business;

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

	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * @return string
	 */
	public function sendProducts()
	{
		$sql = "SELECT 	productId, 
						productVariantId, 
						marketplaceId,
						marketplaceAccountId 
				FROM 	MarketplaceAccountHasProduct mahp, 
						Marketplace m 
				WHERE 	m.id = mahp.marketplaceId 
					AND m.name = 'Amazon'";
		$res = $this->app->repoFactory->create('MarketplaceAccountHasProduct')->em()->findBySql($sql, []);
		foreach ($res as $re) {
			$this->prepareSkus($re);
		}

		$product = new CAmazonProductFeedBuilder($this->app);
		$this->send($product->prepare($res,true)->getRawBody());

		$inventary = new CAmazonInventoryFeedBuilder($this->app);
		$this->send($inventary->prepare($res,true)->getRawBody());

		$pricing = new CAmazonPricingFeedBuilder($this->app);
		$this->send($pricing->prepare($res,true)->getRawBody());

		$image = new CAmazonInventoryFeedBuilder($this->app);
		$this->send($image->prepare($res,true)->getRawBody());

		$relationship = new CAmazonRelationshipFeedBuilder($this->app);
		$this->send($relationship->prepare($res,true)->getRawBody());

	}

	public function send($body) {
		$x = new \XMLWriter();
		$x->openMemory();
		$x->setIndent(true);
		$x->startDocument();
		$x->startElement('AmazonEnvelope');
		$x->writeAttribute("xmlns:xsi","http://www.w3.org/2001/XMLSchema-instance");
		$x->writeAttribute("xsi:noNamespaceSchemaLocation","https://images-na.ssl-images-amazon.com/images/G/01/rainier/help/xsd/release_1_9/amzn-envelope.xsd");
		$x->startElement('Header');
		$x->writeElement('DocumentVersion','1.01');
		$x->writeElement('MerchantIdentifier','xxxxxx');
		$x->endElement();
		$x->writeRaw($body);
		$x->endElement();
		echo $x->outputMemory();
	}

	public function prepareSkus(CMarketplaceAccountHasProduct $marketplaceAccountHasProduct)
	{
		$sizesDone = [];
		foreach ($marketplaceAccountHasProduct->product->productSku as $sku) {
			$marketSku = $this->app->repoFactory->create('MarketplaceAccountHasProductSku')->getEmptyEntity();
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