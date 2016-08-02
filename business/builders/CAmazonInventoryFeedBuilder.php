<?php

namespace bamboo\amazon\business\builders;

use bamboo\core\application\AApplication;
use bamboo\core\base\CObjectCollection;
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
class CAmazonInventoryFeedBuilder extends AAmazonFeedBuilder
{

	public function prepare(CObjectCollection $marketPlaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$i = 0;
		foreach ($marketPlaceAccountHasProducts as $marketPlaceAccountHasProduct)
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
		$this->rawBody = $writer->outputMemory();
		return $this;
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