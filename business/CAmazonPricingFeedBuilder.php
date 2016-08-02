<?php

namespace bamboo\amazon\business;

use bamboo\core\application\AApplication;
use bamboo\domain\entities\CProduct;

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
class CAmazonPricingFeedBuilder
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
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Update');
			$writer->startElement('Price');
			$writer->writeRaw($this->writePrice($marketPlaceAccountHasProduct->product,$indent));
			$writer->endElement();
			$writer->endElement();
		}
		return $writer->outputMemory();
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
	protected function writeProduct(CProduct $product, $indent = false) {
		$writer = new \XMLWriter();

		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$product->printId());
		$writer->startElement('StandardPrice');
		$writer->writeAttribute('currency','EUR');
		$writer->writeRaw((string) number_format($product->getDisplayPrice(),2,'.',''));
		$writer->endElement();
		/**
		 * TODO insert sale
		 * if($product->isOnSale()) {
			$writer->startElement('Sale');
		}*/

		return $writer->outputMemory();
	}
}