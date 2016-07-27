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
class CAmazonProductFeedBuilder
{
	/**
	 * @var AApplication
	 */
	protected $app;

	public function __construct(AApplication $app)
	{
		$this->app = $app;
	}

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
		$writer->setIndent($indent);
		$i = 0;
		foreach ($res as $marketPlaceAccountHasProduct) {
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Insert');
			$writer->startElement('Product');
			$this->writeProduct($marketPlaceAccountHasProduct->product,$indent);


		}
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
	protected function writeProduct(CProduct $product, $indent = false) {
		$writer = new \XMLWriter();
		$writer->setIndent($indent);

		$writer->writeElement('SKU',$product->printId());
		$writer->writeElement('ProductTaxCode','A_GEN_TAX');
		$writer->writeElement('LaunchDate',(new \DateTime())->format(DATE_ATOM));
		$writer->startElement('DescriptionData');
		$writer->writeElement('Title',$product->getName());
		$writer->writeElement('Brand',$product->productBrand->name);
		$writer->writeElement('Description',$product->getDescription());
		return "";
	}
}