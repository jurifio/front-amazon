<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
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
class CAmazonRelationshipFeedBuilder extends AAmazonFeedBuilder
{

	/**
	 * @param CObjectCollection $marketplaceAccountHasProducts
	 * @param bool $indent
	 * @return $this
	 */
	public function prepare(CObjectCollection $marketplaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$i = 0;
		foreach ($marketplaceAccountHasProducts as $marketplaceAccountHasProduct)
		{
			$i++;
			$writer->startElement('Message');
			$writer->writeElement('MessageID',$i);
			$writer->writeElement('OperationType','Update');
			$writer->startElement('Relationship');
			$writer->writeElement('ParentSKU',$marketplaceAccountHasProduct->product->printId());
			foreach ($marketplaceAccountHasProduct->marketplaceAccountHasProductSku as $marketplaceAccountHasProductSku) {
				$writer->startElement('Relation');
				$writer->writeElement('SKU',$marketplaceAccountHasProductSku->productSku->getFirst()->printPublicSku());
				$writer->writeElement('Type','Variation');
				$writer->endElement();
			}
			$writer->endElement();
			$writer->endElement();
		}
		$this->rawBody = $writer->outputMemory();
		return $this;
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
}