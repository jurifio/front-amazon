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
class CAmazonPricingFeedBuilder extends AAmazonFeedBuilder
{
	protected $feedTypeName = '_POST_PRODUCT_PRICING_DATA_';

	/**
	 * @param CObjectCollection $marketPlaceAccountHasProducts
	 * @param bool $indent
	 * @return $this
	 */
	public function prepare(CObjectCollection $marketPlaceAccountHasProducts, $indent = false)
	{
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('MessageType','Price');
        $i = 0;
		$i = 0;
		foreach ($marketPlaceAccountHasProducts as $marketplaceAccountHasProduct)
		{
			foreach ($marketplaceAccountHasProduct->marketplaceAccountHasProductSku as $marketPlaceAccountHasProductSku)
			{
				$i++;
				$writer->startElement('Message');
				$writer->writeElement('MessageID',$i);
				$writer->writeElement('OperationType','Update');
				$writer->startElement('Price');
				$writer->writeRaw($this->writePrice($marketPlaceAccountHasProductSku->productSku->getFirst(),$indent));
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
	protected function writePrice(CProductSku $sku, $indent = false) {
		$writer = new \XMLWriter();

		$writer->openMemory();
		$writer->setIndent($indent);
		$writer->writeElement('SKU',$sku->productId.'-'.$sku->productVariantId.'-'.$sku->productSizeId);
		$writer->startElement('StandardPrice');
		$writer->writeAttribute('currency','EUR');
		$writer->writeRaw((string) $sku->getPrice());
		$writer->endElement();
		/**
		 * TODO insert sale
		 * if($product->isOnSale()) {
			$writer->startElement('Sale');
		}*/

		return $writer->outputMemory();
	}
}