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
class CAmazonPricingFeedBuilder extends AAmazonFeedBuilder
{
	/**
	 * @param CObjectCollection $marketPlaceAccountHasProducts
	 * @param bool $indent
	 * @return string
	 */
	public function prepare(CObjectCollection $marketPlaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','Pricing');
		$i = 0;
		foreach ($marketPlaceAccountHasProducts as $marketPlaceAccountHasProduct)
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
		$this->rawBody = $writer->outputMemory();
		return $this;
	}

	/**
	 * @param CProduct $product
	 * @param $indent
	 * @return string
	 */
	protected function writePrice(CProduct $product, $indent = false) {
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