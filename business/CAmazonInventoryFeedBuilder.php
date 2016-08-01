<?php

namespace bamboo\amazon\business;

use bamboo\core\application\AApplication;
use bamboo\domain\entities\CProduct;
use bamboo\domain\entities\CProductSku;

/**
 * Class CAmazonProductFeedBuilder
 * @package bamboo\amazon\business
 *
 * @author Bambooshoot Team <emanuele@bambooshoot.agency>
 *
 * @copyright (c) Bambooshoot snc - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @date 27/07/2016
 * @since 1.0
 */
class CAmazonInventoryFeedBuilder
{
	/**
	 * @var AApplication
	 */
	protected $app;

	/**
	 * CAmazonProductFeedBuilder constructor.
	 * @param AApplication $app
	 */
	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

	/**
	 * @param bool $indent
	 * @return string
	 */
	public function prepare($indent = false)
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

		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$i = 0;
		foreach ($res as $marketPlaceAccountHasProduct)
		{
			foreach ($marketPlaceAccountHasProduct->product->productSku as $sku) {
				$i++;
				$writer->startElement('Message');
				$writer->writeElement('MessageID',$i);
				$writer->writeElement('OperationType','Update');
				$writer->startElement('Inventory');
				$writer->writeRaw($this->writeProductSku($marketPlaceAccountHasProduct->product,$indent));
				$writer->endElement();
				$writer->endElement();
			}
		}
		return $writer->outputMemory();
	}

	/**
	 * @param CProductSku $sku
	 * @param bool $indent
	 * @return string
	 */
	protected function writeProductSku(CProductSku $sku, $indent = false) {
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$sku->printPublicSku());
		$writer->writeElement('FulfillmentCenterID','DEFAULT');
		$writer->writeElement('Quantity',$sku->stockQty);
		$writer->endElement();

		return $writer->outputMemory();
	}
}